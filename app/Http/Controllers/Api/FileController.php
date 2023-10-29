<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShareFileRequest;
use App\Http\Requests\StoreFileRequest;
use App\Models\File;
use App\Models\SharedFile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
                        ->select('shared_files.*', 'files.file_name', 'users.name', 'permissions.permission_name')
                        ->where('shared_files.shared_with_user_id', $userId)
                        ->orderByDesc('shared_files.created_at')
                        ->get();
        return response()->json($sharedFiles);
    }

    // Display all files uploaded by other users
    public function showAllFiles()
    {

    }

    // Display all requests from other users to share the file
    public function showShareRequests()
    {

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
            $fileHash = ipfs()->addFromPath($filePath);
            $userId = Auth::id();

            File::create([
                'file_name' => $fileName,
                'file_description' => $fileDescription,
                'file_size' => $fileSize,
                'file_mime' => $fileMime,
                'ipfs_cid' => $fileHash,
                'uploaded_by_user_id' => $userId,
            ]);

            return response()->json([
                'message' => 'File uploaded successfully.'
            ], 201);
        }
    }

    // Request file owner to share the file
    public function requestToShare(Request $request)
    {

    }

    // Approve request sent by other users to share the file
    public function approveRequest(Request $request)
    {

    }

    // Share file
    public function share(ShareFileRequest $request)
    {
        $data = $request->validated();
        $fileId = $data['file_id'];
        $sharedWithUserId = $data['shared_with_user_id'];

        $userId = Auth::id();
        $fileRecord = File::where('id', $fileId)->where('uploaded_by_user_id', $userId)->first();

        if (!$fileRecord) {
            return response()->json([
                'message' => 'File not found.'
            ], 404);
        }

        $checkExist = SharedFile::where('file_id', $fileId)->where('shared_with_user_id', $sharedWithUserId)->exists();

        if ($checkExist == true) {
            return response()->json([
                'message' => 'File shared with this user already.'
            ], 422);
        }

        SharedFile::create([
            'file_id' => $fileId,
            'shared_with_user_id' => $sharedWithUserId,
            'shared_permission_id' => $data['permission_id'],
        ]);

        return response()->json([
            'message' => 'File shared successfully.'
        ], 201);
    }

    // Update user access to file
    public function updateAccess(Request $request)
    {
        //
    }

    // Remove user access to file
    public function revokeAccess(Request $request)
    {
        //
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
        $file = ipfs()->get($hash);

        // if ($file === false) {
        //     return response()->json([
        //         'error' => 'File not found on IPFS'
        //     ], 404);
        // }
    
        return response($file)
            ->withHeaders([
                'Content-Type' => $fileMime,
                'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
            ]);
    }

    // Download file shared to user
    public function downloadFromSharedWithMe($id)
    {
        $userId = Auth::id();
        $fileRecord = SharedFile::join('files', 'files.id', '=', 'shared_files.file_id')
                        ->where('file_id', $id)->where('shared_with_user_id', $userId)->first();

        if (!$fileRecord) {
            return response()->json([
                'message' => 'File not found.'
            ], 404);
        }

        $fileName = $fileRecord->file_name;
        $fileMime = $fileRecord->file_mime;
        $hash = $fileRecord->ipfs_cid;
        $file = ipfs()->get($hash);

        // if ($file === false) {
        //     return response()->json([
        //         'error' => 'File not found on IPFS'
        //     ], 404);
        // }
    
        return response($file)
            ->withHeaders([
                'Content-Type' => $fileMime,
                'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
            ]);
    }

    // Edit file metadata uploaded by user
    public function editAtMyFiles(Request $request)
    {
        //
    }

    // Edit file metadata shared to user
    public function editAtSharedWithMe(Request $request)
    {
        //
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
        $file->delete();

        return response()->json([
            'message' => 'File deleted successfully.'
        ], 200);
    }

    public function getUsersToShareFile()
    {
        $userId = Auth::id();
        $users = User::where('id', '!=', $userId)->orderBy('email')->get();
        return response()->json($users);
    }
}
