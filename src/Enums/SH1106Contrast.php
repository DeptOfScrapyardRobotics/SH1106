<?php

namespace ScrapyardIO\Displays\Monochrome\SH1106\Enums;

enum SH1106Contrast: int
{
    case MINIMUM = 0x00;
    case LOW = 0x3F;
    case MEDIUM = 0x7F;
    case HIGH = 0xBF;
    case MAXIMUM = 0xFF;
}

