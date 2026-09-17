<?php

namespace App\Http\Controllers\Mahasiswa\Pages;

use Alert;
use App\Http\Controllers\Controller;
// SECTION ADDONS SYSTEM
use App\Models\Settings\webSettings;
use App\Models\TicketSupport;
// SECTION ADDONS EXTERNAL
use Auth;
// SECTION MODELS
use Illuminate\Http\Request;
use Str;

class SupportController extends Controller
{
    public function index(Request $request)
    {
        $data['web'] = webSettings::where('id', 1)->first();
        $userId = Auth::guard('mahasiswa')->user()->id;
        $filters = [
            'q' => trim((string) $request->query('q')),
            'status' => is_numeric($request->query('status')) && in_array((int) $request->query('status'), range(0, 6), true)
                ? (int) $request->query('status')
                : null,
            'priority' => is_numeric($request->query('priority')) && in_array((int) $request->query('priority'), range(0, 3), true)
                ? (int) $request->query('priority')
                : null,
            'department' => is_numeric($request->query('department')) && in_array((int) $request->query('department'), range(0, 5), true)
                ? (int) $request->query('department')
                : null,
        ];
        $baseQuery = TicketSupport::query()->whereNotNull('code')->where('users_id', $userId);
        $data['ticketSummary'] = [
            'total' => (clone $baseQuery)->count(),
            'open' => (clone $baseQuery)->whereNotIn('stat_id', [2])->count(),
            'answered' => (clone $baseQuery)->where('stat_id', 3)->count(),
            'closed' => (clone $baseQuery)->where('stat_id', 2)->count(),
        ];
        $data['ticket'] = $baseQuery
            ->when($filters['q'], fn ($query, $search) => $query->where(fn ($nested) => $nested
                ->where('code', 'like', '%'.$search.'%')
                ->orWhere('subject', 'like', '%'.$search.'%')))
            ->when($filters['status'] !== null, fn ($query) => $query->where('stat_id', $filters['status']))
            ->when($filters['priority'] !== null, fn ($query) => $query->where('prio_id', $filters['priority']))
            ->when($filters['department'] !== null, fn ($query) => $query->where('dept_id', $filters['department']))
            ->latest('updated_at')
            ->paginate(10)
            ->withQueryString();
        $data['filters'] = $filters;

        return view('mahasiswa.pages.support-ticket-index', $data);
    }

    public function open()
    {
        $data['web'] = webSettings::where('id', 1)->first();
        $userId = Auth::guard('mahasiswa')->user()->id;
        $data['ticket'] = TicketSupport::whereNotNull('code')->where('users_id', $userId)->get();

        return view('mahasiswa.pages.support-ticket-open', $data);
    }

    public function create(Request $request, $dept)
    {
        $data['dept'] = $request->dept;
        $data['web'] = webSettings::where('id', 1)->first();

        // dd($request->dept);
        return view('mahasiswa.pages.support-ticket-create', $data);
    }

    public function view(Request $request, $code)
    {
        $data['ticket'] = TicketSupport::where('code', $code)->first();
        $initialSupport = TicketSupport::where('codr', $code)->latest()->get();
        $data['support'] = $initialSupport;
        $data['web'] = webSettings::where('id', 1)->first();

        $checkStatus = TicketSupport::where('code', $code)->first();
        if ($checkStatus->raw_stat_id === 2) {
            Alert::error('Error', 'Ticket Sudah diClose');

            return back();
        } else {

            return view('mahasiswa.pages.support-ticket-view', $data);
        }
    }

    public function AjaxLastReply($code)
    {
        $latestSupport = TicketSupport::where('codr', $code)->latest()->get();
        $newData = array_diff_assoc($latestSupport->toArray(), $this->support->toArray()); // Efficient data comparison

        // Check for any changes
        if (! empty($newData)) {
            $this->support = $latestSupport; // Update controller's cached support data

            return response()->json($latestSupport);
        } else {
            return response()->json(null); // No changes, send empty response to avoid unnecessary updates
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'dept_id' => 'required|integer',
            'prio_id' => 'required|integer',
            'subject' => 'required|string',
            'message' => 'required|string',
        ]);

        $ticket = new TicketSupport;
        $ticket->code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $ticket->core = Str::random(6);
        $ticket->prio_id = $request->prio_id;
        $ticket->dept_id = $request->dept_id;
        $ticket->sent_to = $request->dept_id;
        $ticket->users_id = Auth::guard('mahasiswa')->user()->id;
        $ticket->subject = $request->subject;
        $ticket->message = $request->message;
        $ticket->save();

        Alert::success('Berhasil', 'Ticket telah berhasil dibuat!');

        return redirect()->route('mahasiswa.support.ticket-index');
    }

    public function storeReply(Request $request, $code)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        // UPDATE TICKET
        $ticket = TicketSupport::where('code', $code)->first();
        $ticket->stat_id = 4;
        $ticket->updated_at = now();
        $ticket->save();

        $rticket = new TicketSupport;
        $rticket->codr = $code;
        $rticket->core = Str::random(6);
        $rticket->prio_id = $ticket->raw_prio_id;
        $rticket->dept_id = $ticket->raw_dept_id;
        $rticket->sent_to = $ticket->raw_dept_id;
        $rticket->users_id = Auth::guard('mahasiswa')->user()->id;
        $rticket->subject = $request->subject;
        $rticket->message = $request->message;
        $rticket->save();

        Alert::success('Berhasil', 'Ticket telah berhasil dibuat!');

        return back();
    }
}
