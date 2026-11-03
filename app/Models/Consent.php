<?php

namespace App\Models;

use App\Models\Concerns\SetsCreatedAt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per consent decision: who consented, to which wording version, when,
 * from where (IP, user agent) and on behalf of which agent. The `consents`
 * table has no event_id column in the PRD schema; the event is reached through
 * the visitor.
 */
class Consent extends Model
{
    use SetsCreatedAt;

    public $timestamps = false;

    protected $fillable = [
        'visitor_id',
        'agent_id',
        'consent_text_id',
        'channel',
        'purpose',
        'granted',
        'ip_address',
        'user_agent',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'granted' => 'boolean',
            'created_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function consentText(): BelongsTo
    {
        return $this->belongsTo(ConsentText::class);
    }
}
