<?php

use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\DemoController;
use App\Http\Controllers\Api\DemoScenarioController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\ExplainController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\MedicalLookupController;
use App\Http\Controllers\Api\MedicationController;
use App\Http\Controllers\Api\ObservationController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\Api\ReferenceController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\TranscriptController;
use App\Http\Controllers\Api\UploadTokenController;
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
    Route::prefix('auth')->middleware('throttle:auth')->group(function () {
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
        Route::prefix('patients/{patient}')->middleware('audit')->group(function () {
            Route::get('/', [PatientController::class, 'show']);
            Route::patch('/', [PatientController::class, 'update']);
            Route::get('visits', [PatientController::class, 'visits']);
            Route::get('conditions', [PatientController::class, 'conditions']);
            Route::post('conditions', [PatientController::class, 'addCondition']);
            Route::get('health-record', [PatientController::class, 'healthRecord']);
            Route::get('observations', [PatientController::class, 'observations']);
            Route::get('documents', [PatientController::class, 'documents']);
            Route::post('documents', [PatientController::class, 'uploadDocument']);
            Route::get('prescriptions', [PrescriptionController::class, 'patientPrescriptions']);
            Route::get('prescriptions/interactions', [PrescriptionController::class, 'patientInteractions']);
        });

        // Documents (standalone)
        Route::middleware('audit')->group(function () {
            Route::get('documents/{document}', [DocumentController::class, 'show']);
            Route::get('documents/{document}/download', [DocumentController::class, 'download']);
            Route::get('documents/{document}/thumbnail', [DocumentController::class, 'thumbnail']);
            Route::get('documents/{document}/analysis', [DocumentController::class, 'analysisStatus']);
            Route::delete('documents/{document}', [DocumentController::class, 'destroy']);
        });

        // Practitioners (for visit form dropdown)
        Route::get('practitioners', [VisitController::class, 'practitioners']);
        Route::post('practitioners', [VisitController::class, 'storePractitioner']);

        // ----- Module 2: Companion Scribe — Visits & Transcripts -----
        Route::post('visits', [VisitController::class, 'store'])->middleware('audit');

        Route::prefix('visits/{visit}')->middleware('audit')->group(function () {
            // Module 3: PostVisit AI
            Route::get('/', [VisitController::class, 'show']);
            Route::get('summary', [VisitController::class, 'summary']);

            // Transcript
            Route::post('transcript', [TranscriptController::class, 'store']);
            Route::post('transcript/upload-audio', [TranscriptController::class, 'uploadAudio']);
            Route::post('transcript/transcribe-chunk', [TranscriptController::class, 'transcribeChunk']);
            Route::post('transcript/save-chunk', [TranscriptController::class, 'saveChunk']);
            Route::get('transcript', [TranscriptController::class, 'show']);
            Route::post('transcript/process', [TranscriptController::class, 'process']);
            Route::get('transcript/status', [TranscriptController::class, 'status']);

            // Chat
            Route::post('chat', [ChatController::class, 'sendMessage']);
            Route::get('chat/history', [ChatController::class, 'history']);

            // Explain
            Route::post('explain', [ExplainController::class, 'explain']);

            // Observations (visit-scoped)
            Route::get('observations', [ObservationController::class, 'index']);
            Route::get('observations/{observation}', [ObservationController::class, 'show']);

            // Prescriptions (visit-scoped)
            Route::get('prescriptions', [PrescriptionController::class, 'visitPrescriptions']);

            // Documents (visit-scoped)
            Route::get('documents', [DocumentController::class, 'visitDocuments']);
            Route::post('documents', [DocumentController::class, 'store']);

            // Upload tokens (QR code mobile upload)
            Route::post('upload-tokens', [UploadTokenController::class, 'store']);

            // Feedback / Messages
            Route::post('messages', [FeedbackController::class, 'store']);
            Route::get('messages', [FeedbackController::class, 'index']);
        });

        // Mark message as read
        Route::patch('messages/{message}/read', [FeedbackController::class, 'markRead'])->middleware('audit');

        // ----- Module 5: Medications -----
        Route::middleware('audit')->group(function () {
            Route::get('medications/search', [MedicationController::class, 'search']);
            Route::get('medications/{rxnormCode}', [MedicationController::class, 'show']);
            Route::get('medications/{rxnormCode}/interactions', [MedicationController::class, 'interactions']);
            Route::get('medications/{rxnormCode}/adverse-events', [MedicationController::class, 'adverseEvents']);
            Route::get('medications/{rxnormCode}/label', [MedicationController::class, 'label']);
        });

        // ----- Module 6: Medical Lookup (NIH + DailyMed) -----
        Route::prefix('lookup')->middleware('audit')->group(function () {
            Route::get('conditions', [MedicalLookupController::class, 'searchConditions']);
            Route::get('drugs', [MedicalLookupController::class, 'searchDrugs']);
            Route::get('procedures', [MedicalLookupController::class, 'searchProcedures']);
            Route::get('drug-label', [MedicalLookupController::class, 'drugLabel']);
        });

        // ----- Module 9: Medical References -----
        Route::prefix('references')->middleware('audit')->group(function () {
            Route::get('/', [ReferenceController::class, 'index']);
            Route::get('{reference}', [ReferenceController::class, 'show']);
            Route::post('{reference}/verify', [ReferenceController::class, 'verify']);
            Route::post('verify-pmid', [ReferenceController::class, 'verifyPmid']);
        });

        // ----- Module 7: Doctor Dashboard (doctor role required) -----
        Route::prefix('doctor')->middleware(['role:doctor,admin', 'audit'])->group(function () {
            Route::get('dashboard', [DoctorController::class, 'dashboard']);
            Route::get('alerts', [DoctorController::class, 'alerts']);
            Route::get('patients', [DoctorController::class, 'patients']);
            Route::get('patients/{patient}', [DoctorController::class, 'patientDetail']);
            Route::get('patients/{patient}/visits', [DoctorController::class, 'patientVisits']);
            Route::get('patients/{patient}/engagement', [DoctorController::class, 'engagement']);
            Route::get('patients/{patient}/chat-audit', [DoctorController::class, 'chatAudit']);
            Route::get('patients/{patient}/observations', [DoctorController::class, 'patientObservations']);
            Route::get('notifications', [DoctorController::class, 'notifications']);
            Route::post('messages/{message}/reply', [DoctorController::class, 'reply']);
        });

        // ----- Module 8: Audit (doctor/admin role required) -----
        Route::get('audit/logs', [AuditController::class, 'index'])
            ->middleware(['role:doctor,admin', 'audit']);
        Route::get('audit/export', [AuditController::class, 'export'])
            ->middleware(['role:doctor,admin', 'audit']);

        // ----- Settings -----
        Route::prefix('settings')->group(function () {
            Route::get('ai-tier', [SettingsController::class, 'getAiTier']);
            Route::put('ai-tier', [SettingsController::class, 'setAiTier']);
        });

        // Upload token status (polling from desktop)
        Route::get('upload-tokens/{token}/status', [UploadTokenController::class, 'status']);
    });

    // -------------------------------------------------------
    // Demo Engine (no auth — easy demo access)
    // -------------------------------------------------------
    Route::prefix('demo')->group(function () {
        Route::post('start', [DemoController::class, 'start']);
        Route::get('status', [DemoController::class, 'status']);
        Route::post('reset', [DemoController::class, 'reset']);
        Route::post('simulate-alert', [DemoController::class, 'simulateAlert']);

        // Scenario picker
        Route::get('scenarios', [DemoScenarioController::class, 'index']);
        Route::get('scenarios/{scenario}/photo', [DemoScenarioController::class, 'photo']);
        Route::post('start-scenario', [DemoScenarioController::class, 'startScenario']);
        Route::post('switch-to-doctor', [DemoScenarioController::class, 'switchToDoctor']);
    });
});
