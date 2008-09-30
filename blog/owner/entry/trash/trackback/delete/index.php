<?php
/// Copyright (c) 2004-2007, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
define('ROOT', '../../../../../..');
require ROOT . '/lib/includeForBlogOwner.php';
requireModel("blog.trackback");

$targets = explode('~*_)', $_POST['targets']);
for ($i = 0; $i < count($targets); $i++) {
	if ($targets[$i] == '')
		continue;
	deleteTrackback($blogid, $targets[$i]);
}
respondResultPage(0);
?>