<?php

namespace DeptOfScrapyardRobotics\Displays\SH1106\SH1106\Enums;

enum SH1106VoltageCommonHigh: int
{
    /**
     * VCOMH = ~0.65 x VCC
     * Lower deselect voltage
     */
    case LEVEL_065 = 0x00;

    /**
     * VCOMH = ~0.77 x VCC (RESET default)
     * Recommended for most displays
     */
    case LEVEL_077 = 0x20;

    /**
     * VCOMH = ~0.83 x VCC
     * Higher deselect voltage
     */
    case LEVEL_083 = 0x30;

    /**
     * Alternative encoding for ~0.77 x VCC
     * Common in example code
     */
    case LEVEL_077_ALT = 0x40;
}
