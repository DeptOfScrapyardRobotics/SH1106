<?php

/*
| Proven against recording fakes: every command and data byte the panel would
| see over I2C or SPI, every DC and RST level. Nothing here touches a bus. The
| live check is an SH1106 on an FT232H's I2C.
*/

use DeptOfScrapyardRobotics\Displays\SH1106\Tests\Support\ConfigPathVessel;
use DeptOfScrapyardRobotics\Displays\SH1106\Tests\Support\FakeDigitalIOConnectionDriver;
use DeptOfScrapyardRobotics\Displays\SH1106\Tests\Support\FakeI2CConnectionDriver;
use DeptOfScrapyardRobotics\Displays\SH1106\Tests\Support\FakeSPIConnectionDriver;
use GeneralPurposeIO\Digital\DigitalOConnectionManager;
use GeneralPurposeIO\I2C\I2CConnectionManager;
use GeneralPurposeIO\IntegratedCircuits\CircuitRegistry;
use GeneralPurposeIO\SPI\SPIConnectionManager;
use Voyager\Config\Repository;
use Voyager\Vessel\ControlPanel;

/*
| A Venusian app's core defines config() over the container's config repository;
| CircuitRegistry::conjure() calls it. A package suite has no core, so this
| stands in for it the same way.
*/
if (! function_exists('config')) {
    function config(array|string|null $key = null, mixed $default = null): mixed
    {
        $config = ControlPanel::getInstance()->make('config');

        return match (true) {
            is_null($key) => $config,
            is_array($key) => $config->set($key),
            default => $config->get($key, $default),
        };
    }
}

/**
 * The shared container as an app sets it up: config, the circuit catalog, and the three protocol managers, each
 * with a 'fake' driver.
 *
 * @return array{i2c: FakeI2CConnectionDriver, spi: FakeSPIConnectionDriver, digital: FakeDigitalIOConnectionDriver, app: ConfigPathVessel}
 */
function fakeBench(array $config = []): array
{
    $app = new ConfigPathVessel;
    $app->registerInstance('config', new Repository($config));
    $app->registerInstance('circuit', new CircuitRegistry);
    ControlPanel::setInstance($app);

    $bench = [
        'i2c' => new FakeI2CConnectionDriver,
        'spi' => new FakeSPIConnectionDriver,
        'digital' => new FakeDigitalIOConnectionDriver,
        'app' => $app,
    ];

    $app->registerInstance('gpio.i2c', (new I2CConnectionManager($app))->extend('fake', fn () => $bench['i2c']));
    $app->registerInstance('gpio.spi', (new SPIConnectionManager($app))->extend('fake', fn () => $bench['spi']));
    $app->registerInstance('gpio.digital', (new DigitalOConnectionManager($app))->extend('fake', fn () => $bench['digital']));

    return $bench;
}

pest()->afterEach(function (): void {
    ControlPanel::setInstance(null);
})->in(__DIR__);
