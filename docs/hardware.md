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

### KioskLite v1.1.0 image

The ready-to-use KioskLite v1.1.0 image has been fully tested on the
Raspberry Pi 2 Model B Rev 1.1.

Testing included:

- Raspberry Pi Imager first-boot customization
- automatic first-boot reboot sequence
- Ethernet networking
- configuration through `bootfs/kiosklite.conf`
- empty and invalid URL handling
- unreachable website handling
- configuration editing from Windows
- fullscreen slideshow operation
- extended continuous operation

The Raspberry Pi 2 Model B Rev 1.1 does **not** include built-in Wi-Fi.
Use Ethernet or a compatible USB Wi-Fi adapter if wireless networking
is required.

Additional tests on 17–18 September 2026 validated fullscreen after boot and
browser restart, and two simultaneous lightweight H.264 640 × 360 videos.
OpenAL required the silent-output workaround `ALSOFT_DRIVERS=null`.
A 1920 × 1080 clip failed; a reduced, Baseline-profile, audio-free version worked.
These tests do not establish general Full HD support or a long-duration video
soak test. The replacement image has not yet been built and validated.

Memory use depends on content and runtime. Earlier image-based tests used approximately 300 MB; do not treat that figure as a budget for simultaneous video playback.

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

Long-running tests showed that Midori may occasionally exit after several days of continuous operation.

The supplied `.xinitrc` therefore automatically restarts Midori after a short delay while keeping Xorg and Openbox running.

## Raspberry Pi 3

**Status: Tested and working**

The same SD card image used on the Raspberry Pi 2 was inserted into a Raspberry Pi 3 and booted successfully without modification.

KioskLite displayed correctly using the same Xorg, Openbox and Midori configuration.

The Raspberry Pi 3 includes built-in Wi-Fi, unlike the Raspberry Pi 2
Model B used for the main KioskLite v1.1.0 validation.

---

## Raspberry Pi Zero

**Status: Image slideshows tested with the September 2026 corrections; video not recommended**

On the original Pi Zero (512 MB RAM), image slideshows worked satisfactorily.
Fullscreen without the address bar was verified after boot and after Midori
restart with the corrected F11 loop. The earlier fullscreen issue is resolved
on the tested device.

Video is not recommended, including lightweight 640 × 360 clips. Playback can
be slow or stall, leaving a blank zone until the slideshow watchdog advances.
Use images and static content instead. These results are not claims about
Pi Zero 2 hardware or a newly released disk image.

Keep the tested device's existing boot and swap configuration. Increased swap
does not establish reliable video playback. See [Media and troubleshooting](media.md).

---

## Recommended Hardware

For a simple KioskLite display, a **Raspberry Pi 2 or newer** is recommended.

KioskLite was specifically designed to avoid requiring recent or powerful Raspberry Pi hardware. Older boards that may no longer be suitable for a full desktop environment can still be useful as dedicated information displays.

For network connectivity:

- Raspberry Pi 2 Model B: Ethernet or compatible USB Wi-Fi adapter
- Raspberry Pi 3 and later Wi-Fi-equipped models: Ethernet or built-in Wi-Fi