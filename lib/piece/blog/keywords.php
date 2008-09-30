<?php
/// Copyright (c) 2004-2007, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)

$keywordView = $skin->keyword;
$previousGroup = '';
$itemsView = '';
if(isset($keywords)) {
	foreach ($keywords as $item) {
		$itemView = $skin->keywordItem;
		dress('keyword_label', htmlspecialchars($item['title']), $itemView);
		if($skinSetting['keylogSkin']!= null) {
			dress('onclick_keyword', "openKeyword('$blogURL/keylog/" . escapeJSInAttribute($item['title']) . "'); return false;", $itemView);
		} else {
			dress('onclick_keyword', "return false;", $itemView);
		}
		$itemsView .= $itemView;
	}
}
dress('keyword_rep', $itemsView, $keywordView);
dress('keyword', $keywordView, $view);
?>