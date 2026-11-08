<?php

namespace App\Http\Requests;

use App\Services\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'min:2', 'max:255'],
            'phone' => [
                'required', 'string', 'max:30',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (PhoneNumber::toE164((string) $value) === null) {
                        $fail('Enter a valid mobile number, for example 613 555 0123.');
                    }
                },
            ],
            'consent' => ['accepted'],
            // Only online wording already in force: never a paper or future-dated version.
            'consent_text_id' => [
                'required', 'integer',
                Rule::exists('consent_texts', 'id')->where(
                    fn ($q) => $q->where('version', 'not like', 'paper-%')->where('effective_from', '<=', now()),
                ),
            ],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'working_with_agent' => ['nullable', 'in:0,1'],
            'buying_timeline' => ['nullable', 'in:immediate,1_3_months,3_6_months,browsing'],
            'pre_approved' => ['nullable', 'in:0,1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'consent.accepted' => 'Please tick the box to agree to receive the text message.',
            'full_name.required' => 'Please enter your name.',
            'phone.required' => 'Please enter your mobile number.',
        ];
    }
}
