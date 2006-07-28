<?
define('ROOT', '../../../..');
$IV = array(
	'POST' => array(
		'confirmativePassword' => array('string', 'mandatory' => false),
		'removeAttachments' => array(array('1'), 'dafault' => null)
	)
);
require ROOT . '/lib/includeForOwner.php';
requireComponent('Tattertools.Data.DataMaintenance');
if (empty($_POST['confirmativePassword']) || !User::confirmPassword($_POST['confirmativePassword']))
	respondResultPage(1);
DataMaintenance::removeAll(Validator::getBool(@$_POST['removeAttachments']));
respondResultPage(0);
?>