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
$newtitle = null;

// including the global initialization file
require_once '../inc/global.inc.php';
require_once 'wiki.inc.php';

// Database table definition
$tbl_wiki           = Database::get_course_table(TABLE_WIKI);
$tbl_wiki_discuss   = Database::get_course_table(TABLE_WIKI_DISCUSS);
$tbl_wiki_mailcue   = Database::get_course_table(TABLE_WIKI_MAILCUE);
$tbl_wiki_conf      = Database::get_course_table(TABLE_WIKI_CONF);

global $charset;

$wiki = new Wiki();
$wiki->charset = $charset;

// section (for the tabs)
$this_section = SECTION_COURSES;
$current_course_tool  = TOOL_WIKI;
//require_once api_get_path(LIBRARY_PATH).'mail.lib.inc.php';

$course_id = api_get_course_int_id();
// additional style information
$htmlHeadXtra[] ='<link rel="stylesheet" type="text/css" href="'.api_get_path(WEB_CODE_PATH).'wiki/css/default.css"/>';

// javascript for advanced parameters menu
$htmlHeadXtra[] = '<script>
function advanced_parameters() {
    if (document.getElementById(\'options\').style.display == \'none\') {
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

/* Constants and variables */
$tool_name = get_lang('ToolWiki');
$MonthsLong = array(
    get_lang("JanuaryLong"),
    get_lang("FebruaryLong"),
    get_lang("MarchLong"),
    get_lang("AprilLong"),
    get_lang("MayLong"),
    get_lang("JuneLong"),
    get_lang("JulyLong"),
    get_lang("AugustLong"),
    get_lang("SeptemberLong"),
    get_lang("OctoberLong"),
    get_lang("NovemberLong"),
    get_lang("DecemberLong")
);

//condition for the session
$session_id = api_get_session_id();
$condition_session = api_get_session_condition($session_id);
$course_id = api_get_course_int_id();

/* ACCESS */
api_protect_course_script();
api_block_anonymous_users();

/* TRACKING */
event_access_tool(TOOL_WIKI);

/* HEADER & TITLE */
// If it is a group wiki then the breadcrumbs will be different.

// Setting variable
$groupId = api_get_group_id();

if ($groupId) {
    $group_properties = GroupManager::get_group_properties($groupId);
    $interbreadcrumb[] = array("url" => "../group/group.php", "name" => get_lang('Groups'));
    $interbreadcrumb[] = array("url"=>"../group/group_space.php?gidReq=".$groupId, "name"=> get_lang('GroupSpace').' '.$group_properties['name']);

    $add_group_to_title = ' '.$group_properties['name'];
    $groupfilter='group_id="'.$groupId.'"';

    //ensure this tool in groups whe it's private or deactivated
    if ($group_properties['wiki_state'] == 0) {
        api_not_allowed();
    } elseif ($group_properties['wiki_state']==2) {
        if (!api_is_allowed_to_edit(false,true) and !GroupManager :: is_user_in_group($_user['user_id'], $_SESSION['_gid'])) {
            api_not_allowed();
        }
    }
} else {
    $groupfilter='group_id=0';
}

if (isset($_POST['action']) && $_POST['action']=='export_to_pdf' && isset($_POST['wiki_id']) && api_get_setting('students_export2pdf') == 'true') {
    $wiki->export_to_pdf($_POST['wiki_id'], api_get_course_id());
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : null;
Display::display_header($tool_name, 'Wiki');
$is_allowed_to_edit = api_is_allowed_to_edit(false, true);
/* INITIALISATION */

//the page we are dealing with
if (!isset($_GET['title'])) {
    $page = 'index';
} else {
    $page = $_GET['title'];
}

$wiki->page = $page;

/* MAIN CODE */

// Tool introduction
Display::display_introduction_section(TOOL_WIKI);

/* ACTIONS */
$wiki->blockConcurrentEditions(api_get_user_id(), $action);

// Saving a change

if (isset($_POST['SaveWikiChange']) AND $_POST['title']<>'') {
    if (empty($_POST['title'])) {
        Display::display_error_message(get_lang("NoWikiPageTitle"));
    } elseif (!$wiki->double_post($_POST['wpost_id'])) {
        //double post
    } elseif ($_POST['version']!='' && $_SESSION['_version']!=0 && $_POST['version']!=$_SESSION['_version']) {
        //prevent concurrent users and double version
        Display::display_error_message(get_lang("EditedByAnotherUser"));
    } else {
        $return_message = $wiki->save_wiki();
        Display::display_confirmation_message($return_message, false);
    }
}

// Saving a new wiki entry
if (isset($_POST['SaveWikiNew'])) {
    if (empty($_POST['title'])) {
        Display::display_error_message(get_lang("NoWikiPageTitle"));
    } elseif (strtotime($wiki->get_date_from_select('startdate_assig')) > strtotime($wiki->get_date_from_select('enddate_assig'))) {
        Display::display_error_message(get_lang("EndDateCannotBeBeforeTheStartDate"));
    } elseif (!$wiki->double_post($_POST['wpost_id'])) {
        //double post
    } else {
        $_clean['assignment'] = null;
        if (isset($_POST['assignment'])) {
            // for mode assignment
            $_clean['assignment'] = Database::escape_string($_POST['assignment']);
        }

        if ($_clean['assignment'] == 1) {
            $wiki->auto_add_page_users($_clean['assignment']);
        }
        $return_message = $wiki->save_new_wiki();
        if ($return_message == false) {
            Display::display_error_message(get_lang('NoWikiPageTitle'), false);
        } else {
            Display::display_confirmation_message($return_message, false);
        }
    }
}

// check last version
if (isset($_GET['view']) && $_GET['view']) {
    $sql = 'SELECT * FROM '.$tbl_wiki.'
            WHERE
                c_id = '.$course_id.' AND
                id="'.Database::escape_string($_GET['view']).'"'; //current view
    $result=Database::query($sql);
    $current_row=Database::fetch_array($result);

    $sql = 'SELECT * FROM '.$tbl_wiki.'
            WHERE c_id = '.$course_id.' AND reflink="'.Database::escape_string($page).'" AND '.$groupfilter.$condition_session.' ORDER BY id DESC'; //last version
    $result=Database::query($sql);
    $last_row=Database::fetch_array($result);

    if ($_GET['view'] < $last_row['id']) {
       $message = '<center>'.get_lang('NoAreSeeingTheLastVersion').'<br /> '.get_lang("Version").' (<a href="index.php?cidReq='.$_course['id'].'&action=showpage&amp;title='.api_htmlentities(urlencode($current_row['reflink'])).'&group_id='.$current_row['group_id'].'&session_id='.$current_row['session_id'].'&view='.api_htmlentities($_GET['view']).'" title="'.get_lang('CurrentVersion').'">'.$current_row['version'].'</a> / <a href="index.php?cidReq='.$_course['id'].'&action=showpage&amp;title='.api_htmlentities(urlencode($last_row['reflink'])).'&group_id='.$last_row['group_id'].'&session_id='.$last_row['session_id'].'" title="'.get_lang('LastVersion').'">'.$last_row['version'].'</a>) <br />'.get_lang("ConvertToLastVersion").': <a href="index.php?cidReq='.$_course['id'].'&action=restorepage&amp;title='.api_htmlentities(urlencode($last_row['reflink'])).'&group_id='.$last_row['group_id'].'&session_id='.$last_row['session_id'].'&view='.api_htmlentities($_GET['view']).'">'.get_lang("Restore").'</a></center>';
       Display::display_warning_message($message,false);
    }

    // Restore page.

    if ($action == 'restorepage') {
        //Only teachers and platform admin can edit the index page. Only teachers and platform admin can edit an assignment teacher
        if ((
            $current_row['reflink']=='index' ||
            $current_row['reflink']=='' ||
            $current_row['assignment'] == 1
            ) &&
            (!api_is_allowed_to_edit(false,true) && intval($_GET['group_id'])==0)
        ) {
            Display::display_normal_message(get_lang('OnlyEditPagesCourseManager'));
        } else {
            $PassEdit=false;

            //check if is a wiki group
            if ($current_row['group_id'] != 0) {
				//Only teacher, platform admin and group members can edit a wiki group
				if (api_is_allowed_to_edit(false,true) || api_is_platform_admin() || GroupManager :: is_user_in_group($_user['user_id'],intval($_GET['group_id']))) {
                    $PassEdit = true;
                } else {
                    Display::display_normal_message(get_lang('OnlyEditPagesGroupMembers'));
                }
            } else {
                $PassEdit=true;
            }

            // check if is an assignment
            $icon_assignment = null;
            if ($current_row['assignment']==1) {
                Display::display_normal_message(get_lang('EditAssignmentWarning'));
                $icon_assignment=Display::return_icon('wiki_assignment.png', get_lang('AssignmentDescExtra'),'',ICON_SIZE_SMALL);
            } elseif($current_row['assignment']==2) {
                $icon_assignment=Display::return_icon('wiki_work.png', get_lang('AssignmentWorkExtra'),'',ICON_SIZE_SMALL);
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

            //show editor if edit is allowed
            if ($PassEdit) {
                if ($row['editlock']==1 && (api_is_allowed_to_edit(false,true)==false || api_is_platform_admin()==false)) {
                    Display::display_normal_message(get_lang('PageLockedExtra'));
                } else {
                    if ($last_row['is_editing']!=0 && $last_row['is_editing'] != $_user['user_id']) {
                        //checking for concurrent users
                        $timestamp_edit = strtotime($last_row['time_edit']);
                        $time_editing = time()-$timestamp_edit;
                        $max_edit_time = 1200; // 20 minutes
                        $rest_time = $max_edit_time - $time_editing;
                        $userinfo = api_get_user_info($last_row['is_editing']);
                        $username = api_htmlentities(sprintf(get_lang('LoginX'), $userinfo['username']), ENT_QUOTES);
                        $is_being_edited = get_lang('ThisPageisBeginEditedBy').' <a href=../user/userInfo.php?uInfo='.$userinfo['user_id'].'>'.
                            Display::tag('span', api_get_person_name($userinfo['firstname'], $userinfo['lastname'], array('title'=>$username))).
                            get_lang('ThisPageisBeginEditedTryLater').' '.date( "i",$rest_time).' '.get_lang('MinMinutes');
                        Display::display_normal_message($is_being_edited, false);
                    } else {
                         Display::display_confirmation_message(
                             $wiki->restore_wikipage(
                                 $current_row['page_id'],
                                 $current_row['reflink'],
                                 api_htmlentities($current_row['title']),
                                 api_htmlentities($current_row['content']),
                                 $current_row['group_id'],
                                 $current_row['assignment'],
                                 $current_row['progress'],
                                 $current_row['version'],
                                 $last_row['version'],
                                 $current_row['linksto']
                             ).': <a href="index.php?cidReq='.$_course['id'].'&action=showpage&amp;title='.api_htmlentities(urlencode($last_row['reflink'])).'&session_id='.$last_row['session_id'].'&group_id='.$last_row['group_id'].'">'.api_htmlentities($last_row['title']).'</a>',
                             false
                         );
                    }
                }
            }
        }
    }
}

if ($action == 'deletewiki') {
    if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
        if ($_GET['delete'] == 'yes') {
            $return_message = $wiki->delete_wiki();
            Display::display_confirmation_message($return_message);
        }
    }
}
if ($action =='discuss' && isset($_POST['Submit']) && $_POST['Submit']) {
    Display::display_confirmation_message(get_lang('CommentAdded'));
}

/* MAIN WIKI AREA */

/** menuwiki (= actions of the page, not of the wiki tool) **/

echo '<div class="actions">';
/*        echo '&nbsp;<a href="index.php?cidReq='.$_course['id'].'&action=show&amp;title=index&session_id='.$session_id.'&group_id='.$groupId.'"'.$wiki->is_active_navigation_tab('show').'>'.
    Display::return_icon('wiki.png',get_lang('HomeWiki'),'',ICON_SIZE_MEDIUM).'</a>&nbsp;';*/
echo '<ul class="nav" style="margin-bottom:0px">
    <li class="dropdown">
    <a class="dropdown-toggle" href="javascript:void(0)">'.Display::return_icon('menu.png', get_lang('Menu'), '', ICON_SIZE_MEDIUM).'</a>';
// menu home
echo '<ul class="dropdown-menu">';
echo '<li><a href="index.php?cidReq='.$_course['id'].'&session_id='.$session_id.'&group_id='.$groupId.'">'.get_lang('Home').'</a></li>';
if (api_is_allowed_to_session_edit(false,true)) {
    //menu add page
    echo '<li><a href="index.php?cidReq='.$_course['id'].'&action=addnew&session_id='.$session_id.'&group_id='.$groupId.'"'.$wiki->is_active_navigation_tab('addnew').'>'.get_lang('AddNew').'</a>';
}
$lock_unlock_addnew = null;
$protect_addnewpage = null;

if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
    // page action: enable or disable the adding of new pages
    if ($wiki->check_addnewpagelock()==0) {
        $protect_addnewpage = Display::return_icon('off.png', get_lang('AddOptionProtected'));
        $lock_unlock_addnew ='unlockaddnew';
    } else {
        $protect_addnewpage = Display::return_icon('on.png', get_lang('AddOptionUnprotected'));
        $lock_unlock_addnew ='lockaddnew';
    }
}

echo '<a href="index.php?action=show&amp;actionpage='.$lock_unlock_addnew.'&amp;title='.api_htmlentities(urlencode($page)).'">'.$protect_addnewpage.'</a></li>';
// menu find
echo '<li><a href="index.php?cidReq='.$_course['id'].'&action=searchpages&session_id='.$session_id.'&group_id='.$groupId.'"'.$wiki->is_active_navigation_tab('searchpages').'>'.get_lang('SearchPages').'</a></li>';
// menu all pages
echo '<li><a href="index.php?cidReq='.$_course['id'].'&action=allpages&session_id='.$session_id.'&group_id='.$groupId.'"'.$wiki->is_active_navigation_tab('allpages').'>'.get_lang('AllPages').'</a></li>';
// menu recent changes
echo '<li><a href="index.php?cidReq='.$_course['id'].'&action=recentchanges&session_id='.$session_id.'&group_id='.$groupId.'"'.$wiki->is_active_navigation_tab('recentchanges').'>'.get_lang('RecentChanges').'</a></li>';
// menu delete all wiki
if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
    echo '<li><a href="index.php?action=deletewiki&amp;title='.api_htmlentities(urlencode($page)).'"'.$wiki->is_active_navigation_tab('deletewiki').'>'.get_lang('DeleteWiki').'</a></li>';
}
///menu more
echo '<li><a href="index.php?action=more&amp;title='.api_htmlentities(urlencode($page)).'"'.$wiki->is_active_navigation_tab('more').'>'.get_lang('Statistics').'</a></li>';
echo '</ul>';
echo '</li>';

//menu show page
echo '<a href="index.php?cidReq='.$_course['id'].'&action=showpage&amp;title='.api_htmlentities(urlencode($page)).'&session_id='.$session_id.'&group_id='.$groupId.'"'.$wiki->is_active_navigation_tab('showpage').'>'.Display::return_icon('page.png',get_lang('ShowThisPage'),'',ICON_SIZE_MEDIUM).'</a>';

if (api_is_allowed_to_session_edit(false,true) ) {
    //menu edit page
    echo '<a href="index.php?cidReq='.$_course['id'].'&action=edit&amp;title='.api_htmlentities(urlencode($page)).'&session_id='.$session_id.'&group_id='.$groupId.'"'.$wiki->is_active_navigation_tab('edit').'>'.Display::return_icon('edit.png',get_lang('EditThisPage'),'',ICON_SIZE_MEDIUM).'</a>';

    //menu discuss page
    echo '<a href="index.php?action=discuss&amp;title='.api_htmlentities(urlencode($page)).'"'.$wiki->is_active_navigation_tab('discuss').'>'.Display::return_icon('discuss.png',get_lang('DiscussThisPage'),'',ICON_SIZE_MEDIUM).'</a>';
 }

//menu history
echo '<a href="index.php?cidReq='.$_course['id'].'&action=history&amp;title='.api_htmlentities(urlencode($page)).'&session_id='.$session_id.'&group_id='.$groupId.'"'.$wiki->is_active_navigation_tab('history').'>'.Display::return_icon('history.png',get_lang('ShowPageHistory'),'',ICON_SIZE_MEDIUM).'</a>';
//menu linkspages
echo '<a href="index.php?action=links&amp;title='.api_htmlentities(urlencode($page)).'&session_id='.$session_id.'&group_id='.$groupId.'"'.$wiki->is_active_navigation_tab('links').'>'.Display::return_icon('what_link_here.png',get_lang('LinksPages'),'',ICON_SIZE_MEDIUM).'</a>';

//menu delete wikipage
if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
    echo '<a href="index.php?action=delete&amp;title='.api_htmlentities(urlencode($page)).'"'.$wiki->is_active_navigation_tab('delete').'>'.Display::return_icon('delete.png',get_lang('DeleteThisPage'),'',ICON_SIZE_MEDIUM).'</a>';
}
echo '</ul>';
echo '</div>'; // End actions


//In new pages go to new page
if (isset($_POST['SaveWikiNew'])) {
    if (isset($_POST['reflink'])) {
        $wiki->display_wiki_entry($_POST['reflink']);
    }
}

//More for export to course document area. See display_wiki_entry
if (isset($_POST['export2DOC']) && $_POST['export2DOC']) {
    $doc_id = $_POST['doc_id'];
    $export2doc = $wiki->export2doc($doc_id);
    if ($export2doc) {
        Display::display_confirmation_message(get_lang('ThePageHasBeenExportedToDocArea'));
    }
}

if (isset($action) && $action =='more') {
    echo '<div class="actions">'.get_lang('More').'</div>';
    echo '<table border="0">';
    echo '  <tr>';
    echo '    <td>';
    echo '      <ul>';
    //Submenu Most active users
    echo '        <li><a href="index.php?cidReq='.$_course['id'].'&action=mactiveusers&session_id='.$session_id.'&group_id='.$groupId.'">'.get_lang('MostActiveUsers').'</a></li>';
    //Submenu Most visited pages
    echo '        <li><a href="index.php?cidReq='.$_course['id'].'&action=mvisited&session_id='.$session_id.'&group_id='.$groupId.'">'.get_lang('MostVisitedPages').'</a></li>';
    //Submenu Most changed pages
    echo '        <li><a href="index.php?cidReq='.$_course['id'].'&action=mostchanged&session_id='.$session_id.'&group_id='.$groupId.'">'.get_lang('MostChangedPages').'</a></li>';
    echo '      </ul>';
    echo '    </td>';
    echo '    <td>';
    echo '      <ul>';
    // Submenu Orphaned pages
    echo '        <li><a href="index.php?cidReq='.$_course['id'].'&action=orphaned&session_id='.$session_id.'&group_id='.$groupId.'">'.get_lang('OrphanedPages').'</a></li>';
    // Submenu Wanted pages
    echo '        <li><a href="index.php?cidReq='.$_course['id'].'&action=wanted&session_id='.$session_id.'&group_id='.$groupId.'">'.get_lang('WantedPages').'</a></li>';
	// Submenu Most linked pages
    echo '<li><a href="index.php?cidReq='.$_course['id'].'&action=mostlinked&session_id='.$session_id.'&group_id='.$groupId.'">'.get_lang('MostLinkedPages').'</a></li>';
    echo '</ul>';
	echo '</td>';
	echo '<td style="vertical-align:top">';
    echo '<ul>';
	// Submenu Statistics
	if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
    	echo '<li><a href="index.php?cidReq='.$_course['id'].'&action=statistics&session_id='.$session_id.'&group_id='.$groupId.'">'.get_lang('Statistics').'</a></li>';
	}
    echo '      </ul>';
    echo'    </td>';
    echo '  </tr>';
    echo '</table>';
}

// Statistics Juan Carlos Raña Trabado

if ($action =='statistics' && (api_is_allowed_to_edit(false,true) || api_is_platform_admin())) {
    $wiki->getStats();
}

// Most active users Juan Carlos Raña Trabado

if ($action =='mactiveusers') {
    $wiki->getActiveUsers($action);
}

// User contributions Juan Carlos Raña Trabado

if ($action =='usercontrib') {
    $userinfo = api_get_user_info($_GET['user_id']);
    $username = api_htmlentities(sprintf(get_lang('LoginX'), $userinfo['username']), ENT_QUOTES);

    echo '<div class="actions">'.get_lang('UserContributions').': <a href="../user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.
            Display::tag('span', api_htmlentities(api_get_person_name($userinfo['firstname'], $userinfo['lastname'])), array('title'=>$username)).
            '</a><a href="'.api_get_self().'?cidReq='.$_course['id'].'&action=usercontrib&user_id='.urlencode($row['user_id']).
            '&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'"></a></div>';

    if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
        //only by professors if page is hidden
        $sql='SELECT * FROM '.$tbl_wiki.'  WHERE c_id = '.$course_id.' AND '.$groupfilter.$condition_session.' AND user_id="'.Database::escape_string($_GET['user_id']).'"';
    } else {
        $sql='SELECT * FROM '.$tbl_wiki.'  WHERE c_id = '.$course_id.' AND '.$groupfilter.$condition_session.' AND user_id="'.Database::escape_string($_GET['user_id']).'" AND visibility=1';
    }

    $allpages = Database::query($sql);

    //show table
    if (Database::num_rows($allpages) > 0) {
        $row = array ();
        while ($obj = Database::fetch_object($allpages)) {
            //get author
            $userinfo = api_get_user_info($obj->user_id);

            //get time
            $year 	 = substr($obj->dtime, 0, 4);
            $month	 = substr($obj->dtime, 5, 2);
            $day 	 = substr($obj->dtime, 8, 2);
            $hours   = substr($obj->dtime, 11,2);
            $minutes = substr($obj->dtime, 14,2);
            $seconds = substr($obj->dtime, 17,2);

            //get type assignment icon
            if ($obj->assignment==1) {
                $ShowAssignment=Display::return_icon('wiki_assignment.png', get_lang('AssignmentDescExtra'),'',ICON_SIZE_SMALL);
            } elseif ($obj->assignment==2) {
                $ShowAssignment=Display::return_icon('wiki_work.png', get_lang('AssignmentWork'),'',ICON_SIZE_SMALL);
            } elseif ($obj->assignment==0) {
                $ShowAssignment='<img src="../img/px_transparent.gif" />';
            }

            $row = array ();
            $row[] = $year.'-'.$month.'-'.$day.' '.$hours.":".$minutes.":".$seconds;
            $row[] =$ShowAssignment;
            $row[] = '<a href="'.api_get_self().'?cidReq='.$_course['id'].'&action=showpage&title='.api_htmlentities(urlencode($obj->reflink)).'&view='.$obj->id.'&session_id='.api_htmlentities(urlencode($_GET['$session_id'])).'&group_id='.api_htmlentities(urlencode($_GET['group_id'])).'">'.api_htmlentities($obj->title).'</a>';
            $row[] =Security::remove_XSS($obj->version);
            $row[] =Security::remove_XSS($obj->comment);
            $row[] =Security::remove_XSS($obj->progress).' %';
            $row[] =Security::remove_XSS($obj->score);
            $rows[] = $row;

        }

        $table = new SortableTableFromArrayConfig($rows,2,10,'UsersContributions_table','','','ASC');
        $table->set_additional_parameters(
            array('cidReq' =>Security::remove_XSS($_GET['cidReq']),'action'=>Security::remove_XSS($action ),'user_id'=>Security::remove_XSS($_GET['user_id']),'session_id'=>Security::remove_XSS($_GET['session_id']),'group_id'=>Security::remove_XSS($_GET['group_id']))
        );
        $table->set_header(0,get_lang('Date'), true, array ('style' => 'width:200px;'));
        $table->set_header(1,get_lang('Type'), true, array ('style' => 'width:30px;'));
        $table->set_header(2,get_lang('Title'), true, array ('style' => 'width:200px;'));
        $table->set_header(3,get_lang('Version'), true, array ('style' => 'width:30px;'));
        $table->set_header(4,get_lang('Comment'), true, array ('style' => 'width:200px;'));
        $table->set_header(5,get_lang('Progress'), true, array ('style' => 'width:30px;'));
        $table->set_header(6,get_lang('Rating'), true, array ('style' => 'width:30px;'));
        $table->display();
    }
}

/* Most changed pages */

if ($action =='mostchanged') {
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
                $ShowAssignment=Display::return_icon('wiki_assignment.png', get_lang('AssignmentDesc'),'',ICON_SIZE_SMALL);
            } elseif ($obj->assignment==2) {
                $ShowAssignment=Display::return_icon('wiki_work.png', get_lang('AssignmentWork'),'',ICON_SIZE_SMALL);
            } elseif ($obj->assignment==0) {
                $ShowAssignment='<img src="../img/px_transparent.gif" />';
            }

            $row = array ();
            $row[] = $ShowAssignment;
            $row[] = '<a href="'.api_get_self().'?cidReq='.$_course['id'].'&action=showpage&title='.api_htmlentities(urlencode($obj->reflink)).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'">'.api_htmlentities($obj->title).'</a>';
            $row[] = $obj->MAX;
            $rows[] = $row;
        }

        $table = new SortableTableFromArrayConfig($rows,2,10,'MostChangedPages_table','','','DESC');
        $table->set_additional_parameters(array('cidReq' =>Security::remove_XSS($_GET['cidReq']),'action'=>Security::remove_XSS($action ),'session_id'=>Security::remove_XSS($_GET['session_id']),'group_id'=>Security::remove_XSS($_GET['group_id'])));
        $table->set_header(0,get_lang('Type'), true, array ('style' => 'width:30px;'));
        $table->set_header(1,get_lang('Title'), true);
        $table->set_header(2,get_lang('Changes'), true);
        $table->display();
    }
}

/* Most visited pages */

if ($action =='mvisited') {
    echo '<div class="actions">'.get_lang('MostVisitedPages').'</div>';

    if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) { //only by professors if page is hidden
        $sql = 'SELECT *, SUM(hits) AS tsum FROM '.$tbl_wiki.'
                WHERE c_id = '.$course_id.' AND '.$groupfilter.$condition_session.'
                GROUP BY reflink';
    } else {
        $sql = 'SELECT *, SUM(hits) AS tsum FROM '.$tbl_wiki.'
                WHERE  c_id = '.$course_id.' AND '.$groupfilter.$condition_session.' AND visibility=1
                GROUP BY reflink';
    }

    $allpages=Database::query($sql);

    //show table
    if (Database::num_rows($allpages) > 0) {
        $row = array ();
        while ($obj = Database::fetch_object($allpages)) {
            //get type assignment icon
            if ($obj->assignment==1) {
                $ShowAssignment=Display::return_icon('wiki_assignment.png', get_lang('AssignmentDesc'),'',ICON_SIZE_SMALL);
            } elseif ($obj->assignment==2) {
                $ShowAssignment=$ShowAssignment=Display::return_icon('wiki_work.png', get_lang('AssignmentWork'),'',ICON_SIZE_SMALL);
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
        $table->set_additional_parameters(array('cidReq' =>Security::remove_XSS($_GET['cidReq']),'action'=>Security::remove_XSS($action ),'session_id'=>Security::remove_XSS($_GET['session_id']),'group_id'=>Security::remove_XSS($_GET['group_id'])));
        $table->set_header(0,get_lang('Type'), true, array ('style' => 'width:30px;'));
        $table->set_header(1,get_lang('Title'), true);
        $table->set_header(2,get_lang('Visits'), true);
        $table->display();
    }
}

/* Wanted pages */

if ($action =='wanted') {
    echo '<div class="actions">'.get_lang('WantedPages').'</div>';
    $pages = array();
    $refs = array();
	$wanted = array();
    //get name pages
    $sql = 'SELECT * FROM '.$tbl_wiki.'
            WHERE  c_id = '.$course_id.' AND '.$groupfilter.$condition_session.'
            GROUP BY reflink ORDER BY reflink ASC';
    $allpages=Database::query($sql);

    while ($row=Database::fetch_array($allpages)) {
		if ($row['reflink']=='index'){
			$row['reflink']=str_replace(' ','_',get_lang('DefaultTitle'));
		}
        $pages[] = $row['reflink'];
    }

    //get name refs in last pages
    $sql = 'SELECT  *  FROM   '.$tbl_wiki.' s1
    		WHERE s1.c_id = '.$course_id.' AND id=(
    		    SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2
    		    WHERE s2.c_id = '.$course_id.' AND s1.reflink = s2.reflink AND '.$groupfilter.$condition_session.'
            )';

    $allpages = Database::query($sql);

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

	$wanted = array_unique($wanted);//make a unique list

	//show table
    $rows = array();
    foreach ($wanted as $wanted_show) {
        $row = array();
        $wanted_show=Security::remove_XSS($wanted_show);
        $row[] = '<a href="'.api_get_path(WEB_PATH).'main/wiki/index.php?cidReq=&action=addnew&title='.str_replace('_',' ',$wanted_show).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'" class="new_wiki_link">'.str_replace('_',' ',$wanted_show).'</a>';//meter un remove xss en lugar de htmlentities
        $rows[] = $row;
    }

    $table = new SortableTableFromArrayConfig($rows,0,10,'WantedPages_table','','','DESC');
    $table->set_additional_parameters(array('cidReq' =>Security::remove_XSS($_GET['cidReq']),'action'=>Security::remove_XSS($action ),'session_id'=>Security::remove_XSS($_GET['session_id']),'group_id'=>Security::remove_XSS($_GET['group_id'])));
    $table->set_header(0,get_lang('Title'), true);
    $table->display();
}

/* Orphaned pages */

if ($action =='orphaned') {
    echo '<div class="actions">'.get_lang('OrphanedPages').'</div>';

    $pages = array();
    $refs = array();
	$list_refs = array();
    $orphaned = array();

    //get name pages
    $sql = 'SELECT * FROM '.$tbl_wiki.'
            WHERE c_id = '.$course_id.' AND '.$groupfilter.$condition_session.'
            GROUP BY reflink
            ORDER BY reflink ASC';
    $allpages=Database::query($sql);
    while ($row=Database::fetch_array($allpages)) {
        $pages[] = $row['reflink'];
    }

    //get name refs in last pages and make a unique list
    $sql = 'SELECT  *  FROM   '.$tbl_wiki.' s1
    		WHERE s1.c_id = '.$course_id.' AND id=(
    		SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2 WHERE s2.c_id = '.$course_id.' AND s1.reflink = s2.reflink AND '.$groupfilter.$condition_session.')';

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
		$sql = 'SELECT  *  FROM   '.$tbl_wiki.'
		        WHERE c_id = '.$course_id.' AND '.$groupfilter.$condition_session.' AND reflink="'.Database::escape_string($orphaned_show).'" GROUP BY reflink';
        $allpages=Database::query($sql);
		while ($row=Database::fetch_array($allpages)) {
			$orphaned_title=$row['title'];
			$orphaned_visibility=$row['visibility'];
			if ($row['assignment']==1) {
                $ShowAssignment=Display::return_icon('wiki_assignment.png','','',ICON_SIZE_SMALL);
            } elseif ($row['assignment']==2) {
                $ShowAssignment=Display::return_icon('wiki_work.png','','',ICON_SIZE_SMALL);
            } elseif ($row['assignment']==0) {
                $ShowAssignment='<img src="../img/px_transparent.gif" />';
            }
		}
		if (!api_is_allowed_to_edit(false,true) || !api_is_platform_admin() AND $orphaned_visibility==0){
			continue;
		}

		//show table
        $row = array();
			$row[] = $ShowAssignment;
            $row[] = '<a href="'.api_get_self().'?cidReq='.$_course['id'].'&action=showpage&title='.api_htmlentities(urlencode($orphaned_show)).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'">'.api_htmlentities($orphaned_title).'</a>';
            $rows[] = $row;
        }

        $table = new SortableTableFromArrayConfig($rows,1,10,'OrphanedPages_table','','','DESC');
        $table->set_additional_parameters(array('cidReq' =>Security::remove_XSS($_GET['cidReq']),'action'=>Security::remove_XSS($action ),'session_id'=>Security::remove_XSS($_GET['session_id']),'group_id'=>Security::remove_XSS($_GET['group_id'])));
        $table->set_header(0,get_lang('Type'), true, array ('style' => 'width:30px;'));
        $table->set_header(1,get_lang('Title'), true);
        $table->display();
}

/* Most linked pages */

if ($action =='mostlinked') {
    echo '<div class="actions">'.get_lang('MostLinkedPages').'</div>';
	$pages = array();
    $refs = array();
	$linked = array();

    //get name pages
    $sql = 'SELECT * FROM '.$tbl_wiki.'
            WHERE  c_id = '.$course_id.' AND '.$groupfilter.$condition_session.'
            GROUP BY reflink ORDER BY reflink ASC';
    $allpages=Database::query($sql);

    while ($row=Database::fetch_array($allpages)) {
		if ($row['reflink']=='index') {
			$row['reflink']=str_replace(' ','_',get_lang('DefaultTitle'));
		}
		$pages[] = $row['reflink'];
    }

    //get name refs in last pages
    $sql = 'SELECT  *  FROM   '.$tbl_wiki.' s1 WHERE s1.c_id = '.$course_id.' AND id=(
            SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2 WHERE s2.c_id = '.$course_id.' AND s1.reflink = s2.reflink AND '.$groupfilter.$condition_session.')';

    $allpages=Database::query($sql);

    while ($row=Database::fetch_array($allpages)) {
	 	$row['linksto']= str_replace($row["reflink"], " ", trim($row["linksto"])); //remove self reference
        $refs = explode(" ", trim($row["linksto"]));

		// Find linksto into reflink. If found ->page is linked
		foreach ($refs as $v) {
			if (in_array($v, $pages)) {
				if (trim($v)!="") {
					$linked[]=$v;
				}
			}
		}
	}

	$linked = array_unique($linked);
	//make a unique list. TODO:delete this line and count how many for each page
    //show table
    $rows = array();
    foreach ($linked as $linked_show) {
        $row = array();
        $row[] = '<a href="'.api_get_self().'?cidReq='.$_course['id'].'&action=showpage&title='.api_htmlentities(urlencode(str_replace('_',' ',$linked_show))).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'">'.str_replace('_',' ',$linked_show).'</a>';
        $rows[] = $row;
    }

    $table = new SortableTableFromArrayConfig($rows,0,10,'LinkedPages_table','','','DESC');
    $table->set_additional_parameters(
        array(
            'cidReq' =>Security::remove_XSS($_GET['cidReq']),
            'action'=>Security::remove_XSS($action ),
            'session_id'=>Security::remove_XSS($_GET['session_id']),
            'group_id'=>Security::remove_XSS($_GET['group_id'])
        )
    );
    $table->set_header(0,get_lang('Title'), true);
    $table->display();
}

/* Delete current page */

if ($action =='delete') {
    if (!$_GET['title']) {
        Display::display_error_message(get_lang('MustSelectPage'));
        exit;
    }

    if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
        echo '<div id="wikititle">'.get_lang('DeletePageHistory').'</div>';

        if ($page == "index") {
            Display::display_warning_message(get_lang('WarningDeleteMainPage'),false);
        }

        $message = get_lang('ConfirmDeletePage')."</p>"."<p>"."<a href=\"index.php\">".get_lang("No")."</a>"."&nbsp;&nbsp;|&nbsp;&nbsp;"."<a href=\"".api_get_self()."?action=delete&amp;title=".api_htmlentities(urlencode($page))."&amp;delete=yes\">".get_lang("Yes")."</a>"."</p>";

        if (!isset ($_GET['delete'])) {
            Display::display_warning_message($message,false);
        }

        if (isset($_GET['delete']) && $_GET['delete'] == 'yes') {
            $result = $wiki->deletePage($page, $course_id, $groupfilter, $condition_session);
            if ($result) {
                Display::display_confirmation_message(get_lang('WikiPageDeleted'));
            }
        }
    } else {
        Display::display_normal_message(get_lang("OnlyAdminDeletePageWiki"));
    }
}

/* Delete all wiki */

if ($action =='deletewiki') {
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

/* Search wiki pages */

if ($action =='searchpages') {
    echo '<div class="actions">'.get_lang('SearchPages').'</div>';
	if (isset($_GET['mode_table'])) {
		if (!isset($_GET['SearchPages_table_page_nr'])) {
			$_GET['search_term'] = $_POST['search_term'];
			$_GET['search_content'] = $_POST['search_content'];
			$_GET['all_vers'] = $_POST['all_vers'];
		}
		$wiki->display_wiki_search_results(
            api_htmlentities($_GET['search_term']),
            api_htmlentities($_GET['search_content']),
            api_htmlentities($_GET['all_vers'])
        );
	} else {

		// initiate the object
		$form = new FormValidator('wiki_search',
            'post',
            api_get_self().'?cidReq='.api_htmlentities($_GET['cidReq']).'&action='.api_htmlentities($action).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'&mode_table=yes1&search_term='.api_htmlentities($_GET['search_term']).'&search_content='.api_htmlentities($_GET['search_content']).'&all_vers='.api_htmlentities($_GET['all_vers'])
        );

		// Setting the form elements

		$form->addElement('text', 'search_term', get_lang('SearchTerm'),'class="input_titles" id="search_title"');
		$form->addElement('checkbox', 'search_content', null, get_lang('AlsoSearchContent'));
		$form->addElement('checkbox', 'all_vers', null, get_lang('IncludeAllVersions'));
		$form->addElement('style_submit_button', 'SubmitWikiSearch', get_lang('Search'), 'class="search"');

		// setting the rules
		$form->addRule('search_term', get_lang('ThisFieldIsRequired'), 'required');
		$form->addRule('search_term', get_lang('TooShort'),'minlength',3); //TODO: before fixing the pagination rules worked, not now
		if ($form->validate()) {
			$form->display();
			$values = $form->exportValues();
			$wiki->display_wiki_search_results(
                $values['search_term'],
                $values['search_content'],
                $values['all_vers']
            );
		} else {
			$form->display();
		}
	}
}

/* What links here. Show pages that have linked this page */

if ($action =='links') {
    if (!$_GET['title']) {
        Display::display_error_message(get_lang("MustSelectPage"));
    } else {
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE
                    c_id = '.$course_id.' AND
                    reflink="'.Database::escape_string($page).'" AND
                    '.$groupfilter.$condition_session.'';
        $result = Database::query($sql);
        $row = Database::fetch_array($result);

        //get type assignment icon

        if ($row['assignment']==1) {
            $ShowAssignment=Display::return_icon('wiki_assignment.png', get_lang('AssignmentDesc'),'',ICON_SIZE_SMALL);
        } elseif ($row['assignment']==2) {
            $ShowAssignment=Display::return_icon('wiki_work.png', get_lang('AssignmentWork'),'',ICON_SIZE_SMALL);
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
                $userinfo = api_get_user_info($obj->user_id);
                $username = api_htmlentities(sprintf(get_lang('LoginX'), $userinfo['username']), ENT_QUOTES);

                //get time
                $year 	 = substr($obj->dtime, 0, 4);
                $month	 = substr($obj->dtime, 5, 2);
                $day 	 = substr($obj->dtime, 8, 2);
                $hours   = substr($obj->dtime, 11,2);
                $minutes = substr($obj->dtime, 14,2);
                $seconds = substr($obj->dtime, 17,2);

                //get type assignment icon
                if ($obj->assignment==1) {
                    $ShowAssignment=Display::return_icon('wiki_assignment.png', get_lang('AssignmentDesc'),'',ICON_SIZE_SMALL);
                } elseif ($obj->assignment==2) {
                    $ShowAssignment=Display::return_icon('wiki_work.png', get_lang('AssignmentWork'),'',ICON_SIZE_SMALL);
                } elseif ($obj->assignment==0) {
                    $ShowAssignment='<img src="../img/px_transparent.gif" />';
                }

                $row = array ();
                $row[] =$ShowAssignment;
                $row[] = '<a href="'.api_get_self().'?cidReq='.$_course['id'].'&action=showpage&title='.api_htmlentities(urlencode($obj->reflink)).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'">'.api_htmlentities($obj->title).'</a>';
                if ($obj->user_id <>0) {
                    $row[] = '<a href="../user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.
                    Display::tag('span', api_htmlentities(api_get_person_name($userinfo['firstname'], $userinfo['lastname'])), array('title'=>$username)).'</a>';
                }
                else {
                    $row[] = get_lang('Anonymous').' ('.$obj->user_ip.')';
                }
                $row[] = $year.'-'.$month.'-'.$day.' '.$hours.":".$minutes.":".$seconds;
                $rows[] = $row;
            }

            $table = new SortableTableFromArrayConfig($rows,1,10,'AllPages_table','','','ASC');
            $table->set_additional_parameters(array('cidReq' =>Security::remove_XSS($_GET['cidReq']),'action'=>Security::remove_XSS($action ),'group_id'=>Security::remove_XSS($_GET['group_id'])));
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

if ($action =='addnew') {
    if (api_get_session_id()!=0 && api_is_allowed_to_session_edit(false,true)==false) {
        api_not_allowed();
    }

    echo '<div class="actions">'.get_lang('AddNew').'</div>';
	echo '<br/>';
    //first, check if page index was created. chektitle=false
    if ($wiki->checktitle('index')) {
        if (api_is_allowed_to_edit(false,true) || api_is_platform_admin() || GroupManager :: is_user_in_group($_user['user_id'],$_SESSION['_gid'])) {
            Display::display_normal_message(get_lang('GoAndEditMainPage'));
        } else {
            return Display::display_normal_message(get_lang('WikiStandBy'));
        }
    } elseif ($wiki->check_addnewpagelock()==0 && (api_is_allowed_to_edit(false,true)==false || api_is_platform_admin()==false)) {
        Display::display_error_message(get_lang('AddPagesLocked'));
    } else {
        if(api_is_allowed_to_edit(false,true) || api_is_platform_admin() || GroupManager :: is_user_in_group($_user['user_id'],$_SESSION['_gid']) || Security::remove_XSS($_GET['group_id'])==0) {
            $wiki->display_new_wiki_form();
        } else {
            Display::display_normal_message(get_lang('OnlyAddPagesGroupMembers'));
        }
    }
}

// Show home page
if (!$action  OR $action =='show' AND !isset($_POST['SaveWikiNew'])) {
    $wiki->display_wiki_entry($newtitle);
}

// Show current page
if ($action =='showpage' AND !isset($_POST['SaveWikiNew'])) {
    if ($_GET['title']) {
        $wiki->display_wiki_entry($newtitle);
    } else {
        Display::display_error_message(get_lang('MustSelectPage'));
    }
}

// Edit current page

if (isset($action) && $action =='edit') {

    if (api_get_session_id()!=0 && api_is_allowed_to_session_edit(false,true)==false) {
        api_not_allowed();
    }

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
        $content = api_html_entity_decode($row['content']);
        $title = api_html_entity_decode($row['title']);
        $page_id = $row['page_id'];
    }

    //Only teachers and platform admin can edit the index page. Only teachers and platform admin can edit an assignment teacher. And users in groups
    if (($row['reflink']=='index' || $row['reflink']=='' || $row['assignment']==1) && (!api_is_allowed_to_edit(false,true) && intval($_GET['group_id'])==0)) {
        Display::display_error_message(get_lang('OnlyEditPagesCourseManager'));
    } else {
        $PassEdit=false;

        //check if is a wiki group
        if ($groupId!=0) {
            //Only teacher, platform admin and group members can edit a wiki group
            if (api_is_allowed_to_edit(false,true) || api_is_platform_admin() || GroupManager :: is_user_in_group($_user['user_id'],intval($_GET['group_id']))) {
                $PassEdit=true;
            } else {
                  Display::display_normal_message(get_lang('OnlyEditPagesGroupMembers'));
            }
        } else {
            $PassEdit=true;
        }
        $icon_assignment = null;
        // check if is a assignment
        if ($row['assignment']==1) {
            Display::display_normal_message(get_lang('EditAssignmentWarning'));
            $icon_assignment=Display::return_icon('wiki_assignment.png', get_lang('AssignmentDescExtra'),'',ICON_SIZE_SMALL);
        } elseif ($row['assignment']==2) {
            $icon_assignment=Display::return_icon('wiki_work.png', get_lang('AssignmentWorkExtra'),'',ICON_SIZE_SMALL);
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

                if (!empty($row['max_version']) && $row['version']>=$row['max_version']) {
                    $message=get_lang('HasReachedMaxiNumVersions');
                    Display::display_warning_message($message);
                    if (!api_is_allowed_to_edit(false,true)) {
                        exit;
                    }
                }

                if (!empty($row['max_text']) && $row['max_text']<=$wiki->word_count($row['content'])) {
                    $message=get_lang('HasReachedMaxNumWords');
                    Display::display_warning_message($message);
                    if (!api_is_allowed_to_edit(false,true)) {
                        exit;
                    }
                }

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
                    Display::display_normal_message(get_lang('WarningMaxEditingTime'));

                    $time_edit = date("Y-m-d H:i:s");
                    $sql='UPDATE '.$tbl_wiki.' SET is_editing="'.$_user['user_id'].'", time_edit="'.$time_edit.'" WHERE c_id = '.$course_id.' AND  id="'.$row['id'].'"';
                    Database::query($sql);
                } elseif ($row['is_editing']!=$_user['user_id']) {
                    $timestamp_edit=strtotime($row['time_edit']);
                    $time_editing=time()-$timestamp_edit;
                    $max_edit_time=1200; // 20 minutes
                    $rest_time=$max_edit_time-$time_editing;

                    $userinfo = api_get_user_info($row['is_editing']);
                    $username = api_htmlentities(sprintf(get_lang('LoginX'), $userinfo['username']), ENT_QUOTES);

                    $is_being_edited= get_lang('ThisPageisBeginEditedBy').
                    ' <a href=../user/userInfo.php?uInfo='.
                    $userinfo['user_id'].'>'.
                    Display::tag('span', api_htmlentities(api_get_person_name($userinfo['firstname'], $userinfo['lastname'])), array('title'=>$username)).
                    '</a>. '.get_lang('ThisPageisBeginEditedTryLater').' '.date( "i",$rest_time).' '.get_lang('MinMinutes').'';
                    Display::display_normal_message($is_being_edited, false);
                    exit;
                }

                // Form.
                echo '<div id="wikititle">'.$icon_assignment.str_repeat('&nbsp;',3).api_htmlentities($title).'</div>';
                echo '<form name="form1" method="post" action="'.api_get_self().'?action=showpage&amp;title='.api_htmlentities(urlencode($page)).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'">';

                if ((api_is_allowed_to_edit(false,true) || api_is_platform_admin()) && $row['reflink'] != 'index') {
                    echo '<a href="javascript://" onclick="advanced_parameters()" >
                         <div id="plus_minus">&nbsp;'.
                        Display::return_icon(
                            'div_show.gif',
                            get_lang('Show'),
                            array('style'=>'vertical-align:middle')
                        ).'&nbsp;'.get_lang('AdvancedParameters').'</div></a>';

                    echo '<div id="options" style="display:none; margin: 20px;" >';

                    // Task
                    echo '<input type="checkbox" value="1" name="checktask" onclick="javascript: if(this.checked){document.getElementById(\'option4\').style.display=\'block\';}else{document.getElementById(\'option4\').style.display=\'none\';}"/>&nbsp;'.Display::return_icon('wiki_task.png', get_lang('DefineTask'),'',ICON_SIZE_SMALL).' '.get_lang('DescriptionOfTheTask').'';
                    echo '&nbsp;&nbsp;&nbsp;<span id="msg_error4" style="display:none;color:red"></span>';
                    echo '<div id="option4" style="padding:4px; margin:5px; border:1px dotted; display:none;">';
                    echo '<table>';
                    echo '<tr>';
                    echo '<td>'.get_lang('DescriptionOfTheTask').'</td>';
                    echo '</tr>';
                    echo '<tr>';
                    echo '<td>'.api_disp_html_area('task', $row['task'], '', '', null, array('ToolbarSet' => 'wiki_task', 'Width' => '100%', 'Height' => '200')).'</td>';
                    echo '</tr>';
                    echo '</table>';
                    echo '</div>';

                    // Feedback
                    echo '<input type="checkbox" value="1" name="checkfeedback" onclick="javascript: if(this.checked){document.getElementById(\'option2\').style.display=\'block\';}else{document.getElementById(\'option2\').style.display=\'none\';}"/>&nbsp;'.get_lang('AddFeedback').'';
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
                        echo $wiki->draw_date_picker('startdate_assig').' <input type="checkbox" name="initstartdate" value="1"> '.get_lang('Yes').'/'.get_lang('No').'';
                    } else {
                        echo $wiki->draw_date_picker('startdate_assig', $row['startdate_assig']).' <input type="checkbox" name="initstartdate" value="1">'.get_lang('Yes').'/'.get_lang('No').'';
                    }
                    echo '</td>';
                    echo '</tr>';
                    echo '<tr>';
                    echo '<td align="right" width="150">'.get_lang("EndDate").':</td>';
                    echo '<td>';
                    if ($row['enddate_assig']=='0000-00-00 00:00:00') {
                        echo $wiki->draw_date_picker('enddate_assig').' <input type="checkbox" name="initenddate" value="1"> '.get_lang('Yes').'/'.get_lang('No').'';
                    } else {
                        echo $wiki->draw_date_picker('enddate_assig', $row['enddate_assig']).' <input type="checkbox" name="initenddate" value="1"> '.get_lang('Yes').'/'.get_lang('No').'';
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

                    echo '</div>';
                }

                echo '<input type="hidden" name="page_id" value="'.$page_id.'">';
                echo '<input type="hidden" name="reflink" value="'.api_htmlentities($page).'">';
                echo '<input type="hidden" name="title" value="'.api_htmlentities($title).'">';

                api_disp_html_area('content', $content, '', '', null, api_is_allowed_to_edit(null,true)
                    ? array('ToolbarSet' => 'Wiki', 'Width' => '100%', 'Height' => '400')
                    : array('ToolbarSet' => 'WikiStudent', 'Width' => '100%', 'Height' => '400', 'UserStatus' => 'student')
                );
                echo '<br/>';
                echo '<br/>';

                echo get_lang('Comments').':&nbsp;&nbsp;<input type="text" name="comment" size="40">&nbsp;&nbsp;&nbsp;';
                echo '<input TYPE="hidden" NAME="assignment" VALUE="'.$row['assignment'].'"/>';
                echo '<input TYPE="hidden" NAME="version" VALUE="'.$row['version'].'"/>';

                //hack date for edit
                echo '<input TYPE="hidden" NAME="startdate_assig" VALUE="'.$row['startdate_assig'].'"/>';
                echo '<input TYPE="hidden" NAME="enddate_assig" VALUE="'.$row['enddate_assig'].'"/>';

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
                 //for save button Don't change name (see fckeditor/editor/plugins/customizations/fckplugin_compressed.js and fckplugin.js
                echo '<button class="save" type="submit" name="SaveWikiChange">'.get_lang('Save').'</button>';
                echo '</form>';
            }
        }
    }
}

// Page history

if ($action == 'history' or isset($_POST['HistoryDifferences'])) {
    if (!$_GET['title']) {
        Display::display_error_message(get_lang("MustSelectPage"));
        exit;
    }

    /* First, see the property visibility that is at the last register and
    therefore we should select descending order.
    But to give ownership to each record,
    this is no longer necessary except for the title. TODO: check this*/

    $sql = 'SELECT * FROM '.$tbl_wiki.'
            WHERE c_id = '.$course_id.' AND reflink="'.Database::escape_string($page).'" AND '.$groupfilter.$condition_session.'
            ORDER BY id DESC';
    $result=Database::query($sql);

    $KeyVisibility = null;
    $KeyAssignment = null;
    $KeyTitle = null;
    $KeyUserId = null;
    while ($row=Database::fetch_array($result)) {
        $KeyVisibility = $row['visibility'];
        $KeyAssignment = $row['assignment'];
        $KeyTitle = $row['title'];
        $KeyUserId = $row['user_id'];
    }
    $icon_assignment = null;
    if ($KeyAssignment == 1) {
        $icon_assignment = Display::return_icon('wiki_assignment.png', get_lang('AssignmentDescExtra'), '', ICON_SIZE_SMALL);
    } elseif($KeyAssignment == 2) {
        $icon_assignment = Display::return_icon('wiki_work.png', get_lang('AssignmentWorkExtra'), '', ICON_SIZE_SMALL);
    }

    // Second, show

    //if the page is hidden and is a job only sees its author and professor
    if ($KeyVisibility == 1 ||
        api_is_allowed_to_edit(false,true) ||
        api_is_platform_admin() ||
        (
            $KeyAssignment==2 && $KeyVisibility==0 &&
            (api_get_user_id() == $KeyUserId)
        )
    ) {
        // We show the complete history
        if (!isset($_POST['HistoryDifferences']) && !isset($_POST['HistoryDifferences2'])) {
            $sql = 'SELECT * FROM '.$tbl_wiki.'
                    WHERE
                        c_id = '.$course_id.' AND
                        reflink="'.Database::escape_string($page).'" AND
                        '.$groupfilter.$condition_session.'
                    ORDER BY id DESC';
            $result = Database::query($sql);
            $title		= $_GET['title'];
            $group_id	= $_GET['group_id'];

            echo '<div id="wikititle">';
            echo $icon_assignment.'&nbsp;&nbsp;&nbsp;'.api_htmlentities($KeyTitle);
            echo '</div>';

            echo '<form id="differences" method="POST" action="index.php?cidReq='.$_course['id'].'&action=history&title='.api_htmlentities(urlencode($title)).'&session_id='.api_htmlentities($session_id).'&group_id='.api_htmlentities($group_id).'">';

            echo '<ul style="list-style-type: none;">';
            echo '<br/>';
            echo '<button class="search" type="submit" name="HistoryDifferences" value="HistoryDifferences">'.get_lang('ShowDifferences').' '.get_lang('LinesDiff').'</button>';
            echo '<button class="search" type="submit" name="HistoryDifferences2" value="HistoryDifferences2">'.get_lang('ShowDifferences').' '.get_lang('WordsDiff').'</button>';
            echo '<br/><br/>';

            $counter=0;
            $total_versions=Database::num_rows($result);

            while ($row=Database::fetch_array($result)) {
                $userinfo = api_get_user_info($row['user_id']);
                $username = api_htmlentities(sprintf(get_lang('LoginX'), $userinfo['username']), ENT_QUOTES);

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
                    echo '<a href="../user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.
                    Display::tag('span', api_htmlentities(api_get_person_name($userinfo['firstname'], $userinfo['lastname'])), array('title'=>$username)).
                    '</a>';
                } else {
                    echo get_lang('Anonymous').' ('.api_htmlentities($row['user_ip']).')';
                }
                echo ' ( '.get_lang('Progress').': '.api_htmlentities($row['progress']).'%, ';
                $comment = $row['comment'];
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
            echo '</ul></form>';
        } else { // We show the differences between two versions
            $version_old = array();
            if (isset($_POST['old'])) {
                $sql_old= "SELECT * FROM $tbl_wiki
                           WHERE c_id = $course_id AND id='".Database::escape_string($_POST['old'])."'";
                $result_old=Database::query($sql_old);
                $version_old=Database::fetch_array($result_old);
            }

            $sql_new="SELECT * FROM $tbl_wiki WHERE c_id = $course_id AND id='".Database::escape_string($_POST['new'])."'";
            $result_new=Database::query($sql_new);
            $version_new=Database::fetch_array($result_new);
            $oldTime = isset($version_old['dtime']) ? $version_old['dtime'] : null;
            $oldContent = isset($version_old['content']) ? $version_old['content'] : null;

            if (isset($_POST['HistoryDifferences'])) {
                include 'diff.inc.php';
                //title
                echo '<div id="wikititle">'.api_htmlentities($version_new['title']).'
                <font size="-2"><i>('.get_lang('DifferencesNew').'</i>
                    <font style="background-color:#aaaaaa">'.$version_new['dtime'].'</font>
                    <i>'.get_lang('DifferencesOld').'</i>
                    <font style="background-color:#aaaaaa">'.$oldTime.'</font>
                ) '.get_lang('Legend').':  <span class="diffAdded" >'.get_lang('WikiDiffAddedLine').'</span>
                <span class="diffDeleted" >'.get_lang('WikiDiffDeletedLine').'</span> <span class="diffMoved">'.get_lang('WikiDiffMovedLine').'</span></font>
                </div>';
            }
            if (isset($_POST['HistoryDifferences2'])) {
                // including global PEAR diff libraries
                require_once 'Text/Diff.php';
                require_once 'Text/Diff/Renderer/inline.php';
                //title
                echo '<div id="wikititle">'.api_htmlentities($version_new['title']).'
                <font size="-2"><i>('.get_lang('DifferencesNew').'</i> <font style="background-color:#aaaaaa">'.$version_new['dtime'].'</font>
                <i>'.get_lang('DifferencesOld').'</i> <font style="background-color:#aaaaaa">'.$version_old['dtime'].'</font>)
                '.get_lang('Legend').':  <span class="diffAddedTex" >'.get_lang('WikiDiffAddedTex').'</span>
                <span class="diffDeletedTex" >'.get_lang('WikiDiffDeletedTex').'</span></font></div>';
            }


            if (isset($_POST['HistoryDifferences'])) {
                echo '<table>'.diff($oldContent, $version_new['content'], true, 'format_table_line' ).'</table>'; // format_line mode is better for words
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

            if (isset($_POST['HistoryDifferences2'])) {
                $lines1 = array(strip_tags($version_old['content'])); //without <> tags
                $lines2 = array(strip_tags($version_new['content'])); //without <> tags
                $diff = new Text_Diff($lines1, $lines2);
                $renderer = new Text_Diff_Renderer_inline();
                echo '<style>del{background:#fcc}ins{background:#cfc}</style>'.$renderer->render($diff); // Code inline
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

// Recent changes
// @todo rss feed
if ($action =='recentchanges') {
    $wiki->recentChanges($page, $action);
}

// All pages
if ($action == 'allpages') {
    $wiki->allPages($action);
}

// Discuss pages
if ($action == 'discuss') {
    $wiki->getDiscuss($page);
}

Display::display_footer();
