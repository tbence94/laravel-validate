<?php

namespace TBence\Validate;

use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class Provider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->provideConfig();

        if ($this->isMigratingOrSeeding()) {
            AutoValidation::disableAutoValidation();
        } else {
            $this->enableForeignKeys();
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * @return bool
     */
    private function isMigratingOrSeeding()
    {
        return app()->runningInConsole() && str_contains($_SERVER['argv'][1], ['migrate', 'db:seed']);
    }

    /**
     * Provide configuration
     */
    private function provideConfig()
    {
        $this->publishes([
            __DIR__ . '/config.php' => config_path('validate.php'),
        ], 'config');

        if (!file_exists(config_path('validate.php'))) {
            $this->mergeConfigFrom(__DIR__ . '/config.php', 'validate');
        }
    }

    /**
     * Enable foreign keys on sqlite connections
     */
    private function enableForeignKeys()
    {
        if (DB::connection() instanceof SQLiteConnection) {
            DB::statement(DB::raw('PRAGMA foreign_keys = ON'));
        }
    }
}
