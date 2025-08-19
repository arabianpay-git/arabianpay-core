<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SupportTicketController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $tickets = SupportTicket::selectRaw('MAX(id) as id, ticket_number, MAX(user_id) as user_id, MAX(subject) as subject, MAX(details) as details, MAX(files) as files, MAX(reply) as reply, MAX(status) as status, MAX(created_at) as created_at')
            ->groupBy('ticket_number')
            ->orderByDesc('id')
            ->paginate(10);

        return view('admin.support-ticket.index', compact('tickets'));
    }

    public function show($id)
    {
        $ticket = SupportTicket::findOrFail($id);

        $activities = SupportTicket::where('ticket_number', $ticket->ticket_number)
            ->orderBy('created_at')
            ->get();

        $activityData = collect();

        foreach ($activities as $activity) {
            $activityData->push([
                'id'    => $activity->id,
                'type' => $activity->user_id == Auth::id(),
                'message' => $activity->details,
                'subject' => $activity->subject,
                'created_at' => $activity->created_at,
                'files' => $activity->files ?? [],
                'reply' => $activity->reply,
            ]);
        }

        return view('admin.support-ticket.show', compact('ticket', 'activityData'));
    }

    public function create()
    {
        return view('admin.support-ticket.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'details' => 'required|string|max:700',
            'files' => 'nullable|array',
        ]);

        $ticket = SupportTicket::create([
            'assigned_to' => $request->assigned_to,
            'user_id' => Auth::id(),
            'ticket_number' => 'TKT-' . now()->format('Ymd') . '-' . rand(1000, 9999),
            'subject' => $validated['subject'],
            'details' => $validated['details'],
            'files' => $validated['files'] ?? [],
        ]);

        return redirect()->route('tickets')->with('success', 'Ticket created successfully!');
    }

    public function reply(Request $request, $ticket_id)
    {
        $validated = $request->validate([
            'reply' => 'required|string|max:700',
        ]);

        $ticket = SupportTicket::findOrFail($ticket_id);

        $ticket->update([
            'reply' => $validated['reply'],
        ]);

        $ticket->logModelAction(
            event: 'reply',
            description: Auth::user()->first_name . " " . Auth::user()->last_name . " replied to ticket: {$ticket->ticket_number} [{$ticket->id}]",
            properties: [
                'ip' => request()->ip(),
                'batch_uuid' => (string) Str::uuid(),
            ]
        );

        return redirect()->route('showTickets', $ticket->id)
            ->with('success', 'Your reply has been submitted successfully!');
    }

    public function updateStatus(Request $request, $ticket_number)
    {
        $request->validate([
            'status' => 'required|in:active,solved,draft,canceled',
        ]);

        $tickets = SupportTicket::where('ticket_number', $ticket_number)->get();

        $ticket = $tickets->first();

        if (!$ticket || ($ticket->user_id !== Auth::id() && Auth::user()->user_type !== 'admin')) {
            return redirect()->back()->with('error', 'You are not authorized to update this ticket status.');
        }

        $batchUuid = (string) Str::uuid();

        foreach ($tickets as $ticket) {
            $ticket->status = $request->status;
            $ticket->save();

            $ticket->logModelAction(
                event: 'status_update',
                description: Auth::user()->first_name . " " . Auth::user()->last_name . " updated ticket status: {$ticket->ticket_number} [{$ticket->id}] to {$request->status}",
                properties: [
                    'ip' => request()->ip(),
                    'batch_uuid' => $batchUuid,
                ]
            );
        }

        return redirect()->back()->with('success', 'Ticket status updated everywhere successfully.');
    }

    public function internelTickets()
    {
        $tickets = SupportTicket::selectRaw('
            MAX(id) as id,
            ticket_number,
            MAX(user_id) as user_id,
            MAX(assigned_to) as assigned_to,
            MAX(subject) as subject,
            MAX(details) as details,
            MAX(files) as files,
            MAX(reply) as reply,
            MAX(status) as status,
            MAX(created_at) as created_at
        ')
            ->where(function ($query) {
                $query->where('assigned_to', Auth::id())
                    ->orWhere('user_id', Auth::id());
            })
            ->with(['assigned', 'user'])
            ->groupBy('ticket_number')
            ->orderByDesc('id')
            ->paginate(10);

        return view('admin.support-ticket.index', compact('tickets'));
    }
}
