<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
Route::get('/patients', [App\Http\Controllers\PatientController::class, 'index'])->name('patients.index');

// Workspace routes
Route::get('/workspace/patients/{patient}', [App\Http\Controllers\PatientWorkspaceController::class, 'show'])
    ->name('workspace.patient.show');
Route::post('/workspace/patients/{patient}/visits', [App\Http\Controllers\PatientWorkspaceController::class, 'storeVisit'])
    ->name('workspace.patient.visits.store');
Route::post('/workspace/visits/{visit}/treatments', [App\Http\Controllers\PatientWorkspaceController::class, 'storeTreatment'])
    ->name('workspace.visit.treatments.store');

// API Documentation (dev-only)
Route::get('/docs/api', [App\Http\Controllers\SwaggerController::class, 'index'])
    ->name('docs.api');
Route::get('/docs/openapi/openapi.yaml', [App\Http\Controllers\SwaggerController::class, 'yaml'])
    ->name('docs.openapi.yaml');
