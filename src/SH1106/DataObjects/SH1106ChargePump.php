<?php

namespace DeptOfScrapyardRobotics\Displays\SH1106\SH1106\DataObjects;

use BareMetal\DataObjects\DataRegister;

readonly class SH1106ChargePump extends DataRegister
{
    public function __construct(
        public bool $enabled = false,
    ) {}

    public function toBits(): string
    {
        $bits7654321 = '1000101';
        $bit0 = $this->enabled ? '1' : '0';

        return "{$bits7654321}{$bit0}";
    }

    public static function fromByte(int $byte): static
    {
        $bits = byte2bits($byte);

        return new static(
            $bits[0],
        );
    }

    public static function none(): static
    {
        return new static(
            false,
        );
    }
}
