<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CatalogSettingsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MaterialRequestController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\KardexController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StockCountController;
use App\Http\Controllers\RestockController;
use App\Http\Controllers\RealEstateProjectController;
use App\Http\Controllers\LandLotController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\LotReservationController;
use App\Http\Controllers\LotSaleController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\FinancingPlanController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/help', [SystemController::class,'help'])->name('help.index');
    Route::get('/system/check', [SystemController::class,'check'])->middleware('can:administrar-almacen')->name('system.check');

    // Rutas de Perfil (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Módulos del Inventario Protegidos
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/restock', [RestockController::class, 'index'])->name('restock.index');
    Route::get('/products/export', [ProductController::class, 'export'])->name('products.export');


    Route::get('/kardex', [KardexController::class, 'index'])->name('kardex.index');
    Route::get('/kardex/export', [KardexController::class, 'export'])->name('kardex.export');
    Route::post('/kardex', [KardexController::class, 'store'])->name('kardex.store');

    // Módulo de Requerimientos
    Route::get('/requests', [MaterialRequestController::class, 'index'])->name('requests.index');
    Route::post('/requests', [MaterialRequestController::class, 'store'])->name('requests.store');
    Route::get('/requests/{materialRequest}/receipt', [MaterialRequestController::class, 'receipt'])->name('requests.receipt');

    Route::middleware('can:administrar-almacen')->group(function () {
        Route::get('/counts', [StockCountController::class, 'index'])->name('counts.index');
        Route::post('/counts', [StockCountController::class, 'store'])->name('counts.store');
        Route::patch('/products/settings/units/{unit}', [CatalogSettingsController::class, 'updateUnit'])->name('products.units.update');
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::get('/products/settings', [CatalogSettingsController::class, 'index'])->name('products.settings');
        Route::post('/products/settings/families', [CatalogSettingsController::class, 'storeFamily'])->name('products.families.store');
        Route::patch('/products/settings/families/{family}', [CatalogSettingsController::class, 'updateFamily'])->name('products.families.update');
        Route::post('/products/settings/units', [CatalogSettingsController::class, 'storeUnit'])->name('products.units.store');
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::patch('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{user}/restore', [UserController::class, 'restore'])->withTrashed()->name('users.restore');
        Route::patch('/requests/{materialRequest}/status', [MaterialRequestController::class, 'updateStatus'])
            ->name('requests.updateStatus');
        Route::post('/requests/{materialRequest}/dispatch', [MaterialRequestController::class, 'dispatch'])
            ->name('requests.dispatch');
    });
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

    Route::prefix('commercial')->name('commercial.')->group(function () {
        Route::middleware('can:gestionar-comercial')->group(function () {
            Route::get('/reservations/{reservation}/purchase', [LotSaleController::class, 'create'])->name('sales.create');
            Route::get('/plans', [FinancingPlanController::class, 'index'])->name('plans.index');
            Route::get('/sales', [LotSaleController::class, 'index'])->name('sales.index');
            Route::get('/payments', [LotSaleController::class, 'payments'])->name('payments.index');
            Route::get('/sales/{sale}', [LotSaleController::class, 'show'])->name('sales.show');
            Route::get('/sales/{sale}/payments/{payment}/receipt', [LotSaleController::class, 'receipt'])->name('sales.receipt');
            Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
            Route::get('/clients/create', [ClientController::class, 'create'])->name('clients.create');
            Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
            Route::get('/clients/{client}', [ClientController::class, 'show'])->name('clients.show');
            Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
            Route::patch('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
            Route::get('/reservations', [LotReservationController::class, 'index'])->name('reservations.index');
            Route::get('/reservations/create', [LotReservationController::class, 'create'])->name('reservations.create');
            Route::post('/reservations', [LotReservationController::class, 'store'])->name('reservations.store');
            Route::get('/reservations/{reservation}', [LotReservationController::class, 'show'])->name('reservations.show');
            Route::post('/reservations/{reservation}/cancel', [LotReservationController::class, 'cancel'])
                ->middleware('can:cancelar-reserva,reservation')->name('reservations.cancel');
        });
        Route::middleware('can:administrar-almacen')->group(function () {
            Route::post('/plans', [FinancingPlanController::class, 'store'])->name('plans.store');
            Route::patch('/plans/{plan}', [FinancingPlanController::class, 'update'])->name('plans.update');
            Route::get('/sales/{sale}/schedule', [LotSaleController::class, 'configure'])->name('sales.configure');
            Route::post('/sales/{sale}/schedule', [LotSaleController::class, 'schedule'])->name('sales.schedule');
            Route::post('/reservations/{reservation}/purchase', [LotSaleController::class, 'store'])->name('sales.store');
            Route::post('/sales/{sale}/payments', [LotSaleController::class, 'payment'])->name('sales.payment');
            Route::get('/projects/create', [RealEstateProjectController::class, 'create'])->name('projects.create');
            Route::post('/projects', [RealEstateProjectController::class, 'store'])->name('projects.store');
            Route::get('/projects/{project}/edit', [RealEstateProjectController::class, 'edit'])->name('projects.edit');
            Route::patch('/projects/{project}', [RealEstateProjectController::class, 'update'])->name('projects.update');
            Route::get('/projects/{project}/lots/create', [LandLotController::class, 'create'])->name('lots.create');
            Route::post('/projects/{project}/lots', [LandLotController::class, 'store'])->name('lots.store');
            Route::get('/lots/{lot}/edit', [LandLotController::class, 'edit'])->name('lots.edit');
            Route::patch('/lots/{lot}', [LandLotController::class, 'update'])->name('lots.update');
        });
        Route::get('/projects', [RealEstateProjectController::class, 'index'])->name('projects.index');
        Route::get('/projects/{project}', [RealEstateProjectController::class, 'show'])->name('projects.show');
        Route::get('/lots/{lot}', [LandLotController::class, 'show'])->name('lots.show');
    });

});

require __DIR__.'/auth.php';
