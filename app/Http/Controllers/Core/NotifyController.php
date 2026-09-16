<?php

namespace App\Http\Controllers\Core;

use Alert;
use App\Helper\roleTrait;
// SECTION ADDONS SYSTEM
use App\Http\Controllers\Controller;
use App\Models\Notification;
// SECTION ADDONS EXTERNAL
use App\Models\Settings\webSettings;
use Auth;
// SECTION AUTH
use Illuminate\Http\Request;
use Str;

class NotifyController extends Controller
{
    use roleTrait;

    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:100',
            'target' => 'nullable|integer|in:0,1,2,3',
            'type' => 'nullable|string|max:255',
            'status' => 'nullable|in:read,unread',
        ]);

        $data['prefix'] = $this->setPrefix();
        $data['web'] = webSettings::where('id', 1)->first();
        $data['categories'] = Notification::query()
            ->whereNotNull('type')
            ->where('type', '!=', '')
            ->distinct()
            ->orderBy('type')
            ->pluck('type');
        $data['totalNotifications'] = Notification::query()->count();
        $data['unreadNotifications'] = Notification::query()->where('read', false)->count();
        $data['notify'] = Notification::query()
            ->with('author')
            ->when($filters['search'] ?? null, function ($query, $search): void {
                $query->where(function ($notification) use ($search): void {
                    $notification->where('name', 'like', "%{$search}%")
                        ->orWhere('desc', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%");
                });
            })
            ->when(array_key_exists('target', $filters) && $filters['target'] !== null, fn ($query) => $query->where('send_to', $filters['target']))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('read', $status === 'read'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('user.admin.system.notify-index', $data);
    }

    public function store(Request $request)
    {
        $notify = new Notification;

        $request->validate([
            'send_to' => 'required|integer|in:0,1,2,3',
            'dept_id' => 'nullable|integer',
            'user_id' => 'nullable|integer',
            'faku_id' => 'nullable|integer',
            'prodi_id' => 'nullable|integer',
            'proku_id' => 'nullable|integer',
            'class_id' => 'nullable|integer',
            'student_id' => 'nullable|integer',
            'lecture_id' => 'nullable|integer',
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'desc' => 'required|string',
        ]);

        $notify->auth_id = Auth::user()->id;
        $notify->send_to = $request->send_to;
        $notify->dept_id = $request->dept_id;
        $notify->user_id = $request->user_id;
        $notify->faku_id = $request->faku_id;
        $notify->prodi_id = $request->prodi_id;
        $notify->proku_id = $request->proku_id;
        $notify->class_id = $request->class_id;
        $notify->student_id = $request->student_id;
        $notify->lecture_id = $request->lecture_id;
        $notify->name = $request->name;
        $notify->type = $request->type;
        $notify->slug = Str::slug($request->name);
        $notify->code = Str::random(7);
        $notify->desc = $request->desc;
        $notify->save();

        Alert::success('Succcess', 'Data berhasil ditambahkan!');

        return back();

    }

    public function update(Request $request, $code)
    {
        $notify = Notification::where('code', $code)->firstOrFail();

        $request->validate([
            'send_to' => 'required|integer|in:0,1,2,3',
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'desc' => 'required|string',
        ]);

        $notify->auth_id = Auth::user()->id;
        $notify->send_to = $request->send_to;
        $notify->name = $request->name;
        $notify->type = $request->type;
        // $notify->code = Str::random(7);
        $notify->desc = $request->desc;
        $notify->save();

        Alert::success('Succcess', 'Data berhasil diupdate!');

        return back();

    }

    public function destroy($code)
    {
        $notify = Notification::where('code', $code)->firstOrFail();
        $notify->delete();

        Alert::success('Succcess', 'Data berhasil dihapus!');

        return back();
    }
}
