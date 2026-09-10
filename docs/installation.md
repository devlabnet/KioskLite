# KioskLite Installation Guide

This guide describes how to set up a lightweight Raspberry Pi kiosk using Raspberry Pi OS Lite, Xorg, Openbox and Midori.

The configuration below has been tested successfully on:

- Raspberry Pi 2 Model B
- Raspberry Pi 3

A Raspberry Pi Zero can also boot and run the same setup, but currently requires additional tuning. See the hardware notes for details.

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

## 6. Install the KioskLite X startup file

Copy:

```text
raspberry/xinitrc.example
```

to:

```text
~/.xinitrc
```

For example:

```bash
cp raspberry/xinitrc.example ~/.xinitrc
```

Then make it executable:

```bash
chmod +x ~/.xinitrc
```

Edit the file and replace:

```text
https://example.org/kiosk/
```

with the URL of your own KioskLite installation.

The default configuration is:

```sh
#!/bin/sh

xset s off
xset s noblank
xset -dpms

unclutter -idle 1 -root &

openbox-session &

sleep 1

exec midori -e Fullscreen https://example.org/kiosk/
```

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
5. Midori opens the configured KioskLite URL in fullscreen mode

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

## 10. Optional: disable Wi-Fi when Ethernet is used

If the kiosk uses Ethernet permanently, Wi-Fi can be disabled separately if desired.

This is optional and is not required for KioskLite itself.

## 11. Daily reboot

A scheduled daily reboot is not required by KioskLite.

Initial testing used a daily reboot at 04:00, but later tests showed stable memory usage and no swap activity.

For this reason, automatic rebooting is currently not recommended unless a specific installation requires it.