<?php
/* For licensing terms, see /license.txt */
/**
 *	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * 	@author Juan Carlos Raña <herodoto@telefonica.net>
 *
 * 	@package chamilo.wiki
 */
/**
 * Code
 */
// name of the language file that needs to be included
$language_file = 'wiki';

// including the global initialization file
require_once '../inc/global.inc.php';

// section (for the tabs)
$this_section = SECTION_COURSES;

// including additional library scripts

require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'mail.lib.inc.php';

require_once 'wiki.inc.php';

$course_id = api_get_course_int_id();

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
function setFocus(){
    $("#search_title").focus();
    }
    $(document).ready(function () {
      setFocus();
    });

</script>';


// Database table definition
$tbl_wiki           = Database::get_course_table(TABLE_WIKI);
$tbl_wiki_discuss   = Database::get_course_table(TABLE_WIKI_DISCUSS);
$tbl_wiki_mailcue   = Database::get_course_table(TABLE_WIKI_MAILCUE);
$tbl_wiki_conf      = Database::get_course_table(TABLE_WIKI_CONF);
/*
Constants and variables
*/
$tool_name = get_lang('ToolWiki');

$MonthsLong = array (get_lang("JanuaryLong"), get_lang("FebruaryLong"), get_lang("MarchLong"), get_lang("AprilLong"), get_lang("MayLong"), get_lang("JuneLong"), get_lang("JulyLong"), get_lang("AugustLong"), get_lang("SeptemberLong"), get_lang("OctoberLong"), get_lang("NovemberLong"), get_lang("DecemberLong"));

//condition for the session
$session_id = api_get_session_id();
$condition_session = api_get_session_condition($session_id);
$course_id = api_get_course_int_id();

/*
ACCESS
*/
api_protect_course_script();
api_block_anonymous_users();

/*
TRACKING
*/
event_access_tool(TOOL_WIKI);

/*
HEADER & TITLE
*/
// If it is a group wiki then the breadcrumbs will be different.

//Setting variable
$_clean['group_id'] = 0;

if ($_SESSION['_gid'] OR $_GET['group_id']) {

    if (isset($_SESSION['_gid'])) {
        $_clean['group_id']=intval($_SESSION['_gid']);
    }
    if (isset($_GET['group_id'])) {
        $_clean['group_id']=intval($_GET['group_id']);
    }

    $group_properties  = GroupManager :: get_group_properties($_clean['group_id']);
    $interbreadcrumb[] = array ("url" => "../group/group.php", "name" => get_lang('Groups'));
    $interbreadcrumb[] = array ("url"=>"../group/group_space.php?gidReq=".$_clean['group_id'], "name"=> get_lang('GroupSpace').' '.$group_properties['name']);

    $add_group_to_title = ' '.$group_properties['name'];
    $groupfilter='group_id="'.$_clean['group_id'].'"';

    //ensure this tool in groups whe it's private or deactivated
    if 	($group_properties['wiki_state']==0) {
        api_not_allowed();
    } elseif ($group_properties['wiki_state']==2) {
         if (!api_is_allowed_to_edit(false,true) and !GroupManager :: is_user_in_group($_user['user_id'], $_SESSION['_gid'])) {
            api_not_allowed();
        }
    }
} else {
    $groupfilter='group_id=0';
}


if ($_POST['action']=='export_to_pdf' && isset($_POST['wiki_id']) && api_get_setting('students_export2pdf') == 'true') {	
    export_to_pdf($_POST['wiki_id'], api_get_course_id());
    exit;
}


Display::display_header($tool_name, 'Wiki');

$is_allowed_to_edit = api_is_allowed_to_edit(false,true);

//api_display_tool_title($tool_name.$add_group_to_title);

/*
INITIALISATION
*/
//the page we are dealing with
if (!isset($_GET['title'])) {
    $page = 'index';
} else {
    $page = $_GET['title'];
}

// some titles are not allowed
// $not_allowed_titles=array("Index", "RecentChanges","AllPages", "Categories"); //not used for now

/*
MAIN CODE
*/

// Tool introduction
Display::display_introduction_section(TOOL_WIKI);

/*
              ACTIONS
*/


//release of blocked pages to prevent concurrent editions
$sql = "SELECT * FROM $tbl_wiki WHERE c_id = $course_id AND is_editing != '0' ".$condition_session;
$result=Database::query($sql);
while ($is_editing_block=Database::fetch_array($result)) {
    $max_edit_time	= 1200; // 20 minutes
    $timestamp_edit	= strtotime($is_editing_block['time_edit']);
    $time_editing	= time()-$timestamp_edit;

    //first prevent concurrent users and double version
    if ($is_editing_block['is_editing']==$_user['user_id']) {
        $_SESSION['_version']=$is_editing_block['version'];
    } else {
        unset ( $_SESSION['_version'] );
    }
    //second checks if has exceeded the time that a page may be available or if a page was edited and saved by its author
    if ($time_editing>$max_edit_time || ($is_editing_block['is_editing']==$_user['user_id'] && $_GET['action']!='edit')) {
        $sql='UPDATE '.$tbl_wiki.' SET is_editing="0", time_edit="0000-00-00 00:00:00" 
              WHERE c_id = '.$course_id.' AND is_editing="'.$is_editing_block['is_editing'].'" '.$condition_session;
        Database::query($sql);
    }
}

// saving a change
if (isset($_POST['SaveWikiChange']) AND $_POST['title']<>'') {
    if(empty($_POST['title'])) {
        Display::display_error_message(get_lang("NoWikiPageTitle"));
    } elseif(!double_post($_POST['wpost_id'])) {
        //double post
    } elseif ($_POST['version']!='' && $_SESSION['_version']!=0 && $_POST['version']!=$_SESSION['_version']) {
        //prevent concurrent users and double version
        Display::display_error_message(get_lang("EditedByAnotherUser"));
    } else {
        $return_message=save_wiki();
        Display::display_confirmation_message($return_message, false);
    }
}

//saving a new wiki entry
if (isset($_POST['SaveWikiNew'])) {
    if (empty($_POST['title'])) {
        Display::display_error_message(get_lang("NoWikiPageTitle"));
    } elseif (strtotime(get_date_from_select('startdate_assig')) > strtotime(get_date_from_select('enddate_assig'))) {
        Display::display_error_message(get_lang("EndDateCannotBeBeforeTheStartDate"));
    } elseif(!double_post($_POST['wpost_id'])) {
        //double post
    } else {
       $_clean['assignment']=Database::escape_string($_POST['assignment']); // for mode assignment
       if ($_clean['assignment']==1) {
              auto_add_page_users($_clean['assignment']);
       } else {
            $return_message=save_new_wiki();
            if ($return_message==false) {
                Display::display_error_message(get_lang('NoWikiPageTitle'), false);
            } else {
                Display::display_confirmation_message($return_message, false);
            }
       }
    }
}


// check last version
if ($_GET['view']) {
    $sql='SELECT * FROM '.$tbl_wiki.' WHERE c_id = '.$course_id.' AND id="'.Database::escape_string($_GET['view']).'"'; //current view
    $result=Database::query($sql);
    $current_row=Database::fetch_array($result);

    $sql='SELECT * FROM '.$tbl_wiki.' WHERE c_id = '.$course_id.' AND reflink="'.Database::escape_string($page).'" AND '.$groupfilter.$condition_session.' ORDER BY id DESC'; //last version
    $result=Database::query($sql);
    $last_row=Database::fetch_array($result);

    if ($_GET['view']<$last_row['id']) {
       $message= '<center>'.get_lang('NoAreSeeingTheLastVersion').'<br /> '.get_lang("Version").' (<a href="index.php?cidReq='.$_course['id'].'&action=showpage&amp;title='.api_htmlentities(urlencode($current_row['reflink'])).'&group_id='.$current_row['group_id'].'&session_id='.$current_row['session_id'].'&view='.api_htmlentities($_GET['view']).'" title="'.get_lang('CurrentVersion').'">'.$current_row['version'].'</a> / <a href="index.php?cidReq='.$_course['id'].'&action=showpage&amp;title='.api_htmlentities(urlencode($last_row['reflink'])).'&group_id='.$last_row['group_id'].'&session_id='.$last_row['session_id'].'" title="'.get_lang('LastVersion').'">'.$last_row['version'].'</a>) <br />'.get_lang("ConvertToLastVersion").': <a href="index.php?cidReq='.$_course['id'].'&action=restorepage&amp;title='.api_htmlentities(urlencode($last_row['reflink'])).'&group_id='.$last_row['group_id'].'&session_id='.$last_row['session_id'].'&view='.api_htmlentities($_GET['view']).'">'.get_lang("Restore").'</a></center>';
       Display::display_warning_message($message,false);
    }

    ///restore page
    if ($_GET['action']=='restorepage') {
        //Only teachers and platform admin can edit the index page. Only teachers and platform admin can edit an assignment teacher
        if (($current_row['reflink']=='index' || $current_row['reflink']=='' || $current_row['assignment']==1) && (!api_is_allowed_to_edit(false,true) && intval($_GET['group_id'])==0)) {
            Display::display_normal_message(get_lang('OnlyEditPagesCourseManager'));
        } else {
            $PassEdit=false;

            //check if is a wiki group
            if ($current_row['group_id']!=0) {
				//Only teacher, platform admin and group members can edit a wiki group
				if (api_is_allowed_to_edit(false,true) || api_is_platform_admin() || GroupManager :: is_user_in_group($_user['user_id'],intval($_GET['group_id']))) {
                    $PassEdit=true;
                } else {
                    Display::display_normal_message(get_lang('OnlyEditPagesGroupMembers'));
                }
            } else {
                $PassEdit=true;
            }

            // check if is an assignment
            if ($current_row['assignment']==1) {
                Display::display_normal_message(get_lang('EditAssignmentWarning'));
                $icon_assignment=Display::return_icon('wiki_assignment.png', get_lang('AssignmentDescExtra'),'',22);
            } elseif($current_row['assignment']==2) {
                $icon_assignment=Display::return_icon('wiki_work.png', get_lang('AssignmentWorkExtra'),'',22);
                if ((api_get_user_id()==$current_row['user_id'])==false) {
                    if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
                        $PassEdit=true;
                    } else {
                        Display::display_warning_message(get_lang('LockByTeacher'));
                        $PassEdit=false;
                    }
                } else {
                    $PassEdit=true;
                }
            }

            if ($PassEdit) { //show editor if edit is allowed
                if ($row['editlock']==1 && (api_is_allowed_to_edit(false,true)==false || api_is_platform_admin()==false)) {
                       Display::display_normal_message(get_lang('PageLockedExtra'));
                } else {
                    if ($last_row['is_editing']!=0 && $last_row['is_editing']!=$_user['user_id']) {
                        //checking for concurrent users
                        $timestamp_edit=strtotime($last_row['time_edit']);
                        $time_editing=time()-$timestamp_edit;
                        $max_edit_time=1200; // 20 minutes
                        $rest_time=$max_edit_time-$time_editing;

                        $userinfo=Database::get_user_info_from_id($last_row['is_editing']);

                        $is_being_edited= get_lang('ThisPageisBeginEditedBy').' <a href=../user/userInfo.php?uInfo='.$userinfo['user_id'].'>'.api_get_person_name($userinfo['firstname'], $userinfo['lastname']).'</a>. '.get_lang('ThisPageisBeginEditedTryLater').' '.date( "i",$rest_time).' '.get_lang('MinMinutes').'';
                        Display::display_normal_message($is_being_edited, false);

                    } else {
                         Display::display_confirmation_message(restore_wikipage($current_row['page_id'], $current_row['reflink'], $current_row['title'], $current_row['content'], $current_row['group_id'], $current_row['assignment'], $current_row['progress'], $current_row['version'], $last_row['version'], $current_row['linksto']).': <a href="index.php?cidReq='.$_course['id'].'&action=showpage&amp;title='.api_htmlentities(urlencode($last_row['reflink'])).'&session_id='.$last_row['session_id'].'&group_id='.$last_row['group_id'].'">'.api_htmlentities($last_row['title']).'</a>',false);
                    }
                }
            }
        }
    }
}


if ($_GET['action']=='deletewiki') {
    if(api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
        if ($_GET['delete'] == 'yes') {
            $return_message=delete_wiki();
            Display::display_confirmation_message($return_message);
        }
    }
}


if ($_GET['action']=='discuss' && $_POST['Submit']) {
    Display::display_confirmation_message(get_lang('CommentAdded'));
}



/* WIKI WRAPPER */

echo '<div id="wikiwrapper">';

/** Actions bar (= action of the wiki tool, not of the page)**/

//dynamic wiki menu
?>
<script type="text/javascript">
function menu_wiki(){
 if(document.getElementById("menuwiki").style.width=="170px"){
	var w=74;
	var b=2;
	var h=30;
 }
 else{
	 var w=170;
	 var b=1;
	 var h=220;
 }

document.getElementById("menuwiki").style.width=w+"px";
document.getElementById("menuwiki").style.height=h+"px";
document.getElementById("menuwiki").style.border=b+"px solid #cccccc";

}
</script>

<?php

echo '<div id="menuwiki">';

echo '&nbsp;<a href="index.php?cidReq='.$_course['id'].'&action=show&amp;title=index&session_id='.$session_id.'&group_id='.$_clean['group_id'].'"'.is_active_navigation_tab('show').'>'.Display::return_icon('wiki.png',get_lang('HomeWiki'),'','32').'</a>&nbsp;';

echo '&nbsp;<a href="javascript:void(0)" onClick="menu_wiki()">'.Display::return_icon('menu.png',get_lang('Menu'),'','22').'</a>';
///menu home
echo '<ul>';
if ( api_is_allowed_to_session_edit(false,true) ) {
    //menu add page
    echo '<li><a href="index.php?cidReq='.$_course['id'].'&action=addnew&session_id='.$session_id.'&group_id='.$_clean['group_id'].'"'.is_active_navigation_tab('addnew').'>'.get_lang('AddNew').'</a> ';
}

if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
    // page action: enable or disable the adding of new pages
    if (check_addnewpagelock()==0) {
        $protect_addnewpage= '<img src="../img/off.png" title="'.get_lang('AddOptionProtected').'" alt="'.get_lang('AddOptionProtected').'" width="8" height="8" />';
        $lock_unlock_addnew='unlockaddnew';
    } else {
        $protect_addnewpage= '<img src="../img/on.png" title="'.get_lang('AddOptionUnprotected').'" alt="'.get_lang('AddOptionUnprotected').'" width="8" height="8" />';
        $lock_unlock_addnew='lockaddnew';
    }
}

echo '<a href="index.php?action=show&amp;actionpage='.$lock_unlock_addnew.'&amp;title='.api_htmlentities(urlencode($page)).'">'.$protect_addnewpage.'</a></li>';

///menu find
echo '<li><a href="index.php?cidReq='.$_course['id'].'&action=searchpages&session_id='.$session_id.'&group_id='.$_clean['group_id'].'"'.is_active_navigation_tab('searchpages').'>'.get_lang('SearchPages').'</a></li>';
///menu all pages
echo '<li><a href="index.php?cidReq='.$_course['id'].'&action=allpages&session_id='.$session_id.'&group_id='.$_clean['group_id'].'"'.is_active_navigation_tab('allpages').'>'.get_lang('AllPages').'</a></li>';
///menu recent changes
echo '<li><a href="index.php?cidReq='.$_course['id'].'&action=recentchanges&session_id='.$session_id.'&group_id='.$_clean['group_id'].'"'.is_active_navigation_tab('recentchanges').'>'.get_lang('RecentChanges').'</a></li>';
///menu delete all wiki
if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
        echo '<li><a href="index.php?action=deletewiki&amp;title='.api_htmlentities(urlencode($page)).'"'.is_active_navigation_tab('deletewiki').'>'.get_lang('DeleteWiki').'</a></li>';
}
///menu more
echo '<li><a href="index.php?action=more&amp;title='.api_htmlentities(urlencode($page)).'"'.is_active_navigation_tab('more').'>'.get_lang('More').'</a></li>';
echo '</ul>';
echo '</div>';

/*
  MAIN WIKI AREA
*/

echo '<div id="mainwiki">';
/** menuwiki (= actions of the page, not of the wiki tool) **/
if (!in_array($_GET['action'], array('addnew', 'searchpages', 'allpages', 'recentchanges', 'deletewiki', 'more', 'mactiveusers', 'mvisited', 'mostchanged', 'orphaned', 'wanted'))) {
    echo '<div class="actions">';

    //menu show page
    echo '&nbsp;&nbsp;<a href="index.php?cidReq='.$_course['id'].'&action=showpage&amp;title='.api_htmlentities(urlencode($page)).'&session_id='.$session_id.'&group_id='.$_clean['group_id'].'"'.is_active_navigation_tab('showpage').'>'.Display::return_icon('page.png',get_lang('ShowThisPage'),'','32').'</a>';

    if (api_is_allowed_to_session_edit(false,true) ) {
        //menu edit page
        echo '<a href="index.php?cidReq='.$_course['id'].'&action=edit&amp;title='.api_htmlentities(urlencode($page)).'&session_id='.$session_id.'&group_id='.$_clean['group_id'].'"'.is_active_navigation_tab('edit').'>'.Display::return_icon('edit.png',get_lang('EditThisPage'),'','32').'</a>';

        //menu discuss page
        echo '<a href="index.php?action=discuss&amp;title='.api_htmlentities(urlencode($page)).'"'.is_active_navigation_tab('discuss').'>'.Display::return_icon('discuss.png',get_lang('DiscussThisPage'),'','32').'</a>';
     }

    //menu history
    echo '<a href="index.php?cidReq='.$_course['id'].'&action=history&amp;title='.api_htmlentities(urlencode($page)).'&session_id='.$session_id.'&group_id='.$_clean['group_id'].'"'.is_active_navigation_tab('history').'>'.Display::return_icon('history.png',get_lang('ShowPageHistory'),'','32').'</a>';
    //menu linkspages
    echo '<a href="index.php?action=links&amp;title='.api_htmlentities(urlencode($page)).'&session_id='.$session_id.'&group_id='.$_clean['group_id'].'"'.is_active_navigation_tab('links').'>'.Display::return_icon('what_link_here.png',get_lang('LinksPages'),'','32').'</a>';

    //menu delete wikipage
    if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
        echo '<a href="index.php?action=delete&amp;title='.api_htmlentities(urlencode($page)).'"'.is_active_navigation_tab('delete').'>'.Display::return_icon('delete.png',get_lang('DeleteThisPage'),'','32').'</a>';
    }
    echo '</div>';
}


//In new pages go to new page
if (isset($_POST['SaveWikiNew'])) {
    display_wiki_entry($_POST['reflink']);
}

//More for export to course document area. See display_wiki_entry
if ($_POST['export2DOC']) {
    $doc_id = $_POST['doc_id'];
    $export2doc = export2doc($doc_id);
    if ($export2doc) {
        Display::display_confirmation_message(get_lang('ThePageHasBeenExportedToDocArea'));
    }

}

if ($_GET['action']=='more') {

    echo '<div class="actions">'.get_lang('More').'</div>';

    echo '<table border="0">';
    echo '  <tr>';
    echo '    <td>';
    echo '      <ul>';
    //Submenu Most active users
    echo '        <li><a href="index.php?cidReq='.$_course['id'].'&action=mactiveusers&session_id='.$session_id.'&group_id='.$_clean['group_id'].'">'.get_lang('MostActiveUsers').'</a></li>';
    //Submenu Most visited pages
    echo '        <li><a href="index.php?cidReq='.$_course['id'].'&action=mvisited&session_id='.$session_id.'&group_id='.$_clean['group_id'].'">'.get_lang('MostVisitedPages').'</a></li>';
    //Submenu Most changed pages
    echo '        <li><a href="index.php?cidReq='.$_course['id'].'&action=mostchanged&session_id='.$session_id.'&group_id='.$_clean['group_id'].'">'.get_lang('MostChangedPages').'</a></li>';
    echo '      </ul>';
    echo '    </td>';
    echo '    <td>';
    echo '      <ul>';
   //Submenu Orphaned pages
    echo '        <li><a href="index.php?cidReq='.$_course['id'].'&action=orphaned&session_id='.$session_id.'&group_id='.$_clean['group_id'].'">'.get_lang('OrphanedPages').'</a></li>';
    //Submenu Wanted pages
    echo '        <li><a href="index.php?cidReq='.$_course['id'].'&action=wanted&session_id='.$session_id.'&group_id='.$_clean['group_id'].'">'.get_lang('WantedPages').'</a></li>';
	//Submenu Most linked pages
    echo '<li><a href="index.php?cidReq='.$_course['id'].'&action=mostlinked&session_id='.$session_id.'&group_id='.$_clean['group_id'].'">'.get_lang('MostLinkedPages').'</a></li>';
    echo '</ul>';
	echo '</td>';
	echo '<td style="vertical-align:top">';
    echo '<ul>';
	//Submenu Statistics
	if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
    	echo '<li><a href="index.php?cidReq='.$_course['id'].'&action=statistics&session_id='.$session_id.'&group_id='.$_clean['group_id'].'">'.get_lang('Statistics').'</a></li>';
	}
    echo '      </ul>';
    echo'    </td>';
    echo '  </tr>';
    echo '</table>';

    //Submenu Dead end pages
    //echo '<li><a href="index.php?cidReq='.$_course['id'].'&action=deadend&session_id='.$session_id.'&group_id='.$_clean['group_id'].'">'.get_lang('DeadEndPages').'</a></li>';//TODO:

    //Submenu Most new pages (not versions)
    //echo '<li><a href="index.php?cidReq='.$_course['id'].'&action=mnew&session_id='.$session_id.'&group_id='.$_clean['group_id'].'">'.get_lang('MostNewPages').'</a></li>';//TODO:

    //Submenu Most long pages
    //echo '<li><a href="index.php?cidReq='.$_course['id'].'&action=mnew&session_id='.$session_id.'&group_id='.$_clean['group_id'].'">'.get_lang('MostLongPages').'</a></li>';//TODO:

    //Submenu Protected pages
    //echo '<li><a href="index.php?cidReq='.$_course['id'].'&action=protected&session_id='.$session_id.'&group_id='.$_clean['group_id'].'">'.get_lang('ProtectedPages').'</a></li>';//TODO:

    //Submenu Hidden pages
    //echo '<li><a href="index.php?cidReq='.$_course['id'].'&action=hidden&session_id='.$session_id.'&group_id='.$_clean['group_id'].'">'.get_lang('HiddenPages').'</a></li>';//TODO:

    //Submenu Most discuss pages
    //echo '<li><a href="index.php?cidReq='.$_course['id'].'&action=mdiscuss&session_id='.$session_id.'&group_id='.$_clean['group_id'].'">'.get_lang('MostDiscussPages').'</a></li>';//TODO:

    //Submenu Best scored pages
    //echo '<li><a href="index.php?cidReq='.$_course['id'].'&action=mscored&session_id='.$session_id.'&group_id='.$_clean['group_id'].'">'.get_lang('BestScoredPages').'</a></li>';//TODO:

    //Submenu Pages with more progress
    //echo '<li><a href="index.php?cidReq='.$_course['id'].'&action=mprogress&session_id='.$session_id.'&group_id='.$_clean['group_id'].'">'.get_lang('MProgressPages').'</a></li>';//TODO:

    //Submenu Most active users in discuss
    //echo '<li><a href="index.php?cidReq='.$_course['id'].'&action=mactiveusers&session_id='.$session_id.'&group_id='.$_clean['group_id'].'">'.get_lang('MostDiscussUsers').'</a></li>';//TODO:

    //Submenu Random page
    //echo '<li><a href="index.php?cidReq='.$_course['id'].'&action=mrandom&session_id='.$session_id.'&group_id='.$_clean['group_id'].'">'.get_lang('RandomPage').'</a></li>';//TODO:
	
	//Submenu Task
	//echo '<li><a href="index.php?cidReq='.$_course['id'].'&action=datetasks&session_id='.$session_id.'&group_id='.$_clean['group_id'].'">'.get_lang('Task').'</a></li>';//TODO:task list order by start date or end date

	//Submenu Who and Where
	//echo '<li><a href="index.php?cidReq='.$_course['id'].'&action=whoandwhere&session_id='.$session_id.'&group_id='.$_clean['group_id'].'">'.get_lang('WhoAndWhere').'</a></li>';//TODO:Who and where everyone is working now?
}

// Statistics Juan Carlos Raña Trabado

if ($_GET['action']=='statistics' && (api_is_allowed_to_edit(false,true) || api_is_platform_admin())) {
	echo '<div class="actions">'.get_lang('Statistics').'</div>';
	
	
	//check all versions of all pages
	
	$total_words 			= 0;
	$total_links 			= 0;
	$total_links_anchors 	= 0;
	$total_links_mail		= 0;
	$total_links_ftp 		= 0;
	$total_links_irc		= 0;
	$total_links_news 		= 0;
	$total_wlinks 			= 0;
	$total_images 			= 0;
	$clean_total_flash 		= 0;
	$total_flash			= 0;
	$total_mp3				= 0;
	$total_flv_p 			= 0;
	$total_flv				= 0;
	$total_youtube			= 0;
	$total_multimedia		= 0;
	$total_tables			= 0;
	
	$sql="SELECT *, COUNT(*) AS TOTAL_VERS, SUM(hits) AS TOTAL_VISITS FROM ".$tbl_wiki." WHERE c_id = $course_id AND ".$groupfilter.$condition_session."";
	
	$allpages=Database::query($sql);
	while ($row=Database::fetch_array($allpages)) {
		$total_versions			= $row['TOTAL_VERS'];
		$total_visits			= intval($row['TOTAL_VISITS']);
	}
	
	$sql="SELECT * FROM ".$tbl_wiki." WHERE c_id = $course_id AND ".$groupfilter.$condition_session."";
	$allpages=Database::query($sql);	
	
    while ($row=Database::fetch_array($allpages)) {		
		$total_words 			= $total_words+word_count($row['content']);
		$total_links 			= $total_links+substr_count($row['content'], "href=");
		$total_links_anchors 	= $total_links_anchors+substr_count($row['content'], 'href="#');
		$total_links_mail		= $total_links_mail+substr_count($row['content'], 'href="mailto');
		$total_links_ftp 		= $total_links_ftp+substr_count($row['content'], 'href="ftp');
		$total_links_irc		= $total_links_irc+substr_count($row['content'], 'href="irc');
		$total_links_news 		= $total_links_news+substr_count($row['content'], 'href="news');
		$total_wlinks 			= $total_wlinks+substr_count($row['content'], "[[");
		$total_images 			= $total_images+substr_count($row['content'], "<img");
		$clean_total_flash = preg_replace('/player.swf/', ' ', $row['content']);
		$total_flash			= $total_flash+substr_count($clean_total_flash, '.swf"');//.swf" end quotes prevent insert swf through flvplayer (is not counted)
		$total_mp3				= $total_mp3+substr_count($row['content'], ".mp3");	
		$total_flv_p = $total_flv_p+substr_count($row['content'], ".flv");
		$total_flv				=	$total_flv_p/5;
		$total_youtube			= $total_youtube+substr_count($row['content'], "http://www.youtube.com");
		$total_multimedia		= $total_multimedia+substr_count($row['content'], "video/x-msvideo");
		$total_tables			= $total_tables+substr_count($row['content'], "<table");
    }
	
	//check only last version of all pages (current page)
	
	$sql =' SELECT  *, COUNT(*) AS TOTAL_PAGES, SUM(hits) AS TOTAL_VISITS_LV  FROM  '.$tbl_wiki.' s1 
			WHERE s1.c_id = '.$course_id.' AND id=(SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2 WHERE s2.c_id = '.$course_id.' AND s1.reflink = s2.reflink AND '.$groupfilter.' AND session_id='.$session_id.')';
	$allpages=Database::query($sql);
	 while ($row=Database::fetch_array($allpages)) {
		$total_pages	 		= $row['TOTAL_PAGES'];
		$total_visits_lv 		= intval($row['TOTAL_VISITS_LV']);
	 }
	

	$total_words_lv			= 0;
	$total_links_lv			= 0;
	$total_links_anchors_lv	= 0;
	$total_links_mail_lv 	= 0;
	$total_links_ftp_lv 	= 0;
	$total_links_irc_lv 	= 0;
	$total_links_news_lv 	= 0;		
	$total_wlinks_lv 		= 0;
	$total_images_lv 		= 0;
	$clean_total_flash_lv 	= 0;
	$total_flash_lv			= 0;
	$total_mp3_lv			= 0;
	$total_flv_p_lv		    = 0;
	$total_flv_lv			= 0;
	$total_youtube_lv		= 0;
	$total_multimedia_lv	= 0;
	$total_tables_lv		= 0;


	$sql='SELECT * FROM  '.$tbl_wiki.' s1 WHERE s1.c_id = '.$course_id.' AND id=(SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2 WHERE s2.c_id = '.$course_id.' AND s1.reflink = s2.reflink AND '.$groupfilter.' AND session_id='.$session_id.')';
	$allpages=Database::query($sql);	

    while ($row=Database::fetch_array($allpages)) {		
		$total_words_lv 		= $total_words_lv+word_count($row['content']);
		$total_links_lv 		= $total_links_lv+substr_count($row['content'], "href=");		
		$total_links_anchors_lv	= $total_links_anchors_lv+substr_count($row['content'], 'href="#');
		$total_links_mail_lv 	= $total_links_mail_lv+substr_count($row['content'], 'href="mailto');
		$total_links_ftp_lv 	= $total_links_ftp_lv+substr_count($row['content'], 'href="ftp');
		$total_links_irc_lv 	= $total_links_irc_lv+substr_count($row['content'], 'href="irc');
		$total_links_news_lv 	= $total_links_news_lv+substr_count($row['content'], 'href="news');		
		$total_wlinks_lv 		= $total_wlinks_lv+substr_count($row['content'], "[[");
		$total_images_lv 		= $total_images_lv+substr_count($row['content'], "<img");
		$clean_total_flash_lv = preg_replace('/player.swf/', ' ', $row['content']);
		$total_flash_lv			= $total_flash_lv+substr_count($clean_total_flash_lv, '.swf"');//.swf" end quotes prevent insert swf through flvplayer (is not counted)
		$total_mp3_lv			= $total_mp3_lv+substr_count($row['content'], ".mp3");
		$total_flv_p_lv = $total_flv_p_lv+substr_count($row['content'], ".flv");
		$total_flv_lv			= $total_flv_p_lv/5;
		$total_youtube_lv		= $total_youtube_lv+substr_count($row['content'], "http://www.youtube.com");
		$total_multimedia_lv	= $total_multimedia_lv+substr_count($row['content'], "video/x-msvideo");
		$total_tables_lv		= $total_tables_lv+substr_count($row['content'], "<table");
    }
	

//Total pages edited at this time

$total_editing_now=0;
$sql='SELECT  *, COUNT(*) AS TOTAL_EDITING_NOW FROM  '.$tbl_wiki.' s1 
		WHERE is_editing!=0 AND s1.c_id = '.$course_id.' AND 
		id=(SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2 WHERE s2.c_id = '.$course_id.' AND s1.reflink = s2.reflink AND '.$groupfilter.' AND session_id='.$session_id.')';//Can not use group by because the mark is set in the latest version
	$allpages=Database::query($sql);
while ($row=Database::fetch_array($allpages)) {
		$total_editing_now	= $row['TOTAL_EDITING_NOW'];
 }

//Total hidden pages

$total_hidden=0;
$sql='SELECT * FROM '.$tbl_wiki.'  WHERE  c_id = '.$course_id.' AND  visibility=0 AND '.$groupfilter.$condition_session.' GROUP BY reflink';// or group by page_id. As the mark of hidden places it in all versions of the page, I can use group by to see the first

$allpages=Database::query($sql);
 while ($row=Database::fetch_array($allpages)) {	 
		$total_hidden	= $total_hidden+1;		
 }
 
//Total protect pages

$total_protected=0;
$sql='SELECT * FROM '.$tbl_wiki.'  WHERE  c_id = '.$course_id.' AND editlock=1 AND '.$groupfilter.$condition_session.' GROUP BY reflink';// or group by page_id. As the mark of protected page is the first version of the page, I can use group by

$allpages=Database::query($sql);
 while ($row=Database::fetch_array($allpages)) {
		$total_protected	= $total_protected+1;
 }
 
//Total empty versions

$total_empty_content=0;
$sql='SELECT * FROM '.$tbl_wiki.'  WHERE c_id = '.$course_id.' AND content="" AND '.$groupfilter.$condition_session.'';
	$allpages=Database::query($sql);
while ($row=Database::fetch_array($allpages)) {
		$total_empty_content	= $total_empty_content+1;
 }

//Total empty pages (last version)

$total_empty_content_lv=0;
$sql = 'SELECT  * FROM  '.$tbl_wiki.' s1 
		WHERE s1.c_id = '.$course_id.' AND content="" AND id=(
		SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2 WHERE s1.c_id = '.$course_id.' AND s1.reflink = s2.reflink AND '.$groupfilter.' AND session_id='.$session_id.')';
	$allpages=Database::query($sql);
while ($row=Database::fetch_array($allpages)) {
		$total_empty_content_lv	= $total_empty_content_lv+1;
 }

//Total locked discuss pages

$total_lock_disc=0;
$sql='SELECT * FROM '.$tbl_wiki.'  WHERE c_id = '.$course_id.' AND addlock_disc=0 AND '.$groupfilter.$condition_session.' GROUP BY reflink';//group by because mark lock in all vers, then always is ok
	$allpages=Database::query($sql);
while ($row=Database::fetch_array($allpages)) {
		$total_lock_disc	= $total_lock_disc+1;
 }

//Total hidden discuss pages

$total_hidden_disc=0;
 $sql='SELECT * FROM '.$tbl_wiki.'  WHERE c_id = '.$course_id.' AND visibility_disc=0 AND '.$groupfilter.$condition_session.' GROUP BY reflink';//group by because mark lock in all vers, then always is ok
	$allpages=Database::query($sql);
while ($row=Database::fetch_array($allpages)) {
		$total_hidden_disc	= $total_hidden_disc+1;
 }

//Total versions with any short comment by user or system

$total_comment_version=0;
$sql='SELECT * FROM '.$tbl_wiki.'  WHERE c_id = '.$course_id.' AND comment!="" AND '.$groupfilter.$condition_session.'';
	$allpages=Database::query($sql);
while ($row=Database::fetch_array($allpages)) {
		$total_comment_version	= $total_comment_version+1;
 }
 
//Total pages that can only be scored by teachers

$total_only_teachers_rating=0;
 $sql='SELECT * FROM '.$tbl_wiki.'  WHERE c_id = '.$course_id.' AND ratinglock_disc=0 AND '.$groupfilter.$condition_session.' GROUP BY reflink';//group by because mark lock in all vers, then always is ok
	$allpages=Database::query($sql);
while ($row=Database::fetch_array($allpages)) {
		$total_only_teachers_rating	= $total_only_teachers_rating+1;
 }

//Total pages scored by peers
$total_rating_by_peers=0;
$total_rating_by_peers=$total_pages-$total_only_teachers_rating;//put always this line alfter check num all pages and num pages rated by teachers

//Total pages identified as standard task

$total_task=0;
 $sql='SELECT * FROM '.$tbl_wiki.', '.$tbl_wiki_conf.' WHERE '.$tbl_wiki_conf.'.c_id = '.$course_id.' AND  '.$tbl_wiki_conf.'.task!="" AND '.$tbl_wiki_conf.'.page_id='.$tbl_wiki.'.page_id AND '.$tbl_wiki.'.'.$groupfilter.$condition_session;
$allpages=Database::query($sql);
	while ($row=Database::fetch_array($allpages)) {
		$total_task=$total_task+1;
	}

//Total pages identified as teacher page (wiki portfolio mode - individual assignment)

$total_teacher_assignment=0;
$sql='SELECT  * FROM  '.$tbl_wiki.' s1 WHERE s1.c_id = '.$course_id.' AND assignment=1 AND id=(SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2 WHERE s2.c_id = '.$course_id.' AND s1.reflink = s2.reflink AND '.$groupfilter.' AND session_id='.$session_id.')';//mark all versions, but do not use group by reflink because y want the pages not versions
$allpages=Database::query($sql);
	while ($row=Database::fetch_array($allpages)) {
		$total_teacher_assignment=$total_teacher_assignment+1;
	}

//Total pages identifies as student page (wiki portfolio mode - individual assignment)

$total_student_assignment=0;
$sql = 'SELECT  * FROM  '.$tbl_wiki.' s1 
		WHERE s1.c_id = '.$course_id.' AND assignment=2 AND 
		id=(SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2 WHERE s2.c_id = '.$course_id.' AND s1.reflink = s2.reflink AND '.$groupfilter.' AND session_id='.$session_id.')';//mark all versions, but do not use group by reflink because y want the pages not versions
$allpages=Database::query($sql);
	while ($row=Database::fetch_array($allpages)) {
		$total_student_assignment=$total_student_assignment+1;
	}


//Current Wiki status add new pages

$sql='SELECT * FROM '.$tbl_wiki.'  WHERE c_id = '.$course_id.' AND '.$groupfilter.$condition_session.' GROUP BY addlock';//group by because mark 0 in all vers, then always is ok
    $allpages=Database::query($sql);
while ($row=Database::fetch_array($allpages)) {
		$wiki_add_lock=$row['addlock'];		
 }
if ($wiki_add_lock==1){
	
	$status_add_new_pag=get_lang('Yes');
}
else{
	$status_add_new_pag=get_lang('No');
}

//Creation date of the oldest wiki page and version

$first_wiki_date='0000-00-00 00:00:00';
$sql='SELECT * FROM '.$tbl_wiki.'  WHERE c_id = '.$course_id.' AND '.$groupfilter.$condition_session.' ORDER BY dtime ASC LIMIT 1'; 
$allpages=Database::query($sql);
	while ($row=Database::fetch_array($allpages)) {
		$first_wiki_date=$row['dtime'];
	}

//Date of publication of the latest wiki version

$last_wiki_date='0000-00-00 00:00:00';
$sql='SELECT * FROM '.$tbl_wiki.'  WHERE c_id = '.$course_id.' AND '.$groupfilter.$condition_session.' ORDER BY dtime DESC LIMIT 1'; 
$allpages=Database::query($sql);
	while ($row=Database::fetch_array($allpages)) {
		$last_wiki_date=$row['dtime'];
	}

//Average score of all wiki pages. (If a page has not scored zero rated)

$media_score =0;
$sql="SELECT *, SUM(score) AS TOTAL_SCORE FROM ".$tbl_wiki." WHERE c_id = $course_id AND ".$groupfilter.$condition_session." GROUP BY reflink ";//group by because mark in all versions, then always is ok. Do not use "count" because using "group by", would give a wrong value
	$allpages=Database::query($sql);
	while ($row=Database::fetch_array($allpages)) {
		$total_score=$total_score+$row['TOTAL_SCORE'];
	}
		
if (!empty($total_pages)) {
	$media_score = $total_score/$total_pages;//put always this line alfter check num all pages
}

//Average user progress in his pages

$media_progress=0;	
$sql='SELECT  *, SUM(progress) AS TOTAL_PROGRESS FROM  '.$tbl_wiki.' s1 WHERE s1.c_id = '.$course_id.' AND id=(SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2 WHERE s2.c_id = '.$course_id.' AND s1.reflink = s2.reflink AND '.$groupfilter.' AND session_id='.$session_id.')';//As the value is only the latest version I can not use group by
	$allpages=Database::query($sql);	

    while ($row=Database::fetch_array($allpages)) {		
	$total_progress			= $row['TOTAL_PROGRESS'];
}

if (!empty($total_pages)) {
	$media_progress=$total_progress/$total_pages;//put always this line alfter check num all pages
}
 
//Total users that have participated in the Wiki

$total_users=0;
 $sql='SELECT * FROM '.$tbl_wiki.'  WHERE  c_id = '.$course_id.' AND '.$groupfilter.$condition_session.' GROUP BY user_id';//as the mark of user it in all versions of the page, I can use group by to see the first
    $allpages=Database::query($sql);
while ($row=Database::fetch_array($allpages)) {
		$total_users	= $total_users+1;
 }
 
//Total of different IP addresses that have participated in the wiki

$total_ip=0;
 $sql='SELECT * FROM '.$tbl_wiki.'  WHERE c_id = '.$course_id.' AND '.$groupfilter.$condition_session.' GROUP BY user_ip';
    $allpages=Database::query($sql);
while ($row=Database::fetch_array($allpages)) {
		$total_ip	= $total_ip+1;
 }
 ?>
<style>
thead {background:#E2E2E2}
tbody tr:hover {
  background: #F9F9F9;
  border-top-width:thin;
  border-bottom-width:thin;
  border-top-style:dotted;
  border-bottom-style:dotted;
  cursor:default;
  }

</style>
<?php

echo '<table width="100%" border="1">';
echo '<thead>';
echo '<tr>';
echo '<td colspan="2">'.get_lang('General').'</td>';
echo '</tr>';
echo '</thead>';
echo '<tr>';
echo '<td>'.get_lang('StudentAddNewPages').'</td>';
echo '<td>'.$status_add_new_pag.'</td>';
echo '</tr>';
echo '<tr>';
echo '<td>'.get_lang('DateCreateOldestWikiPage').'</td>';
echo '<td>'.$first_wiki_date.'</td>';
echo '</tr>';
echo '<tr>';
echo '<td>'.get_lang('DateEditLatestWikiVersion').'</td>';
echo '<td>'.$last_wiki_date.'</td>';
echo '</tr>';
echo '<tr>';
echo '<td>'.get_lang('AverageScoreAllPages').'</td>';
echo '<td>'.$media_score.' %</td>';
echo '</tr>';
echo '<tr>';
echo '<td>'.get_lang('AverageMediaUserProgress').'</td>';
echo '<td>'.$media_progress.' %</td>';
echo '</tr>';
echo '<tr>';
echo '<td>'.get_lang('TotalWikiUsers').'</td>';
echo '<td>'.$total_users.'</td>';
echo '</tr>';
echo '<tr>';
echo '<td>'.get_lang('TotalIpAdress').'</td>';
echo '<td>'.$total_ip.'</td>';
echo '</tr>';
echo '</table>';
echo '<br/>';

echo '<table width="100%" border="1">';
echo '<thead>';
echo '<tr>';
echo '<td colspan="2">'.get_lang('Pages').' '.get_lang('And').' '.get_lang('Versions').'</td>';
echo '</tr>';
echo '</thead>';
echo '<tr>';
echo '<td>'.get_lang('Pages').' - '.get_lang('NumContributions').'</td>';
echo '<td>'.$total_pages.' ('.get_lang('Versions').': '.$total_versions.')</td>';
echo '</tr>';
echo '<tr>';
echo '<td>'.get_lang('EmptyPages').'</td>';
echo '<td>'.$total_empty_content_lv.' ('.get_lang('Versions').': '.$total_empty_content.')</td>';
echo '</tr>';
echo '<tr>';
echo '<td>'.get_lang('NumAccess').'</td>';
echo '<td>'.$total_visits_lv.' ('.get_lang('Versions').': '.$total_visits.')</td>';
echo '</tr>';
echo '<tr>';
echo '<td>'.get_lang('TotalPagesEditedAtThisTime').'</td>';
echo '<td>'.$total_editing_now.'</td>';
echo '</tr>';
echo '<tr>';
echo '<td>'.get_lang('TotalHiddenPages').'</td>';
echo '<td>'.$total_hidden.'</td>';
echo '</tr>';
echo '<tr>';
echo '<td>'.get_lang('NumProtectedPages').'</td>';
echo '<td>'.$total_protected.'</td>';
echo '</tr>';
echo '<tr>';
echo '<td>'.get_lang('LockedDiscussPages').'</td>';
echo '<td>'.$total_lock_disc.'</td>';
echo '</tr>';
echo '<tr>';
echo '<td>'.get_lang('HiddenDiscussPages').'</td>';
echo '<td>'.$total_hidden_disc.'</td>';
echo '</tr>';
echo '<tr>';
echo '<td>'.get_lang('TotalComments').'</td>';
echo '<td>'.$total_comment_version.'</td>';
echo '</tr>';
echo '<tr>';
echo '<td>'.get_lang('TotalOnlyRatingByTeacher').'</td>';
echo '<td>'.$total_only_teachers_rating.'</td>';
echo '</tr>';
echo '<tr>';
echo '<td>'.get_lang('TotalRatingPeers').'</td>';
echo '<td>'.$total_rating_by_peers.'</td>';
echo '</tr>';
echo '<tr>';
echo '<td>'.get_lang('TotalTeacherAssignments').' - '.get_lang('PortfolioMode').'</td>';
echo '<td>'.$total_teacher_assignment.'</td>';
echo '</tr>';
echo '<tr>';
echo '<td>'.get_lang('TotalStudentAssignments').' - '.get_lang('PortfolioMode').'</td>';
echo '<td>'.$total_student_assignment.'</td>';
echo '</tr>';
echo '<tr>';
echo '<td>'.get_lang('TotalTask').' - '.get_lang('StandardMode').'</td>';
echo '<td>'.$total_task.'</td>';
echo '</tr>';
echo '</table>';
echo '<br/>';

echo '<table width="100%" border="1">';
echo '<thead>';
echo '<tr>';
echo '<td colspan="3">'.get_lang('ContentPagesInfo').'</td>';
echo '</tr>';
echo '<tr>';
echo '<td></td>';
echo '<td>'.get_lang('InTheLastVersion').'</td>';
echo '<td>'.get_lang('InAllVersions').'</td>';
echo '</tr>';
echo '</thead>';
echo '<tr>';
echo '<td>'.get_lang('NumWords').'</td>';
echo '<td>'.$total_words_lv.'</td>';
echo '<td>'.$total_words.'</td>';
echo '</tr>';
echo '<tr>';
echo '<td>'.get_lang('NumlinksHtmlImagMedia').'</td>';
echo '<td>'.$total_links_lv.' ('.get_lang('Anchors').':'.$total_links_anchors_lv.', Mail:'.$total_links_mail_lv.', FTP:'.$total_links_ftp_lv.' IRC:'.$total_links_irc_lv.', News:'.$total_links_news_lv.', ... ) </td>';
echo '<td>'.$total_links.' ('.get_lang('Anchors').':'.$total_links_anchors.', Mail:'.$total_links_mail.', FTP:'.$total_links_ftp.', IRC:'.$total_links_irc.', News:'.$total_links_news.', ... ) </td>';
echo '</tr>';
echo '<tr>';
echo '<td>'.get_lang('NumWikilinks').'</td>';
echo '<td>'.$total_wlinks_lv.'</td>';
echo '<td>'.$total_wlinks.'</td>';
echo '</tr>';
echo '<tr>';
echo '<td>'.get_lang('NumImages').'</td>';
echo '<td>'.$total_images_lv.'</td>';
echo '<td>'.$total_images.'</td>';
echo '</tr>';
echo '<tr>';
echo '<td>'.get_lang('NumFlash').'</td>';
echo '<td>'.$total_flash_lv.'</td>';
echo '<td>'.$total_flash.'</td>';
echo '</tr>';
echo '<tr>';
echo '<td>'.get_lang('NumMp3').'</td>';
echo '<td>'.$total_mp3_lv.'</td>';
echo '<td>'.$total_mp3.'</td>';
echo '</tr>';
echo '<tr>';
echo '<td>'.get_lang('NumFlvVideo').'</td>';
echo '<td>'.$total_flv_lv.'</td>';
echo '<td>'.$total_flv.'</td>';
echo '</tr>';
echo '<tr>';
echo '<td>'.get_lang('NumYoutubeVideo').'</td>';
echo '<td>'.$total_youtube_lv.'</td>';
echo '<td>'.$total_youtube.'</td>';
echo '</tr>';
echo '<tr>';
echo '<td>'.get_lang('NumOtherAudioVideo').'</td>';
echo '<td>'.$total_multimedia_lv.'</td>';
echo '<td>'.$total_multimedia.'</td>';
echo '</tr>';
echo '<tr>';
echo '<td>'.get_lang('NumTables').'</td>';
echo '<td>'.$total_tables_lv.'</td>';
echo '<td>'.$total_tables.'</td>';
echo '</tr>';
echo '</table>';
echo '<br/>';

}

// Most active users Juan Carlos Raña Trabado

if ($_GET['action']=='mactiveusers') {
    echo '<div class="actions">'.get_lang('MostActiveUsers').'</div>';

    $sql='SELECT *, COUNT(*) AS NUM_EDIT FROM '.$tbl_wiki.'  WHERE  c_id = '.$course_id.' AND '.$groupfilter.$condition_session.' GROUP BY user_id';
    $allpages=Database::query($sql);

    //show table
    if (Database::num_rows($allpages) > 0) {
        $row = array ();
        while ($obj = Database::fetch_object($allpages)) {
            $userinfo=Database::get_user_info_from_id($obj->user_id);
            $row = array ();

            $row[] = $obj->user_id <>0 ? '<a href="../user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.api_htmlentities(api_get_person_name($userinfo['firstname'], $userinfo['lastname'])).'</a><a href="'.api_get_self().'?cidReq='.$_course['id'].'&action=usercontrib&user_id='.urlencode($row['user_id']).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'"></a>' : get_lang('Anonymous').' ('.$obj->user_ip.')';
            $row[] ='<a href="'.api_get_self().'?cidReq='.$_course['id'].'&action=usercontrib&user_id='.urlencode($obj->user_id).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'">'.$obj->NUM_EDIT.'</a>';
            $rows[] = $row;
        }

        $table = new SortableTableFromArrayConfig($rows,1,10,'MostActiveUsersA_table','','','DESC');
        $table->set_additional_parameters(array('cidReq' =>Security::remove_XSS($_GET['cidReq']),'action'=>Security::remove_XSS($_GET['action']),'session_id'=>Security::remove_XSS($_GET['session_id']),'group_id'=>Security::remove_XSS($_GET['group_id'])));
        $table->set_header(0,get_lang('Author'), true);
        $table->set_header(1,get_lang('Contributions'), true,array ('style' => 'width:30px;'));
        $table->display();
    }
}

// User contributions Juan Carlos Raña Trabado

if ($_GET['action']=='usercontrib') {
    $userinfo=Database::get_user_info_from_id($_GET['user_id']);

    echo '<div class="actions">'.get_lang('UserContributions').': <a href="../user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.api_htmlentities(api_get_person_name($userinfo['firstname'], $userinfo['lastname'])).'</a><a href="'.api_get_self().'?cidReq='.$_course['id'].'&action=usercontrib&user_id='.urlencode($row['user_id']).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'"></a></div>';


    if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) { //only by professors if page is hidden 
        $sql='SELECT * FROM '.$tbl_wiki.'  WHERE c_id = '.$course_id.' AND '.$groupfilter.$condition_session.' AND user_id="'.Database::escape_string($_GET['user_id']).'"';
    } else {
        $sql='SELECT * FROM '.$tbl_wiki.'  WHERE c_id = '.$course_id.' AND '.$groupfilter.$condition_session.' AND user_id="'.Database::escape_string($_GET['user_id']).'" AND visibility=1';
    }

    $allpages=Database::query($sql);

    //show table
    if (Database::num_rows($allpages) > 0) {
        $row = array ();
        while ($obj = Database::fetch_object($allpages)) {
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
            if ($obj->assignment==1) {
                $ShowAssignment=Display::return_icon('wiki_assignment.png', get_lang('AssignmentDescExtra'),'',22);
            } elseif ($obj->assignment==2) {
                $ShowAssignment=Display::return_icon('wiki_work.png', get_lang('AssignmentWork'),'',22);
            } elseif ($obj->assignment==0) {
                $ShowAssignment='<img src="../img/px_transparent.gif" />';
            }

            $row = array ();
            $row[] = $year.'-'.$month.'-'.$day.' '.$hours.":".$minutes.":".$seconds;
            $row[] =$ShowAssignment;

            $row[] = '<a href="'.api_get_self().'?cidReq='.$_course['id'].'&action=showpage&title='.api_htmlentities(urlencode($obj->reflink)).'&view='.$obj->id.'&session_id='.api_htmlentities(urlencode($_GET['$session_id'])).'&group_id='.api_htmlentities(urlencode($_GET['group_id'])).'">'.api_htmlentities($obj->title).'</a>';
            $row[] =Security::remove_XSS($obj->version);
            $row[] =Security::remove_XSS($obj->comment);
            //$row[] = api_strlen($obj->comment)>30 ? Security::remove_XSS(api_substr($obj->comment,0,30)).'...' : Security::remove_XSS($obj->comment);
            $row[] =Security::remove_XSS($obj->progress).' %';
            $row[] =Security::remove_XSS($obj->score);
            //if(api_is_allowed_to_edit() || api_is_platform_admin())
            //{
                //$row[] =Security::remove_XSS($obj->user_ip);
            //}
            $rows[] = $row;

        }

        $table = new SortableTableFromArrayConfig($rows,2,10,'UsersContributions_table','','','ASC');
        $table->set_additional_parameters(array('cidReq' =>Security::remove_XSS($_GET['cidReq']),'action'=>Security::remove_XSS($_GET['action']),'user_id'=>Security::remove_XSS($_GET['user_id']),'session_id'=>Security::remove_XSS($_GET['session_id']),'group_id'=>Security::remove_XSS($_GET['group_id'])));

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

if ($_GET['action']=='mostchanged') {
    echo '<div class="actions">'.get_lang('MostChangedPages').'</div>';


    if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) { //only by professors if page is hidden
        $sql='SELECT *, MAX(version) AS MAX FROM '.$tbl_wiki.'  WHERE c_id = '.$course_id.' AND '.$groupfilter.$condition_session.' GROUP BY reflink';//TODO:check MAX and group by return last version
    } else {
        $sql='SELECT *, MAX(version) AS MAX FROM '.$tbl_wiki.'  WHERE c_id = '.$course_id.' AND '.$groupfilter.$condition_session.' AND visibility=1 GROUP BY reflink'; //TODO:check MAX and group by return last version
    }

    $allpages=Database::query($sql);

    //show table
    if (Database::num_rows($allpages) > 0) {
        $row = array ();
        while ($obj = Database::fetch_object($allpages)) {
            //get type assignment icon
            if ($obj->assignment==1) {
                $ShowAssignment=Display::return_icon('wiki_assignment.png', get_lang('AssignmentDesc'),'',22);
            } elseif ($obj->assignment==2) {
                $ShowAssignment=Display::return_icon('wiki_work.png', get_lang('AssignmentWork'),'',22);
            } elseif ($obj->assignment==0) {
                $ShowAssignment='<img src="../img/px_transparent.gif" />';
            }

            $row = array ();
            $row[] =$ShowAssignment;
            $row[] = '<a href="'.api_get_self().'?cidReq='.$_course['id'].'&action=showpage&title='.api_htmlentities(urlencode($obj->reflink)).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'">'.api_htmlentities($obj->title).'</a>';
            $row[] = $obj->MAX;
            $rows[] = $row;
        }

        $table = new SortableTableFromArrayConfig($rows,2,10,'MostChangedPages_table','','','DESC');
        $table->set_additional_parameters(array('cidReq' =>Security::remove_XSS($_GET['cidReq']),'action'=>Security::remove_XSS($_GET['action']),'session_id'=>Security::remove_XSS($_GET['session_id']),'group_id'=>Security::remove_XSS($_GET['group_id'])));
        $table->set_header(0,get_lang('Type'), true, array ('style' => 'width:30px;'));
        $table->set_header(1,get_lang('Title'), true);
        $table->set_header(2,get_lang('Changes'), true);
        $table->display();
    }
}

/////////////////////// Most visited pages /////////////////////// Juan Carlos Raña Trabado

if ($_GET['action']=='mvisited') {
    echo '<div class="actions">'.get_lang('MostVisitedPages').'</div>';

    if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) { //only by professors if page is hidden
        $sql='SELECT *, SUM(hits) AS tsum FROM '.$tbl_wiki.'  WHERE c_id = '.$course_id.' AND '.$groupfilter.$condition_session.' GROUP BY reflink';
    } else {
        $sql='SELECT *, SUM(hits) AS tsum FROM '.$tbl_wiki.'  WHERE  c_id = '.$course_id.' AND '.$groupfilter.$condition_session.' AND visibility=1 GROUP BY reflink';
    }

    $allpages=Database::query($sql);

    //show table
    if (Database::num_rows($allpages) > 0) {
        $row = array ();
        while ($obj = Database::fetch_object($allpages)) {
            //get type assignment icon
            if ($obj->assignment==1) {
                $ShowAssignment=Display::return_icon('wiki_assignment.png', get_lang('AssignmentDesc'),'',22);
            } elseif ($obj->assignment==2) {
                $ShowAssignment=$ShowAssignment=Display::return_icon('wiki_work.png', get_lang('AssignmentWork'),'',22);
            } elseif ($obj->assignment==0) {
                $ShowAssignment='<img src="../img/px_transparent.gif" />';
            }

            $row = array ();
            $row[] =$ShowAssignment;
            $row[] = '<a href="'.api_get_self().'?cidReq='.$_course['id'].'&action=showpage&title='.api_htmlentities(urlencode($obj->reflink)).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'">'.api_htmlentities($obj->title).'</a>';
            $row[] = $obj->tsum;
            $rows[] = $row;
        }

        $table = new SortableTableFromArrayConfig($rows,2,10,'MostVisitedPages_table','','','DESC');
        $table->set_additional_parameters(array('cidReq' =>Security::remove_XSS($_GET['cidReq']),'action'=>Security::remove_XSS($_GET['action']),'session_id'=>Security::remove_XSS($_GET['session_id']),'group_id'=>Security::remove_XSS($_GET['group_id'])));
        $table->set_header(0,get_lang('Type'), true, array ('style' => 'width:30px;'));
        $table->set_header(1,get_lang('Title'), true);
        $table->set_header(2,get_lang('Visits'), true);
        $table->display();
    }
}

/////////////////////// Wanted pages /////////////////////// Juan Carlos Raña Trabado

if ($_GET['action']=='wanted') {
    echo '<div class="actions">'.get_lang('WantedPages').'</div>';

    $pages = array();
    $refs = array();
	$wanted = array();
	
    //get name pages
    $sql='SELECT * FROM '.$tbl_wiki.'  WHERE  c_id = '.$course_id.' AND '.$groupfilter.$condition_session.' GROUP BY reflink ORDER BY reflink ASC';
    $allpages=Database::query($sql);

    while ($row=Database::fetch_array($allpages)) {
        $pages[] = $row['reflink'];
    }

    //get name refs in last pages
    $sql = 'SELECT  *  FROM   '.$tbl_wiki.' s1 
    		WHERE s1.c_id = '.$course_id.' AND id=(SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2 WHERE s2.c_id = '.$course_id.' AND s1.reflink = s2.reflink AND '.$groupfilter.$condition_session.')';

    $allpages=Database::query($sql);
	
    while ($row=Database::fetch_array($allpages)) {		
	
        $refs = explode(" ", trim($row["linksto"]));
		
		// Find linksto into reflink. If not found ->page is wanted	
		foreach ($refs as $v) {
	
			if (!in_array($v, $pages)) {
				if (trim($v)!="") {
					$wanted[]=$v;
				}
			}
		}
	}
	$wanted=array_unique($wanted);//make a unique list

	//show table
        foreach ($wanted as $wanted_show) {
			
            $row = array ();
			$wanted_show=Security::remove_XSS($wanted_show);
            $row[] = '<a href="'.api_get_path(WEB_PATH).'main/wiki/index.php?cidReq=&action=addnew&title='.str_replace('_',' ',$wanted_show).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'" class="new_wiki_link">'.str_replace('_',' ',$wanted_show).'</a>';//meter un remove xss en lugar de htmlentities

            $rows[] = $row;
        }

        $table = new SortableTableFromArrayConfig($rows,0,10,'WantedPages_table','','','DESC');
        $table->set_additional_parameters(array('cidReq' =>Security::remove_XSS($_GET['cidReq']),'action'=>Security::remove_XSS($_GET['action']),'session_id'=>Security::remove_XSS($_GET['session_id']),'group_id'=>Security::remove_XSS($_GET['group_id'])));
        $table->set_header(0,get_lang('Title'), true);
        $table->display();
}

/////////////////////// Orphaned pages /////////////////////// Juan Carlos Raña Trabado

if ($_GET['action']=='orphaned') {
    echo '<div class="actions">'.get_lang('OrphanedPages').'</div>';

    $pages = array();
    $refs = array();
	$list_refs = array();
    $orphaned = array();

    //get name pages
    $sql='SELECT * FROM '.$tbl_wiki.'  WHERE c_id = '.$course_id.' AND '.$groupfilter.$condition_session.' GROUP BY reflink ORDER BY reflink ASC';
    $allpages=Database::query($sql);
    while ($row=Database::fetch_array($allpages)) {
        $pages[] = $row['reflink'];
    }

    //get name refs in last pages and make a unique list
    $sql = 'SELECT  *  FROM   '.$tbl_wiki.' s1  
    		WHERE s1.c_id = '.$course_id.' AND id=(SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2 WHERE s2.c_id = '.$course_id.' AND s1.reflink = s2.reflink AND '.$groupfilter.$condition_session.')';

    $allpages=Database::query($sql);
    while ($row=Database::fetch_array($allpages)) {
        $row['linksto']= str_replace($row["reflink"], " ", trim($row["linksto"])); //remove self reference		
		$refs = explode(" ", trim($row["linksto"]));
		foreach ($refs as $ref_linked){
			if ($ref_linked==str_replace(' ','_',get_lang('DefaultTitle'))) {
				$ref_linked='index';
			}
			$array_refs_linked[]= $ref_linked;
		}
    }

	$array_refs_linked = array_unique($array_refs_linked);
	
    //search each name of list linksto into list reflink
    foreach ($pages as $v) {
        if (!in_array($v, $array_refs_linked)) {
            $orphaned[] = $v;
        }
    }

    foreach ($orphaned as $orphaned_show) {
		// get visibility status and title
		$sql='SELECT  *  FROM   '.$tbl_wiki.' WHERE c_id = '.$course_id.' AND '.$groupfilter.$condition_session.' AND reflink="'.Database::escape_string($orphaned_show).'" GROUP BY reflink';
        $allpages=Database::query($sql);
		while ($row=Database::fetch_array($allpages)) {
			$orphaned_title=$row['title'];
			$orphaned_visibility=$row['visibility'];
			if ($row['assignment']==1) {
                $ShowAssignment=Display::return_icon('wiki_assignment.png','','',22);
            } elseif ($row['assignment']==2) {
                $ShowAssignment=Display::return_icon('wiki_work.png','','',22);
            } elseif ($row['assignment']==0) {
                $ShowAssignment='<img src="../img/px_transparent.gif" />';
            }
		}
		if (!api_is_allowed_to_edit(false,true) || !api_is_platform_admin() AND $orphaned_visibility==0){
			continue;
		}
		
		//show table
        $row = array ();
			$row[] =$ShowAssignment;
            $row[] = '<a href="'.api_get_self().'?cidReq='.$_course['id'].'&action=showpage&title='.api_htmlentities(urlencode($orphaned_show)).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'">'.api_htmlentities($orphaned_title).'</a>';
			
            $rows[] = $row;
        }

        $table = new SortableTableFromArrayConfig($rows,1,10,'OrphanedPages_table','','','DESC');
        $table->set_additional_parameters(array('cidReq' =>Security::remove_XSS($_GET['cidReq']),'action'=>Security::remove_XSS($_GET['action']),'session_id'=>Security::remove_XSS($_GET['session_id']),'group_id'=>Security::remove_XSS($_GET['group_id'])));
       $table->set_header(0,get_lang('Type'), true, array ('style' => 'width:30px;'));
	   $table->set_header(1,get_lang('Title'), true);
       $table->display();
}

/////////////////////// Most linked pages /////////////////////// Juan Carlos Raña Trabado

if ($_GET['action']=='mostlinked') {
    echo '<div class="actions">'.get_lang('MostLinkedPages').'</div>';
	$pages = array();
    $refs = array();
	$linked = array();
	
    //get name pages
    $sql='SELECT * FROM '.$tbl_wiki.'  WHERE  c_id = '.$course_id.' AND '.$groupfilter.$condition_session.' GROUP BY reflink ORDER BY reflink ASC';
    $allpages=Database::query($sql);

    while ($row=Database::fetch_array($allpages)) {
        $pages[] = $row['reflink'];
    }

    //get name refs in last pages
    $sql='SELECT  *  FROM   '.$tbl_wiki.' s1 WHERE s1.c_id = '.$course_id.' AND id=(SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2 WHERE s2.c_id = '.$course_id.' AND s1.reflink = s2.reflink AND '.$groupfilter.$condition_session.')';

    $allpages=Database::query($sql);
	
    while ($row=Database::fetch_array($allpages)) {		
	 	$row['linksto']= str_replace($row["reflink"], " ", trim($row["linksto"])); //remove self reference
        $refs = explode(" ", trim($row["linksto"]));
		
		// Find linksto into reflink. If found ->page is linked
		foreach ($refs as $v) {
	
			if (in_array($v, $pages)) {
				if (trim($v)!="") {
					if ($v=='index'){
					 $v=str_replace('_',' ',get_lang('DefaultTitle'));
					}					
					$linked[]=$v;
				}
			}
		}
	}
	
	$linked=array_unique($linked);//make a unique list. TODO:delete this line and count how many for each page
	//show table
        foreach ($linked as $linked_show) {
			
            $row = array ();
			
			$row[] = '<a href="'.api_get_self().'?cidReq='.$_course['id'].'&action=showpage&title='.api_htmlentities(urlencode(str_replace('_',' ',$linked_show))).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'">'.str_replace('_',' ',$linked_show).'</a>';
			
            $rows[] = $row;
        }

        $table = new SortableTableFromArrayConfig($rows,0,10,'LinkedPages_table','','','DESC');
        $table->set_additional_parameters(array('cidReq' =>Security::remove_XSS($_GET['cidReq']),'action'=>Security::remove_XSS($_GET['action']),'session_id'=>Security::remove_XSS($_GET['session_id']),'group_id'=>Security::remove_XSS($_GET['group_id'])));
        $table->set_header(0,get_lang('Title'), true);
        $table->display();
	
}

/////////////////////// delete current page /////////////////////// Juan Carlos Raña Trabado

if ($_GET['action']=='delete') {

    if (!$_GET['title']) {
        Display::display_error_message(get_lang('MustSelectPage'));
        exit;
    }

    echo '<div style="overflow:hidden">';
    if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
        echo '<div id="wikititle">'.get_lang('DeletePageHistory').'</div>';

        if ($page=="index") {
            Display::display_warning_message(get_lang('WarningDeleteMainPage'),false);
        }

        $message = get_lang('ConfirmDeletePage')."</p>"."<p>"."<a href=\"index.php\">".get_lang("No")."</a>"."&nbsp;&nbsp;|&nbsp;&nbsp;"."<a href=\"".api_get_self()."?action=delete&amp;title=".api_htmlentities(urlencode($page))."&amp;delete=yes\">".get_lang("Yes")."</a>"."</p>";

        if (!isset ($_GET['delete'])) {
            Display::display_warning_message($message,false);
        }

        if ($_GET['delete'] == 'yes') {
            $sql='DELETE '.$tbl_wiki_discuss.' FROM '.$tbl_wiki.', '.$tbl_wiki_discuss.' 
                WHERE '.$tbl_wiki.'.c_id = '.$course_id.' AND '.$tbl_wiki_discuss.'.c_id = '.$course_id.' AND  '.$tbl_wiki.'.reflink="'.Database::escape_string($page).'" AND '.$tbl_wiki.'.'.$groupfilter.' AND '.$tbl_wiki.'.session_id='.$session_id.' AND '.$tbl_wiki_discuss.'.publication_id='.$tbl_wiki.'.id';
            Database::query($sql);

            $sql='DELETE '.$tbl_wiki_mailcue.' FROM '.$tbl_wiki.', '.$tbl_wiki_mailcue.' 
            WHERE '.$tbl_wiki.'.c_id = '.$course_id.' AND '.$tbl_wiki_mailcue.'.c_id = '.$course_id.' AND  '.$tbl_wiki.'.reflink="'.Database::escape_string($page).'" AND '.$tbl_wiki.'.'.$groupfilter.' AND '.$tbl_wiki.'.session_id='.$session_id.' AND '.$tbl_wiki_mailcue.'.id='.$tbl_wiki.'.id';
            Database::query($sql);

            $sql='DELETE FROM '.$tbl_wiki.' WHERE c_id = '.$course_id.' AND reflink="'.Database::escape_string($page).'" AND '.$groupfilter.$condition_session.'';
            Database::query($sql);

            check_emailcue(0, 'E');

            Display::display_confirmation_message(get_lang('WikiPageDeleted'));
        }
    } else {
        Display::display_normal_message(get_lang("OnlyAdminDeletePageWiki"));
    }

    echo '</div>';
}

/////////////////////// delete all wiki /////////////////////// Juan Carlos Raña Trabado

if ($_GET['action']=='deletewiki') {

    echo '<div class="actions">'.get_lang('DeleteWiki').'</div>';
    echo '<div style="overflow:hidden">';
    if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
        $message = 	get_lang('ConfirmDeleteWiki');
        $message .= '<p>
                        <a href="index.php">'.get_lang('No').'</a>
                        &nbsp;&nbsp;|&nbsp;&nbsp;
                        <a href="'.api_get_self().'?action=deletewiki&amp;delete=yes">'.get_lang('Yes').'</a>
                    </p>';

        if (!isset($_GET['delete'])) {
            Display::display_warning_message($message,false);
        }
    } else {
        Display::display_normal_message(get_lang("OnlyAdminDeleteWiki"));
    }
    echo '</div>';
}

/////////////////////// search wiki pages ///////////////////////

if ($_GET['action']=='searchpages') {
	
    echo '<div class="actions">'.get_lang('SearchPages').'</div>';
    echo '<div style="overflow:hidden">';
	
	if ($_GET['mode_table']) {
		if (! $_GET['SearchPages_table_page_nr']) {
			$_GET['search_term']=$_POST['search_term'];
			$_GET['search_content']=$_POST['search_content'];
			$_GET['all_vers']=$_POST['all_vers'];
		}
		display_wiki_search_results(api_htmlentities($_GET['search_term']),api_htmlentities($_GET['search_content']),api_htmlentities($_GET['all_vers']));
	} else {
	
		// initiate the object
		$form = new FormValidator('wiki_search','post', api_get_self().'?cidReq='.api_htmlentities($_GET['cidReq']).'&action='.api_htmlentities($_GET['action']).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'&mode_table=yes1&search_term='.api_htmlentities($_GET['search_term']).'&search_content='.api_htmlentities($_GET['search_content']).'&all_vers='.api_htmlentities($_GET['all_vers']));

		// settting the form elements
	
		$form->addElement('text', 'search_term', get_lang('SearchTerm'),'class="input_titles" id="search_title"');
		$form->addElement('checkbox', 'search_content', null, get_lang('AlsoSearchContent'));
		$form->addElement('checkbox', 'all_vers', null, get_lang('IncludeAllVersions'));
		$form->addElement('style_submit_button', 'SubmitWikiSearch', get_lang('Search'), 'class="search"');
	
		// setting the rules
		$form->addRule('search_term', '<span class="required">'.get_lang('ThisFieldIsRequired').'</span>', 'required');
		$form->addRule('search_term', get_lang('TooShort'),'minlength',3); //TODO: before fixing the pagination rules worked, not now	
		if ($form->validate()) {
			$form->display();
			$values = $form->exportValues();
			display_wiki_search_results($values['search_term'], $values['search_content'], $values['all_vers']);
		} else {
			$form->display();
		}
	}
	
    echo '</div>';
}


///////////////////////  What links here. Show pages that have linked this page /////////////////////// Juan Carlos Raña Trabado

if ($_GET['action']=='links') {

    if (!$_GET['title']) {
        Display::display_error_message(get_lang("MustSelectPage"));
    } else {

        $sql='SELECT * FROM '.$tbl_wiki.' WHERE c_id = '.$course_id.' AND reflink="'.Database::escape_string($page).'" AND '.$groupfilter.$condition_session.'';
        $result=Database::query($sql);
        $row=Database::fetch_array($result);

        //get type assignment icon

        if ($row['assignment']==1) {
            $ShowAssignment=Display::return_icon('wiki_assignment.png', get_lang('AssignmentDesc'),'',22);
        } elseif ($row['assignment']==2) {
            $ShowAssignment=Display::return_icon('wiki_work.png', get_lang('AssignmentWork'),'',22);
        } elseif ($row['assignment']==0) {
            $ShowAssignment='<img src="../img/px_transparent.gif" />';
        }

        //fix Title to reflink (link Main Page)

        if ($page==get_lang('DefaultTitle')) {
            $page='index';
        }

        echo '<div id="wikititle">';
        echo get_lang('LinksPagesFrom').': '.$ShowAssignment.' <a href="'.api_get_self().'?cidReq='.$_course['id'].'&action=showpage&title='.api_htmlentities(urlencode($page)).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'">'.api_htmlentities($row['title']).'</a>';
        echo '</div>';

        //fix index to title Main page into linksto
        if ($page=='index') {
            $page=str_replace(' ','_',get_lang('DefaultTitle'));
        }

        //table
        if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) { //only by professors if page is hidden 	
			$sql="SELECT * FROM ".$tbl_wiki." s1 WHERE s1.c_id = $course_id AND linksto LIKE '%".Database::escape_string($page)." %' AND id=(SELECT MAX(s2.id) FROM ".$tbl_wiki." s2 WHERE s2.c_id = $course_id AND s1.reflink = s2.reflink AND ".$groupfilter.$condition_session.")";//add blank space after like '%" " %' to identify each word			
        } else {
            $sql="SELECT * FROM ".$tbl_wiki." s1 WHERE s1.c_id = $course_id AND visibility=1 AND linksto LIKE '%".Database::escape_string($page)." %' AND id=(SELECT MAX(s2.id) FROM ".$tbl_wiki." s2 WHERE s2.c_id = $course_id AND s1.reflink = s2.reflink AND ".$groupfilter.$condition_session.")";//add blank space after like '%" " %' to identify each word		
        }

        $allpages=Database::query($sql);

        //show table
        if (Database::num_rows($allpages) > 0) {
            $row = array ();
            while ($obj = Database::fetch_object($allpages)) {
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
                if ($obj->assignment==1) {
                    $ShowAssignment=Display::return_icon('wiki_assignment.png', get_lang('AssignmentDesc'),'',22);
                } elseif ($obj->assignment==2) {
                    $ShowAssignment=Display::return_icon('wiki_work.png', get_lang('AssignmentWork'),'',22);
                } elseif ($obj->assignment==0) {
                    $ShowAssignment='<img src="../img/px_transparent.gif" />';
                }

                $row = array ();
                $row[] =$ShowAssignment;
                $row[] = '<a href="'.api_get_self().'?cidReq='.$_course['id'].'&action=showpage&title='.api_htmlentities(urlencode($obj->reflink)).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'">'.api_htmlentities($obj->title).'</a>';
                $row[] = $obj->user_id <>0 ? '<a href="../user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.api_htmlentities(api_get_person_name($userinfo['firstname'], $userinfo['lastname'])).'</a>' : get_lang('Anonymous').' ('.$obj->user_ip.')';
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


// Adding a new page


// Display the form for adding a new wiki page
if ($_GET['action']=='addnew') {
    if (api_get_session_id()!=0 && api_is_allowed_to_session_edit(false,true)==false) {
        api_not_allowed();
    }

    echo '<div class="actions">'.get_lang('AddNew').'</div>';
	echo '<br/>';
    //first, check if page index was created. chektitle=false
    if (checktitle('index')) {
        if (api_is_allowed_to_edit(false,true) || api_is_platform_admin() || GroupManager :: is_user_in_group($_user['user_id'],$_SESSION['_gid'])) {
            Display::display_normal_message(get_lang('GoAndEditMainPage'));
        } else {
            return Display::display_normal_message(get_lang('WikiStandBy'));
        }
    } elseif (check_addnewpagelock()==0 && (api_is_allowed_to_edit(false,true)==false || api_is_platform_admin()==false)) {
        Display::display_error_message(get_lang('AddPagesLocked'));
    } else {
        if(api_is_allowed_to_edit(false,true) || api_is_platform_admin() || GroupManager :: is_user_in_group($_user['user_id'],$_SESSION['_gid']) || Security::remove_XSS($_GET['group_id'])==0) {
            display_new_wiki_form();
        } else {
            Display::display_normal_message(get_lang('OnlyAddPagesGroupMembers'));
        }
    }

}



// Show home page

if (!$_GET['action'] OR $_GET['action']=='show' AND !isset($_POST['SaveWikiNew'])) {
    display_wiki_entry($newtitle);
}

// Show current page


if ($_GET['action']=='showpage' AND !isset($_POST['SaveWikiNew'])) {
    if ($_GET['title']) {
        display_wiki_entry($newtitle);
    } else {
        Display::display_error_message(get_lang('MustSelectPage'));
    }
}

// Edit current page

if ($_GET['action']=='edit') {

    if (api_get_session_id()!=0 && api_is_allowed_to_session_edit(false,true)==false) {
        api_not_allowed();
    }

    $_clean['group_id']=(int)$_SESSION['_gid'];

    $sql = 'SELECT * FROM '.$tbl_wiki.', '.$tbl_wiki_conf.' 
    		WHERE 
    		'.$tbl_wiki.'.c_id = '.$course_id.' AND
    		'.$tbl_wiki_conf.'.c_id = '.$course_id.' AND
    		'.$tbl_wiki_conf.'.page_id='.$tbl_wiki.'.page_id AND 
    		'.$tbl_wiki.'.reflink="'.Database::escape_string($page).'" AND 
    		'.$tbl_wiki.'.'.$groupfilter.$condition_session.' 
    		ORDER BY id DESC';
    $result=Database::query($sql);
    $row=Database::fetch_array($result); // we do not need a while loop since we are always displaying the last version


    if ($row['content']=='' AND $row['title']=='' AND $page=='') {
        Display::display_error_message(get_lang('MustSelectPage'));
        exit;
    } elseif ($row['content']=='' AND $row['title']=='' AND $page=='index') {
        //Table structure for better export to pdf
        $default_table_for_content_Start='<table align="center" border="0"><tr><td align="center">';
        $default_table_for_content_End='</td></tr></table>';

        $content=$default_table_for_content_Start.sprintf(get_lang('DefaultContent'),api_get_path(WEB_IMG_PATH)).$default_table_for_content_End;
        $title=get_lang('DefaultTitle');
        $page_id=0;
    } else {
        $content=$row['content'];
        $title=$row['title'];
        $page_id=$row['page_id'];
    }

    //Only teachers and platform admin can edit the index page. Only teachers and platform admin can edit an assignment teacher. And users in groups
    if (($row['reflink']=='index' || $row['reflink']=='' || $row['assignment']==1) && (!api_is_allowed_to_edit(false,true) && intval($_GET['group_id'])==0)) {
        Display::display_error_message(get_lang('OnlyEditPagesCourseManager'));
    } else {
        $PassEdit=false;

        //check if is a wiki group
        if ($_clean['group_id']!=0) {
            //Only teacher, platform admin and group members can edit a wiki group
            if (api_is_allowed_to_edit(false,true) || api_is_platform_admin() || GroupManager :: is_user_in_group($_user['user_id'],intval($_GET['group_id']))) {
                $PassEdit=true;
            } else {
                  Display::display_normal_message(get_lang('OnlyEditPagesGroupMembers'));
            }
        } else {
            $PassEdit=true;
        }

        // check if is a assignment
        if ($row['assignment']==1) {
            Display::display_normal_message(get_lang('EditAssignmentWarning'));
            $icon_assignment=Display::return_icon('wiki_assignment.png', get_lang('AssignmentDescExtra'),'',22);
        } elseif ($row['assignment']==2) {
            $icon_assignment=Display::return_icon('wiki_work.png', get_lang('AssignmentWorkExtra'),'',22);
            if ((api_get_user_id()==$row['user_id'])==false) {
                if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
                    $PassEdit=true;
                } else {
                    Display::display_warning_message(get_lang('LockByTeacher'));
                    $PassEdit=false;
                }
            } else {
                $PassEdit=true;
            }
        }

         if ($PassEdit) { //show editor if edit is allowed
             if ($row['editlock']==1 && (api_is_allowed_to_edit(false,true)==false || api_is_platform_admin()==false)) {
                   Display::display_normal_message(get_lang('PageLockedExtra'));
            } else {
                //check tasks
                if (!empty($row['startdate_assig']) && $row['startdate_assig']!='0000-00-00 00:00:00' && time()<strtotime($row['startdate_assig'])) {
                    $message=get_lang('TheTaskDoesNotBeginUntil').': '.api_get_local_time($row['startdate_assig'], null, date_default_timezone_get());
                    Display::display_warning_message($message);
                    if (!api_is_allowed_to_edit(false,true)) {
                        exit;
                    }
                }

                //
                if (!empty($row['enddate_assig']) && $row['enddate_assig']!='0000-00-00 00:00:00' && time()>strtotime($row['enddate_assig']) && $row['enddate_assig']!='0000-00-00 00:00:00' && $row['delayedsubmit']==0) {
                    $message=get_lang('TheDeadlineHasBeenCompleted').': '.api_get_local_time($row['enddate_assig'], null, date_default_timezone_get());
                    Display::display_warning_message($message);
                    if (!api_is_allowed_to_edit(false,true)) {
                        exit;
                    }
                }

                //
                if (!empty($row['max_version']) && $row['version']>=$row['max_version']) {
                    $message=get_lang('HasReachedMaxiNumVersions');
                    Display::display_warning_message($message);
                    if (!api_is_allowed_to_edit(false,true)) {
                        exit;
                    }
                }

                //
                if (!empty($row['max_text']) && $row['max_text']<=word_count($row['content'])) {
                    $message=get_lang('HasReachedMaxNumWords');
                    Display::display_warning_message($message);
                    if (!api_is_allowed_to_edit(false,true)) {
                        exit;
                    }

                }

                ////
                if (!empty($row['task'])) {
                    //previous change 0 by text
                    if ($row['startdate_assig']=='0000-00-00 00:00:00') {
                        $message_task_startdate=get_lang('No');
                    } else {
                        $message_task_startdate=api_get_local_time($row['startdate_assig'], null, date_default_timezone_get());
                    }

                    if ($row['enddate_assig']=='0000-00-00 00:00:00') {
                        $message_task_enddate=get_lang('No');
                    } else {
                        $message_task_endate=api_get_local_time($row['enddate_assig'], null, date_default_timezone_get());
                    }

                    if ($row['delayedsubmit']==0) {
                        $message_task_delayedsubmit=get_lang('No');
                    } else {
                        $message_task_delayedsubmit=get_lang('Yes');
                    }
                    if ($row['max_version']==0) {
                        $message_task_max_version=get_lang('No');
                    } else {
                        $message_task_max_version=$row['max_version'];
                    }
                    if ($row['max_text']==0) {
                        $message_task_max_text=get_lang('No');
                    } else {
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

                if ($row['progress']==$row['fprogress1'] && !empty($row['fprogress1'])) {
                    $feedback_message='<b>'.get_lang('Feedback').'</b><p>'.api_htmlentities($row['feedback1']).'</p>';
                    Display::display_normal_message($feedback_message, false);
                } elseif ($row['progress']==$row['fprogress2'] && !empty($row['fprogress2'])) {
                    $feedback_message='<b>'.get_lang('Feedback').'</b><p>'.api_htmlentities($row['feedback2']).'</p>';
                    Display::display_normal_message($feedback_message, false);
                } elseif ($row['progress']==$row['fprogress3'] && !empty($row['fprogress3'])) {
                    $feedback_message='<b>'.get_lang('Feedback').'</b><p>'.api_htmlentities($row['feedback3']).'</p>';
                    Display::display_normal_message($feedback_message, false);
                }

                //previous checking for concurrent editions
                if ($row['is_editing']==0) {
					echo '<div style="z-index:0">';
                    Display::display_normal_message(get_lang('WarningMaxEditingTime'));
					echo '</div>';

                    $time_edit = date("Y-m-d H:i:s");
                    $sql='UPDATE '.$tbl_wiki.' SET is_editing="'.$_user['user_id'].'", time_edit="'.$time_edit.'" WHERE c_id = '.$course_id.' AND  id="'.$row['id'].'"';
                    Database::query($sql);
                } elseif ($row['is_editing']!=$_user['user_id']) {
                    $timestamp_edit=strtotime($row['time_edit']);
                    $time_editing=time()-$timestamp_edit;
                    $max_edit_time=1200; // 20 minutes
                    $rest_time=$max_edit_time-$time_editing;

                    $userinfo=Database::get_user_info_from_id($row['is_editing']);

                    $is_being_edited= get_lang('ThisPageisBeginEditedBy').' <a href=../user/userInfo.php?uInfo='.$userinfo['user_id'].'>'.api_htmlentities(api_get_person_name($userinfo['firstname'], $userinfo['lastname'])).'</a>. '.get_lang('ThisPageisBeginEditedTryLater').' '.date( "i",$rest_time).' '.get_lang('MinMinutes').'';
                    Display::display_normal_message($is_being_edited, false);
                    exit;
                }
                //form
                echo '<form name="form1" method="post" action="'.api_get_self().'?action=showpage&amp;title='.api_htmlentities(urlencode($page)).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'">';


                echo '<div id="wikititle">';
                echo '<div style="width:70%;float:left;">'.$icon_assignment.str_repeat('&nbsp;',3).api_htmlentities($title).'</div>';

                if ((api_is_allowed_to_edit(false,true) || api_is_platform_admin()) && $row['reflink']!='index') {

                    echo'<a href="javascript://" onclick="advanced_parameters()" ><span id="plus_minus" style="float:right">&nbsp;'.Display::return_icon('div_show.gif',get_lang('Show'),array('style'=>'vertical-align:middle')).'&nbsp;'.get_lang('AdvancedParameters').'</span></a>';

                    echo '<div id="options" style="display:none; margin: 20px;" >';

                    //task
                    echo '<div>&nbsp;</div><input type="checkbox" value="1" name="checktask" onclick="javascript: if(this.checked){document.getElementById(\'option4\').style.display=\'block\';}else{document.getElementById(\'option4\').style.display=\'none\';}"/>&nbsp;'.Display::return_icon('wiki_task.png', get_lang('DefineTask'),'',22).' '.get_lang('DescriptionOfTheTask').'';
                    echo '&nbsp;&nbsp;&nbsp;<span id="msg_error4" style="display:none;color:red"></span>';
                    echo '<div id="option4" style="padding:4px; margin:5px; border:1px dotted; display:none;">';

                    echo '<table border="0" style="font-weight:normal">';
                    echo '<tr>';
                    echo '<td>'.get_lang('DescriptionOfTheTask').'</td>';
                    echo '</tr>';
                    echo '<tr>';
                    echo '<td>'.api_disp_html_area('task', $row['task'], '', '', null, array('ToolbarSet' => 'wiki_task', 'Width' => '585', 'Height' => '200')).'</td>';
                    echo '</tr>';
                    echo '</table>';
                    echo '</div>';

                    //feedback
                    echo '<div>&nbsp;</div><input type="checkbox" value="1" name="checkfeedback" onclick="javascript: if(this.checked){document.getElementById(\'option2\').style.display=\'block\';}else{document.getElementById(\'option2\').style.display=\'none\';}"/>&nbsp;'.get_lang('AddFeedback').'';
                    echo '&nbsp;&nbsp;&nbsp;<span id="msg_error2" style="display:none;color:red"></span>';
                    echo '<div id="option2" style="padding:4px; margin:5px; border:1px dotted; display:none;">';

                    echo '<table border="0" style="font-weight:normal" align="center">';
                    echo '<tr>';
                    echo '<td colspan="2">'.get_lang('Feedback1').'</td>';
                    echo '<td colspan="2">'.get_lang('Feedback2').'</td>';
                    echo '<td colspan="2">'.get_lang('Feedback3').'</td>';
                    echo '</tr>';
                    echo '<tr>';
                    echo '<td colspan="2"><textarea name="feedback1" cols="21" rows="4" >'.api_htmlentities($row['feedback1']).'</textarea></td>';
                    echo '<td colspan="2"><textarea name="feedback2" cols="21" rows="4" >'.api_htmlentities($row['feedback2']).'</textarea></td>';
                    echo '<td colspan="2"><textarea name="feedback3" cols="21" rows="4" >'.api_htmlentities($row['feedback3']).'</textarea></td>';
                    echo '</tr>';
                    echo '<tr>';
                    echo '<td>'.get_lang('FProgress').':</td>';
                    echo '<td><select name="fprogress1">';
                     echo '<option value="'.api_htmlentities($row['fprogress1']).'" selected>'.api_htmlentities($row['fprogress1']).'</option>';
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
                     echo '<option value="'.api_htmlentities($row['fprogress2']).'" selected>'.api_htmlentities($row['fprogress2']).'</option>';;
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
                    echo '<option value="'.api_htmlentities($row['fprogress3']).'" selected>'.api_htmlentities($row['fprogress3']).'</option>';
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
                    echo  '<div>&nbsp;</div><input type="checkbox" value="1" name="checktimelimit" onclick="javascript: if(this.checked){document.getElementById(\'option1\').style.display=\'block\'; $pepe=\'a\';}else{document.getElementById(\'option1\').style.display=\'none\';}"/>&nbsp;'.get_lang('PutATimeLimit').'';
                    echo  '&nbsp;&nbsp;&nbsp;<span id="msg_error1" style="display:none;color:red"></span>';
                    echo  '<div id="option1" style="padding:4px; margin:5px; border:1px dotted; display:none;">';
                    echo '<table width="100%" border="0" style="font-weight:normal">';
                    echo '<tr>';
                    echo '<td align="right" width="150">'.get_lang('StartDate').':</td>';
                    echo '<td>';
                    if ($row['startdate_assig']=='0000-00-00 00:00:00') {
                        echo draw_date_picker('startdate_assig').' <input type="checkbox" name="initstartdate" value="1"> '.get_lang('Yes').'/'.get_lang('No').'';

                    } else {
                        echo draw_date_picker('startdate_assig', $row['startdate_assig']).' <input type="checkbox" name="initstartdate" value="1">'.get_lang('Yes').'/'.get_lang('No').'';
                    }
                    echo '</td>';
                    echo '</tr>';
                    echo '<tr>';
                    echo '<td align="right" width="150">'.get_lang("EndDate").':</td>';
                    echo '<td>';
                    if ($row['enddate_assig']=='0000-00-00 00:00:00') {
                        echo draw_date_picker('enddate_assig').' <input type="checkbox" name="initenddate" value="1"> '.get_lang('Yes').'/'.get_lang('No').'';
                    } else {
                        echo draw_date_picker('enddate_assig', $row['enddate_assig']).' <input type="checkbox" name="initenddate" value="1"> '.get_lang('Yes').'/'.get_lang('No').'';
                    }
                    echo '</td>';
                    echo '</tr>';
                    echo '<tr>';
                    echo '<td align="right">'.get_lang('AllowLaterSends').':</td>';
                    if ($row['delayedsubmit']==1) {
                        $check_uncheck='checked';
                    }
                    echo '<td><input type="checkbox" name="delayedsubmit" value="1" '.$check_uncheck.'></td>';
                    echo '</tr>';
                    echo'</table>';
                    echo '</div>';

                    //other limit
                    echo '<div>&nbsp;</div><input type="checkbox" value="1" name="checkotherlimit" onclick="javascript: if(this.checked){document.getElementById(\'option3\').style.display=\'block\';}else{document.getElementById(\'option3\').style.display=\'none\';}"/>&nbsp;'.get_lang('OtherSettings').'';
                    echo '&nbsp;&nbsp;&nbsp;<span id="msg_error3" style="display:none;color:red"></span>';
                    echo '<div id="option3" style="padding:4px; margin:5px; border:1px dotted; display:none;">';
                    echo '<div style="font-weight:normal"; align="center">'.get_lang('NMaxWords').':&nbsp;<input type="text" name="max_text" size="3" value="'.$row['max_text'].'">&nbsp;&nbsp;'.get_lang('NMaxVersion').':&nbsp;<input type="text" name="max_version" size="3" value="'.$row['max_version'].'"></div>';
                    echo '</div>';

                    //
                    echo '</div>';
                }

                echo '</div>';
                echo '<div id="wikicontent">';

                echo '<input type="hidden" name="page_id" value="'.$page_id.'">';
                echo '<input type="hidden" name="reflink" value="'.api_htmlentities($page).'">';
                echo '<input type="hidden" name="title" value="'.api_htmlentities($title).'">';

                api_disp_html_area('content', $content, '', '', null, api_is_allowed_to_edit(null,true)
                    ? array('ToolbarSet' => 'Wiki', 'Width' => '100%', 'Height' => '400')
                    : array('ToolbarSet' => 'WikiStudent', 'Width' => '100%', 'Height' => '400', 'UserStatus' => 'student')
                );
                echo '<br/>';
                echo '<br/>';
                //if(api_is_allowed_to_edit() || api_is_platform_admin()) //off for now
                //{
                echo get_lang('Comments').':&nbsp;&nbsp;<input type="text" name="comment" size="40">&nbsp;&nbsp;&nbsp;';
                //}
                echo '<INPUT TYPE="hidden" NAME="assignment" VALUE="'.$row['assignment'].'"/>';
                echo '<INPUT TYPE="hidden" NAME="version" VALUE="'.$row['version'].'"/>';

                //hack date for edit
                echo '<INPUT TYPE="hidden" NAME="startdate_assig" VALUE="'.$row['startdate_assig'].'"/>';
                echo '<INPUT TYPE="hidden" NAME="enddate_assig" VALUE="'.$row['enddate_assig'].'"/>';

                //
                echo get_lang('Progress').':&nbsp;&nbsp;<select name="progress" id="progress">';
                echo '<option value="'.api_htmlentities($row['progress']).'" selected>'.api_htmlentities($row['progress']).'</option>';
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
                echo '<button class="save" type="submit" name="SaveWikiChange">'.get_lang('Save').'</button>';//for save button Don't change name (see fckeditor/editor/plugins/customizations/fckplugin_compressed.js and fckplugin.js
                echo '</div>';
                echo '</form>';
            }
        }
    }
}

// Page history

if ($_GET['action']=='history' or $_POST['HistoryDifferences']) {
    if (!$_GET['title']) {
        Display::display_error_message(get_lang("MustSelectPage"));
        exit;
    }

    echo '<div style="overflow:hidden">';
    $_clean['group_id']=(int)$_SESSION['_gid'];

    //First, see the property visibility that is at the last register and therefore we should select descending order. But to give ownership to each record, this is no longer necessary except for the title. TODO: check this

    $sql='SELECT * FROM '.$tbl_wiki.'WHERE c_id = '.$course_id.' AND reflink="'.Database::escape_string($page).'" AND '.$groupfilter.$condition_session.' ORDER BY id DESC';
    $result=Database::query($sql);

    while ($row=Database::fetch_array($result)) {
        $KeyVisibility=$row['visibility'];
        $KeyAssignment=$row['assignment'];
        $KeyTitle=$row['title'];
        $KeyUserId=$row['user_id'];
    }

    if ($KeyAssignment==1) {
        $icon_assignment=Display::return_icon('wiki_assignment.png', get_lang('AssignmentDescExtra'),'',22);
    } elseif($KeyAssignment==2) {
        $icon_assignment=Display::return_icon('wiki_work.png', get_lang('AssignmentWorkExtra'),'',22);
    }


    //Second, show

    //if the page is hidden and is a job only sees its author and professor
    if($KeyVisibility==1 || api_is_allowed_to_edit(false,true) || api_is_platform_admin() || ($KeyAssignment==2 && $KeyVisibility==0 && (api_get_user_id()==$KeyUserId))) {
        // We show the complete history
        if (!$_POST['HistoryDifferences'] && !$_POST['HistoryDifferences2']) {

            $sql='SELECT * FROM '.$tbl_wiki.'WHERE c_id = '.$course_id.' AND reflink="'.Database::escape_string($page).'" AND '.$groupfilter.$condition_session.' ORDER BY id DESC';
            $result=Database::query($sql);

            $title		= $_GET['title'];
            $group_id	= $_GET['group_id'];

            echo '<div id="wikititle">';
            echo $icon_assignment.'&nbsp;&nbsp;&nbsp;'.api_htmlentities($KeyTitle);
            echo '</div>';
            echo '<div id="wikicontent">';
            echo '<form id="differences" method="POST" action="index.php?cidReq='.$_course['id'].'&action=history&title='.api_htmlentities(urlencode($title)).'&session_id='.api_htmlentities($session_id).'&group_id='.api_htmlentities($group_id).'">';

            echo '<ul style="list-style-type: none;">';
            echo '<br/>';
            echo '<button class="search" type="submit" name="HistoryDifferences" value="HistoryDifferences">'.get_lang('ShowDifferences').' '.get_lang('LinesDiff').'</button>';
            echo '<button class="search" type="submit" name="HistoryDifferences2" value="HistoryDifferences2">'.get_lang('ShowDifferences').' '.get_lang('WordsDiff').'</button>';
            echo '<br/><br/>';

            $counter=0;
            $total_versions=Database::num_rows($result);

            while ($row=Database::fetch_array($result)) {
                $userinfo=Database::get_user_info_from_id($row['user_id']);

                echo '<li style="margin-bottom: 5px;">';
                ($counter==0) ? $oldstyle='style="visibility: hidden;"':$oldstyle='';
                ($counter==0) ? $newchecked=' checked':$newchecked='';
                ($counter==$total_versions-1) ? $newstyle='style="visibility: hidden;"':$newstyle='';
                ($counter==1) ? $oldchecked=' checked':$oldchecked='';
                echo '<input name="old" value="'.$row['id'].'" type="radio" '.$oldstyle.' '.$oldchecked.'/> ';
                echo '<input name="new" value="'.$row['id'].'" type="radio" '.$newstyle.' '.$newchecked.'/> ';
                echo '<a href="'.api_get_self().'?action=showpage&amp;title='.api_htmlentities(urlencode($page)).'&amp;view='.$row['id'].'">';
                echo '<a href="'.api_get_self().'?cidReq='.$_course['id'].'&action=showpage&amp;title='.api_htmlentities(urlencode($page)).'&amp;view='.$row['id'].'&session_id='.$session_id.'&group_id='.$group_id.'">';

                echo api_get_local_time($row['dtime'], null, date_default_timezone_get());
                echo '</a>';
                echo ' ('.get_lang('Version').' '.$row['version'].')';
                echo ' '.get_lang('By').' ';
                if ($row['user_id']<>0) {
                    echo '<a href="../user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.api_htmlentities(api_get_person_name($userinfo['firstname'], $userinfo['lastname'])).'</a>';
                } else {
                    echo get_lang('Anonymous').' ('.api_htmlentities($row[user_ip]).')';
                }

                echo ' ( '.get_lang('Progress').': '.api_htmlentities($row['progress']).'%, ';
                $comment=$row['comment'];

                if (!empty($comment)) {
                    echo get_lang('Comments').': '.api_htmlentities(api_substr($row['comment'],0,100));
                    if (api_strlen($row['comment'])>100) {
                        echo '... ';
                    }
                } else {
                    echo get_lang('Comments').':  ---';
                }
                echo ' ) </li>';

                $counter++;
            } //end while
            echo '<br/>';
            echo '<button class="search" type="submit" name="HistoryDifferences" value="HistoryDifferences">'.get_lang('ShowDifferences').' '.get_lang('LinesDiff').'</button>';
            echo '<button class="search" type="submit" name="HistoryDifferences2" value="HistoryDifferences2">'.get_lang('ShowDifferences').' '.get_lang('WordsDiff').'</button>';
            echo '</ul></form></div>';
        } else { // We show the differences between two versions
            $sql_old="SELECT * FROM $tbl_wiki WHERE c_id = $course_id AND id='".Database::escape_string($_POST['old'])."'";
            $result_old=Database::query($sql_old);
            $version_old=Database::fetch_array($result_old);


            $sql_new="SELECT * FROM $tbl_wiki WHERE c_id = $course_id AND id='".Database::escape_string($_POST['new'])."'";
            $result_new=Database::query($sql_new);
            $version_new=Database::fetch_array($result_new);

            if (isset($_POST['HistoryDifferences'])) {
                include('diff.inc.php');
                //title
                echo '<div id="wikititle">'.api_htmlentities($version_new['title']).' <font size="-2"><i>('.get_lang('DifferencesNew').'</i> <font style="background-color:#aaaaaa">'.$version_new['dtime'].'</font> <i>'.get_lang('DifferencesOld').'</i> <font style="background-color:#aaaaaa">'.$version_old['dtime'].'</font>) '.get_lang('Legend').':  <span class="diffAdded" >'.get_lang(WikiDiffAddedLine).'</span> <span class="diffDeleted" >'.get_lang(WikiDiffDeletedLine).'</span> <span class="diffMoved" >'.get_lang(WikiDiffMovedLine).'</span></font></div>';
            }
            if (isset($_POST['HistoryDifferences2'])) {
                // including global PEAR diff libraries
                require_once 'Text/Diff.php';
                require_once 'Text/Diff/Renderer/inline.php';
                //title
                echo '<div id="wikititle">'.api_htmlentities($version_new['title']).' <font size="-2"><i>('.get_lang('DifferencesNew').'</i> <font style="background-color:#aaaaaa">'.$version_new['dtime'].'</font> <i>'.get_lang('DifferencesOld').'</i> <font style="background-color:#aaaaaa">'.$version_old['dtime'].'</font>) '.get_lang('Legend').':  <span class="diffAddedTex" >'.get_lang(WikiDiffAddedTex).'</span> <span class="diffDeletedTex" >'.get_lang(WikiDiffDeletedTex).'</span></font></div>';
            }

            echo '<div class="diff"><br /><br />';

            if (isset($_POST['HistoryDifferences'])) {
                echo '<table>'.diff( $version_old['content'], $version_new['content'], true, 'format_table_line' ).'</table>'; // format_line mode is better for words
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

            if (isset($_POST['HistoryDifferences2'])) {
                $lines1 = array(strip_tags($version_old['content'])); //without <> tags
                $lines2 = array(strip_tags($version_new['content'])); //without <> tags
                $diff = new Text_Diff($lines1, $lines2);
                $renderer = new Text_Diff_Renderer_inline();
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


// Recent changes 

// @todo rss feed

if ($_GET['action']=='recentchanges') {
    $_clean['group_id']=(int)$_SESSION['_gid'];

    if ( api_is_allowed_to_session_edit(false,true) ) {
        if (check_notify_all()==1) {
            $notify_all= Display::return_icon('messagebox_info.png', get_lang('NotifyByEmail'),'',22).' '.get_lang('NotNotifyChanges');
            $lock_unlock_notify_all='unlocknotifyall';
        } else {
            $notify_all=Display::return_icon('mail.png', get_lang('CancelNotifyByEmail'),'',22).' '.get_lang('NotifyChanges');
            $lock_unlock_notify_all='locknotifyall';
        }

    }

    echo '<div class="actions"><span style="float: right;">';
    echo '<a href="index.php?action=recentchanges&amp;actionpage='.$lock_unlock_notify_all.'&amp;title='.api_htmlentities(urlencode($page)).'">'.$notify_all.'</a>';
    echo '</span>'.get_lang('RecentChanges').'</div>';



    if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) { //only by professors if page is hidden
        $sql = 'SELECT * FROM '.$tbl_wiki.', '.$tbl_wiki_conf.' 
        		WHERE 	'.$tbl_wiki_conf.'.c_id= '.$course_id.' AND
        				'.$tbl_wiki.'.c_id= '.$course_id.' AND
        				'.$tbl_wiki_conf.'.page_id='.$tbl_wiki.'.page_id AND 
        				'.$tbl_wiki.'.'.$groupfilter.$condition_session.' 
        		ORDER BY dtime DESC'; // new version
    } else {
        $sql='SELECT * FROM '.$tbl_wiki.' WHERE c_id = '.$course_id.' AND '.$groupfilter.$condition_session.' AND visibility=1 ORDER BY dtime DESC';	// old version TODO: Replace by the bottom line
    }

    $allpages=Database::query($sql);

    //show table
    if (Database::num_rows($allpages) > 0) {
        $row = array ();
        while ($obj = Database::fetch_object($allpages)) {
            //get author
            $userinfo=Database::get_user_info_from_id($obj->user_id);

            //get type assignment icon
            if ($obj->assignment==1) {
                $ShowAssignment=Display::return_icon('wiki_assignment.png', get_lang('AssignmentDesc'),'',22);
            } elseif ($obj->assignment==2) {
                $ShowAssignment=Display::return_icon('wiki_work.png', get_lang('AssignmentWork'),'',22);
            } elseif ($obj->assignment==0) {
                $ShowAssignment='<img src="../img/px_transparent.gif" />';
            }

            //get icon task
            if (!empty($obj->task)) {
                $icon_task=Display::return_icon('wiki_task.png', get_lang('StandardTask'),'',22);
            } else {
                $icon_task='<img src="../img/px_transparent.gif" />';
            }


            $row = array ();
            $row[] = api_get_local_time($obj->dtime, null, date_default_timezone_get());
            $row[] = $ShowAssignment.$icon_task;
            $row[] = '<a href="'.api_get_self().'?cidReq='.$_course['id'].'&action=showpage&title='.api_htmlentities(urlencode($obj->reflink)).'&amp;view='.$obj->id.'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'">'.api_htmlentities($obj->title).'</a>';
            $row[] = $obj->version>1 ? get_lang('EditedBy') : get_lang('AddedBy');
            $row[] = $obj->user_id <> 0 ? '<a href="../user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.api_htmlentities(api_get_person_name($userinfo['firstname'], $userinfo['lastname'])).'</a>' : get_lang('Anonymous').' ('.api_htmlentities($obj->user_ip).')';
            $rows[] = $row;
        }

        $table = new SortableTableFromArrayConfig($rows,0,10,'RecentPages_table','','','DESC');
        $table->set_additional_parameters(array('cidReq' =>Security::remove_XSS($_GET['cidReq']),'action'=>Security::remove_XSS($_GET['action']),'session_id'=>Security::remove_XSS($_GET['session_id']),'group_id'=>Security::remove_XSS($_GET['group_id'])));
        $table->set_header(0,get_lang('Date'), true, array ('style' => 'width:200px;'));
        $table->set_header(1,get_lang('Type'), true, array ('style' => 'width:30px;'));
        $table->set_header(2,get_lang('Title'), true);
        $table->set_header(3,get_lang('Actions'), true, array ('style' => 'width:80px;'));
        $table->set_header(4,get_lang('Author'), true);

        $table->display();
    }
}


// All pages


if ($_GET['action']=='allpages') {
    echo '<div class="actions">'.get_lang('AllPages').'</div>';

    $_clean['group_id']=(int)$_SESSION['_gid'];

    if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) { //only by professors if page is hidden 
        $sql = 'SELECT  *  FROM  '.$tbl_wiki.' s1 
        		WHERE s1.c_id = '.$course_id.' AND id=(
        					SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2 
        					WHERE s2.c_id = '.$course_id.' AND s1.reflink = s2.reflink AND '.$groupfilter.' AND session_id='.$session_id.')'; // warning don't use group by reflink because does not return the last version

    } else {        
		$sql = 'SELECT  *  FROM   '.$tbl_wiki.' s1 
				WHERE visibility=1 AND s1.c_id = '.$course_id.' AND id=(
						SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2 WHERE s2.c_id = '.$course_id.' AND s1.reflink = s2.reflink AND '.$groupfilter.' AND session_id='.$session_id.')'; // warning don't use group by reflink because does not return the last version
    }
	
    $allpages=Database::query($sql);

    //show table
    if (Database::num_rows($allpages) > 0) {
        $row = array ();
        while ($obj = Database::fetch_object($allpages)) { 
            //get author
            $userinfo=Database::get_user_info_from_id($obj->user_id);

            //get type assignment icon
            if ($obj->assignment==1) {
                $ShowAssignment=Display::return_icon('wiki_assignment.png', get_lang('AssignmentDesc'),'',22);
            } elseif ($obj->assignment==2) {
                $ShowAssignment=Display::return_icon('wiki_work.png', get_lang('AssignmentWork'),'',22);
            } elseif ($obj->assignment==0) {
                $ShowAssignment='<img src="../img/px_transparent.gif" />';
            }

            //get icon task
            if (!empty($obj->task)) {
                $icon_task=Display::return_icon('wiki_task.png', get_lang('StandardTask'),'',22);
            } else {
                $icon_task='<img src="../img/px_transparent.gif" />';
            }

            $row = array ();
            $row[] =$ShowAssignment.$icon_task;
            $row[] = '<a href="'.api_get_self().'?cidReq='.$_course['id'].'&action=showpage&title='.api_htmlentities(urlencode($obj->reflink)).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'">'.api_htmlentities($obj->title).'</a>';
            $row[] = $obj->user_id <>0 ? '<a href="../user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.api_htmlentities(api_get_person_name($userinfo['firstname'], $userinfo['lastname'])).'</a>' : get_lang('Anonymous').' ('.api_htmlentities($obj->user_ip).')';
            $row[] = api_get_local_time($obj->dtime, null, date_default_timezone_get());

            if (api_is_allowed_to_edit(false,true)|| api_is_platform_admin()) {
                $showdelete=' <a href="'.api_get_self().'?cidReq='.$_course['id'].'&action=delete&title='.api_htmlentities(urlencode($obj->reflink)).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'">'.Display::return_icon('delete.png', get_lang('Delete'),'',22);
            }
            if (api_is_allowed_to_session_edit(false,true) ) {
                $row[] = '<a href="'.api_get_self().'?cidReq='.$_course['id'].'&action=edit&title='.api_htmlentities(urlencode($obj->reflink)).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'">'.Display::return_icon('edit.png', get_lang('EditPage'),'',22).'</a> <a href="'.api_get_self().'?cidReq='.$_course['id'].'&action=discuss&title='.api_htmlentities(urlencode($obj->reflink)).'&group_id='.api_htmlentities($_GET['group_id']).'">'.Display::return_icon('discuss.png', get_lang('Discuss'),'',22).'</a> <a href="'.api_get_self().'?cidReq='.$_course['id'].'&action=history&title='.api_htmlentities(urlencode($obj->reflink)).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'">'.Display::return_icon('history.png', get_lang('History'),'',22).'</a> <a href="'.api_get_self().'?cidReq='.$_course['id'].'&action=links&title='.api_htmlentities(urlencode($obj->reflink)).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'">'.Display::return_icon('what_link_here.png', get_lang('LinksPages'),'',22).'</a>'.$showdelete;
            }
            $rows[] = $row;
        }
		
        $table = new SortableTableFromArrayConfig($rows,1,10,'AllPages_table','','','ASC');
        $table->set_additional_parameters(array('cidReq' =>Security::remove_XSS($_GET['cidReq']),'action'=>Security::remove_XSS($_GET['action']),'group_id'=>Security::remove_XSS($_GET['group_id'])));
        $table->set_header(0,get_lang('Type'), true, array ('style' => 'width:30px;'));
        $table->set_header(1,get_lang('Title'), true);
        $table->set_header(2,get_lang('Author').' ('.get_lang('LastVersion').')', true);
        $table->set_header(3,get_lang('Date').' ('.get_lang('LastVersion').')', true);
        if (api_is_allowed_to_session_edit(false,true) ) {
            $table->set_header(4,get_lang('Actions'), true, array ('style' => 'width:130px;'));
        }
        $table->display();
    }
}

// Discuss pages 

if ($_GET['action']=='discuss') {
    if (api_get_session_id()!=0 && api_is_allowed_to_session_edit(false,true)==false) {
        api_not_allowed();
    }

    if (!$_GET['title']) {
        Display::display_error_message(get_lang("MustSelectPage"));
        exit;
    }

    //first extract the date of last version
    $sql='SELECT * FROM '.$tbl_wiki.' WHERE c_id = '.$course_id.' AND reflink="'.Database::escape_string($page).'" AND '.$groupfilter.$condition_session.' ORDER BY id DESC';
    $result=Database::query($sql);
    $row=Database::fetch_array($result);
    $lastversiondate=api_get_local_time($row['dtime'], null, date_default_timezone_get());
    $lastuserinfo=Database::get_user_info_from_id($row['user_id']);

    //select page to discuss
    $sql='SELECT * FROM '.$tbl_wiki.' WHERE c_id = '.$course_id.' AND reflink="'.Database::escape_string($page).'" AND '.$groupfilter.$condition_session.' ORDER BY id ASC';
    $result=Database::query($sql);
    $row=Database::fetch_array($result);
    $id=$row['id'];
    $firstuserid=$row['user_id'];

    //mode assignment: previous to show  page type
    if ($row['assignment']==1) {
        $icon_assignment=Display::return_icon('wiki_assignment.png', get_lang('AssignmentDescExtra'),'',22);
    } elseif($row['assignment']==2) {
        $icon_assignment=Display::return_icon('wiki_work.png', get_lang('AssignmentWorkExtra'),'',22);
    }


    //Show title and form to discuss if page exist
    if ($id!='') {
        //Show discussion to students if isn't hidden. Show page to all teachers if is hidden. Mode assignments: If is hidden, show pages to student only if student is the author
        if ($row['visibility_disc']==1 || api_is_allowed_to_edit(false,true) || api_is_platform_admin() || ($row['assignment']==2 && $row['visibility_disc']==0 && (api_get_user_id()==$row['user_id']))) {
            echo '<div id="wikititle">';

            // discussion action: protecting (locking) the discussion
            if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
                if (check_addlock_discuss()==1) {
                    $addlock_disc= Display::return_icon('unlock.png', get_lang('UnlockDiscussExtra'),'',22);
                    $lock_unlock_disc='unlockdisc';
                } else {
                    $addlock_disc= Display::return_icon('lock.png', get_lang('LockDiscussExtra'),'',22);
                    $lock_unlock_disc='lockdisc';
                }
            }
            echo '<span style="float:right">';
            echo '<a href="index.php?action=discuss&amp;actionpage='.$lock_unlock_disc.'&amp;title='.api_htmlentities(urlencode($page)).'">'.$addlock_disc.'</a>';
            echo '</span>';

            // discussion action: visibility.  Show discussion to students if isn't hidden. Show page to all teachers if is hidden.


            if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
                if (check_visibility_discuss()==1) {
                    /// TODO: 	Fix Mode assignments: If is hidden, show discussion to student only if student is the author
                    //if(($row['assignment']==2 && $row['visibility_disc']==0 && (api_get_user_id()==$row['user_id']))==false)
                    //{
                        //$visibility_disc= '<img src="../img/wiki/invisible.gif" title="'.get_lang('HideDiscussExtra').'" alt="'.get_lang('HideDiscussExtra').'" />';

                    //}
                    $visibility_disc= Display::return_icon('visible.png', get_lang('ShowDiscussExtra'),'',22);
                    $hide_show_disc='hidedisc';
                } else {
                    $visibility_disc= Display::return_icon('invisible.png', get_lang('HideDiscussExtra'),'',22);
                    $hide_show_disc='showdisc';
                }
            }
            echo '<span style="float:right">';
            echo '<a href="index.php?action=discuss&amp;actionpage='.$hide_show_disc.'&amp;title='.api_htmlentities(urlencode($page)).'">'.$visibility_disc.'</a>';
            echo '</span>';

            //discussion action: check add rating lock. Show/Hide list to rating for all student

            if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
                if (check_ratinglock_discuss()==1) {
                    $ratinglock_disc= Display::return_icon('star.png', get_lang('UnlockRatingDiscussExtra'),'',22);
                    $lock_unlock_rating_disc='unlockrating';
                } else {
                    $ratinglock_disc= Display::return_icon('star_na.png', get_lang('LockRatingDiscussExtra'),'',22);
                    $lock_unlock_rating_disc='lockrating';
                }
            }

            echo '<span style="float:right">';
            echo '<a href="index.php?action=discuss&amp;actionpage='.$lock_unlock_rating_disc.'&amp;title='.api_htmlentities(urlencode($page)).'">'.$ratinglock_disc.'</a>';
            echo '</span>';

            //discussion action: email notification
            if (check_notify_discuss($page)==1) {
                $notify_disc= Display::return_icon('messagebox_info.png', get_lang('NotifyDiscussByEmail'),'',22);
                $lock_unlock_notify_disc='unlocknotifydisc';
            } else {
                $notify_disc= Display::return_icon('mail.png', get_lang('CancelNotifyDiscussByEmail'),'',22);
                $lock_unlock_notify_disc='locknotifydisc';
            }
            echo '<span style="float:right">';
            echo '<a href="index.php?action=discuss&amp;actionpage='.$lock_unlock_notify_disc.'&amp;title='.api_htmlentities(urlencode($page)).'">'.$notify_disc.'</a>';
            echo '</span>';

            echo $icon_assignment.'&nbsp;&nbsp;&nbsp;'.api_htmlentities($row['title']);

            echo ' ('.get_lang('MostRecentVersionBy').' <a href="../user/userInfo.php?uInfo='.$lastuserinfo['user_id'].'">'.api_htmlentities(api_get_person_name($lastuserinfo['firstname'], $lastuserinfo['lastname'])).'</a> '.$lastversiondate.$countWPost.')'.$avg_WPost_score.' '; //TODO: read average score

            echo '</div>';

            if ($row['addlock_disc']==1 || api_is_allowed_to_edit(false,true) || api_is_platform_admin()) { //show comments but students can't add theirs
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
                    if ($row['ratinglock_disc']==1 || api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
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
                    } else {
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
                if (isset($_POST['Submit']) && double_post($_POST['wpost_id'])) {
                    $dtime = date( "Y-m-d H:i:s" );
                    $message_author=api_get_user_id();

                    $sql="INSERT INTO $tbl_wiki_discuss (c_id, publication_id, userc_id, comment, p_score, dtime) VALUES 
                    	($course_id, '".$id."','".$message_author."','".Database::escape_string($_POST['comment'])."','".Database::escape_string($_POST['rating'])."','".$dtime."')";
                    $result=Database::query($sql) or die(Database::error());

                    check_emailcue($id, 'D', $dtime, $message_author);

                }
            }//end discuss lock

            echo '<hr noshade size="1">';
            $user_table = Database :: get_main_table(TABLE_MAIN_USER);

            $sql="SELECT * FROM $tbl_wiki_discuss reviews, $user_table user  
                  WHERE reviews.c_id = $course_id AND reviews.publication_id='".$id."' AND user.user_id='".$firstuserid."' ORDER BY id DESC";
            $result=Database::query($sql) or die(Database::error());

            $countWPost = Database::num_rows($result);
            echo get_lang('NumComments').": ".$countWPost; //comment's numbers

            $sql="SELECT SUM(p_score) as sumWPost FROM $tbl_wiki_discuss WHERE c_id = $course_id AND publication_id = '".$id."' AND NOT p_score='-' ORDER BY id DESC";
            $result2=Database::query($sql) or die(Database::error());
            $row2=Database::fetch_array($result2);

            $sql="SELECT * FROM $tbl_wiki_discuss WHERE c_id = $course_id AND publication_id='".$id."' AND NOT p_score='-'";
            $result3=Database::query($sql) or die(Database::error());
            $countWPost_score= Database::num_rows($result3);

            echo ' - '.get_lang('NumCommentsScore').': '.$countWPost_score;//

            if ($countWPost_score!=0) {
                $avg_WPost_score = round($row2['sumWPost'] / $countWPost_score,2).' / 10';
            } else {
                $avg_WPost_score = $countWPost_score;
            }

            echo ' - '.get_lang('RatingMedia').': '.$avg_WPost_score; // average rating

            $sql = 'UPDATE '.$tbl_wiki.' SET score="'.Database::escape_string($avg_WPost_score).'" 
                    WHERE c_id = '.$course_id.' AND reflink="'.Database::escape_string($page).'" AND '.$groupfilter.$condition_session;	
            // check if work ok. TODO:
            Database::query($sql);

            echo '<hr noshade size="1">';
            //echo '<div style="overflow:auto; height:170px;">';

            while ($row=Database::fetch_array($result)) {
                $userinfo=Database::get_user_info_from_id($row['userc_id']);
                if (($userinfo['status'])=="5") {
                    $author_status=get_lang('Student');
                } else {
                    $author_status=get_lang('Teacher');
                }
                
                $user_id=$row['userc_id'];
                $name = api_get_person_name($userinfo['firstname'], $userinfo['lastname']);
                $attrb=array();
                if ($user_id<>0) {
                    $image_path = UserManager::get_user_picture_path_by_id($user_id,'web',false, true);
                    $image_repository = $image_path['dir'];
                    $existing_image = $image_path['file'];
                    $author_photo= '<img src="'.$image_repository.$existing_image.'" alt="'.api_htmlentities($name).'"  width="40" height="50" align="top" title="'.api_htmlentities($name).'"  />';
                } else {
                    $author_photo= '<img src="'.api_get_path(WEB_CODE_PATH)."img/unknown.jpg".'" alt="'.api_htmlentities($name).'"  width="40" height="50" align="top"  title="'.api_htmlentities($name).'"  />';
                }

                //stars
                $p_score=$row['p_score'];
                switch ($p_score) {
                    case  0:
                    $imagerating='<img src="../img/rating/stars_0.gif"/>';
                    break;
                    case  1:
                    $imagerating='<img src="../img/rating/stars_5.gif"/>';
                    break;
                    case  2:
                    $imagerating='<img src="../img/rating/stars_10.gif"/>';
                    break;
                    case  3:
                    $imagerating='<img src="../img/rating/stars_15.gif"/>';
                    break;
                    case  4:
                    $imagerating='<img src="../img/rating/stars_20.gif"/>';
                    break;
                    case  5:
                    $imagerating='<img src="../img/rating/stars_25.gif"/>';
                    break;
                    case  6:
                    $imagerating='<img src="../img/rating/stars_30.gif"/>';
                    break;
                    case  7:
                    $imagerating='<img src="../img/rating/stars_35.gif"/>';
                    break;
                    case  8:
                    $imagerating='<img src="../img/rating/stars_40.gif"/>';
                    break;
                    case  9:
                    $imagerating='<img src="../img/rating/stars_45.gif"/>';
                    break;
                    case  10:
                    $imagerating='<img src="../img/rating/stars_50.gif"/>';
                    break;
                }

                echo '<p><table>';
                echo '<tr>';
                echo '<td rowspan="2">'.$author_photo.'</td>';
                echo '<td style=" color:#999999"><a href="../user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.api_htmlentities(api_get_person_name($userinfo['firstname'], $userinfo['lastname'])).'</a> ('.$author_status.') '.api_get_local_time($row['dtime'], null, date_default_timezone_get()).' - '.get_lang('Rating').': '.$row['p_score'].' '.$imagerating.' </td>';
                echo '</tr>';
                echo '<tr>';
                echo '<td>'.api_htmlentities($row['comment']).'</td>';
                echo '</tr>';
                echo "</table>";
                echo '<hr noshade size="1">';
    
                }
            } else {
            Display::display_warning_message(get_lang('LockByTeacher'),false);
        }
    } else {
            Display::display_normal_message(get_lang('DiscussNotAvailable'));
    }
}
echo "</div>"; // echo "<div id='mainwiki'>";
echo "</div>"; // echo "<div id='wikiwrapper'>";

/*
FOOTER
*/
Display::display_footer();
