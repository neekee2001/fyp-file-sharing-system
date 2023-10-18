<?php

namespace App\Rules;

use App\Models\File;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class FileOwnerRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $userId = Auth::id();
        $checkExist = File::where('id', $value)->where('uploaded_by_user_id', $userId)->exists();

        if ($checkExist == false) {
            $fail('Invalid file.');
        }
    }
}
