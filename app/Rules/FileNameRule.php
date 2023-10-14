<?php

namespace App\Rules;

use App\Models\File;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class FileNameRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $fileName = $value->getClientOriginalName();
        $userId = Auth::id();

        $checkExist = File::where('file_name', $fileName)->where('uploaded_by_user_id', $userId)->exists();

        if ($checkExist == true) {
            $fail('A file with the same name already exists.');
        }
    }
}
