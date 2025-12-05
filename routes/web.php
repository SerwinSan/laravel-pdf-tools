<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PdfController;

Route::get('/', [PdfController::class, 'index']);
Route::post('/merge', [PdfController::class, 'merge'])->name('pdf.merge');
Route::post('/image-to-pdf', [PdfController::class, 'imageToPdf'])->name('pdf.image');
Route::post('/split', [PdfController::class, 'split'])->name('pdf.split');