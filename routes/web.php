<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('dashboard');
});

Route::get('/dashboard', function () {
    return view('dashboard');
});

// People routes
Route::get('/people/create', function () {
    return view('people.create');
});

// NVR routes
Route::get('/nvrs/create', function () {
    return view('nvrs.create');
});

// Camera routes
Route::get('/cameras/create', function () {
    return view('cameras.create');
});

// Search route
Route::get('/search', function () {
    return view('search');
});

// Search by image - API
Route::post('/search', [PersonController::class, 'searchByImage']);

// Reports route
Route::get('/reports', function () {
    return view('reports');
});
