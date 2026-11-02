<?php

namespace App\Models\Concerns;

/**
 * The PRD tables default created_at to CURRENT_TIMESTAMP (database clock).
 * Event times and "older than 30 s" comparisons use the application clock, so
 * rows that take part in those comparisons set created_at from PHP instead.
 * This keeps both on the same timezone regardless of the MySQL server's.
 */
trait SetsCreatedAt
{
    protected static function bootSetsCreatedAt(): void
    {
        static::creating(function ($model) {
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
        });
    }
}
