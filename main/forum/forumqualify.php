<?php
/* For licensing terms, see /license.txt */

/**
 * 	@package chamilo.forum
 *  @todo fix all this qualify files avoid including files, use classes POO jmontoya 
 */

// name of the language file that needs to be included
$language_file = array('admin', 'forum');
require_once '../inc/global.inc.php';
require_once 'forumconfig.inc.php';
require_once 'forumfunction.inc.php';

$nameTools = get_lang('ToolForum');
$this_section = SECTION_COURSES;

$allowed_to_edit = api_is_allowed_to_edit(null,true);
if (!$allowed_to_edit) {
    api_not_allowed(true);
}

//are we in a lp ?
$origin = '';
if (isset($_GET['origin'])) {
    $origin =  Security::remove_XSS($_GET['origin']);
}

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

block_course_item_locked_by_gradebook($_GET['thread'], LINK_FORUM_THREAD);
           
$nameTools = get_lang('ToolForum');

/*     Including necessary files */
$htmlHeadXtra[] = '<script type="text/javascript" language="javascript">
                    $(document).ready(function(){ $(\'.hide-me\').slideUp() });
                    function hidecontent(content){ $(content).slideToggle(\'normal\'); }
</script>';
/*         MAIN DISPLAY SECTION */
/*
    Retrieving forum and forum categorie information
*/
// we are getting all the information about the current forum and forum category.
// note pcool: I tried to use only one sql statement (and function) for this
// but the problem is that the visibility of the forum AND forum cateogory are stored in the item_property table
$current_thread=get_thread_information($_GET['thread']); // note: this has to be validated that it is an existing thread
$current_forum=get_forum_information($current_thread['forum_id']); // note: this has to be validated that it is an existing forum.
$current_forum_category=get_forumcategory_information($current_forum['forum_category']);
$group_id = api_get_group_id();
        
/*
    Header and Breadcrumbs
*/
if (isset($_SESSION['gradebook'])){
    $gradebook=	$_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook=='view') {
    $interbreadcrumb[]= array (
            'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
            'name' => get_lang('ToolGradebook')
        );
}

if ($origin=='learnpath') {
    Display::display_reduced_header();
} else {
    if (!empty($group_id)) {
        $group_properties  = GroupManager :: get_group_properties($_clean['toolgroup']);
        $interbreadcrumb[] = array ("url" => "../group/group.php", "name" => get_lang('Groups'));
        $interbreadcrumb[] = array ("url"=>"../group/group_space.php?gidReq=".$group_id, "name"=> get_lang('GroupSpace').' ('.$group_properties['name'].')');
        $interbreadcrumb[]=array("url" => "viewforum.php?forum=".Security::remove_XSS($_GET['forum'])."&amp;origin=".$origin."&amp;search=".Security::remove_XSS(urlencode($_GET['search'])),"name" => prepare4display($current_forum['forum_title']));
        if ($message<>'PostDeletedSpecial') {
            $interbreadcrumb[]=array("url" => "viewthread.php?forum=".Security::remove_XSS($_GET['forum'])."&amp;gradebook=".$gradebook."&amp;thread=".Security::remove_XSS($_GET['thread']),"name" => prepare4display($current_thread['thread_title']));
        }
        $interbreadcrumb[]=array("url" => "#","name" => get_lang('QualifyThread'));
        // the last element of the breadcrumb navigation is already set in interbreadcrumb, so give empty string
        Display :: display_header('');
        api_display_tool_title($nameTools);
    } else {

        $info_thread=get_thread_information(Security::remove_XSS($_GET['thread']));
        $interbreadcrumb[]=array("url" => "index.php?gradebook=$gradebook&amp;search=".Security::remove_XSS(urlencode($_GET['search'])),"name" => $nameTools);
        $interbreadcrumb[]=array("url" => "viewforumcategory.php?forumcategory=".$current_forum_category['cat_id']."&amp;search=".Security::remove_XSS(urlencode($_GET['search'])),"name" => prepare4display($current_forum_category['cat_title']));

        $interbreadcrumb[]=array("url" => "viewforum.php?forum=".Security::remove_XSS($_GET['forum'])."&amp;origin=".$origin."&amp;search=".Security::remove_XSS(urlencode($_GET['search'])),"name" => prepare4display($current_forum['forum_title']));

        if ($message<>'PostDeletedSpecial') {
            if (isset($_GET['gradebook']) and $_GET['gradebook']=='view') {
                $info_thread=get_thread_information(Security::remove_XSS($_GET['thread']));
                $interbreadcrumb[]=array("url" => "viewthread.php?forum=".$info_thread['forum_id']."&amp;gradebook=".$gradebook."&amp;thread=".Security::remove_XSS($_GET['thread']),"name" => prepare4display($current_thread['thread_title']));
            } else {
                $interbreadcrumb[]=array("url" => "viewthread.php?forum=".Security::remove_XSS($_GET['forum'])."&amp;gradebook=".$gradebook."&amp;thread=".Security::remove_XSS($_GET['thread']),"name" => prepare4display($current_thread['thread_title']));
            }
        }
        // the last element of the breadcrumb navigation is already set in interbreadcrumb, so give empty string
        $interbreadcrumb[]=array("url" => "#","name" => get_lang('QualifyThread'));
        Display :: display_header('');        
    }
}


/*
    Is the user allowed here?
*/
// if the user is not a course administrator and the forum is hidden
// then the user is not allowed here.
if (!api_is_allowed_to_edit(false,true) AND ($current_forum['visibility']==0 OR $current_thread['visibility']==0)) {
    $forum_allow = forum_not_allowed_here();
    if ($forum_allow === false) {
        exit;
    }
}


/*
    Actions
*/
if ($_GET['action']=='delete' && isset($_GET['content']) && isset($_GET['id']) && api_is_allowed_to_edit(false,true)) {
    $message=delete_post($_GET['id']); // note: this has to be cleaned first
}
if (($_GET['action']=='invisible' || $_GET['action']=='visible') && isset($_GET['id']) && api_is_allowed_to_edit(false,true)) {
    $message=approve_post($_GET['id'],$_GET['action']); // note: this has to be cleaned first
}
if ($_GET['action']=='move' and isset($_GET['post'])) {
    $message = move_post_form();
}

/*
    Display the action messages
*/
if (!empty($message)) {
    Display :: display_confirmation_message(get_lang($message));
}

if ($message<>'PostDeletedSpecial') {// in this case the first and only post of the thread is removed
    // this increases the number of times the thread has been viewed
    increase_thread_view($_GET['thread']);

    /*
        Action Links
    */
    // the reply to thread link should only appear when the forum_category is not locked AND the forum is not locked AND the thread is not locked.
    // if one of the three levels is locked then the link should not be displayed
    if (($current_forum_category && $current_forum_category['locked']==0) AND $current_forum['locked']==0 AND $current_thread['locked']==0 OR api_is_allowed_to_edit(false,true)) {
        // The link should only appear when the user is logged in or when anonymous posts are allowed.
        if ($_user['user_id'] OR ($current_forum['allow_anonymous']==1 AND !$_user['user_id'])) {
            //reply link
            /*echo '<a href="reply.php?'.api_get_cidreq().'&amp;forum='.Security::remove_XSS($_GET['forum']).'&amp;thread='.Security::remove_XSS($_GET['thread']).'&amp;action=replythread&amp;origin='.$origin.'">'.get_lang('ReplyToThread').'</a>';*/

            //new thread link
            if (api_is_allowed_to_edit(false,true) OR ($current_forum['allow_new_threads']==1 AND isset($_user['user_id'])) OR ($current_forum['allow_new_threads']==1 AND !isset($_user['user_id']) AND $current_forum['allow_anonymous']==1)) {
                if ($current_forum['locked'] <> 1 AND $current_forum['locked'] <> 1) {
                    echo '&nbsp;&nbsp;';                    
                } else {
                    echo get_lang('ForumLocked');
                }
            }
        }
    }
    // note: this is to prevent that some browsers display the links over the table (FF does it but Opera doesn't)
    echo '&nbsp;';

    /*
        Display Forum Category and the Forum information
    */
    if (!$_SESSION['view']) {
        $viewmode=$current_forum['default_view'];
    } else {
        $viewmode=$_SESSION['view'];
    }

    $viewmode_whitelist=array('flat', 'threaded', 'nested');
    if (isset($_GET['view']) and in_array($_GET['view'],$viewmode_whitelist)) {
        $viewmode=Database::escape_string($_GET['view']);
        $_SESSION['view']=$viewmode;
    }
    if (empty($viewmode)) {
        $viewmode = 'flat';
    }

    /*
        Display Forum Category and the Forum information
    */
    // we are getting all the information about the current forum and forum category.
    // note pcool: I tried to use only one sql statement (and function) for this
    // but the problem is that the visibility of the forum AND forum cateogory are stored in the item_property table
    echo "<table class=\"data_table\">";

    // the thread
    echo "<tr><th style=\"padding-left:5px;\" align=\"left\" colspan=\"6\">";
    echo '<span class="forum_title">'.prepare4display($current_thread['thread_title']).'</span><br />';

    if ($origin!='learnpath') {
        echo '<span class="forum_low_description">'.prepare4display($current_forum_category['cat_title']).' - ';
    }

    echo prepare4display($current_forum['forum_title']).'<br />';
    echo "</th>";
    echo "</tr>";
    echo '<span>'.prepare4display($current_thread['thread_comment']).'</span>';
    echo "</table>";

    include_once 'viewpost.inc.php';
} // if ($message<>'PostDeletedSpecial') // in this case the first and only post of the thread is removed

if ($allowed_to_edit) {
    $current_thread=get_thread_information($_GET['thread']);    
    $threadid=$current_thread['thread_id'];
    //show current qualify in my form
    $qualify=current_qualify_of_thread($threadid, api_get_session_id());
    //show max qualify in my form
    $max_qualify=show_qualify('2',$user_id,$threadid);
    require_once 'forumbody.inc.php';
    $value_return = store_theme_qualify($user_id,$threadid,$_REQUEST['idtextqualify'],api_get_user_id(),date("Y-m-d H:i:s"),api_get_session_id());
    $url='cidReq='.Security::remove_XSS($_GET['cidReq']).'&amp;forum='.Security::remove_XSS($_GET['forum']).'&amp;thread='.Security::remove_XSS($_GET['thread']).'&amp;post='.Security::remove_XSS($_GET['post']).'&amp;origin='.$origin.'&amp;user_id='.Security::remove_XSS($_GET['user_id']);
    $current_qualify_thread=show_qualify('1',$user_id,$threadid);

    if ($value_return[0]!=$_REQUEST['idtextqualify'] && $value_return[1]=='update') {
        store_qualify_historical('1','',$_GET['forum'],$user_id,$threadid,$_REQUEST['idtextqualify'],api_get_user_id());
    }

    if (!empty($_REQUEST['idtextqualify']) && $_REQUEST['idtextqualify'] > $max_qualify) {
        $return_message = get_lang('QualificationCanNotBeGreaterThanMaxScore');
        Display :: display_error_message($return_message,false);
    }
    
    // show qualifications history    
    $qualify_historic = get_historical_qualify($user_id, $threadid, $_GET['type']);    
    $counter = count($qualify_historic);
    
    $act_qualify = $_REQUEST['idtextqualify'];
    if ($counter>0) {
        if (isset($_GET['gradebook'])){
            $view_gradebook='&amp;gradebook=view';
        }
        echo '<h4>'.get_lang('QualificationChangesHistory').'</h4>';
        if ($_GET['type'] == 'false') {
            $buttons = '<a class="btn" href="forumqualify.php?'.api_get_cidreq().'&amp;forum='.Security::remove_XSS($_GET['forum']).'&amp;origin='.$origin.'&amp;thread='.$threadid.'&amp;user='.Security::remove_XSS($_GET['user']).'&amp;user_id='.Security::remove_XSS($_GET['user_id']).'&amp;type=true&amp;idtextqualify='.$act_qualify.$view_gradebook.'#history">'.
                    get_lang('MoreRecent').'</a> <a class="btn disabled" >'.get_lang('Older').'</a>';
        } else {
            $buttons =  '<a class="btn disabled">'.get_lang('MoreRecent').'</a>
                        <a class="btn" href="forumqualify.php?'.api_get_cidreq().'&amp;forum='.Security::remove_XSS($_GET['forum']).'&amp;origin='.$origin.'&amp;thread='.$threadid.'&amp;user='.Security::remove_XSS($_GET['user']).'&amp;user_id='.Security::remove_XSS($_GET['user_id']).'&amp;type=false&amp;idtextqualify='.$act_qualify.$view_gradebook.'#history">'.
                    get_lang('Older').'</a>';
        }
        
        $table_list.= '<br /><div class="btn-group">'.$buttons.'</div>';
        $table_list.= '<br /><table class="data_table">';
        $table_list.= '<tr>';
        $table_list.= '<th width="50%">'.get_lang('WhoChanged').'</th>';
        $table_list.= '<th width="10%">'.get_lang('NoteChanged').'</th>';
        $table_list.= '<th width="40%">'.get_lang('DateChanged').'</th>';
        $table_list.= '</tr>';

        for ($i=0;$i<count($qualify_historic);$i++) {
            $my_user_info=api_get_user_info($qualify_historic[$i]['qualify_user_id']);
            $name = api_get_person_name($my_user_info['firstName'], $my_user_info['lastName']);
            $table_list.= '<tr class="'.$class.'"><td>'.$name.'</td>';
            $table_list.= '<td>'.$qualify_historic[$i]['qualify'].'</td>';
            $table_list.= '<td>'.api_convert_and_format_date($qualify_historic[$i]['qualify_time'], DATE_TIME_FORMAT_LONG).'</td></tr>';
        }
        $table_list.= '</table>';
        echo $table_list;
    } else {
        //echo get_lang('NotChanged');
    }
} else {
    api_not_allowed();
}

/*         FOOTER   */
if ($origin!='learnpath') {
    Display :: display_footer();
}