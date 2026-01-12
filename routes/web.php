<?php

declare(strict_types=1);

use Farzai\LaravelSchema\Http\Controllers\DashboardController;
use Farzai\LaravelSchema\Http\Controllers\SchemaApiController;
use Farzai\LaravelSchema\Http\Middleware\Authorize;
use Illuminate\Support\Facades\Route;

Route::group([
    'domain' => config('schema.domain'),
    'prefix' => config('schema.path', 'schema'),
    'middleware' => config('schema.middleware', ['web']),
], function () {
    // Dashboard SPA entry point
    Route::get('/', [DashboardController::class, 'index'])
        ->middleware(Authorize::class)
        ->name('schema.dashboard');

    // API routes
    Route::prefix('api')
        ->middleware(Authorize::class)
        ->group(function () {
            Route::get('/tables', [SchemaApiController::class, 'tables'])
                ->name('schema.api.tables');

            Route::get('/tables/{table}', [SchemaApiController::class, 'table'])
                ->name('schema.api.table');

            Route::get('/diff', [SchemaApiController::class, 'diff'])
                ->name('schema.api.diff');

            Route::get('/diff/{table}', [SchemaApiController::class, 'tableDiff'])
                ->name('schema.api.table-diff');

            Route::get('/migrations', [SchemaApiController::class, 'migrations'])
                ->name('schema.api.migrations');

            Route::get('/status', [SchemaApiController::class, 'status'])
                ->name('schema.api.status');

            Route::post('/refresh', [SchemaApiController::class, 'refresh'])
                ->name('schema.api.refresh');
        });
});
