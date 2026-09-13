<?php
require_once 'i18n.php'; 
//session_start ();
/*
 * ============================================================
 * CONFIGURATION
 * ============================================================
 */
$localConfigFile =
__DIR__ . '/config.local.php';

if (!is_file($localConfigFile)) {
    die(__('missing_local_config'));
}
$localConfig =
require $localConfigFile;

if (
    empty($localConfig['admin_password_hash'])
) {
    die(__('admin_password_not_configured'));
}
		$adminPasswordHash =
		$localConfig['admin_password_hash'];
		
const MAX_UPLOAD_SIZE = 50 * 1024 * 1024; // 50 MB
const DEFAULT_DURATION = 8;
$configFile = __DIR__ . '/config.json';
$zones = [ 
		'left' => __DIR__ . '/media/left',
		'right' => __DIR__ . '/media/right' 
];
/*
 * ============================================================
 * GENERAL FUNCTIONS
 * ============================================================
 */
function redirectAdmin() {
	header ( 'Location: admin.php' );
	exit ();
}
function safeFilename(string $name): string {
	$name = pathinfo ( $name, PATHINFO_FILENAME );
	$converted = iconv ( 'UTF-8', 'ASCII//TRANSLIT//IGNORE', $name );
	if ($converted !== false) {
		$name = $converted;
	}
	$name = preg_replace ( '/[^A-Za-z0-9_-]+/', '-', $name );
	$name = trim ( $name, '-' );
	if ($name === '') {
		$name = 'media';
	}
	return $name;
}
function uniqueFilename(string $directory, string $base, string $extension): string {
	$filename = $base . '.' . $extension;
	$counter = 2;
	while ( file_exists ( $directory . '/' . $filename ) ) {
		$filename = $base . '-' . $counter . '.' . $extension;
		$counter ++;
	}
	return $filename;
}
function formatSize(int $bytes): string {
    if ($bytes >= 1024 * 1024) {
        return number_format($bytes / (1024 * 1024), 1, '.', ' ') . ' MB';
    }
    return number_format($bytes / 1024, 0, '.', ' ') . ' KB';
}
/*
 * ============================================================
 * CONFIG.JSON
 * ============================================================
 */
function loadConfig(string $configFile): array {
	$config = [
			'settings' => [
				'kiosk_title'  => 'KioskLite',
				'show_clock'   => true,
				'show_weather' => false,
				'weather_name' => '',
				'weather_lat'  => 0,
				'weather_lon'  => 0,
				'timezone'     => 'UTC'
			],
			'left'  => [],
			'right' => []
	];
	if (! is_file ( $configFile )) {
		return $config;
	}
	$json = file_get_contents ( $configFile );
	$loaded = json_decode ( $json, true );
	if (! is_array ( $loaded )) {
		return $config;
	}
	foreach ( [ 
			'left',
			'right' 
	] as $zone ) {
		if (isset ( $loaded [$zone] ) && is_array ( $loaded [$zone] )) {
			$config [$zone] = $loaded [$zone];
		}
	}
	if (
			isset($loaded['settings']) &&
			is_array($loaded['settings'])
			) {
				$config['settings'] = array_merge(
						$config['settings'],
						$loaded['settings']
						);
			}
	return $config;
}
function saveConfig(string $configFile, array $config): bool {
	$json = json_encode ( $config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	if ($json === false) {
		return false;
	}
	return file_put_contents ( $configFile, $json, LOCK_EX ) !== false;
}
/*
 * ============================================================
 * READ FILES
 * ============================================================
 */
function getPhysicalFiles(string $directory): array {
	$result = [ ];
	if (! is_dir ( $directory )) {
		return $result;
	}
	foreach ( scandir ( $directory ) as $file ) {
		if ($file === '.' || $file === '..') {
			continue;
		}
		$path = $directory . '/' . $file;
		if (! is_file ( $path )) {
			continue;
		}
		$ext = strtolower ( pathinfo ( $file, PATHINFO_EXTENSION ) );
		if (! in_array ( $ext, [ 
				'jpg',
				'jpeg',
				'png',
				'webp',
				'gif',
				'mp4',
				'webm' 
		], true )) {
			continue;
		}
		$result [] = [ 
				'name' => $file,
				'type' => in_array ( $ext, [ 
						'mp4',
						'webm' 
				], true ) ? 'video' : 'image',
				'size' => filesize ( $path ) 
		];
	}
	usort ( $result, function ($a, $b) {
		return strnatcasecmp ( $a ['name'], $b ['name'] );
	} );
	return $result;
}
/*
 * ============================================================
 * CONFIG <-> FILE SYNCHRONIZATION
 * ============================================================
 */
function syncZoneConfig(array &$zoneConfig, array $files): bool {
	$changed = false;
	$existingNames = [ ];
	foreach ( $files as $file ) {
		$filename = $file ['name'];
		$existingNames [$filename] = true;
		/*
		 * New media item: automatically add it to the configuration
		 */
		if (! isset ( $zoneConfig [$filename] )) {
			$maxOrder = 0;
			foreach ( $zoneConfig as $cfg ) {
				if (isset ( $cfg ['order'] )) {
					$maxOrder = max ( $maxOrder, ( int ) $cfg ['order'] );
				}
			}
			$zoneConfig [$filename] = [ 
					'duration' => DEFAULT_DURATION,
					'order' => $maxOrder + 1 
			];
			$changed = true;
		}
		/*
		 * Complete an existing entry if some fields are missing.
		 */
		if (! isset ( $zoneConfig [$filename] ['duration'] )) {
			$zoneConfig [$filename] ['duration'] = DEFAULT_DURATION;
			$changed = true;
		}
		if (! isset ( $zoneConfig [$filename] ['order'] )) {
			$zoneConfig [$filename] ['order'] = 999999;
			$changed = true;
		}
		if (! isset ( $zoneConfig [$filename] ['active'] )) {
			$zoneConfig [$filename] ['active'] = true;
			$changed = true;
		}
		if (! isset ( $zoneConfig [$filename] ['start'] )) {
			$zoneConfig [$filename] ['start'] = '';
			$changed = true;
		}
		if (! isset ( $zoneConfig [$filename] ['end'] )) {
			$zoneConfig [$filename] ['end'] = '';
			$changed = true;
		}
	}
	/*
	 * Remove entries for files that were deleted, for example via FTP
	 */
	foreach ( array_keys ( $zoneConfig ) as $filename ) {
		if (! isset ( $existingNames [$filename] )) {
			unset ( $zoneConfig [$filename] );
			$changed = true;
		}
	}
	/*
	 * Normalize ordering: 1, 2, 3, 4...
	 */
	uasort ( $zoneConfig, function ($a, $b) {
		$oa = ( int ) ($a ['order'] ?? 999999);
		$ob = ( int ) ($b ['order'] ?? 999999);
		return $oa <=> $ob;
	} );
	$order = 1;
	foreach ( $zoneConfig as &$cfg ) {
		if (( int ) $cfg ['order'] !== $order) {
			$cfg ['order'] = $order;
			$changed = true;
		}
		$order ++;
	}
	unset ( $cfg );
	return $changed;
}
/*
 * ============================================================
 * LOGIN
 * ============================================================
 */
$error = '';
$message = '';
if (
		isset($_POST['login'])
		) {

			if (
					isset($_POST['password']) &&
					password_verify(
							$_POST['password'],
							$adminPasswordHash
							)
					) {
						session_regenerate_id(true);
						$_SESSION['kiosk_admin'] =	true;
						$_SESSION['csrf'] =
						bin2hex(
								random_bytes(32)
								);

						redirectAdmin();
					}
				$error = __('incorrect_password');
		}
if (isset ( $_GET ['logout'] )) {
	session_destroy ();
	redirectAdmin ();
}
/*
 * ============================================================
 * LOGIN PAGE
 * ============================================================
 */
if (empty ( $_SESSION ['kiosk_admin'] )) {
	?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($locale) ?>">
<head>

<meta charset="utf-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title><?php echo __('kiosklite_admin'); ?></title>

<style>
body {
	margin: 0;
	font-family: Arial, sans-serif;
	background: #f2f5f7;
}

.login {
	width: min(400px, calc(100% - 40px));
	margin: 100px auto;
	padding: 30px;
	background: white;
	border-radius: 12px;
	box-shadow: 0 3px 20px #0002;
}

h1 {
	margin-top: 0;
}

input {
	width: 100%;
	box-sizing: border-box;
	padding: 12px;
	font-size: 18px;
	margin: 15px 0;
}

button {
	width: 100%;
	padding: 12px;
	font-size: 18px;
	cursor: pointer;
}

.error {
	color: #b00020;
}

.settings {
	display: block;
	width: 100%;
	margin-top: 10px;
}

.settings-line {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	gap: 8px;
	margin-top: 7px;
}

.date {
	padding: 7px;
	font-size: 14px;
}

.active-label {
	display: inline-flex;
	align-items: center;
	gap: 5px;
	margin-left: 10px;
}

.active-label input {
	width: 18px;
	height: 18px;
}

.move-controls {
	display: flex;
	flex-wrap: wrap;
	gap: 7px;
	margin-top: 10px;
}

.move-controls form {
	margin: 0;
}

.status {
	padding: 5px 9px;
	border-radius: 12px;
	font-size: 12px;
	font-weight: bold;
}

.status.displayed {
	background: #d8f3dc;
	color: #216e39;
}

.status.future {
	background: #dbeafe;
	color: #2457a6;
}

.status.expired {
	background: #ffe8cc;
	color: #a04b00;
}

.status.disabled {
	background: #e5e5e5;
	color: #666;
}

.zone-title {
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: 10px;
}

.zone-counter {
	font-size: 14px;
	font-weight: normal;
	padding: 5px 9px;
	background: #eef6fa;
	color: #456;
	border-radius: 12px;
	white-space: nowrap;
}
.general-settings {
    max-width: 1400px;

    margin: 0 auto 20px;
    padding: 15px 20px;

    background: white;

    border-radius: 12px;

    box-shadow: 0 2px 12px #0001;
}

.general-settings form {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 15px;
}

.general-settings label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.general-settings label.title-setting {
    width: 100%;
}

.general-text {
    flex: 1;
}

.weather-settings {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;

    width: 100%;

    padding-top: 10px;
    margin-top: 5px;

    border-top: 1px solid #ddd;
}

.weather-settings input {
    padding: 7px;
}

.weather-settings input[name="weather_name"] {
    width: 180px;
}

.weather-settings input[name="weather_lat"],
.weather-settings input[name="weather_lon"] {
    width: 110px;
}
.general-settings input[type="checkbox"] {
    width: 18px;
    height: 18px;
}
</style>

</head>

<body>

	<div class="login">

		<h1>Kiosk</h1>

		<p><?php echo __('media_admin'); ?></p>

<?php if ($error): ?>

<p class="error">
<?= htmlspecialchars($error) ?>
</p>

<?php endif; ?>

<form method="post">

			<input
				type="password"
				name="password"
				placeholder="<?= htmlspecialchars(__('password')) ?>"
				autofocus
				required
			>
			<button type="submit" name="login" value="1">
				<?= __('log_in') ?>
			</button>
		</form>

	</div>

</body>

</html>

<?php
	exit ();
}
/*
 * ============================================================
 * CSRF
 * ============================================================
 */
function checkCsrf() {
	if (empty ( $_POST ['csrf'] ) || empty ( $_SESSION ['csrf'] ) || ! hash_equals ( $_SESSION ['csrf'], $_POST ['csrf'] )) {
		die(__('invalid_request'));
	}
}
/*
 * ============================================================
 * LOAD + SYNCHRONIZATION
 * ============================================================
 */
$config = loadConfig ( $configFile );
$leftPhysical = getPhysicalFiles ( $zones ['left'] );
$rightPhysical = getPhysicalFiles ( $zones ['right'] );
$changed = false;
$changed |= syncZoneConfig ( $config ['left'], $leftPhysical );
$changed |= syncZoneConfig ( $config ['right'], $rightPhysical );
if ($changed) {
	saveConfig ( $configFile, $config );
}

/* ============================================================
 PARAMETRES GENERAUX
 ============================================================ */

if (isset($_POST['save_settings'])) {

	checkCsrf();

	$config['settings']['show_clock'] =
	isset($_POST['show_clock']);

	$config['settings']['show_weather'] =
	isset($_POST['show_weather']);
	
	$config['settings']['kiosk_title'] =
	trim($_POST['kiosk_title'] ?? '');
	
	$config['settings']['weather_name'] =
	trim($_POST['weather_name'] ?? '');
	
	$config['settings']['weather_lat'] =
	(float)($_POST['weather_lat'] ?? 0);

	$config['settings']['weather_lon'] =
	(float)($_POST['weather_lon'] ?? 0);

	$timezoneName =
		trim($_POST['timezone'] ?? 'UTC');
	if (!in_array($timezoneName, timezone_identifiers_list(), true)) {
		$_SESSION['message'] = __('invalid_timezone');
		redirectAdmin();
	}

	$config['settings']['timezone'] =
		$timezoneName;
		
	saveConfig(
			$configFile,
			$config
			);

	$_SESSION['message'] = __('display_settings_saved');

	redirectAdmin();
}
/*
 * ============================================================
 * UPLOAD
 * ============================================================
 */
if (isset ( $_POST ['upload'] )) {
	checkCsrf ();
	$zone = $_POST ['zone'] ?? '';
	if (! isset ( $zones [$zone] )) {
		die(__('invalid_zone'));
	}
	if (! isset ( $_FILES ['media'] ) || $_FILES ['media'] ['error'] !== UPLOAD_ERR_OK) {
		$_SESSION ['message'] = __('file_upload_error');
		redirectAdmin ();
	}
	$upload = $_FILES ['media'];
	if ($upload ['size'] > MAX_UPLOAD_SIZE) {
		$_SESSION ['message'] = __('file_too_large');
		redirectAdmin ();
	}
	$finfo = new finfo ( FILEINFO_MIME_TYPE );
	$mime = $finfo->file ( $upload ['tmp_name'] );
	$allowed = [ 
			'image/jpeg' => 'jpg',
			'image/png' => 'png',
			'image/webp' => 'webp',
			'image/gif' => 'gif',
			'video/mp4' => 'mp4',
			'video/webm' => 'webm' 
	];
	if (! isset ( $allowed [$mime] )) {
		$_SESSION ['message'] = __('bad_format') .': '. $mime;
		redirectAdmin ();
	}
	$extension = $allowed [$mime];
	$base = safeFilename ( $upload ['name'] );
	$filename = uniqueFilename ( $zones [$zone], $base, $extension );
	$destination = $zones [$zone] . '/' . $filename;
	if (! move_uploaded_file ( $upload ['tmp_name'], $destination )) {
		$_SESSION ['message'] =  __('save_error') ;
		redirectAdmin ();
	}
	/*
	 * Add directly to configuration
	 */
	$maxOrder = 0;
	foreach ( $config [$zone] as $cfg ) {
		$maxOrder = max ( $maxOrder, ( int ) ($cfg ['order'] ?? 0) );
	}
	$config [$zone] [$filename] = [ 
			'duration' => DEFAULT_DURATION,
			'order' => $maxOrder + 1,
			'active' => true,
			'start' => '',
			'end' => '' 
	];
	saveConfig ( $configFile, $config );
	$_SESSION ['message'] =  __('media_added') .': '. $filename;
	redirectAdmin ();
}
/*
 * ============================================================
 * MEDIA SETTINGS
 * duration + active status + dates
 * ============================================================
 */
if (isset ( $_POST ['save'] )) {
	checkCsrf ();
	$zone = $_POST ['zone'] ?? '';
	$filename = basename ( $_POST ['file'] ?? '');
	if (! isset ( $config [$zone] [$filename] )) {
		die(__('invalid_media'));
	}
	/* ---------- Duration ---------- */
	$duration = ( int ) ($_POST ['duration'] ?? DEFAULT_DURATION);
	if ($duration < 1) {
		$duration = 1;
	}
	if ($duration > 600) {
		$duration = 600;
	}
	/* ---------- Active ---------- */
	$active = isset ( $_POST ['active'] );
	/* ---------- Dates ---------- */
	$start = trim ( $_POST ['start'] ?? '');
	$end = trim ( $_POST ['end'] ?? '');
	/*
	 * Validate YYYY-MM-DD format
	 */
	$validDate = function ($date) {
		if ($date === '') {
			return true;
		}
		$d = DateTimeImmutable::createFromFormat ( '!Y-m-d', $date );
		return $d !== false && $d->format ( 'Y-m-d' ) === $date;
	};
	if (! $validDate ( $start )) {
		$_SESSION['message'] = __('invalid_start_date');
		redirectAdmin ();
	}
	if (! $validDate ( $end )) {
		$_SESSION ['message'] =__('invalid_end_date');
		redirectAdmin ();
	}
	/*
	 * The end date cannot be earlier than the start date.
	 */
	if ($start !== '' && $end !== '' && $end < $start) {
		$_SESSION ['message'] = __('bad_start_end_date');
		redirectAdmin ();
	}
	/* ---------- Save ---------- */
	$config [$zone] [$filename] ['duration'] = $duration;
	$config [$zone] [$filename] ['active'] = $active;
	$config [$zone] [$filename] ['start'] = $start;
	$config [$zone] [$filename] ['end'] = $end;
	saveConfig ( $configFile, $config );
	$_SESSION ['message'] = __('settings_saved') . ': ' . $filename;
	redirectAdmin ();
}
/*
 * ============================================================
 * MOVE ↑ ↓
 * ============================================================
 */
if (isset ( $_POST ['move'] )) {
	checkCsrf ();
	$zone = $_POST ['zone'] ?? '';
	$filename = basename ( $_POST ['file'] ?? '');
	$direction = $_POST ['direction'] ?? '';
	if (! isset ( $config [$zone] [$filename] )) {
		die(__('invalid_media'));
	}
	/*
	 * List sorted by configured order
	 */
	uasort ( $config [$zone], function ($a, $b) {
		return ( int ) $a ['order'] <=> ( int ) $b ['order'];
	} );
	$names = array_keys ( $config [$zone] );
	$index = array_search ( $filename, $names, true );
	if ($index !== false) {
		$swapIndex = $direction === 'up' ? $index - 1 : $index + 1;
		if (isset ( $names [$swapIndex] )) {
			$other = $names [$swapIndex];
			$tmp = $config [$zone] [$filename] ['order'];
			$config [$zone] [$filename] ['order'] = $config [$zone] [$other] ['order'];
			$config [$zone] [$other] ['order'] = $tmp;
			saveConfig ( $configFile, $config );
		}
	}
	redirectAdmin ();
}
/*
 * ============================================================
 * DELETION
 * ============================================================
 */
if (isset ( $_POST ['delete'] )) {
	checkCsrf ();
	$zone = $_POST ['zone'] ?? '';
	$filename = basename ( $_POST ['file'] ?? '');
	if (! isset ( $zones [$zone] )) {
		die(__('invalid_zone'));
	}
	$path = $zones [$zone] . '/' . $filename;
	if ($filename !== '' && is_file ( $path )) {
		unlink ( $path );
	}
	/*
	 * Remove from configuration
	 */
	unset ( $config [$zone] [$filename] );
	/*
	 * Renumbering
	 */
	uasort ( $config [$zone], function ($a, $b) {
		return ( int ) $a ['order'] <=> ( int ) $b ['order'];
	} );
	$order = 1;
	foreach ( $config [$zone] as &$cfg ) {
		$cfg ['order'] = $order ++;
	}
	unset ( $cfg );
	saveConfig ( $configFile, $config );
	$_SESSION ['message'] =  __('media_deleted').': '. $filename;
	redirectAdmin ();
}
/*
 * ============================================================
 * MESSAGE
 * ============================================================
 */
if (! empty ( $_SESSION ['message'] )) {
	$message = $_SESSION ['message'];
	unset ( $_SESSION ['message'] );
}
/*
 * ============================================================
 * DISPLAY LIST
 * ============================================================
 */
function getMediaStatus(array $file, DateTimeZone $timezone): array {
	if (! $file ['active']) {
		return [ 
				'class' => 'disabled',
				'label' => __('DISABLED') 
		];
	}
	
		$today = (
			new DateTimeImmutable('today', $timezone)
		)->format('Y-m-d');
	
	if ($file ['start'] !== '' && $today < $file ['start']) {
		return [ 
				'class' => 'future',
				'label' => __('UPCOMING') 
		];
	}
	if ($file ['end'] !== '' && $today > $file ['end']) {
		return [ 
				'class' => 'expired',
				'label' =>__('EXPIRED')
		];
	}
	return [ 
			'class' => 'displayed',
			'label' => __('DISPLAYED')
	];
}
function buildDisplayList(array $physical, array $config): array {
	foreach ( $physical as &$file ) {
		$name = $file ['name'];
		$file ['duration'] = ( int ) ($config [$name] ['duration'] ?? DEFAULT_DURATION);
		$file ['order'] = ( int ) ($config [$name] ['order'] ?? 999999);
		$file ['active'] = ( bool ) ($config [$name] ['active'] ?? true);
		$file ['start'] = ( string ) ($config [$name] ['start'] ?? '');
		$file ['end'] = ( string ) ($config [$name] ['end'] ?? '');
	}
	unset ( $file );
	usort ( $physical, function ($a, $b) {
		return $a ['order'] <=> $b ['order'];
	} );
	return $physical;
}
$timezoneName =
    $config['settings']['timezone'] ?? 'UTC';

try {
    $timezone = new DateTimeZone($timezoneName);
} catch (Exception $e) {
    $timezone = new DateTimeZone('UTC');
}
$leftFiles = buildDisplayList ( getPhysicalFiles ( $zones ['left'] ), $config ['left'] );
$rightFiles = buildDisplayList ( getPhysicalFiles ( $zones ['right'] ), $config ['right'] );
?>
<!DOCTYPE html>

<html lang="<?= htmlspecialchars($locale) ?>">
<head>

<meta charset="utf-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title><?php echo __('kiosklite_admin'); ?></title>

<style>
* {
	box-sizing: border-box;
}

body {
	margin: 0;
	padding: 20px;
	background: #f2f5f7;
	font-family: Arial, Helvetica, sans-serif;
	color: #333;
}

header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	max-width: 1400px;
	margin: auto auto 20px;
}

header h1 {
	margin: 0;
}

.logout {
	color: #555;
}

.message {
	max-width: 1400px;
	margin: 0 auto 20px;
	padding: 12px 18px;
	background: #dff4df;
	border-radius: 8px;
}

.zones {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	gap: 20px;
	max-width: 1400px;
	margin: auto;
}

.zone {
	background: white;
	padding: 20px;
	border-radius: 12px;
	box-shadow: 0 2px 12px #0001;
}

.zone h2 {
	margin-top: 0;
	padding-bottom: 10px;
	border-bottom: 3px solid lightblue;
}

.upload {
	display: flex;
	gap: 10px;
	margin-bottom: 20px;
}

.upload input[type=file] {
	flex: 1;
}

button {
	padding: 8px 12px;
	border: 0;
	border-radius: 6px;
	cursor: pointer;
	font-size: 15px;
}

.upload button, .save {
	background: #1c87c9;
	color: white;
}

.delete {
	background: #c62828;
	color: white;
}

.move {
	background: #ddd;
	color: #333;
	min-width: 38px;
}

.media {
	display: grid;
	grid-template-columns: 130px minmax(0, 1fr);
	gap: 15px;
	align-items: center;
	padding: 14px 0;
	border-bottom: 1px solid #ddd;
}

.preview {
	width: 130px;
	height: 90px;
	display: flex;
	align-items: center;
	justify-content: center;
	background: #eee;
	overflow: hidden;
	border-radius: 6px;
}

.preview img, .preview video {
	max-width: 100%;
	max-height: 100%;
	object-fit: contain;
}

.name {
	font-weight: bold;
	overflow-wrap: anywhere;
}

.size {
	margin-top: 4px;
	font-size: 13px;
	color: #777;
}

.controls {
	margin-top: 10px;
	display: flex;
	flex-wrap: wrap;
	gap: 7px;
	align-items: center;
}

.controls form {
	display: inline-flex;
	gap: 5px;
	align-items: center;
	margin: 0;
}

.duration {
	width: 70px;
	padding: 7px;
	font-size: 15px;
	text-align: center;
}

.order {
	display: inline-block;
	min-width: 30px;
	text-align: center;
	color: #888;
}

.empty {
	padding: 30px;
	text-align: center;
	color: #999;
}

.general-text {
    width: 50%;
}

@media ( max-width : 850px ) {
	.zones {
		grid-template-columns: 1fr;
	}
	.media {
		grid-template-columns: 100px minmax(0, 1fr);
	}
	.preview {
		width: 100px;
		height: 75px;
	}
}
</style>

</head>

<body>

	<header>

		<div>

			<h1><?php echo __('kiosklite_admin'); ?></h1>

		</div>

		<a href="?logout=1" class="logout"> <?php echo __('log_out'); ?> </a>

    <!-- Language Switcher Links -->
    <nav>
        <a href="?lang=en" style="<?php echo $locale === 'en' ? 'font-weight:bold;' : ''; ?>">English</a> | 
        <a href="?lang=fr" style="<?php echo $locale === 'fr' ? 'font-weight:bold;' : ''; ?>">Français</a>
    </nav>

	</header>


<?php if (!empty($message)): ?>

<div class="message">

<?= htmlspecialchars($message) ?>

</div>

<?php endif; ?>

<div class="general-settings">

<form method="post">

<input
    type="hidden"
    name="csrf"
    value="<?= htmlspecialchars($_SESSION['csrf']) ?>"
>

<strong><?php echo __('general_display'); ?></strong>
<br>

<label class="title-setting">
    Message

    <input
        class="general-text"
        type="text"
        name="kiosk_title"
        value="<?= htmlspecialchars($config['settings']['kiosk_title']) ?>"
    >
</label>
<br>
<label>
    <input
        type="checkbox"
        name="show_clock"
        value="1"
        <?= !empty($config['settings']['show_clock'])
            ? 'checked'
            : '' ?>
    >
	<?php echo __('date_and_time'); ?>
</label>

<br>
<label>
    <input
        type="checkbox"
        name="show_weather"
        value="1"
        <?= !empty($config['settings']['show_weather'])
            ? 'checked'
            : '' ?>
    >
	<?php echo __('weather'); ?>
</label>
<br>

<div class="weather-settings">

<label>
	<?php echo __('location'); ?>
    <input
        type="text"
        name="weather_name"
        value="<?= htmlspecialchars($config['settings']['weather_name']) ?>"
    >
</label>

<label>
    Latitude
    <input
        type="number"
        step="0.0001"
        name="weather_lat"
        value="<?= htmlspecialchars($config['settings']['weather_lat']) ?>"
    >
</label>

<label>
    Longitude
    <input
        type="number"
        step="0.0001"
        name="weather_lon"
        value="<?= htmlspecialchars($config['settings']['weather_lon']) ?>"
    >
</label>
<label>
	<?php echo __('timezone'); ?>
    <input
        type="text"
        name="timezone"
        value="<?= htmlspecialchars(
            $config['settings']['timezone'] ?? 'UTC',
            ENT_QUOTES,
            'UTF-8'
        ) ?>"
        placeholder="Europe/Paris"
    >
</label>
</div>

<br>
<button
    class="save"
    type="submit"
    name="save_settings"
    value="1"
>
<?php echo __('save'); ?>
</button>

</form>

</div>
<div class="zones">

<?php
function countDisplayedMedia(
    array $files,
    DateTimeZone $timezone
): int {
    $count = 0;

    foreach ($files as $file) {
        $status = getMediaStatus(
            $file,
            $timezone
        );

        if ($status['class'] === 'displayed') {
            $count++;
        }
    }

    return $count;
}

foreach ( [ 
		'left' => __('left_area'),
		'right' => __('right_area')
] as $zone => $title ) :
	$files = $zone === 'left' ? $leftFiles : $rightFiles;
	$totalCount = count ( $files );
	$displayedCount =
    countDisplayedMedia(
        $files,
        $timezone
    );	?>

<section class="zone">

			<h2 class="zone-title">

				<span>
<?= $title ?> ->
</span> <span class="zone-counter">
<?= $displayedCount ?> <?php echo __('active'); ?><?= $displayedCount > 1 ? 's' : '' ?>
/
<?= $totalCount ?>  <?php echo __('media_item'); ?><?= $totalCount !== 1 ? 's' : '' ?>
</span>

			</h2>

			<form class="upload" method="post" enctype="multipart/form-data">

				<input type="hidden" name="csrf"
					value="<?= htmlspecialchars($_SESSION['csrf']) ?>"> <input
					type="hidden" name="zone" value="<?= $zone ?>"> <input type="file"
					name="media" accept="image/*,video/mp4,video/webm" required>

				<button type="submit" name="upload" value="1">+ <?php echo __('add'); ?></button>

			</form>


<?php if (!$files): ?>

<div class="empty"><?php echo __('no_media'); ?></div>

<?php endif; ?>


<?php foreach ($files as $index => $file): ?>
<?php
		$status = getMediaStatus ( $file, $timezone );
		?>
<div class="media">


				<div class="preview">

<?php if ($file['type'] === 'video'): ?>

<video src="media/<?= $zone ?>/<?= rawurlencode($file['name']) ?>" muted></video>

<?php else: ?>

<img src="media/<?= $zone ?>/<?= rawurlencode($file['name']) ?>" alt="">

<?php endif; ?>

</div>


				<div>

					<div class="name">

						<span class="order">
<?= $file['order'] ?>.
</span>

<?= htmlspecialchars($file['name']) ?>

</div>


					<div class="size">
<?= formatSize($file['size']) ?>
</div>

					<div class="controls">

<!-- ========================================================
     PARAMETRES DU MEDIA
========================================================= -->

						<form method="post" class="settings">

							<input type="hidden" name="csrf"
								value="<?= htmlspecialchars($_SESSION['csrf']) ?>"> <input
								type="hidden" name="zone" value="<?= $zone ?>"> <input
								type="hidden" name="file"
								value="<?= htmlspecialchars($file['name']) ?>">


							<div class="settings-line">

								<label> <?php echo __('duration'); ?> </label> <input class="duration" type="number"
									name="duration" min="1" max="600"
									value="<?= $file['duration'] ?>"> <span><?php echo __('seconds'); ?> </span> <label
									class="active-label"> <input type="checkbox" name="active"
									value="1" <?= $file['active'] ? 'checked' : '' ?>> <?php echo __('active'); ?> 

								</label> <span class="status <?= $status['class'] ?>">
<?= $status['label'] ?>
</span>

							</div>


							<div class="settings-line">

								<label> <?php echo __('from'); ?> </label> <input class="date" type="date" name="start"
									value="<?= htmlspecialchars($file['start']) ?>"> <label> <?php echo __('to'); ?></label>

								<input class="date" type="date" name="end"
									value="<?= htmlspecialchars($file['end']) ?>">


								<button class="save" type="submit" name="save" value="1">
									<?php echo __('save'); ?></button>

							</div>

						</form>


						<!-- ========================================================
     ORDER
========================================================= -->

						<div class="move-controls">

							<!-- Move up -->

							<form method="post">

								<input type="hidden" name="csrf"
									value="<?= htmlspecialchars($_SESSION['csrf']) ?>"> <input
									type="hidden" name="zone" value="<?= $zone ?>"> <input
									type="hidden" name="file"
									value="<?= htmlspecialchars($file['name']) ?>"> <input
									type="hidden" name="direction" value="up">

								<button class="move" type="submit" name="move" value="1"
									title="Move up">↑ <?php echo __('move_up'); ?></button>

							</form>


							<!-- Move Down -->

							<form method="post">

								<input type="hidden" name="csrf"
									value="<?= htmlspecialchars($_SESSION['csrf']) ?>"> <input
									type="hidden" name="zone" value="<?= $zone ?>"> <input
									type="hidden" name="file"
									value="<?= htmlspecialchars($file['name']) ?>"> <input
									type="hidden" name="direction" value="down">

								<button class="move" type="submit" name="move" value="1"
									title="Move down">↓  <?php echo __('move_down'); ?></button>

							</form>


							<!-- SUPPRIMER -->

							<form method="post"
								onsubmit="return confirm('<?php echo __('delete_media_?'); ?>');">

								<input type="hidden" name="csrf"
									value="<?= htmlspecialchars($_SESSION['csrf']) ?>"> <input
									type="hidden" name="zone" value="<?= $zone ?>"> <input
									type="hidden" name="file"
									value="<?= htmlspecialchars($file['name']) ?>">

								<button class="delete" type="submit" name="delete" value="1">
									<?php echo __('delete'); ?></button>

							</form>

						</div>

					</div>


				</div>

			</div>

<?php endforeach; ?>


</section>

<?php endforeach; ?>

</div>

</body>

</html>