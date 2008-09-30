<?php
/// Copyright (c) 2004-2007, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
define('ROOT', '../../../..');
$IV = array(
	'GET' => array(
		'draft' => array('any', 'mandatory' => false),
		'popupEditor' => array('any', 'mandatory' => false),
		'returnURL' => array('string', 'mandatory' => false)
	),
	'POST' => array(
		'category' => array('int', 'default' => 0),
		'search' => array('string', 'default' => ''),
		'returnURL' => array('string', 'mandatory' => false)
	)
);
require ROOT . '/lib/includeForBlogOwner.php';
requireModel("blog.entry");
requireModel("blog.tag");
requireModel("blog.locative");
requireModel("blog.attachment");
if (false) {
	fetchConfigVal();
}
$isKeyword = false;
define('__TEXTCUBE_EDIT__', true);
if (defined('__TEXTCUBE_POST__'))
	$suri['id'] = 0;

$entry = getEntry(getBlogId(), $suri['id'], false);
if (!$entry) {
	respondErrorPage(_t('포스트 정보가 존재하지 않습니다.'));
	$isKeyword = ($entry['category'] == -1);
}

// Check whether or not user has permission to edit.
if(Acl::check('group.editors')===false && !empty($suri['id'])) {
	if(getUserIdOfEntry(getBlogId(), $suri['id']) != getUserId()) { 
		@header("location:".$blogURL ."/owner/entry");
		exit; 
	}
}

if (isset($_GET['popupEditor'])) {
	require ROOT . '/lib/piece/owner/headerForPopupEditor.php';
} else {
	require ROOT . '/lib/piece/owner/header.php';
}

if (isset($_POST['returnURL']) && !empty($_POST['returnURL'])) {
	$_GET['returnURL'] = $_POST['returnURL'];
}

require ROOT . '/lib/piece/owner/contentMenu.php';
if (defined('__TEXTCUBE_POST__')) {
	printOwnerEditorScript();
} else {
	printOwnerEditorScript($entry['id']);
}

?>
						<script type="text/javascript" src="<?php echo $service['path'];?>/script/generaltag.js"></script>
						<script type="text/javascript" src="<?php echo $service['path'];?>/script/locationtag.js"></script>
						<script type="text/javascript">
							//<![CDATA[
								var enclosured = "<?php echo getEnclosure($entry['id']);?>";
								var originalPermalink = "<?php echo htmlspecialchars($entry['slogan']);?>";

								window.onerror = function(errType, errURL,errLineNum) {
									window.status = "Error: " + errType +" (on line " + errLineNum + " of " + errURL + ")";
									return true;
								}
								
								function setEnclosure(value) {
									var filename = value.substring(0, value.indexOf("|"));
									
									if(document.getElementById("fileList").selectedIndex == -1) {
										alert("<?php echo _t('파일을 선택하십시오.');?>");
										return false;
									}
									
									if(!(new RegExp("\.mp3$", "i").test(filename))) {
										alert("<?php echo _t('MP3만 사용할 수 있습니다.');?>");
										return false;
									}
									
									try {
										if(STD.isIE) 
											var uploader = document.getElementById("uploader");
										else 
											var uploader = document.getElementById("uploader2");
									} catch(e) { }
									
									if(filename == enclosured) {
										var order = 0;
										try { uploader.SetVariable("/:enclosure", ""); } catch(e) { }
									}
									else {
										var order = 1;
										try { uploader.SetVariable("/:enclosure", filename); } catch(e) { }
									}
									
									var request = new HTTPRequest("POST", "<?php echo $blogURL;?>/owner/entry/attach/enclosure/");
									request.onSuccess = function () {
										PM.removeRequest(this);
										var fileList = document.getElementById("fileList");
										fileList.selectedIndex = -1;
										for(var i=0; i<fileList.length; i++)
											fileList[i].style.backgroundColor = (order == 1 && fileList[i].value.indexOf(filename) == 0) ? "#c6a6e7" : "#fff";
										enclosured = (order == 1) ? filename : "";
									}
									
									request.onError= function () {
										PM.removeRequest(this);
										alert("<?php echo _t('변경하지 못했습니다.');?>");
									}
									PM.addRequest(request, "<?php echo _t('변경하고 있습니다.');?>");
									request.send("fileName=" + encodeURIComponent(filename) + "&order=" + order);
								}
								
								function EntryManager() {
									this.savedData = null;
<?php
if (defined('__TEXTCUBE_POST__')) {
?>
									this.isSaved   = false;
<?php
} else {
?>
									this.isSaved   = true;
<?php
}
?>
									this.autoSave  = false;
									this.delay     = false;
									this.nowsaving = false;
									this.isPreview   = false;
									this.entryId   = <?php echo $entry['id'];?>;

									this.pageHolder = new PageHolder(false, "<?php echo _t('아직 저장되지 않았습니다.');?>");

									this.pageHolder.isHolding = function () {
										return (entryManager.savedData != entryManager.getData());
									}
									
									this.getData = function (check) {
										if (check == undefined)
											check = false;
										var oForm = document.forms[0];
										
										var title = trim(oForm.title.value);
										var permalink = trim(oForm.permalink.value);
										if (check && (title.length == 0)) {
											if(entryManager.autoSave == true) {
												title = trim("<?php echo _t('[자동 저장 문서]');?>");
												oForm.title.value = title;
												permalink = "TCDraftPost";
												oForm.permalink.value = permalink;
											} else {
												alert("<?php echo _t('제목을 입력해 주십시오.');?>");
												oForm.title.focus();
												return null;
											}
										} else if (title != trim("<?php echo _t('[자동 저장 문서]');?>")) {
											if(permalink.indexOf("TCDraftPost") != -1) {
												permalink = "";
												oForm.permalink.value = permalink;
											}
										}
										var visibility = 0;
										for (var i = 0; i < oForm.visibility.length; i++) {
											if (oForm.visibility[i].checked) {
												visibility = oForm.visibility[i].value;
												break;
											}
										}

										var entrytype = 0;
										for (var i = 0; i < oForm.entrytype.length; i++) {
											if (oForm.entrytype[i].checked) {
												entrytype = oForm.entrytype[i].value;
												break;
											}
										}

										try {
											editor.syncTextarea();
										} catch(e) {
										}
										var content = trim(oForm.content.value);
										if (check && (content.length == 0)) {
											if(entryManager.autoSave == true) {
												return null;
											}
											alert("<?php echo _t('본문을 입력해 주십시오.');?>");
											return null;
										}
											
										var locationValue = "/";
										try {
											locationValue = oLocationTag.getValues();
										} catch(e) {
											locationValue = oForm.location.value;
										}
								
										var tagValue = "";
										try {
											tagValue = oTag.getValues().join(",");
										} catch (e) {
											tagValue = oForm.tag.value;
										}

										var published = 0;
										for (var i = 0; i < oForm.published.length; i++) {
											if (oForm.published[i].checked) {
												published = oForm.published[i].value;
												break;
											}
										}
										if (published == 2) {
											published = Date.parse(oForm.appointed.value);
											if (isNaN(published)) {
												if (check)
													alert("<?php echo _t('등록 예약 시간이 올바르지 않습니다.');?>");
												return null;
											}
											published = Math.floor(published / 1000);
										}

										return (
											"visibility=" + visibility +
											"&title=" + encodeURIComponent(title) +
											"&permalink=" + encodeURIComponent(permalink) +
											"&content=" + encodeURIComponent(content) +
											"&contentFormatter=" + encodeURIComponent(oForm.contentFormatter.value) +
											"&contentEditor=" + encodeURIComponent(oForm.contentEditor.value) +
											"&published=" + published +
											"&category=" + ((entrytype!=0) ? entrytype : oForm.category.value) +
											"&location=" + encodeURIComponent(locationValue) +
											"&tag=" + encodeURIComponent(tagValue) +
											"&acceptComment=" + (oForm.acceptComment.checked ? 1 : 0) +
											"&acceptTrackback=" + (oForm.acceptTrackback.checked ? 1 : 0)
										);
									}
									
									this.setEnclosure = function(fileName) {
										
									}
									this.loadTemplate = function (templateId,title) {
										var oForm = document.forms[0];
										var content = trim(oForm.content.value);
										if (content.length != 0) {
											if(confirm("<?php echo _t('본문에 내용이 있습니다. 서식이 현재 본문 내용을 덮어쓰게 됩니다. 계속하시겠습니까?');?>")!=1)
												return null;
										}

										var request = new HTTPRequest("POST", "<?php echo $blogURL;?>/owner/entry/loadTemplate/");
										request.message = "<?php echo _t('불러오고 있습니다');?>";
										request.onSuccess = function () {
											PM.showMessage("<?php echo _t('서식을 반영하였습니다.');?>", "center", "bottom");
											templateTitle = this.getText("/response/title");
											templateContents = this.getText("/response/content");
											entryManager.entryId = this.getText("/response/entryId");
											entryManager.isSaved = true;
											PM.removeRequest(this);
											var title = trim(oForm.title.value);
											if(title.length == 0) {
												oForm.title.value = templateTitle;
											}
											oForm.content.value = templateContents;
											reloadUploader();
											try {
												editor.syncEditorWindow();
											} catch(e) {
											}
										}
										request.onError = function() {
											PM.removeRequest(this);
											alert("<?php echo _t('불러오지 못했습니다');?>");
										}
										PM.addRequest(request, "<?php echo _t('불러오고 있습니다');?>");
										request.send("templateId="+templateId
											+"&isSaved="+entryManager.isSaved
											+"&entryId="+entryManager.entryId);
									}

									this.save = function () {
										if(this.nowsaving == true)
											return false;
										this.nowsaving = true;
										var data = this.getData(true);
										if (data == null) {
											this.nowsaving = false;
											return false;
										}
										if(entryManager.isSaved == true) {
											var request = new HTTPRequest("POST", "<?php echo $blogURL;?>/owner/entry/update/"+entryManager.entryId);
										} else {
											var request = new HTTPRequest("POST", "<?php echo $blogURL;?>/owner/entry/add/");
										}
										if(entryManager.autoSave != true) {
											request.message = "<?php echo _t('저장하고 있습니다.');?>";
										}
										request.onSuccess = function () {
											if(entryManager.autoSave == true) {
												document.getElementById("saveButton").value = "<?php echo _t('자동으로 저장됨');?>";
												document.getElementById("saveButton").style.color = "#BBB";
												entryManager.autoSave = false;
											} else {
												document.getElementById("saveButton").value = "<?php echo _t('저장됨');?>";
												document.getElementById("saveButton").style.color = "#BBB";
												PM.showMessage("<?php echo _t('저장되었습니다.');?>", "center", "bottom");
											}
											if(entryManager.isSaved == false) {
												entryManager.entryId = this.getText("/response/entryId");
												entryManager.isSaved = true;
												reloadUploader();
											}

											if(entryManager.autoSave != true) {
												PM.removeRequest(this);
											}
											entryManager.savedData = this.content;
											if (entryManager.savedData == entryManager.getData())
												entryManager.pageHolder.release();
											entryManager.nowsaving = false;
											if (entryManager.isPreview == true) {
												window.open("<?php echo $blogURL;?>/owner/entry/preview/"+entryManager.entryId, "previewEntry"+entryManager.entryId, "location=0,menubar=0,resizable=1,scrollbars=1,status=0,toolbar=0");
												entryManager.isPreview = false;
											}
										}
										request.onError = function () {
											PM.removeRequest(this);
											alert("<?php echo _t('저장하지 못했습니다.');?>");
											this.nowsaving = false;
										}
										if(entryManager.autoSave != true) {
											PM.addRequest(request, "<?php echo _t('저장하고 있습니다.');?>");
										}
										request.send(data);
									}
																		
									this.saveAndReturn = function () {
										this.nowsaving = true;
										var data = this.getData(true);
										if (data == null)
											return false;

										if(entryManager.isSaved == true) {
											var request = new HTTPRequest("POST", "<?php echo $blogURL;?>/owner/entry/update/"+entryManager.entryId);
										} else {
											var request = new HTTPRequest("POST", "<?php echo $blogURL;?>/owner/entry/add/");
										}

										request.message = "<?php echo _t('저장하고 있습니다.');?>";
										request.onSuccess = function () {
											entryManager.pageHolder.isHolding = function () {
												return false;
											}
											PM.removeRequest(this);
											var returnURI = "";
											var oForm = document.forms[0];
											var changedPermalink = trim(oForm.permalink.value);
<?php
if (isset($_GET['popupEditor'])) {
?>
											opener.location.href = opener.location.href;
											window.close();
<?php
} else if (isset($_GET['returnURL'])) {
	if(strpos($_GET['returnURL'],'/owner/entry')!==false) {
?>
											returnURI = "<?php echo escapeJSInCData($_GET['returnURL']);?>";
<?php
	} else {
?>
											if(originalPermalink == changedPermalink) {
												returnURI = "<?php echo escapeJSInCData($_GET['returnURL']);?>";
											} else {
												returnURI = "<?php echo escapeJSInCData("$blogURL/" . $entry['id']);?>";
											}
<?php
	}
?>
											window.location = returnURI;
<?php
} else {
?>
											window.location.href = "<?php echo $blogURL;?>/owner/entry";
<?php
}
?>
										}
										request.onError = function () {
											PM.removeRequest(this);
											alert("<?php echo _t('저장하지 못했습니다.');?>");
											this.nowsaving = false;
										}
										PM.addRequest(request, "<?php echo _t('저장하고 있습니다.');?>");
										request.send(data);
									}
									this.saveAuto = function () {
										if(document.getElementById('templateDialog').style.display != 'none') {
											toggleTemplateDialog();
										}
										document.getElementById("saveButton").value = "<?php echo _t('저장하기');?>";
										document.getElementById("saveButton").style.color = "#000";
										if (this.timer == null)
											this.timer = window.setTimeout("entryManager.saveDraft()", 5000);
										else
											this.delay = true;
									}
									this.saveDraft = function () {
										this.autoSave = true;
										if (this.nowsaving == true) {
											this.timer = null;
											this.autoSave = false;
											return;
										}
										this.timer = null;
										if (this.delay) {
											this.delay = false;
											this.autoSave = false;
											this.timer = window.setTimeout("entryManager.saveDraft()", 5000);
											return;
										}
										this.save();
										return;
									}
									this.preview = function () {
										this.isPreview = true;
										this.save();
										return;
									}
									this.savedData = this.getData();
								}
								var entryManager;

								function keepSessionAlive() {
									var request = new HTTPRequest("<?php echo $blogURL;?>/owner/keep/");
									request.persistent = false;
									request.onVerify = function () {
										return true;
									}
									request.send();
								}
								window.setInterval("keepSessionAlive()", 600000);
								
								function checkCategory(type) {
									switch(type) {
										case "type_keyword":
											document.getElementById("title-line-label").innerHTML = "<?php echo _t('키워드');?>";
											document.getElementById("category").disabled = true;
											break;
										case "type_notice":
											document.getElementById("title-line-label").innerHTML = "<?php echo _t('제목');?>";
											document.getElementById("category").disabled = true;
											break;
										case "type_template":
											document.getElementById("title-line-label").innerHTML = "<?php echo _t('서식이름');?>";
											document.getElementById("category").disabled = true;
											break;
										case "type_post":
											document.getElementById("title-line-label").innerHTML = "<?php echo _t('제목');?>";
											document.getElementById("category").disabled = false;
									}
									if(type == "type_keyword" || type == "type_template") {
										var radio = document.forms[0].visibility;
										if(radio[1].checked)
											radio[0].checked = true;
										if(radio[3].checked)
											radio[2].checked = true;
										document.getElementById("permalink-line").style.display = "none";
										document.getElementById("status-protected").style.display = "none";
										document.getElementById("status-syndicated").style.display = "none";
										document.getElementById("power-line").style.display = "none";
										if(type == "type_template") {
											document.getElementById("date-line").style.display = "none";
											document.getElementById("status-line").style.display = "none";
										}
									}
									else {
										document.getElementById("permalink-line").style.display = "";
										document.getElementById("status-protected").style.display = "";
										document.getElementById("status-syndicated").style.display = "";
										document.getElementById("power-line").style.display = "";
										document.getElementById("date-line").style.display = "";
										document.getElementById("status-line").style.display = "";
									}
								}
								
								function viewWhatIsEolin() {
									document.getElementById('fileList').style.visibility = 'hidden';
									dialog = document.getElementById('eolinDialog');
									PM.showPanel(dialog);
								}
								
								function closeWhatIsEolin() {
									document.getElementById('fileList').style.visibility = 'visible';
									document.getElementById('eolinDialog').style.display = 'none';
								}
								
								function toggleTemplateDialog() {
									if(document.getElementById('templateDialog').style.display != 'none') {
										document.getElementById('templateDialog').style.display = 'none';
									} else {
										document.getElementById('templateDialog').style.display = 'block';
									}
									return false;
								}

								function returnToList() {
									window.location.href='<?php echo $blogURL;?>/owner/entry';
									return true;
								}

							//]]>
						</script>
						
						<form id="editor-form" method="post" action="<?php echo $blogURL;?>/owner/entry">
							<div id="part-editor" class="part">
								<h2 class="caption"><span class="main-text"><?php


if (defined('__TEXTCUBE_POST__')) {
	echo _t('글을 작성합니다');
} else {
	echo _t('선택한 글을 수정합니다');
}
?></span></h2>
									
								<div id="editor" class="data-inbox">
									<div id="title-section" class="section">
										<h3><?php echo _t('머리말');?></h3>
										
										<dl id="title-line" class="line">
											<dt><label for="title" id="title-line-label"><?php echo $isKeyword ? _t('키워드') : _t('제목');?></label></dt>
											<dd>
												<input type="text" id="title" class="input-text" name="title" value="<?php echo htmlspecialchars($entry['title']);?>" onkeypress="return preventEnter(event);" size="60" />
											</dd>
										</dl>
										<dl id="category-line" class="line">
											<dt><label for="permalink"><?php echo _t('분류');?></label></dt>
											<dd>
												<div class="entrytype-notice"><input type="radio" id="type_notice" class="radio" name="entrytype" value="-2" onclick="checkCategory('type_notice')"<?php echo ($entry['category'] == -2 ? ' checked="checked"' : '');?> /><label for="type_notice"><?php echo _t('공지');?></label></div>
												<div class="entrytype-keyword"><input type="radio" id="type_keyword" class="radio" name="entrytype" value="-1" onclick="checkCategory('type_keyword')"<?php echo ($entry['category'] == -1 ? ' checked="checked"' : '');?> /><label for="type_keyword"><?php echo _t('키워드');?></label></div>
												<div class="entrytype-template"><input type="radio" id="type_template" class="radio" name="entrytype" value="-4" onclick="checkCategory('type_template')"<?php echo ($entry['category'] == -4 ? ' checked="checked"' : '');?> /><label for="type_template"><?php echo _t('서식');?></label></div>
												<div class="entrytype-post">
													<input type="radio" id="type_post" class="radio" name="entrytype" value="0" onclick="checkCategory('type_post')"<?php echo ($entry['category'] >= 0 ? ' checked="checked"' : '');?> /><label for="type_post"><?php echo _t('글');?></label>
													<select id="category" name="category"<?php if($isKeyword) echo ' disabled="disabled"';?>>
														<option value="0"><?php echo htmlspecialchars(getCategoryNameById($blogid,0) ? getCategoryNameById($blogid,0) : _t('전체'));?></option>
<?php
		foreach (getCategories($blogid) as $category) {
			if ($category['id']!= 0) {
?>
														<option value="<?php echo $category['id'];?>"<?php echo ($category['id'] == $entry['category'] ? ' selected="selected"' : '');?>><?php echo ($category['visibility'] > 1 ? '' : _t('(비공개)')).htmlspecialchars($category['name']);?></option>
<?php
			}
			foreach ($category['children'] as $child) {
				if ($category['id']!= 0) {
?>
														<option value="<?php echo $child['id'];?>"<?php echo ($child['id'] == $entry['category'] ? ' selected="selected"' : '');?>>&nbsp;― <?php echo ($category['visibility'] > 1 ? '' : _t('(비공개)')).htmlspecialchars($child['name']);?></option>
<?php
				}
			}
		}
?>
													</select>
												</div>
											</dd>
										</dl>
									</div>
									
									<div id="textarea-section" class="section">
										<h3><?php echo _t('본문');?></h3>
										
										<dl class="editoroption">
											<dt><label for="contentFormatter"><?php echo _t('포매터');?></label></dt>
											<dd><select id="contentFormatter" name="contentFormatter" onchange="return setFormatter(this.value, document.getElementById('contentEditor'), setCurrentEditor);">
<?php
	foreach (getAllFormatters() as $key => $formatter) {
?>
												<option value="<?php echo htmlspecialchars($key);?>"<?php echo ($entry['contentFormatter'] == $key ? ' selected="selected"' : '');?>><?php echo htmlspecialchars($formatter['name']);?></option>
<?php
	}
?>
											</select></dd>
											<dt><label for="contentEditor"><?php echo _t('편집기');?></label></dt>
											<dd><select id="contentEditor" name="contentEditor" onfocus="return saveEditor(this);" onchange="return setEditor(this) &amp;&amp; setCurrentEditor(this.value);">
<?php
	foreach (getAllEditors() as $key => $editor) {
?>
												<option value="<?php echo htmlspecialchars($key);?>"<?php echo ($entry['contentEditor'] == $key ? ' selected="selected"' : '');?>><?php echo htmlspecialchars($editor['name']);?></option>
<?php
	}
?>
											</select></dd>
										</dl>
										<textarea id="editWindow" name="content" cols="80" rows="20"><?php echo htmlspecialchars($entry['content']);?></textarea>
										<div id="status-container" class="container"><span id="pathStr"><?php echo _t('path');?></span><span class="divider"> : </span><span id="pathContent"></span></div>
<?php
	$view = fireEvent('AddPostEditorToolbox', '');
	if (!empty($view)) {
?>
										<div id="toolbox-container" class="container"><?php echo $view;?></div>
<?php
	}
?>
										
										<div id="templateDialog" class="entry-editor-property" style="display: <?php echo (defined('__TEXTCUBE_POST__') ? 'block' : 'none');?>; z-index: 100;">
											<div class="temp-box">
												<h4><?php echo _t('서식 선택');?></h4>

												<p class="message">
													<?php echo _t('새 글을 쓰거나 아래의 서식들 중 하나를 선택하여 글을 쓸 수 있습니다. 서식은 자유롭게 작성하여 저장할 수 있습니다.');?>
												</p>

												<dl>
													<dt><?php echo _t('서식 목록');?></dt>
<?php
$templateLists = getTemplates(getBlogId(),'id,title');
if (count($templateLists) == 0) {
	echo '												<dd class="noItem">' . _t('등록된 서식이 없습니다.') . '</dd>' . CRLF;
} else {
	foreach($templateLists as $templateList) {
		echo '												<dd><a href="#void" onclick="entryManager.loadTemplate('.$templateList['id'].',\''.$templateList['title'].'\');return false;">'.$templateList['title'].'</a></dd>'.CRLF;
	}
}
?>
												</dl>
												
												<div class="button-box">
													<button class="close-button input-button" onclick="toggleTemplateDialog();return false;" title="<?php echo _t('이 대화상자를 닫습니다.');?>"><span class="text"><?php echo _t('닫기');?></span></button>
									 			</div>
									 		</div>
								 		</div>
										
										<script type="text/javascript">//<![CDATA[
											var contentFormatterObj = document.getElementById('contentFormatter');
											var contentEditorObj = document.getElementById('contentEditor');
											setFormatter(contentFormatterObj.value, contentEditorObj, false);
											setCurrentEditor(contentEditorObj.value);
										//]]></script>
									</div>
									
									<hr class="hidden" />
									
									<div id="taglocal-section" class="section">
										<h3><?php echo _t('태그 &amp; 위치');?></h3>
												
										<div id="tag-location-container" class="container">
											<dl id="tag-line">
												<dt><span class="label"><?php echo _t('태그');?></span></dt>
												<dd id="tag"></dd>
											</dl>
											
											<dl id="location-line">
												<dt><span class="label"><?php echo _t('지역');?></span></dt>
												<dd id="location"></dd>
											</dl>
											
											<script type="text/javascript">
												//<![CDATA[
													try {
														var oLocationTag = new LocationTag(document.getElementById("location"), "<?php echo $blog['language'];?>", <?php echo isset($service['disableEolinSuggestion']) && $service['disableEolinSuggestion'] ? 'true' : 'false';?>);
														oLocationTag.setInputClassName("input-text");
														oLocationTag.setValue("<?php echo addslashes($entry['location']);?>");	
													} catch (e) {
														document.getElementById("location").innerHTML = '<input type="text" class="input-text" name="location" value="<?php echo addslashes($entry['location']);?>" /><br /><?php echo _t('지역태그 스크립트를 사용할 수 없습니다. 슬래시(/)로 구분된 지역을 직접 입력해 주십시오.(예: /대한민국/서울/강남역)');?>';
														// TODO : 이부분(스크립트를 실행할 수 없는 환경일 때)은 직접 입력보다는 0.96 스타일의 팝업이 좋을 듯
													}
													
													try {
														var oTag = new Tag(document.getElementById("tag"), "<?php echo $blog['language'];?>", <?php echo isset($service['disableEolinSuggestion']) && $service['disableEolinSuggestion'] ? 'true' : 'false';?>);
														oTag.setInputClassName("input-text");
<?php
		$tags = array();
		if (!defined('__TEXTCUBE_POST__')) {
			foreach (getTags($entry['blogid'], $entry['id']) as $tag) {
				array_push($tags, $tag['name']);
				echo 'oTag.setValue("' . addslashes($tag['name']) . '");';
			}
		}
?>
													} catch(e) {
														document.getElementById("tag").innerHTML = '<input type="text" class="input-text" name="tag" value="<?php echo addslashes(str_replace('"', '&quot;', implode(', ', $tags)));?>" /><br /><?php echo _t('태그 입력 스크립트를 사용할 수 없습니다. 콤마(,)로 구분된 태그를 직접 입력해 주십시오.(예: 텍스트큐브, BLOG, 테스트)');?>';
													}
												//]]>
											</script> 
										</div>

										<hr class="hidden" />
										
<?php
if (isset($_GET['popupEditor'])) {
?>
										<div class="button-box two-button-box">
											<input type="button" value="<?php echo _t('미리보기');?>" class="preview-button input-button" onclick="entryManager.preview();return false;" />
											<span class="hidden">|</span>
											<input type="submit" id="saveButton" value="<?php echo _t('저장하기');?>" class="save-button input-button" onclick="entryManager.save();return false;" />
											<span class="hidden">|</span>
											<input type="submit" value="<?php echo _t('저장 후 닫기');?>" class="save-and-return-button input-button" onclick="entryManager.saveAndReturn();return false;" />									
										</div>
<?php
} else {
?>
										<div class="button-box three-button-box">
											<input type="button" value="<?php echo _t('미리보기');?>" class="preview-button input-button" onclick="entryManager.preview();return false;" />
											<span class="hidden">|</span>
							    	  	 		<input type="submit" id="saveButton" value="<?php echo _t('저장하기');?>" class="save-button input-button" onclick="entryManager.save();return false;" />
											<span class="hidden">|</span>
							       			<input type="submit" value="<?php echo _t('저장 후 닫기');?>" class="save-and-return-button input-button" onclick="entryManager.saveAndReturn();return false;" />
											<span class="hidden">|</span>
											<input type="submit" value="<?php echo _t('목록으로');?>" class="list-button input-button" onclick="returnToList();return false;" />
										</div>
<?php
}
?>
									</div>							
									<hr class="hidden" />
									
									<div id="upload-section" class="section">
										<h3><?php echo _t('업로드');?></h3>
										
										<div id="attachment-container" class="container">
<?php
$param = array(
		'uploadPath'=> "$blogURL/owner/entry/attachmulti/", 
		'singleUploadPath'=> "$blogURL/owner/entry/attach/", 
		'deletePath'=>"$blogURL/owner/entry/detach/multi/",
		'labelingPath'=> "$blogURL/owner/entry/attachmulti/list/", 
		'refreshPath'=> "$blogURL/owner/entry/attachmulti/refresh/", 
		'fileSizePath'=> "$blogURL/owner/entry/size?parent=");		
printEntryFileList(getAttachments($blogid, $entry['id'], 'label'), $param);
?>
										</div>
										
										<div id="insert-container" class="container">
											<a class="image-left" href="#void" onclick="editorAddObject(editor, 'Image1L');return false;" title="<?php echo _t('선택한 파일을 글의 왼쪽에 정렬합니다.');?>"><span class="text"><?php echo _t('왼쪽 정렬');?></span></a>
											<a class="image-center" href="#void" onclick="editorAddObject(editor, 'Image1C');return false;" title="<?php echo _t('선택한 파일을 글의 중앙에 정렬합니다.');?>"><span class="text"><?php echo _t('중앙 정렬');?></span></a>
											<a class="image-right" href="#void" onclick="editorAddObject(editor, 'Image1R');return false;" title="<?php echo _t('선택한 파일을 글의 오른쪽에 정렬합니다.');?>"><span class="text"><?php echo _t('오른쪽 정렬');?></span></a>
											<a class="image-2center" href="#void" onclick="editorAddObject(editor, 'Image2C');return false;" title="<?php echo _t('선택한 두개의 파일을 글의 중앙에 정렬합니다.');?>"><span class="text"><?php echo _t('중앙 정렬(2 이미지)');?></span></a>
											<a class="image-3center" href="#void" onclick="editorAddObject(editor, 'Image3C');return false;" title="<?php echo _t('선택한 세개의 파일을 글의 중앙에 정렬합니다.');?>"><span class="text"><?php echo _t('중앙 정렬(3 이미지)');?></span></a>
											<a class="image-free" href="#void" onclick="editorAddObject(editor, 'ImageFree');return false;" title="<?php echo _t('선택한 파일을 글에 삽입합니다. 문단의 모양에 영향을 주지 않습니다.');?>"><span class="text"><?php echo _t('파일 삽입');?></span></a>
											<a class="image-imazing" href="#void" onclick="editorAddObject(editor, 'Imazing');return false;" title="<?php echo _t('이메이징(플래쉬 갤러리)을 삽입합니다.');?>"><span class="text"><?php echo _t('이메이징(플래쉬 갤러리) 삽입');?></span></a>
											<a class="image-sequence" href="#void" onclick="editorAddObject(editor, 'Gallery');return false;" title="<?php echo _t('이미지 갤러리를 삽입합니다.');?>"><span class="text"><?php echo _t('갤러리 삽입');?></span></a>
											<a class="image-mp3" href="#void" onclick="editorAddObject(editor, 'Jukebox');return false;" title="<?php echo _t('쥬크박스를 삽입합니다.');?>"><span class="text"><?php echo _t('쥬크박스 삽입');?></span></a>
											<a class="image-podcast" href="#void" onclick="setEnclosure(document.getElementById('fileList').value);return false;" title="<?php echo _t('팟캐스트로 지정합니다.');?>"><span class="text"><?php echo _t('팟캐스트 지정');?></span></a>
										</div>
<?php
printEntryFileUploadButton($entry['id']);
?>
									</div>

									<hr class="hidden" />
									
									<div id="power-section" class="section">
										<div id="power-container" class="container">
											<dl id="permalink-line" class="line"<?php if($isKeyword) echo _t('style="display: none"');?>>
												<dt><label for="permalink"><?php echo _t('절대 주소');?></label></dt>
												<dd>
													<samp><?php echo _f('%1/entry/', link_cut(getBlogURL()));?></samp><input type="text" id="permalink" class="input-text" name="permalink" onkeypress="return preventEnter(event);" value="<?php echo htmlspecialchars($entry['slogan']);?>" />
													<p>* <?php echo _t('입력하지 않으면 글의 제목이 절대 주소가 됩니다.');?></p>
												</dd>
											</dl>
											<dl id="date-line" class="line">
												<dt><span class="label"><?php echo _t('등록일자');?></span></dt>
												<dd>
<?php
if (defined('__TEXTCUBE_POST__')) {
?>
													<div class="publish-update"><input type="radio" id="publishedUpdate" class="radio" name="published" value="1" checked="checked" /><label for="publishedUpdate"><?php echo _t('갱신');?></label></div>
<?php
} else {
?>
													<div class="publish-nochange"><input type="radio" id="publishedNoChange" class="radio" name="published" value="0" <?php echo (!isset($entry['republish']) && !isset($entry['appointed']) ? 'checked="checked"' : '');?> /><label for="publishedNoChange"><?php echo _t('유지');?> (<?php echo Timestamp::format5($entry['published']);?>)</label></div>
													<div class="publish-update"><input type="radio" id="publishedUpdate" class="radio" name="published" value="1" <?php echo (isset($entry['republish']) ? 'checked="checked"' : '');?> /><label for="publishedUpdate"><?php echo _t('갱신');?></label></div>
<?php
}
?>
													<div class="publish-preserve">
														<input type="radio" id="publishedPreserve" class="radio" name="published" value="2" <?php echo (isset($entry['appointed']) ? 'checked="checked"' : '');?> /><label for="publishedPreserve" onclick="document.getElementById('appointed').select()"><?php echo _t('예약');?></label>
														<input type="text" id="appointed" class="input-text" name="appointed" value="<?php echo Timestamp::format5(isset($entry['appointed']) ? $entry['appointed'] : $entry['published']);?>" onfocus="document.forms[0].published[document.forms[0].published.length - 1].checked = true" onkeypress="return preventEnter(event);" />
													</div>
												</dd>
											</dl>
<?php
$countResult = DBQuery::queryExistence("SELECT `id` FROM `{$database['prefix']}Entries` WHERE `blogid` = ".getBlogId()." AND `visibility` = 3");
?>
											<dl id="status-line" class="line">
												<dt><span class="label"><?php echo _t('공개여부');?></span></dt>
												<dd>
													<div id="status-private" class="status-private"><input type="radio" id="visibility_private" class="radio" name="visibility" value="0"<?php echo (abs($entry['visibility']) == 0 ? ' checked="checked"' : '');?> /><label for="visibility_private"><?php echo _t('비공개');?></label></div>
													<div id="status-protected" class="status-protected"<?php if($isKeyword) echo _t('style="display: none"');?>><input type="radio" id="visibility_protected" class="radio" name="visibility" value="1"<?php echo (abs($entry['visibility']) == 1 ? ' checked="checked"' : '');?> /><label for="visibility_protected"><?php echo _t('보호');?></label></div>
													<div id="status-public" class="status-public"><input type="radio" id="visibility_public" class="radio" name="visibility" value="2"<?php echo (abs($entry['visibility']) == 2 ? ' checked="checked"' : '');?> /><label for="visibility_public"><?php echo _t('공개');?></label></div>
													<div id="status-syndicated" class="status-syndicated"<?php if($isKeyword) echo _t('style="display: none"');?>><input type="radio" id="visibility_syndicated" class="radio" name="visibility" value="3"<?php echo $countResult == false ? ' onclick="viewWhatIsEolin();"' : NULL; echo (abs($entry['visibility']) == 3 ? ' checked="checked"' : '');?> /><label for="visibility_syndicated"><?php echo _t('발행');?><?php echo $countResult == true ? ' (<a href="#void" onclick="viewWhatIsEolin();">'._t('설명').'</a>)' : NULL;?></label></div>
												</dd>
											</dl>
												
											<dl id="power-line" class="line"<?php if($isKeyword) echo _t('style="display: none"');?>>
												<dt><span class="label"><?php echo _t('권한');?></span></dt>
												<dd>
													<div class="comment-yes"><input type="checkbox" id="acceptComment" class="checkbox" name="acceptComment"<?php echo ($entry['acceptComment'] ? ' checked="checked"' : '');?> /><label for="acceptComment"><span class="text"><?php echo _t('댓글 작성을 허용합니다.');?></span></label></div>
												  	<div class="trackback-yes"><input type="checkbox" id="acceptTrackback" class="checkbox" name="acceptTrackback"<?php echo ($entry['acceptTrackback'] ? ' checked="checked"' : '');?> /><label for="acceptTrackback"><span class="text"><?php echo _t('글을 걸 수 있게 합니다.');?></span></label></div>
												</dd>
											</dl>
										</div>
									</div>
<?php
if (isset($_GET['popupEditor'])) {
?>
									<div class="button-box two-button-box">
										<input type="button" value="<?php echo _t('미리 보기');?>" class="preview-button input-button" onclick="entryManager.preview();return false;" />
										<span class="hidden">|</span>
										<input type="submit" value="<?php echo _t('저장 후 닫기');?>" class="save-and-return-button input-button" onclick="entryManager.saveAndReturn();return false;" />									
								</div>
<?php
} else {
?>
									<div class="button-box three-button-box">
										<input type="button" value="<?php echo _t('미리 보기');?>" class="preview-button input-button" onclick="entryManager.preview();return false;" />
										<span class="hidden">|</span>
						       			<input type="submit" value="<?php echo _t('저장 후 닫기');?>" class="save-and-return-button input-button" onclick="entryManager.saveAndReturn();return false;" />
										<span class="hidden">|</span>
										<input type="submit" value="<?php echo _t('목록으로');?>" class="list-button input-button" onclick="returnToList();return false;" />
									</div>
<?php
}
?>
								</div>
								
								<input type="hidden" name="categoryAtHome" value="<?php echo (isset($_POST['category']) ? $_POST['category'] : '0');?>" />
								<input type="hidden" name="page" value="<?php echo $suri['page'];?>" />
								<input type="hidden" name="withSearch" value="<?php echo (empty($_POST['search']) ? '' : 'on');?>" />
								<input type="hidden" name="search" value="<?php echo (isset($_POST['search']) ? htmlspecialchars($_POST['search']) : '');?>" />
							</div>
						</form>
						
						<div id="eolinDialog" class="dialog" style="position: absolute; display: none; z-index: 100;">
							<div class="temp-box">
								<h4><?php echo _t('이올린이란?');?></h4>
								
								<p class="message">
									<?php echo _t('이올린은 텍스트큐브와 텍스트큐브 기반의 블로그에서 "발행"을 통해 보내진 글들을 다양한 방법으로 만날 수 있는 텍스트큐브 블로거들의 열린 공간입니다.');?>
								</p>
								
								<h4><?php echo _t('발행 방법');?></h4>
								
								<p class="message">
									<em><?php echo _t('텍스트큐브 글목록에서 발행버튼을 누르거나 글쓰기시 공개범위를 "발행"으로 체크하면 됩니다.');?></em>
									<?php echo _t('발행을 통해 이올린으로 보내진 게시물들의 저작권을 포함한 일체에 관한 권리는 별도의 의사표시가 없는 한 각 회원에게 있습니다. 이올린에서는 발행된 게시물을 블로거의 동의 없이 상업적으로 이용하지 않습니다. 다만 비영리적 목적인 경우는 이용이 가능하며, 또한 이올린 서비스 내의 게재권, 사용권을 갖습니다.');?>
								</p>
							
								<div class="button-box">
									<button id="eolin-button" class="eolin-button input-button" onclick="window.open('http://www.eolin.com');" title="<?php echo _t('이올린으로 연결합니다.');?>"><span class="text"><?php echo _t('이올린, 지금 만나보세요');?></span></button>
									<button id="close-button" class="close-button input-button" onclick="closeWhatIsEolin();return false;" title="<?php echo _t('이 대화상자를 닫습니다.');?>"><span class="text"><?php echo _t('닫기');?></span></button>
					 			</div>
					 		</div>
				 		</div>
				 		
						<script type="text/javascript">
							//<![CDATA[
								entryManager = new EntryManager();
								reloadUploader();
								window.setInterval("entryManager.saveDraft();", 300000);
							//]]>
						</script> 
<?php
if (isset($_GET['popupEditor']))
	require ROOT . '/lib/piece/owner/footerForPopupEditor.php';
else
	require ROOT . '/lib/piece/owner/footer.php';
?>