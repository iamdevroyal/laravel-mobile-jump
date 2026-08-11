<?php

namespace Iamdevroyal\MobileJump\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConnectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_id'               => ['required', 'string', 'max:32'],
            'token'                    => ['nullable', 'string', 'max:128'],
            'device_info'              => ['sometimes', 'array'],
            'device_info.model'        => ['sometimes', 'string', 'max:128'],
            'device_info.os_version'   => ['sometimes', 'string', 'max:64'],
            'device_info.runner_version' => ['sometimes', 'string', 'max:32'],
        ];
    }

    public function messages(): array
    {
        return [
            'session_id.required' => 'A session ID is required.',
        ];
    }
}
