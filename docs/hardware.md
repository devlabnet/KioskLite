# Hardware Compatibility

KioskLite is designed to run on older and low-power Raspberry Pi hardware.

The following devices have been tested.

## Raspberry Pi 2 Model B

**Status: Fully tested and working**

Test configuration:

- Raspberry Pi 2 Model B Rev 1.1
- 1 GB RAM
- Raspberry Pi OS Lite (Legacy, 32-bit, Bookworm)
- Xorg
- Openbox
- Midori / WebKitGTK

The kiosk has been tested continuously with image and video slideshows.

Typical memory usage is approximately 300 MB, with more than 600 MB remaining available. No swap usage has been observed during normal operation.

### Graphics driver

On the tested Raspberry Pi 2, the default KMS driver resulted in a black screen.

Changing:

```text
dtoverlay=vc4-kms-v3d
```

to:

```text
dtoverlay=vc4-fkms-v3d
```

resolved the problem.

See [Installation Guide](installation.md) for details.

---

## Raspberry Pi 3

**Status: Tested and working**

The same SD card image used on the Raspberry Pi 2 was inserted into a Raspberry Pi 3 and booted successfully without modification.

KioskLite displayed correctly using the same Xorg, Openbox and Midori configuration.

---

## Raspberry Pi Zero

**Status: Experimental**

The same SD card image can boot and run on a Raspberry Pi Zero, but additional tuning is currently required.

Observed limitations:

- 512 MB RAM is significantly more restrictive than on the Raspberry Pi 2/3.
- Increasing swap allows the slideshow to start successfully.
- Midori currently does not enter fullscreen correctly on the tested configuration and leaves the browser address bar visible.
- Increasing the Openbox startup delay did not resolve the fullscreen issue.

The Raspberry Pi Zero is therefore **not currently recommended for production use**.

Further testing is planned.

---

## Recommended Hardware

For a simple KioskLite display, a **Raspberry Pi 2 or newer** is recommended.

KioskLite was specifically designed to avoid requiring recent or powerful Raspberry Pi hardware. Older boards that may no longer be suitable for a full desktop environment can still be useful as dedicated information displays.