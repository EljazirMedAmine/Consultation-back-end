<?php

use App\Http\Controllers\Patient\ComplteProfileController;
use App\Http\Controllers\Patient\MedicalRecordController;

Route::middleware('role:patient')->prefix('patient')->group(function () {
    Route::get('profile/getData', [ComplteProfileController::class, 'show']);
    Route::get('mes-medecins/{id}', [\App\Http\Controllers\Patient\MedecinController::class, 'show']);
    Route::post('profile', [ComplteProfileController::class, 'store']);
    Route::put('profile/update', [ComplteProfileController::class, 'update']);
    Route::get('doctors', [\App\Http\Controllers\Patient\MedecinController::class, 'getDoctors']);
    Route::post('demande-consultation', [\App\Http\Controllers\Patient\DemandeConsultationController::class, 'store']);
    Route::get('demande-consultation', [\App\Http\Controllers\Patient\DemandeConsultationController::class, 'index']);
    Route::get('demande-consultation/{id}', [\App\Http\Controllers\Patient\DemandeConsultationController::class, 'show']);
    Route::get('doctor/{doctorId}/busy-hours', [\App\Http\Controllers\Patient\ConsultationController::class, 'getDoctorBusyHours']);
    Route::get('consultations', [\App\Http\Controllers\Patient\ConsultationController::class, 'index']);
    Route::get('consultation/details/{type}/{id}', [\App\Http\Controllers\Patient\ConsultationController::class, 'getDetailsByTypeAndId']);
    Route::get('recent-events', [\App\Http\Controllers\Patient\ConsultationController::class, 'lastFourEvents']);
    Route::prefix('medical-record')->group(function () {
        Route::get('/', [MedicalRecordController::class, 'index']);
        Route::get('/entry/{entryId}', [MedicalRecordController::class, 'showEntry']);
        Route::get('/all-entries-with-media', [MedicalRecordController::class, 'allEntriesWithMedia']);
    });
});
