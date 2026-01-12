<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema;

use Farzai\LaravelSchema\Commands\LaravelSchemaCommand;
use Farzai\LaravelSchema\Commands\SchemaDiffCommand;
use Farzai\LaravelSchema\Commands\SchemaStatusCommand;
use Farzai\LaravelSchema\Commands\SchemaTablesCommand;
use Farzai\LaravelSchema\Contracts\IntrospectorFactoryInterface;
use Farzai\LaravelSchema\Contracts\MigrationParserInterface;
use Farzai\LaravelSchema\Contracts\SchemaDifferInterface;
use Farzai\LaravelSchema\Diff\SchemaDiffer;
use Farzai\LaravelSchema\Introspectors\IntrospectorFactory;
use Farzai\LaravelSchema\Parsers\BlueprintAnalyzer;
use Farzai\LaravelSchema\Parsers\MigrationParser;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelSchemaServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-schema')
            ->hasConfigFile()
            ->hasViews()
            ->hasCommand(LaravelSchemaCommand::class)
            ->hasCommand(SchemaStatusCommand::class)
            ->hasCommand(SchemaDiffCommand::class)
            ->hasCommand(SchemaTablesCommand::class);
    }

    public function packageRegistered(): void
    {
        // Register BlueprintAnalyzer
        $this->app->singleton(BlueprintAnalyzer::class, function () {
            return new BlueprintAnalyzer;
        });

        // Register IntrospectorFactoryInterface
        $this->app->singleton(IntrospectorFactoryInterface::class, function ($app) {
            $connectionName = config('schema.connection');
            $connection = $app['db']->connection($connectionName);

            return new IntrospectorFactory($connection);
        });

        // Alias concrete class to interface for backward compatibility
        $this->app->alias(IntrospectorFactoryInterface::class, IntrospectorFactory::class);

        // Register MigrationParserInterface
        $this->app->singleton(MigrationParserInterface::class, function ($app) {
            return new MigrationParser(
                $app->make(BlueprintAnalyzer::class)
            );
        });

        // Register SchemaDifferInterface
        $this->app->singleton(SchemaDifferInterface::class, function () {
            return new SchemaDiffer;
        });

        // Register SchemaInspector - use closure for deferred config resolution
        $this->app->singleton(SchemaInspector::class, function ($app) {
            return new SchemaInspector(
                $app->make(IntrospectorFactoryInterface::class),
                $app->make(MigrationParserInterface::class),
                $app->make(SchemaDifferInterface::class),
            );
        });

        // Register LaravelSchema alias for backward compatibility
        // @phpstan-ignore classConstant.deprecatedClass (Intentional backward compatibility alias)
        $this->app->alias(SchemaInspector::class, LaravelSchema::class);
        $this->app->alias(SchemaInspector::class, 'laravel-schema');
    }

    public function packageBooted(): void
    {
        // Explicitly register views to ensure they work with testbench
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-schema');

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        // Publish frontend assets
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../dist' => public_path('vendor/laravel-schema'),
            ], 'laravel-schema-assets');
        }
    }
}
