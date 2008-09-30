<?php
/// Copyright (c) 2004-2007, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
define('ROOT', '../../../../..');
$IV = array(
	'REQUEST' => array(
		'coverpageNumber' => array('int'),
		'modulePos' => array('int'),
		'targetCoverpageNumber' => array('int'),
		'targetPos' => array('int'),
		'viewMode' => array('string', 'default' => '')
	)
);

require ROOT . '/lib/includeForBlogOwner.php';
requireLibrary('blog.skin');
requireModel("blog.sidebar");
requireModel("blog.coverpage");
requireStrictRoute();

$skin = new Skin($skinSetting['skin']);
$coverpageCount = count($skin->coverpageBasicModules);
$coverpageOrder = getCoverpageModuleOrderData($coverpageCount);

if ($_REQUEST['targetPos'] < 0 || $_REQUEST['targetPos'] > count($coverpageOrder[$_REQUEST['coverpageNumber']]) || $_REQUEST['targetCoverpageNumber'] < 0 || $_REQUEST['targetCoverpageNumber'] >= count($coverpageOrder)) {
	if ($_SERVER['REQUEST_METHOD'] != 'POST')
		header('Location: '. $blogURL . '/owner/center/coverpage' . $_REQUEST['viewMode']);
	else
		respondResultPage(-1);
} else {
	if (($_REQUEST['coverpageNumber'] == $_REQUEST['targetCoverpageNumber'])
		&& ($_REQUEST['modulePos'] < $_REQUEST['targetPos'])) 
	{
		$_REQUEST['targetPos']--;
	}
	$temp = array_splice($coverpageOrder[$_REQUEST['coverpageNumber']], $_REQUEST['modulePos'], 1);
	array_splice($coverpageOrder[$_REQUEST['targetCoverpageNumber']], $_REQUEST['targetPos'], 0, $temp);
	
	setBlogSetting("coverpageOrder", serialize($coverpageOrder));
}

if ($_REQUEST['viewMode'] != '') $_REQUEST['viewMode'] = '?' . $_REQUEST['viewMode'];

if ($_SERVER['REQUEST_METHOD'] != 'POST')
	header('Location: '. $blogURL . '/owner/center/coverpage' . $_REQUEST['viewMode']);
else
	respondResultPage(0);
?>