<?php

namespace App\Models;

use App\Models\Concerns\SetsCreatedAt;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SetsCreatedAt, SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'agent_id',
        'slug',
        'address_street',
        'city',
        'province',
        'postal_code',
        'price',
        'bedrooms',
        'bathrooms',
        'blurb',
        'primary_image_url',
        'listing_url',
        'seller_id',
        'event_start',
        'event_end',
        'status',
        'post_event_redirect',
        'custom_redirect_url',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'bathrooms' => 'decimal:1',
            'event_start' => 'datetime',
            'event_end' => 'datetime',
            'recap_sent_at' => 'datetime',
            'created_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function visitors(): HasMany
    {
        return $this->hasMany(Visitor::class);
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Uses the clock, not the `status` column, so the check-in page is correct
     * even if the status-transition job has not run yet.
     */
    public function hasEnded(): bool
    {
        return now()->greaterThanOrEqualTo($this->event_end);
    }

    /**
     * Event times are stored in the app timezone (UTC on the server) but people
     * read them as local clock time. v1 is Ontario-only, so display in Toronto
     * time regardless of the app or server timezone.
     */
    public const DISPLAY_TIMEZONE = 'America/Toronto';

    public function displayStart(): \Illuminate\Support\Carbon
    {
        return $this->event_start->copy()->setTimezone(self::DISPLAY_TIMEZONE);
    }

    public function displayEnd(): \Illuminate\Support\Carbon
    {
        return $this->event_end->copy()->setTimezone(self::DISPLAY_TIMEZONE);
    }

    /**
     * "74 Pinehurst Drive, Welland"
     */
    public function shortAddress(): string
    {
        return $this->address_street.', '.$this->city;
    }
}
