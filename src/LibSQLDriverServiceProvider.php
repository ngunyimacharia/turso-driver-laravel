<?php

namespace Turso\Driver\Laravel;

use Illuminate\Database\DatabaseManager;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Turso\Driver\Laravel\Database\LibSQLConnection;
use Turso\Driver\Laravel\Database\LibSQLConnectionFactory;
use Turso\Driver\Laravel\Database\LibSQLConnector;

class LibSQLDriverServiceProvider extends PackageServiceProvider
{
    public function boot(): void
    {
        parent::boot();
        if (config(sprintf("database.%s.driver", config("database.default"))) !== 'libsql') {
            return;
        }
    }

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('turso-driver-laravel');
    }

    public function register(): void
    {
        parent::register();
        $this->app->singleton('db.factory', function ($app) {
            return new LibSQLConnectionFactory($app);
        });

        $this->app->scoped(LibSQLManager::class, function () {
            return new LibSQLManager(config(sprintf("database.connections.%s", config("database.default"))));
        });

        $this->app->resolving('db', function (DatabaseManager $db) {
            $db->extend('libsql', function ($config, $name) {
                $config = config(sprintf("database.connections.%s", config("database.default")));
                $config['name'] = $name;
                if (! isset($config['driver'])) {
                    $config['driver'] = 'libsql';
                }

                $connector = new LibSQLConnector;
                $db = $connector->connect($config);

                $connection = new LibSQLConnection($db, $config['database'] ?? ':memory:', $config['prefix'], $config);
                app()->instance(LibSQLConnection::class, $connection);

                $connection->createReadPdo($config);

                return $connection;
            });
        });
    }
}
