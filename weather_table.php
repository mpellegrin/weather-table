<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once('weather.class.php');

if (file_exists('cache/calendar.cache.html')) {
	$last_update = filemtime('cache/calendar.cache.html');
} else {
	$last_update = 0;
}
if ($last_update + 60*60 < time()) {

	require_once('config.php');

	$now = mktime(0,0,0);

	$weather = new Weather();
	$response = $weather->query_calendar($calendar_url, $dav_user, $dav_password);
	$events = $weather->parse_events($response);
	$html = $weather->get_html_table($events, $now, 10);

	file_put_contents('cache/calendar.cache.html', $html);

} else {
	$html = file_get_contents('cache/calendar.cache.html');
}

echo $html;
