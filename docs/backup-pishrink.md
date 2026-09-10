# Raspberry Pi SD Card Backup with PiShrink

This guide describes how to create a compact, reusable SD card image from a working KioskLite Raspberry Pi installation.

PiShrink reduces a full raw Raspberry Pi SD card image to approximately the minimum size required by the files stored on it.

On first boot after restoring the image, the root filesystem can automatically expand to use the available space on the new SD card.

## 1. Create a Raw SD Card Image

Shut down the Raspberry Pi cleanly:

```bash
sudo shutdown -h now
```

Remove the SD card and create a complete raw image using your preferred imaging tool.

For example:

```text
kiosk.img
```

A 32 GB SD card will initially produce an image of approximately 30 GB, even if only a few gigabytes are actually used.

## 2. Install WSL on Windows

PiShrink is a Linux shell script.

Windows users can run it conveniently using Windows Subsystem for Linux (WSL2).

Check the installed WSL distributions from PowerShell:

```powershell
wsl -l -v
```

Start the default Linux distribution:

```powershell
wsl
```

The following procedure has been tested with WSL2 and Ubuntu.

## 3. Install PiShrink Dependencies

Inside WSL, update the package list:

```bash
sudo apt update
```

Install the required tools:

```bash
sudo apt install -y wget parted gzip pigz xz-utils udev e2fsprogs
```

Install PiShrink from its official project repository and make the script executable.

For example, after downloading `pishrink.sh`:

```bash
sudo mv pishrink.sh /usr/local/bin/
sudo chmod +x /usr/local/bin/pishrink.sh
```

You should then be able to run:

```bash
pishrink.sh --help
```

## 4. Important WSL Filesystem Note

Do **not** process a large Raspberry Pi image directly from a Windows-mounted drive such as:

```text
/mnt/c/
/mnt/d/
/mnt/g/
```

In the tested KioskLite environment, the Windows drive was exposed to WSL using the `9p` filesystem.

Check the filesystem type with:

```bash
df -T .
```

When working directly under `/mnt/g`, PiShrink did not complete correctly and the generated output became unusable.

The original image was not damaged, but the shrinking operation failed.

For reliable operation, copy the image into the native WSL Linux filesystem first.

For example:

```bash
mkdir -p ~/pishrink-test
cp /mnt/g/kiosk/kiosk.img ~/pishrink-test/
cd ~/pishrink-test
```

Verify:

```bash
df -T .
```

The filesystem should normally be a native Linux filesystem such as:

```text
ext4
```

## 5. Run PiShrink

From the directory containing the copied image:

```bash
sudo pishrink.sh kiosk.img
```

PiShrink will:

1. inspect the partitions
2. check the Linux filesystem
3. shrink the filesystem
4. shrink the root partition
5. truncate the image file
6. prepare the image for filesystem expansion after restoration

On the tested KioskLite installation, the original image was approximately:

```text
30 GB
```

and PiShrink reduced it to approximately:

```text
3.9 GB
```

The Raspberry Pi installation itself used approximately 2.7 GB of storage.

## 6. Copy the Shrunk Image Back to Windows

Once PiShrink has completed successfully, copy the resulting image back to a Windows drive.

For example:

```bash
cp kiosk.img /mnt/g/kiosk/kiosk-shrunk.img
```

The smaller image is much easier to archive, transfer and restore.

## 7. Restore the Image

Use Raspberry Pi Imager or another raw disk imaging tool to write the shrunk image to a new SD card.

The destination SD card can be larger than the original filesystem.

For example, the KioskLite test image created from a 32 GB card was successfully restored to a 64 GB card.

## 8. First Boot

Insert the restored SD card into the Raspberry Pi and boot normally.

On the tested installation, the root filesystem expanded automatically during the first boot.

After booting, check the filesystem size:

```bash
df -h /
```

For the 64 GB test card, the restored system reported approximately:

```text
/dev/mmcblk0p2   57G   2.7G   52G   5%   /
```

You can also inspect the partition layout with:

```bash
lsblk
```

Example:

```text
mmcblk0       58G
├─mmcblk0p1  512M  /boot/firmware
└─mmcblk0p2 57.5G  /
```

This confirms that the root partition expanded successfully.

## 9. Verify KioskLite

After restoring an image, verify that:

- the Raspberry Pi boots correctly
- console autologin works
- Xorg starts automatically
- Midori starts in fullscreen mode
- the KioskLite page loads
- network connectivity works
- media playback works

You can also check memory and swap usage:

```bash
free -h
```

and system uptime:

```bash
uptime
```

## 10. Optional Image Compression

The shrunk `.img` file can be compressed further for storage or distribution.

For example, using XZ:

```bash
xz -T0 kiosk.img
```

This creates:

```text
kiosk.img.xz
```

Keep the uncompressed image until you have verified that the restored SD card boots correctly.

## 11. GitHub Releases

Raw Raspberry Pi images should **not** be committed directly to the Git repository.

If a preconfigured KioskLite Raspberry Pi image is provided in the future, it should be distributed separately, for example as a GitHub Release asset.

Source code and documentation should remain in the normal Git repository.

## 12. Important Security Note

A cloned SD card contains more than the KioskLite software.

Depending on the original Raspberry Pi configuration, it may also contain:

- Wi-Fi credentials
- SSH configuration and host keys
- user accounts
- password hashes
- network configuration
- shell history
- other machine-specific information

Do not publish an SD card image created directly from a production system without reviewing and sanitizing it first.

For public distribution, create a dedicated clean master image that contains no private credentials or organization-specific configuration.