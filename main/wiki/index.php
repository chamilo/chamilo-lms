<?php

/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos SPRL
	Copyright (c) 2003 Ghent University (UGent)

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
*	The Dokeos wiki is a further development of the CoolWiki plugin.
*
*	@Author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
* 	@Author Juan Carlos Raña <herodoto@telefonica.net>
*	@Copyright Ghent University
*	@Copyright Patrick Cool
*
* 	@package dokeos.wiki
*/


// name of the language file that needs to be included
$language_file = 'wiki';

// security
if(isset($_GET['id_session'])) {
	$_SESSION['id_session'] = intval($_GET['id_session']);
}

// including the global dokeos file
include('../inc/global.inc.php');

// section (for the tabs)
$this_section=SECTION_COURSES;

// including additional library scripts

require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'text.lib.php';
require_once api_get_path(LIBRARY_PATH).'security.lib.php';
require_once api_get_path(INCLUDE_PATH).'lib/mail.lib.inc.php';
require_once api_get_path(INCLUDE_PATH).'conf/mail.conf.php';
require_once api_get_path(LIBRARY_PATH).'sortabletable.class.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require_once 'wiki.inc.php';

// additional style information
$htmlHeadXtra[] ='<link rel="stylesheet" type="text/css" href="'.api_get_path(WEB_CODE_PATH).'wiki/css/default.css"/>';

// javascript for advanced parameters menu
$htmlHeadXtra[] = '<script type="text/javascript" language="javascript">
function advanced_parameters() {
	if(document.getElementById(\'options\').style.display == \'none\') {
					document.getElementById(\'options\').style.display = \'block\';
					document.getElementById(\'plus_minus\').innerHTML=\'&nbsp;'.Display::return_icon('div_hide.gif',get_lang('Hide'),array('style'=>'vertical-align:middle')).'&nbsp;'.get_lang('AdvancedParameters').'\';
	} else {
					document.getElementById(\'options\').style.display = \'none\';
					document.getElementById(\'plus_minus\').innerHTML=\'&nbsp;'.Display::return_icon('div_show.gif',get_lang('Show'),array('style'=>'vertical-align:middle')).'&nbsp;'.get_lang('AdvancedParameters').'\';
	}
}
</script>';


// Database table definition
$tbl_wiki = Database::get_course_table(TABLE_WIKI);
$tbl_wiki_discuss = Database::get_course_table(TABLE_WIKI_DISCUSS);
$tbl_wiki_mailcue = Database::get_course_table(TABLE_WIKI_MAILCUE);
$tbl_wiki_conf = Database::get_course_table(TABLE_WIKI_CONF);
/*
-----------------------------------------------------------
Constants and variables
-----------------------------------------------------------
*/
$tool_name = get_lang('Wiki');

$MonthsLong = array (get_lang("JanuaryLong"), get_lang("FebruaryLong"), get_lang("MarchLong"), get_lang("AprilLong"), get_lang("MayLong"), get_lang("JuneLong"), get_lang("JulyLong"), get_lang("AugustLong"), get_lang("SeptemberLong"), get_lang("OctoberLong"), get_lang("NovemberLong"), get_lang("DecemberLong"));

//condition for the session
	$session_id = api_get_session_id();
	$condition_session = api_get_session_condition($session_id);

/*
----------------------------------------------------------
ACCESS
-----------------------------------------------------------
*/
api_protect_course_script();
api_block_anonymous_users();

/*
-----------------------------------------------------------
TRACKING
-----------------------------------------------------------
*/
event_access_tool(TOOL_WIKI);

/*
-----------------------------------------------------------
HEADER & TITLE
-----------------------------------------------------------
*/
// If it is a group wiki then the breadcrumbs will be different.
if ($_SESSION['_gid'] OR $_GET['group_id']) {

	if (isset($_SESSION['_gid'])) {
		$_clean['group_id']=(int)$_SESSION['_gid'];
	}
	if (isset($_GET['group_id'])) {
		$_clean['group_id']=(int)Database::escape_string($_GET['group_id']);
	}

	$group_properties  = GroupManager :: get_group_properties($_clean['group_id']);
	$interbreadcrumb[] = array ("url" => "../group/group.php", "name" => get_lang('Groups'));
	$interbreadcrumb[] = array ("url"=>"../group/group_space.php?gidReq=".$_SESSION['_gid'], "name"=> get_lang('GroupSpace').' ('.$group_properties['name'].')');

	$add_group_to_title = ' ('.$group_properties['name'].')';
	$groupfilter='group_id="'.$_clean['group_id'].'"';

	//ensure this tool in groups whe it's private or deactivated
	if 	($group_properties['wiki_state']==0)
	{
		echo api_not_allowed();
	}
	elseif ($group_properties['wiki_state']==2)
	{
 		if (!api_is_allowed_to_edit(false,true) and !GroupManager :: is_user_in_group($_user['user_id'], $_SESSION['_gid']))
		{
			echo api_not_allowed();
		}
	}

}
else
{
	$groupfilter='group_id=0';
}

Display::display_header($tool_name, 'Wiki');

$is_allowed_to_edit = api_is_allowed_to_edit(false,true);

//api_display_tool_title($tool_name.$add_group_to_title);

/*
-----------------------------------------------------------
INITIALISATION
-----------------------------------------------------------
*/
//the page we are dealing with
if (!isset($_GET['title'])){

	$page='index';
}
else
{
	$page=Security::remove_XSS($_GET['title']);
}

// some titles are not allowed
// $not_allowed_titles=array("Index", "RecentChanges","AllPages", "Categories"); //not used for now

/*
==============================================================================
MAIN CODE
==============================================================================
*/

// Tool introduction
Display::display_introduction_section(TOOL_WIKI);


/*
-----------------------------------------------------------
  			ACTIONS
-----------------------------------------------------------
*/


//release of blocked pages to prevent concurrent editions
$sql='SELECT * FROM '.$tbl_wiki.'WHERE is_editing!="0" '.$condition_session;
$result=Database::query($sql,__LINE__,__FILE__);
while ($is_editing_block=Database::fetch_array($result))
{
	$max_edit_time=1200; // 20 minutes
	$timestamp_edit=strtotime($is_editing_block['time_edit']);
	$time_editing=time()-$timestamp_edit;

	//first prevent concurrent users and double version
	if($is_editing_block['is_editing']==$_user['user_id'])
	{
		$_SESSION['_version']=$is_editing_block['version'];
	}
	else
	{
		unset ( $_SESSION['_version'] );
	}
	//second checks if has exceeded the time that a page may be available or if a page was edited and saved by its author
	if ($time_editing>$max_edit_time || ($is_editing_block['is_editing']==$_user['user_id'] && $_GET['action']!='edit'))
	{
		$sql='UPDATE '.$tbl_wiki.' SET is_editing="0", time_edit="0000-00-00 00:00:00" WHERE is_editing="'.$is_editing_block['is_editing'].'" '.$condition_session;
		Database::query($sql,__FILE__,__LINE__);
	}

}


// saving a change
if (isset($_POST['SaveWikiChange']) AND $_POST['title']<>'')
{

	if(empty($_POST['title']))
	{
		Display::display_error_message(get_lang("NoWikiPageTitle"));
	}
	elseif(!double_post($_POST['wpost_id']))
	{
		//double post
	}
	elseif ($_POST['version']!='' && $_SESSION['_version']!=0 && $_POST['version']!=$_SESSION['_version'])
	{
		//prevent concurrent users and double version
		Display::display_error_message(get_lang("EditedByAnotherUser"));
	}
	else
	{
		$return_message=save_wiki();
		Display::display_confirmation_message($return_message, false);
	}
}

//saving a new wiki entry
if (isset($_POST['SaveWikiNew']))
{
	if(empty($_POST['title']))
	{
		Display::display_error_message(get_lang("NoWikiPageTitle"));
	}
	elseif (strtotime(get_date_from_select('startdate_assig')) > strtotime(get_date_from_select('enddate_assig')))
	{
		Display::display_error_message(get_lang("EndDateCannotBeBeforeTheStartDate"));
	}
	elseif(!double_post($_POST['wpost_id']))
	{
		//double post
	}
	else
	{
	   $_clean['assignment']=Database::escape_string($_POST['assignment']); // for mode assignment
	   if ($_clean['assignment']==1)
	   {
	      	auto_add_page_users($_clean['assignment']);
	   }
	   else
	   {
			$return_message=save_new_wiki();
			Display::display_confirmation_message($return_message, false);
			$page=urlencode(Security::remove_XSS($_POST['reflink']));
	   }
	}
}


// check last version
if ($_GET['view'])
{
	$sql='SELECT * FROM '.$tbl_wiki.'WHERE id="'.Database::escape_string($_GET['view']).'"'; //current view
		$result=Database::query($sql,__LINE__,__FILE__);
		$current_row=Database::fetch_array($result);

	$sql='SELECT * FROM '.$tbl_wiki.'WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.$condition_session.' ORDER BY id DESC'; //last version
		$result=Database::query($sql,__LINE__,__FILE__);
		$last_row=Database::fetch_array($result);

	if ($_GET['view']<$last_row['id'])
	{
	   $message= '<center>'.get_lang('NoAreSeeingTheLastVersion').'<br /> '.get_lang("Version").' (<a href="index.php?cidReq='.$_course[id].'&action=showpage&amp;title='.$current_row['reflink'].'&view='.Security::remove_XSS($_GET['view']).'&group_id='.$current_row['group_id'].'" title="'.get_lang('CurrentVersion').'">'.$current_row['version'].'</a> / <a href="index.php?cidReq='.$_course[id].'&action=showpage&amp;title='.$last_row['reflink'].'&group_id='.$last_row['group_id'].'" title="'.get_lang('LastVersion').'">'.$last_row['version'].'</a>) <br />'.get_lang("ConvertToLastVersion").': <a href="index.php?cidReq='.$_course[id].'&action=restorepage&amp;title='.$last_row['reflink'].'&view='.Security::remove_XSS($_GET['view']).'">'.get_lang("Restore").'</a></center>';

	   Display::display_warning_message($message,false);
	}

	///restore page
	if ($_GET['action']=='restorepage')
	{
		//Only teachers and platform admin can edit the index page. Only teachers and platform admin can edit an assignment teacher
		if(($current_row['reflink']=='index' || $current_row['reflink']=='' || $current_row['assignment']==1) && (!api_is_allowed_to_edit(false,true) && $_clean['group_id']==0))
		{
			Display::display_normal_message(get_lang('OnlyEditPagesCourseManager'));
		}
		else
		{
			$PassEdit=false;

			//check if is a wiki group
			if($current_row['group_id']!=0)
			{
				//Only teacher, platform admin and group members can edit a wiki group
				if(api_is_allowed_to_edit(false,true) || api_is_platform_admin() || GroupManager :: is_user_in_group($_user['user_id'],$_SESSION['_gid']))
				{
					$PassEdit=true;
				}
				else
				{
					Display::display_normal_message(get_lang('OnlyEditPagesGroupMembers'));
				}
			}
			else
			{
				$PassEdit=true;
			}

			// check if is an assignment
			if(stripslashes($current_row['assignment'])==1)
			{
				Display::display_normal_message(get_lang('EditAssignmentWarning'));
				$icon_assignment='<img src="../img/wiki/assignment.gif" title="'.get_lang('AssignmentDescExtra').'" alt="'.get_lang('AssignmentDescExtra').'" />';
			}
			elseif(stripslashes($current_row['assignment'])==2)
			{
				$icon_assignment='<img src="../img/wiki/works.gif" title="'.get_lang('AssignmentWorkExtra').'" alt="'.get_lang('AssignmentWorkExtra').'" />';
				if((api_get_user_id()==$current_row['user_id'])==false)
				{
					if(api_is_allowed_to_edit(false,true) || api_is_platform_admin())
					{
						$PassEdit=true;
					}
					else
					{
						Display::display_warning_message(get_lang('LockByTeacher'));
						$PassEdit=false;
					}
				}
				else
				{
					$PassEdit=true;
				}
			}

			if($PassEdit) //show editor if edit is allowed
			{
				if ($row['editlock']==1 && (api_is_allowed_to_edit(false,true)==false || api_is_platform_admin()==false))
				{
					   Display::display_normal_message(get_lang('PageLockedExtra'));
				}
				else
				{
					if($last_row['is_editing']!=0 && $last_row['is_editing']!=$_user['user_id'])
					{
						//checking for concurrent users
						$timestamp_edit=strtotime($last_row['time_edit']);
						$time_editing=time()-$timestamp_edit;
						$max_edit_time=1200; // 20 minutes
						$rest_time=$max_edit_time-$time_editing;

						$userinfo=Database::get_user_info_from_id($last_row['is_editing']);

						$is_being_edited= get_lang('ThisPageisBeginEditedBy').' <a href=../user/userInfo.php?uInfo='.$userinfo['user_id'].'>'.api_get_person_name($userinfo['firstname'], $userinfo['lastname']).'</a>. '.get_lang('ThisPageisBeginEditedTryLater').' '.date( "i",$rest_time).' '.get_lang('MinMinutes').'';
						Display::display_normal_message($is_being_edited, false);

					}
					else
					{
					 	Display::display_confirmation_message(restore_wikipage($current_row['page_id'], $current_row['reflink'], $current_row['title'], $current_row['content'], $current_row['group_id'], $current_row['assignment'], $current_row['progress'], $current_row['version'], $last_row['version'], $current_row['linksto']).': <a href="index.php?cidReq='.$_course[id].'&action=showpage&amp;title='.$last_row['reflink'].'&group_id='.$last_row['group_id'].'">'.$last_row['title'].'</a>',false);
					}
				}
			}
		}
	}
}


if ($_GET['action']=='deletewiki'){

	if(api_is_allowed_to_edit(false,true) || api_is_platform_admin())
 	{
		if ($_GET['delete'] == 'yes')
		{
			$return_message=delete_wiki();
			Display::display_confirmation_message($return_message);
	    }
	 }
}


if ($_GET['action']=='discuss' && $_POST['Submit'])
{
   		Display::display_confirmation_message(get_lang('CommentAdded'));
}


/*
-----------------------------------------------------------
WIKI WRAPPER
-----------------------------------------------------------
*/

echo "<div id='wikiwrapper'>";

/** Actions bar (= action of the wiki tool, not of the page)**/
echo '<div id="menuwiki">';
echo '<table width="210">';
echo '<tr>';
echo '<td>';
	echo get_lang('Menu');
echo '</td>';
echo '</tr>';
echo '<tr>';
echo '<td>';
	///menu home
	echo '<ul><li><a href="index.php?cidReq='.$_course[id].'&action=show&amp;title=index&group_id='.$_clean['group_id'].'"'.is_active_navigation_tab('show').'>'.get_lang('HomeWiki').'</a></li>';
	if ( api_is_allowed_to_session_edit(false,true) ) {
		//menu add page
		echo '<li><a href="index.php?cidReq='.$_course[id].'&action=addnew&group_id='.$_clean['group_id'].'"'.is_active_navigation_tab('addnew').'>'.get_lang('AddNew').'</a> ';
	}

	if(api_is_allowed_to_edit(false,true) || api_is_platform_admin())
	{
		// page action: enable or disable the adding of new pages
		if (check_addnewpagelock()==0)
		{
			$protect_addnewpage= '<img src="../img/wiki/lockadd.gif" title="'.get_lang('AddOptionProtected').'" alt="'.get_lang('AddOptionProtected').'" width="8" height="8" />';
			$lock_unlock_addnew='unlockaddnew';						
		}
		else
		{					
			$protect_addnewpage= '<img src="../img/wiki/unlockadd.gif" title="'.get_lang('AddOptionUnprotected').'" alt="'.get_lang('AddOptionUnprotected').'" width="8" height="8" />';
			$lock_unlock_addnew='lockaddnew';
		}
	}

		echo '<a href="index.php?action=show&amp;actionpage='.$lock_unlock_addnew.'&amp;title='.$page.'">'.$protect_addnewpage.'</a></li>';

	///menu find
	echo '<li><a href="index.php?cidReq='.$_course[id].'&action=searchpages&group_id='.$_clean['group_id'].'"'.is_active_navigation_tab('searchpages').'>'.get_lang('SearchPages').'</a></li>';
	///menu all pages
	echo '<li><a href="index.php?cidReq='.$_course[id].'&action=allpages&group_id='.$_clean['group_id'].'"'.is_active_navigation_tab('allpages').'>'.get_lang('AllPages').'</a></li>';
	///menu recent changes
	echo '<li><a href="index.php?cidReq='.$_course[id].'&action=recentchanges&group_id='.$_clean['group_id'].'"'.is_active_navigation_tab('recentchanges').'>'.get_lang('RecentChanges').'</a></li>';
	///menu delete all wiki
	if(api_is_allowed_to_edit(false,true) || api_is_platform_admin())
	{
			echo '<li><a href="index.php?action=deletewiki&amp;title='.$page.'"'.is_active_navigation_tab('deletewiki').'>'.get_lang('DeleteWiki').'</a></li>';
	}
	///menu more

	echo '<li><a href="index.php?action=more&amp;title='.$page.'"'.is_active_navigation_tab('more').'>'.get_lang('More').'</a></li>';

	echo '</ul>';
echo '</td>';
echo '</tr>';
echo '</table>';
echo '</div>';


/*
-----------------------------------------------------------
MAIN WIKI AREA
-----------------------------------------------------------
*/

echo "<div id='mainwiki'>";
/** menuwiki (= actions of the page, not of the wiki tool) **/
if (!in_array($_GET['action'], array('addnew', 'searchpages', 'allpages', 'recentchanges', 'deletewiki', 'more', 'mactiveusers', 'mvisited', 'mostchanged', 'orphaned', 'wanted')))
{
	echo "<div class='actions'>";

	//menu show page
	echo '<a href="index.php?cidReq='.$_course[id].'&action=showpage&amp;title='.$page.'&group_id='.$_clean['group_id'].'"'.is_active_navigation_tab('showpage').'>'.Display::display_icon('lp_document.png',get_lang('ShowThisPage')).' '.get_lang('Page').'</a>';

	if (api_is_allowed_to_session_edit(false,true) ) {
		//menu edit page
		echo '<a href="index.php?cidReq='.$_course[id].'&action=edit&amp;title='.$page.'&group_id='.$_clean['group_id'].'"'.is_active_navigation_tab('edit').'>'.Display::display_icon('lp_quiz.png',get_lang('EditThisPage')).' '.get_lang('EditPage').'</a>';

		//menu discuss page
		echo '<a href="index.php?action=discuss&amp;title='.$page.'"'.is_active_navigation_tab('discuss').'>'.Display::display_icon('comment_bubble.gif',get_lang('DiscussThisPage')).' '.get_lang('Discuss').'</a>';
 	}

	//menu history
	echo '<a href="index.php?cidReq='.$_course[id].'&action=history&amp;title='.$page.'&group_id='.$_clean['group_id'].'"'.is_active_navigation_tab('history').'>'.Display::display_icon('history.gif',get_lang('ShowPageHistory')).' '.get_lang('History').'</a>';
	//menu linkspages
	echo '<a href="index.php?action=links&amp;title='.$page.'"'.is_active_navigation_tab('links').'>'.Display::display_icon('lp_link.png',get_lang('ShowLinksPages')).' '.get_lang('LinksPages').'</a>';

	//menu delete wikipage
	if(api_is_allowed_to_edit(false,true) || api_is_platform_admin())
	{
		echo '<a href="index.php?action=delete&amp;title='.$page.'"'.is_active_navigation_tab('delete').'>'.Display::display_icon('delete.gif',get_lang('DeleteThisPage')).' '.get_lang('Delete').'</a>';
	}
	echo '</div>';
}

/////////////////////// more options /////////////////////// Juan Carlos Raña Trabado

if ($_GET['action']=='more')
{

	echo '<div class="actions">'.get_lang('More').'</div>';

	if(api_is_allowed_to_edit(false,true) || api_is_platform_admin())
	{
		//TODO: config area and private stats

	}

	echo '<table border="0">';
    echo '<tr>';
    echo '<td>';
	echo '<ul>';
		//Submenu Most active users
		echo '<li><a href="index.php?cidReq='.$_course[id].'&action=mactiveusers&group_id='.$_clean['group_id'].'">'.get_lang('MostActiveUsers').'</a></li>';
		//Submenu Most visited pages
		echo '<li><a href="index.php?cidReq='.$_course[id].'&action=mvisited&group_id='.$_clean['group_id'].'">'.get_lang('MostVisitedPages').'</a></li>';
		//Submenu Most changed pages
		echo '<li><a href="index.php?cidReq='.$_course[id].'&action=mostchanged&group_id='.$_clean['group_id'].'">'.get_lang('MostChangedPages').'</a></li>';
	echo '</ul>';
	echo '</td>';
  	echo '<td>';
  	echo '<ul>';
	   //Submenu Orphaned pages
		echo '<li><a href="index.php?cidReq='.$_course[id].'&action=orphaned&group_id='.$_clean['group_id'].'">'.get_lang('OrphanedPages').'</a></li>';
		//Submenu Wanted pages
		echo '<li><a href="index.php?cidReq='.$_course[id].'&action=wanted&group_id='.$_clean['group_id'].'">'.get_lang('WantedPages').'</a></li>';
	echo '</ul>';
   	echo'</td>';
  	echo '</tr>';
	echo '</table>';


	//Submenu Most linked pages
	//echo '<li><a href="index.php?cidReq='.$_course[id].'&action=mostlinked&group_id='.$_clean['group_id'].'">'.get_lang('MostLinkedPages').'</a></li>';//TODO:

	//Submenu Dead end pages
	//echo '<li><a href="index.php?cidReq='.$_course[id].'&action=deadend&group_id='.$_clean['group_id'].'">'.get_lang('DeadEndPages').'</a></li>';//TODO:

	//Submenu Most new pages (not versions)
	//echo '<li><a href="index.php?cidReq='.$_course[id].'&action=mnew&group_id='.$_clean['group_id'].'">'.get_lang('MostNewPages').'</a></li>';//TODO:

	//Submenu Most long pages
	//echo '<li><a href="index.php?cidReq='.$_course[id].'&action=mnew&group_id='.$_clean['group_id'].'">'.get_lang('MostLongPages').'</a></li>';//TODO:

	//Submenu Protected pages
	//echo '<li><a href="index.php?cidReq='.$_course[id].'&action=protected&group_id='.$_clean['group_id'].'">'.get_lang('ProtectedPages').'</a></li>';//TODO:

	//Submenu Hidden pages
	//echo '<li><a href="index.php?cidReq='.$_course[id].'&action=hidden&group_id='.$_clean['group_id'].'">'.get_lang('HiddenPages').'</a></li>';//TODO:

	//Submenu Most discuss pages
	//echo '<li><a href="index.php?cidReq='.$_course[id].'&action=mdiscuss&group_id='.$_clean['group_id'].'">'.get_lang('MostDiscussPages').'</a></li>';//TODO:

	//Submenu Best scored pages
	//echo '<li><a href="index.php?cidReq='.$_course[id].'&action=mscored&group_id='.$_clean['group_id'].'">'.get_lang('BestScoredPages').'</a></li>';//TODO:

	//Submenu Pages with more progress
	//echo '<li><a href="index.php?cidReq='.$_course[id].'&action=mprogress&group_id='.$_clean['group_id'].'">'.get_lang('MProgressPages').'</a></li>';//TODO:

	//Submenu Most active users in discuss
	//echo '<li><a href="index.php?cidReq='.$_course[id].'&action=mactiveusers&group_id='.$_clean['group_id'].'">'.get_lang('MostDiscussUsers').'</a></li>';//TODO:

	//Submenu Random page
	//echo '<li><a href="index.php?cidReq='.$_course[id].'&action=mrandom&group_id='.$_clean['group_id'].'">'.get_lang('RandomPage').'</a></li>';//TODO:

}

/////////////////////// Most active users /////////////////////// Juan Carlos Raña Trabado

if ($_GET['action']=='mactiveusers')
{
	echo '<div class="actions">'.get_lang('MostActiveUsers').'</div>';

	$sql='SELECT *, COUNT(*) AS NUM_EDIT FROM '.$tbl_wiki.'  WHERE  '.$groupfilter.$condition_session.' GROUP BY user_id';
	$allpages=Database::query($sql,__FILE__,__LINE__);

	//show table
	if (Database::num_rows($allpages) > 0)
	{
		$row = array ();
		while ($obj = Database::fetch_object($allpages))
		{
			$userinfo=Database::get_user_info_from_id($obj->user_id);
			$row = array ();

			$row[] = $obj->user_id <>0 ? '<a href="../user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.api_get_person_name($userinfo['firstname'], $userinfo['lastname']).'</a><a href="'.api_get_self().'?cidReq='.$_course[id].'&action=usercontrib&user_id='.urlencode($row['user_id']).'&group_id='.Security::remove_XSS($_GET['group_id']).'"></a>' : get_lang('Anonymous').' ('.$obj->user_ip.')';
			$row[] ='<a href="'.api_get_self().'?cidReq='.$_course[id].'&action=usercontrib&user_id='.urlencode($obj->user_id).'&group_id='.Security::remove_XSS($_GET['group_id']).'">'.$obj->NUM_EDIT.'</a>';
			$rows[] = $row;
		}

		$table = new SortableTableFromArrayConfig($rows,1,10,'MostActiveUsersA_table','','','DESC');
		$table->set_additional_parameters(array('cidReq' =>Security::remove_XSS($_GET['cidReq']),'action'=>Security::remove_XSS($_GET['action']),'group_id'=>Security::remove_XSS($_GET['group_id'])));
		$table->set_header(0,get_lang('Author'), true, array ('style' => 'width:30px;'));
		$table->set_header(1,get_lang('Contributions'), true);
		$table->display();
	}
}


/////////////////////// User contributions /////////////////////// Juan Carlos Raña Trabado

if ($_GET['action']=='usercontrib')
{
	$userinfo=Database::get_user_info_from_id(Security::remove_XSS($_GET['user_id']));

	echo '<div class="actions">'.get_lang('UserContributions').': <a href="../user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.api_get_person_name($userinfo['firstname'], $userinfo['lastname']).'</a><a href="'.api_get_self().'?cidReq='.$_course[id].'&action=usercontrib&user_id='.urlencode($row['user_id']).'&group_id='.Security::remove_XSS($_GET['group_id']).'"></a></div>';


	if(api_is_allowed_to_edit(false,true) || api_is_platform_admin()) //only by professors if page is hidden
	{
		$sql='SELECT * FROM '.$tbl_wiki.'  WHERE  '.$groupfilter.$condition_session.' AND user_id="'.Security::remove_XSS($_GET['user_id']).'"';
	}
	else
	{
		$sql='SELECT * FROM '.$tbl_wiki.'  WHERE  '.$groupfilter.$condition_session.' AND user_id="'.Security::remove_XSS($_GET['user_id']).'" AND visibility=1';
	}

	$allpages=Database::query($sql,__FILE__,__LINE__);

	//show table
	if (Database::num_rows($allpages) > 0)
	{
		$row = array ();
		while ($obj = Database::fetch_object($allpages))
		{
			//get author
			$userinfo=Database::get_user_info_from_id($obj->user_id);

			//get time
			$year 	 = substr($obj->dtime, 0, 4);
			$month	 = substr($obj->dtime, 5, 2);
			$day 	 = substr($obj->dtime, 8, 2);
			$hours   = substr($obj->dtime, 11,2);
			$minutes = substr($obj->dtime, 14,2);
			$seconds = substr($obj->dtime, 17,2);

			//get type assignment icon
			if($obj->assignment==1)
			{
				$ShowAssignment='<img src="../img/wiki/assignment.gif" title="'.get_lang('AssignmentDesc').'" alt="'.get_lang('AssignmentDesc').'" />';
			}
			elseif ($obj->assignment==2)
			{
				$ShowAssignment='<img src="../img/wiki/works.gif" title="'.get_lang('AssignmentWork').'" alt="'.get_lang('AssignmentWork').'" />';
			}
			elseif ($obj->assignment==0)
			{
				$ShowAssignment='<img src="../img/wiki/trans.gif" />';
			}

			$row = array ();
			$row[] = $year.'-'.$month.'-'.$day.' '.$hours.":".$minutes.":".$seconds;
			$row[] =$ShowAssignment;

			$row[] = '<a href="'.api_get_self().'?cidReq='.$_course[id].'&action=showpage&title='.urlencode($obj->reflink).'&view='.$obj->id.'&group_id='.Security::remove_XSS($_GET['group_id']).'">'.$obj->title.'</a>';
			$row[] =$obj->version;
			$row[] =$obj->comment;
			//$row[] = api_strlen($obj->comment)>30 ? api_substr($obj->comment,0,30).'...' : $obj->comment;
			$row[] =$obj->progress.' %';
			$row[] =$obj->score;
			//if(api_is_allowed_to_edit() || api_is_platform_admin())
			//{
				//$row[] =$obj->user_ip;
			//}

			$rows[] = $row;

		}

		$table = new SortableTableFromArrayConfig($rows,2,10,'UsersContributions_table','','','ASC');
		$table->set_additional_parameters(array('cidReq' =>Security::remove_XSS($_GET['cidReq']),'action'=>Security::remove_XSS($_GET['action']),'user_id'=>Security::remove_XSS($_GET['user_id']),'group_id'=>Security::remove_XSS($_GET['group_id'])));

		$table->set_header(0,get_lang('Date'), true, array ('style' => 'width:200px;'));
		$table->set_header(1,get_lang('Type'), true, array ('style' => 'width:30px;'));
		$table->set_header(2,get_lang('Title'), true, array ('style' => 'width:200px;'));
		$table->set_header(3,get_lang('Version'), true, array ('style' => 'width:30px;'));
		$table->set_header(4,get_lang('Comment'), true, array ('style' => 'width:200px;'));
		$table->set_header(5,get_lang('Progress'), true, array ('style' => 'width:30px;'));
		$table->set_header(6,get_lang('Rating'), true, array ('style' => 'width:30px;'));
		//if(api_is_allowed_to_edit() || api_is_platform_admin())
		//{
			//$table->set_header(7,get_lang('IP'), true, array ('style' => 'width:30px;'));
		//}

		$table->display();
	}
}

/////////////////////// Most changed pages /////////////////////// Juan Carlos Raña Trabado

if ($_GET['action']=='mostchanged')
{
	echo '<div class="actions">'.get_lang('MostChangedPages').'</div>';


	if(api_is_allowed_to_edit(false,true) || api_is_platform_admin()) //only by professors if page is hidden
	{
		$sql='SELECT *, MAX(version) AS MAX FROM '.$tbl_wiki.'  WHERE  '.$groupfilter.$condition_session.' GROUP BY reflink';
	}
	else
	{
		$sql='SELECT *, MAX(version) AS MAX FROM '.$tbl_wiki.'  WHERE  '.$groupfilter.$condition_session.' AND visibility=1 GROUP BY reflink';
	}

	$allpages=Database::query($sql,__FILE__,__LINE__);

	//show table
	if (Database::num_rows($allpages) > 0)
	{
		$row = array ();
		while ($obj = Database::fetch_object($allpages))
		{
			//get type assignment icon
			if($obj->assignment==1)
			{
				$ShowAssignment='<img src="../img/wiki/assignment.gif" title="'.get_lang('AssignmentDesc').'" alt="'.get_lang('AssignmentDesc').'" />';
			}
			elseif ($obj->assignment==2)
			{
				$ShowAssignment='<img src="../img/wiki/works.gif" title="'.get_lang('AssignmentDesc').'" alt="'.get_lang('AssignmentWork').'" />';
			}
			elseif ($obj->assignment==0)
			{
				$ShowAssignment='<img src="../img/wiki/trans.gif" />';
			}

			$row = array ();
			$row[] =$ShowAssignment;
			$row[] = '<a href="'.api_get_self().'?cidReq='.$_course[id].'&action=showpage&title='.urlencode($obj->reflink).'&group_id='.Security::remove_XSS($_GET['group_id']).'">'.$obj->title.'</a>';
			$row[] = $obj->MAX;
			$rows[] = $row;
		}

		$table = new SortableTableFromArrayConfig($rows,2,10,'MostChangedPages_table','','','DESC');
		$table->set_additional_parameters(array('cidReq' =>Security::remove_XSS($_GET['cidReq']),'action'=>Security::remove_XSS($_GET['action']),'group_id'=>Security::remove_XSS($_GET['group_id'])));
		$table->set_header(0,get_lang('Type'), true, array ('style' => 'width:30px;'));
		$table->set_header(1,get_lang('Title'), true);
		$table->set_header(2,get_lang('Changes'), true);
		$table->display();
	}

}

/////////////////////// Most visited pages /////////////////////// Juan Carlos Raña Trabado

if ($_GET['action']=='mvisited')
{
	echo '<div class="actions">'.get_lang('MostVisitedPages').'</div>';

	if(api_is_allowed_to_edit(false,true) || api_is_platform_admin()) //only by professors if page is hidden
	{
		$sql='SELECT *, SUM(hits) AS tsum FROM '.$tbl_wiki.'  WHERE  '.$groupfilter.$condition_session.' GROUP BY reflink';
	}
	else
	{
		$sql='SELECT *, SUM(hits) AS tsum FROM '.$tbl_wiki.'  WHERE  '.$groupfilter.$condition_session.' AND visibility=1 GROUP BY reflink';
	}

	$allpages=Database::query($sql,__FILE__,__LINE__);

	//show table
	if (Database::num_rows($allpages) > 0)
	{
		$row = array ();
		while ($obj = Database::fetch_object($allpages))
		{
			//get type assignment icon
			if($obj->assignment==1)
			{
				$ShowAssignment='<img src="../img/wiki/assignment.gif" title="'.get_lang('AssignmentDesc').'" alt="'.get_lang('AssignmentDesc').'" />';
			}
			elseif ($obj->assignment==2)
			{
				$ShowAssignment='<img src="../img/wiki/works.gif" title="'.get_lang('AssignmentWork').'" alt="'.get_lang('AssignmentWork').'" />';
			}
			elseif ($obj->assignment==0)
			{
				$ShowAssignment='<img src="../img/wiki/trans.gif" />';
			}

			$row = array ();
			$row[] =$ShowAssignment;
			$row[] = '<a href="'.api_get_self().'?cidReq='.$_course[id].'&action=showpage&title='.urlencode($obj->reflink).'&group_id='.Security::remove_XSS($_GET['group_id']).'">'.$obj->title.'</a>';
			$row[] = $obj->tsum;
			$rows[] = $row;
		}

		$table = new SortableTableFromArrayConfig($rows,2,10,'MostVisitedPages_table','','','DESC');
		$table->set_additional_parameters(array('cidReq' =>Security::remove_XSS($_GET['cidReq']),'action'=>Security::remove_XSS($_GET['action']),'group_id'=>Security::remove_XSS($_GET['group_id'])));
		$table->set_header(0,get_lang('Type'), true, array ('style' => 'width:30px;'));
		$table->set_header(1,get_lang('Title'), true);
		$table->set_header(2,get_lang('Visits'), true);
		$table->display();
	}
}

/////////////////////// Wanted pages /////////////////////// Juan Carlos Raña Trabado

if ($_GET['action']=='wanted')
{
	echo '<div class="actions">'.get_lang('WantedPages').'</div>';

	$pages = array();
	$refs = array();
	$sort_wanted=array();

	//get name pages
	$sql='SELECT * FROM '.$tbl_wiki.'  WHERE  '.$groupfilter.$condition_session.' GROUP BY reflink ORDER BY reflink ASC';
	$allpages=Database::query($sql,__FILE__,__LINE__);

	while ($row=Database::fetch_array($allpages))
	{
		$pages[] = $row['reflink'];
	}

	//get name refs in last pages and make a unique list
	//$sql='SELECT  *  FROM   '.$tbl_wiki.' s1 WHERE id=(SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2 WHERE s1.reflink = s2.reflink AND '.$groupfilter.')'; //old version TODO: Replace by the bottom line

	$sql='SELECT * FROM '.$tbl_wiki.', '.$tbl_wiki_conf.' WHERE visibility=1 AND '.$tbl_wiki_conf.'.page_id='.$tbl_wiki.'.page_id AND '.$tbl_wiki.'.'.$groupfilter.$condition_session; // new version

	$allpages=Database::query($sql,__FILE__,__LINE__);
	while ($row=Database::fetch_array($allpages))
	{
		//$row['linksto']= str_replace("\n".$row["reflink"]."\n", "\n", $row["linksto"]); //remove self reference. TODO: check
		$rf = explode(" ", trim($row["linksto"]));//wanted pages without /n only blank " "
		$refs = array_merge($refs, $rf);
		if ($n++ > 299)
		{
			$refs = array_unique($refs);
			$n=0;
		} // (clean-up only every 300th loop). Thanks to Erfurt Wiki
	}

	//sort linksto. Find linksto into reflink. If not found ->page is wanted
	natcasesort($refs);
	echo '<ul>';
	foreach($refs as $v)
	{
		if(!in_array($v, $pages))
		{
			if (trim($v)!="")
			{
				echo   '<li><a href="'.api_get_path(WEB_PATH).'main/wiki/index.php?cidReq=&action=addnew&title='.urlencode(str_replace('_',' ',$v)).'&group_id='.Security::remove_XSS($_GET['group_id']).'" class="new_wiki_link">'.str_replace('_',' ',$v).'</a></li>';
			}
		}
	}
	echo '</ul>';
}

/////////////////////// Orphaned pages /////////////////////// Juan Carlos Raña Trabado

if ($_GET['action']=='orphaned')
{
	echo '<div class="actions">'.get_lang('OrphanedPages').'</div>';

	$pages = array();
   	$refs = array();
  	$orphaned = array();

	//get name pages
	$sql='SELECT * FROM '.$tbl_wiki.'  WHERE  '.$groupfilter.$condition_session.' GROUP BY reflink ORDER BY reflink ASC';
	$allpages=Database::query($sql,__FILE__,__LINE__);
	while ($row=Database::fetch_array($allpages))
	{
		$pages[] = $row['reflink'];
	}

	//get name refs in last pages and make a unique list
	//$sql='SELECT  *  FROM   '.$tbl_wiki.' s1 WHERE id=(SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2 WHERE s1.reflink = s2.reflink AND '.$groupfilter.')'; //old version TODO: Replace by the bottom line

	$sql='SELECT * FROM '.$tbl_wiki.', '.$tbl_wiki_conf.' WHERE '.$tbl_wiki_conf.'.page_id='.$tbl_wiki.'.page_id AND '.$tbl_wiki.'.'.$groupfilter.$condition_session.' '; // new version

	$allpages=Database::query($sql,__FILE__,__LINE__);
	while ($row=Database::fetch_array($allpages))
	{
		//$row['linksto']= str_replace("\n".$row["reflink"]."\n", "\n", $row["linksto"]); //remove self reference. TODO: check
		$rf = explode(" ", trim($row["linksto"]));	//fix replace explode("\n", trim($row["linksto"])) with  explode(" ", trim($row["linksto"]))

		$refs = array_merge($refs, $rf);
		if ($n++ > 299)
		{
			$refs = array_unique($refs);
			$n=0;
		} // (clean-up only every 300th loop). Thanks to Erfurt Wiki
	}

	//search each name of list linksto into list reflink
	foreach($pages as $v)
	{
		if(!in_array($v, $refs))
		{
			$orphaned[] = $v;
		}
	}

	//change reflink by title
	foreach($orphaned as $vshow)
	{
		if(api_is_allowed_to_edit(false,true) || api_is_platform_admin()) //only by professors if page is hidden
		{
			$sql='SELECT  *  FROM   '.$tbl_wiki.' WHERE '.$groupfilter.$condition_session.' AND reflink="'.$vshow.'" GROUP BY reflink';
		}
		else
		{
			$sql='SELECT  *  FROM   '.$tbl_wiki.' WHERE '.$groupfilter.$condition_session.' AND reflink="'.$vshow.'" AND visibility=1 GROUP BY reflink';
		}

		$allpages=Database::query($sql,__FILE__,__LINE__);

		echo '<ul>';
		while ($row=Database::fetch_array($allpages))
		{
			//fix assignment icon
			if($row['assignment']==1)
			{
				$ShowAssignment='<img src="../img/wiki/assignment.gif" />';
			}
			elseif ($row['assignment']==2)
			{
				$ShowAssignment='<img src="../img/wiki/works.gif" />';
			}
			elseif ($row['assignment']==0)
			{
				$ShowAssignment='<img src="../img/wiki/trans.gif" />';
			}

			echo '<li>'.$ShowAssignment.'<a href="'.api_get_self().'?cidReq='.$_course[id].'&action=showpage&title='.urlencode($row['reflink']).'&group_id='.Security::remove_XSS($_GET['group_id']).'">'.$row['title'].'</a></li>';
		}
		echo '</ul>';
	}

}

/////////////////////// delete current page /////////////////////// Juan Carlos Raña Trabado

if ($_GET['action']=='delete')
{

	if(!$_GET['title'])
	{
		Display::display_error_message(get_lang('MustSelectPage'));
		exit;
	}

	echo '<div style="overflow:hidden">';
	if(api_is_allowed_to_edit(false,true) || api_is_platform_admin())
	{
		echo '<div id="wikititle">'.get_lang('DeletePageHistory').'</div>';

		if($page=="index")
		{
			Display::display_warning_message(get_lang('WarningDeleteMainPage'),false);
		}

		$message = get_lang('ConfirmDeletePage')."</p>"."<p>"."<a href=\"index.php\">".get_lang("No")."</a>"."&nbsp;&nbsp;|&nbsp;&nbsp;"."<a href=\"".api_get_self()."?action=delete&amp;title=".$page."&amp;delete=yes\">".get_lang("Yes")."</a>"."</p>";

		if (!isset ($_GET['delete']))
		{
			Display::display_warning_message($message,false);
		}

		if ($_GET['delete'] == 'yes')
		{
			$sql='DELETE '.$tbl_wiki_discuss.' FROM '.$tbl_wiki.', '.$tbl_wiki_discuss.' WHERE '.$tbl_wiki.'.reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$tbl_wiki.'.'.$groupfilter.' AND '.$tbl_wiki_discuss.'.publication_id='.$tbl_wiki.'.id';
			Database::query($sql,__FILE__,__LINE__);

			$sql='DELETE '.$tbl_wiki_mailcue.' FROM '.$tbl_wiki.', '.$tbl_wiki_mailcue.' WHERE '.$tbl_wiki.'.reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$tbl_wiki.'.'.$groupfilter.' AND '.$tbl_wiki_mailcue.'.id='.$tbl_wiki.'.id';
			Database::query($sql,__FILE__,__LINE__);

			$sql='DELETE FROM '.$tbl_wiki.' WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.$condition_session.'';
	  		Database::query($sql,__FILE__,__LINE__);

			check_emailcue(0, 'E');

	  		Display::display_confirmation_message(get_lang('WikiPageDeleted'));
		}
	}
	else
	{
		Display::display_normal_message(get_lang("OnlyAdminDeletePageWiki"));
	}

	echo '</div>';
}


/////////////////////// delete all wiki /////////////////////// Juan Carlos Raña Trabado

if ($_GET['action']=='deletewiki')
{

	echo '<div class="actions">'.get_lang('DeleteWiki').'</div>';
	echo '<div style="overflow:hidden">';
	if(api_is_allowed_to_edit(false,true) || api_is_platform_admin())
	{
		$message = 	get_lang('ConfirmDeleteWiki');
		$message .= '<p>
						<a href="index.php">'.get_lang('No').'</a>
						&nbsp;&nbsp;|&nbsp;&nbsp;
						<a href="'.api_get_self().'?action=deletewiki&amp;delete=yes">'.get_lang('Yes').'</a>
					</p>';

		if (!isset($_GET['delete']))
		{
			Display::display_warning_message($message,false);
		}
	}
	else
	{
		Display::display_normal_message(get_lang("OnlyAdminDeleteWiki"));
	}
	echo '</div>';
}

/////////////////////// search wiki pages ///////////////////////
if ($_GET['action']=='searchpages')
{
	echo '<div class="actions">'.get_lang('SearchPages').'</div>';
	echo '<div style="overflow:hidden">';
	// initiate the object
	$form = new FormValidator('wiki_search','post', api_get_self().'?cidReq='.Security::remove_XSS($_GET['cidReq']).'&action='.Security::remove_XSS($_GET['action']).'&group_id='.Security::remove_XSS($_GET['group_id']));

	// settting the form elements

	$form->addElement('text', 'search_term', get_lang('SearchTerm'),'class="input_titles"');
	$form->addElement('checkbox', 'search_content', null, get_lang('AlsoSearchContent'));
	$form->addElement('style_submit_button', 'SubmitWikiSearch', get_lang('Search'), 'class="search"');

	// setting the rules
	$form->addRule('search_term', '<span class="required">'.get_lang('ThisFieldIsRequired').'</span>', 'required');
	$form->addRule('search_term', get_lang('TooShort'),'minlength',3);

	if ($form->validate())
	{
		$form->display();
		$values = $form->exportValues();
		display_wiki_search_results($values['search_term'], $values['search_content']);
	}
	else
	{
		$form->display();
    }
	echo '</div>';
}


///////////////////////  What links here. Show pages that have linked this page /////////////////////// Juan Carlos Raña Trabado

if ($_GET['action']=='links')
{

	if (!$_GET['title'])
	{
		Display::display_error_message(get_lang("MustSelectPage"));
    }
	else
	{

		$sql='SELECT * FROM '.$tbl_wiki.' WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.$condition_session.'';
		$result=Database::query($sql,__FILE__,__LINE__);
		$row=Database::fetch_array($result);

		//get type assignment icon

				if($row['assignment']==1)
				{
					$ShowAssignment='<img src="../img/wiki/assignment.gif" title="'.get_lang('AssignmentDesc').'" alt="'.get_lang('AssignmentDesc').'" />';
				}
				elseif ($row['assignment']==2)
				{
					$ShowAssignment='<img src="../img/wiki/works.gif" title="'.get_lang('AssignmentWork').'" alt="'.get_lang('AssignmentWork').'" />';
				}
				elseif ($row['assignment']==0)
				{
					$ShowAssignment='<img src="../img/wiki/trans.gif" />';
				}

		//fix Title to reflink (link Main Page)

		if ($page==get_lang('DefaultTitle'))
		{
			$page='index';
		}

		echo '<div id="wikititle">';
		echo get_lang('LinksPagesFrom').': '.$ShowAssignment.' <a href="'.api_get_self().'?cidReq='.$_course[id].'&action=showpage&title='.Security::remove_XSS($page).'&group_id='.Security::remove_XSS($_GET['group_id']).'">'.Security::remove_XSS($row['title']).'</a>';
		echo '</div>';

		//fix index to title Main page into linksto
		if ($page=='index')
		{
			$page=str_replace(' ','_',get_lang('DefaultTitle'));
		}

		//table

		if(api_is_allowed_to_edit(false,true) || api_is_platform_admin()) //only by professors if page is hidden
		{
			//$sql="SELECT * FROM ".$tbl_wiki." s1 WHERE linksto LIKE '%".html_entity_decode(Database::escape_string(stripslashes(urldecode($page))))." %' AND id=(SELECT MAX(s2.id) FROM ".$tbl_wiki." s2 WHERE s1.reflink = s2.reflink AND ".$groupfilter.")"; //add blank space after like '%" " %' to identify each word. //Old version TODO: Replace by the bottom line

			$sql="SELECT * FROM ".$tbl_wiki.", ".$tbl_wiki_conf." WHERE linksto LIKE '%".html_entity_decode(Database::escape_string(stripslashes(urldecode($page))))." %' AND ".$tbl_wiki_conf.".page_id=".$tbl_wiki.".page_id AND ".$tbl_wiki.".".$groupfilter.$condition_session.""; //add blank space after like '%" " %' to identify each word. // new version

		}
		else
		{
			//$sql="SELECT * FROM ".$tbl_wiki." s1 WHERE visibility=1 AND linksto LIKE '%".html_entity_decode(Database::escape_string(stripslashes(urldecode($page))))." %' AND id=(SELECT MAX(s2.id) FROM ".$tbl_wiki." s2 WHERE s1.reflink = s2.reflink AND ".$groupfilter.")"; //add blank space after like '%" " %' to identify each word //old version TODO: Replace by the bottom line

			$sql="SELECT * FROM ".$tbl_wiki.", ".$tbl_wiki_conf." WHERE visibility=1 AND linksto LIKE '%".html_entity_decode(Database::escape_string(stripslashes(urldecode($page))))." %' AND ".$tbl_wiki_conf.".page_id=".$tbl_wiki.".page_id AND ".$tbl_wiki.".".$groupfilter.$condition_session.""; //add blank space after like '%" " %' to identify each word // new version

		}

		$allpages=Database::query($sql,__LINE__,__FILE__);

		//show table
		if (Database::num_rows($allpages) > 0)
		{
			$row = array ();
			while ($obj = Database::fetch_object($allpages))
			{
				//get author
				$userinfo=Database::get_user_info_from_id($obj->user_id);

				//get time
				$year 	 = substr($obj->dtime, 0, 4);
				$month	 = substr($obj->dtime, 5, 2);
				$day 	 = substr($obj->dtime, 8, 2);
				$hours   = substr($obj->dtime, 11,2);
				$minutes = substr($obj->dtime, 14,2);
				$seconds = substr($obj->dtime, 17,2);

				//get type assignment icon
				if($obj->assignment==1)
				{
					$ShowAssignment='<img src="../img/wiki/assignment.gif" title="'.get_lang('AssignmentDesc').'" alt="'.get_lang('AssignmentDesc').'" />';
				}
				elseif ($obj->assignment==2)
				{
					$ShowAssignment='<img src="../img/wiki/works.gif" title="'.get_lang('AssignmentWork').'" alt="'.get_lang('AssignmentWork').'" />';
				}
				elseif ($obj->assignment==0)
				{
					$ShowAssignment='<img src="../img/wiki/trans.gif" />';
				}

				$row = array ();
				$row[] =$ShowAssignment;
				$row[] = '<a href="'.api_get_self().'?cidReq='.$_course[id].'&action=showpage&title='.urlencode($obj->reflink).'&group_id='.Security::remove_XSS($_GET['group_id']).'">'.Security::remove_XSS($obj->title).'</a>';
				$row[] = $obj->user_id <>0 ? '<a href="../user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.api_get_person_name($userinfo['firstname'], $userinfo['lastname']).'</a>' : get_lang('Anonymous').' ('.$obj->user_ip.')';
				$row[] = $year.'-'.$month.'-'.$day.' '.$hours.":".$minutes.":".$seconds;
				$rows[] = $row;
			}

			$table = new SortableTableFromArrayConfig($rows,1,10,'AllPages_table','','','ASC');
			$table->set_additional_parameters(array('cidReq' =>Security::remove_XSS($_GET['cidReq']),'action'=>Security::remove_XSS($_GET['action']),'group_id'=>Security::remove_XSS($_GET['group_id'])));
			$table->set_header(0,get_lang('Type'), true, array ('style' => 'width:30px;'));
			$table->set_header(1,get_lang('Title'), true);
			$table->set_header(2,get_lang('Author'), true);
			$table->set_header(3,get_lang('Date'), true);
			$table->display();
		}
	}
}


/////////////////////// adding a new page ///////////////////////


// Display the form for adding a new wiki page
if ($_GET['action']=='addnew')
{
	if (api_get_session_id()!=0 && api_is_allowed_to_session_edit(false,true)==false) {
		api_not_allowed();
	}

	echo '<div class="actions">'.get_lang('AddNew').'</div>';

	//first, check if page index was created. chektitle=false
	if (checktitle('index'))
	{
		if(api_is_allowed_to_edit(false,true) || api_is_platform_admin() || GroupManager :: is_user_in_group($_user['user_id'],$_SESSION['_gid']))
		{
			Display::display_normal_message(get_lang('GoAndEditMainPage'));
		}
		else
		{
			return Display::display_normal_message(get_lang('WikiStandBy'));
		}
	}

	elseif (check_addnewpagelock()==0 && (api_is_allowed_to_edit(false,true)==false || api_is_platform_admin()==false))
	{
		Display::display_error_message(get_lang('AddPagesLocked'));
	}
	else
	{
		if(api_is_allowed_to_edit(false,true) || api_is_platform_admin() || GroupManager :: is_user_in_group($_user['user_id'],$_SESSION['_gid']) || Security::remove_XSS($_GET['group_id'])==0)
		{
			display_new_wiki_form();
		}
		else
		{
			Display::display_normal_message(get_lang('OnlyAddPagesGroupMembers'));
		}
	}

}



/////////////////////// show home page ///////////////////////

if (!$_GET['action'] OR $_GET['action']=='show' AND !isset($_POST['SaveWikiNew']))
{
	display_wiki_entry($newtitle);
}

/////////////////////// show current page ///////////////////////

if ($_GET['action']=='showpage' AND !isset($_POST['SaveWikiNew']))
{
	if($_GET['title'])
	{
		display_wiki_entry();
	}
	else
	{
		Display::display_error_message(get_lang('MustSelectPage'));
	}
}


/////////////////////// edit current page ///////////////////////

if ($_GET['action']=='edit')
{
	if (api_get_session_id()!=0 && api_is_allowed_to_session_edit(false,true)==false) {
		api_not_allowed();
	}

	$_clean['group_id']=(int)$_SESSION['_gid'];

	$sql='SELECT * FROM '.$tbl_wiki.', '.$tbl_wiki_conf.' WHERE '.$tbl_wiki_conf.'.page_id='.$tbl_wiki.'.page_id AND '.$tbl_wiki.'.reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$tbl_wiki.'.'.$groupfilter.$condition_session.' ORDER BY id DESC';
	$result=Database::query($sql,__LINE__,__FILE__);
	$row=Database::fetch_array($result); // we do not need a while loop since we are always displaying the last version


	if ($row['content']=='' AND $row['title']=='' AND $page=='')
	{
		Display::display_error_message(get_lang('MustSelectPage'));
		exit;
	}
	elseif ($row['content']=='' AND $row['title']=='' AND $page=='index')
	{
		//Table structure for better export to pdf
		$default_table_for_content_Start='<table align="center" border="0"><tr><td align="center">';
		$default_table_for_content_End='</td></tr></table>';

		$content=$default_table_for_content_Start.sprintf(get_lang('DefaultContent'),api_get_path(WEB_IMG_PATH)).$default_table_for_content_End;
		$title=get_lang('DefaultTitle');
		$page_id=0;
	}
	else
	{
		$content=$row['content'];
		$title=$row['title'];
		$page_id=$row['page_id'];
	}

	//Only teachers and platform admin can edit the index page. Only teachers and platform admin can edit an assignment teacher. And users in groups
	if(($row['reflink']=='index' || $row['reflink']=='' || $row['assignment']==1) && (!api_is_allowed_to_edit(false,true) && $_clean['group_id']==0))
	{
		Display::display_error_message(get_lang('OnlyEditPagesCourseManager'));
	}
    else
	{
		$PassEdit=false;

	    //check if is a wiki group
		if($_clean['group_id']!=0)
		{
			//Only teacher, platform admin and group members can edit a wiki group
			if(api_is_allowed_to_edit(false,true) || api_is_platform_admin() || GroupManager :: is_user_in_group($_user['user_id'],$_SESSION['_gid']))
			{
				$PassEdit=true;
			}
			else
			{
			  	Display::display_normal_message(get_lang('OnlyEditPagesGroupMembers'));
			}
		}
		else
		{
		    $PassEdit=true;
		}

		// check if is a assignment
		if(stripslashes($row['assignment'])==1)
		{
		    Display::display_normal_message(get_lang('EditAssignmentWarning'));
			$icon_assignment='<img src="../img/wiki/assignment.gif" title="'.get_lang('AssignmentDescExtra').'" alt="'.get_lang('AssignmentDescExtra').'" />';
		}
		elseif(stripslashes($row['assignment'])==2)
		{
			$icon_assignment='<img src="../img/wiki/works.gif" title="'.get_lang('AssignmentWorkExtra').'" alt="'.get_lang('AssignmentWorkExtra').'" />';
			if((api_get_user_id()==$row['user_id'])==false)
			{
			    if(api_is_allowed_to_edit(false,true) || api_is_platform_admin())
				{
					$PassEdit=true;
				}
				else
				{
					Display::display_warning_message(get_lang('LockByTeacher'));
					$PassEdit=false;
				}
			}
			else
			{
				$PassEdit=true;
			}
		}

	 	if($PassEdit) //show editor if edit is allowed
		 {
	 		if ($row['editlock']==1 && (api_is_allowed_to_edit(false,true)==false || api_is_platform_admin()==false))
  	   	    {
    		       Display::display_normal_message(get_lang('PageLockedExtra'));
		    }
			else
			{
				//check tasks
				if (!empty($row['startdate_assig']) && $row['startdate_assig']!='0000-00-00 00:00:00' && time()<strtotime($row['startdate_assig']))
				{
					$message=get_lang('TheTaskDoesNotBeginUntil').': '.$row['startdate_assig'];
					Display::display_warning_message($message);
					if(!api_is_allowed_to_edit(false,true))
					{
						exit;
					}
				}

				//
				if (!empty($row['enddate_assig']) && $row['enddate_assig']!='0000-00-00 00:00:00' && time()>strtotime($row['enddate_assig']) && $row['enddate_assig']!='0000-00-00 00:00:00' && $row['delayedsubmit']==0)
				{
					$message=get_lang('TheDeadlineHasBeenCompleted').': '.$row['enddate_assig'];
					Display::display_warning_message($message);
					if(!api_is_allowed_to_edit(false,true))
					{
						exit;
					}
				}

				//
				if(!empty($row['max_version']) && $row['version']>=$row['max_version'])
				{
					$message=get_lang('HasReachedMaxiNumVersions');
					Display::display_warning_message($message);
					if(!api_is_allowed_to_edit(false,true))
					{
						exit;
					}
				}

				//
				if (!empty($row['max_text']) && $row['max_text']<=word_count($row['content']))
				{
					$message=get_lang('HasReachedMaxNumWords');
					Display::display_warning_message($message);
					if(!api_is_allowed_to_edit(false,true))
					{
						exit;
					}

				}

				////
				if (!empty($row['task']))
				{
					//previous change 0 by text
					if ($row['startdate_assig']=='0000-00-00 00:00:00')
					{
						$message_task_startdate=get_lang('No');
					}
					else
					{
						$message_task_startdate=$row['startdate_assig'];
					}

					if ($row['enddate_assig']=='0000-00-00 00:00:00')
					{
						$message_task_enddate=get_lang('No');
					}
					else
					{
						$message_task_endate=$row['enddate_assig'];
					}

					if ($row['delayedsubmit']==0)
					{
						$message_task_delayedsubmit=get_lang('No');
					}
					else
					{
						$message_task_delayedsubmit=get_lang('Yes');
					}
					if ($row['max_version']==0)
					{
						$message_task_max_version=get_lang('No');
					}
					else
					{
						$message_task_max_version=$row['max_version'];
					}
					if ($row['max_text']==0)
					{
						$message_task_max_text=get_lang('No');
					}
					else
					{
						$message_task_max_text=$row['max_text'];
					}

					//comp message
					$message_task='<b>'.get_lang('DescriptionOfTheTask').'</b><p>'.$row['task'].'</p><hr>';
					$message_task.='<p>'.get_lang('StartDate').': '.$message_task_startdate.'</p>';
					$message_task.='<p>'.get_lang('EndDate').': '.$message_task_enddate;
					$message_task.=' ('.get_lang('AllowLaterSends').') '.$message_task_delayedsubmit.'</p>';
					$message_task.='<p>'.get_lang('OtherSettings').': '.get_lang('NMaxVersion').': '.$message_task_max_version;
					$message_task.=' '.get_lang('NMaxWords').': '.$message_task_max_text;

					//display message
					Display::display_normal_message($message_task,false);

				}

				if($row['progress']==$row['fprogress1'] && !empty($row['fprogress1']))
				{
					$feedback_message='<b>'.get_lang('Feedback').'</b><p>'.$row['feedback1'].'</p>';
					Display::display_normal_message($feedback_message, false);
				}
				elseif($row['progress']==$row['fprogress2'] && !empty($row['fprogress2']))
				{
					$feedback_message='<b>'.get_lang('Feedback').'</b><p>'.$row['feedback2'].'</p>';
					Display::display_normal_message($feedback_message, false);
				}
				elseif($row['progress']==$row['fprogress3'] && !empty($row['fprogress3']))
				{
					$feedback_message='<b>'.get_lang('Feedback').'</b><p>'.$row['feedback3'].'</p>';
					Display::display_normal_message($feedback_message, false);
				}

				//previous checking for concurrent editions
				if($row['is_editing']==0)
				{
					Display::display_normal_message(get_lang('WarningMaxEditingTime'));

					$time_edit = date("Y-m-d H:i:s");
					$sql='UPDATE '.$tbl_wiki.' SET is_editing="'.$_user['user_id'].'", time_edit="'.$time_edit.'" WHERE id="'.$row['id'].'"';
					Database::query($sql,__FILE__,__LINE__);
				}
				elseif($row['is_editing']!=$_user['user_id'])
				{
					$timestamp_edit=strtotime($row['time_edit']);
					$time_editing=time()-$timestamp_edit;
					$max_edit_time=1200; // 20 minutes
					$rest_time=$max_edit_time-$time_editing;

					$userinfo=Database::get_user_info_from_id($row['is_editing']);

					$is_being_edited= get_lang('ThisPageisBeginEditedBy').' <a href=../user/userInfo.php?uInfo='.$userinfo['user_id'].'>'.api_get_person_name($userinfo['firstname'], $userinfo['lastname']).'</a>. '.get_lang('ThisPageisBeginEditedTryLater').' '.date( "i",$rest_time).' '.get_lang('MinMinutes').'';
					Display::display_normal_message($is_being_edited, false);
					exit;
				}
				//form
				echo '<form name="form1" method="post" action="'.api_get_self().'?action=showpage&amp;title='.$page.'&group_id='.Security::remove_XSS($_GET['group_id']).'">';
				echo '<div id="wikititle">';
				echo $icon_assignment.'&nbsp;&nbsp;&nbsp;'.$title;
				//

				if((api_is_allowed_to_edit(false,true) || api_is_platform_admin()) && $row['reflink']!='index')
				{

					echo'<a href="javascript://" onclick="advanced_parameters()" ><span id="plus_minus" style="float:right">&nbsp;'.Display::return_icon('div_show.gif',get_lang('Show'),array('style'=>'vertical-align:middle')).'&nbsp;'.get_lang('AdvancedParameters').'</span></a>';

					echo '<div id="options" style="display:none; margin: 20px;" >';

					//task
					echo '<div>&nbsp;</div><input type="checkbox" value="1" name="checktask" onclick="if(this.checked==true){document.getElementById(\'option4\').style.display=\'block\';}else{document.getElementById(\'option4\').style.display=\'none\';}"/>&nbsp;<img src="../img/wiki/task.gif" title="'.get_lang('DefineTask').'" alt="'.get_lang('DefineTask').'"/>'.get_lang('DescriptionOfTheTask').'';
					echo '&nbsp;&nbsp;&nbsp;<span id="msg_error4" style="display:none;color:red"></span>';
					echo '<div id="option4" style="padding:4px; margin:5px; border:1px dotted; display:none;">';

					echo '<table border="0" style="font-weight:normal">';
					echo '<tr>';
					echo '<td>'.get_lang('DescriptionOfTheTask').'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td>'.api_disp_html_area('task', stripslashes($row['task']), '', '', null, array('ToolbarSet' => 'wiki_task', 'Width' => '600', 'Height' => '200')).'</td>';
					echo '</tr>';
					echo '</table>';
					echo '</div>';

					//feedback
					echo '<div>&nbsp;</div><input type="checkbox" value="1" name="checkfeedback" onclick="if(this.checked==true){document.getElementById(\'option2\').style.display=\'block\';}else{document.getElementById(\'option2\').style.display=\'none\';}"/>&nbsp;'.get_lang('AddFeedback').'';
					echo '&nbsp;&nbsp;&nbsp;<span id="msg_error2" style="display:none;color:red"></span>';
					echo '<div id="option2" style="padding:4px; margin:5px; border:1px dotted; display:none;">';

					echo '<table border="0" style="font-weight:normal" align="center">';
					echo '<tr>';
					echo '<td colspan="2">'.get_lang('Feedback1').'</td>';
					echo '<td colspan="2">'.get_lang('Feedback2').'</td>';
					echo '<td colspan="2">'.get_lang('Feedback3').'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td colspan="2"><textarea name="feedback1" cols="23" rows="4" >'.stripslashes($row['feedback1']).'</textarea></td>';
					echo '<td colspan="2"><textarea name="feedback2" cols="23" rows="4" >'.stripslashes($row['feedback2']).'</textarea></td>';
					echo '<td colspan="2"><textarea name="feedback3" cols="23" rows="4" >'.stripslashes($row['feedback3']).'</textarea></td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td>'.get_lang('FProgress').':</td>';
					echo '<td><select name="fprogress1">';
				 	echo '<option value="'.stripslashes($row['fprogress1']).'" selected>'.stripslashes($row['fprogress1']).'</option>';
					echo '<option value="10">10</option>
					   <option value="20">20</option>
					   <option value="30">30</option>
					   <option value="40">40</option>
					   <option value="50">50</option>
					   <option value="60">60</option>
					   <option value="70">70</option>
					   <option value="80">80</option>
					   <option value="90">90</option>
					   <option value="100">100</option>
					   </select> %</td>';
					echo '<td>'.get_lang('FProgress').':</td>';
					echo '<td><select name="fprogress2">';
				 	echo '<option value="'.stripslashes($row['fprogress2']).'" selected>'.stripslashes($row['fprogress2']).'</option>';
					echo '<option value="10">10</option>
					   <option value="20">20</option>
					   <option value="30">30</option>
					   <option value="40">40</option>
					   <option value="50">50</option>
					   <option value="60">60</option>
					   <option value="70">70</option>
					   <option value="80">80</option>
					   <option value="90">90</option>
					   <option value="100">100</option>
					   </select> %</td>';
					echo '<td>'.get_lang('FProgress').':</td>';
					echo '<td><select name="fprogress3">';
				 	echo '<option value="'.stripslashes($row['fprogress3']).'" selected>'.stripslashes($row['fprogress3']).'</option>';
					echo '<option value="10">10</option>
					   <option value="20">20</option>
					   <option value="30">30</option>
					   <option value="40">40</option>
					   <option value="50">50</option>
					   <option value="60">60</option>
					   <option value="70">70</option>
					   <option value="80">80</option>
					   <option value="90">90</option>
					   <option value="100">100</option>
					   </select> %</td>';
					echo '</tr>';
					echo '</table>';
					echo '</div>';

					//time limit
					echo  '<div>&nbsp;</div><input type="checkbox" value="1" name="checktimelimit" onclick="if(this.checked==true){document.getElementById(\'option1\').style.display=\'block\'; $pepe=\'a\';}else{document.getElementById(\'option1\').style.display=\'none\';}"/>&nbsp;'.get_lang('PutATimeLimit').'';
					echo  '&nbsp;&nbsp;&nbsp;<span id="msg_error1" style="display:none;color:red"></span>';
					echo  '<div id="option1" style="padding:4px; margin:5px; border:1px dotted; display:none;">';
					echo '<table width="100%" border="0" style="font-weight:normal">';
					echo '<tr>';
					echo '<td align="right">'.get_lang("StartDate").':</td>';
					echo '<td>';
					if ($row['startdate_assig']=='0000-00-00 00:00:00')
					{
						echo draw_date_picker('startdate_assig').' <input type="checkbox" name="initstartdate" value="1"> '.get_lang('Yes').'/'.get_lang('No').'';

					}
					else
					{
						echo draw_date_picker('startdate_assig', $row['startdate_assig']).' <input type="checkbox" name="initstartdate" value="1"> '.get_lang('Yes').'/'.get_lang('No').'';
					}
					echo '</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td align="right">'.get_lang("EndDate").':</td>';
					echo '<td>';
					if ($row['enddate_assig']=='0000-00-00 00:00:00')
					{
						echo draw_date_picker('enddate_assig').' <input type="checkbox" name="initenddate" value="1"> '.get_lang('Yes').'/'.get_lang('No').'';
					}
					else
					{
						echo draw_date_picker('enddate_assig', $row['enddate_assig']).' <input type="checkbox" name="initenddate" value="1"> '.get_lang('Yes').'/'.get_lang('No').'';
					}
					echo '</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td align="right">'.get_lang('AllowLaterSends').':</td>';
					if (stripslashes($row['delayedsubmit'])==1)
					{
						$check_uncheck='checked';
					}
					echo '<td><input type="checkbox" name="delayedsubmit" value="1" '.$check_uncheck.'></td>';
					echo '</tr>';
					echo'</table>';
					echo '</div>';

					//other limit
					echo '<div>&nbsp;</div><input type="checkbox" value="1" name="checkotherlimit" onclick="if(this.checked==true){document.getElementById(\'option3\').style.display=\'block\';}else{document.getElementById(\'option3\').style.display=\'none\';}"/>&nbsp;'.get_lang('OtherSettings').'';
					echo '&nbsp;&nbsp;&nbsp;<span id="msg_error3" style="display:none;color:red"></span>';
					echo '<div id="option3" style="padding:4px; margin:5px; border:1px dotted; display:none;">';
					echo '<div style="font-weight:normal"; align="center">'.get_lang('NMaxWords').':&nbsp;<input type="text" name="max_text" size="3" value="'.stripslashes($row['max_text']).'">&nbsp;&nbsp;'.get_lang('NMaxVersion').':&nbsp;<input type="text" name="max_version" size="3" value="'.stripslashes($row['max_version']).'"></div>';
					echo '</div>';

					//
					echo '</div>';
				}

				echo '</div>';
				echo '<div id="wikicontent">';

				echo '<input type="hidden" name="page_id" value="'.$page_id.'">';
				echo '<input type="hidden" name="reflink" value="'.$page.'">';
				echo '<input type="hidden" name="title" value="'.stripslashes($title).'">';

				api_disp_html_area('content', stripslashes($content), '', '', null, api_is_allowed_to_edit(null,true)
					? array('ToolbarSet' => 'Wiki', 'Width' => '100%', 'Height' => '400')
					: array('ToolbarSet' => 'WikiStudent', 'Width' => '100%', 'Height' => '400', 'UserStatus' => 'student')
				);
				echo '<br/>';
	            echo '<br/>';
				//if(api_is_allowed_to_edit() || api_is_platform_admin()) //off for now
				//{
				echo get_lang('Comments').':&nbsp;&nbsp;<input type="text" name="comment" size="40">&nbsp;&nbsp;&nbsp;';
				//}
				echo '<INPUT TYPE="hidden" NAME="assignment" VALUE="'.stripslashes($row['assignment']).'"/>';
				echo '<INPUT TYPE="hidden" NAME="version" VALUE="'.stripslashes($row['version']).'"/>';

				//hack date for edit
				echo '<INPUT TYPE="hidden" NAME="startdate_assig" VALUE="'.stripslashes($row['startdate_assig']).'"/>';
				echo '<INPUT TYPE="hidden" NAME="enddate_assig" VALUE="'.stripslashes($row['enddate_assig']).'"/>';

				//
				echo get_lang('Progress').':&nbsp;&nbsp;<select name="progress" id="progress">';
				echo '<option value="'.stripslashes($row['progress']).'" selected>'.stripslashes($row['progress']).'</option>';
				echo '<option value="10">10</option>
				<option value="20">20</option>
				<option value="30">30</option>
				<option value="40">40</option>
				<option value="50">50</option>
				<option value="60">60</option>
				<option value="70">70</option>
				<option value="80">80</option>
				<option value="90">90</option>
				<option value="100">100</option>
				</select> %';
				echo '<br/><br/>';
				echo '<input type="hidden" name="wpost_id" value="'.md5(uniqid(rand(), true)).'">';//prevent double post
				echo '<input type="hidden" name="SaveWikiChange" value="'.get_lang('langSave').'">'; //for save icon
				echo '<button class="save" type="submit" name="SaveWikiChange">'.get_lang('langSave').'</button>';//for save button
				echo '</div>';
				echo '</form>';
			}
		}
	}
}

/////////////////////// page history ///////////////////////


if ($_GET['action']=='history' or Security::remove_XSS($_POST['HistoryDifferences']))
{
	if (!$_GET['title'])
	{
		Display::display_error_message(get_lang("MustSelectPage"));
		exit;
    }

	echo '<div style="overflow:hidden">';
	$_clean['group_id']=(int)$_SESSION['_gid'];

    //First, see the property visibility that is at the last register and therefore we should select descending order. But to give ownership to each record, this is no longer necessary except for the title. TODO: check this

	$sql='SELECT * FROM '.$tbl_wiki.'WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.$condition_session.' ORDER BY id DESC';
	$result=Database::query($sql,__LINE__,__FILE__);

	while ($row=Database::fetch_array($result))
	{
		$KeyVisibility=$row['visibility'];
		$KeyAssignment=$row['assignment'];
		$KeyTitle=$row['title'];
		$KeyUserId=$row['user_id'];
	}

	    if($KeyAssignment==1)
		{
			$icon_assignment='<img src="../img/wiki/assignment.gif" title="'.get_lang('AssignmentDescExtra').'" alt="'.get_lang('AssignmentDescExtra').'" />';
		}
		elseif($KeyAssignment==2)
		{
			$icon_assignment='<img src="../img/wiki/works.gif" title="'.get_lang('AssignmentWorkExtra').'" alt="'.get_lang('AssignmentWorkExtra').'" />';
		}


	//Second, show

	//if the page is hidden and is a job only sees its author and professor
	if($KeyVisibility==1 || api_is_allowed_to_edit(false,true) || api_is_platform_admin() || ($KeyAssignment==2 && $KeyVisibility==0 && (api_get_user_id()==$KeyUserId)))
	{
		// We show the complete history
		if (!$_POST['HistoryDifferences'] && !$_POST['HistoryDifferences2'] )
		{

			$sql='SELECT * FROM '.$tbl_wiki.'WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.$condition_session.' ORDER BY id DESC';
			$result=Database::query($sql,__LINE__,__FILE__);

			$title		= Security::remove_XSS($_GET['title']);
			$group_id	= Security::remove_XSS($_GET['group_id']);

			echo '<div id="wikititle">';
			echo $icon_assignment.'&nbsp;&nbsp;&nbsp;'.$KeyTitle;
			echo '</div>';
			echo '<div id="wikicontent">';
			echo '<form id="differences" method="POST" action="index.php?cidReq='.$_course[id].'&action=history&title='.$title.'&group_id='.$group_id.'">';

			echo '<ul style="list-style-type: none;">';
			echo '<br/>';
			echo '<button class="search" type="submit" name="HistoryDifferences" value="HistoryDifferences">'.get_lang('ShowDifferences').' '.get_lang('LinesDiff').'</button>';
			echo '<button class="search" type="submit" name="HistoryDifferences2" value="HistoryDifferences2">'.get_lang('ShowDifferences').' '.get_lang('WordsDiff').'</button>';
			echo '<br/><br/>';

			$counter=0;
			$total_versions=Database::num_rows($result);

			while ($row=Database::fetch_array($result))
			{
				$userinfo=Database::get_user_info_from_id($row['user_id']);

				$year = substr($row['dtime'], 0, 4);
				$month = substr($row['dtime'], 5, 2);
				$day = substr($row['dtime'], 8, 2);
				$hours=substr($row['dtime'], 11,2);
				$minutes=substr($row['dtime'], 14,2);
				$seconds=substr($row['dtime'], 17,2);

				echo '<li style="margin-bottom: 5px;">';
				($counter==0) ? $oldstyle='style="visibility: hidden;"':$oldstyle='';
				($counter==0) ? $newchecked=' checked':$newchecked='';
				($counter==$total_versions-1) ? $newstyle='style="visibility: hidden;"':$newstyle='';
				($counter==1) ? $oldchecked=' checked':$oldchecked='';
				echo '<input name="old" value="'.$row['id'].'" type="radio" '.$oldstyle.' '.$oldchecked.'/> ';
				echo '<input name="new" value="'.$row['id'].'" type="radio" '.$newstyle.' '.$newchecked.'/> ';
				echo '<a href="'.api_get_self().'?action=showpage&amp;title='.$page.'&amp;view='.$row['id'].'">';
				echo '<a href="'.api_get_self().'?cidReq='.$_course[id].'&action=showpage&amp;title='.$page.'&amp;view='.$row['id'].'&group_id='.$group_id.'">';

				echo $year.'-'.$month.'-'.$day.' '.$hours.":".$minutes.":".$seconds;
				echo '</a>';
				echo ' ('.get_lang('Version').' '.$row['version'].')';
				echo ' '.get_lang('By').' ';
				if ($row['user_id']<>0)
				{
					echo '<a href="../user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.api_get_person_name($userinfo['firstname'], $userinfo['lastname']).'</a>';
				}
				else
				{
					echo get_lang('Anonymous').' ('.$row[user_ip].')';
				}

				echo ' ( '.get_lang('Progress').': '.$row['progress'].'%, ';
				$comment=$row['comment'];

				if (!empty($comment))
				{
					echo get_lang('Comments').': '.api_substr(api_htmlentities($row['comment'], ENT_QUOTES, $charset),0,100);
					if (api_strlen($row['comment'])>100)
					{
						echo '... ';
					}
				}
				else
				{
					echo get_lang('Comments').':  ---';
				}
				echo ' ) </li>';

				$counter++;
			} //end while
			echo '<br/>';
			echo '<button class="search" type="submit" name="HistoryDifferences" value="HistoryDifferences">'.get_lang('ShowDifferences').' '.get_lang('LinesDiff').'</button>';
			echo '<button class="search" type="submit" name="HistoryDifferences2" value="HistoryDifferences2">'.get_lang('ShowDifferences').' '.get_lang('WordsDiff').'</button>';
			echo '</ul></form></div>';
		}
		// We show the differences between two versions
		else
		{
			$sql_old="SELECT * FROM $tbl_wiki WHERE id='".Database::escape_string($_POST['old'])."'";
			$result_old=Database::query($sql_old,__LINE__,__FILE__);
			$version_old=Database::fetch_array($result_old);


			$sql_new="SELECT * FROM $tbl_wiki WHERE id='".Database::escape_string($_POST['new'])."'";
			$result_new=Database::query($sql_new,__LINE__,__FILE__);
			$version_new=Database::fetch_array($result_new);

		    if(isset($_POST['HistoryDifferences']))
			{
				include('diff.inc.php');
				//title
				echo '<div id="wikititle">'.stripslashes($version_new['title']).' <font size="-2"><i>('.get_lang('DifferencesNew').'</i> <font style="background-color:#aaaaaa">'.stripslashes($version_new['dtime']).'</font> <i>'.get_lang('DifferencesOld').'</i> <font style="background-color:#aaaaaa">'.stripslashes($version_old['dtime']).'</font>) '.get_lang('Legend').':  <span class="diffAdded" >'.get_lang(WikiDiffAddedLine).'</span> <span class="diffDeleted" >'.get_lang(WikiDiffDeletedLine).'</span> <span class="diffMoved" >'.get_lang(WikiDiffMovedLine).'</span></font></div>';
			}
			if(isset($_POST['HistoryDifferences2']))
			{
				require_once 'Text/Diff.php';
   				require_once 'Text/Diff/Renderer/inline.php';
				//title
				echo '<div id="wikititle">'.stripslashes($version_new['title']).' <font size="-2"><i>('.get_lang('DifferencesNew').'</i> <font style="background-color:#aaaaaa">'.stripslashes($version_new['dtime']).'</font> <i>'.get_lang('DifferencesOld').'</i> <font style="background-color:#aaaaaa">'.stripslashes($version_old['dtime']).'</font>) '.get_lang('Legend').':  <span class="diffAddedTex" >'.get_lang(WikiDiffAddedTex).'</span> <span class="diffDeletedTex" >'.get_lang(WikiDiffDeletedTex).'</span></font></div>';
			}

			echo '<div class="diff"><br /><br />';

			if(isset($_POST['HistoryDifferences']))
			{
				echo '<table>'.diff( stripslashes($version_old['content']), stripslashes($version_new['content']), true, 'format_table_line' ).'</table>'; // format_line mode is better for words
				echo '</div>';

				echo '<br />';
				echo '<strong>'.get_lang('Legend').'</strong><div class="diff">' . "\n";
				echo '<table><tr>';
				echo  '<td>';
				echo '</td><td>';
				echo '<span class="diffEqual" >'.get_lang('WikiDiffUnchangedLine').'</span><br />';
				echo '<span class="diffAdded" >'.get_lang('WikiDiffAddedLine').'</span><br />';
				echo '<span class="diffDeleted" >'.get_lang('WikiDiffDeletedLine').'</span><br />';
				echo '<span class="diffMoved" >'.get_lang('WikiDiffMovedLine').'</span><br />';
				echo '</td>';
				echo '</tr></table>';

				echo '</div>';

			}

	        if(isset($_POST['HistoryDifferences2']))
			{

				$lines1 = array(strip_tags($version_old['content'])); //without <> tags
				$lines2 = array(strip_tags($version_new['content'])); //without <> tags

				$diff = &new Text_Diff($lines1, $lines2);

				$renderer = &new Text_Diff_Renderer_inline();
				echo '<style>del{background:#fcc}ins{background:#cfc}</style>'.$renderer->render($diff); // Code inline
				//echo '<div class="diffEqual">'.html_entity_decode($renderer->render($diff)).'</div>'; // Html inline. By now, turned off by problems in comparing pages separated by more than one version
				echo '</div>';

				echo '<br />';
				echo '<strong>'.get_lang('Legend').'</strong><div class="diff">' . "\n";
				echo '<table><tr>';
				echo  '<td>';
				echo '</td><td>';
				echo '<span class="diffAddedTex" >'.get_lang('WikiDiffAddedTex').'</span><br />';
				echo '<span class="diffDeletedTex" >'.get_lang('WikiDiffDeletedTex').'</span><br />';
				echo '</td>';
				echo '</tr></table>';

				echo '</div>';

			}
		}
	}
	echo '</div>';
}


/////////////////////// recent changes ///////////////////////

//
//rss feed. TODO:
//

if ($_GET['action']=='recentchanges')
{
	$_clean['group_id']=(int)$_SESSION['_gid'];

	if ( api_is_allowed_to_session_edit(false,true) ) {
		if (check_notify_all()==1)
		{
			$notify_all= '<img src="../img/wiki/send_mail_checked.gif" title="'.get_lang('FullNotifyByEmail').'" alt="'.get_lang('FullNotifyByEmail').'" style="vertical-align:middle;" />'.get_lang('NotNotifyChanges');
			$lock_unlock_notify_all='unlocknotifyall';			
		}
		else
		{
			$notify_all= '<img src="../img/wiki/send_mail.gif" title="'.get_lang('FullCancelNotifyByEmail').'" alt="'.get_lang('FullCancelNotifyByEmail').'"  style="vertical-align:middle;"/>'.get_lang('NotifyChanges');
			$lock_unlock_notify_all='locknotifyall';
		}

	}

	echo '<div class="actions"><span style="float: right;">';	
	echo '<a href="index.php?action=recentchanges&amp;actionpage='.$lock_unlock_notify_all.'&amp;title='.$page.'">'.$notify_all.'</a>';	
	echo '</span>'.get_lang('RecentChanges').'</div>';



	if(api_is_allowed_to_edit(false,true) || api_is_platform_admin()) //only by professors if page is hidden
	{
		//$sql='SELECT * FROM '.$tbl_wiki.' WHERE '.$groupfilter.' ORDER BY dtime DESC'; // old version TODO: Replace by the bottom line

		$sql='SELECT * FROM '.$tbl_wiki.', '.$tbl_wiki_conf.' WHERE '.$tbl_wiki_conf.'.page_id='.$tbl_wiki.'.page_id AND '.$tbl_wiki.'.'.$groupfilter.$condition_session.' ORDER BY dtime DESC'; // new version

	}
	else
	{
		$sql='SELECT * FROM '.$tbl_wiki.' WHERE '.$groupfilter.$condition_session.' AND visibility=1 ORDER BY dtime DESC';	// old version TODO: Replace by the bottom line

		//$sql='SELECT * FROM '.$tbl_wiki.', '.$tbl_wiki_conf.' WHERE '.$tbl_wiki_conf.'.page_id='.$tbl_wiki.'.page_id AND visibility=1 AND '.$tbl_wiki.'.'.$groupfilter.' ORDER BY dtime DESC'; // new version
	}

	$allpages=Database::query($sql,__LINE__,__FILE__);

	//show table
	if (Database::num_rows($allpages) > 0)
	{
		$row = array ();
		while ($obj = Database::fetch_object($allpages))
		{
			//get author
			$userinfo=Database::get_user_info_from_id($obj->user_id);

			//get time
			$year 	 = substr($obj->dtime, 0, 4);
			$month	 = substr($obj->dtime, 5, 2);
			$day 	 = substr($obj->dtime, 8, 2);
			$hours   = substr($obj->dtime, 11,2);
			$minutes = substr($obj->dtime, 14,2);
			$seconds = substr($obj->dtime, 17,2);

			//get type assignment icon
			if($obj->assignment==1)
			{
				$ShowAssignment='<img src="../img/wiki/assignment.gif" title="'.get_lang('AssignmentDesc').'" alt="'.get_lang('AssignmentDesc').'" />';
			}
			elseif ($obj->assignment==2)
			{
				$ShowAssignment='<img src="../img/wiki/works.gif" title="'.get_lang('AssignmentWork').'" alt="'.get_lang('AssignmentWork').'" />';
			}
			elseif ($obj->assignment==0)
			{
				$ShowAssignment='<img src="../img/wiki/trans.gif" />';
			}

			//get icon task
			if (!empty($obj->task))
			{
				$icon_task='<img src="../img/wiki/task.gif" title="'.get_lang('StandardTask').'" alt="'.get_lang('StandardTask').'" />';
			}
			else
			{
				$icon_task='<img src="../img/wiki/trans.gif" />';
			}


			$row = array ();
			$row[] = $year.'-'.$month.'-'.$day.' '.$hours.':'.$minutes.":".$seconds;
			$row[] = $ShowAssignment.$icon_task;
			$row[] = '<a href="'.api_get_self().'?cidReq='.$_course[id].'&action=showpage&title='.urlencode($obj->reflink).'&amp;view='.$obj->id.'&group_id='.Security::remove_XSS($_GET['group_id']).'">'.$obj->title.'</a>';
			$row[] = $obj->version>1 ? get_lang('EditedBy') : get_lang('AddedBy');
			$row[] = $obj->user_id <> 0 ? '<a href="../user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.api_get_person_name($userinfo['firstname'], $userinfo['lastname']).'</a>' : get_lang('Anonymous').' ('.$obj->user_ip.')';
			$rows[] = $row;
		}

		$table = new SortableTableFromArrayConfig($rows,0,10,'RecentPages_table','','','DESC');
		$table->set_additional_parameters(array('cidReq' =>Security::remove_XSS($_GET['cidReq']),'action'=>Security::remove_XSS($_GET['action']),'group_id'=>Security::remove_XSS($_GET['group_id'])));
		$table->set_header(0,get_lang('Date'), true, array ('style' => 'width:200px;'));
		$table->set_header(1,get_lang('Type'), true, array ('style' => 'width:30px;'));
		$table->set_header(2,get_lang('Title'), true);
		$table->set_header(3,get_lang('Actions'), true, array ('style' => 'width:80px;'));
		$table->set_header(4,get_lang('Author'), true);

		$table->display();
	}
}


/////////////////////// all pages ///////////////////////


if ($_GET['action']=='allpages')
{
	echo '<div class="actions">'.get_lang('AllPages').'</div>';

	$_clean['group_id']=(int)$_SESSION['_gid'];


	if(api_is_allowed_to_edit(false,true) || api_is_platform_admin()) //only by professors if page is hidden
	{
		//$sql='SELECT  *  FROM  '.$tbl_wiki.' s1 WHERE id=(SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2 WHERE s1.reflink = s2.reflink AND '.$groupfilter.')'; // warning don't use group by reflink because don't return the last version// old version TODO: Replace by the bottom line

		$sql='SELECT * FROM '.$tbl_wiki.', '.$tbl_wiki_conf.' WHERE '.$tbl_wiki_conf.'.page_id='.$tbl_wiki.'.page_id AND '.$tbl_wiki.'.'.$groupfilter.$condition_session.' GROUP BY '.$tbl_wiki.'.page_id'; // new version
	}
	else
	{
		//$sql='SELECT  *  FROM   '.$tbl_wiki.' s1 WHERE visibility=1 AND id=(SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2 WHERE s1.reflink = s2.reflink AND '.$groupfilter.')'; // warning don't use group by reflink because don't return the last version	// old version TODO: Replace by the bottom line

		$sql='SELECT * FROM '.$tbl_wiki.', '.$tbl_wiki_conf.' WHERE visibility=1 AND '.$tbl_wiki_conf.'.page_id='.$tbl_wiki.'.page_id AND '.$tbl_wiki.'.'.$groupfilter.$condition_session.' GROUP BY '.$tbl_wiki.'.page_id'; // new version

	}

	$allpages=Database::query($sql,__LINE__,__FILE__);

	//show table
	if (Database::num_rows($allpages) > 0)
	{
		$row = array ();
		while ($obj = Database::fetch_object($allpages))
		{
			//get author
			$userinfo=Database::get_user_info_from_id($obj->user_id);

			//get time
			$year 	 = substr($obj->dtime, 0, 4);
			$month	 = substr($obj->dtime, 5, 2);
			$day 	 = substr($obj->dtime, 8, 2);
			$hours   = substr($obj->dtime, 11,2);
			$minutes = substr($obj->dtime, 14,2);
			$seconds = substr($obj->dtime, 17,2);

			//get type assignment icon
			if($obj->assignment==1)
			{
				$ShowAssignment='<img src="../img/wiki/assignment.gif" title="'.get_lang('AssignmentDesc').'" alt="'.get_lang('AssignmentDesc').'" />';
			}
			elseif ($obj->assignment==2)
			{
				$ShowAssignment='<img src="../img/wiki/works.gif" title="'.get_lang('AssignmentWork').'" alt="'.get_lang('AssignmentWork').'" />';
			}
			elseif ($obj->assignment==0)
			{
				$ShowAssignment='<img src="../img/wiki/trans.gif" />';
			}

			//get icon task
			if (!empty($obj->task))
			{
				$icon_task='<img src="../img/wiki/task.gif" title="'.get_lang('StandardTask').'" alt="'.get_lang('StandardTask').'" />';
			}
			else
			{
				$icon_task='<img src="../img/wiki/trans.gif" />';
			}

			$row = array ();
			$row[] =$ShowAssignment.$icon_task;
			$row[] = '<a href="'.api_get_self().'?cidReq='.$_course[id].'&action=showpage&title='.urlencode(Security::remove_XSS($obj->reflink)).'&group_id='.Security::remove_XSS($_GET['group_id']).'">'.Security::remove_XSS($obj->title).'</a>';
			$row[] = $obj->user_id <>0 ? '<a href="../user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.api_get_person_name($userinfo['firstname'], $userinfo['lastname']).'</a>' : get_lang('Anonymous').' ('.$obj->user_ip.')';
			$row[] = $year.'-'.$month.'-'.$day.' '.$hours.":".$minutes.":".$seconds;

			if(api_is_allowed_to_edit(false,true)|| api_is_platform_admin())
			{
				$showdelete=' <a href="'.api_get_self().'?cidReq='.$_course[id].'&action=delete&title='.urlencode(Security::remove_XSS($obj->reflink)).'&group_id='.Security::remove_XSS($_GET['group_id']).'"><img src="../img/delete.gif" title="'.get_lang('Delete').'" alt="'.get_lang('Delete').'" />';
			}
			if (api_is_allowed_to_session_edit(false,true) )
			$row[] = '<a href="'.api_get_self().'?cidReq='.$_course[id].'&action=edit&title='.urlencode(Security::remove_XSS($obj->reflink)).'&group_id='.Security::remove_XSS($_GET['group_id']).'"><img src="../img/lp_quiz.png" title="'.get_lang('EditPage').'" alt="'.get_lang('EditPage').'" /></a> <a href="'.api_get_self().'?cidReq='.$_course[id].'&action=discuss&title='.urlencode(Security::remove_XSS($obj->reflink)).'&group_id='.Security::remove_XSS($_GET['group_id']).'"><img src="../img/comment_bubble.gif" title="'.get_lang('Discuss').'" alt="'.get_lang('Discuss').'" /></a> <a href="'.api_get_self().'?cidReq='.$_course[id].'&action=history&title='.urlencode(Security::remove_XSS($obj->reflink)).'&group_id='.Security::remove_XSS($_GET['group_id']).'"><img src="../img/history.gif" title="'.get_lang('History').'" alt="'.get_lang('History').'" /></a> <a href="'.api_get_self().'?cidReq='.$_course[id].'&action=links&title='.urlencode(Security::remove_XSS($obj->reflink)).'&group_id='.Security::remove_XSS($_GET['group_id']).'"><img src="../img/lp_link.png" title="'.get_lang('LinksPages').'" alt="'.get_lang('LinksPages').'" /></a>'.$showdelete;
			$rows[] = $row;
		}

		$table = new SortableTableFromArrayConfig($rows,1,10,'AllPages_table','','','ASC');
		$table->set_additional_parameters(array('cidReq' =>Security::remove_XSS($_GET['cidReq']),'action'=>Security::remove_XSS($_GET['action']),'group_id'=>Security::remove_XSS($_GET['group_id'])));
		$table->set_header(0,get_lang('Type'), true, array ('style' => 'width:30px;'));
		$table->set_header(1,get_lang('Title'), true);
		$table->set_header(2,get_lang('Author').' ('.get_lang('LastVersion').')', true);
		$table->set_header(3,get_lang('Date').' ('.get_lang('LastVersion').')', true);
		if (api_is_allowed_to_session_edit(false,true) )
		$table->set_header(4,get_lang('Actions'), true, array ('style' => 'width:100px;'));
		$table->display();
	}
}

/////////////////////// discuss pages ///////////////////////


if ($_GET['action']=='discuss')
{
	if (api_get_session_id()!=0 && api_is_allowed_to_session_edit(false,true)==false) {
		api_not_allowed();
	}

	if (!$_GET['title'])
	{
		Display::display_error_message(get_lang("MustSelectPage"));
		exit;
    }

	//first extract the date of last version
	$sql='SELECT * FROM '.$tbl_wiki.'WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.$condition_session.' ORDER BY id DESC';
	$result=Database::query($sql,__LINE__,__FILE__);
	$row=Database::fetch_array($result);
	$lastversiondate=$row['dtime'];
	$lastuserinfo=Database::get_user_info_from_id($row['user_id']);

	//select page to discuss
    $sql='SELECT * FROM '.$tbl_wiki.'WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.$condition_session.' ORDER BY id ASC';
	$result=Database::query($sql,__LINE__,__FILE__);
	$row=Database::fetch_array($result);
	$id=$row['id'];
	$firstuserid=$row['user_id'];

	//mode assignment: previous to show  page type
	if(stripslashes($row['assignment'])==1)
	    {
		$icon_assignment='<img src="../img/wiki/assignment.gif" title="'.get_lang('AssignmentDescExtra').'" alt="'.get_lang('AssignmentDescExtra').'" />';
	    }
	elseif(stripslashes($row['assignment'])==2)
	{
		$icon_assignment='<img src="../img/wiki/works.gif" title="'.get_lang('AssignmentWorkExtra').'" alt="'.get_lang('AssignmentWorkExtra').'" />';
	}


	//Show title and form to discuss if page exist
	if ($id!='')
	{
		//Show discussion to students if isn't hidden. Show page to all teachers if is hidden. Mode assignments: If is hidden, show pages to student only if student is the author
		if($row['visibility_disc']==1 || api_is_allowed_to_edit(false,true) || api_is_platform_admin() || ($row['assignment']==2 && $row['visibility_disc']==0 && (api_get_user_id()==$row['user_id'])))
		{
			echo '<div id="wikititle">';

			// discussion action: protecting (locking) the discussion
			if(api_is_allowed_to_edit(false,true) || api_is_platform_admin())
			{
				if (check_addlock_discuss()==1)
				{
					$addlock_disc= '<img src="../img/wiki/unlock.gif" title="'.get_lang('UnlockDiscussExtra').'" alt="'.get_lang('UnlockDiscussExtra').'" />';
					$lock_unlock_disc='unlockdisc';
				}

				else
				{
					$addlock_disc= '<img src="../img/wiki/lock.gif" title="'.get_lang('LockDiscussExtra').'" alt="'.get_lang('LockDiscussExtra').'" />';
					$lock_unlock_disc='lockdisc';
				}
			}
			echo '<span style="float:right">';
			echo '<a href="index.php?action=discuss&amp;actionpage='.$lock_unlock_disc.'&amp;title='.$page.'">'.$addlock_disc.'</a>';
			echo '</span>';

			// discussion action: visibility.  Show discussion to students if isn't hidden. Show page to all teachers if is hidden.


			if(api_is_allowed_to_edit(false,true) || api_is_platform_admin())
			{
				if (check_visibility_discuss()==1)
				{
					/// TODO: 	Fix Mode assignments: If is hidden, show discussion to student only if student is the author
					//if(($row['assignment']==2 && $row['visibility_disc']==0 && (api_get_user_id()==$row['user_id']))==false)
					//{
						//$visibility_disc= '<img src="../img/wiki/invisible.gif" title="'.get_lang('HideDiscussExtra').'" alt="'.get_lang('HideDiscussExtra').'" />';

					//}
					$visibility_disc= '<img src="../img/wiki/visible.gif" title="'.get_lang('ShowDiscussExtra').'" alt="'.get_lang('ShowDiscussExtra').'" />';
					$hide_show_disc='hidedisc';
				}
				else
				{
					$visibility_disc= '<img src="../img/wiki/invisible.gif" title="'.get_lang('HideDiscussExtra').'" alt="'.get_lang('HideDiscussExtra').'" />';
					$hide_show_disc='showdisc';
				}
			}
			echo '<span style="float:right">';
			echo '<a href="index.php?action=discuss&amp;actionpage='.$hide_show_disc.'&amp;title='.$page.'">'.$visibility_disc.'</a>';
			echo '</span>';

			//discussion action: check add rating lock. Show/Hide list to rating for all student

			if(api_is_allowed_to_edit(false,true) || api_is_platform_admin())
			{
				if (check_ratinglock_discuss()==1)
				{
					$ratinglock_disc= '<img src="../img/wiki/rating.gif" title="'.get_lang('UnlockRatingDiscussExtra').'" alt="'.get_lang('UnlockRatingDiscussExtra').'" />';
					$lock_unlock_rating_disc='unlockrating';
				}
				else
				{
					$ratinglock_disc= '<img src="../img/wiki/rating_na.gif" title="'.get_lang('LockRatingDiscussExtra').'" alt="'.get_lang('LockRatingDiscussExtra').'" />';
					$lock_unlock_rating_disc='lockrating';
				}
			}

			echo '<span style="float:right">';
			echo '<a href="index.php?action=discuss&amp;actionpage='.$lock_unlock_rating_disc.'&amp;title='.$page.'">'.$ratinglock_disc.'</a>';
			echo '</span>';

			//discussion action: email notification
			if (check_notify_discuss($page)==1)
			{
				$notify_disc= '<img src="../img/wiki/send_mail_checked.gif" title="'.get_lang('NotifyDiscussByEmail').'" alt="'.get_lang('NotifyDiscussByEmail').'" />';
				$lock_unlock_notify_disc='unlocknotifydisc';
			}
			else
			{
				$notify_disc= '<img src="../img/wiki/send_mail.gif" title="'.get_lang('CancelNotifyDiscussByEmail').'" alt="'.get_lang('CancelNotifyDiscussByEmail').'" />';
				$lock_unlock_notify_disc='locknotifydisc';
			}
			echo '<span style="float:right">';
			echo '<a href="index.php?action=discuss&amp;actionpage='.$lock_unlock_notify_disc.'&amp;title='.$page.'">'.$notify_disc.'</a>';
			echo '</span>';

			echo $icon_assignment.'&nbsp;&nbsp;&nbsp;'.$row['title'];

			echo ' ('.get_lang('MostRecentVersionBy').' <a href="../user/userInfo.php?uInfo='.$lastuserinfo['user_id'].'">'.api_get_person_name($lastuserinfo['firstname'], $lastuserinfo['lastname']).'</a> '.$lastversiondate.$countWPost.')'.$avg_WPost_score.' '; //TODO: read avg score

			echo '</div>';

			if($row['addlock_disc']==1 || api_is_allowed_to_edit(false,true) || api_is_platform_admin()) //show comments but students can't add theirs
			{
				?>
				<form name="form1" method="post" action="">
				<table>
					<tr>
					<td valign="top" ><?php echo get_lang('Comments');?>:</td>
                    <?php  echo '<input type="hidden" name="wpost_id" value="'.md5(uniqid(rand(), true)).'">';//prevent double post ?>
					<td><textarea name="comment" cols="80" rows="5" id="comment"></textarea></td>
					</tr>

					<tr>

					<?php
					//check if rating is allowed
					if($row['ratinglock_disc']==1 || api_is_allowed_to_edit(false,true) || api_is_platform_admin())
					{
						?>
						<td><?php echo get_lang('Rating');?>: </td>
						<td valign="top"><select name="rating" id="rating">
						   <option value="-" selected>-</option>
						   <option value="0">0</option>
						   <option value="1">1</option>
						   <option value="2">2</option>
						   <option value="3">3</option>
						   <option value="4">4</option>
						   <option value="5">5</option>
						   <option value="6">6</option>
						   <option value="7">7</option>
						   <option value="8">8</option>
						   <option value="9">9</option>
						   <option value="10">10</option>
						   </select></td>
						<?php
                    }
					 else
					{
					 	echo '<input type=hidden name="rating" value="-">';// must pass a default value to avoid rate automatically
					}
					?>
					</tr>
					<tr>
			        <td>&nbsp;</td>
					<td> <?php  echo '<button class="save" type="submit" name="Submit"> '.get_lang('Send').'</button>'; ?></td>
				  	</tr>
				</table>
				</form>

				<?php
				if (isset($_POST['Submit']) && double_post($_POST['wpost_id']))
				{
					$dtime = date( "Y-m-d H:i:s" );
					$message_author=api_get_user_id();

					$sql="INSERT INTO $tbl_wiki_discuss (publication_id, userc_id, comment, p_score, dtime) VALUES ('".$id."','".$message_author."','".$_POST['comment']."','".$_POST['rating']."','".$dtime."')";
					$result=Database::query($sql,__FILE__,__LINE__) or die(mysql_error());

					check_emailcue($id, 'D', $dtime, $message_author);

				}
			}//end discuss lock

			echo '<hr noshade size="1">';
			$user_table = Database :: get_main_table(TABLE_MAIN_USER);

			$sql="SELECT * FROM $tbl_wiki_discuss reviews, $user_table user  WHERE reviews.publication_id='".$id."' AND user.user_id='".$firstuserid."' ORDER BY id DESC";
			$result=Database::query($sql,__FILE__,__LINE__) or die(mysql_error());

			$countWPost = Database::num_rows($result);
			echo get_lang('NumComments').": ".$countWPost; //comment's numbers

			$sql="SELECT SUM(p_score) as sumWPost FROM $tbl_wiki_discuss WHERE publication_id='".$id."' AND NOT p_score='-' ORDER BY id DESC";
			$result2=Database::query($sql,__FILE__,__LINE__) or die(mysql_error());
			$row2=Database::fetch_array($result2);

			$sql="SELECT * FROM $tbl_wiki_discuss WHERE publication_id='".$id."' AND NOT p_score='-'";
			$result3=Database::query($sql,__FILE__,__LINE__) or die(mysql_error());
			$countWPost_score= Database::num_rows($result3);

			echo ' - '.get_lang('NumCommentsScore').': '.$countWPost_score;//

			if ($countWPost_score!=0)
			{
				$avg_WPost_score = round($row2['sumWPost'] / $countWPost_score,2).' / 10';
			}
			else
			{
				$avg_WPost_score = $countWPost_score;
			}

			echo ' - '.get_lang('RatingMedia').': '.$avg_WPost_score; // average rating

			$sql='UPDATE '.$tbl_wiki.' SET score="'.Database::escape_string($avg_WPost_score).'" WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.$condition_session;	// check if work ok. TODO:
				Database::query($sql,__FILE__,__LINE__);

			echo '<hr noshade size="1">';
			//echo '<div style="overflow:auto; height:170px;">';

			while ($row=Database::fetch_array($result))
			{
				$userinfo=Database::get_user_info_from_id($row['userc_id']);
				if (($userinfo['status'])=="5")
				{
					$author_status=get_lang('Student');
				}
				else
				{
					$author_status=get_lang('Teacher');
				}

				require_once api_get_path(INCLUDE_PATH).'/lib/usermanager.lib.php';
				$user_id=$row['userc_id'];
				$name = api_get_person_name($userinfo['firstname'], $userinfo['lastname']);
				$attrb=array();
				if ($user_id<>0)
				{
					$image_path = UserManager::get_user_picture_path_by_id($user_id,'web',false, true);
					$image_repository = $image_path['dir'];
					$existing_image = $image_path['file'];
					$author_photo= '<img src="'.$image_repository.$existing_image.'" alt="'.$name.'"  width="40" height="50" align="top" title="'.$name.'"  />';

				}
				else
				{
					$author_photo= '<img src="'.api_get_path(WEB_CODE_PATH)."img/unknown.jpg".'" alt="'.$name.'"  width="40" height="50" align="top"  title="'.$name.'"  />';
				}

				//stars
				$p_score=$row['p_score'];
				switch($p_score){
				case  0:
				$imagerating='<img src="../img/wiki/rating/stars_0.gif"/>';
				break;
				case  1:
				$imagerating='<img src="../img/wiki/rating/stars_5.gif"/>';
				break;
				case  2:
				$imagerating='<img src="../img/wiki/rating/stars_10.gif"/>';
				break;
				case  3:
				$imagerating='<img src="../img/wiki/rating/stars_15.gif"/>';
				break;
				case  4:
				$imagerating='<img src="../img/wiki/rating/stars_20.gif"/>';
				break;
				case  5:
				$imagerating='<img src="../img/wiki/rating/stars_25.gif"/>';
				break;
				case  6:
				$imagerating='<img src="../img/wiki/rating/stars_30.gif"/>';
				break;
				case  7:
				$imagerating='<img src="../img/wiki/rating/stars_35.gif"/>';
				break;
				case  8:
				$imagerating='<img src="../img/wiki/rating/stars_40.gif"/>';
				break;
				case  9:
				$imagerating='<img src="../img/wiki/rating/stars_45.gif"/>';
				break;
				case  10:
				$imagerating='<img src="../img/wiki/rating/stars_50.gif"/>';
				break;
			}

			echo '<p><table>';
			echo '<tr>';
			echo '<td rowspan="2">'.$author_photo.'</td>';
			echo '<td style=" color:#999999"><a href="../user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.api_get_person_name($userinfo['firstname'], $userinfo['lastname']).'</a> ('.$author_status.') '.$row['dtime'].' - '.get_lang('Rating').': '.$row['p_score'].' '.$imagerating.' </td>';
			echo '</tr>';
			echo '<tr>';
			echo '<td>'.$row['comment'].'</td>';
			echo '</tr>';
			echo "</table>";
			echo '<hr noshade size="1">';

			}
		  //  echo"</div>";
		}
		else
		{

			Display::display_warning_message(get_lang('LockByTeacher'),false);

		}
	}
	else
	{

			Display::display_normal_message(get_lang('DiscussNotAvailable'));

	}
}

///in new pages go to new page
if ($_POST['SaveWikiNew'])
{
	display_wiki_entry(Security::remove_XSS($_POST['reflink']));
}

echo "</div>"; // echo "<div id='mainwiki'>";

echo "</div>"; // echo "<div id='wikiwrapper'>";


/*
==============================================================================
FOOTER
==============================================================================
*/
//$_SESSION['_gid'];
Display::display_footer();
?>