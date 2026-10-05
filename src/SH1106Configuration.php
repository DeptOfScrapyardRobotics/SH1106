<?php

namespace DeptOfScrapyardRobotics\Displays\SH1106;

use DeptOfScrapyardRobotics\Displays\SH1106\Breakouts\SH1106COMPinsHWConfig;
use DeptOfScrapyardRobotics\Displays\SH1106\Enums\SH1106PumpVoltage;
use DeptOfScrapyardRobotics\Displays\SH1106\Enums\SH1106VoltageCommonHigh;
use ReflectionMethod;
use ReflectionParameter;

/**
 * The panel's geometry and settings. A setting the panel can change at run time is also a magic property on the
 * panel under the same key, readable and writable ($panel->contrast, $panel->contrast = 0x40).
 */
class SH1106Configuration
{
    protected bool $display_on = false;

    protected bool $charge_pump = true;

    protected bool $fill_overlay_on = false;

    protected SH1106COMPinsHWConfig $com_pins_config;

    /**
     * @param  int  $column_offset  The RAM column of the panel's first pixel. The SH1106 has 132 columns and a
     *                              128-pixel glass is centred on them, so its first pixel is column 2.
     * @param  ?bool  $alternative_com_pins  0xDA bit 4. Null picks by height: alternative above 32 rows (128×64),
     *                                       sequential at 32 rows or fewer. The wrong one blanks or doubles every
     *                                       other row.
     */
    public function __construct(
        protected int $width = 128,
        protected int $height = 64,
        protected int $column_offset = 2,
        protected int $contrast = 191,
        protected int $start_line = 0,
        protected int $display_offset = 0,
        protected int $max_packet_size = 1024,
        protected bool $invert_display = false,
        protected bool $enable_com_lr_remap = false,
        protected bool $powered_by_host_device = true,
        protected bool $map_line_0_to_line_127 = false,
        ?bool $alternative_com_pins = null,
        protected bool $reverse_line_scan_direction = false,
        protected SH1106VoltageCommonHigh $v_com_h = SH1106VoltageCommonHigh::LEVEL_077_ALT,
        protected SH1106PumpVoltage $vpp = SH1106PumpVoltage::PUMP_VOLTAGE_900,
    ) {
        if ($this->width < 1 || $this->height < 16 || $this->height > 64 || $this->height % 8 !== 0) {
            throw SH1106Exception::invalidGeometry($this->width, $this->height);
        }

        if ($this->column_offset < 0 || $this->column_offset + $this->width > 132) {
            throw SH1106Exception::invalidColumnOffset($this->column_offset, $this->width);
        }

        $this->com_pins_config = new SH1106COMPinsHWConfig(
            $this->enable_com_lr_remap,
            $alternative_com_pins ?? $this->height > 32,
        );
    }

    /**
     * Built from a config entry's `panel` array: constructor arguments by name.
     *
     * @param  array<string, mixed>  $panel
     *
     * @throws SH1106Exception for a key the constructor does not take
     */
    public static function fromArray(array $panel): static
    {
        $known = array_map(
            static fn (ReflectionParameter $parameter): string => $parameter->getName(),
            (new ReflectionMethod(static::class, '__construct'))->getParameters(),
        );

        foreach (array_keys($panel) as $key) {
            if (! in_array($key, $known, true)) {
                throw SH1106Exception::invalidProperty((string) $key, static::class);
            }
        }

        return new static(...$panel);
    }

    public function get(string $var): mixed
    {
        if (isset($this->$var)) {
            return $this->$var;
        }

        throw SH1106Exception::invalidProperty($var, static::class);
    }

    public function set(string $var, mixed $value): void
    {
        if (isset($this->$var)) {
            $this->$var = $value;

            return;
        }

        throw SH1106Exception::invalidProperty($var, static::class);
    }
}
