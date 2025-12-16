<?php

namespace Farzai\LaravelSchema;

use Farzai\LaravelSchema\Commands\LaravelSchemaCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelSchemaServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-schema')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_schema_table')
            ->hasCommand(LaravelSchemaCommand::class);
    }
}
