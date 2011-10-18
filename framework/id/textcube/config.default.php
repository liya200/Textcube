<?php
/// Copyright (c) 2004-2011, Needlworks  / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/documents/LICENSE, /documents/COPYRIGHT)

// Define basic signatures.
define('TEXTCUBE_NAME', 'Textcube');
define('TEXTCUBE_VERSION', '2.0 : Alpha 1');
define('TEXTCUBE_REVISION', 'root-main-branch2.0-r30');
define('TEXTCUBE_COPYRIGHT', 'Copyright &copy; 2004-2011. Needlworks / Tatter Network Foundation. All rights reserved. Licensed under the GPL.');
define('TEXTCUBE_HOMEPAGE', 'http://www.textcube.org/');
define('TEXTCUBE_RESOURCE_URL', 'http://resources.textcube.org/2.0');
define('TEXTCUBE_NOTICE_URL','http://feeds.feedburner.com/textcube/');
// Define basic definitions.
define('CRLF', "\r\n");
define('TAB', "	");
define('INT_MAX',2147483647);
if( strstr( PHP_OS, "WIN") !== false ) {
	define('DS', "\\");
} else {
	define('DS', "/");
}
// Define library specific options.
define( "OPENID_LIBRARY_ROOT", ROOT . "/library/contrib/phpopenid/" );
define( "XPATH_LIBRARY_ROOT", ROOT . "/library/contrib/phpxpath/" );
define( "Auth_OpenID_NO_MATH_SUPPORT", 1 );
define( "OPENID_PASSWORD", "-OPENID-" );
define('JQUERY_VERSION','1.5.1');
define('JQUERYMOBILE_VERSION','1.0rc1');

// Define global variable for legacy support.
// This settings are set to default for configuration.
global $database, $service, $blog, $memcache;

$database['server'] = 'localhost';
$database['database'] = '';
$database['username'] = '';
$database['password'] = '';
$database['prefix'] = '';
$service['timeout'] = 3600;
$service['autologinTimeout'] = 3600 * 24 * 14;	// Automatic login for 2 weeks.
$service['type'] = 'single';
$service['domain'] = '';
$service['path'] = '';
$service['language'] = 'ko';
$service['timezone'] = 'Asia/Seoul';
$service['encoding'] = 'UTF-8';
$service['umask'] = 0;
$service['skin'] = 'musicpaper';
if(defined('__TEXTCUBE_NO_FANCY_URL__')) $service['fancyURL'] = 1;
else $service['fancyURL'] = 2;
$service['useEncodedURL'] = false;
$service['debugmode'] = false;
$service['reader'] = true;
$service['flashclipboardpoter'] = true;
$service['allowBlogVisibilitySetting'] = true;
$service['disableEolinSuggestion'] = false;
$service['interface'] = 'simple';	// 'simple' or 'detail'. Default is 'simple' from 2.0
$service['pagecache'] = true;
$service['codecache'] = false;
$service['skincache'] = true;
$service['externalresources'] = false;
$service['favicon_daily_traffic'] = 10;
$service['flashuploader'] = true;
$service['debug_session_dump'] = false;
$service['debug_rewrite_module'] = false;
$service['useNumericURLonRSS'] = false;
$service['forceinstall'] = false;
$service['jqueryURL'] = null;	// You can change this to use external CDNs. (microsoft / google, etc..)
?>
