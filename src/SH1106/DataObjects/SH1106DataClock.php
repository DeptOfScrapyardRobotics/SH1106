<?php

namespace DeptOfScrapyardRobotics\Displays\SH1106\SH1106\DataObjects;

use BareMetal\DataObjects\DataRegister;

readonly class SH1106DataClock extends DataRegister
{
    public function __construct(
        public bool $OSC3 = true,
        public bool $OSC2 = false,
        public bool $OSC1 = false,
        public bool $OSC0 = false,
        public bool $DCLK3 = false,
        public bool $DCLK2 = false,
        public bool $DCLK1 = false,
        public bool $DCLK0 = false
    ) {}

    public function toBits(): string
    {
        $bit7 = $this->OSC3 ? '1' : '0';
        $bit6 = $this->OSC2 ? '1' : '0';
        $bit5 = $this->OSC1 ? '1' : '0';
        $bit4 = $this->OSC0 ? '1' : '0';
        $bit3 = $this->DCLK3 ? '1' : '0';
        $bit2 = $this->DCLK2 ? '1' : '0';
        $bit1 = $this->DCLK1 ? '1' : '0';
        $bit0 = $this->DCLK0 ? '1' : '0';

        return "{$bit7}{$bit6}{$bit5}{$bit4}{$bit3}{$bit2}{$bit1}{$bit0}";
    }

    public static function fromByte(int $byte): static
    {
        $bits = byte2bits($byte);

        return new static(
            $bits[7],
            $bits[6],
            $bits[5],
            $bits[4],
            $bits[3],
            $bits[2],
            $bits[1],
            $bits[0],
        );
    }

    public static function none(): static
    {
        return new static(
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            false,
        );
    }
}
