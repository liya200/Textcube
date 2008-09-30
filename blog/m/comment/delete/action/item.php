<?php
/// Copyright (c) 2004-2007, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
define('__TEXTCUBE_MOBILE__', true);
define('ROOT', '../../../../..');
require ROOT . '/lib/includeForBlog.php';
requireView('mobileView');
requireStrictRoute();
list($entryId) = getCommentAttributes($blogid, $suri['id'], 'entry');
if (deleteComment($blogid, $suri['id'], $entryId, '') === false) {
	printMobileErrorPage(_t('답글을 삭제할 수 없습니다'), _t('관리자가 아닙니다'), "$blogURL/comment/delete/{$suri['id']}");
	exit();
}
list($entries, $paging) = getEntryWithPaging($blogid, $entryId);
$entry = $entries ? $entries[0] : null;
printMobileHtmlHeader();
?>
<div id="content">
	<h2><?php echo _t('답글이 삭제됐습니다');?></h2>
</div>
<?php
printMobileNavigation($entry);
printMobileHtmlFooter();
?>