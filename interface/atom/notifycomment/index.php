<?php
/// Copyright (c) 2004-2008, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
define('NO_SESSION', true);
define('__TEXTCUBE_LOGIN__',true);
if(isset($_GET['loginid'])) $_POST['loginid'] = $_GET['loginid'];
if(isset($_GET['key'])) $_POST['key'] = $_GET['key'];

$IV = array(
	'POST' => array(
		'loginid' => array('email'),
		'key' => array('string')
		)
);
require ROOT . '/library/includeForBlog.php';
requireModel("blog.feed");
requireModel("blog.entry");

requireStrictBlogURL();
if (false) {
	fetchConfigVal();
}
validateAPIKey(getBlogId(),$_POST['loginid'],$_POST['key']);

$cache = new pageCache;
$cache->name = 'commentNotifiedATOM';
if(!$cache->load()) {
	$result = getCommentNotifiedFeedTotal(getBlogId(),'atom');
	if($result !== false) {
		$cache->contents = $result;
		$cache->update();
	}
}
header('Content-Type: text/xml; charset=utf-8');
echo fireEvent('ViewCommentNotifiedATOM', $cache->contents);
?>