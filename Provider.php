<?php

namespace TBence\Validate;

use DB;
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
        $this->mergeConfigFrom(__DIR__.'/config.php', 'validate');

        if (DB::connection() instanceof \Illuminate\Database\SQLiteConnection) {
            if(!app()->runningInConsole() || !str_contains($_SERVER['argv'][1], 'migrate')){
                DB::statement(DB::raw('PRAGMA foreign_keys = ON'));
            }
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
}
