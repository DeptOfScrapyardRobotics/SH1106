<?php

namespace ScrapyardIO\Displays\Monochrome\SH1106\Enums;

enum SH1106Command: int {
    case SET_MEMORY_MODE = 0x20;

    case SET_COLUMN_RANGE = 0x21;
    case SET_PAGE_RANGE = 0x22;

    case SET_CONTRAST = 0x81;
    case CHARGE_PUMP_SETTING = 0x8D;
    case DCDC = 0xAD;

    case SET_MULTIPLEX_RATIO = 0xA8;

    case SET_DISPLAY_OFFSET = 0xD3;
    case SET_DISPLAY_CLOCK = 0xD5;
    case SET_PRECHARGE_PERIOD = 0xD9;
    case SET_COM_PINS = 0xDA;
    case SET_VOLTAGE_COMMON_HIGH = 0xDB;

    case DISPLAY_OFF = 0xAE;
    case DISPLAY_ON = 0xAF;
    case DISPLAY_START_LINE = 0x40;

    case NO_HORIZONTAL_INVERSION = 0xA0;
    case INVERT_DISPLAY_HORIZONTALLY = 0xA1;

    case NO_VERTICAL_INVERSION = 0xC0;
    case INVERT_DISPLAY_VERTICALLY = 0xC8;

    case ENTIRE_DISPLAY_ON_RESUME = 0xA4;
    case ENTIRE_DISPLAY_ON = 0xA5;
    case NORMAL_DISPLAY = 0xA6;
    case INVERSE_DISPLAY = 0xA7;
    case DEACTIVATE_SCROLL = 0x2E;

    case SET_PAGE_ADDR = 0xB0;
    case SET_LOW_COLUMN = 0x00;
    case SET_HIGH_COLUMN = 0x10;
}
