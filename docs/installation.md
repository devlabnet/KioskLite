# KioskLite Installation Guide

This guide describes two ways to install the KioskLite Raspberry Pi client:

1. **Ready-to-use KioskLite image** — recommended for most users.
2. **Manual installation** — useful for development, customization or installation on an existing Raspberry Pi OS Lite system.

KioskLite uses a minimal graphical environment based on Xorg, Openbox and
Midori instead of a full Raspberry Pi desktop.

The KioskLite client has been tested successfully on:

- Raspberry Pi 2 Model B
- Raspberry Pi 3

A Raspberry Pi Zero can also boot and run the same basic setup, but
has now been tested with corrected fullscreen handling for image slideshows.
Video is not recommended on Pi Zero. See [hardware notes](hardware.md).

## Recommended Installation — KioskLite Image

### 1. Write the image

Use Raspberry Pi Imager to write the KioskLite image to an SD card.

The current release image is:

`KioskLite-v1.1.0.img`

Raspberry Pi Imager customizations can be used to configure:

- hostname
- username and password
- SSH access
- Wi-Fi credentials on supported hardware
- keyboard layout
- country
- timezone

### 2. First boot

Insert the SD card into the Raspberry Pi and power it on.

During the first startup, Raspberry Pi Imager customizations are applied
automatically.

Several automatic reboots may occur. This is normal.

When first-boot configuration is complete, KioskLite starts automatically.

If no valid website has been configured yet, a local KioskLite
configuration page is displayed.

### 3. Configure the displayed website

Power off the Raspberry Pi and insert the SD card into a computer.

Open the `bootfs` partition and edit:

`kiosklite.conf`

Set the URL:

`URL=https://www.example.com`

The address must begin with `http://` or `https://`.

Save the file, eject the SD card, insert it into the Raspberry Pi and
power it on.

KioskLite will automatically display the configured website in fullscreen
mode.

No SSH access or Linux configuration is required to change the URL.

### 4. Windows warning

Windows may display the following message after a KioskLite SD card is
inserted:

> There's a problem with this drive. Scan the drive now and fix it.

This message can be ignored when accessing `bootfs` to edit
`kiosklite.conf`.

If Windows offers to **format** another partition on the card, do not
format it. That partition contains the Linux filesystem used by KioskLite.

---

## Manual Installation

The following procedure describes how to build the KioskLite client
manually from Raspberry Pi OS Lite.

## 1. Install Raspberry Pi OS Lite

Install:

**Raspberry Pi OS Lite (Legacy, 32-bit, Bookworm)**

Using Raspberry Pi Imager is recommended.

During imaging, you may configure:

- hostname
- username and password
- Wi-Fi credentials if required
- SSH access

After the first boot, log in locally or through SSH.

## 2. Update the system

Run:

```bash
sudo apt update
sudo apt upgrade -y
```

## 3. Install the required packages

Install the minimal graphical environment and browser:

```bash
sudo apt install --no-install-recommends \
    xserver-xorg \
    xinit \
    x11-xserver-utils \
    unclutter \
    openbox \
    midori \
    xdotool \
    fonts-noto-color-emoji \
    -y
```

The `fonts-noto-color-emoji` package is required for proper rendering of weather icons and other emoji characters.

## 4. Enable console autologin

Run:

```bash
sudo raspi-config
```

Then select:

```text
System Options
→ Boot / Auto Login
→ Console Autologin
```

Exit `raspi-config`.

## 5. Start X automatically on tty1

Edit:

```bash
nano ~/.bash_profile
```

Add the following lines:

```bash
if [ -z "$DISPLAY" ] && [ "$(tty)" = "/dev/tty1" ]; then
    startx
fi
```

This starts the graphical environment only when the user logs in automatically on the local console.

SSH sessions are not affected.

## 6. Install the KioskLite client files

The exact client files used by KioskLite v1.1.0 are provided in:

`raspberry/kiosklite/`

Install the X startup file `raspberry/kiosklite/xinitrc` as:

`/home/kiosk/.xinitrc`

Make it executable with `chmod +x /home/kiosk/.xinitrc`.

Install `raspberry/kiosklite/config-error.html` as:

`/usr/local/share/kiosklite/config-error.html`

The KioskLite URL configuration file is:

`raspberry/kiosklite/kiosklite.conf`

On Raspberry Pi OS Bookworm, install it as:

`/boot/firmware/kiosklite.conf`

Edit the `URL=` line, for example:

`URL=https://www.example.com`

The URL must begin with `http://` or `https://`.

The supplied `.xinitrc` reads this configuration automatically.

If the configuration file is missing, the URL is empty, or the address
does not begin with `http://` or `https://`, KioskLite displays the local
configuration page.

Midori runs inside a restart loop. If the browser exits or crashes,
KioskLite waits briefly and starts it again automatically.

### Corrections for existing v1.1.0 installations

The repository's historical startup file may still use `midori -e Fullscreen`.
Apply the replacement loop in [Media and troubleshooting](media.md) for the
fullscreen and silent-audio corrections validated on Pi 2 and Pi Zero.
The new image is not yet available. Preserve device-specific startup settings,
including any existing delay before `startx` on Pi Zero.
Use the home directory of your configured kiosk user, not necessarily `/home/kiosk`.
The existing `kiosk.conf` sourced by `.xinitrc` must remain available.

## 7. Raspberry Pi 2 display compatibility

On the tested Raspberry Pi 2 Model B, the default KMS graphics driver caused a black screen.

Edit:

```bash
sudo nano /boot/firmware/config.txt
```

If your system uses `/boot/config.txt` instead, edit that file.

Find:

```text
dtoverlay=vc4-kms-v3d
```

and replace it with:

```text
dtoverlay=vc4-fkms-v3d
```

Save the file and reboot.

This step was required on the tested Raspberry Pi 2, but was not required on the Raspberry Pi 3.

## 8. Reboot

Run:

```bash
sudo reboot
```

After reboot:

1. the `kiosk` user logs in automatically
2. `startx` launches Xorg
3. Openbox starts
4. the mouse cursor is hidden
5. Midori reads the URL from `/boot/firmware/kiosklite.conf`, opens it in fullscreen mode and is automatically restarted if it exits

## 9. Basic checks

To check system uptime:

```bash
uptime
```

To check memory usage:

```bash
free -h
```

To inspect the main memory-consuming processes:

```bash
ps aux --sort=-%mem | head
```

On the tested Raspberry Pi 2, KioskLite typically uses only a fraction of the available 1 GB RAM and does not require swap during normal operation.
Long-running tests on the Raspberry Pi 2 have shown stable memory usage over several days of continuous operation.

## 10. Optional: disable Wi-Fi when Ethernet is used

If the kiosk uses Ethernet permanently, Wi-Fi can be disabled separately if desired.

This is optional and is not required for KioskLite itself.

## 11. Daily reboot

A scheduled daily reboot is not required by KioskLite.

Initial testing used a daily reboot at 04:00, but later tests showed stable memory usage and no swap activity.

For this reason, automatic rebooting is currently not recommended unless a specific installation requires it.
The automatic Midori restart mechanism provides recovery from browser exits without requiring a full system reboot.