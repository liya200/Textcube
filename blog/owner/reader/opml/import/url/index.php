<?php
/// Copyright (c) 2004-2006, Tatter & Company / Tatter & Friends.
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
define('ROOT', '../../../../../..');
$VI = array(
	'POST' => array(
		'url' => array('url')
	)
);
require ROOT . '/lib/includeForOwner.php';
requireStrictRoute();
set_time_limit(60);
$result = importOPMLFromURL($owner, $_POST['url']);
printRespond($result);
?>