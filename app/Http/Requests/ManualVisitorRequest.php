<?php

namespace App\Http\Requests;

use App\Services\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A paper-sheet row typed in by the signed-in agent. Same fields as the public
 * check-in form; `sms_ok` stands for the "OK to text" box the visitor ticked on paper.
 */
class ManualVisitorRequest extends FormRequest
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
            'full_name' => ['required', 'string', 'min:2', 'max:255'],
            'phone' => [
                'required', 'string', 'max:30',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (PhoneNumber::toE164((string) $value) === null) {
                        $fail('Enter a valid mobile number, for example 613 555 0123.');
                    }
                },
            ],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'working_with_agent' => ['nullable', 'in:0,1'],
            'buying_timeline' => ['nullable', 'in:immediate,1_3_months,3_6_months,browsing'],
            'pre_approved' => ['nullable', 'in:0,1'],
            'sms_ok' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'full_name.required' => "Enter the visitor's name.",
            'phone.required' => "Enter the visitor's mobile number.",
        ];
    }
}
