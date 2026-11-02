<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Versioned consent wording. Rows are never edited: a wording change is a new
 * row with a new version, so old consent records keep the text they were shown.
 */
class ConsentText extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['version', 'body', 'effective_from'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['effective_from' => 'datetime'];
    }

    /**
     * The wording in force right now, or null if none is effective yet.
     */
    public static function current(): ?self
    {
        return static::query()
            ->where('effective_from', '<=', now())
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Fill the agent placeholders for display. The stored body keeps the
     * placeholders; the version identifies exactly what was shown.
     */
    public function renderFor(Agent $agent): string
    {
        $body = $this->body;

        if (blank($agent->brokerage_name)) {
            $body = str_replace(' ({brokerage})', '', $body);
        }

        return str_replace(
            ['{agent_name}', '{brokerage}'],
            [$agent->full_name, (string) $agent->brokerage_name],
            $body,
        );
    }
}
