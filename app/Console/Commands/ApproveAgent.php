<?php

namespace App\Console\Commands;

use App\Models\Agent;
use Illuminate\Console\Command;

/**
 * Approve (or revoke) an agent's right to create open houses. Idempotent.
 * On the cPanel host this runs from the deploy via the .run-approve-agents marker.
 */
class ApproveAgent extends Command
{
    protected $signature = 'scanin:approve-agent {email} {--revoke}';

    protected $description = 'Approve an agent to create open houses (or --revoke)';

    public function handle(): int
    {
        $agent = Agent::query()->where('email', strtolower((string) $this->argument('email')))->first();

        if ($agent === null) {
            $this->error('No agent with that email.');

            return self::FAILURE;
        }

        $agent->forceFill(['approved_at' => $this->option('revoke') ? null : ($agent->approved_at ?? now())])->save();
        $this->info($agent->email.($this->option('revoke') ? ' revoked' : ' approved'));

        return self::SUCCESS;
    }
}
