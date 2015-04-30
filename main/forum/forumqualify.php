<?php
/* For licensing terms, see /license.txt */

/**
 * 	@package chamilo.forum
 *  @todo fix all this qualify files avoid including files, use classes POO jmontoya
 */
require_once '../inc/global.inc.php';
require_once 'forumconfig.inc.php';
require_once 'forumfunction.inc.php';

$nameTools = get_lang('ToolForum');
$this_section = SECTION_COURSES;

$message = '';
//are we in a lp ?
$origin = '';
if (isset($_GET['origin'])) {
    $origin = Security::remove_XSS($_GET['origin']);
}

$currentUserId = api_get_user_id();
$userIdToQualify = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
api_block_course_item_locked_by_gradebook($_GET['thread'], LINK_FORUM_THREAD);
$nameTools = get_lang('ToolForum');

$allowed_to_edit = api_is_allowed_to_edit(null, true);
$currentThread = get_thread_information($_GET['thread']);
$currentForum = get_forum_information($currentThread['forum_id']);

$allowToQualify = false;
if ($allowed_to_edit) {
    $allowToQualify = true;
} else {
    $allowToQualify = $currentThread['thread_peer_qualify'] == 1 && $currentForum['visibility'] == 1 && $userIdToQualify != $currentUserId;
}

if (!$allowToQualify) {
    api_not_allowed(true);
}

/*     Including necessary files */
$htmlHeadXtra[] = '<script>
    $(document).ready(function(){
        $(\'.hide-me\').slideUp()
    });

    function hidecontent(content){
        $(content).slideToggle(\'normal\');
    }
</script>';

$currentForumCategory = get_forumcategory_information(
    $currentForum['forum_category']
);
$groupId = api_get_group_id();

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

if ($origin == 'learnpath') {
    Display::display_reduced_header();
} else {
    if (!empty($groupId)) {
        $group_properties  = GroupManager :: get_group_properties($groupId);
        $interbreadcrumb[] = array(
            "url" => "../group/group.php",
            "name" => get_lang('Groups'),
        );
        $interbreadcrumb[] = array(
            "url" => "../group/group_space.php?".api_get_cidreq(),
            "name"=> get_lang('GroupSpace').' ('.$group_properties['name'].')'
        );
        $interbreadcrumb[] = array(
            "url" => "viewforum.php?forum=".Security::remove_XSS($_GET['forum'])."&origin=".$origin."&search=".Security::remove_XSS(urlencode($_GET['search'])),
            "name" => prepare4display($currentForum['forum_title'])
        );
        if ($message <> 'PostDeletedSpecial') {
            $interbreadcrumb[]= array(
                "url" => "viewthread.php?forum=".Security::remove_XSS($_GET['forum'])."&gradebook=".$gradebook."&thread=".Security::remove_XSS($_GET['thread']),
                "name" => prepare4display($currentThread['thread_title'])
            );
        }

        $interbreadcrumb[] = array(
            "url" => "#",
            "name" => get_lang('QualifyThread'),
        );

        // the last element of the breadcrumb navigation is already set in interbreadcrumb, so give empty string
        Display :: display_header('');
        api_display_tool_title($nameTools);
    } else {

        $search = isset($_GET['search']) ? Security::remove_XSS(urlencode($_GET['search'])) : '';
        $info_thread = get_thread_information($_GET['thread']);
        $interbreadcrumb[] = array(
            "url" => "index.php?".api_get_cidreq()."&search=".$search,
            "name" => $nameTools);
        $interbreadcrumb[] = array(
            "url" => "viewforumcategory.php?forumcategory=".$currentForumCategory['cat_id']."&search=".$search,
            "name" => prepare4display($currentForumCategory['cat_title'])
        );
        $interbreadcrumb[] = array(
            "url" => "viewforum.php?forum=".Security::remove_XSS($_GET['forum'])."&origin=".$origin."&search=".$search,
            "name" => prepare4display($currentForum['forum_title'])
        );

        if ($message <> 'PostDeletedSpecial') {
            if (isset($_GET['gradebook']) and $_GET['gradebook']=='view') {
                $info_thread=get_thread_information(Security::remove_XSS($_GET['thread']));
                $interbreadcrumb[] = array(
                    "url" => "viewthread.php?".api_get_cidreq()."&forum=".$info_thread['forum_id']."&thread=".Security::remove_XSS($_GET['thread']),
                    "name" => prepare4display($currentThread['thread_title'])
                );
            } else {
                $interbreadcrumb[] = array(
                    "url" => "viewthread.php?".api_get_cidreq()."&forum=".Security::remove_XSS($_GET['forum'])."&thread=".Security::remove_XSS($_GET['thread']),
                    "name" => prepare4display($currentThread['thread_title'])
                );
            }
        }
        // the last element of the breadcrumb navigation is already set in interbreadcrumb, so give empty string
        $interbreadcrumb[]=array("url" => "#","name" => get_lang('QualifyThread'));
        Display :: display_header('');
    }
}

/*
    Actions
*/
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action =='delete' &&
    isset($_GET['content']) &&
    isset($_GET['id']) && api_is_allowed_to_edit(false, true)
) {
    $message = delete_post($_GET['id']);
}
if (($action == 'invisible' || $action == 'visible') &&
    isset($_GET['id']) && api_is_allowed_to_edit(false, true)
) {
    $message = approve_post($_GET['id'], $action);
}
if ($action == 'move' && isset($_GET['post'])) {
    $message = move_post_form();
}

/*
    Display the action messages
*/
if (!empty($message)) {
    Display :: display_confirmation_message(get_lang($message));
}

if ($message <> 'PostDeletedSpecial') {
    // in this case the first and only post of the thread is removed
    // this increases the number of times the thread has been viewed
    increase_thread_view($_GET['thread']);
    /*
        Action Links
    */
    // the reply to thread link should only appear when the forum_category is
    // not locked AND the forum is not locked AND the thread is not locked.
    // if one of the three levels is locked then the link should not be displayed
    if (($currentForumCategory && $currentForumCategory['locked'] == 0) &&
        $currentForum['locked'] == 0 &&
        $currentThread['locked'] == 0 || api_is_allowed_to_edit(false, true)
    ) {
        // The link should only appear when the user is logged in or when anonymous posts are allowed.
        if ($currentUserId ||
            ($currentForum['allow_anonymous']==1 && !$currentUserId)
        ) {
            //new thread link
            if (api_is_allowed_to_edit(false,true) ||
                ($currentForum['allow_new_threads'] == 1 && isset($currentUserId)) ||
                ($currentForum['allow_new_threads'] == 1 &&
                !isset($currentUserId) &&
                $currentForum['allow_anonymous'] == 1)
            ) {
                if ($currentForum['locked'] <> 1 &&
                    $currentForum['locked'] <> 1
                ) {
                    echo '&nbsp;&nbsp;';
                } else {
                    echo get_lang('ForumLocked');
                }
            }
        }
    }

    // note: this is to prevent that some browsers display the links over
    // the table (FF does it but Opera doesn't)
    echo '&nbsp;';

    /*
        Display Forum Category and the Forum information
    */
    if (!isset($_SESSION['view'])) {
        $viewmode = $currentForum['default_view'];
    } else {
        $viewmode = $_SESSION['view'];
    }

    $whiteList = array('flat', 'threaded', 'nested');
    if (isset($_GET['view']) && in_array($_GET['view'], $whiteList)) {
        $viewmode = Database::escape_string($_GET['view']);
        $_SESSION['view'] = $viewmode;
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
    echo "<table class=\"table\">";

    // the thread
    echo "<tr><th style=\"padding-left:5px;\" align=\"left\" colspan=\"6\">";
    echo '<span class="forum_title">'.prepare4display($currentThread['thread_title']).'</span><br />';

    if ($origin!='learnpath') {
        echo '<span class="forum_low_description">'.prepare4display($currentForumCategory['cat_title']).' - ';
    }

    echo prepare4display($currentForum['forum_title']).'<br />';
    echo "</th>";
    echo "</tr>";
    if (isset($currentThread['thread_comment'])) {
        echo '<span>'.prepare4display($currentThread['thread_comment']).'</span>';
    }
    echo "</table>";

    include_once 'viewpost.inc.php';
} // if ($message<>'PostDeletedSpecial') // in this case the first and only post of the thread is removed

if ($allowToQualify) {
    $currentThread = get_thread_information($_GET['thread']);
    $threadId = $currentThread['thread_id'];
    // Show max qualify in my form
    $maxQualify = showQualify('2', $userIdToQualify, $threadId);

    $score = isset($_POST['idtextqualify']) ? $_POST['idtextqualify'] : '';

    if ($score > $maxQualify) {
        Display:: display_error_message(
            get_lang('QualificationCanNotBeGreaterThanMaxScore'),
            false
        );
    }

    if (!empty($score)) {

        $saveResult = saveThreadScore(
            $currentThread,
            $userIdToQualify,
            $threadId,
            $score,
            api_get_user_id(),
            date("Y-m-d H:i:s"),
            api_get_session_id()
        );

        if ($saveResult == 'update') {
            saveThreadScoreHistory(
                '1',
                api_get_course_int_id(),
                $_GET['forum'],
                $userIdToQualify,
                $threadId,
                $score,
                api_get_user_id()
            );
        }
    }

    // show qualifications history
    $type = isset($_GET['type']) ? $_GET['type'] : '';
    $historyList = getThreadScoreHistory(
        $userIdToQualify,
        $threadId,
        $type
    );

    $counter = count($historyList);

    require_once 'forumbody.inc.php';

    if ($counter > 0) {
        if (isset($_GET['gradebook'])){
            $view_gradebook='&gradebook=view';
        }
        echo '<h4>'.get_lang('QualificationChangesHistory').'</h4>';
        if (isset($_GET['type']) && $_GET['type'] == 'false') {
            $buttons = '<a class="btn btn-default" href="forumqualify.php?'.api_get_cidreq().'&forum='.intval($_GET['forum']).'&origin='.$origin.'&thread='.$threadId.'&user='.intval($_GET['user']).'&user_id='.intval($_GET['user_id']).'&type=true&idtextqualify='.$score.$view_gradebook.'#history">'.
                    get_lang('MoreRecent').'</a> <a class="btn btn-default disabled" >'.get_lang('Older').'</a>';
        } else {
            $buttons = '<a class="btn btn-default">'.get_lang('MoreRecent').'</a>
                        <a class="btn btn-default" href="forumqualify.php?'.api_get_cidreq().'&forum='.intval($_GET['forum']).'&origin='.$origin.'&thread='.$threadId.'&user='.intval($_GET['user']).'&user_id='.intval($_GET['user_id']).'&type=false&idtextqualify='.$score.$view_gradebook.'#history">'.
                    get_lang('Older').'</a>';
        }

        $table_list = '<br /><div class="btn-group">'.$buttons.'</div>';
        $table_list .= '<br /><table class="table">';
        $table_list .= '<tr>';
        $table_list .= '<th width="50%">'.get_lang('WhoChanged').'</th>';
        $table_list .= '<th width="10%">'.get_lang('NoteChanged').'</th>';
        $table_list .= '<th width="40%">'.get_lang('DateChanged').'</th>';
        $table_list .= '</tr>';

        for ($i = 0; $i < count($historyList); $i++) {
            $userInfo = api_get_user_info($historyList[$i]['qualify_user_id']);
            $table_list .= '<tr><td>'.$userInfo['complete_name'].'</td>';
            $table_list .= '<td>'.$historyList[$i]['qualify'].'</td>';
            $table_list .= '<td>'.api_convert_and_format_date(
                $historyList[$i]['qualify_time'],
                DATE_TIME_FORMAT_LONG
            );
            $table_list .= '</td></tr>';
        }
        $table_list.= '</table>';

        echo $table_list;
    }
}

if ($origin!='learnpath') {
    Display :: display_footer();
}
