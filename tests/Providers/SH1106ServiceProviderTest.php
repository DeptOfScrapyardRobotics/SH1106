<?php

use DeptOfScrapyardRobotics\Displays\SH1106\Enums\SH1106I2CAddress;
use DeptOfScrapyardRobotics\Displays\SH1106\Providers\SH1106ServiceProvider;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106;
use DeptOfScrapyardRobotics\Displays\SH1106\Tests\Support\ConfigPathVessel;
use GeneralPurposeIO\IntegratedCircuits\CircuitRegistry;
use Voyager\Config\Repository;
use Voyager\NutsAndBolts\ServiceProvider;

it('registers the wiring config under circuits.sh1106, keeping anything the app already set', function (): void {
    $vessel = new ConfigPathVessel;
    $vessel->registerInstance('config', new Repository(['circuits' => [
        'front_panel' => ['ic' => 'st7789'],
        'sh1106' => ['default_config' => 'spi'],
    ]]));

    (new SH1106ServiceProvider($vessel))->register();

    $config = $vessel->make('config');

    expect($config->get('circuits.sh1106.default_config'))->toBe('spi')
        ->and($config->get('circuits.sh1106.configs.i2c.slave'))->toBe(SH1106I2CAddress::SAO_GROUNDED->value)
        ->and($config->get('circuits.sh1106.configs.spi.dc.pin'))->toBe(0)
        ->and($config->get('circuits.sh1106.configs.spi.speed'))->toBe(4_000_000)
        ->and($config->get('circuits.sh1106.configs.i2c.panel'))->toBe(['width' => 128, 'height' => 64])
        ->and($config->get('circuits.front_panel'))->toBe(['ic' => 'st7789'])
        ->and($config->has('sh1106'))->toBeFalse();
});

it('publishes the config into config/circuits under the sh1106-config tag', function (): void {
    $app = new ConfigPathVessel('/app/config');
    $app->registerInstance('config', new Repository);

    $provider = new SH1106ServiceProvider($app);
    $provider->register();
    $provider->boot();

    expect(ServiceProvider::pathsToPublish(SH1106ServiceProvider::class, 'sh1106-config'))->toBe([
        dirname(__DIR__, 2).'/config/sh1106.php' => '/app/config/circuits/sh1106.php',
    ]);
});

it('adds the panel to the circuit catalog when one is bound', function (): void {
    $app = new ConfigPathVessel;
    $app->registerInstance('config', new Repository);
    $app->registerInstance('circuit', $catalog = new CircuitRegistry);

    $provider = new SH1106ServiceProvider($app);
    $provider->register();
    $provider->boot();

    expect($catalog->listCircuits())->toBe(['sh1106' => SH1106::class]);
});

it('boots without a circuit catalog', function (): void {
    $app = new ConfigPathVessel;
    $app->registerInstance('config', new Repository);

    $provider = new SH1106ServiceProvider($app);
    $provider->register();
    $provider->boot();

    expect($app->isBound('circuit'))->toBeFalse();
});
