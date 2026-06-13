<?php

namespace DeptOfScrapyardRobotics\Displays\SH1106\SH1106\Concerns;

trait SH1106InternalAPI
{
    protected function setDisplay(bool $on): void
    {
        $on ? $this->displayOn() : $this->displayOff();
    }

    protected function setFillOverlay(bool $on): void
    {
        $on ? $this->filledScreenMode() : $this->normalOperationMode();
    }

    protected function data(array $data): void
    {
        $this->carrier->data($data);
    }

    protected function command(int $register_hex, array $command_data = []): void
    {
        $this->carrier->command($register_hex, $command_data);
    }
}
