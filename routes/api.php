<?php

use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\DemoController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\ExplainController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\MedicationController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\Api\TranscriptController;
use App\Http\Controllers\Api\VisitController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — /api/v1/
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // -------------------------------------------------------
    // Auth (public)
    // -------------------------------------------------------
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    // -------------------------------------------------------
    // Auth (authenticated)
    // -------------------------------------------------------
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('user', [AuthController::class, 'user']);
        });

        // ----- Module 1: Core Health — Patients -----
        Route::prefix('patients/{patient}')->group(function () {
            Route::get('/', [PatientController::class, 'show']);
            Route::patch('/', [PatientController::class, 'update']);
            Route::get('visits', [PatientController::class, 'visits']);
            Route::get('conditions', [PatientController::class, 'conditions']);
            Route::post('conditions', [PatientController::class, 'addCondition']);
            Route::get('health-record', [PatientController::class, 'healthRecord']);
            Route::get('documents', [PatientController::class, 'documents']);
            Route::post('documents', [PatientController::class, 'uploadDocument']);
            Route::get('prescriptions', [PrescriptionController::class, 'patientPrescriptions']);
            Route::get('prescriptions/interactions', [PrescriptionController::class, 'patientInteractions']);
        });

        // Documents (standalone)
        Route::get('documents/{document}', [DocumentController::class, 'show']);

        // ----- Module 2: Companion Scribe — Visits & Transcripts -----
        Route::post('visits', [VisitController::class, 'store']);

        Route::prefix('visits/{visit}')->group(function () {
            // Module 3: PostVisit AI
            Route::get('/', [VisitController::class, 'show']);
            Route::get('summary', [VisitController::class, 'summary']);

            // Transcript
            Route::post('transcript', [TranscriptController::class, 'store']);
            Route::get('transcript', [TranscriptController::class, 'show']);
            Route::post('transcript/process', [TranscriptController::class, 'process']);
            Route::get('transcript/status', [TranscriptController::class, 'status']);

            // Chat
            Route::post('chat', [ChatController::class, 'sendMessage']);
            Route::get('chat/history', [ChatController::class, 'history']);

            // Explain
            Route::post('explain', [ExplainController::class, 'explain']);

            // Prescriptions (visit-scoped)
            Route::get('prescriptions', [PrescriptionController::class, 'visitPrescriptions']);

            // Feedback / Messages
            Route::post('messages', [FeedbackController::class, 'store']);
            Route::get('messages', [FeedbackController::class, 'index']);
        });

        // Mark message as read
        Route::patch('messages/{message}/read', [FeedbackController::class, 'markRead']);

        // ----- Module 5: Medications -----
        Route::get('medications/search', [MedicationController::class, 'search']);
        Route::get('medications/{rxnormCode}', [MedicationController::class, 'show']);
        Route::get('medications/{rxnormCode}/interactions', [MedicationController::class, 'interactions']);

        // ----- Module 7: Doctor Dashboard (doctor role required) -----
        Route::prefix('doctor')->middleware('role:doctor,admin')->group(function () {
            Route::get('dashboard', [DoctorController::class, 'dashboard']);
            Route::get('patients', [DoctorController::class, 'patients']);
            Route::get('patients/{patient}', [DoctorController::class, 'patientDetail']);
            Route::get('patients/{patient}/visits', [DoctorController::class, 'patientVisits']);
            Route::get('patients/{patient}/engagement', [DoctorController::class, 'engagement']);
            Route::get('patients/{patient}/chat-audit', [DoctorController::class, 'chatAudit']);
            Route::get('notifications', [DoctorController::class, 'notifications']);
            Route::post('messages/{message}/reply', [DoctorController::class, 'reply']);
        });

        // ----- Module 8: Audit (doctor/admin role required) -----
        Route::get('audit/logs', [AuditController::class, 'index'])
            ->middleware('role:doctor,admin');
    });

    // -------------------------------------------------------
    // Demo Engine (no auth — easy demo access)
    // -------------------------------------------------------
    Route::prefix('demo')->group(function () {
        Route::post('start', [DemoController::class, 'start']);
        Route::get('status', [DemoController::class, 'status']);
        Route::post('reset', [DemoController::class, 'reset']);
        Route::post('simulate-alert', [DemoController::class, 'simulateAlert']);
    });
});
