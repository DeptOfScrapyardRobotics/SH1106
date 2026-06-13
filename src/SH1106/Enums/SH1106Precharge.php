<?php

namespace DeptOfScrapyardRobotics\Displays\SH1106\SH1106\Enums;

enum SH1106Precharge: int
{
    /**
     * Phase1=2 DCLKs, Phase2=2 DCLKs (RESET default)
     * Conservative, safe for all panels
     */
    case DEFAULT = 0x22;

    /**
     * Phase1=1 DCLK, Phase2=15 DCLKs
     * Recommended for better display quality
     * Faster charging, cleaner image
     */
    case RECOMMENDED = 0xF1;

    /**
     * Phase1=1 DCLK, Phase2=1 DCLK
     * Minimum pre-charge (fast but may be dim)
     */
    case MINIMUM = 0x11;

    /**
     * Phase1=15 DCLKs, Phase2=15 DCLKs
     * Maximum pre-charge (cleanest but slower)
     */
    case MAXIMUM = 0xFF;

    /**
     * Phase1=8 DCLKs, Phase2=8 DCLKs
     * Balanced setting
     */
    case BALANCED = 0x88;

    /**
     * Build custom pre-charge value
     *
     * @param  int  $phase1  Phase 1 duration (1-15 DCLKs)
     * @param  int  $phase2  Phase 2 duration (1-15 DCLKs)
     * @return int Pre-charge byte value
     */
    public static function custom(int $phase1, int $phase2): int
    {
        $phase1 = max(1, min(15, $phase1));
        $phase2 = max(1, min(15, $phase2));

        return ($phase2 << 4) | $phase1;
    }
}
