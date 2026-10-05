<?php

namespace DeptOfScrapyardRobotics\Displays\SH1106\Transports;

use GeneralPurposeIO\Contracts\Digital\DigitalOutTransport;
use GeneralPurposeIO\Contracts\SPI\SPITransport;

/** 4-wire SPI: DC low for commands, high for display RAM bytes; RST pulses the chip's reset on boot. */
class SH1106SPITransport extends SH1106DataTransport
{
    public function __construct(
        protected SPITransport $transport,
        protected DigitalOutTransport $dc,
        protected DigitalOutTransport $rst,
        int $max_packet_size = 1024
    ) {
        parent::__construct($max_packet_size);
    }

    protected function sendCommand(int $register, array $command_data = []): int
    {
        $this->dc->low();
        $payload = [$register, ...$command_data];

        return $this->checked(sprintf('command 0x%02X', $register), count($payload), $this->transport->write($payload));
    }

    protected function sendData(array|string $data = []): void
    {
        $this->dc->high();

        if (is_string($data)) {
            foreach (str_split($data, $this->max_packet_size) as $chunk) {
                $this->checked('data', strlen($chunk), $this->transport->write($chunk));
            }

            return;
        }

        foreach (array_chunk($data, $this->max_packet_size) as $chunk) {
            $this->checked('data', count($chunk), $this->transport->write($chunk));
        }
    }

    /** RES low for at least 10 µs resets the chip; it is ready again once RES is high. */
    public function reset(): void
    {
        $this->rst->high();
        usleep(3000);

        $this->rst->low();
        usleep(3000);

        $this->rst->high();
        usleep(3000);
    }

    /** SPI adapters split a long write into the bus's own message size. */
    protected function packetLimit(): int
    {
        return PHP_INT_MAX;
    }

    protected function closeMain(): void
    {
        $this->dc->close();
        $this->rst->close();
    }
}
