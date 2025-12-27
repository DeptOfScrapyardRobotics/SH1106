<?php

namespace ScrapyardIO\Displays\Monochrome\SH1106\Enums;

enum SH1106I2CAddress: int {
    case SA0_GROUNDED = 0x3C;
    case SA0_ENERGIZED = 0x3D;
}
