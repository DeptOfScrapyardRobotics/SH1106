<?php

namespace DeptOfScrapyardRobotics\Displays\SH1106\SH1106\DataObjects;

use BareMetal\DataObjects\DataRegister;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\Enums\SH1106OpCode;

readonly class SH1106COMScanDirection extends DataRegister
{
    public function __construct(
        public bool $enabled = false,
    ) {}

    public function toBits(): string
    {
        $bits7654 = '1100';
        $bit3 = $this->enabled ? '1' : '0';
        $bits210 = '000';

        return "{$bits7654}{$bit3}{$bits210}";
    }

    public function toOpCode(): SH1106OpCode
    {
        return SH1106OpCode::from($this->toByte());
    }

    public static function fromByte(int $byte): static
    {
        $bits = byte2bits($byte);

        return new static(
            $bits[3],
        );
    }

    public static function none(): static
    {
        return new static(
            false,
        );
    }
}
