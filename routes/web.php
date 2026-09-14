<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\PurchaseController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
})->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::middleware('role:super_admin,owner,manager,inventory_staff')->prefix('inventory')->group(function (): void {
        Route::get('/', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('/movements', [InventoryController::class, 'movements'])->name('inventory.movements');
        foreach (['receive', 'issue', 'adjust', 'opname'] as $action) {
            Route::get("/{$action}", [InventoryController::class, 'create'])->defaults('action', $action)->name("inventory.{$action}.create");
            Route::post("/{$action}", [InventoryController::class, 'store'])->defaults('action', $action)->name("inventory.{$action}.store");
        }
    });

    Route::middleware('role:super_admin,owner')->prefix('inventory')->group(function (): void {
        Route::get('/transfer', [InventoryController::class, 'create'])->defaults('action', 'transfer')->name('inventory.transfer.create');
        Route::post('/transfer', [InventoryController::class, 'store'])->defaults('action', 'transfer')->name('inventory.transfer.store');
    });

    Route::middleware('role:super_admin,owner,manager,inventory_staff')->prefix('purchases')->group(function (): void {
        Route::get('/', [PurchaseController::class, 'index'])->name('purchases.index');
        Route::get('/create', [PurchaseController::class, 'create'])->name('purchases.create');
        Route::post('/', [PurchaseController::class, 'store'])->name('purchases.store');
        Route::get('/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show');
        Route::post('/{purchase}/receive', [PurchaseController::class, 'receive'])->name('purchases.receive');
    });

    Route::middleware('role:super_admin,owner,manager,inventory_staff')->prefix('master-data')->group(function (): void {
        foreach (['categories', 'brands', 'units', 'products', 'suppliers'] as $resource) {
            Route::get("/{$resource}", [MasterDataController::class, 'index'])->defaults('resource', $resource)->name("master-data.{$resource}.index");
            Route::get("/{$resource}/create", [MasterDataController::class, 'create'])->defaults('resource', $resource)->name("master-data.{$resource}.create");
            Route::post("/{$resource}", [MasterDataController::class, 'store'])->defaults('resource', $resource)->name("master-data.{$resource}.store");
            Route::get("/{$resource}/{id}/edit", [MasterDataController::class, 'edit'])->defaults('resource', $resource)->name("master-data.{$resource}.edit");
            Route::put("/{$resource}/{id}", [MasterDataController::class, 'update'])->defaults('resource', $resource)->name("master-data.{$resource}.update");
            Route::delete("/{$resource}/{id}", [MasterDataController::class, 'destroy'])->defaults('resource', $resource)->name("master-data.{$resource}.destroy");
        }
    });

    Route::middleware('role:super_admin,owner')->prefix('master-data')->group(function (): void {
        foreach (['stores'] as $resource) {
            Route::get("/{$resource}", [MasterDataController::class, 'index'])->defaults('resource', $resource)->name("master-data.{$resource}.index");
            Route::get("/{$resource}/create", [MasterDataController::class, 'create'])->defaults('resource', $resource)->name("master-data.{$resource}.create");
            Route::post("/{$resource}", [MasterDataController::class, 'store'])->defaults('resource', $resource)->name("master-data.{$resource}.store");
            Route::get("/{$resource}/{id}/edit", [MasterDataController::class, 'edit'])->defaults('resource', $resource)->name("master-data.{$resource}.edit");
            Route::put("/{$resource}/{id}", [MasterDataController::class, 'update'])->defaults('resource', $resource)->name("master-data.{$resource}.update");
            Route::delete("/{$resource}/{id}", [MasterDataController::class, 'destroy'])->defaults('resource', $resource)->name("master-data.{$resource}.destroy");
        }
    });
});

Route::middleware(['auth', 'role:super_admin,owner'])->group(function (): void {
    Route::view('/admin/users', 'dashboard')->name('admin.users');
});
