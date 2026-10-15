<?php

use App\Http\Controllers\CertificationController;
use App\Http\Controllers\EducationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\WorkExperienceController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/profile')->name('home');

Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

Route::resource('experiences', WorkExperienceController::class)->except('show');
Route::resource('education', EducationController::class)->except('show');
Route::resource('certifications', CertificationController::class)->except('show');
Route::resource('skills', SkillController::class)->except('show');
Route::resource('projects', ProjectController::class)->except('show');
