<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    public function showProfileInfo()
    {
        // get start time in microseconds
        $startTime = microtime(true);

        $userId = Auth::id();
        $user = User::where('id', $userId)->first();

        // get end time in microseconds
        $endTime = microtime(true);

        // calculate the duration
        $duration = $endTime - $startTime;

        // convert duration to milliseconds for readability
        $durationInMilliseconds = $duration * 1000;

        // log start, end and duration times
        Log::info('Start time: ' . $startTime);
        Log::info('End time: ' . $endTime);
        Log::info('Execution time: ' . $durationInMilliseconds . ' milliseconds');


        return response()->json([
            'name' => $user->name,
            'department_id' => $user->department_id,
            'role_id' => $user->role_id
        ]);
    }

    public function updateProfileInfo(UpdateProfileRequest $request)
    {
        $userId = Auth::id();
        $user = User::where('id', $userId)->first();

        $data = $request->validated();
        $newName = $data['name'];
        $newDepartmentId = $data['department_id'];
        $newRoleId = $data['role_id'];

        if ($newName == $user->name && $newDepartmentId == $user->department_id && $newRoleId == $user->role_id) {
            return response()->json([
                'message' => 'No changes made.'
            ], 200);
        }

        $user->update([
            'name' => $newName,
            'department_id' => $newDepartmentId,
            'role_id' => $newRoleId
        ]);

        return response()->json([
            'message' => 'Profile updated successfully.'
        ], 200);
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        $userId = Auth::id();
        $user = User::where('id', $userId)->first();

        $data = $request->validated();
        $currentPassword = $data['current_password'];
        $newPassword = $data['new_password'];

        if (!Hash::check($currentPassword, $user->password)) {
            return response()->json([
                'message' => 'Current password does not match.'
            ], 422);
        }

        if (Hash::check($newPassword, $user->password)) {
            return response()->json([
                'message' => 'New password is same as current password.'
            ], 422);
        }

        $user->update([
            'password' => bcrypt($newPassword)
        ]);

        return response()->json([
            'message' => 'Password updated successfully.'
        ], 200);
    }
}
