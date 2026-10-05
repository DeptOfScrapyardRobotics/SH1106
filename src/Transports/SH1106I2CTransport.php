<?php

namespace DeptOfScrapyardRobotics\Displays\SH1106\Transports;

use GeneralPurposeIO\Contracts\I2C\I2CTransport;

/** Control byte 0x00 before commands, 0x40 before display RAM bytes. */
class SH1106I2CTransport extends SH1106DataTransport
{
    public function __construct(
        protected I2CTransport $transport,
        int $max_packet_size = 1024
    ) {
        parent::__construct($max_packet_size);
    }

    protected function sendCommand(int $register, array $command_data = []): int
    {
        $payload = [0x00, $register, ...$command_data];

        return $this->checked(sprintf('command 0x%02X', $register), count($payload), $this->transport->write($payload));
    }

    protected function sendData(array|string $data = []): void
    {
        $bytes = is_string($data) ? array_values(unpack('C*', $data) ?: []) : array_values($data);

        foreach (array_chunk($bytes, $this->max_packet_size) as $chunk) {
            $packet = [0x40, ...$chunk];
            $this->checked('data', count($packet), $this->transport->write($packet));
        }
    }

    /** gpio/i2c takes 8192 bytes per message; the control byte rides in front of each packet. */
    protected function packetLimit(): int
    {
        return 8191;
    }

    protected function closeMain(): void
    {
        //
    }
}
