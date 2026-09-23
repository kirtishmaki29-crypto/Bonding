<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ChatController extends Controller
{
    /**
     * Display the main chat application.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // Update active heartbeat
        $user->update([
            'is_online' => true,
            'last_active_at' => Carbon::now(),
        ]);

        return view('chat.index', [
            'currentUser' => $user,
            'initialConversationId' => $request->query('conversation'),
        ]);
    }

    /**
     * Fetch list of conversations for current authenticated user.
     */
    public function getConversations(): JsonResponse
    {
        $userId = Auth::id();

        $conversations = Conversation::where('user_one_id', $userId)
            ->orWhere('user_two_id', $userId)
            ->with(['userOne', 'userTwo', 'latestMessage'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (Conversation $conv) use ($userId) {
                $otherUser = $conv->otherUser($userId);
                $latest = $conv->latestMessage;

                return [
                    'id' => $conv->id,
                    'other_user' => [
                        'id' => $otherUser->id,
                        'name' => $otherUser->name,
                        'avatar' => $otherUser->avatarUrl(),
                        'headline' => $otherUser->headline ?? 'Quiet soul under the stars',
                        'is_online' => (bool) $otherUser->is_online,
                        'last_active' => $otherUser->last_active_at ? $otherUser->last_active_at->diffForHumans() : 'Recently',
                    ],
                    'latest_message' => $latest ? [
                        'body' => $latest->body,
                        'time' => $latest->created_at->diffForHumans(null, true, true),
                        'is_sender' => $latest->sender_id === $userId,
                        'is_read' => (bool) $latest->is_read,
                    ] : null,
                    'unread_count' => $conv->unreadCount($userId),
                    'last_activity' => $conv->last_message_at ? $conv->last_message_at->toIso8601String() : $conv->updated_at->toIso8601String(),
                ];
            });

        return response()->json([
            'conversations' => $conversations,
        ]);
    }

    /**
     * Discover other users to connect with.
     */
    public function getUsers(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $query = $request->query('q');

        $usersQuery = User::where('id', '!=', $userId);

        if (! empty($query)) {
            $usersQuery->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('headline', 'like', "%{$query}%");
            });
        }

        $users = $usersQuery->orderByDesc('is_online')
            ->orderBy('name')
            ->limit(30)
            ->get()
            ->map(function (User $user) use ($userId) {
                $existingConv = Conversation::where(function ($q) use ($userId, $user) {
                    $q->where('user_one_id', min($userId, $user->id))
                        ->where('user_two_id', max($userId, $user->id));
                })->first();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar' => $user->avatarUrl(),
                    'headline' => $user->headline ?? 'Quiet soul under the stars',
                    'is_online' => (bool) $user->is_online,
                    'last_active' => $user->last_active_at ? $user->last_active_at->diffForHumans() : 'Recently',
                    'conversation_id' => $existingConv ? $existingConv->id : null,
                ];
            });

        return response()->json([
            'users' => $users,
        ]);
    }

    /**
     * Get messages for a given conversation with strict authorization.
     */
    public function getMessages(Conversation $conversation): JsonResponse
    {
        $userId = Auth::id();

        // Strict authorization check: must be a conversation participant
        if ($conversation->user_one_id !== $userId && $conversation->user_two_id !== $userId) {
            return response()->json(['error' => 'Unauthorized conversation access.'], 403);
        }

        // Mark incoming messages as read
        $conversation->messages()
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => Carbon::now(),
            ]);

        $otherUser = $conversation->otherUser($userId);

        $messages = $conversation->messages()
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function (Message $msg) use ($userId) {
                return [
                    'id' => $msg->id,
                    'body' => $msg->body,
                    'is_sender' => $msg->sender_id === $userId,
                    'created_at' => $msg->created_at->format('h:i A'),
                    'full_date' => $msg->created_at->format('M j, Y'),
                    'is_read' => (bool) $msg->is_read,
                ];
            });

        return response()->json([
            'conversation_id' => $conversation->id,
            'other_user' => [
                'id' => $otherUser->id,
                'name' => $otherUser->name,
                'avatar' => $otherUser->avatarUrl(),
                'headline' => $otherUser->headline ?? 'Quiet soul under the stars',
                'is_online' => (bool) $otherUser->is_online,
                'last_active' => $otherUser->last_active_at ? $otherUser->last_active_at->diffForHumans() : 'Recently',
            ],
            'messages' => $messages,
        ]);
    }

    /**
     * Send a new message in a conversation.
     */
    public function sendMessage(Request $request, Conversation $conversation): JsonResponse
    {
        $userId = Auth::id();

        // Strict authorization check
        if ($conversation->user_one_id !== $userId && $conversation->user_two_id !== $userId) {
            return response()->json(['error' => 'Unauthorized conversation access.'], 403);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $userId,
            'body' => trim($validated['body']),
            'is_read' => false,
        ]);

        $conversation->update([
            'last_message_at' => $message->created_at,
        ]);

        return response()->json([
            'message' => [
                'id' => $message->id,
                'body' => $message->body,
                'is_sender' => true,
                'created_at' => $message->created_at->format('h:i A'),
                'full_date' => $message->created_at->format('M j, Y'),
                'is_read' => false,
            ],
        ]);
    }

    /**
     * Start or locate a conversation with another user.
     */
    public function startConversation(User $user): JsonResponse
    {
        $currentUserId = Auth::id();

        if ($user->id === $currentUserId) {
            return response()->json(['error' => 'Cannot create conversation with yourself.'], 422);
        }

        $conversation = Conversation::findOrCreateBetween($currentUserId, $user->id);

        return response()->json([
            'success' => true,
            'conversation_id' => $conversation->id,
        ]);
    }

    /**
     * Update current user profile headline / bio.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'headline' => ['nullable', 'string', 'max:160'],
        ]);

        $user = Auth::user();
        $user->update([
            'headline' => $validated['headline'],
        ]);

        return response()->json([
            'success' => true,
            'headline' => $user->headline,
        ]);
    }
}
