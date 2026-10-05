# Security Policy

## Supported versions

sh1106 is pre-1.0. No 0.x release receives security fixes or advisories; fixes land in the
next release line. Security support starts with 1.0.

| Version | Security fixes |
|---------|----------------|
| < 1.0   | No             |

## Reporting a vulnerability

Please don't open a public issue for a security problem.

Report it privately through GitHub: the **Report a vulnerability** button on this repository's
**Security** tab. If that isn't available, email **info@projectsaturnstudios.com**.

Include what you found, the affected version, the adapter and hardware in use, and steps to
reproduce. Reports are read and weighed for the release line in development; before 1.0 there is
no response-time commitment.

## Security model

sh1106 is plain PHP. It writes SH1106 commands and display data through the bus and pin
transports `scrapyard-io/framework` hands it, and holds no native code of its own. What a PHP
process may touch is decided below it: the adapter (`microscrap/scrapyard-linux` over ext-posi,
`microscrap/scrapyard-usb` over ext-ftdi) and the operating system's permissions on the I2C, SPI,
GPIO or USB device. Grant those through device groups or udev rules scoped to the hardware, not by
running PHP as root.

- **Configuration is trusted input.** `conjure()` connects whatever bus, chip select and DC / RST
  pins the `circuits.sh1106` config names, and drives those pins as outputs. Keep that config under
  the app's control: a wrong pin number drives another device's line.
- **Writes are checked.** Settings outside the chip's ranges throw before anything is written, the
  SPI factory refuses clocks above the chip's 4 MHz, and a refused or short bus write throws
  instead of being ignored.
- **Frame bytes are not validated.** `transmit()` sends the bytes it is given; the panel shows them.

A report is in scope when this package writes a command or a bus frame it was not asked to, drives
a pin other than its configured DC and RST, or lets a well-formed call leave the chip in a state
its settings do not describe. Weaknesses in an adapter or extension belong to that package's own
policy.
