{{-- Shared by create and edit. $event is null on create. --}}
@php
    $field = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm';
    $event ??= null;
@endphp

<form method="POST" action="{{ $action }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-5" autocomplete="off">
    @csrf
    @if ($event) @method('PATCH') @endif

    <div>
        <label for="address_street" class="block text-sm font-medium text-gray-700">Street address</label>
        <input id="address_street" name="address_street" type="text" value="{{ old('address_street', $event?->address_street) }}" required @unless ($event) autofocus @endunless class="{{ $field }}">
        @error('address_street') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="city" class="block text-sm font-medium text-gray-700">City</label>
            <input id="city" name="city" type="text" value="{{ old('city', $event?->city) }}" required class="{{ $field }}">
            @error('city') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="postal_code" class="block text-sm font-medium text-gray-700">Postal code <span class="text-gray-400">(optional)</span></label>
            <input id="postal_code" name="postal_code" type="text" value="{{ old('postal_code', $event?->postal_code) }}" placeholder="L3C 3J1" class="{{ $field }}">
            @error('postal_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
            <label for="event_date" class="block text-sm font-medium text-gray-700">Date</label>
            <input id="event_date" name="event_date" type="date" value="{{ old('event_date', $event?->displayStart()->format('Y-m-d')) }}" required class="{{ $field }}">
            @error('event_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="start_time" class="block text-sm font-medium text-gray-700">Starts</label>
            <input id="start_time" name="start_time" type="time" value="{{ old('start_time', $event?->displayStart()->format('H:i') ?? '14:00') }}" required class="{{ $field }}">
            @error('start_time') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="end_time" class="block text-sm font-medium text-gray-700">Ends</label>
            <input id="end_time" name="end_time" type="time" value="{{ old('end_time', $event?->displayEnd()->format('H:i') ?? '16:00') }}" required class="{{ $field }}">
            @error('end_time') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>
    <p class="-mt-3 text-xs text-gray-500">Times are Ontario time.</p>

    <div>
        <label for="listing_url" class="block text-sm font-medium text-gray-700">Link to your listing page</label>
        <input id="listing_url" name="listing_url" type="url" value="{{ old('listing_url', $event?->listing_url) }}" required placeholder="https://" class="{{ $field }}">
        <p class="mt-1 text-xs text-gray-500">Visitors get this link by text, and the QR sends people here after the open house ends. Use your own page.</p>
        @error('listing_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="border-t border-gray-100 pt-5">
        <p class="text-sm font-medium text-gray-700">Shown on the check-in page <span class="text-gray-400">(all optional)</span></p>
        <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label for="price" class="block text-sm text-gray-700">Price</label>
                <input id="price" name="price" type="number" min="0" step="1" inputmode="numeric" value="{{ old('price', $event?->price !== null ? (int) $event->price : null) }}" class="{{ $field }}">
                @error('price') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="bedrooms" class="block text-sm text-gray-700">Bedrooms</label>
                <input id="bedrooms" name="bedrooms" type="number" min="0" max="20" step="1" value="{{ old('bedrooms', $event?->bedrooms) }}" class="{{ $field }}">
                @error('bedrooms') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="bathrooms" class="block text-sm text-gray-700">Bathrooms</label>
                <input id="bathrooms" name="bathrooms" type="number" min="0" max="20" step="0.5" value="{{ old('bathrooms', $event?->bathrooms !== null ? (float) $event->bathrooms : null) }}" class="{{ $field }}">
                @error('bathrooms') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="mt-4">
            <label for="blurb" class="block text-sm text-gray-700">Short blurb</label>
            <textarea id="blurb" name="blurb" rows="2" maxlength="500" class="{{ $field }}">{{ old('blurb', $event?->blurb) }}</textarea>
            @error('blurb') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">{{ $submit }}</button>
        <a href="{{ $event ? route('events.show', $event->id) : route('dashboard') }}" class="text-sm text-gray-600 underline">Cancel</a>
    </div>
</form>
