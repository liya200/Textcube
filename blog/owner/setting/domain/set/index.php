<?php
/// Copyright (c) 2004-2007, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
define('ROOT', '../../../../..');
$IV = array(
	'POST' => array(
		'defaultDomain' => array(array('0', '1')),
		'primaryDomain' => array('string'),
		'secondaryDomain' => array('domain', 'default' => '')
	)
);
require ROOT . '/lib/includeForBlogOwner.php';
requireStrictRoute();

if(empty($_POST['secondaryDomain']) && $_POST['defaultDomain'] == 1)
	respondResultPage(4);
else if( ($result = setPrimaryDomain($blogid, $_POST['primaryDomain'])) > 0 )
	printRespond(array('error' => 2, 'msg' => $result));
else if( ($result = setSecondaryDomain($blogid, $_POST['secondaryDomain'])) > 0 )
	printRespond(array('error' => 3, 'msg' => $result));
else if(!setDefaultDomain($blogid, $_POST['defaultDomain']))
	respondResultPage(1);
else
	respondResultPage(0);
?>