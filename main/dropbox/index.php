<?php // $Id: index.php,v 1.46 2005/09/26 10:20:25 pcool Exp $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2006 Dokeos S.A.
	Copyright (c) 2006 Ghent University (UGent)
	Copyright (c) various contributors

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
==============================================================================
 * @desc The dropbox is a personal (peer to peer) file exchange module that allows
 * you to send documents to a certain (group of) users.
 *
 * @version 1.3
 *
 * @author Jan Bols <jan@ivpv.UGent.be>, main programmer, initial version
 * @author René Haentjens <rene.haentjens@UGent.be>, several contributions  (see RH)
 * @author Roan Embrechts, virtual course support
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University (see history version 1.3)
 *
 * @package dokeos.dropbox
 *
 * @todo complete refactoring. Currently there are about at least 3 sql queries needed for every individual dropbox document.
 *			first we find all the documents that were sent (resp. received) by the user
 *			then for every individual document the user(s)information who received (resp. sent) the document is searched
 *			then for every individual document the feedback is retrieved
 * @todo 	the implementation of the dropbox categories could (on the database level) have been done more elegantly by storing the category
 *			in the dropbox_person table because this table stores the relationship between the files (sent OR received) and the users
==============================================================================
 */

/**
==============================================================================
					HISTORY
==============================================================================
Version 1.1
------------
- dropbox_init1.inc.php: changed include statements to require statements. This way if a file is not found, it stops the execution of a script instead of continuing with warnings.
- dropbox_init1.inc.php: the include files "claro_init_global.inc.php" & "debug.lib.inc.php" are first checked for their existence before including them. If they don't exist, in the .../include dir, they get loaded from the .../inc dir. This change is necessary because the UCL changed the include dir to inc.
- dropbox_init1.inc.php: the databasetable name in the variable $dropbox_cnf["introTbl"] is chnged from "introduction" to "tool_intro"
- install.php: after submit, checks if the database uses accueil or tool_list as a tablename
- index.php: removed the behaviour of only the teachers that are allowed to delete entries
- index.php: added field "lastUploadDate" in table dropbox_file to store information about last update when resubmiting a file
- dropbox.inc.php: added $lang["lastUpdated"]
- index.php: entries in received list show when file was last updated if it is updated
- index.php: entries in sent list show when file was last resent if it was resent
- dropbox_submit.php: add a unique id to every uploaded file
- index.php: add POST-variable to the upload form with overwrite data when user decides to overwrite the previous sent file with new file
- dropbox_submit.php: add sanity checks on POST['overwrite'] data
- index.php: remove title field in upload form
- dropbox_submit.php: remove use of POST['title'] variable
- dropbox_init1.inc.php: added $dropbox_cnf["version"] variable
- dropbox_class.inc.php: add $this->lastUploadDate to Dropbox_work class
- dropbox.inc.php: added $lang['emptyTable']
- index.php: if the received or sent list is empty, a message is displayed
- dropbox_download.php: the $file var is set equal to the title-field of the filetable. So not constructed anymore by substracting the username from the filename
- index.php: add check to see if column lastUploadDate exists in filetable
- index.php: moved javascripts from dropbox_init2.inc.php to index.php
- index.php: when specifying an uploadfile in the form, a checkbox allowing the user to overwrite a previously sent file is shown when the specified file has the same name as a previously uploaded file of that user.
- index.php: assign all the metadata (author, description, date, recipient, sender) of an entry in a list to the class="dropbox_detail" and add css to html-header
- index.php: assign all dates of entries in list to the class="dropbox_date" and add CSS
- index.php: assign all persons in entries of list to the class="dropbox_person" and add CSS
- dropbox.inc.php: added $lang['dropbox_version'] to indicate the lates version. This must be equal to the $dropbox_cnf['version'] variable.
- dropbox_init1.inc.php: if the newest lang file isn't loaded by claro_init_global.inc.php from the .../lang dir it will be loaded locally from the .../plugin/dropbox/ dir. This way an administrator must not install the dropbox.inc.php in the .../lang/english dir, but he can leave it in the local .../plugin/dropbox/ dir. However if you want to present multiple language translations of the file you must still put the file in the /lang/ dir, because there is no language management system inside the .../plugin/dropbox dir.
- mime.inc.php: created this file. It contains an array $mimetype with all the mimetypes that are used by dropbox_download.php to give hinst to the browser during download about content
- dropbox_download.php: remove https specific headers because they're not necessary
- dropbox_download.php: use application/octet-stream as the default mime and inline as the default Content-Disposition
- dropbox.inc.php: add lang vars for "order by" action
- dropbox_class.inc.php: add methods orderSentWork, orderReceivedWork en _cmpWork and propery _orderBy to class Dropbox_person to take care of sorting
- index.php: add selectionlist to headers of sent/received lists to select "order by" and add code to keep selected value in sessionvar.
- index.php: moved part of a <a> hyperlink to previous line to remove the underlined space between symbol and title of a work entry in the sent/received list
- index.php: add filesize info in sent/received lists
- dropbox_submit.php: resubmit prevention only for GET action, because it gives some annoying behaviour in POST situation: white screen in IE6

Version 1.2
-----------
- adapted entire dropbox tool so it can be used as a default tool in Dokeos 1.5
- index.php: add event registration to log use of tool in stats tables
- index.php: upload form checks for correct user selection and file specification before uploading the script
- dropbox_init1.inc.php: added dropbox_cnf["allowOverwrite"] to allow or disallow overwriting of files
- index.php: author name textbox is automatically filled in
- mailing functionality (see RH comments in code)
- allowStudentToStudent and allowJustUpload options (id.)
- help in separate window (id.)

Version 1.3 (Patrick Cool)
--------------------------
- sortable table
- categories
- fixing a security hole
- tabs (which can be disabled: see $dropbox_cnf['sent_received_tabs'])
- same action on multiple documents ([zip]download, move, delete)
- consistency with the docuements tool (open/download file, icons of documents, ...)
- zip download of complete folder
==============================================================================
 */

/*
==============================================================================
		INIT SECTION
==============================================================================
*/
// the file that contains all the initialisation stuff (and includes all the configuration stuff)
require_once( "dropbox_init.inc.php");

// get the last time the user accessed the tool
if ($_SESSION['last_access'][$_course['id']][TOOL_DROPBOX]=='')
{
	$last_access=get_last_tool_access(TOOL_DROPBOX,$_course['code'],$_user['user_id']);
	$_SESSION['last_access'][$_course['id']][TOOL_DROPBOX]=$last_access;
}
else
{
	$last_access=$_SESSION['last_access'][$_course['id']][TOOL_DROPBOX];
}

// do the tracking
event_access_tool(TOOL_DROPBOX);

//this var is used to give a unique value to every page request. This is to prevent resubmiting data
$dropbox_unid = md5( uniqid( rand( ), true));

/*
==============================================================================
		DISPLAY SECTION
==============================================================================
*/

// Tool introduction text
Display::display_introduction_section(TOOL_DROPBOX);


/*
-----------------------------------------------------------
	ACTIONS: add a dropbox file, add a dropbox category.
-----------------------------------------------------------
*/

// *** display the form for adding a new dropbox item. ***
if ($_GET['action']=="add")
{
	display_add_form();
}
if ($_POST['submitWork'])
{
	Display :: display_normal_message(store_add_dropbox());
	//include_once('dropbox_submit.php');
}


// *** display the form for adding a category ***
if ($_GET['action']=="addreceivedcategory" or $_GET['action']=="addsentcategory")
{
	display_addcategory_form($_POST['category_name']);
}

// *** editing a category: displaying the form ***
if ($_GET['action']=='editcategory' and isset($_GET['id']))
{
	if (!$_POST)
	{
		display_addcategory_form('',$_GET['id']);
	}
}

// *** storing a new or edited category ***
if ($_POST['StoreCategory'])
{
	Display :: display_normal_message(store_addcategory());
}

// *** Move a File ***
if (($_GET['action']=='movesent' OR $_GET['action']=='movereceived') AND isset($_GET['move_id']))
{
	display_move_form(str_replace('move','',$_GET['action']), $_GET['move_id'], get_dropbox_categories(str_replace('move','',$_GET['action'])));
}
if ($_POST['do_move'])
{
	Display :: display_normal_message(store_move($_POST['id'], $_POST['move_target'], $_POST['part']));
}

// *** Delete a file ***
if (($_GET['action']=='deletereceivedfile' OR $_GET['action']=='deletesentfile') AND isset($_GET['id']) AND is_numeric($_GET['id']))
{
	$dropboxfile=new Dropbox_Person( $_user['user_id'], $is_courseAdmin, $is_courseTutor);
	if ($_GET['action']=='deletereceivedfile')
	{
		$dropboxfile->deleteReceivedWork($_GET['id']);
		$message=get_lang('ReceivedFileDeleted');
	}
	if ($_GET['action']=='deletesentfile')
	{
		$dropboxfile->deleteSentWork($_GET['id']);
		$message=get_lang('SentFileDeleted');
	}
	Display :: display_normal_message($message);
}

// *** Delete a category ***
if (($_GET['action']=='deletereceivedcategory' OR $_GET['action']=='deletesentcategory') AND isset($_GET['id']) AND is_numeric($_GET['id']))
{
	$message=delete_category($_GET['action'], $_GET['id']);
}

// *** Do an action on multiple files ***
// only the download has is handled separately in dropbox_init_inc.php because this has to be done before the headers are sent
// (which also happens in dropbox_init.inc.php
if ($_POST['do_actions_received'] OR $_POST['do_actions_sent'])
{
	$display_message=handle_multiple_actions();
	Display :: display_normal_message($display_message);
}

// *** Store Feedback ***
if ($_POST['store_feedback'])
{
	$display_message = store_feedback();
	Display :: display_normal_message($display_message);
}


// *** Error Message ***
if (isset($_GET['error']) AND !empty($_GET['error']))
{
	Display :: display_normal_message(get_lang($_GET['error']));
}




// getting all the categories in the dropbox for the given user
$dropbox_categories=get_dropbox_categories();
// creating the arrays with the categories for the received files and for the sent files
foreach ($dropbox_categories as $category)
{
	if ($category['received']=='1')
	{
		$dropbox_received_category[]=$category;
	}
	if ($category['sent']=='1')
	{
		$dropbox_sent_category[]=$category;
	}
}


/*
-----------------------------------------------------------
	THE MENU TABS
-----------------------------------------------------------
*/
if ($dropbox_cnf['sent_received_tabs'])
{
?>
<div id="tabbed_menu">
	<ul id="tabbed_menu_tabs">
		<li><a href="index.php?view=received" <?php if (!$_GET['view'] OR $_GET['view']=='received'){echo 'class="active"';}?> ><?php echo get_lang('ReceivedFiles'); ?></a></li>
		<li><a href="index.php?view=sent" <?php if ($_GET['view']=='sent'){echo 'class="active"';}?>><?php echo get_lang('SentFiles'); ?></a></li>
	</ul>
</div>
<?php
}

/*
-----------------------------------------------------------
	RECEIVED FILES
-----------------------------------------------------------
*/

if (!$_GET['view'] OR $_GET['view']=='received' OR $dropbox_cnf['sent_received_tabs']==false)
{
	//echo '<h3>'.get_lang('ReceivedFiles').'</h3>';

	// This is for the categories
	if (isset($_GET['view_received_category']) AND $_GET['view_received_category']<>'')
	{
		$view_dropbox_category_received=$_GET['view_received_category'];
	}
	else
	{
		$view_dropbox_category_received=0;
	}

	/* *** Menu Received *** */
	if ($view_dropbox_category_received<>0)
	{
		echo get_lang('CurrentlySeeing').': <strong>'.$dropbox_categories[$view_dropbox_category_received]['cat_name'].'</strong><br />';
		echo '<img src="../img/folder_up.gif" alt="'.get_lang('up').'" align="absmiddle" /><a href="'.$_SERVER['PHP_SELF'].'?view_received_category=0&amp;view_sent_category='.$_GET['view_sent_category'].'&amp;view='.$_GET['view'].'">'.get_lang('Root')."</a>\n";
	}
	echo "<a href=\"".$_SERVER['PHP_SELF']."?action=addreceivedcategory\"><img src=\"../img/folder_new.gif\" alt=\"".get_lang('NewFolder')."\" align=\"absmiddle\"/> ".get_lang('AddNewCategory')."</a>\n";


	echo '<form name="recieved_files" method="post" action="'.$_SERVER['PHP_SELF'].'?view_received_category='.$_GET['view_received_category'].'&amp;view_sent_category='.$_GET['view_sent_category'].'&amp;view='.$_GET['view'].'&amp;action='.$_GET['action'].'&amp;id='.$_GET['id'].'">';


	// object initialisation
	$dropbox_person = new Dropbox_Person( $_user['user_id'], $is_courseAdmin, $is_courseTutor); // note: are the $is_courseAdmin and $is_courseTutor parameters needed????

	// constructing the array that contains the total number of feedback messages per document.
	$number_feedback=get_total_number_feedback();

	// sorting and paging options
	$sorting_options = array();
	$paging_options = array();

	// the headers of the sortable tables
	$column_header=array();
	$column_header[] = array('',false,'');
	$column_header[] = array(get_lang('Type'),true,'style="width:40px"');
	$column_header[] = array(get_lang('ReceivedTitle'), TRUE, '');
	$column_header[] = array(get_lang('Authors'), TRUE, '');
	$column_header[] = array(get_lang('Description'), TRUE, '');
	$column_header[] = array(get_lang('Size'), TRUE, '');
	$column_header[] = array(get_lang('LastResent'), TRUE, '');
	$column_header[] = array(get_lang('Modify'), FALSE, '', 'nowrap style="text-align: right"');

	// the content of the sortable table = the received files
	foreach ( $dropbox_person -> receivedWork as $dropbox_file)
	{
		//echo '<pre>';
		//print_r($dropbox_file);
		//echo '</pre>';


		$dropbox_file_data=array();
		if ($view_dropbox_category_received==$dropbox_file->category) // we only display the files that are in the category that we are in.
		{
			$dropbox_file_data[]=display_file_checkbox($dropbox_file->id, 'received');
			// new icon
			if ($dropbox_file->last_upload_date > $last_access AND !in_array($dropbox_file->id,$_SESSION['_seen'][$_course['id']][TOOL_DROPBOX]))
			{
				$new_icon='<img src="../img/new.gif" align="absmiddle alt="'.get_lang('New').'" />';
			}
			else
			{
				$new_icon='';
			}
			$dropbox_file_data[]=build_document_icon_tag('file',$dropbox_file->title).$new_icon;
			$dropbox_file_data[]='<a href="dropbox_download.php?id='.$dropbox_file->id.'&amp;action=download"><img src="../img/filesave.gif" style="float:right;" alt="'.get_lang('Save').'"/></a><a href="dropbox_download.php?id='.$dropbox_file->id.'">'.$dropbox_file->title.'</a>';
			$dropbox_file_data[]=$dropbox_file->author;
			$dropbox_file_data[]=$dropbox_file->description;
			$dropbox_file_data[]=ceil(($dropbox_file->filesize)/1024).' '.get_lang('kB');
			$dropbox_file_data[]=$dropbox_file->last_upload_date;
			$action_icons=check_number_feedback($dropbox_file->id, $number_feedback).' '.get_lang('Feedback').'
									<a href="'.$_SERVER['PHP_SELF'].'?view_received_category='.$_GET['view_received_category'].'&amp;view_sent_category='.$_GET['view_sent_category'].'&amp;view='.$_GET['view'].'&amp;action=viewfeedback&amp;id='.$dropbox_file->id.'"><img src="../img/comment_bubble.gif" alt="'.get_lang('Comment').'" align="absmiddle" /></a>
									<a href="'.$_SERVER['PHP_SELF'].'?view_received_category='.$_GET['view_received_category'].'&amp;view_sent_category='.$_GET['view_sent_category'].'&amp;view='.$_GET['view'].'&amp;action=movereceived&amp;move_id='.$dropbox_file->id.'"><img src="../img/deplacer_fichier.gif" alt="'.get_lang('Move').'" align="absmiddle"/></a>
									<a href="'.$_SERVER['PHP_SELF'].'?view_received_category='.$_GET['view_received_category'].'&amp;view_sent_category='.$_GET['view_sent_category'].'&amp;view='.$_GET['view'].'&amp;action=deletereceivedfile&amp;id='.$dropbox_file->id.'" onclick="return confirmation(\''.$dropbox_file->title.'\');"><img src="../img/delete.gif" alt="'.get_lang('Delete').'" align="absmiddle" /></a>';
			//$action_icons='		<a href="'.$_SERVER['PHP_SELF'].'?view_received_category='.$_GET['view_received_category'].'&amp;view_sent_category='.$_GET['view_sent_category'].'&amp;action=movereceived&amp;move_id='.$dropbox_file->id.'"><img src="../img/deplacer.gif"  alt="'.get_lang('Move').'"/></a>
			//						<a href="'.$_SERVER['PHP_SELF'].'?view_received_category='.$_GET['view_received_category'].'&amp;view_sent_category='.$_GET['view_sent_category'].'&amp;action=deletereceivedfile&amp;id='.$dropbox_file->id.'" onclick="return confirmation(\''.$dropbox_file->title.'\');"><img src="../img/delete.gif"  alt="'.get_lang('Delete').'"/></a>';
			// this is a hack to have an additional row in a sortable table
			if($_GET['action']=='viewfeedback' AND isset($_GET['id']) and is_numeric($_GET['id']) AND $dropbox_file->id==$_GET['id'])
			{
				$action_icons.="</td></tr>\n"; // ending the normal row of the sortable table
				$action_icons.="<tr>\n\t<td colspan=\"2\"><a href=\"index.php?view_received_category=".$_GET['view_received_category']."&amp;view_sent_category=".$_GET['view_sent_category']."&amp;view=".$_GET['view']."\">".get_lang('CloseFeedback')."</a></td><td colspan=\"7\">".feedback($dropbox_file->feedback2)."</td>\n</tr>\n";

			}
			$dropbox_file_data[]=$action_icons;
			$action_icons='';

			$dropbox_data_recieved[]=$dropbox_file_data;
		}
	}

	// the content of the sortable table = the categories (if we are not in the root)
	if ($view_dropbox_category_received==0)
	{
		foreach ($dropbox_categories as $category) // note: this can probably be shortened since the categories for the received files are already in the $dropbox_received_category array;
		{
			$dropbox_category_data=array();
			if ($category['received']=='1')
			{
				$dropbox_category_data[]=''; // this is where the checkbox icon for the files appear
				// the icon of the category
				$dropbox_category_data[]=build_document_icon_tag('folder',$category['cat_name']);
				$dropbox_category_data[]='<a href="dropbox_download.php?cat_id='.$category['cat_id'].'&amp;action=downloadcategory&amp;sent_received=received"><img width="16" height="16" src="../img/folder_zip.gif" style="float:right;" alt="'.get_lang('Save').'"/></a><a href="'.$_SERVER['PHP_SELF'].'?view_received_category='.$category['cat_id'].'&amp;view_sent_category='.$_GET['view_sent_category'].'&amp;view='.$_GET['view'].'">'.stripslashes($category['cat_name']).'</a>';
				$dropbox_category_data[]='';
				$dropbox_category_data[]='';
				$dropbox_category_data[]='';
				$dropbox_category_data[]='';
				$dropbox_category_data[]='<a href="'.$_SERVER['PHP_SELF'].'?view_received_category='.$_GET['view_received_category'].'&amp;view_sent_category='.$_GET['view_sent_category'].'&amp;view='.$_GET['view'].'&amp;action=editcategory&amp;id='.$category['cat_id'].'"><img src="../img/edit.gif" alt="'.get_lang('Edit').'" /></a>
										  <a href="'.$_SERVER['PHP_SELF'].'?view_received_category='.$_GET['view_received_category'].'&amp;view_sent_category='.$_GET['view_sent_category'].'&amp;view='.$_GET['view'].'&amp;action=deletereceivedcategory&amp;id='.$category['cat_id'].'" onclick="return confirmation(\''.$category['cat_name'].'\');"><img src="../img/delete.gif" alt="'.get_lang('Delete').'" /></a>';
			}
			if (is_array($dropbox_category_data))
			{
				$dropbox_data_recieved[]=$dropbox_category_data;
			}
		}

	}

	// Displaying the table
	$additional_get_parameters=array('view'=>$_GET['view'], 'view_received_category'=>$_GET['view_received_category'],'view_sent_category'=>$_GET['view_sent_category']);
	Display::display_sortable_table($column_header, $dropbox_data_recieved, $sorting_options, $paging_options, $additional_get_parameters);
	if (empty($dropbox_data_recieved))
	{
		//echo get_lang('NoFilesHere');
	}
	else
	{
		echo '<a href="'.$_SERVER['PHP_SELF'].'?view_received_category='.$_GET['view_received_category'].'&amp;view_sent_category='.$_GET['view_sent_category'].'&amp;view='.$_GET['view'].'&amp;selectall">'.get_lang('SelectAll').'</a> - ';
		echo '<a href="'.$_SERVER['PHP_SELF'].'?view_received_category='.$_GET['view_received_category'].'&amp;view_sent_category='.$_GET['view_sent_category'].'&amp;view='.$_GET['view'].'">'.get_lang('UnSelectAll').'</a> ';
		echo get_lang('WithSelected').': ';
		display_action_options('received',$dropbox_received_category, $view_dropbox_category_received);
	}
	echo '</form>';
}





/*
-----------------------------------------------------------
	SENT FILES
-----------------------------------------------------------
*/
if ($_GET['view']=='sent' OR $dropbox_cnf['sent_received_tabs']==false)
{
	//echo '<h3>'.get_lang('SentFiles').'</h3>';

	// This is for the categories
	if (isset($_GET['view_sent_category']) AND $_GET['view_sent_category']<>'')
	{
		$view_dropbox_category_sent=$_GET['view_sent_category'];
	}
	else
	{
		$view_dropbox_category_sent=0;
	}

	/* *** Menu Sent *** */
	if ($view_dropbox_category_sent<>0)
	{
		echo get_lang('CurrentlySeeing').': <strong>'.$dropbox_categories[$view_dropbox_category_sent]['cat_name'].'</strong><br />';
		echo '<img src="../img/folder_up.gif" alt="'.get_lang('Up').'" align="absmiddle" /><a href="'.$_SERVER['PHP_SELF'].'?view_received_category='.$_GET['view_received_category'].'&amp;view_sent_category=0&amp;view='.$_GET['view'].'">'.get_lang('Root')."</a>\n";
	}
	echo "<a href=\"".$_SERVER['PHP_SELF']."?view=".$_GET['view']."&amp;action=add\"><img src=\"../img/submit_file.gif\" alt=\"".get_lang('Upload')."\" align=\"absmiddle\"/> ".get_lang('UploadNewFile')."</a>&nbsp;\n";
	echo "<a href=\"".$_SERVER['PHP_SELF']."?view=".$_GET['view']."&amp;action=addsentcategory\"><img src=\"../img/folder_new.gif\" alt=\"".get_lang('NewFolder')."\" align=\"absmiddle\" /> ".get_lang('AddNewCategory')."</a>\n";

	//echo '<form name="sent_files" method="post" action="'.$_SERVER['PHP_SELF'].'?view_received_category='.$_GET['view_received_category'].'&amp;view_sent_category='.$_GET['view_sent_category'].'">';
	echo '<form name="recieved_files" method="post" action="'.$_SERVER['PHP_SELF'].'?view_received_category='.$_GET['view_received_category'].'&amp;view_sent_category='.$_GET['view_sent_category'].'&amp;view='.$_GET['view'].'&amp;action='.$_GET['action'].'&amp;id='.$_GET['id'].'">';

	// object initialisation
	$dropbox_person = new Dropbox_Person( $_user['user_id'], $is_courseAdmin, $is_courseTutor);

	// constructing the array that contains the total number of feedback messages per document.
	$number_feedback=get_total_number_feedback();

	// sorting and paging options
	$sorting_options = array();
	$paging_options = array();


	// the headers of the sortable tables
	$column_header=array();
	$column_header[] = array('',false,'');
	$column_header[] = array(get_lang('Type'),true,'style="width:40px"','style="text-align:center"');
	$column_header[] = array(get_lang('SentTitle'), TRUE, '');
	$column_header[] = array(get_lang('Authors'), TRUE, '');
	$column_header[] = array(get_lang('Description'), TRUE, '');
	$column_header[] = array(get_lang('Size'), TRUE, '');
	$column_header[] = array(get_lang('LastResent'), TRUE, '');
	$column_header[] = array(get_lang('SentTo'), TRUE, '');
	$column_header[] = array(get_lang('Modify'), FALSE, '', 'nowrap style="text-align: right"');

	// the content of the sortable table = the received files
	foreach ( $dropbox_person -> sentWork as $dropbox_file)
	{
		/*echo '<pre>';
		print_r($dropbox_file);
		echo '</pre>';	*/

		$dropbox_file_data=array();
		if ($view_dropbox_category_sent==$dropbox_file->category)
		{
			$dropbox_file_data[]=display_file_checkbox($dropbox_file->id, 'sent'); ;
			$dropbox_file_data[]=build_document_icon_tag('file',$dropbox_file->title);
			$dropbox_file_data[]='<a href="dropbox_download.php?id='.$dropbox_file->id.'&amp;action=download"><img src="../img/filesave.gif" style="float:right;" alt="'.get_lang('Save').'" /></a><a href="dropbox_download.php?id='.$dropbox_file->id.'">'.$dropbox_file->title.'</a>';
			$dropbox_file_data[]=$dropbox_file->author;
			$dropbox_file_data[]=$dropbox_file->description;
			$dropbox_file_data[]=ceil(($dropbox_file->filesize)/1024).' '.get_lang('kB');
			$dropbox_file_data[]=$dropbox_file->last_upload_date;
			foreach ($dropbox_file->recipients as $recipient)
			{
				$receivers_celldata=display_user_link($recipient['user_id'], $recipient['name']).', '.$receivers_celldata;
			}
			$dropbox_file_data[]=$receivers_celldata;
			$receivers_celldata='';
			$action_icons=check_number_feedback($dropbox_file->id, $number_feedback).' '.get_lang('Feedback').'
									<a href="'.$_SERVER['PHP_SELF'].'?view_received_category='.$_GET['view_received_category'].'&amp;view_sent_category='.$_GET['view_sent_category'].'&amp;view='.$_GET['view'].'&amp;action=viewfeedback&amp;id='.$dropbox_file->id.'"><img src="../img/comment_bubble.gif" alt="'.get_lang('Comment').'" align="absmiddle" /></a>
									<a href="'.$_SERVER['PHP_SELF'].'?view_received_category='.$_GET['view_received_category'].'&amp;view_sent_category='.$_GET['view_sent_category'].'&amp;view='.$_GET['view'].'&amp;action=movesent&amp;move_id='.$dropbox_file->id.'"><img src="../img/deplacer_fichier.gif" alt="'.get_lang('Move').'" align="absmiddle"/></a>
									<a href="'.$_SERVER['PHP_SELF'].'?view_received_category='.$_GET['view_received_category'].'&amp;view_sent_category='.$_GET['view_sent_category'].'&amp;view='.$_GET['view'].'&amp;action=deletesentfile&amp;id='.$dropbox_file->id.'" onclick="return confirmation(\''.$dropbox_file->title.'\');"><img src="../img/delete.gif" alt="'.get_lang('Delete').'" align="absmiddle" /></a>';
			// this is a hack to have an additional row in a sortable table
			if($_GET['action']=='viewfeedback' AND isset($_GET['id']) and is_numeric($_GET['id']) AND $dropbox_file->id==$_GET['id'])
			{
				$action_icons.="</td></tr>\n"; // ending the normal row of the sortable table
				$action_icons.="<tr>\n\t<td colspan=\"2\"><a href=\"index.php?view_received_category=".$_GET['view_received_category']."&amp;view_sent_category=".$_GET['view_sent_category']."&amp;view=".$_GET['view']."\">".get_lang('CloseFeedback')."</a></td><td colspan=\"7\">".feedback($dropbox_file->feedback2)."</td>\n</tr>\n";

			}
			$dropbox_file_data[]=$action_icons;
			$action_icons='';
			$dropbox_data_sent[]=$dropbox_file_data;
			//echo '<pre>';
			//print_r($dropbox_data_sent);
			//echo '</pre>';
		}
	}

	// the content of the sortable table = the categories (if we are not in the root)
	if ($view_dropbox_category_sent==0)
	{
		foreach ($dropbox_categories as $category)
		{
			$dropbox_category_data=array();
			if ($category['sent']=='1')
			{
				$dropbox_category_data[]=''; // this is where the checkbox icon for the files appear
				$dropbox_category_data[]=build_document_icon_tag('folder',$category['cat_name']);
				$dropbox_category_data[]='<a href="dropbox_download.php?cat_id='.$category['cat_id'].'&amp;action=downloadcategory&amp;sent_received=sent"><img width="16" height="16" src="../img/folder_zip.gif" style="float:right;" alt="'.get_lang('Save').'" /></a><a href="'.$_SERVER['PHP_SELF'].'?view_received_category='.$_GET['view_received_category'].'&amp;view_sent_category='.$category['cat_id'].'&amp;view='.$_GET['view'].'">'.stripslashes($category['cat_name']).'</a>';
				$dropbox_category_data[]='';
				$dropbox_category_data[]='';
				$dropbox_category_data[]='';
				$dropbox_category_data[]='';
				$dropbox_category_data[]='';
				$dropbox_category_data[]='<a href="'.$_SERVER['PHP_SELF'].'?view_received_category='.$_GET['view_received_category'].'&amp;view_sent_category='.$_GET['view_sent_category'].'&amp;view='.$_GET['view'].'&amp;action=editcategory&id='.$category['cat_id'].'"><img src="../img/edit.gif" alt="'.get_lang('Edit').'"/></a>
										  <a href="'.$_SERVER['PHP_SELF'].'?view_received_category='.$_GET['view_received_category'].'&amp;view_sent_category='.$_GET['view_sent_category'].'&amp;view='.$_GET['view'].'&amp;action=deletesentcategory&amp;id='.$category['cat_id'].'" onclick="return confirmation(\''.$category['cat_name'].'\');"><img src="../img/delete.gif" alt="'.get_lang('Delete').'" /></a>';
			}
			if (is_array($dropbox_category_data))
			{
				$dropbox_data_sent[]=$dropbox_category_data;
			}
		}

	}

	// Displaying the table
	$additional_get_parameters=array('view'=>$_GET['view'], 'view_received_category'=>$_GET['view_received_category'],'view_sent_category'=>$_GET['view_sent_category']);
	Display::display_sortable_table($column_header, $dropbox_data_sent, $sorting_options, $paging_options, $additional_get_parameters);
	if (empty($dropbox_data_sent))
	{
		//echo get_lang('NoFilesHere');
	}
	else
	{
		echo '<a href="'.$_SERVER['PHP_SELF'].'?view_received_category='.$_GET['view_received_category'].'&amp;view_sent_category='.$_GET['view_sent_category'].'&amp;view='.$_GET['view'].'&amp;selectall">'.get_lang('SelectAll').'</a> - ';
		echo '<a href="'.$_SERVER['PHP_SELF'].'?view_received_category='.$_GET['view_received_category'].'&amp;view_sent_category='.$_GET['view_sent_category'].'&amp;view='.$_GET['view'].'">'.get_lang('UnSelectAll').'</a> ';
		echo get_lang('WithSelected').': ';
		display_action_options('sent',$dropbox_sent_category, $view_dropbox_category_sent);
	}
	echo '</form>';
}


Display::display_footer();
exit;


























if ( $_GET['mailing'])  // RH: Mailing detail window passes parameter
{
	getUserOwningThisMailing($_GET['mailing'], $_user['user_id'], '304');  // RH or die
	$dropbox_person = new Dropbox_Person( $_GET['mailing'], $is_courseAdmin, $is_courseTutor);
	$mailingInUrl = "&mailing=" . urlencode( $_GET['mailing']);
}
else
{

	$mailingInUrl = "";
}
$dropbox_person->orderReceivedWork ($receivedOrder);
if( isset($_GET['dropbox_user_filter']) && $_GET['dropbox_user_filter'] != -1)
{
	$dropbox_person->filter_received_work('uploader_id',$_GET['dropbox_user_filter']);
}
$dropbox_person->orderSentWork ($sentOrder);

if (isset($_POST["feedbackid"]) && isset($_POST["feedbacktext"]))  // RH: Feedback
{
	$dropbox_person->updateFeedback ($_POST["feedbackid"], get_magic_quotes_gpc() ?
	stripslashes($_POST["feedbacktext"]) : $_POST["feedbacktext"]);
}









/*
==============================================================================
		FORM UPLOAD FILE
==============================================================================
*/
if ( $_GET['mailing'])  // RH: Mailing detail: no form upload
{
	echo "<h3>", htmlspecialchars( getUserNameFromId ( $_GET['mailing'])), "</h3>";
	echo "<a href='index.php?".api_get_cidreq()."&origin=$origin'>".dropbox_lang("mailingBackToDropbox").'</a><br><br>';
}
else
{

}  // RH: Mailing: end of 'Mailing detail: no form upload'

/*
==============================================================================
		FILES LIST
==============================================================================
*/

echo "<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" width=\"100%\">",
	"<tr>",
	"<td valign=\"top\" align=\"center\">";

/*
-----------------------------------------------------------
	RECEIVED FILES LIST:  TABLE HEADER
-----------------------------------------------------------
*/
if ( !$_GET['mailing'])  // RH: Mailing detail: no received files
{
	?>
			<table cellpadding="5" cellspacing="1" border="0" width="100%">
				<!--This is no longer neede because of sortable table -->
				<tr class="cell_header">
					<td colspan="2">
						<table width="100%" border="0" cellspacing="0" cellpadding="0">
							<tr>
								<td>
								<form name="formReceived" method="get" action="index.php?<?php echo "origin=$origin"; ?>">
								  <span class="dropbox_listTitle"><?php echo dropbox_lang("orderBy")?></span>
						  		  <?php if ($origin=='learnpath') { echo "<input type='hidden' name='origin' value='learnpath'>"; } ?>
								  <select name="receivedOrder" onchange="javascript: this.form.submit()">
								    <option value="lastDate" <?php if ($receivedOrder=="lastDate") {
								                                   echo "selected";
								                               }?>><?php echo dropbox_lang("lastDate")?></option>
								    <?php if (dropbox_cnf("allowOverwrite")) { ?>
								    <option value="firstDate" <?php if ($receivedOrder=="firstDate") {
								                                   echo "selected";
								                               }?>><?php echo dropbox_lang("firstDate")?></option>
								    <?php } ?>
								    <option value="title" <?php if ($receivedOrder=="title") {
								                                   echo "selected";
								                               }?>><?php echo dropbox_lang("title")?></option>
								    <option value="size" <?php if ($receivedOrder=="size") {
								                                   echo "selected";
								                               }?>><?php echo dropbox_lang("size")?></option>
								    <option value="author" <?php if ($receivedOrder=="author") {
								                                   echo "selected";
								                               }?>><?php echo dropbox_lang("author")?></option>
								    <option value="sender" <?php if ($receivedOrder=="sender") {
								                                   echo "selected";
								                               }?>><?php echo dropbox_lang("sender")?></option>
								  </select>
								   <span class="dropbox_listTitle"><?php echo dropbox_lang('sentBy'); ?></span>
								  <select name="dropbox_user_filter" onchange="javascript: this.form.submit()">
								  <option value="-1"><?php echo get_lang('All'); ?></option>
								  <?php
								  	foreach ($complete_user_list_for_dropbox as $current_user)
									{
										$full_name = $current_user['lastcommafirst'];
										echo '<option value="' . $current_user['user_id'] . '"'.($_GET['dropbox_user_filter'] == $current_user['user_id'] ? 'selected="selected"' : '').'>' . $full_name . '</option>';
									}
								  ?>
								  </select>
								  <noscript><input type="submit" value="OK"/></noscript>
								</form>
								</td>
								<td align="right"><div class="dropbox_listTitle"><?php echo strtoupper( dropbox_lang("receivedTitle"))?></div></td>
								<td align="right" width="30px">
									<a href="dropbox_submit.php?<?php echo "origin=$origin"; ?>&deleteReceived=all&dropbox_unid=<?php echo urlencode( $dropbox_unid)?>"
										onClick="return confirmation('<?php echo addslashes( dropbox_lang("all", "noDLTT"))?>');">
									<img src="../img/delete.gif" border="0" title="<?php echo get_lang("Delete"); ?>" alt="" /></a>
									<?php if ($origin=='learnpath') { echo "<input type='hidden' name='origin' value='learnpath' />"; } ?>
								</td>
			  				</tr>
						</table>
		  			</td>
				</tr>
	<?php

/*
-----------------------------------------------------------
	RECEIVED FILES LIST
-----------------------------------------------------------
*/

$numberDisplayed = count($dropbox_person -> receivedWork);  // RH
$i = 0;

// RH: Feedback: pencil for Give/Edit Feedback, UI rearranged, feedback added

foreach ( $dropbox_person -> receivedWork as $w)
{
	if ( $w -> uploader_id == $_user['user_id'])  // RH: justUpload
	{
		$numberDisplayed -= 1; continue;
	}
    ?>

					<tr>
					<td valign="top" algin="left" width="25">
						<a href="dropbox_download.php?<?php echo api_get_cidreq()."&origin=$origin"; ?>&id=<?php echo urlencode($w->id)?>">
							<img  src="../img/travaux.gif" border="0" alt="" /></a>
					</td>
					<td valign="top" align="left">
						<table width="100%" border="0" cellspacing="0" cellpadding="0">
							<tr>
								<td valign="top">
									<a href="dropbox_download.php?<?php echo api_get_cidreq()."&origin=$origin"; ?>&id=<?php echo urlencode($w->id)?>">
										<?php echo $w -> title?></a> <span class="dropbox_detail">(<?php echo ceil(($w->filesize)/1024)?> kB)</span>
								</td>
								<td align="right" valign="top">
									<a href="dropbox_submit.php?<?php echo "origin=$origin"; ?>&editFeedback=<?php echo urlencode($w->id)?>&dropbox_unid=<?php echo urlencode($dropbox_unid)?>">
										<img src="../img/comment.gif" border="0" title="<?php echo dropbox_lang("giveFeedback", "noDLTT"); ?>" alt="" /></a>
									<a href="dropbox_submit.php?<?php echo "origin=$origin"; ?>&deleteReceived=<?php echo urlencode($w->id)?>&dropbox_unid=<?php echo urlencode($dropbox_unid)?>"
										onClick='return confirmation("<?php echo htmlentities($w->title, ENT_COMPAT)?>");'>
										<img src="../img/delete.gif" border="0" title="<?php echo $langDelete; ?>" alt="" /></a>
								</td>
							</tr>
							<tr><td>
						<?php
    if ( $w -> author != '')  //only show if filled in in DB
    {
                        ?>  <span class="dropbox_detail"><?php echo dropbox_lang("authors").': '.$w -> author?></span><br>
						<?php
    }
    if ( $w -> description != '')
    {
                        ?>  <span class="dropbox_detail"><?php echo dropbox_lang("description").': '.$w -> description?></span><br>
						<?php
    }
                        ?>  <span class="dropbox_detail"><?php echo dropbox_lang("sentBy")?> <span class="dropbox_person"><?php echo $w -> uploaderName?></span> <?php echo dropbox_lang("sentOn")?> <span class="dropbox_date"><?php echo $w -> upload_date?></span></span>
						<?php
	if ($w -> upload_date != $w->last_upload_date)
	{
                    	?>  <br>
                    	    <span class="dropbox_detail"><?php echo dropbox_lang("lastUpdated")?> <span class="dropbox_date"><?php echo $w->last_upload_date?></span></span>
                        <?php
	}
                        ?>
                            </td>
                            <td align="right">
						<?php
	if (($fbtext = $w -> feedback))
	{
                    	?>  <div class="dropbox_feedback"><?php echo dropbox_lang("sentOn")?> <span class="dropbox_date">
                    	    <?php echo htmlspecialchars($w->feedback_date), ':</span><br>',
                    	        nl2br(htmlspecialchars($fbtext)); ?>
                    	    </div>
                        <?php
	}
                        ?>
                            </td></tr>
						</table>
					</td>
				</tr>
	<?php
    $i++;
} //end of foreach
if ( $numberDisplayed == 0)
{  // RH
    ?>
				<tr>
					<td align="center"><?php echo get_lang('TheListIsEmpty'); ?>
					</td>
				</tr>
	<?php
}

?>

			</table>
			<br>

	<?php
}  // RH: Mailing: end of 'Mailing detail: no received files'

/**
 * --------------------------------------
 *       SENT FILES LIST:  TABLE HEADER
 * --------------------------------------
 */
?>
			<table cellpadding="5" cellspacing="1" border="0" width="100%">
				<tr class="cell_header">
					<td colspan="2">
						<table width="100%" border="0" cellspacing="0" cellpadding="0">
							<tr>
								<td>
								<form name="formSent" method="get" action="index.php?<?php echo "origin=$origin"; ?>">
								  <?php if ($origin=='learnpath') { echo "<input type='hidden' name='origin' value='learnpath' />"; } ?>
								  <span class="dropbox_listTitle"><?php echo dropbox_lang("orderBy")?></span>
								  <select name="sentOrder" onchange="javascript: this.form.submit()">
								    <option value="lastDate" <?php if ($sentOrder=="lastDate") {
								                                   echo "selected";
								                               }?>><?php echo dropbox_lang("lastDate")?></option>
								    <?php if (dropbox_cnf("allowOverwrite")) { ?>
									<option value="firstDate" <?php if ($sentOrder=="firstDate") {
								                                   echo "selected";
								                               }?>><?php echo dropbox_lang("firstDate")?></option>
									<?php } ?>
								    <option value="title" <?php if ($sentOrder=="title") {
								                                   echo "selected";
								                               }?>><?php echo dropbox_lang("title")?></option>
								    <option value="size" <?php if ($sentOrder=="size") {
								                                   echo "selected";
								                               }?>><?php echo dropbox_lang("size")?></option>
								    <option value="author" <?php if ($sentOrder=="author") {
								                                   echo "selected";
								                               }?>><?php echo dropbox_lang("author")?></option>
								    <option value="recipient" <?php if ($sentOrder=="recipient") {
								                                   echo "selected";
								                               }?>><?php echo dropbox_lang("recipient")?></option>
								  </select>
								  <noscript><input type="submit" value="OK"/></noscript>
								</form>
								</td>
								<td align="right"><div class="dropbox_listTitle"><?php echo strtoupper( dropbox_lang("sentTitle"))?></div></td>
								<td align="right" width="30px">
<!--	Users cannot delete their own sent files
								<img src="shim.gif" width="20" height="20" border="0">
-->

								<a href="dropbox_submit.php?<?php echo "origin=$origin"; ?>&deleteSent=all&dropbox_unid=<?php echo urlencode( $dropbox_unid).$mailingInUrl?>"
											onClick="return confirmation('<?php echo addslashes( dropbox_lang("all", "noDLTT"))?>');">
									<img src="../img/delete.gif" border="0" title="<?php echo $langDelete; ?>" alt="" /></a>
<!--	-->
 								</td>
			  				</tr>
						</table>
		  			</td>
				</tr>

<?php

/**
 * --------------------------------------
 *       SENT FILES LIST
 * --------------------------------------
 */
$i = 0;

// RH: Feedback: UI rearranged, feedback added

foreach ( $dropbox_person -> sentWork as $w)
{
	$langSentTo = dropbox_lang("sentTo", "noDLTT") . '&nbsp;';  // RH: Mailing: not for unsent

	// RH: Mailing: clickable folder image for detail

	if ( $w->recipients[0]['id'] > dropbox_cnf("mailingIdBase"))
	{
		$ahref = "index.php?".api_get_cidreq()."&origin=$origin&mailing=" . urlencode($w->recipients[0]['id']);
		$imgsrc = '../img/folder.gif';
	}
	else
	{
		$ahref = "dropbox_download.php?".api_get_cidreq()."&origin=$origin&id=" . urlencode($w->id) . $mailingInUrl;
		$imgsrc = '../img/travaux.gif';
	}
?>
				<tr>
					<td valign="top" algin="left"  width="25">
						<a href="<?php echo $ahref?>">
							<img  src="<?php echo $imgsrc?>" border="0" alt="" /></a>
					</td>
					<td valign="top" align="left">
						<table width="100%" border="0" cellspacing="0" cellpadding="0">
							<tr>
								<td valign="top">
									<a href="<?php echo $ahref?>">
										<?php echo $w -> title?></a> <span class="dropbox_detail">(<?php echo ceil(($w->filesize)/1024)?> kB)</span>
								</td>
								<td align="right" valign="top">

<?php  // RH: Mailing: clickable images for examine and send
if ( $w->recipients[0]['id'] == $_user['user_id'])
{
	$langSentTo = dropbox_lang("justUploadInList", "noDLTT") . '&nbsp;';  // RH: justUpload
}
elseif ( $w->recipients[0]['id'] > dropbox_cnf("mailingIdBase"))
{
?>
									<a href="dropbox_submit.php?<?php echo "origin=$origin"; ?>&mailingIndex=<?php echo urlencode( $i)?>&dropbox_unid=<?php echo urlencode( $dropbox_unid).$mailingInUrl?>">
										<img src="../img/checkzip.gif" border="0" title="<?php echo dropbox_lang("mailingExamine", "noDLTT")?>" alt="" /></a>
<?php  // RH: Mailing: filesize is set to zero on send, allow no 2nd send!
	if ( $w->filesize != 0)
	{
		$langSentTo = '';  // unsent: do not write 'Sent to'
?>
									<a href="dropbox_submit.php?<?php echo "origin=$origin"; ?>&mailingIndex=<?php echo urlencode( $i)?>&mailingSend=yes&dropbox_unid=<?php echo urlencode( $dropbox_unid).$mailingInUrl?>"
										onClick='return confirmsend();'>
										<img src="../img/sendzip.gif" border="0" title="<?php echo dropbox_lang("mailingSend", "noDLTT")?>" alt="" /></a>
<?php  // RH: Mailing: end of 'clickable images for examine and send'
	}
}


// RH: Feedback

$lastfeedbackdate = ''; $lastfeedbackfrom = '';
foreach ($w -> recipients as $r) if (($fb = $r["feedback"]))
    if ($r["feedback_date"] > $lastfeedbackdate)
    {
        $lastfeedbackdate = $r["feedback_date"]; $lastfeedbackfrom = $r["name"];
    }

if ($lastfeedbackdate)
{
?>
									<span class="dropbox_feedback" title="<?php echo $lastfeedbackfrom; ?>"><?php echo $lastfeedbackdate; ?></span>
                                    <a href="dropbox_submit.php?<?php echo "origin=$origin"; ?>&showFeedback=<?php echo urlencode($w->id)?>&dropbox_unid=<?php echo urlencode($dropbox_unid)?>">
										<img src="../img/comment.gif" border="0" alt=""  title="<?php echo dropbox_lang("showFeedback", "noDLTT"); ?>"/></a>
<?php
}
?>
									<a href="dropbox_submit.php?<?php echo "origin=$origin"; ?>&deleteSent=<?php echo urlencode($w->id)?>&dropbox_unid=<?php echo urlencode($dropbox_unid) . $mailingInUrl?>"
										onClick='return confirmation("<?php echo htmlentities($w->title, ENT_COMPAT)?>");'>
										<img src="../img/delete.gif" border="0" title="<?php echo $langDelete; ?>" alt="" /></a>
        					</td>
        				</tr>
        				<tr><td>
        				<?php
    if ( $w -> author != '')  //only show if filled in in DB
    {
                        ?>  <span class="dropbox_detail"><?php echo dropbox_lang("authors").': '.$w -> author?></span><br>
						<?php
    }
    if ( $w -> description != '')
    {
                        ?>  <span class="dropbox_detail"><?php echo dropbox_lang("description").': '.$w -> description?></span><br>
						<?php
    }
                            echo '<span class="dropbox_detail">', $langSentTo, '<span class="dropbox_person">';
                        	foreach( $w -> recipients as $r){ echo $r["name"], ', '; }
                        	echo '</span>', dropbox_lang("sentOn"), ' <span class="dropbox_date">', $w -> upload_date, '</span></span>';

	if ($w -> upload_date != $w->last_upload_date)
	{
                    	?>  <br>
                    	    <span class="dropbox_detail"><?php echo dropbox_lang("lastResent")?> <span class="dropbox_date"><?php echo $w->last_upload_date?></span></span>
						<?php
	}
                        ?>
                        </td>
                        <td align="right">
                            <div class="dropbox_feedback">&nbsp;</div>
                        </td>
                        </tr>
						</table>
					</td>
				</tr>

	<?php
    $i++;
} //end of foreach

if (count($dropbox_person -> sentWork)==0)
{
	echo "<tr>",
			"<td align=\"center\">",get_lang('TheListIsEmpty'),
			"</td>",
			"</tr>";
}

echo "</table>",

	"</td>",
	"</tr>",
	"</table>";

if ($origin != 'learnpath')
{
	//we are not in the learning path tool
	Display::display_footer();
}
?>
