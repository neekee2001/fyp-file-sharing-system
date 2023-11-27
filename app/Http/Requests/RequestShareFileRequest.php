<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestShareFileRequest extends FormRequest
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
            'requested_file_id' => 'required|exists:files,id',
            'requested_permission_id' => 'required|exists:permissions,id'
        ];
    }
}
