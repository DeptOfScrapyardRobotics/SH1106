<?php

namespace ScrapyardIO\Displays\Monochrome\SH1106\Adapters;

use ScrapyardIO\Displays\Adapters\MonochromeDisplayAdapter;
use ScrapyardIO\Displays\Monochrome\SH1106\Concerns\SH1106I2CChip;
use ScrapyardIO\Displays\Monochrome\SH1106\Enums\SH1106I2CAddress;
use ScrapyardIO\Displays\Monochrome\SH1106\Concerns\SH1106BootSequence;

class SH1106I2CAdapter extends MonochromeDisplayAdapter
{
    use SH1106I2CChip;
    use SH1106BootSequence;

    public function bus(int $bus):static
    {
        $this->i2c_sh1106_bus($bus);
        return $this;
    }

    public function address(SH1106I2CAddress $address):static
    {
        $this->i2c_sh1106_address($address->value);
        return $this;
    }

    public function boot(): static
    {
        $this->sh1106_i2c();

        $this->max_packet_size = intVal(($this->width * $this->height) / 8);
        $this->multiplex_ratio = $this->height - 1;

        $this->turnDisplayOff();
        $this->setDisplayClock();
        $this->setMultiplexRatio();
        $this->setDisplayOffset();
        $this->setStartLine();
        $this->setDCDC();
        $this->setSegmentRemap();
        $this->setComScanRemap();
        $this->setCommPins();
        $this->setContrast();
        $this->setPrechargePeriod();
        $this->setVoltageCommonHigh();
        $this->setVPP();
        $this->setNormalDisplayMode();
        $this->setMemoryMode();
        $this->displayResumeState();
        $this->wait(100);
        $this->turnDisplayOn();

        return $this;
    }
}
