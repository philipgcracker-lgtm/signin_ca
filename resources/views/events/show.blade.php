@php
    $timelines = [
        'immediate' => 'Right away',
        '1_3_months' => '1–3 months',
        '3_6_months' => '3–6 months',
        'browsing' => 'Just browsing',
    ];
    $yesNo = fn ($v) => $v === null ? '—' : ($v ? 'Yes' : 'No');
    $phone = function (string $e164) {
        return preg_match('/^\+1(\d{3})(\d{3})(\d{4})$/', $e164, $m) ? "({$m[1]}) {$m[2]}-{$m[3]}" : $e164;
    };
    $statusClass = [
        'queued' => 'bg-gray-100 text-gray-700',
        'sent' => 'bg-blue-50 text-blue-700',
        'delivered' => 'bg-green-50 text-green-700',
        'failed' => 'bg-red-50 text-red-700',
        'undelivered' => 'bg-red-50 text-red-700',
    ];
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Your open houses</a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $event->shortAddress() }}</h2>
                <p class="text-sm text-gray-500">
                    {{ $event->displayStart()->format('D, M j, Y · g:i A') }} – {{ $event->displayEnd()->format('g:i A') }}
                    · {{ ucfirst($event->phase()) }}
                </p>
            </div>
            <p class="text-sm text-gray-500 break-all">Check-in page: <a class="text-gray-700 underline" href="{{ route('checkin.show', $event->slug) }}">{{ route('checkin.show', $event->slug) }}</a></p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-2 gap-3 px-4 sm:px-0 md:grid-cols-5">
                @foreach ([
                    'Check-ins' => $summary['checkins'],
                    'Agreed to texts' => $summary['consented'],
                    'Texts sent' => $summary['delivered'],
                    'Texts failed' => $summary['failed'],
                    'Opted out' => $summary['opted_out'],
                ] as $label => $n)
                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <p class="text-2xl font-semibold text-gray-900">{{ $n }}</p>
                        <p class="text-sm text-gray-500">{{ $label }}</p>
                    </div>
                @endforeach
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if ($rows->isEmpty())
                    <div class="p-6 text-gray-600">No one has checked in yet.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="px-4 py-3">Time</th>
                                    <th class="px-4 py-3">Name</th>
                                    <th class="px-4 py-3">Mobile</th>
                                    <th class="px-4 py-3">Email</th>
                                    <th class="px-4 py-3">Has agent</th>
                                    <th class="px-4 py-3">Pre-approved</th>
                                    <th class="px-4 py-3">Timeline</th>
                                    <th class="px-4 py-3">Consent</th>
                                    <th class="px-4 py-3">Text</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($rows as $r)
                                    @php $v = $r->visitor; @endphp
                                    <tr class="align-top">
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-500">{{ $v->created_at->copy()->setTimezone(\App\Models\Event::DISPLAY_TIMEZONE)->format('g:i A') }}</td>
                                        @if ($v->anonymized_at)
                                            <td class="px-4 py-3 text-gray-400 italic" colspan="3">Personal details removed</td>
                                        @else
                                            <td class="px-4 py-3 font-medium text-gray-900">{{ $v->full_name }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap">{{ $phone($v->phone_e164) }}</td>
                                            <td class="px-4 py-3 break-all">{{ $v->email ?: '—' }}</td>
                                        @endif
                                        <td class="px-4 py-3">{{ $yesNo($v->working_with_agent) }}</td>
                                        <td class="px-4 py-3">{{ $yesNo($v->pre_approved) }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">{{ $timelines[$v->buying_timeline] ?? '—' }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if ($v->sms_opt_out)
                                                <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs text-amber-700">Opted out</span>
                                            @elseif ($r->consent?->granted && ! $r->consent->revoked_at)
                                                <span class="rounded-full bg-green-50 px-2 py-0.5 text-xs text-green-700">Agreed</span>
                                            @else
                                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-700">None</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($r->text)
                                                <span class="rounded-full px-2 py-0.5 text-xs {{ $statusClass[$r->text->status] ?? 'bg-gray-100 text-gray-700' }}">{{ ucfirst($r->text->status) }}</span>
                                                @if ($r->text->error)
                                                    <span class="mt-1 block max-w-xs text-xs text-red-600">{{ \Illuminate\Support\Str::limit($r->text->error, 120) }}</span>
                                                @endif
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
