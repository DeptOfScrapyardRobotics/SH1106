<?php

namespace DeptOfScrapyardRobotics\Displays\SH1106\SH1106\Adapters;

use Waveforms\Carriers\I2C\I2CDevice;

abstract class SH1106DataCarrier
{
    public function __construct(
        protected I2CDevice $carrier
    ) {}

    abstract public function data(array $data): void;

    abstract public function command(int $register_hex, array $command_data = []): void;

    public function reset(): void {}
}
