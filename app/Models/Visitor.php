<?php

namespace App\Models;

use App\Models\Concerns\SetsCreatedAt;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Visitor extends Model
{
    use HasFactory, SetsCreatedAt;

    public $timestamps = false;

    protected $fillable = [
        'event_id',
        'source',
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

    /**
     * Erase this visitor's personal data (visitor asked to be deleted).
     *
     * Kept on purpose: the row itself (so event counts stay right), the feedback
     * score, and the consent records, now marked revoked. A consent record is the
     * proof that texting was lawful, so it outlives the person's details.
     * The dashboard and CSV already treat anonymized_at as "details removed".
     */
    public function anonymize(): void
    {
        DB::transaction(function () {
            $this->shortLinks()->delete();

            $this->messages()->where('direction', 'out')->update(['to_addr' => 'deleted', 'body' => '[deleted]', 'error' => null]);
            $this->messages()->where('direction', 'in')->update(['from_addr' => 'deleted', 'body' => '[deleted]', 'error' => null]);

            $this->consents()->whereNull('revoked_at')->update(['revoked_at' => now()]);

            $this->forceFill([
                'full_name' => 'Deleted',
                'phone_e164' => 'deleted-'.$this->id,
                'email' => null,
                'working_with_agent' => null,
                'buying_timeline' => null,
                'pre_approved' => null,
                'feedback_notes' => null,
                'ip_address' => null,
                'user_agent' => null,
                'sms_opt_out' => true,
                'anonymized_at' => now(),
            ])->save();
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class);
    }

    public function shortLinks(): HasMany
    {
        return $this->hasMany(ShortLink::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
