<?php

namespace CodingSocks\LostInTranslation;

use CodingSocks\LostInTranslation\Console\Commands\FindMissingTranslationStrings;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class LostInTranslationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->commands(
            FindMissingTranslationStrings::class,
        );
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/lost-in-translation.php', 'lost-in-translation');

        if ($this->app->runningInConsole()) {
            $this->registerToBePublished();
        }

        $this->app->when(FindMissingTranslationStrings::class)
            ->needs(Filesystem::class)
            ->give('files');

        $this->app->singleton(LostInTranslation::class, function ($app) {
            return new LostInTranslation($app->make('blade.compiler'), $this->detectionConfigurations());
        });

        $this->app->singleton('lost-in-translation', function ($app) {
            return $app->make(LostInTranslation::class);
        });
    }

    /**
     * @return void
     */
    protected function registerToBePublished(): void
    {
        $this->publishes([
            __DIR__ . '/../config/lost-in-translation.php' => config_path('lost-in-translation.php'),
        ], 'lost-in-translation-config');

        $this->publishes([
            __DIR__ . '/Console/Commands' => app_path('Console/Commands/vendor/LostInTranslation')
        ], 'lost-in-translation-commands');
    }

    /**
     * Translation detection configurations.
     *
     * @return array
     */
    private function detectionConfigurations()
    {
        return [
            'function' => config('lost-in-translation.detect.function', []),
            'method-function' => config('lost-in-translation.detect.method-function', []),
            'method-static' => config('lost-in-translation.detect.method-static', []),
            'static' => config('lost-in-translation.detect.static', []),
        ];
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'lost-in-translation',
        ];
    }
}
