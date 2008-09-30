<?php
/// Copyright (c) 2004-2007, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)

function decorateComment( & $comment )
{
	$authorized = doesHaveOwnership();
	$comment['hidden'] = false;
	$comment['name'] = htmlspecialchars($comment['name']);
	$comment['comment'] = htmlspecialchars($comment['comment']);
	if ($comment['secret'] == 1) {
		if($authorized) {
			$comment['comment'] = '<span class="hiddenCommentTag_content">' . _text('[비밀댓글]') . '</span> ' . $comment['comment'];
		} else {
			if( !fireEvent('ShowSecretComment', false, $comment) ) {
				$comment['hidden'] = true;
				$comment['name'] = '<span class="hiddenCommentTag_name">' . _text('비밀방문자') . '</span>';
				$comment['homepage'] = '';
				$comment['comment'] = _text('관리자만 볼 수 있는 댓글입니다.');
			} else {
				$comment['name'] = '<span class="hiddenCommentTag_name">' . _text('비밀방문자') . '</span>'. $comment['name'];
			}
		}
	}
}

function getCommentsWithPagingForOwner($blogid, $category, $name, $ip, $search, $page, $count) {
	global $database;
	
	$postfix = '';
	
	$sql = "SELECT c.*, e.title, c2.name parentName 
		FROM {$database['prefix']}Comments c 
		LEFT JOIN {$database['prefix']}Entries e ON c.blogid = e.blogid AND c.entry = e.id AND e.draft = 0 
		LEFT JOIN {$database['prefix']}Comments c2 ON c.parent = c2.id AND c.blogid = c2.blogid 
		WHERE c.blogid = $blogid AND c.isFiltered = 0";
	if ($category > 0) {
		$categories = DBQuery::queryColumn("SELECT id FROM {$database['prefix']}Categories WHERE parent = $category");
		array_push($categories, $category);
		$sql .= ' AND e.category IN (' . implode(', ', $categories) . ')';
		$postfix .= '&category=' . rawurlencode($category);
	} else
		$sql .= ' AND e.category >= 0';
	if (!empty($name)) {
		$sql .= ' AND c.name = \'' . mysql_tt_escape_string($name) . '\'';
		$postfix .= '&name=' . rawurlencode($name);
	}
	if (!empty($ip)) {
		$sql .= ' AND c.ip = \'' . mysql_tt_escape_string($ip) . '\'';
		$postfix .= '&ip=' . rawurlencode($ip);
	}
	if (!empty($search)) {
		$search = escapeMysqlSearchString($search);
		$sql .= " AND (c.name LIKE '%$search%' OR c.homepage LIKE '%$search%' OR c.comment LIKE '%$search%')";
		$postfix .= '&search=' . rawurlencode($search);
	}
	
	$sql .= ' ORDER BY c.written DESC';
	list($comments, $paging) = fetchWithPaging($sql, $page, $count);
	if (strlen($postfix) > 0) {
		$postfix .= '&withSearch=on';
		$paging['postfix'] .= $postfix;
	}
	
	return array($comments, $paging);
}

function getCommentsNotifiedWithPagingForOwner($blogid, $category, $name, $ip, $search, $page, $count) {
	global $database;
	if (empty($name) && empty($ip) && empty($search)) {
		$sql = "SELECT 
					c.*, 
					csiteinfo.title AS siteTitle,
					csiteinfo.name AS nickname,
					csiteinfo.url AS siteUrl,
					csiteinfo.modified AS siteModified
				FROM 
					{$database['prefix']}CommentsNotified c
				LEFT JOIN 
						{$database['prefix']}CommentsNotifiedSiteInfo csiteinfo ON c.siteId = csiteinfo.id  
				WHERE c.blogid = $blogid AND (c.parent is null)";
		$sql .= ' ORDER BY c.modified DESC';
	} else {
		if (!empty($search)) {
			$search = escapeMysqlSearchString($search);
		}
				
		$preQuery = "SELECT parent FROM {$database['prefix']}CommentsNotified WHERE blogid = $blogid AND parent is NOT NULL";
		if (!empty($name))
			$preQuery .= ' AND name = \''. mysql_tt_escape_string($name) . '\' ';
		if (!empty($ip))
			$preQuery .= ' AND ip = \''. mysql_tt_escape_string($ip) . '\' ';
		if (!empty($search)) {
			$preQuery .= " AND ((name LIKE '%$search%') OR (homepage LIKE '%$search%') OR (comment LIKE '%$search%'))";
		}
	
		$childListTemp = array_unique(DBQuery::queryColumn($preQuery));
		$childList = array();
		foreach ($childListTemp as $item) 
			if(!is_null($item)) array_push($childList, $item);
		$childListStr = (count($childList) == 0) ? '' : ('OR c.id IN ( ' . implode(', ',$childList) . ' ) ') ;
		
		$sql = "SELECT 
				c.*, 
				csiteinfo.title AS siteTitle,
				csiteinfo.name AS nickname,
				csiteinfo.url AS siteUrl,
				csiteinfo.modified AS siteModified
			FROM 
				{$database['prefix']}CommentsNotified c 
				LEFT JOIN 
				{$database['prefix']}CommentsNotifiedSiteInfo csiteinfo ON c.siteId = csiteinfo.id  
			WHERE c.blogid = $blogid AND (c.parent is null) ";
		if (!empty($name))
			$sql .= ' AND ( c.name = \'' . mysql_tt_escape_string($name) . '\') ' ;
		if (!empty($ip))
			$sql .= ' AND ( c.ip = \'' . mysql_tt_escape_string($ip) . '\') ';
		if (!empty($search)) {
			$sql .= " AND ((c.name LIKE '%$search%') OR (c.homepage LIKE '%$search%') OR (c.comment LIKE '%$search%')) ";
		}
		$sql .= $childListStr . ' ORDER BY c.modified DESC ';
	}
	return fetchWithPaging($sql, $page, $count);
}

function getCommentCommentsNotified($parent) {
	global $database;
	$comments = array();
	$authorized = doesHaveOwnership();
	$sql = "SELECT
				c.*, 
				csiteinfo.title AS siteTitle,
				csiteinfo.name AS nickname,
				csiteinfo.url AS siteUrl,
				csiteinfo.modified AS siteModified
			FROM 
				{$database['prefix']}CommentsNotified c 
				LEFT JOIN 
				{$database['prefix']}CommentsNotifiedSiteInfo csiteinfo ON c.siteId = csiteinfo.id  
			WHERE c.blogid = ".getBlogId()." AND c.parent=$parent";
	$sql .= ' ORDER BY c.written ASC';
	if ($result = DBQuery::query($sql)) {
		while ($comment = mysql_fetch_array($result)) {
			if (($comment['secret'] == 1) && !$authorized) {
				if( !fireEvent('ShowSecretComment', false, $comment) ) {
					$comment['name'] = '';
					$comment['homepage'] = '';
					$comment['comment'] = _text('관리자만 볼 수 있는 댓글입니다.');
				}
			}
			array_push($comments, $comment);
		}
	}
	return $comments;
}

function getCommentsWithPagingForGuestbook($blogid, $page, $count) {
	global $database;
	$sql = "SELECT * FROM {$database['prefix']}Comments WHERE blogid = $blogid";
	$sql .= ' AND entry = 0 AND parent is null AND isFiltered = 0';
	$sql .= ' ORDER BY written DESC';
	return fetchWithPaging($sql, $page, $count);
}

function getCommentAttributes($blogid, $id, $attributeNames) {
	global $database;
	return DBQuery::queryRow("select $attributeNames from {$database['prefix']}Comments where blogid = $blogid and id = $id");
}

function getComments($entry) {
	global $database;
	$comments = array();
	$authorized = doesHaveOwnership();
	$aux = ($entry == 0 ? 'ORDER BY written DESC' : 'order by id ASC');
	$sql = "select * from {$database['prefix']}Comments where blogid = ".getBlogId()." and entry = $entry and parent is null and isFiltered = 0 $aux";
	if ($result = DBQuery::query($sql)) {
		while ($comment = mysql_fetch_array($result)) {
			if (($comment['secret'] == 1) && !$authorized) {
				if( !fireEvent('ShowSecretComment', false, $comment) ) {
					$comment['name'] = '';
					$comment['homepage'] = '';
					$comment['comment'] = _text('관리자만 볼 수 있는 댓글입니다.');
				}
			}
			array_push($comments, $comment);
		}
	}
	return $comments;
}

function getCommentComments($parent) {
	global $database;
	$comments = array();
	$authorized = doesHaveOwnership();
	if ($result = DBQuery::query("select * from {$database['prefix']}Comments where blogid = ".getBlogId()." and parent = $parent and isFiltered = 0 order by id")) {
		while ($comment = mysql_fetch_array($result)) {
			if (($comment['secret'] == 1) && !$authorized) {
				if( !fireEvent('ShowSecretComment', false, $comment) ) {
					$comment['name'] = '';
					$comment['homepage'] = '';
					$comment['comment'] = _text('관리자만 볼 수 있는 댓글입니다.');
				}
			}
			array_push($comments, $comment);
		}
	}
	return $comments;
}

function isCommentWriter($blogid, $commentId) {
	global $database;
	if (!doesHaveMembership())
		return false;
	$result = DBQuery::query("select replier 
			FROM {$database['prefix']}Comments 
			WHERE blogid = $blogid and id = $commentId and replier = " . getUserId());
	return mysql_num_rows($result) > 0 ? true : false;
}

function getComment($blogid, $id, $password) {
	global $database;
	$sql = "select * from {$database['prefix']}Comments where blogid = $blogid and id = $id";
	if (!doesHaveOwnership()) {
		if (doesHaveMembership())
			$sql .= ' and replier = ' . getUserId();
		else
			$sql .= ' and password = \'' . md5($password) . '\'';
	}
	if ($result = DBQuery::query($sql))
		return mysql_fetch_array($result);
	return false;
}

function getCommentList($blogid, $search) {
	global $database;
	$list = array('title' => "$search", 'items' => array());
	$search = escapeMysqlSearchString($search);
	$authorized = doesHaveOwnership() ? '' : 'AND c.secret = 0 AND (ct.visibility > 1 OR e.category = 0)';
	if ($result = DBQuery::query("SELECT c.id, c.entry, c.parent, c.name, c.comment, c.written, e.slogan
		FROM {$database['prefix']}Comments c
		LEFT JOIN {$database['prefix']}Entries e ON c.entry = e.id AND c.blogid = e.blogid
		LEFT JOIN {$database['prefix']}Categories ct ON ct.id = e.category AND ct.blogid = c.blogid
		WHERE c.entry > 0 
			AND c.blogid = $blogid $authorized 
			and c.isFiltered = 0 
			and (c.comment like '%$search%' OR c.name like '%$search%')")) {
		while ($comment = mysql_fetch_array($result))
			array_push($list['items'], $comment);
	}
	return $list;
}

function updateCommentsOfEntry($blogid, $entryId) {
	global $database;
	requireComponent('Needlworks.Cache.PageCache');
	$commentCount = DBQuery::queryCell("SELECT COUNT(*) From {$database['prefix']}Comments WHERE blogid = $blogid AND entry = $entryId AND isFiltered = 0");
	DBQuery::query("UPDATE {$database['prefix']}Entries SET comments = $commentCount WHERE blogid = $blogid AND id = $entryId");
	if($entryId >=0) CacheControl::flushEntry($entryId);
	return $commentCount;
}

function sendCommentPing($entryId, $permalink, $name, $homepage) {
	global $database, $blog;
	$blogid = getBlogId();
	if($slogan = DBQuery::queryCell("SELECT slogan FROM {$database['prefix']}Entries WHERE blogid = $blogid AND id = $entryId AND draft = 0 AND visibility = 3 AND acceptComment = 1")) {
		requireComponent('Eolin.PHP.Core');
		requireComponent('Eolin.PHP.XMLRPC');
		$rpc = new XMLRPC();
		$rpc->url = TEXTCUBE_SYNC_URL;
		$summary = array(
			'permalink' => $permalink,
			'name' => $name,
			'homepage' => $homepage
		);
		$rpc->async = true;
		$rpc->call('sync.comment', $summary);
	}
}

function addComment($blogid, & $comment) {
	global $database, $user, $blog, $defaultURL;
	
	$filtered = 0;
	
	if (!doesHaveOwnership()) {
		requireComponent('Textcube.Data.Filter');
		if (Filter::isFiltered('ip', $comment['ip'])) {
			$blockType = "ip";
			$filtered = 1;
		} else if (Filter::isFiltered('name', $comment['name'])) {
			$blockType = "name";
			$filtered = 1;
		} else if (Filter::isFiltered('url', $comment['homepage'])) {
			$blockType = "homepage";
			$filtered = 1;
		} elseif (Filter::isFiltered('content', $comment['comment'])) {
			$blockType = "comment";
			$filtered = 1;
		} else if (!fireEvent('AddingComment', true, $comment)) {
			$blockType = "etc";
			$filtered = 1;
		}
	}

	$comment['homepage'] = stripHTML($comment['homepage']);
	$comment['name'] = mysql_lessen($comment['name'], 80);
	$comment['homepage'] = mysql_lessen($comment['homepage'], 80);
	$comment['comment'] = mysql_lessen($comment['comment'], 65535);
	
	if (!doesHaveOwnership() && $comment['entry'] != 0) {
		$result = DBQuery::query("SELECT * FROM {$database['prefix']}Entries WHERE blogid = $blogid AND id = {$comment['entry']} AND draft = 0 AND visibility > 0 AND acceptComment = 1");
		if (mysql_num_rows($result) == 0)
			return false;
	}
	$parent = $comment['parent'] == null ? 'null' : "'{$comment['parent']}'";
	if ($user !== null) {
		$comment['replier'] = getUserId();
		$name = mysql_tt_escape_string($user['name']);
		$password = '';
		$homepage = mysql_tt_escape_string($user['homepage']);
	} else {
		$comment['replier'] = 'null';
		$name = mysql_tt_escape_string($comment['name']);
		$password = empty($comment['password']) ? '' : md5($comment['password']);
		$homepage = mysql_tt_escape_string($comment['homepage']);
	}
	$comment0 = mysql_tt_escape_string($comment['comment']);
	$filteredAux = ($filtered == 1 ? "UNIX_TIMESTAMP()" : 0);
	$result = DBQuery::query("INSERT INTO {$database['prefix']}Comments 
		(blogid,replier,entry,parent,name,password,homepage,secret,comment,ip,written,isFiltered)
		VALUES (
			$blogid,
			{$comment['replier']},
			{$comment['entry']},
			$parent,
			'$name',
			'$password',
			'$homepage',
			{$comment['secret']},
			'$comment0',
			'{$comment['ip']}',
			UNIX_TIMESTAMP(),
			$filteredAux
		)");
	if ($result && (mysql_affected_rows() > 0)) {
		$id = mysql_insert_id();
		if ($parent != 'null' && $comment['secret'] < 1) {
			DBQuery::execute("
				INSERT INTO 
					`{$database['prefix']}CommentsNotifiedQueue` 
					( `blogid` , `commentId` , `sendStatus` , `checkDate` , `written` ) 
				VALUES 
					($blogid , '" . $id . "', '0', '0', UNIX_TIMESTAMP());");
		}
		updateCommentsOfEntry($blogid, $comment['entry']);
		fireEvent($comment['entry'] ? 'AddComment' : 'AddGuestComment', $id, $comment);
		if ($filtered == 1)
			return $blockType;
		else
			return $id;
	}
	return false;
}

function updateComment($blogid, $comment, $password) {
	global $database, $user;

	if (!doesHaveOwnership()) {
		// if filtered, only block and not send to trash
		requireComponent('Textcube.Data.Filter');
		if (Filter::isFiltered('ip', $comment['ip']))
			return 'blocked';
		if (Filter::isFiltered('name', $comment['name']))
			return 'blocked';
		if (Filter::isFiltered('url', $comment['homepage']))
			return 'blocked';
		if (Filter::isFiltered('content', $comment['comment']))
			return 'blocked';
		if (!fireEvent('ModifyingComment', true, $comment))
			return 'blocked';
	}
	
	$comment['homepage'] = stripHTML($comment['homepage']);
	$comment['name'] = mysql_lessen($comment['name'], 80);
	$comment['homepage'] = mysql_lessen($comment['homepage'], 80);
	$comment['comment'] = mysql_lessen($comment['comment'], 65535);
	
	$setPassword = '';
	if ($user !== null) {
		$comment['replier'] = getUserId();
		$name = mysql_tt_escape_string($user['name']);
		$setPassword = 'password = \'\',';
		$homepage = mysql_tt_escape_string($user['homepage']);
	} else {
		$name = mysql_tt_escape_string($comment['name']);
		if ($comment['password'] !== true)
			$setPassword = 'password = \'' . (empty($comment['password']) ? '' : md5($comment['password'])) . '\', ';
		$homepage = mysql_tt_escape_string($comment['homepage']);
	}
	$comment0 = mysql_tt_escape_string($comment['comment']);
	
	$guestcomment = false;
	if (DBQuery::queryExistence("SELECT * from {$database['prefix']}Comments WHERE blogid = $blogid AND id = {$comment['id']} AND replier IS NULL")) {
		$guestcomment = true;
	}
	
	$wherePassword = '';
	if (!doesHaveOwnership()) {
		if ($guestcomment == false) {
			if (!doesHaveMembership())
				return false;
			$wherePassword = ' and replier = ' . getUserId();
		}
		else
		{
			$wherePassword = ' and password = \'' . md5($password) . '\'';
		}
	}
	
	$replier = is_null($comment['replier']) ? 'null' : "'{$comment['replier']}'";
	
	$result = DBQuery::query("update {$database['prefix']}Comments
				set
					name = '$name',
					$setPassword
					homepage = '$homepage',
					secret = {$comment['secret']},
					comment = '$comment0',
					ip = '{$comment['ip']}',
					written = UNIX_TIMESTAMP(),
					isFiltered = {$comment['isFiltered']},
					replier = {$replier}
				where blogid = $blogid and id = {$comment['id']} $wherePassword");
	return $result ? true : false;
}

function deleteComment($blogid, $id, $entry, $password) {
	global $database;
	
	if (!is_numeric($id)) return false;
	if (!is_numeric($entry)) return false;
		
	$guestcomment = false;
	if (DBQuery::queryExistence("SELECT * from {$database['prefix']}Comments WHERE blogid = $blogid AND id = $id AND replier IS NULL")) {
		$guestcomment = true;
	}
	
	$wherePassword = '';
	
	$sql = "delete from {$database['prefix']}Comments where blogid = $blogid and id = $id and entry = $entry";
	if (!doesHaveOwnership()) {
		if ($guestcomment == false) {
			if (!doesHaveMembership()) {
				return false;
			}
			$wherePassword = ' and replier = ' . getUserId();
		}
		else
		{
			$wherePassword = ' and password = \'' . md5($password) . '\'';
		}
	}
	$result = DBQuery::query($sql . $wherePassword);
	if (mysql_affected_rows() > 0) {
		DBQuery::query("delete from {$database['prefix']}Comments where blogid = $blogid and parent = $id");
		updateCommentsOfEntry($blogid, $entry);
		return true;
	}
	return false;
}

function trashComment($blogid, $id, $entry, $password) {
	global $database;
	if (!doesHaveOwnership()) {
		return false;
	}
	if (!is_numeric($id)) return false;
	if (!is_numeric($entry)) return false;
	$sql = "update {$database['prefix']}Comments set isFiltered = UNIX_TIMESTAMP() where blogid = $blogid and id = $id and entry = $entry";
	$result = DBQuery::query($sql);
	$affected = mysql_affected_rows();
	$sql = "update {$database['prefix']}Comments set isFiltered = UNIX_TIMESTAMP() where blogid = $blogid and parent = $id and entry = $entry";
	$result = DBQuery::query($sql);
	if ($affected + mysql_affected_rows() > 0) {
		updateCommentsOfEntry($blogid, $entry);
		return true;
	}
	return false;
}

function revertComment($blogid, $id, $entry, $password) {
	// not used, so
	return false;	
	global $database;
	if (!doesHaveOwnership()) {
		return false;
	}
	if (!is_numeric($id)) return false;
	if (!is_numeric($entry)) return false;
	$sql = "update {$database['prefix']}Comments set isFiltered = 0 where blogid = $blogid and id = $id and entry = $entry";
	$result = DBQuery::query($sql);
	if (mysql_affected_rows() > 0) {
		updateCommentsOfEntry($blogid, $entry);
		return true;
	}
	return false;
}

function getRecentComments($blogid,$count = false,$isGuestbook = false) {
	global $skinSetting, $database;
	$comments = array();
	$sql = doesHaveOwnership() ? "SELECT r.*, e.slogan
		FROM 
			{$database['prefix']}Comments r
			LEFT JOIN {$database['prefix']}Entries e ON r.blogid = e.blogid AND r.entry = e.id
		WHERE 
			r.blogid = $blogid".($isGuestbook != false ? " AND r.entry=0" : " AND r.entry>0")." AND r.isFiltered = 0 
		ORDER BY 
			r.written 
		DESC LIMIT ".($count != false ? $count : $skinSetting['commentsOnRecent']) :
		"SELECT r.*, e.slogan
		FROM 
			{$database['prefix']}Comments r
			LEFT JOIN {$database['prefix']}Entries e ON r.blogid = e.blogid AND r.entry = e.id
			LEFT JOIN {$database['prefix']}Categories c ON e.blogid = c.blogid AND e.category = c.id
		WHERE 
			r.blogid = $blogid AND e.draft = 0 AND e.visibility >= 2 AND (c.visibility > 1 OR e.category = 0) "
			.($isGuestbook != false ? " AND r.entry = 0" : " AND r.entry > 0")." AND r.isFiltered = 0 
		ORDER BY 
			r.written 
		DESC LIMIT 
			".($count != false ? $count : $skinSetting['commentsOnRecent']);
	if ($result = DBQuery::query($sql)) {
		while ($comment = mysql_fetch_array($result)) {
			if (($comment['secret'] == 1) && !doesHaveOwnership()) {
				if( !fireEvent('ShowSecretComment', false, $comment) ) {
					$comment['name'] = '';
					$comment['homepage'] = '';
					$comment['comment'] = _text('관리자만 볼 수 있는 댓글입니다.');
				}
			}
			array_push($comments, $comment);
		}
	}
	return $comments;
}

function getRecentGuestbook($blogid,$count = false) {
	return getRecentComments($blogid,$count,true);
}

function getGuestbookPageById($blogid, $id) {
	global $database, $skinSetting;
	$totalGuestbookId = DBQuery::queryColumn("SELECT id
		FROM {$database['prefix']}Comments
		WHERE
			blogid = $blogid AND entry = 0 AND isFiltered = 0 AND parent is null
		ORDER BY
			written DESC");
	$order = array_search($id, $totalGuestbookId);
	if($order == false) {
		$parentCommentId = DBQuery::queryCell("SELECT parent
			FROM {$database['prefix']}Comments
			WHERE
				blogid = $blogid AND entry = 0 AND isFiltered = 0 AND id = $id");
		if($parentCommentId != false) {
			$order = array_search($parentCommentId, $totalGuestbookId);
		} else {
			return false;
		}
	}
	return intval($order / $skinSetting['commentsOnGuestbook'])+1;
}

function deleteCommentInOwner($blogid, $id) {
	global $database;
	if (!is_numeric($id)) return false;
	$entryId = DBQuery::queryCell("SELECT entry FROM {$database['prefix']}Comments WHERE blogid = $blogid AND id = $id");
	$result = DBQuery::query("DELETE FROM {$database['prefix']}Comments WHERE blogid = $blogid AND id = $id");
	if ($result && (mysql_affected_rows() == 1)) {
		if (DBQuery::query("DELETE FROM {$database['prefix']}Comments WHERE blogid = $blogid AND parent = $id")) {
			updateCommentsOfEntry($blogid, $entryId);
			return true;
		}
	}
	return false;
}

function trashCommentInOwner($blogid, $id) {
	global $database;
	if (!is_numeric($id)) return false;
	$entryId = DBQuery::queryCell("SELECT entry FROM {$database['prefix']}Comments WHERE blogid = $blogid AND id = $id");
	$result = DBQuery::query("UPDATE {$database['prefix']}Comments SET isFiltered = UNIX_TIMESTAMP() WHERE blogid = $blogid AND id = $id");
	if ($result && (mysql_affected_rows() == 1)) {
		if (DBQuery::query("UPDATE {$database['prefix']}Comments SET isFiltered = UNIX_TIMESTAMP() WHERE blogid = $blogid AND parent = $id")) {
			updateCommentsOfEntry($blogid, $entryId);
			return true;
		}
	}
	return false;
}

function revertCommentInOwner($blogid, $id) {
	global $database;
	if (!is_numeric($id)) return false;
	$entryId = DBQuery::queryCell("SELECT entry FROM {$database['prefix']}Comments WHERE blogid = $blogid AND id = $id");
	$parent = DBQuery::queryCell("SELECT parent FROM {$database['prefix']}Comments WHERE blogid = $blogid AND id = $id");
	$result = DBQuery::query("UPDATE {$database['prefix']}Comments SET isFiltered = 0 WHERE blogid = $blogid AND id = $id");
	if ($result && (mysql_affected_rows() == 1)) {
		if (is_null($parent) || DBQuery::query("UPDATE {$database['prefix']}Comments SET isFiltered = 0 WHERE blogid = $blogid AND id = $parent")) {
			updateCommentsOfEntry($blogid, $entryId);
			return true;
		}
	}
	return false;
}

function deleteCommentNotifiedInOwner($blogid, $id) {
	global $database;
	if (!is_numeric($id)) return false;
	
	fireEvent('DeleteCommentNotified', $id);
	
	$entryId = DBQuery::queryCell("SELECT entry FROM {$database['prefix']}CommentsNotified WHERE blogid = $blogid AND id = $id");
	$result = DBQuery::query("DELETE FROM {$database['prefix']}CommentsNotified WHERE blogid = $blogid AND id = $id");
	if ($result && (mysql_affected_rows() == 1)) {
		if (DBQuery::query("DELETE FROM {$database['prefix']}CommentsNotified WHERE blogid = $blogid AND parent = $id")) {
			updateCommentsOfEntry($blogid, $entryId);
			return true;
		}
	}
	return false;
}

function notifyComment() {
	global $database, $service, $blog, $defaultURL;
	$blogid = getBlogId();
	$sql = "
			select
				CN.*,
				CNQ.id AS queueId, 
				CNQ.commentId AS commentId, 
				CNQ.sendStatus AS sendStatus, 
				CNQ.checkDate AS checkDate, 
				CNQ.written  AS queueWritten
			from
				{$database['prefix']}CommentsNotifiedQueue AS CNQ
			LEFT JOIN
				{$database['prefix']}Comments AS CN ON CNQ.commentId = CN.id
			where
				CNQ.sendStatus = '0'
				and CN.parent is not null
			ORDER BY CNQ.id ASC
			limit 0, 1
		";
	$queue = DBQuery::queryRow($sql);
	if (empty($queue) && empty($queue['queueId'])) {
		//DBQuery::execute("DELETE FROM {$database['prefix']}CommentsNotifiedQueue WHERE id={$queue['queueId']}");
		return false;
	}
	$comments = (DBQuery::queryRow("SELECT * FROM {$database['prefix']}Comments WHERE blogid = $blogid AND id = {$queue['commentId']}"));
	if (empty($comments['parent']) || $comments['secret'] == 1) {
		DBQuery::execute("DELETE FROM {$database['prefix']}CommentsNotifiedQueue WHERE id={$queue['queueId']}");
		return false;
	}
	$parentComments = (DBQuery::queryRow("SELECT * FROM {$database['prefix']}Comments WHERE blogid = $blogid AND id = {$comments['parent']}"));
	if (empty($parentComments['homepage'])) {
		DBQuery::execute("DELETE FROM {$database['prefix']}CommentsNotifiedQueue WHERE id={$queue['queueId']}");
		return false;
	}
	$entry = (DBQuery::queryRow("SELECT * FROM {$database['prefix']}Entries WHERE blogid = $blogid AND id={$comments['entry']}"));
	if( $entry['id'] == 0) {
		$r1_comment_check_url = rawurlencode("$defaultURL/guestbook#comment" . $parentComments['id']);
		$r2_comment_check_url = rawurlencode("$defaultURL/guestbook#comment" . $comments['id']);
	}else{
		$r1_comment_check_url = rawurlencode("$defaultURL/" . ($blog['useSlogan'] ? "entry/{$entry['slogan']}" : $entry['id']) . "#comment" . $parentComments['id']);
		$r2_comment_check_url = rawurlencode("$defaultURL/" . ($blog['useSlogan'] ? "entry/{$entry['slogan']}" : $entry['id']) . "#comment" . $comments['id']);
	}
		
	$data = "url=" . rawurlencode($defaultURL) . "&mode=fb" . "&s_home_title=" . rawurlencode($blog['title']) . "&s_post_title=" . rawurlencode($entry['title']) . "&s_name=" . rawurlencode($comments['name']) . "&s_no=" . rawurlencode($comments['entry']) . "&s_url=" . rawurlencode("$defaultURL/" . ($blog['useSlogan'] ? "entry/{$entry['slogan']}" : $entry['id'])) . "&r1_name=" . rawurlencode($parentComments['name']) . "&r1_no=" . rawurlencode($parentComments['id']) . "&r1_pno=" . rawurlencode($comments['entry']) . "&r1_rno=0" . "&r1_homepage=" . rawurlencode($parentComments['homepage']) . "&r1_regdate=" . rawurlencode($parentComments['written']) . "&r1_url=" . $r1_comment_check_url. "&r2_name=" . rawurlencode($comments['name']) . "&r2_no=" . rawurlencode($comments['id']) . "&r2_pno=" . rawurlencode($comments['entry']) . "&r2_rno=" . rawurlencode($comments['parent']) . "&r2_homepage=" . rawurlencode($comments['homepage']) . "&r2_regdate=" . rawurlencode($comments['written']) . "&r2_url=" . $r2_comment_check_url . "&r1_body=" . rawurlencode($parentComments['comment']) . "&r2_body=" . rawurlencode($comments['comment']);
	requireComponent('Eolin.PHP.HTTPRequest');
	if (strpos($parentComments['homepage'], "http://") === false) {
		$homepage = 'http://' . $parentComments['homepage'];
	} else {
		$homepage = $parentComments['homepage'];
	}
	$request = new HTTPRequest('POST', $homepage);
	$request->contentType = 'application/x-www-form-urlencoded; charset=utf-8';
	$request->content = $data;
	if ($request->send()) {
		$xmls = new XMLStruct();
		if ($xmls->open($request->responseText)) {
			$result = $xmls->selectNode('/response/error/');
			if ($result['.value'] != '1' && $result['.value'] != '0') {
				$homepage = rtrim($homepage, '/') . '/index.php';
				$request = new HTTPRequest('POST', $homepage);
				$request->contentType = 'application/x-www-form-urlencoded; charset=utf-8';
				$request->content = $data;
				if ($request->send()) {
				}
			}
		}
	} else {
	}
	DBQuery::execute("DELETE FROM {$database['prefix']}CommentsNotifiedQueue WHERE id={$queue['queueId']}");
}

function receiveNotifiedComment($post) {
	if (empty($post['mode']) || $post['mode'] != 'fb')
		return 1;
	global $database;
	
	$post = fireEvent('ReceiveNotifiedComment', $post);
	if ($post === false) return 7;
	
	$blogid = getBlogId();
	$title = mysql_tt_escape_string(mysql_lessen($post['s_home_title'], 255));
	$name = mysql_tt_escape_string(mysql_lessen($post['s_name'], 255));
	$entryId = mysql_tt_escape_string($post['s_no']);
	$homepage = mysql_tt_escape_string(mysql_lessen($post['url'], 255));
	$entryUrl = mysql_tt_escape_string($post['s_url']);
	$entryTitle = mysql_tt_escape_string($post['s_post_title']);
	$parent_id = $post['r1_no'];
	$parent_name = mysql_tt_escape_string(mysql_lessen($post['r1_name'], 80));
	$parent_parent = $post['r1_rno'];
	$parent_homepage = mysql_tt_escape_string(mysql_lessen($post['r1_homepage'], 80));
	$parent_written = $post['r1_regdate'];
	$parent_comment = mysql_tt_escape_string(mysql_lessen($post['r1_body'], 255));
	$parent_url = mysql_tt_escape_string(mysql_lessen($post['r1_url'], 255));
	$child_id = $post['r2_no'];
	$child_name = mysql_tt_escape_string(mysql_lessen($post['r2_name'], 80));
	$child_parent = $post['r2_rno'];
	$child_homepage = mysql_tt_escape_string(mysql_lessen($post['r2_homepage'], 80));
	$child_written = $post['r2_regdate'];
	$child_comment = mysql_tt_escape_string(mysql_lessen($post['r2_body'], 255));
	$child_url = mysql_tt_escape_string(mysql_lessen($post['r2_url'], 255));
	$sql = "SELECT id FROM {$database['prefix']}CommentsNotifiedSiteInfo WHERE url = '$homepage'";
	$siteId = DBQuery::queryCell($sql);
	if (empty($siteId)) {
		if (DBQuery::execute("INSERT INTO {$database['prefix']}CommentsNotifiedSiteInfo VALUES ('', '$title', '$name', '$homepage', UNIX_TIMESTAMP());"))
			$siteId = mysql_insert_id();
		else
			return 2;
	}
	$parentId = DBQuery::queryCell("SELECT id FROM {$database['prefix']}CommentsNotified WHERE entry = $entryId AND siteId = $siteId AND blogid = $blogid AND remoteId = $parent_id");
	if (empty($parentId)) {
		$sql = "INSERT INTO {$database['prefix']}CommentsNotified ( blogid , replier , id , entry , parent , name , password , homepage , secret , comment , ip , written, modified , siteId , isNew , url , remoteId ,entryTitle , entryUrl ) 
VALUES (
$blogid, NULL , '', " . $entryId . ", " . (empty($parent_parent) ? 'null' : $parent_parent) . ", '" . $parent_name . "', '', '" . $parent_homepage . "', '', '" . $parent_comment . "', '', " . $parent_written . ",UNIX_TIMESTAMP(), " . $siteId . ", 1, '" . $parent_url . "'," . $parent_id . ", '" . $entryTitle . "', '" . $entryUrl . "'
);";
		if (!DBQuery::execute($sql))
			return 3;
		$parentId = mysql_insert_id();
	}
	if (DBQuery::queryCell("SELECT count(*) FROM {$database['prefix']}CommentsNotified WHERE siteId=$siteId AND remoteId=$child_id") > 0)
		return 4;
	$sql = "INSERT INTO {$database['prefix']}CommentsNotified ( blogid , replier , id , entry , parent , name , password , homepage , secret , comment , ip , written, modified , siteId , isNew , url , remoteId ,entryTitle , entryUrl ) 
VALUES (
$blogid, NULL , '', " . $entryId . ", $parentId, '$child_name', '', '$child_homepage', '', '$child_comment', '', $child_written, UNIX_TIMESTAMP(), $siteId, 1, '$child_url',$child_id, '$entryTitle', '$entryUrl');";
	if (!DBQuery::execute($sql))
		return 5;
	$sql = "UPDATE {$database['prefix']}CommentsNotified SET modified = UNIX_TIMESTAMP() WHERE id=$parentId";
	if (!DBQuery::execute($sql))
		return 6;
	return 0;
}

function getCommentCount($blogid, $entryId = null) {
	global $database;
	if (is_null($entryId))
		return DBQuery::queryCell("SELECT SUM(comments) FROM {$database['prefix']}Entries WHERE blogid = $blogid AND draft= 0 ");
	return DBQuery::queryCell("SELECT comments FROM {$database['prefix']}Entries WHERE blogid = $blogid AND id = $entryId AND draft = 0");
}

function getCommentCountPart($commentCount, &$skin) {
	$noneCommentMessage = $skin->noneCommentMessage;
	$singleCommentMessage = $skin->singleCommentMessage;
	
	if ($commentCount == 0 && !empty($noneCommentMessage)) {
		dress('article_rep_rp_cnt', 0, $noneCommentMessage);
		$commentView = $noneCommentMessage;
	} else if ($commentCount == 1 && !empty($singleCommentMessage)) {
		dress('article_rep_rp_cnt', 1, $singleCommentMessage);
		$commentView = $singleCommentMessage;
	} else {
		$commentPart = $skin->commentCount;
		dress('article_rep_rp_cnt', $commentCount, $commentPart);
		$commentView = $commentPart;
	}
	
	return array("rp_count", $commentView);
}
?>