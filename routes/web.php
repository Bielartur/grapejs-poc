<?php

use App\Http\Controllers\FaturaPdfController;
use App\Http\Controllers\LayoutFaturaController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::prefix('fatura/layout')->name('fatura.layout.')->group(function () {
        Route::get('/', [LayoutFaturaController::class, 'index'])->name('index');
        Route::post('/', [LayoutFaturaController::class, 'salvar'])->name('salvar');
    });

    Route::post('fatura/pdf', [FaturaPdfController::class, 'baixar'])->name('fatura.pdf.baixar');
});

/*
 * A página que o Browsershot abre fica FORA do grupo autenticado: o
 * Chromium é outro cliente e não carrega a sessão do usuário. Quem
 * autoriza é a assinatura da URL, que o `GeradorPdf` emite com validade
 * de minutos — daí o `signed` ser obrigatório aqui.
 */
Route::get('fatura/pdf/documento/{chave}', [FaturaPdfController::class, 'documento'])
    ->middleware('signed')
    ->name('fatura.pdf.documento');

require __DIR__.'/settings.php';
