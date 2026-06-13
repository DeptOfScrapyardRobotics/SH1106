Introduction
============

PHP Package for the SH1106 Monochrome OLED display.

Compatible I2C Interfaces
===============
The SH1106 display communicates with your device over I2C, the InterIntegrated Circuit Protocol.

You can interface with displays such as the SH1106 with this package the following ways:
* A Linux Single-Board Computer's exposed GPIO pins using the dedicated I2C SDA/SCL pins
* An MPSSE-enabled USB-to-Serial device such as an FT232H generally using D0 and SCL and D1 for SDA connected to nearly any Linux or MacOS USB port.

Dependencies
=============
This package makes use of modules within:
* [The ScrapyardIO Framework](https://github.com/ScrapyardIO/framework)

This package also requires one of the following extensions in order to interface with I2C
* [POSI Extension v^0.4.0 or newer](https://github.com/php-io-extensions/posi)
* [FTDI Extension v^0.4.0 or newer](https://github.com/php-io-extensions/ftdi)

In addition, an extension wrapper package is needed

For ext-posi
* [Microscrap POSIX Package v0.4.0 or newer](https://github.com/microscrap/posix)
* [Microscrap Native I2C Package v0.4.0 or newer](https://github.com/microscrap/i2c)
* [Microscrap Native GPIO Package v0.4.0 or newer](https://github.com/microscrap/gpio)

For ext-ftdi
* [Microscrap FTDI Package v0.4.0 or newer](https://github.com/microscrap/ftdi)
* [Microscrap MPSSE Package v0.4.0 or newer](https://github.com/microscrap/mpsse)

Installing from Composer
====================
Inside the root of your PHP Project, simply require the SH1106 package from composer
```shell
composer require dept-of-scrapyard-robotics/sh1106
```
Framework Configuration
====================
If you would like to use the ScrapyardIO Framework to bootstrap your display without
wasting lines configuring your display right in the script you can add your desired
configuration to scrapyard-io.php, such as in this example:

### I2C
```php

use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\SH1106;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\Enums\SH1106I2CAddress;

return [
    'displays' => [
        // For Native Configurations 
        'sh1106-native' => [
            'class_name' => SH1106::class,
            'connection' => ['driver' => 'native'],
            'startup' => [
                'i2c' => [
                    'chip_device' => 1,
                    'slave_address' => SH1106I2CAddress::SAO_GROUNDED->value,
                ],
            ],
        ],
        // For USB Configurations
        'sh1106-usb' => [
            'class_name' => SH1106::class,
            'connection' => ['driver' => 'usb'],
            'startup' => [
                'i2c' => [
                    'chip_device' => 'ft232h',
                    'slave_address' => SH1106I2CAddress::SAO_GROUNDED->value,
                ],
            ],
        ],        
    ]
];

```

Basic Usage
============

### Native (POSIX) I2C driver. (Single Board Computers)
```php

use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\SH1106;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\Enums\SH1106I2CAddress;

$native_i2c_display = SH1106::connection('native')
    ->i2c(1, SH1106I2CAddress::SAO_GROUNDED->value)
    ->create()

```

### USB (MPSSE) driver using I2C. (Linux and MacOS)
```php

use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\SH1106;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\Enums\SH1106I2CAddress;

$usb_i2c_display = SH1106::connection('usb')
    ->i2c('ft232h', SH1106I2CAddress::SAO_GROUNDED->value)
    ->create()

```

## Alternative Usage

### Using Through the Display Library (as a MonochromeDisplay)
```php
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\SH1106;
use RealityInterface\Displays\Applied\Monochrome\MonochromeDisplay;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\Enums\SH1106I2CAddress;

$sh1106 = SH1106::connection('native')
    ->i2c(1, SH1106I2CAddress::SAO_GROUNDED->value)
    ->create()
    
$display = MonochromeDisplay::as($sh1106);

```

### Using Through the Display Framework (with an autoloaded config) (as a MonochromeDisplay)
```php

use RealityInterface\Displays\Applied\Monochrome\MonochromeDisplay;

$display = MonochromeDisplay::using('sh1106-native');

```

Display API
==========
The setters and property access in this API interface with the device directly
(register writes/reads), so you can use property access while still working
against the panel itself.

Readable Properties (Getters)
-----------------------------
* `$display->display_on`
  Returns whether the panel is currently on.

* `$display->offset`
  Returns the current vertical display offset.

* `$display->contrast`
  Returns the current contrast value (0–255).

* `$display->start_line`
  Returns the current display RAM start line.

* `$display->charge_pump`
  Returns whether the internal charge-pump regulator is enabled.

* `$display->flip_line_0_and_127`
  Returns whether segments are mirrored (horizontal flip).

* `$display->flip_line_scan_dir`
  Returns whether the COM scan direction is reversed (vertical flip).

* `$display->com_pins_config` (`SH1106COMPinsHWConfig`)
  Returns the current COM pin hardware configuration.

* `$display->powered_by_host_device`
  Returns whether the panel is treated as host-powered.

* `$display->v_com_h` (`SH1106VoltageCommonHigh`)
  Returns the current VCOMH deselect level.

* `$display->vpp` (`SH1106PumpVoltage`)
  Returns the current charge-pump output voltage.

* `$display->fill_overlay_on`
  Returns whether the fill overlay is enabled.

Writable Properties (Setters)
-----------------------------
* `$display->display_on = true;`
  Turns the panel on or off.

* `$display->offset = 0;`
  Sets the vertical display offset (COM shift).

* `$display->contrast = 191;`
  Sets the contrast/brightness (0–255).

* `$display->start_line = 0;`
  Sets the display RAM start line.

* `$display->charge_pump = true;`
  Enables or disables the internal charge-pump regulator.

* `$display->flip_line_0_and_127 = true;`
  Mirrors the segments (horizontal flip).

* `$display->flip_line_scan_dir = true;`
  Reverses the COM scan direction (vertical flip).

* `$display->com_pins_config = new SH1106COMPinsHWConfig(...);`
  Sets the COM pin hardware configuration.

* `$display->powered_by_host_device = true;`
  Adjusts the precharge period for host-powered vs externally powered panels.

* `$display->v_com_h = SH1106VoltageCommonHigh::LEVEL_077_ALT;`
  Sets the VCOMH deselect level.

* `$display->vpp = SH1106PumpVoltage::PUMP_VOLTAGE_900;`
  Sets the charge-pump output voltage.

* `$display->fill_overlay_on = true;`
  Toggles the fill overlay.

Drawing on the Display
============
Draw with a `Screen`, which wraps a `GFXRenderer` over a frame buffer matched to
the panel's `FormatSpec`, then ships the bytes on `render()`. A monochrome OLED
uses a `PageSegmentBuffer` (one update per dirty 8-row page). Colors are `0`
(pixel off) and `1` (pixel on).

```php
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\SH1106;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\Enums\SH1106I2CAddress;
use Microscrap\GFX\PhpdaFruit\Buffers\PageSegmentBuffer;
use Microscrap\GFX\PhpdaFruit\GFXRenderer;
use RealityInterface\Displays\Applied\Monochrome\MonochromeDisplay;
use RealityInterface\Displays\Screen;

$sh1106 = SH1106::connection('usb')
    ->i2c('ft232h', SH1106I2CAddress::SAO_GROUNDED->value)
    ->create();

$display = MonochromeDisplay::as($sh1106);

$buffer = new PageSegmentBuffer($display->width(), $display->height(), $display->getFormatSpec());
$screen = new Screen($display, new GFXRenderer($buffer));

$screen
    ->fill(0)
    ->drawRect(0, 0, $display->width(), $display->height(), 1)
    ->setTextColor(1)
    ->setCursor(8, 24)
    ->print('Hello from PHP')
    ->render();
```
