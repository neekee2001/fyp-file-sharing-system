<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\File;
use Illuminate\Http\Request;

class FileController extends Controller
{
    // Display files uploaded by user
    public function showMyFiles()
    {
        //
    }

    // Display files shared to user
    public function showSharedWithMe()
    {
        //
    }

    // Upload file
    public function store(Request $request)
    {
        //
    }

    // Share file
    public function share(Request $request)
    {
        //
    }

    // Remove user access to file
    public function removeAccess(Request $request)
    {
        //
    }

    // Download file uploaded by user
    public function downloadFromMyFiles(Request $request)
    {
        //
    }

    // Download file shared to user
    public function downloadFromSharedWithMe(Request $request)
    {
        //
    }

    // Rename file uploaded by user
    public function renameAtMyFiles(Request $request)
    {
        //
    }

    // Rename file shared to user
    public function renameAtSharedWithMe(Request $request)
    {
        //
    }

    // Delete file
    public function destroy(File $file)
    {
        //
    }
}
