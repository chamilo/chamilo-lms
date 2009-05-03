<?php //$id: $
/* For licensing terms, see /dokeos_license.txt */
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

Version 1.4 (Yannick Warnier)
-----------------------------
- removed all self-built database tables names
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
if ($_SESSION[$_course['id']]['last_access'][TOOL_DROPBOX]=='') {
	$last_access=get_last_tool_access(TOOL_DROPBOX,$_course['code'],$_user['user_id']);
	$_SESSION[$_course['id']]['last_access'][TOOL_DROPBOX]=$last_access;
} else {
	$last_access=$_SESSION[$_course['id']]['last_access'][TOOL_DROPBOX];
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

// Tool introduction
$fck_attribute['Width'] = '100%';
$fck_attribute['Height'] = '300';
$fck_attribute['ToolbarSet'] = 'Introduction';
Display::display_introduction_section(TOOL_DROPBOX,'left');
$fck_attribute = null; // Clearing this global variable immediatelly after it has been used.

/*
-----------------------------------------------------------
	ACTIONS: add a dropbox file, add a dropbox category.
-----------------------------------------------------------
*/

// *** display the form for adding a new dropbox item. ***
if ($_GET['action']=="add") {
	display_add_form();
}

if (isset($_POST['submitWork'])) {
	$check = Security::check_token();
	if ($check) {
		Display :: display_confirmation_message(store_add_dropbox());
		//include_once('dropbox_submit.php');
	}
}


// *** display the form for adding a category ***
if ($_GET['action']=="addreceivedcategory" or $_GET['action']=="addsentcategory") {
	display_addcategory_form($_POST['category_name']);
}

// *** editing a category: displaying the form ***
if ($_GET['action']=='editcategory' and isset($_GET['id'])) {
	if (!$_POST) {
		display_addcategory_form('',$_GET['id']);
	}
}

// *** storing a new or edited category ***
if (isset($_POST['StoreCategory'])) {
	Display :: display_confirmation_message(store_addcategory());
}

// *** Move a File ***
if (($_GET['action']=='movesent' OR $_GET['action']=='movereceived') AND isset($_GET['move_id'])) {
	display_move_form(str_replace('move','',$_GET['action']), $_GET['move_id'], get_dropbox_categories(str_replace('move','',$_GET['action'])));
}
if ($_POST['do_move']) {
	Display :: display_confirmation_message(store_move($_POST['id'], $_POST['move_target'], $_POST['part']));
}

// *** Delete a file ***
if (($_GET['action']=='deletereceivedfile' OR $_GET['action']=='deletesentfile') AND isset($_GET['id']) AND is_numeric($_GET['id'])) {
	$dropboxfile=new Dropbox_Person( $_user['user_id'], $is_courseAdmin, $is_courseTutor);
	if ($_GET['action']=='deletereceivedfile') {
		$dropboxfile->deleteReceivedWork($_GET['id']);
		$message=get_lang('ReceivedFileDeleted');
	}
	if ($_GET['action']=='deletesentfile') {
		$dropboxfile->deleteSentWork($_GET['id']);
		$message=get_lang('SentFileDeleted');
	}
	Display :: display_confirmation_message($message);
}

// *** Delete a category ***
if (($_GET['action']=='deletereceivedcategory' OR $_GET['action']=='deletesentcategory') AND isset($_GET['id']) AND is_numeric($_GET['id'])) {
	$message=delete_category($_GET['action'], $_GET['id']);
}

// *** Do an action on multiple files ***
// only the download has is handled separately in dropbox_init_inc.php because this has to be done before the headers are sent
// (which also happens in dropbox_init.inc.php

if (!isset($_POST['feedback']) && (strstr($_POST['action'],'move_received') OR
        $_POST['action'] == 'delete_received' OR $_POST['action'] == 'download_received' OR
        $_POST['action'] == 'delete_sent' OR $_POST['action'] == 'download_sent'))
{
	$display_message=handle_multiple_actions();
	Display :: display_normal_message($display_message);
}

// *** Store Feedback ***
if ($_POST['feedback']) {
	$display_message = store_feedback();
	Display :: display_normal_message($display_message);
}


// *** Error Message ***
if (isset($_GET['error']) AND !empty($_GET['error'])) {
	Display :: display_normal_message(get_lang($_GET['error']));
}



if ($_GET['action']!="add") {
// getting all the categories in the dropbox for the given user
$dropbox_categories=get_dropbox_categories();
// creating the arrays with the categories for the received files and for the sent files
foreach ($dropbox_categories as $category) {
	if ($category['received']=='1') {
		$dropbox_received_category[]=$category;
	}
	if ($category['sent']=='1') {
		$dropbox_sent_category[]=$category;
	}
}


// ACTIONS
if (!$_GET['view'] OR $_GET['view']=='received' OR $dropbox_cnf['sent_received_tabs']==false) {
	//echo '<h3>'.get_lang('ReceivedFiles').'</h3>';

	// This is for the categories
	if (isset($_GET['view_received_category']) AND $_GET['view_received_category']<>'') {
		$view_dropbox_category_received=Security::remove_XSS($_GET['view_received_category']);
	} else {
		$view_dropbox_category_received=0;
	}


	/* *** Menu Received *** */
	echo '<div class="actions">';
	if ($view_dropbox_category_received<>0) {
		echo get_lang('CurrentlySeeing').': <strong>'.$dropbox_categories[$view_dropbox_category_received]['cat_name'].'</strong> ';
		echo '<img src="../img/folder_up.gif" alt="'.get_lang('Up').'" align="absmiddle" /><a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category=0&amp;view_sent_category='.Security::remove_XSS($_GET['view_sent_category']).'&amp;view='.Security::remove_XSS($_GET['view']).'">'.get_lang('Root')."</a>\n";
        $movelist[0] = 'Root'; // move_received selectbox content
	} else {
	    echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=addreceivedcategory"><img src="../img/folder_new.gif" align=\"absmiddle\"/> '.get_lang('AddNewCategory').'</a>';
	}

	echo '</div>';
}
if ($_GET['view']=='sent' OR $dropbox_cnf['sent_received_tabs']==false) {
	//echo '<h3>'.get_lang('SentFiles').'</h3>';

	// This is for the categories
	if (isset($_GET['view_sent_category']) AND $_GET['view_sent_category']<>'') {
		$view_dropbox_category_sent=$_GET['view_sent_category'];
	} else {
		$view_dropbox_category_sent=0;
	}

	/* *** Menu Sent *** */
	echo '<div class="actions">';
	if ($view_dropbox_category_sent<>0) {
		echo get_lang('CurrentlySeeing').': <strong>'.$dropbox_categories[$view_dropbox_category_sent]['cat_name'].'</strong> ';
		echo '<img src="../img/folder_up.gif" alt="'.get_lang('Up').'" align="absmiddle" /><a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.Security::remove_XSS($_GET['view_received_category']).'&amp;view_sent_category=0&amp;view='.Security::remove_XSS($_GET['view']).'">'.get_lang('Root')."</a>\n";
	} else {
		echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&view=".Security::remove_XSS($_GET['view'])."&amp;action=addsentcategory\"><img src=\"../img/folder_new.gif\" align=\"absmiddle\" /> ".get_lang('AddNewCategory')."</a>\n";
	}
	if (empty($_GET['view_sent_category'])) {
	echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&view=".Security::remove_XSS($_GET['view'])."&amp;action=add\"><img src=\"../img/submit_file.gif\" align=\"absmiddle\"/> ".get_lang('UploadNewFile')."</a>&nbsp;\n";
	}
	echo '</div>';
}


/*
-----------------------------------------------------------
	THE MENU TABS
-----------------------------------------------------------
*/
if ($dropbox_cnf['sent_received_tabs']) {
?>
<div id="tabbed_menu">
	<ul id="tabbed_menu_tabs">
		<li><a href="index.php?<?php echo api_get_cidreq();?>&view=received" <?php if (!$_GET['view'] OR $_GET['view']=='received'){echo 'class="active"';}?> ><?php echo get_lang('ReceivedFiles'); ?></a></li>
		<li><a href="index.php?<?php echo api_get_cidreq();?>&view=sent" <?php if ($_GET['view']=='sent'){echo 'class="active"';}?>><?php echo get_lang('SentFiles'); ?></a></li>
	</ul>
</div>
<?php
}

/*
-----------------------------------------------------------
	RECEIVED FILES
-----------------------------------------------------------
*/
if (!$_GET['view'] OR $_GET['view']=='received' OR $dropbox_cnf['sent_received_tabs']==false) {
	//echo '<h3>'.get_lang('ReceivedFiles').'</h3>';

	// This is for the categories
	if (isset($_GET['view_received_category']) AND $_GET['view_received_category']<>'') {
		$view_dropbox_category_received=$_GET['view_received_category'];
	} else {
		$view_dropbox_category_received=0;
	}

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
	$column_header[] = array(get_lang('Size'), TRUE, '');
	$column_header[] = array(get_lang('Authors'), TRUE, '');
	$column_header[] = array(get_lang('LastResent'), true);
	$column_header[] = array(get_lang('Modify'), FALSE, '', 'nowrap style="text-align: right"');
	$column_header[] = array('RealDate', true);


	// An array with the setting of the columns -> 1: columns that we will show, 0:columns that will be hide
	$column_show[]=1;
	$column_show[]=1;
	$column_show[]=1;
	$column_show[]=1;
	$column_show[]=1;
	$column_show[]=1;
	$column_show[]=1;
	$column_show[]=0;

	// Here we change the way how the colums are going to be sort
	// in this case the the column of LastResent ( 4th element in $column_header) we will be order like the column RealDate
	// because in the column RealDate we have the days in a correct format "2008-03-12 10:35:48"

	$column_order[]=1;
	$column_order[]=2;
	$column_order[]=3;
	$column_order[]=4;
	$column_order[]=7;
	$column_order[]=6;
	$column_order[]=7;
	$column_order[]=8;



	// the content of the sortable table = the received files
	foreach ( $dropbox_person -> receivedWork as $dropbox_file) {
		$dropbox_file_data=array();
		if ($view_dropbox_category_received==$dropbox_file->category) {// we only display the files that are in the category that we are in.
			$dropbox_file_data[]=$dropbox_file->id;

			if (!is_array($_SESSION['_seen'][$_course['id']][TOOL_DROPBOX])) {
				$_SESSION['_seen'][$_course['id']][TOOL_DROPBOX] = array();
			}

			// new icon
			$new_icon='';
			if ($dropbox_file->last_upload_date > $last_access AND !in_array($dropbox_file->id,$_SESSION['_seen'][$_course['id']][TOOL_DROPBOX])) {
				$new_icon='&nbsp;<img src="../img/new.gif" align="absmiddle" alt="'.get_lang('New').'" />';
			}


			$dropbox_file_data[]=build_document_icon_tag('file',$dropbox_file->title);
			$dropbox_file_data[]='<a href="dropbox_download.php?'.api_get_cidreq().'&id='.$dropbox_file->id.'&amp;action=download"><img src="../img/filesave.gif" style="float:right;" alt="'.get_lang('Download').'" title="'.get_lang('Download').'"/></a><a href="dropbox_download.php?'.api_get_cidreq().'&id='.$dropbox_file->id.'">'.$dropbox_file->title.'</a>'.$new_icon.'<br>'.$dropbox_file->description;
			$dropbox_file_data[]=ceil(($dropbox_file->filesize)/1024).' '.get_lang('kB');
			$dropbox_file_data[]=$dropbox_file->author;
			//$dropbox_file_data[]=$dropbox_file->description;

			$dropbox_file_data[]=date_to_str_ago($dropbox_file->last_upload_date).'<br><span class="dropbox_date">'.$dropbox_file->last_upload_date.'</span>';

			$action_icons=check_number_feedback($dropbox_file->id, $number_feedback).' '.get_lang('Feedback').'
									<a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.Security::remove_XSS($_GET['view_received_category']).'&amp;view_sent_category='.Security::remove_XSS($_GET['view_sent_category']).'&amp;view='.Security::remove_XSS($_GET['view']).'&amp;action=viewfeedback&amp;id='.$dropbox_file->id.'"><img src="../img/comment_bubble.gif" alt="'.get_lang('Comment').'" title="'.get_lang('Comment').'" align="absmiddle" /></a>
									<a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.Security::remove_XSS($_GET['view_received_category']).'&amp;view_sent_category='.Security::remove_XSS($_GET['view_sent_category']).'&amp;view='.Security::remove_XSS($_GET['view']).'&amp;action=movereceived&amp;move_id='.$dropbox_file->id.'"><img src="../img/deplacer_fichier.gif" alt="'.get_lang('Move').'" title="'.get_lang('Move').'" align="absmiddle"/></a>
									<a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.Security::remove_XSS($_GET['view_received_category']).'&amp;view_sent_category='.Security::remove_XSS($_GET['view_sent_category']).'&amp;view='.Security::remove_XSS($_GET['view']).'&amp;action=deletereceivedfile&amp;id='.$dropbox_file->id.'" onclick="return confirmation(\''.$dropbox_file->title.'\');"><img src="../img/delete.gif" alt="'.get_lang('Delete').'" title="'.get_lang('Delete').'" align="absmiddle" /></a>';
			//$action_icons='		<a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.$_GET['view_received_category'].'&amp;view_sent_category='.$_GET['view_sent_category'].'&amp;action=movereceived&amp;move_id='.$dropbox_file->id.'"><img src="../img/deplacer.gif"  alt="'.get_lang('Move').'"/></a>
			//						<a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.$_GET['view_received_category'].'&amp;view_sent_category='.$_GET['view_sent_category'].'&amp;action=deletereceivedfile&amp;id='.$dropbox_file->id.'" onclick="return confirmation(\''.$dropbox_file->title.'\');"><img src="../img/delete.gif"  alt="'.get_lang('Delete').'"/></a>';
			// this is a hack to have an additional row in a sortable table

			if ($_GET['action']=='viewfeedback' AND isset($_GET['id']) and is_numeric($_GET['id']) AND $dropbox_file->id==$_GET['id']) {
				$action_icons.="</td></tr>\n"; // ending the normal row of the sortable table
				$action_icons.='<tr><td colspan="2"><a href="index.php?"'.api_get_cidreq().'&view_received_category='.Security::remove_XSS($_GET['view_received_category'])."&amp;view_sent_category=".Security::remove_XSS($_GET['view_sent_category'])."&amp;view=".Security::remove_XSS($_GET['view'])."\">".get_lang('CloseFeedback')."</a></td><td colspan=\"7\">".feedback($dropbox_file->feedback2)."</td>\n</tr>\n";
			}
			$dropbox_file_data[]=$action_icons;
			$action_icons='';

			$dropbox_file_data[]=$dropbox_file->last_upload_date;//date

			$dropbox_data_recieved[]=$dropbox_file_data;
		}
	}



	// the content of the sortable table = the categories (if we are not in the root)
	if ($view_dropbox_category_received==0) {
		foreach ($dropbox_categories as $category) { // note: this can probably be shortened since the categories for the received files are already in the $dropbox_received_category array;
			$dropbox_category_data=array();
			if ($category['received']=='1') {
                $movelist[$category['cat_id']] = $category['cat_name'];
				$dropbox_category_data[]=$category['cat_id']; // this is where the checkbox icon for the files appear
				// the icon of the category
				$dropbox_category_data[]=build_document_icon_tag('folder',$category['cat_name']);
				$dropbox_category_data[]='<a href="dropbox_download.php?'.api_get_cidreq().'&cat_id='.$category['cat_id'].'&amp;action=downloadcategory&amp;sent_received=received"><img width="16" height="16" src="../img/folder_zip.gif" style="float:right;" alt="'.get_lang('Save').'" title="'.get_lang('Save').'"/></a><a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.$category['cat_id'].'&amp;view_sent_category='.Security::remove_XSS($_GET['view_sent_category']).'&amp;view='.Security::remove_XSS($_GET['view']).'">'.$category['cat_name'].'</a>';
				$dropbox_category_data[]='';
				$dropbox_category_data[]='';
				$dropbox_category_data[]='';
				$dropbox_category_data[]='<a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.Security::remove_XSS($_GET['view_received_category']).'&amp;view_sent_category='.Security::remove_XSS($_GET['view_sent_category']).'&amp;view='.Security::remove_XSS($_GET['view']).'&amp;action=editcategory&amp;id='.$category['cat_id'].'"><img src="../img/edit.gif" alt="'.get_lang('Edit').'" title="'.get_lang('Edit').'"/></a>
										  <a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.Security::remove_XSS($_GET['view_received_category']).'&amp;view_sent_category='.Security::remove_XSS($_GET['view_sent_category']).'&amp;view='.Security::remove_XSS($_GET['view']).'&amp;action=deletereceivedcategory&amp;id='.$category['cat_id'].'" onclick="return confirmation(\''.$category['cat_name'].'\');"><img src="../img/delete.gif" alt="'.get_lang('Delete').'" title="'.get_lang('Delete').'"/></a>';
			}
			if (is_array($dropbox_category_data) && count($dropbox_category_data)>0) {
				$dropbox_data_recieved[]=$dropbox_category_data;
			}
		}
	}
	// Displaying the table
	$additional_get_parameters=array('view'=>$_GET['view'], 'view_received_category'=>$_GET['view_received_category'],'view_sent_category'=>$_GET['view_sent_category']);
	$selectlist = array ('delete_received' => get_lang('Delete'),'download_received'=>get_lang('Download'));
    if (is_array($movelist)) {
        foreach ($movelist as $catid => $catname){
        	$selectlist['move_received_'.$catid] = get_lang('Move') . '->'. $catname;
    	}
    }
	Display::display_sortable_config_table($column_header, $dropbox_data_recieved, $sorting_options, $paging_options, $additional_get_parameters,$column_show,$column_order, $selectlist);
}


/*
-----------------------------------------------------------
	SENT FILES
-----------------------------------------------------------
*/
if ($_GET['view']=='sent' OR $dropbox_cnf['sent_received_tabs']==false) {
	//echo '<h3>'.get_lang('SentFiles').'</h3>';

	// This is for the categories
	if (isset($_GET['view_sent_category']) AND $_GET['view_sent_category']<>'') {
		$view_dropbox_category_sent=$_GET['view_sent_category'];
	} else {
		$view_dropbox_category_sent=0;
	}

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
	$column_header[] = array(get_lang('Size'), TRUE, '');
	$column_header[] = array(get_lang('SentTo'), TRUE, '');
	$column_header[] = array(get_lang('LastResent'), TRUE, '');
	$column_header[] = array(get_lang('Modify'), FALSE, '', 'nowrap style="text-align: right"');
	$column_header[] = array('RealDate', FALSE);

	$column_show=array();
	$column_order=array();

	// An array with the setting of the columns -> 1: columns that we will show, 0:columns that will be hide
	$column_show[]=1;
	$column_show[]=1;
	$column_show[]=1;
	$column_show[]=1;
	$column_show[]=1;
	$column_show[]=1;
	$column_show[]=1;
	$column_show[]=0;

	// Here we change the way how the colums are going to be sort
	// in this case the the column of LastResent ( 4th element in $column_header) we will be order like the column RealDate
	// because in the column RealDate we have the days in a correct format "2008-03-12 10:35:48"

	$column_order[]=1;
	$column_order[]=2;
	$column_order[]=3;
	$column_order[]=4;
	$column_order[]=7;
	$column_order[]=6;
	$column_order[]=7;
	$column_order[]=8;

	// the content of the sortable table = the received files
	foreach ( $dropbox_person -> sentWork as $dropbox_file) {
		$dropbox_file_data=array();

		if ($view_dropbox_category_sent==$dropbox_file->category) {
			$dropbox_file_data[]=$dropbox_file->id;
			$dropbox_file_data[]=build_document_icon_tag('file',$dropbox_file->title);
			$dropbox_file_data[]='<a href="dropbox_download.php?'.api_get_cidreq().'&id='.$dropbox_file->id.'&amp;action=download"><img src="../img/filesave.gif" style="float:right;" alt="'.get_lang('Save').'" title="'.get_lang('Save').'"/></a><a href="dropbox_download.php?'.api_get_cidreq().'&id='.$dropbox_file->id.'">'.$dropbox_file->title.'</a><br>'.$dropbox_file->description;
			$dropbox_file_data[]=ceil(($dropbox_file->filesize)/1024).' '.get_lang('kB');
			foreach ($dropbox_file->recipients as $recipient) {
				$receivers_celldata=display_user_link($recipient['user_id'], $recipient['name']).', '.$receivers_celldata;
			}
			$dropbox_file_data[]=$receivers_celldata;
			$dropbox_file_data[]=date_to_str_ago($dropbox_file->last_upload_date).'<br><span class="dropbox_date">'.$dropbox_file->last_upload_date.'</span>';

			//$dropbox_file_data[]=$dropbox_file->author;
			$receivers_celldata='';
			$action_icons=check_number_feedback($dropbox_file->id, $number_feedback).' '.get_lang('Feedback').'
									<a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.Security::remove_XSS($_GET['view_received_category']).'&amp;view_sent_category='.Security::remove_XSS($_GET['view_sent_category']).'&amp;view='.Security::remove_XSS($_GET['view']).'&amp;action=viewfeedback&amp;id='.$dropbox_file->id.'"><img src="../img/comment_bubble.gif" alt="'.get_lang('Comment').'" title="'.get_lang('Comment').'" align="absmiddle" /></a>
									<a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.Security::remove_XSS($_GET['view_received_category']).'&amp;view_sent_category='.Security::remove_XSS($_GET['view_sent_category']).'&amp;view='.Security::remove_XSS($_GET['view']).'&amp;action=movesent&amp;move_id='.$dropbox_file->id.'"><img src="../img/deplacer_fichier.gif" alt="'.get_lang('Move').'" title="'.get_lang('Move').'" align="absmiddle"/></a>
									<a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.Security::remove_XSS($_GET['view_received_category']).'&amp;view_sent_category='.Security::remove_XSS($_GET['view_sent_category']).'&amp;view='.Security::remove_XSS($_GET['view']).'&amp;action=deletesentfile&amp;id='.$dropbox_file->id.'" onclick="return confirmation(\''.$dropbox_file->title.'\');"><img src="../img/delete.gif" alt="'.get_lang('Delete').'" title="'.get_lang('Delete').'" align="absmiddle" /></a>';
			// this is a hack to have an additional row in a sortable table
			if ($_GET['action']=='viewfeedback' AND isset($_GET['id']) and is_numeric($_GET['id']) AND $dropbox_file->id==$_GET['id']) {
				$action_icons.="</td></tr>\n"; // ending the normal row of the sortable table
				$action_icons.="<tr>\n\t<td colspan=\"2\"><a href=\"index.php?".api_get_cidreq()."&view_received_category=".Security::remove_XSS($_GET['view_received_category'])."&amp;view_sent_category=".Security::remove_XSS($_GET['view_sent_category'])."&amp;view=".Security::remove_XSS($_GET['view'])."\">".get_lang('CloseFeedback')."</a></td><td colspan=\"7\">".feedback($dropbox_file->feedback2)."</td>\n</tr>\n";

			}
			$dropbox_file_data[]=$action_icons;
			$dropbox_file_data[]=$dropbox_file->last_upload_date;
			$action_icons='';
			$dropbox_data_sent[]=$dropbox_file_data;
		}
	}

	// the content of the sortable table = the categories (if we are not in the root)
	if ($view_dropbox_category_sent==0) {
		foreach ($dropbox_categories as $category) {
			$dropbox_category_data=array();
			if ($category['sent']=='1') {
				$dropbox_category_data[]=$category['cat_id']; // this is where the checkbox icon for the files appear
				$dropbox_category_data[]=build_document_icon_tag('folder',$category['cat_name']);
				$dropbox_category_data[]='<a href="dropbox_download.php?'.api_get_cidreq().'&cat_id='.$category['cat_id'].'&amp;action=downloadcategory&amp;sent_received=sent"><img width="16" height="16" src="../img/folder_zip.gif" style="float:right;" alt="'.get_lang('Save').'" title="'.get_lang('Save').'"/></a><a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.Security::remove_XSS($_GET['view_received_category']).'&amp;view_sent_category='.$category['cat_id'].'&amp;view='.Security::remove_XSS($_GET['view']).'">'.$category['cat_name'].'</a>';
				//$dropbox_category_data[]='';
				$dropbox_category_data[]='';
				//$dropbox_category_data[]='';
				$dropbox_category_data[]='';
				$dropbox_category_data[]='';
				$dropbox_category_data[]='<a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.Security::remove_XSS($_GET['view_received_category']).'&amp;view_sent_category='.Security::remove_XSS($_GET['view_sent_category']).'&amp;view='.Security::remove_XSS($_GET['view']).'&amp;action=editcategory&id='.$category['cat_id'].'"><img src="../img/edit.gif" alt="'.get_lang('Edit').'" title="'.get_lang('Edit').'"/></a>
										  <a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.Security::remove_XSS($_GET['view_received_category']).'&amp;view_sent_category='.Security::remove_XSS($_GET['view_sent_category']).'&amp;view='.Security::remove_XSS($_GET['view']).'&amp;action=deletesentcategory&amp;id='.$category['cat_id'].'" onclick="return confirmation(\''.$category['cat_name'].'\');"><img src="../img/delete.gif" alt="'.get_lang('Delete').'" title="'.get_lang('Delete').'"/></a>';
			}
			if (is_array($dropbox_category_data) && count($dropbox_category_data)>0) {
				$dropbox_data_sent[]=$dropbox_category_data;
			}
		}
	}
	// Displaying the table
	$additional_get_parameters=array('view'=>Security::remove_XSS($_GET['view']), 'view_received_category'=>Security::remove_XSS($_GET['view_received_category']),'view_sent_category'=>Security::remove_XSS($_GET['view_sent_category']));
	Display::display_sortable_config_table($column_header, $dropbox_data_sent, $sorting_options, $paging_options, $additional_get_parameters,$column_show,$column_order, array ('delete_received' => get_lang('Delete'),'download_received'=>get_lang('Download')));
  }
}
Display::display_footer();
