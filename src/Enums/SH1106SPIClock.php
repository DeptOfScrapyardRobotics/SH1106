<?php

namespace DeptOfScrapyardRobotics\Displays\SH1106\Enums;

enum SH1106SPIClock: int
{
    /** 4-wire SPI clock cycle time, 250 ns minimum. */
    case MAX_HZ = 4_000_000;
}
