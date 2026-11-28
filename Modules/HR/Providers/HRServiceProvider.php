<?php

namespace Modules\HR\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class HRServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'HR';

    protected string $nameLower = 'hr';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerPortalRateLimiters();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        // [Multi-Tenancy] Module migrations run on tenant DBs only via tenants:migrate (config/tenancy.php migration_parameters). Not on the central migrator.
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            \Modules\HR\Console\PortalCheckinRemindersCommand::class,
            \Modules\HR\Console\CheckContractExpiryCommand::class,
            \Modules\HR\Console\CheckEmployeeDocumentExpiryCommand::class,
        ]);
    }

    /**
     * Rate limiters for the employee portal API — keyed per tenant + employee (not IP only):
     * employees of one branch share the same public IP (office Wi-Fi / carrier NAT).
     */
    protected function registerPortalRateLimiters(): void
    {
        $tenantKey = static fn (): string => (string) (function_exists('tenant') ? (tenant('id') ?? 'central') : 'central');
        $employeeKey = static function (Request $request) use ($tenantKey): string {
            $employee = $request->attributes->get('portal_employee');

            return $tenantKey().':'.($employee ? 'emp:'.$employee->id : 'ip:'.$request->ip());
        };

        RateLimiter::for('employee-portal-public', static fn (Request $request) => Limit::perMinute(300)->by($tenantKey().':'.$request->ip()));

        RateLimiter::for('employee-portal-login', static fn (Request $request) => [
            Limit::perMinute(10)->by($tenantKey().':login:'.mb_strtolower(trim((string) $request->input('login'))).':'.$request->ip()),
            Limit::perMinute(300)->by($tenantKey().':login-ip:'.$request->ip()),
        ]);

        RateLimiter::for('employee-portal-punch', static fn (Request $request) => Limit::perMinute(6)->by('punch:'.$employeeKey($request)));

        RateLimiter::for('employee-portal-write', static fn (Request $request) => Limit::perMinute(30)->by('write:'.$employeeKey($request)));
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
    }

    /**
     * Register translations.
     * Module translations take priority over root lang files.
     */
    public function registerTranslations(): void
    {
        $publishedPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($publishedPath)) {
            $this->loadTranslationsFrom($publishedPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($publishedPath);
            return;
        }

        foreach (['resources/lang', 'lang', 'Resources/lang'] as $relative) {
            $path = module_path($this->name, $relative);
            if (is_dir($path)) {
                $this->loadTranslationsFrom($path, $this->nameLower);
                $this->loadJsonTranslationsFrom($path);

                // Prepend module lang path so __('hr.key') picks up module
                // translations before the root lang/ files
                $loader = $this->app['translation.loader'];
                $reflection = new \ReflectionClass($loader);
                if ($reflection->hasProperty('paths')) {
                    $prop = $reflection->getProperty('paths');
                    $prop->setAccessible(true);
                    $currentPaths = $prop->getValue($loader);
                    if (! in_array($path, $currentPaths)) {
                        array_unshift($currentPaths, $path);
                        $prop->setValue($loader, $currentPaths);
                    }
                }

                return;
            }
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, config('modules.paths.generator.config.path'));

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $config = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $config_key = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
                    $segments = explode('.', $this->nameLower.'.'.$config_key);

                    // Remove duplicated adjacent segments
                    $normalized = [];
                    foreach ($segments as $segment) {
                        if (end($normalized) !== $segment) {
                            $normalized[] = $segment;
                        }
                    }

                    $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);

                    $this->publishes([$file->getPathname() => config_path($config)], 'config');
                    $this->merge_config_from($file->getPathname(), $key);
                }
            }
        }
    }

    /**
     * Merge config from the given path recursively.
     */
    protected function merge_config_from(string $path, string $key): void
    {
        $existing = config($key, []);
        $module_config = require $path;

        config([$key => array_replace_recursive($existing, $module_config)]);
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        // BC: recruitment:: views + x-recruitment:: components after merge into HR
        $this->loadViewsFrom([$sourcePath], 'recruitment');

        Blade::componentNamespace(config('modules.namespace').'\\' . $this->name . '\\View\\Components', $this->nameLower);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }
}
