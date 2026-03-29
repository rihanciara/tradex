<?php

namespace Modules\JerryUpdates\Providers;

use App\Events\TransactionPaymentAdded;
use App\Events\TransactionPaymentDeleted;
use App\Events\TransactionPaymentUpdated;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\Facades\Event;
use Modules\JerryUpdates\Listeners\JerryAccountHardeningListener;

class JerryUpdatesServiceProvider extends ServiceProvider
{
    /**
     * @var string $moduleName
     */
    protected $moduleName = 'JerryUpdates';

    /**
     * @var string $moduleNameLower
     */
    protected $moduleNameLower = 'jerryupdates';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));

        // Self-Healing Custom Views Scaffolding
        // Automatically deploy the module's internal custom_views to the application root if not present.
        if (!\File::exists(base_path('custom_views'))) {
            try {
                \File::copyDirectory(module_path($this->moduleName, 'Resources/custom_views'), base_path('custom_views'));
            } catch (\Exception $e) {
                \Log::error("JerryUpdates: Failed to scaffold custom_views folder - " . $e->getMessage());
            }
        }

        \Illuminate\Support\Facades\View::composer('layouts.app', function ($view) {
            if (\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_system_black_theme') == '1') {
                $business = session('business');
                if ($business) {
                    if (is_object($business)) {
                        $business->theme_color = 'black';
                    } elseif (is_array($business)) {
                        $business['theme_color'] = 'black';
                    }
                    session()->put('business', $business);
                }
            }
        });

        // Toggle V12 Bulk Edit globally (Outside View Composer so it applies to AJAX calls)
        if (\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_product_bulk_edit') == '1') {
            config(['constants.enable_product_bulk_edit' => true]);
        }

        // Upgrade-safe account hardening hooks for payment/account ledgers.
        Event::listen(TransactionPaymentAdded::class, [JerryAccountHardeningListener::class, 'onPaymentAdded']);
        Event::listen(TransactionPaymentUpdated::class, [JerryAccountHardeningListener::class, 'onPaymentUpdated']);
        Event::listen(TransactionPaymentDeleted::class, [JerryAccountHardeningListener::class, 'onPaymentDeleted']);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
        
        // Bind the core TransactionUtil to our overridden JerryTransactionUtil
        $this->app->bind(\App\Utils\TransactionUtil::class, \Modules\JerryUpdates\Utils\JerryTransactionUtil::class);
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'), $this->moduleNameLower
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);

        $sourcePath = module_path($this->moduleName, 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath
        ], ['views', $this->moduleNameLower . '-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
            $this->loadJsonTranslationsFrom($langPath, $this->moduleNameLower);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'Resources/lang'), $this->moduleNameLower);
            $this->loadJsonTranslationsFrom(module_path($this->moduleName, 'Resources/lang'), $this->moduleNameLower);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (\Config::get('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->moduleNameLower)) {
                $paths[] = $path . '/modules/' . $this->moduleNameLower;
            }
        }
        return $paths;
    }
}
