<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_one_id', 'user_two_id', 'last_message_at'])]
class Conversation extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    /**
     * The first participant.
     */
    public function userOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    /**
     * The second participant.
     */
    public function userTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    /**
     * All messages in this conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * The latest message.
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * Get the other user in the conversation relative to the given user.
     */
    public function otherUser(int|User $user): User
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->user_one_id === $userId ? $this->userTwo : $this->userOne;
    }

    /**
     * Count unread messages for a given user.
     */
    public function unreadCount(int $userId): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Find or create a conversation between two users with normalized ordering.
     */
    public static function findOrCreateBetween(int $userA, int $userB): self
    {
        $userOneId = min($userA, $userB);
        $userTwoId = max($userA, $userB);

        return static::firstOrCreate([
            'user_one_id' => $userOneId,
            'user_two_id' => $userTwoId,
        ]);
    }
}
