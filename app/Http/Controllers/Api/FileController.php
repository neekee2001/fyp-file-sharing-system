<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveShareFileRequest;
use App\Http\Requests\EditFileRequest;
use App\Http\Requests\RequestShareFileRequest;
use App\Http\Requests\RevokeFileAccessRequest;
use App\Http\Requests\ShareFileRequest;
use App\Http\Requests\StoreFileRequest;
use App\Http\Requests\UpdateFileAccessRequest;
use App\Models\File;
use App\Models\SharedFile;
use App\Models\ShareRequest;
use App\Models\User;
use App\Models\Department;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class FileController extends Controller
{
    // Display files uploaded by user
    public function showMyFiles()
    {
        $userId = Auth::id();
        $files = File::where('uploaded_by_user_id', $userId)->orderByDesc('updated_at')->get();
        return response()->json($files);
    }

    // Display files shared with user
    public function showSharedWithMe()
    {
        $userId = Auth::id();
        $sharedFiles = SharedFile::join('permissions', 'permissions.id', '=', 'shared_files.shared_permission_id')
            ->join('files', 'files.id', '=', 'shared_files.file_id')
            ->join('users', 'users.id', '=', 'files.uploaded_by_user_id')
            ->select('shared_files.*', 'files.file_name', 'files.file_description', 'users.name', 'permissions.permission_name')
            ->where('shared_files.shared_with_user_id', $userId)
            ->orderByDesc('shared_files.created_at')
            ->get();
        return response()->json($sharedFiles);
    }

    // Display all files uploaded by other users
    public function showAllFiles()
    {
        $userId = Auth::id();
        $sharedFiles = SharedFile::where('shared_with_user_id', $userId)->pluck('file_id');
        $requestedFiles = ShareRequest::where('requested_by_user_id', $userId)->pluck('requested_file_id');

        $allFiles = File::join('users', 'users.id', '=', 'files.uploaded_by_user_id')
            ->select('files.*', 'users.name')
            ->where('files.uploaded_by_user_id', '!=', $userId)
            ->whereNotIn('files.id', $sharedFiles)
            ->whereNotIn('files.id', $requestedFiles)
            ->orderBy('files.file_name')
            ->get();
        return response()->json($allFiles);
    }

    // Display all requests from other users to share the file
    public function showShareRequests()
    {
        $userId = Auth::id();
        $shareRequests = ShareRequest::join('permissions', 'permissions.id', '=', 'share_requests.requested_permission_id')
            ->join('files', 'files.id', '=', 'share_requests.requested_file_id')
            ->join('users', 'users.id', '=', 'share_requests.requested_by_user_id')
            ->select('share_requests.*', 'files.file_name', 'files.file_description', 'users.name', 'permissions.permission_name')
            ->where('files.uploaded_by_user_id', $userId)
            ->orderByDesc('share_requests.created_at')
            ->get();
        return response()->json($shareRequests);
    }

    // Display all requested file 
    public function showRequestedFile()
    {
        $userId = Auth::id();
        $requestedFiles = ShareRequest::join('permissions', 'permissions.id', '=', 'share_requests.requested_permission_id')
            ->join('files', 'files.id', '=', 'share_requests.requested_file_id')
            ->join('users', 'users.id', '=', 'files.uploaded_by_user_id')
            ->select('share_requests.*', 'files.file_name', 'files.file_description', 'users.name', 'permissions.permission_name')
            ->where('share_requests.requested_by_user_id', $userId)
            ->orderByDesc('share_requests.created_at')
            ->get();
        return response()->json($requestedFiles);
    }

    // Upload file
    public function store(StoreFileRequest $request)
    {
        if ($request->hasFile('file')) {
            $data = $request->validated();
            $file = $request->file('file');

            $fileName = $file->getClientOriginalName();
            $fileDescription = $data['file_description'];
            $fileSize = $file->getSize();
            $fileMime = $file->getMimeType();
            $filePath = $file->getRealPath();
            $fileContent = file_get_contents($filePath);
            $aesKey = Key::createNewRandomKey();
            $encryptedFileContent = Crypto::encrypt($fileContent, $aesKey);
            $fileHash = ipfs()->add($encryptedFileContent);
            $userId = Auth::id();

            File::create([
                'file_name' => $fileName,
                'file_description' => $fileDescription,
                'file_size' => $fileSize,
                'file_mime' => $fileMime,
                'ipfs_cid' => $fileHash,
                'aes_key' => $aesKey->saveToAsciiSafeString(),
                'uploaded_by_user_id' => $userId,
            ]);

            return response()->json([
                'message' => 'File uploaded successfully.'
            ], 201);
        }
    }

    // Request file owner to share the file
    public function requestToShare(RequestShareFileRequest $request)
    {
        $userId = Auth::id();
        $data = $request->validated();
        $requestedFileId = $data['requested_file_id'];
        $requestedPermissionId = $data['requested_permission_id'];

        ShareRequest::create([
            'requested_file_id' => $requestedFileId,
            'requested_by_user_id' => $userId,
            'requested_permission_id' => $requestedPermissionId,
        ]);

        return response()->json([
            'message' => 'Request sent successfully.'
        ], 201);
    }

    // Approve request sent by other users to share the file
    public function approveRequest(ApproveShareFileRequest $request)
    {
        $data = $request->validated();
        $shareRequestId = $data['share_request_id'];
        $shareRequest = ShareRequest::where('id', $shareRequestId)->first();
        $requestedByUser = User::find($shareRequest->requested_by_user_id);

        if (!$requestedByUser || !$shareRequest) {
            return response()->json([
                'message' => 'User not found.'
            ], 404);
        }

        $userDepartment = $requestedByUser->department_id;

        $checkExist = SharedFile::where('file_id', $shareRequest->requested_file_id)
            ->where('shared_with_user_id', $shareRequest->requested_by_user_id)->exists();

        if ($checkExist == true) {
            $shareRequest->delete();

            return response()->json([
                'message' => 'File shared with this user already. The request is deleted.'
            ], 422);
        }

        $goHost = env('GO_HOST');
        $goPort = env('GO_PORT');
        $url = "http://{$goHost}:{$goPort}/encrypt";

        $fileRecord = File::find($shareRequest->requested_file_id);
        $aesKey = $fileRecord->aes_key;
        $user = User::find($fileRecord->uploaded_by_user_id);
        $masterPublicKey = $user->master_public_key;

        $usersWithAccess = SharedFile::where('file_id', $shareRequest->requested_file_id)->pluck('shared_with_user_id')->toArray();

        $goRequest = [
            'aesKey' => $aesKey,
            'UserID' => array_merge([$user->id], [$requestedByUser->id], $usersWithAccess),
            'ownerMPK' => $masterPublicKey,
        ];

        $goResponse = Http::post($url, $goRequest);

        if ($goResponse->successful()) {
            $goData = $goResponse->json();

            SharedFile::create([
                'file_id' => $shareRequest->requested_file_id,
                'shared_with_department_id' => $userDepartment,
                'shared_with_user_id' => $shareRequest->requested_by_user_id,
                'shared_permission_id' => $shareRequest->requested_permission_id,
            ]);

            $shareRequest->delete();

            $fileRecord->update([
                'ciphertext' => $goData,
            ]);

            return response()->json([
                'message' => 'Request has been approved.'
            ], 201);
        } else {
            return response()->json([
                'message' => 'Failed to fetch data from Golang.'
            ], 500);
        }
    }

    // Share file
    public function share(ShareFileRequest $request)
    {
        $data = $request->validated();
        $fileId = $data['file_id'];
        $sharedWithDeptId = $data['shared_with_department_id'];

        $userId = Auth::id();
        $fileRecord = File::where('id', $fileId)->where('uploaded_by_user_id', $userId)->first();

        if (!$fileRecord) {
            return response()->json([
                'message' => 'File not found.'
            ], 404);
        }

        $sharedWithUserId = User::where('department_id', $sharedWithDeptId)->where('id', '!=', $userId)->pluck('id')->toArray();

        if (empty($sharedWithUserId)) {
            return response()->json([
                'message' => 'There are no users in this department.'
            ], 404);
        }

        $usersWithAccess = SharedFile::where('file_id', $fileId)->pluck('shared_with_user_id')->toArray();
        $usersWithoutAccess = array_diff($sharedWithUserId, $usersWithAccess);


        if (empty($usersWithoutAccess)) {
            return response()->json([
                'message' => 'File shared with all users in this department.'
            ], 404);
        }

        $goHost = env('GO_HOST');
        $goPort = env('GO_PORT');
        $url = "http://{$goHost}:{$goPort}/encrypt";

        $user = User::where('id', $userId)->first();
        $masterPublicKey = $user->master_public_key;
        $aesKey = $fileRecord->aes_key;

        $goRequest = [
            'aesKey' => $aesKey,
            'UserID' => array_merge([$userId], $usersWithoutAccess, $usersWithAccess),
            'ownerMPK' => $masterPublicKey,
        ];

        $goResponse = Http::post($url, $goRequest);

        if ($goResponse->successful()) {
            $goData = $goResponse->json();

            foreach ($usersWithoutAccess as $sharedUserId) {
                SharedFile::create([
                    'file_id' => $fileId,
                    'shared_with_department_id' => $sharedWithDeptId,
                    'shared_with_user_id' => $sharedUserId,
                    'shared_permission_id' => $data['permission_id'],
                ]);
            }

            $fileRecord->update([
                'ciphertext' => $goData,
            ]);

            $shareRequestsToDelete = ShareRequest::whereIn('requested_by_user_id', $sharedWithUserId)
                ->where('requested_file_id', $fileId)
                ->get();

            if ($shareRequestsToDelete->isNotEmpty()) {
                foreach ($shareRequestsToDelete as $shareRequest) {
                    $shareRequest->delete();
                }
            }

            return response()->json([
                'message' => 'File shared successfully.'
            ], 201);
        } else {
            return response()->json([
                'message' => 'Failed to fetch data from Golang.'
            ], 500);
        }
    }

    // Update user access to file
    public function updateFileAccess(UpdateFileAccessRequest $request)
    {
        $data = $request->validated();
        $sharedFileId = $data['shared_file_id'];
        $permissionId = $data['shared_permission_id'];
        $sharedFileRecord = SharedFile::where('id', $sharedFileId)->first();

        if ($permissionId == $sharedFileRecord->shared_permission_id) {
            return response()->json([
                'message' => 'No changes made.'
            ], 200);
        }

        $sharedFileRecord->update([
            'shared_permission_id' => $permissionId
        ]);

        return response()->json([
            'message' => 'User access to file updated successfully.'
        ], 200);
    }

    // Remove user access to file
    public function revokeFileAccess(RevokeFileAccessRequest $request)
    {
        $data = $request->validated();
        $sharedFileId = $data['shared_file_id'];
        $sharedFileRecord = SharedFile::where('id', $sharedFileId)->first();

        $goHost = env('GO_HOST');
        $goPort = env('GO_PORT');
        $url = "http://{$goHost}:{$goPort}/encrypt";

        $fileRecord = File::where('id', $sharedFileRecord->file_id)->first();
        $aesKey = $fileRecord->aes_key;
        $user = User::where('id', $fileRecord->uploaded_by_user_id)->first();
        $masterPublicKey = $user->master_public_key;

        $userRemoved = SharedFile::where('id', $sharedFileId)->value('shared_with_user_id');
        $usersWithAccess = SharedFile::where('file_id', $sharedFileRecord->file_id)->pluck('shared_with_user_id')->toArray();
        $usersWithoutRemoved = array_diff($usersWithAccess, [$userRemoved]);

        $goRequest = [
            'aesKey' => $aesKey,
            'UserID' => array_merge([$user->id], $usersWithoutRemoved),
            'ownerMPK' => $masterPublicKey,
        ];

        $goResponse = Http::post($url, $goRequest);

        if ($goResponse->successful()) {
            $goData = $goResponse->json();
            $sharedFileRecord->delete();

            $fileRecord->update([
                'ciphertext' => $goData,
            ]);

            return response()->json([
                'message' => 'File unshared with the user successfully.'
            ], 200);
        } else {
            return response()->json([
                'message' => 'Failed to fetch data from Golang.'
            ], 500);
        }
    }

    // Download file uploaded by user
    public function downloadFromMyFiles($id)
    {
        $userId = Auth::id();
        $fileRecord = File::where('id', $id)->where('uploaded_by_user_id', $userId)->first();

        if (!$fileRecord) {
            return response()->json([
                'message' => 'File not found.'
            ], 404);
        }

        $fileName = $fileRecord->file_name;
        $fileMime = $fileRecord->file_mime;
        $hash = $fileRecord->ipfs_cid;
        $encryptedFile = ipfs()->get($hash);
        $aesKey = Key::loadFromAsciiSafeString($fileRecord->aes_key);

        $file = Crypto::decrypt($encryptedFile, $aesKey);
        return response($file)
            ->withHeaders([
                'Content-Type' => $fileMime,
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ]);
    }

    // Download file shared to user
    public function downloadFromSharedWithMe($id)
    {
        $userId = Auth::id();
        // $user = User::find($userId);
        $fileExist = File::join('shared_files', 'shared_files.file_id', '=', 'files.id')
            ->where('shared_files.file_id', $id)
            ->where('shared_files.shared_with_user_id', $userId)->first();

        if (!$fileExist) {
            return response()->json([
                'message' => 'File not found.'
            ], 404);
        }

        $fileRecord = File::where('id', $id)->first();
        $fileName = $fileRecord->file_name;
        $fileMime = $fileRecord->file_mime;
        $hash = $fileRecord->ipfs_cid;
        $encryptedFile = ipfs()->get($hash);

        $goHost = env('GO_HOST');
        $goPort = env('GO_PORT');
        $fileOwner = User::find($fileRecord->uploaded_by_user_id);
        $url = "http://{$goHost}:{$goPort}/decrypt";

        $goRequest = [
            'userID' => $userId,
            'cipher' => $fileRecord->ciphertext,
            'publicKey' => $fileOwner->master_public_key,
            'secretKey' => $fileOwner->master_secret_key,
        ];

        $goResponse = Http::post($url, $goRequest);
        if ($goResponse->successful()) {
            $goData = $goResponse->json();
            $aesKey = Key::loadFromAsciiSafeString($goData['aes_key']);
            $file = Crypto::decrypt($encryptedFile, $aesKey);

            return response($file)
                ->withHeaders([
                    'Content-Type' => $fileMime,
                    'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                ]);
        } else {
            return response()->json([
                'message' => 'Failed to fetch data from Golang.'
            ], 500);
        }
    }

    // Edit file metadata uploaded by user
    public function editAtMyFiles(EditFileRequest $request, $id)
    {
        $userId = Auth::id();
        $fileRecord = File::where('id', $id)->where('uploaded_by_user_id', $userId)->first();

        if (!$fileRecord) {
            return response()->json([
                'message' => 'File not found.'
            ], 404);
        }

        $data = $request->validated();
        $existingFileExtension = pathinfo($fileRecord->file_name, PATHINFO_EXTENSION);
        $newFileName = $data['file_name'] . '.' . $existingFileExtension;
        $newFileDescription = $data['file_description'];

        if ($newFileName === $fileRecord->file_name && $newFileDescription === $fileRecord->file_description) {
            return response()->json([
                'message' => 'No changes made.'
            ], 200);
        }

        $checkExist = File::where('id', '!=', $id)->where('uploaded_by_user_id', $userId)
            ->where('file_name', $newFileName)->exists();

        if ($checkExist == true) {
            return response()->json([
                'message' => 'A file with the same name already exists.'
            ], 422);
        }

        $fileRecord->update([
            'file_name' => $newFileName,
            'file_description' => $newFileDescription
        ]);

        return response()->json([
            'message' => 'File metadata edited successfully.'
        ], 200);
    }

    // Edit file metadata shared to user
    public function editAtSharedWithMe(EditFileRequest $request, $id)
    {
        $userId = Auth::id();
        $fileExist = File::join('shared_files', 'shared_files.file_id', '=', 'files.id')
            ->where('shared_files.file_id', $id)->where('shared_files.shared_with_user_id', $userId)->first();

        if (!$fileExist) {
            return response()->json([
                'message' => 'File not found.'
            ], 404);
        }

        $fileRecord = File::where('id', $id)->first();
        $data = $request->validated();
        $existingFileExtension = pathinfo($fileRecord->file_name, PATHINFO_EXTENSION);
        $newFileName = $data['file_name'] . '.' . $existingFileExtension;
        $newFileDescription = $data['file_description'];

        if ($newFileName === $fileRecord->file_name && $newFileDescription === $fileRecord->file_description) {
            return response()->json([
                'message' => 'No changes made.'
            ], 200);
        }

        $fileOwnerId = $fileRecord->uploaded_by_user_id;
        $checkExist = File::where('id', '!=', $id)->where('uploaded_by_user_id', $fileOwnerId)
            ->where('file_name', $newFileName)->exists();

        if ($checkExist == true) {
            return response()->json([
                'message' => 'A file with the same name already exists at the owner side.'
            ], 422);
        }

        $fileRecord->update([
            'file_name' => $newFileName,
            'file_description' => $newFileDescription
        ]);

        return response()->json([
            'message' => 'File metadata edited successfully.'
        ], 200);
    }

    // Delete file
    public function destroy($id)
    {
        $userId = Auth::id();
        $file = File::where('id', $id)->where('uploaded_by_user_id', $userId)->first();

        if (!$file) {
            return response()->json([
                'message' => 'File not found.'
            ], 404);
        }

        $sharedFileRecords = SharedFile::where('file_id', $id)->delete();
        $shareRequestRecords = ShareRequest::where('requested_file_id', $id)->delete();
        $file->delete();

        return response()->json([
            'message' => 'File deleted successfully.'
        ], 200);
    }

    public function getDeptToShare()
    {
        $departments = Department::all();
        return response()->json($departments);
    }

    public function getUsersToShareFile()
    {
        $userId = Auth::id();
        $users = User::where('id', '!=', $userId)->orderBy('email')->get();
        return response()->json($users);
    }

    public function getUsersWithViewerAccess($id)
    {
        $viewers = SharedFile::join('permissions', 'permissions.id', '=', 'shared_files.shared_permission_id')
            ->join('users', 'users.id', '=', 'shared_files.shared_with_user_id')
            ->join('departments', 'departments.id', '=', 'shared_files.shared_with_department_id')
            ->select('shared_files.*', 'users.name', 'users.email', 'permissions.permission_name', 'departments.dep_name')
            ->where('shared_files.file_id', $id)
            ->where('permission_name', 'Viewer')
            ->orderBy('departments.dep_name')
            ->orderBy('users.name')
            ->get();

        return response()->json($viewers);
    }

    public function getUsersWithEditorAccess($id)
    {
        $editors = SharedFile::join('permissions', 'permissions.id', '=', 'shared_files.shared_permission_id')
            ->join('users', 'users.id', '=', 'shared_files.shared_with_user_id')
            ->join('departments', 'departments.id', '=', 'shared_files.shared_with_department_id')
            ->select('shared_files.*', 'users.name', 'users.email', 'permissions.permission_name', 'departments.dep_name')
            ->where('shared_files.file_id', $id)
            ->where('permission_name', 'Editor')
            ->orderBy('departments.dep_name')
            ->orderBy('users.name')
            ->get();

        return response()->json($editors);
    }

    public function getFileEditInfo($id)
    {
        $file = File::where('id', $id)->first();
        $fileNameWithoutExtension = pathinfo($file->file_name, PATHINFO_FILENAME);

        return response()->json([
            'file_name' => $fileNameWithoutExtension,
            'file_description' => $file->file_description
        ]);
    }
}
