<?php
/// Copyright (c) 2004-2008, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)

// Define basic signatures.
define('TEXTCUBE_NAME', 'Textcube');
define('TEXTCUBE_VERSION', '1.8 : Alpha 4');
define('TEXTCUBE_COPYRIGHT', 'Copyright &copy; 2004-2008. Needlworks / Tatter Network Foundation. All rights reserved. Licensed under the GPL.');
define('TEXTCUBE_HOMEPAGE', 'http://www.textcube.org/');
define('TEXTCUBE_RESOURCE_URL', 'http://resources.textcube.org/1.8');
define('TEXTCUBE_SYNC_URL', 'http://ping.eolin.com/');
define('CRLF', "\r\n");
define('TAB', "	");
define('INT_MAX',2147483647);
if( strstr( PHP_OS, "WIN") !== false ) {
	define('DS', "\\");
} else {
	define('DS', "/");
}
// Define global variable.
global $database, $service, $blog, $memcache;

$database['server'] = 'localhost';
$database['database'] = '';
$database['username'] = '';
$database['password'] = '';
$database['prefix'] = '';
$service['timeout'] = 3600;
$service['type'] = 'single';
$service['domain'] = '';
$service['path'] = '';
$service['language'] = 'ko';
$service['timezone'] = 'Asia/Seoul';
$service['encoding'] = 'EUC-KR';
$service['umask'] = 0;
$service['skin'] = 'coolant';
if(defined('__TEXTCUBE_NO_FANCY_URL__')) $service['fancyURL'] = 1;
else $service['fancyURL'] = 2;
$service['useEncodedURL'] = false;
$service['debugmode'] = false;
$service['reader'] = true;
$service['flashclipboardpoter'] = true;
$service['allowBlogVisibilitySetting'] = true;
$service['disableEolinSuggestion'] = false;
$service['effect'] = false;
$service['interface'] = 'detail';	// 'simple' or 'detail'. Default is 'detail'
$service['pagecache'] = true;
$service['codecache'] = false;
$service['skincache'] = true;
$service['externalresources'] = false;
$service['favicon_daily_traffic'] = 10;
$service['flashuploader'] = true;
$service['debug_session_dump'] = false;
$service['debug_rewrite_module'] = false;
$service['useNumericURLonRSS'] = false;
// Map port setting.
if (@is_numeric($_SERVER['SERVER_PORT']) && ($_SERVER['SERVER_PORT'] != 80) && ($_SERVER['SERVER_PORT'] != 443))
	$service['port'] = $_SERVER['SERVER_PORT'];

// Include installation configuration.
$service['session_cookie_path'] = '/';
if(!defined('__TEXTCUBE_SETUP__')) @include ROOT . '/config.php';

// Set resource path.
if($service['externalresources']) {
	if(isset($service['resourceURL']) && !empty($service['resourceURL'])) 
		$service['resourcepath'] = $service['resourceURL'];
	else 
		$service['resourcepath'] = TEXTCUBE_RESOURCE_URL;
} else {
	$service['resourcepath'] = $service['path'].'/resources';
}

// Database setting.
if(isset($service['dbms'])) {
	if($service['dbms'] == 'mysql' && class_exists('mysqli')) $service['dbms'] = 'mysqli';
}

// Debug mode configuration.
if($service['debugmode'] == true) {
	if(isset($service['dbms'])) {
		switch($service['dbms']) {
			case 'mysqli':         requireLibrary("components/Needlworks.Debug.MySQLi"); break;
			case 'mysql': default: requireLibrary("components/Needlworks.Debug.MySQL"); break;
		}
	} else requireLibrary("components/Needlworks.Debug.MySQL"); 
}

// Session cookie patch.
if(!empty($service['domain']) && strstr( $_SERVER['HTTP_HOST'], $service['domain'] ) ) {
	$service['session_cookie_domain'] = $service['domain'];
} else {
	$service['session_cookie_domain'] = $_SERVER['HTTP_HOST'];
}

// Basic POST/GET variable validation.
if (isset($IV)) {
	if (!Validator::validate($IV)) {
		header('HTTP/1.1 404 Not Found');
		exit;
	}
}
// Basic SERVER variable validation.
$basicIV = array(
	'SCRIPT_NAME' => array('string'),
	'REQUEST_URI' => array('string'),
	'REDIRECT_URL' => array('string', 'mandatory' => false)
);
Validator::validateArray($_SERVER, $basicIV);
if(isset($accessInfo)) {
	$basicIV = array(
		'fullpath' => array('string'),
		'input'    => array('string'),
		'position' => array('string'),
		'root'     => array('string'),
		'input'    => array('string', 'mandatory' => false)
	);
	$accessInfo['fullpath'] = urldecode($accessInfo['fullpath']);
	Validator::validateArray($accessInfo, $basicIV);
}
?>