<?php

namespace DeptOfScrapyardRobotics\Displays\SH1106\Transports;

use DeptOfScrapyardRobotics\Displays\SH1106\SH1106Exception;
use GeneralPurposeIO\Contracts\IntegratedCircuits\DataCommander;

/**
 * Commands and display RAM bytes to the panel. Every write is checked: a short or failed write (a NACK, a bus
 * error) throws, so a missing or unpowered I2C panel fails boot instead of booting silently.
 */
abstract class SH1106DataTransport implements DataCommander
{
    public function __construct(
        protected int $max_packet_size
    ) {
        $this->maxPacketSize($max_packet_size);
    }

    abstract protected function closeMain(): void;
    abstract protected function sendData(array|string $data = []): void;
    abstract protected function sendCommand(int $register, array $command_data = []): int;

    /** The largest data packet this bus takes in one write. */
    abstract protected function packetLimit(): int;

    public function command(int $register, array $command_data = []): int
    {
        return $this->sendCommand($register, $command_data);
    }

    public function data(array|string $data = []): void
    {
        $this->sendData($data);
    }

    /** Data bytes per write; transmit() splits a frame into packets of at most this many. */
    public function maxPacketSize(int $size): static
    {
        if ($size < 1 || $size > $this->packetLimit()) {
            throw SH1106Exception::invalidPacketSize($size, $this->packetLimit());
        }

        $this->max_packet_size = $size;

        return $this;
    }

    public function reset(): void {}

    public function close(): void
    {
        $this->closeMain();
    }

    /** @throws SH1106Exception when the bus wrote fewer bytes than asked */
    protected function checked(string $what, int $expected, int $written): int
    {
        if ($written !== $expected) {
            throw SH1106Exception::writeFailed($what, $expected, $written);
        }

        return $written;
    }
}
