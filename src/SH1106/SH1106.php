<?php

namespace DeptOfScrapyardRobotics\Displays\SH1106\SH1106;

use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\Adapters\SH1106DataCarrier;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\Concerns\SH1106API;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\DataObjects\SH1106COMPinsHWConfig;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\DataObjects\SH1106DataClock;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\Enums\SH1106PumpVoltage;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\Enums\SH1106VoltageCommonHigh;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\Exceptions\SH1106Exception;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106\Factory\SH1106Factory;
use Exception;
use RealityInterface\Displays\Attributes\OutputsOnlyBlackAndWhite;
use RealityInterface\Displays\Contracts\Applied\Monochrome\MonochromeDisplayInterface;
use RealityInterface\Displays\EmbeddedDisplay;
use ScrapyardIO\NutsAndBolts\DataObjects\DumpedBuffer;
use ScrapyardIO\NutsAndBolts\DataObjects\FormatSpec;
use ScrapyardIO\NutsAndBolts\Enums\BitDepth;
use ScrapyardIO\NutsAndBolts\Enums\BitOrder;
use ScrapyardIO\NutsAndBolts\Enums\PageAxis;
use ScrapyardIO\NutsAndBolts\Enums\PixelFormat;
use ScrapyardIO\NutsAndBolts\Enums\ScanDirection;
use Waveforms\Carriers\I2C\I2C;

#[OutputsOnlyBlackAndWhite]
class SH1106 extends EmbeddedDisplay implements MonochromeDisplayInterface
{
    use SH1106API;

    protected bool $booted = false;

    protected bool $display_on = false;

    protected bool $fill_overlay_on = false;

    protected bool $charge_pump = true;

    /** SH1106 is a 132-column controller; 128px panels are centred, so RAM starts at column 2. */
    protected int $_column_offset = 2;

    /**
     * @throws Exception
     */
    public function __construct(
        protected readonly SH1106DataCarrier $carrier,
        int $width,
        int $height,
        protected int $_offset,
        protected int $_contrast,
        protected int $_start_line,

        protected bool $_map_line_0_to_line_127,
        protected bool $_reverse_line_scan_direction,
        protected SH1106COMPinsHWConfig $_com_pins_config,
        protected bool $_powered_by_host_device,
        protected SH1106VoltageCommonHigh $_v_com_h,
        protected SH1106PumpVoltage $_vpp,
    ) {
        $this->boot(
            $_offset,
            $_contrast,
            $_start_line,
            $_map_line_0_to_line_127,
            $_reverse_line_scan_direction,
            $_com_pins_config,
            $_powered_by_host_device,
            $_v_com_h,
            $_vpp,
            $height
        );
        parent::__construct($width, $height);
    }

    public function display(DumpedBuffer $buffer): void
    {
        // A whole-frame dump leaves width/height unset (fall back to the panel);
        // a partial page strip carries its own window.
        $this->sendData(
            $buffer->origin_x,
            $buffer->origin_y,
            $buffer->width ?? $this->width(),
            $buffer->height ?? $this->height(),
            $buffer->raw_data
        );
    }

    public function __set(string $name, mixed $value): void
    {
        match ($name) {
            'display_on' => $this->setDisplay((bool) $value),
            'offset' => $this->setDisplayOffset((int) $value),
            'contrast' => $this->setContrast((int) $value),
            'start_line' => $this->setDisplayStartLine((int) $value),
            'charge_pump' => $this->setChargePumpRegulator((bool) $value),
            'flip_line_0_and_127' => $this->setSegmentRemap((bool) $value),
            'flip_line_scan_dir' => $this->setCOMOutputScanDirection((bool) $value),
            'com_pins_config' => $this->setCOMPinsHardwareConfiguration($value),
            'powered_by_host_device' => $this->setPrechargePeriod((bool) $value),
            'v_com_h' => $this->setVoltageCommonHigh($value),
            'vpp' => $this->setVPP($value),
            'fill_overlay_on' => $this->setFillOverlay((bool) $value),
            default => throw SH1106Exception::invalidProperty($name)
        };
    }

    public function __get(string $name): mixed
    {
        return match ($name) {
            'display_on' => $this->display_on,
            'offset' => $this->_offset,
            'contrast' => $this->_contrast,
            'start_line' => $this->_start_line,
            'charge_pump' => $this->charge_pump,
            'flip_line_0_and_127' => $this->_map_line_0_to_line_127,
            'flip_line_scan_dir' => $this->_reverse_line_scan_direction,
            'com_pins_config' => $this->_com_pins_config,
            'powered_by_host_device' => $this->_powered_by_host_device,
            'v_com_h' => $this->_v_com_h,
            'vpp' => $this->_vpp,
            'fill_overlay_on' => $this->fill_overlay_on,
            default => throw SH1106Exception::invalidProperty($name)
        };
    }

    /**
     * @throws Exception
     */
    protected function boot(
        int $offset,
        int $contrast,
        int $start_line,
        bool $map_line_0_to_line_127,
        bool $reverse_line_scan_direction,
        SH1106COMPinsHWConfig $com_pins_config,
        bool $powered_by_host_device,
        SH1106VoltageCommonHigh $v_com_h,
        SH1106PumpVoltage $vpp,
        int $height,
    ): void {
        if (! $this->booted) {
            $this->displayOff();
            $this->setDataClockOscillationFrequency(new SH1106DataClock);
            $this->setMultiplexRatio($height - 1);
            $this->setDisplayOffset($offset);
            $this->setDisplayStartLine($start_line);
            $this->setChargePumpRegulator(true);
            $this->setSegmentRemap($map_line_0_to_line_127);
            $this->setCOMOutputScanDirection($reverse_line_scan_direction);
            $this->setCOMPinsHardwareConfiguration($com_pins_config);
            $this->setContrast($contrast);
            $this->setPrechargePeriod($powered_by_host_device);
            $this->setVoltageCommonHigh($v_com_h);
            $this->setVPP($vpp);
            $this->setFillOverlay(false);
            $this->setMemoryMode();

            usleep(100000);
            $this->displayOn();
            $this->booted = true;
        }
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

    /**
     * @throws Exception
     */
    public static function connection(string $driver): SH1106Factory
    {
        return new SH1106Factory(
            I2C::connection($driver),
        );
    }
}
