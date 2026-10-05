<?php

use DeptOfScrapyardRobotics\Displays\SH1106\Breakouts\SH1106COMPinsHWConfig;
use DeptOfScrapyardRobotics\Displays\SH1106\Enums\SH1106PumpVoltage;
use DeptOfScrapyardRobotics\Displays\SH1106\Enums\SH1106VoltageCommonHigh;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106Configuration;
use DeptOfScrapyardRobotics\Displays\SH1106\SH1106Exception;
use DeptOfScrapyardRobotics\Displays\SH1106\Tests\Support\FakeI2CTransport;
use DeptOfScrapyardRobotics\Displays\SH1106\Tests\Support\FakeOutputPin;
use DeptOfScrapyardRobotics\Displays\SH1106\Tests\Support\FakeSPITransport;
use DeptOfScrapyardRobotics\Displays\SH1106\Transports\SH1106I2CTransport;
use DeptOfScrapyardRobotics\Displays\SH1106\Transports\SH1106SPITransport;
use GeneralPurposeIO\Contracts\IntegratedCircuits\CircuitException;
use GeneralPurposeIO\Contracts\IntegratedCircuits\DisplayPanel;
use Surface\Contracts\Framebuffers\BitDepth;
use Surface\Contracts\Framebuffers\BitOrder;
use Surface\Contracts\Framebuffers\FormatSpec;
use Surface\Contracts\Framebuffers\PageAxis;
use Surface\Contracts\Framebuffers\PixelFormat;

/** The datasheet init sequence with this package's defaults, one entry per command. */
function defaultBootCommands(): array
{
    return [
        [0xAE],             // display off
        [0xD5, 0x80],       // clock divide / oscillator
        [0xA8, 0x3F],       // multiplex = height - 1
        [0xD3, 0x00],       // display offset
        [0x40],             // start line 0
        [0xAD, 0x8B],       // DC-DC converter on
        [0xA0],             // segment remap off
        [0xC0],             // COM scan normal
        [0xDA, 0x12],       // COM pins
        [0x81, 0xBF],       // contrast 191
        [0xD9, 0xF1],       // pre-charge
        [0xDB, 0x40],       // VCOMH
        [0x33],             // pump voltage 9.0 V
        [0xA4],             // resume from RAM
        [0xA6],             // normal, not inverted
        [0xAF],             // display on
    ];
}

/** @return array{0: SH1106, 1: FakeI2CTransport} */
function i2cPanel(?SH1106Configuration $config = null, bool $boot = true): array
{
    $bus = new FakeI2CTransport;
    $panel = new SH1106(new SH1106I2CTransport($bus), $config ?? new SH1106Configuration, boot_now: $boot);

    return [$panel, $bus];
}

/** I2C writes with the control byte split off: [control, [bytes]] */
function framed(FakeI2CTransport $bus, int $from = 0): array
{
    return array_map(fn (array $w): array => [$w[0], array_slice($w, 1)], array_slice($bus->writes, $from));
}

it('is a bootable display panel with the configured size', function (): void {
    [$panel] = i2cPanel(new SH1106Configuration(width: 128, height: 32), boot: false);

    expect($panel)->toBeInstanceOf(DisplayPanel::class)
        ->and($panel->hasBooted())->toBeFalse()
        ->and($panel->width())->toBe(128)
        ->and($panel->height())->toBe(32)
        ->and($panel->transport())->toBeInstanceOf(SH1106I2CTransport::class);
});

it('boots over I2C with the datasheet init sequence, every command behind a 0x00 control byte', function (): void {
    [$panel, $bus] = i2cPanel();

    expect($panel->hasBooted())->toBeTrue()
        ->and(array_column(framed($bus), 0))->each->toBe(0x00)
        ->and(array_column(framed($bus), 1))->toBe(defaultBootCommands());
});

it('derives the multiplex ratio and the COM pin layout from the configured height', function (): void {
    [, $short] = i2cPanel(new SH1106Configuration(width: 128, height: 32));
    [, $tall] = i2cPanel(new SH1106Configuration(width: 128, height: 64));

    expect(framed($short)[2][1])->toBe([0xA8, 0x1F])
        ->and(framed($short)[8][1])->toBe([0xDA, 0x02])
        ->and(framed($tall)[8][1])->toBe([0xDA, 0x12]);
});

it('takes an explicit COM pin layout over the height default', function (): void {
    [, $bus] = i2cPanel(new SH1106Configuration(width: 128, height: 32, alternative_com_pins: true));

    expect(framed($bus)[8][1])->toBe([0xDA, 0x12]);
});

it('applies every boot setting from the configuration', function (): void {
    [, $bus] = i2cPanel(new SH1106Configuration(
        contrast: 0x10,
        start_line: 5,
        display_offset: 3,
        invert_display: true,
        enable_com_lr_remap: true,
        powered_by_host_device: false,
        map_line_0_to_line_127: true,
        alternative_com_pins: false,
        reverse_line_scan_direction: true,
        v_com_h: SH1106VoltageCommonHigh::LEVEL_083,
        vpp: SH1106PumpVoltage::PUMP_VOLTAGE_740,
    ));

    $commands = array_column(framed($bus), 1);

    expect($commands)->toContain([0xD3, 0x03], [0x45], [0xA1], [0xC8], [0xDA, 0x22], [0x81, 0x10], [0xD9, 0x22], [0xDB, 0x30], [0x31], [0xA7]);
});

it('exposes a vertical-page FormatSpec, LSB the top row', function (): void {
    [$panel] = i2cPanel();

    $spec = $panel->formatSpec();

    expect($spec)->toBeInstanceOf(FormatSpec::class)
        ->and($spec->pixel_format)->toBe(PixelFormat::MONO_VERTICAL_PAGE)
        ->and($spec->bit_depth)->toBe(BitDepth::B1)
        ->and($spec->bit_order)->toBe(BitOrder::LSB_FIRST)
        ->and($spec->page_axis)->toBe(PageAxis::VERTICAL);
});

it('places and sends each page of a region at its column, two RAM columns in', function (): void {
    [$panel, $bus] = i2cPanel();
    $before = count($bus->writes);

    // 3 columns × 2 pages, page-major: page 2 = [1, 2, 3], page 3 = [4, 5, 6]; column 0x28 lands on RAM column 0x2A
    $panel->transmit(0x28, 16, [1, 2, 3, 4, 5, 6], 3, 16);

    expect(framed($bus, $before))->toBe([
        [0x00, [0x0A]], [0x00, [0x12]], [0x00, [0xB2]],
        [0x40, [1, 2, 3]],
        [0x00, [0x0A]], [0x00, [0x12]], [0x00, [0xB3]],
        [0x40, [4, 5, 6]],
    ]);
});

it('sends a full frame by default as eight placed pages of 128 bytes', function (): void {
    [$panel, $bus] = i2cPanel();
    $before = count($bus->writes);

    $panel->transmit(0, 0, array_fill(0, 128 * 8, 0xFF));

    $frames = framed($bus, $before);
    $pages = array_values(array_filter($frames, fn (array $f): bool => $f[0] === 0x00 && ($f[1][0] & 0xF0) === 0xB0));
    $data = array_values(array_filter($frames, fn (array $f): bool => $f[0] === 0x40));

    expect($pages)->toBe(array_map(fn (int $p): array => [0x00, [0xB0 | $p]], range(0, 7)))
        ->and(array_slice($frames, 0, 2))->toBe([[0x00, [0x02]], [0x00, [0x10]]])
        ->and(array_map(fn (array $f): int => count($f[1]), $data))->toBe(array_fill(0, 8, 128));
});

it('stops at the last page the region covers', function (): void {
    [$panel, $bus] = i2cPanel();
    $before = count($bus->writes);

    $panel->transmit(0, 8, array_fill(0, 3 * 4, 0x01), 3, 8);

    expect(framed($bus, $before))->toBe([
        [0x00, [0x02]], [0x00, [0x10]], [0x00, [0xB1]],
        [0x40, [1, 1, 1]],
    ]);
});

it('honours a configured column offset', function (): void {
    [$panel, $bus] = i2cPanel(new SH1106Configuration(column_offset: 0));
    $before = count($bus->writes);

    $panel->transmit(0x1F, 0, [9], 1, 8);

    expect(framed($bus, $before))->toBe([
        [0x00, [0x0F]], [0x00, [0x11]], [0x00, [0xB0]],
        [0x40, [9]],
    ]);
});

it('refuses a geometry the chip cannot drive', function (array $args, string $message): void {
    expect(fn () => new SH1106Configuration(...$args))->toThrow(SH1106Exception::class, $message);
})->with([
    'too tall' => [['height' => 72], '128×72 is not drivable'],
    'too short' => [['height' => 8], '128×8 is not drivable'],
    'not whole pages' => [['height' => 30], '128×30 is not drivable'],
    'past column 131' => [['column_offset' => 5], 'column_offset 5 puts a 128-pixel row'],
    'negative offset' => [['column_offset' => -1], 'column_offset -1'],
]);

it('splits data into packets of the configured size, behind a 0x40 control byte', function (): void {
    [$panel, $bus] = i2cPanel(new SH1106Configuration(max_packet_size: 4));
    $before = count($bus->writes);

    $panel->transport()->data([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);
    $panel->transport()->data("\x0B\x0C\x0D\x0E\x0F");

    expect(framed($bus, $before))->toBe([
        [0x40, [1, 2, 3, 4]], [0x40, [5, 6, 7, 8]], [0x40, [9, 10]],
        [0x40, [11, 12, 13, 14]], [0x40, [15]],
    ]);
});

it('reads and writes settings through properties and keeps the configuration current', function (): void {
    [$panel, $bus] = i2cPanel();
    $before = count($bus->writes);

    $panel->contrast = 0x20;
    $panel->invert_display = true;
    $panel->display_offset = 7;
    $panel->display_on = false;
    $panel->fill_overlay_on = true;

    expect(array_column(framed($bus, $before), 1))->toBe([[0x81, 0x20], [0xA7], [0xD3, 0x07], [0xAE], [0xA5]])
        ->and($panel->contrast)->toBe(0x20)
        ->and($panel->display_offset)->toBe(7)
        ->and($panel->display_on)->toBeFalse()
        ->and($panel->fill_overlay_on)->toBeTrue()
        ->and($panel->com_pins_config)->toBeInstanceOf(SH1106COMPinsHWConfig::class)
        ->and($panel->config()->get('invert_display'))->toBeTrue();
});

it('refuses out-of-range settings and unknown properties', function (): void {
    [$panel] = i2cPanel();

    expect(fn () => $panel->contrast = 256)->toThrow(SH1106Exception::class, 'Contrast')
        ->and(fn () => $panel->display_offset = 64)->toThrow(SH1106Exception::class, 'Offset')
        ->and(fn () => $panel->nope)->toThrow(SH1106Exception::class, "Invalid property 'nope'")
        ->and(fn () => $panel->nope = 1)->toThrow(SH1106Exception::class, "Invalid property 'nope'");
});

it('the configuration reads and writes its own keys and names an unknown one', function (): void {
    $config = new SH1106Configuration(width: 96);

    $config->set('contrast', 12);

    expect($config->get('width'))->toBe(96)
        ->and($config->get('contrast'))->toBe(12)
        ->and(fn () => $config->get('nope'))->toThrow(SH1106Exception::class, "'nope'")
        ->and(fn () => $config->set('nope', 1))->toThrow(SH1106Exception::class, "'nope'");
});

it('roots its exception at the framework circuit exception', function (): void {
    expect(SH1106Exception::invalidContrast(300))->toBeInstanceOf(CircuitException::class);
});

// --- SPI ---------------------------------------------------------------------------

/** @return array{0: SH1106, 1: FakeSPITransport, 2: FakeOutputPin, 3: FakeOutputPin} */
function spiPanel(): array
{
    $dc = new FakeOutputPin(24);
    $rst = new FakeOutputPin(25);
    $spi = new FakeSPITransport(0);
    $spi->dc = $dc;
    $panel = new SH1106(new SH1106SPITransport($spi, $dc, $rst), new SH1106Configuration, boot_now: true);

    return [$panel, $spi, $dc, $rst];
}

it('boots over SPI: pulses RST, then sends every command with DC low', function (): void {
    [, $spi, , $rst] = spiPanel();

    expect($rst->levels)->toBe([true, false, true])
        ->and(array_column($spi->writes, 0))->each->toBe('cmd')
        ->and(array_column($spi->writes, 1))->toBe(defaultBootCommands());
});

it('sends data over SPI with DC high, in packets', function (): void {
    [$panel, $spi] = spiPanel();
    $before = count($spi->writes);

    $panel->transmit(0, 0, [1, 2, 3], 3, 8);

    expect(array_slice($spi->writes, $before))->toBe([
        ['cmd', [0x02]],
        ['cmd', [0x10]],
        ['cmd', [0xB0]],
        ['data', [1, 2, 3]],
    ]);
});

it('releases DC and RST on close', function (): void {
    [$panel, $spi, $dc, $rst] = spiPanel();

    $panel->close();

    expect($dc->closed())->toBeTrue()
        ->and($rst->closed())->toBeTrue()
        ->and($spi->closed())->toBeFalse();
});

it('leaves the I2C connection to its driver on close', function (): void {
    [$panel, $bus] = i2cPanel();

    $panel->close();

    expect($bus->closed())->toBeFalse();
});

it('is a window-addressable, switchable display panel that does not refresh on command', function (): void {
    expect(is_subclass_of(SH1106::class, DisplayPanel::class))->toBeTrue()
        ->and(is_subclass_of(SH1106::class, \GeneralPurposeIO\Contracts\IntegratedCircuits\WindowAddressable::class))->toBeTrue()
        ->and(is_subclass_of(SH1106::class, \GeneralPurposeIO\Contracts\IntegratedCircuits\Switchable::class))->toBeTrue()
        ->and(is_subclass_of(SH1106::class, \GeneralPurposeIO\Contracts\IntegratedCircuits\RefreshesOnCommand::class))->toBeFalse();
});

it('reads back every setting under the name it was written', function (string $name, mixed $value, array $command): void {
    [$panel, $bus] = i2cPanel();
    $before = count($bus->writes);

    $panel->{$name} = $value;

    expect($panel->{$name})->toEqual($value)
        ->and($panel->config()->get($name))->toEqual($value)
        ->and(array_column(framed($bus, $before), 1))->toBe([$command]);
})->with([
    'display_on' => ['display_on', false, [0xAE]],
    'display_offset' => ['display_offset', 9, [0xD3, 9]],
    'contrast' => ['contrast', 0x7F, [0x81, 0x7F]],
    'start_line' => ['start_line', 12, [0x4C]],
    'charge_pump' => ['charge_pump', false, [0xAD, 0x8A]],
    'map_line_0_to_line_127' => ['map_line_0_to_line_127', true, [0xA1]],
    'reverse_line_scan_direction' => ['reverse_line_scan_direction', true, [0xC8]],
    'com_pins_config' => ['com_pins_config', new SH1106COMPinsHWConfig(true, false), [0xDA, 0x22]],
    'powered_by_host_device' => ['powered_by_host_device', false, [0xD9, 0x22]],
    'v_com_h' => ['v_com_h', SH1106VoltageCommonHigh::LEVEL_065, [0xDB, 0x00]],
    'vpp' => ['vpp', SH1106PumpVoltage::PUMP_VOLTAGE_640, [0x30]],
    'fill_overlay_on' => ['fill_overlay_on', true, [0xA5]],
    'invert_display' => ['invert_display', true, [0xA7]],
]);

it('keeps the COM left/right remap key in step with a written COM pin config', function (): void {
    [$panel] = i2cPanel();

    $panel->com_pins_config = new SH1106COMPinsHWConfig(enable_com_lr_remap: true, alternative_com_pins: true);

    expect($panel->config()->get('enable_com_lr_remap'))->toBeTrue();
});

it('fails boot when the I2C panel does not acknowledge', function (): void {
    $bus = new FakeI2CTransport;
    $bus->nack_from = 0;

    expect(fn () => new SH1106(new SH1106I2CTransport($bus), new SH1106Configuration, boot_now: true))
        ->toThrow(SH1106Exception::class, 'SH1106 command 0xAE write failed: -1 of 2 bytes');
});

it('fails a transmit whose data packet is not acknowledged', function (): void {
    [$panel, $bus] = i2cPanel();
    $bus->nack_from = count($bus->writes) + 3;

    expect(fn () => $panel->transmit(0, 0, [1, 2, 3], 3, 8))
        ->toThrow(SH1106Exception::class, 'SH1106 data write failed: -1 of 4 bytes');
});

it('fails an SPI write the bus could not send', function (): void {
    [$panel, $spi] = spiPanel();
    $spi->answer = -1;

    expect(fn () => $panel->contrast = 1)->toThrow(SH1106Exception::class, 'SH1106 command 0x81 write failed: -1 of 2 bytes')
        ->and(fn () => $panel->transport()->data("\x01\x02"))->toThrow(SH1106Exception::class, 'SH1106 data write failed: -1 of 2 bytes');
});

it('bounds the packet size by what the bus takes in one write', function (): void {
    $spi = new FakeSPITransport(0);
    $pin = new FakeOutputPin(1);

    expect(fn () => new SH1106I2CTransport(new FakeI2CTransport, 8192))->toThrow(SH1106Exception::class, 'max_packet_size 8192 must be at least 1 and at most 8191')
        ->and(fn () => new SH1106I2CTransport(new FakeI2CTransport, 0))->toThrow(SH1106Exception::class, 'max_packet_size 0')
        ->and(fn () => (new SH1106I2CTransport(new FakeI2CTransport))->maxPacketSize(9000))->toThrow(SH1106Exception::class, 'max_packet_size 9000')
        ->and((new SH1106I2CTransport(new FakeI2CTransport, 8191))->maxPacketSize(16))->toBeInstanceOf(SH1106I2CTransport::class)
        ->and(new SH1106SPITransport($spi, $pin, $pin, 65536))->toBeInstanceOf(SH1106SPITransport::class);
});

it('builds a configuration from a config entry\'s panel array and names an unknown key', function (): void {
    $config = SH1106Configuration::fromArray(['width' => 96, 'height' => 16, 'contrast' => 0x20]);

    expect($config->get('width'))->toBe(96)
        ->and($config->get('height'))->toBe(16)
        ->and($config->get('contrast'))->toBe(0x20)
        ->and($config->get('com_pins_config')->alternative_com_pins)->toBeFalse()
        ->and(fn () => SH1106Configuration::fromArray(['widht' => 96]))->toThrow(SH1106Exception::class, "Invalid property 'widht'");
});
