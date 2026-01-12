<?php

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;

class WorkbenchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Override migration path for workbench testing (use absolute path)
        // dirname(__DIR__, 2) = workbench folder
        $migrationsPath = dirname(__DIR__, 2).'/database/migrations';
        config(['schema.migrations_path' => $migrationsPath]);
    }
}
