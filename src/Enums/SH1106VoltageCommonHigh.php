<?php

namespace ScrapyardIO\Displays\Monochrome\SH1106\Enums;

enum SH1106VoltageCommonHigh: int
{
    case LEVEL_065 = 0x00;
    case LEVEL_077 = 0x20;
    case LEVEL_083 = 0x30;
    case LEVEL_077_ALT = 0x40;
}

