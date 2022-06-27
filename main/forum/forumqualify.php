<?php

/* For licensing terms, see /license.txt */

/**
 * @todo fix all this qualify files avoid including files, use classes POO jmontoya
 */
require_once __DIR__.'/../inc/global.inc.php';
require_once 'forumfunction.inc.php';

api_protect_course_script(true);

$nameTools = get_lang('ToolForum');
$this_section = SECTION_COURSES;
$message = '';
//are we in a lp ?
$origin = api_get_origin();

$currentUserId = api_get_user_id();
$userIdToQualify = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
$forumId = isset($_GET['forum']) ? intval($_GET['forum']) : 0;
api_block_course_item_locked_by_gradebook($_GET['thread'], LINK_FORUM_THREAD);
$nameTools = get_lang('ToolForum');

$allowed_to_edit = api_is_allowed_to_edit(null, true);
$currentThread = get_thread_information($forumId, $_GET['thread']);
$forumId = $currentThread['forum_id'];
$currentForum = get_forums($currentThread['forum_id']);
$threadId = $currentThread['thread_id'];

$allowToQualify = false;
if ($allowed_to_edit) {
    $allowToQualify = true;
} else {
    $allowToQualify = $currentThread['thread_peer_qualify'] == 1 && $currentForum['visibility'] == 1 && $userIdToQualify != $currentUserId;
}

if (!$allowToQualify) {
    api_not_allowed(true);
}

// Show max qualify in my form
$maxQualify = showQualify('2', $userIdToQualify, $threadId);
$score = 0;

if (isset($_POST['idtextqualify'])) {
    $score = floatval($_POST['idtextqualify']);

    if ($score <= $maxQualify) {
        saveThreadScore(
            $currentThread,
            $userIdToQualify,
            $threadId,
            $score,
            api_get_utc_datetime(),
            api_get_session_id()
        );

        header(
            'Location: '.api_get_path(WEB_CODE_PATH).'forum/viewforum.php?'.api_get_cidreq().'&'
            .http_build_query([
                'forum' => $forumId,
                'action' => 'liststd',
                'content' => 'thread',
                'id' => $threadId,
                'list' => 'qualify',
            ])
        );
        exit;
    }

    Display::addFlash(
        Display::return_message(get_lang('QualificationCanNotBeGreaterThanMaxScore'), 'error')
    );
}

/*     Including necessary files */
$htmlHeadXtra[] = '<script>
    $(function() {
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

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('ToolGradebook'),
    ];
}

$search = isset($_GET['search']) ? Security::remove_XSS(urlencode($_GET['search'])) : '';

if ($origin == 'learnpath') {
    Display::display_reduced_header();
} else {
    if (!empty($groupId)) {
        $group_properties = GroupManager::get_group_properties($groupId);
        $interbreadcrumb[] = [
            "url" => "../group/group.php?".api_get_cidreq(),
            "name" => get_lang('Groups'),
        ];
        $interbreadcrumb[] = [
            "url" => "../group/group_space.php?".api_get_cidreq(),
            "name" => get_lang('GroupSpace').' ('.$group_properties['name'].')',
        ];
        $interbreadcrumb[] = [
            "url" => "viewforum.php?".api_get_cidreq()."&forum=".intval($_GET['forum'])."&search=".$search,
            "name" => prepare4display($currentForum['forum_title']),
        ];
        if ($message != 'PostDeletedSpecial') {
            $interbreadcrumb[] = [
                "url" => "viewthread.php?".api_get_cidreq()."&forum=".intval($_GET['forum'])."&thread=".intval($_GET['thread']),
                "name" => prepare4display($currentThread['thread_title']),
            ];
        }

        $interbreadcrumb[] = [
            "url" => "#",
            "name" => get_lang('QualifyThread'),
        ];

        // the last element of the breadcrumb navigation is already set in interbreadcrumb, so give empty string
        Display::display_header('');
        api_display_tool_title($nameTools);
    } else {
        $info_thread = get_thread_information($currentForum['forum_id'], $_GET['thread']);
        $interbreadcrumb[] = [
            "url" => "index.php?".api_get_cidreq()."&search=".$search,
            "name" => $nameTools,
        ];
        $interbreadcrumb[] = [
            "url" => "viewforumcategory.php?".api_get_cidreq()."&forumcategory=".$currentForumCategory['cat_id']."&search=".$search,
            "name" => prepare4display($currentForumCategory['cat_title']),
        ];
        $interbreadcrumb[] = [
            "url" => "viewforum.php?".api_get_cidreq()."&forum=".intval($_GET['forum'])."&search=".$search,
            "name" => prepare4display($currentForum['forum_title']),
        ];

        if ($message != 'PostDeletedSpecial') {
            $interbreadcrumb[] = [
                "url" => "viewthread.php?".api_get_cidreq()."&forum=".$info_thread['forum_id']."&thread=".intval($_GET['thread']),
                "name" => prepare4display($currentThread['thread_title']),
            ];
        }
        // the last element of the breadcrumb navigation is already set in interbreadcrumb, so give empty string
        $interbreadcrumb[] = [
            "url" => "#",
            "name" => get_lang('QualifyThread'),
        ];
        Display::display_header('');
    }
}

/*
    Actions
*/
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action == 'delete' &&
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
    echo Display::return_message(get_lang($message), 'confirm');
}

// show qualifications history
$type = isset($_GET['type']) ? $_GET['type'] : '';
$historyList = getThreadScoreHistory(
    $userIdToQualify,
    $threadId,
    $type
);

$counter = count($historyList);

// Show current qualify in my form
$qualify = current_qualify_of_thread(
    $threadId,
    api_get_session_id(),
    $_GET['user']
);

$result = get_statistical_information(
    $threadId,
    $_GET['user_id'],
    api_get_course_int_id()
);

$url = api_get_path(WEB_CODE_PATH).'forum/forumqualify.php?'.
    api_get_cidreq().'&forum='.intval($_GET['forum']).'&thread='.$threadId.'&user='.intval($_GET['user']).'&user_id='.intval($_GET['user']);

$userToQualifyInfo = api_get_user_info($userIdToQualify);
$form = new FormValidator('forum-thread-qualify', 'post', $url);
$form->addHeader($userToQualifyInfo['complete_name']);
$form->addLabel(get_lang('Thread'), $currentThread['thread_title']);
$form->addLabel(get_lang('CourseUsers'), $result['user_course']);
$form->addLabel(get_lang('PostsNumber'), $result['post']);
$form->addLabel(get_lang('NumberOfPostsForThisUser'), $result['user_post']);
$form->addLabel(
    get_lang('AveragePostPerUser'),
    round($result['user_post'] / $result['post'], 2)
);
$form->addText(
    'idtextqualify',
    [get_lang('Qualification'), get_lang('MaxScore').' '.$maxQualify],
    $qualify
);

$course = api_get_course_info();

$rows = get_thread_user_post($course['code'], $currentThread['thread_id'], $_GET['user']);
if (isset($rows)) {
    $counter = 1;
    foreach ($rows as $row) {
        if ($row['status'] == '0') {
            $style = " id = 'post".$post_en."' class=\"hide-me\" style=\"border:1px solid red; display:none; background-color:#F7F7F7; width:95%; margin: 0px 0px 4px 40px; \" ";
        } else {
            $style = '';
            $post_en = $row['post_parent_id'];
        }

        if ($row['user_id'] == '0') {
            $name = prepare4display($row['poster_name']);
        } else {
            $name = api_get_person_name($row['firstname'], $row['lastname']);
        }
        if ($counter == 1) {
            echo Display::page_subheader($name);
        }

        echo "<div ".$style."><table class=\"table table-hover table-striped data_table\">";

        if ($row['visible'] == '0') {
            $titleclass = 'forum_message_post_title_2_be_approved';
            $messageclass = 'forum_message_post_text_2_be_approved';
            $leftclass = 'forum_message_left_2_be_approved';
        } else {
            $titleclass = 'forum_message_post_title';
            $messageclass = 'forum_message_post_text';
            $leftclass = 'forum_message_left';
        }

        echo "<tr>";
        echo "<td rowspan=\"3\" class=\"$leftclass\">";
        echo '<br /><b>'.api_convert_and_format_date($row['post_date'], DATE_TIME_FORMAT_LONG).'</b><br />';
        echo "</td>";

        // The post title
        echo "<td class=\"$titleclass\">".prepare4display($row['post_title'])."</td>";
        echo "</tr>";

        // The post message
        echo "<tr >";
        echo "<td class=\"$messageclass\">".prepare4display($row['post_text'])."</td>";
        echo "</tr>";

        // The check if there is an attachment
        $attachment_list = get_attachment($row['post_id']);
        if (!empty($attachment_list)) {
            echo '<tr ><td height="50%">';
            $realname = $attachment_list['path'];
            $user_filename = $attachment_list['filename'];
            echo Display::return_icon('attachment.gif', get_lang('Attachment'));
            echo '<a href="download.php?file=';
            echo $realname;
            echo ' "> '.$user_filename.' </a>';
            echo '<span class="forum_attach_comment" >'.$attachment_list['comment'].'</span><br />';
            echo '</td></tr>';
        }

        echo "</table></div>";
        $counter++;
    }
}

$form->addButtonSave(get_lang('QualifyThisThread'));
$form->setDefaults(['idtextqualify' => $qualify]);
$form->display();

// Show past data
if (api_is_allowed_to_edit() && $counter > 0) {
    echo '<h4>'.get_lang('QualificationChangesHistory').'</h4>';
    if (isset($_GET['type']) && $_GET['type'] === 'false') {
        $buttons = '<a class="btn btn-default" href="forumqualify.php?'.api_get_cidreq().'&forum='.intval($_GET['forum']).'&origin='.$origin.'&thread='.$threadId.'&user='.intval($_GET['user']).'&user_id='.intval($_GET['user_id']).'&type=true&idtextqualify='.$score.'#history">'.
            get_lang('MoreRecent').'</a> <a class="btn btn-default disabled" >'.get_lang('Older').'</a>';
    } else {
        $buttons = '<a class="btn btn-default">'.get_lang('MoreRecent').'</a>
                        <a class="btn btn-default" href="forumqualify.php?'.api_get_cidreq().'&forum='.intval($_GET['forum']).'&origin='.$origin.'&thread='.$threadId.'&user='.intval($_GET['user']).'&user_id='.intval($_GET['user_id']).'&type=false&idtextqualify='.$score.'#history">'.
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
    $table_list .= '</table>';

    echo $table_list;
}

if ($origin != 'learnpath') {
    Display::display_footer();
}
