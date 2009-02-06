<?php
 
/*
==============================================================================
		INIT SECTION
==============================================================================

*/

/*
-----------------------------------------------------------
	Language Initialisation
-----------------------------------------------------------
*/
$language_file = 'wiki';


if(isset($_GET['id_session']))
{
	$_SESSION['id_session'] = Security::remove_XSS($_GET['id_session']); 
}
/*
-----------------------------------------------------------
	Including necessary files
-----------------------------------------------------------
*/
include('../inc/global.inc.php');  

// Section (for the tabs)
$this_section=SECTION_COURSES; 

require_once (api_get_path(LIBRARY_PATH).'course.lib.php');
require_once (api_get_path(LIBRARY_PATH).'groupmanager.lib.php');
require_once (api_get_path(LIBRARY_PATH).'text.lib.php');
require_once (api_get_path(LIBRARY_PATH).'events.lib.inc.php'); 
require_once (api_get_path(LIBRARY_PATH).'security.lib.php'); 
require_once (api_get_path(INCLUDE_PATH).'lib/mail.lib.inc.php');
require_once (api_get_path(INCLUDE_PATH).'conf/mail.conf.php');
require_once (api_get_path(LIBRARY_PATH).'sortabletable.class.php');
/*
-----------------------------------------------------------
  			ADDITIONAL STYLE INFORMATION
-----------------------------------------------------------
*/
$htmlHeadXtra[] ='<link rel="stylesheet" type="text/css" href="'.api_get_path(WEB_CODE_PATH).'wiki/css/default.css"/>';

/*

/*
-----------------------------------------------------------
	Table definitions
-----------------------------------------------------------
*/
$tbl_wiki = Database::get_course_table(TABLE_WIKI);
$tbl_wiki_discuss = Database::get_course_table(TABLE_WIKI_DISCUSS);
$tbl_wiki_mailcue = Database::get_course_table(TABLE_WIKI_MAILCUE);

/*
-----------------------------------------------------------
	Constants and variables
-----------------------------------------------------------
*/
$tool_name = get_lang('Wiki'); 

$MonthsLong = array (get_lang("JanuaryLong"), get_lang("FebruaryLong"), get_lang("MarchLong"), get_lang("AprilLong"), get_lang("MayLong"), get_lang("JuneLong"), get_lang("JulyLong"), get_lang("AugustLong"), get_lang("SeptemberLong"), get_lang("OctoberLong"), get_lang("NovemberLong"), get_lang("DecemberLong")); 

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
if ($_SESSION['_gid'] OR $_GET['group_id'])
{

	if (isset($_SESSION['_gid']))
	{
		$_clean['group_id']=(int)$_SESSION['_gid'];
	}
	if (isset($_GET['group_id']))
	{
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
 		if (!api_is_allowed_to_edit() and !GroupManager :: is_user_in_group($_user['user_id'], $_SESSION['_gid']))	
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

$is_allowed_to_edit = api_is_allowed_to_edit(); 
api_display_tool_title($tool_name.$add_group_to_title);

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
//$not_allowed_titles=array("Index", "RecentChanges","AllPages", "Categories"); //not used for now	

/*
==============================================================================
		MAIN CODE
==============================================================================
*/


/*
-----------------------------------------------------------
	Introduction section
-----------------------------------------------------------
*/

$fck_attribute['Width'] = '100%';
$fck_attribute['Height'] = '300';
$fck_attribute['ToolbarSet'] = 'Introduction';

Display::display_introduction_section(TOOL_WIKI);

$fck_attribute = null; // Clearing this global variable immediatelly after it has been used.


/*
-----------------------------------------------------------
	Wiki configuration settings
-----------------------------------------------------------
*/

$fck_attribute['Width'] = '100%';
$fck_attribute['ToolbarSet'] = 'Wiki';
if(!api_is_allowed_to_edit())
{
	$fck_attribute['Config']['UserStatus'] = 'student';
}

/*
-----------------------------------------------------------
  			ACTIONS
-----------------------------------------------------------
*/

// saving a change
if (isset($_POST['SaveWikiChange']) AND $_POST['title']<>'')
{
	if(empty($_POST['title']))
	{ 		
		Display::display_normal_message(get_lang("NoWikiPageTitle"));
	}
	else
	{
		$return_message=save_wiki();	
	}
}

//saving a new wiki entry
if (isset($_POST['SaveWikiNew']))
{
	if(empty($_POST['title']))
	{
		Display::display_normal_message(get_lang("NoWikiPageTitle")); 
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
	   }
	}
}


// displaying the message if there is a message to be displayed
if (!empty($return_message))
{
	Display::display_confirmation_message($return_message, false); 
}


// check last version
if ($_GET['view'])
{
	$sql='SELECT * FROM '.$tbl_wiki.'WHERE id="'.Database::escape_string($_GET['view']).'"'; //current view
		$result=api_sql_query($sql,__LINE__,__FILE__);
		$current_row=Database::fetch_array($result);
	
	$sql='SELECT * FROM '.$tbl_wiki.'WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.' ORDER BY id DESC'; //last version
		$result=api_sql_query($sql,__LINE__,__FILE__);
		$last_row=Database::fetch_array($result);
		
	if ($_GET['view']<$last_row['id'])
	{
	   $message= '(<a href="index.php?cidReq='.$_course[id].'&action=showpage&amp;title='.$current_row['reflink'].'&view='.Security::remove_XSS($_GET['view']).'&group_id='.$current_row['group_id'].'" title="'.get_lang('CurrentVersion').'">'.$current_row['version'].'</a> / <a href="index.php?cidReq='.$_course[id].'&action=showpage&amp;title='.$last_row['reflink'].'&group_id='.$last_row['group_id'].'" title="'.get_lang('LastVersion').'">'.$last_row['version'].'</a>) '.get_lang('NoAreSeeingTheLastVersion').'<br />'.get_lang("ConvertToLastVersion").': <a href="index.php?cidReq='.$_course[id].'&action=restorepage&amp;title='.$last_row['reflink'].'&view='.Security::remove_XSS($_GET['view']).'">'.get_lang("Restore").'</a>';
	   
	   Display::display_warning_message($message,false);
	}
		
	///restore page 
	if ($_GET['action']=='restorepage')
	{	    
		//Only teachers and platform admin can edit the index page. Only teachers and platform admin can edit an assignment teacher
		if(($current_row['reflink']=='index' || $current_row['reflink']=='' || $current_row['assignment']==1) && (!api_is_allowed_to_edit()))
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
				if(api_is_allowed_to_edit() || api_is_platform_admin() || GroupManager :: is_user_in_group($_user['user_id'],$_SESSION['_gid']))
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
					if(api_is_allowed_to_edit() || api_is_platform_admin())
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
				if (check_protect_page() && (api_is_allowed_to_edit()==false || api_is_platform_admin()==false))
				{ 
					   Display::display_normal_message(get_lang('PageLockedExtra'));
				}
				else
				{
					Display::display_confirmation_message(restore_wikipage($current_row['reflink'], $current_row['title'], $current_row['content'], $current_row['group_id'], $current_row['assignment'], $current_row['progress'], $current_row['version'], $last_row['version'], $current_row['linksto']).': <a href="index.php?cidReq='.$_course[id].'&action=showpage&amp;title='.$last_row['reflink'].'&group_id='.$last_row['group_id'].'">'.$last_row['title'].'</a>',false);
				}						
			}
		}
	}			
}


if ($_GET['action']=='deletewiki'){
	
	if(api_is_allowed_to_edit() || api_is_platform_admin()) 
 	{		
		$message = get_lang('ConfirmDeleteWiki')."</p>"."<p>"."<a href=\"index.php\">".get_lang("No")."</a>"."&nbsp;&nbsp;|&nbsp;&nbsp;"."<a href=\"".api_get_self()."?action=deletewiki&amp;delete=yes\">".get_lang("Yes")."</a>"."</p>";			

		if (!isset($_GET['delete']))
		{
			Display::display_warning_message($message,false);
		}
			    
		if ($_GET['delete'] == 'yes')
		{
			$return_message=delete_wiki(); 
			Display::display_confirmation_message($return_message); 
	    }	    
	 }	
	 else
	 {
	 	Display::display_normal_message(get_lang("OnlyAdminDeleteWiki")); 
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

/*
-----------------------------------------------------------
  			WIKI MENU
-----------------------------------------------------------
*/

echo "<div id='menuwiki'>";

echo '<ul id="tabnav">';


//menu home
echo '<li><a href="index.php?cidReq='.$_course[id].'&action=show&amp;title=index&group_id='.$_clean['group_id'].'"'.is_active_navigation_tab('show').'><img src="../img/wiki/whome.png" title="'.get_lang('StartPage').'" align="absmiddle"/> '.get_lang('HomeWiki').'</a></li>'; 

//menu find
echo '<li><a href="index.php?cidReq='.$_course[id].'&action=searchpages&group_id='.$_clean['group_id'].'"'.is_active_navigation_tab('searchpages').'><img src="../img/wiki/wsearch.png" title="'.get_lang('SearchPages').'" align="absmiddle"/></a></li>';

//menu all pages
echo '<li><a href="index.php?cidReq='.$_course[id].'&action=allpages&group_id='.$_clean['group_id'].'"'.is_active_navigation_tab('allpages').'><img src="../img/wiki/wallpages.png" title="'.get_lang('AllPages').'" align="absmiddle"/></a></li>';

//menu recent changes
echo '<li><a href="index.php?cidReq='.$_course[id].'&action=recentchanges&group_id='.$_clean['group_id'].'"'.is_active_navigation_tab('recentchanges').'><img src="../img/wiki/wrecentchanges.png" title="'.get_lang('RecentChanges').'" align="absmiddle"/></a></li>';

//menu delete all wiki
if(api_is_allowed_to_edit() || api_is_platform_admin()) 
{	
		echo '<li><a href="index.php?action=deletewiki&amp;title='.$page.'"'.is_active_navigation_tab('deletewiki').'"><img src="../img/wiki/wdeletewiki.png" title="'.get_lang('DeleteWiki').'" align="absmiddle"/></a></li>';
}

//menu more
echo '<li><a href="index.php?action=more&amp;title='.$page.'"'.is_active_navigation_tab('more').'"><img src="../img/wiki/wmore.png" title="'.get_lang('More').'" align="absmiddle"/></a></li>';

//menu add page
echo '<li><a href="index.php?cidReq='.$_course[id].'&action=addnew&group_id='.$_clean['group_id'].'"'.is_active_navigation_tab('addnew').'><img src="../img/wiki/wadd.png" title="'.get_lang('AddNew').'" align="absmiddle"/></a></li>';

//menu show page
echo '<li><a href="index.php?cidReq='.$_course[id].'&action=showpage&amp;title='.$page.'&group_id='.$_clean['group_id'].'"'.is_active_navigation_tab('showpage').' style="margin-left:20px;"><img src="../img/wiki/wviewpage.png" title="'.get_lang('ShowThisPage').'" align="absmiddle"/> '.get_lang('Page').'</a></li>';

//menu edit page
if ($_clean['group_id'])
{
	echo '<li><a href="index.php?cidReq='.$_course[id].'&action=edit&amp;title='.$page.'&group_id='.$_clean['group_id'].'"'.is_active_navigation_tab('edit').'><img src="../img/wiki/wedit.png" title="'.get_lang('EditThisPage').'" align="absmiddle"/> '.get_lang('EditPage').'</a></li>';

}
else
{
	echo '<li><a href="index.php?cidReq='.$_course[id].'&action=edit&amp;title='.$page.'"'.is_active_navigation_tab('edit').'><img src="../img/wiki/wedit.png" title="'.get_lang('EditThisPage').'" align="absmiddle"/> '.get_lang('EditPage').'</a></li>';
	
}

//menu discuss page
echo '<li><a href="index.php?action=discuss&amp;title='.$page.'"'.is_active_navigation_tab('discuss').'"><img src="../img/wiki/wdiscuss.png" title="'.get_lang('DiscussThisPage').'" align="absmiddle"/> '.get_lang('Discuss').'</a></li>';

//menu history
if ($_clean['group_id']!=0)
{
	echo '<li><a href="index.php?cidReq='.$_course[id].'&action=history&amp;title='.$page.'&group_id='.$_clean['group_id'].'"'.is_active_navigation_tab('history').'><img src="../img/wiki/whistory.png" title="'.get_lang('ShowPageHistory').'" align="absmiddle"/> '.get_lang('History').'</a></li>';

}
else
{
	echo '<li><a href="index.php?cidReq='.$_course[id].'&action=history&amp;title='.$page.'"'.is_active_navigation_tab('history').'><img src="../img/wiki/whistory.png" title="'.get_lang('ShowPageHistory').'" align="absmiddle"/> '.get_lang('History').'</a></li>';
}

//menu linkspages
echo '<li><a href="index.php?action=links&amp;title='.$page.'"'.is_active_navigation_tab('links').'"><img src="../img/wiki/wlinkspages.png" title="'.$ShowLinksPages.'" align="absmiddle"/> '.$LinksPages.'</a></li>';

//menu delete wikipage
if(api_is_allowed_to_edit() || api_is_platform_admin()) 
{
	echo '<li><a href="index.php?action=delete&amp;title='.$page.'"'.is_active_navigation_tab('delete').'"><img src="../img/wiki/wdelete.png" title="'.get_lang('DeleteThisPage').'" align="absmiddle"/> '.get_lang('Delete').'</a></li>';
}
echo '</ul></div>';

/*
-----------------------------------------------------------
  			MAIN WIKI AREA
-----------------------------------------------------------
*/
echo "<div id='mainwiki'>";


/////////////////////// more options /////////////////////// Juan Carlos Ra�a Trabado

if ($_GET['action']=='more')
{
	echo '<br />'; 
	echo '<b>'.get_lang('More').'</b><br />'; 
    echo '<hr>';
	
	if(api_is_allowed_to_edit() || api_is_platform_admin())
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
	//echo '<li><a href="index.php?cidReq='.$_course[id].'&action=mostlinked&group_id='.$_clean['group_id'].'">'.get_lang('MostLinkedPages').'</a></li>';//TODO
	
	//Submenu Dead end pages
	//echo '<li><a href="index.php?cidReq='.$_course[id].'&action=deadend&group_id='.$_clean['group_id'].'">'.get_lang('DeadEndPages').'</a></li>';//TODO	
	
	//Submenu Most new pages (not versions)
	//echo '<li><a href="index.php?cidReq='.$_course[id].'&action=mnew&group_id='.$_clean['group_id'].'">'.get_lang('MostNewPages').'</a></li>';//TODO
	
	//Submenu Most long pages
	//echo '<li><a href="index.php?cidReq='.$_course[id].'&action=mnew&group_id='.$_clean['group_id'].'">'.get_lang('MostLongPages').'</a></li>';//TODO
	
	//Submenu Protected pages
	//echo '<li><a href="index.php?cidReq='.$_course[id].'&action=protected&group_id='.$_clean['group_id'].'">'.get_lang('ProtectedPages').'</a></li>';//TODO
	
	//Submenu Hidden pages
	//echo '<li><a href="index.php?cidReq='.$_course[id].'&action=hidden&group_id='.$_clean['group_id'].'">'.get_lang('HiddenPages').'</a></li>';//TODO	
	
	//Submenu Most discuss pages
	//echo '<li><a href="index.php?cidReq='.$_course[id].'&action=mdiscuss&group_id='.$_clean['group_id'].'">'.get_lang('MostDiscussPages').'</a></li>';//TODO	
	
	//Submenu Best scored pages
	//echo '<li><a href="index.php?cidReq='.$_course[id].'&action=mscored&group_id='.$_clean['group_id'].'">'.get_lang('BestScoredPages').'</a></li>';//TODO	
	
	//Submenu Pages with more progress
	//echo '<li><a href="index.php?cidReq='.$_course[id].'&action=mprogress&group_id='.$_clean['group_id'].'">'.get_lang('MProgressPages').'</a></li>';//TODO	
		
	//Submenu Most active users in discuss
	//echo '<li><a href="index.php?cidReq='.$_course[id].'&action=mactiveusers&group_id='.$_clean['group_id'].'">'.get_lang('MostDiscussUsers').'</a></li>';//TODO
	
	//Submenu Individual assignments
	//echo '<li><a href="index.php?cidReq='.$_course[id].'&action=assignments&group_id='.$_clean['group_id'].'">'.get_lang('Assignments').'</a></li>';//TODO
	
	//Submenu Delayed assignments
	//echo '<li><a href="index.php?cidReq='.$_course[id].'&action=delayed&group_id='.$_clean['group_id'].'">'.get_lang('DelayedAssignments').'</a></li>';//TODO
	
	//Submenu Random page
	//echo '<li><a href="index.php?cidReq='.$_course[id].'&action=mrandom&group_id='.$_clean['group_id'].'">'.get_lang('RandomPage').'</a></li>';//TODO

}

/////////////////////// Most active users /////////////////////// Juan Carlos Ra�a Trabado

if ($_GET['action']=='mactiveusers')
{
	echo '<br />';
	echo '<b>'.get_lang('MostActiveUsers').'</b><br />'; 
	echo '<hr>';	
	
	$sql='SELECT *, COUNT(*) AS NUM_EDIT FROM '.$tbl_wiki.'  WHERE  '.$groupfilter.' GROUP BY user_id';
	$allpages=api_sql_query($sql,__FILE__,__LINE__);		

	//show table
	if (mysql_num_rows($allpages) > 0)
	{
		$row = array ();
		while ($obj = mysql_fetch_object($allpages))
		{
			$userinfo=Database::get_user_info_from_id($obj->user_id);
			$row = array ();
						
			$row[] = $obj->user_id <>0 ? '<a href="../user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.$userinfo['lastname'].', '.$userinfo['firstname'].'</a><a href="'.$_SERVER['PHP_SELF'].'?cidReq='.$_course[id].'&action=usercontrib&user_id='.urlencode($row['user_id']).'&group_id='.Security::remove_XSS($_GET['group_id']).'"></a>' : get_lang('Anonymous').' ('.$obj->user_ip.')';				
			$row[] ='<a href="'.$_SERVER['PHP_SELF'].'?cidReq='.$_course[id].'&action=usercontrib&user_id='.urlencode($obj->user_id).'&group_id='.Security::remove_XSS($_GET['group_id']).'">'.$obj->NUM_EDIT.'</a>';				
			$rows[] = $row;
		}
	
		$table = new SortableTableFromArrayConfig($rows,1,10,'MostActiveUsersA_table','','','DESC');
		$table->set_additional_parameters(array('cidReq' =>Security::remove_XSS($_GET['cidReq']),'action'=>Security::remove_XSS($_GET['action']),'group_id'=>Security::remove_XSS($_GET['group_id'])));		
		$table->set_header(0,get_lang('Author'), true, array ('style' => 'width:30px;'));
		$table->set_header(1,get_lang('Contributions'), true);
		$table->display();
	}			
}


/////////////////////// User contributions /////////////////////// Juan Carlos Ra�a Trabado

if ($_GET['action']=='usercontrib')
{
	$userinfo=Database::get_user_info_from_id(Security::remove_XSS($_GET['user_id']));
	echo '<br />';
	echo '<b>'.get_lang('UserContributions').': <a href="../user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.$userinfo['lastname'].', '.$userinfo['firstname'].'</a><a href="'.$_SERVER['PHP_SELF'].'?cidReq='.$_course[id].'&action=usercontrib&user_id='.urlencode($row['user_id']).'&group_id='.Security::remove_XSS($_GET['group_id']).'"></a></b><br />';	
	echo '<hr>';	
		
	
	if(api_is_allowed_to_edit() || api_is_platform_admin()) //only by professors if page is hidden
	{
		$sql='SELECT * FROM '.$tbl_wiki.'  WHERE  '.$groupfilter.' AND user_id="'.Security::remove_XSS($_GET['user_id']).'"';		
	}
	else
	{
		$sql='SELECT * FROM '.$tbl_wiki.'  WHERE  '.$groupfilter.' AND user_id="'.Security::remove_XSS($_GET['user_id']).'" AND visibility=1';	
	}	
	
	$allpages=api_sql_query($sql,__FILE__,__LINE__);
		
	//show table
	if (mysql_num_rows($allpages) > 0)
	{
		$row = array ();
		while ($obj = mysql_fetch_object($allpages))
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
			$row[] = $day.' '.$MonthsLong[$month-1].' '.$year.' '.$hours.":".$minutes.":".$seconds;	
			$row[] =$ShowAssignment;
			
			$row[] = '<a href="'.$_SERVER['PHP_SELF'].'?cidReq='.$_course[id].'&action=showpage&title='.urlencode($obj->reflink).'&view='.$obj->id.'&group_id='.Security::remove_XSS($_GET['group_id']).'">'.$obj->title.'</a>';
			$row[] =$obj->version;	
			$row[] =$obj->comment;		
			//$row[] =strlen($obj->comment)>30 ? substr($obj->comment,0,30).'...' : $obj->comment;		
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

/////////////////////// Most changed pages /////////////////////// Juan Carlos Ra�a Trabado

if ($_GET['action']=='mostchanged')
{
	echo '<br />';
	echo '<b>'.get_lang('MostChangedPages').'</b><br />'; 
	echo '<hr>';
	
	
	if(api_is_allowed_to_edit() || api_is_platform_admin()) //only by professors if page is hidden
	{
		$sql='SELECT *, MAX(version) AS MAX FROM '.$tbl_wiki.'  WHERE  '.$groupfilter.' GROUP BY reflink';
	}
	else
	{	
		$sql='SELECT *, MAX(version) AS MAX FROM '.$tbl_wiki.'  WHERE  '.$groupfilter.' AND visibility=1 GROUP BY reflink';
	}	
	
	$allpages=api_sql_query($sql,__FILE__,__LINE__);
		
	//show table
	if (mysql_num_rows($allpages) > 0)
	{
		$row = array ();
		while ($obj = mysql_fetch_object($allpages))
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
			$row[] = '<a href="'.$_SERVER['PHP_SELF'].'?cidReq='.$_course[id].'&action=showpage&title='.urlencode($obj->reflink).'&group_id='.Security::remove_XSS($_GET['group_id']).'">'.$obj->title.'</a>';			
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

/////////////////////// Most visited pages /////////////////////// Juan Carlos Ra�a Trabado

if ($_GET['action']=='mvisited')
{
	echo '<br />';
	echo '<b>'.get_lang('MostVisitedPages').'</b><br />'; 
	echo '<hr>';	
	
	if(api_is_allowed_to_edit() || api_is_platform_admin()) //only by professors if page is hidden
	{	
		$sql='SELECT *, SUM(hits) AS tsum FROM '.$tbl_wiki.'  WHERE  '.$groupfilter.' GROUP BY reflink';
	}
	else
	{			
		$sql='SELECT *, SUM(hits) AS tsum FROM '.$tbl_wiki.'  WHERE  '.$groupfilter.' AND visibility=1 GROUP BY reflink';
	}		
	
	$allpages=api_sql_query($sql,__FILE__,__LINE__);

	//show table
	if (mysql_num_rows($allpages) > 0)
	{
		$row = array ();
		while ($obj = mysql_fetch_object($allpages))
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
			$row[] = '<a href="'.$_SERVER['PHP_SELF'].'?cidReq='.$_course[id].'&action=showpage&title='.urlencode($obj->reflink).'&group_id='.Security::remove_XSS($_GET['group_id']).'">'.$obj->title.'</a>';			
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

/////////////////////// Wanted pages /////////////////////// Juan Carlos Ra�a Trabado

if ($_GET['action']=='wanted')
{
	echo  '<br />';
	echo '<b>'.get_lang('WantedPages').'</b><br />'; 
	echo  '<hr>';
	$pages = array();
	$refs = array();	
	$sort_wanted=array();

	//get name pages
	$sql='SELECT * FROM '.$tbl_wiki.'  WHERE  '.$groupfilter.' GROUP BY reflink ORDER BY reflink ASC';
	$allpages=api_sql_query($sql,__FILE__,__LINE__);
		
	while ($row=Database::fetch_array($allpages))
	{
		$pages[] = $row['reflink'];
	}
	
	//get name refs in last pages and make a unique list
	$sql='SELECT  *  FROM   '.$tbl_wiki.' s1 WHERE '.$groupfilter.' AND id=(SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2 WHERE s1.reflink = s2.reflink)';
	$allpages=api_sql_query($sql,__FILE__,__LINE__);	
	while ($row=Database::fetch_array($allpages))
	{	
		//$row['linksto']= str_replace("\n".$row["reflink"]."\n", "\n", $row["linksto"]); //remove self reference. TODO check
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

/////////////////////// Orphaned pages /////////////////////// Juan Carlos Ra�a Trabado

if ($_GET['action']=='orphaned')
{
	echo '<br />';
	echo '<b>'.get_lang('OrphanedPages').'</b><br />'; 
	echo '<hr>';	
		
	$pages = array();
   	$refs = array();
  	$orphaned = array();
	
	//get name pages	
	$sql='SELECT * FROM '.$tbl_wiki.'  WHERE  '.$groupfilter.' GROUP BY reflink ORDER BY reflink ASC';
	$allpages=api_sql_query($sql,__FILE__,__LINE__);	
	while ($row=Database::fetch_array($allpages))
	{			   
		$pages[] = $row['reflink'];
	}
	
	//get name refs in last pages and make a unique list	
	$sql='SELECT  *  FROM   '.$tbl_wiki.' s1 WHERE '.$groupfilter.' AND id=(SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2 WHERE s1.reflink = s2.reflink)';
	$allpages=api_sql_query($sql,__FILE__,__LINE__);	
	while ($row=Database::fetch_array($allpages))
	{			
		//$row['linksto']= str_replace("\n".$row["reflink"]."\n", "\n", $row["linksto"]); //remove self reference. TODO check
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
		if(api_is_allowed_to_edit() || api_is_platform_admin()) //only by professors if page is hidden
		{
			$sql='SELECT  *  FROM   '.$tbl_wiki.' WHERE '.$groupfilter.' AND reflink="'.$vshow.'" GROUP BY reflink';	
		}
		else
		{
			$sql='SELECT  *  FROM   '.$tbl_wiki.' WHERE '.$groupfilter.' AND reflink="'.$vshow.'" AND visibility=1 GROUP BY reflink';		
		}			
		
		$allpages=api_sql_query($sql,__FILE__,__LINE__);		
		
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
			
			echo '<li>'.$ShowAssignment.'<a href="'.$_SERVER['PHP_SELF'].'?cidReq='.$_course[id].'&action=showpage&title='.urlencode($row['reflink']).'&group_id='.Security::remove_XSS($_GET['group_id']).'">'.$row['title'].'</a></li>';
		}
		echo '</ul>';
	}
	
}

/////////////////////// delete current page /////////////////////// Juan Carlos Ra�a Trabado

if ($_GET['action']=='delete')
{
	
	if(api_is_allowed_to_edit() || api_is_platform_admin())
	{
	    echo '<br />'; 
	    echo '<b>'.get_lang('DeletePageHistory').'</b>'; 
	    echo '<hr>';
		   
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
			api_sql_query($sql,__FILE__,__LINE__);
			
			$sql='DELETE '.$tbl_wiki_mailcue.' FROM '.$tbl_wiki.', '.$tbl_wiki_mailcue.' WHERE '.$tbl_wiki.'.reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$tbl_wiki.'.'.$groupfilter.' AND '.$tbl_wiki_mailcue.'.id='.$tbl_wiki.'.id';
			api_sql_query($sql,__FILE__,__LINE__);
			
			$sql='DELETE FROM '.$tbl_wiki.' WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.'';
	  		api_sql_query($sql,__FILE__,__LINE__);		
			
			check_emailcue(0, 'E');
			
	  		Display::display_confirmation_message(get_lang('WikiPageDeleted')); 
		}
	}
	else
	{
		Display::display_normal_message(get_lang("OnlyAdminDeletePageWiki"));		
	}	
}


/////////////////////// delete all wiki /////////////////////// Juan Carlos Ra�a Trabado

if ($_GET['action']=='deletewiki')
{
	    echo '<br />'; 
	    echo '<b>'.get_lang('DeleteWiki').'</b>'; 
	    echo '<hr>';
}


/////////////////////// search pages /////////////////////// Juan Carlos Ra�a Trabado
//// 1 Searchpages: input search

if ($_GET['action']=='searchpages')
{
    echo '<br />'; 
    echo '<b>'.$SearchPages.'</b>'; 
    echo '<hr>';
		
	if (!$_POST['Skeyword'])
	{	
		echo '<form id="fsearch" method="POST" action="index.php?action=showsearchpages">';		
		echo '<input type="text" name="Skeyword" >';
		echo '<button class="search" type="submit">'.get_lang('Search').'</button></br></br>';	
		echo '<input type="checkbox" name="Scontent" value="1"> '.get_lang('AlsoSearchContent');
		echo '</form>';		
	}
}

//// 2 SearchPages: find and show pages

if ($_GET['action']=='showsearchpages')
{
	echo '<br />'; 
    echo '<b>'.$Search.'</b>: '.$_POST['Skeyword']; //TODO: post is lost when a table has some pages
    echo '<hr>';
	
	$_clean['group_id']=(int)$_SESSION['_gid'];	
	
	if(api_is_allowed_to_edit() || api_is_platform_admin()) //only by professors if page is hidden
	{
		if($_POST['Scontent']=="1")
		{
			$sql="SELECT * FROM ".$tbl_wiki." s1 WHERE  ".$groupfilter." AND title LIKE '%".$_POST['Skeyword']."%' OR content LIKE '%".$_POST['Skeyword']."%' AND id=(SELECT MAX(s2.id) FROM ".$tbl_wiki." s2 WHERE s1.reflink = s2.reflink)";// warning don't use group by reflink because don't return the last version			
		}
		else
		{
			$sql="SELECT * FROM ".$tbl_wiki." s1 WHERE  ".$groupfilter." AND title LIKE '%".$_POST['Skeyword']."%' AND id=(SELECT MAX(s2.id) FROM ".$tbl_wiki." s2 WHERE s1.reflink = s2.reflink)";// warning don't use group by reflink because don't return the last version
		}
	}
	else
	{	
		if($_POST['Scontent']=="1")
		{
		
			$sql="SELECT * FROM ".$tbl_wiki." s1 WHERE  ".$groupfilter." AND visibility=1 AND title LIKE '%".$_POST['Skeyword']."%' OR content LIKE '%".$_POST['Skeyword']."%' AND id=(SELECT MAX(s2.id) FROM ".$tbl_wiki." s2 WHERE s1.reflink = s2.reflink)";// warning don't use group by reflink because don't return the last version				
		}
		else
		{
			$sql="SELECT * FROM ".$tbl_wiki." s1 WHERE  ".$groupfilter." AND visibility=1 AND title LIKE '%".$_POST['Skeyword']."%' AND id=(SELECT MAX(s2.id) FROM ".$tbl_wiki." s2 WHERE s1.reflink = s2.reflink)";// warning don't use group by reflink because don't return the last version
		}		
	}
			
	$result=api_sql_query($sql,__LINE__,__FILE__);				
	
	//show table
	if (mysql_num_rows($result) > 0)
	{
		$row = array ();
		while ($obj = mysql_fetch_object($result))
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
			$row[] = '<a href="'.$_SERVER['PHP_SELF'].'?cidReq='.$_course[id].'&action=showpage&title='.urlencode($obj->reflink).'&group_id='.Security::remove_XSS($_GET['group_id']).'">'.$obj->title.'</a>';				
			$row[] = $obj->user_id <>0 ? '<a href="../user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.$userinfo['lastname'].', '.$userinfo['firstname'].'</a>' : get_lang('Anonymous').' ('.$obj->user_ip.')';				
			$row[] = $day.' '.$MonthsLong[$month-1].' '.$year.' '.$hours.":".$minutes.":".$seconds;				
			$rows[] = $row;
		}
	
		$table = new SortableTableFromArrayConfig($rows,1,10,'SearchPages_table','','','ASC');				
		$table->set_additional_parameters(array('cidReq' =>$_GET['cidReq'],'action'=>$_GET['action'],'group_id'=>Security::remove_XSS($_GET['group_id'])));		
		
		$table->set_header(0,get_lang('Type'), true, array ('style' => 'width:30px;'));			
		$table->set_header(1,get_lang('Title'), true);		
		$table->set_header(2,get_lang('Author'), true);
		$table->set_header(3,get_lang('Date'), true);
		
		$table->display();
	}		
} 	


///////////////////////  What links here. Show pages that have linked this page /////////////////////// Juan Carlos Ra�a Trabado

if ($_GET['action']=='links')
{
    echo '<br />'; 
    echo '<b>'.$LinksPages.'</b>';
    echo '<hr>';
	
	if (!$_GET['title'])
	{
	   Display::display_normal_message(get_lang("MustSelectPage"));	   	   
    }
	else
	{	
	
		$sql='SELECT * FROM '.$tbl_wiki.' WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.'';		
		$result=api_sql_query($sql,__FILE__,__LINE__);	
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
				
		echo $LinksPagesFrom.': '.$ShowAssignment.'<a href="'.$_SERVER['PHP_SELF'].'?cidReq='.$_course[id].'&action=showpage&title='.$page.'&group_id='.Security::remove_XSS($_GET['group_id']).'">'.$row['title'].'</a>';
		
		//fix index to title Main page into linksto
		if ($page=='index')
		{	
			$page=str_replace(' ','_',get_lang('DefaultTitle'));
		}		
		
		//table
		
		if(api_is_allowed_to_edit() || api_is_platform_admin()) //only by professors if page is hidden
		{				
			$sql="SELECT * FROM ".$tbl_wiki." s1 WHERE  ".$groupfilter." AND linksto LIKE '%".html_entity_decode(Database::escape_string(stripslashes(urldecode($page))))." %' AND id=(SELECT MAX(s2.id) FROM ".$tbl_wiki." s2 WHERE s1.reflink = s2.reflink)"; //add blank space after like '%" " %' to identify each word. 						
		}
		else
		{	
			$sql="SELECT * FROM ".$tbl_wiki." s1 WHERE  ".$groupfilter." AND visibility=1 AND linksto LIKE '%".html_entity_decode(Database::escape_string(stripslashes(urldecode($page))))." %' AND id=(SELECT MAX(s2.id) FROM ".$tbl_wiki." s2 WHERE s1.reflink = s2.reflink)"; //add blank space after like '%" " %' to identify each word		
		}		

		$allpages=api_sql_query($sql,__LINE__,__FILE__);	
	
		//show table
		if (mysql_num_rows($allpages) > 0)
		{
			$row = array ();
			while ($obj = mysql_fetch_object($allpages))
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
				$row[] = '<a href="'.$_SERVER['PHP_SELF'].'?cidReq='.$_course[id].'&action=showpage&title='.urlencode($obj->reflink).'&group_id='.Security::remove_XSS($_GET['group_id']).'">'.$obj->title.'</a>';
				$row[] = $obj->user_id <>0 ? '<a href="../user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.$userinfo['lastname'].', '.$userinfo['firstname'].'</a>' : get_lang('Anonymous').' ('.$obj->user_ip.')';	
				$row[] = $day.' '.$MonthsLong[$month-1].' '.$year.' '.$hours.":".$minutes.":".$seconds;					
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
	
	//first, check if page index was created. chektitle=false
	if (checktitle('index'))
	{		
		if(api_is_allowed_to_edit() || api_is_platform_admin()) 
		{
			Display::display_normal_message(get_lang('GoAndEditMainPage'));
		}
		else
		{		
			return Display::display_normal_message(get_lang('WikiStandBy'));
		}		
	}
	
	elseif (check_addnewpagelock() && (api_is_allowed_to_edit()==false || api_is_platform_admin()==false))
	{
		Display::display_normal_message(get_lang('AddPagesLocked')); 		
	}
	else
	{  
		if(api_is_allowed_to_edit() || api_is_platform_admin() || GroupManager :: is_user_in_group($_user['user_id'],$_SESSION['_gid']) || Security::remove_XSS($_GET['group_id'])==0)
		{					
			echo '<br />'; 
			echo '<b>'.get_lang('AddNew').'</b>'; 
			echo '<hr>'; 
			display_new_wiki_form();		
		}
		else
		{
			Display::display_normal_message(get_lang('OnlyAddPagesGroupMembers')); 
		}		
	} 
}



/////////////////////// show home page ///////////////////////

if (!$_GET['action'] OR $_GET['action']=='show' AND !$_POST['SaveWikiNew'])
{
	display_wiki_entry();
}


/////////////////////// show current page ///////////////////////

if ($_GET['action']=='showpage' AND !$_POST['SaveWikiNew'])
{    
	display_wiki_entry();	
}


/////////////////////// edit current page ///////////////////////

if ($_GET['action']=='edit')
{ 	
	$_clean['group_id']=(int)$_SESSION['_gid'];				
				
	$sql='SELECT * FROM '.$tbl_wiki.'WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.' ORDER BY id DESC';
	$result=api_sql_query($sql,__LINE__,__FILE__);
	$row=Database::fetch_array($result); // we do not need a while loop since we are always displaying the last version				


	//Only teachers and platform admin can edit the index page. Only teachers and platform admin can edit an assignment teacher
	if(($row['reflink']=='index' || $row['reflink']=='' || $row['assignment']==1) && (!api_is_allowed_to_edit()))
	{
  
      Display::display_normal_message(get_lang('OnlyEditPagesCourseManager'));
  
	}
    else
	{	
		$PassEdit=false;		
       
	    //check if is a wiki group
		if($_clean['group_id']!=0)
		{
			//Only teacher, platform admin and group members can edit a wiki group
			if(api_is_allowed_to_edit() || api_is_platform_admin() || GroupManager :: is_user_in_group($_user['user_id'],$_SESSION['_gid']))
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
			    if(api_is_allowed_to_edit() || api_is_platform_admin())
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
	 		if (check_protect_page() && (api_is_allowed_to_edit()==false || api_is_platform_admin()==false))
  	   	    { 
    		       Display::display_normal_message(get_lang('PageLockedExtra'));
		    }
			else
			{			
				if ($row['content']=='' AND $row['title']=='' AND $page='index')
				{				 
					$content=sprintf(get_lang('DefaultContent'),api_get_path(WEB_IMG_PATH));
					$title=get_lang('DefaultTitle');
				}
				else
				{								
					$content=$row['content'];
					$title=$row['title'];								
				}
			    echo '<div id="wikititle">';				
				echo $icon_assignment.'&nbsp;&nbsp;&nbsp;'.$title.'</div>'; 
				echo '<div id="wikicontent">'; 
				echo '<form name="form1" method="post" action="'.$_SERVER['PHP_SELF'].'?action=showpage&amp;title='.$page.'&group_id='.Security::remove_XSS($_GET['group_id']).'">'; 
				echo '<input type="hidden" name="reflink" value="'.$page.'">';
				echo '<input type="hidden" name="title" value="'.stripslashes($title).'">'; 
				
				api_disp_html_area('content',stripslashes($content),'300px');	
				echo '<br/>';
	            echo '<br/>'; 	
				//if(api_is_allowed_to_edit() || api_is_platform_admin()) //off for now
				//{ 
				echo get_lang('Comments').':&nbsp;&nbsp;<input type="text" name="comment">&nbsp;&nbsp;&nbsp;';											 
				//}												
								
				echo '<INPUT TYPE="hidden" NAME="assignment" VALUE="'.stripslashes($row['assignment']).'"/>';
			    //echo '<INPUT TYPE="hidden" NAME="startdate_assig" VALUE="'.stripslashes($row['startdate_assig']).'"/>'; //off for now
				//echo '<INPUT TYPE="hidden" NAME="enddate_assig" VALUE="'.stripslashes($row['enddate_assig']).'"/>'; //off for now	
				//echo '<INPUT TYPE="hidden" NAME="delayedsubmit" VALUE="'.stripslashes($row['delayedsubmit']).'"/>'; //off for now					
							 
				echo '<INPUT TYPE="hidden" NAME="version" VALUE="'.stripslashes($row['version']).'"/>'; //get current version		   
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
				echo '<input type="hidden" NAME="SaveWikiChange" value="'.get_lang('langSave').'">'; //for save icon
				echo '<button class="save" type="submit" name="SaveWikiChange">'.get_lang('langSave').'</button>';//for save button
				echo '</form>';				
				echo '</div>';			
			} 	
		}   
	}
}

/////////////////////// page history ///////////////////////


if ($_GET['action']=='history' or Security::remove_XSS($_POST['HistoryDifferences']))
{
	$_clean['group_id']=(int)$_SESSION['_gid'];

    //First, see the property visibility that is at the last register and therefore we should select descending order. But to give ownership to each record, this is no longer necessary except for the title. TODO: check this

	$sql='SELECT * FROM '.$tbl_wiki.'WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.' ORDER BY id DESC';
	$result=api_sql_query($sql,__LINE__,__FILE__);
		
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
	if($KeyVisibility==1 || api_is_allowed_to_edit() || api_is_platform_admin() || ($KeyAssignment==2 && $KeyVisibility==0 && (api_get_user_id()==$KeyUserId)))
	{	
		// We show the complete history
		if (!$_POST['HistoryDifferences'] && !$_POST['HistoryDifferences2'] )
		{
	
			$sql='SELECT * FROM '.$tbl_wiki.'WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.' ORDER BY id DESC';
			$result=api_sql_query($sql,__LINE__,__FILE__);
			
			$title=Security::remove_XSS($_GET['title']); 
			$group_id=Security::remove_XSS($_GET['group_id']);
		
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
		
				echo '<li>';
				($counter==0) ? $oldstyle='style="visibility: hidden;"':$oldstyle='';
				($counter==0) ? $newchecked=' checked':$newchecked='';
				($counter==$total_versions-1) ? $newstyle='style="visibility: hidden;"':$newstyle='';
				($counter==1) ? $oldchecked=' checked':$oldchecked='';
				echo '<input name="old" value="'.$row['id'].'" type="radio" '.$oldstyle.' '.$oldchecked.'/> ';
				echo '<input name="new" value="'.$row['id'].'" type="radio" '.$newstyle.' '.$newchecked.'/> ';
				echo '<a href="'.$_SERVER['PHP_SELF'].'?action=showpage&amp;title='.$page.'&amp;view='.$row['id'].'">'; 
				echo '<a href="'.$_SERVER['PHP_SELF'].'?cidReq='.$_course[id].'&action=showpage&amp;title='.$page.'&amp;view='.$row['id'].'&group_id='.$group_id.'">'; 
				
				echo $day.' '.$MonthsLong[$month-1].' '.$year.' '.$hours.":".$minutes.":".$seconds;					
				echo '</a>';				
				echo ' ('.get_lang('Version').' '.$row['version'].')';
				echo ' ... ';				
				if ($row['user_id']<>0)
				{
					echo '<a href="../user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.$userinfo['lastname'].', '.$userinfo['firstname'].'</a>'; 
				}
				else
				{
					echo get_lang('Anonymous').' ('.$row[user_ip].')'; 
				}
				
				echo ' ... '.get_lang('Progress').': '.$row['progress'].'%';
				$comment=$row['comment'];
				
				if (!empty($comment))
				{ 
					echo ' ... '.get_lang('Comments').':  <input name="comment" value="'.$row['comment'].'"  readonly="readonly" width="5"/>';
				}	
				else
				{
					echo ' ... '. get_lang('Comments').':  <input name="comment" value="---"  readonly="readonly" width="5"/>';
				}
					
				 echo '<br/><br/></li>';
			   
				$counter++;		
			} //end while
			echo '<button class="search" type="submit" name="HistoryDifferences" value="HistoryDifferences">'.get_lang('ShowDifferences').' '.get_lang('LinesDiff').'</button>';
			echo '<button class="search" type="submit" name="HistoryDifferences2" value="HistoryDifferences2">'.get_lang('ShowDifferences').' '.get_lang('WordsDiff').'</button>';
			echo '</ul></div></form>';	
		}	
		// We show the differences between two versions
		else
		{
			$sql_old="SELECT * FROM $tbl_wiki WHERE id='".Database::escape_string($_POST['old'])."'";
			$result_old=api_sql_query($sql_old,__LINE__,__FILE__);
			$version_old=Database::fetch_array($result_old);
			

			$sql_new="SELECT * FROM $tbl_wiki WHERE id='".Database::escape_string($_POST['new'])."'";
			$result_new=api_sql_query($sql_new,__LINE__,__FILE__);
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
					
			}	
		}
	}
}


/////////////////////// recent changes ///////////////////////

//
//rss feed. TODO
//

if ($_GET['action']=='recentchanges')
{
	$_clean['group_id']=(int)$_SESSION['_gid'];
		
	if (check_notify_all())
	{
		$notify_all= '<img src="../img/wiki/send_mail_checked.gif" title="'.get_lang('FullNotifyByEmail').'" alt="'.get_lang('FullNotifyByEmail').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('NotNotifyChanges').'</font>';
	}
	else
	{	 
		$notify_all= '<img src="../img/wiki/send_mail.gif" title="'.get_lang('FullCancelNotifyByEmail').'" alt="'.get_lang('FullCancelNotifyByEmail').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('NotifyChanges').'</font>';
	}	
		
	echo '<br />'; 
	echo '<b>'.get_lang('RecentChanges').'</b> <a href="index.php?action=recentchanges&amp;actionpage=notify_all&amp;title='.$page.'">'.$notify_all.'</a><br />'; 
    echo '<hr>';	
	
	if(api_is_allowed_to_edit() || api_is_platform_admin()) //only by professors if page is hidden
	{	
		$sql='SELECT * FROM '.$tbl_wiki.' WHERE '.$groupfilter.' ORDER BY dtime DESC';		
	}
	else
	{	
		$sql='SELECT * FROM '.$tbl_wiki.' WHERE '.$groupfilter.' AND visibility=1 ORDER BY dtime DESC';					
	}		

	$allpages=api_sql_query($sql,__LINE__,__FILE__);

	//show table
	if (mysql_num_rows($allpages) > 0)
	{
		$row = array ();
		while ($obj = mysql_fetch_object($allpages))
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
			$row[] = $day.' '.$MonthsLong[$month-1].' '.$year.' '.$hours.':'.$minutes.":".$seconds;	
			$row[] =$ShowAssignment;
			$row[] = '<a href="'.$_SERVER['PHP_SELF'].'?cidReq='.$_course[id].'&action=showpage&title='.urlencode($obj->reflink).'&amp;view='.$obj->id.'&group_id='.Security::remove_XSS($_GET['group_id']).'">'.$obj->title.'</a>';
			$row[] =$obj->version>1 ? get_lang('EditedBy') : get_lang('AddedBy');	
			$row[] = $obj->user_id <>0 ? '<a href="../user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.$userinfo['lastname'].', '.$userinfo['firstname'].'</a>' : get_lang('Anonymous').' ('.$obj->user_ip.')';	
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
	echo '<br />';
	echo '<b>'.get_lang('AllPages').'</b>'; 
   	echo '<hr>';
	
	$_clean['group_id']=(int)$_SESSION['_gid'];


	if(api_is_allowed_to_edit() || api_is_platform_admin()) //only by professors if page is hidden
	{	
		$sql='SELECT  *  FROM   '.$tbl_wiki.' s1 WHERE '.$groupfilter.' AND id=(SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2 WHERE s1.reflink = s2.reflink)'; // warning don't use group by reflink because don't return the last version
	}
	else
	{	
		$sql='SELECT  *  FROM   '.$tbl_wiki.' s1 WHERE '.$groupfilter.' AND visibility=1 AND id=(SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2 WHERE s1.reflink = s2.reflink)'; // warning don't use group by reflink because don't return the last version				
	}		

	$allpages=api_sql_query($sql,__LINE__,__FILE__);	

	//show table
	if (mysql_num_rows($allpages) > 0)
	{
		$row = array ();
		while ($obj = mysql_fetch_object($allpages))
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
			$row[] = '<a href="'.$_SERVER['PHP_SELF'].'?cidReq='.$_course[id].'&action=showpage&title='.urlencode($obj->reflink).'&group_id='.Security::remove_XSS($_GET['group_id']).'">'.$obj->title.'</a>';
			$row[] = $obj->user_id <>0 ? '<a href="../user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.$userinfo['lastname'].', '.$userinfo['firstname'].'</a>' : get_lang('Anonymous').' ('.$obj->user_ip.')';	
			$row[] = $day.' '.$MonthsLong[$month-1].' '.$year.' '.$hours.":".$minutes.":".$seconds;					
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

/////////////////////// discuss pages ///////////////////////


if ($_GET['action']=='discuss')
{

	//first extract the date of last version
	$sql='SELECT * FROM '.$tbl_wiki.'WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.' ORDER BY id DESC';
	$result=api_sql_query($sql,__LINE__,__FILE__);
	$row=Database::fetch_array($result);
	$lastversiondate=$row['dtime'];
	$lastuserinfo=Database::get_user_info_from_id($row['user_id']);
	
	//select page to discuss
    $sql='SELECT * FROM '.$tbl_wiki.'WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.' ORDER BY id ASC';
	$result=api_sql_query($sql,__LINE__,__FILE__);
	$row=Database::fetch_array($result);
	$id=$row['id'];
	$firstuserid=$row['user_id'];

	//check discuss visibility.  Show discussion to students if isn't hidden. Show page to all teachers if is hidden. 
	if (check_visibility_discuss())
	{	
	    //Mode assignments: If is hidden, show pages to student only if student is the author
	 	if(($row['assignment']==2 && $row['visibility_disc']==0 && (api_get_user_id()==$row['user_id']))==false)	
	    {	
	 		$visibility_disc= '<img src="../img/wiki/invisible.gif" title="'.get_lang('HideDiscussExtra').'" alt="'.get_lang('HideDiscussExtra').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('ShowDiscuss').'</font>';
	    }
	}
	else
	{			  
		if(api_is_allowed_to_edit() || api_is_platform_admin()) 
		{
			$visibility_disc= '<img src="../img/wiki/visible.gif" title="'.get_lang('ShowDiscussExtra').'" alt="'.get_lang('ShowDiscussExtra').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('HideDiscuss').'</font>';
		}	     	
	}	
		
	//check add messages lock.
	if (check_addlock_discuss())
	{		
		if(api_is_allowed_to_edit() || api_is_platform_admin()) 
		{	
	 		$addlock_disc= '<img src="../img/wiki/lock.gif" title="'.get_lang('LockDiscussExtra').'" alt="'.get_lang('LockDiscussExtra').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('UnlockDiscuss').'</font>';
		}
		else
		{
		 	$addlock_disc= '<img src="../img/wiki/lock.gif" title="'.get_lang('LockDiscussExtra').'" alt="'.get_lang('LockDiscussExtra').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('PageLocked').'</font>';
		}
		 
	}
	else
	{			  
		if(api_is_allowed_to_edit() || api_is_platform_admin()) 
		{
			$addlock_disc= '<img src="../img/wiki/unlock.gif" title="'.get_lang('UnlockDiscussExtra').'" alt="'.get_lang('UnlockDiscussExtra').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('LockDiscuss').'</font>';
		}	     	
	}		
	
	//check add rating lock. Show/Hide list to rating for all student
	if (check_ratinglock_discuss())
	{
		//Mode assignment: only the teacher can assign scoring
		if(($row['assignment']==2 && $row['ratinglock_disc']==0 && (api_get_user_id()==$row['user_id']))==false)			
	    {		
	 		$ratinglock_disc= '<img src="../img/wiki/rating_na.gif" title="'.get_lang('LockRatingDiscussExtra').'" alt="'.get_lang('LockRatingDiscussExtra').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('UnlockRatingDiscuss').'</font>';
		}
	}
	else
	{			  
		if(api_is_allowed_to_edit() || api_is_platform_admin()) 
		{
			$ratinglock_disc= '<img src="../img/wiki/rating.gif" title="'.get_lang('UnlockRatingDiscussExtra').'" alt="'.get_lang('UnlockRatingDiscussExtra').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('LockRatingDiscuss').'</font>';
		}	     	
	}

	//check notify by email
	if (check_notify_discuss($page))
	{
		$notify_disc= '<img src="../img/wiki/send_mail_checked.gif" title="'.get_lang('NotifyDiscussByEmail').'" alt="'.get_lang('NotifyDiscussByEmail').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('NotNotifyDiscussChanges').'</font>';
	}
	else
	{	
	 	$notify_disc= '<img src="../img/wiki/send_mail.gif" title="'.get_lang('CancelNotifyDiscussByEmail').'" alt="'.get_lang('CancelNotifyDiscussByEmail').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('NotifyDiscussChanges').'</font>';
	   
	}	

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
		if($row['visibility_disc']==1 || api_is_allowed_to_edit() || api_is_platform_admin() || ($row['assignment']==2 && $row['visibility_disc']==0 && (api_get_user_id()==$row['user_id'])))
	    {													
		    echo '<div id="wikititle">';
			echo $icon_assignment.'&nbsp;&nbsp;&nbsp;'.$row['title'].'<br/>'.'<a href="index.php?action=discuss&amp;actionpage=addlock_disc&amp;title='.$page.'">'.$addlock_disc.'</a>'.'&nbsp;&nbsp;&nbsp;<a href="index.php?action=discuss&amp;actionpage=visibility_disc&amp;title='.$page.'">'.$visibility_disc.'</a>'.'&nbsp;&nbsp;&nbsp;<a href="index.php?action=discuss&amp;actionpage=ratinglock_disc&amp;title='.$page.'">'.$ratinglock_disc.'</a>&nbsp;&nbsp;&nbsp;<a href="index.php?action=discuss&amp;actionpage=notify_disc&amp;title='.$page.'">'.$notify_disc.'</a>&nbsp;&nbsp;&nbsp;<font size="-2"><i> ('.get_lang('MostRecentVersionBy').'<a href="../user/userInfo.php?uInfo='.$lastuserinfo['user_id'].'">'.$lastuserinfo['firstname'].' '.$lastuserinfo['lastname'].'</a> '.$lastversiondate.$countWPost.')'.$avg_WPost_score.' </i></font>'; //TODO: read avg score
			echo '</div>';
	
			if($row['addlock_disc']==1 || api_is_allowed_to_edit() || api_is_platform_admin()) //show comments but students can't add theirs
			{	
				?>						
				<form name="form1" method="post" action="">
				<table>
					<tr>
					<td valign="top" ><?php echo get_lang('Comments');?>:</td>
					<td><textarea name="comment" cols="80" rows="5" id="comment"></textarea></td>
                    
					<?php 
					//check if rating is allow
					if($row['ratinglock_disc']==1 || api_is_allowed_to_edit() || api_is_platform_admin()) 
					{					
						?>
						<td valign="top"><?php echo get_lang('Rating');?>: <select name="rating" id="rating">
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
				if (isset($_POST['Submit']))
				{
					$dtime = date( "Y-m-d H:i:s" );
					$message_author=api_get_user_id();
					
					$sql="INSERT INTO $tbl_wiki_discuss (publication_id, userc_id, comment, p_score, dtime) VALUES ('".$id."','".$message_author."','".$_POST['comment']."','".$_POST['rating']."','".$dtime."')";
					$result=api_sql_query($sql,__FILE__,__LINE__) or die(mysql_error());					
					
					check_emailcue($id, 'D', $dtime, $message_author);			
										
				}
			}//end discuss lock
			
			echo '<hr noshade size="1">';
			$user_table = Database :: get_main_table(TABLE_MAIN_USER);
			
			$sql="SELECT * FROM $tbl_wiki_discuss reviews, $user_table user  WHERE reviews.publication_id='".$id."' AND user.user_id='".$firstuserid."' ORDER BY id DESC";
			$result=api_sql_query($sql,__FILE__,__LINE__) or die(mysql_error());
			
			$countWPost = Database::num_rows($result); 
			echo get_lang('NumComments').": ".$countWPost; //comment's numbers 
			
			$sql="SELECT SUM(p_score) as sumWPost FROM $tbl_wiki_discuss WHERE publication_id='".$id."' AND NOT p_score='-' ORDER BY id DESC"; 
			$result2=api_sql_query($sql,__FILE__,__LINE__) or die(mysql_error());
			$row2=Database::fetch_array($result2);
			
			$sql="SELECT * FROM $tbl_wiki_discuss WHERE publication_id='".$id."' AND NOT p_score='-'"; 
			$result3=api_sql_query($sql,__FILE__,__LINE__) or die(mysql_error());
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
	
			$sql='UPDATE '.$tbl_wiki.' SET score="'.Database::escape_string($avg_WPost_score).'" WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter;	// check if work ok. TODO			
				api_sql_query($sql,__FILE__,__LINE__); 
	
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
				
				require_once(api_get_path(INCLUDE_PATH).'/lib/usermanager.lib.php');
				$user_id=$row['userc_id'];
				$name=$userinfo['lastname']." ".$userinfo['firstname'];
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
			echo '<td style=" color:#999999"><a href="../user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.$userinfo['lastname'].', '.$userinfo['firstname'].'</a> ('.$author_status.') '.$row['dtime'].' - '.get_lang('Rating').': '.$row['p_score'].' '.$imagerating.' </td>';
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

echo "</div>"; // echo "<div id='mainwiki'>";

echo "</div>"; // echo "<div id='wikiwrapper'>";



/*
==============================================================================
		FOOTER
==============================================================================
*/
//$_SESSION['_gid'];
Display::display_footer();


/*
==============================================================================
		FUNCTIONS
==============================================================================
*/


/**
* @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
* @desc This function checks weither the proposed reflink is not in use yet. It is a recursive function because every newly created reflink suggestion
*		has to be checked also
*/
function createreflink($testvalue)
{
	global $groupfilter;
	$counter='';
	while (!checktitle($testvalue.$counter))
	{
		$counter++; 
		echo $counter."-".$testvalue.$counter."<br />"; 
	
	}
			// the reflink has not been found yet, so it is OK
	return $testvalue.$counter;
}


/**
* @author Patrick Cool <patrick.cool@ugent.be>, Ghent University 
**/
function checktitle($paramwk) 
{
	global $tbl_wiki;
	global $groupfilter;

	$sql='SELECT * FROM '.$tbl_wiki.' WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($paramwk)))).'" AND '.$groupfilter.''; // TODO: check if need entity
	$result=api_sql_query($sql,__FILE__,__LINE__);
	$numberofresults=Database::num_rows($result);

	if ($numberofresults==0) // the value has not been found and is this available
	{
		return true;
	}
	else // the value has been found
	{
		return false;
	}
}


/**
* @author Juan Carlos Ra�a <herodoto@telefonica.net>
* check wikilinks that has a page
**/
function links_to($input)
{
    $input_array=preg_split("/(\[\[|\]\])/",$input,-1, PREG_SPLIT_DELIM_CAPTURE);
    $all_links = array();
	
	foreach ($input_array as $key=>$value)
	{
		
		if ($input_array[$key-1]=='[[' AND $input_array[$key+1]==']]')
		{
		
		    if (strpos($value, "|") != false)
			{
			 	$full_link_array=explode("|", $value);			
				$link=trim($full_link_array[0]);
				$title=trim($full_link_array[1]);				
			}
		    else
			{
				$link=$value;
				$title=$value;
	        }
		
			unset($input_array[$key-1]);
			unset($input_array[$key+1]);
			
			$all_links[]= Database::escape_string(str_replace(' ','_',$link)).' ';	//replace blank spaces by _ within the links. But to remove links at the end add a blank space
		}

    }
	
	$output=implode($all_links);
	return $output;
	
}

/*
detect and add style to external links
author Juan Carlos Ra�a Trabado
**/
function detect_external_link($input)
{
	$exlink='href=';
	$exlinkStyle='class="wiki_link_ext" href=';	
	$output=str_replace($exlink, $exlinkStyle, $input);		
	return $output;
}

/*
detect and add style to anchor links
author Juan Carlos Ra�a Trabado
**/
function detect_anchor_link($input)
{
	$anchorlink='href="#';
	$anchorlinkStyle='class="wiki_anchor_link" href="#';	
	$output=str_replace($anchorlink, $anchorlinkStyle, $input);		
	return $output;
}

/*
detect and add style to mail links
author Juan Carlos Ra�a Trabado
**/
function detect_mail_link($input)
{
	$maillink='href="mailto';
	$maillinkStyle='class="wiki_mail_link" href="mailto';	
	$output=str_replace($maillink, $maillinkStyle, $input);		
	return $output;
}

/*
detect and add style to ftp links
author Juan Carlos Ra�a Trabado
**/
function detect_ftp_link($input)
{
	$ftplink='href="ftp';
	$ftplinkStyle='class="wiki_ftp_link" href="ftp';	
	$output=str_replace($ftplink, $ftplinkStyle, $input);		
	return $output;
}

/*
detect and add style to news links
author Juan Carlos Ra�a Trabado
**/
function detect_news_link($input)
{
	$newslink='href="news';
	$newslinkStyle='class="wiki_news_link" href="news';	
	$output=str_replace($newslink, $newslinkStyle, $input);		
	return $output;
}

/*
detect and add style to irc links
author Juan Carlos Ra�a Trabado
**/
function detect_irc_link($input)
{
	$irclink='href="irc';
	$irclinkStyle='class="wiki_irc_link" href="irc';	
	$output=str_replace($irclink, $irclinkStyle, $input);		
	return $output;
}


/*
* This function allows users to have [link to a title]-style links like in most regular wikis.
* It is true that the adding of links is probably the most anoying part of Wiki for the people
* who know something about the wiki syntax.
* @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
* Improvements [[]] and [[ | ]]by Juan Carlos Ra�a
* Improvements internal wiki style and mark group by Juan Carlos Ra�a
**/
function make_wiki_link_clickable($input)
{
	if (isset($_SESSION['_gid']))
	{
		$_clean['group_id']=(int)$_SESSION['_gid'];
	}
	if (isset($_GET['group_id']))
	{
		$_clean['group_id']=(int)Security::remove_XSS($_GET['group_id']);
	}


	$input_array=preg_split("/(\[\[|\]\])/",$input,-1, PREG_SPLIT_DELIM_CAPTURE); //now doubles brackets

	foreach ($input_array as $key=>$value)
	{
		
		if ($input_array[$key-1]=='[[' AND $input_array[$key+1]==']]') //now doubles brackets
		{
		
		    if ($_clean['group_id']==0) 
			{
				$titleg_ex='';
			}
			else
			{
		   		$titleg_ex='<sup>(g'.$_clean['group_id'].')</sup>';
		    }	
					
			//now full wikilink
			if (strpos($value, "|") != false)
			 {
			 	$full_link_array=explode("|", $value);			
				$link=trim($full_link_array[0]);
				$title=trim($full_link_array[1]);				
		      }
		      else
			  {
				$link=$value;
				$title=$value;
	          }
			  
			//if wikilink is homepage			
			if($link=='index'){
				$title=get_lang('DefaultTitle');				
			}
			if ($link==get_lang('DefaultTitle')){
				$link='index';
			}
			
		
			// note: checkreflink checks if the link is still free. If it is not used then it returns true, if it is used, then it returns false. Now the title may be different
			if (checktitle(strtolower(str_replace(' ','_',$link))))
			{			
				$input_array[$key]='<a href="'.api_get_path(WEB_PATH).'main/wiki/index.php?cidReq='.$_course[id].'&action=addnew&amp;title='.urldecode($link).'&group_id='.$_clean['group_id'].'" class="new_wiki_link">'.$title.$titleg_ex.'</a>';		
			}
			else 
			{				
						
				$input_array[$key]='<a href="'.api_get_path(WEB_PATH).'main/wiki/index.php?cidReq='.$_course[id].'&action=showpage&amp;title='.strtolower(str_replace(' ','_',$link)).'&group_id='.$_clean['group_id'].'" class="wiki_link">'.$title.$titleg_ex.'</a>';
			}
			unset($input_array[$key-1]);
			unset($input_array[$key+1]);
		}
	}
	$output=implode('',$input_array);
	return $output;
}


/**
* This function saves a change in a wiki page
* @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
* @return language string saying that the changes are stored
**/
function save_wiki()
{

	global $tbl_wiki;	
	
	// NOTE: visibility, visibility_disc and ratinglock_disc changes are not made here, but through the interce buttons
	
	// cleaning the variables

	$_clean['reflink']=Database::escape_string($_POST['reflink']);
	$_clean['title']=Database::escape_string($_POST['title']);
	$_clean['content']= html_entity_decode(Database::escape_string(stripslashes($_POST['content'])));
	$_clean['user_id']=(int)Database::escape_string(api_get_user_id());
	$_clean['assignment']=Database::escape_string($_POST['assignment']);
    $_clean['comment']=Database::escape_string($_POST['comment']);	
    $_clean['progress']=Database::escape_string($_POST['progress']);	
	$_clean['version']=Database::escape_string($_POST['version']);
	$_clean['version']=$_clean['version']+1;//sum 1 here instead of adding in Database::escape_string($_POST['version']), to avoid failures in the sum when there is heavy use of the database
	$_clean['linksto'] = links_to($_clean['content']); //and check links content	
	$dtime = date( "Y-m-d H:i:s" );

	if (isset($_SESSION['_gid']))
    {
	  	$_clean['group_id']=Database::escape_string($_SESSION['_gid']);
    }
    if (isset($_GET['group_id']))
    {
 	   	$_clean['group_id']=Database::escape_string($_GET['group_id']);
    }
	
	$sql="INSERT INTO ".$tbl_wiki." (reflink, title, content, user_id, group_id, dtime, assignment, comment, progress, version, linksto, user_ip) VALUES ('".$_clean['reflink']."','".$_clean['title']."','".$_clean['content']."','".$_clean['user_id']."','".$_clean['group_id']."','".$dtime."','".$_clean['assignment']."','".$_clean['comment']."','".$_clean['progress']."','".$_clean['version']."','".$_clean['linksto']."','".Database::escape_string($_SERVER['REMOTE_ADDR'])."')";
	
	$result=api_sql_query($sql);	
    $Id = Database::insert_id();		
	api_item_property_update($_course, 'wiki', $Id, 'WikiAdded', api_get_user_id());
	
	check_emailcue($_clean['reflink'], 'P', $dtime, $_clean['user_id']);
	
	return get_lang('ChangesStored');
}

/**
* This function restore a wikipage
* @author Juan Carlos Ra�a <herodoto@telefonica.net>
**/
function restore_wikipage($r_reflink, $r_title, $r_content, $r_group_id, $r_assignment, $r_progress, $c_version, $r_version, $r_linksto)
{

	global $tbl_wiki;
	
	$r_user_id= api_get_user_id();
	$r_dtime = date( "Y-m-d H:i:s" );
	$r_version = $r_version+1;
	$r_comment = get_lang('RestoredFromVersion').': '.$c_version;
	
	$sql="INSERT INTO ".$tbl_wiki." (reflink, title, content, user_id, group_id, dtime, assignment, comment, progress, version, linksto, user_ip) VALUES ('".$r_reflink."','".$r_title."','".$r_content."','".$r_user_id."','".$r_group_id."','".$r_dtime."','".$r_assignment."','".$r_comment."','".$r_progress."','".$r_version."','".$r_linksto."','".Database::escape_string($_SERVER['REMOTE_ADDR'])."')";
	
	$result=api_sql_query($sql);	
    $Id = Database::insert_id();		
	api_item_property_update($_course, 'wiki', $Id, 'WikiAdded', api_get_user_id());
	
	check_emailcue($r_reflink, 'P', $r_dtime, $r_user_id);
	
	return get_lang('PageRestored');
}

/**
* This function delete a wiki
* @author Juan Carlos Ra�a <herodoto@telefonica.net>
**/

function delete_wiki()
{

	global $tbl_wiki, $tbl_wiki_discuss, $tbl_wiki_mailcue, $groupfilter;
	//identify the first id by group = identify wiki
	$sql='SELECT * FROM '.$tbl_wiki.'  WHERE  '.$groupfilter.' ORDER BY id DESC';
	$allpages=api_sql_query($sql,__FILE__,__LINE__);
		
	while ($row=Database::fetch_array($allpages))	{
		$id 		= $row['id'];
		$group_id	= $row['group_id'];
	}

	api_sql_query('DELETE FROM '.$tbl_wiki_discuss.' WHERE publication_id="'.$id.'"' ,__FILE__,__LINE__);
	api_sql_query('DELETE FROM '.$tbl_wiki_mailcue.' WHERE group_id="'.$group_id.'"' ,__FILE__,__LINE__);	
	api_sql_query('DELETE FROM '.$tbl_wiki.' WHERE '.$groupfilter.'',__FILE__,__LINE__);	
	return get_lang('WikiDeleted');
}


/**
* This function saves a new wiki page.
* @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
* @todo consider merging this with the function save_wiki into one single function.
**/
function save_new_wiki()
{

	global $tbl_wiki;
    global $assig_user_id; //need for assignments mode

	 
	// cleaning the variables

	$_clean['assignment']=Database::escape_string($_POST['assignment']);
			
	if($_clean['assignment']==2 || $_clean['assignment']==1) // Unlike ordinary pages of pages of assignments. Allow create a ordinary page although there is a assignment with the same name
	{
		$_clean['reflink']=Database::escape_string(str_replace(' ','_',$_POST['title']."_uass".$assig_user_id));			
    }
	else
	{		
	 	$_clean['reflink']=Database::escape_string(str_replace(' ','_',$_POST['title']));			
	}	

	$_clean['title']=Database::escape_string($_POST['title']);		    
	$_clean['content']= html_entity_decode(Database::escape_string(stripslashes($_POST['content'])));
	
	if($_clean['assignment']==2) //config by default for individual assignment (students)
	{	 
	
	 	$_clean['user_id']=(int)Database::escape_string($assig_user_id);//Identifies the user as a creator, not the teacher who created
		
		$_clean['visibility']=0;
		$_clean['visibility_disc']=0;
		$_clean['ratinglock_disc']=0;
		
	}
	else
	{
	 	$_clean['user_id']=(int)Database::escape_string(api_get_user_id());
		
		$_clean['visibility']=1;
		$_clean['visibility_disc']=1;
		$_clean['ratinglock_disc']=1;		
		
	}	

	$_clean['comment']=Database::escape_string($_POST['comment']);
	$_clean['progress']=Database::escape_string($_POST['progress']);
	$_clean['version']=1;
		
	if (isset($_SESSION['_gid']))
	  {
	  $_clean['group_id']=(int)$_SESSION['_gid'];
	}
	if (isset($_GET['group_id']))
	  {
	   $_clean['group_id']=(int)Database::escape_string($_GET['group_id']);
	}		   
	
	$_clean['linksto'] = links_to($_clean['content']);	//check wikilinks
	
	//filter no _uass
	if(eregi("_uass",$_POST['title']) || (strtoupper(trim($_POST['title']))==strtoupper ('index') || strtoupper(trim(htmlentities($_POST['title'])))==strtoupper(htmlentities(get_lang('DefaultTitle')))))
	{
		$message= get_lang('GoAndEditMainPage');
		Display::display_warning_message($message,false);
	}
	else
	{		
	
		$var=$_clean['reflink'];
	
		$group_id=Security::remove_XSS($_GET['group_id']);
		if(!checktitle($var))
		{
		   return get_lang('WikiPageTitleExist').'<a href="index.php?action=edit&amp;title='.$var.'&group_id='.$group_id.'">'.$_POST['title'].'</a>'; 
		} 
		else 
		{ 	
			$dtime = date( "Y-m-d H:i:s" );
			 
			$sql="INSERT INTO ".$tbl_wiki." (reflink, title, content, user_id, group_id, dtime, visibility, visibility_disc, ratinglock_disc, assignment, comment, progress, version, linksto, user_ip) VALUES ('".$_clean['reflink']."','".$_clean['title']."','".$_clean['content']."','".$_clean['user_id']."','".$_clean['group_id']."','".$dtime."','".$_clean['visibility']."','".$_clean['visibility_disc']."','".$_clean['ratinglock_disc']."','".$_clean['assignment']."','".$_clean['comment']."','".$_clean['progress']."','".$_clean['version']."','".$_clean['linksto']."','".Database::escape_string($_SERVER['REMOTE_ADDR'])."')";
			   
		   $result=api_sql_query($sql,__LINE__,__FILE__);
		   $Id = Database::insert_id();	
		   api_item_property_update($_course, 'wiki', $Id, 'WikiAdded', api_get_user_id());
		  
		   check_emailcue(0, 'A');
		   
		   return get_lang('NewWikiSaved').'<a href="index.php?action=showpage&amp;title='.$_clean['reflink'].'&group_id='.$group_id.'">'.$_POST['title'].'</a>'; 
		   
		} 
	}//end filter no _uass
}


/**
* This function displays the form for adding a new wiki page.
* @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
* @return html code
**/
function display_new_wiki_form()
{

	?>
	<script language="JavaScript" type="text/JavaScript"> 
    <!-- 
    function Send(form) 
    { 
        if (form.title.value == "") 
        {   
            alert("<?php echo get_lang('NoWikiPageTitle');?>"); form.title.focus();return false; 
        }   
            form.submit(); 
    } 
    //--> 
    </script>
	<?php
	echo '<form name="form1" method="post" action="'.$_SERVER['PHP_SELF'].'?cidReq='.$_course[id].'&action=showpage&amp;title='.$page.'&group_id='.Security::remove_XSS($_GET['group_id']).'">';  
	echo '<div id="wikititle">';
	echo  get_lang(Title).': <input type="text" name="title" value="'.urldecode($_GET['title']).'">';	
	
	if(api_is_allowed_to_edit() || api_is_platform_admin())
	{	
	
		$_clean['group_id']=(int)$_SESSION['_gid']; // TODO: check if delete ?
		
			echo '&nbsp;&nbsp;&nbsp;<img src="../img/wiki/assignment.gif" />&nbsp;'.get_lang('DefineAssignmentPage').'&nbsp;<INPUT TYPE="checkbox" NAME="assignment" VALUE="1">'; // 1= teacher 2 =student
			
			//by now turned off			
			//echo'<div style="border:groove">';			
			//echo '&nbsp;'.get_lang('StartDate').': <INPUT TYPE="text" NAME="startdate_assig" VALUE="0000-00-00 00:00:00">(yyyy-mm-dd hh:mm:ss)'; //by now turned off
			//echo '&nbsp;'.get_lang('EndDate').': <INPUT TYPE="text" NAME="enddate_assig" VALUE="0000-00-00 00:00:00">(yyyy-mm-dd hh:mm:ss)'; //by now turned off				
		    //echo '<br />&nbsp;'.get_lang('AllowLaterSends').'&nbsp;<INPUT TYPE="checkbox" NAME="delayedsubmit" VALUE="0">'; //by now turned off		
			//echo'</div>';			
	}
	echo '<br /></div>';
	echo '<div id="wikicontent">';
	api_disp_html_area('content','','300px'); 	
	echo '<br/>';
	echo '<br/>'; 	
	echo get_lang('Comments').':&nbsp;&nbsp;<input type="text" name="comment" value="'.stripslashes($row['comment']).'">&nbsp;&nbsp;&nbsp;';
	echo get_lang('Progress').':&nbsp;&nbsp;<select name="progress" id="progress">
	   <option value="0" selected>0</option>	   
	   <option value="10">10</option>
	   <option value="20">20</option>
	   <option value="30">30</option>
	   <option value="40">40</option>
	   <option value="50">50</option>
	   <option value="60">60</option>
	   <option value="70">70</option>
	   <option value="80">80</option>
	   <option value="90">90</option>
	   <option value="100">100</option>   
	   </select>&nbsp;%';
	echo '<br/><br/>'; 
	echo '<input type="hidden" name="SaveWikiNew" value="'.get_lang('langSave').'">'; //for save icon
	echo '<button class="save" type="submit" name="SaveWikiNew" " onClick="return Send(this.form)">'.get_lang('langSave').'</button>'; 	//for button icon
	echo '</div>';
	echo '</form>';
}

/**
* This function displays a wiki entry
* @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
* @return html code
**/
function display_wiki_entry()
{
	global $tbl_wiki;
	global $groupfilter;
	global $page;
   
	$_clean['group_id']=(int)$_SESSION['_gid']; 
	if ($_GET['view'])
	{
		$_clean['view']=(int)Database::escape_string($_GET['view']);
		$filter=" AND id='".$_clean['view']."'";
	}	

	//first, check page visibility in the first page version
	$sql='SELECT * FROM '.$tbl_wiki.'WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.' ORDER BY id ASC';
		$result=api_sql_query($sql,__LINE__,__FILE__);	
		$row=Database::fetch_array($result);		
		$KeyVisibility=$row['visibility'];	

	// second, show the last version
	$sql="SELECT * FROM ".$tbl_wiki."WHERE reflink='".html_entity_decode(Database::escape_string(stripslashes(urldecode($page))))."' AND $groupfilter $filter ORDER BY id DESC";
		$result=api_sql_query($sql,__LINE__,__FILE__);
		$row=Database::fetch_array($result); // we do not need a while loop since we are always displaying the last version	


	//update visits 
	if($row['id'])
	{
		$sql='UPDATE '.$tbl_wiki.' SET hits=(hits+1) WHERE id='.$row['id'].'';
		api_sql_query($sql,__FILE__,__LINE__);
	}


	// if both are empty and we are displaying the index page then we display the default text.
	if ($row['content']=='' AND $row['title']=='' AND $page='index')
	{
		if(api_is_allowed_to_edit() || api_is_platform_admin()) 
		{
			$content=sprintf(get_lang('DefaultContent'),api_get_path(WEB_IMG_PATH));
			$title=get_lang('DefaultTitle');
		}
		else
		{		
			return Display::display_normal_message(get_lang('WikiStandBy'));
		}
	}
	else
	{
  		$content=$row['content'];
		$title=$row['title'];
	}
	

	//Button lock add new pages
	if (check_addnewpagelock())
	{
		if(api_is_allowed_to_edit() || api_is_platform_admin()) 
		{
	 		$protect_addnewpage= '<img src="../img/wiki/lockadd.gif" title="'.get_lang('AddOptionProtected').'" alt="'.get_lang('AddOptionProtected').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('ShowAddOption').'</font>';
	    }
	}
	else
	{			  
		if(api_is_allowed_to_edit() || api_is_platform_admin()) 
		{
			$protect_addnewpage= '<img src="../img/wiki/unlockadd.gif" title="'.get_lang('AddOptionUnprotected').'" alt="'.get_lang('AddOptionUnprotected').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('HideAddOption').'</font>';
		}	     	
	}
	
	//Button lock page
	if (check_protect_page())
	{	
		if(api_is_allowed_to_edit() || api_is_platform_admin())
		{
	 		$protect_page= '<img src="../img/wiki/lock.gif" title="'.get_lang('PageLockedExtra').'" alt="'.get_lang('PageLockedExtra').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('UnlockPage').'</font>';
		}
		else
		{
			$protect_page= '<img src="../img/wiki/lock.gif" title="'.get_lang('PageLockedExtra').'" alt="'.get_lang('PageLockedExtra').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('PageLocked').'</font>';
		}
	}
	else
	{					  
		if(api_is_allowed_to_edit() || api_is_platform_admin()) 
	   	{
	   		$protect_page= '<img src="../img/wiki/unlock.gif" title="'.get_lang('PageUnlockedExtra').'" alt="'.get_lang('PageUnlockedExtra').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('LockPage').'</font>';
	   	}   	
	}	

	//Button visibility page
	if (check_visibility_page())
	{
		//This hides the icon eye closed to users of work they can see yours
		if(($row['assignment']==2 && $KeyVisibility=="0" && (api_get_user_id()==$row['user_id']))==false)
	  	{	  
	 		$visibility_page= '<img src="../img/wiki/invisible.gif" title="'.get_lang('HidePageExtra').'" alt="'.get_lang('HidePageExtra').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('Show').'</font>';
	    }
	}
	else
	{			  
		if(api_is_allowed_to_edit() || api_is_platform_admin()) 
		{
			$visibility_page= '<img src="../img/wiki/visible.gif" title="'.get_lang('ShowPageExtra').'" alt="'.get_lang('ShowPageExtra').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('Hide').'</font>';
		}	     	
	}		
	
	//Button notify page
	if (check_notify_page($page))
	{
		$notify_page= '<img src="../img/wiki/send_mail_checked.gif" title="'.get_lang('NotifyByEmail').'" alt="'.get_lang('NotifyByEmail').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('NotNotifyChanges').'</font>';
	}
	else
	{	 
		$notify_page= '<img src="../img/wiki/send_mail.gif" title="'.get_lang('CancelNotifyByEmail').'" alt="'.get_lang('CancelNotifyByEmail').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('NotifyChanges').'</font>';
	}	

	//assignment mode: for identify page type
	if(stripslashes($row['assignment'])==1)
	{
		$icon_assignment='<img src="../img/wiki/assignment.gif" title="'.get_lang('AssignmentDescExtra').'" alt="'.get_lang('AssignmentDescExtra').'" />';
	}
	elseif(stripslashes($row['assignment'])==2)
	{
		$icon_assignment='<img src="../img/wiki/works.gif" title="'.get_lang('AssignmentWorkExtra').'" alt="'.get_lang('AssignmentWorkExtra').'" />';
	}	
		
	//Show page. Show page to all users if isn't hide page. Mode assignments: if studen is the author, can view
	if($KeyVisibility=="1" || api_is_allowed_to_edit() || api_is_platform_admin() || ($row['assignment']==2 && $KeyVisibility=="0" && (api_get_user_id()==$row['user_id'])))
	{		
		echo '<div id="wikititle">';
			
		if (empty($title))
		{
			$title=get_lang('DefaultTitle');
				
		}
		
		if (wiki_exist($title))
		{ 			
			echo $icon_assignment.'&nbsp;&nbsp;&nbsp;'.stripslashes($title).'<a href="index.php?action=show&amp;actionpage=addlock&amp;title='.$page.'"><br/>'.$protect_addnewpage.'</a>'.'&nbsp;&nbsp;&nbsp;<a href="index.php?action=showpage&amp;actionpage=lock&amp;title='.$page.'">'.$protect_page.'</a>'.'&nbsp;&nbsp;&nbsp;<a href="index.php?action=showpage&amp;actionpage=visibility&amp;title='.$page.'">'.$visibility_page.'</a>'.'&nbsp;&nbsp;&nbsp;<a href="index.php?action=showpage&amp;actionpage=notify&amp;title='.$page.'">'.$notify_page.'</a>'.'&nbsp;&nbsp;&nbsp;'.get_lang('Progress').': '.stripslashes($row['progress']).'%&nbsp;&nbsp;&nbsp;'.get_lang('Rating').': '.stripslashes($row['score']).'&nbsp;&nbsp;&nbsp;'.get_lang('Words').': '.word_count($content);			
		}
		else
		{
			echo stripslashes($title);				
		}
		
		//export to pdf
		echo '<span style="float:right">';
		echo '<form name="form_export2PDF" method="post" action="export_html2pdf.php" target="_blank, fullscreen">'; // also with  export_tcpdf.php
		echo '<input type=hidden name="titlePDF" value="'.htmlentities($title).'">';
		echo '<input type=hidden name="contentPDF" value="'.htmlentities($content).'">';
		echo '<input type="image" src="../img/wiki/wexport2pdf.gif" border ="0" title="'.get_lang('ExportToPDF').'" alt="'.get_lang('ExportToPDF').'" style=" border:none;">';
		echo '</form>';
		echo '</span>';
		
		//copy last version to doc area
		if(api_is_allowed_to_edit() || api_is_platform_admin())
		{
			echo '<span style="float:right;">';				
			echo '<form name="form_export2DOC" method="post" action="index.php">';
			echo '<input type=hidden name="export2DOC" value="export2doc">';
			echo '<input type=hidden name="titleDOC" value="'.htmlentities($title).'">';
			echo '<input type=hidden name="contentDOC" value="'.htmlentities($content).'">';
			echo '<input type="image" src="../img/wiki/wexport2doc.png" border ="0" title="'.get_lang('ExportToDocArea').'" alt="'.get_lang('ExportToDocArea').'" style=" border:none;">';
			echo '</form>';
			echo '</span>';	
		}
		//export to print
		?>	
    	
		<script>
        function goprint()
        {
            var a = window.open('','','width=800,height=600');
            a.document.open("text/html");
            a.document.write(document.getElementById('wikicontent').innerHTML);
            a.document.close();
            a.print();
        }
        </script>
		<?php				
		echo '<span style="float:right; cursor:pointer;">';
		echo '<img src="../img/wiki/wprint.gif" title="'.get_lang('Print').'" alt="'.get_lang('Print').'" onclick="goprint()">';
		echo '</span>';
		
		//export to zip			

			//echo '<span style="float:right;"><img src="../img/wiki/wzip_save.gif" alt="'.get_lang('Export2ZIP').'" onclick="alert(\'This is not implemented yet but it will be in the near future\')"/></span>'; //TODO	
			
			echo '</div>';	
			echo '<div id="wikicontent">'. make_wiki_link_clickable(detect_external_link(detect_anchor_link(detect_mail_link(detect_ftp_link(detect_irc_link(detect_news_link(stripslashes($content)))))))).'</div>';

	}//end filter visibility
} // end function display_wiki_entry


//more for export to course document area. See display_wiki_entry
if ($_POST['export2DOC'])
{ 
	$titleDOC=$_POST['titleDOC'];
	$contentDOC=$_POST['contentDOC'];
	$groupIdDOC=$_clean['group_id'];
	export2doc($titleDOC,$contentDOC,$groupIdDOC); 
}

/**
* This function counted the words in a document. Thanks Adeel Khan
*/

function word_count($document) {

	$search = array(
	'@<script[^>]*?>.*?</script>@si',
	'@<style[^>]*?>.*?</style>@siU',
	'@<![\s\S]*?--[ \t\n\r]*>@'
	);
	
    $document = preg_replace($search, '', $document);

  	# strip all html tags
  	$wc = strip_tags($document);

  	# remove 'words' that don't consist of alphanumerical characters or punctuation
  	$pattern = "#[^(\w|\d|\'|\"|\.|\!|\?|;|,|\\|\/|\-|:|\&|@)]+#";
  	$wc = trim(preg_replace($pattern, " ", $wc));

  	# remove one-letter 'words' that consist only of punctuation
  	$wc = trim(preg_replace("#\s*[(\'|\"|\.|\!|\?|;|,|\\|\/|\-|:|\&|@)]\s*#", " ", $wc));

  	# remove superfluous whitespace
  	$wc = preg_replace("/\s\s+/", " ", $wc);

  	# split string into an array of words
  	$wc = explode(" ", $wc);

  	# remove empty elements
  	$wc = array_filter($wc);
  
  	# return the number of words
 	 return count($wc);

}

/**
 * This function checks if wiki title exist
 */

function wiki_exist($title)
{
	global $tbl_wiki;
	global $groupfilter;
	$sql='SELECT id FROM '.$tbl_wiki.'WHERE title="'.Database::escape_string($title).'" AND '.$groupfilter.' ORDER BY id ASC'; 
	$result=api_sql_query($sql,__LINE__,__FILE__);
	$cant=Database::num_rows($result);
	if ($cant>0)
		return true;
	else 
		return false;
}

/**
* This function a wiki warning
* @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
* @return html code
**/
function display_wiki_warning($variable)
{
	echo '<div class="wiki_warning">'.$variable.'</div>';
}


/**
 * Checks if this navigation tab has to be set to active
 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
 * @return html code
 */
function is_active_navigation_tab($paramwk) 
{
	if ($_GET['action']==$paramwk) 
	{
		return ' class="active"';
	}
}


/**
 * Lock add pages
 * @author Juan Carlos Ra�a <herodoto@telefonica.net>
 */

function check_addnewpagelock() 
{

	global $tbl_wiki;
	global $groupfilter;

	$_clean['group_id']=(int)$_SESSION['_gid'];
		
	$sql='SELECT * FROM '.$tbl_wiki.'WHERE '.$groupfilter.' ORDER BY id ASC'; 
	$result=api_sql_query($sql,__LINE__,__FILE__);
	$row=Database::fetch_array($result); 
							
	$status_addlock=$row['addlock'];
			
	//change status
	if ($_GET['actionpage']=='addlock' && (api_is_allowed_to_edit() || api_is_platform_admin())) 
	{	
		if ($row['addlock']==1)
		{
			$status_addlock=0;
		}
		else
		{
			$status_addlock=1; 
		}       
		
		api_sql_query('UPDATE '.$tbl_wiki.' SET addlock="'.Database::escape_string($status_addlock).'" WHERE '.$groupfilter.'',__LINE__,__FILE__);	
	  
		$sql='SELECT * FROM '.$tbl_wiki.'WHERE '.$groupfilter.' ORDER BY id ASC'; 
		$result=api_sql_query($sql,__LINE__,__FILE__);
		$row=Database::fetch_array($result);
	} 
	 		
	//show status				
				
	if ($row['addlock']==1 || ($row['content']=='' AND $row['title']=='' AND $page='index'))
	{
		return false;		
	}
	else
	{
		return true;				
	}		
}


/**
 * Protect page
 * @author Juan Carlos Ra�a <herodoto@telefonica.net>
 */
function check_protect_page() 
{
	global $tbl_wiki;
	global $page;
	global $groupfilter;

	$_clean['group_id']=(int)$_SESSION['_gid'];

	$sql='SELECT * FROM '.$tbl_wiki.'WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.' ORDER BY id ASC';
		
	$result=api_sql_query($sql,__LINE__,__FILE__);
	$row=Database::fetch_array($result);
			
	$status_editlock=$row['editlock'];
	$id=$row['id'];	

	///change status
	if ($_GET['actionpage']=='lock' && (api_is_allowed_to_edit() || api_is_platform_admin())) 
    {		 	 
	    if ($row['editlock']==0)
	    {
	    	$status_editlock=1;
	    }
	    else
		{
	 		$status_editlock=0; 
	 	}
		
		$sql='UPDATE '.$tbl_wiki.' SET editlock="'.Database::escape_string($status_editlock).'" WHERE id="'.$id.'"';			   
	    api_sql_query($sql,__FILE__,__LINE__); 
	  
	    $sql='SELECT * FROM '.$tbl_wiki.'WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.' ORDER BY id ASC';
		
	    $result=api_sql_query($sql,__LINE__,__FILE__);
	    $row=Database::fetch_array($result); 

	}
	 				
	//show status	
	if ($row['editlock']==0 || ($row['content']=='' AND $row['title']=='' AND $page=='index'))
	{
	 	return false;
	}
	else
	{
	 	return true;				
	}    
		
}


/**
 * Visibility page
 * @author Juan Carlos Ra�a <herodoto@telefonica.net>
 */
function check_visibility_page() 
{

	global $tbl_wiki;
	global $page;
	global $groupfilter;

	$_clean['group_id']=(int)$_SESSION['_gid'];

	$sql='SELECT * FROM '.$tbl_wiki.'WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.' ORDER BY id ASC';
	$result=api_sql_query($sql,__LINE__,__FILE__);
	$row=Database::fetch_array($result);
				
	$status_visibility=$row['visibility'];
	$id=$row['id'];	//need ? check. TODO
		
	//change status
	if ($_GET['actionpage']=='visibility' && (api_is_allowed_to_edit() || api_is_platform_admin())) 
	{	
		if ($row['visibility']==1)
	    {
	    	$status_visibility=0;
	    }
	    else
		{
		 	$status_visibility=1; 
		}       
				
		$sql='UPDATE '.$tbl_wiki.' SET visibility="'.Database::escape_string($status_visibility).'" WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter; 
	    api_sql_query($sql,__FILE__,__LINE__); 
		
	    //Although the value now is assigned to all (not only the first), these three lines remain necessary. They do that by changing the page state is made when you press the button and not have to wait to change his page
	    $sql='SELECT * FROM '.$tbl_wiki.'WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.' ORDER BY id ASC';
	    $result=api_sql_query($sql,__LINE__,__FILE__);
	    $row=Database::fetch_array($result); 

     } 
	 			
	//show status
	if ($row['visibility']=="1" || ($row['content']=='' AND $row['title']=='' AND $page=='index'))
	{
		return false;
	}
	else
	{
		return true;				
	}    
		
}


/**
 * Visibility discussion
 * @author Juan Carlos Ra�a <herodoto@telefonica.net>
 */
function check_visibility_discuss() 
{

	global $tbl_wiki;
	global $page;
	global $groupfilter;

	$_clean['group_id']=(int)$_SESSION['_gid'];

	$sql='SELECT * FROM '.$tbl_wiki.'WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.' ORDER BY id ASC';
	$result=api_sql_query($sql,__LINE__,__FILE__);
	$row=Database::fetch_array($result);
				
	$status_visibility_disc=$row['visibility_disc'];
	$id=$row['id'];	//need ? check. TODO	
		
	//change status
	if ($_GET['actionpage']=='visibility_disc' && (api_is_allowed_to_edit() || api_is_platform_admin())) 
	{	
		if ($row['visibility_disc']==1)
	    {
	    	$status_visibility_disc=0;
	    }
	    else
		{
			$status_visibility_disc=1; 
		}       
		
		$sql='UPDATE '.$tbl_wiki.' SET visibility_disc="'.Database::escape_string($status_visibility_disc).'" WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter;
	    api_sql_query($sql,__FILE__,__LINE__); 
		
	   //Although the value now is assigned to all (not only the first), these three lines remain necessary. They do that by changing the page state is made when you press the button and not have to wait to change his page
	    $sql='SELECT * FROM '.$tbl_wiki.'WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.' ORDER BY id ASC';
	    $result=api_sql_query($sql,__LINE__,__FILE__);
	    $row=Database::fetch_array($result); 

	}
					
	//show status			

	if ($row['visibility_disc']==1 || ($row['content']=='' AND $row['title']=='' AND $page=='index'))
	{
	 	return false;		 

	}
	else
	{
	 	return true;				
	}   		
}


/**
 * Lock add discussion
 * @author Juan Carlos Ra�a <herodoto@telefonica.net>
 */
function check_addlock_discuss() 
{
	global $tbl_wiki;
	global $page;
	global $groupfilter;

	$_clean['group_id']=(int)$_SESSION['_gid'];

	$sql='SELECT * FROM '.$tbl_wiki.'WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.' ORDER BY id ASC';
	$result=api_sql_query($sql,__LINE__,__FILE__);
	$row=Database::fetch_array($result);
				
	$status_addlock_disc=$row['addlock_disc'];
	$id=$row['id'];		//need ? check. TODO	
		
	//change status
	if ($_GET['actionpage']=='addlock_disc' && (api_is_allowed_to_edit() || api_is_platform_admin())) 
    {	
		if ($row['addlock_disc']==1)
	    {
	    	$status_addlock_disc=0;
	    }
	    else
		{
			$status_addlock_disc=1; 
		}       
		
		$sql='UPDATE '.$tbl_wiki.' SET addlock_disc="'.Database::escape_string($status_addlock_disc).'" WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter;		
	    api_sql_query($sql,__FILE__,__LINE__); 
		
	  	//Although the value now is assigned to all (not only the first), these three lines remain necessary. They do that by changing the page state is made when you press the button and not have to wait to change his page
	    $sql='SELECT * FROM '.$tbl_wiki.'WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.' ORDER BY id ASC';
	    $result=api_sql_query($sql,__LINE__,__FILE__);
	    $row=Database::fetch_array($result); 

	}
	 		
	//show status			

	if ($row['addlock_disc']==1 || ($row['content']=='' AND $row['title']=='' AND $page=='index'))
	{
		return false;
	}
	else
	{
		return true;				
	}    
		
}


/**
 * Lock rating discussion
 * @author Juan Carlos Ra�a <herodoto@telefonica.net>
 */
function check_ratinglock_discuss() 
{

	global $tbl_wiki;
	global $page;
	global $groupfilter;

	$_clean['group_id']=(int)$_SESSION['_gid'];

	$sql='SELECT * FROM '.$tbl_wiki.'WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.' ORDER BY id ASC';
	$result=api_sql_query($sql,__LINE__,__FILE__);
	$row=Database::fetch_array($result);
				
	$status_ratinglock_disc=$row['ratinglock_disc'];
	$id=$row['id'];	//need ? check. TODO	
		
	//change status
	if ($_GET['actionpage']=='ratinglock_disc' && (api_is_allowed_to_edit() || api_is_platform_admin())) 
    {	
		if ($row['ratinglock_disc']==1)
	    {
	    	$status_ratinglock_disc=0;
	    }
	    else
		{
			$status_ratinglock_disc=1; 
		}       
		
		$sql='UPDATE '.$tbl_wiki.' SET ratinglock_disc="'.Database::escape_string($status_ratinglock_disc).'" WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter; //Visibility. Value to all,not only for the first	
	    api_sql_query($sql,__FILE__,__LINE__); 
		
	  	//Although the value now is assigned to all (not only the first), these three lines remain necessary. They do that by changing the page state is made when you press the button and not have to wait to change his page
	    $sql='SELECT * FROM '.$tbl_wiki.'WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.' ORDER BY id ASC';
	    $result=api_sql_query($sql,__LINE__,__FILE__);
	    $row=Database::fetch_array($result); 

	}
	 			
	//show status
	if ($row['ratinglock_disc']==1 || ($row['content']=='' AND $row['title']=='' AND $page=='index'))
	{
		return false;
	}
	else
	{
		return true;				
	}    
		
}


/**
 * Notify page changes
 * @author Juan Carlos Ra�a <herodoto@telefonica.net>
 */
 
function check_notify_page($reflink)
{
	global $tbl_wiki;
	global $groupfilter;	
	global $tbl_wiki_mailcue;
	
	$_clean['group_id']=(int)$_SESSION['_gid'];
	$sql='SELECT * FROM '.$tbl_wiki.'WHERE reflink="'.$reflink.'" AND '.$groupfilter.' ORDER BY id ASC';
	$result=api_sql_query($sql,__LINE__,__FILE__);
	$row=Database::fetch_array($result);
	
	$id=$row['id'];		
		
	$sql='SELECT * FROM '.$tbl_wiki_mailcue.'WHERE id="'.$id.'" AND user_id="'.api_get_user_id().'" AND type="P"';
	$result=api_sql_query($sql,__LINE__,__FILE__);
	$row=Database::fetch_array($result);
			
	$idm=$row['id'];
	
	if (empty($idm))	
	{ 
		$status_notify=0;
	}
	else
	{
		$status_notify=1;
	}	
			
	//change status
	if ($_GET['actionpage']=='notify')
	{	
		
		if ($status_notify==0)
	    {		
		   	  
			$sql="INSERT INTO ".$tbl_wiki_mailcue." (id, user_id, type, group_id) VALUES ('".$id."','".api_get_user_id()."','P','".$_clean['group_id']."')";
			api_sql_query($sql,__FILE__,__LINE__);		
			
	    	$status_notify=1;			
	    }
	    else
		{					
		    $sql='DELETE FROM '.$tbl_wiki_mailcue.' WHERE id="'.$id.'" AND user_id="'.api_get_user_id().'" AND type="P"'; //$_clean['group_id'] not necessary
			api_sql_query($sql,__FILE__,__LINE__);
			
		    $status_notify=0;				
	    } 			
	}	
		
	//show status
	if ($status_notify==0)
	{
		return false;
	}
	else
	{
		return true;				
	}
}


/**
 * Notify discussion changes
 * @author Juan Carlos Ra�a <herodoto@telefonica.net>
 */
function check_notify_discuss($reflink)
{
	global $tbl_wiki;
	global $groupfilter;	
	global $tbl_wiki_mailcue;	
	
	$_clean['group_id']=(int)$_SESSION['_gid'];
	$sql='SELECT * FROM '.$tbl_wiki.'WHERE reflink="'.$reflink.'" AND '.$groupfilter.' ORDER BY id ASC';
	$result=api_sql_query($sql,__LINE__,__FILE__);
	$row=Database::fetch_array($result);
	
	$id=$row['id'];	
			
	$sql='SELECT * FROM '.$tbl_wiki_mailcue.'WHERE id="'.$id.'" AND user_id="'.api_get_user_id().'" AND type="D"';
	$result=api_sql_query($sql,__LINE__,__FILE__);
	$row=Database::fetch_array($result);
			
	$idm=$row['id'];
		
	if (empty($idm))	
	{ 	
		$status_notify_disc=0;
		
	}
	else
	{
		$status_notify_disc=1;
	}	
			
	//change status
	if ($_GET['actionpage']=='notify_disc')
	{	
		
		if ($status_notify_disc==0)
	    {	
		
			if (!$_POST['Submit'])
			{	  
								
				$sql="INSERT INTO ".$tbl_wiki_mailcue." (id, user_id, type, group_id) VALUES ('".$id."','".api_get_user_id()."','D','".$_clean['group_id']."')";
				api_sql_query($sql,__FILE__,__LINE__);		
				
				$status_notify_disc=1;
			}
			else
			{
				$status_notify_disc=0;
			}			
	    }
	    else
		{	
			if (!$_POST['Submit'])
			{				
				$sql='DELETE FROM '.$tbl_wiki_mailcue.' WHERE id="'.$id.'" AND user_id="'.api_get_user_id().'" AND type="D"'; //$_clean['group_id'] not necessary
				api_sql_query($sql,__FILE__,__LINE__);
				
				$status_notify_disc=0;
			}
			else
			{
				$status_notify_disc=1;
			}					
	    } 			
	}	
		
	//show status
	if ($status_notify_disc==0)
	{				
		return false;
	}
	else
	{
		return true;					
	}
}


/**
 * Notify all changes
 * @author Juan Carlos Ra�a <herodoto@telefonica.net>
 */
 
function check_notify_all()
{

	global $tbl_wiki_mailcue;
	
	$_clean['group_id']=(int)$_SESSION['_gid'];	
		
	$sql='SELECT * FROM '.$tbl_wiki_mailcue.'WHERE user_id="'.api_get_user_id().'" AND type="F" AND group_id="'.$_clean['group_id'].'"';
	$result=api_sql_query($sql,__LINE__,__FILE__);
	$row=Database::fetch_array($result);
			
	$idm=$row['user_id'];
	
	if (empty($idm))
	{ 
		$status_notify_all=0;	
	}
	else
	{
		$status_notify_all=1;
	}	
			
	//change status
	if ($_GET['actionpage']=='notify_all')
	{	
		
		if ($status_notify_all==0)
	    {	
			$sql="INSERT INTO ".$tbl_wiki_mailcue." (user_id, type, group_id) VALUES ('".api_get_user_id()."','F','".$_clean['group_id']."')";
			api_sql_query($sql,__FILE__,__LINE__);		
			
			$status_notify_all=1;				
	    }
	    else
		{								
			$sql='DELETE FROM '.$tbl_wiki_mailcue.' WHERE user_id="'.api_get_user_id().'" AND type="F" AND group_id="'.$_clean['group_id'].'"';
			api_sql_query($sql,__FILE__,__LINE__);
		
			$status_notify_all=0;			
	    } 					
	}	
		
	//show status
	if ($status_notify_all==0)
	{
		return false;
	}
	else
	{
		return true;				
	}
}


/**
 * Function check emailcue and send email when a page change
 * @author Juan Carlos Ra�a <herodoto@telefonica.net>
 */
 
function check_emailcue($id_or_ref, $type, $lastime, $lastuser)
{
	global $tbl_wiki;
	global $groupfilter;	
	global $tbl_wiki_mailcue;
	global $_course;		

    $_clean['group_id']=(int)$_SESSION['_gid'];
	
	$group_properties  = GroupManager :: get_group_properties($_clean['group_id']);	
	$group_name= $group_properties['name'];

    $allow_send_mail=false; //define the variable to below
	
	if ($type=='P')
	{
	//if modifying a wiki page
		
		//first, current author and time
		//Who is the author?
		$userinfo=	Database::get_user_info_from_id($lastuser);		
		$email_user_author= get_lang('EditedBy').': '.$userinfo['firstname'].' '.$userinfo['lastname'];		
		
		//When ?		
		$year = substr($lastime, 0, 4);
		$month = substr($lastime, 5, 2);
		$day = substr($lastime, 8, 2);
		$hours=substr($lastime, 11,2);
		$minutes=substr($lastime, 14,2);
		$seconds=substr($lastime, 17,2);
		$email_date_changes=$day.' '.$month.' '.$year.' '.$hours.":".$minutes.":".$seconds;	
		
		//second, extract data from first reg
	 	$sql='SELECT * FROM '.$tbl_wiki.'WHERE reflink="'.$id_or_ref.'" AND '.$groupfilter.' ORDER BY id ASC'; //id_or_ref is reflink from tblwiki
		
		$result=api_sql_query($sql,__LINE__,__FILE__);
		$row=Database::fetch_array($result);
		
		$id=$row['id'];
		$email_page_name=$row['title'];
		
			
		if ($row['visibility']==1)
		{
			$allow_send_mail=true; //if visibility off - notify off	
		
			$sql='SELECT * FROM '.$tbl_wiki_mailcue.'WHERE id="'.$id.'" AND type="'.$type.'" OR type="F" AND group_id="'.$_clean['group_id'].'"'; //type: P=page, D=discuss, F=full.		
			$result=api_sql_query($sql,__LINE__,__FILE__);
			
			$emailtext=get_lang('EmailWikipageModified').' <strong>'.$email_page_name.'</strong> '.get_lang('Wiki');
		}
	
	}
	elseif ($type=='D')
	{
	//if added a post to discuss
	
		//first, current author and time
		//Who is the author of last message?
		$userinfo=	Database::get_user_info_from_id($lastuser);		
		$email_user_author= get_lang('AddedBy').': '.$userinfo['firstname'].' '.$userinfo['lastname'];		
		
		//When ?		
		$year = substr($lastime, 0, 4);
		$month = substr($lastime, 5, 2);
		$day = substr($lastime, 8, 2);
		$hours=substr($lastime, 11,2);
		$minutes=substr($lastime, 14,2);
		$seconds=substr($lastime, 17,2);
		$email_date_changes=$day.' '.$month.' '.$year.' '.$hours.":".$minutes.":".$seconds;	
		
		//second, extract data from first reg	
		
		$id=$id_or_ref; //$id_or_ref is id from tblwiki
		
		$sql='SELECT * FROM '.$tbl_wiki.'WHERE id="'.$id.'" ORDER BY id ASC';
		
		$result=api_sql_query($sql,__LINE__,__FILE__);
		$row=Database::fetch_array($result);
		
		$email_page_name=$row['title'];
		
		
		if ($row['visibility_disc']==1)
		{
			$allow_send_mail=true; //if visibility off - notify off				
				
			$sql='SELECT * FROM '.$tbl_wiki_mailcue.'WHERE id="'.$id.'" AND type="'.$type.'" OR type="F" AND group_id="'.$_clean['group_id'].'"'; //type: P=page, D=discuss, F=full
			$result=api_sql_query($sql,__LINE__,__FILE__);			
			
			$emailtext=get_lang('EmailWikiPageDiscAdded').' <strong>'.$email_page_name.'</strong> '.get_lang('Wiki');
		}
	}
	elseif($type=='A')
	{
	//for added pages
		$id=0; //for tbl_wiki_mailcue
	
		$sql='SELECT * FROM '.$tbl_wiki.' ORDER BY id DESC'; //the added is always the last
		
		$result=api_sql_query($sql,__LINE__,__FILE__);
		$row=Database::fetch_array($result);
		
		$email_page_name=$row['title'];
		
		//Who is the author?
		$userinfo=	Database::get_user_info_from_id($row['user_id']);		
		$email_user_author= get_lang('AddedBy').': '.$userinfo['firstname'].' '.$userinfo['lastname'];		
		
		//When ?		
		$year = substr($row['dtime'], 0, 4);
		$month = substr($row['dtime'], 5, 2);
		$day = substr($row['dtime'], 8, 2);
		$hours=substr($row['dtime'], 11,2);
		$minutes=substr($row['dtime'], 14,2);
		$seconds=substr($row['dtime'], 17,2);
		$email_date_changes=$day.' '.$month.' '.$year.' '.$hours.":".$minutes.":".$seconds;		
		
		
		if($row['assignment']==0)	
		{
			$allow_send_mail=true;
		}
		elseif($row['assignment']==1)	
		{
			$email_assignment=get_lang('AssignmentDescExtra').' ('.get_lang('AssignmentMode').')';
			$allow_send_mail=true;
		}
		elseif($row['assignment']==2)		
		{
			$allow_send_mail=false; //Mode tasks: avoids notifications to all users about all users
		}		
		
		$sql='SELECT * FROM '.$tbl_wiki_mailcue.'WHERE id="'.$id.'" AND type="F" AND group_id="'.$_clean['group_id'].'"'; //type: P=page, D=discuss, F=full
		$result=api_sql_query($sql,__LINE__,__FILE__);
	
		$emailtext=get_lang('EmailWikiPageAdded').' <strong>'.$email_page_name.'</strong> '.get_lang('In').' '. get_lang('Wiki');
	}
	elseif($type=='E')
	{
		$id=0;
		
		$allow_send_mail=true;
		
		//Who is the author?
		$userinfo=	Database::get_user_info_from_id(api_get_user_id());	//current user
		$email_user_author= get_lang('DeletedBy').': '.$userinfo['firstname'].' '.$userinfo['lastname'];		
		
		
		//When ?		
		$today = date('r');		//current time
		$email_date_changes=$today;	
		
		$sql='SELECT * FROM '.$tbl_wiki_mailcue.'WHERE id="'.$id.'" AND type="F" AND group_id="'.$_clean['group_id'].'"'; //type: P=page, D=discuss, F=wiki
		$result=api_sql_query($sql,__LINE__,__FILE__);
				
		$emailtext=get_lang('EmailWikipageDedeleted');
	}			
	
		
	///make and send email		
		
	if ($allow_send_mail)
	{	
		while ($row=Database::fetch_array($result))
		{		
			if(empty($charset)){$charset='ISO-8859-1';}
			$headers = 'Content-Type: text/html; charset='. $charset;
			$userinfo=Database::get_user_info_from_id($row['user_id']);	//$row['user_id'] obtained from tbl_wiki_mailcue
			$name_to=$userinfo['firstname'].' '.$userinfo['lastname'];
			$email_to=$userinfo['email'];
			$sender_name=get_setting('emailAdministrator');
			$sender_email=get_setting('emailAdministrator');
			$email_subject = get_lang('EmailWikiChanges').' - '.$_course['official_code'];
			$email_body= get_lang('DearUser').' '.$userinfo['firstname'].' '.$userinfo['lastname'].',<br /><br />';
			$email_body .= $emailtext.' <strong>'.$_course['name'].' - '.$group_name.'</strong><br /><br /><br />';						
			$email_body .= $email_user_author.' ('.$email_date_changes.')<br /><br /><br />';		
			$email_body .= $email_assignment.'<br /><br /><br />';					
			$email_body .= '<font size="-2">'.get_lang('EmailWikiChangesExt_1').': <strong>'.get_lang('NotifyChanges').'</strong><br />';
			$email_body .= get_lang('EmailWikiChangesExt_2').': <strong>'.get_lang('NotNotifyChanges').'</strong></font><br />';
			api_mail_html($name_to, $email_to, $email_subject, $email_body, $sender_name, $sender_email, $headers);
		}	
	}
}  


/**
 * Function export last wiki page version to document area
 * @author Juan Carlos Ra�a <herodoto@telefonica.net>
 */
function export2doc($wikiTitle, $wikiContents, $groupId)
{

	if ( 0 != $groupId)
	{
		$groupPart = '_group' . $groupId; // and add groupId to put the same title document in different groups
		$group_properties  = GroupManager :: get_group_properties($groupId);
		$groupPath = $group_properties['directory'];
	}
	else
	{
		$groupPart = '';
		$groupPath ='';
	}
	
	$exportDir = api_get_path(SYS_COURSE_PATH).api_get_course_path(). '/document'.$groupPath;
	$exportFile = replace_dangerous_char(replace_accents($wikiTitle), 'strict' ) . $groupPart;
	
	$i = 1;
	while ( file_exists($exportDir . '/' .$exportFile.'_'.$i.'.html') ) $i++; //only export last version, but in new export new version in document area
	$wikiFileName = $exportFile . '_' . $i . '.html';
	$exportPath = $exportDir . '/' . $wikiFileName;
	$wikiContents = stripslashes($wikiContents);
	file_put_contents( $exportPath, $wikiContents );		 
	$doc_id = add_document($_course, $groupPath.'/'.$wikiFileName,'file',filesize($exportPath),$wikiFileName);
	api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentAdded', api_get_user_id(), $groupId);					              
    // TODO: link to go document area
}


/**
 * Function wizard individual assignment
 * @author Juan Carlos Ra�a <herodoto@telefonica.net>
 */
function auto_add_page_users($assignment_type)
{
	global $assig_user_id; //need to identify end reflinks	

	$_clean['group_id']=(int)$_SESSION['_gid'];	
	
	
	if($_clean['group_id']==0)   
  	{	
  		//extract course members
		if(!empty($_SESSION["id_session"])){
			$a_users_to_add = CourseManager :: get_user_list_from_course_code($_SESSION['_course']['id'], true, $_SESSION['id_session']);
		}
		else
		{
			$a_users_to_add = CourseManager :: get_user_list_from_course_code($_SESSION['_course']['id'], true);
		}
	}
	else
	{ 
		//extract group members
		$subscribed_users = GroupManager :: get_subscribed_users($_clean['group_id']);
		$subscribed_tutors = GroupManager :: get_subscribed_tutors($_clean['group_id']);
		$a_users_to_add_with_duplicates=array_merge($subscribed_users, $subscribed_tutors);
	
		//remove duplicates
		$a_users_to_add = $a_users_to_add_with_duplicates;
		array_walk($a_users_to_add, create_function('&$value,$key', '$value = json_encode($value);'));
		$a_users_to_add = array_unique($a_users_to_add);
		array_walk($a_users_to_add, create_function('&$value,$key', '$value = json_decode($value, true);'));	
	}    

	
	$all_students_pages = array();

	//data about teacher
	$userinfo=Database::get_user_info_from_id(api_get_user_id());
	require_once(api_get_path(INCLUDE_PATH).'/lib/usermanager.lib.php');
	if (api_get_user_id()<>0)
	{		
		$image_path = UserManager::get_user_picture_path_by_id(api_get_user_id(),'web',false, true);
		$image_repository = $image_path['dir'];
		$existing_image = $image_path['file'];
		$photo= '<img src="'.$image_repository.$existing_image.'" alt="'.$name.'"  width="40" height="50" align="top" title="'.$name.'"  />';	
	}
	else
	{
		$photo= '<img src="'.api_get_path(WEB_CODE_PATH)."img/unknown.jpg".'" alt="'.$name.'"  width="40" height="50" align="top"  title="'.$name.'"  />';
	}			 
	
	//teacher assignment title
	$title_orig=$_POST['title'];
	
	//teacher assignment reflink
	$link2teacher=$_POST['title']= $title_orig."_uass".api_get_user_id();
	
	//first: teacher name, photo, and assignment description (original content)	
    $content_orig_A='<div align="center" style="font-size:24px; background-color: #F5F8FB;  border:double">'.$photo.get_lang('Teacher').': '.$userinfo['firstname'].$userinfo['lastname'].'</div><br/><div>';
	$content_orig_B='<h1>'.get_lang('AssignmentDescription').'</h1></div><br/>'.$_POST['content'];
	
    //Second: student list (names, photo and links to their works).
	//Third: Create Students work pages.
	
   	foreach($a_users_to_add as $user_id=>$o_user_to_add)
	{					  
		if($o_user_to_add['user_id'] != api_get_user_id()) //except that puts the task
		{
											 		 
			$assig_user_id= $o_user_to_add['user_id']; //identifies each page as created by the student, not by teacher		
			$image_path = UserManager::get_user_picture_path_by_id($assig_user_id,'web',false, true);
			$image_repository = $image_path['dir'];
			$existing_image = $image_path['file'];
			$name= $o_user_to_add['lastname'].', '.$o_user_to_add['firstname'];
			$photo= '<img src="'.$image_repository.$existing_image.'" alt="'.$name.'"  width="40" height="50" align="bottom" title="'.$name.'"  />';
			
			$is_tutor_of_group = GroupManager :: is_tutor_of_group($assig_user_id,$_clean['group_id']); //student is tutor			
			$is_tutor_and_member = (GroupManager :: is_tutor_of_group($assig_user_id,$_clean['group_id']) && GroupManager :: is_subscribed($assig_user_id, $_clean['group_id'])); //student is tutor and member		
			
			if($is_tutor_and_member)
			{
				$status_in_group=get_lang('GroupTutorAndMember');
				
			}
			else
			{
				if($is_tutor_of_group)
				{
					$status_in_group=get_lang('GroupTutor');
				}
				else
				{
					$status_in_group=" "; //get_lang('GroupStandardMember')
				}
			}					
			
			if($assignment_type==1)
			{			 
				$_POST['title']= $title_orig;
				$_POST['comment']=get_lang('AssignmentFirstComToStudent');				
				$_POST['content']='<div align="center" style="font-size:24px; background-color: #F5F8FB;  border:double">'.$photo.get_lang('Student').': '.$name.'</div>[['.$link2teacher.' | '.get_lang('AssignmentLinktoTeacherPage').']] '; //If $content_orig_B is added here, the task written by the professor was copied to the page of each student. TODO: config options
				
			   //AssignmentLinktoTeacherPage	        
			 	$all_students_pages[] = '<li>'.$o_user_to_add['lastname'].', '.$o_user_to_add['firstname'].' [['.$_POST['title']."_uass".$assig_user_id.' | '.$photo.']] '.$status_in_group.'</li>';
				
				$_POST['assignment']=2;
				
			}			
			save_new_wiki();	
		}	
        		
	}//end foreach for each user
	
	
	foreach($a_users_to_add as $user_id=>$o_user_to_add)
	{
			
		if($o_user_to_add['user_id'] == api_get_user_id())
		{		
			$assig_user_id=$o_user_to_add['user_id'];			
			if($assignment_type==1)			
			 {					 
				$_POST['title']= $title_orig;	
				$_POST['comment']=get_lang('AssignmentDesc');
				sort($all_students_pages);
				$_POST['content']=$content_orig_A.$content_orig_B.'<br/><div align="center" style="font-size:24px; background-color: #F5F8FB;  border:double">'.get_lang('AssignmentLinkstoStudentsPage').'<br/><strong>'.$title_orig.'</strong></div><div style="background-color: #F5F8FB; border:double"><ol>'.implode($all_students_pages).'</ol></div><br/>';
			 	$_POST['assignment']=1;
						
			 }					 
			
			save_new_wiki();
		}
			
	} //end foreach to teacher
}
?>