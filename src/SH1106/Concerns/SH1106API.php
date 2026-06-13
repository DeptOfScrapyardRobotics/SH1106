<?php

namespace DeptOfScrapyardRobotics\Displays\SH1106\SH1106\Concerns;

use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\DataObjects\SH1106ChargePump;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\DataObjects\SH1106COMPinsHWConfig;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\DataObjects\SH1106COMScanDirection;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\DataObjects\SH1106DataClock;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\DataObjects\SH1106SegmentRemap;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\Enums\SH1106OpCode;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\Enums\SH1106Precharge;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\Enums\SH1106PumpVoltage;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\Enums\SH1106StartLineCommand;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\Enums\SH1106VoltageCommonHigh;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\Exceptions\SH1106Exception;
use Exception;

trait SH1106API
{
    use SH1106InternalAPI;

    public function displayOn(): void
    {
        $this->command(SH1106OpCode::TOGGLE_DISPLAY_ON->value);
        $this->display_on = true;
    }

    public function displayOff(): void
    {
        $this->command(SH1106OpCode::TOGGLE_DISPLAY_OFF->value);
        $this->display_on = false;
    }

    public function setDataClockOscillationFrequency(SH1106DataClock $freq): void
    {
        $this->command(SH1106OpCode::DISPLAY_CLOCK_REGISTER->value, [$freq->toByte()]);
    }

    /**
     * @throws Exception
     */
    public function setMultiplexRatio(int $ratio): void
    {
        if (($ratio < 16) || ($ratio > 63)) {
            throw SH1106Exception::invalidMux($ratio);
        }

        $this->command(SH1106OpCode::MUX_REGISTER->value, [$ratio]);
    }

    public function setDisplayOffset(int $offset): void
    {
        if (($offset < 0) || ($offset > 63)) {
            throw SH1106Exception::invalidOffset($offset);
        }

        $this->command(SH1106OpCode::VERTICAL_OFFSET_REGISTER->value, [$offset]);
        $this->_offset = $offset;
    }

    public function setDisplayStartLine(int $pos): void
    {
        if (($pos < 0) || ($pos > 63)) {
            throw SH1106Exception::invalidStartLine($pos);
        }
        $this->command(SH1106StartLineCommand::fromInt($pos)->value);
        $this->_start_line = $pos;
    }

    public function setChargePumpRegulator(bool $flag): void
    {
        $register = new SH1106ChargePump($flag);

        $this->command(SH1106OpCode::DCDC_CONTROL_REGISTER->value, [$register->toByte()]);
        $this->charge_pump = $flag;
    }

    public function setSegmentRemap(bool $flag): void
    {
        $register = new SH1106SegmentRemap($flag);

        $this->command($register->toOpCode()->value);
        $this->_map_line_0_to_line_127 = $flag;
    }

    public function setCOMOutputScanDirection(bool $flag): void
    {
        $register = new SH1106COMScanDirection($flag);

        $this->command($register->toOpCode()->value);
        $this->_reverse_line_scan_direction = $flag;
    }

    public function setCOMPinsHardwareConfiguration(SH1106COMPinsHWConfig $config): void
    {
        $this->command(SH1106OpCode::COM_PINS_HW_CONFIG_REGISTER->value, [$config->toByte()]);
        $this->_com_pins_config = $config;
    }

    public function setContrast(int $contrast): void
    {
        if (($contrast < 0) || ($contrast > 255)) {
            throw SH1106Exception::invalidContrast($contrast);
        }

        $this->command(SH1106OpCode::CONTRAST_REGISTER->value, [$contrast]);
        $this->_contrast = $contrast;
    }

    public function setPrechargePeriod(bool $powered_by_host_device): void
    {
        $period = $powered_by_host_device ? SH1106Precharge::RECOMMENDED : SH1106Precharge::DEFAULT;
        $this->command(SH1106OpCode::SET_PRECHARGE_PERIOD->value, [$period->value]);
        $this->_powered_by_host_device = $powered_by_host_device;
    }

    public function setVoltageCommonHigh(SH1106VoltageCommonHigh $v_com_h): void
    {
        $this->command(SH1106OpCode::SET_V_COM_H_DESELECT_LEVEL->value, [$v_com_h->value]);
        $this->_v_com_h = $v_com_h;
    }

    public function setVPP(SH1106PumpVoltage $vpp): void
    {
        $this->command($vpp->toOpCode()->value);
        $this->_vpp = $vpp;
    }

    public function filledScreenMode(): void
    {
        $this->command(SH1106OpCode::FILLED_SCREEN_MODE->value);
        $this->fill_overlay_on = true;
    }

    public function normalOperationMode(): void
    {
        $this->command(SH1106OpCode::NORMAL_OPERATION_MODE->value);
        $this->fill_overlay_on = false;
    }

    public function setMemoryMode(int $hex = 0x10): void
    {
        $this->command(SH1106OpCode::MEMORY_MODE_REGISTER->value, [$hex]);
    }

    /**
     * Blast a vertical-page region one page at a time.
     *
     * SH1106 has no auto-incrementing address window: each page is selected with
     * a B0|page command, the column pointer is reset to the panel's RAM origin,
     * then that page's row of bytes is streamed. $x nudges the column origin,
     * while $y/$height pick which pages to write — so a single 8-row strip
     * (a partial update) touches just its page and leaves the rest of the panel
     * alone. The payload is page-major for the region it covers.
     *
     * @param  array<int, int>  $payload  Page-major packed bytes (region's first page first).
     */
    public function sendData(int $x, int $y, int $width, int $height, array $payload): void
    {
        $start_page = intdiv($y, 8);
        $page_count = intdiv($height + 7, 8);
        $bytes_per_page = $width;
        $column = $this->_column_offset + $x;

        for ($index = 0; $index < $page_count; $index++) {
            $page = $start_page + $index;

            $this->command(
                SH1106OpCode::SET_PAGE_ADDR->value | $page, [
                    SH1106OpCode::SET_LOW_COLUMN->value | ($column & 0x0F),
                    SH1106OpCode::SET_HIGH_COLUMN->value | (($column >> 4) & 0x0F),
                ]
            );

            $page_data = array_slice($payload, $index * $bytes_per_page, $bytes_per_page);
            $this->data($page_data);
        }
    }
}
