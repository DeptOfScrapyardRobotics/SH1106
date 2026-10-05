<?php

namespace DeptOfScrapyardRobotics\Displays\SH1106\Tests\Support;

use GeneralPurposeIO\I2C\I2CTransport;

/** Records every write as a byte list. From write number $nack_from on (0-based), every write answers -1, a NACK. */
final class FakeI2CTransport extends I2CTransport
{
    /** @var list<list<int>> */
    public array $writes = [];

    public ?int $nack_from = null;

    public bool $released = false;

    public function __construct(int $address = 0x3C)
    {
        parent::__construct($address);
    }

    public function handle(): string
    {
        return 'fake';
    }

    public function probe(): bool
    {
        return true;
    }

    public function read(int $len): array|false
    {
        return false;
    }

    public function write(array|string $data): int
    {
        if (! is_null($this->nack_from) && count($this->writes) >= $this->nack_from) {
            return -1;
        }

        $bytes = is_array($data) ? array_values($data) : array_values(unpack('C*', $data));
        $this->writes[] = $bytes;

        return count($bytes);
    }

    public function writeRead(array|string $bytes_to_write, int $bytes_to_read): array|false
    {
        return false;
    }

    public function bulkWrite(array|string $messages): array|false
    {
        return false;
    }

    protected function release(): void
    {
        $this->released = true;
    }
}
