<?php
/// Copyright (c) 2004-2008, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)

require ROOT . '/library/includeForBlogOwner.php';

requireModel('blog.trash');
requireModel('blog.trackback');
requireModel('blog.sidebar');
requireLibrary('blog.skin');

requireStrictRoute();
$blogid = getBlogId();
$entryId = trashTrackback($blogid, $suri['id']);
if ($entryId !== false) {
	$skin = new Skin($skinSetting['skin']);
	
	$trackbackCount = getTrackbackCount($blogid, $entryId);
	list($tempTag, $trackbackCountContent) = getTrackbackCountPart($trackbackCount, $skin);
	$recentTrackbackContent = getRecentTrackbacksView(getRecentTrackbacks($blogid), $skin->recentTrackback);
	$entry = array();
	$entry['id'] = $entryId;
	$entry['slogan'] = getSloganById($blogid, $entry['id']);
	$trackbackListContent = getTrackbacksView($entry, $skin, true);
}
if ($trackbackListContent === false)
	respond::PrintResult(array('error' => 1));
else
	respond::PrintResult(array('error' => 0, 'trackbackList' => $trackbackListContent, 'trackbackCount' => $trackbackCountContent, 'recentTrackbacks' => $recentTrackbackContent));
?>