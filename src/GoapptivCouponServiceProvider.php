<?php

namespace Woohoo\GoapptivCoupon;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Woohoo\GoapptivCoupon\Console\FetchCards;
use Woohoo\GoapptivCoupon\Console\FetchWoohooCategories;
use Woohoo\GoapptivCoupon\Console\FetchWoohooProducts;
use Woohoo\GoapptivCoupon\Console\GenerateWoohooBearerToken;
use Woohoo\GoapptivCoupon\Console\GenerateWoohooToken;
use Woohoo\GoapptivCoupon\Console\InstallWoohooCouponPackage;
use Woohoo\GoapptivCoupon\Http\Middleware\AuthMiddleware;
use Woohoo\GoapptivCoupon\Http\Middleware\GoApptivTokenMiddleware;

class GoapptivCouponServiceProvider extends ServiceProvider {
    /**
     * Bootstrap the application services.
     */
    public function boot() {
        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('goapptiv-coupon.php'),
            ], 'config');

            if (!class_exists('CreateConfigTable')) {
                $this->publishes([
                    __DIR__ . '/../database/migrations/create_config_table.php.stub' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_config_table.php'),
                    __DIR__ . '/../database/migrations/create_orders_table.php.stub' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_orders_table.php'),
                ], 'migrations');
            }

            // Publishing assets.
            /*$this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/goapptiv-coupon'),
            ], 'assets');*/

            // Registering package commands.
            $this->commands([
                InstallWoohooCouponPackage::class,
                GenerateWoohooToken::class,
                GenerateWoohooBearerToken::class,
                FetchWoohooCategories::class,
                FetchWoohooProducts::class,
                FetchCards::class
            ]);
        }
    }

    /**
     * Register the application services.
     */
    public function register() {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'goapptiv-coupon');

        // Register the main class to use with the facade
        $this->app->singleton('goapptiv-coupon', function () {
            return new GoapptivCoupon;
        });
    }

    protected function registerRoutes() {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('auth_token', AuthMiddleware::class);
        $router->aliasMiddleware('ga_token', GoApptivTokenMiddleware::class);
        Route::prefix('woohoo/api/v1')->group(function () {
            $this->loadRoutesFrom(__DIR__ . '/api_v1.php');
        });
    }
}
