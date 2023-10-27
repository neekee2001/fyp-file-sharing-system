<?php

namespace App\Http\Requests;

use App\Rules\ExcludeCurrentUserRule;
use Illuminate\Foundation\Http\FormRequest;

class ShareFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file_id' => 'required|exists:files,id',
            'shared_with_user_id' => [
                'required',
                'exists:users,id',
                new ExcludeCurrentUserRule()
            ],
            'permission_id' => 'required|exists:permissions,id'
        ];
    }

    public function messages()
    {
        return [
            'shared_with_user_id.required' => 'Please select user to share.',
            'permission_id.required' => 'Please select share permission.',
        ];
    }
}
