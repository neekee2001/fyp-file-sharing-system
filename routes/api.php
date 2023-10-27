<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->group(function() {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/permissions', [PermissionController::class, 'index']);
    Route::get('/users-to-share', [FileController::class, 'getUsersToShareFile']);
    Route::get('/myfiles', [FileController::class, 'showMyFiles']);
    Route::get('/shared-with-me', [FileController::class, 'showSharedWithMe']);
    Route::post('/file/upload', [FileController::class, 'store']);
    Route::post('/file/share', [FileController::class, 'share']);
    Route::get('/file/download-myfiles/{id}', [FileController::class, 'downloadFromMyFiles']);
    Route::get('/file/download-shared-with-me/{id}', [FileController::class, 'downloadFromSharedWithMe']);
    Route::delete('/file/delete/{id}', [FileController::class, 'destroy']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::get('/departments', [DepartmentController::class, 'index']);
Route::get('/roles', [RoleController::class, 'index']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
