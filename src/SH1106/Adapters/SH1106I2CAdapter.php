<?php

namespace DeptOfScrapyardRobotics\Displays\SH1106\SH1106\Adapters;

use Waveforms\Carriers\I2C\I2CDevice;

class SH1106I2CAdapter extends SH1106DataCarrier
{
    public function __construct(
        I2CDevice $carrier,
        protected int $max_packet_size
    ) {
        parent::__construct($carrier);
    }

    public function data(array $data): void
    {
        foreach (array_chunk($data, $this->max_packet_size) as $chunk) {
            $payload = [0x40, ...$chunk];
            $this->carrier->write($payload);
        }
    }

    public function command(int $register_hex, array $command_data = []): void
    {
        $payload = [0x00, ...[$register_hex, ...$command_data]];
        $this->carrier->write($payload);
    }
}
