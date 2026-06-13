<?php

namespace DeptOfScrapyardRobotics\Displays\SH1106\SH1106\Enums;

enum SH1106I2CAddress: int
{
    case SAO_GROUNDED = 0x3C;
    case SAO_ENERGIZED = 0x3D;
}
