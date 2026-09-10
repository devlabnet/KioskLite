# KioskLite

KioskLite is a lightweight web-based digital signage system designed to run on older Raspberry Pi hardware.

It was created to provide a simple and low-resource information display using:

- Raspberry Pi OS Lite
- Xorg
- Openbox
- Midori / WebKitGTK
- PHP
- a standard web server

The Raspberry Pi only acts as a lightweight display client. The kiosk content, configuration and media are hosted on a web server.

## Features

KioskLite currently supports:

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
- lightweight Raspberry Pi client configuration

## Tested Hardware

KioskLite has been tested on:

- Raspberry Pi 2 Model B — working
- Raspberry Pi 3 — working
- Raspberry Pi Zero — experimental

The Raspberry Pi 2 has been tested continuously for more than 2 days with no swap usage.

For details, see:

[Hardware Compatibility](docs/hardware.md)

## Repository Structure

```text
KioskLite/
├── web/
│   ├── index.php
│   ├── admin.php
│   ├── api.php
│   ├── config.local.example.php
│   └── media/
│       ├── left/
│       │   └── .gitkeep
│       └── right/
│           └── .gitkeep
│
├── raspberry/
│   └── xinitrc.example
│
├── docs/
│   ├── installation.md
│   └── hardware.md
│
├── .gitignore
├── LICENSE
└── README.md
```

## Installation

The Raspberry Pi client uses a minimal graphical environment instead of a full desktop.

See:

[Installation Guide](docs/installation.md)

The web application can be deployed on any PHP-capable web server.

More detailed server installation instructions will be added later.

## Configuration

Local configuration files are intentionally excluded from Git.

The repository includes:

```text
web/config.local.example.php
```

Copy it to:

```text
web/config.local.php
```

and configure your local administrator password.

Runtime configuration and uploaded media are also excluded from version control.

## Media

Runtime media files are stored in:

```text
web/media/left/
web/media/right/
```

Only `.gitkeep` files are tracked by Git.

Images and videos uploaded through the administration interface are therefore not committed accidentally.

## Raspberry Pi Client

The Raspberry Pi starts a minimal X session automatically and launches Midori in fullscreen mode.

An example X startup configuration is provided in:

```text
raspberry/xinitrc.example
```

Replace the example kiosk URL with the URL of your own KioskLite installation.

## Project Status

KioskLite is currently under active development.

The current PHP version is the first public version of the project.

Future versions may include additional features and alternative server implementations.

## License

See the [LICENSE](LICENSE) file.