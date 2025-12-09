<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ChatMessage;
use App\Events\MessageSent;
use App\Events\TypingEvent;
use App\Events\MessagesRead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    /**
     * Return users for sidebar (AJAX).
     * Responds to: GET /admin/chat-users?search=...
     */
    public function listUsers(Request $request)
    {
        $q = $request->query('search', '');

        $users = User::where('id', '!=', Auth::id())
            ->when($q, function ($qbuilder) use ($q) {
                $qbuilder->where(function ($w) use ($q) {
                    $w->where('first_name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->whereIn('id', [847, 2, 3, 4, 5, 6, 7, 8, 9, 10])
            ->orderBy('first_name')
            ->limit(50)
            ->get();

        $result = $users->map(function ($user) {
            // last message between auth and this user
            $last = ChatMessage::where(function ($q) use ($user) {
                $q->where('sender_id', Auth::id())->where('receiver_id', $user->id);
            })->orWhere(function ($q) use ($user) {
                $q->where('sender_id', $user->id)->where('receiver_id', Auth::id());
            })->orderByDesc('created_at')->first();

            $unread = ChatMessage::where('sender_id', $user->id)
                ->where('receiver_id', Auth::id())
                ->where('is_read', false)
                ->count();

            return [
                'id' => $user->id,
                'name' => $user->first_name . ' ' . $user->last_name,
                'avatar' => $user->avatar ?? "https://ui-avatars.com/api/?name={$user->first_name}+{$user->last_name}&color=7F9CF5&background=EBF4FF",
                'last_message' => $last ? Str::limit($last->message ?? '', 60) : '',
                'last_time' => $last ? $last->created_at->diffForHumans() : '',
                'unread_count' => $unread,
                'online' => false,
            ];
        });

        return response()->json($result->values());
    }

    /**
     * Fetch messages (web route, session auth)
     * GET /admin/messages/{user}
     */
    public function fetchMessages(User $user)
    {
        $authId = Auth::id();
        $messages = ChatMessage::where(function ($q) use ($authId, $user) {
            $q->where('sender_id', $authId)->where('receiver_id', $user->id);
        })->orWhere(function ($q) use ($authId, $user) {
            $q->where('sender_id', $user->id)->where('receiver_id', $authId);
        })->orderBy('created_at', 'asc')->get();

        return response()->json($messages);
    }

    /**
     * Send message (web route form-data)
     * POST /admin/messages
     */
    public function sendMessage(Request $request)
    {
        $data = $request->validate([
            'receiver_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'message' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'max:10240'],
        ]);

        $senderId = Auth::id();
        $filePath = null;

        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('chat_files', 'public');
        }

        $message = ChatMessage::create([
            'sender_id' => $senderId,
            'receiver_id' => $data['receiver_id'],
            'message' => $data['message'] ?? null,
            'file_path' => $filePath,
            'is_read' => false,
        ]);

        // Broadcast the message to both users
        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message);
    }

    /**
     * Send typing indicator
     * POST /admin/typing
     */
    public function typing(Request $request)
    {
        $data = $request->validate([
            'receiver_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ]);

        // Broadcast typing event to the receiver
        broadcast(new TypingEvent(Auth::id(), $data['receiver_id']))->toOthers();

        return response()->json(['success' => true]);
    }

    /**
     * Stop typing indicator
     * POST /admin/typing/stop
     */
    public function stopTyping(Request $request)
    {
        $data = $request->validate([
            'receiver_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ]);

        // Broadcast stop typing event
        broadcast(new TypingEvent(Auth::id(), $data['receiver_id'], false))->toOthers();

        return response()->json(['success' => true]);
    }

    /**
     * Mark messages as read
     * POST /admin/messages/{user}/read
     */
    public function markAsRead(User $user)
    {
        $authId = Auth::id();

        // Mark all unread messages from this user as read
        $updated = ChatMessage::where('sender_id', $user->id)
            ->where('receiver_id', $authId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        if ($updated > 0) {
            // Broadcast that messages were read - notify both users
            broadcast(new MessagesRead($authId, $user->id, $updated))->toOthers();
        }

        return response()->json(['success' => true, 'updated' => $updated]);
    }
}
