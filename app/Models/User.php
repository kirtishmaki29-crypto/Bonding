<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'google_id', 'avatar', 'headline', 'is_online', 'last_active_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_online' => 'boolean',
            'last_active_at' => 'datetime',
        ];
    }

    /**
     * Return user's avatar or generate an aesthetic celestial SVG fallback.
     */
    public function avatarUrl(): string
    {
        if (! empty($this->avatar)) {
            return $this->avatar;
        }

        $initial = mb_strtoupper(mb_substr($this->name ?? 'U', 0, 1));
        $colors = ['#1e3a8a', '#1e1b4b', '#0f766e', '#312e81', '#3730a3', '#164e63'];
        $bgColor = $colors[abs(crc32($this->name ?? 'Bonding')) % count($colors)];

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128">'
            .'<defs><radialGradient id="g" cx="30%" cy="30%" r="70%"><stop offset="0%" stop-color="#38bdf8"/>'
            .'<stop offset="100%" stop-color="'.$bgColor.'"/></radialGradient></defs>'
            .'<circle cx="64" cy="64" r="64" fill="url(#g)"/>'
            .'<circle cx="36" cy="36" r="3" fill="#ffffff" opacity="0.6"/>'
            .'<circle cx="94" cy="42" r="2" fill="#ffffff" opacity="0.4"/>'
            .'<text x="50%" y="54%" font-family="system-ui, -apple-system, sans-serif" font-size="52" '
            .'font-weight="bold" fill="#f8fafc" text-anchor="middle" dominant-baseline="middle">'
            .htmlspecialchars($initial, ENT_QUOTES, 'UTF-8').'</text></svg>';

        return 'data:image/svg+xml;utf8,'.rawurlencode($svg);
    }

    /**
     * Sent messages.
     */
    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }
}
