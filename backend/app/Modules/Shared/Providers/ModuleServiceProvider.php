<?php

namespace App\Modules\Shared\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;

/**
 * Base provider for every module. A module's own provider extends this class
 * and gets its routes and migrations registered automatically.
 *
 * Routes:     app/Modules/<Module>/Routes/api.php  -> /api/v1/...
 * Migrations: app/Modules/<Module>/Database/Migrations
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Interface => implementation bindings exposed by the module (its Contracts).
     *
     * @var array<class-string, class-string>
     */
    protected array $contracts = [];

    public function register(): void
    {
        foreach ($this->contracts as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }
    }

    public function boot(): void
    {
        $migrations = $this->modulePath('Database/Migrations');

        if (is_dir($migrations)) {
            $this->loadMigrationsFrom($migrations);
        }

        $routes = $this->modulePath('Routes/api.php');

        if (is_file($routes)) {
            Route::middleware('api')->prefix('api/v1')->group($routes);
        }
    }

    protected function modulePath(string $path = ''): string
    {
        $root = dirname((new ReflectionClass(static::class))->getFileName(), 2);

        return $path === '' ? $root : $root.DIRECTORY_SEPARATOR.$path;
    }
}
