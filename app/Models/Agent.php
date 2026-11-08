<?php

namespace App\Models;

use App\Models\Concerns\SetsCreatedAt;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Agent extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\AgentFactory> */
    use HasFactory, Notifiable, SetsCreatedAt, SoftDeletes;

    /**
     * The PRD schema has created_at (DB default, set from PHP by SetsCreatedAt) but no updated_at.
     */
    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'full_name',
        'email',
        'password_hash',
        'phone',
        'brokerage_name',
        'headshot_url',
        'google_review_url',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
    ];

    protected static function booted(): void
    {
        static::creating(function (Agent $agent) {
            $agent->uuid ??= (string) Str::uuid();
        });
    }

    /** Only approved agents can create open houses (set by the owner, never by the agent). */
    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin && ! $this->isSuspended();
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    /** Approved and not suspended. */
    public function canCreateEvents(): bool
    {
        return $this->isApproved() && ! $this->isSuspended();
    }

    public function isPro(): bool
    {
        return $this->subscription_tier === 'pro';
    }

    public function events():\Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * The password column is password_hash per the PRD schema.
     */
    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    /**
     * The PRD schema has no remember_token column; disable "remember me" persistence.
     */
    public function getRememberTokenName(): string
    {
        return '';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password_hash' => 'hashed',
            'approved_at' => 'datetime',
            'suspended_at' => 'datetime',
            'comped_at' => 'datetime',
            'is_admin' => 'boolean',
            'created_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }
}
