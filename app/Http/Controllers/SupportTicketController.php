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
            ->where('user_id', Auth::id())
            ->groupBy('ticket_number')
            ->orderByDesc('id')
            ->paginate(10);

        return view('merchant.support-ticket.index', compact('tickets'));
    }


    public function show($id)
    {
        $ticket = SupportTicket::where('user_id', Auth::id())->findOrFail($id);

        $activities = SupportTicket::where('ticket_number', $ticket->ticket_number)
            ->orderBy('created_at')
            ->get();

        $activityData = collect();

        foreach ($activities as $activity) {
            $activityData->push([
                'type' => $activity->user_id == Auth::id(),
                'message' => $activity->details,
                'subject' => $activity->subject,
                'created_at' => $activity->created_at,
                'files' => $activity->files ?? [],
                'reply' => $activity->reply,
            ]);
        }

        return view('merchant.support-ticket.show', compact('ticket', 'activityData'));
    }




    public function create()
    {
        return view('merchant.support-ticket.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'details' => 'required|string|max:700',
            'files' => 'nullable|array',
        ]);

        $ticket = SupportTicket::create([
            'user_id' => Auth::id(),
            'ticket_number' => 'TKT-' . now()->format('Ymd') . '-' . rand(1000, 9999),
            'subject' => $validated['subject'],
            'details' => $validated['details'],
            'files' => $validated['files'] ?? [],
        ]);

        return redirect()->route('support-ticket.index')->with('success', 'Ticket created successfully!');
    }

    public function reply(Request $request, $ticket_id)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'details' => 'required|string|max:700',
            'files' => 'nullable|array',
        ]);

        $ticket = SupportTicket::where('user_id', Auth::id())->findOrFail($ticket_id);

        $newTicket = SupportTicket::create([
            'user_id' => Auth::id(),
            'ticket_number' => $ticket->ticket_number,
            'subject' => $validated['subject'],
            'details' => $validated['details'],
            'files' => $validated['files'] ?? [],
        ]);

        return redirect()->route('support-ticket.show', $ticket->id)
            ->with('success', 'Your reply has been submitted successfully!');
    }
}
