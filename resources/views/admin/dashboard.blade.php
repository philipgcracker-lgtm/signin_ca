@php
    $pct = fn ($v) => $v === null ? '—' : $v.'%';
    $money = fn (int $cents) => '$'.number_format($cents / 100, 2);
    $tile = function (string $label, $value, ?string $sub = null) {
        return compact('label', 'value', 'sub');
    };
    $sections = [
        'Agents' => [
            $tile('Agents', $agents['total'], $agents['new_30d'].' new in '.$days.' days'),
            $tile('Waiting for approval', $agents['pending']),
            $tile('Suspended', $agents['suspended']),
            $tile('Pro', $agents['pro'], $agents['comped'].' comped'),
        ],
        'Open houses' => [
            $tile('Total', $events['total'], $events['new_30d'].' created in '.$days.' days'),
            $tile('Upcoming', $events['upcoming']),
            $tile('Live now', $events['live']),
            $tile('Ended', $events['ended']),
        ],
        'Visitors' => [
            $tile('Check-ins', $visitors['total'], $visitors['month'].' this month'),
            $tile('Agreed to texts', $pct($visitors['consent_rate']), 'of all check-ins'),
            $tile('Opted out', $visitors['opted_out']),
            $tile('Feedback', $visitors['feedback'], $visitors['avg_score'] ? 'avg '.number_format($visitors['avg_score'], 1).' / 5' : null),
        ],
        'Texts' => [
            $tile('Sent this month', $texts['month'], $texts['total'].' all time'),
            $tile('Failed this month', $texts['failed_month'], $pct($texts['failure_rate']).' failure rate'),
            $tile('Twilio cost this month', $money($texts['cost_month_cents']), 'as reported by Twilio'),
            $tile('Listing opened', $pct($engagement['rate']), $engagement['clicked'].' of '.$engagement['links'].' links · '.$engagement['clicks'].' clicks'),
        ],
    ];
    $sections['System'] = [
        $tile('Scheduler', $system['running'] ? 'Running' : ($system['last'] ? 'Not running' : 'No signal yet'),
            $system['last'] ? 'last ran '.$system['last']->diffForHumans() : 'cron has not fired since this was added'),
        $tile('Texts stuck in queue', $system['stuck'], 'waiting more than 5 minutes'),
    ];
    $charts = ['Check-ins per day' => $checkinSeries, 'Texts sent per day' => $textSeries];
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Admin &middot; Overview</h2>
            <a href="{{ route('admin.agents.index') }}" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Manage agents</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @foreach ($sections as $title => $tiles)
                <section class="space-y-2">
                    <h3 class="px-4 text-xs font-semibold uppercase tracking-wide text-gray-500 sm:px-0">{{ $title }}</h3>
                    <div class="grid grid-cols-2 gap-3 px-4 sm:px-0 md:grid-cols-4">
                        @foreach ($tiles as $t)
                            <div class="bg-white rounded-lg shadow-sm p-4">
                                <p class="text-2xl font-semibold text-gray-900">{{ $t['value'] }}</p>
                                <p class="text-sm text-gray-500">{{ $t['label'] }}</p>
                                @if ($t['sub']) <p class="mt-1 text-xs text-gray-400">{{ $t['sub'] }}</p> @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach

            <div class="grid gap-6 px-4 sm:px-0 lg:grid-cols-2">
                @foreach ($charts as $title => $series)
                    @php $max = max(1, max($series)); @endphp
                    <section class="bg-white rounded-lg shadow-sm p-4">
                        <div class="flex items-baseline justify-between">
                            <h3 class="text-sm font-semibold text-gray-900">{{ $title }}</h3>
                            <p class="text-xs text-gray-500">last {{ $days }} days &middot; {{ array_sum($series) }} total</p>
                        </div>
                        <div class="mt-4 flex h-28 items-end gap-px" role="img" aria-label="{{ $title }}, last {{ $days }} days, {{ array_sum($series) }} total">
                            @foreach ($series as $day => $n)
                                <div class="flex-1 rounded-t bg-gray-800/80" title="{{ \Illuminate\Support\Carbon::parse($day)->format('M j') }}: {{ $n }}" style="height: {{ $n === 0 ? 2 : max(4, round($n / $max * 100)) }}%; {{ $n === 0 ? 'opacity:.25' : '' }}"></div>
                            @endforeach
                        </div>
                        <div class="mt-1 flex justify-between text-xs text-gray-400">
                            <span>{{ \Illuminate\Support\Carbon::parse(array_key_first($series))->format('M j') }}</span>
                            <span>peak {{ max($series) }}/day</span>
                            <span>{{ \Illuminate\Support\Carbon::parse(array_key_last($series))->format('M j') }}</span>
                        </div>
                    </section>
                @endforeach
            </div>

            <section class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <h3 class="px-4 pt-4 text-sm font-semibold text-gray-900">Top agents &middot; check-ins in the last {{ $days }} days</h3>
                @if ($top->isEmpty())
                    <p class="p-4 text-sm text-gray-600">No check-ins yet.</p>
                @else
                    <table class="mt-2 min-w-full text-sm">
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($top as $row)
                                <tr>
                                    <td class="px-4 py-3"><a href="{{ route('admin.agents.show', $row->agent->id) }}" class="font-medium text-gray-900 underline">{{ $row->agent->full_name }}</a></td>
                                    <td class="px-4 py-3 text-gray-500">{{ $row->agent->brokerage_name ?: '—' }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-900">{{ $row->n }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>

            <p class="px-4 text-xs text-gray-500 sm:px-0">Counts only. Visitor names, phone numbers and message text are never read here. Days are Toronto time.</p>
        </div>
    </div>
</x-app-layout>
