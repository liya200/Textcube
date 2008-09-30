<?php 
/// Copyright (c) 2004-2007, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)

$commentListView = $skin->commentList;
$itemsView = '';
foreach ($commentList['items'] as $item) {
	$itemView = $skin->commentListItem;
	dress('rplist_rep_regdate', fireEvent('ViewCommentListDate', Timestamp::format3($item['written']), $item['written']), $itemView);
	dress('rplist_rep_link', "$blogURL/".($blog['useSlogan'] ? "entry/".encodeURL($item['slogan']) : $item['entry'])."#comment{$item['id']}", $itemView);
	
	dress('rplist_rep_name', htmlspecialchars($item['name']), $itemView);
	dress('rplist_rep_body', htmlspecialchars(fireEvent('ViewCommentListTitle', UTF8::lessenAsEm($item['comment'], 70))), $itemView);
	$itemsView .= $itemView;
}
dress('rplist_rep', $itemsView, $commentListView);
dress('rplist_conform', htmlspecialchars(fireEvent('ViewCommentListHeadTitle', $commentList['title'])), $commentListView);
dress('rplist_count', count($commentList['items']), $commentListView);
dress('rplist', $commentListView, $view);
?>