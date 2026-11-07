<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Your open houses</a>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">New open house</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            @include('events._form', ['action' => route('events.store'), 'event' => null, 'submit' => 'Create open house'])
        </div>
    </div>
</x-app-layout>
