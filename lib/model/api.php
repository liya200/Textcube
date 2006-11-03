<?php
/*--------- Basic functions -----------*/

function api_setHint( $hint )
{
	global $arr_hint;
	if( !$arr_hint )
	{
		$arr_hint = array();
	}
	$arr_hint[$hint] = true;
}

function api_checkHint( $hint )
{
	global $arr_hint;
	if( $arr_hint )
	{
		return $arr_hint[$hint];
	}
	return false;
}

function api_get_request_id( $id )
{
	if(array_key_exists('id', $_GET))
	{
		return $_GET["id"];
	}
	return $id;
}

function api_get_canonical_id( $id )
{
	$alias_file = ROOT . "/.htaliases";
	$canon = api_get_request_id( $id );
	
	if( $id == "" || !file_exists( $alias_file ) )
	{
		return $canon;
	}
	
	$fd = fopen( $alias_file, "r" );
	if( $fd == FALSE )
	{
		return $canon;
	}
	while( !feof($fd) )
	{
		$line = fgets( $fd, 1024 );
		if( substr($line,0,1) == "#" )
		{
			continue;
		}
		$match = preg_split( '/( |\t|\r|\n)+/', $line );
		if( $id == $match[0] )
		{
			$canon = $match[1];
			break;
		}
	}
	fclose( $fd );
	return $canon;
}

function api_login( $id, $password )
{
	$auth = new Auth;
	if( !$auth->login( $id, $password ) )
	{
		$canon_id = api_get_canonical_id($id);
		if( !$auth->login( $canon_id, $password ) )
		{
			return new XMLRPCFault( 1, "Authentication failed: $id($canon_id)" );
		}
	}
	return false;
}

function api_utf8_substr($str,$start) 
{ 
	preg_match_all("/./u", $str, $ar); 
	
	if(func_num_args() >= 3) { 
		$end = func_get_arg(2); 
		return join("",array_slice($ar[0],$start,$end)); 
	} else { 
		return join("",array_slice($ar[0],$start)); 
	} 
} 

function api_get_title( $content )
{
	if( preg_match( "{<title>(.+)?</title>}", $content, $match ) )
	{
		return $match[1];
	}
	$title = preg_replace( "{<.*?>}", "", $content);
	$title = api_utf8_substr( $title, 0, 40 );
	return $title;
}

function api_escape_content( $content )
{
	$content = str_replace( "\r", '', $content );
	return htmlspecialchars($content);
}

function api_timestamp( $date8601 )
{
	if( substr( $date8601, 8,1 ) != "T" )
	{
		return $date8601;
	}
	return Timezone::getOffset() + 
		mktime( 
			substr( $date8601, 9, 2 ),
			substr( $date8601, 12, 2 ),
			substr( $date8601, 15, 2 ),
			substr( $date8601, 4, 2 ),
			substr( $date8601, 6, 2 ),
			substr( $date8601, 0, 4 ) );
}

function api_dateiso8601( $timestamp )
{
	return gmstrftime( "%Y%m%dT%H:%M:%S", $timestamp );
}


function send_failure( $msg )
{
	print(  "<methodResponse>\n" .
			"<fault><value><struct>\n" .
			"<member>\n" .
			"<name>faultCode</name>\n" .
			"<value><int>1</int></value>\n" .
			"</member>\n" .
			"<member>\n" .
			"<name>faultString</name>\n" .
			"<value><string>" . api_escape_content($msg) . "</string></value>\n" .
			"</member>\n" .
			"</struct></value></fault>\n" .
			"</methodResponse>\n" );
}

function api_getCategoryIdByName( $name_array )
{
	$category = new Category();
	$category->open(false);
	
	$name = $name_array[0];
	$id = $name;
	
	while(1)
	{
		if( $category->label == $name )
		{
			$id = $category->id;
			break;
		}
		if( !$category->shift() )
		{
			break;
		}
	}
	
	$category->close();
	return $id;
	
}

function api_getCategoryNameById( $id )
{
	$category = new Category();
	$category->open();
	
	$name = $id;
	
	while(1)
	{
		if( $category->id == $id )
		{
			$name = $category->name;
			break;
		}
		if( !$category->shift() )
		{
			break;
		}
	}
	
	$category->close();
	return $name;
	
}

function api_make_post( $param, $ispublic, $postid = -1 )
{
	$post = new Post();
	if( $postid != -1 )
	{
		if( !$post->open( $postid ) )
		{
			return false;
		}
	}
	
	$post->content = $param['description'];
	$post->title = $param['title'];
	
	$param['mt_excerpt'] = array_key_exists('mt_excerpt', $param) ? $param['mt_excerpt'] : '';
	$param['tagwords'] = array_key_exists('tagwords', $param) ? $param['tagwords'] : array();
	$param['categories'] = array_key_exists('categories', $param) ? $param['categories'] : '';
	$param['dateCreated'] = array_key_exists('dateCreated', $param) ? $param['dateCreated'] : api_dateiso8601(time());
	$param['mt_allow_comments'] = array_key_exists('mt_allow_comments', $param) ? $param['mt_allow_comments'] : '';
	$param['mt_allow_pings'] = array_key_exists('mt_allow_pings', $param) ? $param['mt_allow_pings'] : '';

	global $arr_hint;
	if( api_checkHint("TagsFromCategories") )
	{
		$post->tags = array_merge( $param['categories'], split(",", $param['mt_excerpt']) , $param['tagwords'] );
	}
	else
	{
		$post->tags = array_merge( split(",", $param['mt_excerpt']) , $param['tagwords'] );
		$post->category = api_getCategoryIdByName( $param['categories'] );
	}
	
	$post->created = api_timestamp( $param['dateCreated'] );
	$post->modified = api_timestamp( $param['dateCreated'] );
	
	$post->acceptComment = $param['mt_allow_comments'] !== 0 ? true : false;
	$post->acceptTrackback = $param['mt_allow_pings'] !== 0 ? true : false;
	
	if( $ispublic )
	{
		$post->visibility = "public";
		$post->published = api_timestamp( $param['dateCreated'] );
	}
	else
	{
		$post->visibility = "private";
	}
	
	return $post;
}

function api_get_post( $post, $type = "bl" )
{ 
	$post->loadTags();
	$params = func_get_args();
	global $service, $hostURL, $blogURL;
	return array( 
			"userid" => "",
			"dateCreated" => api_dateiso8601( $post->created ),
			"datePosted" => api_dateiso8601( $post->published ),
			"dateModified" => api_dateiso8601( $post->modified ),
			"title" =>  api_escape_content($post->title),
			"postid" => $post->id,
			"categories" => array( api_getCategoryNameById($post->category) ),
			"link" => $hostURL . $blogURL . "/" . $post->id ,
			"permaLink" => $hostURL . $blogURL . "/" . $post->id ,
			"description" => ($type == "mt" ? $post->content : "" ),
			"content" => $post->content,
			"mt_allow_comments" => $post->acceptComment ? 1 : 0,
			"mt_allow_pings" => $post->acceptTrackback ? 1 : 0,
			"mt_excerpt" => join( ",", $post->tags )
			);
}

/* Copied from blog/owner/entry/attach/index.php:getMIMEType,addAttachment */

function api_getMIMEType($ext,$filename=null){
	if($filename){
		return '';
	}else{
		switch(strtolower($ext)){
			case 'gif':
				return 'image/gif';
			case 'jpeg':
			case 'jpg':
			case 'jpe':
				return 'image/jpeg';
			case 'png':
				return 'image/png';
			case 'tiff':
			case 'tif':
				return 'image/tiff';
			case 'bmp':
				return 'image/bmp';
			case 'wav':
				return 'audio/x-wav';
			case 'mpga':
			case 'mp2':
			case 'mp3':
				return 'audio/mpeg';
			case 'm3u':
				return 'audio/x-mpegurl';
			case 'wma':
				return 'audio/x-msaudio';
			case 'ra':
				return 'audio/x-realaudio';
			case 'css':
				return 'text/css';
			case 'html':
			case 'htm':
			case 'xhtml':
				return 'text/html';
			case 'rtf':
				return 'text/rtf';
			case 'sgml':
			case 'sgm':
				return 'text/sgml';
			case 'xml':
			case 'xsl':
				return 'text/xml';
			case 'mpeg':
			case 'mpg':
			case 'mpe':
				return 'video/mpeg';
			case 'qt':
			case 'mov':
				return 'video/quicktime';
			case 'avi':
			case 'wmv':
				return 'video/x-msvideo';
			case 'pdf':
				return 'application/pdf';
			case 'bz2':
				return 'application/x-bzip2';
			case 'gz':
			case 'tgz':
				return 'application/x-gzip';
			case 'tar':
				return 'application/x-tar';
			case 'zip':
				return 'application/zip';
		}
	}
	return '';
}


function api_file_hash( $content )
{
	$md5sum = md5( $content );
	return sprintf( "ta%stt%ser%s", substr( $md5sum, 0, 7 ), substr( $md5sum, 7, 7 ), substr( $md5sum, 14, 7 ) );
}


function api_addAttachment($owner,$parent,$file){
	global $database;
	
	$attachment=array();
	$attachment['parent']=$parent?$parent:0;
	$attachment['label']=Path::getBaseName($file['name']);
	$label=mysql_tt_escape_string(mysql_lessen($attachment['label'],64));
	$attachment['size']=$file['size'];
	$extension=Path::getExtension($attachment['label']);
	switch(strtolower($extension)){
		case '.exe':
		case '.php':
		case '.sh':
		case '.com':
		case '.bat':
			$extension='.xxx';
			break;
	}
	
	/* Create directory for owner */
	$path = ROOT . "/attach/$owner";
	if(!is_dir($path)){
		mkdir($path);
		if(!is_dir($path))
			return false;
		@chmod($path,0777);
	}
	
	$oldFile = DBQuery::queryCell("SELECT name FROM {$database['prefix']}Attachments WHERE owner=$owner AND parent=$parent AND label = '$label'");
	
	if ($oldFile !== null) {
		$attachment['name'] = $oldFile;
	} else {
		requireComponent('Tattertools.Data.Attachment');
		$attachment['name'] = rand(1000000000, 9999999999) . $extension;
		
		while (Attachment::doesExist($attachment['name']))
		$attachment['name'] = rand(1000000000, 9999999999) . $extension;
	}
	
	
	$attachment['path'] = "$path/{$attachment['name']}";
	
	api_deleteAttachment($owner,-1,$attachment['name']);
	
	if( $file['content'] )
	{
		$f = fopen( $attachment['path'], "w" );
		if( !$f )
		{
			return false;
		}
		$attachment['size'] = fwrite( $f, $file['content'] );
		fclose( $f );
		$file['tmp_name'] = $attachment['path'];
	}
	
	if($imageAttributes=@getimagesize($file['tmp_name'])){
		$attachment['mime']=$imageAttributes['mime'];
		$attachment['width']=$imageAttributes[0];
		$attachment['height']=$imageAttributes[1];
	}else{
		$attachment['mime']=getMIMEType($extension);
		$attachment['width']=0;
		$attachment['height']=0;
	}
	
	$attachment['mime']=mysql_lessen($attachment['mime'], 32);
	
	@chmod($attachment['path'],0666);
	$result=mysql_query("insert into {$database['prefix']}Attachments values ($owner, {$attachment['parent']}, '{$attachment['name']}', '$label', '{$attachment['mime']}', {$attachment['size']}, {$attachment['width']}, {$attachment['height']}, UNIX_TIMESTAMP(), 0,0)");
	if(!$result){
		@unlink($attachment['path']);
		return false;
	}
	return $attachment;
}


/* Up to here, copied from blog/owner/entry/attach/index.php */

/* Work around , copied from blog/owner/entry/delete/item.php -r594 */
function api_getAttachments($owner,$parent){
	global $database;
	$attachments=array();
	if($result=mysql_query("select * from {$database['prefix']}Attachments where owner = $owner and parent = $parent")){
		while($attachment=mysql_fetch_array($result))
		array_push($attachments,$attachment);
	}
	return $attachments;
}
function api_deleteAttachment($owner,$parent,$name){
	global $database;
	
	requireComponent('Eolin.PHP.Core');
	$name = Path::getBaseName($name);
	
	@unlink(ROOT . "/attach/$owner/$name");
	$name=mysql_tt_escape_string($name);
	$parent_clause = "";
	if( $parent >= 0 )
	{
		$parent_clause = "parent = $parent and ";
	}
	if(mysql_query("delete from {$database['prefix']}Attachments where owner = $owner and $parent_clause name = '$name'")&&(mysql_affected_rows()==1)){
		return true;
	}
	return false;
}
function api_deleteAttachments($owner,$parent){
	$attachments=api_getAttachments($owner,$parent);
	foreach($attachments as $attachment)
		api_deleteAttachment($owner,$parent,$attachment['name']);
}

/* Work around end */

function api_get_attaches( $content)
{
	global $owner;
	preg_match_all( "/attach\/$owner\/(ta.{7}tt.{7}er.{7}\.[a-z]{2,5})/", $content, $matches );
	return $matches[1];
}

function api_update_attaches( $parent, $attaches = null)
{
	global $database, $owner;
	if (is_null($attaches)) {
		mysql_query( "update {$database['prefix']}Attachments set parent=$parent where owner=$owner and parent=0");		
	} else {
		foreach( $attaches as $att )
		{
			$att = mysql_tt_escape_string($att);
			mysql_query( "update {$database['prefix']}Attachments set parent=$parent where owner=$owner and parent=0 and name='" . $att . "'");
		}
	}
}

function api_update_attaches_with_replace($entryId)
{
	global $database, $owner;
	
	requireComponent('Eolin.PHP.Core');
	$newFiles = DBQuery::queryAll("SELECT name, label FROM {$database['prefix']}Attachments WHERE owner=$owner AND parent=0");
	foreach($newFiles as $newfile) {
		$newfile['label'] = mysql_tt_escape_string(mysql_lessen($newfile['label'], 64));
		$oldFile = DBQuery::queryCell("SELECT name FROM {$database['prefix']}Attachments WHERE owner=$owner AND parent=$entryId AND label='{$newfile['label']}'");
	
		if (!is_null($oldFile)) {
			api_deleteAttachment($owner, $entryId, $oldFile);
		}
	}
	
	api_update_attaches($entryId);
}

/*--------- API main ---------------*/
function api_BlogAPI()
{
	if (!array_key_exists('HTTP_RAW_POST_DATA', $GLOBALS)) {
		XMLRPC::sendFault(1, 'Invalid Method Call');
		exit;
	}
	
	$xml = $GLOBALS['HTTP_RAW_POST_DATA'];

	$functions = array(
		"blogger.getUsersBlogs",
		"blogger.newPost",
		"blogger.editPost",
		"blogger.getTemplate",
		"blogger.getRecentPosts",
		"blogger.deletePost", 
		"blogger.getPost", 
		"metaWeblog.newPost",
		"metaWeblog.getPost",
		"metaWeblog.getCategories",
		"metaWeblog.getRecentPosts",
		"metaWeblog.editPost",
		"metaWeblog.newMediaObject",
		"mt.getPostCategories",
		"mt.setPostCategories",
		"mt.getCategoryList" );

	$xmlrpc = new XMLRPC;

	foreach( $functions as $func )
	{
		$callback = str_replace( ".", "_", $func );
		$xmlrpc->registerMethod( $func, $callback );
	}

	$xmlrpc->receive( $xml );
}

/*--------- Blogger API functions -----------*/

function blogger_getUsersBlogs()
{
	global $service, $blog, $hostURL, $blogURL;
	global $owner;

	$params = func_get_args();
	$result = api_login( $params[1], $params[2] );
	if( $result )
	{
		return $result;
	}

	$blogs = array( 
		array( 
				"url" => $hostURL . $blogURL,
				"blogid" => "$owner",
				"blogName" => $blog['title'],
		) 
	);
	return $blogs;
}

function blogger_newPost()
{
	$params = func_get_args();
	$post = new Post();
	$post->content = $params[4];
	$post->title = htmlspecialchars(api_get_title($params[4]));

	if( $params[5] )
	{
		$post->visibility = "public";
	}
	else
	{
		$post->visibility = "private";
	}

	$result = api_login( $params[2], $params[3] );
	if( $result )
	{
		return $result;
	}

	if( !$post->add() )
	{
		$post->close();
		return new XMLRPCFault( 1, "Posting error" );
	}

	RSS::refresh();

	$id = "{$post->id}";
	$post->close();
	return $id;
}

function blogger_editPost()
{
	$params = func_get_args();

	$result = api_login( $params[2], $params[3] );
	if( $result )
	{
		return $result;
	}

	$post = new Post();
	$post->title = htmlspecialchars(api_get_title( $params[4] ));
	$post->id = intval($params[1]);
	$post->content = htmlspecialchars($params[4]);

	if( $params[5] )
	{
		$post->visibility = "public";
	}
	else
	{
		$post->visibility = "private";
	}

	$ret = $post->update();
	$post->close();

	RSS::refresh();

	return $ret ? 1 : 0;
}

function blogger_deletePost()
{
	$params = func_get_args();
	$result = api_login( $params[2], $params[3] );
	if( $result )
	{
		return $result;
	}

/*
	$post = new Post();
	$post->count = 1;
	$ret = $post->remove( intval( $params['blog_post_id'] ) );
	$post->close();
*/

// Workaround: TT must fix "DELETE FROM FROM" typo error
	{
		global $database, $owner;
		$id = intval( $params[1] );
		$filter = 'AND id = ' . $id;
		$ret = mysql_query("DELETE FROM {$database['prefix']}Entries " .
			"WHERE owner = $owner AND category >= 0 $filter");
		if(mysql_affected_rows()>0){
			$result=mysql_query("DELETE FROM {$database['prefix']}Comments WHERE owner = $owner AND entry = $id");
			$result=mysql_query("DELETE FROM {$database['prefix']}Trackbacks WHERE owner = $owner AND entry = $id");
			$result=mysql_query("DELETE FROM {$database['prefix']}TrackbackLogs WHERE owner = $owner AND entry = $id");
			$result=mysql_query("DELETE FROM {$database['prefix']}TagRelations WHERE owner = $owner AND entry = $id");
			api_deleteAttachments($owner,$id);
			RSS::refresh();
		}
	}
// To here.

	return $ret;

}

function blogger_getRecentPosts()
{
	$params = func_get_args();
	$result = api_login( $params[2], $params[3] );
	if( $result )
	{
		return $result;
	}

	$post = new Post();
	$post->open();
	$out = array();

	for($i=0; $i<$params[4]; $i++ )
	{
		array_push( $out, api_get_post( $post, "bl" ) );
		if( !$post->shift() )
		{
			break;
		}
	}
	$post->close();
	return $out;

}

function blogger_getPost()
{
	$params = func_get_args();
	$result = api_login( $params[2], $params[3] );
	if( $result )
	{
		return $result;
	}

	$post = new Post();
	$post->open( intval( $params[1] ) );

	$ret = api_get_post( $post );
	$post->close();

	return $ret;

}

function blogger_getTemplate()
{
	$params = func_get_args();
	$result = api_login( $params[2], $params[3] );
	if( $result )
	{
		return $result;
	}

	$file = ( $params[4] == "main" ? "template.main.tpl" : "template.archindex.tpl" );
	$template = "";
	if( file_exists( $file ) )
	{
		$fd = fopen( $file, "r" );
		while( !feof($fd) )
		{
			$template .= fgets( $fd, 4096 );
		}
		fclose( $fd );
	}
	return htmlspecialchars($template);
}

/*--------- MetaWebLog API functions -----------*/

function metaWeblog_getCategories()
{
	global $service, $hostURL, $blogURL;
	$params = func_get_args();
	$result = api_login( $params[1], $params[2] );
	if( $result )
	{
		return $result;
	}

	$category = new Category();
	$category->open(false);


	$cat = array();
	while($category->id)
	{
		array_push( $cat, array( 
			'htmlUrl' => "$hostURL$blogURL/category/" . $category->label,
			'rssUrl' => "",
			'categoryName' => $category->label,
			'description' => $category->label,
			'title' => $category->label,
			'categoryid' => $category->id,
			'isPrimary' => true 
			) );
			
		if( !$category->shift() )
		{
			break;
		}
	}

	$category->close();
	return $cat;

}

/*
function mt_getCategoryList()
{
	global $service;
	$params = func_get_args();
	$result = api_login( $params[1], $params[2] );
	if( $result )
	{
		return $result;
	}

	$category = new Category();
	$category->open();

	$cat = array();
	$base_url = "http://" . $service['domain'] . $service['path'] . "/";
	while(1)
	{
		array_push( $cat, array( 
			'categoryName' => $category->name,
			'categoryId' => $category->id,
			'isPrimary' => true ) );
			
		if( !$category->shift() )
		{
			break;
		}
	}

	$category->close();
	return $cat;

}
*/

function metaWeblog_getRecentPosts()
{
	$params = func_get_args();
	$result = api_login( $params[1], $params[2] );
	if( $result )
	{
		return $result;
	}

	global $blog;

	$post = new Post();
	$post->open();
	$out = array();

	for($i=0; $post->_count > 0 && $i<$params[3]; $i++ )
	{
		array_push( $out, api_get_post( $post, "mt" ) );
		if( !$post->shift() )
		{
			break;
		}
	}

	$post->close();
	return $out;
}

function metaWeblog_getPost()
{
	$params = func_get_args();
	$result = api_login( $params[1], $params[2] );
	if( $result )
	{
		return $result;
	}

	$post = new Post();
	$post->open( intval( $params[0] ) );

	$ret = api_get_post( $post, "mt" );
	$post->close();

	return $ret;
}

function metaWeblog_newPost()
{
	$params = func_get_args();
	$result = api_login( $params[1], $params[2] );
	if( $result )
	{
		return $result;
	}

	$post = api_make_post( $params[3], $params[4] );

	if( !$post->add() )
	{
		$post->close();
		return new XMLRPCFault( 1, "Tattertools posting error" );
	}

	api_update_attaches($post->id );
	RSS::refresh();


	$id = "{$post->id}";
	$post->close();
	return $id;
}
 
function mt_setPostCategories()
{
	$params = func_get_args();

	$result = api_login( $params[1], $params[2] );
	if( $result )
	{
		return $result;
	}

	$post = new Post();
	if( !$post->open( $params[0] ) )
	{
		return new XMLRPCFault( 1, "Posting error" );
	}

	$category = "";
	foreach( $params[3] as $index => $cat )
	{
		if( $cat['isPrimary'] )
		{
			$category = $cat['categoryId'];
		}
	}
	if( $category )
	{
		$post->category = $category;
		$post->update();
	}
	$post->close();
	return 1;
}

function mt_getPostCategories()
{
	global $service;
	$params = func_get_args();
	$result = api_login( $params[1], $params[2] );
	if( $result )
	{
		return $result;
	}

	$post = new Post();
	$post->open( intval( $params[0] ) );

	$cat = array( $post->category );
	$post->close();


	return $cat;
}

function metaWeblog_editPost()
{
	$params = func_get_args();
	$result = api_login( $params[1], $params[2] );
	if( $result )
	{
		return $result;
	}

	$post = api_make_post( $params[3], $params[4], $params[0] );
	$post->created = null;
	if( !$post )
	{
		return new XMLRPCFault( 1, "Tattertools editing error" );
	}

	$ret = $post->update();

	// 기존 글의 파일들 지우기 (잘 찾아서)
	// 새로 업로드 된 파일들 옮기기
	api_update_attaches_with_replace( $post->id );
	RSS::refresh();

	$post->close();

	return $ret ? 1 : 0;
}

function metaWeblog_newMediaObject()
{
	global $owner;
	$params = func_get_args();
	$result = api_login( $params[1], $params[2] );
	if( $result )
	{
		return $result;
	}
	$mediaOjbect = $params[3]['bits'];

	$tmp_dir = ROOT. "/attach/temp";
	if( !is_dir( $tmp_dir ) )
	{
		mkdir( $tmp_dir );
		if( !is_dir( $tmp_dir ) )
		{
			return new XMLRPCFault( 1, "Can't Create Directory $tmp_dir" );
		}
		@chmod( $path, 0777 );
	}

	$file = array( 
		'name' => $params[3]['name'],
		'content' => $params[3]['bits'],
		'size' => count($params[3]['bits']) 
		);
		
	$attachment = api_addAttachment( $owner, 0, $file );
	if( !$attachment )
	{
		return new XMLRPCFault( 1, "Can't create file" );
	}
	$attachurl = array ( 'url' => getBlogURL() . "/attach/$owner/" . $attachment['name'] );
	return $attachurl;
}
?>
