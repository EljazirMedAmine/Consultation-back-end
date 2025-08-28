<?php

use App\Http\Controllers\Medecin\ConsultationController;
use App\Http\Controllers\Medecin\HomeController;
use App\Http\Controllers\Medecin\MedicalRecordController;
use App\Http\Controllers\Medecin\PatientController;


Route::middleware('role:medecin')->prefix('medecin')->group(function () {
    // Routes pour le profil du médecin
    Route::get('profile', [HomeController::class, 'show']);
    Route::post('profile', [HomeController::class, 'store']);
    Route::put('profile', [HomeController::class, 'update']);
    Route::get('profile/specialities', [HomeController::class, 'getSpecialities']);

    // Routes pour les consultations
    Route::get('consultations', [ConsultationController::class, 'index']);
    Route::get('consultations/{id}', [ConsultationController::class, 'show']);
    Route::post('consultations', [ConsultationController::class, 'store']);
    Route::put('consultations/{id}', [ConsultationController::class, 'update']);
    Route::delete('consultations/{id}', [ConsultationController::class, 'destroy']);

    // Routes pour les demandes de consultation
    Route::get('demandes-consultation', [ConsultationController::class, 'index']);
    Route::post('demandes-consultation/{id}/reject', [ConsultationController::class, 'reject']);
    Route::post('demandes-consultation/{id}/validate', [ConsultationController::class, 'approveConsultation']);
    Route::get('consultation/details/{type}/{id}', [ConsultationController::class, 'getDetailsByTypeAndId']);
    Route::get('patients', [PatientController::class, 'index']);
    // Dans routes/modules/medecin.php
    Route::get('google/auth', [ConsultationController::class, 'getGoogleAuthUrl']);
    Route::get('google/callback', [ConsultationController::class, 'handleGoogleCallback']);
    // Routes pour le calendrier
    Route::get('calendrier/my-consultations-list', [ConsultationController::class, 'myConsultationsList']);
    Route::get('calendrier/recent-events', [ConsultationController::class, 'recentEvents']);
    // routes/modules/medecin.php (ajouter ces routes)
// Routes pour les dossiers médicaux
    Route::prefix('medical-records')->group(function () {
        Route::get('/', [MedicalRecordController::class, 'index']);
        Route::get('/{patientId}', [MedicalRecordController::class, 'show']);
        Route::get('/entry/{entryId}', [MedicalRecordController::class, 'getEntry']);
        Route::post('/consultation/{consultationId}', [MedicalRecordController::class, 'store']);
        Route::put('/entry/{entryId}', [MedicalRecordController::class, 'update']);
    });
});
