<?php

namespace ScrapyardIO\Displays\Monochrome\SH1106\Concerns;

use ScrapyardIO\Transports\I2CTransport;

trait SH1106I2CChip
{
    protected ?I2CTransport $sh1106_i2c = null;
    protected int $sh1106_i2c_bus = 1;
    protected int $sh1106_i2c_address = 0;
    protected int $max_packet_size = 0;

    protected function i2c_sh1106_bus(?int $bus = null): int
    {
        if($bus)
        {
            $this->sh1106_i2c_bus = $bus;
        }
        return $this->sh1106_i2c_bus;
    }

    protected function i2c_sh1106_address(?int $address = null): int
    {
        if($address)
        {
            $this->sh1106_i2c_address = $address;
        }
        return $this->sh1106_i2c_address;
    }

    protected function sh1106_i2c(): ?I2CTransport
    {
        if(empty($this->sh1106_i2c))
        {
            $this->sh1106_i2c = new I2CTransport(
                $this->i2c_sh1106_address(),
                $this->i2c_sh1106_bus()
            );
        }

        return $this->sh1106_i2c;
    }

    public function sendData(array $bytes): void
    {
        $payload = [0x40, ...$bytes];
        $this->sh1106_i2c()->send($payload);
    }

    public function sendCommand(array $bytes): void
    {
        $payload = [0x00, ...$bytes];
        $this->sh1106_i2c()->send($payload);
    }
}
