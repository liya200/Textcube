<?php
/// Copyright (c) 2004-2018, Needlworks  / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/documents/LICENSE, /documents/COPYRIGHT)

require_once ROOT . '/library/preprocessor.php';

$icon_path = __TEXTCUBE_ATTACH_DIR__."/$blogid/index.gif";
if( !file_exists($icon_path) ) {
	$icon_path = ROOT . '/image/icon_blogIcon_default.png';
}

require ROOT . '/interface/favicon.ico.php';
?>
