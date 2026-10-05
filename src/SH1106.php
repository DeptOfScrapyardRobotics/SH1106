<?php

namespace DeptOfScrapyardRobotics\Displays\SH1106;

use Surface\Contracts\Framebuffers\BitDepth;
use Surface\Contracts\Framebuffers\BitOrder;
use Surface\Contracts\Framebuffers\PageAxis;
use Surface\Contracts\Framebuffers\FormatSpec;
use Surface\Contracts\Framebuffers\PixelFormat;
use GeneralPurposeIO\IntegratedCircuits\Bootable;
use Surface\Contracts\Framebuffers\ScanDirection;
use GeneralPurposeIO\Contracts\IntegratedCircuits\DisplayPanel;
use GeneralPurposeIO\Contracts\IntegratedCircuits\Switchable;
use GeneralPurposeIO\Contracts\IntegratedCircuits\WindowAddressable;
use DeptOfScrapyardRobotics\Displays\SH1106\Concerns\ConjuresSH1106;
use DeptOfScrapyardRobotics\Displays\SH1106\Concerns\SH1106Bootstrap;
use DeptOfScrapyardRobotics\Displays\SH1106\Transports\SH1106DataTransport;

/**
 * A monochrome OLED of up to 128×64 pixels on the 132-column SH1106. Surface packs frames per formatSpec() and
 * hands them to transmit(); conjure('sh1106') or the i2c() / spi() factories build a wired, booted panel from
 * config.
 */
class SH1106 extends Bootable implements DisplayPanel, WindowAddressable, Switchable
{
    use ConjuresSH1106;
    use SH1106Bootstrap;

    protected FormatSpec $format_spec;

    public function __construct(
        protected readonly SH1106DataTransport $transport,
        protected SH1106Configuration $props,
        bool $boot_now = false,
    ) {
        $this->format_spec = $this->generateFormatSpec();

        parent::__construct($boot_now);
    }

    public function width(): int
    {
        return $this->props->get('width');
    }

    public function height(): int
    {
        return $this->props->get('height');
    }

    /**
     * Write bytes packed per formatSpec() into the rectangle at (x, y). The SH1106 only addresses by page: each
     * 8-row page of the region is placed at its column and streamed on its own, so the rest of the panel is left
     * alone. y and height round to 8-row pages.
     */
    public function transmit(int $origin_x, int $origin_y, array $raw_data, ?int $frame_width = null, ?int $frame_height = null): void
    {
        $width = $frame_width ?? $this->width();
        $height = $frame_height ?? $this->height();
        $bytes = array_values($raw_data);

        $first_page = $origin_y >> 3;
        $last_page = ($origin_y + $height - 1) >> 3;

        foreach (array_chunk($bytes, max(1, $width)) as $offset => $row) {
            if ($first_page + $offset > $last_page) {
                break;
            }

            $this->setPagePosition($origin_x, $first_page + $offset);
            $this->transport()->data($row);
        }
    }

    public function transport(): SH1106DataTransport
    {
        return $this->transport;
    }

    /** Release DC and RST on SPI; the bus connection belongs to its driver and stays open. */
    public function close(): void
    {
        $this->transport->close();
    }

    public function config(): SH1106Configuration
    {
        return $this->props;
    }

    /** How transmit() wants its bytes packed: 8-row pages, LSB the top row. */
    public function formatSpec(): FormatSpec
    {
        return $this->format_spec;
    }

    public function setFormatSpec(FormatSpec $format_spec): void
    {
        $this->format_spec = $format_spec;
    }

    public function generateFormatSpec(): FormatSpec
    {
        return new FormatSpec(
            PixelFormat::MONO_VERTICAL_PAGE,
            BitDepth::B1,
            ScanDirection::TOP_TO_BOTTOM,
            BitOrder::LSB_FIRST,
            page_axis: PageAxis::VERTICAL,
        );
    }
}
