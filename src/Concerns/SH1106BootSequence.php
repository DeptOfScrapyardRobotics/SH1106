<?php

namespace ScrapyardIO\Displays\Monochrome\SH1106\Concerns;

use ScrapyardIO\Displays\Monochrome\SH1106\Enums\SH1106Command;
use ScrapyardIO\Displays\Monochrome\SH1106\Enums\SH1106Contrast;
use ScrapyardIO\Displays\Monochrome\SH1106\Enums\SH1106Rotation;
use ScrapyardIO\Displays\Monochrome\SH1106\Enums\SH1106Precharge;
use ScrapyardIO\Displays\Monochrome\SH1106\Enums\SH1106ComPinConfig;
use ScrapyardIO\Displays\Monochrome\SH1106\Enums\SH1106DisplayClock;
use ScrapyardIO\Displays\Monochrome\SH1106\Enums\SH1106VoltageCommonHigh;

trait SH1106BootSequence
{
    protected int $page_start_offset = 2;
    protected int $start_line = 0;
    protected int $multiplex_ratio;
    protected int $display_offset = 0;
    protected bool $dcdc_enabled = true;
    protected SH1106Contrast $contrast = SH1106Contrast::MAXIMUM;
    protected SH1106Rotation $rotation = SH1106Rotation::INVERTED_PORTRAIT;
    protected SH1106DisplayClock $clock_rate = SH1106DisplayClock::DEFAULT;
    protected SH1106VoltageCommonHigh $v_com_h = SH1106VoltageCommonHigh::LEVEL_077_ALT;
    protected int $vpp_setting = 0x33;
    protected SH1106Precharge $precharge = SH1106Precharge::SH1106_DEFAULT;

    abstract public function wait(int $ms): void;
    abstract public function sendCommand(array $bytes): void;
    abstract public function sendData(array $bytes): void;

    protected function turnDisplayOff(): void
    {
        $this->sendCommand([SH1106Command::DISPLAY_OFF->value]);
    }

    protected function setDisplayClock(): void
    {
        $this->sendCommand([
            SH1106Command::SET_DISPLAY_CLOCK->value,
            $this->clock_rate->value
        ]);
    }

    protected function setMultiplexRatio(): void
    {
        $this->sendCommand([
            SH1106Command::SET_MULTIPLEX_RATIO->value,
            $this->multiplex_ratio
        ]);
    }

    protected function setDisplayOffset(): void
    {
        $this->sendCommand([
            SH1106Command::SET_DISPLAY_OFFSET->value,
            $this->display_offset
        ]);
    }

    protected function setStartLine(): void
    {
        $this->sendCommand([
            SH1106Command::DISPLAY_START_LINE->value | $this->start_line
        ]);
    }

    protected function setDCDC(): void
    {
        $this->sendCommand([
            SH1106Command::DCDC->value,
            $this->dcdc_enabled ? 0x8B : 0x8A
        ]);
    }

    protected function setSegmentRemap(): void
    {
        $rot = $this->rotation->toRemap();
        $this->sendCommand([$rot[0]]);
    }

    protected function setComScanRemap(): void
    {
        $rot = $this->rotation->toRemap();
        $this->sendCommand([$rot[1]]);
    }

    protected function setCommPins(): void
    {
        $this->sendCommand([
            SH1106Command::SET_COM_PINS->value,
            SH1106ComPinConfig::forHeight($this->height)->value
        ]);
    }

    protected function setContrast(): void
    {
        $this->sendCommand([
            SH1106Command::SET_CONTRAST->value,
            $this->contrast->value
        ]);
    }

    protected function setPrechargePeriod(): void
    {
        $this->sendCommand([
            SH1106Command::SET_PRECHARGE_PERIOD->value,
            $this->precharge->value
        ]);
    }

    protected function setVoltageCommonHigh(): void
    {
        $this->sendCommand([
            SH1106Command::SET_VOLTAGE_COMMON_HIGH->value,
            $this->v_com_h->value
        ]);
    }

    protected function setVPP(): void
    {
        $this->sendCommand([$this->vpp_setting]);
    }

    protected function setNormalDisplayMode(): void
    {
        $this->sendCommand([SH1106Command::NORMAL_DISPLAY->value]);
    }

    protected function setMemoryMode(): void
    {
        $this->sendCommand([
            SH1106Command::SET_MEMORY_MODE->value,
            0x10
        ]);
    }

    protected function displayResumeState(): void
    {
        $this->sendCommand([SH1106Command::ENTIRE_DISPLAY_ON_RESUME->value]);
    }

    protected function turnDisplayOn(): void
    {
        $this->sendCommand([SH1106Command::DISPLAY_ON->value]);
    }

    public function display(): static
    {
        $payload = $this->wire->toRows();
        $pages = intval($this->height / 8);
        $bytes_per_page = $this->width;

        for ($page = 0; $page < $pages; $page++) {
            $this->sendCommand([
                SH1106Command::SET_PAGE_ADDR->value | $page,
                SH1106Command::SET_LOW_COLUMN->value | (($this->page_start_offset) & 0x0F),
                SH1106Command::SET_HIGH_COLUMN->value | (($this->page_start_offset) >> 4)
            ]);

            $page_data = array_slice($payload, $page * $bytes_per_page, $bytes_per_page);
            $this->sendData($page_data);
        }

        return $this;
    }
}
