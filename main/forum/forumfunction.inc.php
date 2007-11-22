<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2006 Dokeos S.A.
	Copyright (c) 2006 Ghent University (UGent)

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/

/**
*	These files are a complete rework of the forum. The database structure is
*	based on phpBB but all the code is rewritten. A lot of new functionalities
*	are added:
* 	- forum categories and forums can be sorted up or down, locked or made invisible
*	- consistent and integrated forum administration
* 	- forum options: 	are students allowed to edit their post?
* 						moderation of posts (approval)
* 						reply only forums (students cannot create new threads)
* 						multiple forums per group
*	- sticky messages
* 	- new view option: nested view
* 	- quoting a message
*
*	@Author Patrick Cool <patrick.cool@UGent.be>, Ghent University
*	@Copyright Ghent University
*	@Copyright Patrick Cool
*
* 	@package dokeos.forum
*
* @todo several functions have to be moved to the itemmanager library
* @todo displaying icons => display library
* @todo complete the missing phpdoc the correct order should be
*
* 				some explanation of the function
*
* 				@param
* 				@return
*
 				@todo
*
* 				@author firstname lastname <email>, organisation
* 				@version (day) month year
*
* 				@deprecated
*/

/**
 **************************************************************************
 *						IMPORTANT NOTICE
 * Please do not change anything is this code yet because there are still
 * some significant code that need to happen and I do not have the time to
 * merge files and test it all over again. So for the moment, please do not
 * touch the code
 * 							-- Patrick Cool <patrick.cool@UGent.be>
 **************************************************************************
 */

include(api_get_path(INCLUDE_PATH).'/lib/mail.lib.inc.php');
include(api_get_path(INCLUDE_PATH).'/conf/mail.conf.php');

/**
* This function handles all the forum and forumcategories actions. This is a wrapper for the
* forum and forum categories. All this code code could go into the section where this function is
* called but this make the code there cleaner.
*
* @param
* @return
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function handle_forum_and_forumcategories()
{
	// Adding a forum category
	if (($_GET['action']=='add' AND $_GET['content']=='forumcategory') OR $_POST['SubmitForumCategory'] )
	{
		show_add_forumcategory_form();
	}
	// Adding a forum
	if ((($_GET['action']=='add' OR $_GET['action']=='edit') AND $_GET['content']=='forum') OR $_POST['SubmitForum'] )
	{
		if ($_GET['action']=='edit' and isset($_GET['id']) OR $_POST['SubmitForum'] )
		{
			$inputvalues=get_forums($_GET['id']); // note: this has to be cleaned first
		}
		show_add_forum_form($inputvalues);
	}
	// Edit a forum category
	if (($_GET['action']=='edit' AND $_GET['content']=='forumcategory' AND isset($_GET['id'])) OR $_POST['SubmitEditForumCategory'] )
	{
		$forum_category=get_forum_categories($_GET['id']); // note: this has to be cleaned first
		show_edit_forumcategory_form($forum_category);
	}
	// Delete a forum category
	if ($_GET['action']=='delete' AND isset($_GET['content']) AND isset($_GET['id']))
	{
		$return_message=delete_forum_forumcategory_thread($_GET['content'],$_GET['id']);// note: this has to be cleaned first
		Display :: display_confirmation_message($return_message,false);

	}
	// Change visibility of a forum or a forum category
	if (($_GET['action']=='invisible' OR $_GET['action']=='visible') AND isset($_GET['content']) AND isset($_GET['id']))
	{
		$return_message=change_visibility($_GET['content'], $_GET['id'],$_GET['action']);// note: this has to be cleaned first
		Display :: display_confirmation_message($return_message,false);
	}
	// Change lock status of a forum or a forum category
	if (($_GET['action']=='lock' OR $_GET['action']=='unlock') AND isset($_GET['content']) AND isset($_GET['id']))
	{
		$return_message=change_lock_status($_GET['content'], $_GET['id'],$_GET['action']);// note: this has to be cleaned first
		Display :: display_confirmation_message($return_message,false);
	}
	// Move a forum or a forum category
	if ($_GET['action']=='move' AND isset($_GET['content']) AND isset($_GET['id']) AND isset($_GET['direction']))
	{
		$return_message=move_up_down($_GET['content'], $_GET['direction'], $_GET['id']);// note: this has to be cleaned first
		Display :: display_confirmation_message($return_message,false);
	}

}

/**
* This function displays the form that is used to add a forum category.
*
* @param
* @return
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function show_add_forumcategory_form($inputvalues=array())
{
	// initiate the object
	$form = new FormValidator('forumcategory');

	// settting the form elements
	$form->addElement('header', '', get_lang('AddForumCategory'));
	$form->addElement('text', 'forum_category_title', get_lang('Title'),'class="input_titles"');
	$form->addElement('html_editor', 'forum_category_comment', get_lang('Comment'));
	$form->addElement('submit', 'SubmitForumCategory', 'OK');

	// setting the rules
	$form->addRule('forum_category_title', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');

	// The validation or display
	if( $form->validate() )
	{
	   $values = $form->exportValues();
	   store_forumcategory($values);
	}
	else
	{
		$form->display();
	}
}


/**
* This function displays the form that is used to add a forum category.
*
* @param
* @return
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function show_add_forum_form($inputvalues=array())
{
	global $_course;

	// initiate the object
	$form = new FormValidator('forumcategory', 'post', 'index.php');

	// the header for the form
	$form->addElement('header', '', get_lang('AddForum'));

	// we have a hidden field if we are editing
	if (is_array($inputvalues))
	{
		$form->addElement('hidden', 'forum_id', $inputvalues['forum_id']);
	}

	// The title of the forum
	$form->addElement('text', 'forum_title', get_lang('Title'),'class="input_titles"');

	// The comment of the forum
	$form->addElement('html_editor', 'forum_comment', get_lang('Comment'));

	// dropdown list: Forum Categories
	$forum_categories=get_forum_categories();
	foreach ($forum_categories as $key=>$value)
	{
		$forum_categories_titles[$value['cat_id']]=$value['cat_title'];
	}
	$form->addElement('select', 'forum_category', get_lang('InForumCategory'), $forum_categories_titles);

	if ($_course['visibility']==COURSE_VISIBILITY_OPEN_WORLD)
	{
		// This is for vertical
		//$form->addElement('radio', 'allow_anonymous', get_lang('AllowAnonymousPosts'), get_lang('Yes'), 1);
		//$form->addElement('radio', 'allow_anonymous', '', get_lang('No'), 0);
		// This is for horizontal
		$group='';
		$group[] =& HTML_QuickForm::createElement('radio', 'allow_anonymous',null,get_lang('Yes'),1);
		$group[] =& HTML_QuickForm::createElement('radio', 'allow_anonymous',null,get_lang('No'),0);
		$form->addGroup($group, 'allow_anonymous_group', get_lang('AllowAnonymousPosts'), '&nbsp;');
	}

	// This is for vertical
	//$form->addElement('radio', 'students_can_edit', get_lang('StudentsCanEdit'), get_lang('Yes'), 1);
	//$form->addElement('radio', 'students_can_edit', '', get_lang('No'), 0);
	// This is for horizontal
	$group='';
	$group[] =& HTML_QuickForm::createElement('radio', 'students_can_edit',null,get_lang('Yes'),1);
	$group[] =& HTML_QuickForm::createElement('radio', 'students_can_edit',null,get_lang('No'),0);
	$form->addGroup($group, 'students_can_edit_group', get_lang('StudentsCanEdit'), '&nbsp;');

	// This is for vertical
	//$form->addElement('radio', 'approval_direct', get_lang('ApprovalDirect'), get_lang('Approval'), 1);
	//$form->addElement('radio', 'approval_direct', '', get_lang('Direct'), 0);
	// This is for horizontal
	$group='';
	$group[] =& HTML_QuickForm::createElement('radio', 'approval_direct',null,get_lang('Approval'),1);
	$group[] =& HTML_QuickForm::createElement('radio', 'approval_direct',null,get_lang('Direct'),0);
	//$form->addGroup($group, 'approval_direct_group', get_lang('ApprovalDirect'), '&nbsp;');


	// This is for vertical
	//$form->addElement('radio', 'allow_attachments', get_lang('AllowAttachments'), get_lang('Yes'), 1);
	//$form->addElement('radio', 'allow_attachments', '', get_lang('No'), 0);
	// This is for horizontal
	$group='';
	$group[] =& HTML_QuickForm::createElement('radio', 'allow_attachments',null,get_lang('Yes'),1);
	$group[] =& HTML_QuickForm::createElement('radio', 'allow_attachments',null,get_lang('No'),0);
	//$form->addGroup($group, 'allow_attachments_group', get_lang('AllowAttachments'), '&nbsp;');

	// This is for vertical
	//$form->addElement('radio', 'allow_new_threads', get_lang('AllowNewThreads'), 1, get_lang('Yes'));
	//$form->addElement('radio', 'allow_new_threads', '', 0, get_lang('No'));
	// This is for horizontal
	$group='';
	$group[] =& HTML_QuickForm::createElement('radio', 'allow_new_threads',null, get_lang('Yes'),1);
	$group[] =& HTML_QuickForm::createElement('radio', 'allow_new_threads',null, get_lang('No'),0);
	$form->addGroup($group, 'allow_new_threads_group', get_lang('AllowNewThreads'), '&nbsp;');

	$group='';
	$group[] =& HTML_QuickForm::createElement('radio', 'default_view_type', null, get_lang('Flat'), 'flat');
	$group[] =& HTML_QuickForm::createElement('radio', 'default_view_type', null, get_lang('Threaded'), 'threaded');
	$group[] =& HTML_QuickForm::createElement('radio', 'default_view_type', null, get_lang('Nested'), 'nested');
	$form->addGroup($group, 'default_view_type_group', get_lang('DefaultViewType'), '&nbsp;');


	$form->addElement('static','Group', '<br /><strong>'.get_lang('GroupSettings').'</strong>');

	// dropdown list: Groups
	$groups=GroupManager::get_group_list();
	$groups_titles[0]=get_lang('NotAGroupForum');
	foreach ($groups as $key=>$value)
	{
		$groups_titles[$value['id']]=$value['name'];
	}
	$form->addElement('select', 'group_forum', get_lang('ForGroup'), $groups_titles);

	// Public or private group forum
	$group='';
	$group[] =& HTML_QuickForm::createElement('radio', 'public_private_group_forum', null, get_lang('Public'), 'public');
	$group[] =& HTML_QuickForm::createElement('radio', 'public_private_group_forum', null, get_lang('Private'), 'private');
	$form->addGroup($group, 'public_private_group_forum_group', get_lang('PublicPrivateGroupForum'), '&nbsp;');


	// The OK button
	$form->addElement('submit', 'SubmitForum', 'OK');

	// setting the rules
	$form->addRule('forum_title', get_lang('ThisFieldIsRequired'), 'required');
	$form->addRule('forum_category', get_lang('ThisFieldIsRequired'), 'required');


	// settings the defaults
	if (!is_array($inputvalues))
	{
		$defaults['allow_anonymous_group']['allow_anonymous']=0;
		$defaults['students_can_edit_group']['students_can_edit']=0;
		$defaults['approval_direct_group']['approval_direct']=0;
		$defaults['allow_attachments_group']['allow_attachments']=1;
		$defaults['allow_new_threads_group']['allow_new_threads']=1;
		$defaults['default_view_type_group']['default_view_type']=api_get_setting('default_forum_view');
		$defaults['public_private_group_forum_group']['public_private_group_forum']='public';
		if (isset($_GET['forumcategory']))
		{
			$defaults['forum_category']=$_GET['forumcategory'];
		}
	}
	else  // the default values when editing = the data in the table
	{
		$defaults['forum_id']=$inputvalues['forum_id'];
		$defaults['forum_title']=prepare4display($inputvalues['forum_title']);
		$defaults['forum_comment']=prepare4display($inputvalues['forum_comment']);
		$defaults['forum_category']=$inputvalues['forum_category'];
		$defaults['allow_anonymous_group']['allow_anonymous']=$inputvalues['allow_anonymous'];
		$defaults['students_can_edit_group']['students_can_edit']=$inputvalues['allow_edit'];
		$defaults['approval_direct_group']['approval_direct']=$inputvalues['approval_direct_post'];
		$defaults['allow_attachments_group']['allow_attachments']=$inputvalues['allow_attachments'];
		$defaults['allow_new_threads_group']['allow_new_threads']=$inputvalues['allow_new_threads'];
		$defaults['default_view_type_group']['default_view_type']=$inputvalues['default_view'];
		$defaults['public_private_group_forum_group']['public_private_group_forum']=$inputvalues['forum_group_public_private'];
		$defaults['group_forum']=$inputvalues['forum_of_group'];
	}
	$form->setDefaults($defaults);


	// The validation or display
	if( $form->validate() )
	{
	   $values = $form->exportValues();
	   store_forum($values);
	}
	else
	{
		$form->display();
	}
}



/**
* This function displays the form that is used to edit a forum category.
* This is more or less a copy from the show_add_forumcategory_form function with the only difference that is uses
* some default values. I tried to have both in one function but this gave problems with the handle_forum_and_forumcategories function
* (storing was done twice)
*
* @param
* @return
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function show_edit_forumcategory_form($inputvalues=array())
{
	// initiate the object
	$form = new FormValidator('forumcategory');

	// settting the form elements
	$form->addElement('header', '', get_lang('EditForumCategory'));
	$form->addElement('hidden', 'forum_category_id');
	$form->addElement('text', 'forum_category_title', get_lang('Title'),'class="input_titles"');
	$form->addElement('html_editor', 'forum_category_comment', get_lang('Comment'));
	$form->addElement('submit', 'SubmitEditForumCategory', 'OK');

	// setting the default values
	$defaultvalues['forum_category_id']=$inputvalues['cat_id'];
	$defaultvalues['forum_category_title']=prepare4display($inputvalues['cat_title']);
	$defaultvalues['forum_category_comment']=prepare4display($inputvalues['cat_comment']);
	$form->setDefaults($defaultvalues);

	// setting the rules
	$form->addRule('forum_category_title', get_lang('ThisFieldIsRequired'), 'required');

	// The validation or display
	if( $form->validate() )
	{
	   $values = $form->exportValues();
	   store_forumcategory($values);
	}
	else
	{
		$form->display();
	}
}



/**
* This function stores the forum category in the database. The new category is added to the end.
*
* @param
* @return
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function store_forumcategory($values)
{
	global $table_categories;
	global $_course;
	global $_user;

	// find the max cat_order. The new forum category is added at the end => max cat_order + &
	$sql="SELECT MAX(cat_order) as sort_max FROM ".mysql_real_escape_string($table_categories);
	$result=api_sql_query($sql);
	$row=mysql_fetch_array($result);
	$new_max=$row['sort_max']+1;

	if (isset($values['forum_category_id']))
	{ // storing an edit
		$sql="UPDATE ".$table_categories." SET cat_title='".mysql_real_escape_string($values['forum_category_title'])."', cat_comment='".mysql_real_escape_string($values['forum_category_comment'])."' WHERE cat_id='".mysql_real_escape_string($values['forum_category_id'])."'";
		api_sql_query($sql);
		$last_id=mysql_insert_id();
		api_item_property_update($_course, TOOL_FORUM_CATEGORY, $values['forum_category_id'],"ForumCategoryAdded", api_get_user_id());
		$return_message=get_lang('ForumCategoryEdited');
	}
	else
	{
		$sql="INSERT INTO ".$table_categories." (cat_title, cat_comment, cat_order) VALUES ('".mysql_real_escape_string($values['forum_category_title'])."','".mysql_real_escape_string($values['forum_category_comment'])."','".mysql_real_escape_string($new_max)."')";
		api_sql_query($sql);
		$last_id=mysql_insert_id();
		api_item_property_update($_course, TOOL_FORUM_CATEGORY, $last_id,"ForumCategoryAdded", api_get_user_id());
		$return_message=get_lang('ForumCategoryAdded');
	}

	Display :: display_confirmation_message($return_message);
}

/**
* This function stores the forum in the database. The new forum is added to the end.
*
* @param
* @return
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function store_forum($values)
{
	global $table_forums;
	global $_course;
	global $_user;

	// find the max forum_order for the given category. The new forum is added at the end => max cat_order + &
	$sql="SELECT MAX(forum_order) as sort_max FROM ".$table_forums." WHERE forum_category=".mysql_real_escape_string($values['forum_category']);
	$result=api_sql_query($sql);
	$row=mysql_fetch_array($result);
	$new_max=$row['sort_max']+1;


	if (isset($values['forum_id']))
	{ // storing an edit
		$sql="UPDATE ".$table_forums." SET
					forum_title='".mysql_real_escape_string($values['forum_title'])."',
					forum_comment='".mysql_real_escape_string($values['forum_comment'])."',
					forum_category='".mysql_real_escape_string($values['forum_category'])."',
					allow_anonymous='".mysql_real_escape_string($values['allow_anonymous_group']['allow_anonymous'])."',
					allow_edit='".mysql_real_escape_string($values['students_can_edit_group']['students_can_edit'])."',
					approval_direct_post='".mysql_real_escape_string($values['approval_direct_group']['approval_direct'])."',
					allow_attachments='".mysql_real_escape_string($values['allow_attachments_group']['allow_attachments'])."',
					allow_new_threads='".mysql_real_escape_string($values['allow_new_threads_group']['allow_new_threads'])."',
					forum_group_public_private='".mysql_real_escape_string($values['public_private_group_forum_group']['public_private_group_forum'])."',
					default_view='".mysql_real_escape_string($values['default_view_type_group']['default_view_type'])."',
					forum_of_group='".mysql_real_escape_string($values['group_forum'])."'
				WHERE forum_id='".mysql_real_escape_string($values['forum_id'])."'";
		mysql_query($sql) or die(mysql_error());
		$return_message=get_lang('ForumEdited');
	}
	else
	{
		$sql="INSERT INTO ".$table_forums."
					(forum_title, forum_comment, forum_category, allow_anonymous, allow_edit, approval_direct_post, allow_attachments, allow_new_threads, default_view, forum_of_group, forum_group_public_private, forum_order)
					VALUES ('".mysql_real_escape_string($values['forum_title'])."',
						'".mysql_real_escape_string($values['forum_comment'])."',
						'".mysql_real_escape_string($values['forum_category'])."',
						'".mysql_real_escape_string($values['allow_anonymous_group']['allow_anonymous'])."',
						'".mysql_real_escape_string($values['students_can_edit_group']['students_can_edit'])."',
						'".mysql_real_escape_string($values['approval_direct_group']['approval_direct'])."',
						'".mysql_real_escape_string($values['allow_attachments_group']['allow_attachments'])."',
						'".mysql_real_escape_string($values['allow_new_threads_group']['allow_new_threads'])."',
						'".mysql_real_escape_string($values['default_view_type_group']['default_view_type'])."',
						'".mysql_real_escape_string($values['group_forum'])."',
						'".mysql_real_escape_string($values['public_private_group_forum_group']['public_private_group_forum'])."',
						'".mysql_real_escape_string($new_max)."')";
		api_sql_query($sql, __LINE__,__FILE__);
		$last_id=mysql_insert_id();
		api_item_property_update($_course, TOOL_FORUM, $last_id,"ForumCategoryAdded", api_get_user_id());
		$return_message=get_lang('ForumAdded');

	}

	Display :: display_confirmation_message($return_message);
}

/**
* This function deletes a forum or a forum category
* This function currently does not delete the forums inside the category, nor the threads and replies inside these forums.
* For the moment this is the easiest method and it has the advantage that it allows to recover fora that were acidently deleted
* when the forum category got deleted.
*
* @param $content = what we are deleting (a forum or a forum category)
* @param $id The id of the forum category that has to be deleted.
*
* @todo write the code for the cascading deletion of the forums inside a forum category and also the threads and replies inside these forums
* @todo config setting for recovery or not (see also the documents tool: real delete or not).
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function delete_forum_forumcategory_thread($content, $id)
{
	global $_course;

	if ($content=='forumcategory')
	{
		$tool_constant=TOOL_FORUM_CATEGORY;
		$return_message=get_lang('ForumCategoryDeleted');
	}
	if ($content=='forum')
	{
		$tool_constant=TOOL_FORUM;
		$return_message=get_lang('ForumDeleted');
	}
	if ($content=='thread')
	{
		$tool_constant=TOOL_FORUM_THREAD;
		$return_message=get_lang('ThreadDeleted');
	}

	api_item_property_update($_course,$tool_constant,$id,"delete",api_get_user_id()); // note: check if this returns a true and if so => return $return_message, if not => return false;

	return $return_message;
}

/**
* This function deletes a forum post. This separate function is needed because forum posts do not appear in the item_property table (yet)
* and because deleting a post also has consequence on the posts that have this post as parent_id (they are also deleted).
* an alternative would be to store the posts also in item_property and mark this post as deleted (visibility = 2).
* We also have to decrease the number of replies in the thread table
*
* @param $post_id the id of the post that will be deleted
*
* @todo write recursive function that deletes all the posts that have this message as parent
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function delete_post($post_id)
{
	global $table_posts;
	global $table_threads;

	$sql="DELETE FROM $table_posts WHERE post_id='".mysql_real_escape_string($post_id)."'"; // note: this has to be a recursive function that deletes all of the posts in this block.
	api_sql_query($sql,__FILE__,__LINE__);

	$last_post_of_thread=check_if_last_post_of_thread($_GET['thread']); // note: clean the $_GET['thread']

	if (is_array($last_post_of_thread))
	{
		// Decreasing the number of replies for this thread and also changing the last post information
		$sql="UPDATE $table_threads SET thread_replies=thread_replies-1,
					thread_poster_id='".mysql_real_escape_string($last_post_of_thread['poster_id'])."',
					thread_last_post='".mysql_real_escape_string($last_post_of_thread['post_id'])."',
					thread_date='".mysql_real_escape_string($last_post_of_thread['post_date'])."'
			WHERE thread_id='".mysql_real_escape_string($_GET['thread'])."'";  // note: clean the $_GET['thread']
		api_sql_query($sql,__FILE__,__LINE__);
		return 'PostDeleted';
	}
	if ($last_post_of_thread==false)
	{
		// we deleted the very single post of the thread so we need to delete the entry in the thread table also.
		$sql="DELETE FROM $table_threads WHERE thread_id='".mysql_real_escape_string($_GET['thread'])."'";  // note: clean the $_GET['thread']
		api_sql_query($sql,__FILE__,__LINE__);
		return 'PostDeletedSpecial';
	}

}


/**
* This function gets the all information of the last (=most recent) post of the thread
* This can be done by sorting the posts that have the field thread_id=$thread_id and sort them by post_date
*
* @param $thread_id the id of the thread we want to know the last post of.
* @return an array if there is a last post found, false if there is no post entry linked to that thread => thread will be deleted
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function check_if_last_post_of_thread($thread_id)
{
	global $table_posts;

	$sql="SELECT * FROM $table_posts WHERE thread_id='".mysql_real_escape_string($thread_id)."' ORDER BY post_date DESC";
	$result=api_sql_query($sql,__FILE__,__LINE__);
	if (mysql_num_rows($result)>0)
	{
		$row=mysql_fetch_array($result);
		return $row;
	}
	else
	{
		return false;
	}
}


/**
* This function takes care of the display of the visibility icon
*
* @param $content what is it that we want to make (in)visible: forum category, forum, thread, post
* @param $id the id of the content we want to make invisible
* @param $current_visibility_status what is the current status of the visibility (0 = invisible, 1 = visible)
* @return
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function display_visible_invisible_icon($content, $id, $current_visibility_status, $additional_url_parameters='')
{
	if ($current_visibility_status=='1')
	{
		echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&';
		if (is_array($additional_url_parameters))
		{
			foreach ($additional_url_parameters as $key=>$value)
			{
				echo $key.'='.$value.'&amp;';
			}
		}
		echo 'action=invisible&amp;content='.$content.'&amp;id='.$id.'">'.icon('../img/visible.gif',get_lang('MakeInvisible')).'</a>';
	}
	if ($current_visibility_status=='0')
	{
		echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&';
		if (is_array($additional_url_parameters))
		{
			foreach ($additional_url_parameters as $key=>$value)
			{
				echo $key.'='.$value.'&amp;';
			}
		}
		echo 'action=visible&amp;content='.$content.'&amp;id='.$id.'">'.icon('../img/invisible.gif',get_lang('MakeVisible')).'</a>';
	}
}

/**
* This function takes care of the display of the lock icon
*
* @param $content what is it that we want to (un)lock: forum category, forum, thread, post
* @param $id the id of the content we want to (un)lock
* @param $current_visibility_status what is the current status of the visibility (0 = invisible, 1 = visible)
* @return
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function display_lock_unlock_icon($content, $id, $current_lock_status, $additional_url_parameters='')
{
	if ($current_lock_status=='1')
	{
		echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&';
		if (is_array($additional_url_parameters))
		{
			foreach ($additional_url_parameters as $key=>$value)
			{
				echo $key.'='.$value.'&amp;';
			}
		}
		echo 'action=unlock&amp;content='.$content.'&amp;id='.$id.'">'.icon('../img/lock.gif',get_lang('Unlock')).'</a>';
	}
	if ($current_lock_status=='0')
	{
		echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&';
		if (is_array($additional_url_parameters))
		{
			foreach ($additional_url_parameters as $key=>$value)
			{
				echo $key.'='.$value.'&amp;';
			}
		}
		echo 'action=lock&amp;content='.$content.'&amp;id='.$id.'">'.icon('../img/unlock.gif',get_lang('Lock')).'</a>';
	}
}

/**
* This function takes care of the display of the up and down icon
*
* @param $content what is it that we want to make (in)visible: forum category, forum, thread, post
* @param $id is the id of the item we want to display the icons for
* @param $list is an array of all the items. All items in this list should have an up and down icon except for the first (no up icon) and the last (no down icon)
* 		 The key of this $list array is the id of the item.
*
* @return
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function display_up_down_icon($content, $id, $list)
{
	$total_items=count($list);
	$position = 0;
	$internal_counter=0;

	if(is_array($list))
	{
		foreach ($list as $key=>$listitem)
		{
			$internal_counter++;
			if ($id==$key)
			{
				$position=$internal_counter;
			}
		}
	}
	if ($position>1)
	{
		$return_value='<a href="'.api_get_self().'?'.api_get_cidreq().'&action=move&amp;direction=up&amp;content='.$content.'&amp;id='.$id.'"><img src="../img/up.gif" /></a>';
	}
	if ($position<$total_items)
	{
		$return_value.='<a href="'.api_get_self().'?'.api_get_cidreq().'&action=move&amp;direction=down&amp;content='.$content.'&amp;id='.$id.'"><img src="../img/down.gif" /></a>';
	}

	echo $return_value;
}




/**
* This function changes the visibility in the database (item_property)
*
* @param $content what is it that we want to make (in)visible: forum category, forum, thread, post
* @param $id the id of the content we want to make invisible
* @param $target_visibility what is the current status of the visibility (0 = invisible, 1 = visible)
*
* @todo change the get parameter so that it matches the tool constants.
* @todo check if api_item_property_update returns true or false => returnmessage depends on it.
* @todo move to itemmanager
*
* @return
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function change_visibility($content, $id, $target_visibility)
{
	global $_course;
	$constants=array('forumcategory'=>TOOL_FORUM_CATEGORY, 'forum'=>TOOL_FORUM, 'thread'=>TOOL_FORUM_THREAD);
	api_item_property_update($_course,$constants[$content],$id,$target_visibility,api_get_user_id()); // note: check if this returns true or false => returnmessage depends on it.
	if ($target_visibility=='visible')
	{
		handle_mail_cue($content, $id);
	}

	return get_lang('VisibilityChanged');
}


/**
* This function changes the lock status in the database
*
* @param $content what is it that we want to (un)lock: forum category, forum, thread, post
* @param $id the id of the content we want to (un)lock
* @param $action do we lock (=>locked value in db = 1) or unlock (=> locked value in db = 0)
* @return string, language variable
*
* @todo move to itemmanager
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function change_lock_status($content, $id, $action)
{
	global $table_categories;
	global $table_forums;
	global $table_threads;
	global $table_posts;

	// Determine the relevant table
	if ($content=='forumcategory')
	{
		$table=$table_categories;
		$id_field='cat_id';
	}
	elseif ($content=='forum')
	{
		$table=$table_forums;
		$id_field='forum_id';
	}
	elseif ($content=='thread')
	{
		$table=$table_threads;
		$id_field='thread_id';
	}
	else
	{
		return get_lang('Error');
	}

	// Determine what we are doing => defines the value for the database and the return message
	if ($action=='lock')
	{
		$db_locked=1;
		$return_message=get_lang('Locked');
	}
	elseif ($action=='unlock')
	{
		$db_locked=0;
		$return_message=get_lang('Unlocked');
	}
	else
	{
		return get_lang('Error');
	}

	// Doing the change in the database
	$sql="UPDATE $table SET locked='".mysql_real_escape_string($db_locked)."' WHERE $id_field='".mysql_real_escape_string($id)."'";
	if (api_sql_query($sql))
	{
		return $return_message;
	}
	else
	{
		return get_lang('Error');
	}
}


/**
* This function moves a forum or a forum category up or down
*
* @param $content what is it that we want to make (in)visible: forum category, forum, thread, post
* @param $direction do we want to move it up or down.
* @param $id the id of the content we want to make invisible
* @todo consider removing the table_item_property calls here but this can prevent unwanted side effects when a forum does not have an entry in
* 		the item_property table but does have one in the forum table.
* @return
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function move_up_down($content, $direction, $id)
{
	global $table_categories;
	global $table_forums;
	global $table_item_property;


	// Determine which field holds the sort order
	if ($content=='forumcategory')
	{
		$table=$table_categories;
		$sort_column='cat_order';
		$id_column='cat_id';
		$sort_column='cat_order';
	}
	elseif ($content=='forum')
	{
		$table=$table_forums;
		$sort_column='forum_order';
		$id_column='forum_id';
		$sort_column='forum_order';
		// we also need the forum_category of this forum
		$sql="SELECT forum_category FROM $table_forums WHERE forum_id=".mysql_real_escape_string($id);
		$result=api_sql_query($sql);
		$row=mysql_fetch_array($result);
		$forum_category=$row['forum_category'];
	}
	else
	{
		return get_lang('Error');
	}

	// determine if need to sort ascending or descending
	if ($direction=='down')
	{
		$sort_direction='ASC';
	}
	elseif ($direction=='up')
	{
		$sort_direction='DESC';
	}
	else
	{
		return get_lang('Error');
	}

	// The SQL statement
	if ($content=='forumcategory')
	{
		$sql="SELECT * FROM".$table_categories." forum_categories, ".$table_item_property." item_properties
					WHERE forum_categories.cat_id=item_properties.ref
					AND item_properties.tool='".TOOL_FORUM_CATEGORY."'
					ORDER BY forum_categories.cat_order $sort_direction";
	}
	if ($content=='forum')
	{
		$sql="SELECT * FROM".$table." WHERE forum_category='".mysql_real_escape_string($forum_category)."' ORDER BY forum_order $sort_direction";
	}


	// echo $sql.'<br />';


	// finding the items that need to be switched
	$result=api_sql_query($sql);
	$found=false;
	while ($row=mysql_fetch_array($result))
	{
		//echo $row[$id_column].'-';
		if ($found==true)
		{
			$next_id=$row[$id_column];
			$next_sort=$row[$sort_column];
			$found=false;
		}
		if($id==$row[$id_column])
		{
			$this_id=$id;
			$this_sort=$row[$sort_column];
			$found=true;
		}
	}

	// Committing the switch
	// we do an extra check if we do not have illegal values. If your remove this if statment you will
	// be able to mess with the sorting by refreshing the page over and over again.
	if ($this_sort<>'' AND $next_sort<>'' AND $next_id<>'' AND $this_id<>'')
	{
		$sql_update1="UPDATE $table SET $sort_column='".mysql_real_escape_string($this_sort)."' WHERE $id_column='".mysql_real_escape_string($next_id)."'";
		$sql_update2="UPDATE $table SET $sort_column='".mysql_real_escape_string($next_sort)."' WHERE $id_column='".mysql_real_escape_string($this_id)."'";
		api_sql_query($sql_update1);
		api_sql_query($sql_update2);
	}

	return get_lang(ucfirst($content).'Moved');
}


/**
* This function returns a piece of html code that make the links grey (=invisible for the student)
*
* @param boolean 0/1: 0 = invisible, 1 = visible
* @return string
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function class_visible_invisible($current_visibility_status)
{
	if ($current_visibility_status=='0')
	{
		return "class='invisible'";
	}
}

/**
* Retrieve all the information off the forum categories (or one specific) for the current course.
* The categories are sorted according to their sorting order (cat_order
*
* @param $id default ''. When an id is passed we only find the information about that specific forum category. If no id is passed we get all the forum categories.
* @return an array containing all the information about all the forum categories
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function get_forum_categories($id='')
{
	global $table_categories;
	global $table_item_property;

	if ($id=='')
	{
		$sql="SELECT * FROM".$table_categories." forum_categories, ".$table_item_property." item_properties
					WHERE forum_categories.cat_id=item_properties.ref
					AND item_properties.visibility=1
					AND item_properties.tool='".TOOL_FORUM_CATEGORY."'
					ORDER BY forum_categories.cat_order ASC";
		if (is_allowed_to_edit())
		{
			$sql="SELECT * FROM".$table_categories." forum_categories, ".$table_item_property." item_properties
					WHERE forum_categories.cat_id=item_properties.ref
					AND item_properties.visibility<>2
					AND item_properties.tool='".TOOL_FORUM_CATEGORY."'
					ORDER BY forum_categories.cat_order ASC";
		}
	}
	else
	{
		$sql="SELECT * FROM".$table_categories." forum_categories, ".$table_item_property." item_properties
				WHERE forum_categories.cat_id=item_properties.ref
				AND item_properties.tool='".TOOL_FORUM_CATEGORY."'
				AND forum_categories.cat_id='".mysql_real_escape_string($id)."'
				ORDER BY forum_categories.cat_order ASC";
	}
	$result=api_sql_query($sql);
	while ($row=mysql_fetch_array($result))
	{
		if ($id=='')
		{
			$forum_categories_list[$row['cat_id']]=$row;
		}
		else
		{
			$forum_categories_list=$row;
		}
	}
	return $forum_categories_list;
}

/**
* This function retrieves all the fora in a given forum category
*
* @param integer $cat_id the id of the forum category
* @return an array containing all the information about the forums (regardless of their category)
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function get_forums_in_category($cat_id)
{
	global $table_forums;
	global $table_item_property;

	$sql="SELECT * FROM ".$table_forums." forum , ".$table_item_property." item_properties
				WHERE forum.forum_category='".mysql_real_escape_string($cat_id)."'
				AND forum.forum_id=item_properties.ref
				AND item_properties.visibility=1
				AND item_properties.tool='".TOOL_FORUM."'
				ORDER BY forum.forum_order ASC";
	if (is_allowed_to_edit())
	{
		$sql="SELECT * FROM ".$table_forums." forum , ".$table_item_property." item_properties
				WHERE forum.forum_category='".mysql_real_escape_string($cat_id)."'
				AND forum.forum_id=item_properties.ref
				AND item_properties.visibility<>2
				AND item_properties.tool='".TOOL_FORUM."'
				ORDER BY forum_order ASC";
	}
	$result=api_sql_query($sql);
	while ($row=mysql_fetch_array($result))
	{
		$forum_list[$row['forum_id']]=$row;
	}
	return $forum_list;
}
/**
* Retrieve all the forums (regardless of their category) or of only one. The forums are sorted according to the forum_order.
* Since it does not take the forum category into account there probably will be two or more forums that have forum_order=1, ...
*
* @return an array containing all the information about the forums (regardless of their category)
* @todo check $sql4 because this one really looks fishy.
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function get_forums($id='')
{
	global $table_forums;
	global $table_threads;
	global $table_posts;
	global $table_item_property;
	global $table_users;

	// **************** GETTING ALL THE FORUMS ************************* //
	if ($id=='')
	{
		//-------------- Student -----------------//
		// select all the forum information of all forums (that are visible to students)
		$sql="SELECT * FROM ".$table_forums." forum , ".$table_item_property." item_properties
					WHERE forum.forum_id=item_properties.ref
					AND item_properties.visibility=1
					AND item_properties.tool='".TOOL_FORUM."'
					ORDER BY forum.forum_order ASC";
		// select the number of threads of the forums (only the threads that are visible)
		$sql2="SELECT count(*) AS number_of_threads, threads.forum_id FROM $table_threads threads, ".$table_item_property." item_properties
						WHERE threads.thread_id=item_properties.ref
						AND item_properties.visibility=1
						AND item_properties.tool='".TOOL_FORUM_THREAD."'
						GROUP BY threads.forum_id";
		// select the number of posts of the forum (post that are visible and that are in a thread that is visible)
		$sql3="SELECT count(*) AS number_of_posts, posts.forum_id FROM $table_posts posts, $table_threads threads, ".$table_item_property." item_properties
				WHERE posts.visible=1
				AND posts.thread_id=threads.thread_id
				AND threads.thread_id=item_properties.ref
				AND item_properties.visibility=1
				AND item_properties.tool='".TOOL_FORUM_THREAD."'
				GROUP BY threads.forum_id";

		//-------------- Course Admin  -----------------//
		if (is_allowed_to_edit())
		{
			// select all the forum information of all forums (that are not deleted)
			$sql="SELECT * FROM ".$table_forums." forum , ".$table_item_property." item_properties
							WHERE forum.forum_id=item_properties.ref
							AND item_properties.visibility<>2
							AND item_properties.tool='".TOOL_FORUM."'
							ORDER BY forum_order ASC";
			//echo $sql.'<hr>';
			// select the number of threads of the forums (only the threads that are not deleted)
			$sql2="SELECT count(*) AS number_of_threads, threads.forum_id FROM $table_threads threads, ".$table_item_property." item_properties
							WHERE threads.thread_id=item_properties.ref
							AND item_properties.visibility<>2
							AND item_properties.tool='".TOOL_FORUM_THREAD."'
							GROUP BY threads.forum_id";
			//echo $sql2.'<hr>';
			// select the number of posts of the forum
			$sql3="SELECT count(*) AS number_of_posts, forum_id FROM $table_posts GROUP BY forum_id";
			//echo $sql3.'<hr>';
		}


	}
	// **************** GETTING ONE SPECIFIC FORUM ************************* //
	// We could do the splitup into student and course admin also but we want to have as much as information about a certain forum as possible
	// so we do not take too much information into account. This function (or this section of the function) is namely used to fill the forms
	// when editing a forum (and for the moment it is the only place where we use this part of the function)
	else
	{
		// select all the forum information of the given forum (that is not deleted)
		$sql="SELECT * FROM ".$table_forums." forum , ".$table_item_property." item_properties
							WHERE forum.forum_id=item_properties.ref
							AND forum_id='".mysql_real_escape_string($id)."'
							AND item_properties.visibility<>2
							AND item_properties.tool='".TOOL_FORUM."'
							ORDER BY forum_order ASC";
		// select the number of threads of the forum
		$sql2="SELECT count(*) AS number_of_threads, forum_id FROM $table_threads WHERE forum_id=".mysql_real_escape_string($id)." GROUP BY forum_id";
		// select the number of posts of the forum
		$sql3="SELECT count(*) AS number_of_posts, forum_id FROM $table_posts WHERE forum_id=".mysql_real_escape_string($id)." GROUP BY forum_id";
		// select the last post and the poster (note: this is probably no longer needed)
		$sql4="SELECT  post.post_id, post.forum_id, post.poster_id, post.poster_name, post.post_date, users.lastname, users.firstname
					FROM $table_posts post, $table_users users
					WHERE forum_id=".mysql_real_escape_string($id)."
					AND post.poster_id=users.user_id
					GROUP BY post.forum_id
					ORDER BY post.post_id ASC";
	}

	// handling all the forum information
	$result=api_sql_query($sql);
	while ($row=mysql_fetch_array($result))
	{
		if ($id=='')
		{
			$forum_list[$row['forum_id']]=$row;
		}
		else
		{
			$forum_list=$row;
		}
	}

	// handling the threadcount information
	$result2=api_sql_query($sql2);
	while ($row2=mysql_fetch_array($result2))
	{
		if ($id=='')
		{
			$forum_list[$row2['forum_id']]['number_of_threads']=$row2['number_of_threads'];
		}
		else
		{
			$forum_list['number_of_threads']=$row2['number_of_threads'];;
		}
	}
	// handling the postcount information
	$result3=api_sql_query($sql3);
	while ($row3=mysql_fetch_array($result3))
	{
		if ($id=='')
		{
			if (array_key_exists($row3['forum_id'],$forum_list)) // this is needed because sql3 takes also the deleted forums into account
			{
				$forum_list[$row3['forum_id']]['number_of_posts']=$row3['number_of_posts'];
			}
		}
		else
		{
			$forum_list['number_of_posts']=$row3['number_of_posts'];
		}
	}

	// finding the last post information (last_post_id, last_poster_id, last_post_date, last_poster_name, last_poster_lastname, last_poster_firstname)
	if ($id=='')
	{
		if(is_array($forum_list))
		{
			foreach ($forum_list as $key=>$value)
			{
				$last_post_info_of_forum=get_last_post_information($key,is_allowed_to_edit());
				$forum_list[$key]['last_post_id']=$last_post_info_of_forum['last_post_id'];
				$forum_list[$key]['last_poster_id']=$last_post_info_of_forum['last_poster_id'];
				$forum_list[$key]['last_post_date']=$last_post_info_of_forum['last_post_date'];
				$forum_list[$key]['last_poster_name']=$last_post_info_of_forum['last_poster_name'];
				$forum_list[$key]['last_poster_lastname']=$last_post_info_of_forum['last_poster_lastname'];
				$forum_list[$key]['last_poster_firstname']=$last_post_info_of_forum['last_poster_firstname'];
			}
		}
		else
		{
			$forum_list = array();
		}
	}
	else
	{
		$last_post_info_of_forum=get_last_post_information($id,is_allowed_to_edit());
		$forum_list['last_post_id']=$last_post_info_of_forum['last_post_id'];
		$forum_list['last_poster_id']=$last_post_info_of_forum['last_poster_id'];
		$forum_list['last_post_date']=$last_post_info_of_forum['last_post_date'];
		$forum_list['last_poster_name']=$last_post_info_of_forum['last_poster_name'];
		$forum_list['last_poster_lastname']=$last_post_info_of_forum['last_poster_lastname'];
		$forum_list['last_poster_firstname']=$last_post_info_of_forum['last_poster_firstname'];
	}
	return $forum_list;
}

/**
* This functions gets all the last post information of a certain forum
*
* @param $forum_id the id of the forum we want to know the last post information of.
* @param $show_invisibles
* @return array containing all the information about the last post (last_post_id, last_poster_id, last_post_date, last_poster_name, last_poster_lastname, last_poster_firstname)
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function get_last_post_information($forum_id, $show_invisibles=false)
{
	global $table_forums;
	global $table_threads;
	global $table_posts;
	global $table_item_property;
	global $table_users;

	$sql="SELECT post.post_id, post.forum_id, post.poster_id, post.poster_name, post.post_date, users.lastname, users.firstname, post.visible, thread_properties.visibility AS thread_visibility, forum_properties.visibility AS forum_visibility
				FROM $table_posts post, $table_users users, $table_item_property thread_properties,  $table_item_property forum_properties
				WHERE post.forum_id=".mysql_real_escape_string($forum_id)."
				AND post.poster_id=users.user_id
				AND post.thread_id=thread_properties.ref
				AND thread_properties.tool='".TOOL_FORUM_THREAD."'
				AND post.forum_id=forum_properties.ref
				AND forum_properties.tool='".TOOL_FORUM."'
				ORDER BY post.post_id DESC";
	$result=api_sql_query($sql,__LINE__,__FILE__);
	if ($show_invisibles==true)
	{
		$row=mysql_fetch_array($result);
		$return_array['last_post_id']=$row['post_id'];
		$return_array['last_poster_id']=$row['poster_id'];
		$return_array['last_post_date']=$row['post_date'];
		$return_array['last_poster_name']=$row['poster_name'];
		$return_array['last_poster_lastname']=$row['lastname'];
		$return_array['last_poster_firstname']=$row['firstname'];
		return $return_array;
	}
	else
	{
		// we have to loop through the results to find the first one that is actually visible to students (forum_category, forum, thread AND post are visible)
		while ($row=mysql_fetch_array($result))
		{
			if ($row['visible']=='1' AND $row['thread_visibility']=='1' AND $row['forum_visibility']=='1')
			{
				$return_array['last_post_id']=$row['post_id'];
				$return_array['last_poster_id']=$row['poster_id'];
				$return_array['last_post_date']=$row['post_date'];
				$return_array['last_poster_name']=$row['poster_name'];
				$return_array['last_poster_lastname']=$row['lastname'];
				$return_array['last_poster_firstname']=$row['firstname'];
				return $return_array;
			}
		}
	}
}

/**
* Retrieve all the threads of a given forum
*
* @param
* @return an array containing all the information about the threads
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function get_threads($forum_id)
{
	global $table_item_property;
	global $table_threads;
	global $table_posts;
	global $table_users;

	// important note: 	it might seem a little bit awkward that we have 'thread.locked as locked' in the sql statement
	//					because we also have thread.* in it. This is because thread has a field locked and post also has the same field
	// 					since we are merging these we would have the post.locked value but in fact we want the thread.locked value
	//					This is why it is added to the end of the field selection

	
	$sql = "SELECT thread.*, item_properties.*, post.*, users.firstname, users.lastname, users.user_id,
				last_poster_users.firstname as last_poster_firstname , last_poster_users.lastname as last_poster_lastname, last_poster_users.user_id as last_poster_user_id, thread.locked as locked
			FROM $table_threads thread
			INNER JOIN $table_item_property item_properties
				ON thread.thread_id=item_properties.ref
				AND item_properties.visibility='1'
				AND item_properties.tool='".TOOL_FORUM_THREAD."'
			LEFT JOIN $table_users users
				ON thread.thread_poster_id=users.user_id
			LEFT JOIN $table_posts post
				ON thread.thread_last_post = post.post_id
			LEFT JOIN $table_users last_poster_users
				ON post.poster_id= last_poster_users.user_id
			WHERE thread.forum_id='".mysql_real_escape_string($forum_id)."'
			ORDER BY thread.thread_sticky DESC, thread.thread_date DESC";
	if (is_allowed_to_edit())
	{
		// important note: 	it might seem a little bit awkward that we have 'thread.locked as locked' in the sql statement
		//					because we also have thread.* in it. This is because thread has a field locked and post also has the same field
		// 					since we are merging these we would have the post.locked value but in fact we want the thread.locked value
		//					This is why it is added to the end of the field selection
		$sql = "SELECT thread.*, item_properties.*, post.*, users.firstname, users.lastname, users.user_id,
					last_poster_users.firstname as last_poster_firstname , last_poster_users.lastname as last_poster_lastname, last_poster_users.user_id as last_poster_user_id, thread.locked as locked
				FROM $table_threads thread
				INNER JOIN $table_item_property item_properties
					ON thread.thread_id=item_properties.ref
					AND item_properties.visibility<>2
					AND item_properties.tool='".TOOL_FORUM_THREAD."'
				LEFT JOIN $table_users users
					ON thread.thread_poster_id=users.user_id
				LEFT JOIN $table_posts post
					ON thread.thread_last_post = post.post_id
				LEFT JOIN $table_users last_poster_users
					ON post.poster_id= last_poster_users.user_id
				WHERE thread.forum_id='".mysql_real_escape_string($forum_id)."'
				ORDER BY thread.thread_sticky DESC, thread.thread_date DESC";
	}
	$result=api_sql_query($sql);
	while ($row=mysql_fetch_assoc($result))
	{
		$thread_list[]=$row;
	}
	return $thread_list;
}

/**
* Retrieve all posts of a given thread
*
* @return an array containing all the information about the posts of a given thread
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function get_posts($thread_id)
{
	global $table_posts;
	global $table_users;

	// note: change these SQL so that only the relevant fields of the user table are used
	if (api_is_allowed_to_edit())
	{
		$sql = "SELECT * FROM $table_posts posts
				LEFT JOIN  $table_users users
					ON posts.poster_id=users.user_id
				WHERE posts.thread_id='".mysql_real_escape_string($thread_id)."'
				ORDER BY posts.post_id ASC";
	}
	else
	{
		// students can only se the posts that are approved (posts.visible='1')
		$sql = "SELECT * FROM $table_posts posts
				LEFT JOIN  $table_users users
					ON posts.poster_id=users.user_id
				WHERE posts.thread_id='".mysql_real_escape_string($thread_id)."'
				AND posts.visible='1'
				ORDER BY posts.post_id ASC";
	}
	$result=api_sql_query($sql, __FILE__, __LINE__);
	while ($row=mysql_fetch_array($result))
	{
		$post_list[]=$row;
	}
	return $post_list;
}

/**
* This function return the html syntax for the image
*
* @param $image_url The url of the image (absolute or relative)
* @param $alt The alt text (when the images cannot be displayed). http://www.w3.org/TR/html4/struct/objects.html#adef-alt
* @param $title The title of the image. Most browsers display this as 'tool tip'. http://www.w3.org/TR/html4/struct/global.html#adef-title
*
* @todo this is the same as the Display::xxx function, so it can be removed => all calls have to be changed also
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function icon($image_url,$alt='',$title='')
{
	if ($title=='')
	{
		$title=$alt;
	}
	return '<img src="'.$image_url.'" alt="'.$alt.'" title="'.$title.'" />';
}






/**************************************************************************
					NEW TOPIC FUNCTIONS
**************************************************************************/

/**
* This function retrieves all the information of a post
*
* @param $forum_id integer that indicates the forum
* @return array returns
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function get_post_information($post_id)
{
	global $table_posts;
	global $table_users;

	$sql="SELECT * FROM ".$table_posts."posts, ".$table_users." users WHERE posts.poster_id=users.user_id AND posts.post_id='".mysql_real_escape_string($post_id)."'";
	$result=api_sql_query($sql);
	$row=mysql_fetch_array($result);
	return $row;
}


/**
* This function retrieves all the information of a thread
*
* @param $forum_id integer that indicates the forum
* @return array returns
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function get_thread_information($thread_id)
{
	global $table_threads;
	global $table_item_property;

	$sql="SELECT * FROM ".$table_threads." threads, ".$table_item_property." item_properties
			WHERE item_properties.tool='".TOOL_FORUM_THREAD."'
			AND item_properties.ref='".mysql_real_escape_string($thread_id)."'
			AND threads.thread_id='".mysql_real_escape_string($thread_id)."'";
	$result=api_sql_query($sql);
	$row=mysql_fetch_array($result);
	return $row;
}


/**
* This function retrieves all the information of a given forum_id
*
* @param $forum_id integer that indicates the forum
* @return array returns
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*
* @deprecated this functionality is now moved to get_forums($forum_id)
*/
function get_forum_information($forum_id)
{
	global $table_forums;
	global $table_item_property;

	$sql="SELECT * FROM ".$table_forums." forums, ".$table_item_property." item_properties
			WHERE item_properties.tool='".TOOL_FORUM."'
			AND item_properties.ref='".mysql_real_escape_string($forum_id)."'
			AND forums.forum_id='".mysql_real_escape_string($forum_id)."'";
	$result=api_sql_query($sql);
	$row=mysql_fetch_array($result);
	return $row;
}

/**
* This function retrieves all the information of a given forumcategory id
*
* @param $forum_id integer that indicates the forum
* @return array returns
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function get_forumcategory_information($cat_id)
{
	global $table_categories;
	global $table_item_property;

	$sql="SELECT * FROM ".$table_categories." forumcategories, ".$table_item_property." item_properties
			WHERE item_properties.tool='".TOOL_FORUM_CATEGORY."'
			AND item_properties.ref='".mysql_real_escape_string($cat_id)."'
			AND forumcategories.cat_id='".mysql_real_escape_string($cat_id)."'";
	$result=api_sql_query($sql);
	$row=mysql_fetch_array($result);
	return $row;
}

/**
* This function counts the number of forums inside a given category
*
* @param $cat_id the id of the forum category
* @todo an additional parameter that takes the visibility into account. For instance $countinvisible=0 would return the number
* 		of visible forums, $countinvisible=1 would return the number of visible and invisible forums
* @return int the number of forums inside the given category
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function count_number_of_forums_in_category($cat_id)
{
	global $table_forums;

	$sql="SELECT count(*) AS number_of_forums FROM ".$table_forums." WHERE forum_category='".mysql_real_escape_string($cat_id)."'";
	$result=api_sql_query($sql);
	$row=mysql_fetch_array($result);
	return $row['number_of_forums'];
}

/**
* This function stores a new thread. This is done through an entry in the forum_thread table AND
* in the forum_post table because. The threads are also stored in the item_property table. (forum posts are not (yet))
*
* @param
* @return
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function store_thread($values)
{
	global $table_threads;
	global $table_posts;
	global $_user;
	global $_course;
	global $current_forum;
	global $origin;

	$post_date=date('Y-m-d H:i:s');

	if ($current_forum['approval_direct_post']=='1' AND !api_is_allowed_to_edit())
	{
		$visible=0; // the post is not approved yet.
	}
	else
	{
		$visible=1;
	}

	// We first store an entry in the forum_thread table because the thread_id is used in the forum_post table
	$sql="INSERT INTO $table_threads (thread_title, forum_id, thread_poster_id, thread_poster_name, thread_date, thread_sticky)
			VALUES ('".mysql_real_escape_string($values['post_title'])."',
					'".mysql_real_escape_string($values['forum_id'])."',
					'".mysql_real_escape_string($_user['user_id'])."',
					'".mysql_real_escape_string($values['poster_name'])."',
					'".mysql_real_escape_string($post_date)."',
					'".mysql_real_escape_string($values['thread_sticky'])."')";
	$result=api_sql_query($sql, __LINE__, __FILE__);
	$last_thread_id=mysql_insert_id();
	api_item_property_update($_course, TOOL_FORUM_THREAD, $last_thread_id,"ForumThreadAdded", api_get_user_id());
	// if the forum properties tell that the posts have to be approved we have to put the whole thread invisible
	// because otherwise the students will see the thread and not the post in the thread.
	// we also have to change $visible because the post itself has to be visible in this case (otherwise the teacher would have
	// to make the thread visible AND the post
	if ($visible==0)
	{
		api_item_property_update($_course, TOOL_FORUM_THREAD, $last_thread_id,"invisible", api_get_user_id());
		$visible=1;
	}


	// We now store the content in the table_post table
	$sql="INSERT INTO $table_posts (post_title, post_text, thread_id, forum_id, poster_id, poster_name, post_date, post_notification, post_parent_id, visible)
			VALUES ('".mysql_real_escape_string($values['post_title'])."',
			'".mysql_real_escape_string($values['post_text'])."',
			'".mysql_real_escape_string($last_thread_id)."',
			'".mysql_real_escape_string($values['forum_id'])."',
			'".mysql_real_escape_string($_user['user_id'])."',
			'".mysql_real_escape_string($values['poster_name'])."',
			'".mysql_real_escape_string($post_date)."',
			'".mysql_real_escape_string($values['post_notification'])."','0',
			'".mysql_real_escape_string($visible)."')";
	$result=api_sql_query($sql, __LINE__, __FILE__);
	$last_post_id=mysql_insert_id();

	// Storing the attachments if any
	// store_resources('forum_post',$last_post_id);

	// now have to update the thread table to fill the thread_last_post field (so that we know when the thread has been updated for the last time)
	$sql="UPDATE $table_threads SET thread_last_post='".mysql_real_escape_string($last_post_id)."'  WHERE thread_id='".mysql_real_escape_string($last_thread_id)."'";
	$result=api_sql_query($sql, __LINE__, __FILE__);

	$message=get_lang('NewThreadStored').'<br />';
	if ($current_forum['approval_direct_post']=='1' AND !api_is_allowed_to_edit())
	{
		$message.=get_lang('MessageHasToBeApproved').'<br />';
		$message.=get_lang('ReturnTo').' <a href="viewforum.php?'.api_get_cidreq().'&forum='.$values['forum_id'].'">'.get_lang('Forum').'</a><br />';
	}
	else
	{
		$message.=get_lang('ReturnTo').' <a href="viewforum.php?'.api_get_cidreq().'&forum='.$values['forum_id'].'&origin='.$origin.'">'.get_lang('Forum').'</a><br />';
		$message.=get_lang('ReturnTo').' <a href="viewthread.php?'.api_get_cidreq().'&forum='.$values['forum_id'].'&origin='.$origin.'&amp;thread='.$last_thread_id.'">'.get_lang('Message').'</a>';
	}

	session_unregister('formelements');
	session_unregister('origin');
	session_unregister('breadcrumbs');
	session_unregister('addedresource');
	session_unregister('addedresourceid');

	Display :: display_confirmation_message($message,false);
}
/**
* This function displays the form that is used to add a post. This can be a new thread or a reply.
* @param $action is the parameter that determines if we are
*					1. newthread: adding a new thread (both empty) => No I-frame
*					2. replythread: Replying to a thread ($action = replythread) => I-frame with the complete thread (if enabled)
*					3. replymessage: Replying to a message ($action =replymessage) => I-frame with the complete thread (if enabled) (I first thought to put and I-frame with the message only)
* 					4. quote: Quoting a message ($action= quotemessage) => I-frame with the complete thread (if enabled). The message will be in the reply. (I first thought not to put an I-frame here)
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function show_add_post_form($action='', $id='', $form_values='')
{
	global $forum_setting;
	global $current_forum;
	global $_user;
	global $origin;

	// initiate the object
	$form = new FormValidator('thread', 'post', api_get_self().'?forum='.$_GET['forum'].'&thread='.$_GET['thread'].'&post='.$_GET['post'].'&action='.$_GET['action'].'&origin='.$origin);
	$form->setConstants(array('forum' => '5'));

	// settting the form elements
	$form->addElement('hidden', 'forum_id', $_GET['forum']);
	$form->addElement('hidden', 'thread_id', $_GET['thread']);

	// if anonymous posts are allowed we also display a form to allow the user to put his name or username in
	if ($current_forum['allow_anonymous']==1 AND !isset($_user['user_id']))
	{
		$form->addElement('text', 'poster_name', get_lang('Name'));
	}

	$form->addElement('text', 'post_title', get_lang('Title'),'class="input_titles"');
	$form->addElement('html_editor', 'post_text', get_lang('Text'));

	if ($forum_setting['allow_post_notificiation'] AND isset($_user['user_id']))
	{
		$form->addElement('checkbox', 'post_notification', '', get_lang('NotifyByEmail').' ('.$_user['mail'].')');
	}

	if ($forum_setting['allow_sticky'] AND api_is_allowed_to_edit() AND $action=='newthread')
	{
		$form->addElement('checkbox', 'thread_sticky', '', get_lang('StickyPost'));
	}

	if ($current_forum['allow_attachments']=='1' OR api_is_allowed_to_edit())
	{
		//$form->add_resource_button();
		$values = $form->exportValues();
	}

	$form->addElement('submit', 'SubmitPost', get_lang('Ok'));

	if (!empty($form_values))
	{
		$defaults['post_title']=prepare4display($form_values['post_title']);
		$defaults['post_text']=prepare4display($form_values['post_text']);
		$defaults['post_notification']=$form_values['post_notification'];
		$defaults['thread_sticky']=$form_values['thread_sticky'];
	}

	// if we are quoting a message we have to retrieve the information of the post we are quoting so that
	// we can add this as default to the textarea
	if (($action=='quote' OR $action=='replymessage') and isset($_GET['post']))
	{
		// we also need to put the parent_id of the post in a hidden form when we are quoting or replying to a message (<> reply to a thread !!!)
		$form->addElement('hidden', 'post_parent_id', $_GET['post']); // note this has to be cleaned first

		// if we are replying or are quoting then we display a default title.
 		$values=get_post_information($_GET['post']); // note: this has to be cleaned first
		$defaults['post_title']=get_lang('ReplyShort').$values['post_title'];
		// When we are quoting a message then we have to put that message into the wysiwyg editor.
		// note: the style has to be hardcoded here because using class="quote" didn't work
		if($action=='quote')
		{
			$defaults['post_text']='<div>&nbsp;</div><div style="margin: 5px;"><div style="font-size: 90%;	font-style: italic;">'.get_lang('Quoting').' '.$values['firstname'].' '.$values['lastname'].':</div><div style="color: #006600; font-size: 90%;	font-style: italic; background-color: #FAFAFA; border: #D1D7DC 1px solid; padding: 3px;">'.prepare4display($values['post_text']).'</div></div><div>&nbsp;</div><div>&nbsp;</div>';
		}
	}
	$form->setDefaults($defaults);

	// the course admin can make a thread sticky (=appears with special icon and always on top)
	$form->addRule('post_title', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');
	if ($current_forum['allow_anonymous']==1 AND !isset($_user['user_id']))
	{
		$form->addRule('poster_name', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');
	}

	// The validation or display
	if( $form->validate() )
	{
	   $values = $form->exportValues();
	   return $values;
	}
	else
	{
		$form->display();
		if ($forum_setting['show_thread_iframe_on_reply'] and $action<>'newthread')
		{
			echo "<iframe src=\"iframe_thread.php?forum=".$_GET['forum']."&amp;thread=".$_GET['thread']."#".$_GET['post']."\" width=\"80%\"></iframe>";
		}
	}
}


/**
* This function stores a reply in the forum_post table.
* It also updates the forum_threads table (thread_replies +1 , thread_last_post, thread_date)
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function store_reply($values)
{
	global $table_threads;
	global $table_posts;
	global $_user;
	global $_course;
	global $current_forum;
	global $origin;

	$post_date=date('Y-m-d H:i:s');
	if ($current_forum['approval_direct_post']=='1' AND !api_is_allowed_to_edit())
	{
		$visible=0; // the post is not approved yet.
	}
	else
	{
		$visible=1;
	}

	// We first store an entry in the forum_post table
	$sql="INSERT INTO $table_posts (post_title, post_text, thread_id, forum_id, poster_id, post_date, post_notification, post_parent_id, visible)
			VALUES ('".mysql_real_escape_string($values['post_title'])."',
					'".mysql_real_escape_string($values['post_text'])."',
					'".mysql_real_escape_string($values['thread_id'])."',
					'".mysql_real_escape_string($values['forum_id'])."',
					'".mysql_real_escape_string($_user['user_id'])."',
					'".mysql_real_escape_string($post_date)."',
					'".mysql_real_escape_string($values['post_notification'])."',
					'".mysql_real_escape_string($values['post_parent_id'])."',
					'".mysql_real_escape_string($visible)."')";
	$result=api_sql_query($sql, __LINE__, __FILE__);
	$new_post_id=mysql_insert_id();
	$values['new_post_id']=$new_post_id;

	// Storing the attachments if any
	// store_resources('forum_post',$new_post_id);

	// update the thread
	update_thread($values['thread_id'], $new_post_id,$post_date);

	// update the forum
	api_item_property_update($_course, TOOL_FORUM, $values['forum_id'],"NewMessageInForum", api_get_user_id());

	$message=get_lang('ReplyAdded').'<br />';
	if ($current_forum['approval_direct_post']=='1' AND !api_is_allowed_to_edit())
	{
		$message.=get_lang('MessageHasToBeApproved').'<br />';
	}
	$message.=get_lang('ReturnTo').' <a href="viewforum.php?'.api_get_cidreq().'&forum='.$values['forum_id'].'&origin='.$origin.'">'.get_lang('Forum').'</a><br />';
	$message.=get_lang('ReturnTo').' <a href="viewthread.php?'.api_get_cidreq().'&forum='.$values['forum_id'].'&amp;thread='.$values['thread_id'].'&origin='.$origin.'">'.get_lang('Message').'</a>';

	send_notification_mails($values['thread_id'], $values);

	session_unregister('formelements');
	session_unregister('origin');
	session_unregister('breadcrumbs');
	session_unregister('addedresource');
	session_unregister('addedresourceid');

	Display :: display_confirmation_message($message,false);
}


/**
* This function displays the form that is used to edit a post. This can be a new thread or a reply.
* @param $current_post array that contains all the information about the current post
* @param $current_thread array that contains all the information about the current thread
* @return
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function show_edit_post_form($current_post, $current_thread, $form_values='')
{
	global $forum_setting;
	global $_user;

	// initiate the object
	$form = new FormValidator('edit_post', 'post', api_get_self().'?forum='.$_GET['forum'].'&thread='.$_GET['thread'].'&post='.$_GET['post']);

	// settting the form elements
	$form->addElement('hidden', 'post_id', $current_post['post_id']);
	$form->addElement('hidden', 'thread_id', $current_thread['thread_id']);
	if ($current_post['post_parent_id']==0)
	{
		$form->addElement('hidden', 'is_first_post_of_thread', '1');
	}
	$form->addElement('text', 'post_title', get_lang('Title'),'class="input_titles"');
	$form->addElement('html_editor', 'post_text', get_lang('Text'));

	if ($forum_setting['allow_post_notificiation'])
	{
		$form->addElement('checkbox', 'post_notification', '', get_lang('NotifyByEmail').' ('.$current_post['email'].')');
	}
	if ($forum_setting['allow_sticky'] and api_is_allowed_to_edit() and $current_post['post_parent_id']==0) // the sticky checkbox only appears when it is the first post of a thread
	{
		$form->addElement('checkbox', 'thread_sticky', '', get_lang('StickyPost'));
		if ($current_thread['thread_sticky']==1)
		{
			$defaults['thread_sticky']=true;
		}
	}
	if ($current_forum['allow_attachments']=='1' OR api_is_allowed_to_edit())
	{
		if (empty($form_values) AND !$_POST['SubmitPost'])
		{
			//edit_added_resources('forum_post',$current_post['post_id']);
		}
		//$form->add_resource_button();
		$values = $form->exportValues();
	}

	$form->addElement('submit', 'SubmitPost', get_lang('Ok'));

	// setting the default values for the form elements
	$defaults['post_title']=prepare4display($current_post['post_title']);
	$defaults['post_text']=prepare4display($current_post['post_text']);
	if ($current_post['post_notification']==1)
	{
		$defaults['post_notification']=true;
	}

	if (!empty($form_values))
	{
		$defaults['post_title']=$form_values['post_title'];
		$defaults['post_text']=$form_values['post_text'];
		$defaults['post_notification']=$form_values['post_notification'];
		$defaults['thread_sticky']=$form_values['thread_sticky'];
	}

	$form->setDefaults($defaults);

	// the course admin can make a thread sticky (=appears with special icon and always on top)

	$form->addRule('post_title', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');

	// The validation or display
	if( $form->validate() )
	{
	   $values = $form->exportValues();
	   return $values;
	}
	else
	{
		$form->display();
	}
}

/**
* This function stores the edit of a post in the forum_post table.
*
* @param
* @return
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function store_edit_post($values)
{
	global $table_threads;
	global $table_posts;

	// first we check if the change affects the thread and if so we commit the changes (sticky and post_title=thread_title are relevant)
	if (array_key_exists('is_first_post_of_thread',$values) AND $values['is_first_post_of_thread']=='1')
	{
		$sql="UPDATE $table_threads SET thread_title='".mysql_real_escape_string($values['post_title'])."',
					thread_sticky='".mysql_real_escape_string($values['thread_sticky'])."'
					WHERE thread_id='".mysql_real_escape_string($values['thread_id'])."'";
		api_sql_query($sql,__FILE__, __LINE__);
	}

	// update the post_title and the post_text
	$sql="UPDATE $table_posts SET post_title='".mysql_real_escape_string($values['post_title'])."',
				post_text='".mysql_real_escape_string($values['post_text'])."',
				post_notification='".mysql_real_escape_string($values['post_notification'])."'
				WHERE post_id='".mysql_real_escape_string($values['post_id'])."'";
	api_sql_query($sql,__FILE__, __LINE__);

	// Storing the attachments if any
	//update_added_resources('forum_post',$values['post_id']);

	$message=get_lang('EditPostStored').'<br />';
	$message.=get_lang('ReturnTo').' <a href="viewforum.php?'.api_get_cidreq().'&forum='.$_GET['forum'].'">'.get_lang('Forum').'</a><br />';
	$message.=get_lang('ReturnTo').' <a href="viewthread.php?'.api_get_cidreq().'&forum='.$_GET['forum'].'&amp;thread='.$values['thread_id'].'&amp;post='.$_GET['post'].'">'.get_lang('Message').'</a>';

	session_unregister('formelements');
	session_unregister('origin');
	session_unregister('breadcrumbs');
	session_unregister('addedresource');
	session_unregister('addedresourceid');

	Display :: display_confirmation_message($message,false);
}


/**
* This function displays the firstname and lastname of the user as a link to the user tool.
*
* @param
* @return
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function display_user_link($user_id, $name)
{
	if ($user_id<>0)
	{
		return "<a href=\"../user/userInfo.php?uInfo=".$user_id."\">".$name."</a>";
	}
	else
	{
		return $name.' ('.get_lang('Anonymous').')';
	}
}

/**
* The thread view counter gets increased every time someone looks at the thread
*
* @param
* @return
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function increase_thread_view($thread_id)
{
	global $table_threads;

	$sql="UPDATE $table_threads SET thread_views=thread_views+1 WHERE thread_id='".mysql_real_escape_string($thread_id)."'"; // this needs to be cleaned first
	$result=api_sql_query($sql, __LINE__, __FILE__);
}

/**
* The relies counter gets increased every time somebody replies to the thread
*
* @param
* @return
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function update_thread($thread_id, $last_post_id,$post_date)
{
	global $table_threads;

	$sql="UPDATE $table_threads SET thread_replies=thread_replies+1,
			thread_last_post='".mysql_real_escape_string($last_post_id)."',
			thread_date='".mysql_real_escape_string($post_date)."' WHERE thread_id='".mysql_real_escape_string($thread_id)."'"; // this needs to be cleaned first
	$result=api_sql_query($sql, __LINE__, __FILE__);
}



/**
* This function is called when the user is not allowed in this forum/thread/...
*
* @param
* @return
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function forum_not_allowed_here()
{
	Display :: display_error_message(get_lang('NotAllowedHere'));
	Display :: display_footer();
	exit;
}



/**
* This function is used to find all the information about what's new in the forum tool
*
* @param
* @return
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function get_whats_new()
{
	global $_user;
	global $_course;
	global $table_posts;

	// note this has later to be replaced by the tool constant. But temporarily bb_forum is used since this is the only thing that is in the tracking currently.
	//$tool=TOOL_FORUM;
	$tool=TOOL_FORUM; //
	// to do: remove this. For testing purposes only
	//session_unregister('last_forum_access');
	//session_unregister('whatsnew_post_info');

	if (!$_SESSION['last_forum_access'])
	{
		$tracking_last_tool_access=Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LASTACCESS);
		$sql="SELECT * FROM ".$tracking_last_tool_access." WHERE access_user_id='".mysql_real_escape_string($_user['user_id'])."' AND access_cours_code='".mysql_real_escape_string($_course['sysCode'])."' AND access_tool='".mysql_real_escape_string($tool)."'";
		$result=api_sql_query($sql,__FILE__,__LINE__);
		$row=mysql_fetch_array($result);
		$_SESSION['last_forum_access']=$row['access_date'];
	}

	if (!$_SESSION['whatsnew_post_info'])
	{
		if ($_SESSION['last_forum_access']<>'')
		{
			$whatsnew_post_info = array();
			$sql="SELECT * FROM".$table_posts."WHERE post_date>'".mysql_real_escape_string($_SESSION['last_forum_access'])."'"; // note: check the performance of this query.
			$result=api_sql_query($sql,__FILE__,__LINE__);
			while ($row=mysql_fetch_array($result))
			{
				$whatsnew_post_info[$row['forum_id']][$row['thread_id']][$row['post_id']]=$row['post_date'];
			}
			$_SESSION['whatsnew_post_info']=$whatsnew_post_info;
		}
	}
}

/**
* With this function we find the number of posts and topics in a given forum.
*
* @param
* @return
*
* @todo consider to call this function only once and let it return an array where the key is the forum id and the value is an array with number_of_topics and number of post
* as key of this array and the value as a value. This could reduce the number of queries needed (especially when there are more forums)
* @todo consider merging both in one query.
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*
* @deprecated the counting mechanism is now inside the function get_forums
*/
function get_post_topics_of_forum($forum_id)
{
	global $table_posts;
	global $table_threads;
	global $table_item_property;

	$sql="SELECT count(*) as number_of_posts FROM $table_posts WHERE forum_id='".$forum_id."'";
	if (api_is_allowed_to_edit())
	{
		$sql="SELECT count(*) as number_of_posts
				FROM $table_posts posts, $table_threads threads, $table_item_property item_property
				WHERE posts.forum_id='".mysql_real_escape_string($forum_id)."'
				AND posts.thread_id=threads.thread_id
				AND item_property.ref=threads.thread_id
				AND item_property.visibility<>2
				AND item_property.tool='".TOOL_FORUM_THREAD."'
				";
	}
	else
	{
		$sql="SELECT count(*) as number_of_posts
				FROM $table_posts posts, $table_threads threads, $table_item_property item_property
				WHERE posts.forum_id='".mysql_real_escape_string($forum_id)."'
				AND posts.thread_id=threads.thread_id
				AND item_property.ref=threads.thread_id
				AND item_property.visibility=1
				AND posts.visible=1
				AND item_property.tool='".TOOL_FORUM_THREAD."'
				";
	}
	$result=api_sql_query($sql);
	$row=mysql_fetch_array($result);
	$number_of_posts=$row['number_of_posts'];

	// we could loop through the result array and count the number of different group_ids but I have chosen to use a second sql statement
	if (api_is_allowed_to_edit())
	{
		$sql="SELECT count(*) as number_of_topics
				FROM $table_threads threads, $table_item_property item_property
				WHERE threads.forum_id='".mysql_real_escape_string($forum_id)."'
				AND item_property.ref=threads.thread_id
				AND item_property.visibility<>2
				AND item_property.tool='".TOOL_FORUM_THREAD."'
				";
	}
	else
	{
		$sql="SELECT count(*) as number_of_topics
				FROM $table_threads threads, $table_item_property item_property
				WHERE threads.forum_id='".mysql_real_escape_string($forum_id)."'
				AND item_property.ref=threads.thread_id
				AND item_property.visibility=1
				AND item_property.tool='".TOOL_FORUM_THREAD."'
				";
	}
	$result=api_sql_query($sql);
	$row=mysql_fetch_array($result);
	$number_of_topics=$row['number_of_topics'];
	if ($number_of_topics=='')
	{
		$number_of_topics=0; // due to the nature of the group by this can result in an empty string.
	}

	$return=array('number_of_topics'=>$number_of_topics, 'number_of_posts'=>$number_of_posts);
	return $return;
}
/**
* This function approves a post = change
*
* @param $post_id the id of the post that will be deleted
* @param $action make the post visible or invisible
* @return
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function approve_post($post_id, $action)
{
	global $table_posts;

	if ($action=='invisible')
	{
		$visibility_value=0;
	}
	if ($action=='visible')
	{
		$visibility_value=1;
		handle_mail_cue('post',$post_id);
	}

	$sql="UPDATE $table_posts SET visible='".mysql_real_escape_string($visibility_value)."' WHERE post_id='".mysql_real_escape_string($post_id)."'";
	$return=api_sql_query($sql);
	if ($return)
	{
		return 'PostVisibilityChanged';
	}
}


/**
* This function retrieves all the unapproved messages for a given forum
* This is needed to display the icon that there are unapproved messages in that thread (only the courseadmin can see this)
*
* @param $forum_id the forum where we want to know the unapproved messages of
* @return
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function get_unaproved_messages($forum_id)
{
	global $table_posts;

	$return_array=array();

	$sql="SELECT DISTINCT thread_id FROM $table_posts WHERE forum_id='".mysql_real_escape_string($forum_id)."' AND visible='0'";
	$result=api_sql_query($sql);
	while($row=mysql_fetch_array($result))
	{
		$return_array[]=$row['thread_id'];
	}
	return $return_array;
}


/**
* This function sends the notification mails to everybody who stated that they wanted to be informed when a new post
* was added to a given thread.
*
* @param
* @return
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function send_notification_mails($thread_id, $reply_info)
{
	global $table_posts;
	global $table_user;
	global $table_mailcue;

	// First we need to check if
	// 1. the forum category is visible
	// 2. the forum is visible
	// 3. the thread is visible
	// 4. the reply is visible (=when there is
	$current_thread=get_thread_information($thread_id);
	$current_forum=get_forum_information($current_thread['forum_id']);
	$current_forum_category=get_forumcategory_information($current_forum['forum_category']);
	if($current_thread['visibility']=='1' AND $current_forum['visibility']=='1' AND $current_forum_category['visibility']=='1' AND $current_forum['approval_direct_post']=='0')
	{
		$send_mails=true;
	}
	else
	{
		$send_mails=false;
	}

	// the forum category, the forum, the thread and the reply are visible to the user
	if ($send_mails==true)
	{
		$sql="SELECT user.firstname, user.lastname, user.email, user.user_id
				FROM $table_posts post, $table_user user
				WHERE post.thread_id='".mysql_real_escape_string($thread_id)."'
				AND post.post_notification='1'
				AND post.poster_id=user.user_id";
		$result=api_sql_query($sql, __LINE__, __FILE__);
		while ($row=mysql_fetch_array($result))
		{
			send_mail($row, $current_thread);
		}
	}
	else
	{
		$sql="SELECT * FROM $table_posts WHERE thread_id='".mysql_real_escape_string($thread_id)."' AND post_notification='1'";
		$result=api_sql_query($sql, __LINE__, __FILE__);
		while ($row=mysql_fetch_array($result))
		{
			$sql_mailcue="INSERT INTO $table_mailcue (thread_id, post_id) VALUES ('".mysql_real_escape_string($thread_id)."', '".mysql_real_escape_string($reply_info['new_post_id'])."')";
			$result_mailcue=api_sql_query($sql_mailcue, __LINE__, __FILE__);
		}
	}
}

/**
* This function is called whenever something is made visible because there might be new posts and the user might have indicated that (s)he wanted
* to be informed about the new posts by mail.
*
* @param
* @return
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function handle_mail_cue($content, $id)
{
	global $table_mailcue;
	global $table_forums;
	global $table_threads;
	global $table_posts;
	global $table_users;

	// if the post is made visible we only have to send mails to the people who indicated that they wanted to be informed for that thread.
	if ($content=='post')
	{
		// getting the information about the post (need the thread_id)
		$post_info=get_post_information($id);

		// sending the mail to all the users that wanted to be informed for replies on this thread.
		$sql="SELECT users.firstname, users.lastname, users.user_id, users.email FROM $table_mailcue mailcue, $table_posts posts, $table_users users
				WHERE posts.thread_id='".mysql_real_escape_string($post_info['thread_id'])."'
				AND posts.post_notification='1'
				AND mailcue.thread_id='".mysql_real_escape_string($post_info['thread_id'])."'
				AND users.user_id=posts.poster_id
				GROUP BY users.email";
		$result=api_sql_query($sql, __LINE__, __FILE__);
		while ($row=mysql_fetch_array($result))
		{
			send_mail($row, get_thread_information($post_info['thread_id']));
		}

		// deleting the relevant entries from the mailcue
		$sql_delete_mailcue="DELETE FROM $table_mailcue WHERE post_id='".mysql_real_escape_string($id)."' AND thread_id='".mysql_real_escape_string($post_info['thread_id'])."'";
		//$result=api_sql_query($sql_delete_mailcue, __LINE__, __FILE__);
	}
	elseif ($content=='thread')
	{
		// sending the mail to all the users that wanted to be informed for replies on this thread.
		$sql="SELECT users.firstname, users.lastname, users.user_id, users.email FROM $table_mailcue mailcue, $table_posts posts, $table_users users
				WHERE posts.thread_id='".mysql_real_escape_string($id)."'
				AND posts.post_notification='1'
				AND mailcue.thread_id='".mysql_real_escape_string($id)."'
				AND users.user_id=posts.poster_id
				GROUP BY users.email";
		$result=api_sql_query($sql, __LINE__, __FILE__);
		while ($row=mysql_fetch_array($result))
		{
			send_mail($row, get_thread_information($id));
		}

		// deleting the relevant entries from the mailcue
		$sql_delete_mailcue="DELETE FROM $table_mailcue WHERE thread_id='".mysql_real_escape_string($id)."'";
		$result=api_sql_query($sql_delete_mailcue, __LINE__, __FILE__);
	}
	elseif ($content=='forum')
	{
		$sql="SELECT * FROM $table_threads WHERE forum_id='".mysql_real_escape_string($id)."'";
		$result=api_sql_query($sql, __LINE__, __FILE__);
		while ($row=mysql_fetch_array($result))
		{
			handle_mail_cue('thread',$row['thread_id']);
		}
	}
	elseif ($content=='forum_category')
	{
		$sql="SELECT * FROM $table_forums WHERE forum_category ='".mysql_real_escape_string($id)."'";
		$result=api_sql_query($sql, __LINE__, __FILE__);
		while ($row=mysql_fetch_array($result))
		{
			handle_mail_cue('forum',$row['forum_id']);
		}
	}
	else
	{
		return get_lang('Error');
	}
}
/**
* This function sends the mails for the mail notification
*
* @param
* @return
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function send_mail($user_info=array(), $thread_information=array())
{
	global $_course;
	global $_user;

	$email_subject = get_lang('NewForumPost')." - ".$_course['official_code'];

	if (isset($thread_information) and is_array($thread_information))
	{
		$thread_link= api_get_path('WEB_CODE_PATH').'forum/viewthread.php?'.api_get_cidreq().'&forum='.$thread_information['forum_id'].'&thread='.$thread_information['thread_id'];
		//http://157.193.57.110/dokeos_cvs/claroline/forum/viewthread.php?forum=12&thread=49
	}
	$email_body= $user_info['firstname']." ".$user_info['lastname']."\n\r";
	$email_body .= '['.$_course['official_code'].'] - ['.$_course['name']."]<br>\n";
	$email_body .= get_lang('NewForumPost')."\n";
	$email_body .= get_lang('YouWantedToStayInformed')."<br><br>\n";
	$email_body .= get_lang('ThreadCanBeFoundHere')." : <a href=\"".$thread_link."\">".$thread_link."</a>\n";

	//set the charset and use it for the encoding of the email - small fix, not really clean (should check the content encoding origin first)
	//here we use the encoding used for the webpage where the text is encoded (ISO-8859-1 in this case)
	if(empty($charset)){$charset='ISO-8859-1';}

	if ($user_info['user_id']<>$_user['user_id'])
	{
		$newmail = api_mail_html($user_info["lastname"].' '.$user_info["firstname"], $user_info["email"], $email_subject, $email_body, $_SESSION['_user']['lastName'].' '.$_SESSION['_user']['firstName'], $_SESSION['_user']['mail']);
	}
}

/**
* This function displays the form for moving a thread to a different (already existing) forum
*
* @param
* @return
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function move_thread_form()
{
	global $origin;

	// initiate the object
	$form = new FormValidator('movepost', 'post', api_get_self().'?forum='.$_GET['forum'].'&thread='.$_GET['thread'].'&action='.$_GET['action'].'&origin='.$origin);
	// the header for the form
	$form->addElement('header', '', get_lang('MoveThread'));
	// invisible form: the thread_id
	$form->addElement('hidden', 'thread_id', $_GET['thread']); // note: this has to be cleaned first

	// the fora
	$forum_categories=get_forum_categories();
	$forums=get_forums();

	$htmlcontent="\n<tr>\n<td></td>\n<td>\n<SELECT NAME='forum'>\n";
	foreach ($forum_categories as $key=>$category)
	{
		$htmlcontent.="\t<OPTGROUP LABEL=\"".$category['cat_title']."\">\n";
		foreach ($forums as $key=>$forum)
		{
			if ($forum['forum_category']==$category['cat_id'])
			{
				$htmlcontent.="\t\t<OPTION VALUE='".$forum['forum_id']."'>".$forum['forum_title']."</OPTION>\n";
			}
		}
		$htmlcontent.="\t</OPTGROUP>\n";
	}
	$htmlcontent.="</SELECT>\n</td></tr>";
	$form->addElement('html',$htmlcontent);

	// The OK button
	$form->addElement('submit', 'SubmitForum', 'OK');

	// The validation or display
	if( $form->validate())
	{
	   $values = $form->exportValues();
	   if (isset($_POST['forum']))
	   {
	   		store_move_thread($values);
	   }

	}
	else
	{
		$form->display();
	}
}

/**
* This function displays the form for moving a post message to a different (already existing) or a new thread.
*
* @param
* @return
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function move_post_form()
{
	// initiate the object
	$form = new FormValidator('movepost', 'post', api_get_self().'?forum='.$_GET['forum'].'&thread='.$_GET['thread'].'&post='.$_GET['post'].'&action='.$_GET['action'].'&post='.$_GET['post']);
	// the header for the form
	$form->addElement('header', '', get_lang('MovePost'));

	// invisible form: the post_id
	$form->addElement('hidden', 'post_id', $_GET['post']); // note: this has to be cleaned first

	// dropdown list: Threads of this forum
	$threads=get_threads($_GET['forum']); // note: this has to be cleaned
	//my_print_r($threads);
	$threads_list[0]=get_lang('ANewThread');
	foreach ($threads as $key=>$value)
	{
		$threads_list[$value['thread_id']]=$value['thread_title'];
	}
	$form->addElement('select', 'thread', get_lang('MoveToThread'), $threads_list);


	// The OK button
	$form->addElement('submit', 'SubmitForum', 'OK');

	// setting the rules
	$form->addRule('thread', get_lang('ThisFieldIsRequired'), 'required');


	// The validation or display
	if( $form->validate() )
	{
	   $values = $form->exportValues();
	   store_move_post($values);
	}
	else
	{
		$form->display();
	}
}


/**
*
* @param
* @return
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function store_move_post($values)
{
	global $table_posts;
	global $table_threads;
	global $table_forums;
	global $_course;

	if ($values['thread']=='0')
	{
		$current_post=get_post_information($values['post_id']);

		// storing a new thread
		$sql="INSERT INTO $table_threads (thread_title, forum_id, thread_poster_id, thread_poster_name, thread_last_post, thread_date)
			VALUES (
				'".mysql_real_escape_string($current_post['post_title'])."',
				'".mysql_real_escape_string($current_post['forum_id'])."',
				'".mysql_real_escape_string($current_post['poster_id'])."',
				'".mysql_real_escape_string($current_post['poster_name'])."',
				'".mysql_real_escape_string($values['post_id'])."',
				'".mysql_real_escape_string($current_post['post_date'])."'
				)";
		//echo $sql.'<br />';
		$result=api_sql_query($sql, __LINE__, __FILE__);
		$new_thread_id=mysql_insert_id();
		api_item_property_update($_course, TOOL_FORUM_THREAD, $new_thread_id,"visible", $current_post['poster_id']);

		// moving the post to the newly created thread
		$sql="UPDATE $table_posts SET thread_id='".mysql_real_escape_string($new_thread_id)."', post_parent_id='0' WHERE post_id='".mysql_real_escape_string($values['post_id'])."'";
		$result=api_sql_query($sql, __LINE__, __FILE__);
		//echo $sql.'<br />';

		// resetting the parent_id of the thread to 0 for all those who had this moved post as parent
		$sql="UPDATE $table_posts SET post_parent_id='0' WHERE post_parent_id='".mysql_real_escape_string($values['post_id'])."'";
		$result=api_sql_query($sql, __LINE__, __FILE__);
		//echo $sql.'<br />';

		// updating updating the number of threads in the forum
		$sql="UPDATE $table_forums SET forum_threads=forum_threads+1 WHERE forum_id='".mysql_real_escape_string($current_post['forum_id'])."'";
		$result=api_sql_query($sql, __LINE__, __FILE__);
		//echo $sql.'<br />';

		// resetting the last post of the old thread and decreasing the number of replies and the thread
		$sql="SELECT * FROM $table_posts WHERE thread_id='".mysql_real_escape_string($current_post['thread_id'])."' ORDER BY post_id DESC";
		//echo $sql.'<br />';
		$result=api_sql_query($sql, __LINE__, __FILE__);
		$row=mysql_fetch_array($result);
		//my_print_r($row);
		$sql="UPDATE $table_threads SET thread_last_post='".$row['post_id']."', thread_replies=thread_replies-1 WHERE thread_id='".mysql_real_escape_string($current_post['thread_id'])."'";
		$result=api_sql_query($sql, __LINE__, __FILE__);
		//echo $sql.'<br />';
	}
	else
	{
		// moving to the chosen thread
		$sql="UPDATE $table_posts SET thread_id='".mysql_real_escape_string($_POST['thread'])."', post_parent_id='0' WHERE post_id='".mysql_real_escape_string($values['post_id'])."'";
		$result=api_sql_query($sql, __LINE__, __FILE__);

		// resetting the parent_id of the thread to 0 for all those who had this moved post as parent
		$sql="UPDATE $table_posts SET post_parent_id='0' WHERE post_parent_id='".mysql_real_escape_string($values['post_id'])."'";
		$result=api_sql_query($sql, __LINE__, __FILE__);
	}

	return get_lang('ThreadMoved');
}

/**
*
* @param
* @return
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function store_move_thread($values)
{
	global $table_posts;
	global $table_threads;
	global $table_forums;
	global $_course;

	// change the thread table: setting the forum_id to the new forum
	$sql="UPDATE $table_threads SET forum_id='".mysql_real_escape_string($_POST['forum'])."' WHERE thread_id='".mysql_real_escape_string($_POST['thread_id'])."'";
	$result=api_sql_query($sql, __LINE__, __FILE__);


	// changing all the posts of the thread: setting the forum_id to the new forum
	$sql="UPDATE $table_posts SET forum_id='".mysql_real_escape_string($_POST['forum'])."' WHERE thread_id='".mysql_real_escape_string($_POST['thread_id'])."'";
	$result=api_sql_query($sql, __LINE__, __FILE__);

	return get_lang('ThreadMoved');
}


/**
*
* @param
* @return
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function prepare4display($input='')
{
	if (!is_array($input))
	{
		return stripslashes($input);
	}
	else
	{
		/*foreach ($input as $key=>$value)
		{
			$returnarray[$key]=stripslashes($value);
		}*/
		$returnarray=array_walk($input, 'stripslashes');
		return $returnarray;
	}
}

?>