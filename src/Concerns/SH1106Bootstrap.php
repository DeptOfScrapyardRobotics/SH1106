<?php

namespace DeptOfScrapyardRobotics\Displays\SH1106\Concerns;

use DeptOfScrapyardRobotics\Displays\SH1106\Breakouts\SH1106DataClock;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106Exception;

trait SH1106Bootstrap
{
    use SH1106API;

    /**
     * Every run-time setting under its config key: reads come from config(), writes go to the chip and then config().
     *
     * @throws SH1106Exception
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'display_on', 'display_offset', 'contrast', 'start_line', 'charge_pump',
            'map_line_0_to_line_127', 'reverse_line_scan_direction', 'com_pins_config', 'powered_by_host_device',
            'v_com_h', 'vpp', 'fill_overlay_on', 'invert_display' => $this->config()->get($name),
            default => throw SH1106Exception::invalidProperty($name, static::class),
        };
    }

    /**
     * @throws SH1106Exception
     */
    public function __set(string $name, mixed $value): void
    {
        match ($name) {
            'display_on' => $this->setDisplay((bool) $value),
            'display_offset' => $this->setDisplayOffset((int) $value),
            'contrast' => $this->setContrast((int) $value),
            'start_line' => $this->setDisplayStartLine((int) $value),
            'charge_pump' => $this->setChargePumpRegulator((bool) $value),
            'map_line_0_to_line_127' => $this->setSegmentRemap((bool) $value),
            'reverse_line_scan_direction' => $this->setCOMOutputScanDirection((bool) $value),
            'com_pins_config' => $this->setCOMPinsHardwareConfiguration($value),
            'powered_by_host_device' => $this->setPrechargePeriod((bool) $value),
            'v_com_h' => $this->setVoltageCommonHigh($value),
            'vpp' => $this->setPumpVoltage($value),
            'fill_overlay_on' => $this->setFillOverlay((bool) $value),
            'invert_display' => $this->setInvertDisplay((bool) $value),
            default => throw SH1106Exception::invalidProperty($name, static::class),
        };
    }

    protected function _boot(): void
    {
        $this->transport()->maxPacketSize($this->config()->get('max_packet_size'));

        $this->deviceReset();
        $this->displayOff();
        $this->setDataClockOscillationFrequency(new SH1106DataClock);
        $this->setMultiplexRatio($this->config()->get('height') - 1);
        $this->setDisplayOffset($this->config()->get('display_offset'));
        $this->setDisplayStartLine($this->config()->get('start_line'));
        $this->setChargePumpRegulator(true);
        $this->setSegmentRemap($this->config()->get('map_line_0_to_line_127'));
        $this->setCOMOutputScanDirection($this->config()->get('reverse_line_scan_direction'));
        $this->setCOMPinsHardwareConfiguration($this->config()->get('com_pins_config'));
        $this->setContrast($this->config()->get('contrast'));
        $this->setPrechargePeriod($this->config()->get('powered_by_host_device'));
        $this->setVoltageCommonHigh($this->config()->get('v_com_h'));
        $this->setPumpVoltage($this->config()->get('vpp'));
        $this->setFillOverlay(false);
        $this->setInvertDisplay($this->config()->get('invert_display'));

        // The DC-DC converter needs 100 ms to settle before the panel is lit.
        usleep(100_000);
        $this->displayOn();
    }

    protected function deviceReset(): void
    {
        $this->transport()->reset();
    }
}