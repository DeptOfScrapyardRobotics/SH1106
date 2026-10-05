<?php

namespace DeptOfScrapyardRobotics\Displays\SH1106\Tests\Support;

use GeneralPurposeIO\Contracts\Digital\DigitalInTransport;
use GeneralPurposeIO\Contracts\Digital\LineBias;
use GeneralPurposeIO\Digital\DigitalIOConnectionDriver;
use GeneralPurposeIO\Digital\DigitalIOConnectionFactory;
use LogicException;

/** Hands out one FakeOutputPin per device and pin. */
final class FakeDigitalIOConnectionDriver extends DigitalIOConnectionDriver
{
    /** @var list<string|int> every device connectTo() opened */
    public array $opened = [];

    /** @var array<string, FakeOutputPin> */
    public array $outputs = [];

    protected function newConnection(int|string $device): DigitalIOConnectionFactory
    {
        $this->opened[] = $device;

        return new FakeDigitalIOConnectionFactory($device, $this);
    }

    protected function getInputTransport(string|int $device, int $pin, LineBias $bias = LineBias::AS_IS, bool $active_low = false): DigitalInTransport
    {
        throw new LogicException('The SH1106 only drives its DC and RST lines.');
    }

    protected function getOutputTransport(string|int $device, int $pin): FakeOutputPin
    {
        return $this->outputs["{$device}:{$pin}"] ??= new FakeOutputPin($pin);
    }

    protected function closeConnection(mixed $handle): void {}
}
