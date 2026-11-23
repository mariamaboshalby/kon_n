<?php

namespace Modules\Currencies\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class CurrenciesServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Currencies';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'currencies';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    // protected array $commands = [];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Boot the module.
     */
    public function boot(): void
    {
        parent::boot();
        
        // Load translations
        $this->loadTranslationsFrom(module_path($this->name, 'resources/lang'), $this->nameLower);
        $this->publishes([
            module_path($this->name, 'resources/lang') => lang_path("modules/{$this->nameLower}"),
        ], 'currencies-lang');
    }

    /**
     * Define module schedules.
     * 
     * @param $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }
}
