<?php
/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls for forum attachments
 * @package chamilo/forum
 * @author Daniel Barreto Alva <daniel.barreto@beeznest.com>
 */

require_once '../global.inc.php';
require_once api_get_path(SYS_CODE_PATH) . 'forum/forumfunction.inc.php';

// First, protect this script
api_protect_course_script(false);

/**
 * Main code
 */
// Create a default error response
$json = array(
    'error' => true,
    'errorMessage' => 'ERROR',
);
$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;

$current_forum = get_forum_information($_REQUEST['forum']);
$current_forum_category = get_forumcategory_information($current_forum['forum_category']);
$current_thread = get_thread_information($_REQUEST['thread']);

// Check if exist action
if (!empty($action)) {
    switch ($action) {
        case 'upload_file':
            if (!empty($_FILES) && !empty($_REQUEST['forum'])) {
                // The user is not allowed here if
                // 1. the forum category, forum or thread is invisible (visibility==0)
                // 2. the forum category, forum or thread is locked (locked <>0)
                // 3. if anonymous posts are not allowed
                // The only exception is the course manager
                // They are several pieces for clarity.
                if (!api_is_allowed_to_edit(null, true) AND
                    (
                        ($current_forum_category && $current_forum_category['visibility'] == 0) OR
                        $current_forum['visibility'] == 0
                    )
                ) {
                    $json['errorMessage'] = '1. the forum category, forum or thread is invisible (visibility==0)';
                    break;
                }
                if (!api_is_allowed_to_edit(null, true) AND
                    (
                        ($current_forum_category && $current_forum_category['locked'] <> 0) OR
                        $current_forum['locked'] <> 0 OR $current_thread['locked'] <> 0
                    )
                ) {
                    $json['errorMessage'] = '2. the forum category, forum or thread is locked (locked <>0)';
                    break;
                }
                if (api_is_anonymous() AND
                    $current_forum['allow_anonymous'] == 0
                ) {
                    $json['errorMessage'] = '3. if anonymous posts are not allowed';
                    break;
                }
                // If pass all previous control, user can edit post
                $courseId = isset($_REQUEST['c_id'])? intval($_REQUEST['c_id']) : api_get_course_int_id();
                $json['courseId'] = $courseId;
                $forumId = isset($_REQUEST['forum'])? intval($_REQUEST['forum']) : null;
                $json['forum'] = $forumId;
                $threadId = isset($_REQUEST['thread'])? intval($_REQUEST['thread']) : null;
                $json['thread'] = $threadId;
                $postId = isset($_REQUEST['postId'])? intval($_REQUEST['postId']) : null;
                $json['postId'] = $postId;

                if (!empty($courseId) &&
                    !is_null($forumId) &&
                    !is_null($threadId) &&
                    !is_null($postId)
                ) {
                    // Save forum attachment
                    $attachId = add_forum_attachment_file('', $postId);
                    if ($attachId !== false) {
                        // Get prepared array of attachment data
                        $array = getAttachedFiles(
                            $forumId,
                            $threadId,
                            $postId,
                            $attachId,
                            $courseId
                        );
                        // Check if array data is consistent
                        if (isset($array['name'])) {
                            $json['error'] = false;
                            $json['errorMessage'] = 'Success';
                            $json = array_merge($json, $array);
                        }
                    }
                }
            }
            break;
        case 'delete_file':
            // Check if set attachment ID and thread ID
            if (isset($_REQUEST['attachId']) && isset($_REQUEST['thread'])) {
                api_block_course_item_locked_by_gradebook($_REQUEST['thread'], LINK_FORUM_THREAD);
                // The user is not allowed here if
                // 1. the forum category, forum or thread is invisible (visibility==0)
                // 2. the forum category, forum or thread is locked (locked <>0)
                // 3. if anonymous posts are not allowed
                // 4. if editing of replies is not allowed
                // The only exception is the course manager
                // They are several pieces for clarity.
                if (!api_is_allowed_to_edit(null, true) AND
                    (
                        ($current_forum_category && $current_forum_category['visibility'] == 0) OR
                        $current_forum['visibility'] == 0)
                ) {
                    $json['errorMessage'] = '1. the forum category, forum or thread is invisible (visibility==0)';
                    break;
                }
                if (!api_is_allowed_to_edit(null, true) AND
                    (
                        ($current_forum_category && $current_forum_category['locked'] <> 0) OR
                        $current_forum['locked'] <> 0 OR $current_thread['locked'] <> 0
                    )
                ) {
                    $json['errorMessage'] = '2. the forum category, forum or thread is locked (locked <>0)';
                    break;
                }
                if (api_is_anonymous() AND $current_forum['allow_anonymous'] == 0) {
                    $json['errorMessage'] = '3. if anonymous posts are not allowed';
                    break;
                }
                $group_id = api_get_group_id();
                if (!api_is_allowed_to_edit(null, true) AND
                    $current_forum['allow_edit'] == 0 &&
                    ($group_id && !GroupManager::is_tutor_of_group(api_get_user_id(), $group_id))
                ) {
                    $json['errorMessage'] = '4. if editing of replies is not allowed';
                    break;
                }
                // If pass all previous control, user can edit post
                $attachId = $_REQUEST['attachId'];
                $threadId = $_REQUEST['thread'];
                // Delete forum attachment from database and file system
                $affectedRows = delete_attachment(0, $attachId, false);
                if ($affectedRows > 0) {
                    $json['error'] = false;
                    $json['errorMessage'] = 'Success';
                }
            }
            break;
        case 'reply_form':
            $parentPostId = isset($_REQUEST['post']) ? intval($_REQUEST['post']) : 0;
            $quotePost = isset($_REQUEST['action']) && $_REQUEST['action'] == 'quote';

            $form = new FormValidator('form_reply');
            $form->addHidden('thread', $current_thread['thread_id']);
            $form->addHidden('forum', $current_forum['forum_id']);
            $form->addHidden('post_parent_id', $parentPostId);
            $form->addHidden('a', 'reply_form');
            $form->addHtmlEditor('post_text', get_lang('Text'), true, false, ['ToolbarSet' => 'Minimal']);
            $form->addButtonCreate(get_lang('ReplyToMessage'), 'SubmitPost');

            if ($form->validate()) {
                $check = Security::check_token('post');

                if (!$check) {
                    break;
                }

                $values = $form->exportValues();

                $values['thread_id'] = $current_thread['thread_id'];
                $values['forum_id'] = $current_forum['forum_id'];
                $values['post_title'] = '';

                $result = store_reply($current_forum, $values);

                if ($result['type'] !== 'confirmation') {
                    $json['errorMessage'] = $result['msg'];
                    break;
                }

                $json['error'] = false;
                $json['errorMessage'] = $result['msg'];

                Security::clear_token();
                break;
            }

            $form->addHidden('sec_token', Security::get_token());

            if ($parentPostId > 0 && $quotePost) {
                $parentPost = get_post_information($parentPostId);

                $parentPostText = Display::tag('p', null);
                $parentPostText .= Display::tag(
                    'div',
                    Display::tag(
                        'div',
                        Display::tag(
                            'blockquote',
                            Display::tag(
                                'p',
                                sprintf(
                                    get_lang('QuotingToXUser'),
                                    api_get_person_name($parentPost['firstname'], $parentPost['lastname'])
                                )
                            ) . prepare4display($parentPost['post_text'])
                        )
                    )
                );
                $parentPostText .= Display::tag('p', null);

                $form->setDefaults([
                    'post_text' => $parentPostText
                ]);
            }

            $json['error'] = false;
            $json['form'] = $form->returnForm();
            break;
        case 'get_more_posts':
            $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
            $loadedPosts = isset($_POST['posts']) && is_array($_POST['posts']) ? $_POST['posts'] : [];
            $locked = isset($_POST['locked']) && $_POST['locked'] === 'true' ? true : false;
            $allowReply = false;

            if (
                ($current_forum_category && $current_forum_category['locked'] == 0) &&
                $current_forum['locked'] == 0 &&
                $current_thread['locked'] == 0 ||
                api_is_allowed_to_edit(false, true)
            ) {
                // The link should only appear when the user is logged in or when anonymous posts are allowed.
                if ($_user['user_id'] OR ($current_forum['allow_anonymous'] == 1 && !$_user['user_id'])) {
                    // reply link
                    if (!api_is_anonymous() && api_is_allowed_to_session_edit(false, true)) {
                        $allowReply = true;
                    }
                }
            }

            $templateFolder = api_get_configuration_value('default_template');
            $templateFolder = empty($templateFolder) ? 'default' : $templateFolder;

            $em = Database::getManager();
            $postsRepo = $em->getRepository('ChamiloCourseBundle:CForumPost');

            $thread = $em->find('ChamiloCourseBundle:CForumThread', $_REQUEST['thread']);
            $posts = $postsRepo->getPostList($thread, 'desc', null, $page, 10);

            $list = [];

            foreach ($posts as $post) {
                $user = $em->find('ChamiloUserBundle:User', $post->getPosterId());

                $template = new Template(null, false, false, false, false, false);
                $template->assign('is_anonymous', api_is_anonymous());
                $template->assign('is_allowed_to_edit', api_is_allowed_to_edit(false, true));
                $template->assign('is_allowed_to_session_edit', api_is_allowed_to_session_edit(false, true));
                $template->assign(
                    'delete_confirm_message',
                    addslashes(
                        api_htmlentities(
                            get_lang('DeletePost'),
                            ENT_QUOTES
                        )
                    )
                );
                $template->assign('locked', $locked);
                $template->assign('allow_reply', $allowReply);
                $template->assign('thread_id', $thread->getThreadId());
                $template->assign('forum', $current_forum);
                $template->assign('post_data', [
                    'post' => [
                        'id' => $post->getPostId(),
                        'date' => api_get_local_time($post->getPostDate()),
                        'text' => $post->getPostText()
                    ],
                    'user' => [
                        'image' => display_user_image($user->getId(), $user->getCompleteName()),
                        'link' => display_user_link($user->getId(), $user->getCompleteName()),
                    ]
                ]);

                $list[] = [
                    'id' => $post->getPostId(),
                    'parentId' => $post->getPostParentId(),
                    'html' => $template->fetch("$templateFolder/forum/flat_learnpath_post.tpl")
                ];
            }

            $json['error'] = false;
            $json['posts'] = $list;
            break;
    }
}

echo json_encode($json);
exit;
