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

        return redirect()->route('showTickets', $ticket->id)
            ->with('success', 'Your reply has been submitted successfully!');
    }
}
