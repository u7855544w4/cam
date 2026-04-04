<?php

use App\Http\Controllers\PersonController;
use App\Http\Controllers\CameraController;
use App\Http\Controllers\DetectionController;
use Illuminate\Support\Facades\Route;

// API Routes for Face Recognition System

// People routes
Route::apiResource('people', PersonController::class);
Route::post('people/search', [PersonController::class, 'searchByImage']);

// Cameras routes
Route::apiResource('cameras', CameraController::class);
Route::get('cameras/{camera}/stream', [CameraController::class, 'stream']);

// Detections routes
Route::get('detections', [DetectionController::class, 'index']);
Route::post('detections', [DetectionController::class, 'store']);
Route::get('detections/{detection}', [DetectionController::class, 'show']);
Route::get('detections/today', [DetectionController::class, 'today']);
Route::get('detections/statistics', [DetectionController::class, 'statistics']);

// Dashboard route
Route::get('dashboard', [DetectionController::class, 'statistics']);