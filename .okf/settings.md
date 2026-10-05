---
type: Reference
title: Panel settings
description: Magic properties for reading and writing SH1106 settings under their configuration key names, the setter methods behind them, and where state lives.
tags: [settings, properties, contrast, invert, pump-voltage]
status: draft
generated: { by: claude-opus/5.5, at: 2026-10-04T21:10:00Z }
sources:
  - id: bootstrap
    resource: src/Concerns/SH1106Bootstrap.php
    title: SH1106Bootstrap __get / __set
  - id: api
    resource: src/Concerns/SH1106API.php
    title: SH1106API
  - id: tests
    resource: tests/SH1106Test.php
    title: panel tests
---

# State

Setters write chip, then configuration. Reads come from configuration only; driver never reads chip.[^api]

# Properties

One name per setting, read and write, = configuration key.[^bootstrap][^tests]

| Property | Setter | Bytes |
|---|---|---|
| `display_on` | `setDisplay` / `displayOn` / `displayOff` | AF / AE |
| `display_offset` | `setDisplayOffset` 0–63 | D3 n |
| `contrast` | `setContrast` 0–255 | 81 n |
| `start_line` | `setDisplayStartLine` 0–63 | 40+n |
| `charge_pump` | `setChargePumpRegulator` (DC-DC converter) | AD 8B / AD 8A |
| `vpp` | `setPumpVoltage` (`SH1106PumpVoltage` 6.4 / 7.4 / 8.0 / 9.0 V) | 30–33 |
| `map_line_0_to_line_127` | `setSegmentRemap` | A1 / A0 |
| `reverse_line_scan_direction` | `setCOMOutputScanDirection` | C8 / C0 |
| `com_pins_config` | `setCOMPinsHardwareConfiguration` (also updates `enable_com_lr_remap`) | DA n |
| `powered_by_host_device` | `setPrechargePeriod` | D9 F1 / D9 22 |
| `v_com_h` | `setVoltageCommonHigh` | DB n |
| `fill_overlay_on` | `setFillOverlay` | A5 / A4 |
| `invert_display` | `setInvertDisplay` | A7 / A6 |

Unknown name → `invalidProperty`. Also: `setMultiplexRatio` 16–63, `setDataClockOscillationFrequency`, `setPagePosition` (adds `column_offset`).

# Related

* [configuration-object](/configuration-object.md) · [drawing](/drawing.md)

[^bootstrap]: SH1106Bootstrap __get / __set
[^api]: SH1106API
[^tests]: panel tests
