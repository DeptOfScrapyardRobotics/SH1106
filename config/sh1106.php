<?php

use DeptOfScrapyardRobotics\Displays\SH1106\Enums\SH1106I2CAddress;

/*
| conjure('sh1106') builds the panel from default_config, or the config named.
| A config is named after its protocol unless it carries 'protocol'. 'panel'
| holds SH1106Configuration's constructor arguments by name: width, height,
| column_offset, contrast, invert_display, alternative_com_pins, vpp,
| and the rest.
*/
return [
    'default_config' => 'i2c',
    'configs' => [
        'i2c' => [
            'driver' => 'none',
            'device' => '',
            'slave' => SH1106I2CAddress::SAO_GROUNDED->value,
            'panel' => [
                'width' => 128,
                'height' => 64,
            ],
        ],
        'spi' => [
            'driver' => 'none',
            'device' => '',
            'chip_select' => 0,
            'speed' => 4_000_000,
            'dc' => [
                'driver' => 'none',
                'device' => '',
                'pin' => 0,
            ],
            'rst' => [
                'driver' => 'none',
                'device' => '',
                'pin' => 1,
            ],
            'panel' => [
                'width' => 128,
                'height' => 64,
            ],
        ],
    ],
];
