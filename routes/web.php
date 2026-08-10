<?php

use App\Http\Controllers\LayoutFaturaController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::prefix('fatura/layout')->name('fatura.layout.')->group(function () {
        Route::get('/', [LayoutFaturaController::class, 'index'])->name('index');
        Route::post('preview', [LayoutFaturaController::class, 'preview'])->name('preview');
        Route::post('/', [LayoutFaturaController::class, 'salvar'])->name('salvar');
    });
});

require __DIR__.'/settings.php';
