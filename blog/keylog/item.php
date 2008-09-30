<?php
/// Copyright (c) 2004-2007, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
define('ROOT', '../..');
require ROOT . '/lib/includeForBlog.php';

if (false) {
	fetchConfigVal();
}
if (!$keyword = getKeywordByName($blogid, $suri['value']))
	respondErrorPage();

$keylog = getKeylog($blogid, $keyword['title']);
$skinSetting['keylogSkin'] = fireEvent('setKeylogSkin');
if(!empty($keylog)) {
	require ROOT . '/lib/piece/blog/keylog.php';
} else {
	respondErrorPage();
}
?>