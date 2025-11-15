<?php

use App\Http\Controllers\IncidentRoomController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Incident Room API Routes
Route::prefix('api/incident-room')->group(function () {
    Route::post('process-reports', [IncidentRoomController::class, 'processReports']);
    Route::get('test-example', [IncidentRoomController::class, 'testWithExampleData']);
    Route::get('sitreps', [IncidentRoomController::class, 'listSitreps']);
    Route::get('sitreps/{incidentId}', [IncidentRoomController::class, 'getSitrep']);
});

// Dashboard route
Route::get('dashboard', function () {
    return view('dashboard');
});