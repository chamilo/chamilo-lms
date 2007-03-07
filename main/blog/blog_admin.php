<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 University of Ghent (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com

==============================================================================

    BLOG HOMEPAGE

	This file takes care of all blog navigation and displaying.

	@package dokeos.blogs
==============================================================================
*/

// name of the language file that needs to be included
$language_file = "blog";

include('../inc/global.inc.php');
require_once(api_get_path(LIBRARY_PATH) . "blog.lib.php");

$nameTools = get_lang("blog_management");


// showing the header if we are not in the learning path, if we are in
// the learning path, we do not include the banner so we have to explicitly
// include the stylesheet, which is normally done in the header
if ($_GET['origin'] != 'learnpath')
{
	Display::display_header($nameTools,'Blogs');
}
else
{
	echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$clarolineRepositoryWeb."css/default.css\"/>";
}
/*
==============================================================================
	PROCESSING..
==============================================================================
*/
if ($_POST['new_blog_submit'])
{
	Blog::create_blog($_POST['blog_name'],$_POST['blog_subtitle']);
}
if ($_POST['edit_blog_submit'])
{
	Blog::edit_blog($_POST['blog_id'],$_POST['blog_name'],$_POST['blog_subtitle']);
}
if ($_GET['action'] == 'visibility')
{
	Blog::change_blog_visibility(mysql_real_escape_string((int)$_GET['blog_id']));
}
if ($_GET['action'] == 'delete')
{
	Blog::delete_blog(mysql_real_escape_string((int)$_GET['blog_id']));
}


/*
==============================================================================
	DISPLAY
==============================================================================
*/
api_display_tool_title($nameTools);
//api_introductionsection(TOOL_BLOG);


	if ($_GET['action'] == 'add')
	{
		Blog::display_new_blog_form();
	}
	if ($_GET['action'] == 'edit')
	{
		Blog::display_edit_blog_form(mysql_real_escape_string((int)$_GET['blog_id']));
	}

	echo "<a href='".$_SERVER['PHP_SELF']."?action=add'>",
	"<img src='../img/blog_new.gif' border=\"0\" align=\"absmiddle\" alt='".get_lang('NewBlog')."'>&nbsp;&nbsp;".get_lang('AddBlog')."</a>";
	echo "<table width=\"100%\" border=\"0\" cellspacing=\"2\" class='data_table'>";
	echo	"<tr>",
				 "<th>",get_lang('Title'),"</th>\n",
				 "<th>",get_lang('Subtitle'),"</th>\n",
				 "<th>",get_lang('Modify'),"</th>\n",
			"</tr>\n";
	Blog::display_blog_list();
	echo "</table>";


// Display the footer
Display::display_footer();
?>