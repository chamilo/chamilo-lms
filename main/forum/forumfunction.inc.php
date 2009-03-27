<?php
 
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
*	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University
*	@copyright Ghent University
*	@copyright Patrick Cool
*   @author Julio Montoya <gugli100@gmail.com>, Dokeos Several fixes 
* 	@package dokeos.forum
*
* 	@todo several functions have to be moved to the itemmanager library
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
require_once(api_get_path(LIBRARY_PATH).'mail.lib.inc.php');
require_once(api_get_path(LIBRARY_PATH).'text.lib.php');
require_once(api_get_path(INCLUDE_PATH).'/conf/mail.conf.php');
require_once(api_get_path(LIBRARY_PATH).'usermanager.lib.php');
require_once(api_get_path(LIBRARY_PATH).'text.lib.php');
get_notifications_of_user();
/**
* This function handles all the forum and forumcategories actions. This is a wrapper for the
* forum and forum categories. All this code code could go into the section where this function is
* called but this make the code there cleaner.
* @param
* @return
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function handle_forum_and_forumcategories() {
	$action_forum_cat = isset($_GET['action']) ? $_GET['action'] : '';
	$post_submit_cat= isset($_POST['SubmitForumCategory']) ?  true : false;
	$post_submit_forum= isset($_POST['SubmitForum']) ? true : false;
	$get_id=isset($_GET['id']) ? $_GET['id'] : '';
	// Adding a forum category
	if (($action_forum_cat=='add' && $_GET['content']=='forumcategory') || $post_submit_cat ) {
		show_add_forumcategory_form();
	}
	// Adding a forum
	if ((($action_forum_cat=='add' || $action_forum_cat=='edit') && $_GET['content']=='forum') || $post_submit_forum ) {
		if ($action_forum_cat=='edit' && $get_id || $post_submit_forum ) {
			$inputvalues=get_forums(strval(intval($get_id))); // note: this has to be cleaned first
		} else {
			$inputvalues='';
		}
		show_add_forum_form($inputvalues);
	}
	// Edit a forum category
	if (($action_forum_cat=='edit' && $_GET['content']=='forumcategory' && isset($_GET['id'])) || (isset($_POST['SubmitEditForumCategory'])) ? true : false )
	{
		$forum_category=get_forum_categories(strval(intval($_GET['id']))); // note: this has to be cleaned first
		show_edit_forumcategory_form($forum_category);
	}
	// Delete a forum category
	if (( isset($_GET['action']) && $_GET['action']=='delete') && isset($_GET['content']) && $get_id) {
		$id_forum=Security::remove_XSS($get_id);
		$list_threads=get_threads($id_forum);
		
		for ( $i=0; $i < count($list_threads); $i++ ) {
			$messaje=delete_forum_forumcategory_thread('thread',$list_threads[$i]['thread_id']);
			$table_link = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
			$sql_link='DELETE FROM '.$table_link.' WHERE ref_id='.$list_threads[$i]['thread_id'].' and type=5 and course_code="'.api_get_course_id().'";';
			api_sql_query($sql_link,__FILE__,__LINE__);
		}
		$return_message=delete_forum_forumcategory_thread($_GET['content'],$_GET['id']);
		Display :: display_confirmation_message($return_message,false);
	}
	// Change visibility of a forum or a forum category
	if (($action_forum_cat=='invisible' || $action_forum_cat=='visible') && isset($_GET['content']) && isset($_GET['id'])) {
		$return_message=change_visibility($_GET['content'], $_GET['id'],$_GET['action']);// note: this has to be cleaned first
		Display :: display_confirmation_message($return_message,false);
	}
	// Change lock status of a forum or a forum category
	if (($action_forum_cat=='lock' || $action_forum_cat=='unlock') && isset($_GET['content']) && isset($_GET['id'])) {
		$return_message=change_lock_status($_GET['content'], $_GET['id'],$_GET['action']);// note: this has to be cleaned first
		Display :: display_confirmation_message($return_message,false);
	}
	// Move a forum or a forum category
	if ($action_forum_cat=='move' && isset($_GET['content']) && isset($_GET['id']) && isset($_GET['direction'])) {
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
function show_add_forumcategory_form($inputvalues=array()) {
	// initiate the object
	$form = new FormValidator('forumcategory','post');

	// settting the form elements
	$form->addElement('header', '', get_lang('AddForumCategory'));
	$form->addElement('text', 'forum_category_title', get_lang('Title'),'class="input_titles"');
	$form->addElement('html_editor', 'forum_category_comment', get_lang('Comment'));
	$form->addElement('style_submit_button', 'SubmitForumCategory', get_lang('CreateCategory'), 'class="add"');

	// setting the rules
	$form->addRule('forum_category_title', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');

	// The validation or display
	if ( $form->validate() ) {
		$check = Security::check_token('post');	
		if ($check) {
	   		$values = $form->exportValues();
	   		store_forumcategory($values);
		}
		Security::clear_token();
	} else {
		$token = Security::get_token();
		$form->addElement('hidden','sec_token');
		$form->setConstants(array('sec_token' => $token));		
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
function show_add_forum_form($inputvalues=array()) {
	global $_course;

	// initiate the object
	$form = new FormValidator('forumcategory', 'post', 'index.php');

	// the header for the form
	$session_header = isset($_SESSION['session_name']) ? ' ('.$_SESSION['session_name'].') ' : '';
	$form->addElement('header', '', get_lang('AddForum').$session_header);

	// we have a hidden field if we are editing
	if (is_array($inputvalues)) {
		$my_forum_id=isset($inputvalues['forum_id']) ? $inputvalues['forum_id'] : null;
		$form->addElement('hidden', 'forum_id', $my_forum_id);
	}
	// The title of the forum
	$form->addElement('text', 'forum_title', get_lang('Title'),'class="input_titles"');
	// The comment of the forum
	$form->addElement('html_editor', 'forum_comment', get_lang('Comment'));
	// dropdown list: Forum Categories
	$forum_categories=get_forum_categories();
	foreach ($forum_categories as $key=>$value) {
		$forum_categories_titles[$value['cat_id']]=$value['cat_title'];
	}
	$form->addElement('select', 'forum_category', get_lang('InForumCategory'), $forum_categories_titles);

	if ($_course['visibility']==COURSE_VISIBILITY_OPEN_WORLD) {
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
	
			/*		if (document.getElementById('id_qualify').style.display == 'none') {
					document.getElementById('id_qualify').style.display = 'block';
					document.getElementById('plus').innerHTML='&nbsp;<img src=\"../img/nolines_minus.gif\" alt=\"\" />&nbsp;".get_lang('AddAnAttachment')."';
				} else {
				document.getElementById('options').style.display = 'none';
				document.getElementById('plus').innerHTML='&nbsp;<img src=\"../img/nolines_plus.gif\" alt=\"\" />&nbsp;".get_lang('AddAnAttachment')."';
				}*/
			
		$form->addElement('static','Group','','<div id="plus"><a href="javascript://" onclick="advanced_parameters()" ><br /><span id="plus_minus">&nbsp;<img src="../img/nolines_plus.gif" alt="" />&nbsp;'.get_lang('AdvancedParameters').'</span></a></div>');
		$form->addElement('html','<div id="options" style="display:none">');
	
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
	foreach ($groups as $key=>$value) {
		$groups_titles[$value['id']]=$value['name'];
	}
	$form->addElement('select', 'group_forum', get_lang('ForGroup'), $groups_titles);

	// Public or private group forum
	$group='';
	$group[] =& HTML_QuickForm::createElement('radio', 'public_private_group_forum', null, get_lang('Public'), 'public');
	$group[] =& HTML_QuickForm::createElement('radio', 'public_private_group_forum', null, get_lang('Private'), 'private');
	$form->addGroup($group, 'public_private_group_forum_group', get_lang('PublicPrivateGroupForum'), '&nbsp;');

	
	 // Forum image 	 
	 $form->add_progress_bar(); 	 
	 if (isset($inputvalues['forum_image']) && strlen($inputvalues['forum_image']) > 0) { 	 
	
		 $image_path = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/upload/forum/images/'.$inputvalues['forum_image'];
		 $image_size = @getimagesize($image_path);
		 $img_attributes = '';
		 if (!empty($image_size)) {
			 if ($image_size[0] > 100 || $image_size[1] > 100) {
				//limit display width and height to 100px
				$img_attributes = 'width="100" height="100"';	
			 }							
		 	 $show_preview_image='<img src="'.$image_path.'" '.$img_attributes.'>';
		     $div = '<div class="row"> 	 
		     <div class="label">'.get_lang('PreviewImage').'</div> 	 
		     <div class="formw"> 	 
		     '.$show_preview_image.' 	 
		     </div> 	 
		     </div>';
		 
		     $form->addElement('html', $div .'<br/>'); 	 
	     	 $form->addElement('checkbox', 'remove_picture', null, get_lang('DelImage'));
	     }	
		  	 
	 } 	 
	 $forum_image=isset($inputvalues['forum_image']) ? $inputvalues['forum_image'] : '';
	 $form->addElement('file', 'picture', ($forum_image != '' ? get_lang('UpdateImage') : get_lang('AddImage'))); 	 
	 $form->addRule('picture', get_lang('OnlyImagesAllowed'), 'mimetype', array('image/gif', 'image/jpeg', 'image/png'));
	 $form->addElement('html','</div>');

	// The OK button
	if (isset($_GET['id']) && $_GET['action']=='edit'){
		$class='save';
		$text=get_lang('ModifyForum');
	}else{
		$class='add';
		$text=get_lang('CreateForum');		
	}
	$form->addElement('style_submit_button', 'SubmitForum', $text, 'class="'.$class.'"');
	// setting the rules
	$form->addRule('forum_title', get_lang('ThisFieldIsRequired'), 'required');
	$form->addRule('forum_category', get_lang('ThisFieldIsRequired'), 'required');

	global $charset;
	
	// settings the defaults
	if (!is_array($inputvalues)) {
		$defaults['allow_anonymous_group']['allow_anonymous']=0;
		$defaults['students_can_edit_group']['students_can_edit']=0;
		$defaults['approval_direct_group']['approval_direct']=0;
		$defaults['allow_attachments_group']['allow_attachments']=1;
		$defaults['allow_new_threads_group']['allow_new_threads']=0;
		$defaults['default_view_type_group']['default_view_type']=api_get_setting('default_forum_view');
		$defaults['public_private_group_forum_group']['public_private_group_forum']='public';
		if (isset($_GET['forumcategory'])) {
			$defaults['forum_category']=Security::remove_XSS($_GET['forumcategory']);
		}
	} else {   // the default values when editing = the data in the table
		$defaults['forum_id']=isset($inputvalues['forum_id']) ? $inputvalues['forum_id'] : null;
		$defaults['forum_title']=prepare4display(html_entity_decode(isset($inputvalues['forum_title']) ? $inputvalues['forum_title'] : null,ENT_QUOTES,$charset));
		$defaults['forum_comment']=prepare4display(isset($inputvalues['forum_comment'])?$inputvalues['forum_comment']:null);
		$defaults['forum_category']=isset($inputvalues['forum_category']) ? $inputvalues['forum_category'] : null;
		$defaults['allow_anonymous_group']['allow_anonymous']=isset($inputvalues['allow_anonymous']) ? $inputvalues['allow_anonymous'] :null;
		$defaults['students_can_edit_group']['students_can_edit']=isset($inputvalues['allow_edit'])?$inputvalues['allow_edit']:null;
		$defaults['approval_direct_group']['approval_direct']=isset($inputvalues['approval_direct_post'])?$inputvalues['approval_direct_post']:null;
		$defaults['allow_attachments_group']['allow_attachments']=isset($inputvalues['allow_attachments'])?$inputvalues['allow_attachments']:null;
		$defaults['allow_new_threads_group']['allow_new_threads']=isset($inputvalues['allow_new_threads'])?$inputvalues['allow_new_threads']:null;
		$defaults['default_view_type_group']['default_view_type']=isset($inputvalues['default_view'])?$inputvalues['default_view']:null;
		$defaults['public_private_group_forum_group']['public_private_group_forum']=isset($inputvalues['forum_group_public_private'])?$inputvalues['forum_group_public_private']:null;
		$defaults['group_forum']=isset($inputvalues['forum_of_group'])?$inputvalues['forum_of_group']:null;
	}
	$form->setDefaults($defaults);
	// The validation or display
	if( $form->validate() ) {
		$check = Security::check_token('post');	
		if ($check) {
			$values = $form->exportValues();
	   		store_forum($values);
		}
		Security::clear_token();
	} else {
		
			$token = Security::get_token();
			$form->addElement('hidden','sec_token');
			$form->setConstants(array('sec_token' => $token));
			$form->display();
			
	}
}

/**
 * This function deletes the forum image if exists
*
* @param int forum id
* @return boolean true if success
* @author Julio Montoya <gugli100@gmail.com>, Dokeos
* @version february 2006, dokeos 1.8
*/
function delete_forum_image($forum_id)
{
	$table_forums = Database::get_course_table(TABLE_FORUM);        
	echo '<br />';
	$sql="SELECT forum_image FROM $table_forums WHERE forum_id = '".$forum_id."' ";
	$result=api_sql_query($sql,__FILE__,__LINE__);
	$row=Database::fetch_array($result);
	if ($row['forum_image']!='') {
		$del_file = api_get_path(SYS_COURSE_PATH).api_get_course_path().'/upload/forum/images/'.$row['forum_image'];
		return @unlink($del_file);
	} else {
		return false;
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
function show_edit_forumcategory_form($inputvalues=array()) {
	// initiate the object
	$form = new FormValidator('forumcategory','post');

	// settting the form elements
	$form->addElement('header', '', get_lang('EditForumCategory'));
	$form->addElement('hidden', 'forum_category_id');
	$form->addElement('text', 'forum_category_title', get_lang('Title'),'class="input_titles"');
	$form->addElement('html_editor', 'forum_category_comment', get_lang('Comment'));
	$form->addElement('style_submit_button', 'SubmitEditForumCategory',get_lang('ModifyCategory'), 'class="save"');
	global $charset;
	// setting the default values
	$defaultvalues['forum_category_id']=$inputvalues['cat_id'];

	$defaultvalues['forum_category_title']=prepare4display(html_entity_decode($inputvalues['cat_title'],ENT_QUOTES,$charset));
	$defaultvalues['forum_category_comment']=prepare4display($inputvalues['cat_comment']);
	$form->setDefaults($defaultvalues);

	// setting the rules
	$form->addRule('forum_category_title', get_lang('ThisFieldIsRequired'), 'required');

	// The validation or display
	if ( $form->validate() ) {
		$check = Security::check_token('post');	
		if ($check) {
	   		$values = $form->exportValues();
	  		store_forumcategory($values);
		}
		Security::clear_token();
	} else {
		$token = Security::get_token();
		$form->addElement('hidden','sec_token');
		$form->setConstants(array('sec_token' => $token));
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
function store_forumcategory($values) {
	global $table_categories;
	global $_course;
	global $_user;

	// find the max cat_order. The new forum category is added at the end => max cat_order + &
	$sql="SELECT MAX(cat_order) as sort_max FROM ".Database::escape_string($table_categories);
	$result=api_sql_query($sql,__FILE__,__LINE__);
	$row=Database::fetch_array($result);
	$new_max=$row['sort_max']+1;
	
	$clean_cat_title=Security::remove_XSS(Database::escape_string($values['forum_category_title']));

	if (isset($values['forum_category_id'])) { // storing an edit
		$sql="UPDATE ".$table_categories." SET cat_title='".$clean_cat_title."', cat_comment='".Database::escape_string($values['forum_category_comment'])."' WHERE cat_id='".Database::escape_string($values['forum_category_id'])."'";
		api_sql_query($sql,__FILE__,__LINE__);
		$last_id=Database::get_last_insert_id();
		api_item_property_update($_course, TOOL_FORUM_CATEGORY, $values['forum_category_id'],"ForumCategoryAdded", api_get_user_id());
		$return_message=get_lang('ForumCategoryEdited');
	} else {
		$sql="INSERT INTO ".$table_categories." (cat_title, cat_comment, cat_order) VALUES ('".$clean_cat_title."','".Database::escape_string($values['forum_category_comment'])."','".Database::escape_string($new_max)."')";
		api_sql_query($sql,__FILE__,__LINE__);
		$last_id=Database::get_last_insert_id();
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
function store_forum($values) {
	global $_course;
	global $_user;
	
	$table_forums = Database::get_course_table(TABLE_FORUM);

	// find the max forum_order for the given category. The new forum is added at the end => max cat_order + &
	$sql="SELECT MAX(forum_order) as sort_max FROM ".$table_forums." WHERE forum_category=".Database::escape_string($values['forum_category']);
	$result=api_sql_query($sql,__FILE__,__LINE__);
	$row=Database::fetch_array($result);
	$new_max=$row['sort_max']+1;
	$session_id = isset($_SESSION['id_session']) ? $_SESSION['id_session'] : 0;
	
	$clean_title=Security::remove_XSS(Database::escape_string(htmlspecialchars($values['forum_title'])));

	// forum images
	
	$image_moved=false;
	if (!empty($_FILES['picture']['name'])) {
		$upload_ok = process_uploaded_file($_FILES['picture']);
        $has_attachment=true;
    } else {
        $image_moved=true;
    }
 
    // remove existing picture if asked
	if (!empty($_POST['remove_picture'])) {
    	delete_forum_image($values['forum_id']);
	}
 
	if (isset($upload_ok)) {
		if ($has_attachment) {
			$courseDir   = $_course['path'].'/upload/forum/images';
			$sys_course_path = api_get_path(SYS_COURSE_PATH);
			$updir = $sys_course_path.$courseDir;
            // Try to add an extension to the file if it hasn't one
			$new_file_name = add_ext_on_mime(Database::escape_string($_FILES['picture']['name']), $_FILES['picture']['type']);
            // user's file name
			$file_name =$_FILES['picture']['name'];
 
            if (!filter_extension($new_file_name)) {
                 //Display :: display_error_message(get_lang('UplUnableToSaveFileFilteredExtension'));
                 $image_moved=false;
            } else {
                 $file_extension = explode('.', $_FILES['picture']['name']);
                 $file_extension = strtolower($file_extension[sizeof($file_extension) - 1]);
                 $new_file_name = uniqid('').'.'.$file_extension;
                 $new_path=$updir.'/'.$new_file_name;
                 $result= @move_uploaded_file($_FILES['picture']['tmp_name'], $new_path);
                 // Storing the attachments if any
                 if ($result) {
                         $image_moved=true;
                 }
            }
		}
	}
		
	if (isset($values['forum_id'])) {
		$sql_image=isset($sql_image)?$sql_image:'';
		$new_file_name=isset($new_file_name) ? $new_file_name:'';
	  	if ($image_moved) {			
	  		if(empty($_FILES['picture']['name'])){
	  			$sql_image=" ";
	  		} else {
	  			$sql_image=" forum_image='".Database::escape_string($new_file_name)."', ";	
	  			delete_forum_image($values['forum_id']);	
	  		}
		}
	
		// storing an edit
		$sql="UPDATE ".$table_forums." SET
				forum_title='".$clean_title."',
				".$sql_image."		
				forum_comment='".Database::escape_string($values['forum_comment'])."',
				forum_category='".Database::escape_string($values['forum_category'])."',
				allow_anonymous='".Database::escape_string(isset($values['allow_anonymous_group']['allow_anonymous'])?$values['allow_anonymous_group']['allow_anonymous']:null)."',
				allow_edit='".Database::escape_string($values['students_can_edit_group']['students_can_edit'])."',
				approval_direct_post='".Database::escape_string(isset($values['approval_direct_group']['approval_direct'])?$values['approval_direct_group']['approval_direct']:null)."',
				allow_attachments='".Database::escape_string(isset($values['allow_attachments_group']['allow_attachments'])?$values['allow_attachments_group']['allow_attachments']:null)."',
				allow_new_threads='".Database::escape_string($values['allow_new_threads_group']['allow_new_threads'])."',
				forum_group_public_private='".Database::escape_string($values['public_private_group_forum_group']['public_private_group_forum'])."',
				default_view='".Database::escape_string($values['default_view_type_group']['default_view_type'])."',
				forum_of_group='".Database::escape_string($values['group_forum'])."'
			WHERE forum_id='".Database::escape_string($values['forum_id'])."'";
			api_sql_query($sql,__FILE__,__LINE__);
			$return_message=get_lang('ForumEdited');
	} else {
		$sql_image='';
		if ($image_moved) {
			$new_file_name=isset($new_file_name)?$new_file_name:'';
			$sql_image="'".$new_file_name."', ";
		}
			
		$sql="INSERT INTO ".$table_forums."
			(forum_title, forum_image, forum_comment, forum_category, allow_anonymous, allow_edit, approval_direct_post, allow_attachments, allow_new_threads, default_view, forum_of_group, forum_group_public_private, forum_order, session_id)
			VALUES ('".$clean_title."',
				".$sql_image."	
				'".Database::escape_string(isset($values['forum_comment'])?$values['forum_comment']:null)."',
				'".Database::escape_string(isset($values['forum_category'])?$values['forum_category']:null)."',
				'".Database::escape_string(isset($values['allow_anonymous_group']['allow_anonymous'])?$values['allow_anonymous_group']['allow_anonymous']:null)."',
				'".Database::escape_string(isset($values['students_can_edit_group']['students_can_edit'])?$values['students_can_edit_group']['students_can_edit']:null)."',
				'".Database::escape_string(isset($values['approval_direct_group']['approval_direct'])?$values['approval_direct_group']['approval_direct']:null)."',
				'".Database::escape_string(isset($values['allow_attachments_group']['allow_attachments'])?$values['allow_attachments_group']['allow_attachments']:null)."',
				'".Database::escape_string(isset($values['allow_new_threads_group']['allow_new_threads'])?$values['allow_new_threads_group']['allow_new_threads']:null)."',
				'".Database::escape_string(isset($values['default_view_type_group']['default_view_type'])?$values['default_view_type_group']['default_view_type']:null)."',
				'".Database::escape_string(isset($values['group_forum'])?$values['group_forum']:null)."',
				'".Database::escape_string(isset($values['public_private_group_forum_group']['public_private_group_forum'])?$values['public_private_group_forum_group']['public_private_group_forum']:null)."',
				'".Database::escape_string(isset($new_max)?$new_max:null)."',
				".intval($session_id).")";
		api_sql_query($sql,__FILE__,__LINE__);
		$last_id=Database::get_last_insert_id();
		api_item_property_update($_course, TOOL_FORUM, $last_id,"ForumCategoryAdded", api_get_user_id());
		$return_message=get_lang('ForumAdded');
	}
	return $return_message;
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
function delete_forum_forumcategory_thread($content, $id) {
	global $_course;
	$table_forums = Database::get_course_table(TABLE_FORUM);
	$table_forums_post = Database::get_course_table(TABLE_FORUM_POST);
	$table_forum_thread = Database::get_course_table(TABLE_FORUM_THREAD);
		
	// delete all attachment file about this tread id	
	$sql = "SELECT post_id FROM $table_forums_post WHERE thread_id = '".(int)$id."' ";
	$res = api_sql_query($sql,__FILE__,__LINE__);
	while ($poster_id = Database::fetch_row($res)) {
		delete_attachment($poster_id[0]);	
	}	
			
	if ($content=='forumcategory') {
		$tool_constant=TOOL_FORUM_CATEGORY;
		$return_message=get_lang('ForumCategoryDeleted');
		
		if (!empty($forum_list)){
			$sql="SELECT forum_id FROM ". $table_forums . "WHERE forum_category='".$id."'";
			$result = api_sql_query($sql, __FILE__, __LINE__);
			$row = Database::fetch_array($result);
			foreach ($row as $arr_forum) {
				$forum_id = $arr_forum['forum_id'];
				api_item_property_update($_course,'forum',$forum_id,'delete',api_get_user_id());			
			}
		}	
	}
	if ($content=='forum') {
		$tool_constant=TOOL_FORUM;
		$return_message=get_lang('ForumDeleted');
		
		if (!empty($number_threads)){
			$sql="SELECT thread_id FROM". $table_forum_thread . "WHERE forum_id='".$id."'";
			$result = api_sql_query($sql, __FILE__, __LINE__);
			$row = mysql_fetch_array($result);
			foreach ($row as $arr_forum) {
				$forum_id = $arr_forum['thread_id'];
				api_item_property_update($_course,'forum_thread',$forum_id,'delete',api_get_user_id());			
			}
		}
	}
	if ($content=='thread') {
		$tool_constant=TOOL_FORUM_THREAD;
		$return_message=get_lang('ThreadDeleted');
	}
	api_item_property_update($_course,$tool_constant,$id,'delete',api_get_user_id()); // note: check if this returns a true and if so => return $return_message, if not => return false;
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
function delete_post($post_id) {
	global $table_posts;
	global $table_threads;

	$sql="DELETE FROM $table_posts WHERE post_id='".Database::escape_string($post_id)."'"; // note: this has to be a recursive function that deletes all of the posts in this block.
	api_sql_query($sql,__FILE__,__LINE__);
	
	//delete attachment file about this post id
	delete_attachment($post_id);	
		
	$last_post_of_thread=check_if_last_post_of_thread(strval(intval($_GET['thread'])));
	
	if (is_array($last_post_of_thread)) {
		// Decreasing the number of replies for this thread and also changing the last post information
		$sql="UPDATE $table_threads SET thread_replies=thread_replies-1,
					thread_last_post='".Database::escape_string($last_post_of_thread['post_id'])."',
					thread_date='".Database::escape_string($last_post_of_thread['post_date'])."'
			WHERE thread_id='".Database::escape_string($_GET['thread'])."'";
		api_sql_query($sql,__FILE__,__LINE__);
		return 'PostDeleted';
	}
	if ($last_post_of_thread==false) {
		// we deleted the very single post of the thread so we need to delete the entry in the thread table also.
		$sql="DELETE FROM $table_threads WHERE thread_id='".Database::escape_string($_GET['thread'])."'";
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
function check_if_last_post_of_thread($thread_id) {
	global $table_posts;

	$sql="SELECT * FROM $table_posts WHERE thread_id='".Database::escape_string($thread_id)."' ORDER BY post_date DESC";
	$result=api_sql_query($sql,__FILE__,__LINE__);
	if ( Database::num_rows($result)>0 ) {
		$row=Database::fetch_array($result);
		return $row;
	} else {
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
function display_visible_invisible_icon($content, $id, $current_visibility_status, $additional_url_parameters='') {
	global $origin;
	$id = Security::remove_XSS($id);
	if ($current_visibility_status=='1') {
		echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&';
		if (is_array($additional_url_parameters)) {
			foreach ($additional_url_parameters as $key=>$value) {
				echo $key.'='.$value.'&amp;';
			}
		}
		echo 'action=invisible&amp;content='.$content.'&amp;id='.$id.'&amp;origin='.$origin.'">'.icon('../img/visible.gif',get_lang('MakeInvisible')).'</a>';
	}
	if ($current_visibility_status=='0') {
		echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&';
		if (is_array($additional_url_parameters)) {
			foreach ($additional_url_parameters as $key=>$value) {
				echo $key.'='.$value.'&amp;';
			}
		}
		echo 'action=visible&amp;content='.$content.'&amp;id='.$id.'&amp;origin='.$origin.'">'.icon('../img/invisible.gif',get_lang('MakeVisible')).'</a>';
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
	$id = Security::remove_XSS($id);
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
function display_up_down_icon($content, $id, $list) {
	$id = strval(intval($id));
	$total_items=count($list);
	$position = 0;
	$internal_counter=0;

	if(is_array($list)) {
		foreach ($list as $key=>$listitem) {
			$internal_counter++;
			if ($id==$key) {
				$position=$internal_counter;
			}
		}
	}
	if ($position>1) {
		$return_value='<a href="'.api_get_self().'?'.api_get_cidreq().'&action=move&amp;direction=up&amp;content='.$content.'&amp;forumcategory='.Security::remove_XSS($_GET['forumcategory']).'&amp;id='.$id.'" title="'.get_lang('MoveUp').'"><img src="../img/up.gif" /></a>';
	} else {
		$return_value='<img src="../img/up_na.gif" />';
	}
	
	if ($position<$total_items) {
		$return_value.='<a href="'.api_get_self().'?'.api_get_cidreq().'&action=move&amp;direction=down&amp;content='.$content.'&amp;forumcategory='.Security::remove_XSS($_GET['forumcategory']).'&amp;id='.$id.'" title="'.get_lang('MoveDown').'" ><img src="../img/down.gif" /></a>';
	} else {
		
	   $return_value.='<img src="../img/down_na.gif" />';
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
function change_visibility($content, $id, $target_visibility) {
	global $_course;
	$constants=array('forumcategory'=>TOOL_FORUM_CATEGORY, 'forum'=>TOOL_FORUM, 'thread'=>TOOL_FORUM_THREAD);
	api_item_property_update($_course,$constants[$content],$id,$target_visibility,api_get_user_id()); // note: check if this returns true or false => returnmessage depends on it.
	if ($target_visibility=='visible') {
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
function change_lock_status($content, $id, $action) {
	global $table_categories;
	global $table_forums;
	global $table_threads;
	global $table_posts;

	// Determine the relevant table
	if ($content=='forumcategory') {
		$table=$table_categories;
		$id_field='cat_id';
	} elseif ($content=='forum') {
		$table=$table_forums;
		$id_field='forum_id';
	} elseif ($content=='thread') {
		$table=$table_threads;
		$id_field='thread_id';
	} else {
		return get_lang('Error');
	}

	// Determine what we are doing => defines the value for the database and the return message
	if ($action=='lock') {
		$db_locked=1;
		$return_message=get_lang('Locked');
	} elseif ($action=='unlock') {
		$db_locked=0;
		$return_message=get_lang('Unlocked');
	} else {
		return get_lang('Error');
	}

	// Doing the change in the database
	$sql="UPDATE $table SET locked='".Database::escape_string($db_locked)."' WHERE $id_field='".Database::escape_string($id)."'";
	if (api_sql_query($sql,__FILE__,__LINE__)) {
		return $return_message;
	} else {
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
function move_up_down($content, $direction, $id) {
	global $table_categories;
	global $table_forums;
	global $table_item_property;

	// Determine which field holds the sort order
	if ($content=='forumcategory') {
		$table=$table_categories;
		$sort_column='cat_order';
		$id_column='cat_id';
		$sort_column='cat_order';
	} elseif ($content=='forum') {
		$table=$table_forums;
		$sort_column='forum_order';
		$id_column='forum_id';
		$sort_column='forum_order';
		// we also need the forum_category of this forum
		$sql="SELECT forum_category FROM $table_forums WHERE forum_id=".Database::escape_string($id);
		$result=api_sql_query($sql,__FILE__,__LINE__);
		$row=Database::fetch_array($result);
		$forum_category=$row['forum_category'];
	} else {
		return get_lang('Error');
	}

	// determine if need to sort ascending or descending
	if ($direction=='down') {
		$sort_direction='ASC';
	} elseif ($direction=='up') {
		$sort_direction='DESC';
	} else {
		return get_lang('Error');
	}

	// The SQL statement
	if ($content=='forumcategory') {
		$sql="SELECT * FROM".$table_categories." forum_categories, ".$table_item_property." item_properties
					WHERE forum_categories.cat_id=item_properties.ref
					AND item_properties.tool='".TOOL_FORUM_CATEGORY."'
					ORDER BY forum_categories.cat_order $sort_direction";
	}
	if ($content=='forum') {
		$sql="SELECT * FROM".$table." WHERE forum_category='".Database::escape_string($forum_category)."' ORDER BY forum_order $sort_direction";
	}
	// echo $sql.'<br />';
	// finding the items that need to be switched
	$result=api_sql_query($sql,__FILE__,__LINE__);
	$found=false;
	while ($row=Database::fetch_array($result)) {
		//echo $row[$id_column].'-';
		if ($found==true) {
			$next_id=$row[$id_column];
			$next_sort=$row[$sort_column];
			$found=false;
		}
		if($id==$row[$id_column]) {
			$this_id=$id;
			$this_sort=$row[$sort_column];
			$found=true;
		}
	}

	// Committing the switch
	// we do an extra check if we do not have illegal values. If your remove this if statment you will
	// be able to mess with the sorting by refreshing the page over and over again.
	if ($this_sort<>'' && $next_sort<>'' && $next_id<>'' && $this_id<>'') {
		$sql_update1="UPDATE $table SET $sort_column='".Database::escape_string($this_sort)."' WHERE $id_column='".Database::escape_string($next_id)."'";
		$sql_update2="UPDATE $table SET $sort_column='".Database::escape_string($next_sort)."' WHERE $id_column='".Database::escape_string($this_id)."'";
		api_sql_query($sql_update1,__FILE__,__LINE__);
		api_sql_query($sql_update2,__FILE__,__LINE__);
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
function class_visible_invisible($current_visibility_status) {
	if ($current_visibility_status=='0') {
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
function get_forum_categories($id='') {
	$table_categories		= Database :: get_course_table(TABLE_FORUM_CATEGORY);
	$table_item_property	= Database :: get_course_table(TABLE_ITEM_PROPERTY);
	$forum_categories_list=array();
	if ($id=='') {
		$sql="SELECT * FROM".$table_categories." forum_categories, ".$table_item_property." item_properties
					WHERE forum_categories.cat_id=item_properties.ref
					AND item_properties.visibility=1
					AND item_properties.tool='".TOOL_FORUM_CATEGORY."'
					ORDER BY forum_categories.cat_order ASC";
		if (is_allowed_to_edit()) {
			$sql="SELECT * FROM".$table_categories." forum_categories, ".$table_item_property." item_properties
					WHERE forum_categories.cat_id=item_properties.ref
					AND item_properties.visibility<>2
					AND item_properties.tool='".TOOL_FORUM_CATEGORY."'
					ORDER BY forum_categories.cat_order ASC";
		}
	} else {
		$sql="SELECT * FROM".$table_categories." forum_categories, ".$table_item_property." item_properties
				WHERE forum_categories.cat_id=item_properties.ref
				AND item_properties.tool='".TOOL_FORUM_CATEGORY."'
				AND forum_categories.cat_id='".Database::escape_string($id)."'
				ORDER BY forum_categories.cat_order ASC";
	}
	$result=api_sql_query($sql,__FILE__,__LINE__);
	while ($row=Database::fetch_array($result)) {
		if ($id=='') {
			$forum_categories_list[$row['cat_id']]=$row;
		} else {
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
	$forum_list=array();
	$sql="SELECT * FROM ".$table_forums." forum , ".$table_item_property." item_properties
				WHERE forum.forum_category='".Database::escape_string($cat_id)."'
				AND forum.forum_id=item_properties.ref
				AND item_properties.visibility=1
				AND item_properties.tool='".TOOL_FORUM."'
				ORDER BY forum.forum_order ASC";
	if (is_allowed_to_edit()) {
		$sql="SELECT * FROM ".$table_forums." forum , ".$table_item_property." item_properties
				WHERE forum.forum_category='".Database::escape_string($cat_id)."'
				AND forum.forum_id=item_properties.ref
				AND item_properties.visibility<>2
				AND item_properties.tool='".TOOL_FORUM."'
				ORDER BY forum_order ASC";
	}
	$result=api_sql_query($sql,__FILE__,__LINE__);
	while ($row=Database::fetch_array($result)) {
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
function get_forums($id='') {
	global $table_forums;	
	global $table_threads;
	global $table_posts;
	global $table_item_property;
	global $table_users;

	// **************** GETTING ALL THE FORUMS ************************* //
	
	$session_condition = isset($_SESSION['id_session']) ? 'AND forum.session_id IN (0,'.intval($_SESSION['id_session']).')' : '';
	$forum_list = array();
	if ($id=='') {
		//-------------- Student -----------------//
		// select all the forum information of all forums (that are visible to students)
		$sql="SELECT * FROM ".$table_forums." forum , ".$table_item_property." item_properties
					WHERE forum.forum_id=item_properties.ref
					AND item_properties.visibility=1
					AND item_properties.tool='".TOOL_FORUM."'
					$session_condition
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
		if (is_allowed_to_edit()) {
			// select all the forum information of all forums (that are not deleted)
			$sql="SELECT * FROM ".$table_forums." forum , ".$table_item_property." item_properties
							WHERE forum.forum_id=item_properties.ref
							AND item_properties.visibility<>2
							AND item_properties.tool='".TOOL_FORUM."'
							$session_condition
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
	else {
		// select all the forum information of the given forum (that is not deleted)
		$sql="SELECT * FROM ".$table_forums." forum , ".$table_item_property." item_properties
							WHERE forum.forum_id=item_properties.ref
							AND forum_id='".Database::escape_string($id)."'
							AND item_properties.visibility<>2
							AND item_properties.tool='".TOOL_FORUM."'
							$session_condition
							ORDER BY forum_order ASC";
		// select the number of threads of the forum
		$sql2="SELECT count(*) AS number_of_threads, forum_id FROM $table_threads WHERE forum_id=".Database::escape_string($id)." GROUP BY forum_id";
		// select the number of posts of the forum
		$sql3="SELECT count(*) AS number_of_posts, forum_id FROM $table_posts WHERE forum_id=".Database::escape_string($id)." GROUP BY forum_id";
		// select the last post and the poster (note: this is probably no longer needed)
		$sql4="SELECT  post.post_id, post.forum_id, post.poster_id, post.poster_name, post.post_date, users.lastname, users.firstname
					FROM $table_posts post, $table_users users
					WHERE forum_id=".Database::escape_string($id)."
					AND post.poster_id=users.user_id
					GROUP BY post.forum_id
					ORDER BY post.post_id ASC";
	}
	// handling all the forum information
	$result=api_sql_query($sql,__FILE__,__LINE__);
	while ($row=Database::fetch_array($result)) {
		if ($id=='') {
			$forum_list[$row['forum_id']]=$row;
		} else {
			$forum_list=$row;
		}
	}

	// handling the threadcount information
	$result2=api_sql_query($sql2,__FILE__,__LINE__);
	while ($row2=Database::fetch_array($result2)) {
		if ($id=='') {
			$forum_list[$row2['forum_id']]['number_of_threads']=$row2['number_of_threads'];
		} else {
			$forum_list['number_of_threads']=$row2['number_of_threads'];;
		}
	}
	// handling the postcount information
	$result3=api_sql_query($sql3,__FILE__,__LINE__);
	while ($row3=Database::fetch_array($result3)) {
		if ($id=='') {
			if (array_key_exists($row3['forum_id'],$forum_list)) {// this is needed because sql3 takes also the deleted forums into account
				$forum_list[$row3['forum_id']]['number_of_posts']=$row3['number_of_posts'];
			}
		} else {
			$forum_list['number_of_posts']=$row3['number_of_posts'];
		}
	}

	// finding the last post information (last_post_id, last_poster_id, last_post_date, last_poster_name, last_poster_lastname, last_poster_firstname)
	if ($id=='') {
		if(is_array($forum_list)) {
			foreach ($forum_list as $key=>$value) {
				$last_post_info_of_forum=get_last_post_information($key,is_allowed_to_edit());
				$forum_list[$key]['last_post_id']=$last_post_info_of_forum['last_post_id'];
				$forum_list[$key]['last_poster_id']=$last_post_info_of_forum['last_poster_id'];
				$forum_list[$key]['last_post_date']=$last_post_info_of_forum['last_post_date'];
				$forum_list[$key]['last_poster_name']=$last_post_info_of_forum['last_poster_name'];
				$forum_list[$key]['last_poster_lastname']=$last_post_info_of_forum['last_poster_lastname'];
				$forum_list[$key]['last_poster_firstname']=$last_post_info_of_forum['last_poster_firstname'];
			}
		} else {
			$forum_list = array();
		}
	} else {
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
function get_last_post_information($forum_id, $show_invisibles=false) {
	global $table_forums;
	global $table_threads;
	global $table_posts;
	global $table_item_property;
	global $table_users;

	$sql="SELECT post.post_id, post.forum_id, post.poster_id, post.poster_name, post.post_date, users.lastname, users.firstname, post.visible, thread_properties.visibility AS thread_visibility, forum_properties.visibility AS forum_visibility
				FROM $table_posts post, $table_users users, $table_item_property thread_properties,  $table_item_property forum_properties
				WHERE post.forum_id=".Database::escape_string($forum_id)."
				AND post.poster_id=users.user_id
				AND post.thread_id=thread_properties.ref
				AND thread_properties.tool='".TOOL_FORUM_THREAD."'
				AND post.forum_id=forum_properties.ref
				AND forum_properties.tool='".TOOL_FORUM."'
				ORDER BY post.post_id DESC";
	$result=api_sql_query($sql,__FILE__,__LINE__);
	if ($show_invisibles==true) {
		$row=Database::fetch_array($result);
		$return_array['last_post_id']=$row['post_id'];
		$return_array['last_poster_id']=$row['poster_id'];
		$return_array['last_post_date']=$row['post_date'];
		$return_array['last_poster_name']=$row['poster_name'];
		$return_array['last_poster_lastname']=$row['lastname'];
		$return_array['last_poster_firstname']=$row['firstname'];
		return $return_array;
	} else {
		// we have to loop through the results to find the first one that is actually visible to students (forum_category, forum, thread AND post are visible)
		while ($row=Database::fetch_array($result)) {
			if ($row['visible']=='1' AND $row['thread_visibility']=='1' AND $row['forum_visibility']=='1') {
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
function get_threads($forum_id) {
	global $table_item_property;
	global $table_threads;
	global $table_posts;
	global $table_users;
	$thread_list=array();
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
				AND item_properties.tool='".TABLE_FORUM_THREAD."'
			LEFT JOIN $table_users users
				ON thread.thread_poster_id=users.user_id
			LEFT JOIN $table_posts post
				ON thread.thread_last_post = post.post_id
			LEFT JOIN $table_users last_poster_users
				ON post.poster_id= last_poster_users.user_id
			WHERE thread.forum_id='".Database::escape_string($forum_id)."'
			ORDER BY thread.thread_sticky DESC, thread.thread_date DESC";
	if (is_allowed_to_edit()) {
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
					AND item_properties.tool='".TABLE_FORUM_THREAD."'
				LEFT JOIN $table_users users
					ON thread.thread_poster_id=users.user_id
				LEFT JOIN $table_posts post
					ON thread.thread_last_post = post.post_id
				LEFT JOIN $table_users last_poster_users
					ON post.poster_id= last_poster_users.user_id
				WHERE thread.forum_id='".Database::escape_string($forum_id)."'
				ORDER BY thread.thread_sticky DESC, thread.thread_date DESC";
	}	
	$result=api_sql_query($sql, __FILE__, __LINE__);
	while ( $row=Database::fetch_array($result,'ASSOC') ) {
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
function get_posts($thread_id) {
	global $table_posts;
	global $table_users;

	// note: change these SQL so that only the relevant fields of the user table are used
	if (api_is_allowed_to_edit()) {
		$sql = "SELECT * FROM $table_posts posts
				LEFT JOIN  $table_users users
					ON posts.poster_id=users.user_id
				WHERE posts.thread_id='".Database::escape_string($thread_id)."'
				ORDER BY posts.post_id ASC";
	} else {
		// students can only se the posts that are approved (posts.visible='1')
		$sql = "SELECT * FROM $table_posts posts
				LEFT JOIN  $table_users users
					ON posts.poster_id=users.user_id
				WHERE posts.thread_id='".Database::escape_string($thread_id)."'
				AND posts.visible='1'
				ORDER BY posts.post_id ASC";
	}
	$result=api_sql_query($sql, __FILE__, __LINE__);
	while ($row=Database::fetch_array($result)) {
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
function icon($image_url,$alt='',$title='') {
	if ($title=='') {
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
function get_post_information($post_id) {
	global $table_posts;
	global $table_users;

	$sql="SELECT * FROM ".$table_posts."posts, ".$table_users." users WHERE posts.poster_id=users.user_id AND posts.post_id='".Database::escape_string($post_id)."'";
	$result=api_sql_query($sql, __FILE__, __LINE__);
	$row=Database::fetch_array($result);
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
function get_thread_information($thread_id) {
	global $table_threads;
	global $table_item_property;

	$sql="SELECT * FROM ".$table_threads." threads, ".$table_item_property." item_properties
			WHERE item_properties.tool='".TOOL_FORUM_THREAD."'
			AND item_properties.ref='".Database::escape_string($thread_id)."'
			AND threads.thread_id='".Database::escape_string($thread_id)."'";
	$result=api_sql_query($sql, __FILE__, __LINE__);
	$row=Database::fetch_array($result);
	return $row;
}

/**
* This function retrieves forum thread users details
* @param 	int Thread ID
* @param	string	Course DB name (optional)
* @return	array Array of type ([user_id=>w,lastname=>x,firstname=>y,thread_id=>z],[])
* @author Christian Fasanando <christian.fasanando@dokeos.com>, 
* @version octubre 2008, dokeos 1.8
*/

function get_thread_users_details($thread_id, $db_name = null) {
	$t_posts = Database :: get_course_table(TABLE_FORUM_POST, (empty($db_name)?null:$db_name));
	$t_users = Database :: get_main_table(TABLE_MAIN_USER);
	$t_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
	$sql = "SELECT DISTINCT user.user_id, user.lastname, user.firstname, thread_id
			  FROM $t_posts , $t_users user, $t_course_user course_user
			  WHERE poster_id = user.user_id
			  AND user.user_id = course_user.user_id		
			  AND thread_id = '".Database::escape_string($thread_id)."' 
			  AND course_user.status NOT IN('1')
			  AND course_code = '".api_get_course_id()."'";

	$result = api_sql_query($sql, __FILE__, __LINE__);
	return $result;
}

/**
* This function retrieves forum thread users qualify
* @param 	int Thread ID
* @param	string	Course DB name (optional)
* @return	array Array of type ([user_id=>w,lastname=>x,firstname=>y,thread_id=>z],[])
* @author Jhon Hinojosa<jhon.hinojosa@dokeos.com>, 
* @version octubre 2008, dokeos 1.8
*/

function get_thread_users_qualify($thread_id, $db_name = null) {
	$t_posts = Database :: get_course_table(TABLE_FORUM_POST, (empty($db_name)?null:$db_name));
	$t_qualify = Database :: get_course_table(TABLE_FORUM_THREAD_QUALIFY, (empty($db_name)?null:$db_name));
	$t_users = Database :: get_main_table(TABLE_MAIN_USER);
	$t_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);  
			  				  
  $sql = "SELECT post.poster_id, user.lastname, user.firstname, post.thread_id,user.user_id,qualify.qualify
						FROM $t_posts post, 
						     $t_qualify qualify,
						     $t_users user,
						     $t_course_user course_user		
						WHERE 
						     post.poster_id = user.user_id
						     AND post.poster_id = qualify.user_id
						     AND user.user_id = course_user.user_id								     	
						     AND qualify.thread_id = '".Database::escape_string($thread_id)."'
						     AND course_user.status not in('1')
						     AND course_code = '".api_get_course_id()."'		
						GROUP BY post.poster_id ";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	return $result;
}

/**
* This function retrieves forum thread users not qualify
* @param 	int Thread ID
* @param	string	Course DB name (optional)
* @return	array Array of type ([user_id=>w,lastname=>x,firstname=>y,thread_id=>z],[])
* @author Jhon Hinojosa<jhon.hinojosa@dokeos.com>, 
* @version octubre 2008, dokeos 1.8
*/

function get_thread_users_not_qualify($thread_id, $db_name = null) {
	$t_posts = Database :: get_course_table(TABLE_FORUM_POST, (empty($db_name)?null:$db_name));
	$t_qualify = Database :: get_course_table(TABLE_FORUM_THREAD_QUALIFY, (empty($db_name)?null:$db_name));
	$t_users = Database :: get_main_table(TABLE_MAIN_USER);
	$t_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);  
				  
  	$sql1 = "select user_id FROM  $t_qualify WHERE thread_id = '".$thread_id."'";
	$result1 = api_sql_query($sql1,__FILE__,__LINE__);    
    $cad='';
    while ($row=Database::fetch_array($result1)) {
    	$cad .= $row['user_id'].',';	
    }
    if($cad=='') {
    	$cad='0';
    } else  {
    	$cad=substr($cad,0,strlen($cad)-1);
    }
	$sql = "SELECT DISTINCT user.user_id, user.lastname, user.firstname, post.thread_id
			  FROM $t_posts post, $t_users user,$t_course_user course_user
			  WHERE post.poster_id = user.user_id
			  AND user.user_id NOT IN (".$cad.")
			  AND user.user_id = course_user.user_id		
			  AND post.thread_id = '".Database::escape_string($thread_id)."'
			  AND course_user.status not in('1')
			  AND course_code = '".api_get_course_id()."'";
	
	$result = api_sql_query($sql, __FILE__, __LINE__);
	return $result;
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
function get_forum_information($forum_id) {
	global $table_forums;
	global $table_item_property;

	$sql="SELECT * FROM ".$table_forums." forums, ".$table_item_property." item_properties
			WHERE item_properties.tool='".TOOL_FORUM."'
			AND item_properties.ref='".Database::escape_string($forum_id)."'
			AND forums.forum_id='".Database::escape_string($forum_id)."'";
	$result=api_sql_query($sql, __FILE__, __LINE__);
	$row=Database::fetch_array($result);
	$row['approval_direct_post'] = 0; // we can't anymore change this option, so it should always be activated
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
function get_forumcategory_information($cat_id) {
	global $table_categories;
	global $table_item_property;

	$sql="SELECT * FROM ".$table_categories." forumcategories, ".$table_item_property." item_properties
			WHERE item_properties.tool='".TOOL_FORUM_CATEGORY."'
			AND item_properties.ref='".Database::escape_string($cat_id)."'
			AND forumcategories.cat_id='".Database::escape_string($cat_id)."'";
	$result=api_sql_query($sql, __FILE__, __LINE__);
	$row=Database::fetch_array($result);
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
function count_number_of_forums_in_category($cat_id) {
	global $table_forums;

	$sql="SELECT count(*) AS number_of_forums FROM ".$table_forums." WHERE forum_category='".Database::escape_string($cat_id)."'";
	$result=api_sql_query($sql, __FILE__, __LINE__);
	$row=Database::fetch_array($result);
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
function store_thread($values) {
	global $table_threads;
	global $table_posts;
	global $_user;
	global $_course;
	global $current_forum;
	global $origin;
	global $forum_table_attachment;

	$upload_ok=1;
	$has_attachment=false;
		
	if(!empty($_FILES['user_upload']['name'])) {
		$upload_ok = process_uploaded_file($_FILES['user_upload']);
		$has_attachment=true;
	}
	if($upload_ok) {		
	
		$post_date=date('Y-m-d H:i:s');
	
		if ($current_forum['approval_direct_post']=='1' AND !api_is_allowed_to_edit()) {
			$visible=0; // the post is not approved yet.
		} else {
			$visible=1;
		}
		
		$clean_post_title=Security::remove_XSS(Database::escape_string(htmlspecialchars($values['post_title'])));
		
		// We first store an entry in the forum_thread table because the thread_id is used in the forum_post table
		$sql="INSERT INTO $table_threads (thread_title, forum_id, thread_poster_id, thread_poster_name, thread_date, thread_sticky,thread_title_qualify,thread_qualify_max,thread_weight,session_id)
				VALUES ('".$clean_post_title."',
						'".Database::escape_string($values['forum_id'])."',
						'".Database::escape_string($_user['user_id'])."',
						'".Database::escape_string(isset($values['poster_name'])?$values['poster_name']:null)."',
						'".Database::escape_string($post_date)."',
						'".Database::escape_string(isset($values['thread_sticky'])?$values['thread_sticky']:null)."'," .
						"'".Database::escape_string($values['calification_notebook_title'])."'," .
						"'".Database::escape_string($values['numeric_calification'])."'," .
						"'".Database::escape_string($values['weight_calification'])."'," .
						"'".api_get_session_id()."')";
		$result=api_sql_query($sql, __LINE__, __FILE__);
		$last_thread_id=Database::insert_id();

		//add option gradebook qualify 
		
		if(isset($values['thread_qualify_gradebook']) && 1==$values['thread_qualify_gradebook']) {
			//add function gradebook
			$coursecode=api_get_course_id();
			$resourcetype=5;
			$resourceid=$last_thread_id;
			$resourcename=$values['calification_notebook_title'];
			$maxqualify=$values['numeric_calification'];
			$weigthqualify=$values['weight_calification'];
			$resourcedescription='';
			$date=time();
			//is_resource_in_course_gradebook($course_code, $resource_type, $resource_id);
			add_resource_to_course_gradebook($coursecode,$resourcetype,$resourceid,$resourcename,$weigthqualify,$maxqualify,$resourcedescription,$date,0,api_get_session_id());
					
			}
		
		api_item_property_update($_course, TOOL_FORUM_THREAD, $last_thread_id,"ForumThreadAdded", api_get_user_id());
		// if the forum properties tell that the posts have to be approved we have to put the whole thread invisible
		// because otherwise the students will see the thread and not the post in the thread.
		// we also have to change $visible because the post itself has to be visible in this case (otherwise the teacher would have
		// to make the thread visible AND the post
		
		if ($visible==0) {
			api_item_property_update($_course, TOOL_FORUM_THREAD, $last_thread_id,"invisible", api_get_user_id());
			$visible=1;
		}
		// We now store the content in the table_post table
		$sql="INSERT INTO $table_posts (post_title, post_text, thread_id, forum_id, poster_id, poster_name, post_date, post_notification, post_parent_id, visible)
				VALUES ('".$clean_post_title."',
				'".Database::escape_string($values['post_text'])."',
				'".Database::escape_string($last_thread_id)."',
				'".Database::escape_string($values['forum_id'])."',
				'".Database::escape_string($_user['user_id'])."',
				'".Database::escape_string(isset($values['poster_name'])?$values['poster_name']:null)."',
				'".Database::escape_string($post_date)."',
				'".Database::escape_string(isset($values['post_notification'])?$values['post_notification']:null)."','0',
				'".Database::escape_string($visible)."')";
		api_sql_query($sql, __FILE__,__LINE__);
		$last_post_id=Database::insert_id();
				
		// now have to update the thread table to fill the thread_last_post field (so that we know when the thread has been updated for the last time)
		$sql="UPDATE $table_threads SET thread_last_post='".Database::escape_string($last_post_id)."'  WHERE thread_id='".Database::escape_string($last_thread_id)."'";
		$result=api_sql_query($sql, __LINE__, __FILE__);
		$message=get_lang('NewThreadStored');
		// Storing the attachments if any
		if ($has_attachment) {			
			$courseDir   = $_course['path'].'/upload/forum';
			$sys_course_path = api_get_path(SYS_COURSE_PATH);		
			$updir = $sys_course_path.$courseDir;
						
			// Try to add an extension to the file if it hasn't one
			$new_file_name = add_ext_on_mime(stripslashes($_FILES['user_upload']['name']), $_FILES['user_upload']['type']);	
		
			// user's file name
			$file_name =$_FILES['user_upload']['name'];
						
			if (!filter_extension($new_file_name))  {
				Display :: display_error_message(get_lang('UplUnableToSaveFileFilteredExtension'));				
			} else {
				if ($result) {
					$comment = Database::escape_string($comment);				
					add_forum_attachment_file($comment,$last_post_id);
				}										
			}			 
		} else {
			$message.='<br />';
		}

		if ($current_forum['approval_direct_post']=='1' AND !api_is_allowed_to_edit()) {
			$message.=get_lang('MessageHasToBeApproved').'<br />';
			$message.=get_lang('ReturnTo').' <a href="viewforum.php?'.api_get_cidreq().'&forum='.$values['forum_id'].'&gidReq='.$_SESSION['toolgroup'].'&origin='.$origin.'">'.get_lang('Forum').'</a><br />';
		} else {
			$message.=get_lang('ReturnTo').' <a href="viewforum.php?'.api_get_cidreq().'&forum='.$values['forum_id'].'&gidReq='.$_SESSION['toolgroup'].'&origin='.$origin.'">'.get_lang('Forum').'</a><br />';
			$message.=get_lang('ReturnTo').' <a href="viewthread.php?'.api_get_cidreq().'&forum='.$values['forum_id'].'&origin='.$origin.'&amp;thread='.$last_thread_id.'">'.get_lang('Message').'</a>';
		}
		$reply_info['new_post_id'] = $last_post_id;
		$my_post_notification=isset($values['post_notification']) ? $values['post_notification'] : null;
		if ($my_post_notification == 1) {
			set_notification('thread',$last_thread_id, true);
		}		
		
		send_notification_mails($last_thread_id,$reply_info);
	
		session_unregister('formelements');
		session_unregister('origin');
		session_unregister('breadcrumbs');
		session_unregister('addedresource');
		session_unregister('addedresourceid');
	
		Display :: display_confirmation_message($message,false);
	} else {
		Display::display_error_message(get_lang('UplNoFileUploaded'));			
	}
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
function show_add_post_form($action='', $id='', $form_values='') {
	global $forum_setting;
	global $current_forum;
	global $_user;
	global $origin;
	global $charset; 

	// initiate the object
	$my_thread  = isset($_GET['thread']) ? $_GET['thread']:'';
	$my_forum   = isset($_GET['forum'])  ? $_GET['forum']:'';
	$my_action  = isset($_GET['action']) ? $_GET['action']:'';
	$my_post    = isset($_GET['post'])   ? $_GET['post']:'';
	$my_gradebook    = isset($_GET['gradebook'])   ? $_GET['gradebook']:'';
	$form = new FormValidator('thread', 'post', api_get_self().'?forum='.Security::remove_XSS($my_forum).'&thread='.Security::remove_XSS($my_thread).'&post='.Security::remove_XSS($my_post).'&action='.Security::remove_XSS($my_action).'&origin='.$origin);
	$form->setConstants(array('forum' => '5'));

	// settting the form elements
	$form->addElement('hidden', 'forum_id', strval(intval($my_forum)));
	$form->addElement('hidden', 'thread_id', strval(intval($my_thread)));
	$form->addElement('hidden', 'gradebook', $my_gradebook);

	// if anonymous posts are allowed we also display a form to allow the user to put his name or username in
	if ($current_forum['allow_anonymous']==1 AND !isset($_user['user_id'])) {
		$form->addElement('text', 'poster_name', get_lang('Name'));
	}

	$form->addElement('text', 'post_title', get_lang('Title'),'class="input_titles"');
	$form->addElement('html_editor', 'post_text', get_lang('Text'));
	$form->addElement('static','Group','','<a href="javascript://" onclick="return advanced_parameters()"><span id="img_plus_and_minus"><img src="../img/nolines_plus.gif" alt="" />'.get_lang('AdvancedParameters').'</span></a>');
	$form->addElement('html','<div id="id_qualify" style="display:none">');
	if( (api_is_course_admin() || api_is_course_coach() || api_is_course_tutor()) && !($my_thread) ){
		// thread qualify
		
		$form->addElement('static','Group', '<br /><strong>'.get_lang('AlterQualifyThread').'</strong>');
		$form->addElement('text', 'numeric_calification', get_lang('QualifyNumeric'),'Style="width:40px"');
		$form->addElement('checkbox', 'thread_qualify_gradebook', '', get_lang('QualifyThreadGradebook'),'onclick="javascript:if(this.checked==true){document.getElementById(\'options_field\').style.display = \'block\';}else{document.getElementById(\'options_field\').style.display = \'none\';}"');

		$form -> addElement('html','<div id="options_field" style="display:none">');
		$form->addElement('text', 'calification_notebook_title', get_lang('TitleColumnGradebook'));
		$form->addElement('text', 'weight_calification', get_lang('QualifyWeight'),'value="0.00" Style="width:40px" onfocus="this.select();"');
		$form->addElement('html','</div>'); 
	}

	if ($forum_setting['allow_post_notificiation'] AND isset($_user['user_id'])) {
		$form->addElement('checkbox', 'post_notification', '', get_lang('NotifyByEmail').' ('.$_user['mail'].')');
	}

	if ($forum_setting['allow_sticky'] AND api_is_allowed_to_edit() AND $action=='newthread') {
		$form->addElement('checkbox', 'thread_sticky', '', get_lang('StickyPost'));
	}

	if ($current_forum['allow_attachments']=='1' OR api_is_allowed_to_edit()) {
		//$form->add_resource_button();
		$values = $form->exportValues();
	}
	
	// user upload
	$form->addElement('html','<br /><b><div class="row"><div class="label">'.get_lang('AddAnAttachment').'</div></div></b><br /><br />');
	$form->addElement('file','user_upload',get_lang('FileName'),'');		
	$form->addElement('textarea','file_comment',get_lang('FileComment'),array ('rows' => 4, 'cols' => 34));
	$form->addElement('html','</div>');
	$userid  =api_get_user_id();
	$info    =api_get_user_info($userid);
	$courseid=api_get_course_id();		
	
	if ($_GET['action']=='quote'){
		$class='save';
		$text=get_lang('QuoteMessage');
	}elseif ($_GET['action']=='replymessage'){
		$class='save';
		$text=get_lang('ReplyToThread');		
	}else {
		$class='add';
		$text=get_lang('CreateThread');		
	}
		
	$form->addElement('style_submit_button', 'SubmitPost', $text, 'class="'.$class.'"');	
	$form->add_real_progress_bar('DocumentUpload','user_upload');

	if ( !empty($form_values) ) {
		$defaults['post_title']=prepare4display(Security::remove_XSS($form_values['post_title']));
		$defaults['post_text']=prepare4display(Security::remove_XSS($form_values['post_text']));
		$defaults['post_notification']=Security::remove_XSS($form_values['post_notification']);
		$defaults['thread_sticky']=Security::remove_XSS($form_values['thread_sticky']);
	}

	// if we are quoting a message we have to retrieve the information of the post we are quoting so that
	// we can add this as default to the textarea
	if (($action=='quote' || $action=='replymessage') && isset($my_post)) {
		// we also need to put the parent_id of the post in a hidden form when we are quoting or replying to a message (<> reply to a thread !!!)
		$form->addElement('hidden', 'post_parent_id', strval(intval($my_post))); // note this has to be cleaned first

		// if we are replying or are quoting then we display a default title.
 		$values=get_post_information($my_post); // note: this has to be cleaned first
		$defaults['post_title']=get_lang('ReplyShort').html_entity_decode($values['post_title'],ENT_QUOTES,$charset);
		// When we are quoting a message then we have to put that message into the wysiwyg editor.
		// note: the style has to be hardcoded here because using class="quote" didn't work
		if($action=='quote') {
			$defaults['post_text']='<div>&nbsp;</div><div style="margin: 5px;"><div style="font-size: 90%;	font-style: italic;">'.get_lang('Quoting').' '.$values['firstname'].' '.$values['lastname'].':</div><div style="color: #006600; font-size: 90%;	font-style: italic; background-color: #FAFAFA; border: #D1D7DC 1px solid; padding: 3px;">'.prepare4display($values['post_text']).'</div></div><div>&nbsp;</div><div>&nbsp;</div>';
		}
	}
	$form->setDefaults(isset($defaults)?$defaults:null);

	// the course admin can make a thread sticky (=appears with special icon and always on top)
	$form->addRule('post_title', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');
	if ($current_forum['allow_anonymous']==1 AND !isset($_user['user_id'])) {
		$form->addRule('poster_name', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');
	}

	// The validation or display
	if( $form->validate() ) {
		$check = Security::check_token('post');	
		if ($check) {
	   		$values = $form->exportValues();	   		
	   		if($values['thread_qualify_gradebook']=='1' && empty($values['weight_calification'])){
				Display::display_error_message(get_lang('YouMustAssignWeightOfQualification').'&nbsp;<a href="javascript:window.back()">'.get_lang('Back').'</a>',false);
	   			return false;	   		   						   				
	   		}	   		
	   		Security::clear_token();
	   		return $values;
		}
	   
	} else {
		$token = Security::get_token();
		$form->addElement('hidden','sec_token');
		$form->setConstants(array('sec_token' => $token));
		$form->display();
		echo '<br />';
		if ($forum_setting['show_thread_iframe_on_reply'] and $action<>'newthread') {

				echo "<iframe src=\"iframe_thread.php?forum=".Security::remove_XSS($my_forum)."&amp;thread=".Security::remove_XSS($my_thread)."#".Security::remove_XSS($my_post)."\" width=\"80%\"></iframe>";

			
		}
	}
}
/**
 * @param integer contains the information of user id
 * @param integer contains the information of thread id
 * @param integer contains the information of thread qualify
 * @param integer contains the information of user id of qualifier
 * @param integer contains the information of time 
 * @param integer contains the information of session id 
 * @return Array() optional
 * @author Isaac Flores <isaac.flores@dokeos.com>, U.N.A.S University
 * @version October 2008, dokeos  1.8.6
 **/
function store_theme_qualify($user_id,$thread_id,$thread_qualify=0,$qualify_user_id=0,$qualify_time,$session_id=null) {
	$table_threads_qualify = Database::get_course_table(TABLE_FORUM_THREAD_QUALIFY,'');
 	$table_threads		   =Database::get_course_table(TABLE_FORUM_THREAD,'');
		if ($user_id==strval(intval($user_id)) && $thread_id==strval(intval($thread_id)) && $thread_qualify==strval(floatval($thread_qualify))) {
			
		//testing
		
		$sql_string="SELECT thread_qualify_max FROM ". $table_threads ." WHERE thread_id=".$thread_id.";";
		$res_string=api_sql_query($sql_string,__FILE__,__LINE__);
		$row_string=Database::fetch_array($res_string);
		if ($thread_qualify<=$row_string[0]) {
			
			$sql1="SELECT COUNT(*) FROM ".$table_threads_qualify." WHERE user_id=".$user_id." and thread_id=".$thread_id.";";
			$res1=api_sql_query($sql1);
			$row=Database::fetch_array($res1);
		
			if ($row[0]==0) {
				$sql="INSERT INTO $table_threads_qualify (user_id," .
					"thread_id,qualify,qualify_user_id,qualify_time,session_id)" .
					"VALUES('".$user_id."','".$thread_id."',".(float)$thread_qualify."," .
					"'".$qualify_user_id."','".$qualify_time."','".$session_id."')";
				$res=api_sql_query($sql,__FILE__,__LINE__);
			
				return $res;
			} else {
				
				$sql1="SELECT qualify FROM ".$table_threads_qualify." WHERE user_id=".$user_id." and thread_id=".$thread_id.";";
				$rs=api_sql_query($sql1,__FILE__,__LINE__);
				$row=Database::fetch_array($rs);
				$row[1]="update";
				return $row;
					
			}
				
		}else{
			return null;
		}
	}
}
/**
* This function show qualify.
* @param string contains the information of option to run
* @param string contains the information the current course id
* @param integer contains the information the current forum id
* @param integer contains the information the current user id
* @param integer contains the information the current thread id
* @return integer qualify
* @example $option=1 obtained the qualification of the current thread
* @author Isaac Flores <isaac.flores@dokeos.com>, U.N.A.S University
* @version October 2008, dokeos  1.8.6
*/
 function show_qualify($option,$couser_id,$forum_id,$user_id,$thread_id){

 	$table_threads_qualify = Database::get_course_table(TABLE_FORUM_THREAD_QUALIFY,'');
 	$table_threads		   =Database::get_course_table(TABLE_FORUM_THREAD,'');
	if ($user_id==strval(intval($user_id)) && $thread_id==strval(intval($thread_id)) && $option==1) {
 		
 		$sql="SELECT qualify FROM ".$table_threads_qualify." WHERE user_id=".$user_id." and thread_id=".$thread_id.";";
		$rs=api_sql_query($sql,__FILE__,__LINE__);
		$row=Database::fetch_array($rs);
		return $row[0];
 	}
 	
	if ($user_id==strval(intval($user_id)) && $option==2) {
 		
 		$sql="SELECT thread_qualify_max FROM ".$table_threads." WHERE thread_id=".$thread_id.";";
		$rs=api_sql_query($sql,__FILE__,__LINE__);
		$row=Database::fetch_array($rs);
		return $row[0];
 	}
 	
 }
 /**
* 
*  This function get qualify historical.
* @param integer contains the information the current user id
* @param integer contains the information the current thread id
* @param boolean contains the information of option to run
* @return array()
* @author Christian Fasanando <christian.fasanando@dokeos.com>, 
* @author Isaac Flores <isaac.flores@dokeos.com>, 
* @version October 2008, dokeos  1.8.6
*/
 function get_historical_qualify($user_id,$thread_id,$opt) {
	$my_qualify_log=array();
 	$table_threads_qualify_log = Database::get_course_table(TABLE_FORUM_THREAD_QUALIFY_LOG,''); 	
	$opt = Database::escape_string($opt);	
		if ($opt=='false') {
			$sql="SELECT * FROM ".$table_threads_qualify_log." WHERE thread_id='".Database::escape_string($thread_id)."' and user_id='".Database::escape_string($user_id)."' ORDER BY qualify_time";	
		} else {
			$sql="SELECT * FROM ".$table_threads_qualify_log." WHERE thread_id='".Database::escape_string($thread_id)."' and user_id='".Database::escape_string($user_id)."' ORDER BY qualify_time DESC";	
		} 		
		$rs=api_sql_query($sql,__FILE__,__LINE__);
		while ($row=Database::fetch_array($rs,'ASSOC')) {
			$my_qualify_log[]=$row;
		}		
		return $my_qualify_log; 	
 } 

/**
* 
*  This function store qualify historical.
* @param boolean contains the information of option to run
* @param string contains the information the current course id
* @param integer contains the information the current forum id
* @param integer contains the information the current user id
* @param integer contains the information the current thread id
* @param integer contains the information the current qualify
* @return void
* @example $option=1 obtained the qualification of the current thread
* @author Isaac Flores <isaac.flores@dokeos.com>, U.N.A.S University
* @version October 2008, dokeos  1.8.6
*/
function store_qualify_historical($option,$couser_id,$forum_id,$user_id,$thread_id,$current_qualify,$qualify_user_id) {
	
	$table_threads_qualify = Database::get_course_table(TABLE_FORUM_THREAD_QUALIFY,'');
	$table_threads		   =Database::get_course_table(TABLE_FORUM_THREAD,'');
	$table_threads_qualify_log=Database::get_course_table(TABLE_FORUM_THREAD_QUALIFY_LOG,'');
	$current_date=date('Y-m-d H:i:s');
	
		
	if ($user_id==strval(intval($user_id)) && $thread_id==strval(intval($thread_id)) && $option==1) {
 		//extract information of thread_qualify 
 		
 		$sql="SELECT qualify,qualify_time FROM ".$table_threads_qualify." WHERE user_id=".$user_id." and thread_id=".$thread_id.";";
		$rs=api_sql_query($sql,__FILE__,__LINE__);
		$row=Database::fetch_array($rs);
	
		//insert thread_historical
		$sql1="INSERT INTO $table_threads_qualify_log (user_id," .
				"thread_id,qualify,qualify_user_id,qualify_time,session_id)" .
				"VALUES('".$user_id."','".$thread_id."',".(float)$row[0]."," .
				"'".$qualify_user_id."','".$row[1]."','')";
		api_sql_query($sql1,__FILE__,__LINE__);
				
		//update
 		$sql2="UPDATE ".$table_threads_qualify." SET qualify=".$current_qualify.",qualify_time='".$current_date."' WHERE user_id=".$user_id." and thread_id=".$thread_id.";";
		api_sql_query($sql2,__FILE__,__LINE__);
	}
}
/**
* 
*  This function show current thread qualify .
* @param integer contains the information the current thread id
* @param integer contains the information the current session id
* @return integer
* @author Isaac Flores <isaac.flores@dokeos.com>, U.N.A.S University
* @version December 2008, dokeos  1.8.6
*/
function current_qualify_of_thread($thread_id,$session_id) {

	$table_threads_qualify = Database::get_course_table(TABLE_FORUM_THREAD_QUALIFY,'');
	$res=api_sql_query('SELECT qualify FROM '.$table_threads_qualify.' WHERE thread_id='.$thread_id.' AND session_id='.$session_id);
	$row=Database::fetch_array($res,'ASSOC');
	return $row['qualify'];
}
/**
* This function stores a reply in the forum_post table.
* It also updates the forum_threads table (thread_replies +1 , thread_last_post, thread_date)
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function store_reply($values) {
	global $table_threads;
	global $table_posts;
	global $forum_table_attachment;
	global $_user;
	global $_course;
	global $current_forum;
	global $origin;

	$post_date=date('Y-m-d H:i:s');
	if ($current_forum['approval_direct_post']=='1' AND !api_is_allowed_to_edit()) {
		$visible=0; // the post is not approved yet.
	} else {
		$visible=1;
	}
	
	$upload_ok=1;
	$has_attachment=false;	
	if (!empty($_FILES['user_upload']['name'])) {
		$upload_ok = process_uploaded_file($_FILES['user_upload']);
		$has_attachment=true;
	}
	
	if ($upload_ok) {
		// We first store an entry in the forum_post table
		$sql="INSERT INTO $table_posts (post_title, post_text, thread_id, forum_id, poster_id, post_date, post_notification, post_parent_id, visible)
				VALUES ('".Database::escape_string($values['post_title'])."',
						'".Database::escape_string(isset($values['post_text']) ? $values['post_text'] : null)."',
						'".Database::escape_string($values['thread_id'])."',
						'".Database::escape_string($values['forum_id'])."',
						'".Database::escape_string($_user['user_id'])."',
						'".Database::escape_string($post_date)."',
						'".Database::escape_string(isset($values['post_notification'])?$values['post_notification']:null)."',
						'".Database::escape_string(isset($values['post_parent_id'])?$values['post_parent_id']:null)."',
						'".Database::escape_string($visible)."')";
		$result=api_sql_query($sql, __LINE__, __FILE__);
		$new_post_id=Database::insert_id();
		$values['new_post_id']=$new_post_id;
		
		$message=get_lang('ReplyAdded');
				
		if ($has_attachment) {			
			$courseDir   = $_course['path'].'/upload/forum';
			$sys_course_path = api_get_path(SYS_COURSE_PATH);		
			$updir = $sys_course_path.$courseDir;
						
			// Try to add an extension to the file if it hasn't one
			$new_file_name = add_ext_on_mime(stripslashes($_FILES['user_upload']['name']), $_FILES['user_upload']['type']);	
		
			// user's file name
			$file_name =$_FILES['user_upload']['name'];
						
			if (!filter_extension($new_file_name)) {
				Display :: display_error_message(get_lang('UplUnableToSaveFileFilteredExtension'));				
			} else {
				$new_file_name = uniqid('');						
				$new_path=$updir.'/'.$new_file_name;
				$result= @move_uploaded_file($_FILES['user_upload']['tmp_name'], $new_path);
				$comment=$values['file_comment'];				
								
				// Storing the attachments if any
				if ($result) {					
					$sql='INSERT INTO '.$forum_table_attachment.'(filename,comment, path, post_id,size) '.
						 "VALUES ( '".Database::escape_string($file_name)."', '".Database::escape_string($comment)."', '".Database::escape_string($new_file_name)."' , '".$new_post_id."', '".$_FILES['user_upload']['size']."' )";						
					$result=api_sql_query($sql, __LINE__, __FILE__);					
					$message.=' / '.get_lang('FileUploadSucces');
					$last_id=Database::insert_id();
					
					api_item_property_update($_course, TOOL_FORUM_ATTACH, $last_id ,'ForumAttachmentAdded', api_get_user_id());							
				}			
			}			 
		}
	
		// update the thread
		update_thread($values['thread_id'], $new_post_id,$post_date);
	
		// update the forum
		api_item_property_update($_course, TOOL_FORUM, $values['forum_id'],"NewMessageInForum", api_get_user_id());
		
		

		if ($current_forum['approval_direct_post']=='1' AND !api_is_allowed_to_edit()) {
			$message.='<br />'.get_lang('MessageHasToBeApproved').'<br />';
		}
		
		$message.='<br />'.get_lang('ReturnTo').' <a href="viewforum.php?'.api_get_cidreq().'&forum='.$values['forum_id'].'&origin='.$origin.'">'.get_lang('Forum').'</a><br />';
		$message.=get_lang('ReturnTo').' <a href="viewthread.php?'.api_get_cidreq().'&forum='.$values['forum_id'].'&amp;thread='.$values['thread_id'].'&origin='.$origin.'">'.get_lang('Message').'</a>';
	
		// setting the notification correctly
		$my_post_notification=isset($values['post_notification']) ? $values['post_notification'] :null;
		if ($my_post_notification == 1) {
			set_notification('thread',$values['thread_id'], true);
		}
		
		send_notification_mails($values['thread_id'], $values);
	
		session_unregister('formelements');
		session_unregister('origin');
		session_unregister('breadcrumbs');
		session_unregister('addedresource');
		session_unregister('addedresourceid');
			
		Display :: display_confirmation_message($message,false);
		
	} else {
		Display::display_error_message(get_lang('UplNoFileUploaded')." ". get_lang('UplSelectFileFirst'));
	}
	
}


/**
* This function displays the form that is used to edit a post. This can be a new thread or a reply.
* @param array contains all the information about the current post
* @param array contains all the information about the current thread
* @param array contains all info about the current forum (to check if attachments are allowed)
* @param array contains the default values to fill the form
* @return void
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function show_edit_post_form($current_post, $current_thread, $current_forum, $form_values='',$id_attach=0) {
	global $forum_setting;
	global $_user;
	global $origin;

	// initiate the object
	$form = new FormValidator('edit_post', 'post', api_get_self().'?forum='.Security::remove_XSS($_GET['forum']).'&origin='.$origin.'&thread='.Security::remove_XSS($_GET['thread']).'&post='.Security::remove_XSS($_GET['post']));

	// settting the form elements
	$form->addElement('hidden', 'post_id', $current_post['post_id']);
	$form->addElement('hidden', 'thread_id', $current_thread['thread_id']);
	$form->addElement('hidden', 'id_attach', $id_attach);
	if ($current_post['post_parent_id']==0) {
		$form->addElement('hidden', 'is_first_post_of_thread', '1');
	}
	$form->addElement('text', 'post_title', get_lang('Title'),'class="input_titles"');
	$form->addElement('html_editor', 'post_text', get_lang('Text'));
	
	$form->addElement('static','Group','','<a href="javascript://" onclick="return advanced_parameters()"><span id="img_plus_and_minus"><img src="../img/nolines_plus.gif" alt="" />'.get_lang('AdvancedParameters').'</span></a>');
	$form->addElement('html','<div id="id_qualify" style="display:none">');
	
	if (!isset($_GET['edit'])) {				
		$form->addElement('static','Group','<strong>'.get_lang('AlterQualifyThread').'</strong>');
		$form->addElement('text', 'numeric_calification', get_lang('QualifyNumeric'),'value="'.$current_thread['thread_qualify_max'].'" Style="width:40px"');
		$form->addElement('checkbox', 'thread_qualify_gradebook', '', get_lang('QualifyThreadGradebook'),'onclick="javascript:if(this.checked==true){document.getElementById(\'options_field\').style.display = \'block\';}else{document.getElementById(\'options_field\').style.display = \'none\';}"');		
		$defaults['thread_qualify_gradebook']=is_resource_in_course_gradebook(api_get_course_id(),5,$_GET['thread'],api_get_session_id());
				
		if (!empty($defaults['thread_qualify_gradebook'])) {
		$form -> addElement('html','<div id="options_field" style="display:block">');
		} else {
			$form -> addElement('html','<div id="options_field" style="display:none">');
		}						
		$form->addElement('text', 'calification_notebook_title', get_lang('TitleColumnGradebook'),'value="'.$current_thread['thread_title_qualify'].'"');
		$form->addElement('text', 'weight_calification', get_lang('QualifyWeight'),'value="'.$current_thread['thread_weight'].'" Style="width:40px"');		
		$form->addElement('html','</div>');				
		//add gradebook
	}
	
	if ($forum_setting['allow_post_notificiation']) {
		$form->addElement('checkbox', 'post_notification', '', get_lang('NotifyByEmail').' ('.$current_post['email'].')');
	}
	if ($forum_setting['allow_sticky'] and api_is_allowed_to_edit() and $current_post['post_parent_id']==0) { // the sticky checkbox only appears when it is the first post of a thread
		$form->addElement('checkbox', 'thread_sticky', '', get_lang('StickyPost'));
		if ( $current_thread['thread_sticky']==1 ) {
			$defaults['thread_sticky']=true;
		}
	}
		
	$attachment_list=get_attachment($current_post['post_id']);
	$message=get_lang('AddAnAttachment');
	if (!empty($attachment_list)) {
		$message=get_lang('EditAnAttachment');
		$form->addElement('static','Group','','<br />'.Display::return_icon('attachment.gif',get_lang('Attachment')).'&nbsp;'.$attachment_list['filename'].(!empty($attachment_list['comment'])?'('.$attachment_list['comment'].')':''));
		$form->addElement('checkbox', 'remove_attach', null, get_lang('DeleteAttachmentFile'));
	}
	// user upload
	$form->addElement('html','<br /><b><div class="row"><div class="label">'.$message.'</div></div></b><br /><br />');
	$form->addElement('file','user_upload',get_lang('FileName'),'');		
	$form->addElement('textarea','file_comment',get_lang('FileComment'),array ('rows' => 4, 'cols' => 34));		
	$form->addElement('html','</div><br /><br />');
	if ($current_forum['allow_attachments']=='1' OR api_is_allowed_to_edit()) {
		if (empty($form_values) AND !isset($_POST['SubmitPost'])) {
			//edit_added_resources('forum_post',$current_post['post_id']);
		}
		//$form->add_resource_button();
		$values = $form->exportValues();
	}

	$form->addElement('style_submit_button', 'SubmitPost', get_lang('ModifyThread'), 'class="save"');
	global $charset;
	// setting the default values for the form elements
	$defaults['post_title']=prepare4display(html_entity_decode($current_post['post_title'],ENT_QUOTES,$charset));
	$defaults['post_text']=prepare4display($current_post['post_text']);
	if ( $current_post['post_notification']==1 ) {
		$defaults['post_notification']=true;
	}

	if (!empty($form_values)) {
		$defaults['post_title']=Security::remove_XSS($form_values['post_title']);
		$defaults['post_text']=Security::remove_XSS($form_values['post_text']);
		$defaults['post_notification']=Security::remove_XSS($form_values['post_notification']);
		$defaults['thread_sticky']=Security::remove_XSS($form_values['thread_sticky']);
	}

	$form->setDefaults($defaults);

	// the course admin can make a thread sticky (=appears with special icon and always on top)

	$form->addRule('post_title', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');

	// The validation or display
	if( $form->validate() ) {
	   $values = $form->exportValues();
	   if($values['thread_qualify_gradebook']=='1' && empty($values['weight_calification'])){
				Display::display_error_message(get_lang('YouMustAssignWeightOfQualification').'&nbsp;<a href="javascript:window.back()">'.get_lang('Back').'</a>',false);
	   			return false;	   		   						   				
	   }	
	   return $values;
	} else {
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
function store_edit_post($values) {
	global $table_threads;
	global $table_posts;
	global $origin;
	// first we check if the change affects the thread and if so we commit the changes (sticky and post_title=thread_title are relevant)
	//if (array_key_exists('is_first_post_of_thread',$values)  AND $values['is_first_post_of_thread']=='1') {
		$sql="UPDATE $table_threads SET thread_title='".Database::escape_string($values['post_title'])."',
					thread_sticky='".Database::escape_string(isset($values['thread_sticky']) ? $values['thread_sticky'] : null)."'," .
					"thread_title_qualify='".Database::escape_string($values['calification_notebook_title'])."'," .
					"thread_qualify_max='".Database::escape_string($values['numeric_calification'])."',".
					"thread_weight='".Database::escape_string($values['weight_calification'])."'".
					" WHERE thread_id='".Database::escape_string($values['thread_id'])."'";
		
		api_sql_query($sql,__FILE__, __LINE__);
	//}

	// update the post_title and the post_text
	$sql="UPDATE $table_posts SET post_title='".Database::escape_string($values['post_title'])."',
				post_text='".Database::escape_string($values['post_text'])."',
				post_notification='".Database::escape_string(isset($values['post_notification'])?$values['post_notification']:null)."'
				WHERE post_id='".Database::escape_string($values['post_id'])."'";
				//error_log($sql);
	api_sql_query($sql,__FILE__, __LINE__);
	
	if (!empty($values['remove_attach'])) {
		delete_attachment($values['post_id']);
	}
	
	if (empty($values['id_attach'])) {
		add_forum_attachment_file($values['file_comment'],$values['post_id']);
	} else {
		edit_forum_attachment_file($values['file_comment'],$values['post_id'],$values['id_attach']);
	}

    if (api_is_course_admin()==true) {
        $ccode = api_get_course_id();
        $sid = api_get_session_id();
    	$link_id = is_resource_in_course_gradebook($ccode,5,$values['thread_id'],$sid);
    	$thread_qualify_gradebook=isset($values['thread_qualify_gradebook']) ? $values['thread_qualify_gradebook'] : null;
        if ($thread_qualify_gradebook!=1) {
            if ($link_id !== false) {
            	remove_resource_from_course_gradebook($link_id);
            }
    	} else {
            if ($link_id === false && !$_GET['thread']) {
            	//$date_in_gradebook=date('Y-m-d H:i:s');
            	$date_in_gradebook=null;
            	$weigthqualify=$values['weight_calification'];
               	add_resource_to_course_gradebook($ccode,5,$values['thread_id'],Database::escape_string($values['calification_notebook_title']),$weigthqualify,$values['numeric_calification'],null,$date_in_gradebook,0,$sid);
            }
    	}
    }		
	// Storing the attachments if any
	//update_added_resources('forum_post',$values['post_id']);

	$message=get_lang('EditPostStored').'<br />';
	$message.=get_lang('ReturnTo').' <a href="viewforum.php?'.api_get_cidreq().'&forum='.Security::remove_XSS($_GET['forum']).'&amp;gidReq='.$_SESSION['toolgroup'].'&amp;origin='.$origin.'">'.get_lang('Forum').'</a><br />';
	$message.=get_lang('ReturnTo').' <a href="viewthread.php?'.api_get_cidreq().'&forum='.Security::remove_XSS($_GET['forum']).'&amp;gidReq='.$_SESSION['toolgroup'].'&amp;origin='.$origin.'&amp;thread='.$values['thread_id'].'&amp;post='.Security::remove_XSS($_GET['post']).'">'.get_lang('Message').'</a>';

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
function display_user_link($user_id, $name, $origin='') {
	if ($user_id<>0) {
		return '<a href="../user/userInfo.php?uInfo='.$user_id.'" '. (!empty($origin)? 'target="_top"': '') .'>'.$name.'</a>';
	} else {
		return $name.' ('.get_lang('Anonymous').')';
	}
}

/**
* This function displays the user image from the profile, with a link to the user's details.
* @param 	int 	User's database ID
* @param 	str 	User's name
* @return 	string 	An HTML with the anchor and the image of the user
* @author Julio Montoya <julio.montoya@dokeos.com>
*/

function display_user_image($user_id,$name, $origin='') {
	$link='<a href="../user/userInfo.php?uInfo='.$user_id.'" '. (!empty($origin)? 'target="_top"': '') .'>';
	$attrb=array();
		
	if ($user_id<>0) {		
		$image_path = UserManager::get_user_picture_path_by_id($user_id,'web',false, true);
		$image_repository = $image_path['dir'];
		$existing_image = $image_path['file'];
		return 	$link.'<img src="'.$image_repository.$existing_image.'" alt="'.$name.'"  title="'.$name.'"  /></a>';			
			
	} else {
		return $link.'<img src="'.api_get_path(WEB_CODE_PATH)."img/unknown.jpg".'" alt="'.$name.'"  title="'.$name.'"  /></a>';
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
function increase_thread_view($thread_id) {
	global $table_threads;

	$sql="UPDATE $table_threads SET thread_views=thread_views+1 WHERE thread_id='".Database::escape_string($thread_id)."'"; // this needs to be cleaned first
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
function update_thread($thread_id, $last_post_id,$post_date) {
	global $table_threads;

	$sql="UPDATE $table_threads SET thread_replies=thread_replies+1,
			thread_last_post='".Database::escape_string($last_post_id)."',
			thread_date='".Database::escape_string($post_date)."' WHERE thread_id='".Database::escape_string($thread_id)."'"; // this needs to be cleaned first
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
function forum_not_allowed_here() {
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
function get_whats_new() {
	global $_user;
	global $_course;
	global $table_posts;

	// note this has later to be replaced by the tool constant. But temporarily bb_forum is used since this is the only thing that is in the tracking currently.
	//$tool=TOOL_FORUM;
	$tool=TOOL_FORUM; //
	// to do: remove this. For testing purposes only
	//session_unregister('last_forum_access');
	//session_unregister('whatsnew_post_info');

	if (!$_SESSION['last_forum_access']) {
		$tracking_last_tool_access=Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LASTACCESS);
		$sql="SELECT * FROM ".$tracking_last_tool_access." WHERE access_user_id='".Database::escape_string($_user['user_id'])."' AND access_cours_code='".Database::escape_string($_course['sysCode'])."' AND access_tool='".Database::escape_string($tool)."'";
		$result=api_sql_query($sql,__FILE__,__LINE__);
		$row=Database::fetch_array($result);
		$_SESSION['last_forum_access']=$row['access_date'];
	}

	if (!$_SESSION['whatsnew_post_info']) {
		if ($_SESSION['last_forum_access']<>'') {
			$whatsnew_post_info = array();
			$sql="SELECT * FROM".$table_posts."WHERE post_date>'".Database::escape_string($_SESSION['last_forum_access'])."'"; // note: check the performance of this query.
			$result=api_sql_query($sql,__FILE__,__LINE__);
			while ($row=Database::fetch_array($result)) {
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
function get_post_topics_of_forum($forum_id) {
	global $table_posts;
	global $table_threads;
	global $table_item_property;

	$sql="SELECT count(*) as number_of_posts FROM $table_posts WHERE forum_id='".$forum_id."'";
	if (api_is_allowed_to_edit()) {
		$sql="SELECT count(*) as number_of_posts
				FROM $table_posts posts, $table_threads threads, $table_item_property item_property
				WHERE posts.forum_id='".Database::escape_string($forum_id)."'
				AND posts.thread_id=threads.thread_id
				AND item_property.ref=threads.thread_id
				AND item_property.visibility<>2
				AND item_property.tool='".TOOL_FORUM_THREAD."'
				";
	} else {
		$sql="SELECT count(*) as number_of_posts
				FROM $table_posts posts, $table_threads threads, $table_item_property item_property
				WHERE posts.forum_id='".Database::escape_string($forum_id)."'
				AND posts.thread_id=threads.thread_id
				AND item_property.ref=threads.thread_id
				AND item_property.visibility=1
				AND posts.visible=1
				AND item_property.tool='".TOOL_FORUM_THREAD."'
				";
	}
	$result=api_sql_query($sql, __FILE__, __LINE__);
	$row=Database::fetch_array($result);
	$number_of_posts=$row['number_of_posts'];

	// we could loop through the result array and count the number of different group_ids but I have chosen to use a second sql statement
	if (api_is_allowed_to_edit()) {
		$sql="SELECT count(*) as number_of_topics
				FROM $table_threads threads, $table_item_property item_property
				WHERE threads.forum_id='".Database::escape_string($forum_id)."'
				AND item_property.ref=threads.thread_id
				AND item_property.visibility<>2
				AND item_property.tool='".TOOL_FORUM_THREAD."'
				";
	} else {
		$sql="SELECT count(*) as number_of_topics
				FROM $table_threads threads, $table_item_property item_property
				WHERE threads.forum_id='".Database::escape_string($forum_id)."'
				AND item_property.ref=threads.thread_id
				AND item_property.visibility=1
				AND item_property.tool='".TOOL_FORUM_THREAD."'
				";
	}
	$result=api_sql_query($sql, __FILE__, __LINE__);
	$row=Database::fetch_array($result);
	$number_of_topics=$row['number_of_topics'];
	if ($number_of_topics=='') {
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
function approve_post($post_id, $action) {
	global $table_posts;

	if ($action=='invisible') {
		$visibility_value=0;
	}
	if ($action=='visible') {
		$visibility_value=1;
		handle_mail_cue('post',$post_id);
	}

	$sql="UPDATE $table_posts SET visible='".Database::escape_string($visibility_value)."' WHERE post_id='".Database::escape_string($post_id)."'";
	$return=api_sql_query($sql, __FILE__, __LINE__);
	if ($return) {
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
function get_unaproved_messages($forum_id) {
	global $table_posts;
	
	$return_array=array();
	$sql="SELECT DISTINCT thread_id FROM $table_posts WHERE forum_id='".Database::escape_string($forum_id)."' AND visible='0'";
	$result=api_sql_query($sql, __FILE__, __LINE__);
	while($row=Database::fetch_array($result)) {
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
function send_notification_mails($thread_id, $reply_info) {
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
	if($current_thread['visibility']=='1' AND $current_forum['visibility']=='1' AND $current_forum_category['visibility']=='1' AND $current_forum['approval_direct_post']!='1') {
		$send_mails=true;
	} else {
		$send_mails=false;
	}

	// the forum category, the forum, the thread and the reply are visible to the user
	if ($send_mails==true) {
		send_notifications($current_thread['forum_id'],$thread_id);
		/*
		$sql="SELECT DISTINCT user.firstname, user.lastname, user.email, user.user_id
				FROM $table_posts post, $table_user user
				WHERE post.thread_id='".Database::escape_string($thread_id)."'
				AND post.post_notification='1'
				AND post.poster_id=user.user_id";
		$result=api_sql_query($sql, __LINE__, __FILE__);
		while ($row=Database::fetch_array($result))
		{
			send_mail($row, $current_thread);
		}
		*/
	} else {
		/*
		$sql="SELECT * FROM $table_posts WHERE thread_id='".Database::escape_string($thread_id)."' AND post_notification='1'";
		$result=api_sql_query($sql, __LINE__, __FILE__);
		*/
		$table_notification = Database::get_course_table('forum_notification');
		$sql = "SELECT * FROM $table_notification WHERE forum_id = '".Database::escape_string($current_forum['forum_id'])."' OR thread_id = '".Database::escape_string($thread_id)."'";
		$result=api_sql_query($sql, __FILE__, __LINE__);
		while ($row=Database::fetch_array($result)) {
			$sql_mailcue="INSERT INTO $table_mailcue (thread_id, post_id) VALUES ('".Database::escape_string($thread_id)."', '".Database::escape_string($reply_info['new_post_id'])."')";
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
function handle_mail_cue($content, $id) {
	global $table_mailcue;
	global $table_forums;
	global $table_threads;
	global $table_posts;
	global $table_users;

	// if the post is made visible we only have to send mails to the people who indicated that they wanted to be informed for that thread.
	if ($content=='post') {
		// getting the information about the post (need the thread_id)
		$post_info=get_post_information($id);

		// sending the mail to all the users that wanted to be informed for replies on this thread.
		$sql="SELECT users.firstname, users.lastname, users.user_id, users.email FROM $table_mailcue mailcue, $table_posts posts, $table_users users
				WHERE posts.thread_id='".Database::escape_string($post_info['thread_id'])."'
				AND posts.post_notification='1'
				AND mailcue.thread_id='".Database::escape_string($post_info['thread_id'])."'
				AND users.user_id=posts.poster_id
				GROUP BY users.email";
		$result=api_sql_query($sql, __FILE__, __LINE__);
		while ($row=Database::fetch_array($result)) {
			send_mail($row, get_thread_information($post_info['thread_id']));
		}

		// deleting the relevant entries from the mailcue
		$sql_delete_mailcue="DELETE FROM $table_mailcue WHERE post_id='".Database::escape_string($id)."' AND thread_id='".Database::escape_string($post_info['thread_id'])."'";
		//$result=api_sql_query($sql_delete_mailcue, __LINE__, __FILE__);
	} elseif ($content=='thread') {
		// sending the mail to all the users that wanted to be informed for replies on this thread.
		$sql="SELECT users.firstname, users.lastname, users.user_id, users.email FROM $table_mailcue mailcue, $table_posts posts, $table_users users
				WHERE posts.thread_id='".Database::escape_string($id)."'
				AND posts.post_notification='1'
				AND mailcue.thread_id='".Database::escape_string($id)."'
				AND users.user_id=posts.poster_id
				GROUP BY users.email";
		$result=api_sql_query($sql,__FILE__, __LINE__);
		while ($row=Database::fetch_array($result)) {
			send_mail($row, get_thread_information($id));
		}

		// deleting the relevant entries from the mailcue
		$sql_delete_mailcue="DELETE FROM $table_mailcue WHERE thread_id='".Database::escape_string($id)."'";
		$result=api_sql_query($sql_delete_mailcue, __FILE__, __LINE__);
	} elseif ($content=='forum') {
		$sql="SELECT * FROM $table_threads WHERE forum_id='".Database::escape_string($id)."'";
		$result=api_sql_query($sql, __FILE__, __LINE__);
		while ($row=Database::fetch_array($result)) {
			handle_mail_cue('thread',$row['thread_id']);
		}
	} elseif ($content=='forum_category') {
		$sql="SELECT * FROM $table_forums WHERE forum_category ='".Database::escape_string($id)."'";
		$result=api_sql_query($sql, __FILE__, __LINE__);
		while ($row=Database::fetch_array($result)) {
			handle_mail_cue('forum',$row['forum_id']);
		}
	} else {
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
function send_mail($user_info=array(), $thread_information=array()) {
	global $_course;
	global $_user;

	$email_subject = get_lang('NewForumPost')." - ".$_course['official_code'];

	if (isset($thread_information) and is_array($thread_information)) {
		$thread_link= api_get_path('WEB_CODE_PATH').'forum/viewthread.php?'.api_get_cidreq().'&forum='.$thread_information['forum_id'].'&thread='.$thread_information['thread_id'];
	}
	$email_body= $user_info['firstname']." ".$user_info['lastname']."\n\r";
	$email_body .= '['.$_course['official_code'].'] - ['.$_course['name']."]<br>\n";
	$email_body .= get_lang('NewForumPost')."\n";
	$email_body .= get_lang('YouWantedToStayInformed')."<br><br>\n";
	$email_body .= get_lang('ThreadCanBeFoundHere')." : <a href=\"".$thread_link."\">".$thread_link."</a>\n";

	//set the charset and use it for the encoding of the email - small fix, not really clean (should check the content encoding origin first)
	//here we use the encoding used for the webpage where the text is encoded (ISO-8859-1 in this case)
	if(empty($charset)) {
		$charset='ISO-8859-1';
	}

	if ($user_info['user_id']<>$_user['user_id']) {
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
function move_thread_form() {
	global $origin;

	// initiate the object
	$form = new FormValidator('movepost', 'post', api_get_self().'?forum='.Security::remove_XSS($_GET['forum']).'&thread='.Security::remove_XSS($_GET['thread']).'&action='.Security::remove_XSS($_GET['action']).'&origin='.$origin);
	// the header for the form
	$form->addElement('header', '', get_lang('MoveThread'));
	// invisible form: the thread_id
	$form->addElement('hidden', 'thread_id', strval(intval($_GET['thread']))); // note: this has to be cleaned first

	// the fora
	$forum_categories=get_forum_categories();
	$forums=get_forums();

	$htmlcontent="\n<tr>\n<td></td>\n<td>\n<SELECT NAME='forum'>\n";
	foreach ($forum_categories as $key=>$category) {
		$htmlcontent.="\t<OPTGROUP LABEL=\"".$category['cat_title']."\">\n";
		foreach ($forums as $key=>$forum) {
			if ($forum['forum_category']==$category['cat_id']) {
				$htmlcontent.="\t\t<OPTION VALUE='".$forum['forum_id']."'>".$forum['forum_title']."</OPTION>\n";
			}
		}
		$htmlcontent.="\t</OPTGROUP>\n";
	}
	$htmlcontent.="</SELECT>\n</td></tr>";
	$form->addElement('html',$htmlcontent);

	// The OK button
	$form->addElement('submit', 'SubmitForum',get_lang('Ok'));

	// The validation or display
	if( $form->validate()) {
	   $values = $form->exportValues();
	   if (isset($_POST['forum'])) {
	   		store_move_thread($values);
	   }

	} else {
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
function move_post_form() {
	global $origin;
	// initiate the object
	$form = new FormValidator('movepost', 'post', api_get_self().'?forum='.Security::remove_XSS($_GET['forum']).'&thread='.Security::remove_XSS($_GET['thread']).'&origin='.$origin.'&post='.Security::remove_XSS($_GET['post']).'&action='.Security::remove_XSS($_GET['action']).'&post='.Security::remove_XSS($_GET['post']));
	// the header for the form
	$form->addElement('header', '', get_lang('MovePost'));

	// invisible form: the post_id
	$form->addElement('hidden', 'post_id', strval(intval($_GET['post']))); // note: this has to be cleaned first

	// dropdown list: Threads of this forum
	$threads=get_threads(strval(intval($_GET['forum']))); // note: this has to be cleaned
	//my_print_r($threads);
	$threads_list[0]=get_lang('ANewThread');
	foreach ($threads as $key=>$value) {
		$threads_list[$value['thread_id']]=$value['thread_title'];
	}
	$form->addElement('select', 'thread', get_lang('MoveToThread'), $threads_list);


	// The OK button
	$form->addElement('submit', '',get_lang('Ok'));

	// setting the rules
	$form->addRule('thread', get_lang('ThisFieldIsRequired'), 'required');


	// The validation or display
	if( $form->validate() ) {
	   $values = $form->exportValues();
	   store_move_post($values);
	} else {
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
function store_move_post($values) {
	global $table_posts;
	global $table_threads;
	global $table_forums;
	global $_course;

	if ($values['thread']=='0') {
		$current_post=get_post_information($values['post_id']);

		// storing a new thread
		$sql="INSERT INTO $table_threads (thread_title, forum_id, thread_poster_id, thread_poster_name, thread_last_post, thread_date)
			VALUES (
				'".Database::escape_string($current_post['post_title'])."',
				'".Database::escape_string($current_post['forum_id'])."',
				'".Database::escape_string($current_post['poster_id'])."',
				'".Database::escape_string($current_post['poster_name'])."',
				'".Database::escape_string($values['post_id'])."',
				'".Database::escape_string($current_post['post_date'])."'
				)";
		$result=api_sql_query($sql, __FILE__, __LINE__);
		$new_thread_id=Database::get_last_insert_id();
		api_item_property_update($_course, TOOL_FORUM_THREAD, $new_thread_id,"visible", $current_post['poster_id']);

		// moving the post to the newly created thread
		$sql="UPDATE $table_posts SET thread_id='".Database::escape_string($new_thread_id)."', post_parent_id='0' WHERE post_id='".Database::escape_string($values['post_id'])."'";
		$result=api_sql_query($sql,__FILE__, __LINE__);
		//echo $sql.'<br />';

		// resetting the parent_id of the thread to 0 for all those who had this moved post as parent
		$sql="UPDATE $table_posts SET post_parent_id='0' WHERE post_parent_id='".Database::escape_string($values['post_id'])."'";
		$result=api_sql_query($sql, __FILE__, __LINE__);
		//echo $sql.'<br />';

		// updating updating the number of threads in the forum
		$sql="UPDATE $table_forums SET forum_threads=forum_threads+1 WHERE forum_id='".Database::escape_string($current_post['forum_id'])."'";
		$result=api_sql_query($sql, __FILE__, __LINE__);
		//echo $sql.'<br />';

		// resetting the last post of the old thread and decreasing the number of replies and the thread
		$sql="SELECT * FROM $table_posts WHERE thread_id='".Database::escape_string($current_post['thread_id'])."' ORDER BY post_id DESC";
		//echo $sql.'<br />';
		$result=api_sql_query($sql, __FILE__, __LINE__);
		$row=Database::fetch_array($result);
		//my_print_r($row);
		$sql="UPDATE $table_threads SET thread_last_post='".$row['post_id']."', thread_replies=thread_replies-1 WHERE thread_id='".Database::escape_string($current_post['thread_id'])."'";
		$result=api_sql_query($sql, __FILE__, __LINE__);
		//echo $sql.'<br />';
	} else {
		// moving to the chosen thread
		$sql="UPDATE $table_posts SET thread_id='".Database::escape_string($_POST['thread'])."', post_parent_id='0' WHERE post_id='".Database::escape_string($values['post_id'])."'";
		$result=api_sql_query($sql, __FILE__, __LINE__);

		// resetting the parent_id of the thread to 0 for all those who had this moved post as parent
		$sql="UPDATE $table_posts SET post_parent_id='0' WHERE post_parent_id='".Database::escape_string($values['post_id'])."'";
		$result=api_sql_query($sql, __FILE__, __LINE__);
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
function store_move_thread($values) {
	global $table_posts;
	global $table_threads;
	global $table_forums;
	global $_course;

	// change the thread table: setting the forum_id to the new forum
	$sql="UPDATE $table_threads SET forum_id='".Database::escape_string($_POST['forum'])."' WHERE thread_id='".Database::escape_string($_POST['thread_id'])."'";
	$result=api_sql_query($sql, __FILE__, __LINE__);


	// changing all the posts of the thread: setting the forum_id to the new forum
	$sql="UPDATE $table_posts SET forum_id='".Database::escape_string($_POST['forum'])."' WHERE thread_id='".Database::escape_string($_POST['thread_id'])."'";
	$result=api_sql_query($sql, __FILE__, __LINE__);

	return get_lang('ThreadMoved');
}


/**
* Prepares a string or an array of strings for display by stripping slashes
* @param mixed	String or array of strings
* @return mixed String or array of strings
*
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version february 2006, dokeos 1.8
*/
function prepare4display($input='') {
	$highlightcolors = array('yellow', '#33CC33','#3399CC', '#9999FF', '#33CC33');
	if (!is_array($input)) {
		if (!empty($_GET['search'])) {
			if (strstr($_GET['search'],'+')) {
				$search_terms = explode('+',$_GET['search']);
			} else  {
				$search_terms[] = trim($_GET['search']);
			}
			$counter = 0;
			foreach ($search_terms as $key=>$search_term) {
				$input = str_replace(trim(html_entity_decode($search_term)),'<span style="background-color: '.$highlightcolors[$counter].'">'.trim(html_entity_decode($search_term)).'</span>',$input);
				$counter++;
			}
		}
		return html_entity_decode(stripslashes($input));
	} else {
		/*foreach ($input as $key=>$value)
		{
			$returnarray[$key]=stripslashes($value);
		}*/
		$returnarray=array_walk($input, 'html_entity_decode');
		$returnarray=array_walk($input, 'stripslashes');
		return $returnarray;
	}
}

/**
 * Display the search form for the forum and display the search results
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version march 2008, dokeos 1.8.5
 */
function forum_search() {
	global $origin;
	// initiate the object
	$form = new FormValidator('forumsearch','post','forumsearch.php?origin='.$origin.'');

	// settting the form elements
	$form->addElement('header', '', get_lang('ForumSearch'));
	$form->addElement('text', 'search_term', get_lang('SearchTerm'),'class="input_titles"');
	$form->addElement('static', 'search_information', '', get_lang('ForumSearchInformation')/*, $dissertation[$_GET['opleidingsonderdeelcode']]['code']*/);
	$form->addElement('style_submit_button', 'SubmitForumSearch', get_lang('Search'), 'class="search"');

	// setting the rules
	$form->addRule('search_term', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');
	$form->addRule('search_term', get_lang('TooShort'),'minlength',3);

	// The validation or display
	if( $form->validate() ) {
	   $values = $form->exportValues();
	   $form->setDefaults($values);
	   $form->display();
	   
	   // display the search results
	   display_forum_search_results($values['search_term']);
	} else {
		$form->display();
	}	
}
/**
 * Display the search results
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version march 2008, dokeos 1.8.5
 */
function display_forum_search_results($search_term) {
	global $table_categories, $table_forums, $table_threads, $table_posts; 
	global $origin;
	
	// defining the search strings as an array
	if (strstr($search_term,'+')) {
		$search_terms = explode('+',$search_term);
	} else  {
		$search_terms[] = $search_term;
	}
	
	// search restriction
	foreach ($search_terms as $key => $value) {
		$search_restriction[] = "(posts.post_title LIKE '%".Database::escape_string(trim($value))."%' 
									OR posts.post_text LIKE '%".Database::escape_string(trim($value))."%')";
	}
	
	$sql = "SELECT * FROM $table_posts posts
				WHERE ".implode(' AND ',$search_restriction)."
				/*AND posts.thread_id = threads.thread_id*/
				GROUP BY posts.post_id";

	// getting all the information of the forum categories
	$forum_categories_list=get_forum_categories();
	
	// getting all the information of the forums
	$forum_list=get_forums();	

	$result = api_sql_query($sql, __FILE__, __LINE__);
	while ($row = Database::fetch_array($result,'ASSOC')) {
		$display_result = false; 
		/*
			we only show it when
			1. forum cateogory is visible
			2. forum is visible
			3. thread is visible (to do)
			4. post is visible
		*/
		if (!api_is_allowed_to_edit()) {
			if ($forum_categories_list[$row['forum_id']['forum_category']]['visibility'] == '1'  AND $forum_list[$row['forum_id']]['visibility'] == '1' AND $row['visible'] == '1') {
				$display_result = true;
			}
		} else {
			$display_result = true; 
		}
		
		if ($display_result == true) {
			$search_results_item = '<li><a href="viewforumcategory.php?forumcategory='.$forum_list[$row['forum_id']]['forum_category'].'&origin='.$origin.'&amp;search='.urlencode($search_term).'">'.$forum_categories_list[$row['forum_id']['forum_category']]['cat_title'].'</a> > ';
			$search_results_item .= '<a href="viewforum.php?forum='.$row['forum_id'].'&amp;origin='.$origin.'&amp;search='.urlencode($search_term).'">'.$forum_list[$row['forum_id']]['forum_title'].'</a> > ';
			//$search_results_item .= '<a href="">THREAD</a> > ';
			$search_results_item .= '<a href="viewthread.php?forum='.$row['forum_id'].'&amp;origin='.$origin.'&amp;thread='.$row['thread_id'].'&amp;search='.urlencode($search_term).'">'.$row['post_title'].'</a>';
			$search_results_item .= '<br />';
			if (strlen($row['post_title']) > 200 ) {
				$search_results_item .= substr(strip_tags($row['post_title']),0,200).'...';
			} else {
				$search_results_item .= $row['post_title'];
			}
			$search_results_item .= '</li>';
			$search_results[] = $search_results_item;
		}
	}
	echo '<div class="row"><div class="form_header">'.count($search_results).' '.get_lang('ForumSearchResults').'</div></div>';
	echo '<ol>';
	if($search_results)	{
		echo implode($search_results);
	}
	echo '</ol>';
}

/**
 * Return the link to the forum search page
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version April 2008, dokeos 1.8.5
 */
function search_link() {
	global $origin;
	$return = '<a href="forumsearch.php?'.api_get_cidreq().'&action=search&origin='.$origin.'"> '.Display::return_icon('search.gif', get_lang('Search')).' '.get_lang('Search').'</a>';
	if (!empty($_GET['search'])) {
		$return .= ': '.Security::remove_XSS($_GET['search']).' ';
		$url = api_get_self().'?';
		foreach ($_GET as $key=>$value) {
			if ($key<>'search') {
				$url_parameter[]=Security::remove_XSS($key).'='.Security::remove_XSS($value);
			}
		}
		$url = $url.implode('&amp;',$url_parameter);
		$return .= '<a href="'.$url.'">'.Display::return_icon('delete.gif', get_lang('RemoveSearchResults')).'</a>';
	}
	return $return;
}
/**
 * This function add a attachment file into forum
 * @param string  a comment about file
 * @param int last id from forum_post table
 *
 */
function add_forum_attachment_file($file_comment,$last_id) {

	global $_course;
	$agenda_forum_attachment = Database::get_course_table(TABLE_FORUM_ATTACHMENT);
	// Storing the attachments

    if(!empty($_FILES['user_upload']['name'])) {
		$upload_ok = process_uploaded_file($_FILES['user_upload']);
	}

	if (!empty($upload_ok)) {
			$courseDir   = $_course['path'].'/upload/forum';
			$sys_course_path = api_get_path(SYS_COURSE_PATH);
			$updir = $sys_course_path.$courseDir;

			// Try to add an extension to the file if it hasn't one
			$new_file_name = add_ext_on_mime(stripslashes($_FILES['user_upload']['name']), $_FILES['user_upload']['type']);
			// user's file name
			$file_name =$_FILES['user_upload']['name'];

			if (!filter_extension($new_file_name))  {
				Display :: display_error_message(get_lang('UplUnableToSaveFileFilteredExtension'));
			} else {
				$new_file_name = uniqid('');
				$new_path=$updir.'/'.$new_file_name;
				$result= @move_uploaded_file($_FILES['user_upload']['tmp_name'], $new_path);
				$safe_file_comment= Database::escape_string($file_comment);
				$safe_file_name = Database::escape_string($file_name);
				$safe_new_file_name = Database::escape_string($new_file_name);
				// Storing the attachments if any
				if ($result) {
					$sql="INSERT INTO $agenda_forum_attachment(filename,comment, path,post_id,size) 
						  VALUES ( '$safe_file_name', '$safe_file_comment', '$safe_new_file_name' , '$last_id', '".$_FILES['user_upload']['size']."' )";
					$result=api_sql_query($sql, __LINE__, __FILE__);
					$message.=' / '.get_lang('FileUploadSucces').'<br />';

					$last_id_file=Database::insert_id();
					api_item_property_update($_course, TOOL_FORUM_ATTACH, $last_id_file ,'ForumAttachmentAdded', api_get_user_id());

				}
			}
		}
}

/**
 * This function edit a attachment file into forum
 * @param string  a comment about file
 * @param int Post Id
 *  @param int attachment file Id
 */
function edit_forum_attachment_file($file_comment,$post_id,$id_attach) {

	global $_course;
	$table_forum_attachment = Database::get_course_table(TABLE_FORUM_ATTACHMENT);
	// Storing the attachments

    if(!empty($_FILES['user_upload']['name'])) {
		$upload_ok = process_uploaded_file($_FILES['user_upload']);
	}

	if (!empty($upload_ok)) {
			$courseDir   = $_course['path'].'/upload/forum';
			$sys_course_path = api_get_path(SYS_COURSE_PATH);
			$updir = $sys_course_path.$courseDir;

			// Try to add an extension to the file if it hasn't one
			$new_file_name = add_ext_on_mime(stripslashes($_FILES['user_upload']['name']), $_FILES['user_upload']['type']);
			// user's file name
			$file_name =$_FILES['user_upload']['name'];

			if (!filter_extension($new_file_name))  {
				Display :: display_error_message(get_lang('UplUnableToSaveFileFilteredExtension'));
			} else {
				$new_file_name = uniqid('');
				$new_path=$updir.'/'.$new_file_name;
				$result= @move_uploaded_file($_FILES['user_upload']['tmp_name'], $new_path);
				$safe_file_comment= Database::escape_string($file_comment);
				$safe_file_name = Database::escape_string($file_name);
				$safe_new_file_name = Database::escape_string($new_file_name);
				$safe_post_id = (int)$post_id;
				$safe_id_attach = (int)$id_attach;
				// Storing the attachments if any
				if ($result) {
					$sql="UPDATE $table_forum_attachment SET filename = '$safe_file_name', comment = '$safe_file_comment', path = '$safe_new_file_name', post_id = '$safe_post_id', size ='".$_FILES['user_upload']['size']."'
						   WHERE id = '$safe_id_attach'";
					$result=api_sql_query($sql, __LINE__, __FILE__);

					api_item_property_update($_course, TOOL_FORUM_ATTACH, $safe_id_attach ,'ForumAttachmentUpdated', api_get_user_id());

				}
			}
		}
}

/**
 * Show a list with all the attachments according to the post's id
 * @param the post's id 
 * @return array with the post info   
 * @author Julio Montoya Dokeos
 * @version avril 2008, dokeos 1.8.5
 */ 
 
function get_attachment($post_id) {	
	global $forum_table_attachment;
	$row=array();	
	$sql = 'SELECT id, path, filename,comment FROM '. $forum_table_attachment.' WHERE post_id ="'.$post_id.'"';
	$result=api_sql_query($sql, __FILE__, __LINE__);
	if (Database::num_rows($result)!=0) {
		$row=Database::fetch_array($result);
	}
	return $row;	
}
/**
 * Delete the all the attachments from the DB and the file according to the post's id or attach id(optional)
 * @param post id
 * @param attach id (optional)
 * @author Julio Montoya Dokeos
 * @version avril 2008, dokeos 1.8.5
 */ 

function delete_attachment($post_id,$id_attach=0) {		
	global $_course;
	$forum_table_attachment = Database::get_course_table(TABLE_FORUM_ATTACHMENT);
	
	$cond = (!empty($id_attach))?" id = ".(int)$id_attach."" : " post_id = ".(int)$post_id."";
		
	$sql="SELECT path FROM $forum_table_attachment WHERE $cond";
	$res=api_sql_query($sql,__FILE__,__LINE__);
	$row=Database::fetch_array($res);
	
	$courseDir       = $_course['path'].'/upload/forum';
	$sys_course_path = api_get_path(SYS_COURSE_PATH);		
	$updir           = $sys_course_path.$courseDir;
	$my_path         =isset($row['path']) ? $row['path'] : null;
	$file            =$updir.'/'.$my_path;		
	if (Security::check_abs_path($file,$updir) ) {			
		@unlink($file);
	}		
		
	//Delete from forum_attachment table
	$sql="DELETE FROM $forum_table_attachment WHERE $cond ";	
	
	$result=api_sql_query($sql, __LINE__, __FILE__);
	$last_id_file=Database::insert_id();
	// update item_property
	api_item_property_update($_course, TOOL_FORUM_ATTACH, $id_attach ,'ForumAttachmentDelete', api_get_user_id());
	
	if (!empty($result) && !empty($id_attach)) {	
	$message=get_lang(get_lang('AttachmentFileDeleteSuccess'));			
	Display::display_confirmation_message($message);
	}
	
	
}
/**
 * This function gets all the forum information of the all the forum of the group
 *
 * @param integer $group_id the id of the group we need the fora of (see forum.forum_of_group)
 * @return array
 *
 * @todo this is basically the same code as the get_forums function. Consider merging the two.
 */
function get_forums_of_group($group_id) {
	global $table_forums;
	global $table_threads;
	global $table_posts;
	global $table_item_property;
	global $table_users;

	//-------------- Student -----------------//
	// select all the forum information of all forums (that are visible to students)
	$sql="SELECT * FROM ".$table_forums." forum , ".$table_item_property." item_properties
				WHERE forum.forum_of_group = '".Database::escape_string($group_id)."'
				AND forum.forum_id=item_properties.ref
				AND item_properties.visibility=1
				AND item_properties.tool='".TOOL_FORUM."'
				ORDER BY forum.forum_order ASC";
	// select the number of threads of the forums (only the threads that are visible)
	$sql2="SELECT count(thread_id) AS number_of_threads, threads.forum_id FROM $table_threads threads, ".$table_item_property." item_properties
					WHERE threads.thread_id=item_properties.ref
					AND item_properties.visibility=1
					AND item_properties.tool='".TOOL_FORUM_THREAD."'
					GROUP BY threads.forum_id";
	// select the number of posts of the forum (post that are visible and that are in a thread that is visible)
	$sql3="SELECT count(post_id) AS number_of_posts, posts.forum_id FROM $table_posts posts, $table_threads threads, ".$table_item_property." item_properties
			WHERE posts.visible=1
			AND posts.thread_id=threads.thread_id
			AND threads.thread_id=item_properties.ref
			AND item_properties.visibility=1
			AND item_properties.tool='".TOOL_FORUM_THREAD."'
			GROUP BY threads.forum_id";

	//-------------- Course Admin  -----------------//
	if (is_allowed_to_edit()) {
		// select all the forum information of all forums (that are not deleted)
		$sql="SELECT * FROM ".$table_forums." forum , ".$table_item_property." item_properties
					WHERE forum.forum_of_group = '".Database::escape_string($group_id)."'
					AND forum.forum_id=item_properties.ref
					AND item_properties.visibility<>2
					AND item_properties.tool='".TOOL_FORUM."'
					ORDER BY forum_order ASC";
		//echo $sql.'<hr>';
		// select the number of threads of the forums (only the threads that are not deleted)
		$sql2="SELECT count(thread_id) AS number_of_threads, threads.forum_id FROM $table_threads threads, ".$table_item_property." item_properties
						WHERE threads.thread_id=item_properties.ref
						AND item_properties.visibility<>2
						AND item_properties.tool='".TOOL_FORUM_THREAD."'
						GROUP BY threads.forum_id";
		//echo $sql2.'<hr>';
		// select the number of posts of the forum
		$sql3="SELECT count(post_id) AS number_of_posts, forum_id FROM $table_posts GROUP BY forum_id";
		//echo $sql3.'<hr>';
	}

	// handling all the forum information
	$result=api_sql_query($sql, __FILE__, __LINE__);
	while ($row=Database::fetch_array($result,'ASSOC')) {
		$forum_list[$row['forum_id']]=$row;
	}

	// handling the threadcount information
	$result2=api_sql_query($sql2, __FILE__, __LINE__);
	while ($row2=Database::fetch_array($result2,'ASSOC')) {
		if (is_array($forum_list)) {
			if (array_key_exists($row2['forum_id'],$forum_list)) {
				$forum_list[$row2['forum_id']]['number_of_threads']=$row2['number_of_threads'];
			}
		}
	}

	// handling the postcount information
	$result3=api_sql_query($sql3, __FILE__, __LINE__);
	while ($row3=Database::fetch_array($result3,'ASSOC')) {
		if (is_array($forum_list)) {		
			if (array_key_exists($row3['forum_id'],$forum_list)) {// this is needed because sql3 takes also the deleted forums into account
				$forum_list[$row3['forum_id']]['number_of_posts']=$row3['number_of_posts'];
			}
		}
	}

	// finding the last post information (last_post_id, last_poster_id, last_post_date, last_poster_name, last_poster_lastname, last_poster_firstname)
	if (is_array($forum_list)) {
		foreach ($forum_list as $key=>$value) {
			$last_post_info_of_forum=get_last_post_information($key,is_allowed_to_edit());
			$forum_list[$key]['last_post_id']=$last_post_info_of_forum['last_post_id'];
			$forum_list[$key]['last_poster_id']=$last_post_info_of_forum['last_poster_id'];
			$forum_list[$key]['last_post_date']=$last_post_info_of_forum['last_post_date'];
			$forum_list[$key]['last_poster_name']=$last_post_info_of_forum['last_poster_name'];
			$forum_list[$key]['last_poster_lastname']=$last_post_info_of_forum['last_poster_lastname'];
			$forum_list[$key]['last_poster_firstname']=$last_post_info_of_forum['last_poster_firstname'];
		}
	}
	return $forum_list;
}

/**
 * This function stores which users have to be notified of which forums or threads
 *
 * @param string $content does the user want to be notified about a forum or about a thread
 * @param integer $id the id of the forum or thread
 * 
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version May 2008, dokeos 1.8.5
 * @since May 2008, dokeos 1.8.5
 */
function set_notification($content,$id, $add_only = false) {
	global $_user;
	
	// which database field do we have to store the id in?
	if ($content == 'forum') {
		$database_field = 'forum_id';
	} else {
		$database_field = 'thread_id';
	}
	
	// database table definition
	$table_notification = Database::get_course_table('forum_notification');
	
	// first we check if the notification is already set for this
	$sql = "SELECT * FROM $table_notification WHERE $database_field = '".Database::escape_string($id)."' AND user_id = '".Database::escape_string($_user['user_id'])."'";
	$result=api_sql_query($sql, __FILE__, __LINE__);
	$total = mysql_num_rows($result);
	
	// if the user did not indicate that (s)he wanted to be notified already then we store the notification request (to prevent double notification requests)
	if ($total <= 0) {
		$sql = "INSERT INTO $table_notification ($database_field, user_id) VALUES ('".Database::escape_string($id)."','".Database::escape_string($_user['user_id'])."')";
		$result=api_sql_query($sql, __FILE__, __LINE__);
		api_session_unregister('forum_notification'); 
		get_notifications_of_user(0,true);		
		return get_lang('YouWillBeNotifiedOfNewPosts');
	} else {
		if (!$add_only) {
			$sql = "DELETE FROM $table_notification WHERE $database_field = '".Database::escape_string($id)."' AND user_id = '".Database::escape_string($_user['user_id'])."'";
			$result=api_sql_query($sql, __FILE__, __LINE__);
			api_session_unregister('forum_notification'); 
			get_notifications_of_user(0,true);
			return get_lang('YouWillNoLongerBeNotifiedOfNewPosts');	
		}
			
	}
}

/**
 * This function retrieves all the email adresses of the users who wanted to be notified
 * about a new post in a certain forum or thread
 *
 * @param string $content does the user want to be notified about a forum or about a thread
 * @param integer $id the id of the forum or thread
 * 
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version May 2008, dokeos 1.8.5
 * @since May 2008, dokeos 1.8.5
 */
function get_notifications($content,$id) {
	global $table_users;

	// which database field contains the notification?
	if ($content == 'forum') {
		$database_field = 'forum_id';
	} else {
		$database_field = 'thread_id';
	}
	// database table definition
	$table_notification = Database::get_course_table('forum_notification');
	$sql = "SELECT user.user_id, user.firstname, user.lastname, user.email, user.user_id user FROM $table_users user, $table_notification notification
			WHERE user.user_id = notification.user_id
			AND notification.$database_field= '".Database::escape_string($id)."'";
	$result=api_sql_query($sql, __FILE__, __LINE__);
	$return = array();
	while ($row=Database::fetch_array($result)) {
		$return['user'.$row['user_id']]=array('email' => $row['email'], 'user_id' => $row['user_id']); 
	}	
	return $return;
}

/**
 * Get all the users who need to receive a notification of a new post (those subscribed to 
 * the forum or the thread)
 *
 * @param integer $forum_id the id of the forum
 * @param integer $thread_id the id of the thread
 * @param integer $post_id the id of the post
 * @return unknown
 * 
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version May 2008, dokeos 1.8.5
 * @since May 2008, dokeos 1.8.5
 */
function send_notifications($forum_id=0, $thread_id=0, $post_id=0) {
	global $_course; 
	
	// the content of the mail
	$email_subject = get_lang('NewForumPost')." - ".$_course['official_code'];
	$thread_link= api_get_path('WEB_CODE_PATH').'forum/viewthread.php?'.api_get_cidreq().'&forum='.$forum_id.'&thread='.$thread_id;
	$my_link=isset($link)?$link:'';
	$my_message=isset($message)?$message:'';
	$my_message .= $my_link;
	
	// users who subscribed to the forum
	if ($forum_id<>0) {
		$users_to_be_notified_by_forum = get_notifications('forum',$forum_id);
	} else {
		return false;
	}
	
	// user who subscribed to the thread
	if ($thread_id<>0) {
		$users_to_be_notified_by_thread = get_notifications('thread',$thread_id);
	}	
	
	// merging the two
	$users_to_be_notified = array_merge($users_to_be_notified_by_forum, $users_to_be_notified_by_thread);
	
	if (is_array($users_to_be_notified)) {
		foreach ($users_to_be_notified as $key=>$value) {
			if ($value['email'] <> $_user['email']) {
				$email_body= $value['firstname']." ".$value['lastname']."\n\r";
				$email_body .= '['.$_course['official_code'].'] - ['.$_course['name']."]<br>\n";
				$email_body .= get_lang('NewForumPost')."\n";
				$email_body .= get_lang('YouWantedToStayInformed')."<br><br>\n";
				$email_body .= get_lang('ThreadCanBeFoundHere')." : <a href=\"".$thread_link."\">".$thread_link."</a>\n";
			
				//set the charset and use it for the encoding of the email - small fix, not really clean (should check the content encoding origin first)
				//here we use the encoding used for the webpage where the text is encoded (ISO-8859-1 in this case)
				if(empty($charset)) {
					$charset='ISO-8859-1';
				}
			
				$newmail = api_mail_html($value['lastname'].' '.$value['firstname'], $value['email'], $email_subject, $email_body, $_SESSION['_user']['lastName'].' '.$_SESSION['_user']['firstName'], $_SESSION['_user']['mail']);
			}
		}
	}
}

/**
 * Get all the notification subscriptions of the user 
 * = which forums and which threads does the user wants to be informed of when a new 
 * post is added to this thread
 *
 * @param integer $user_id the user_id of a user (default = 0 => the current user)
 * @param boolean $force force get the notification subscriptions (even if the information is already in the session
 * 
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version May 2008, dokeos 1.8.5
 * @since May 2008, dokeos 1.8.5
 */
function get_notifications_of_user($user_id = 0, $force = false) {
	global $_course;
	$course = api_get_course_id();
	if (empty($course) OR $course==-1) {
		return null;
	}
	if ($user_id == 0) {
		global $_user;
		$user_id = $_user['user_id'];
	}
	
	// database table definition
	$table_notification = Database::get_course_table('forum_notification');
	$my_code = isset($_course['code']) ? $_course['code'] : '';
	if (!isset($_SESSION['forum_notification']) OR $_SESSION['forum_notification']['course'] <> $my_code OR $force=true) {
		$_SESSION['forum_notification']['course'] = $my_code;
		
		$sql = "SELECT * FROM $table_notification WHERE user_id='".Database::escape_string($user_id)."'";
		$result=api_sql_query($sql, __FILE__, __LINE__);
		while ($row=Database::fetch_array($result)) {
			if (!is_null($row['forum_id'])) {
				$_SESSION['forum_notification']['forum'][] = $row['forum_id'];
			}
			if (!is_null($row['thread_id'])) {
				$_SESSION['forum_notification']['thread'][] = $row['thread_id'];
			}		
		}
	}
}

/**
* This function counts the number of post inside a thread
* @param 	int Thread ID  
* @return	int the number of post inside a thread
* @author Jhon Hinojosa <jhon.hinojosa@dokeos.com>, 
* @version octubre 2008, dokeos 1.8
*/
function count_number_of_post_in_thread($thread_id) {
	global $table_posts;
	$sql = "SELECT * FROM $table_posts WHERE thread_id='".Database::escape_string($thread_id)."' ";
	$result = api_sql_query($sql, __FILE__, __LINE__);	
	return count(api_store_result($result));	
}

/**
* This function counts the number of post inside a thread user
* @param 	int Thread ID
* @param 	int User ID    
* @return	int the number of post inside a thread user
* @author Jhon Hinojosa <jhon.hinojosa@dokeos.com>, 
* @version octubre 2008, dokeos 1.8
*/
function count_number_of_post_for_user_thread($thread_id, $user_id) {
	global $table_posts;
	$sql = "SELECT * FROM $table_posts WHERE thread_id='".Database::escape_string($thread_id)."'
																			AND poster_id = '".Database::escape_string($user_id)."' ";
	$result = api_sql_query($sql, __FILE__, __LINE__);	
	return count(api_store_result($result));	
}

/**
* This function counts the number of user register in course
* @param 	int Course ID  
* @return	int the number of user register in course
* @author Jhon Hinojosa <jhon.hinojosa@dokeos.com>, 
* @version octubre 2008, dokeos 1.8
*/
function count_number_of_user_in_course($course_id) {	
	$table_course_rel_user = Database::get_main_table("course_rel_user");	
	$sql = "SELECT * FROM $table_course_rel_user  WHERE course_code ='".Database::escape_string($course_id)."' ";
	$result = api_sql_query($sql, __FILE__, __LINE__);	
	return count(api_store_result($result));
} 

/**
* This function retrieves information of statistical
* @param 	int Thread ID
* @param 	int User ID  
* @param 	int Course ID    
* @return	array the information of statistical
* @author Jhon Hinojosa <jhon.hinojosa@dokeos.com>, 
* @version octubre 2008, dokeos 1.8
*/
function get_statistical_information($thread_id, $user_id, $course_id) {
	$stadistic = array();
	$stadistic['user_course'] = count_number_of_user_in_course($course_id);
	$stadistic['post'] = count_number_of_post_in_thread($thread_id);
	$stadistic['user_post'] = count_number_of_post_for_user_thread($thread_id, $user_id);
	//$stadistic['average'] = get_average_of_thread_post_user();	
	return $stadistic; 	
}

/**
* This function return the posts inside a thread from a given user
* @param 	course code
* @param 	int Thread ID
* @param 	int User ID    
* @return	int the number of post inside a thread
* @author Jhon Hinojosa <jhon.hinojosa@dokeos.com>, 
* @version octubre 2008, dokeos 1.8
*/
function get_thread_user_post($course_db, $thread_id, $user_id )
{	
	$table_posts =  Database::get_course_table(TABLE_FORUM_POST, $course_db);
	global $table_users;
	
	$sql = "SELECT * FROM $table_posts posts
						LEFT JOIN  $table_users users
							ON posts.poster_id=users.user_id
						WHERE posts.thread_id='".Database::escape_string($thread_id)."'
							AND posts.poster_id='".Database::escape_string($user_id)."'					
						ORDER BY posts.post_id ASC";
	
	$result=api_sql_query($sql, __FILE__, __LINE__);
	
	while ($row=Database::fetch_array($result)) {
		$row['status'] = '1';
		$post_list[]=$row;	
		$sql = "SELECT * FROM $table_posts posts
					LEFT JOIN  $table_users users
						ON posts.poster_id=users.user_id
					WHERE posts.thread_id='".Database::escape_string($thread_id)."'
						AND posts.post_parent_id='".$row['post_id']."'  
					ORDER BY posts.post_id ASC";
		$result2=api_sql_query($sql, __FILE__, __LINE__);
		while ($row2=Database::fetch_array($result2))
		{
			$row2['status'] = '0';
			$post_list[] = $row2;			
		}
	}
	return $post_list;
}

/* This function get the name of an user by id
 * @param user_id int
 * return String
 * @author Christian Fasanando
 */
 function get_name_user_by_id($user_id) {
	$t_users = Database :: get_main_table(TABLE_MAIN_USER);	
	$sql ="SELECT CONCAT(firstname,' ',lastname) FROM ".$t_users." WHERE user_id = '".$user_id."' ";	
	$result = api_sql_query($sql, __FILE__, __LINE__);
	$row = Database::fetch_array($result);
	return $row[0];	
 }
 
 /** This function get the name of an thread by id
 * @param int thread_id
 * @return String
 * @author Christian Fasanando
 **/
 function get_name_thread_by_id($thread_id) {
	$t_forum_thread = Database::get_course_table(TABLE_FORUM_THREAD,'');
	$sql ="SELECT thread_title FROM ".$t_forum_thread." WHERE thread_id = '".$thread_id."' ";	
	$result = api_sql_query($sql, __FILE__, __LINE__);
	$row = Database::fetch_array($result);
	return $row[0];	
 }
 
 /** This function gets all the post written by an user 
 * @param int user id
 * @param string db course name
 * @return string 
 * @author Christian Fasanando / J. M
 **/
 
 function get_all_post_from_user($user_id, $course_db) 
 { 	 
 	$j=0;
	$forums = get_forums();	
	krsort($forums);
	$forum_results = '';	
 	foreach($forums as $forum) {	
		if ($j<=4) { 		
	 		$threads = get_threads($forum['forum_id']); 		
	 		if (is_array($threads)) {	 			
	 			/*echo Display::return_icon('forum.gif'); 		
	 			echo $forum['forum_title'];*/
	 			$my_course_db=explode('_',$course_db);
	 			$my_course_code=$my_course_db[1];	 			 
	 			$i=0;
	 			$hand_forums = ''; 	
	 			$post_counter = 0;		 			
		 		foreach($threads as $thread) {
		 				if ($i<=4) {
			 				$post_list = get_thread_user_post_limit($course_db, $thread['thread_id'], $user_id, 1);
			 				$post_counter = count($post_list);	
			 				if (is_array($post_list) && count($post_list)>0) {
			 					$hand_forums.= '<div id="social-thread">';
				 				$hand_forums.= Display::return_icon('forumthread.gif'); 	
				 				$hand_forums.= $thread['thread_title'].' ';
				 				foreach($post_list as $posts) {
				 					$hand_forums.= '<div id="social-post">';
				 					$hand_forums.= '<strong>'.$posts['post_title'].'</strong>'; 
				 					$hand_forums.= '<br / >';
				 					$hand_forums.= cut($posts['post_text'], 150); 					
				 					$hand_forums.= '</div>';	
				 					$hand_forums.= '<br / >';
				 				}
			 				}
			 			$hand_forums.= '</div>';
		 			}	
		 			$i++;
				}
				if ($post_counter > 0 ) {
					$forum_results .='<div id="social-forum">';
	 				$forum_results .='<div class="clear"></div><br />';
	 				$forum_results .='<div class="actions" style="margin-left:5px;margin-right:5px;">'.Display::return_icon('forum.gif').'&nbsp;&nbsp;&nbsp;&nbsp;'.$forum['forum_title'].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<div style="float:right;margin-top:-18px"><a href="../forum/viewforum.php?cidReq='.$my_course_code.'&gidReq=&forum='.$forum['forum_id'].' " >'.get_lang('SeeForum').'</a></div></div>';
	 				$forum_results .='<br / >';					
					$forum_results .=$hand_forums;
					$forum_results .='</div>'; 
				}
	 		}	 		
 		} $j++;		
 	}
 	return $forum_results;
 }
 
function get_thread_user_post_limit($course_db, $thread_id, $user_id, $limit=10)
{	
	$table_posts =  Database::get_course_table(TABLE_FORUM_POST, $course_db);
	global $table_users;
	
	$sql = "SELECT * FROM $table_posts posts
						LEFT JOIN  $table_users users
							ON posts.poster_id=users.user_id
						WHERE posts.thread_id='".Database::escape_string($thread_id)."'
							AND posts.poster_id='".Database::escape_string($user_id)."'					
						ORDER BY posts.post_id DESC LIMIT $limit ";
	$result=api_sql_query($sql, __FILE__, __LINE__);
	
	while ($row=Database::fetch_array($result)) {
		$row['status'] = '1';
		$post_list[]=$row;
	}
	return $post_list;
}
