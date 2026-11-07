<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Admin &middot; Agents</h2>
            <p class="text-sm text-gray-500">
                {{ $agents->count() }} {{ \Illuminate\Support\Str::plural('agent', $agents->count()) }}
                @if ($pending > 0) &middot; <span class="font-medium text-amber-700">{{ $pending }} waiting for approval</span>@endif
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="mx-4 rounded-md bg-green-50 p-4 text-sm text-green-800 sm:mx-0">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="mx-4 rounded-md bg-amber-50 p-4 text-sm text-amber-800 sm:mx-0">{{ session('error') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Agent</th>
                                <th class="px-4 py-3">Brokerage</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Plan</th>
                                <th class="px-4 py-3">Events</th>
                                <th class="px-4 py-3">Check-ins</th>
                                <th class="px-4 py-3">Texts this month</th>
                                <th class="px-4 py-3">Joined</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($agents as $a)
                                @php $t = $texts->get($a->id); @endphp
                                <tr class="align-top">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('admin.agents.show', $a->id) }}" class="font-medium text-gray-900 underline">{{ $a->full_name }}</a>
                                        @if ($a->is_admin) <span class="ml-1 rounded-full bg-gray-800 px-2 py-0.5 text-xs text-white">Admin</span>@endif
                                        <span class="block break-all text-gray-500">{{ $a->email }}</span>
                                    </td>
                                    <td class="px-4 py-3">{{ $a->brokerage_name ?: '—' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if ($a->isSuspended())
                                            <span class="rounded-full bg-red-50 px-2 py-0.5 text-xs text-red-700">Suspended</span>
                                        @elseif ($a->isApproved())
                                            <span class="rounded-full bg-green-50 px-2 py-0.5 text-xs text-green-700">Approved</span>
                                        @else
                                            <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs text-amber-700">Pending</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        {{ $a->isPro() ? 'Pro' : 'Free' }}@if ($a->comped_at) <span class="text-xs text-gray-500">(comped)</span>@endif
                                    </td>
                                    <td class="px-4 py-3">{{ $a->events_count }}</td>
                                    <td class="px-4 py-3">{{ $checkins->get($a->id, 0) }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        {{ $t->sent ?? 0 }}@if (($t->failed ?? 0) > 0) <span class="text-xs text-red-600">({{ $t->failed }} failed)</span>@endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-500">{{ $a->created_at?->copy()->setTimezone(\App\Models\Event::DISPLAY_TIMEZONE)->format('M j, Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <p class="px-4 text-xs text-gray-500 sm:px-0">Counts only. Visitor names and phone numbers are never shown here.</p>
        </div>
    </div>
</x-app-layout>
