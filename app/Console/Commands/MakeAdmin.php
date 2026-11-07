<?php

namespace App\Console\Commands;

use App\Models\Agent;
use Illuminate\Console\Command;

/**
 * Grant (or --revoke) admin access. This is the ONLY way is_admin is set:
 * it is not mass-assignable and no web form touches it. Also approves the
 * agent, since an admin who cannot create events would be odd.
 * On the cPanel host this runs from the deploy via the .run-make-admin marker.
 */
class MakeAdmin extends Command
{
    protected $signature = 'scanin:make-admin {email} {--revoke}';

    protected $description = 'Make an agent an admin (or --revoke)';

    public function handle(): int
    {
        $agent = Agent::query()->where('email', strtolower((string) $this->argument('email')))->first();

        if ($agent === null) {
            $this->error('No agent with that email.');

            return self::FAILURE;
        }

        $revoke = (bool) $this->option('revoke');

        $agent->forceFill([
            'is_admin' => ! $revoke,
            'approved_at' => $agent->approved_at ?? now(),
        ])->save();

        $this->info($agent->email.($revoke ? ' is no longer an admin' : ' is now an admin'));

        return self::SUCCESS;
    }
}
