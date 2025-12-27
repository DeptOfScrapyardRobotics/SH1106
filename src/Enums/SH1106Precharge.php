<?php

namespace ScrapyardIO\Displays\Monochrome\SH1106\Enums;

enum SH1106Precharge: int
{
    case DEFAULT = 0x22;
    case RECOMMENDED = 0xF1;
    case SH1106_DEFAULT = 0x1F;
    case MINIMUM = 0x11;
    case MAXIMUM = 0xFF;
    case BALANCED = 0x88;

    public static function custom(int $phase1, int $phase2): int
    {
        $phase1 = max(1, min(15, $phase1));
        $phase2 = max(1, min(15, $phase2));
        return ($phase2 << 4) | $phase1;
    }
}

