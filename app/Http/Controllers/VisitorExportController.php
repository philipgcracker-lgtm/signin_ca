<?php

namespace App\Http\Controllers;

use App\Models\Consent;
use App\Models\Event;
use App\Models\Visitor;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV of one event's visitors for the agent's CRM. Carries the consent record
 * (yes/no, date, wording version) so the proof travels with the contact.
 * People whose details were removed by the retention job are not exported.
 */
class VisitorExportController extends Controller
{
    private const TIMELINES = [
        'immediate' => 'Right away',
        '1_3_months' => '1-3 months',
        '3_6_months' => '3-6 months',
        'browsing' => 'Just browsing',
    ];

    public function __invoke(Request $request, int $event): StreamedResponse
    {
        $event = Event::query()
            ->where('agent_id', $request->user()->id)
            ->findOrFail($event);

        $marketingOnly = $request->boolean('marketing');

        $visitors = $event->visitors()
            ->whereNull('anonymized_at')
            ->with('consents.consentText')
            ->orderBy('id')
            ->get()
            ->map(fn (Visitor $v) => [
                'visitor' => $v,
                'texting' => $this->latestGranted($v->consents, 'checkin'),
                'marketing' => $this->latestGranted($v->consents, 'marketing'),
            ])
            ->when($marketingOnly, fn (Collection $rows) => $rows->filter(fn ($r) => $r['marketing']['yes']));

        $filename = 'scanin-'.$event->slug.($marketingOnly ? '-marketing-consent' : '').'-visitors.csv';

        return response()->streamDownload(function () use ($visitors) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads accents correctly

            fputcsv($out, [
                'Name', 'Mobile', 'Email', 'Working with an agent', 'Buying timeline', 'Pre-approved',
                'Checked in (Toronto time)', 'Source',
                'Texting consent', 'Texting consent date', 'Texting consent wording',
                'Marketing consent', 'Marketing consent date', 'Marketing consent wording',
                'Opted out of texts',
            ]);

            foreach ($visitors as $r) {
                /** @var Visitor $v */
                $v = $r['visitor'];

                fputcsv($out, array_map([self::class, 'safe'], [
                    $v->full_name,
                    $v->phone_e164,
                    (string) $v->email,
                    $this->yesNo($v->working_with_agent),
                    self::TIMELINES[$v->buying_timeline] ?? '',
                    $this->yesNo($v->pre_approved),
                    $v->created_at->copy()->setTimezone(Event::DISPLAY_TIMEZONE)->format('Y-m-d H:i'),
                    $v->source,
                    $r['texting']['yes'] ? 'yes' : 'no',
                    $r['texting']['date'],
                    $r['texting']['version'],
                    $r['marketing']['yes'] ? 'yes' : 'no',
                    $r['marketing']['date'],
                    $r['marketing']['version'],
                    $v->sms_opt_out ? 'yes' : 'no',
                ]));
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * The newest consent of this purpose: granted and not revoked means "yes".
     *
     * @return array{yes: bool, date: string, version: string}
     */
    private function latestGranted(Collection $consents, string $purpose): array
    {
        /** @var Consent|null $c */
        $c = $consents->where('purpose', $purpose)->sortByDesc('id')->first();

        if ($c === null) {
            return ['yes' => false, 'date' => '', 'version' => ''];
        }

        return [
            'yes' => $c->granted && $c->revoked_at === null,
            'date' => $c->created_at->copy()->setTimezone(Event::DISPLAY_TIMEZONE)->format('Y-m-d H:i'),
            'version' => (string) $c->consentText?->version,
        ];
    }

    private function yesNo(?bool $v): string
    {
        return $v === null ? '' : ($v ? 'yes' : 'no');
    }

    /**
     * Stop spreadsheet formula injection: visitors type their own name and email
     * on a public form. A cell that starts with = - @ tab or CR (or + followed by
     * anything but digits) gets a leading apostrophe. Plain +1905... numbers are left alone.
     */
    public static function safe(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        $first = $value[0];

        if (in_array($first, ['=', '-', '@', "\t", "\r"], true)
            || ($first === '+' && ! preg_match('/^\+\d+$/', $value))) {
            return "'".$value;
        }

        return $value;
    }
}
