<?php

namespace App\Http\Requests;

use App\Rules\SafeListingUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

/**
 * A new open house entered by the signed-in agent. Date and times are Toronto
 * wall-clock (v1 is Ontario only); the controller converts them for storage.
 * Display fields are what the agent chooses to show; nothing comes from MLS.
 */
class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'address_street' => ['required', 'string', 'min:3', 'max:255'],
            'city' => ['required', 'string', 'min:2', 'max:100'],
            'postal_code' => ['nullable', 'string', 'regex:/^[A-Za-z]\d[A-Za-z][ -]?\d[A-Za-z]\d$/'],
            'event_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'listing_url' => ['required', 'url:https', 'max:2000', new SafeListingUrl],
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            'bathrooms' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'blurb' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'postal_code.regex' => 'Enter a postal code like L3C 3J1.',
            'event_date.after_or_equal' => 'The date can not be in the past.',
            'end_time.after' => 'The end time must be after the start time.',
            'listing_url.required' => 'Paste the link to your listing page. Visitors are sent there.',
            'listing_url.url' => 'Enter a full link starting with https://',
        ];
    }

    /** Toronto wall-clock start. */
    public function startsAt(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d H:i', $this->input('event_date').' '.$this->input('start_time'), 'America/Toronto');
    }

    public function endsAt(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d H:i', $this->input('event_date').' '.$this->input('end_time'), 'America/Toronto');
    }
}
