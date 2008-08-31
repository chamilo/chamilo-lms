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
event_access_tool('TOOL_WIKI'); 

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
	$interbreadcrumb[]= array ("url"=>"../group/group_space.php?gidReq=".$_SESSION['_gid'], "name"=> get_lang('GroupSpace').' ('.$group_properties['name'].')');
	$add_group_to_title = ' ('.$group_properties['name'].')';	
	$groupfilter='group_id="'.$_clean['group_id'].'"';
}
else
{
	$groupfilter='group_id IS NULL';
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
	$index='index';
}
else
{
	$page=Security::remove_XSS($_GET['title']); 
}

// some titles are not allowed
//$not_allowed_titles=array("Index", "RecentChanges","AllPages", "Categories"); //not used for now

	
//SANITY CHECK FOR NOTIFY BY EMAIL Juan Carlos Raña
$tbl_wiki_mailcue 	= "`".$_course['dbNameGlu']."wiki_mailcue`";

if (api_sql_query("SELECT * FROM $tbl_wiki_mailcue")==false)
{		  
	$sql="CREATE TABLE ".$tbl_wiki_mailcue." (
	  id int(11) NOT NULL,
	  user_id int(11) NOT NULL,	 	 
	  PRIMARY KEY  (id)
	) TYPE=MyISAM;"; 
	$result=api_sql_query($sql,__FILE__,__LINE__) or die(mysql_error());
}

/*
-----------------------------------------------------------
	Configuration settings
-----------------------------------------------------------
*/

$fck_attribute['Width'] = '100%';
$fck_attribute['ToolbarSet'] = 'Wiki';
if(!api_is_allowed_to_edit())
{
	$fck_attribute['Config']['UserStatus'] = 'student';
}


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
Display::display_introduction_section(TOOL_WIKI);

/*
-----------------------------------------------------------
  			ACTIONS
-----------------------------------------------------------
*/

// saving a change
if ($_POST['SaveWikiChange'] AND $_POST['title']<>'')
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
if ($_POST['SaveWikiNew'])
{
	if(empty($_POST['title']))
	{
		Display::display_normal_message(get_lang("NoWikiPageTitle")); 
	}
	else
	{	 
	   $_clean['assignment']=Database::escape_string($_POST['assignment']); //Juan Carlos Raña for mode assignment
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


// check last version hack by Juan Carlos Raña
if ($_GET['view'])
{

	$sql='SELECT * FROM '.$tbl_wiki.'WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.' ORDER BY id DESC';
		$result=api_sql_query($sql,__LINE__,__FILE__);
		$row=Database::fetch_array($result); // we do not need a while loop since we are always displaying the last version
		
		if ($_GET['view']<$row['id'])
		{
		   $message= get_lang('NoAreSeeingTheLastVersion');
		   Display::display_warning_message($message,false);
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
echo '<li><a href="index.php?action=deletewiki&amp;title='.$page.'"'.is_active_navigation_tab('deletewiki').'"><img src="../img/wiki/wdeletewiki.png" title="'.get_lang('DeleteWiki').'" align="absmiddle"/></a></li>';

//menu more
//echo '<li><a href="index.php?action=more&amp;title='.$page.'"'.is_active_navigation_tab('more').'"><img src="../img/wiki/wmore.png" title="'.get_lang('More').'" align="absmiddle"/></a></li>'; //no avalaible so far. TO DO

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
echo '<li><a href="index.php?action=delete&amp;title='.$page.'"'.is_active_navigation_tab('delete').'"><img src="../img/wiki/wdelete.png" title="'.get_lang('DeleteThisPage').'" align="absmiddle"/> '.get_lang('Delete').'</a></li>';
echo '</ul></div>';

/*
-----------------------------------------------------------
  			MAIN WIKI AREA
-----------------------------------------------------------
*/
echo "<div id='mainwiki'>";


/////////////////////// more options /////////////////////// Juan Carlos Raña Trabado

if ($_GET['action']=='more')
{
//to do

}


/////////////////////// delete current page /////////////////////// Juan Carlos Raña Trabado

if ($_GET['action']=='delete')
{
	
	if(api_is_allowed_to_edit() || api_is_platform_admin())
	{
	    echo '<br>'; 
	    echo '<b>'.get_lang('DeletePageHistory').'</b>'; 
	    echo '<hr>';   
		$message = get_lang('ConfirmDeletePage')."</p>"."<p>"."<a href=\"index.php\">".get_lang("No")."</a>"."&nbsp;&nbsp;|&nbsp;&nbsp;"."<a href=\"".api_get_self()."?action=delete&amp;title=".$page."&amp;delete=yes\">".get_lang("Yes")."</a>"."</p>";
		
		if (!isset ($_GET['delete']))
		{
			Display::display_warning_message($message,false);
		}
		
		if ($_GET['delete'] == 'yes')
		{
			$sql='DELETE FROM '.$tbl_wiki.' WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.' ORDER BY id DESC';
	  		api_sql_query($sql,__FILE__,__LINE__); 
			
			////
			//here to do: delete discussion and mailcue too
			///
			
	  		Display::display_confirmation_message(get_lang('WikiPageDeleted')); 
		}
	}
	else
	{
		Display::display_normal_message(get_lang("OnlyAdminDeletePageWiki"));		
	}	
}


/////////////////////// delete all wiki /////////////////////// Juan Carlos Raña Trabado

if ($_GET['action']=='deletewiki')
{
	    echo '<br>'; 
	    echo '<b>'.get_lang('DeleteWiki').'</b>'; 
	    echo '<hr>';
}


/////////////////////// search pages /////////////////////// Juan Carlos Raña Trabado

if ($_GET['action']=='searchpages')
{
    echo '<br>'; 
    echo '<b>'.$SearchPages.'</b>'; 
    echo '<hr>';
	
	if (!$_POST['Skeyword'])
	{	
		echo '<form id="fsearch" method="POST" action="index.php?action=searchpages">';		
		echo '<input type="text" name="Skeyword" >';
		echo '<input type="submit" value="'.get_lang('Search').'"/></br></br>';	
		echo '<input type="checkbox" name="Scontent" value="1"> '.get_lang('AlsoSearchContent');
		echo '</form>';		
	}
	else
	{	
		if($_POST['Scontent']=="1")
		{
			$sql="SELECT * FROM ".$tbl_wiki." WHERE  ".$groupfilter." AND title LIKE '%".$_POST['Skeyword']."%' OR content LIKE '%".$_POST['Skeyword']."%' GROUP BY reflink ORDER BY title ASC";
		}
		else
		{
			$sql="SELECT * FROM ".$tbl_wiki." WHERE  ".$groupfilter." AND title LIKE '%".$_POST['Skeyword']."%' GROUP BY reflink ORDER BY title ASC";
		}

		//show result	
		$_clean['group_id']=(int)$_SESSION['_gid'];		
		$result=api_sql_query($sql,__LINE__,__FILE__);	
		
		echo '<ul>';
				
		while ($row=Database::fetch_array($result))
		{	
			$userinfo=Database::get_user_info_from_id($row['user_id']);			
			
			//Show page to students if not is hidden. Show page to all teachers if is hidden. Mode assignments: show pages to student only if student it the author
			if($row['visibility']==1 || api_is_allowed_to_edit() || api_is_platform_admin() || ($row['assignment']==2 && $row['visibility']==0 && (api_get_user_id()==$row['user_id'])))
			{							
				$year = substr($row['timestamp'], 0, 4);
				$month = substr($row['timestamp'], 5, 2);
				$day = substr($row['timestamp'], 8, 2);
				$hours=substr($row['timestamp'], 11,2);
				$minutes=substr($row['timestamp'], 14,2);
				$seconds=substr($row['timestamp'], 17,2);
				
				//show teacher assignment
				if($row['assignment']==1)
				{
				    //teachers assigment pages only list for teachers	
				 	if(api_is_allowed_to_edit() || api_is_platform_admin())
					{
						$ShowAssignment='<img src="../img/wiki/assignment.gif" />';
						echo '<li>';
						echo '<a href="'.$_SERVER['PHP_SELF'].'?cidReq='.$_course[id].'&action=showpage&title='.$row['reflink'].'&group_id='.$_clean['group_id'].'">'.$ShowAssignment.$row['title'].'</a>';
						
						echo '...'.$userinfo['lastname'].', '.$userinfo['firstname']; 						
						echo '...'.$day.' '.$MonthsLong[$month-1].' '.$year.' '.$hours.":".$minutes.":".$seconds.'</li>'; 
												 
					 }
			    }
				//show student assignment
				if ($row['assignment']==2)
				{	
				    //student assignment pages only list for each student author and for teachers
					if ($row['user_id']==(int)api_get_user_id() || api_is_allowed_to_edit() || api_is_platform_admin())
					{
						$ShowAssignment='<img src="../img/wiki/works.gif" />';	
						echo '<li>';
						echo '<a href="'.$_SERVER['PHP_SELF'].'?cidReq='.$_course[id].'&action=showpage&title='.$row['reflink'].'&group_id='.$_clean['group_id'].'">'.$ShowAssignment.$row['title'].'</a>';
									
						echo '...'.$userinfo['lastname'].', '.$userinfo['firstname']; 								   
						echo '...'.$day.' '.$MonthsLong[$month-1].' '.$year.' '.$hours.":".$minutes.":".$seconds.'</li>';											
					}
				}
				//show  wiki pages standard
				if ($row['assignment']==0)
				{
					$ShowAssignment='<img src="../img/wiki/trans.gif" />';	
				  	echo '<li>';
					echo '<a href="'.$_SERVER['PHP_SELF'].'?cidReq='.$_course[id].'&action=showpage&title='.$row['reflink'].'&group_id='.$_clean['group_id'].'">'.$ShowAssignment.$row['title'].'</a>';
				   
					if ($row['user_id']<>0)
					{
						echo '...'.$userinfo['lastname'].', '.$userinfo['firstname']; 
					}
					else
					{
						echo  get_lang('Anonymous').' ('.$row[user_ip].')'; 
					}		   
					echo '...'.$day.' '.$MonthsLong[$month-1].' '.$year.' '.$hours.":".$minutes.":".$seconds.'</li>'; 				
				}			 
			}
		}		
		echo '</ul>';			
	} 	
}


///////////////////////  What links here. Show pages that have linked this page /////////////////////// Juan Carlos Raña Trabado

if ($_GET['action']=='links')
{
    echo '<br>'; 
    echo '<b>'.$LinksPages.'</b>';
    echo '<hr>';
	
	if (!$_GET['title'])
	{
	   Display::display_normal_message(get_lang("MustSelectPage"));	   	   
    }
	else
	{	
	      
		$sql='SELECT * FROM '.$tbl_wiki.' WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.'';		
		$result=api_sql_query($sql,__FILE__,__LINE__); //necessary for pages with compound name
						
		$row=Database::fetch_array($result);	
		echo $LinksPagesFrom.': <a href="'.$_SERVER['PHP_SELF'].'?cidReq='.$_course[id].'&action=showpage&title='.$page.'&group_id='.Security::remove_XSS($_GET['group_id']).'">'.$row['title'].'</a>';	
			

		if ($page==get_lang('DefaultTitle'))
		{
			$page='index';
		}		
	
		$sql="SELECT * FROM ".$tbl_wiki." WHERE  ".$groupfilter." AND linksto LIKE '%".html_entity_decode(Database::escape_string(stripslashes(urldecode($page))))."%' GROUP BY reflink ORDER BY title ASC";		
		$result=api_sql_query($sql,__LINE__,__FILE__);
	
		//show result	
		
		echo '<ul>';				
			
		while ($row=Database::fetch_array($result))
		{    
			$userinfo=Database::get_user_info_from_id($row['user_id']);				
		
			//Show page to students if not is hidden, but the author can see. Show page to all teachers if is hidden. Mode assignments: show pages to student only if student is the author				
			if($row['visibility']==1 || api_is_allowed_to_edit() || api_is_platform_admin() || ($row['assignment']==2 && $row['visibility']==0 && (api_get_user_id()==$row['user_id'])))
			{										
				$year = substr($row['timestamp'], 0, 4);
				$month = substr($row['timestamp'], 5, 2);
				$day = substr($row['timestamp'], 8, 2);
				$hours=substr($row['timestamp'], 11,2);
				$minutes=substr($row['timestamp'], 14,2);
				$seconds=substr($row['timestamp'], 17,2);					
				
				//Description assignments visible for all teachers
				if($row['assignment']==1)
				{						
					if(api_is_allowed_to_edit() || api_is_platform_admin())
					{
						$ShowAssignment='<img src="../img/wiki/assignment.gif" />';
						echo '<li>';
						echo '<a href="'.$_SERVER['PHP_SELF'].'?cidReq='.$_course[id].'&action=showpage&title='.$row['reflink'].'&group_id='.$_clean['group_id'].'">'.$ShowAssignment.$row['title'].'</a>';						
						echo '...'.$userinfo['lastname'].', '.$userinfo['firstname']; 							   
						echo '...'.$day.' '.$MonthsLong[$month-1].' '.$year.' '.$hours.":".$minutes.":".$seconds.'</li>'; 										 
					 }
				}
				
				//Work on the assignments visible for each student
				if ($row['assignment']==2)
				{	
					if ($row['user_id']==(int)api_get_user_id() || api_is_allowed_to_edit() || api_is_platform_admin())
					{
						$ShowAssignment='<img src="../img/wiki/works.gif" />';	
						echo '<li>';
						echo '<a href="'.$_SERVER['PHP_SELF'].'?cidReq='.$_course[id].'&action=showpage&title='.$row['reflink'].'&group_id='.$_clean['group_id'].'">'.$ShowAssignment.$row['title'].'</a>';
						echo '...'.$userinfo['lastname'].', '.$userinfo['firstname']; 							   
						echo '...'.$day.' '.$MonthsLong[$month-1].' '.$year.' '.$hours.":".$minutes.":".$seconds.'</li>';												
					}
				}
				
				//show  wiki pages standard
				if ($row['assignment']==0)
				{
					$ShowAssignment='<img src="../img/wiki/trans.gif" />';	
				 	echo '<li>';
				   	echo '<a href="'.$_SERVER['PHP_SELF'].'?cidReq='.$_course[id].'&action=showpage&title='.$row['reflink'].'&group_id='.$_clean['group_id'].'">'.$ShowAssignment.$row['title'].'</a>';
					
					if ($row['user_id']<>0)
					{
						echo '...'.$userinfo['lastname'].', '.$userinfo['firstname']; 
					}
					else
					{
						echo  get_lang('Anonymous').' ('.$row[user_ip].')'; 
					}
							   
					echo '...'.$day.' '.$MonthsLong[$month-1].' '.$year.' '.$hours.":".$minutes.":".$seconds.'</li>'; 				
				}	 
			}		
		}
					
		echo '</ul>';	
	} 
}


/////////////////////// adding a new page ///////////////////////


// Display the form for adding a new wiki page
if ($_GET['action']=='addnew')
{
	
	//first, check if page index was created. chektitle=false
	if (checktitle('index'))
	{	
		Display::display_normal_message(get_lang('GoAndEditMainPage'));
	}
	
	elseif (check_addnewpagelock() && (api_is_allowed_to_edit()==false || api_is_platform_admin()==false))
	{
		Display::display_normal_message(get_lang('AddPagesLocked')); 		
	}
	else
	{  
		if(GroupManager :: is_user_in_group($_user['user_id'],$_SESSION['_gid']))
		{
			if(api_is_allowed_to_edit() || api_is_platform_admin() || GroupManager :: is_user_in_group($_user['user_id'],$_SESSION['_gid']))
			{					
				echo '<br>'; 
				echo '<b>'.get_lang('AddNew').'</b>'; 
				echo '<hr>'; 
				display_new_wiki_form();
				
			}
			else
			{
				Display::display_normal_message(get_lang('OnlyAddPagesGroupMembers')); 
			}
		}
		else
		{ 		
			 echo '<br>'; 
			 echo '<b>'.get_lang('AddNew').'</b>'; 		 
			 echo '<hr>'; 
			 display_new_wiki_form(); 
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
	if(($row['reflink']=='index' || $row['assignment']==1) && (!api_is_allowed_to_edit() || !api_is_platform_admin()))
	{
  
      Display::display_normal_message(get_lang('OnlyEditPagesCourseManager'));
  
	}
    else
	{	
		$PassEdit=false;
		
		if(stripslashes($row['assignment'])==1)
		{
		    Display::display_normal_message(get_lang('EditAssignmentWarning'));
			$icon_assignment='<img src="../img/wiki/assignment.gif" alt="'.get_lang('AssignmentDescExtra').'" />';
		}
		elseif(stripslashes($row['assignment'])==2)
		{
			$icon_assignment='<img src="../img/wiki/works.gif" alt="'.get_lang('AssignmentWorkExtra').'" />';
		}
       
	    //check if is a wiki group
		if($_clean['group_id']!==0)
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
				echo get_lang('Comments').':&nbsp;&nbsp;<input type="text" name="comment" value="'.stripslashes($row['comment']).'">&nbsp;&nbsp;&nbsp;'; 
				//}												
								
				echo '<INPUT TYPE="hidden" NAME="assignment" VALUE="'.stripslashes($row['assignment']).'"/>';
			    //echo '<INPUT TYPE="hidden" NAME="startdate_assig" VALUE=".stripslashes($row['startdate_assig'])."/>'; //off for now
				//echo '<INPUT TYPE="hidden" NAME="enddate_assig" VALUE=".stripslashes($row['enddate_assig'])."/>'; //off for now				 
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
				echo '<input type="submit" name="SaveWikiChange" value="'.get_lang('langSave').'">';
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

    //First, see the property visibility that is at the last register and therefore we should select descending order. But to give ownership to each record, this is no longer necessary except for the title. TO DO: check this

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
			$icon_assignment='<img src="../img/wiki/assignment.gif" alt="'.get_lang('AssignmentDescExtra').'" />';
		}
		elseif($KeyAssignment==2)
		{
			$icon_assignment='<img src="../img/wiki/works.gif" alt="'.get_lang('AssignmentWorkExtra').'" />';
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
			echo '<input type="submit" name="HistoryDifferences" value="'.get_lang('ShowDifferences').' '.get_lang('LinesDiff').'" />';
			echo '<input type="submit" name="HistoryDifferences2" value="'.get_lang('ShowDifferences').' '.get_lang('WordsDiff').'" />';
			echo '<br/><br/>';
	
			$counter=0;
			$total_versions=Database::num_rows($result);
			
			while ($row=Database::fetch_array($result))
			{
				$userinfo=Database::get_user_info_from_id($row['user_id']);
				
				$year = substr($row['timestamp'], 0, 4);
				$month = substr($row['timestamp'], 5, 2);
				$day = substr($row['timestamp'], 8, 2);
				$hours=substr($row['timestamp'], 11,2);
				$minutes=substr($row['timestamp'], 14,2);
				$seconds=substr($row['timestamp'], 17,2);				
		
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
				echo ' ('.get_lang('Version').' '.$row['version'].')'; //juan carlos crudo
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
			echo '<input type="submit" name="HistoryDifferences" value="'.get_lang('ShowDifferences').' '.get_lang('LinesDiff').'" />';
			echo '<input type="submit" name="HistoryDifferences2" value="'.get_lang('ShowDifferences').' '.get_lang('WordsDiff').'" />';
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
		
		    if($_POST['HistoryDifferences'])
			{
				include('diff.inc.php');
				//title
				echo '<div id="wikititle">'.stripslashes($version_new['title']).' <font size="-2"><i>('.get_lang('DifferencesNew').'</i> <font style="background-color:#aaaaaa">'.stripslashes($version_new['timestamp']).'</font> <i>'.get_lang('DifferencesOld').'</i> <font style="background-color:#aaaaaa">'.stripslashes($version_old['timestamp']).'</font>) '.get_lang('Legend').':  <span class="diffAdded" >'.get_lang(WikiDiffAddedLine).'</span> <span class="diffDeleted" >'.get_lang(WikiDiffDeletedLine).'</span> <span class="diffMoved" >'.get_lang(WikiDiffMovedLine).'</span></font></div>'; 	
			}
			if($_POST['HistoryDifferences2'])
			{			
				require_once 'Text/Diff.php';
   				require_once 'Text/Diff/Renderer/inline.php';
				//title
				echo '<div id="wikititle">'.stripslashes($version_new['title']).' <font size="-2"><i>('.get_lang('DifferencesNew').'</i> <font style="background-color:#aaaaaa">'.stripslashes($version_new['timestamp']).'</font> <i>'.get_lang('DifferencesOld').'</i> <font style="background-color:#aaaaaa">'.stripslashes($version_old['timestamp']).'</font>) '.get_lang('Legend').':  <span class="diffAddedTex" >'.get_lang(WikiDiffAddedTex).'</span> <span class="diffDeletedTex" >'.get_lang(WikiDiffDeletedTex).'</span></font></div>'; 	
			}			
				
			echo '<div class="diff"><br /><br />';	
			
			if($_POST['HistoryDifferences'])
			{				
				echo '<table>'.diff( stripslashes($version_old['content']), stripslashes($version_new['content']), true, 'format_table_line' ).'</table>'; // format_line mode is better for words
				echo '</div>'; 					
				echo '</div>';
				
				echo '<br>'; 
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
							
	        if($_POST['HistoryDifferences2'])
			{
					
				$lines1 = array(stripslashes($version_old['content'])); //it may not be necessary stripslashes. to do
				$lines2 = array(stripslashes($version_new['content'])); //it may not be necessary stripslashes. to do
	
				$diff = &new Text_Diff($lines1, $lines2);
	
				$renderer = &new Text_Diff_Renderer_inline();
				echo '<style>del{background:#fcc}ins{background:#cfc}</style>'.$renderer->render($diff); // Code inline	
				//echo '<div class="diffEqual">'.html_entity_decode($renderer->render($diff)).'</div>'; // Html inline. By now, turned off by problems in comparing pages separated by more than one version
				echo '</div>'; 					
				echo '</div>';
				
				echo '<br>'; 
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
//rss feed. to do
//

if ($_GET['action']=='recentchanges')
{
	$_clean['group_id']=(int)$_SESSION['_gid'];

	$sql='SELECT * FROM '.$tbl_wiki.' WHERE '.$groupfilter.' ORDER BY timestamp DESC LIMIT 0,10'; // last 10
	$result=api_sql_query($sql,__LINE__,__FILE__);
	
	echo  '<br>'; 
	echo '<b>'.get_lang('RecentChanges').'</b><br>'; 
    echo  '<hr>';	
	echo '<ul>';
	
	$group_id=Security::remove_XSS($_GET['group_id']);
	while ($row=Database::fetch_array($result))
	{
		$userinfo=Database::get_user_info_from_id($row['user_id']);
		
	    $year = substr($row['timestamp'], 0, 4);
		$month = substr($row['timestamp'], 5, 2);
		$day = substr($row['timestamp'], 8, 2);
		$hours=substr($row['timestamp'], 11,2);
		$minutes=substr($row['timestamp'], 14,2);
		$seconds=substr($row['timestamp'], 17,2);
				
		$url_enc=urlencode($row['reflink']);
	
		//Show page to students if isn't hidden. Show page to all teachers if is hidden. Mode assignments: If is hidden, show pages to student only if student is the author
		if($row['visibility']==1 || api_is_allowed_to_edit() || api_is_platform_admin() || ($row['assignment']==2 && $row['visibility']==0 && (api_get_user_id()==$row['user_id'])))
		{		
			if($row['assignment']==1)
			{
				if(api_is_allowed_to_edit() || api_is_platform_admin()) //Mode assignments: show pages assignment (task) only for teachers
				{
					$ShowAssignment='<img src="../img/wiki/assignment.gif" />';		
					 
					if ($row['version']==1) $flag=get_lang('AddedBy');
                    if ($row['version']>1)  $flag=get_lang('EditedBy');					 			 			 
				
					echo '<li><a href="'.$_SERVER['PHP_SELF'].'?cidReq='.$_course[id].'&action=showpage&amp;title='.$url_enc.'&amp;view='.$row['id'].'&group_id='.$group_id.'">'; 		   
					echo $day.' '.$MonthsLong[$month-1].' '.$year.' '.$hours.":".$minutes.":".$seconds;
					echo '</a> ... '.$ShowAssignment.'<a href="'.$_SERVER['PHP_SELF'].'?cidReq='.$_course[id].'&action=showpage&amp;title='.$url_enc.'&group_id='.$group_id.'">'.$row['title'].'</a> ...'.$flag.' <a href="../user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.$userinfo['lastname'].', '.$userinfo['firstname'].'</a></li>';  				
		    	}
			}		

			if($row['assignment']==2)
			{
				if ($row['user_id']==(int)api_get_user_id() || api_is_allowed_to_edit() || api_is_platform_admin()) //Mode assignments: show pages assignment (works) only for teachers. Also show pages to student only if student is the author. 
				{
					 $ShowAssignment='<img src="../img/wiki/works.gif" />';
					 
					  if ($row['version']==1) $flag=get_lang('AddedBy');
                      if ($row['version']>1)  $flag=get_lang('EditedBy');
					
					 echo '<li><a href="'.$_SERVER['PHP_SELF'].'?cidReq='.$_course[id].'&action=showpage&amp;title='.$url_enc.'&amp;view='.$row['id'].'&group_id='.$group_id.'">'; 		   
					 echo $day.' '.$MonthsLong[$month-1].' '.$year.' '.$hours.":".$minutes.":".$seconds;				
					 echo '</a> ... '.$ShowAssignment.'<a href="'.$_SERVER['PHP_SELF'].'?cidReq='.$_course[id].'&action=showpage&amp;title='.$url_enc.'&group_id='.$group_id.'">'.$row['title'].'</a> ...'.$flag.' <a href="../user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.$userinfo['lastname'].', '.$userinfo['firstname'].'</a></li>';  					
		    	}
			}	

			if($row['assignment']==0)
			{
				
				$ShowAssignment='<img src="../img/wiki/trans.gif" />';	 
				
				if ($row['version']==1) $flag=get_lang('AddedBy');
                if ($row['version']>1)  $flag=get_lang('EditedBy');
				 
				echo '<li><a href="'.$_SERVER['PHP_SELF'].'?cidReq='.$_course[id].'&action=showpage&amp;title='.$url_enc.'&amp;view='.$row['id'].'&group_id='.$group_id.'">'; 		   
				echo $day.' '.$MonthsLong[$month-1].' '.$year.' '.$hours.":".$minutes.":".$seconds;
				if ($row['user_id']<>0) 
				{
					echo '</a> ... '.$ShowAssignment.'<a href="'.$_SERVER['PHP_SELF'].'?cidReq='.$_course[id].'&action=showpage&amp;title='.$url_enc.'&amp;view='.$row['id'].'&group_id='.$group_id.'">'.$row['title'].'</a> ...'.$flag.' <a href="../user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.$userinfo['lastname'].', '.$userinfo['firstname'].'</a></li>';
				}
				else
				{
					echo '</a> ... <a href="'.$_SERVER['PHP_SELF'].'?cidReq='.$_course[id].'&action=showpage&amp;title='.$url_enc.'&group_id='.$group_id.'">'.$row['title'].'</a> ... '.$flag.' '.get_lang('Anonymous').' ('.$row['user_ip'].')</a></li>'; 						
				}			    
			}					
		}
			
		//print_r($userinfo);
	}
	echo '</ul>';
}



/////////////////////// all pages ///////////////////////


if ($_GET['action']=='allpages')
{
	$_clean['group_id']=(int)$_SESSION['_gid'];

	$sql='SELECT * FROM '.$tbl_wiki.' WHERE '.$groupfilter.' GROUP BY reflink ORDER BY title ASC'; //tasks grouped by reflink instead of tilte, because there may be pages with the same name but with different reflink. This is true of the tasks
	
	$result=api_sql_query($sql,__LINE__,__FILE__);
	
	echo  '<br>';
	echo '<b>'.get_lang('AllPages').'</b>'; 
   	echo '<hr>'; 
	
	echo '<ul>';
	
	while ($row=Database::fetch_array($result))
	{
		$userinfo=Database::get_user_info_from_id($row['user_id']);			
			
		$year = substr($row['timestamp'], 0, 4);
		$month = substr($row['timestamp'], 5, 2);
		$day = substr($row['timestamp'], 8, 2);
		$hours=substr($row['timestamp'], 11,2);
		$minutes=substr($row['timestamp'], 14,2);
		$seconds=substr($row['timestamp'], 17,2);
			
		$url_enc=urlencode($row['reflink']);	
			
		//Show page to students if isn't hidden. Show page to all teachers if is hidden. Mode assignments: If is hidden, show pages to student only if student is the author
		if($row['visibility']==1 || api_is_allowed_to_edit() || api_is_platform_admin() || ($row['assignment']==2 && $row['visibility']==0 && (api_get_user_id()==$row['user_id'])))
		{
			if($row['assignment']==1)
			{
				if(api_is_allowed_to_edit() || api_is_platform_admin()) //Mode assignments: show pages assignment (task) only for teachers
			 	{
				 	$ShowAssignment='<img src="../img/wiki/assignment.gif" />';
					 echo '<li><a href="'.$_SERVER['PHP_SELF'].'?cidReq='.$_course[id].'&action=showpage&title='.$url_enc.'&group_id='.Security::remove_XSS($_GET['group_id']).'">'.$ShowAssignment.$row['title'].'</a> ('.get_lang('AssignmentDesc').' '.$userinfo['firstname'].' '.$userinfo['lastname'].' )</li>';
			   	}
			}
			
			if ($row['assignment']==2)
			{	
				//Mode assignments: show pages assignment (works) only for teachers. Also show pages to student only if student is the author.
				if ($row['user_id']==(int)api_get_user_id() || api_is_allowed_to_edit() || api_is_platform_admin())
				{
				  $ShowAssignment='<img src="../img/wiki/works.gif" />';						 
				  echo '<li><a href="'.$_SERVER['PHP_SELF'].'?cidReq='.$_course[id].'&action=showpage&title='.$url_enc.'&group_id='.Security::remove_XSS($_GET['group_id']).'">'.$ShowAssignment.$row['title'].'</a> ('.get_lang('AssignmentWork').' '.$userinfo['firstname'].' '.$userinfo['lastname'].' )</li>';
				 }
			}			
			if ($row['assignment']==0)
			{
			  	$ShowAssignment='<img src="../img/wiki/trans.gif" />';			
			   	echo '<li><a href="'.$_SERVER['PHP_SELF'].'?cidReq='.$_course[id].'&action=showpage&title='.$url_enc.'&group_id='.Security::remove_XSS($_GET['group_id']).'">'.$ShowAssignment.$row['title'].'</a></li>';
			}					
		}
	}	
	echo '</ul>';
}




/////////////////////// discuss pages ///////////////////////


if ($_GET['action']=='discuss')
{
	//select page to discuss
    $sql='SELECT * FROM '.$tbl_wiki.'WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.' ORDER BY id ASC';
	$result=api_sql_query($sql,__LINE__,__FILE__);
	$row=Database::fetch_array($result); // we do not need a while loop since we are always displaying the last version	
	$id=$row['id'];
	$wuid=$row['user_id'];
	$userinfo=Database::get_user_info_from_id($row['user_id']);	
	
	//Sanity check
	$tbl_wiki_discuss 	= $_course['dbNameGlu']."wiki_discuss";	
	if (api_sql_query("SELECT * FROM `$tbl_wiki_discuss`")==false)
	{
		
		$sql="CREATE TABLE `$tbl_wiki_discuss` (
		  `id` int(11) NOT NULL auto_increment,
		  `publication_id` int(11) NOT NULL default '0',
		  `userc_id` int(11) NOT NULL default '0',	 
		  `comment` text NOT NULL,
		  `p_score` varchar(255) default NULL,
		  `timestamp` timestamp(14) NOT NULL,
		  PRIMARY KEY  (`id`)
		) TYPE=MyISAM;"; 
		
		$result=api_sql_query($sql) or die(mysql_error());
	}

	//check discuss visibility.  Show discussion to students if isn't hidden. Show page to all teachers if is hidden. 
	if (check_visibility_discuss())
	{	
	    //Mode assignments: If is hidden, show pages to student only if student is the author
	 	if(($row['assignment']==2 && $row['visibility_disc']==0 && (api_get_user_id()==$row['user_id']))==false)	
	    {	
	 		$visibility_disc= '<img src="../img/wiki/invisible.gif" alt="'.get_lang('HideDiscussExtra').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('ShowDiscuss').'</font>';
	    }
	}
	else
	{			  
		if(api_is_allowed_to_edit() || api_is_platform_admin()) 
		{
			$visibility_disc= '<img src="../img/wiki/visible.gif" alt="'.get_lang('ShowDiscussExtra').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('HideDiscuss').'</font>';
		}	     	
	}	
		
	//check add messages lock.
	if (check_addlock_discuss())
	{	
	 	$addlock_disc= '<img src="../img/wiki/lock.gif" alt="'.get_lang('LockDiscussExtra').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('UnlockDiscuss').'</font>';	 
	}
	else
	{			  
		if(api_is_allowed_to_edit() || api_is_platform_admin()) 
		{
			$addlock_disc= '<img src="../img/wiki/unlock.gif" alt="'.get_lang('UnlockDiscussExtra').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('LockDiscuss').'</font>';
		}	     	
	}		
	
	//check add rating lock. Show/Hide list to rating for all student
	if (check_ratinglock_discuss())
	{
		//Mode assignment: check. to do
		if(($row['assignment']==2 && $row['ratinglock_disc']==0 && (api_get_user_id()==$row['user_id']))==false)		
	    {		
	 		$ratinglock_disc= '<img src="../img/wiki/rating_na.gif" alt="'.get_lang('LockRatingDiscussExtra').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('UnlockRatingDiscuss').'</font>';
		}
	}
	else
	{			  
		if(api_is_allowed_to_edit() || api_is_platform_admin()) 
		{
			$ratinglock_disc= '<img src="../img/wiki/rating.gif" alt="'.get_lang('UnlockRatingDiscussExtra').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('LockRatingDiscuss').'</font>';
		}	     	
	}		
	
	//check notify by email		
	if (check_notify_discuss())
	{
	 	$notify_disc= '<img src="../img/wiki/send_mail_checked.gif" alt="'.get_lang('NotifyDiscussByEmail').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('NotifyDiscussChanges').'</font>';
	}
	else
	{	 
	   $notify_disc= '<img src="../img/wiki/send_mail.gif" alt="'.get_lang('CancelNotifyDiscussByEmail').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('NotNotifyDiscussChanges').'</font>';
	}	
	
    //mode assignment: previous to show  page type
	if(stripslashes($row['assignment'])==1)
	{
		$icon_assignment='<img src="../img/wiki/assignment.gif" alt="'.get_lang('AssignmentDescExtra').'" />';
	}
	elseif(stripslashes($row['assignment'])==2)
	{
		$icon_assignment='<img src="../img/wiki/works.gif" alt="'.get_lang('AssignmentWorkExtra').'" />';
	}	
	
	
	//Show title and form to discuss if page exist
	if ($id!='')
	{		
		//Show discussion to students if isn't hidden. Show page to all teachers if is hidden. Mode assignments: If is hidden, show pages to student only if student is the author
		if($row['visibility_disc']==1 || api_is_allowed_to_edit() || api_is_platform_admin() || ($row['assignment']==2 && $row['visibility_disc']==0 && (api_get_user_id()==$row['user_id'])))
	    {													
		    echo '<div id="wikititle">';
			echo $icon_assignment.'&nbsp;&nbsp;&nbsp;'.$row['title'].'<br/>'.'<a href="index.php?action=discuss&amp;actionpage=addlock_disc&amp;title='.$page.'">'.$addlock_disc.'</a>'.'&nbsp;&nbsp;&nbsp;<a href="index.php?action=discuss&amp;actionpage=visibility_disc&amp;title='.$page.'">'.$visibility_disc.'</a>'.'&nbsp;&nbsp;&nbsp;<a href="index.php?action=discuss&amp;actionpage=ratinglock_disc&amp;title='.$page.'">'.$ratinglock_disc.'</a>&nbsp;&nbsp;&nbsp;<a href="index.php?action=discuss&amp;actionpage=notify_disc&amp;title='.$page.'">'.$notify_disc.'</a>&nbsp;&nbsp;&nbsp;<font size="-2"><i> ('.get_lang('MostRecentVersionBy').'<a href="../user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.$userinfo['firstname'].' '.$userinfo['lastname'].'</a> '.$row['timestamp'].$countWPost.')'.$avg_WPost_score.' </i></font>'; //to do: read avg score
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
					<td> <?php  echo '<input type="submit" name="Submit" value="'.get_lang('Send').'">'; ?></td>
				  	</tr>
				</table>
				</form>
                
				<?php
				if ($_POST['Submit'])
				{
					$sql="INSERT INTO `$tbl_wiki_discuss` (publication_id, userc_id, comment, p_score) VALUES ('".$id."','".api_get_user_id()."','".$_POST['comment']."','".$_POST['rating']."')";
					$result=api_sql_query($sql,__FILE__,__LINE__) or die(mysql_error());					
				}
			}//end discuss lock
			
			echo '<hr noshade size="1">';
			$user_table = Database :: get_main_table(TABLE_MAIN_USER);
			
			$sql="SELECT * FROM `$tbl_wiki_discuss` reviews, $user_table user  WHERE reviews.publication_id='".$id."' AND user.user_id='".$wuid."' ORDER BY id DESC";
			$result=api_sql_query($sql,__FILE__,__LINE__) or die(mysql_error());
			
			$countWPost = Database::num_rows($result); 
			echo get_lang('NumComments').": ".$countWPost; //comment's numbers 
			
			$sql="SELECT SUM(p_score) as sumWPost FROM `$tbl_wiki_discuss` WHERE publication_id='".$id."' AND NOT p_score='-' ORDER BY id DESC"; 
			$result2=api_sql_query($sql,__FILE__,__LINE__) or die(mysql_error());
			$row2=Database::fetch_array($result2);
			
			$sql="SELECT * FROM `$tbl_wiki_discuss` WHERE publication_id='".$id."' AND NOT p_score='-'"; 
			$result3=api_sql_query($sql,__FILE__,__LINE__) or die(mysql_error());
			$countWPost_score= Database::num_rows($result3);
			
			echo ' - '.get_lang('NumCommentsScore').': '.$countWPost_score;//
			
			$avg_WPost_score = round($row2['sumWPost'] / $countWPost_score,2).' / 10';
			
			echo ' - '.get_lang('RatingMedia').': '.$avg_WPost_score; // average rating
	
			$sql='UPDATE '.$tbl_wiki.' SET score="'.Database::escape_string($avg_WPost_score).'" WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter;	// check if work ok. to do			
				api_sql_query($sql,__FILE__,__LINE__); 
	
			echo '<hr noshade size="1">';
			echo '<div style="overflow:auto; height:170px;">';
			
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
			echo '<td style=" color:#999999"><a href="../user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.$userinfo['lastname'].', '.$userinfo['firstname'].'</a> ('.$author_status.') '.$row['timestamp'].' - '.get_lang('Rating').': '.$row['p_score'].' '.$imagerating.' </td>';
			echo '</tr>';
			echo '<tr>';
			echo '<td>'.$row['comment'].'</td>';
			echo '</tr>';			
			echo "</table>";		
			echo '<hr noshade size="1">';
			
			}	
		    echo"</div>";			
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
		echo $counter."-".$testvalue.$counter."<br>"; 
	
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

	$sql='SELECT * FROM '.$tbl_wiki.' WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($paramwk)))).'" AND '.$groupfilter.''; // to do: check if need entity
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
* @author Juan Carlos Raña <herodoto@telefonica.net>
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
		
		    if (strpos($value, "|") !== false)
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
			
			$all_links[]= Database::escape_string(str_replace(' ','',$link)).' ';	//remove blank spaces within the links. But to remove links at the end add a blank space				
		}

    }
	
	$output=implode($all_links);
	return $output;
	
}


/**
* This function allows users to have [link to a title]-style links like in most regular wikis.
* It is true that the adding of links is probably the most anoying part of Wiki for the people
* who know something about the wiki syntax.
* @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
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
			if (strpos($value, "|") !== false)
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
			if (checktitle(strtolower(str_replace(' ','',$link))))
			{			
				$input_array[$key]='<a href="'.api_get_path(WEB_PATH).'main/wiki/index.php?cidReq='.$_course[id].'&action=addnew&amp;title='.urldecode($link).'&group_id='.$_clean['group_id'].'" class="new_wiki_link">'.$title.$titleg_ex.'</a>';		
			}
			else 
			{				
						
				$input_array[$key]='<a href="'.api_get_path(WEB_PATH).'main/wiki/index.php?cidReq='.$_course[id].'&action=showpage&amp;title='.strtolower(str_replace(' ','',$link)).'&group_id='.$_clean['group_id'].'" class="wiki_link">'.$title.$titleg_ex.'</a>'; // juan esto recoge la posibilidad de que el titulo sea diferente a la url			
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
	
	// cleaning the variables

	$_clean['reflink']=Database::escape_string($_POST['reflink']);
	$_clean['title']=Database::escape_string($_POST['title']);
	$_clean['content']= html_entity_decode(Database::escape_string(stripslashes(urldecode($_POST['content']))));
	$_clean['user_id']=(int)Database::escape_string(api_get_user_id());
	$_clean['assignment']=Database::escape_string($_POST['assignment']);
    $_clean['comment']=Database::escape_string($_POST['comment']);	
    $_clean['progress']=Database::escape_string($_POST['progress']);
	$_clean['startdate_assig']=Database::escape_string($_POST['startdate_assig']);
    $_clean['enddate_assig']=Database::escape_string($_POST['enddate_assig']);
	$_clean['version']=Database::escape_string($_POST['version'])+1;	
	$_clean['linksto'] = links_to($_clean['content']); //and check links content
	


	if (isset($_SESSION['_gid']))
    {
	  	$_clean['group_id']=Database::escape_string($_SESSION['_gid']);
    }
    if (isset($_GET['group_id']))
    {
 	   	$_clean['group_id']=Database::escape_string($_GET['group_id']);
    }		

	if ($_clean['group_id'])
	{
		$sql="INSERT INTO ".$tbl_wiki." (reflink, title,content,user_id, group_id, assignment, comment, progress, startdate_assig, enddate_assig, version, linksto, user_ip) VALUES ('".$_clean['reflink']."','".$_clean['title']."','".$_clean['content']."','".$_clean['user_id']."','".$_clean['group_id']."','".$_clean['assignment']."','".$_clean['comment']."','".$_clean['progress']."','".$_clean['startdate_assig']."','".$_clean['enddate_assig']."','".$_clean['version']."','".$_clean['linksto']."','".$_SERVER['REMOTE_ADDR']."')";
	}
	else
	{
		$sql="INSERT INTO ".$tbl_wiki." (reflink, title,content,user_id, assignment, comment, progress, startdate_assig, enddate_assig, version, linksto, user_ip) VALUES ('".$_clean['reflink']."','".$_clean['title']."','".$_clean['content']."','".$_clean['user_id']."','".$_clean['assignment']."','".$_clean['comment']."','".$_clean['progress']."','".$_clean['startdate_assig']."','".$_clean['enddate_assig']."','".$_clean['version']."','".$_clean['linksto']."','".$_SERVER['REMOTE_ADDR']."')";
	}	
	$result=api_sql_query($sql);	
    $Id = Database::insert_id();		
	api_item_property_update($_course, 'wiki', $Id, 'WikiAdded', api_get_user_id());
	return get_lang('ChangesStored');
}

/**
* This function delete a wiki
* @author Juan Carlos Raña <herodoto@telefonica.net>
**/

function delete_wiki()
{
	global $tbl_wiki, $tbl_discuss, $groupfilter;
	api_sql_query('DELETE FROM '.$tbl_wiki.' WHERE '.$groupfilter.'',__FILE__,__LINE__);	
	//to do: delete discuss and mailcue
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
		$_clean['reflink']=Database::escape_string(str_replace(' ','',$_POST['title']."_uass".$assig_user_id));			
    }
	else
	{		
	 	$_clean['reflink']=Database::escape_string(str_replace(' ','',$_POST['title']));			
	}	

	$_clean['title']=Database::escape_string($_POST['title']);		    
	$_clean['content']= html_entity_decode(Database::escape_string(stripslashes(urldecode($_POST['content']))));
	
	if($_clean['assignment']==2) // for automatic assignment. Identifies the user as a creator, not the teacher who created
	{	 
	 	$_clean['user_id']=(int)Database::escape_string($assig_user_id);
	}
	else
	{
	 	$_clean['user_id']=(int)Database::escape_string(api_get_user_id());	
	}	
	
	$_clean['comment']=Database::escape_string($_POST['comment']);
	$_clean['progress']=Database::escape_string($_POST['progress']);	
	$_clean['startdate_assig']=Database::escape_string($_POST['startdate_assig']);
	$_clean['enddate_assig']=Database::escape_string($_POST['enddate_assig']);	
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
			if ($_clean['group_id']) 
		    {	 
				$sql="INSERT INTO ".$tbl_wiki." (reflink, title, content, user_id, group_id, assignment, comment, progress, startdate_assig, enddate_assig, version, linksto, user_ip) VALUES ('".$_clean['reflink']."','".$_clean['title']."','".$_clean['content']."','".$_clean['user_id']."','".$_clean['group_id']."','".$_clean['assignment']."','".$_clean['comment']."','".$_clean['progress']."','".$_clean['startdate_assig']."','".$_clean['enddate_assig']."','".$_clean['version']."','".$_clean['linksto']."','".$_SERVER['REMOTE_ADDR']."')";
		    }
		    else
		    {	    
				$sql="INSERT INTO ".$tbl_wiki." (reflink, title,content,user_id, assignment, comment, progress, startdate_assig, enddate_assig, version, linksto, user_ip) VALUES ('".$_clean['reflink']."','".$_clean['title']."','".$_clean['content']."','".$_clean['user_id']."','".$_clean['assignment']."','".$_clean['comment']."','".$_clean['progress']."','".$_clean['startdate_assig']."','".$_clean['enddate_assig']."','".$_clean['version']."','".$_clean['linksto']."','".$_SERVER['REMOTE_ADDR']."')";
		    }  
			   
		   $result=api_sql_query($sql,__LINE__,__FILE__);
		   $Id = Database::insert_id();	
		   api_item_property_update($_course, 'wiki', $Id, 'WikiAdded', api_get_user_id());
		  
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
		//by now only on wiki course
		$_clean['group_id']=(int)$_SESSION['_gid'];
		if($_clean['group_id']==0)
		{
			echo '&nbsp;&nbsp;&nbsp;<img src="../img/wiki/assignment.gif" />&nbsp;'.get_lang('DefineAssignmentPage').'&nbsp;<INPUT TYPE="checkbox" NAME="assignment" VALUE="1">'; // 1 teacher 2 student	
			//echo'<div style="border:groove">';//by now turned off				
			//echo '&nbsp;Start. Date and time: <INPUT TYPE="text" NAME="startdate_assig" VALUE="0000-00-00 00:00:00">(yyyy-mm-dd hh:mm:ss)'; //by now turned off
			//echo '&nbsp;End. Date and time: <INPUT TYPE="text" NAME="enddate_assig" VALUE="0000-00-00 00:00:00">(yyyy-mm-dd hh:mm:ss)'; //by now turned off				
			//echo'</div>';	 
		} 
	}
	echo '<br></div>';
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
	echo '<input type="submit" name="SaveWikiNew" value="'.get_lang('langSave').'" onClick="return Send(this.form)">'; 
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
	
    if (empty($page))
	{
		$page='index';	
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


	// if both are empty and we are displaying the index page then we display the default text.
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
	

	//Button lock add new pages
	if (check_addnewpagelock())
	{
		if(api_is_allowed_to_edit() || api_is_platform_admin()) 
		{
	 		$protect_addnewpage= '<img src="../img/wiki/lockadd.gif" alt="'.get_lang('AddOptionProtected').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('ShowAddOption').'</font>';
	    }
	}
	else
	{			  
		if(api_is_allowed_to_edit() || api_is_platform_admin()) 
		{
			$protect_addnewpage= '<img src="../img/wiki/unlockadd.gif" alt="'.get_lang('AddOptionUnprotected').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('HideAddOption').'</font>';
		}	     	
	}
	
	//Button lock page
	if (check_protect_page())
	{
	 	$protect_page= '<img src="../img/wiki/lock.gif" alt="'.get_lang('PageLockedExtra').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('PageUnlocked').'</font>';
	}
	else
	{					  
		if(api_is_allowed_to_edit() || api_is_platform_admin()) 
	   	{
	   		$protect_page= '<img src="../img/wiki/unlock.gif" alt="'.get_lang('PageUnlockedExtra').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('PageLocked').'</font>';
	   	} 	     	
	}	

	//Button visibility page
	if (check_visibility_page())
	{
		//This hides the icon eye closed to users of work they can see yours
		if(($row['assignment']==2 && $KeyVisibility=="0" && (api_get_user_id()==$row['user_id']))==false)
	  	{	  
	 		$visibility_page= '<img src="../img/wiki/invisible.gif" alt="'.get_lang('HidePageExtra').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('ShowPage').'</font>';
	    }
	}
	else
	{			  
		if(api_is_allowed_to_edit() || api_is_platform_admin()) 
		{
			$visibility_page= '<img src="../img/wiki/visible.gif" alt="'.get_lang('ShowPageExtra').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('HidePage').'</font>';
		}	     	
	}		
	
	//Button notify page
	if (check_notify_page())
	{
		$notify_page= '<img src="../img/wiki/send_mail_checked.gif" alt="'.get_lang('NotifyByEmail').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('NotNotifyChanges').'</font>';
	}
	else
	{	 
		$notify_page= '<img src="../img/wiki/send_mail.gif" alt="'.get_lang('CancelNotifyByEmail').'" /><font style="font-weight: normal; background-color:#FFCC00"">'.get_lang('NotifyChanges').'</font>';
	}	

	//assignment mode: for identify page type
	if(stripslashes($row['assignment'])==1)
	{
		$icon_assignment='<img src="../img/wiki/assignment.gif" alt="'.get_lang('AssignmentDescExtra').'" />';
	}
	elseif(stripslashes($row['assignment'])==2)
	{
		$icon_assignment='<img src="../img/wiki/works.gif" alt="'.get_lang('AssignmentWorkExtra').'" />';
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
			echo $icon_assignment.'&nbsp;&nbsp;&nbsp;'.stripslashes($title).'<a href="index.php?action=show&amp;actionpage=addlock&amp;title='.$page.'"><br/>'.$protect_addnewpage.'</a>'.'&nbsp;&nbsp;&nbsp;<a href="index.php?action=showpage&amp;actionpage=lock&amp;title='.$page.'">'.$protect_page.'</a>'.'&nbsp;&nbsp;&nbsp;<a href="index.php?action=showpage&amp;actionpage=visibility&amp;title='.$page.'">'.$visibility_page.'</a>'.'&nbsp;&nbsp;&nbsp;<a href="index.php?action=showpage&amp;actionpage=notify&amp;title='.$page.'">'.$notify_page.'</a>'.'&nbsp;&nbsp;&nbsp;'.get_lang('Progress').': '.stripslashes($row['progress']).'%&nbsp;&nbsp;&nbsp;'.get_lang('Rating').': '.stripslashes($row['score']);			
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
		echo '<input type="image" src="../img/wiki/wexport2pdf.gif" border ="0" alt="'.get_lang('ExportToPDF').'">';
		echo '</form>';
		echo '</span>';
		
		//copy last version to doc area
		echo '<span style="float:right;">';				
		echo '<form name="form_export2DOC" method="post" action="index.php">';
		echo '<input type=hidden name="export2DOC" value="export2doc">';
		echo '<input type=hidden name="titleDOC" value="'.htmlentities($title).'">';
		echo '<input type=hidden name="contentDOC" value="'.htmlentities($content).'">';
		echo '<input type="image" src="../img/wiki/wexport2doc.png" border ="0" alt="'.get_lang('ExportToDocArea').'">';
		echo '</form>';
		echo '</span>';	
			
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
		echo '<span style="float:right;">';
		echo '<img src="../img/wiki/wprint.gif" alt="'.get_lang('Print').'" onclick="goprint()">';
		echo '</span>';
		
		//export to zip			

			echo '<span style="float:right;"><img src="../img/wiki/wzip_save.gif" alt="'.get_lang('Export2ZIP').'" onclick="alert(\'This is not implemented yet but it will be in the near future\')"/></span>'; //to do.	
			
			echo '</div>';	
			echo '<div id="wikicontent">'.make_wiki_link_clickable(stripslashes($content)).'</div>';		
			
	}//end filter visibility
} // end function display_wiki_entry

//more for export to course document area. See display_wiki_entry
if ($_POST['export2DOC'])
{ 
	$titleDOC=$_POST['titleDOC'];
	$contentDOC=$_POST['contentDOC']; //check. to do.
	$groupIdDOC=$_clean['group_id'];
	export2doc($titleDOC,$contentDOC,$groupIdDOC); 
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
 * @author Juan Carlos Raña <herodoto@telefonica.net>
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
 * @author Juan Carlos Raña <herodoto@telefonica.net>
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
 * @author Juan Carlos Raña <herodoto@telefonica.net>
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
	$id=$row['id'];	//need ? check. to do			
		
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
 * @author Juan Carlos Raña <herodoto@telefonica.net>
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
	$id=$row['id'];	//need ? check. to do	
		
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
 * @author Juan Carlos Raña <herodoto@telefonica.net>
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
	$id=$row['id'];		//need ? check. to do	
		
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
 * @author Juan Carlos Raña <herodoto@telefonica.net>
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
	$id=$row['id'];	//need ? check. to do	
		
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
		
		$sql='UPDATE '.$tbl_wiki.' SET ratinglock_disc="'.Database::escape_string($status_ratinglock_disc).'" WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter; //juan carlos da valor de visible o no a todos los registros de la pagina, no solo al primero como antes		
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
 * @author Juan Carlos Raña <herodoto@telefonica.net>
 */
function check_notify_page()
{
	global $tbl_wiki;
	global $page;
	global $groupfilter;	
	global $tbl_wiki_mailcue;

	$_clean['group_id']=(int)$_SESSION['_gid'];
	
	$sql='SELECT * FROM '.$tbl_wiki.'WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.' ORDER BY id ASC';
	$result=api_sql_query($sql,__LINE__,__FILE__);
	$row=Database::fetch_array($result);
				
	$status_notify=$row['notify'];
	$id=$row['id'];
								
	//change status
	if ($_GET['actionpage']=='notify') 
	{	
		if ($row['notify']==0)				
	    {
	    	$status_notify=1;				 
	    }
	    else
		{
		    $status_notify=0;	
	    }    
	
	    $sql='UPDATE '.$tbl_wiki.' SET notify="'.Database::escape_string($status_notify).'" WHERE id="'.$id.'"';			   
	    api_sql_query($sql,__FILE__,__LINE__); 	
  
    	$sql='SELECT * FROM '.$tbl_wiki.'WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.' ORDER BY id ASC';
		$result=api_sql_query($sql,__LINE__,__FILE__);
		$row=Database::fetch_array($result); 
	  
		$sql="INSERT INTO ".$tbl_wiki_mailcue." (id, user_id) VALUES ('".$id."','".api_get_user_id()."')"; 
		$result=api_sql_query($sql);		
	}	
	
	//show status
	if ($row['notify']==0 || ($row['content']=='' AND $row['title']=='' AND $page='index'))
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
 * @author Juan Carlos Raña <herodoto@telefonica.net>
 */
function check_notify_discuss()
{
	global $tbl_wiki;
	global $page;
	global $groupfilter;	
	global $tbl_wiki_mailcue;

	$_clean['group_id']=(int)$_SESSION['_gid'];
	
	$sql='SELECT * FROM '.$tbl_wiki.'WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.' ORDER BY id ASC';
	$result=api_sql_query($sql,__LINE__,__FILE__);
	$row=Database::fetch_array($result);
				
	$status_notify_disc=$row['notify_disc'];
	$id=$row['id'];	//need ? check. to do	
								
	///change status
	if ($_GET['actionpage']=='notify_disc') 
	{	
		if ($row['notify_disc']=="0")				
	    {
			$status_notify_disc="1";				 
	    }
	    else
		{
		    $status_notify_disc="0";	
	    }          
    	
	    $sql='UPDATE '.$tbl_wiki.' SET notify_disc="'.Database::escape_string($status_notify_disc).'" WHERE id="'.$id.'"';			   
	    api_sql_query($sql,__FILE__,__LINE__); 		
  
		$sql='SELECT * FROM '.$tbl_wiki.'WHERE reflink="'.html_entity_decode(Database::escape_string(stripslashes(urldecode($page)))).'" AND '.$groupfilter.' ORDER BY id ASC';
		$result=api_sql_query($sql,__LINE__,__FILE__);
		$row=Database::fetch_array($result); 
	  
		$sql="INSERT INTO ".$tbl_wiki_mailcue." (id, user_id) VALUES ('".$id."','".api_get_user_id()."')"; 
		$result=api_sql_query($sql);		
	}	
	
	//show status
	if ($row['notify_disc']=="0" || ($row['content']=='' AND $row['title']=='' AND $page='index'))
	{
		return false;
	}
	else
	{
		return true;				
	}
}

/**
 * Function check emailcue
 * TO DO
 */
 
 
/**
 * Function send email when a page change
 * TO DO
 */
 


/**
 * Function export last wiki page version to document area
 * @author Juan Carlos Raña <herodoto@telefonica.net>
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
			   
	require_once (api_get_path(LIBRARY_PATH).'fileUpload.lib.php');  
	require_once (api_get_path(LIBRARY_PATH).'document.lib.php');
	$exportDir = api_get_path(SYS_COURSE_PATH).api_get_course_path(). '/document'.$groupPath;
	$exportFile = replace_dangerous_char( $wikiTitle, 'strict' ) . $groupPart;
	
	$i = 1;
	while ( file_exists($exportDir . '/' .$exportFile.'_'.$i.'.html') ) $i++; //only export last version, but in new export new version in document area
	$wikiFileName = $exportFile . '_' . $i . '.html';
	$exportPath = $exportDir . '/' . $wikiFileName;
	$wikiContents = stripslashes($wikiContents);
	file_put_contents( $exportPath, $wikiContents );		 
	$doc_id = add_document($_course, $groupPath.'/'.$wikiFileName,'file',filesize($exportPath),$wikiFileName);
	api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentAdded', api_get_user_id(), $groupId);					              
    // to do: link to go document area
}


/**
 * Function wizard individual assignment
 * @author Juan Carlos Raña <herodoto@telefonica.net>
 */
function auto_add_page_users($assignment_type)
{
	global $assig_user_id; //need to identify end reflinks	

    //extract course members
	if(!empty($_SESSION["id_session"])){
		$a_course_users = CourseManager :: get_user_list_from_course_code($_SESSION['_course']['id'], true, $_SESSION['id_session']);
	}
	else
	{
		$a_course_users = CourseManager :: get_user_list_from_course_code($_SESSION['_course']['id'], true);
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
	
	//teacher assignement title
	$title_orig=$_POST['title'];
	
	//teacher assignement reflink
	$link2teacher=$_POST['title']= $title_orig."_uass".api_get_user_id();
	
	//first: teacher name, photo, and assignment description (original content)	
    $content_orig_A='<div align="center" style="font-size:24px; background-color: #F5F8FB;  border:double">'.$photo.get_lang('Teacher').': '.$userinfo['firstname'].$userinfo['lastname'].'</div><br/><div>';
	$content_orig_B='<h1>'.get_lang('AssignmentDescription').'</h1></div><br/>'.$_POST['content'];
    //Second: student list (names, photo and links to their works).
	//Third: Create Students work pages.
   	foreach($a_course_users as $user_id=>$o_course_user)
	{					  
		if($o_course_user['user_id'] != api_get_user_id()) //except that puts the task
		{
									 		 
			$assig_user_id= $o_course_user['user_id']; //identifies each page as created by the student, not by teacher		
			$image_path = UserManager::get_user_picture_path_by_id($assig_user_id,'web',false, true);
			$image_repository = $image_path['dir'];
			$existing_image = $image_path['file'];
			$name= $o_course_user['lastname'].', '.$o_course_user['firstname'];
			$photo= '<img src="'.$image_repository.$existing_image.'" alt="'.$name.'"  width="40" height="50" align="bottom" title="'.$name.'"  />';			
			
			if($assignment_type==1)
			{			 
				$_POST['title']= $title_orig;
				$_POST['comment']=get_lang('AssignmentFirstComToStudent');				
				$_POST['content']='<div align="center" style="font-size:24px; background-color: #F5F8FB;  border:double">'.$photo.get_lang('Student').': '.$name.'</div>[['.$link2teacher.' | '.get_lang('AssignmentLinktoTeacherPage').']] '.$content_orig_B;	
			   //AssignmentLinktoTeacherPage	        
			 	$all_students_pages[] = '<li>'.$o_course_user['lastname'].', '.$o_course_user['firstname'].' [['.$_POST['title']."_uass".$assig_user_id.' | '.$photo.']] </li>';
				
				$_POST['assignment']=2;
				
			}			
			save_new_wiki();	
		}	
        		
	}//end foreach for each user
	
	
	foreach($a_course_users as $user_id=>$o_course_user)
	{
			
		if($o_course_user['user_id'] == api_get_user_id())
		{		
			$assig_user_id=$o_course_user['user_id'];			
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