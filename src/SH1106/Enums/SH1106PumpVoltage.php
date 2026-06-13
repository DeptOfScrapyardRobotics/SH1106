<?php

namespace DeptOfScrapyardRobotics\Displays\SH1106\SH1106\Enums;

enum SH1106PumpVoltage: int
{
    case PUMP_VOLTAGE_640 = 0;
    case PUMP_VOLTAGE_740 = 1;
    case PUMP_VOLTAGE_800 = 2;
    case PUMP_VOLTAGE_900 = 3;

    public function toOpCode(): SH1106OpCode
    {
        return match ($this) {
            self::PUMP_VOLTAGE_640 => SH1106OpCode::PUMP_VOLTAGE_640,
            self::PUMP_VOLTAGE_740 => SH1106OpCode::PUMP_VOLTAGE_740,
            self::PUMP_VOLTAGE_800 => SH1106OpCode::PUMP_VOLTAGE_800,
            self::PUMP_VOLTAGE_900 => SH1106OpCode::PUMP_VOLTAGE_900,
        };
    }
}
