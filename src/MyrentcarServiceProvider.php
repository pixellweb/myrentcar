<?php

namespace PixellWeb\Myrentcar;

use Illuminate\Support\ServiceProvider;
use PixellWeb\Myrentcar\app\Console\Commands\ImportTarif;
use PixellWeb\Myrentcar\app\Console\Commands\Test;

class MyrentcarServiceProvider extends ServiceProvider
{

    protected $commands = [
        Test::class,
        ImportTarif::class,
    ];


    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;


    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->addCustomConfigurationValues();
    }

    public function addCustomConfigurationValues()
    {
        // add filesystems.disks for the log viewer
        config([
            'logging.channels.myrentcar' => [
                'driver' => 'single',
                'path' => storage_path('logs/myrentcar.log'),
                'level' => 'debug',
            ]
        ]);

    }


    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/myrentcar.php', 'myrentcar'
        );


        // register the artisan commands
        $this->commands($this->commands);
    }
}
