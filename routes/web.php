<?php

use App\Http\Controllers\IncidentRoomController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Incident Room API Routes
Route::prefix('api/incident-room')->group(function () {
    Route::post('process-reports', [IncidentRoomController::class, 'processReports']);
    Route::post('submit-form', [IncidentRoomController::class, 'submitForm']);
    Route::get('test-example', [IncidentRoomController::class, 'testWithExampleData']);
    Route::get('sitreps', [IncidentRoomController::class, 'listSitreps']);
    Route::get('sitreps/{incidentId}', [IncidentRoomController::class, 'getSitrep']);
});

//// Debug route to check storage
//Route::get('debug/storage', function() {
//    $files = \Illuminate\Support\Facades\Storage::disk('local')->files('sitreps');
//    return response()->json([
//        'files' => $files,
//        'storage_path' => storage_path('app/private'),
//        'files_exist' => count($files) > 0
//    ]);
//});

// Dashboard route
Route::get('dashboard', function () {
    return view('dashboard');
});