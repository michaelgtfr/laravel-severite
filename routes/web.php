<?php

use Severite\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ReportController::class, 'index'])->name('home');
Route::get('/{report}', [ReportController::class, 'show'])->name('detailReport');
Route::delete('/{report}', [ReportController::class, 'destroy'])->name('deleteReport');
