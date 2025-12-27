<?php

namespace ScrapyardIO\Displays\Monochrome\SH1106\Enums;

enum SH1106DisplayClock: int
{
    case SLOWEST = 0x00;
    case SLOW = 0x40;
    case DEFAULT = 0x80;
    case FAST = 0xA0;
    case FASTEST = 0xF0;
    case DEFAULT_HALF_RATE = 0x81;
    case DEFAULT_QUARTER_RATE = 0x83;

    public static function custom(int $oscFreq, int $divideRatio): int
    {
        $oscFreq = max(0, min(15, $oscFreq));
        $divideRatio = max(1, min(16, $divideRatio));
        return ($oscFreq << 4) | ($divideRatio - 1);
    }
}

