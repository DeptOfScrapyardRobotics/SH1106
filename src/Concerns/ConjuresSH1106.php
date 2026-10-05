<?php

namespace DeptOfScrapyardRobotics\Displays\SH1106\Concerns;

use DeptOfScrapyardRobotics\Displays\SH1106\Enums\SH1106I2CAddress;
use DeptOfScrapyardRobotics\Displays\SH1106\Enums\SH1106SPIClock;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106Configuration;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106Exception;
use DeptOfScrapyardRobotics\Displays\SH1106\Transports\SH1106I2CTransport;
use DeptOfScrapyardRobotics\Displays\SH1106\Transports\SH1106SPITransport;
use GeneralPurposeIO\Contracts\Digital\DigitalOutTransport;
use GeneralPurposeIO\Contracts\SPI\SPIMode;
use Voyager\Vessel\ControlPanel;

/**
 * The i2c() and spi() protocol factories the circuit catalog calls. Their parameters are the keys of a
 * config/circuits/sh1106.php entry, so app('circuit')->conjure('sh1106') builds a wired, booted panel from the
 * app's config alone. A bus or pin device that is not connected yet is connected here; one the app already
 * connected is shared as it is. $panel holds SH1106Configuration's constructor arguments by name.
 */
trait ConjuresSH1106
{
    /** @param  array<string, mixed>  $panel */
    public static function i2c(
        string $driver,
        string|int $device,
        int $slave = SH1106I2CAddress::SAO_GROUNDED->value,
        array $panel = [],
        bool $boot_now = true,
    ): static {
        $config = SH1106Configuration::fromArray($panel);
        $bus = static::gpio('gpio.i2c')->driver($driver);
        $i2c = $bus->device($device, $slave) ?? $bus->connectTo($device)->register()->device($device, $slave);

        if (is_null($i2c)) {
            throw SH1106Exception::notConnected('I2C', $driver, $device);
        }

        return new static(new SH1106I2CTransport($i2c, $config->get('max_packet_size')), $config, $boot_now);
    }

    /**
     * Opens the bus in mode 0 when it is not connected yet, and clocks this chip select at $speed whatever the bus
     * runs at. The chip shifts SDIN in on SCLK's rising edge, so a bus the app opened in mode 1 or 2 is refused;
     * mode 3 samples on the rising edge too and is shared. The bus is connected before DC and RST, so pins on an
     * FT232H ride the bus's own context.
     *
     * @param  array{driver: string, device: string|int, pin: int}  $dc
     * @param  array{driver: string, device: string|int, pin: int}  $rst
     * @param  array<string, mixed>  $panel
     */
    public static function spi(
        string $driver,
        string|int $device,
        array $dc,
        array $rst,
        int $chip_select = 0,
        int $speed = SH1106SPIClock::MAX_HZ->value,
        array $panel = [],
        bool $boot_now = true,
    ): static {
        if ($speed < 1 || $speed > SH1106SPIClock::MAX_HZ->value) {
            throw SH1106Exception::spiClockOutOfRange($speed);
        }

        $config = SH1106Configuration::fromArray($panel);
        $bus = static::gpio('gpio.spi')->driver($driver);
        $spi = $bus->device($device, $chip_select)
            ?? $bus->connectTo($device)->mode(SPIMode::MODE_0)->speed($speed)->register()->device($device, $chip_select);

        if (is_null($spi)) {
            throw SH1106Exception::notConnected('SPI', $driver, $device);
        }

        $mode = $bus->settingsOf($device)?->mode;

        if ($mode === SPIMode::MODE_1 || $mode === SPIMode::MODE_2) {
            throw SH1106Exception::wrongSpiMode($device, $mode->value);
        }

        $spi->speed($speed);

        return new static(
            new SH1106SPITransport($spi, static::line($dc, 'dc'), static::line($rst, 'rst'), $config->get('max_packet_size')),
            $config,
            $boot_now,
        );
    }

    /** @param  array{driver?: string, device?: string|int, pin?: int}  $line */
    protected static function line(array $line, string $name): DigitalOutTransport
    {
        if (! isset($line['driver'], $line['device'], $line['pin'])) {
            throw SH1106Exception::incompletePin($name);
        }

        $pins = static::gpio('gpio.digital')->driver($line['driver']);
        $pin = $pins->output($line['device'], $line['pin'])
            ?? $pins->connectTo($line['device'])->register()->output($line['device'], $line['pin']);

        return $pin ?? throw SH1106Exception::notConnected('DigitalIO', $line['driver'], $line['device']);
    }

    /** A protocol manager from the app's container: gpio.i2c, gpio.spi or gpio.digital. */
    protected static function gpio(string $manager): mixed
    {
        return ControlPanel::getInstance()->make($manager);
    }
}
