<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\FileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/storage/{path}', function (Request $request, $path) {
    $filePath = storage_path('app/public/' . $path);

    if (!file_exists($filePath)) {
        return response()->json(['error' => 'File not found'], 404);
    }

    // Vérification des permissions du fichier
    if (!is_readable($filePath)) {
        return response()->json(['error' => 'Forbidden - File not accessible'], 403);
    }

    return response()->file($filePath);
})->where('path', '.*');

Route::prefix('v1')->group(function () {
    Route::post('/guest', [\App\Http\Controllers\Admin\UserController::class, 'createGuestUser']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::middleware(['jwt.auth'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('files/upload-temp', [FileController::class, 'uploadTemp']);
        Route::post('files/cleanup-temp', [FileController::class, 'cleanupTemp']);

        require __DIR__ . '/modules/admin.php';
        require __DIR__ . '/modules/patient.php';
        require __DIR__ . '/modules/medecin.php';
    });
});


