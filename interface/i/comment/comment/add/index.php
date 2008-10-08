<?php
/// Copyright (c) 2004-2008, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
define('__TEXTCUBE_IPHONE__', true);
require ROOT . '/library/includeForBlog.php';
requireView('iphoneView');
requireStrictRoute();
$replyId = $suri['id'];
$IV = array(
	'GET' => array(
		"name_$replyId" => array('string', 'default' => null),
		"password_$replyId" => array('string', 'default' => ''),
		"secret_$replyId" => array('string', 'default' => null),
		"homepage_$replyId" => array('string', 'default' => 'http://'),
		"comment_$replyId" => array('string', 'default' => '')
	)
);
if(!Validator::validate($IV))
	respond::NotFoundPage();
list($entryId) = getCommentAttributes($blogid, $replyId, 'entry');
if (!doesHaveOwnership() && empty($_GET["name_$replyId"])) {
	printIphoneErrorPage(_text('Comment write error.'), _text('Please enter your name.'), "$blogURL/comment/comment/$replyId");
} else if (!doesHaveOwnership() && empty($_GET["comment_$replyId"])) {
	printIphoneErrorPage(_text('Comment write error.'), _text('Please enter content.'), "$blogURL/comment/comment/$replyId");
} else {
	$comment = array();
	$comment['entry'] = $entryId;
	$comment['parent'] = $replyId;
	$comment['name'] = empty($_GET["name_$replyId"]) ? '' : $_GET["name_$replyId"];
	$comment['password'] = empty($_GET["password_$replyId"]) ? '' : $_GET["password_$replyId"];
	$comment['homepage'] = empty($_GET["homepage_$replyId"]) || ($_GET["homepage_$replyId"] == 'http://') ? '' : $_GET["homepage_$replyId"];
	$comment['secret'] = empty($_GET["secret_$replyId"]) ? 0 : 1;
	$comment['comment'] = $_GET["comment_$replyId"];
	$comment['ip'] = $_SERVER['REMOTE_ADDR'];
	$result = addComment($blogid, $comment);
	if (in_array($result, array('ip', 'name', 'homepage', 'comment', 'openidonly', 'etc'))) {
		if ($result == 'openidonly') {
			$blockMessage = _text('You have to log in with and OpenID to leave a comment.');
		} else {
			$blockMessage = _textf('Blocked %1', $result);
		}
		printIphoneErrorPage(_text('Comment write blocked.'), $blockMessage, "$blogURL/comment/$entryId");
	} else if ($result === false) {
		printIphoneErrorPage(_text('Comment write error.'), _text('Cannot write comment.'), "$blogURL/comment/$entryId");
	} else {
		setcookie('guestName', $comment['name'], time() + 2592000, $blogURL);
		setcookie('guestHomepage', $comment['homepage'], time() + 2592000, $blogURL);
		printIphoneSimpleMessage(_text('Comment registered.'), _text('Go to comments page'), "$blogURL/comment/$entryId");
	}
}
?>