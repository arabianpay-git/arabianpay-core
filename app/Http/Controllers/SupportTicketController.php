<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupportTicketController extends Controller
{
    public function index()
    {
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

    public function reply(Request $request, $ticket_id)
    {
        $validated = $request->validate([
            'reply' => 'required|string|max:700',
        ]);

        $ticket = SupportTicket::findOrFail($ticket_id);

        $ticket->update([
            'reply' => $validated['reply'],
        ]);

        // Log the reply action
        $ticket->logModelAction(
            event: 'reply',
            description: Auth::user()->first_name . " " . Auth::user()->last_name . " replied to ticket: {$ticket->ticket_number} [{$ticket->id}]",
            properties: [
                'ip' => request()->ip(),
                'batch_uuid' => (string) \Str::uuid(),
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

        // Log the status update
        $batchUuid = (string) \Str::uuid();
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
}
