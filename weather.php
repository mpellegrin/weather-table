<?php
header("Content-type: image/png");

if (!isset($_GET['mode'])) {
	$_GET['mode'] = 'normal';
}

if ($_GET['mode'] == 'large') {
	$mode = 'large';
	$width = 200;
	$height = 40;
	$font_size = 4;
	$borders_margin = 5;
	$margin = 5;
	$circle_width = 20;
} elseif ($_GET['mode'] == 'small') {
	$mode = 'small';
	$width = 32;
	$height = 32;
	$borders_margin = 5;
	$margin = 5;
	$circle_width = 32-5;
} elseif ($_GET['mode'] == 'date') {
	$mode = 'date';
	$width = 100;
	$height = 32;
	$font_size = 2;
	$borders_margin = 5;
	$margin = 5;
	$circle_width = 18;
} elseif ($_GET['mode'] == 'mail') {
	$mode = 'mail';
	$width = 16;
	$height = 16;
	$font_size = 2;
	$borders_margin = 0;
	$margin = 0;
	$circle_width = 10;
} else {
	$mode = 'normal';
	$width = 150;
	$height = 32;
	$font_size = 2;
	$borders_margin = 3;
	$margin = 5;
	$circle_width = 18;
}

if (file_exists('cache/weather.' . $mode . '.cache.png')) {
	$last_update = filemtime('cache/weather.' . $mode . '.cache.png');
} else {
		$last_update = 0;
}
if ($last_update + 60*60 < time()) {

	ob_start();

	$img = imagecreatetruecolor($width, $height);

	$colors['white'] = imagecolorallocate($img, 255, 255, 255);
	$colors['blue'] = imagecolorallocate($img, 0, 0, 255);
	$colors['black'] = imagecolorallocate($img, 0, 0, 0);
	$colors['red'] = imagecolorallocate($img, 255, 0, 0);
	$colors['orange'] = imagecolorallocate($img, 255, 127, 0);
	$colors['green'] = imagecolorallocate($img, 0, 255, 0);

	require_once('weather.class.php');
	require_once('config.php');
	$weather = new Weather();
	$response = $weather->query_calendar($calendar_url, $dav_user, $dav_password);
	$events = $weather->parse_events($response);
	$level = $weather->get_level($events, mktime(0,0,0));

	imagefilledrectangle($img, 0, 0, $width, $height, $colors['white']);
	if ($mode == 'normal' || $mode == 'large') {
		imagestring($img, $font_size, $borders_margin + $margin, $borders_margin + $margin, 'Charge de travail', $colors['black']);
		imagefilledellipse($img, $width - $circle_width - $borders_margin, $height/2, $circle_width, $circle_width, $colors[$level]);
	} elseif ($mode == 'date') {
		imagestring($img, $font_size, $borders_margin + $circle_width + $margin*2, $borders_margin + $margin, date('Y-m-d'), $colors['black']);
		imagefilledellipse($img, $circle_width/2 + $borders_margin, $height/2, $circle_width, $circle_width, $colors[$level]);
	} else {
		imagefilledellipse($img, $width/2, $height/2, $circle_width, $circle_width, $colors[$level]);
	}

	imagepng($img);
	imagedestroy($img);

	$image = ob_get_clean();
	ob_end_flush();

	file_put_contents('cache/weather.' . $mode . '.cache.png', $image);

} else {
	$image = file_get_contents('cache/weather.' . $mode . '.cache.png');
}

echo $image;
