<?php
date_default_timezone_set('UTC');
header ( 'Content-Type: application/json; charset=utf-8' );
header ( 'Cache-Control: no-cache, no-store, must-revalidate' );//

 // ============================================================
 // Configuration
 // ============================================================
 */
const DEFAULT_DURATION = 8;
$configFile = __DIR__ . '/config.json';
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

function syncConfigWithFiles(string $directory, array &$zoneConfig): bool {
	$changed = false;
	$files = glob ( $directory . '/*' );
	$existing = [ ];
	 // Search for the highest current order
	$maxOrder = 0;
	foreach ( $zoneConfig as $cfg ) {
		$maxOrder = max ( $maxOrder, ( int ) ($cfg ['order'] ?? 0) );
	}
	foreach ( $files as $file ) {
		if (! is_file ( $file )) {
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
		$filename = basename ( $file );
		$existing [$filename] = true;
		 // New file found
		if (! isset ( $zoneConfig [$filename] )) {
			$maxOrder ++;
			$zoneConfig [$filename] = [ 
					'duration' => DEFAULT_DURATION,
					'order' => $maxOrder,
					'active' => true,
					'start' => '',
					'end' => '' 
			];
			$changed = true;
		}
	}
 // Remove entries whose files no longer exist
 // (for example after FTP deletion)
	foreach ( array_keys ( $zoneConfig ) as $filename ) {
		if (! isset ( $existing [$filename] )) {
			unset ( $zoneConfig [$filename] );
			$changed = true;
		}
	}
	 // Clean renumbering: 1, 2, 3...
	uasort ( $zoneConfig, function ($a, $b) {
		return ( int ) ($a ['order'] ?? 999999) <=> ( int ) ($b ['order'] ?? 999999);
	} );
	$order = 1;
	foreach ( $zoneConfig as &$cfg ) {
		if (( int ) ($cfg ['order'] ?? 0) !== $order) {
			$cfg ['order'] = $order;
			$changed = true;
		}
		if (! isset ( $cfg ['duration'] )) {
			$cfg ['duration'] = DEFAULT_DURATION;
			$changed = true;
		}
		if (! isset ( $cfg ['active'] )) {
			$cfg ['active'] = true;
			$changed = true;
		}
		if (! isset ( $cfg ['start'] )) {
			$cfg ['start'] = '';
			$changed = true;
		}
		if (! isset ( $cfg ['end'] )) {
			$cfg ['end'] = '';
			$changed = true;
		}
		$order ++;
	}
	unset ( $cfg );
	return $changed;
}
if (is_file ( $configFile )) {
	$json = file_get_contents ( $configFile );
	$loaded = json_decode ( $json, true );
	if (is_array ( $loaded )) {
		$config = array_replace_recursive ( $config, $loaded );
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
$timezoneName = (string) ($config['settings']['timezone'] ?? 'UTC');

try {
    $timezone = new DateTimeZone($timezoneName);
} catch (Exception $e) {
    $timezoneName = 'UTC';
    $timezone = new DateTimeZone('UTC');
    $config['settings']['timezone'] = 'UTC';
}

date_default_timezone_set($timezoneName);

function isMediaVisible(array $mediaConfig, DateTimeZone $timezone): bool {
	 // Manually disabled
	if (isset ( $mediaConfig ['active'] ) && $mediaConfig ['active'] === false) {
		return false;
	}
	$today = new DateTimeImmutable('today', $timezone);
	$start = trim ( ( string ) ($mediaConfig ['start'] ?? '') );
	$end = trim ( ( string ) ($mediaConfig ['end'] ?? '') );
	 // Start date
	if ($start !== '') {
		$startDate = DateTimeImmutable::createFromFormat('!Y-m-d', $start, $timezone);
		if ($startDate !== false && $today < $startDate) {
			return false;
		}
	}
	 // End date
	 // The end date is inclusive.
	if ($end !== '') {
		$endDate = DateTimeImmutable::createFromFormat('!Y-m-d', $end, $timezone);
		if ($endDate !== false && $today > $endDate) {
			return false;
		}
	}
	return true;
}
 // ============================================================
 // Read media files
 // ============================================================
function getMedia(
    string $directory,
    string $urlPrefix,
    array $zoneConfig,
    DateTimeZone $timezone
): array {
	$items = [ ];
	$files = glob ( $directory . '/*' );
	foreach ( $files as $file ) {
		if (! is_file ( $file )) {
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
		$filename = basename ( $file );
		 // Optional configuration
		 // for this media item
		$mediaConfig = $zoneConfig [$filename] ?? [ ];
		// The file exists but is not currently
		// scheduled for display.
		if (!isMediaVisible($mediaConfig, $timezone)) {
			continue;
		}
		$duration = isset ( $mediaConfig ['duration'] ) ? ( int ) $mediaConfig ['duration'] : DEFAULT_DURATION;
		if ($duration < 1) {
			$duration = DEFAULT_DURATION;
		}
		$order = isset ( $mediaConfig ['order'] ) ? ( int ) $mediaConfig ['order'] : 999999;
		$items [] = [ 
				'file' => $urlPrefix . '/' . $filename,
				'name' => $filename,
				'type' => in_array ( $ext, [ 
						'mp4',
						'webm' 
				], true ) ? 'video' : 'image',
				'duration' => $duration,
				'order' => $order,
				'mtime' => filemtime ( $file ) 
		];
	}
	// Sorting:
	// 1 - order defined in config.json
	// 2 - filename
	usort ( $items, function ($a, $b) {
		if ($a ['order'] !== $b ['order']) {
			return $a ['order'] <=> $b ['order'];
		}
		return strnatcasecmp ( $a ['name'], $b ['name'] );
	} );
	return $items;
}
$configChanged = false;
$configChanged |= syncConfigWithFiles ( __DIR__ . '/media/left', $config ['left'] );
$configChanged |= syncConfigWithFiles ( __DIR__ . '/media/right', $config ['right'] );
if ($configChanged) {
	file_put_contents ( $configFile, json_encode ( $config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ), LOCK_EX );
}
 // Two display zones
$left = getMedia(
    __DIR__ . '/media/left',
    'media/left',
    $config['left'],
    $timezone
);

$right = getMedia(
    __DIR__ . '/media/right',
    'media/right',
    $config['right'],
    $timezone
);


 // Version
 // It now depends on:
 // - the files
 // - their modification time
 // - their duration
 // - their order

$version = md5 ( json_encode ( [ 
		'date' => date ( 'Y-m-d' ),
		'settings' => $config['settings'],
		'left' => $left,
		'right' => $right 
] ) );

// JSON response

echo json_encode ( [ 
		'version' => $version,
		'settings' => $config['settings'],
		'left' => $left,
		'right' => $right 
], JSON_UNESCAPED_SLASHES );