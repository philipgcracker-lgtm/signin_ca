<?php

namespace App\Models;

use App\Models\Concerns\SetsCreatedAt;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visitor extends Model
{
    use HasFactory, SetsCreatedAt;

    public $timestamps = false;

    protected $fillable = [
        'event_id',
        'full_name',
        'phone_e164',
        'email',
        'working_with_agent',
        'buying_timeline',
        'pre_approved',
        'ip_address',
        'user_agent',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'working_with_agent' => 'boolean',
            'pre_approved' => 'boolean',
            'sms_opt_out' => 'boolean',
            'feedback_at' => 'datetime',
            'anonymized_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
