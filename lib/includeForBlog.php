<?php
/// Copyright (c) 2004-2007, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
require 'config.php';
include_once ROOT . '/config.php';
require 'function/string.php';
require 'function/time.php';
require 'function/javascript.php';
require 'function/html.php';
require 'function/xml.php';
require 'function/mysql.php';
require 'function/misc.php';
require 'function/image.php';
require 'function/mail.php';
require 'functions.php';
require 'database.php';
require 'model/blog.service.php';
require 'model/blog.archive.php';
require 'model/blog.attachment.php';
require 'model/blog.blogSetting.php';
require 'model/blog.category.php';
require 'model/blog.comment.php';
require 'model/blog.entry.php';
require 'model/blog.keyword.php';
require 'model/blog.notice.php';
require 'model/blog.link.php';
require 'model/blog.locative.php';
require 'model/common.paging.php';
require 'model/common.plugin.php';
require 'model/blog.sidebar.php';
require 'auth.php';
require 'model/common.setting.php';
require 'model/blog.statistics.php';
require 'model/blog.trackback.php';
require 'model/blog.tag.php';
require 'model/reader.common.php';
require 'suri.php';
if (!defined('NO_SESSION')) require 'session.php';
require 'model/blog.user.php';
require 'locale.php';
require 'plugins.php';
if (defined( 'TCDEBUG')) __tcSqlLogPoint('end of plugins.php');
require 'view/html.php';
require 'view/pages.php';
require 'view/paging.php';
require 'view/view.php';
require 'blog.skin.php';
if (defined( 'TCDEBUG')) __tcSqlLogPoint('end of blog.skin.php');
header('Content-Type: text/html; charset=utf-8');
?>