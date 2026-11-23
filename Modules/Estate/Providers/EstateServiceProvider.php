<?php

namespace Modules\Estate\Providers;

use Livewire\Livewire;
use Modules\Estate\Livewire\PropertyList;
use Modules\Estate\Services\PropertyFilterService;
use Nwidart\Modules\Support\ModuleServiceProvider;

class EstateServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Estate';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'estate';

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
     * Register services
     */
    public function register(): void
    {
        parent::register();

        // Register PropertyFilterService as singleton
        $this->app->singleton(PropertyFilterService::class);

        // Register Livewire components
        Livewire::component('estate::property-list', PropertyList::class);
    }

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        parent::boot();

        // Register translations
        $this->registerTranslations();
    }

    /**
     * Register translations.
     */
    protected function registerTranslations(): void
    {
        $publishedPath = resource_path('lang/modules/'.$this->nameLower);
        $moduleLangPath = module_path($this->name, 'lang');
        $moduleResourcesLangPath = module_path($this->name, 'resources/lang');

        if (is_dir($publishedPath)) {
            $this->loadTranslationsFrom($publishedPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($publishedPath);
        } elseif (is_dir($moduleLangPath)) {
            $this->loadTranslationsFrom($moduleLangPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($moduleLangPath);
        } elseif (is_dir($moduleResourcesLangPath)) {
            $this->loadTranslationsFrom($moduleResourcesLangPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($moduleResourcesLangPath);
        }
    }

    /**
     * Define module schedules.
     *
     * @param  $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }
}
