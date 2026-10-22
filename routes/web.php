<?php

use App\Http\Controllers\CertificationController;
use App\Http\Controllers\CvProfileController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentLinkController;
use App\Http\Controllers\EducationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\WorkExperienceController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/profile')->name('home');

Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
Route::get('/documents/create', [DocumentController::class, 'create'])->name('documents.create');
Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
Route::post('/documents/verify', [DocumentController::class, 'verify'])->name('documents.verify');
Route::put('/documents/{document}', [DocumentController::class, 'update'])->name('documents.update');
Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
Route::get('/documents/{document}/viewer', [DocumentController::class, 'viewer'])->name('documents.viewer');
Route::get('/documents/{document}/file', [DocumentController::class, 'file'])->name('documents.file');
Route::post('/documents/{document}/links', [DocumentLinkController::class, 'store'])->name('documents.links.store');
Route::delete('/documents/{document}/links', [DocumentLinkController::class, 'destroy'])->name('documents.links.destroy');

Route::resource('experiences', WorkExperienceController::class)->except('show');
Route::resource('education', EducationController::class)->except('show');
Route::resource('certifications', CertificationController::class)->except('show');
Route::resource('skills', SkillController::class)->except('show');
Route::resource('projects', ProjectController::class)->except('show');

Route::get('/profiles', [CvProfileController::class, 'index'])->name('profiles.index');
Route::get('/profiles/create', [CvProfileController::class, 'create'])->name('profiles.create');
Route::post('/profiles', [CvProfileController::class, 'store'])->name('profiles.store');
Route::post('/profiles/initial', [CvProfileController::class, 'storeInitial'])->name('profiles.initial');
Route::get('/profiles/{cvProfile}', [CvProfileController::class, 'show'])->name('profiles.show');
Route::get('/profiles/{cvProfile}/edit', [CvProfileController::class, 'edit'])->name('profiles.edit');
Route::put('/profiles/{cvProfile}', [CvProfileController::class, 'update'])->name('profiles.update');
Route::post('/profiles/{cvProfile}/activate', [CvProfileController::class, 'activate'])->name('profiles.activate');
Route::post('/profiles/{cvProfile}/deactivate', [CvProfileController::class, 'deactivate'])->name('profiles.deactivate');
