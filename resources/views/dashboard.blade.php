<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Your open houses') }}
            </h2>
            <a href="{{ route('events.create') }}" class="rounded-md bg-gray-800 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-700">New open house</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if ($events->isEmpty())
                    <div class="p-6 text-gray-600">
                        No open houses yet. <a href="{{ route('events.create') }}" class="text-gray-900 underline">Create your first one</a>.
                    </div>
                @else
                    <ul class="divide-y divide-gray-100">
                        @foreach ($events as $event)
                            <li>
                                <a href="{{ route('events.show', $event->id) }}" class="flex flex-wrap items-center justify-between gap-3 p-5 hover:bg-gray-50">
                                    <span>
                                        <span class="block font-medium text-gray-900">{{ $event->shortAddress() }}</span>
                                        <span class="block text-sm text-gray-500">
                                            {{ $event->displayStart()->format('D, M j, Y · g:i A') }} – {{ $event->displayEnd()->format('g:i A') }}
                                        </span>
                                    </span>
                                    <span class="flex items-center gap-4 text-sm">
                                        <span class="text-gray-700">{{ $event->visitors_count }} {{ \Illuminate\Support\Str::plural('check-in', $event->visitors_count) }}</span>
                                        <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">{{ ucfirst($event->phase()) }}</span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
