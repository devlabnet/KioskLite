# Changelog

All notable changes to KioskLite are documented in this file.

## [Unreleased]

Validated on running devices and the web application on 17–18 September 2026.
The replacement disk image still needs to be built and tested. The historical
client startup file must be updated using the documented loop before image creation.

### Fixed and tested

- Fullscreen without address bar on Pi 2 and Pi Zero, after boot and browser restart.
- OpenAL audio-device startup failure bypassed with `ALSOFT_DRIVERS=null` (silent output).
- Web slideshow recovery from video startup failures, rejected playback and stalls.
- Player cleanup and cancellation of obsolete slide callbacks and timers.

### Documented

- Two simultaneous lightweight H.264 640 × 360 clips worked on the tested Pi 2.
- Pi Zero image slideshows worked; video is not recommended.
- Video conversion, recovery delays, PHP deployment completeness and image checks.

## [1.1.0] - 2026-09-16

### Added

- Ready-to-use Raspberry Pi disk image.
- `kiosklite.conf` on the boot partition for easy URL configuration.
- URL configuration directly from Windows without SSH or Linux access.
- Local configuration page displayed when no valid URL is configured.
- Support for configuration files edited with Windows line endings.
- Raspberry Pi Imager first-boot customization support.

### Changed

- The kiosk URL is no longer hard-coded in `.xinitrc`.
- KioskLite can display any HTTP or HTTPS website.
- Midori automatically restarts if it exits or crashes.

### Fixed

- Raspberry Pi Imager first-boot handling on Bookworm systems using `/boot/firmware`.
- Initramfs first-boot processing and reboot sequence.

### Tested

- Raspberry Pi 2 Model B.
- Raspberry Pi Imager customization.
- Empty, missing and invalid URL configuration.
- Valid HTTP/HTTPS configuration.
- DNS/network failure behavior.
- Configuration editing from Windows.
- Real-world slideshow display.

## [1.0.3] - 2026-09-16

### Fixed

- Raspberry Pi Imager first-boot compatibility.
- `/boot` to `/boot/firmware` handling during image customization.

## [1.0.0] - 2026-09-11

### Added

- Initial public KioskLite web application.
- Two-zone slideshow.
- Image and video support.
- Browser-based administration.
- Media scheduling.
- Weather and date display.
- English and French localization.
- Lightweight Raspberry Pi kiosk client.
