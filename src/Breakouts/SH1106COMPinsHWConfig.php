<?php

namespace DeptOfScrapyardRobotics\Displays\SH1106\Breakouts;

use GeneralPurposeIO\IntegratedCircuits\DataRegister;

/** 0xDA: bit 5 = COM left/right remap, bit 4 = alternative (1) or sequential (0) COM pin configuration. */
readonly class SH1106COMPinsHWConfig extends DataRegister
{
    public function __construct(
        public bool $enable_com_lr_remap = false,
        public bool $alternative_com_pins = false
    ) {}

    public function toBits(): string
    {
        $bits76 = '00';
        $bit5 = $this->enable_com_lr_remap ? '1' : '0';
        $bit4 = $this->alternative_com_pins ? '1' : '0';
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
