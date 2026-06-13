<?php

namespace DeptOfScrapyardRobotics\Displays\SH1106\SH1106\DataObjects;

use BareMetal\DataObjects\DataRegister;

readonly class SH1106COMPinsHWConfig extends DataRegister
{
    public function __construct(
        public bool $enable_com_lr_remap = false,
        public bool $sequential_com_pin_config = false
    ) {}

    public function toBits(): string
    {
        $bits76 = '00';
        $bit5 = $this->enable_com_lr_remap ? '1' : '0';
        $bit4 = $this->sequential_com_pin_config ? '1' : '0';
        $bits3210 = '0010';

        return "{$bits76}{$bit5}{$bit4}{$bits3210}";
    }

    public static function fromByte(int $byte): static
    {
        $bits = byte2bits($byte);

        return new static(
            $bits[5],
            $bits[4],
        );
    }

    public static function none(): static
    {
        return new static(
            false,
            false,
        );
    }
}
