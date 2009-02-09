<?php //$Id: announcements.php 16702 2008-11-10 13:02:30Z elixir_inter $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos SPRL
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	info@dokeos.com

==============================================================================

    BLOG HOMEPAGE

	This file takes care of all blog navigation and displaying.

	@package dokeos.blogs
==============================================================================
*/

// name of the language file that needs to be included
$language_file = "blog";

include('../inc/global.inc.php');
$this_section=SECTION_COURSES;

$blog_table_attachment 	= Database::get_course_table(TABLE_BLOGS_ATTACHMENT);

/* ------------	ACCESS RIGHTS ------------ */
// notice for unauthorized people.
api_protect_course_script(true);

//------------ ONLY USERS REGISTERED IN THE COURSE----------------------
if((!$is_allowed_in_course || !$is_courseMember) && !api_is_allowed_to_edit())
{
	api_not_allowed(true);//print headers/footers
}


if (api_is_allowed_to_edit())
{
	
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
	if (!empty($_POST['new_blog_submit']))
	{
		Blog::create_blog($_POST['blog_name'],$_POST['blog_subtitle']);
	}
	if (!empty($_POST['edit_blog_submit']))
	{
		Blog::edit_blog($_POST['blog_id'],$_POST['blog_name'],$_POST['blog_subtitle']);
	}
	if (isset($_GET['action']) && $_GET['action'] == 'visibility')
	{
		Blog::change_blog_visibility(Database::scape_string((int)$_GET['blog_id']));
	}
	if (isset($_GET['action']) && $_GET['action'] == 'delete')
	{
		Blog::delete_blog(Database::scape_string((int)$_GET['blog_id']));
	}
	
	
	/*
	==============================================================================
		DISPLAY
	==============================================================================
	*/
	api_display_tool_title($nameTools);
	//api_introductionsection(TOOL_BLOG);
	
	
		if (isset($_GET['action']) && $_GET['action'] == 'add')
		{
			Blog::display_new_blog_form();
		}
		if (isset($_GET['action']) && $_GET['action'] == 'edit')
		{
			Blog::display_edit_blog_form(Database::escape_string((int)$_GET['blog_id']));
		}
	
		echo '<div class="actions">';
		echo "<a href='".api_get_self()."?".api_get_cidreq()."&action=add'>",Display::return_icon('blog_new.gif',get_lang('AddBlog')),get_lang('AddBlog')."</a>";
		echo '</div>';
		echo "<table width=\"100%\" border=\"0\" cellspacing=\"2\" class='data_table'>";
		echo	"<tr>",
					 "<th>",get_lang('Title'),"</th>\n",
					 "<th>",get_lang('Subtitle'),"</th>\n",
					 "<th>",get_lang('Modify'),"</th>\n",
				"</tr>\n";
		Blog::display_blog_list();
		echo "</table>";			
	}
	else
	{
		api_not_allowed(true);		
	}

// Display the footer
Display::display_footer();
?>