<?php

namespace DeptOfScrapyardRobotics\Displays\SH1106\Providers;

use DeptOfScrapyardRobotics\Displays\SH1106\Enums\SH1106CatalogIc;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106;
use Voyager\NutsAndBolts\ServiceProvider;

/**
 * The panel's wiring config lives under the circuits tree: config('circuits.sh1106'),
 * published to config/circuits/sh1106.php, which the config loader keys the same way.
 */
class SH1106ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__, 2).'/config/sh1106.php', 'circuits.sh1106');
    }

    public function boot(): void
    {
        $this->publishes([
            dirname(__DIR__, 2).'/config/sh1106.php' => $this->app->configPath('circuits/sh1106.php'),
        ], 'sh1106-config');

        // With the GPIO catalog installed, the panel is conjurable by slug: app('circuit')->conjure('sh1106').
        if ($this->app->isBound('circuit')) {
            foreach (SH1106CatalogIc::cases() as $ic) {
                $this->app->make('circuit')->addCircuit($ic->value, SH1106::class);
            }
        }
    }
}
