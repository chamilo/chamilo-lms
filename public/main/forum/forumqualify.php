<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumThread;

/**
 * @todo fix all this qualify files avoid including files, use classes POO jmontoya
 */
require_once __DIR__.'/../inc/global.inc.php';

$htmlHeadXtra[] = api_get_jquery_libraries_js(['jquery-ui', 'jquery-upload']);
$htmlHeadXtra[] = '<script>

function check_unzip() {
    if (document.upload.unzip.checked){
        document.upload.if_exists[0].disabled=true;
        document.upload.if_exists[1].checked=true;
        document.upload.if_exists[2].disabled=true;
    } else {
        document.upload.if_exists[0].checked=true;
        document.upload.if_exists[0].disabled=false;
        document.upload.if_exists[2].disabled=false;
    }
}
function setFocus() {
    $("#title_file").focus();
}
</script>';
// The next javascript script is to manage ajax upload file
$htmlHeadXtra[] = api_get_jquery_libraries_js(['jquery-ui', 'jquery-upload']);

// Recover Thread ID, will be used to generate delete attachment URL to do ajax
$threadId = isset($_REQUEST['thread']) ? (int) ($_REQUEST['thread']) : 0;
$forumId = isset($_REQUEST['forum']) ? (int) ($_REQUEST['forum']) : 0;

$ajaxUrl = api_get_path(WEB_AJAX_PATH).'forum.ajax.php?'.api_get_cidreq();
// The next javascript script is to delete file by ajax
$htmlHeadXtra[] = '<script>
$(function () {
    $(document).on("click", ".deleteLink", function(e) {
        e.preventDefault();
        e.stopPropagation();
        var l = $(this);
        var id = l.closest("tr").attr("id");
        var filename = l.closest("tr").find(".attachFilename").html();
        if (confirm("'.get_lang('Are you sure to delete').'", filename)) {
            $.ajax({
                type: "POST",
                url: "'.$ajaxUrl.'&a=delete_file&attachId=" + id +"&thread='.$threadId.'&forum='.$forumId.'",
                dataType: "json",
                success: function(data) {
                    if (data.error == false) {
                        l.closest("tr").remove();
                        if ($(".files td").length < 1) {
                            $(".files").closest(".control-group").hide();
                        }
                    }
                }
            })
        }
    });
});
</script>';

api_protect_course_script(true);

$nameTools = get_lang('Forums');
$this_section = SECTION_COURSES;
$message = '';
// are we in a lp ?
$origin = api_get_origin();

$currentUserId = api_get_user_id();

/**
 * Normalize user id from GET.
 * We accept both "user_id" and legacy "user" keys.
 */
$userParamId = isset($_GET['user']) ? (int) $_GET['user'] : null;
$userIdToQualify = isset($_GET['user_id']) ? (int) $_GET['user_id'] : $userParamId;

$forumId = isset($_GET['forum']) ? (int) ($_GET['forum']) : 0;
$threadId = isset($_GET['thread']) ? (int) ($_GET['thread']) : 0;
api_block_course_item_locked_by_gradebook($threadId, LINK_FORUM_THREAD);
$nameTools = get_lang('Forums');
$allowed_to_edit = api_is_allowed_to_edit(null, true);

$repo = Container::getForumRepository();
$repoThread = Container::getForumThreadRepository();

/** @var CForum $forumEntity */
$forumEntity = $repo->find($forumId);

/** @var CForumThread $threadEntity */
$threadEntity = $repoThread->find($threadId);

$course = api_get_course_entity();
$session = api_get_session_entity();

$allowToQualify = false;
if ($allowed_to_edit) {
    $allowToQualify = true;
} else {
    $allowToQualify = $threadEntity->isThreadPeerQualify()
        && $forumEntity->isVisible($course)
        && null !== $userIdToQualify
        && $userIdToQualify !== $currentUserId;
}

if (!$allowToQualify) {
    api_not_allowed(true);
}

// Show max qualify in my form
$maxQualify = showQualify('2', (int) $userIdToQualify, $threadId);
$score = 0;

if (isset($_POST['idtextqualify'])) {
    $score = (float) $_POST['idtextqualify'];

    if ($score <= $maxQualify) {
        saveThreadScore(
            $threadEntity,
            (int) $userIdToQualify,
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
        Display::return_message(get_lang('Grade cannot exceed max score'), 'error')
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
$category = $forumEntity->getForumCategory();
$groupId = api_get_group_id();

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('Assessments'),
    ];
}

$search = isset($_GET['search']) ? Security::remove_XSS(urlencode($_GET['search'])) : '';

if ('learnpath' === $origin) {
    Display::display_reduced_header();
} else {
    if (!empty($groupId)) {
        $group_properties = GroupManager::get_group_properties($groupId);
        $interbreadcrumb[] = [
            'url' => '../group/group.php?'.api_get_cidreq(),
            'name' => get_lang('Groups'),
        ];
        $interbreadcrumb[] = [
            'url' => '../group/group_space.php?'.api_get_cidreq(),
            'name' => get_lang('Group area').' ('.$group_properties['name'].')',
        ];
        $interbreadcrumb[] = [
            'url' => 'viewforum.php?'.api_get_cidreq().'&forum='.$forumId.'&search='.$search,
            'name' => prepare4display($forumEntity->getTitle()),
        ];
        if ('PostDeletedSpecial' != $message) {
            $interbreadcrumb[] = [
                'url' => 'viewthread.php?'.api_get_cidreq().'&forum='.$forumId.'&thread='.$threadId,
                'name' => prepare4display($threadEntity->getTitle()),
            ];
        }

        $interbreadcrumb[] = [
            'url' => '#',
            'name' => get_lang('Grade thread'),
        ];

        // the last element of the breadcrumb navigation is already set in interbreadcrumb, so give empty string
        Display::display_header('');
        Display::page_subheader2($nameTools);
    } else {
        $interbreadcrumb[] = [
            'url' => 'index.php?'.api_get_cidreq().'&search='.$search,
            'name' => $nameTools,
        ];
        $interbreadcrumb[] = [
            'url' => 'index.php?'.api_get_cidreq().'&forumcategory='.$category->getIid().'&search='.$search,
            'name' => prepare4display($category->getTitle()),
        ];
        $interbreadcrumb[] = [
            'url' => 'viewforum.php?'.api_get_cidreq().'&forum='.$forumId.'&search='.$search,
            'name' => prepare4display($forumEntity->getTitle()),
        ];

        if ('PostDeletedSpecial' != $message) {
            $interbreadcrumb[] = [
                'url' => 'viewthread.php?'.api_get_cidreq().'&forum='.$forumId.'&thread='.$threadId,
                'name' => prepare4display($threadEntity->getTitle()),
            ];
        }
        // the last element of the breadcrumb navigation is already set in interbreadcrumb, so give empty string
        $interbreadcrumb[] = [
            'url' => '#',
            'name' => get_lang('Grade thread'),
        ];
        Display::display_header('');
    }
}

echo '<div class="ch-container mx-auto w-full px-4 py-6 space-y-6 bg-gray-10">';

$postId = isset($_GET['id']) ? (int) ($_GET['id']) : 0;
$repoPost = Container::getForumPostRepository();
$postEntity = !empty($postId) ? $repoPost->find($postId) : null;
$action = isset($_GET['action']) ? $_GET['action'] : '';

$currentUrl = api_get_self().'?forum='.$forumId.'&'.api_get_cidreq().'&thread='.$threadId;

if ('delete' === $action
    && isset($_GET['content'], $_GET['id'])
    && api_is_allowed_to_edit(false, true)
) {
    deletePost($postEntity);
    api_location($currentUrl);
}
if (('invisible' === $action || 'visible' === $action)
    && isset($_GET['id']) && api_is_allowed_to_edit(false, true)
) {
    approvePost($postEntity, $action);
    api_location($currentUrl);
}
if ('move' === $action && isset($_GET['post'])) {
    $message = move_post_form();
}

if (!empty($message)) {
    echo '<div class="w-full mb-4">';
    echo Display::return_message(get_lang($message), 'confirm');
    echo '</div>';
}

// show qualifications history
$type = isset($_GET['type']) ? $_GET['type'] : '';
$historyList = getThreadScoreHistory((int) $userIdToQualify, $threadId, $type);

$counter = count($historyList);

// Show current qualify in my form
$qualify = current_qualify_of_thread(
    $threadId,
    api_get_session_id(),
    (int) $userIdToQualify
);

$result = get_statistical_information(
    $threadId,
    (int) $userIdToQualify,
    api_get_course_int_id()
);

$userQueryId = (int) $userIdToQualify;
$url = api_get_path(WEB_CODE_PATH).'forum/forumqualify.php?'.
    api_get_cidreq().'&forum='.$forumId.'&thread='.$threadId.'&user='.$userQueryId.'&user_id='.$userQueryId;

$userToQualifyInfo = api_get_user_info((int) $userIdToQualify);

echo '<section class="w-full">
    <div class="bg-white border border-gray-20 rounded-2xl shadow-sm px-6 py-5 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div class="space-y-1">
            <p class="text-sm text-gray-50">'.get_lang('Grade thread').'</p>
            <h1 class="text-xl font-semibold text-gray-90">'.htmlspecialchars($userToQualifyInfo['complete_name']).'</h1>
            <p class="text-sm text-gray-50">'.get_lang('Thread').': '.prepare4display($threadEntity->getTitle()).'</p>
        </div>
        <div class="grid grid-cols-2 gap-4 text-right text-sm text-gray-50">
            <div class="flex flex-col">
                <span class="text-xs uppercase tracking-wide">'.get_lang('Users in course').'</span>
                <span class="mt-1 text-base font-semibold text-gray-90">'.$result['user_course'].'</span>
            </div>
            <div class="flex flex-col">
                <span class="text-xs uppercase tracking-wide">'.get_lang('Number of posts').'</span>
                <span class="mt-1 text-base font-semibold text-gray-90">'.$result['post'].'</span>
            </div>
            <div class="flex flex-col">
                <span class="text-xs uppercase tracking-wide">'.get_lang('Number of posts for this user').'</span>
                <span class="mt-1 text-base font-semibold text-gray-90">'.$result['user_post'].'</span>
            </div>
            <div class="flex flex-col">
                <span class="text-xs uppercase tracking-wide">'.get_lang('Posts by user').'</span>
                <span class="mt-1 text-base font-semibold text-gray-90">'.
    ($result['post'] > 0 ? round($result['user_post'] / $result['post'], 2) : 0)
    .'</span>
            </div>
        </div>
    </div>
</section>';

$form = new FormValidator('forum-thread-qualify', 'post', $url);

$form->addText(
    'idtextqualify',
    [get_lang('Score'), get_lang('Max score').' '.$maxQualify],
    $qualify
);

$rows = get_thread_user_post($course, $threadId, (int) $userIdToQualify);

if (isset($rows)) {
    $counter = 1;

    echo '<section class="w-full space-y-4">';
    foreach ($rows as $row) {
        $style = '';
        if ('0' == $row['status']) {
            $style = " id='post".$post_en."' class=\"hide-me\" style=\"border:1px solid red; display:none; background-color:#F7F7F7; width:95%; margin: 0px 0px 4px 40px;\" ";
        } else {
            $post_en = $row['post_parent_id'];
        }

        if ('0' == $row['user_id']) {
            $name = prepare4display($row['poster_name']);
        } else {
            $name = api_get_person_name($row['firstname'], $row['lastname']);
        }
        if (1 == $counter) {
            echo '<div class="mt-4">';
            echo Display::page_subheader($name);
            echo '</div>';
        }

        echo '<div '.$style.'>';
        echo '<table class="data_table w-full bg-white border border-gray-20 rounded-2xl shadow-sm overflow-hidden">';

        if ('0' == $row['visible']) {
            $titleclass = 'forum_message_post_title_2_be_approved text-gray-90 font-semibold text-base';
            $messageclass = 'forum_message_post_text_2_be_approved text-gray-90 text-sm leading-relaxed';
            $leftclass = 'forum_message_left_2_be_approved align-top bg-support-2 border-r border-gray-20 px-4 py-3 text-xs text-gray-90';
        } else {
            $titleclass = 'forum_message_post_title text-gray-90 font-semibold text-base';
            $messageclass = 'forum_message_post_text text-gray-90 text-sm leading-relaxed';
            $leftclass = 'forum_message_left align-top bg-support-2 border-r border-gray-20 px-4 py-3 text-xs text-gray-90';
        }

        echo '<tr>';
        echo "<td rowspan=\"3\" class=\"$leftclass\">";
        echo '<div class="space-y-1">';
        echo '<div class="font-semibold">'.get_lang('Date').'</div>';
        echo '<div class="text-gray-50">';
        echo api_convert_and_format_date($row['post_date'], DATE_TIME_FORMAT_LONG);
        echo '</div>';
        echo '</div>';
        echo '</td>';

        // The post title
        echo "<td class=\"$titleclass px-4 pt-3 pb-1\">".prepare4display($row['title']).'</td>';
        echo '</tr>';

        // The post message
        echo '<tr>';
        echo "<td class=\"$messageclass px-4 pb-3\">".prepare4display($row['post_text']).'</td>';
        echo '</tr>';

        // The check if there is an attachment
        $attachment_list = get_attachment($row['iid']);
        if (!empty($attachment_list)) {
            echo '<tr><td class="px-4 pb-3 align-middle">';
            $realname = $attachment_list['path'];
            $user_filename = $attachment_list['filename'];
            echo '<div class="flex items-center gap-2 text-sm">';
            echo Display::getMdiIcon('paperclip', 'ch-tool-icon', '', ICON_SIZE_SMALL, get_lang('Attachment'));
            echo '<a class="underline text-primary" href="download.php?file=';
            echo $realname;
            echo ' "> '.$user_filename.' </a>';
            echo '<span class="forum_attach_comment text-gray-50">'.$attachment_list['comment'].'</span>';
            echo '</div>';
            echo '</td></tr>';
        }

        echo '</table>';
        echo '</div>';

        $counter++;
    }
    echo '</section>';
}

$form->addButtonSave(get_lang('Grade this thread'));
$form->setDefaults(['idtextqualify' => $qualify]);

echo '<section class="w-full mt-4 bg-white border border-gray-20 rounded-2xl shadow-sm px-6 py-5">';
$form->display();
echo '</section>';

// Show past data
if (api_is_allowed_to_edit() && $counter > 0) {
    echo '<section class="w-full mt-8" id="history">';
    echo '<h2 class="text-lg font-semibold text-gray-90">'.get_lang('Score changes history').'</h2>';

    if (isset($_GET['type']) && 'false' === $_GET['type']) {
        $buttons = '<a
            class="btn btn--plain"
            href="forumqualify.php?'.api_get_cidreq().'&forum='.$forumId.'&origin='.$origin.'&thread='.$threadId.'&user='.$userQueryId.'&user_id='.$userQueryId.'&type=true&idtextqualify='.$score.'#history">'.
            get_lang('more recent').'</a>
            <a class="btn btn--plain disabled">'.get_lang('older').'</a>';
    } else {
        $buttons = '<a class="btn btn--plain">'.get_lang('more recent').'</a>
            <a
                class="btn btn--plain"
                href="forumqualify.php?'.api_get_cidreq().'&forum='.$forumId.'&origin='.$origin.'&thread='.$threadId.'&user='.$userQueryId.'&user_id='.$userQueryId.'&type=false&idtextqualify='.$score.'#history">'.
            get_lang('older').'</a>';
    }

    $table_list = '<div class="mt-3 mb-3 flex flex-wrap items-center gap-2">'.$buttons.'</div>';
    $table_list .= '<div class="overflow-x-auto">';
    $table_list .= '<table class="table w-full text-sm bg-white border border-gray-20 rounded-2xl shadow-sm">';
    $table_list .= '<tr class="bg-gray-15">';
    $table_list .= '<th class="px-4 py-2 text-left w-1/2">'.get_lang('Who changed').'</th>';
    $table_list .= '<th class="px-4 py-2 text-left w-1/6">'.get_lang('Note changed').'</th>';
    $table_list .= '<th class="px-4 py-2 text-left w-1/3">'.get_lang('Date changed').'</th>';
    $table_list .= '</tr>';

    for ($i = 0; $i < count($historyList); $i++) {
        $userInfo = api_get_user_info($historyList[$i]['qualify_user_id']);
        $table_list .= '<tr class="border-t border-gray-20">';
        $table_list .= '<td class="px-4 py-2">'.$userInfo['complete_name'].'</td>';
        $table_list .= '<td class="px-4 py-2">'.$historyList[$i]['qualify'].'</td>';
        $table_list .= '<td class="px-4 py-2">'.api_convert_and_format_date(
                $historyList[$i]['qualify_time'],
                DATE_TIME_FORMAT_LONG
            );
        $table_list .= '</td></tr>';
    }
    $table_list .= '</table>';
    $table_list .= '</div>';

    echo $table_list;
    echo '</section>';
}

echo '</div>';

if ('learnpath' !== $origin) {
    Display::display_footer();
}
