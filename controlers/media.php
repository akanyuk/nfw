<?php
$CMedia = new media;

$path_parts = pathinfo(preg_replace("/^\//", "", $_SERVER['REQUEST_URI']));
$path_parts['dirname'] = str_replace(get_class($CMedia).'/_protected/', '', $path_parts['dirname']);

if (stristr($_SERVER['REQUEST_URI'], '_protected/')) {
	list($foo, $record_id) = explode('/', $path_parts['dirname']);
	
	if (!$CMedia->reload($record_id, array('load_data' => true))) {
		NFW::i()->stop($CMedia->last_msg);
	}
}
else {
	if (!$CMedia->reload(
		array(
			'owner_class' => preg_replace('/(^\/?'.get_class($CMedia).'\/)/', '', $path_parts['dirname']),
			'basename' => rawurldecode(preg_replace("/_tmb.*/", "", $path_parts['filename'])).(isset($path_parts['extension']) ? '.'.$path_parts['extension'] : '')
		),
		array('load_data' => true))) {
		NFW::i()->stop($CMedia->last_msg);
	}
}

if ($CMedia->record['type'] != 'image') {
	header('Content-type: '.$CMedia->record['mime_type']);
	header('Content-Length: '.$CMedia->record['filesize']);
	header('Content-Disposition: attachment; filename="'.$CMedia->record['basename'].'"');
	header('Content-Transfer-Encoding: binary');
	NFW::i()->stop($CMedia->record['data']);
}

if (!$result = getimagesize($CMedia->record['fullpath'])) {
	// This is not image!
	header("Status: 404 Not Found");
	NFW::i()->stop('File not found.');
}

list($src_width, $src_height, $img_type) = $result;
$img_type = str_replace('jpeg', 'jpg', image_type_to_extension($img_type, false));

// Determine thumbnail size
preg_match("/_tmb(\d+)?(x(\d+))?/", $path_parts['filename'], $d);
$tmb_width = isset($d[1]) ? $d[1] : null;
$tmb_height = isset($d[3]) ? $d[3] : null;


// Determine new image dimension
$max_width  = isset($tmb_width) && $tmb_width > 0 && $tmb_width < 2048 ? intval($tmb_width) : 2048;
$max_height  = isset($tmb_height) && $tmb_height > 0 && $tmb_height < 2048 ? intval($tmb_height) : 2048;
	
if ($max_width > $src_width) $max_width = $src_width;
if ($max_height > $src_height) $max_height = $src_height;

$ratio = 1;

if ($max_width)
	$ratio = $max_width / $src_width;
if ($max_height)
	$ratio = ($max_height / $src_height < $ratio) ? $max_height / $src_height : $ratio;

$width  = intval($src_width * $ratio);
$height = intval($src_height * $ratio);

if (!$width) $width = 1;
if (!$height) $height = 1;

if ($ratio == 1) {
	// Show original image without resizing
	imageOut($CMedia->record['fullpath']);
}

// Check if image with same dimension already cached
for ($i = 1; $i <= media::NUM_CACHED; $i++) {
	if (file_exists(PROJECT_ROOT.media::CACHE_PATH.$CMedia->record['cache_prefix'].$i)) {
		list($cached_width, $cached_height) = getimagesize(PROJECT_ROOT.media::CACHE_PATH.$CMedia->record['cache_prefix'].$i);
		if ($width == $cached_width && $height == $cached_height) {
			imageOut(PROJECT_ROOT.media::CACHE_PATH.$CMedia->record['cache_prefix'].$i);
			return;
		}
	}
}

// Create resized image
switch ($img_type) {
	case 'jpg':
		$src_img = @imagecreatefromjpeg($CMedia->record['fullpath']);
		$img = imagecreatetruecolor($width, $height);
		imagecopyresampled ($img, $src_img, 0,0,0,0, $width, $height, $src_width, $src_height);
		break;
	case 'png':
		$src_img = @imagecreatefrompng($CMedia->record['fullpath']);
		$img = imagecreatetruecolor($width, $height);
		imagecopyresampled ($img, $src_img, 0,0,0,0, $width, $height, $src_width, $src_height);
		break;
	case 'gif':
		$src_img = @imagecreatefromgif($CMedia->record['fullpath']);
		$img = imagecreate($width, $height);
		imagecopyresampled ($img, $src_img, 0,0,0,0, $width, $height, $src_width, $src_height);
		break;
}

// Cache created image
$cur_index = 1;
$last_mod = time();
for ($i = 1; $i <= media::NUM_CACHED; $i++) {
	if (!file_exists(PROJECT_ROOT.media::CACHE_PATH.$CMedia->record['cache_prefix'].$i)) {
		$cur_index = $i;
		break;
	}
	else {
		if (filemtime(PROJECT_ROOT.media::CACHE_PATH.$CMedia->record['cache_prefix'].$i) < $last_mod) {
			$last_mod = filemtime(PROJECT_ROOT.media::CACHE_PATH.$CMedia->record['cache_prefix'].$i);
			$cur_index = $i;
		}
	}
}
$cached_path = PROJECT_ROOT.media::CACHE_PATH.$CMedia->record['cache_prefix'].$cur_index;

switch ($img_type) {
	case 'jpg':
		imagejpeg($img, $cached_path, media::JPEG_QUALITY);
		break;
	case 'png':
		imagepng($img, $cached_path);
		break;
	case 'gif':
		imagegif($img, $cached_path);
		break;
}

imageOut($cached_path);
imagedestroy($img);
imagedestroy($src_img);


function imageOut($path) {
	doConditionalGet(filemtime($path));

	$size = getimagesize($path);

	header('Content-type: '.$size['mime']);
	header("Content-Length: ".filesize($path));

	// Cache control
	header("Last-Modified: " . gmdate("D, d M Y H:i:s",filemtime($path)) . " GMT");
	header("Cache-Control: max-age=10000000, s-maxage=1000000, must-revalidate, proxy-revalidate");
	header("Expires: " . gmdate("D, d M Y H:i:s", time() + (60 * 60 * 24 * 100)) . " GMT");

	readfile($path);
}

// A PHP implementation of conditional get, see http://fishbowl.pastiche.org/archives/001132.html
function doConditionalGet ($timestamp) {
	$last_modified = substr(date('r', $timestamp), 0, -5).'GMT';
	$etag = '"'.md5($last_modified).'"';

	// Send the headers
	header("Last-Modified: $last_modified");
	header("ETag: $etag");

	// See if the client has provided the required headers
	$if_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? stripslashes($_SERVER['HTTP_IF_MODIFIED_SINCE']) : false;
	$if_none_match = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) : false;
	if (!$if_modified_since && !$if_none_match) {
		return false;
	}

	$last_check = strtotime(substr($if_modified_since, 0, -4));

	// At least one of the headers is there - check them
	if ($if_none_match && $if_none_match != $etag) {
		return $last_check; // etag is there but doesn't match
	}
	if ($if_modified_since && $if_modified_since != $last_modified) {
		return $last_check; // if-modified-since is there but doesn't match
	}

	// Nothing has changed since their last request - serve a 304 and exit
	header('HTTP/1.0 304 Not Modified');
	NFW::i()->stop();
}