<?php

namespace DeptOfScrapyardRobotics\Displays\SH1106\SH1106\Factory;

use BareMetal\CircuitFactory;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\Adapters\SH1106I2CAdapter;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\DataObjects\SH1106COMPinsHWConfig;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\Enums\SH1106PumpVoltage;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\Enums\SH1106VoltageCommonHigh;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\SH1106;
use Exception;
use Waveforms\Carriers\I2C\Factory\I2CConnectionBuilder;
use Waveforms\Carriers\I2C\I2CDevice;

class SH1106Factory extends CircuitFactory
{
    public int $width = 128;

    public int $height = 64;

    public int $contrast = 191;

    public int $start_line = 0;

    public int $display_offset = 0;

    public int $max_packet_size = 1024;

    public bool $enable_com_lr_remap = false;

    public bool $powered_by_host_device = true;

    public bool $map_line_0_to_line_127 = false;

    public bool $sequential_com_pin_config = true;

    public bool $reverse_line_scan_direction = false;

    public SH1106PumpVoltage $vpp = SH1106PumpVoltage::PUMP_VOLTAGE_900;

    public SH1106VoltageCommonHigh $v_com_h = SH1106VoltageCommonHigh::LEVEL_077_ALT;

    public ?I2CConnectionBuilder $connection = null;

    public function __construct(
        public I2CConnectionBuilder $i2c_connection
    ) {}

    public function i2c(string|int $chip_device, int $slave_address): static
    {
        $this->connection = $this->i2c_connection->firstly($chip_device)
            ->slaveAddress($slave_address);

        return $this;
    }

    public function width(int $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function height(int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function maxPacketSize(int $max_packet_size): static
    {
        $this->max_packet_size = $max_packet_size;

        return $this;
    }

    public function offset(int $offset): static
    {
        $this->display_offset = $offset;

        return $this;
    }

    public function startLine(int $pos): static
    {
        $this->start_line = $pos;

        return $this;
    }

    public function flipLine0And127(bool $flip): static
    {
        $this->map_line_0_to_line_127 = $flip;

        return $this;
    }

    public function flipLineScanDir(bool $flip): static
    {
        $this->reverse_line_scan_direction = $flip;

        return $this;
    }

    public function startingContrast(int $contrast): static
    {
        $this->contrast = $contrast;

        return $this;
    }

    public function notPoweredByHostDevice(): static
    {
        $this->powered_by_host_device = false;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function create(): SH1106
    {
        $carrier = $this->connection?->boot();
        if (is_null($carrier)) {
            throw new Exception('A connection was not registered.');
        }

        if (! $carrier instanceof I2CDevice) {
            throw new Exception('The SH1106 driver only supports I2C connections.');
        }

        $carrier = new SH1106I2CAdapter($carrier, $this->max_packet_size);

        return new SH1106(
            $carrier,
            $this->width,
            $this->height,
            $this->display_offset,
            $this->contrast,
            $this->start_line,
            $this->map_line_0_to_line_127,
            $this->reverse_line_scan_direction,
            new SH1106COMPinsHWConfig(
                $this->enable_com_lr_remap,
                $this->sequential_com_pin_config
            ),
            $this->powered_by_host_device,
            $this->v_com_h,
            $this->vpp,
        );
    }
}
