<?php
/// Copyright (c) 2004-2007, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
define('ROOT', '../../../../../..');
$IV = array(
	'POST' => array(
		'title' => array('string'),
		'current' => array('int', 0)
	)
);
require ROOT . '/lib/includeForReader.php';
requireStrictRoute();
$result = array('error' => addFeedGroup($blogid, $_POST['title']));
ob_start();
printFeedGroups($blogid, $_POST['current']);
$result['view'] = escapeCData(ob_get_contents());
ob_end_clean();
printRespond($result);
?>