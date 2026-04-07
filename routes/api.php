<?php

use App\Http\Controllers\PersonController;
use App\Http\Controllers\CameraController;
use App\Http\Controllers\DetectionController;
use App\Http\Controllers\NvrController;
use Illuminate\Support\Facades\Route;

// API Routes for Face Recognition System

// People routes
Route::apiResource('people', PersonController::class);
Route::post('people/search', [PersonController::class, 'searchByImage']);

// NVR routes (before cameras)
Route::apiResource('nvrs', NvrController::class);
Route::get('nvrs/{nvr}/channels', [NvrController::class, 'channels']);
Route::get('nvrs/{nvr}/test', [NvrController::class, 'testConnection']);

// Cameras routes
Route::apiResource('cameras', CameraController::class);
Route::get('cameras/{camera}/stream', [CameraController::class, 'stream']);
Route::get('cameras/{camera}/test', [CameraController::class, 'testConnection']);

// Detections routes
Route::get('detections', [DetectionController::class, 'index']);
Route::post('detections', [DetectionController::class, 'store']);
Route::get('detections/{detection}', [DetectionController::class, 'show']);
Route::get('detections/today', [DetectionController::class, 'today']);
Route::get('detections/statistics', [DetectionController::class, 'statistics']);

// Dashboard route
Route::get('dashboard', [DetectionController::class, 'statistics']);

// Face Recognition Service Integration
use App\Http\Controllers\Api\FaceRecognitionController;

Route::prefix('face')->group(function () {
    Route::get('health', [FaceRecognitionController::class, 'health']);
    Route::post('detect', [FaceRecognitionController::class, 'detect']);
    Route::post('encode', [FaceRecognitionController::class, 'encode']);
    Route::post('recognize', [FaceRecognitionController::class, 'recognize']);
    Route::post('search', [FaceRecognitionController::class, 'search']);
});