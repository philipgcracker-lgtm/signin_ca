<?php

namespace App\Models;

use App\Models\Concerns\SetsCreatedAt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Every outbound and inbound message. `updated_at` is used as the claim marker
 * by MessageSender: it is NULL while a queued message is unclaimed.
 */
class Message extends Model
{
    use SetsCreatedAt;

    public $timestamps = false;

    protected $fillable = [
        'visitor_id',
        'event_id',
        'direction',
        'channel',
        'kind',
        'to_addr',
        'from_addr',
        'body',
        'provider',
        'provider_message_id',
        'status',
        'cost_cents',
        'error',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }
}
