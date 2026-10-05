<?php

namespace DeptOfScrapyardRobotics\Displays\SH1106\Concerns;

use DeptOfScrapyardRobotics\Displays\SH1106\Breakouts\SH1106ChargePump;
use DeptOfScrapyardRobotics\Displays\SH1106\Breakouts\SH1106COMPinsHWConfig;
use DeptOfScrapyardRobotics\Displays\SH1106\Breakouts\SH1106COMScanDirection;
use DeptOfScrapyardRobotics\Displays\SH1106\Breakouts\SH1106DataClock;
use DeptOfScrapyardRobotics\Displays\SH1106\Breakouts\SH1106SegmentRemap;
use DeptOfScrapyardRobotics\Displays\SH1106\Enums\SH1106OpCode;
use DeptOfScrapyardRobotics\Displays\SH1106\Enums\SH1106Precharge;
use DeptOfScrapyardRobotics\Displays\SH1106\Enums\SH1106PumpVoltage;
use DeptOfScrapyardRobotics\Displays\SH1106\Enums\SH1106StartLineCommand;
use DeptOfScrapyardRobotics\Displays\SH1106\Enums\SH1106VoltageCommonHigh;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106Configuration;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106Exception;
use DeptOfScrapyardRobotics\Displays\SH1106\Transports\SH1106DataTransport;

trait SH1106API
{
    abstract public function config(): SH1106Configuration;
    abstract public function transport(): SH1106DataTransport;

    protected function sendCommand(SH1106OpCode $register, array $command_data = []): int
    {
        return $this->transport()->command($register->value, $command_data);
    }

    public function displayOn(): void
    {
        $this->sendCommand(SH1106OpCode::TOGGLE_DISPLAY_ON);
        $this->config()->set('display_on', true);
    }

    public function displayOff(): void
    {
        $this->sendCommand(SH1106OpCode::TOGGLE_DISPLAY_OFF);
        $this->config()->set('display_on', false);
    }

    public function setDataClockOscillationFrequency(SH1106DataClock $freq): void
    {
        $this->sendCommand(SH1106OpCode::DISPLAY_CLOCK_REGISTER, [$freq->toByte()]);
    }

    /**
     * @throws SH1106Exception
     */
    public function setMultiplexRatio(int $ratio): void
    {
        if (($ratio < 16) || ($ratio > 63)) {
            throw SH1106Exception::invalidMux($ratio);
        }

        $this->sendCommand(SH1106OpCode::MUX_REGISTER, [$ratio]);
    }

    /**
     * @throws SH1106Exception
     */
    public function setDisplayOffset(int $offset): void
    {
        if (($offset < 0) || ($offset > 63)) {
            throw SH1106Exception::invalidOffset($offset);
        }

        $this->sendCommand(SH1106OpCode::VERTICAL_OFFSET_REGISTER, [$offset]);
        $this->config()->set('display_offset', $offset);
    }

    /**
     * @throws SH1106Exception
     */
    public function setDisplayStartLine(int $pos): void
    {
        if (($pos < 0) || ($pos > 63)) {
            throw SH1106Exception::invalidStartLine($pos);
        }
        $this->transport()->command(SH1106StartLineCommand::fromInt($pos)->value);
        $this->config()->set('start_line', $pos);
    }

    public function setChargePumpRegulator(bool $flag): void
    {
        $register = new SH1106ChargePump($flag);

        $this->sendCommand(SH1106OpCode::DCDC_CONTROL_REGISTER, [$register->toByte()]);
        $this->config()->set('charge_pump', $flag);
    }

    public function setSegmentRemap(bool $flag): void
    {
        $register = new SH1106SegmentRemap($flag);

        $this->sendCommand($register->toOpCode());
        $this->config()->set('map_line_0_to_line_127', $flag);
    }

    public function setCOMOutputScanDirection(bool $flag): void
    {
        $register = new SH1106COMScanDirection($flag);

        $this->sendCommand($register->toOpCode());
        $this->config()->set('reverse_line_scan_direction', $flag);
    }

    public function setCOMPinsHardwareConfiguration(SH1106COMPinsHWConfig $config): void
    {
        $this->sendCommand(SH1106OpCode::COM_PINS_HW_CONFIG_REGISTER, [$config->toByte()]);
        $this->config()->set('com_pins_config', $config);
        $this->config()->set('enable_com_lr_remap', $config->enable_com_lr_remap);
    }

    /**
     * @throws SH1106Exception
     */
    public function setContrast(int $contrast): void
    {
        if (($contrast < 0) || ($contrast > 255)) {
            throw SH1106Exception::invalidContrast($contrast);
        }

        $this->sendCommand(SH1106OpCode::CONTRAST_REGISTER, [$contrast]);
        $this->config()->set('contrast', $contrast);
    }

    public function setPrechargePeriod(bool $powered_by_host_device): void
    {
        $period = $powered_by_host_device ? SH1106Precharge::RECOMMENDED : SH1106Precharge::DEFAULT;
        $this->sendCommand(SH1106OpCode::SET_PRECHARGE_PERIOD, [$period->value]);
        $this->config()->set('powered_by_host_device', $powered_by_host_device);
    }

    public function setVoltageCommonHigh(SH1106VoltageCommonHigh $v_com_h): void
    {
        $this->sendCommand(SH1106OpCode::SET_V_COM_H_DESELECT_LEVEL, [$v_com_h->value]);
        $this->config()->set('v_com_h', $v_com_h);
    }

    public function setFillOverlay(bool $flag): void
    {
        if ($flag) {
            $this->sendCommand(SH1106OpCode::FILLED_SCREEN_MODE);
        } else {
            $this->sendCommand(SH1106OpCode::NORMAL_OPERATION_MODE);
        }

        $this->config()->set('fill_overlay_on', $flag);
    }

    public function setInvertDisplay(bool $flag): void
    {
        if ($flag) {
            $this->sendCommand(SH1106OpCode::INVERT_DISPLAY_ON);
        } else {
            $this->sendCommand(SH1106OpCode::INVERT_DISPLAY_OFF);
        }

        $this->config()->set('invert_display', $flag);
    }

    public function setPumpVoltage(SH1106PumpVoltage $vpp): void
    {
        $this->sendCommand($vpp->toOpCode());
        $this->config()->set('vpp', $vpp);
    }

    /**
     * Point the RAM pointer at a panel column of one page: lower nibble, upper nibble, page. The column is shifted
     * by column_offset onto the chip's 132 columns.
     */
    public function setPagePosition(int $x, int $page): void
    {
        $column = $x + $this->config()->get('column_offset');

        $this->transport()->command(SH1106OpCode::SET_LOW_COLUMN->value | ($column & 0x0F));
        $this->transport()->command(SH1106OpCode::SET_HIGH_COLUMN->value | (($column >> 4) & 0x0F));
        $this->transport()->command(SH1106OpCode::SET_PAGE_ADDRESS->value | ($page & 0x07));
    }

    public function setDisplay(bool $on): void
    {
        $on ? $this->displayOn() : $this->displayOff();
    }
}