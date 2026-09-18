# KioskLite Web Configuration

This guide describes how to deploy and configure the optional PHP web application included with KioskLite.

The web application contains both the public kiosk display and the administration interface.

## 1. Requirements

KioskLite requires a web server with PHP support.

A typical installation can use:

- Apache or another PHP-capable web server
- PHP
- HTTPS is recommended for Internet-accessible installations

No database is required.

KioskLite stores its configuration in local files and stores uploaded images and videos directly in the `media` directories.

## 2. Upload the Web Application

Copy the contents of the `web/` directory to the directory that will be served by your web server.

The resulting structure should look like:

```text
index.php
admin.php
api.php
i18n.php
config.local.example.php
lang/
├── en.php
└── fr.php
media/
├── left/
└── right/
```

For example, if KioskLite is installed at:

```text
https://example.org/kiosk/
```

the public display will be available at that URL.

The administration interface will be available at:

```text
https://example.org/kiosk/admin.php
```

Include `i18n.php` and the `lang/` directory when deploying or updating the
application. A missing `i18n.php` caused an HTTP 500 during testing because
`index.php` requires it. Consult the server PHP error log for HTTP 500 errors;
browser JavaScript diagnostics do not identify a PHP startup failure.
Preserve local configuration and uploaded media when updating application files.

## 3. Create the Local Configuration

Copy:

```text
config.local.example.php
```

to:

```text
config.local.php
```

`config.local.php` contains installation-specific information and must not be committed to Git.

It is excluded by the supplied `.gitignore`.

## 4. Configure the Administrator Password

KioskLite does not store the administrator password in plain text.

Generate a PHP password hash using `password_hash()`.

For example, create a temporary PHP file containing:

```php
<?php
echo password_hash('YOUR_PASSWORD', PASSWORD_DEFAULT);
```

Open the file through your web server and copy the generated hash.

Then edit `config.local.php`:

```php
<?php

return [
    'admin_password_hash' =>
        'PUT_YOUR_PASSWORD_HASH_HERE'
];
```

Replace:

```text
PUT_YOUR_PASSWORD_HASH_HERE
```

with the generated password hash.

For example, the resulting file will have the following structure:

```php
<?php

return [
    'admin_password_hash' =>
        '$2y$...'
];
```

The actual hash will be much longer.

Delete the temporary password-generation PHP file immediately after generating the hash.

Do not publish or commit your real `config.local.php`.

## 5. Media Directories

KioskLite uses two media directories:

```text
media/left/
media/right/
```

The PHP process must have permission to create and delete files in these directories.

The administration interface uses these directories to:

- upload media
- delete media
- manage the two display zones

Images and videos stored in these directories are runtime content and should not be committed to Git.

## 6. Runtime Configuration

KioskLite creates and maintains:

```text
config.json
```

This file contains runtime settings and media configuration.

It is intentionally excluded from Git because every KioskLite installation can have its own configuration.

The API automatically synchronizes the configuration with the media files found in the `media` directories.

This means that media files copied directly to the directories can also be detected by KioskLite.

## 7. Administration Interface

Open:

```text
https://example.org/kiosk/admin.php
```

and log in using the administrator password configured previously.

The administration interface can be used to manage:

- kiosk title
- date display
- weather display
- weather location
- media uploads
- media deletion
- media ordering
- image display duration
- active / inactive status
- publication start date
- publication end date

Each display zone is managed independently.

## 8. Language Configuration

KioskLite includes a lightweight internationalization system.

English and French are currently included.

Language files are stored in:

    lang/en.php
    lang/fr.php

The language can be selected directly from the administration interface.

The selected language is stored in the user session and in a browser cookie.

The selected language is also used for the kiosk display, including date formatting and weather information.

Additional languages can be added by creating a new translation file based on:

    lang/en.php

Keep the translation keys unchanged and translate only their values.

The new language must also be added to the language selector and to the supported languages in `i18n.php`.

## 9. Media Scheduling

Each media item can optionally have:

- an active / inactive state
- a start date
- an end date

KioskLite automatically determines whether the media should currently be displayed.

The administration interface indicates the current state of each item, such as:

```text
DISPLAYED
UPCOMING
EXPIRED
DISABLED
```

If only one display zone contains active media, KioskLite automatically expands that zone to use the available display area.

### Video behavior in the tested update

The configured duration applies to images. Videos that progress normally play
to their natural end. The updated `Slideshow` class allows 20 seconds for initial
progress and 15 seconds without progress after playback starts. Failure is
followed by a 3-second delay before advancing, so a blank area can last about
23 seconds at startup or 18 seconds after a stall. Checks run about once per
second and require a responsive browser JavaScript event loop.

Playback errors and rejected `play()` calls also advance after 3 seconds.
Changing slides releases the previous video and cancels timers; stale callbacks
cannot advance a new slide. Failed media are not permanently disabled and may
be retried on the next cycle. With only one active video, the same item is retried.
Disable an unsuitable file in the admin interface to avoid recurring blank areas.

Use images on Pi Zero. See [Media preparation and troubleshooting](media.md).

## 10. Weather Configuration

KioskLite can display a weather forecast using Open-Meteo.

The administration interface allows you to configure:

- whether weather information is displayed
- location name
- latitude
- longitude

Internet access is required for weather updates.

The kiosk itself can continue displaying locally hosted media if the weather service is temporarily unavailable.
Weather forecast dates are calculated using the timezone configured in the General Display settings.

## 11. Date and Time

The date and time display can be enabled or disabled from the administration interface.

The installation timezone can also be configured from the administration interface.

Use a valid PHP timezone identifier, for example:

    Europe/Paris
    Europe/London
    America/New_York
    Asia/Tokyo
    UTC

The configured timezone is used for media scheduling, the kiosk clock and weather data.

The Raspberry Pi should still have a correctly synchronized system clock.

## 12. Security Recommendations

For installations accessible from the Internet:

- use HTTPS
- choose a strong administrator password
- never commit `config.local.php`
- do not expose temporary diagnostic or password-generation PHP files
- keep PHP and the web server updated
- restrict filesystem permissions to what KioskLite actually requires

The supplied `.gitignore` prevents local configuration, runtime configuration and uploaded media from being committed accidentally.

## 13. Connecting the Raspberry Pi

Once the web application is working, configure the KioskLite Raspberry Pi
client to open the public kiosk URL.

With KioskLite v1.1.0, the displayed URL is configured in:

`/boot/firmware/kiosklite.conf`

For example:

`URL=https://example.org/kiosk/`

The configuration file is also accessible from the `bootfs` partition,
allowing the URL to be changed from a Windows PC without SSH or Linux access.

The Raspberry Pi client automatically reads this file and opens the
configured URL in Midori fullscreen mode.

See the [Installation Guide](installation.md) for the complete Raspberry Pi
setup.

## 14. Backup

The important installation-specific data is:

```text
config.local.php
config.json
media/
```

These files are deliberately not stored in the Git repository.

Back them up separately if you need to preserve an existing KioskLite installation.

Raspberry Pi SD card backup and cloning are covered separately in the project documentation.