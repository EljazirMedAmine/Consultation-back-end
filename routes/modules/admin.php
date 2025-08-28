<?php

use App\Http\Controllers\Admin\UserController;

Route::post('users/guest', [UserController::class, 'createGuestUser']);
Route::middleware('role:admin')->prefix('admin')->group(function () {
    Route::get('users', [UserController::class, 'index']);
    Route::post('users', [UserController::class, 'store']);
    Route::get('users/{id}', [UserController::class, 'show']);
    Route::put('users/{id}', [UserController::class, 'update']);
    Route::delete('users/{id}', [UserController::class, 'destroy']);
    Route::post('users/guest', [UserController::class, 'createGuestUser']);
    Route::post('users/{id}/reject-doctor', [UserController::class, 'rejectUser']);
    Route::post('users/{id}/validate-doctor', [UserController::class, 'validateUser']);
});
