<?php

namespace DeptOfScrapyardRobotics\Displays\SH1106\Tests\Support;

use GeneralPurposeIO\Digital\DigitalIOConnectionFactory;

final class FakeDigitalIOConnectionFactory extends DigitalIOConnectionFactory
{
    protected function device(): string|int
    {
        return $this->device;
    }

    protected function getHandle(): string
    {
        return "gpio:{$this->device}";
    }
}
