<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CdrController;
use App\Http\Controllers\CarrierController;
use App\Http\Controllers\DetrafReportController;
use App\Http\Controllers\NetworkController;
use App\Http\Controllers\StaticRouteController;
use App\Http\Controllers\SipiTrunkController;
use App\Http\Controllers\AuthController;

// Redirecionamento inicial
Route::get('/', function () {
    return redirect()->route('login');
});

// Autenticação
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Rotas Autenticadas
Route::middleware(['auth'])->group(function () {

    // Área do Cliente / Usuário (Consultas)
    Route::prefix('client')->name('client.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'clientIndex'])->name('dashboard');
        Route::get('/cdrs', [CdrController::class, 'index'])->name('cdrs.index');
    });

    // Área Administrativa Completa (Admin)
    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/cdrs', [CdrController::class, 'index'])->name('cdrs.index');

        // Relatórios DETRAF (Entrada & Saída por Operadora / Oi)
        Route::get('/detraf', [DetrafReportController::class, 'index'])->name('detraf.index');
        Route::get('/detraf/export', [DetrafReportController::class, 'exportCsv'])->name('detraf.export');

        // Cadastro de Operadoras & Tarifas
        Route::resource('carriers', CarrierController::class);

        // Troncos SIP-i (vinculados às Operadoras)
        Route::resource('sipi-trunks', SipiTrunkController::class);

        // Rede, IPs Virtuais (Aliases) e Rotas Estáticas
        Route::get('/network', [NetworkController::class, 'index'])->name('network.index');
        Route::put('/network/{interface}', [NetworkController::class, 'update'])->name('network.update');
        Route::post('/network/aliases', [\App\Http\Controllers\NetworkAliasController::class, 'store'])->name('network.aliases.store');
        Route::delete('/network/aliases/{alias}', [\App\Http\Controllers\NetworkAliasController::class, 'destroy'])->name('network.aliases.destroy');
        Route::resource('static-routes', StaticRouteController::class);
    });
});
