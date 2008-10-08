<?php
/// Copyright (c) 2004-2008, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
require ROOT . '/library/includeForBlogOwner.php';
requireModel("blog.comment");


if(isset($suri['id'])) {
	$isAjaxRequest = checkAjaxRequest();
	
	if (revertCommentInOwner($blogid, $suri['id']) === true)
		$isAjaxRequest ? respond::ResultPage(0) : header("Location: ".$_SERVER['HTTP_REFERER']);
	else
		$isAjaxRequest ? respond::ResultPage(-1) : header("Location: ".$_SERVER['HTTP_REFERER']);
} else {
	$targets = explode('~*_)', $_POST['targets']);
	for ($i = 0; $i < count($targets); $i++) {
		if ($targets[$i] == '')
			continue;
		revertCommentInOwner($blogid, $targets[$i], false);
	}
	respond::ResultPage(0);
}
?>