<?php

namespace ScrapyardIO\Displays\Monochrome\SH1106\Enums;

enum SH1106ComPinConfig: int
{
    case SEQUENTIAL = 0x02;
    case ALTERNATIVE = 0x12;
    case SEQUENTIAL_REMAP = 0x22;
    case ALTERNATIVE_REMAP = 0x32;

    public static function forHeight(int $height): self
    {
        return match($height) {
            64 => self::ALTERNATIVE,
            default => self::SEQUENTIAL,
        };
    }
}

