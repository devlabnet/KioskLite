# Media preparation and troubleshooting

These notes describe tests on 17–18 September 2026. The corrected devices and
web application were tested; a replacement release image is still pending.

## Hardware limits observed

| Device | Tested outcome |
| --- | --- |
| Pi 2 Model B | Two lightweight H.264 640 × 360 videos played simultaneously. |
| Original Pi Zero | Images worked satisfactorily; video is not recommended, even at 640 × 360. |

A Full HD H.264 High 4.0 file (1920 × 1080, 29.97 fps, approximately
3.45 Mbit/s video) failed in the tested setup. Its reduced-resolution,
Baseline-profile, audio-free copy worked. Multiple parameters changed, so this
does not prove that resolution alone caused the failure.

## Prepare a lightweight silent video

Run FFmpeg on a machine containing the source file. Conversion on an older Pi
may take some time. For a 16:9 input, the command tested successfully was:

```bash
ffmpeg -i "input.mp4" \
  -vf "scale=640:360" \
  -c:v libx264 -profile:v baseline -level:v 3.0 \
  -pix_fmt yuv420p -crf 23 -preset fast \
  -an -movflags +faststart \
  "video-640x360.mp4"
```

This creates a separate output, removes audio and preserves the input frame
rate. The tested inputs were around 30 fps. For other aspect ratios, use
`scale=640:360:force_original_aspect_ratio=decrease:force_divisible_by=2`
instead to avoid stretching. Upload the output through the admin interface.
Keep the original separately; avoid overwriting it.

## Fullscreen and silent audio on existing clients

Run as the user whose graphical session starts the kiosk. Install `xdotool`
if missing:

```bash
sudo apt install xdotool
```

Back up `~/.xinitrc`. Keep its screen settings, Openbox startup, `kiosk.conf`
loading and URL configuration. Replace only the final Midori restart loop:

```sh
while true
do
    ALSOFT_DRIVERS=null midori "$KIOSK_URL" \
        > /tmp/midori-debug.log 2>&1 &

    MIDORI_PID=$!
    ATTEMPTS=0

    while kill -0 "$MIDORI_PID" 2>/dev/null &&
          [ "$ATTEMPTS" -lt 60 ]
    do
        WINDOW=$(xdotool search --onlyvisible \
            --class '[Mm]idori' 2>/dev/null | head -n 1)
        ACTIVE=$(xdotool getactivewindow 2>/dev/null)

        if [ -n "$WINDOW" ] &&
           [ -n "$ACTIVE" ] &&
           [ "$ACTIVE" != "0" ]; then
            sleep 2
            if timeout 5 xdotool windowactivate --sync "$WINDOW"
            then
                xdotool key --clearmodifiers F11
            fi
            break
        fi

        sleep 1
        ATTEMPTS=$((ATTEMPTS + 1))
    done

    wait "$MIDORI_PID"
    sleep 5
done
```

Do not retain `-e Fullscreen` with this loop. It targets the visible Midori
window and sends F11 after activation. This assumes one kiosk Midori window.
The polling period is bounded to 60 attempts; a failed activation does not
guarantee fullscreen and should be investigated rather than repeatedly toggling F11.

Reboot to load the edited `.xinitrc`. Killing Midori alone does not reload the
script already running. After reboot, verify fullscreen, then run
`pkill -x midori` from SSH and verify automatic fullscreen recovery again.
This procedure was validated on Pi 2 and Pi Zero.

`ALSOFT_DRIVERS=null` selects a silent OpenAL output. In the tested setup,
OpenAL failed to prepare an audio device and aborted video playback, even with
`video.muted = true`. This workaround allowed playback but does not provide
audible output. Sound requires separate audio configuration and testing.

## Slideshow watchdog

In the updated web application, the watchdog observes playback position:

- Up to 20 seconds for initial progress.
- Up to 15 seconds without progress after playback begins.
- A further 3 seconds before moving on after failure, error or rejected playback.
- Normal end of video advances immediately; progressing videos are not cut off
  by the image-duration setting.

Checks occur roughly once per second. They cannot recover a frozen browser
event loop, and advancing playback time does not guarantee that frames are
visibly rendering. The watchdog is a fallback, not a performance improvement.
Blank periods of about 23 or 18 seconds are possible. Disable unsuitable
videos in the admin interface, especially on Pi Zero.

At each slide change the old player is paused, its source is removed and its
load is reset. Old timers and callbacks cannot advance the replacement slide.
Failed items remain in the list and can be retried in subsequent cycles.

## Diagnose playback failures

First distinguish failure of the entire device from one slideshow zone waiting
for a video. Check SSH access, memory and CPU over several samples. One CPU
snapshot is not enough to establish sustained saturation.

The browser's `MEDIA_ERR_SRC_NOT_SUPPORTED` (code 4) did not identify the root
cause in these tests. The message `Plug-in handled load` alone was also not a
reliable indication of a missing codec.

For temporary GStreamer logs, add `GST_DEBUG=3` alongside `ALSOFT_DRIVERS=null`
in the startup command above and reboot. Reproduce the issue, then read:

```bash
tail -n 150 /tmp/midori-debug.log
```

Capture the log before restarting Midori: the next launch overwrites it.
Remove `GST_DEBUG=3` after diagnosis. Relevant errors in the tested audio
failure were `Unable to prepare device` and `ALC error: Invalid Device`.

To isolate browser integration, diagnostic tools can be installed with
`sudo apt install gstreamer1.0-tools`. With the media saved as `/tmp/v0.mp4`:

```bash
gst-inspect-1.0 avdec_h264
gst-inspect-1.0 avdec_aac
gst-inspect-1.0 qtdemux
gst-launch-1.0 filesrc location=/tmp/v0.mp4 ! qtdemux ! h264parse ! avdec_h264 ! fakesink
gst-launch-1.0 playbin uri=file:///tmp/v0.mp4 video-sink=fakesink audio-sink=fakesink
DISPLAY=:0 gst-launch-1.0 playbin uri=file:///tmp/v0.mp4 video-sink=ximagesink audio-sink=fakesink
```

The first two playback tests discard output and can finish faster than the
clip duration. The last displays video without sound in the kiosk user's X
session. Successful playback with fake audio does not validate real audio output.
