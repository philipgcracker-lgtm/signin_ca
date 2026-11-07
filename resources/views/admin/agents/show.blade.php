@php
    $btn = 'rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50';
    $btnPrimary = 'rounded-md bg-gray-800 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-700';
    $btnDanger = 'rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-700';
@endphp
<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('admin.agents.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; All agents</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $agent->full_name }}</h2>
            <p class="text-sm text-gray-500 break-all">{{ $agent->email }}@if ($agent->phone) &middot; {{ $agent->phone }}@endif @if ($agent->brokerage_name) &middot; {{ $agent->brokerage_name }}@endif</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="mx-4 rounded-md bg-green-50 p-4 text-sm text-green-800 sm:mx-0">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="mx-4 rounded-md bg-amber-50 p-4 text-sm text-amber-800 sm:mx-0">{{ session('error') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-5 space-y-4">
                <div class="flex flex-wrap items-center gap-3 text-sm">
                    @if ($agent->isSuspended())
                        <span class="rounded-full bg-red-50 px-2 py-0.5 text-xs text-red-700">Suspended {{ $agent->suspended_at->copy()->setTimezone(\App\Models\Event::DISPLAY_TIMEZONE)->format('M j, Y') }}</span>
                    @elseif ($agent->isApproved())
                        <span class="rounded-full bg-green-50 px-2 py-0.5 text-xs text-green-700">Approved</span>
                    @else
                        <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs text-amber-700">Pending approval</span>
                    @endif
                    <span class="text-gray-700">Plan: {{ $agent->isPro() ? 'Pro' : 'Free' }}@if ($agent->comped_at) (comped)@endif</span>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @if (! $agent->isApproved())
                        <form method="POST" action="{{ route('admin.agents.approve', $agent->id) }}">@csrf
                            <button class="{{ $btnPrimary }}">Approve</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.agents.revoke', $agent->id) }}" onsubmit="return confirm('Revoke approval? They keep existing events but cannot create new ones.')">@csrf
                            <button class="{{ $btn }}">Revoke approval</button>
                        </form>
                    @endif

                    @if ($agent->isSuspended())
                        <form method="POST" action="{{ route('admin.agents.unsuspend', $agent->id) }}">@csrf
                            <button class="{{ $btnPrimary }}">Restore account</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.agents.suspend', $agent->id) }}" onsubmit="return confirm('Suspend this agent? Their check-in pages, links and texts stop working immediately.')">@csrf
                            <button class="{{ $btnDanger }}">Suspend</button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('admin.agents.tier', $agent->id) }}" class="flex items-center gap-2">@csrf
                        <select name="tier" class="rounded-md border-gray-300 text-sm">
                            <option value="free" @selected(! $agent->isPro())>Free</option>
                            <option value="pro" @selected($agent->isPro())>Pro (comped)</option>
                        </select>
                        <button class="{{ $btn }}">Set plan</button>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 px-4 sm:px-0 md:grid-cols-4">
                @foreach ([
                    'Texts this month' => $textsMonth->sent ?? 0,
                    'Failed this month' => $textsMonth->failed ?? 0,
                    'Texts all time' => $textsAll->sent ?? 0,
                    'Failed all time' => $textsAll->failed ?? 0,
                ] as $label => $n)
                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <p class="text-2xl font-semibold text-gray-900">{{ $n }}</p>
                        <p class="text-sm text-gray-500">{{ $label }}</p>
                    </div>
                @endforeach
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if ($events->isEmpty())
                    <div class="p-6 text-gray-600">No open houses yet.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="px-4 py-3">Open house</th>
                                    <th class="px-4 py-3">When</th>
                                    <th class="px-4 py-3">Check-ins</th>
                                    <th class="px-4 py-3">Listing site</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($events as $e)
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-gray-900">{{ $e->shortAddress() }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-500">{{ $e->displayStart()->format('M j, Y · g:i A') }} &middot; {{ ucfirst($e->phase()) }}</td>
                                        <td class="px-4 py-3">{{ $e->visitors_count }}</td>
                                        {{-- Plain text on purpose: never make the admin click a link an agent supplied. --}}
                                        <td class="px-4 py-3 break-all text-gray-700">{{ $e->listing_url }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            <p class="px-4 text-xs text-gray-500 sm:px-0">Counts only. Visitor names and phone numbers are never shown here.</p>
        </div>
    </div>
</x-app-layout>
