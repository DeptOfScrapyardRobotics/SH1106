<?php

namespace DeptOfScrapyardRobotics\Displays\SH1106\Tests\Support;

use GeneralPurposeIO\I2C\I2CConnectionDriver;
use GeneralPurposeIO\I2C\I2CConnectionFactory;

/** Hands out FakeI2CTransports and keeps each one, keyed device:address. */
final class FakeI2CConnectionDriver extends I2CConnectionDriver
{
    /** @var list<string|int> every bus connectTo() opened */
    public array $opened = [];

    /** @var array<string, FakeI2CTransport> */
    public array $slaves = [];

    protected function newConnection(int|string $device): I2CConnectionFactory
    {
        $this->opened[] = $device;

        return new FakeI2CConnectionFactory($device, $this);
    }

    protected function getTransport(string|int $device, int $slave_address): FakeI2CTransport
    {
        return $this->slaves["{$device}:{$slave_address}"] = new FakeI2CTransport($slave_address);
    }

    protected function closeConnection(mixed $handle): void {}
}
