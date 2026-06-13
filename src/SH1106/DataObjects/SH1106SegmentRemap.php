<?php

namespace DeptOfScrapyardRobotics\Displays\SH1106\SH1106\DataObjects;

use BareMetal\DataObjects\DataRegister;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\Enums\SH1106OpCode;

readonly class SH1106SegmentRemap extends DataRegister
{
    public function __construct(
        public bool $enabled = false,
    ) {}

    public function toBits(): string
    {
        $bits7654321 = '1010000';
        $bit0 = $this->enabled ? '1' : '0';

        return "{$bits7654321}{$bit0}";
    }

    public function toOpCode(): SH1106OpCode
    {
        return SH1106OpCode::from($this->toByte());
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
