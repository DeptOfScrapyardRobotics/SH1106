<?php

namespace DeptOfScrapyardRobotics\Displays\SH1106;

use GeneralPurposeIO\Contracts\IntegratedCircuits\CircuitException;

class SH1106Exception extends CircuitException
{
    public static function transportMissingProtocol(): static
    {
        return new static("SH1106 requires an SPI or an I2C capable connection.");
    }

    public static function missingDigitalPins(): static
    {
        return new static("SH1106 requires SPI connections to enable DC and RST DigitalOutput pins.");
    }

    public static function incompletePin(string $name): static
    {
        return new static("SH1106 SPI needs its {$name} pin as driver, device and pin.");
    }

    public static function notConnected(string $protocol, string $driver, string|int $device): static
    {
        return new static("SH1106 could not get a {$protocol} connection from driver [{$driver}] on device [{$device}].");
    }

    public static function spiClockOutOfRange(int $hz): static
    {
        return new static("SH1106 SPI clock {$hz} Hz is outside 1 Hz – 4 MHz.");
    }

    public static function wrongSpiMode(string|int $device, int $mode): static
    {
        return new static("SPI bus [{$device}] runs in mode {$mode}; the SH1106 samples on the rising edge and needs mode 0 or 3.");
    }

    public static function writeFailed(string $what, int $expected, int $written): static
    {
        return new static("SH1106 {$what} write failed: {$written} of {$expected} bytes. Check the panel's power, wiring and address.");
    }

    public static function invalidPacketSize(int $size, int $max): static
    {
        return new static("SH1106 max_packet_size {$size} must be at least 1 and at most {$max} on this bus.");
    }

    public static function invalidMux(int $ratio): static
    {
        return new static("invalid Multiplex Ratio - $ratio");
    }

    public static function invalidOffset(int $offset): static
    {
        return new static("invalid Display Offset - $offset");
    }

    public static function invalidStartLine(int $pos): static
    {
        return new static("invalid StartLine - {$pos}");
    }

    public static function invalidContrast(int $pos): static
    {
        return new static("invalid Contrast value - {$pos}");
    }

    public static function invalidGeometry(int $width, int $height): static
    {
        return new static("SH1106 panel {$width}×{$height} is not drivable: width must be at least 1 and height a multiple of 8 from 16 to 64.");
    }

    public static function invalidColumnOffset(int $offset, int $width): static
    {
        return new static("SH1106 column_offset {$offset} puts a {$width}-pixel row outside the chip's 132 RAM columns.");
    }

    public static function invalidProperty(string $name, string $class): static
    {
        return new static("Invalid property '{$name}' on {$class}.");
    }
}