<?php

namespace App\Models;

use App\Models\Concerns\SetsCreatedAt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ShortLink extends Model
{
    use SetsCreatedAt;

    public $timestamps = false;

    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['code', 'target_url', 'event_id', 'visitor_id'];

    /**
     * No 0/O/1/l/I: codes get read aloud and typed from SMS.
     */
    private const ALPHABET = '23456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ';

    /**
     * Create a link with a fresh unguessable 6-character code.
     */
    public static function createFor(string $targetUrl, ?int $eventId = null, ?int $visitorId = null): self
    {
        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
        } while (static::query()->whereKey($code)->exists());

        return static::create([
            'code' => $code,
            'target_url' => $targetUrl,
            'event_id' => $eventId,
            'visitor_id' => $visitorId,
        ]);
    }

    public function url(): string
    {
        return rtrim(config('app.url'), '/').'/s/'.$this->code;
    }
}
