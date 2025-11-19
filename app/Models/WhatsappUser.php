<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class WhatsappUser extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'phone_number',
        'name',
        'profile_picture',
        'is_active',
        'last_interaction_at',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'last_interaction_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the guard name for this model.
     */
    protected string $guard_name = 'whatsapp';

    /**
     * Get the conversations for the WhatsApp user.
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Get the active conversation for the WhatsApp user.
     */
    public function activeConversation()
    {
        return $this->conversations()->active()->latest('last_message_at')->first();
    }

    /**
     * Get or create an active conversation for the WhatsApp user.
     */
    public function getOrCreateActiveConversation(): Conversation
    {
        $conversation = $this->activeConversation();

        if (!$conversation) {
            $conversation = $this->conversations()->create([
                'status' => 'active',
                'last_message_at' => now(),
            ]);
        }

        return $conversation;
    }
}
