# KioskLite

**English** | [Français](README.fr.md)

KioskLite is a lightweight Raspberry Pi web kiosk and digital signage
system designed to run even on older Raspberry Pi hardware.

Flash the image, edit one text file, power on the Raspberry Pi — that's it.

KioskLite can display any HTTP or HTTPS website in fullscreen mode.
It also includes an optional PHP digital signage application with
slideshow management and a browser-based administration interface.

## Quick Start

### 1. Flash the KioskLite image

Use Raspberry Pi Imager to write the KioskLite image to an SD card.

Raspberry Pi Imager customizations can be used to configure the hostname,
username and password, SSH, Wi-Fi credentials on supported hardware,
keyboard layout, country and timezone.

Several automatic reboots may occur during the first startup. This is normal.

### 2. Configure the website

Insert the SD card into a computer and open the `bootfs` partition.

Edit the file:

`kiosklite.conf`

Set the URL you want KioskLite to display:

`URL=https://www.example.com`

The address must begin with `http://` or `https://`.

Save the file and eject the SD card.

### 3. Start KioskLite

Insert the SD card into the Raspberry Pi and power it on.

KioskLite automatically starts its minimal graphical environment and opens
the configured website in Midori fullscreen mode.

No SSH or Linux configuration is required to change the displayed URL.

### Missing or invalid URL

If `kiosklite.conf` is missing, `URL=` is empty, or the address does not
begin with `http://` or `https://`, KioskLite displays a local configuration
page.

If the address is valid but the website cannot be reached because of a
network, DNS or server problem, Midori displays its normal network error page.

### Windows note

After inserting a KioskLite SD card into a Windows PC, Windows may display:

> There's a problem with this drive. Scan the drive now and fix it.

This message can be ignored when accessing the `bootfs` partition to edit
`kiosklite.conf`.

If Windows offers to **format** another partition on the SD card, do not
format it. KioskLite also contains a Linux filesystem that Windows does not
read natively.

## Raspberry Pi Client Features

The KioskLite Raspberry Pi client provides:

- lightweight Raspberry Pi OS Bookworm 32-bit base
- minimal Xorg graphical environment
- Openbox window manager
- Midori fullscreen browser
- automatic browser restart after an exit or crash
- screen blanking and power management disabled
- automatic mouse cursor hiding
- URL configuration from the `bootfs` partition
- configuration editable from Windows
- local configuration page for missing or invalid URLs
- Raspberry Pi Imager first-boot customization support
- volatile systemd journal in the release image

KioskLite can display any HTTP or HTTPS website. The KioskLite web
application is optional.

## Optional Web Application

The repository also contains a lightweight PHP digital signage application
in the `web/` directory.

It provides:

- two independent slideshow zones
- images and videos
- configurable display duration
- media ordering
- enable / disable status
- start and end publication dates
- automatic single-zone expansion when only one side contains active media
- configurable kiosk title
- optional date display
- optional weather display using Open-Meteo
- browser-based administration interface
- media upload and deletion
- English and French localization

No database is required.

## Tested Hardware

KioskLite has been tested on:

- Raspberry Pi 2 Model B — working
- Raspberry Pi 3 — working
- Raspberry Pi Zero — experimental

The KioskLite v1.1.0 ready-to-use image has been validated on a
Raspberry Pi 2 Model B, including a complete first boot using
Raspberry Pi Imager customizations.

Wi-Fi requires a Raspberry Pi with built-in Wi-Fi or a compatible
USB Wi-Fi adapter. Ethernet can be used on models without Wi-Fi
hardware.

For details, see:

[Hardware Compatibility](docs/hardware.md)

## Repository Structure

```text
KioskLite/
├── raspberry/
│   ├── kiosklite/
│   │   ├── config-error.html
│   │   ├── kiosklite.conf
│   │   └── xinitrc
│   └── xinitrc.example
│
├── web/
│   ├── index.php
│   ├── admin.php
│   ├── api.php
│   ├── i18n.php
│   ├── config.local.example.php
│   ├── lang/
│   │   ├── en.php
│   │   └── fr.php
│   └── media/
│       ├── left/
│       │   └── .gitkeep
│       └── right/
│           └── .gitkeep
│
├── docs/
│   ├── installation.md
│   ├── configuration.md
│   ├── hardware.md
│   └── backup-pishrink.md
│
├── CHANGELOG.md
├── README.md
├── README.fr.md
├── LICENSE
├── .gitignore
└── .gitattributes
```

## Installation

The easiest way to install KioskLite is to use the ready-to-use
KioskLite disk image with Raspberry Pi Imager.

After writing the image to an SD card, the displayed website can be
configured simply by editing `kiosklite.conf` on the `bootfs`
partition.

No manual Linux configuration is required when using the release image.

KioskLite can also be installed manually on Raspberry Pi OS Lite
using Xorg, Openbox and Midori.

For manual installation and technical details, see:

[Installation Guide](docs/installation.md)

The optional web application can be deployed on any PHP-capable
web server.

For web server deployment and configuration, see:

[Web Configuration Guide](docs/configuration.md)

## Configuration

### Raspberry Pi client

The website displayed by KioskLite is configured in:

`/boot/firmware/kiosklite.conf`

The same file is directly accessible on the `bootfs` partition when
the SD card is inserted into a Windows PC.

Example:

`URL=https://www.example.com`

Lines beginning with `#` are comments.

The URL must begin with `http://` or `https://`.

Configuration files edited under Windows are supported.

### Optional web application

Local web application configuration files are intentionally excluded
from Git.

The repository includes:

`web/config.local.example.php`

Copy it to:

`web/config.local.php`

and configure your local administrator password.

Runtime configuration and uploaded media are also excluded from
version control.

For detailed web application configuration instructions, see:

[Configuration Guide](docs/configuration.md)

## Languages

KioskLite includes a simple built-in internationalization system.

Currently included languages:

- English
- French

Language files are stored in:

    web/lang/

The language can be selected directly from the administration interface.

The selected language is stored in the user session and in a browser cookie.

Additional languages can be added by creating a new translation file based on:

    web/lang/en.php

Translation keys should remain unchanged; only the translated values need to be modified.

## Media

Runtime media files are stored in:

```text
web/media/left/
web/media/right/
```

Only `.gitkeep` files are tracked by Git.

Images and videos uploaded through the administration interface are therefore not committed accidentally.

## Raspberry Pi Client

The Raspberry Pi starts a minimal X session automatically and runs
Midori in fullscreen mode.

In KioskLite v1.1.0, the browser URL is read from
`/boot/firmware/kiosklite.conf` instead of being hard-coded in
`.xinitrc`.

The supplied X startup configuration automatically restarts Midori
if the browser exits unexpectedly, allowing the kiosk to recover
without restarting the Raspberry Pi.

The exact files used by the v1.1.0 release image are available in:

`raspberry/kiosklite/`

They include:

- `xinitrc`
- `kiosklite.conf`
- `config-error.html`

The older `raspberry/xinitrc.example` file is retained as a simple
reference for manual installations.

## Releases

See [CHANGELOG.md](CHANGELOG.md) for the version history.

### KioskLite v1.1.0

Release date: **2026-09-16**

Image:

`KioskLite-v1.1.0.img`

Image size:

`5830103552 bytes`

SHA-256:

`7f13886c194eea58ecb935a2fba2b3829b58f592ea3342600ee8aa78cf267b37`

Always verify the checksum of a downloaded image before writing it
to an SD card.

## Project Status

KioskLite is under active development.

Version 1.1.0 provides a simple and tested Raspberry Pi web kiosk
client whose URL can be configured directly from the SD card boot
partition.

The PHP digital signage application remains available as an optional
content server.

Future versions may include additional features and alternative
server implementations.

## License

See the [LICENSE](LICENSE) file.