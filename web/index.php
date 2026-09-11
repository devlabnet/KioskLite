<?php
require_once 'i18n.php'; 

?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($locale) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Kiosk</title>

<style>
html, body {
	margin: 0;
	padding: 0;
	width: 100%;
	height: 100%;
	overflow: hidden;
	background: #fff;
	font-family: Arial, Helvetica, sans-serif;
}

body {
	display: flex;
	flex-direction: column;
	box-sizing: border-box;
}

/* ----------------------------------------
   Top Banner
----------------------------------------- */
.banner {
	flex: 0 0 auto;
	margin: 5px 5px 2px 5px;
	padding: 8px;
	background: #1c87c9;
	color: white;
	border-radius: 10px;
	text-align: center;
	font-size: 30px;
	font-weight: bold;
}

/* ----------------------------------------
   Date / weather
----------------------------------------- */
.info-bar {
	flex: 0 0 105px;
	display: flex;
	align-items: center;
	margin: 2px 5px;
	border: 3px solid lightblue;
	border-radius: 10px;
}

.clock {
	width: 50%;
	text-align: center;
	font-family: "Arial Black", Arial, Helvetica, sans-serif;
	font-size: 30px;
	color: #444;
}

.weather {
	width: 50%;
	text-align: center;
	font-size: 26px;
	font-weight: bold;
	color: #444;
}

/* ----------------------------------------
   Zone slideshows
----------------------------------------- */
.slides-container {
	flex: 1;
	display: flex;
	min-height: 0;
	margin: 2px 5px 5px 5px;
	gap: 4px;
}

/* One slideshow zone */
.slideshow {
	flex: 1;
	min-width: 0;
	min-height: 0;
	display: flex;
	justify-content: center;
	align-items: center;
	overflow: hidden;
	border: 3px solid lightblue;
	border-radius: 10px;
	background: white;
}

/* Image / vidéo */
.slideshow img, .slideshow video {
	display: block;
	max-width: 100%;
	max-height: 100%;
	width: auto;
	height: auto;
	object-fit: contain;
}

/* If one zone is empty */
.empty {
	font-size: 24px;
	color: #aaa;
}

/* Small image transition */
.slide-image {
	animation: fadein 0.5s;
}

@
keyframes fadein {from { opacity:0;
	
}

to {
	opacity: 1;
}
}
</style>

</head>

<body>

	<div id="banner" class="banner">
	    Kiosk
	</div>
	<div class="info-bar">

		<div id="clock" class="clock"></div>

		<div id="weather" class="weather"><?php echo __('loading_weather'); ?>...</div>
	</div>

	<div class="slides-container">

		<div id="left" class="slideshow"></div>

		<div id="right" class="slideshow"></div>

	</div>


	<script>

/* ============================================================
   Configuration
============================================================ */

const API_REFRESH    = 30000;      // check every 30 seconds
const LOCALE =
    <?= json_encode(
        $locale === 'fr'
            ? 'fr-FR'
            : 'en-GB'
    ) ?>;

const I18N = {
    kiosk: <?= json_encode(__('kiosk')) ?>,
    noMedia: <?= json_encode(__('no_media')) ?>,
    weatherUnavailable: <?= json_encode(__('weather_unavailable')) ?>
};

let currentVersion = null;

let weatherSettings = {
    name: "",
    lat: null,
    lon: null,
    timezone: "UTC"
};
/* ============================================================
   Horloge
============================================================ */

function updateClock()
{
    const now = new Date();

    const timezone =
        weatherSettings.timezone || "UTC";

    const date = now.toLocaleDateString(
        LOCALE,
        {
            weekday: "long",
            day: "numeric",
            month: "long",
            year: "numeric",
            timeZone: timezone
        }
    );

    const time = now.toLocaleTimeString(
        LOCALE,
        {
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
            timeZone: timezone
        }
    );

    document.getElementById("clock").innerHTML =
        date + "<br>" + time;
}

updateClock();

setInterval(
    updateClock,
    1000
);

function updateLayout(leftItems, rightItems)
{
    const container =
        document.querySelector(".slides-container");

    const left =
        document.getElementById("left");

    const right =
        document.getElementById("right");


    const hasLeft =
        leftItems.length > 0;

    const hasRight =
        rightItems.length > 0;


    if (hasLeft && hasRight) {

        left.style.display = "flex";
        right.style.display = "flex";

        left.style.flex = "1";
        right.style.flex = "1";

        return;
    }


    if (hasLeft) {

        left.style.display = "flex";
        right.style.display = "none";

        left.style.flex = "1";

        return;
    }


    if (hasRight) {

        left.style.display = "none";
        right.style.display = "flex";

        right.style.flex = "1";

        return;
    }


    /*
     * No active media
     */

    left.style.display = "flex";
    right.style.display = "none";

    left.style.flex = "1";
}

function updateInfoBar(settings)
{
    settings = settings || {};

    const banner =
        document.getElementById("banner");

    banner.textContent =
        settings.kiosk_title || I18N.kiosk;
    
    const showClock =
        settings.show_clock !== false;

    const showWeather =
        settings.show_weather !== false;

    const infoBar =
        document.querySelector(".info-bar");

    const clock =
        document.getElementById("clock");

    const weather =
        document.getElementById("weather");


    clock.style.display =
        showClock ? "block" : "none";

    weather.style.display =
        showWeather ? "block" : "none";

	weatherSettings = {
		name: settings.weather_name || "",
		lat: settings.weather_lat,
		lon: settings.weather_lon,
		timezone: settings.timezone || "UTC"
	};
    /*
     * Both are disabled: remove the bar completely.
     */
    if (!showClock && !showWeather) {

        infoBar.style.display = "none";

        return;
    }


    infoBar.style.display = "flex";


    /*
     * Only one visible item: it takes the full width.
     */
    if (showClock && !showWeather) {

        clock.style.width = "100%";

    } else {

        clock.style.width = "50%";
    }


    if (showWeather && !showClock) {

        weather.style.width = "100%";

    } else {

        weather.style.width = "50%";
    }

    if (showWeather) {
        updateWeather();
    }
}

/* ============================================================
   Slideshow handling
============================================================ */

class Slideshow
{
    constructor(elementId)
    {
        this.element =
            document.getElementById(elementId);

        this.items = [];

        this.index = 0;

        this.timer = null;
    }


    setItems(items)
    {
        /*
         *  Avoid restarting the slideshow if its media list has not changed.
         */

        const oldList =
            JSON.stringify(this.items);

        const newList =
            JSON.stringify(items);

        if (oldList === newList) {
            return;
        }

        this.items = items;

        this.index = 0;

        this.show();
    }


    show()
    {
        if (this.timer !== null) {

            clearTimeout(this.timer);

            this.timer = null;
        }

        this.element.innerHTML = "";


        /* No media */

        if (this.items.length === 0)
        {
            const empty =
                document.createElement("div");

            empty.className = "empty";

            empty.textContent =
                I18N.noMedia;

            this.element.appendChild(empty);

            return;
        }


        /* Index safety check */

        if (this.index >= this.items.length) {
            this.index = 0;
        }


        const item =
            this.items[this.index];


        /* ----------------------------------------
           Video
        ----------------------------------------- */

        if (item.type === "video")
        {
            const video =
                document.createElement("video");

            video.src = item.file;

            video.autoplay = true;
            video.muted = true;

            video.playsInline = true;

            video.onended = () => {
                this.next();
            };

            /*
             *If the video fails to start or triggers an error, move to the next item.
             */

            video.onerror = () => {

                console.log(
                    "Erreur vidéo :",
                    item.file
                );

                this.timer =
                    setTimeout(
                        () => this.next(),
                        3000
                    );
            };

            this.element.appendChild(video);

            video.play().catch(error => {

                console.log(
                    "Autoplay failed :",
                    error
                );

            });

            return;
        }


        /* ----------------------------------------
           Image
        ----------------------------------------- */

        const image =
            document.createElement("img");

        image.src = item.file;

        image.className =
            "slide-image";

        image.onerror = () => {

            console.log(
                "Erreur image :",
                item.file
            );

            this.timer =
                setTimeout(
                    () => this.next(),
                    3000
                );
        };

        this.element.appendChild(image);


        this.timer =
            setTimeout(
                () => this.next(),
				(item.duration || 8) * 1000
            );
    }


    next()
    {
        if (this.items.length === 0) {
            return;
        }

        this.index++;

        if (this.index >= this.items.length) {
            this.index = 0;
        }

        this.show();
    }
}


/* ============================================================
   Create both slideshows
============================================================ */

const leftSlideshow =
    new Slideshow("left");

const rightSlideshow =
    new Slideshow("right");


/* ============================================================
   API fetch
============================================================ */


async function updateSlides()
{
    try
    {
        const response =
            await fetch(
                "api.php?t=" + Date.now(),
                {
                    cache: "no-store"
                }
            );


        if (!response.ok)
        {
            throw new Error(
                "HTTP " + response.status
            );
        }


        const data =
            await response.json();


        /*
         * Version unchanged: nothing to do.
         */

        if (
            currentVersion !== null &&
            data.version === currentVersion
        ) {
            return;
        }


        console.log(
            "New kiosk configuration :",
            data.version
        );


        currentVersion =
            data.version;

        updateInfoBar(
        	    data.settings || {}
        	);
   		updateLayout(
			data.left || [],
			data.right || []
		);

        leftSlideshow.setItems(
            data.left || []
        );

        rightSlideshow.setItems(
            data.right || []
        );

    }
    catch(error)
    {
        /*
         * Very important for the kiosk: if the Internet connection is lost, keep the currently displayed content.
         */

        console.log(
            "Unable to reach api.php :",
            error
        );
    }
}


/* Initial fetch */

updateSlides();


/* Then check periodically */

setInterval(
    updateSlides,
    API_REFRESH
);

/* ============================================================
   Weather
============================================================ */

const WEATHER_REFRESH = 30 * 60 * 1000; // 30 minutes

async function updateWeather()
{
    const weather =
        document.getElementById("weather");

    if (
        weatherSettings.lat === null ||
        weatherSettings.lon === null
    ) {
        return;
    }

    try
    {
        const url =
            "https://api.open-meteo.com/v1/forecast" +
            "?latitude=" + encodeURIComponent(weatherSettings.lat) +
            "&longitude=" + encodeURIComponent(weatherSettings.lon) +
            "&current=temperature_2m,weather_code" +
            "&daily=weather_code,temperature_2m_max,temperature_2m_min" +
            "&timezone=" + encodeURIComponent(weatherSettings.timezone) +
            "&forecast_days=3";
        const response = await fetch(url);

        if (!response.ok) {
            throw new Error("HTTP " + response.status);
        }

        const data = await response.json();

        const icons = {
            0: "☀️",
            1: "?️",
            2: "⛅",
            3: "☁️",
            45: "?️",
            48: "?️",
            51: "?️",
            53: "?️",
            55: "?️",
            61: "?️",
            63: "?️",
            65: "?️",
            71: "?️",
            73: "?️",
            75: "❄️",
            80: "?️",
            81: "?️",
            82: "?️",
            95: "⛈️",
            96: "⛈️",
            99: "⛈️"
        };

        const currentIcon =
            icons[data.current.weather_code] || "?️";

        let html =
            `<strong>${weatherSettings.name}</strong> &nbsp;` +
             `${currentIcon} ` +
            `${Math.round(data.current.temperature_2m)} °C`;

        html += "<br>";

        for (let i = 0; i < data.daily.time.length; i++)
        {
            const date =
                new Date(data.daily.time[i] + "T12:00:00");

            const day =
                date.toLocaleDateString(
                    LOCALE,
                    { weekday: "short" }
                );

            const icon =
                icons[data.daily.weather_code[i]] || "?️";

            const max =
                Math.round(
                    data.daily.temperature_2m_max[i]
                );

            const min =
                Math.round(
                    data.daily.temperature_2m_min[i]
                );

            html +=
                `${day} ${icon} ${min}°/${max}°`;

            if (i < data.daily.time.length - 1) {
                html += " &nbsp;&nbsp; ";
            }
        }

        weather.innerHTML = html;
    }
    catch(error)
    {
        console.log("Weather error :", error);

        weather.textContent =
            I18N.weatherUnavailable;
    }
}

updateWeather();

setInterval(
    updateWeather,
    WEATHER_REFRESH
);
</script>

</body>
</html>