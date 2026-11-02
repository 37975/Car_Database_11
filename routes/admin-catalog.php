<?php

use App\Http\Controllers\Admin\AdminLogController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CarController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DatabaseController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\TierController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('members', MemberController::class)
            ->only(['index', 'edit', 'update', 'destroy']);

        Route::resource('brands', BrandController::class)
            ->except('show');

        Route::resource('categories', CategoryController::class)
            ->except('show');

        Route::resource('cars', CarController::class)
            ->except('show');

        Route::patch('cars/{car}/stock', [
            CarController::class,
            'addStock',
        ])->name('cars.stock');

        Route::resource('tiers', TierController::class)
            ->except('show');

        Route::resource('reviews', ReviewController::class)
            ->only(['index', 'destroy']);

        Route::get('logs', [
            AdminLogController::class,
            'index',
        ])->name('logs.index');

        // Read-only database viewer (tables, structure, rows, SQL console)
        Route::get('database', [DatabaseController::class, 'index'])->name('database.index');
        Route::match(['get', 'post'], 'database/query', [DatabaseController::class, 'query'])->name('database.query');
        Route::get('database/{table}', [DatabaseController::class, 'show'])
            ->where('table', '[A-Za-z_]+')
            ->name('database.show');
    });