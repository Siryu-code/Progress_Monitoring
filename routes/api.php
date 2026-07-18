<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TimelineController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Developer Authentication
Route::post('/auth/login', [AuthController::class, 'login']);

// Client Access
Route::post('/projects/access', [ProjectController::class, 'access']);

// Shared Dashboard
Route::get('/projects/{project}/dashboard', [ProjectController::class, 'dashboard']);

// Timeline
Route::get('/projects/{project}/timelines', [TimelineController::class, 'index']);

// Evidence
Route::get('/timelines/{timeline}/evidences', [EvidenceController::class, 'index']);


/*
|--------------------------------------------------------------------------
| Protected Routes (Developer Only)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // Authentication
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Timeline
    Route::post('/projects/{project}/timelines', [TimelineController::class, 'store']);
    Route::patch('/timelines/{timeline}', [TimelineController::class, 'update']);
    Route::delete('/timelines/{timeline}', [TimelineController::class, 'destroy']);

    // Milestone
    Route::patch('/milestones/{milestone}', [MilestoneController::class, 'update']);

    // Evidence
    Route::post('/timelines/{timeline}/evidences', [EvidenceController::class, 'store']);
    Route::delete('/evidences/{evidence}', [EvidenceController::class, 'destroy']);

});