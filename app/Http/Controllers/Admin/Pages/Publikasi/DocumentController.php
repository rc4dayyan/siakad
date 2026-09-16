<?php

namespace App\Http\Controllers\Admin\Pages\Publikasi;

use Alert;
use App\Helper\roleTrait;
use App\Http\Controllers\Controller;
use App\Models\docsResource;
use App\Models\Settings\webSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    use roleTrait;

    public function index()
    {
        $data['prefix'] = $this->setPrefix();
        $data['web'] = webSettings::where('id', 1)->first();
        $data['docs'] = docsResource::with('author')->orderBy('created_at', 'desc')->get();

        return view('user.pages.publikasi.document-index', $data);
    }

    public function create()
    {
        $data['prefix'] = $this->setPrefix();
        $data['web'] = webSettings::where('id', 1)->first();
        $data['docs'] = docsResource::latest();

        return view('user.pages.publikasi.document-create', $data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'cover' => 'required|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'name' => 'required|string|max:255',
            'source_type' => 'required|in:file,link',
            'link' => 'nullable|required_if:source_type,link|max:255|url',
            'path' => 'nullable|required_if:source_type,file|mimes:pdf|max:8192',
        ], [
            'cover.required' => 'Sampul dokumen wajib dipilih.',
            'cover.mimes' => 'Sampul harus berformat JPEG, PNG, JPG, GIF, atau SVG.',
            'cover.max' => 'Ukuran sampul maksimal 2 MB.',
            'name.required' => 'Nama dokumen wajib diisi.',
            'source_type.required' => 'Sumber dokumen wajib dipilih.',
            'link.required_if' => 'Tautan dokumen wajib diisi.',
            'link.url' => 'Tautan dokumen harus berupa URL yang valid.',
            'path.required_if' => 'File PDF wajib dipilih.',
            'path.mimes' => 'Dokumen harus berupa file PDF.',
            'path.max' => 'Ukuran dokumen maksimal 8 MB.',
        ]);

        $docs = new DocsResource;
        $docs->author_id = Auth::user()->id;
        $docs->name = $request->name;
        $docs->link = $request->link;
        $docs->code = uniqid();

        if ($request->hasFile('cover')) {
            $file = $request->file('cover');
            $fileName = uniqid().'.'.$file->getClientOriginalExtension();
            $docs->cover = $file->storeAs('images/document', $fileName, 'public');
        }
        if ($request->hasFile('path')) {
            $file = $request->file('path');
            $fileName = uniqid().'.'.$file->getClientOriginalExtension();
            $docs->path = $file->storeAs('document', $fileName, 'public');
        }

        $docs->save();

        Alert::success('Success', 'Data berhasil ditambahkan');

        return redirect()->route($this->setPrefix().'document-index');
    }

    public function destroy(Request $request, $code)
    {
        $docs = docsResource::where('code', $code)->firstOrFail();

        // Hapus dokumen (path)
        if ($docs->cover) {
            Storage::disk('public')->delete($docs->cover);
        }
        if ($docs->path) {
            Storage::disk('public')->delete($docs->path);
        }
        // Hapus entri dari database
        $docs->delete();

        // Tampilkan pesan sukses
        Alert::success('Success', 'Data berhasil dihapus');

        return back();
    }
}
