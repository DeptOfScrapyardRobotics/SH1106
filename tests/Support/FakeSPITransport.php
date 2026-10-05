<?php

namespace DeptOfScrapyardRobotics\Displays\SH1106\Tests\Support;

use GeneralPurposeIO\SPI\SPITransport;

/** Records every write, tagged with the DC level at that moment once a DC pin is attached; 'raw' before. */
final class FakeSPITransport extends SPITransport
{
    /** @var list<array{0: string, 1: list<int>}> ['cmd'|'data'|'raw', bytes] */
    public array $writes = [];

    public ?FakeOutputPin $dc = null;

    /** Every write answers this when set, as a failed spidev ioctl answers -1. */
    public ?int $answer = null;

    public bool $released = false;

    public function handle(): string
    {
        return 'fake';
    }

    public function read(int $len): array|false
    {
        return array_fill(0, $len, 0);
    }

    public function write(array|string $data): int
    {
        $bytes = is_array($data) ? array_values($data) : array_values(unpack('C*', $data));
        $this->writes[] = [is_null($this->dc) ? 'raw' : ($this->dc->state ? 'data' : 'cmd'), $bytes];

        return $this->answer ?? count($bytes);
    }

    public function transfer(array|string $data): array|false
    {
        return false;
    }

    public function writeRead(array|string $bytes_to_write, int $bytes_to_read): array|false
    {
        return array_fill(0, $bytes_to_read, 0);
    }

    public function speed(int $hz): static
    {
        $this->hz = $hz;

        return $this;
    }

    public function clock(): ?int
    {
        return $this->hz;
    }

    protected function beginSelection(): void {}

    protected function endSelection(): void {}

    protected function release(): void
    {
        $this->released = true;
    }
}
