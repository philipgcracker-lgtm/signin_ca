<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('events.show', $event->id) }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; {{ $event->shortAddress() }}</a>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit open house</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <p class="mx-4 text-sm text-gray-600 sm:mx-0">The check-in link and QR code stay the same, so a printed sign keeps working.</p>
            @include('events._form', ['action' => route('events.update', $event->id), 'event' => $event, 'submit' => 'Save changes'])

            <form method="POST" action="{{ route('events.cancel', $event->id) }}"
                  onsubmit="return confirm('Cancel this open house? The check-in page and QR will stop working. Your check-ins are kept.');"
                  class="bg-white shadow-sm sm:rounded-lg p-6">
                @csrf
                <p class="text-sm font-medium text-gray-700">Cancel this open house</p>
                <p class="mt-1 text-sm text-gray-500">Turns the check-in page and QR off. Nothing is deleted, and you can reopen it later.</p>
                <button type="submit" class="mt-3 rounded-md border border-red-300 bg-white px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Cancel open house</button>
            </form>
        </div>
    </div>
</x-app-layout>
