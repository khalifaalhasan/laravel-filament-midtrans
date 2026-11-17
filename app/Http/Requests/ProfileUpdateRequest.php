<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\IndonesianPhone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'phone' => ['nullable', 'string', new IndonesianPhone],
        ];
    }

    /** Store WhatsApp in the same +62 form as registration. */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        if ($key === null && array_key_exists('phone', $data)) {
            $data['phone'] = filled($data['phone']) ? IndonesianPhone::normalize($data['phone']) : null;
        }

        return $data;
    }
}
