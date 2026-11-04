<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('events.show', $event->id) }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; {{ $event->shortAddress() }}</a>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add a visitor from the paper sheet</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('visitors.store', $event->id) }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-5" autocomplete="off">
                @csrf

                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700">Full name</label>
                    <input id="full_name" name="full_name" type="text" value="{{ old('full_name') }}" required autofocus
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    @error('full_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">Mobile number</label>
                    <input id="phone" name="phone" type="tel" inputmode="tel" value="{{ old('phone') }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email <span class="text-gray-400">(optional)</span></label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <label for="working_with_agent" class="block text-sm font-medium text-gray-700">Working with an agent?</label>
                        <select id="working_with_agent" name="working_with_agent" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">Not answered</option>
                            <option value="1" @selected(old('working_with_agent') === '1')>Yes</option>
                            <option value="0" @selected(old('working_with_agent') === '0')>No</option>
                        </select>
                    </div>
                    <div>
                        <label for="pre_approved" class="block text-sm font-medium text-gray-700">Pre-approved?</label>
                        <select id="pre_approved" name="pre_approved" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">Not answered</option>
                            <option value="1" @selected(old('pre_approved') === '1')>Yes</option>
                            <option value="0" @selected(old('pre_approved') === '0')>No</option>
                        </select>
                    </div>
                    <div>
                        <label for="buying_timeline" class="block text-sm font-medium text-gray-700">Timeline</label>
                        <select id="buying_timeline" name="buying_timeline" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">Not answered</option>
                            <option value="immediate" @selected(old('buying_timeline') === 'immediate')>Right away</option>
                            <option value="1_3_months" @selected(old('buying_timeline') === '1_3_months')>1–3 months</option>
                            <option value="3_6_months" @selected(old('buying_timeline') === '3_6_months')>3–6 months</option>
                            <option value="browsing" @selected(old('buying_timeline') === 'browsing')>Just browsing</option>
                        </select>
                    </div>
                </div>

                <div class="rounded-md border border-gray-200 bg-gray-50 p-4 space-y-2">
                    <label class="flex items-start gap-3 text-sm text-gray-800">
                        <input type="checkbox" name="sms_ok" value="1" @checked(old('sms_ok')) class="mt-0.5 rounded border-gray-300">
                        <span><strong>This visitor ticked “OK to text” on the paper sheet.</strong> Only tick this if their row on the sheet has the box ticked. A text with the listing link is sent right away.</span>
                    </label>
                    @if ($paperWording)
                        <p class="text-xs text-gray-500">Wording on the sheet ({{ $paperVersion }}): {{ $paperWording }}</p>
                    @endif
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" class="rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">Add visitor</button>
                    <a href="{{ route('events.show', $event->id) }}" class="text-sm text-gray-600 underline">Back to the list</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
