<?php

namespace DeptOfScrapyardRobotics\Displays\SH1106\Enums;

/** The SH1106 command set. The low/high column and page commands carry their argument in the opcode's low bits. */
enum SH1106OpCode: int
{
    case SET_LOW_COLUMN = 0x00;
    case SET_HIGH_COLUMN = 0x10;
    case PUMP_VOLTAGE_640 = 0x30;
    case PUMP_VOLTAGE_740 = 0x31;
    case PUMP_VOLTAGE_800 = 0x32;
    case PUMP_VOLTAGE_900 = 0x33;
    case CONTRAST_REGISTER = 0x81;
    case MAP_SEG0_TO_SEG0_REGISTER = 0xA0;
    case MAP_SEG0_TO_SEG127_REGISTER = 0xA1;
    case NORMAL_OPERATION_MODE = 0xA4;
    case FILLED_SCREEN_MODE = 0xA5;
    case INVERT_DISPLAY_OFF = 0xA6;
    case INVERT_DISPLAY_ON = 0xA7;
    case MUX_REGISTER = 0xA8;
    case DCDC_CONTROL_REGISTER = 0xAD;
    case TOGGLE_DISPLAY_OFF = 0xAE;
    case TOGGLE_DISPLAY_ON = 0xAF;
    case SET_PAGE_ADDRESS = 0xB0;
    case MAP_COL_0_TO_COL_0 = 0xC0;
    case MAP_COL_0_TO_COL_N_MINUS_1 = 0xC8;
    case VERTICAL_OFFSET_REGISTER = 0xD3;
    case DISPLAY_CLOCK_REGISTER = 0xD5;
    case SET_PRECHARGE_PERIOD = 0xD9;
    case COM_PINS_HW_CONFIG_REGISTER = 0xDA;
    case SET_V_COM_H_DESELECT_LEVEL = 0xDB;
}
