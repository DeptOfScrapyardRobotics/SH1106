# Agent guidelines — dept-of-scrapyard-robotics/sh1106

## Knowledge Bundle (OKF)

This package ships an Open Knowledge Format bundle at [`.okf/`](.okf/) (excluded from the Composer dist via `.gitattributes` `export-ignore`). Before changing code or advising on this package: read [`.okf/index.md`](.okf/index.md) first, open only the concepts the task needs, prefer `status: stable` over `draft`. When you learn something durable, update the affected concept(s), bump `generated.at`, and append [`.okf/log.md`](.okf/log.md); new or changed concepts stay `status: draft` until a human verifies them. The bundle documents the package, never a session.

Do **not** create `.okf` folders under `src/*` — knowledge for this package lives at the package root only. Catalog, transport and adapter semantics belong to `scrapyard-io/framework`'s bundle, framebuffer packing to Surface's; point there, do not restate them here.

## Where this package sits

`ext-posi` / `ext-ftdi` → `microscrap/*` → `scrapyard-io/framework` (protocol managers, transports, the circuit catalog, `DisplayPanel`) → **`dept-of-scrapyard-robotics/sh1106`** (panel driver) → apps and Surface.

## Package rules (quick) — 0.10.x

- Composer: `dept-of-scrapyard-robotics/sh1106` **0.10.0**. PHP `^8.4|^8.5|^8.6`. Namespace `DeptOfScrapyardRobotics\Displays\SH1106\` → `src/`.
- **Requires split components only**: `gpio/contracts`, `gpio/integrated-circuits`, `gpio/nuts-and-bolts`, `venusian-surface/contracts`, `venusian-voyager/nuts-and-bolts`, `venusian-voyager/vessel`. Never `scrapyard-io/framework`, `venusian/surface` or `venusian/framework`. Protocol components and adapters are `suggest`; requires follow imports (and helper functions such as `byte2bits()`).
- **Panel = `Bootable` + `DisplayPanel` + `WindowAddressable` + `Switchable`.** Not `RefreshesOnCommand`: the SH1106 shows on write. `formatSpec()` is `MONO_VERTICAL_PAGE` / `B1` / `LSB_FIRST` / `PageAxis::VERTICAL`. The chip addresses RAM by page only (no addressing modes, no column/page window, no scrolling); `transmit()` places each page with `setPagePosition()` and streams it. Surface 0.10 has no `FormatSpecification` interface; keep the three `formatSpec` methods as plain methods.
- **132 RAM columns, `column_offset` 2.** A 128-pixel glass is centred, so every column write adds `column_offset` (config, default 2). `SH1106Configuration` refuses a height outside 16–64 in whole pages and a row past column 131.
- **Factories are the config shape.** `ConjuresSH1106` gives `i2c()` and `spi()`, whose parameters are exactly a `circuits.sh1106.configs.*` entry's keys; `panel` = `SH1106Configuration` ctor args by name through `fromArray()`. The provider catalogs `sh1106` (`SH1106CatalogIc`). A new config key = a new factory parameter, and the reverse. Bus first, DC and RST after.
- **SPI is mode 0 or 3, ≤ 4 MHz** (250 ns clock cycle). `spi()` opens an unconnected bus in `SPIMode::MODE_0`, clocks its chip select through the transport's `speed()`, refuses a shared bus in mode 1 or 2. Limits live in `SH1106SPIClock`.
- **Boot is the 0.4 sequence** less its `20 10` (an SSD1306 command the SH1106 lacks): `AE`, `D5 80`, `A8`, `D3`, `40+`, `AD 8B` (DC-DC on), `A0|A1`, `C0|C8`, `DA`, `81`, `D9`, `DB`, `30–33` (pump), `A4`, `A6|A7`, 100 ms DC-DC settle, `AF`.
- **Every write is checked.** Transports compare each bus write's result with the bytes sent and throw `writeFailed`; never return or swallow `-1`. I2C packets ≤ 8191 bytes (gpio/i2c's 8192-byte message less the control byte).
- **One name per setting.** Magic properties read and write under the `SH1106Configuration` key; setters write the chip, then the configuration; reads never touch the chip. A new setting gets its key, its setter, its `__get` / `__set` arm and a row in the property dataset test.
- **Register breakouts** are `readonly` `DataRegister`s (`toBits` / `fromByte` / `none`), named for what the datasheet bit does.
- **Reach the framework through the container.** 0.10 has no protocol aliases or facades. Factories resolve `gpio.i2c` / `gpio.spi` / `gpio.digital` from `ControlPanel::getInstance()`; apps call `app('circuit')->conjure()`.
- **Config** merges under `circuits.sh1106`; publish tag `sh1106-config` → `config/circuits/sh1106.php`. Package defaults are `driver => 'none'`.
- **Exceptions** descend from `GeneralPurposeIO\Contracts\IntegratedCircuits\CircuitException` → `GPIOLevelException`.
- Enums int-backed, FULLY UPPERCASE cases. No class constants. `is_null($x)` over `$x === null`.

## Verification

```bash
vendor/bin/pest            # recording fake buses and pins; no hardware
```

Run it under NTS and ZTS PHP before every commit. Suites stay hardware-free: panels are for scratch smoke scripts, never committed and never in `tests/`. `.okf/runbooks/hardware-smoke.md` has the script's shape.

Hardware truth: a 128×64 SH1106 at `0x3C` on an FT232H's I2C (driver `usb`, device `ft232h`). The SPI path is proven by the suite only. A frame is proven only when someone watched the panel show it.
