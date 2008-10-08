<?php
/// Copyright (c) 2004-2008, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
define('NO_SESSION', true);

require ROOT . '/library/includeForBlog.php';
requireModel("blog.feed");
requireModel("blog.entry");

requireStrictBlogURL();
if (false) {
	fetchConfigVal();
}
$cache = new pageCache;
if(!empty($suri['id'])) {
	$cache->name = 'responseRSS_'.$suri['id'];
	if(!$cache->load()) {
		$result = getResponseFeedByEntryId(getBlogId(),$suri['id']);
		if($result !== false) {
			$cache->contents = $result;
			$cache->update();
		}
	}
} else {
	$cache->name = 'responseRSS';
	if(!$cache->load()) {
		$result = getResponseFeedTotal(getBlogId());
		if($result !== false) {
			$cache->contents = $result;
			$cache->update();
		}
	}
}
header('Content-Type: text/xml; charset=utf-8');
echo fireEvent('ViewResponseRSS', $cache->contents);
?>