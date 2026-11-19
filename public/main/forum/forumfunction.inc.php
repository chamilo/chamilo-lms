<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\Session as SessionEntity;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumAttachment;
use Chamilo\CourseBundle\Entity\CForumCategory;
use Chamilo\CourseBundle\Entity\CForumMailcue;
use Chamilo\CourseBundle\Entity\CForumNotification;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Entity\CForumThreadQualify;
use Chamilo\CourseBundle\Entity\CForumThreadQualifyLog;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use ChamiloSession as Session;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\HttpFoundation\File\UploadedFile;


/**
 * This function deletes the forum image if exists.
 * @author Julio Montoya <gugli100@gmail.com>
 * @version february 2006, dokeos 1.8
 * @todo Implement
 * @throws Exception
 */
function deleteForumImage(CForum $forum): bool
{
    throw new Exception('delete_forum_image');

    return false;
}

/**
 * This function stores the forum category in the database.
 * The new category is added to the end.
 * @throws Exception
 */
function saveForumCategory(array $values, array $courseInfo = [], bool $showMessage = true): CForumCategory
{
    $courseInfo = empty($courseInfo) ? api_get_course_info() : $courseInfo;
    $course_id = $courseInfo['real_id'];
    $session_id = api_get_session_id();
    $clean_cat_title = $values['forum_category_title'];
    $repo = Container::getForumCategoryRepository();

    $message = '';
    if (isset($values['forum_category_id'])) {
        /** @var CForumCategory $category */
        $category = $repo->find($values['forum_category_id']);
        $category
            ->setCatComment($values['forum_category_comment'] ?? '')
            ->setTitle($values['forum_category_title'])
        ;
        $repo->update($category);
        $message = get_lang('The forum category has been modified');

        $logInfo = [
            'tool' => TOOL_FORUM,
            'action' => 'update-forumcategory',
            'action_details' => 'forumcategory',
            'info' => $clean_cat_title,
        ];
        Event::registerLog($logInfo);

        $values['item_id'] = $values['forum_category_id'];
    } else {
        $course = api_get_course_entity($course_id);
        $session = api_get_session_entity($session_id);

        $category = new CForumCategory();
        $category
            ->setTitle($clean_cat_title)
            ->setCatComment($values['forum_category_comment'] ?? '')
            ->setParent($course)
            ->addCourseLink($course, $session)
        ;
        $repo->create($category);

        $last_id = $category->getIid();
        if ($last_id > 0) {
            $message = get_lang('The forum category has been added');
        }

        $logInfo = [
            'tool' => TOOL_FORUM,
            'action' => 'new-forumcategory',
            'action_details' => 'forumcategory',
            'info' => $clean_cat_title,
        ];
        Event::registerLog($logInfo);

        $values['item_id'] = $last_id;
    }

    $extraFieldValue = new ExtraFieldValue('forum_category');
    $extraFieldValue->saveFieldValues($values);

    if ($showMessage) {
        Display::addFlash(Display::return_message($message, 'confirmation'));
    }

    return $category;
}

/**
 * Stores a new or edited forum in the database.
 * The new forum is added to the end of the others.
 * Returns an ID if the option 'returnId' was set to true, otherwise returns a confirmation message
 */
function store_forum(array $values, array $courseInfo = [], bool $returnId = false): string|int
{
    $courseInfo = empty($courseInfo) ? api_get_course_info() : $courseInfo;
    $courseId = $courseInfo['real_id'];
    $session_id = api_get_session_id();

    // Find the max forum_order for the given category. The new forum is added at the end => max cat_order + &
    if (null === $values['forum_category']) {
        $new_max = null;
    } else {
        /*$sql = "SELECT MAX(forum_order) as sort_max
                FROM $table_forums
                WHERE
                    c_id = $courseId AND
                    forum_category='".Database::escape_string($values['forum_category'])."'";
        $result = Database::query($sql);
        $row = Database::fetch_array($result);
        $new_max = $row['sort_max'] + 1;*/
    }

    // Forum images
    $has_attachment = false;
    $image_moved = true;
    if (!empty($_FILES['picture']['name'])) {
        $upload_ok = process_uploaded_file($_FILES['picture']);
        $has_attachment = true;
    }

    // Remove existing picture if it was requested.
    if (!empty($_POST['remove_picture'])) {
        //deleteForumImage($values['forum_id']);
    }

    $new_file_name = '';
    if (isset($upload_ok)) {
        if ($has_attachment) {
            throw new Exception('$has_attachment');
            /*$course_dir = $courseInfo['path'].'/upload/forum/images';
            $sys_course_path = api_get_path(SYS_COURSE_PATH);
            $updir = $sys_course_path.$course_dir;
            // Try to add an extension to the file if it hasn't one.
            $new_file_name = add_ext_on_mime(
                Database::escape_string($_FILES['picture']['name']),
                $_FILES['picture']['type']
            );
            if (!filter_extension($new_file_name)) {
                //Display::addFlash(Display::return_message(get_lang('File upload failed: this file extension or file type is prohibited'), 'error'));
                $image_moved = false;
            } else {
                $file_extension = explode('.', $_FILES['picture']['name']);
                $file_extension = strtolower($file_extension[count($file_extension) - 1]);
                $new_file_name = uniqid('').'.'.$file_extension;
                $new_path = $updir.'/'.$new_file_name;
                $result = @move_uploaded_file($_FILES['picture']['tmp_name'], $new_path);
                // Storing the attachments if any
                if ($result) {
                    $image_moved = true;
                }
            }*/
        }
    }

    $repo = Container::getForumRepository();

    if (!isset($values['forum_id'])) {
        $forum = new CForum();
    } else {
        /** @var CForum $forum */
        $forum = $repo->find($values['forum_id']);
    }

    $forumCategory = null;
    if (!empty($values['forum_category'])) {
        $repoForumCategory = Container::getForumCategoryRepository();
        $forumCategory = $repoForumCategory->find($values['forum_category']);
    }

    $lpId = $values['lp_id'] ?? 0;
    $lpRepo = Container::getLpRepository();
    $lp = null;
    if (!empty($lpId)) {
        /** @var CLp $lp */
        $lp = $lpRepo->find($lpId);
    }

    $forum
        ->setTitle($values['forum_title'])
        ->setForumComment($values['forum_comment'] ?? '')
        ->setForumCategory($forumCategory)
        ->setAllowAnonymous($values['allow_anonymous_group']['allow_anonymous'] ?? 0)
        ->setAllowEdit($values['students_can_edit_group']['students_can_edit'] ?? 0)
        ->setApprovalDirectPost((string) ($values['approval_direct_group']['approval_direct'] ?? '0'))
        ->setAllowAttachments($values['allow_attachments_group']['allow_attachments'] ?? 0)
        ->setAllowNewThreads((int) ($values['allow_new_threads_group']['allow_new_threads'] ?? 0))
        ->setDefaultView($values['default_view_type_group']['default_view_type'] ?? '')
        ->setForumOfGroup((string) ($values['group_forum'] ?? 0))
        ->setForumGroupPublicPrivate($values['public_private_group_forum_group']['public_private_group_forum'] ?? 'public')
        ->setModerated((bool) ($values['moderated']['moderated'] ?? false))
        ->setStartTime(!empty($values['start_time']) ? api_get_utc_datetime($values['start_time'], true, true) : null)
        ->setEndTime(!empty($values['end_time']) ? api_get_utc_datetime($values['end_time'], true, true) : null)
        ->setLp($lp)
    ;

    $course = api_get_course_entity($courseId);
    $session = api_get_session_entity($session_id);

    if (isset($values['forum_id'])) {
        // Update.
        $repo->update($forum);

        // Move groups from one group to another
        if (isset($values['group_forum']) && false) {
            $forumData = get_forums($values['forum_id']);
            $currentGroupId = $forumData['forum_of_group'];
        }

        $return_message = get_lang('The forum has been modified');
        $forumId = $forum->getIid();

        $logInfo = [
            'tool' => TOOL_FORUM,
            'tool_id' => $values['forum_id'],
            'action' => 'update-forum',
            'action_details' => 'forum',
            'info' => $values['forum_title'],
        ];
        Event::registerLog($logInfo);
    } else {
        $forum
            ->setParent($forumCategory)
            ->addCourseLink($course, $session);
        $repo->create($forum);

        $forumId = $forum->getIid();
        if ($forumId > 0) {
            $courseCode = $courseInfo['code'];
            $subscribe = (int) api_get_course_setting('subscribe_users_to_forum_notifications');

            $status = STUDENT;
            if (!empty($session_id)) {
                $status = 0;
            }
            if (1 === $subscribe) {
                $userList = CourseManager::get_user_list_from_course_code(
                    $courseCode,
                    $session_id,
                    null,
                    null,
                    $status
                );
                foreach ($userList as $userInfo) {
                    set_notification('forum', $forumId, false, $userInfo, $courseInfo);
                }
            }

            $logInfo = [
                'tool' => TOOL_FORUM,
                'tool_id' => $forumId,
                'action' => 'new-forum',
                'action_details' => 'forum',
                'info' => $values['forum_title'],
            ];
            Event::registerLog($logInfo);
        }
        $return_message = get_lang('The forum has been added');
    }

    if ($returnId) {
        return $forumId;
    }

    return $return_message;
}

function deletePost(CForumPost $post): void
{
    $em = Database::getManager();
    $em
        ->createQuery('
            UPDATE Chamilo\CourseBundle\Entity\CForumPost p
            SET p.postParent = :parent_of_deleted_post
            WHERE
                p.postParent = :post AND
                p.thread = :thread_of_deleted_post AND
                p.forum = :forum_of_deleted_post
        ')
        ->execute([
            'parent_of_deleted_post' => $post->getPostParent(),
            'post' => $post->getIid(),
            'thread_of_deleted_post' => $post->getThread() ? $post->getThread()->getIid() : 0,
            'forum_of_deleted_post' => $post->getForum(),
        ]);

    $attachments = $post->getAttachments();
    if (!empty($attachments)) {
        foreach ($attachments as $attachment) {
            $em->remove($attachment);
        }
    }

    $em->remove($post);
    $em->flush();

    $threadId = $post->getThread()->getIid();
    $lastPostOfTheTread = getLastPostOfThread($threadId);

    if (!empty($lastPostOfTheTread)) {
        // Decreasing the number of replies only if > 0
        $threadReplies = $em->createQueryBuilder()
            ->select('t.threadReplies AS thread_replies')
            ->from(CForumThread::class, 't')
            ->where('t.iid = :threadId')
            ->setParameter('threadId', $threadId)
            ->getQuery()
            ->getArrayResult()[0] ?? false;
        $replyCount = (int) ($threadReplies['thread_replies'] ?? 0);

        $update = $em->createQueryBuilder()
            ->update(CForumThread::class, 't')
            ->set('t.threadLastPost', ':lastPost')
            ->set('t.threadDate', ':threadDate')
            ->where('t.iid = :threadId')
            ->setParameter('lastPost', (int) $lastPostOfTheTread['iid'])
            ->setParameter('threadDate', $lastPostOfTheTread['post_date'])
            ->setParameter('threadId', $threadId);
        if ($replyCount > 0) {
            // Decreasing the number of replies only if > 0
            $update->set('t.threadReplies', 't.threadReplies - 1');
        }
        $update->getQuery()->execute();
        Display::addFlash(Display::return_message(get_lang('Post has been deleted')));
    } else {
        // We deleted the very last post of the thread, so we need to delete the thread as well.
        $em->createQueryBuilder()
            ->delete(CForumThread::class, 't')
            ->where('t.iid = :threadId')
            ->setParameter('threadId', $threadId)
            ->getQuery()
            ->execute();

        Display::addFlash(Display::return_message(get_lang('Thread deleted')));
    }
}

/**
 * This function gets the all information of the last (=most recent) post of the thread
 * This can be done by sorting the posts that have the field threadId=$threadId and sort them by post_date.
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function getLastPostOfThread(int $threadId): array
{
    $post = Container::getForumPostRepository()->findOneBy(['thread' => $threadId], ['postDate' => 'DESC']);

    if (null === $post) {
        return [];
    }

    return [
        'iid' => $post->getIid(),
        'post_date' => $post->getPostDate()->format('Y-m-d H:i:s'),
    ];
}

/**
 * @param string $content                   Type of content forum category, forum, thread, post
 * @param int    $id                        the id of the content we want to make invisible
 * @param int    $current_visibility_status what is the current status of the visibility (0 = invisible, 1 = visible)
 * @param array  $additional_url_parameters
 *
 * @return string HTML
 */
function returnVisibleInvisibleIcon(
    string $content,
    int $id,
    int|bool $current_visibility_status,
    array $additional_url_parameters = []
): string
{
    $current_visibility_status = (int) $current_visibility_status;
    $html = '';

    if (1 == $current_visibility_status) {
        $html .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&';
        if (is_array($additional_url_parameters)) {
            foreach ($additional_url_parameters as $key => $value) {
                $html .= $key.'='.$value.'&';
            }
        }
        $html .= 'action=invisible&content='.$content.'&id='.$id.'">'.
            Display::getMdiIcon(ActionIcon::VISIBLE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Make invisible')).'</a>';
    }
    if (0 == $current_visibility_status) {
        $html .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&';
        if (is_array($additional_url_parameters)) {
            foreach ($additional_url_parameters as $key => $value) {
                $html .= $key.'='.$value.'&';
            }
        }
        $html .= 'action=visible&content='.$content.'&id='.$id.'">'.
            Display::getMdiIcon(ActionIcon::INVISIBLE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Make visible')).'</a>';
    }

    return $html;
}

/**
 * Returns an HTML string with the appropriate icon
 * @param string $content                   Type of content forum category, forum, thread, post
 * @param int    $id                        the id of the content we want to make invisible
 * @param int    $current_lock_status what is the current status of the visibility (0 = unlocked, 1 = locked)
 * @param array  $additional_url_parameters
 */
function returnLockUnlockIcon(
    string $content,
    int $id,
    int $current_lock_status,
    array $additional_url_parameters = []
): string
{
    $html = '';
    //check if the forum is blocked due
    if ('thread' === $content) {
        if (api_resource_is_locked_by_gradebook($id, LINK_FORUM_THREAD)) {
            return $html.Display::getMdiIcon(
                    ActionIcon::LOCK,
                    'ch-tool-icon-disabled',
                    '',
                    ICON_SIZE_SMALL,
                    get_lang(
                        'This option is not available because this activity is contained by an assessment, which is currently locked. To unlock the assessment, ask your platform administrator.'
                    )
                );
        }
    }
    if ('1' == $current_lock_status) {
        $html .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&';
        if (is_array($additional_url_parameters)) {
            foreach ($additional_url_parameters as $key => $value) {
                $html .= $key.'='.$value.'&';
            }
        }
        $html .= 'action=unlock&content='.$content.'&id='.$id.'">'.
            Display::getMdiIcon(ActionIcon::LOCK, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Unlock')).'</a>';
    }
    if ('0' == $current_lock_status) {
        $html .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&';
        if (is_array($additional_url_parameters)) {
            foreach ($additional_url_parameters as $key => $value) {
                $html .= $key.'='.$value.'&';
            }
        }
        $html .= 'action=lock&content='.$content.'&id='.$id.'">'.
            Display::getMdiIcon(ActionIcon::UNLOCK, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Lock')).'</a>';
    }

    return $html;
}

/**
 * Generates HTML for up and down icons with links to move an item up or down in a list.
 */
function returnUpDownIcon(string $content, int $id, array $list): string {
    $forumCategory = isset($_GET['forumcategory']) ? Security::remove_XSS($_GET['forumcategory']) : null;
    $totalItemsOfType = 0;
    $position = 0;
    foreach ($list as $key => $item) {
        if (($content === 'forumcategory' && $item instanceof CForumCategory) ||
            ($content === 'forum' && $item instanceof CForum)) {
            $totalItemsOfType++;
            if ($id == $item->getIid()) {
                $position = $key + 1;
            }
        }
    }

    $upIcon = $position > 1
        ? '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=move&direction=up&content='.$content.'&id='.$id.'" title="'.get_lang('Move up').'">'.Display::getMdiIcon(ActionIcon::UP, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Move up')).'</a>'
        : '<span class="ch-tool-icon-disabled">'.Display::getMdiIcon(ActionIcon::UP, 'ch-tool-icon-disabled', null, ICON_SIZE_SMALL, '').'</span>';

    $downIcon = $position < $totalItemsOfType
        ? '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=move&direction=down&content='.$content.'&id='.$id.'" title="'.get_lang('Move down').'">'.Display::getMdiIcon(ActionIcon::DOWN, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Move down')).'</a>'
        : '<span class="ch-tool-icon-disabled">'.Display::getMdiIcon(ActionIcon::DOWN, 'ch-tool-icon-disabled', null, ICON_SIZE_SMALL, '').'</span>';


    return $upIcon . $downIcon;
}

/**
 * Retrieve all the information off the forum categories (or one specific) for the current course.
 * The categories are sorted according to their sorting order (cat_order.
 *
 * @param int $courseId  Optional. The course ID
 * @param int $sessionId Optional. The session ID
 *
 * @return CForumCategory[]
 */
function get_forum_categories(int $courseId = 0, int $sessionId = 0): Array
{
    $repo = Container::getForumCategoryRepository();

    $course = api_get_course_entity($courseId);
    $session = api_get_session_entity($sessionId);

    $qb = $repo->getResourcesByCourse($course, $session, null, $course->getResourceNode(), true, true);

    return $qb->getQuery()->getResult();
}

/**
 * This function retrieves all the fora in a given forum category.
 *
 * @todo: Fixes error if there forums with no category.
 *
 * @param int $categoryId the id of the forum category
 * @param int $courseId   Optional. The course ID
 *
 * @return CForum[] containing all the information about the forums (regardless of their category)
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 *
 * @version february 2006, dokeos 1.8
 */
function get_forums_in_category(int $categoryId, int $courseId = 0, int $sessionId = 0)
{
    $repo = Container::getForumRepository();
    $course = api_get_course_entity($courseId);
    $session = api_get_session_entity($sessionId);

    $qb = $repo->getResourcesByCourse($course, $session, null, null, true, true);
    $qb
        ->andWhere('resource.forumCategory = :catId')
        ->setParameter('catId', $categoryId)
    ;

    return $qb->getQuery()->getResult();
}

/**
 * Retrieve all the forums (regardless of their category) or of only one.
 * The forums are sorted according to the forum_order.
 * Since it does not take the forum category into account there probably
 * will be two or more forums that have forum_order=1, ...
 *
 * @param bool $includeGroupsForum
 * @param int  $sessionId
 *
 * @return CForum[]
 */
function get_forums(
    ?int $courseId = null,
    int $sessionId = 0
) {
    $repo = Container::getForumRepository();
    $courseId = empty($courseId) ? api_get_course_int_id() : $courseId;
    $course = api_get_course_entity($courseId);
    $session = api_get_session_entity($sessionId);

    $qb = $repo->getResourcesByCourse($course, $session);

    /*$qb->andWhere('resource.forumCategory = :catId')
        ->setParameter('catId', $cat_id);
    */
    return $qb->getQuery()->getResult();
}

/**
 * Returns the given forum ID's forum instance
 */
function getForum(
    ?int $forumId = null
): CForum|bool {
    if (!empty($forumId)) {
        $repo = Container::getForumRepository();
        $qb = $repo->find($forumId);

        return $qb->getQuery()->getResult();
    }

    return false;
}

/**
 * Retrieves all the threads for a given forum or counts them.
 */
function get_threads(int $forumId, ?int $courseId = null, ?int $sessionId = null, bool $count = false): array|int
{
    $repo = Container::getForumThreadRepository();
    $courseId = empty($courseId) ? api_get_course_int_id() : $courseId;
    $course = api_get_course_entity($courseId);
    $session = api_get_session_entity($sessionId);

    $qb = $repo->getResourcesByCourse($course, $session);
    $qb->andWhere('resource.forum = :forum')->setParameter('forum', $forumId);

    if ($count) {
        $qb->select('COUNT(resource.iid)');
        return (int) $qb->getQuery()->getSingleScalarResult();
    } else {
        return $qb->getQuery()->getResult();
    }
}

/**
 * Get a thread by Id and course id.
 *
 * @param int $threadId the thread Id
 *
 * @return array containing all the information about the thread
 */
function getThreadInfo(int $threadId): Array
{
    $repo = Database::getManager()->getRepository(CForumThread::class);
    /** @var CForumThread $forumThread */
    $forumThread = $repo->findOneBy(['iid' => $threadId]);

    $thread = [];
    if ($forumThread) {
        $thread['iid'] = $forumThread->getIid();
        $thread['threadId'] = $forumThread->getIid();
        $thread['threadTitle'] = $forumThread->getTitle();
        $thread['forumId'] = $forumThread->getForum() ? $forumThread->getForum()->getIid() : 0;
        //$thread['sessionId'] = $forumThread->getSessionId();
        $thread['threadSticky'] = $forumThread->getThreadSticky();
        $thread['locked'] = $forumThread->getLocked();
        $thread['threadTitleQualify'] = $forumThread->getThreadTitleQualify();
        $thread['threadQualifyMax'] = $forumThread->getThreadQualifyMax();
        $thread['threadCloseDate'] = $forumThread->getThreadCloseDate();
        $thread['threadWeight'] = $forumThread->getThreadWeight();
        $thread['threadPeerQualify'] = $forumThread->isThreadPeerQualify();
    }

    return $thread;
}

/**
 * Retrieve all posts of a given thread.
 *
 * @param int    $threadId       The thread ID
 * @param string $orderDirection Optional. The direction for sort the posts
 * @param bool   $recursive      Optional. If the list is recursive
 * @param int    $postId         Optional. The post ID for recursive list
 * @param int    $depth          Optional. The depth to indicate the indent
 *
 * @todo move to a repository
 *
 * @return array containing all the information about the posts of a given thread
 */
function getPosts(
    CForum $forum,
    int $threadId,
    string $orderDirection = 'ASC',
    bool $recursive = false,
    ?int $postId = null,
    int $depth = -1
): Array
{
    $em = Database::getManager();

    $orderDirection = strtoupper($orderDirection);
    if (!in_array($orderDirection, ['ASC', 'DESC'], true)) {
        $orderDirection = 'ASC';
    }

    // Build visibility criteria based on permissions
    if (api_is_allowed_to_edit(false, true)) {
        // Entity maps 'visible' as boolean; keeping legacy behavior as requested.
        $visibleCriteria = Criteria::expr()->neq('visible', 2);
    } else {
        $visibleCriteria = Criteria::expr()->eq('visible', 1);
    }

    $threadRef = $em->getReference(CForumThread::class, $threadId);

    $criteria = Criteria::create();
    $criteria
        ->where(Criteria::expr()->eq('thread', $threadRef))
        ->andWhere($visibleCriteria);

    $groupId = api_get_group_id();
    $filterModerated = true;

    if (empty($groupId)) {
        if (api_is_allowed_to_edit()) {
            $filterModerated = false;
        }
    } else {
        $groupEntity = api_get_group_entity($groupId);
        if (GroupManager::isTutorOfGroup(api_get_user_id(), $groupEntity) ||
            api_is_allowed_to_edit(false, true)
        ) {
            $filterModerated = false;
        }
    }

    if ($recursive) {
        // Compare association with entity reference when filtering by parent
        $parentRef = $postId ? $em->getReference(CForumPost::class, $postId) : null;
        $criteria->andWhere(Criteria::expr()->eq('postParent', $parentRef));
    }

    $qb = $em->getRepository(CForumPost::class)->createQueryBuilder('p');
    $qb->select('p')
        ->addCriteria($criteria)
        ->addOrderBy('p.iid', $orderDirection);

    // Apply moderation filter if forum is moderated and user is not editor
    if ($filterModerated && 1 == $forum->isModerated()) {
        if (!api_is_allowed_to_edit(false, true)) {
            $userId = api_get_user_id();

            // instead of a non-existent scalar field like p.posterId
            $qb->andWhere(
                '(p.status = :st_valid)
                 OR (p.status IN (:st_own) AND IDENTITY(p.user) = :uid)
                 OR (p.status IS NULL AND IDENTITY(p.user) = :uid)'
            )
                ->setParameter('st_valid', CForumPost::STATUS_VALIDATED)
                ->setParameter('st_own', [
                    CForumPost::STATUS_WAITING_MODERATION,
                    CForumPost::STATUS_REJECTED,
                ])
                ->setParameter('uid', $userId);
        }
    }

    $posts = $qb->getQuery()->getResult();
    $depth++;

    $list = [];
    /** @var CForumPost $post */
    foreach ($posts as $post) {
        $postInfo = [
            'iid' => $post->getIid(),
            'post_id' => $post->getIid(),
            'post_title' => $post->getTitle(),
            'post_text' => $post->getPostText(),
            'thread_id' => $post->getThread() ? $post->getThread()->getIid() : 0,
            'forum_id' => $post->getForum()->getIid(),
            'post_date' => $post->getPostDate(),
            'post_notification' => $post->getPostNotification(),
            'post_parent_id' => $post->getPostParent() ? $post->getPostParent()->getIid() : 0,
            'visible' => $post->getVisible(),
            'status' => $post->getStatus(),
            'indent_cnt' => $depth,
            'entity' => $post,
        ];

        // Fill user info if available
        $user = $post->getUser();
        if ($user) {
            $postInfo['user_id'] = $user->getId();
            $postInfo['username'] = $user->getUsername();
            $postInfo['username_canonical'] = $user->getUsernameCanonical();
            $postInfo['lastname'] = $user->getLastname();
            $postInfo['firstname'] = $user->getFirstname();
            $postInfo['complete_name'] = UserManager::formatUserFullName($user);
        }

        $list[] = $postInfo;

        if (!$recursive) {
            continue;
        }

        // Recursive fetch of children when requested
        $list = array_merge(
            $list,
            getPosts(
                $forum,
                $threadId,
                $orderDirection,
                $recursive,
                $post->getIid(),
                $depth
            )
        );
    }

    return $list;
}

/**
 * This function retrieves forum thread users details.
 *
 * @return Doctrine\DBAL\Driver\Statement|null array Array of type
 *                                             ([user_id=>w,lastname=>x,firstname=>y,threadId=>z],[])
 *
 * @author Christian Fasanando <christian.fasanando@beeznest.com>,
 *
 * @todo     this function needs to be improved
 *
 * @version October 2008, dokeos 1.8
 */
function get_thread_users_details(int $threadId)
{
    $t_posts = Database::get_course_table(TABLE_FORUM_POST);
    $t_users = Database::get_main_table(TABLE_MAIN_USER);
    $t_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
    $t_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

    $courseId = (int) api_get_course_int_id();
    $threadId = (int) $threadId;
    $sessionId = (int) api_get_session_id();

    $isWesternNameOrder = api_is_western_name_order();
    $orderBy = $isWesternNameOrder
        ? 'ORDER BY user.firstname, user.lastname'
        : 'ORDER BY user.lastname, user.firstname';

    if ($sessionId > 0) {
        // We are inside a session: use session_rel_course_rel_user
        $session = api_get_session_entity();
        $coachIds = [];

        if ($session instanceof SessionEntity) {
            // Collect general coaches and session admins to exclude them
            $generalCoachesId = $session
                ->getGeneralCoaches()
                ->map(fn (User $coach) => $coach->getId())
                ->getValues();

            $sessionAdminsId = $session
                ->getSessionAdmins()
                ->map(fn (User $admin) => $admin->getId())
                ->getValues();

            $coachIds = array_unique(array_merge($generalCoachesId, $sessionAdminsId));
        }

        $avoidClause = '';
        if (!empty($coachIds)) {
            // Avoid empty NOT IN () clause
            $ids = implode(',', array_map('intval', $coachIds));
            $avoidClause = "AND session_rel.user_id NOT IN ($ids)";
        }

        $sql = "
            SELECT DISTINCT user.id, user.lastname, user.firstname, p.thread_id
            FROM $t_posts p
            INNER JOIN $t_users user
                ON p.poster_id = user.id
            INNER JOIN $t_session_rel_user session_rel
                ON session_rel.user_id = user.id
            WHERE
                p.thread_id = $threadId
                AND (p.status IS NULL OR p.status = ".CForumPost::STATUS_VALIDATED.")
                AND session_rel.c_id = $courseId
                AND session_rel.session_id = $sessionId
                AND session_rel.status = ".SessionEntity::STUDENT."
                $avoidClause
            $orderBy
        ";
    } else {
        // No session: use course_rel_user
        $sql = "
            SELECT DISTINCT user.id, user.lastname, user.firstname, p.thread_id
            FROM $t_posts p
            INNER JOIN $t_users user
                ON p.poster_id = user.id
            INNER JOIN $t_course_user course_user
                ON course_user.user_id = user.id
            WHERE
                p.thread_id = $threadId
                AND (p.status IS NULL OR p.status = ".CForumPost::STATUS_VALIDATED.")
                AND course_user.c_id = $courseId
                AND course_user.status = ".CourseRelUser::STUDENT."
                AND course_user.relation_type <> ".COURSE_RELATION_TYPE_RRHH."
            $orderBy
        ";
    }

    return Database::query($sql);
}

/**
 * This function retrieves forum thread users qualify.
 *
 * @return Doctrine\DBAL\Driver\Statement|null Array of type ([user_id=>w,lastname=>x,firstname=>y,threadId=>z],[])
 * @author Jhon Hinojosa
 * @todo     this function needs to be improved
 */
function get_thread_users_qualify(int $thread_id)
{
    $t_posts = Database::get_course_table(TABLE_FORUM_POST);
    $t_qualify = Database::get_course_table(TABLE_FORUM_THREAD_QUALIFY);
    $t_users = Database::get_main_table(TABLE_MAIN_USER);
    $t_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
    $t_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

    $course_id = api_get_course_int_id();
    $sessionId = api_get_session_id();

    $is_western_name_order = api_is_western_name_order();
    if ($is_western_name_order) {
        $orderby = 'ORDER BY user.firstname, user.lastname ';
    } else {
        $orderby = 'ORDER BY user.lastname, user.firstname';
    }

    $session = api_get_session_entity();

    if ($session) {
        $generalCoachesId = $session->getGeneralCoaches()->map(fn(User $coach) => $coach->getId())->getValues();
        $sessionAdminsId = $session->getSessionAdmins()->map(fn(User $admin) => $admin->getId())->getValues();
        $coachesId = array_merge($generalCoachesId, $sessionAdminsId);
        // An empty NOT IN () is a SQL error: a session may have neither general coach nor session admin.
        $user_to_avoid = implode(', ', array_map('intval', $coachesId ?: [0]));
        //not showing coaches
        $sql = "SELECT DISTINCT post.poster_id, user.lastname, user.firstname, post.thread_id,user.id,forum_qualify.`qualify`
                FROM $t_posts post , $t_users user, $t_session_rel_user scu, $t_qualify forum_qualify
                WHERE poster_id = user.id
                    AND post.poster_id = forum_qualify.user_id
                    AND user.id = scu.user_id
                    AND scu.status = ".SessionEntity::STUDENT."
                    AND scu.user_id NOT IN ($user_to_avoid)
                    AND forum_qualify.thread_id = $thread_id
                    AND post.thread_id = $thread_id
                    AND scu.session_id = $sessionId
                    AND scu.c_id = $course_id AND
                    forum_qualify.c_id = $course_id
                $orderby ";
    } else {
        $sql = "SELECT DISTINCT post.poster_id, user.lastname, user.firstname, post.thread_id,user.id,forum_qualify.`qualify`
                FROM $t_posts post,
                     $t_qualify forum_qualify,
                     $t_users user,
                     $t_course_user course_user
                WHERE
                     post.poster_id = user.id
                     AND post.poster_id = forum_qualify.user_id
                     AND user.id = course_user.user_id
                     AND course_user.relation_type<>".COURSE_RELATION_TYPE_RRHH."
                     AND forum_qualify.thread_id = $thread_id
                     AND post.thread_id = $thread_id
                     AND course_user.status not in('1')
                     AND course_user.c_id = $course_id
                     AND forum_qualify.c_id = $course_id
                 $orderby ";
    }

    return Database::query($sql);
}

/**
 * This function update a thread.
 *
 * @param array $values - The form Values
 */
function updateThread($values)
{
    if (!api_is_allowed_to_edit()) {
        return '';
    }

    $logInfo = [
        'tool' => TOOL_FORUM,
        'tool_id' => $values['forum_id'],
        'tool_id_detail' => $values['thread_id'],
        'action' => 'edit-thread',
        'action_details' => 'thread',
        'info' => $values['thread_title'],
    ];
    Event::registerLog($logInfo);

    $threadTable = Database::get_course_table(TABLE_FORUM_THREAD);
    $courseId = api_get_course_int_id();
    $courseCode = api_get_course_id();
    $sessionId = api_get_session_id();

    // Simple update + set gradebook values to null
    $params = [
        'title' => $values['thread_title'],
        'thread_sticky' => $values['thread_sticky'] ?? 0,
    ];
    $where = ['iid = ?' => [$values['thread_id']]];
    Database::update($threadTable, $params, $where);

    $id = $values['thread_id'];
    $linkInfo = GradebookUtils::isResourceInCourseGradebook(
        $courseId,
        LINK_FORUM_THREAD,
        $id,
        $sessionId
    );

    $gradebookLink = null;
    $em = Database::getManager();
    if (!empty($linkInfo) && isset($linkInfo['id'])) {
        $gradebookLink = $em->getRepository(GradebookLink::class)->find($linkInfo['id']);
    }

    // values 1 or 0
    $check = isset($values['thread_qualify_gradebook']) ? $values['thread_qualify_gradebook'] : false;
    if ($check) {
        $title = Security::remove_XSS(stripslashes($values['calification_notebook_title']));
        $value = isset($values['numeric_calification']) ? (int) ($values['numeric_calification']) : 0;
        $weight = isset($values['weight_calification']) ? (float) ($values['weight_calification']) : 0;
        $description = '';
        // Update title
        $params = [
            'thread_title_qualify' => $values['calification_notebook_title'],
            'thread_qualify_max' => api_float_val($values['numeric_calification']),
            'thread_weight' => api_float_val($values['weight_calification']),
            'thread_peer_qualify' => $values['thread_peer_qualify'],
        ];
        $where = ['iid = ?' => [$values['thread_id']]];
        Database::update($threadTable, $params, $where);

        if (!$linkInfo) {
            GradebookUtils::add_resource_to_course_gradebook(
                $values['category_id'],
                $courseId,
                LINK_FORUM_THREAD,
                $id,
                $title,
                $weight,
                $value,
                $description,
                1,
                $sessionId
            );
        } else {
            if ($gradebookLink) {
                $gradebookLink->setWeight($weight);
                $em->persist($gradebookLink);
                $em->flush();
            }
        }
    } else {
        $params = [
            'thread_title_qualify' => '',
            'thread_qualify_max' => 0,
            'thread_weight' => 0,
            'thread_peer_qualify' => 0,
        ];
        $where = ['iid = ?' => [$values['thread_id']]];
        Database::update($threadTable, $params, $where);

        if (!empty($linkInfo)) {
            if ($gradebookLink) {
                $em->remove($gradebookLink);
                $em->flush();
            }
        }
    }

    $message = get_lang('The post has been modified').'<br />';
    Display::addFlash(Display::return_message($message, 'confirmation', false));
}

function saveThread(
    CForum $forum,
    array $values,
    array $courseInfo = [],
    $showMessage = true,
    $userId = 0,
    $sessionId = 0
): ?CForumThread {
    $courseInfo = empty($courseInfo) ? api_get_course_info() : $courseInfo;
    $userId = $userId ?: api_get_user_id();
    $course_id = $courseInfo['real_id'];
    $courseCode = $courseInfo['code'];
    $sessionId = $sessionId ?: api_get_session_id();

    $post_date = new DateTime(api_get_utc_datetime(), new DateTimeZone('UTC'));
    $visible = true;
    if ('1' == $forum->getApprovalDirectPost() && !api_is_allowed_to_edit(null, true)) {
        $visible = false; // The post has not been approved yet.
    }
    $clean_post_title = $values['post_title'];

    $user = api_get_user_entity(api_get_user_id());
    $course = api_get_course_entity($course_id);
    $session = api_get_session_entity($sessionId);

    // We first store an entry in the forum_thread table because the threadId is used in the forum_post table.
    $thread = new CForumThread();
    $thread
        ->setTitle($clean_post_title)
        ->setForum($forum)
        ->setUser($user)
        ->setThreadDate($post_date)
        ->setThreadSticky((bool) ($values['thread_sticky'] ?? false))
        ->setThreadTitleQualify($values['calification_notebook_title'] ?? '')
        ->setThreadQualifyMax(api_float_val($values['numeric_calification'] ?? 0))
        ->setThreadWeight(api_float_val($values['weight_calification'] ?? 0))
        ->setThreadPeerQualify(isset($values['thread_peer_qualify']) ? (bool) $values['thread_peer_qualify'] : false)
        ->setParent($forum)
        ->addCourseLink($course, $session)
    ;
    $em = Database::getManager();
    $itemId = isset($values['lp_item_id']) ? (int) $values['lp_item_id'] : 0;
    if (!empty($itemId)) {
        $item = $em->getRepository(CLpItem::class)->find($itemId);
        $thread->setItem($item);
    }

    $repo = Container::getForumThreadRepository();
    $repo->create($thread);

    if (!$thread->getIid()) {
        return null;
    }

    // Add option gradebook qualify.
    if (isset($values['thread_qualify_gradebook']) &&
        1 == $values['thread_qualify_gradebook']
    ) {
        // Add function gradebook.
        $resourcename = stripslashes($values['calification_notebook_title']);
        GradebookUtils::add_resource_to_course_gradebook(
            $values['category_id'],
            $course_id,
            5,
            $thread->getIid(),
            $resourcename,
            $values['weight_calification'],
            $values['numeric_calification'],
            '',
            0,
            $sessionId
        );
    }

    $logInfo = [
        'tool' => TOOL_FORUM,
        'tool_id' => $values['forum_id'],
        'tool_id_detail' => $thread->getIid(),
        'action' => 'new-thread',
        'info' => $clean_post_title,
    ];
    Event::registerLog($logInfo);

    // We now store the content in the table_post table.
    $post = new CForumPost();
    $post
        ->setTitle($clean_post_title)
        ->setPostText($values['post_text'])
        ->setThread($thread)
        ->setForum($forum)
        ->setUser(api_get_user_entity($userId))
        ->setPostDate($post_date)
        ->setPostNotification(isset($values['post_notification']) ? (bool) $values['post_notification'] : false)
        ->setVisible($visible)
        ->setStatus(CForumPost::STATUS_VALIDATED)
        ->setParent($thread)
        ->addCourseLink($course, $session)
    ;

    if ($forum->isModerated()) {
        $post->setStatus(
            api_is_course_admin() ? CForumPost::STATUS_VALIDATED : CForumPost::STATUS_WAITING_MODERATION
        );
    }

    $repo = Container::getForumPostRepository();
    $repo->create($post);
    $thread->setThreadLastPost($post);
    $em = Database::getManager();
    $em->persist($thread);
    $em->flush();

    $postId = $post->getIid();

    $logInfo = [
        'tool' => TOOL_FORUM,
        'tool_id' => $values['forum_id'],
        'tool_id_detail' => $thread->getIid(),
        'action' => 'new-post',
        'info' => $clean_post_title,
    ];
    Event::registerLog($logInfo);

    // Now we have to update the thread table to fill the thread_last_post
    // field (so that we know when the thread has been updated for the last time).
    Database::getManager()->createQueryBuilder()
        ->update(CForumThread::class, 't')
        ->set('t.threadLastPost', ':postId')
        ->where('t.iid = :threadId')
        ->setParameter('postId', (int) $postId)
        ->setParameter('threadId', (int) $thread->getIid())
        ->getQuery()
        ->execute();

    $message = '';
    if ($showMessage) {
        Display::addFlash(Display::return_message(get_lang('The new thread has been added'), 'success', false));
    }

    // Overwrite default message.
    if ($forum->isModerated() &&
        !api_is_allowed_to_edit(null, true)
    ) {
        if ($showMessage) {
            Display::addFlash(Display::return_message(get_lang('Your message has to be approved before people can view it.'), 'success', false));
        }
    }

    add_forum_attachment_file(
        null,
        $post
    );

    if ('1' == $forum->getApprovalDirectPost() &&
        !api_is_allowed_to_edit(null, true)
    ) {
        $message .= get_lang('Your message has to be approved before people can view it.').'<br />';
        $message .= get_lang('You can now return to the').
            ' <a href="viewforum.php?'.api_get_cidreq(true, true, false).'&forum='.$values['forum_id'].'">'.
            get_lang('Forum').'</a><br />';
    } else {
        $message .= get_lang('You can now return to the').
            ' <a href="viewforum.php?'.api_get_cidreq(true, true, false).'&forum='.$values['forum_id'].'">'.
            get_lang('Forum').'</a><br />';
        $message .= get_lang('You can now return to the').
            ' <a href="viewthread.php?'.api_get_cidreq(true, true, false).'&forum='.$values['forum_id'].'&thread='.$thread->getIid().'">'.
            get_lang('Message').'</a>';
    }
    $reply_info['new_post_id'] = $postId;
    $my_post_notification = isset($values['post_notification']) ? $values['post_notification'] : null;

    if (1 == $my_post_notification) {
        set_notification('thread', $thread->getIid(), true);
    }

    send_notification_mails(
        $forum,
        $thread,
        $reply_info,
        $course
    );

    Session::erase('formelements');
    Session::erase('origin');
    Session::erase('breadcrumbs');
    Session::erase('addedresource');
    Session::erase('addedresourceid');

    if ($showMessage) {
        Display::addFlash(Display::return_message($message, 'success', false));
    }

    return $thread;
}

/**
 * This function displays the form that is used to add a post. This can be a new thread or a reply.
 *
 * @param string $action
 *                            is the parameter that determines if we are
 *                            2. replythread: Replying to a thread ($action = replythread) => I-frame with the complete
 *                            thread (if enabled)
 *                            3. replymessage: Replying to a message ($action =replymessage) => I-frame with the
 *                            complete thread (if enabled)
 *                            (I first thought to put and I-frame with the message only)
 *                            4. quote: Quoting a message ($action= quotemessage) => I-frame with the complete thread
 *                            (if enabled). The message will be in the reply. (I first thought not to put an I-frame
 *                            here)
 * @param array  $form_values
 * @param bool   $showPreview
 *
 * @return FormValidator
 */
function show_add_post_form(CForum $forum, CForumThread $thread, ?CForumPost $post, $action, $form_values, $showPreview = true)
{
    $_user = api_get_user_info();
    $action = isset($action) ? Security::remove_XSS($action) : '';
    $threadId = $thread->getIid();
    $forumId = $forum->getIid();
    $giveRevision = isset($_GET['give_revision']) && 1 == $_GET['give_revision'];
    $postId = $post ? $post->getIid() : 0;

    $url = api_get_self().'?'.http_build_query(
        [
            'action' => $action,
            'forum' => $forumId,
            'thread' => $threadId,
            'post' => $postId,
        ]
    ).'&'.api_get_cidreq();

    $form = new FormValidator(
        'thread',
        'post',
        $url
    );
    $form->setConstants(['forum' => '5']);

    // Setting the form elements.
    $form->addElement('hidden', 'forum_id', $forumId);
    $form->addElement('hidden', 'thread_id', $threadId);
    $form->addElement('hidden', 'action', $action);

    // If anonymous posts are allowed we also display a form to allow the user to put his name or username in.
    if (1 == $forum->getAllowAnonymous() && !isset($_user['user_id'])) {
        $form->addElement('text', 'poster_name', get_lang('Name'));
        $form->applyFilter('poster_name', 'html_filter');
    }

    $form->addElement('text', 'post_title', get_lang('Title'));
    $form->applyFilter('post_title', 'html_filter');
    $form->addHtmlEditor(
        'post_text',
        get_lang('Text'),
        true,
        false,
        api_is_allowed_to_edit(null, true) ? [
            'ToolbarSet' => 'Forum',
            'Width' => '100%',
            'Height' => '300',
        ] : [
            'ToolbarSet' => 'ForumStudent',
            'Width' => '100%',
            'Height' => '300',
            'UserStatus' => 'student',
        ]
    );
    $form->addRule('post_text', get_lang('Required field'), 'required');

    if (in_array($action, ['replythread', 'replymessage', 'quote'])) {
        $extraFields = new ExtraField('forum_post');
        $extraFields->addElements(
            $form,
            null,
            [], //exclude
            false, // filter
            false, // tag as select
            ['ask_for_revision'], //show only fields
            [], // order fields
            [] // extra data);
        );
    }

    if (in_array($action, ['quote', 'replymessage'])) {
        $form->addFile('user_upload[]', get_lang('Attachment'));
        $form->addButton(
            'add_attachment',
            get_lang('Add attachment'),
            'paperclip',
            'plain',
            'plain',
            null,
            ['id' => 'reply-add-attachment']
        );
    } else {
        $form->addFile('user_upload', get_lang('Attachment'));
    }

    if ($giveRevision) {
        $hide = ('true' === api_get_setting('forum.hide_forum_post_revision_language'));
        $form->addHidden('give_revision', 1);
        if (false === $hide) {
            $extraField = new ExtraField('forum_post');
            $extraField->addElements(
                $form,
                null,
                [], //exclude
                false, // filter
                false, // tag as select
                ['revision_language'], //show only fields
                [], // order fields
                [] // extra data
            );
        } else {
            $form->addHidden('extra_revision_language', 1);
        }
    }

    // Setting the class and text of the form title and submit button.
    if ('quote' === $action) {
        $form->addButtonCreate(get_lang('Quote this message'), 'SubmitPost');
    } elseif ('replythread' === $action) {
        $form->addButtonCreate(get_lang('Reply to this thread'), 'SubmitPost');
    } elseif ('replymessage' === $action) {
        $form->addButtonCreate(get_lang('Reply to this message'), 'SubmitPost');
    }

    $defaults['thread_peer_qualify'] = 0;
    if (!empty($form_values)) {
        $defaults['post_title'] = prepare4display($form_values['post_title']);
        $defaults['post_text'] = prepare4display($form_values['post_text']);
        $defaults['post_notification'] = (int) $form_values['post_notification'];
        $defaults['thread_sticky'] = (int) $form_values['thread_sticky'];
        $defaults['thread_peer_qualify'] = (int) $form_values['thread_peer_qualify'];
    }

    // If we are quoting a message we have to retrieve the information of the post we are quoting so that
    // we can add this as default to the textarea.
    // We also need to put the parent_id of the post in a hidden form when
    if (('quote' === $action || 'replymessage' === $action || $giveRevision) && !empty($postId)) {
        // we are quoting or replying to a message (<> reply to a thread !!!)
        $form->addHidden('post_parent_id', $post->getIid());
        // If we are replying or are quoting then we display a default title.
        $posterName = UserManager::formatUserFullName($post->getUser());
        $defaults['post_title'] = get_lang('Re:').api_html_entity_decode($post->getTitle(), ENT_QUOTES);
        // When we are quoting a message then we have to put that message into the wysiwyg editor.
        // Note: The style has to be hardcoded here because using class="quote" didn't work.
        if ('quote' === $action) {
            $defaults['post_text'] = '<div>&nbsp;</div>
                <div style="margin: 5px;">
                    <div style="font-size: 90%; font-style: italic;">'.
                get_lang('Quoting').' '.$posterName.':</div>
                        <div style="color: #006600; font-size: 90%;  font-style: italic; background-color: #FAFAFA; border: #D1D7DC 1px solid; padding: 3px;">'.
                prepare4display($post->getPostText()).'
                        </div>
                    </div>
                <div>&nbsp;</div>
                <div>&nbsp;</div>
            ';
        }
        if ($giveRevision) {
            $defaults['post_text'] = prepare4display($post->getPostText());
        }
    }

    $form->setDefaults(isset($defaults) ? $defaults : []);

    // The course admin can make a thread sticky (=appears with special icon and always on top).
    $form->addRule('post_title', get_lang('Required field'), 'required');
    if (1 == $forum->getAllowAnonymous() && !isset($_user['user_id'])) {
        $form->addRule(
            'poster_name',
            get_lang('Required field'),
            'required'
        );
    }

    if ($showPreview && 'newthread' !== $action && !empty($threadId)) {
        $previewHtml = render_thread_preview_html((int) $forumId, (int) $threadId, (int) $postId, 20);
        $form->addElement('html', '<div class="mt-4">'.$previewHtml.'</div>');
    }

    // Validation or display
    if ($form->validate()) {
        $check = Security::check_token('post');
        if ($check) {
            $values = $form->getSubmitValues();
            if (isset($values['thread_qualify_gradebook']) &&
                '1' == $values['thread_qualify_gradebook'] &&
                empty($values['weight_calification'])
            ) {
                Display::addFlash(
                    Display::return_message(
                        get_lang('You must assign a score to this activity').'&nbsp;<a href="javascript:window.history.go(-1);">'.get_lang('Back').'</a>',
                        'error',
                        false
                    )
                );

                return false;
            }

            $postId = 0;
            $threadId = 0;

            switch ($action) {
                case 'quote':
                case 'replythread':
                case 'replymessage':
                    $postId = store_reply($forum, $thread, $values);

                    break;
            }

            if ($postId) {
                if (isset($values['give_revision']) && 1 == $values['give_revision']) {
                    $extraFieldValues = new ExtraFieldValue('forum_post');
                    $revisionLanguage = isset($values['extra_revision_language']) ? $values['extra_revision_language'] : '';
                    $params = [
                        'item_id' => $postId,
                        'extra_revision_language' => $revisionLanguage,
                    ];

                    $extraFieldValues->saveFieldValues(
                        $params,
                        false,
                        false,
                        ['revision_language']
                    );
                }

                if (in_array($action, ['replythread', 'replymessage', 'quote'])) {
                    $extraFieldValues = new ExtraFieldValue('forum_post');
                    $params = [
                        'item_id' => $postId,
                        'extra_ask_for_revision' => isset($values['extra_ask_for_revision']) ? $values['extra_ask_for_revision'] : '',
                    ];
                    $extraFieldValues->saveFieldValues(
                        $params,
                        false,
                        false,
                        ['ask_for_revision']
                    );
                }
            }

            $url = api_get_path(WEB_CODE_PATH).'forum/viewthread.php?'.api_get_cidreq().'&'.http_build_query(
                [
                    'forum' => $forumId,
                    'thread' => $thread->getIid(),
                ]
            );

            Security::clear_token();
            header('Location: '.$url);
            exit;
        }
    } else {
        $token = Security::get_token();
        $form->addElement('hidden', 'sec_token');
        $form->setConstants(['sec_token' => $token]);

        // Delete from $_SESSION forum attachment from other posts
        // and keep only attachments for new post
        //clearAttachedFiles(FORUM_NEW_POST);
        // Get forum attachment ajax table to add it to form
        $attachmentAjaxTable = getAttachmentsAjaxTable(0, $forum->getIid());
        $ajaxHtml = $attachmentAjaxTable;
        $form->addElement('html', $ajaxHtml);

        return $form;
    }
}

/**
 * Build a lightweight Tailwind-based preview for a thread (no iframe).
 * Security: keep XSS protection as in the original iframe.
 */
function render_thread_preview_html(
    int $forumId,
    int $threadId,
    int $highlightPostId = 0,
    int $limit = 20,
    string $heightClass = 'h-72'
): string {
    $rows = Database::getManager()->createQueryBuilder()
        ->select(
            'p.iid AS iid',
            'p.postDate AS post_date',
            'p.title AS post_title',
            'p.postText AS post_text',
            'u.id AS user_id',
            'u.username AS username',
            'u.firstname AS firstname',
            'u.lastname AS lastname'
        )
        ->from(CForumPost::class, 'p')
        ->innerJoin('p.user', 'u')
        ->where('IDENTITY(p.thread) = :threadId')
        ->andWhere('p.visible = true')
        ->andWhere('(p.status IS NULL OR p.status = :validated)')
        ->setParameter('threadId', (int) $threadId)
        ->setParameter('validated', (int) CForumPost::STATUS_VALIDATED)
        ->orderBy('p.postDate', 'ASC')
        ->setMaxResults((int) $limit)
        ->getQuery()
        ->getArrayResult();
    foreach ($rows as &$row) {
        // The raw row used to carry the datetime as a string.
        $row['post_date'] = $row['post_date']->format('Y-m-d H:i:s');
    }
    unset($row);

    ob_start(); ?>
    <div class="mt-6 w-full">
        <div
            class="forum-preview <?php echo $heightClass ?> overflow-y-auto border border-gray-25 rounded-xl p-3 bg-white"
            role="region"
            aria-label="<?php echo get_lang('Discussion thread'); ?>"
        >
            <div class="sticky top-0 z-10 bg-white pb-2 text-gray-90 font-bold shadow-[0_1px_0_0_#e4e9ed]">
                <?php echo get_lang('Discussion thread'); ?>
            </div>

            <?php if (empty($rows)): ?>
                <div class="text-gray-50 text-body-2 mt-2"><?php echo get_lang('No post yet'); ?></div>
            <?php else: foreach ($rows as $row):
                $postId  = (int) $row['iid'];
                $isFocus = $highlightPostId && $postId === (int) $highlightPostId;

                // Compute display name (fallback to username)
                $isAnon      = ((int)$row['user_id'] === (int)\Chamilo\CoreBundle\Entity\User::ANONYMOUS);
                $displayName = $isAnon
                    ? get_lang('Anonymous')
                    : (api_get_person_name($row['firstname'], $row['lastname']) ?: $row['username']);

                $userTitle = api_htmlentities(sprintf(get_lang('Login: %s'), $row['username']), ENT_QUOTES);
                ?>
                <article id="post-<?php echo $postId ?>"
                         class="mb-2 border border-gray-25 rounded-lg p-3 <?php echo $isFocus ? 'bg-gray-15 ring-1 ring-primary' : 'bg-white' ?>">
                    <div class="text-caption text-gray-50" title="<?php echo $userTitle ?>">
                        <?php echo Security::remove_XSS($displayName) ?> • <?php echo api_convert_and_format_date($row['post_date']) ?>
                    </div>

                    <div
                        class="font-bold mt-1 mb-1 text-gray-90"
                        dir="auto"
                    >
                        <?php echo Security::remove_XSS($row['post_title']) ?>
                    </div>

                    <div
                        class="text-body-2 leading-4"
                        dir="auto"
                    >
                        <?php echo Security::remove_XSS($row['post_text'], STUDENT) ?>
                    </div>
                </article>
            <?php endforeach; endif; ?>
        </div>

        <div class="mt-2">
            <a class="text-body-2 underline text-primary"
               href="<?php echo api_get_path(WEB_CODE_PATH) . 'forum/viewthread.php?' . api_get_cidreq()
               . '&forum='.(int)$forumId.'&thread='.(int)$threadId ?>">
                <?php echo get_lang('View full thread') ?>
            </a>
        </div>
    </div>
    <?php
    if ($highlightPostId) {
        // Try to bring the highlighted post into view.
        echo '<script>try{document.getElementById("post-'.$highlightPostId.'")?.scrollIntoView({behavior:"instant",block:"start"});}catch(e){}</script>';
    }

    return (string) ob_get_clean();
}

function newThread(CForum $forum, $form_values = '', $showPreview = true)
{
    $_user = api_get_user_info();
    $forumId = $forum->getIid();
    $my_post = isset($_GET['post']) ? (int) $_GET['post'] : '';
    $giveRevision = isset($_GET['give_revision']) && 1 == $_GET['give_revision'];
    $action = 'new_thread';

    $url = api_get_self().'?'.http_build_query(
            [
                'action' => $action,
                'forum' => $forumId,
                'post' => $my_post,
            ]
        ).'&'.api_get_cidreq();

    $form = new FormValidator(
        'thread',
        'post',
        $url
    );

    // Setting the form elements.
    $form->addElement('hidden', 'forum_id', $forumId);
    $form->addElement('hidden', 'thread_id', 0);
    $form->addElement('hidden', 'action', $action);

    // If anonymous posts are allowed we also display a form to allow the user to put his name or username in.
    if (1 == $forum->getAllowAnonymous() && !isset($_user['user_id'])) {
        $form->addElement('text', 'poster_name', get_lang('Name'));
        $form->applyFilter('poster_name', 'html_filter');
    }

    $form->addElement('text', 'post_title', get_lang('Title'));
    $form->applyFilter('post_title', 'html_filter');
    $form->addHtmlEditor(
        'post_text',
        get_lang('Text'),
        true,
        false,
        api_is_allowed_to_edit(null, true) ? [
            'ToolbarSet' => 'Forum',
            'Width' => '100%',
            'Height' => '300',
        ] : [
            'ToolbarSet' => 'ForumStudent',
            'Width' => '100%',
            'Height' => '300',
            'UserStatus' => 'student',
        ]
    );
    $form->addRule('post_text', get_lang('Required field'), 'required');

    $extraFields = new ExtraField('forum_post');
    $extraFields->addElements(
        $form,
        null,
        [], //exclude
        false, // filter
        false, // tag as select
        ['ask_for_revision'], //show only fields
        [], // order fields
        [] // extra data);
    );

    if ((api_is_course_admin() || api_is_session_general_coach() || api_is_course_tutor())) {
        $form->addElement('advanced_settings', 'advanced_params', get_lang('Advanced settings'));
        $form->addElement('html', '<div id="advanced_params_options" style="display:none">');

        // Thread qualify
        GradebookUtils::load_gradebook_select_in_tool($form);
        $form->addCheckBox(
            'thread_qualify_gradebook',
            '',
            get_lang('Grade this thread'),
            ['onclick' => 'javascript:if(this.checked==true){document.getElementById(\'options_field\').style.display = \'block\';}else{document.getElementById(\'options_field\').style.display = \'none\';}']
        );

        $form->addElement('html', '<div id="options_field" style="display:none">');
        $form->addElement('text', 'numeric_calification', get_lang('Maximum score'));
        $form->applyFilter('numeric_calification', 'html_filter');
        $form->addElement('text', 'calification_notebook_title', get_lang('Column header in Competences Report'));
        $form->applyFilter('calification_notebook_title', 'html_filter');

        $form->addElement(
            'text',
            'weight_calification',
            get_lang('Weight in Report'),
            ['value' => '0.00', 'onfocus' => 'javascript: this.select();']
        );
        $form->applyFilter('weight_calification', 'html_filter');

        $group = [];
        $group[] = $form->createElement('radio', 'thread_peer_qualify', null, get_lang('Yes'), 1);
        $group[] = $form->createElement('radio', 'thread_peer_qualify', null, get_lang('No'), 0);
        $form->addGroup(
            $group,
            '',
            [
                get_lang('Thread scored by peers'),
                get_lang('If selected, this option will require each student to qualify at least 2 other students in order to get his score greater than 0 in the gradebook.'),
            ]
        );
        $form->addElement('html', '</div>');
        $form->addElement('html', '</div>');
    }

    SkillModel::addSkillsToForm($form, ITEM_TYPE_FORUM_THREAD, 0);
    $form->addElement('checkbox', 'thread_sticky', '', get_lang('This is a sticky message (appears always on top and has a special sticky icon)'));

    $form->addFile('user_upload', get_lang('Attachment'));

    if ($giveRevision) {
        $hide = ('true' === api_get_setting('forum.hide_forum_post_revision_language'));
        $form->addHidden('give_revision', 1);
        if (false === $hide) {
            $extraField = new ExtraField('forum_post');
            $extraField->addElements(
                $form,
                null,
                [], //exclude
                false, // filter
                false, // tag as select
                ['revision_language'], //show only fields
                [], // order fields
                [] // extra data
            );
        } else {
            $form->addHidden('extra_revision_language', 1);
        }
    }
    $form->addButtonCreate(get_lang('Create thread'), 'SubmitPost');

    $defaults['thread_peer_qualify'] = 0;
    if (!empty($form_values)) {
        $defaults['post_title'] = prepare4display($form_values['post_title']);
        $defaults['post_text'] = prepare4display($form_values['post_text']);
        $defaults['post_notification'] = (int) $form_values['post_notification'];
        $defaults['thread_sticky'] = (int) $form_values['thread_sticky'];
        $defaults['thread_peer_qualify'] = (int) $form_values['thread_peer_qualify'];
    }

    $form->setDefaults(isset($defaults) ? $defaults : []);

    // The course admin can make a thread sticky (=appears with special icon and always on top).
    $form->addRule('post_title', get_lang('Required field'), 'required');
    if (1 == $forum->getAllowAnonymous() && !isset($_user['user_id'])) {
        $form->addRule(
            'poster_name',
            get_lang('Required field'),
            'required'
        );
    }

    // Validation or display
    if ($form->validate()) {
        $check = Security::check_token('post');
        if ($check) {
            $values = $form->getSubmitValues();
            if (isset($values['thread_qualify_gradebook']) &&
                '1' == $values['thread_qualify_gradebook'] &&
                empty($values['weight_calification'])
            ) {
                Display::addFlash(
                    Display::return_message(
                        get_lang('You must assign a score to this activity').'&nbsp;<a href="javascript:window.history.go(-1);">'.get_lang('Back').'</a>',
                        'error',
                        false
                    )
                );

                return false;
            }

            $newThread = saveThread($forum, $values);
            if ($newThread) {
                SkillModel::saveSkills($form, ITEM_TYPE_FORUM_THREAD, $newThread->getIid());
                $post = $newThread->getThreadLastPost();

                if ($post) {
                    $postId = $post->getIid();

                    if (isset($values['give_revision']) && 1 == $values['give_revision']) {
                        $extraFieldValues = new ExtraFieldValue('forum_post');
                        $revisionLanguage = isset($values['extra_revision_language']) ? $values['extra_revision_language'] : '';

                        $params = [
                            'item_id' => $postId,
                            'extra_revision_language' => $revisionLanguage,
                        ];

                        $extraFieldValues->saveFieldValues(
                            $params,
                            false,
                            false,
                            ['revision_language']
                        );
                    }
                    $extraFieldValues = new ExtraFieldValue('forum_post');
                    $params = [
                        'item_id' => $postId,
                        'extra_ask_for_revision' => $values['extra_ask_for_revision'] ?? '',
                    ];
                    $extraFieldValues->saveFieldValues(
                        $params,
                        false,
                        false,
                        ['ask_for_revision']
                    );
                }
            }

            $url = api_get_path(WEB_CODE_PATH).'forum/viewthread.php?'.api_get_cidreq().'&'.http_build_query(
                [
                    'forum' => $forumId,
                    'thread' => $newThread->getIid(),
                ]
            );

            Security::clear_token();
            header('Location: '.$url);
            exit;
        }
    } else {
        $token = Security::get_token();
        $form->addElement('hidden', 'sec_token');
        $form->setConstants(['sec_token' => $token]);

        // Delete from $_SESSION forum attachment from other posts
        // and keep only attachments for new post
        //clearAttachedFiles(FORUM_NEW_POST);
        // Get forum attachment ajax table to add it to form
        $attachmentAjaxTable = getAttachmentsAjaxTable(0, $forum->getIid());
        $ajaxHtml = $attachmentAjaxTable;
        $form->addElement('html', $ajaxHtml);

        return $form;
    }
}

/**
 * @param CForumThread $threadInfo
 * @param int          $user_id
 * @param int          $thread_id
 * @param int          $thread_qualify
 * @param int          $qualify_time
 * @param int          $session_id
 *
 * @return string
 *
 * @author Isaac Flores <isaac.flores@dokeos.com>, U.N.A.S University
 *
 * @version October 2008, dokeos  1.8.6
 */
function saveThreadScore(
    CForumThread $threadEntity,
    $user_id,
    $thread_id,
    $thread_qualify,
    $qualify_time,
    $session_id
) {
    $table_threads_qualify = Database::get_course_table(TABLE_FORUM_THREAD_QUALIFY);
    $em = Database::getManager();

    $course_id = api_get_course_int_id();
    $session_id = (int) $session_id;
    $thread_id = (int) $thread_id;
    $user_id = (int) $user_id;
    $thread_qualify = (float) $thread_qualify;
    $currentUserId = api_get_user_id();

    $max = $threadEntity->getThreadQualifyMax();
    if ($thread_qualify <= $max) {
        $countQb = $em->createQueryBuilder()
            ->select('COUNT(q.iid)')
            ->from(CForumThreadQualify::class, 'q')
            ->where('IDENTITY(q.user) = :userId')
            ->andWhere('IDENTITY(q.thread) = :threadId')
            ->setParameter('userId', $user_id)
            ->setParameter('threadId', $thread_id);
        if ($threadEntity->isThreadPeerQualify()) {
            $countQb->andWhere('IDENTITY(q.qualifyUser) = :qualifyUserId')
                ->setParameter('qualifyUserId', (int) $currentUserId);
        }

        if (0 == $countQb->getQuery()->getSingleScalarResult()) {
            $connection = $em->getConnection();
            $connection->createQueryBuilder()
                ->insert($table_threads_qualify)
                ->values([
                    'c_id' => ':courseId',
                    'user_id' => ':userId',
                    'thread_id' => ':threadId',
                    $connection->quoteIdentifier('qualify') => ':qualify',
                    'qualify_user_id' => ':qualifyUserId',
                    'qualify_time' => ':qualifyTime',
                ])
                ->setParameter('courseId', (int) $course_id)
                ->setParameter('userId', $user_id)
                ->setParameter('threadId', $thread_id)
                ->setParameter('qualify', $thread_qualify)
                ->setParameter('qualifyUserId', (int) $currentUserId)
                ->setParameter('qualifyTime', (string) $qualify_time)
                ->executeStatement();

            return 'insert';
        } else {
            saveThreadScoreHistory(
                '1',
                $course_id,
                $user_id,
                $thread_id
            );

            // Update
            $em->createQueryBuilder()
                ->update(CForumThreadQualify::class, 'q')
                ->set('q.qualify', ':qualify')
                ->set('q.qualifyTime', ':qualifyTime')
                ->where('IDENTITY(q.user) = :userId')
                ->andWhere('IDENTITY(q.thread) = :threadId')
                ->andWhere('IDENTITY(q.qualifyUser) = :qualifyUserId')
                ->setParameter('qualify', $thread_qualify)
                ->setParameter('qualifyTime', (string) $qualify_time)
                ->setParameter('userId', $user_id)
                ->setParameter('threadId', $thread_id)
                ->setParameter('qualifyUserId', (int) $currentUserId)
                ->getQuery()
                ->execute();

            return 'update';
        }
    }

    return '';
}

/**
 * This function shows qualify.
 *
 * @param string $option    contains the information of option to run
 * @param int    $user_id   contains the information the current user id
 * @param int    $thread_id contains the information the current thread id
 *
 * @return int qualify
 *             <code> $option=1 obtained the qualification of the current thread</code>
 *
 * @author Isaac Flores <isaac.flores@dokeos.com>, U.N.A.S University
 *
 * @version October 2008, dokeos  1.8.6
 */
function showQualify($option, $user_id, $thread_id)
{
    $course_id = api_get_course_int_id();
    $user_id = (int) $user_id;
    $thread_id = (int) $thread_id;

    if (empty($user_id) || empty($thread_id)) {
        return 0;
    }

    $qb = null;
    switch ($option) {
        case 1:
            $qb = Database::getManager()->createQueryBuilder()
                ->select('q.qualify AS value')
                ->from(CForumThreadQualify::class, 'q')
                ->where('q.cId = :courseId')
                ->andWhere('IDENTITY(q.user) = :userId')
                ->andWhere('IDENTITY(q.thread) = :threadId')
                ->setParameter('courseId', (int) $course_id)
                ->setParameter('userId', $user_id)
                ->setParameter('threadId', $thread_id);

            break;
        case 2:
            $qb = Database::getManager()->createQueryBuilder()
                ->select('t.threadQualifyMax AS value')
                ->from(CForumThread::class, 't')
                ->where('t.iid = :threadId')
                ->setParameter('threadId', $thread_id);

            break;
    }

    if (null !== $qb) {
        $row = $qb->setMaxResults(1)->getQuery()->getArrayResult()[0] ?? false;
        if ($row) {
            return $row['value'];
        }
    }

    return 0;
}

/**
 * This function gets qualify historical.
 *
 * @param int  $user_id   contains the information the current user id
 * @param int  $thread_id contains the information the current thread id
 * @param bool $opt       contains the information of option to run
 *
 * @return array
 *
 * @author Christian Fasanando <christian.fasanando@dokeos.com>,
 * @author Isaac Flores <isaac.flores@dokeos.com>,
 *
 * @version October 2008, dokeos  1.8.6
 */
function getThreadScoreHistory($user_id, $thread_id, $opt)
{
    $user_id = (int) $user_id;
    $thread_id = (int) $thread_id;

    $course_id = api_get_course_int_id();

    $rows = Database::getManager()->createQueryBuilder()
        ->select(
            'l.iid AS iid',
            'l.cId AS c_id',
            'l.userId AS user_id',
            'l.threadId AS thread_id',
            'l.qualify AS qualify',
            'l.qualifyUserId AS qualify_user_id',
            'l.qualifyTime AS qualify_time',
            'l.sessionId AS session_id'
        )
        ->from(CForumThreadQualifyLog::class, 'l')
        ->where('l.cId = :courseId')
        ->andWhere('l.threadId = :threadId')
        ->andWhere('l.userId = :userId')
        ->setParameter('courseId', (int) $course_id)
        ->setParameter('threadId', $thread_id)
        ->setParameter('userId', $user_id)
        ->orderBy('l.qualifyTime', 'false' == $opt ? 'ASC' : 'DESC')
        ->getQuery()
        ->getArrayResult();
    $log = [];
    foreach ($rows as $row) {
        // The raw row used to carry the datetime as a string.
        $row['qualify_time'] = $row['qualify_time']?->format('Y-m-d H:i:s');
        $log[] = $row;
    }

    return $log;
}

/**
 * This function stores qualify historical.
 *
 * @param bool contains the information of option to run
 * @param string contains the information the current course id
 * @param int contains the information the current forum id
 * @param int contains the information the current user id
 * @param int contains the information the current thread id
 * @param int contains the information the current qualify
 * @param string $option
 * @param int    $course_id
 * @param int    $user_id
 * @param int    $thread_id
 *
 * @author Isaac Flores <isaac.flores@dokeos.com>, U.N.A.S University
 *
 * @version October 2008, dokeos  1.8.6
 */
function saveThreadScoreHistory(
    $option,
    $course_id,
    $user_id,
    $thread_id
) {
    $table_threads_qualify_log = Database::get_course_table(TABLE_FORUM_THREAD_QUALIFY_LOG);

    $thread_id = (int) $thread_id;
    $course_id = (int) $course_id;
    $user_id = (int) $user_id;
    $qualify_user_id = api_get_user_id();

    if (1 == $option) {
        // Extract information of thread_qualify.
        $em = Database::getManager();
        $row = $em->createQueryBuilder()
            ->select('q.qualify AS qualify', 'q.qualifyTime AS qualify_time')
            ->from(CForumThreadQualify::class, 'q')
            ->where('q.cId = :courseId')
            ->andWhere('IDENTITY(q.user) = :userId')
            ->andWhere('IDENTITY(q.thread) = :threadId')
            ->andWhere('IDENTITY(q.qualifyUser) = :qualifyUserId')
            ->setParameter('courseId', $course_id)
            ->setParameter('userId', $user_id)
            ->setParameter('threadId', $thread_id)
            ->setParameter('qualifyUserId', (int) $qualify_user_id)
            ->setMaxResults(1)
            ->getQuery()
            ->getArrayResult()[0] ?? false;

        // Nothing to archive when this grader has not graded the thread before.
        if (empty($row)) {
            return;
        }

        $qualifyTime = empty($row['qualify_time']) ? null : $row['qualify_time']->format('Y-m-d H:i:s');

        // Insert thread_historical.
        $connection = $em->getConnection();
        $connection->createQueryBuilder()
            ->insert($table_threads_qualify_log)
            ->values([
                'c_id' => ':courseId',
                'user_id' => ':userId',
                'thread_id' => ':threadId',
                $connection->quoteIdentifier('qualify') => ':qualify',
                'qualify_user_id' => ':qualifyUserId',
                'qualify_time' => ':qualifyTime',
            ])
            ->setParameter('courseId', $course_id)
            ->setParameter('userId', $user_id)
            ->setParameter('threadId', $thread_id)
            ->setParameter('qualify', (float) $row['qualify'])
            ->setParameter('qualifyUserId', (int) $qualify_user_id)
            ->setParameter('qualifyTime', $qualifyTime)
            ->executeStatement();
    }
}

/**
 * This function stores a reply in the forum_post table.
 * It also updates the forum_threads table (thread_replies +1 , thread_last_post, thread_date).
 *
 * @param array $values
 * @param int   $courseId Optional
 * @param int   $userId   Optional
 *
 * @return int post id
 */
function store_reply(CForum $forum, CForumThread $thread, $values, $courseId = 0, $userId = 0)
{
    $courseId = !empty($courseId) ? $courseId : api_get_course_int_id();
    $post_date = api_get_utc_datetime();
    $userId = $userId ?: api_get_user_id();

    if (1 == $forum->getAllowAnonymous()) {
        if (api_is_anonymous() && empty($userId)) {
            $userId = api_get_anonymous_id();
        }
    }

    if (empty($userId)) {
        return false;
    }

    $visible = 1;
    if ('1' == $forum->getApprovalDirectPost() &&
        !api_is_allowed_to_edit(null, true)
    ) {
        $visible = 0;
    }

    $upload_ok = 1;
    $new_post_id = 0;

    if ($upload_ok) {
        $course = api_get_course_entity($courseId);
        $session = api_get_session_entity();

        $repo = Container::getForumPostRepository();
        $post = new CForumPost();
        $text = empty($values['post_text']) ? '' : $values['post_text'];
        $post
            ->setTitle($values['post_title'])
            ->setPostText($text)
            ->setThread($thread)
            ->setForum($forum)
            ->setUser(api_get_user_entity($userId))
            ->setPostNotification(isset($values['post_notification']) ? (bool) $values['post_notification'] : false)
            ->setVisible($visible)
            ->setPostDate(api_get_utc_datetime(null, false, true))
            ->setParent($thread)
            ->addCourseLink($course, $session)
        ;

        if (isset($values['post_parent_id']) && !empty($values['post_parent_id'])) {
            $parent = $repo->find($values['post_parent_id']);
            $post->setPostParent($parent);
        }
        $repo->create($post);

        $new_post_id = $post->getIid();
        if ($new_post_id) {
            $values['new_post_id'] = $new_post_id;
            $message = get_lang('The reply has been added');

            if (!empty($_POST['file_ids']) && is_array($_POST['file_ids'])) {
                foreach ($_POST['file_ids'] as $key => $id) {
                    editAttachedFile(
                        [
                            'comment' => $_POST['file_comments'][$key],
                            'post_id' => $new_post_id,
                        ],
                        $id
                    );
                }
            }

            // Update the thread.
            updateThreadInfo($values['thread_id'], $new_post_id, $post_date);

            if ('1' == $forum->getApprovalDirectPost() &&
                !api_is_allowed_to_edit(null, true)
            ) {
                $message .= '<br />'.get_lang('Your message has to be approved before people can view it.').'<br />';
            }

            if ($forum->isModerated() &&
                !api_is_allowed_to_edit(null, true)
            ) {
                $message .= '<br />'.get_lang('Your message has to be approved before people can view it.').'<br />';
            }

            // Setting the notification correctly.
            $my_post_notification = isset($values['post_notification']) ? $values['post_notification'] : null;
            if (1 == $my_post_notification) {
                set_notification('thread', $values['thread_id'], true);
            }

            send_notification_mails(
                $forum,
                $thread,
                $values,
                $course
            );
            add_forum_attachment_file('', $post);

            $logInfo = [
                'tool' => TOOL_FORUM,
                'tool_id' => $values['forum_id'],
                'tool_id_detail' => $values['thread_id'],
                'action' => 'new-post',
                'action_details' => $values['action'],
                'info' => $values['post_title'],
            ];
            Event::registerLog($logInfo);
        }

        Session::erase('formelements');
        Session::erase('origin');
        Session::erase('breadcrumbs');
        Session::erase('addedresource');
        Session::erase('addedresourceid');

        Display::addFlash(Display::return_message($message, 'confirmation', false));
    } else {
        Display::addFlash(
            Display::return_message(
                get_lang('No file was uploaded.').' '.get_lang('Please select a file before pressing the upload button.'),
                'error'
            )
        );
    }

    return $new_post_id;
}

/**
 * This function displays the form that is used to edit a post. This can be a new thread or a reply.
 *
 * @param CForumPost   $post        contains all the information about the current post
 * @param CForumThread $thread      contains all the information about the current thread
 * @param CForum       $forum       contains all info about the current forum (to check if attachments are allowed)
 * @param array        $form_values contains the default values to fill the form
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 *
 * @version february 2006, dokeos 1.8
 */
function show_edit_post_form(
    $post,
    $thread,
    $forum,
    $form_values,
    $id_attach = 0
) {
    // Initialize the object.
    $form = new FormValidator(
        'edit_post',
        'post',
        api_get_self().'?'.api_get_cidreq().'&forum='.(int) ($_GET['forum']).'&thread='.(int) ($_GET['thread']).'&post='.(int) ($_GET['post'])
    );
    $form->addElement('header', get_lang('Edit a post'));
    // Setting the form elements.
    $form->addElement('hidden', 'post_id', $post->getIid());
    $form->addElement('hidden', 'thread_id', $thread->getIid());
    $form->addElement('hidden', 'id_attach', $id_attach);

    if (null === $post->getPostParent()) {
        $form->addElement('hidden', 'is_first_post_of_thread', '1');
    }

    $form->addElement('text', 'post_title', get_lang('Title'));
    $form->applyFilter('post_title', 'html_filter');
    $form->addElement(
        'html_editor',
        'post_text',
        get_lang('Text'),
        null,
        api_is_allowed_to_edit(null, true) ? [
            'ToolbarSet' => 'Forum',
            'Width' => '100%',
            'Height' => '400',
        ] : [
            'ToolbarSet' => 'ForumStudent',
            'Width' => '100%',
            'Height' => '400',
            'UserStatus' => 'student',
        ]
    );
    $form->addRule('post_text', get_lang('Required field'), 'required');

    $extraFields = new ExtraField('forum_post');
    $extraFields->addElements($form, $post->getIid());

    $form->addButtonAdvancedSettings('advanced_params');
    $form->addElement('html', '<div id="advanced_params_options" style="display:none">');

    if ($forum->isModerated() && api_is_allowed_to_edit(null, true)) {
        $group = [];
        $group[] = $form->createElement(
            'radio',
            'status',
            null,
            get_lang('Validated'),
            1
        );
        $group[] = $form->createElement(
            'radio',
            'status',
            null,
            get_lang('Waiting for moderation'),
            2
        );
        $group[] = $form->createElement(
            'radio',
            'status',
            null,
            get_lang('Rejected'),
            3
        );
        $form->addGroup($group, 'status', get_lang('Status'));
    }

    $defaults['status']['status'] = $post->getStatus();

    $form->addElement(
        'checkbox',
        'post_notification',
        '',
        get_lang('Notify me by e-mail when somebody replies')
    );

    if (api_is_allowed_to_edit(null, true) && null === $post->getPostParent()) {
        // The sticky checkbox only appears when it is the first post of a thread.
        $form->addElement(
            'checkbox',
            'thread_sticky',
            '',
            get_lang('This is a sticky message (appears always on top and has a special sticky icon)')
        );
        if (1 == $thread->getThreadSticky()) {
            $defaults['thread_sticky'] = true;
        }
    }

    $form->addElement('html', '</div>');

    $form->addFile('user_upload[]', get_lang('Attachment'));
    $form->addButton(
        'add_attachment',
        get_lang('Add attachment'),
        'paperclip',
        'default',
        'default',
        null,
        ['id' => 'reply-add-attachment']
    );

    $form->addButtonUpdate(get_lang('Edit'), 'SubmitPost');

    // Setting the default values for the form elements.
    $defaults['post_title'] = $post->getTitle();
    $defaults['post_text'] = $post->getPostText();

    if (1 == $post->getPostNotification()) {
        $defaults['post_notification'] = true;
    }

    if (!empty($form_values)) {
        $defaults['post_notification'] = Security::remove_XSS($form_values['post_notification']);
        $defaults['thread_sticky'] = Security::remove_XSS($form_values['thread_sticky']);
    }

    $form->setDefaults($defaults);

    // The course admin can make a thread sticky (=appears with special icon and always on top).
    $form->addRule('post_title', get_lang('Required field'), 'required');

    // Validation or display
    if ($form->validate()) {
        $values = $form->exportValues();
        $values['item_id'] = $post->getIid();
        $extraFieldValues = new ExtraFieldValue('forum_post');
        $extraFieldValues->saveFieldValues($values);

        store_edit_post($forum, $values);
    } else {
        // Delete from $_SESSION forum attachment from other posts
        clearAttachedFiles($post->getIid());
        // Get forum attachment ajax table to add it to form
        $fileData = getAttachmentsAjaxTable($post->getIid(), $forum->getIid());
        $form->addElement('html', $fileData);
        $form->display();
    }
}

/**
 * This function stores the edit of a post in the forum_post table.
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 *
 * @version february 2006, dokeos 1.8
 */
function store_edit_post(CForum $forum, $values)
{
    $logInfo = [
        'tool' => TOOL_FORUM,
        'tool_id' => $_GET['forum'],
        'tool_id_detail' => $values['thread_id'],
        'action' => 'edit-post',
        'action_details' => 'post',
        'info' => $values['post_title'],
    ];
    Event::registerLog($logInfo);

    $threadTable = Database::get_course_table(TABLE_FORUM_THREAD);

    //check if this post is the first of the thread
    // First we check if the change affects the thread and if so we commit
    // the changes (sticky and post_title=thread_title are relevant).
    $posts = getPosts($forum, $values['thread_id']);
    $first_post = null;
    if (!empty($posts) && count($posts) > 0 && isset($posts[0])) {
        $first_post = $posts[0];
    }

    if (!empty($first_post) && $first_post['post_id'] == $values['post_id']) {
        // Simple edit
        $params = [
            'title' => $values['post_title'],
            'thread_sticky' => isset($values['thread_sticky']) ? $values['thread_sticky'] : 0,
        ];
        $where = ['iid = ?' => [$values['thread_id']]];
        Database::update($threadTable, $params, $where);
    }

    $status = '';
    $updateStatus = false;
    if ($forum->isModerated()) {
        if (api_is_allowed_to_edit(null, true)) {
            $status = $values['status']['status'];
            $updateStatus = true;
        } else {
            $status = CForumPost::STATUS_WAITING_MODERATION;
            $updateStatus = true;
        }
    }

    $postId = $values['post_id'];
    $repo = Container::getForumPostRepository();
    /** @var CForumPost $post */
    $post = $repo->find($postId);
    if ($post) {
        $post
            ->setTitle($values['post_title'])
            ->setPostText($values['post_text'])
            ->setPostNotification(isset($values['post_notification']))
        ;

        if ($updateStatus) {
            $post->setStatus($status);
        }
        $repo->update($post);
    }

    // Update attached files
    if (!empty($_POST['file_ids']) && is_array($_POST['file_ids'])) {
        foreach ($_POST['file_ids'] as $key => $id) {
            editAttachedFile(
                [
                    'comment' => $_POST['file_comments'][$key],
                    'post_id' => $values['post_id'],
                ],
                $id
            );
        }
    }

    if (!empty($values['remove_attach'])) {
        throw new Exception('remove_attach');
    }

    if (empty($values['id_attach'])) {
        add_forum_attachment_file(
            isset($values['file_comment']) ? $values['file_comment'] : null,
            $post
        );
    }

    $message = get_lang('The post has been modified').'<br />';
    $message .= get_lang('You can now return to the').
        ' <a href="viewforum.php?'.api_get_cidreq().'&forum='.(int) ($_GET['forum']).'&">'.
        get_lang('Forum').'</a><br />';
    $message .= get_lang('You can now return to the').
        ' <a
            href="viewthread.php?'.api_get_cidreq().'&forum='.(int) ($_GET['forum']).'&thread='.$values['thread_id'].'&post='.Security::remove_XSS($_GET['post']).'">'.
        get_lang('Message').'</a>';

    Session::erase('formelements');
    Session::erase('origin');
    Session::erase('breadcrumbs');
    Session::erase('addedresource');
    Session::erase('addedresourceid');

    echo Display::return_message($message, 'confirmation', false);
}

function displayUserLink(User $user)
{
    return '<a href="'.$user->getProfileUrl().'">'.
        Security::remove_XSS(UserManager::formatUserFullName($user)).'</a>';
}

function displayUserImage(User $user)
{
    $url = Container::getIllustrationRepository()->getIllustrationUrl($user, '', ICON_SIZE_BIG);

    return '<div class="thumbnail"><img src="'.$url.'?w=100"/></div>';
}

/**
 * The relies counter gets increased every time somebody replies to the thread.
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 *
 * @version february 2006, dokeos 1.8
 *
 * @param int    $threadId
 * @param string $lastPostId
 * @param string $post_date
 */
function updateThreadInfo($threadId, $lastPostId, $post_date)
{
    $threadId = (int) $threadId;
    $lastPostId = (int) $lastPostId;

    Database::getManager()->createQueryBuilder()
        ->update(CForumThread::class, 't')
        ->set('t.threadReplies', 't.threadReplies + 1')
        ->set('t.threadLastPost', ':lastPostId')
        ->set('t.threadDate', ':postDate')
        ->where('t.iid = :threadId')
        ->setParameter('lastPostId', $lastPostId)
        ->setParameter('postDate', (string) $post_date)
        ->setParameter('threadId', $threadId)
        ->getQuery()
        ->execute();
}

function approvePost(CForumPost $post, $action): string
{
    if ('invisible' === $action) {
        $visibility = 0;
        $message = 'PostMadeInvisible';
    }

    if ('visible' === $action) {
        $visibility = 1;
        handle_mail_cue('post', $post->getIid());
        $message = 'PostMadeVisible';
    }

    $post->setVisible($visibility);
    Database::getManager()->persist($post);
    Database::getManager()->flush();

    return $message;
}

/**
 * This function retrieves all the unapproved messages for a given forum
 * This is needed to display the icon that there are unapproved messages in that thread (only the courseadmin can see
 * this).
 *
 * @param int $forum_id forum where we want to know the unapproved messages of
 *
 * @return array returns
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 *
 * @version february 2006, dokeos 1.8
 */
function get_unaproved_messages($forum_id)
{
    $return_array = [];
    $rows = Database::getManager()->createQueryBuilder()
        ->select('DISTINCT IDENTITY(p.thread) AS thread_id')
        ->from(CForumPost::class, 'p')
        ->where('IDENTITY(p.forum) = :forumId')
        ->andWhere('p.visible = false')
        ->setParameter('forumId', (int) $forum_id)
        ->getQuery()
        ->getArrayResult();
    foreach ($rows as $row) {
        $return_array[] = $row['thread_id'];
    }

    return $return_array;
}

/**
 * This function sends the notification mails to everybody who stated that they wanted to be informed when a new post
 * was added to a given thread.
 */
function send_notification_mails(CForum $forum, CForumThread $thread, $reply_info, ?Course $course = null)
{
    $courseEntity = api_get_course_entity();

    if (null !== $course) {
        $courseEntity = $course;
    }

    $courseId = $courseEntity->getId();

    $sessionId = api_get_session_id();
    $sessionEntity = api_get_session_entity($sessionId);

    // First we need to check if
    // 1. the forum category is visible
    // 2. the forum is visible
    // 3. the thread is visible
    // 4. the reply is visible (=when there is)

    $current_forum_category = null;
    if ($forum->getForumCategory()) {
        $current_forum_category = $forum->getForumCategory();
    }

    $send_mails = false;
    if ($thread->isVisible($courseEntity) &&
        $forum->isVisible($courseEntity) &&
        ($current_forum_category && $forum->getForumCategory()->isVisible($courseEntity)) &&
        '1' != $forum->getApprovalDirectPost()
    ) {
        $send_mails = true;
    }

    // The forum category, the forum, the thread and the reply are visible to the user
    if ($send_mails && !empty($forum)) {
        $postId = isset($reply_info['new_post_id']) ? $reply_info['new_post_id'] : 0;
        send_notifications($forum, $thread, $postId);
    } else {
        if ($forum) {
            $em = Database::getManager();
            $notificationCount = count($em->createQueryBuilder()
                ->select('n.iid AS iid')
                ->from(CForumNotification::class, 'n')
                ->where('n.cId = :courseId')
                ->andWhere('(n.forumId = :forumId OR n.threadId = :threadId)')
                ->setParameter('courseId', (int) $courseId)
                ->setParameter('forumId', (int) $forum->getIid())
                ->setParameter('threadId', (int) $thread->getIid())
                ->getQuery()
                ->getArrayResult());
            $user_id = api_get_user_id();
            for ($i = 0; $i < $notificationCount; $i++) {
                $em->getConnection()->createQueryBuilder()
                    ->insert(Database::get_course_table(TABLE_FORUM_MAIL_QUEUE))
                    ->values(['c_id' => ':courseId', 'thread_id' => ':threadId', 'post_id' => ':postId', 'user_id' => ':userId'])
                    ->setParameter('courseId', (int) $courseId)
                    ->setParameter('threadId', (int) $thread->getIid())
                    ->setParameter('postId', (int) ($reply_info['new_post_id']))
                    ->setParameter('userId', (int) $user_id)
                    ->executeStatement();
            }
        }
    }
}

/**
 * This function is called whenever something is made visible because there might
 * be new posts and the user might have indicated that (s)he wanted to be
 * informed about the new posts by mail.
 *
 * @param string $content Content type (post, thread, forum, forum_category)
 * @param int    $id      Item DB ID of the corresponding content type
 *
 * @return string language variable
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 *
 * @version february 2006, dokeos 1.8
 */
function handle_mail_cue($content, $id)
{
    $em = Database::getManager();

    $course_id = api_get_course_int_id();
    $id = (int) $id;

    /* If the post is made visible we only have to send mails to the people
     who indicated that they wanted to be informed for that thread.*/
    if ('post' === $content) {
        // Getting the information about the post (need the threadId).
        /** @var CForumPost $post */
        $post = Container::getForumPostRepository()->find($id);
        $thread_id = $post->getThread()->getIid();

        // Sending the mail to all the users that wanted to be informed for replies on this thread.
        $rows = $em->createQueryBuilder()
            ->select('users.firstname AS firstname', 'users.lastname AS lastname', 'users.id AS user_id', 'users.email AS email')
            ->from(CForumMailcue::class, 'mailcue')
            ->from(CForumPost::class, 'posts')
            ->from(User::class, 'users')
            ->where('mailcue.cId = :courseId')
            ->andWhere('IDENTITY(posts.thread) = :threadId')
            ->andWhere('posts.postNotification = true')
            ->andWhere('mailcue.threadId = :threadId')
            ->andWhere('users.id = IDENTITY(posts.user)')
            ->andWhere('users.active = 1')
            ->groupBy('users.email')
            ->setParameter('courseId', (int) $course_id)
            ->setParameter('threadId', (int) $thread_id)
            ->getQuery()
            ->getArrayResult();
        $forum = Container::getForumRepository()->find($post->getForum()->getIid());

        foreach ($rows as $row) {
            send_mail($row, $forum, $post->getThread(), $post);
        }
    } elseif ('thread' === $content) {
        // Sending the mail to all the users that wanted to be informed for replies on this thread.
        $rows = $em->createQueryBuilder()
            ->select(
                'users.firstname AS firstname',
                'users.lastname AS lastname',
                'users.id AS user_id',
                'users.email AS email',
                'IDENTITY(posts.forum) AS forum_id'
            )
            ->from(CForumMailcue::class, 'mailcue')
            ->from(CForumPost::class, 'posts')
            ->from(User::class, 'users')
            ->where('mailcue.cId = :courseId')
            ->andWhere('IDENTITY(posts.thread) = :threadId')
            ->andWhere('posts.postNotification = true')
            ->andWhere('mailcue.threadId = :threadId')
            ->andWhere('users.id = IDENTITY(posts.user)')
            ->andWhere('users.active = 1')
            ->groupBy('users.email')
            ->setParameter('courseId', (int) $course_id)
            ->setParameter('threadId', $id)
            ->getQuery()
            ->getArrayResult();
        foreach ($rows as $row) {
            $forum = Container::getForumRepository()->find($row['forum_id']);
            $thread = Container::getForumThreadRepository()->find($id);
            send_mail($row, $forum, $thread);
        }

        // Deleting the relevant entries from the mailcue.
        $em->createQueryBuilder()
            ->delete(CForumMailcue::class, 'mailcue')
            ->where('mailcue.cId = :courseId')
            ->andWhere('mailcue.threadId = :threadId')
            ->setParameter('courseId', (int) $course_id)
            ->setParameter('threadId', $id)
            ->getQuery()
            ->execute();
    } elseif ('forum' === $content) {
        $rows = $em->createQueryBuilder()
            ->select('t.iid AS iid')
            ->from(CForumThread::class, 't')
            ->where('IDENTITY(t.forum) = :forumId')
            ->setParameter('forumId', $id)
            ->getQuery()
            ->getArrayResult();
        foreach ($rows as $row) {
            handle_mail_cue('thread', $row['iid']);
        }
    } elseif ('forum_category' === $content) {
        $rows = $em->createQueryBuilder()
            ->select('f.iid AS iid')
            ->from(CForum::class, 'f')
            ->where('IDENTITY(f.forumCategory) = :categoryId')
            ->setParameter('categoryId', $id)
            ->getQuery()
            ->getArrayResult();
        foreach ($rows as $row) {
            handle_mail_cue('forum', $row['iid']);
        }
    } else {
        return get_lang('Error');
    }
}

/**
 * This function sends the mails for the mail notification.
 */
function send_mail($userInfo, CForum $forum, CForumThread $thread, ?CForumPost $postInfo = null)
{
    if (empty($userInfo) || empty($forum) || empty($thread)) {
        return false;
    }

    $_course = api_get_course_info();
    $user_id = api_get_user_id();
    $forumId = $forum->getIid();
    $threadId = $thread->getIid();

    $thread_link = api_get_path(WEB_CODE_PATH).
        'forum/viewthread.php?'.api_get_cidreq(true, true, false).'&forum='.$forumId.'&thread='.$threadId;

    $email_body = get_lang('Dear').' '.
        api_get_person_name($userInfo['firstname'], $userInfo['lastname'], null, PERSON_NAME_EMAIL_ADDRESS).", <br />\n\r";
    $email_body .= get_lang('New Post in the forum').
        ': '.$forum->getTitle().' - '.$thread->getTitle()." <br />\n";

    $courseId = (int) api_get_setting('forum.global_forums_course_id');
    $subject = get_lang('New Post in the forum').' - '.
        $_course['official_code'].': '.$forum->getTitle().' - '.$thread->getTitle();

    $courseInfoTitle = get_lang('Course').': '.$_course['name'].' - ['.$_course['official_code']."] - <br />\n";
    if (!empty($courseId) && $_course['real_id'] == $courseId) {
        $subject = get_lang('New Post in the forum').': '.
            $forum->getTitle().' - '.$thread->getTitle();
        $courseInfoTitle = " <br />\n";
    }
    $email_body .= $courseInfoTitle;

    if (!empty($postInfo)) {
        $text = cut(strip_tags($postInfo->getPostText()), 100);
        if (!empty($text)) {
            $email_body .= get_lang('Message').": <br />\n ";
            $email_body .= $text;
            $email_body .= "<br /><br />\n";
        }
    }

    $email_body .= get_lang('You stated that you wanted to be informed by e-mail whenever somebody replies on the thread')."<br />\n";

    if (!empty($thread_link)) {
        $email_body .= get_lang('The thread can be found here').' : <br /><a href="'.$thread_link.'">'.$thread_link."</a>\n";
    }

    if ($userInfo['user_id'] != $user_id) {
        MessageManager::send_message(
            $userInfo['user_id'],
            $subject,
            $email_body,
            [],
            [],
            null,
            null,
            null,
            null,
            $user_id
        );
    }
}

/**
 * Prepares a string for displaying by highlighting the search results inside, if any.
 *
 * @param string $input the input string
 *
 * @return string the same string with highlighted hits inside
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, February 2006 - the initial version.
 * @author Ivan Tcholakov, March 2011 - adaptation for Chamilo LMS.
 */
function prepare4display($input)
{
    static $highlightcolors = ['yellow', '#33CC33', '#3399CC', '#9999FF', '#33CC33'];
    static $search;

    if (!isset($search)) {
        if (isset($_POST['search_term'])) {
            $search = $_POST['search_term']; // No html at all.
        } elseif (isset($_GET['search'])) {
            $search = $_GET['search'];
        } else {
            $search = '';
        }
    }

    if (!empty($search)) {
        if (strstr($search, '+')) {
            $search_terms = explode('+', $search);
        } else {
            $search_terms[] = trim($search);
        }
        $counter = 0;
        foreach ($search_terms as $key => $search_term) {
            $input = api_preg_replace(
                '/'.preg_quote(trim($search_term), '/').'/i',
                '<span style="background-color: '.$highlightcolors[$counter].'">$0</span>',
                $input
            );
            $counter++;
        }
    }

    // TODO: Security should be implemented outside this function.
    // Change this to COURSEMANAGERLOWSECURITY or COURSEMANAGER to lower filtering and allow more styles
    // (see comments of Security::remove_XSS() method to learn about other levels).

    return Security::remove_XSS($input, STUDENT, true);
}

/**
 * Display the search form for the forum and display the search results.
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 *
 * @version march 2008, dokeos 1.8.5
 */
function forum_search()
{
    $form = new FormValidator(
        'forumsearch',
        'post',
        'forumsearch.php?'.api_get_cidreq()
    );

    // Setting the form elements.
    $form->addElement('header', '', get_lang('Search in the Forum'));
    $form->addElement('text', 'search_term', get_lang('Search term'), ['autofocus']);
    $form->applyFilter('search_term', 'html_filter');
    $form->addElement('static', 'search_information', '', get_lang('Search in the Forum'));
    $form->addButtonSearch(get_lang('Search'));

    // Setting the rules.
    $form->addRule('search_term', get_lang('Required field'), 'required');
    $form->addRule('search_term', get_lang('Too short'), 'minlength', 3);

    // Validation or display.
    if ($form->validate()) {
        $values = $form->exportValues();
        $form->setDefaults($values);
        $form->display();
        // Display the search results.
        displayForumSearchResults($values['search_term']);
    } else {
        $form->display();
    }
}

/**
 * Displays the search results for forums, threads, and posts within a course.
 */
function displayForumSearchResults(string $searchTerm): void
{
    $forumRepo = Container::getForumRepository();
    $threadRepo = Container::getForumThreadRepository();
    $postRepo = Container::getForumPostRepository();

    $courseId = api_get_course_int_id();
    $sessionId = api_get_session_id();
    $course = api_get_course_entity($courseId);
    $session = api_get_session_entity($sessionId);

    $searchTerms = explode(' ', $searchTerm);
    $searchTerms = array_filter($searchTerms);

    $forumQb = $forumRepo->getResourcesByCourse($course, $session);
    foreach ($searchTerms as $term) {
        $forumQb->andWhere('resource.title LIKE :term OR resource.forumComment LIKE :term')
            ->setParameter('term', '%' . $term . '%');
    }
    $forums = $forumQb->getQuery()->getResult();

    $threadQb = $threadRepo->getResourcesByCourse($course, $session);
    foreach ($searchTerms as $term) {
        $threadQb->andWhere('resource.title LIKE :term')
            ->setParameter('term', '%' . $term . '%');
    }
    $threads = $threadQb->getQuery()->getResult();

    $postQb = $postRepo->getResourcesByCourse($course, $session);
    foreach ($searchTerms as $term) {
        $postQb->andWhere('resource.title LIKE :term OR resource.postText LIKE :term')
            ->setParameter('term', '%' . $term . '%');
    }
    $posts = $postQb->getQuery()->getResult();

    $search_results = [];
    foreach ($forums as $forum) {
        if ($forum->isVisible($course) && $forum->getForumCategory()->isVisible($course)) {
            $search_results[] = '<li class="mb-2"><a href="viewforum.php?'.api_get_cidreq().'&forum='.$forum->getIid().'" class="text-blue-500 hover:text-blue-600">'.htmlspecialchars($forum->getTitle()).'</a></li>';
        }
    }
    foreach ($threads as $thread) {
        if ($thread->isVisible($course)) {
            $search_results[] = '<li class="mb-2"><a href="viewthread.php?'.api_get_cidreq().'&thread='.$thread->getIid().'&forum='.$thread->getForum()->getIid().'" class="text-blue-500 hover:text-blue-600">'.htmlspecialchars($thread->getTitle()).'</a></li>';
        }
    }
    foreach ($posts as $post) {
        if ($post->isVisible($course)) {
            $search_results[] = '<li class="mb-2"><a href="viewthread.php?'.api_get_cidreq().'&thread='.$post->getThread()->getIid().'&forum='.$post->getForum()->getIid().'" class="text-blue-500 hover:text-blue-600">'.htmlspecialchars($post->getTitle()).'</a></li>';
        }
    }

    echo '<div class="text-lg font-semibold mb-4">'.count($search_results).' '.get_lang('Search results').'</div>';
    echo '<ol class="list-decimal ps-5">'.implode($search_results).'</ol>';
}

/**
 * Return the link to the forum search page.
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 *
 * @version April 2008, dokeos 1.8.5
 */
function search_link()
{
    $return = '';
    $origin = api_get_origin();
    if ('learnpath' != $origin) {
        $return = '<a href="forumsearch.php?'.api_get_cidreq().'&action=search"> ';
        $return .= Display::getMdiIcon('magnify-plus-outline	', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Search')).'</a>';

        if (!empty($_GET['search'])) {
            $return .= ': '.Security::remove_XSS($_GET['search']).' ';
            $url = api_get_self().'?';
            $url_parameter = [];
            foreach ($_GET as $key => $value) {
                if ('search' != $key) {
                    $url_parameter[] = Security::remove_XSS($key).'='.Security::remove_XSS($value);
                }
            }
            $url .= implode('&', $url_parameter);
            $return .= '<a href="'.$url.'">'.Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', '', ICON_SIZE_MEDIUM,  get_lang('Clean search results')).'</a>';
        }
    }

    return $return;
}

/**
 * This function adds an attachment file into a forum.
 *
 * @param string     $file_comment a comment about file
 * @param CForumPost $post         from forum_post table
 *
 * @return false|null
 */
function add_forum_attachment_file($file_comment, CForumPost $post)
{
    $request = Container::getRequest();
    if (false === $request->files->has('user_upload')) {
        return false;
    }

    $file = $request->files->get('user_upload');
    if (empty($file)) {
        return false;
    }

    $files = [];
    if ($file instanceof UploadedFile) {
        $files[] = $file;
    } else {
        $files = $file;
    }

    /** @var UploadedFile $attachment */
    foreach ($files as $file) {
        $valid = process_uploaded_file($file);

        if (!$valid) {
            continue;
        }

        // Try to add an extension to the file if it hasn't one.
        $new_file_name = add_ext_on_mime(
            stripslashes($file->getPathname()),
            $file->getType()
        );

        // User's file name
        $file_name = $file->getClientOriginalName();

        if (!filter_extension($new_file_name)) {
            Display::addFlash(
                Display::return_message(
                    get_lang('File upload failed: this file extension or file type is prohibited'),
                    'error'
                )
            );

            return;
        }

        $safe_file_comment = Database::escape_string($file_comment);

        $user = api_get_user_entity(api_get_user_id());
        $course = api_get_course_entity(api_get_course_int_id());
        $session = api_get_session_entity(api_get_session_id());

        $attachment = (new CForumAttachment())
            ->setCId(api_get_course_int_id())
            ->setComment($safe_file_comment)
            ->setFilename($file_name)
            ->setPath($file_name)
            ->setPost($post)
            ->setSize($file->getSize())
            ->setParent($post)
            ->addCourseLink(
                $course,
                $session
            );

        $repo = Container::getForumAttachmentRepository();
        $repo->create($attachment);
        $repo->addFile($attachment, $file);
        $repo->update($attachment);
    }
}

/**
 * This function edits an attachment file into a forum.
 *
 * @param string $file_comment a comment about file
 * @param int    $post_id
 * @param int    $id_attach    attachment file Id
 */
function edit_forum_attachment_file($file_comment, $post_id, $id_attach)
{
    throw new Exception('edit_forum_attachment_file');
    /*
    $_course = api_get_course_info();
    $table_forum_attachment = Database::get_course_table(TABLE_FORUM_ATTACHMENT);
    $course_id = api_get_course_int_id();

    $filesData = [];

    if (!is_array($_FILES['user_upload']['name'])) {
        $filesData[] = $_FILES['user_upload'];
    } else {
        $fileCount = count($_FILES['user_upload']['name']);
        $fileKeys = array_keys($_FILES['user_upload']);

        for ($i = 0; $i < $fileCount; $i++) {
            foreach ($fileKeys as $key) {
                $filesData[$i][$key] = $_FILES['user_upload'][$key][$i];
            }
        }
    }

    foreach ($filesData as $attachment) {
        if (empty($attachment['name'])) {
            continue;
        }

        $upload_ok = process_uploaded_file($attachment);

        if (!$upload_ok) {
            continue;
        }

        $course_dir = $_course['path'].'/upload/forum';
        $sys_course_path = api_get_path(SYS_COURSE_PATH);
        $updir = $sys_course_path.$course_dir;

        // Try to add an extension to the file if it hasn't one.
        $new_file_name = add_ext_on_mime(stripslashes($attachment['name']), $attachment['type']);
        // User's file name
        $file_name = $attachment['name'];

        if (!filter_extension($new_file_name)) {
            Display::addFlash(
                Display::return_message(
                    get_lang('File upload failed: this file extension or file type is prohibited'),
                    'error'
                )
            );
        } else {
            $new_file_name = uniqid('');
            $new_path = $updir.'/'.$new_file_name;
            $result = @move_uploaded_file($attachment['tmp_name'], $new_path);
            $safe_file_comment = Database::escape_string($file_comment);
            $safe_file_name = Database::escape_string($file_name);
            $safe_new_file_name = Database::escape_string($new_file_name);
            $safe_post_id = (int) $post_id;
            $safe_id_attach = (int) $id_attach;
            // Storing the attachments if any.
            if ($result) {
                $sql = "UPDATE $table_forum_attachment
                        SET
                            filename = '$safe_file_name',
                            comment = '$safe_file_comment',
                            path = '$safe_new_file_name',
                            post_id = '$safe_post_id',
                            size ='".$attachment['size']."'
                        WHERE c_id = $course_id AND id = '$safe_id_attach'";
                Database::query($sql);
                api_item_property_update(
                    $_course,
                    TOOL_FORUM_ATTACH,
                    $safe_id_attach,
                    'ForumAttachmentUpdated',
                    api_get_user_id()
                );
            }
        }
    }*/
}

/**
 * Show a list with all the attachments according to the post's id.
 *
 * @param int $postId
 *
 * @return array with the post info
 *
 * @author Julio Montoya
 *
 * @version avril 2008, dokeos 1.8.5
 */
function get_attachment($postId)
{
    $course_id = api_get_course_int_id();
    $postId = (int) $postId;

    if (empty($postId)) {
        return [];
    }

    return Database::getManager()->createQueryBuilder()
        ->select('a.iid AS iid', 'a.path AS path', 'a.filename AS filename', 'a.comment AS comment')
        ->from(CForumAttachment::class, 'a')
        ->where('a.cId = :courseId')
        ->andWhere('IDENTITY(a.post) = :postId')
        ->setParameter('courseId', (int) $course_id)
        ->setParameter('postId', $postId)
        ->getQuery()
        ->getArrayResult()[0] ?? [];
}

/**
 * Delete the all the attachments from the DB and the file according to the post's id or attach id(optional).
 *
 * @param int $postId
 * @param int $attachmentId
 *
 * @return bool
 */
function delete_attachment($postId, $attachmentId)
{
    $repo = Container::getForumPostRepository();
    $em = Database::getManager();
    /** @var CForumPost $post */
    $post = $repo->find($postId);
    if ($post) {
        $repoAttachment = Container::getForumAttachmentRepository();
        $attachment = $repoAttachment->find($attachmentId);
        if ($attachment) {
            $post->removeAttachment($attachment);
            $em->remove($attachment);
        }
        $repo->update($post);

        Display::addFlash(Display::return_message(get_lang('The attached file has been deleted'), 'confirmation'));
    }

    return true;
}

/**
 * This function gets all the forum information of the all the forum of the group.
 *
 * @param array $groupInfo the id of the group we need the fora of (see forum.forum_of_group)
 *
 * @return CForum[]
 *
 * @todo this is basically the same code as the get_forums function. Consider merging the two.
 */
function get_forums_of_group(CGroup $group)
{
    $course = api_get_course_entity();
    $session = api_get_session_entity();
    $repo = Container::getForumRepository();
    $qb = $repo->getResourcesByCourse($course, $session, $group);

    return $qb->getQuery()->getResult();
}

/**
 * This function stores which users have to be notified of which forums or threads.
 *
 * @param string $content    does the user want to be notified about a forum or about a thread
 * @param int    $id         the id of the forum or thread
 * @param bool   $addOnly
 * @param array  $userInfo
 * @param array  $courseInfo
 *
 * @return string language variable
 *
 * @author  Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @author  Julio Montoya
 *
 * @since   May 2008 v1.8.5
 */
function set_notification($content, $id, $addOnly = false, $userInfo = [], $courseInfo = [])
{
    $userInfo = empty($userInfo) ? api_get_user_info() : $userInfo;
    $courseInfo = empty($courseInfo) ? api_get_course_info() : $courseInfo;
    $id = (int) $id;

    if (empty($userInfo) || empty($courseInfo) || empty($id) || empty($content)) {
        return false;
    }

    $course_id = $courseInfo['real_id'];

    // Which database field do we have to store the id in?
    $field = 'thread_id';
    if ('forum' === $content) {
        $field = 'forum_id';
    }

    $userId = $userInfo['user_id'];

    // First we check if the notification is already set for this.
    // $field is one of two fixed names, mapped to the entity property.
    $property = 'forum_id' === $field ? 'forumId' : 'threadId';
    $total = (int) Database::getManager()->createQueryBuilder()
        ->select('COUNT(n.iid)')
        ->from(CForumNotification::class, 'n')
        ->where('n.cId = :courseId')
        ->andWhere('n.'.$property.' = :id')
        ->andWhere('n.userId = :userId')
        ->setParameter('courseId', (int) $course_id)
        ->setParameter('id', $id)
        ->setParameter('userId', (int) $userId)
        ->getQuery()
        ->getSingleScalarResult();

    // If the user did not indicate that (s)he wanted to be notified already
    // then we store the notification request (to prevent double notification requests).
    if ($total <= 0) {
        $notification = new CForumNotification();
        $notification
            ->setCId($course_id)
            ->setUserId($userId)
        ;

        if ('forum' === $content) {
            $notification->setForumId($id);
        } else {
            $notification->setThreadId($id);
        }

        $em = Database::getManager();
        $em->persist($notification);
        $em->flush();

        Session::erase('forum_notification');
        getNotificationsPerUser(0, true);

        return get_lang('You will be notified of new posts by e-mail.');
    } else {
        if (!$addOnly) {
            Database::getManager()->createQueryBuilder()
                ->delete(CForumNotification::class, 'n')
                ->where('n.cId = :courseId')
                ->andWhere('n.'.$property.' = :id')
                ->andWhere('n.userId = :userId')
                ->setParameter('courseId', (int) $course_id)
                ->setParameter('id', $id)
                ->setParameter('userId', (int) $userId)
                ->getQuery()
                ->execute();
            Session::erase('forum_notification');
            getNotificationsPerUser(0, true);

            return get_lang('You will no longer be notified of new posts by email');
        }
    }
}

/**
 * This function retrieves all the email adresses of the users who wanted to be notified
 * about a new post in a certain forum or thread.
 *
 * @param string $content does the user want to be notified about a forum or about a thread
 * @param int $id      the id of the forum or thread
 *
 * @return array returns
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @author Julio Montoya
 *
 * @version May 2008, dokeos 1.8.5
 *
 * @since May 2008, dokeos 1.8.5
 */
function get_notifications(string $content, int $id): array
{
    // Database table definition
    $course_id = api_get_course_int_id();

    // Which entity property contains the notification?
    $property = 'forum' === $content ? 'forumId' : 'threadId';

    $rows = Database::getManager()->createQueryBuilder()
        ->select('user.id AS user_id', 'user.email AS email')
        ->from(User::class, 'user')
        ->from(CForumNotification::class, 'notification')
        ->where('notification.cId = :courseId')
        ->andWhere('user.active = 1')
        ->andWhere('user.id = notification.userId')
        ->andWhere('notification.'.$property.' = :id')
        ->setParameter('courseId', (int) $course_id)
        ->setParameter('id', $id)
        ->getQuery()
        ->getArrayResult();
    $return = [];

    foreach ($rows as $row) {
        $return['user'.$row['user_id']] = ['email' => $row['email'], 'user_id' => $row['user_id']];
    }

    return $return;
}

/**
 * Get all the users who need to receive a notification of a new post (those subscribed to
 * the forum or the thread).
 *
 * @param int $post_id the id of the post
 *
 * @return false|null
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 *
 * @version May 2008, dokeos 1.8.5
 *
 * @since May 2008, dokeos 1.8.5
 */
function send_notifications(CForum $forum, CForumThread $thread, $post_id = 0)
{
    if (!$forum) {
        return false;
    }

    // Users who subscribed to the forum
    $users_to_be_notified_by_forum = get_notifications('forum', $forum->getIid());

    // User who subscribed to the thread
    $users_to_be_notified_by_thread = [];
    if ($thread) {
        $users_to_be_notified_by_thread = get_notifications('thread', $thread->getIid());
    }

    $postInfo = null;
    if (!empty($post_id)) {
        $postInfo = Container::getForumPostRepository()->find($post_id);
    }

    // Merging the two
    $users_to_be_notified = array_merge($users_to_be_notified_by_forum, $users_to_be_notified_by_thread);

    $subscribe = (int) api_get_course_setting('subscribe_users_to_forum_notifications');
    if (1 === $subscribe) {
        $sessionId = api_get_session_id();
        if (!empty($sessionId)) {
            $users_to_be_notified = SessionManager::getAllUserIdsInSession($sessionId);
        } else {
            $users_to_be_notified = CourseManager::get_user_list_from_course_code(api_get_course_id());
        }
    }

    if (is_array($users_to_be_notified)) {
        foreach ($users_to_be_notified as $value) {
            $userInfo = api_get_user_info($value['user_id']);
            send_mail($userInfo, $forum, $thread, $postInfo);
        }
    }
}

/**
 * Get all the notification subscriptions of the user
 * = which forums and which threads does the user wants to be informed of when a new
 * post is added to this thread.
 *
 * @param int  $user_id the user_id of a user (default = 0 => the current user)
 * @param bool $force   force get the notification subscriptions (even if the information is already in the session
 *
 * @return array
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 *
 * @version May 2008, dokeos 1.8.5
 *
 * @since May 2008, dokeos 1.8.5
 */
function getNotificationsPerUser($user_id = 0, $force = false, $course_id = 0)
{
    // Database table definition
    $course_id = empty($course_id) ? api_get_course_int_id() : (int) $course_id;
    if (empty($course_id) || -1 == $course_id) {
        return null;
    }

    $user_id = empty($user_id) ? api_get_user_id() : (int) $user_id;

    if (!isset($_SESSION['forum_notification']) ||
        $_SESSION['forum_notification']['course'] != $course_id ||
        true == $force
    ) {
        $_SESSION['forum_notification']['course'] = $course_id;
        $rows = Database::getManager()->createQueryBuilder()
            ->select('n.forumId AS forum_id', 'n.threadId AS thread_id')
            ->from(CForumNotification::class, 'n')
            ->where('n.cId = :courseId')
            ->andWhere('n.userId = :userId')
            ->setParameter('courseId', (int) $course_id)
            ->setParameter('userId', (int) $user_id)
            ->getQuery()
            ->getArrayResult();
        foreach ($rows as $row) {
            if (!empty($row['forum_id'])) {
                $_SESSION['forum_notification']['forum'][] = $row['forum_id'];
            }
            if (!empty($row['thread_id'])) {
                $_SESSION['forum_notification']['thread'][] = $row['thread_id'];
            }
        }
    }
}

/**
 * This function counts the number of post inside a thread.
 *
 * @param int $thread_id
 *
 * @return int the number of post inside a thread
 *
 * @author Jhon Hinojosa <jhon.hinojosa@dokeos.com>,
 *
 * @version octubre 2008, dokeos 1.8
 */
function count_number_of_post_in_thread($thread_id)
{
    $course_id = api_get_course_int_id();
    if (empty($course_id)) {
        return 0;
    }

    return (int) Database::getManager()->createQueryBuilder()
        ->select('COUNT(p.iid)')
        ->from(CForumPost::class, 'p')
        ->where('IDENTITY(p.thread) = :threadId')
        ->setParameter('threadId', (int) $thread_id)
        ->getQuery()
        ->getSingleScalarResult();
}

/**
 * This function counts the number of post inside a thread user.
 *
 * @param int $thread_id
 * @param int $user_id
 *
 * @return int the number of post inside a thread user
 */
function count_number_of_post_for_user_thread($thread_id, $user_id)
{
    return (int) Database::getManager()->createQueryBuilder()
        ->select('COUNT(p.iid)')
        ->from(CForumPost::class, 'p')
        ->where('IDENTITY(p.thread) = :threadId')
        ->andWhere('IDENTITY(p.user) = :userId')
        ->andWhere('p.visible = true')
        ->setParameter('threadId', (int) $thread_id)
        ->setParameter('userId', (int) $user_id)
        ->getQuery()
        ->getSingleScalarResult();
}

/**
 * This function retrieves information of statistical.
 *
 * @param int $thread_id
 * @param int $user_id
 * @param int $course_id
 *
 * @return array the information of statistical
 *
 * @author Jhon Hinojosa <jhon.hinojosa@dokeos.com>,
 *
 * @version oct 2008, dokeos 1.8
 */
function get_statistical_information($thread_id, $user_id, $course_id)
{
    $result = [];
    $courseInfo = api_get_course_info_by_id($course_id);
    $result['user_course'] = CourseManager::get_users_count_in_course($courseInfo['code']);
    $result['post'] = count_number_of_post_in_thread($thread_id);
    $result['user_post'] = count_number_of_post_for_user_thread($thread_id, $user_id);

    return $result;
}

/**
 * This function return the posts inside a thread from a given user.
 *
 * @param int $thread_id
 * @param int $user_id
 *
 * @return array posts inside a thread
 *
 * @author  Jhon Hinojosa <jhon.hinojosa@dokeos.com>,
 *
 * @version oct 2008, dokeos 1.8
 */
/**
 * Every column of a forum post plus the main columns of its author (the legacy rows were SELECT * of both tables).
 */
function forumPostRowsQueryBuilder(): Doctrine\ORM\QueryBuilder
{
    return Database::getManager()->createQueryBuilder()
        ->select(
            'posts.iid AS iid',
            'posts.title AS title',
            'posts.postText AS post_text',
            'posts.postDate AS post_date',
            'posts.postNotification AS post_notification',
            'posts.visible AS visible',
            'posts.status AS status',
            'IDENTITY(posts.thread) AS thread_id',
            'IDENTITY(posts.forum) AS forum_id',
            'IDENTITY(posts.user) AS poster_id',
            'IDENTITY(posts.postParent) AS post_parent_id',
            'IDENTITY(posts.resourceNode) AS resource_node_id',
            'users.id AS user_id',
            'users.username AS username',
            'users.firstname AS firstname',
            'users.lastname AS lastname',
            'users.email AS email'
        )
        ->from(CForumPost::class, 'posts')
        ->leftJoin('posts.user', 'users');
}

/**
 * The raw rows carried the datetime as a string and the booleans as 0/1.
 */
function toLegacyForumPostRow(array $row): array
{
    $row['post_date'] = $row['post_date']->format('Y-m-d H:i:s');
    $row['visible'] = (int) $row['visible'];
    $row['post_notification'] = null === $row['post_notification'] ? null : (int) $row['post_notification'];

    return $row;
}

function get_thread_user_post(Course $course, $thread_id, $user_id)
{
    $thread_id = (int) $thread_id;
    $user_id = (int) $user_id;
    $course_id = $course->getId();

    if (empty($course_id)) {
        $course_id = api_get_course_int_id();
    }
    $post_list = [];
    foreach (forumPostRowsQueryBuilder()
        ->where('IDENTITY(posts.thread) = :threadId')
        ->andWhere('IDENTITY(posts.user) = :userId')
        ->setParameter('threadId', $thread_id)
        ->setParameter('userId', $user_id)
        ->orderBy('posts.iid', 'ASC')
        ->getQuery()
        ->getArrayResult() as $row) {
        $row = toLegacyForumPostRow($row);
        $row['status'] = '1';
        $post_list[] = $row;
        $replyRows = forumPostRowsQueryBuilder()
            ->where('IDENTITY(posts.thread) = :threadId')
            ->andWhere('IDENTITY(posts.postParent) = :parentId')
            ->setParameter('threadId', $thread_id)
            ->setParameter('parentId', (int) $row['iid'])
            ->orderBy('posts.iid', 'ASC')
            ->getQuery()
            ->getArrayResult();
        foreach ($replyRows as $row2) {
            $row2 = toLegacyForumPostRow($row2);
            $row2['status'] = '0';
            $post_list[] = $row2;
        }
    }

    return $post_list;
}

/**
 * This function get the name of an thread by id.
 *
 * @param int $threadId
 *
 * @return string
 *
 * @author Christian Fasanando
 * @author Julio Montoya <gugli100@gmail.com> Adding security
 */
function get_name_thread_by_id(int $threadId): string
{
    $row = Database::getManager()->createQueryBuilder()
        ->select('t.title AS title')
        ->from(CForumThread::class, 't')
        ->where('t.iid = :threadId')
        ->setParameter('threadId', $threadId)
        ->getQuery()
        ->getArrayResult()[0] ?? false;

    return $row['title'];
}

/**
 * This function gets all the post written by an user.
 *
 * @param int $user_id
 * @param int $courseId
 *
 * @return string
 */
function get_all_post_from_user(int $user_id, int $courseId): string
{
    $j = 0;
    $forums = get_forums($courseId);
    krsort($forums);
    $forum_results = '';

    foreach ($forums as $forum) {
        $forumId = $forum->getIid();

        /*if (0 == $forum['visibility']) {
            continue;
        }*/
        if ($j <= 4) {
            $threads = get_threads($forumId);

            if ($threads) {
                $i = 0;
                $hand_forums = '';
                $post_counter = 0;
                foreach ($threads as $thread) {
                    /*if (0 == $thread['visibility']) {
                        continue;
                    }*/
                    if ($i <= 4) {
                        $post_list = get_thread_user_post_limit(
                            $courseId,
                            $thread->getIid(),
                            $user_id,
                            1
                        );
                        $post_counter = count($post_list);
                        if (is_array($post_list) && count($post_list) > 0) {
                            $hand_forums .= '<div id="social-thread">';
                            $hand_forums .= Display::getMdiIcon('format-quote-open', 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Thread'));
                            $hand_forums .= '&nbsp;'.Security::remove_XSS($thread->getTitle(), STUDENT);
                            $hand_forums .= '</div>';

                            foreach ($post_list as $posts) {
                                $hand_forums .= '<div id="social-post">';
                                $hand_forums .= '<strong>'.Security::remove_XSS($posts['post_title'], STUDENT).'</strong>';
                                $hand_forums .= '<br / >';
                                $hand_forums .= Security::remove_XSS($posts['post_text'], STUDENT);
                                $hand_forums .= '</div>';
                                $hand_forums .= '<br / >';
                            }
                        }
                    }
                    $i++;
                }
                $forum_results .= '<div id="social-forum">';
                $forum_results .= '<div class="clear"></div><br />';
                $forum_results .= '<div id="social-forum-title">'.
                    Display::getMdiIcon('comment-quote', 'ch-tool-icon', '', ICON_SIZE_SMALL, get_lang('Forum')).'&nbsp;'.Security::remove_XSS($forum->getTitle(), STUDENT).
                    '<div style="float:inline-end;margin-top:-35px">
                        <a href="../forum/viewforum.php?'.api_get_cidreq_params($courseId).'&forum='.$forum->getIid().' " >'.
                    get_lang('See forum').'
                        </a>
                     </div></div>';
                $forum_results .= '<br / >';
                if ($post_counter > 0) {
                    $forum_results .= $hand_forums;
                }
                $forum_results .= '</div>';
            }
            $j++;
        }
    }

    return $forum_results;
}

/**
 * @param int $thread_id
 * @param int $user_id
 * @param int $limit
 *
 * @return array
 */
function get_thread_user_post_limit($courseId, $thread_id, $user_id, $limit = 10)
{
    $courseId = (int) $courseId;
    $limit = (int) $limit;

    $rows = forumPostRowsQueryBuilder()
        ->where('IDENTITY(posts.thread) = :threadId')
        ->andWhere('IDENTITY(posts.user) = :userId')
        ->setParameter('threadId', (int) $thread_id)
        ->setParameter('userId', (int) $user_id)
        ->orderBy('posts.iid', 'DESC')
        ->setMaxResults($limit)
        ->getQuery()
        ->getArrayResult();
    $post_list = [];
    foreach ($rows as $row) {
        $row = toLegacyForumPostRow($row);
        $row['status'] = '1';
        $post_list[] = $row;
    }

    return $post_list;
}

/**
 * @param string $userId
 * @param array  $courseInfo
 * @param int    $sessionId
 *
 * @return array
 */
function getForumCreatedByUser($userId, $courseInfo, $sessionId)
{
    if (empty($userId) || empty($courseInfo)) {
        return [];
    }

    $courseId = $courseInfo['real_id'];

    $repo = Container::getForumRepository();

    $courseEntity = api_get_course_entity($courseId);
    $sessionEntity = api_get_session_entity($sessionId);

    $qb = $repo->getResourcesByCourse($courseEntity, $sessionEntity);

    $qb->andWhere('node.creator = :creator');
    $qb->setParameter('creator', $userId);
    $items = $qb->getQuery()->getResult();

    $forumList = [];
    if (!empty($items)) {
        /** @var CForum $forum */
        foreach ($items as $forum) {
            $forumList[] = [
                $forum->getTitle(),
                api_get_local_time($forum->getResourceNode()->getCreatedAt()),
                api_get_local_time($forum->getResourceNode()->getUpdatedAt()),
            ];
        }
    }

    return $forumList;
}

/**
 * This function builds an array of all the posts in a given thread
 * where the key of the array is the post_id
 * It also adds an element children to the array which itself is an array
 * that contains all the id's of the first-level children.
 *
 * @return array containing all the information on the posts of a thread
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function calculate_children($rows)
{
    $sorted_rows = [0 => []];
    if (!empty($rows)) {
        foreach ($rows as $row) {
            $rows_with_children[$row['post_id']] = $row;
            $rows_with_children[$row['post_parent_id']]['children'][] = $row['post_id'];
        }

        $rows = $rows_with_children;
        forumRecursiveSort($rows, $sorted_rows);
        unset($sorted_rows[0]);
    }

    return $sorted_rows;
}

/**
 * @param $rows
 * @param $threads
 * @param int $seed
 * @param int $indent
 */
function forumRecursiveSort($rows, &$threads, $seed = 0, $indent = 0)
{
    if ($seed > 0) {
        $threads[$rows[$seed]['post_id']] = $rows[$seed];
        $threads[$rows[$seed]['post_id']]['indent_cnt'] = $indent;
        $indent++;
    }

    if (isset($rows[$seed]['children'])) {
        foreach ($rows[$seed]['children'] as $child) {
            forumRecursiveSort($rows, $threads, $child, $indent);
        }
    }
}

/**
 * Update forum attachment data, used to update comment and post ID.
 *
 * @param array $array    (field => value) to update forum attachment row
 * @param int   $id       ID to find row to update
 * @param int   $courseId course ID to find row to update
 *
 * @return int number of affected rows
 */
function editAttachedFile($array, $id, $courseId = null)
{
    // Init variables
    $setString = '';
    $id = (int) $id;
    $courseId = (int) $courseId;
    if (empty($courseId)) {
        // $courseId can be null, use api method
        $courseId = api_get_course_int_id();
    }
    /*
     * Check if Attachment ID and Course ID are greater than zero
     * and array of field values is not empty
     */
    if ($id > 0 && $courseId > 0 && !empty($array) && is_array($array)) {
        // Only these columns can be updated (the callers pass 'comment' and 'post_id').
        $allowed = [
            'comment' => 'a.comment',
            'post_id' => 'a.post',
            'filename' => 'a.filename',
            'path' => 'a.path',
            'size' => 'a.size',
        ];
        $qb = Database::getManager()->createQueryBuilder()
            ->update(CForumAttachment::class, 'a')
            ->where('a.cId = :courseId')
            ->andWhere('a.iid = :attachmentId')
            ->setParameter('courseId', $courseId)
            ->setParameter('attachmentId', $id);
        $i = 0;
        foreach ($array as $key => $value) {
            if (!isset($allowed[$key])) {
                continue;
            }
            $qb->set($allowed[$key], ':value'.$i)->setParameter('value'.$i, 'post_id' === $key ? (int) $value : $value);
            $i++;
        }
        if (0 === $i) {
            return 0;
        }
        $result = $qb->getQuery()->execute();
        if (false !== $result) {
            $affectedRows = (int) $result;
            if ($affectedRows > 0) {
                /*
                 * If exist in $_SESSION variable, then delete them from it
                 * because they would be deprecated
                 */
                if (!empty($_SESSION['forum']['upload_file'][$courseId][$id])) {
                    unset($_SESSION['forum']['upload_file'][$courseId][$id]);
                }
            }

            return $affectedRows;
        }
    }

    return 0;
}

/**
 * Return a table where the attachments will be set.
 *
 * @param int $postId Forum Post ID
 *
 * @return string The Forum Attachments Ajax Table
 */
function getAttachmentsAjaxTable($postId = 0)
{
    $postId = (int) $postId;
    $courseId = api_get_course_int_id();
    $attachIds = getAttachmentIdsByPostId($postId, $courseId);
    $fileDataContent = '';
    // Update comment to show if form did not pass validation
    if (!empty($_REQUEST['file_ids']) && is_array($_REQUEST['file_ids'])) {
        // 'file_ids is the name from forum attachment ajax form
        foreach ($_REQUEST['file_ids'] as $key => $attachId) {
            if (!empty($_SESSION['forum']['upload_file'][$courseId][$attachId]) &&
                is_array($_SESSION['forum']['upload_file'][$courseId][$attachId])
            ) {
                // If exist forum attachment then update into $_SESSION data
                $_SESSION['forum']['upload_file'][$courseId][$attachId]['comment'] = $_POST['file_comments'][$key];
            }
        }
    }

    // Get data to fill into attachment files table
    if (!empty($_SESSION['forum']['upload_file'][$courseId]) &&
        is_array($_SESSION['forum']['upload_file'][$courseId])
    ) {
        $uploadedFiles = $_SESSION['forum']['upload_file'][$courseId];
        foreach ($uploadedFiles as $k => $uploadedFile) {
            if (!empty($uploadedFile) && in_array($uploadedFile['id'], $attachIds)) {
                // Buil html table including an input with attachmentID
                $fileDataContent .= '<tr id="'.$uploadedFile['id'].'" ><td>'.$uploadedFile['name'].'</td><td>'.$uploadedFile['size'].'</td><td>&nbsp;'.$uploadedFile['result'].
                    ' </td><td> <input style="width:90%;" type="text" value="'.$uploadedFile['comment'].'" name="file_comments[]"> </td><td>'.
                    $uploadedFile['delete'].'</td>'.
                    '<input type="hidden" value="'.$uploadedFile['id'].'" name="file_ids[]">'.'</tr>';
            } else {
                /*
                 * If attachment data is empty, then delete it from $_SESSION
                 * because could generate and empty row into html table
                 */
                unset($_SESSION['forum']['upload_file'][$courseId][$k]);
            }
        }
    }
    $style = empty($fileDataContent) ? 'display: none;' : '';
    // Forum attachment Ajax table
    return '
    <div class="control-group " style="'.$style.'">
        <label class="control-label">'.get_lang('Attachments list').'</label>
        <div class="controls">
            <table id="attachmentFileList" class="files data_table span10">
                <tr>
                    <th>'.get_lang('Filename').'</th>
                    <th>'.get_lang('Size').'</th>
                    <th>'.get_lang('Status').'</th>
                    <th>'.get_lang('Comment').'</th>
                    <th>'.get_lang('Delete').'</th>
                </tr>
                '.$fileDataContent.'
            </table>
        </div>
    </div>';
}

/**
 * Return an array of prepared attachment data to build forum attachment table
 * Also, save this array into $_SESSION to do available the attachment data.
 *
 * @param int $forumId
 * @param int $threadId
 * @param int $postId
 * @param int $attachId
 * @param int $courseId
 *
 * @return array
 */
function getAttachedFiles(
    $forumId,
    $threadId,
    $postId = 0,
    $attachId = 0,
    $courseId = 0
) {
    $forumId = (int) $forumId;
    $courseId = (int) $courseId;
    $attachId = (int) $attachId;
    $postId = (int) $postId;
    $threadId = (int) $threadId;

    if (empty($courseId)) {
        // $courseId can be null, use api method
        $courseId = api_get_course_int_id();
    }
    if (empty($forumId)) {
        if (!empty($_REQUEST['forum'])) {
            $forumId = (int) $_REQUEST['forum'];
        } else {
            // if forum ID is empty, cannot generate delete url

            return [];
        }
    }
    // Check if exist at least one of them to filter forum attachment select query
    if (empty($postId) && empty($attachId)) {
        return [];
    }

    if (empty($postId)) {
        $filter = "AND iid = $attachId";
    } elseif (empty($attachId)) {
        $filter = "AND post_id = $postId";
    } else {
        $filter = "AND post_id = $postId AND iid = $attachId";
    }
    $forumAttachmentTable = Database::get_course_table(TABLE_FORUM_ATTACHMENT);
    $sql = "SELECT iid
            FROM $forumAttachmentTable
            WHERE c_id = $courseId $filter";
    $result = Database::query($sql);
    $json = [];
    if (Database::num_rows($result) > 0) {
        $repo = Container::getForumAttachmentRepository();
        while ($row = Database::fetch_assoc($result)) {
            /** @var CForumAttachment $attachment */
            $attachment = $repo->find($row['iid']);
            $downloadUrl = $repo->getResourceFileDownloadUrl($attachment);

            // name contains an URL to download attachment file and its filename
            $json['name'] = Display::url(
                api_htmlentities($attachment->getFilename()),
                $downloadUrl,
                ['target' => '_blank', 'class' => 'attachFilename']
            );
            $json['id'] = $row['iid'];
            $json['comment'] = $attachment->getComment();
            // Format file size
            $json['size'] = \Chamilo\CoreBundle\Helpers\FormatHelper::formatFileSize($attachment->getSize());
            // Check if $row is consistent
            if ($attachment) {
                // Set result as success and bring delete URL
                $json['result'] = Display::getMdiIcon('check-circle', 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Uploaded.'));
                $url = api_get_path(WEB_CODE_PATH).'forum/viewthread.php?'.api_get_cidreq().'&action=delete_attach&forum='.$forumId.'&thread='.$threadId.'&id_attach='.$row['iid'];
                $json['delete'] = Display::url(
                    Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete')),
                    $url,
                    ['class' => 'deleteLink']
                );
            } else {
                // If not, set an exclamation result
                $json['result'] = Display::getMdiIcon('close-circle', 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Error'));
            }
            // Store array data into $_SESSION
            $_SESSION['forum']['upload_file'][$courseId][$json['id']] = $json;
        }
    }

    return $json;
}

/**
 * Clear forum attachment data stored in $_SESSION,
 * If is not defined post, it will clear all forum attachment data from course.
 *
 * @param int $postId   -1 : Clear all attachments from course stored in $_SESSION
 *                      0 : Clear attachments from course, except from temporal post "0"
 *                      but without delete them from file system and database
 *                      Other values : Clear attachments from course except specified post
 *                      and delete them from file system and database
 * @param int $courseId : Course ID, if it is null, will use api_get_course_int_id()
 *
 * @return array
 */
function clearAttachedFiles($postId = 0, $courseId = 0)
{
    // Init variables
    $courseId = (int) $courseId;
    $postId = (int) $postId;
    $array = [];
    if (empty($courseId)) {
        // $courseId can be null, use api method
        $courseId = api_get_course_int_id();
    }
    if (-1 === $postId) {
        // If post ID is -1 then delete course's attachment data from $_SESSION
        if (!empty($_SESSION['forum']['upload_file'][$courseId])) {
            $array = array_keys($_SESSION['forum']['upload_file'][$courseId]);
            unset($_SESSION['forum']['upload_file'][$courseId]);
        }
    } else {
        $attachIds = getAttachmentIdsByPostId($postId, $courseId);
        if (!empty($_SESSION['forum']['upload_file'][$courseId]) &&
            is_array($_SESSION['forum']['upload_file'][$courseId])) {
            foreach ($_SESSION['forum']['upload_file'][$courseId] as $attachId => $attach) {
                if (!in_array($attachId, $attachIds)) {
                    // If attach ID is not into specified post, delete attachment
                    // Save deleted attachment ID
                    $array[] = $attachId;
                    if (0 !== $postId) {
                        // Post 0 is temporal, delete them from file system and DB
                        delete_attachment(0, $attachId);
                    }
                    // Delete attachment data from $_SESSION
                    unset($_SESSION['forum']['upload_file'][$courseId][$attachId]);
                }
            }
        }
    }

    return $array;
}

/**
 * Returns an array of forum attachment ids into a course and forum post.
 *
 * @param int $postId
 * @param int $courseId
 *
 * @return array
 */
function getAttachmentIdsByPostId($postId, $courseId = 0)
{
    $array = [];
    $courseId = (int) $courseId;
    $postId = (int) $postId;
    if (empty($courseId)) {
        // $courseId can be null, use api method
        $courseId = api_get_course_int_id();
    }
    if ($courseId > 0) {
        $rows = Database::getManager()->createQueryBuilder()
            ->select('a.iid AS iid')
            ->from(CForumAttachment::class, 'a')
            ->where('a.cId = :courseId')
            ->andWhere('IDENTITY(a.post) = :postId')
            ->setParameter('courseId', $courseId)
            ->setParameter('postId', $postId)
            ->getQuery()
            ->getArrayResult();
        foreach ($rows as $row) {
            $array[] = $row['iid'];
        }
    }

    return $array;
}

function getPostStatus(CForum $forum, array $row, bool $addWrapper = true): string
{
    $statusIcon = '';
    if ($forum->isModerated()) {
        if ($addWrapper) {
            $statusIcon = '<br /><br /><span id="status_post_'.$row['iid'].'">';
        }
        $row['status'] = empty($row['status']) ? 2 : $row['status'];

        $addUrl = false;
        $showStatus = false;
        if (api_is_allowed_to_edit(false, true)) {
            $addUrl = true;
        } else {
            if ($row['user_id'] == api_get_user_id()) {
                $showStatus = true;
            }
        }

        $label = '';
        $icon = '';
        $buttonType = '';
        switch ($row['status']) {
            case CForumPost::STATUS_VALIDATED:
                $label = get_lang('Validated');
                $icon = 'check-circle';
                $buttonType = 'success';

                break;
            case CForumPost::STATUS_WAITING_MODERATION:
                $label = get_lang('Waiting for moderation');
                $icon = 'alert';
                $buttonType = 'warning';

                break;
            case CForumPost::STATUS_REJECTED:
                $label = get_lang('Rejected');
                $icon = 'minus-circle';
                $buttonType = 'danger';

                break;
        }

        if ($addUrl) {
            $statusIcon .= Display::toolbarButton(
                $label.'&nbsp;',
                'javascript:void(0)',
                $icon,
                $buttonType,
                ['class' => 'change_post_status']
            );
        } else {
            if ($showStatus) {
                $statusIcon .= Display::label(
                    Display::getMdiIcon($icon).$label,
                    $buttonType
                );
            }
        }

        if ($addWrapper) {
            $statusIcon .= '</span>';
        }
    }

    return $statusIcon;
}

/**
 * @param CForum $forum
 * @param int    $threadId
 * @param int    $status
 */
function getCountPostsWithStatus($status, $forum, $threadId = null)
{
    $em = Database::getManager();
    $criteria = Criteria::create();
    $criteria
        ->where(Criteria::expr()->eq('status', $status))
        ->andWhere(Criteria::expr()->eq('forum', $forum))
        ->andWhere(Criteria::expr()->eq('visible', 1))
    ;

    if (!empty($threadId)) {
        $criteria->andWhere(Criteria::expr()->eq('thread', $threadId));
    }

    $qb = $em->getRepository(CForumPost::class)->createQueryBuilder('p');
    $qb->select('count(p.iid)')
        ->addCriteria($criteria);

    return $qb->getQuery()->getSingleScalarResult();
}

/**
 * @param CForum     $forum
 * @param CForumPost $post
 *
 * @return bool
 */
/**
 * Student editability guard that accepts either an entity or the array shape used in views.
 *
 * @param CForum              $forum
 * @param CForumPost|array    $post
 */
function postIsEditableByStudent(CForum $forum, $post): bool
{
    // Normalize $post to an entity
    /** @var CForumPost|null $entity */
    $entity = null;

    if ($post instanceof CForumPost) {
        $entity = $post;
    } elseif (is_array($post)) {
        // Prefer the embedded entity from getPosts()
        if (isset($post['entity']) && $post['entity'] instanceof CForumPost) {
            $entity = $post['entity'];
        } else {
            // Fallback: fetch by id if present in the array
            $postId = $post['post_id'] ?? $post['iid'] ?? null;
            if ($postId) {
                $em = Database::getManager();
                $entity = $em->getRepository(CForumPost::class)->find($postId);
            }
        }
    }

    // If we still don't have a valid entity, be conservative: deny student edit
    if (!$entity instanceof CForumPost) {
        return false;
    }

    // Admins/teachers can always edit
    if (api_is_platform_admin() || api_is_allowed_to_edit()) {
        return true;
    }

    $isModerated = (bool) $forum->isModerated();
    $userId      = (int) api_get_user_id();
    $ownerId     = $entity->getUser() ? (int) $entity->getUser()->getId() : 0;
    $isOwner     = ($ownerId === $userId);
    $status      = $entity->getStatus();

    // Unmoderated forum: student can edit only own posts
    if (!$isModerated) {
        return $isOwner;
    }

    // editable by owner when status is NULL or WAITING or REJECTED.
    if ($isOwner) {
        if ($status === null) {
            return true;
        }

        return in_array(
            $status,
            [
                CForumPost::STATUS_WAITING_MODERATION,
                CForumPost::STATUS_REJECTED,
            ],
            true
        );
    }

    // Not owner and not a teacher/admin
    return false;
}


/**
 * @return bool
 */
function savePostRevision(CForumPost $post)
{
    $userId = api_get_user_id();

    if ($post->getUser()->getId() != $userId) {
        return false;
    }

    $status = (int) !postNeedsRevision($post);
    $extraFieldValue = new ExtraFieldValue('forum_post');
    $params = [
        'item_id' => $post->getIid(),
        'extra_ask_for_revision' => ['extra_ask_for_revision' => $status],
    ];
    if (empty($status)) {
        unset($params['extra_ask_for_revision']);
    }
    $extraFieldValue->saveFieldValues(
        $params,
        true,
        false,
        ['ask_for_revision']
    );
}

/**
 * @param int $postId
 *
 * @return string
 */
function getPostRevision($postId)
{
    $extraFieldValue = new ExtraFieldValue('forum_post');
    $value = $extraFieldValue->get_values_by_handler_and_field_variable(
        $postId,
        'revision_language'
    );
    $revision = '';
    if ($value && isset($value['value'])) {
        $revision = $value['value'];
    }

    return $revision;
}

function postNeedsRevision(CForumPost $post): bool
{
    $postId = $post->getIid();
    $extraFieldValue = new ExtraFieldValue('forum_post');
    $value = $extraFieldValue->get_values_by_handler_and_field_variable(
        $postId,
        'ask_for_revision'
    );
    $hasRevision = false;
    if ($value && isset($value['value'])) {
        return 1 == $value['value'];
    }

    return $hasRevision;
}

/**
 * Generates an HTML button to ask for a review
 */
function getAskRevisionButton(CForumPost $post, CForumThread $threadInfo): string
{
    if ('false' === api_get_setting('forum.allow_forum_post_revisions')) {
        return '';
    }

    $postId = $post->getIid();

    $status = 'btn--plain';
    if (postNeedsRevision($post)) {
        $status = 'btn--success';
    }

    return Display::url(
        get_lang('Ask for a revision'),
        api_get_path(WEB_CODE_PATH).'forum/viewthread.php?'.
        api_get_cidreq().'&action=ask_revision&post_id='.$postId.'&forum='.$threadInfo->getForum()->getIid().'&thread='.$threadInfo->getIid(),
        ['class' => "btn $status", 'title' => get_lang('Ask for a revision')]
    );
}

/**
 * Generates an HTML button to give a review
 */
function getGiveRevisionButton(int $postId, CForumThread $threadInfo): string
{
    return Display::toolbarButton(
        get_lang('Give revision'),
        api_get_path(WEB_CODE_PATH).'forum/reply.php?'.api_get_cidreq().'&'.http_build_query(
            [
                'forum' => $threadInfo->getForum()->getIid(),
                'thread' => $threadInfo->getIid(),
                'post' => $postId,
                'action' => 'replymessage',
                'give_revision' => 1,
            ]
        ),
        'reply',
        'primary',
        ['id' => "reply-to-post-{$postId}"]
    );
}

/**
 * Generates an HTML button to report a post as offensive
 */
function getReportButton(int $postId, CForumThread $threadInfo): string
{
    return Display::url(
        Display::getMdiIcon('flag'),
        api_get_path(WEB_CODE_PATH).'forum/viewthread.php?'.
        api_get_cidreq().'&action=report&post_id='.$postId.
        '&forum='.$threadInfo->getForum()->getIid().'&thread='.$threadInfo->getIid(),
        ['class' => 'btn btn--danger', 'title' => get_lang('Report')]
    );
}

/**
 * @return bool
 */
function reportAvailable()
{
    $extraFieldValue = new ExtraFieldValue('course');
    $value = $extraFieldValue->get_values_by_handler_and_field_variable(
        api_get_course_int_id(),
        'allow_forum_report_button'
    );
    $allowReport = false;
    if ($value && isset($value['value']) && 1 == $value['value']) {
        $allowReport = true;
    }

    return $allowReport;
}

/**
 * @return array
 */
function getReportRecipients()
{
    $extraFieldValue = new ExtraFieldValue('course');
    $value = $extraFieldValue->get_values_by_handler_and_field_variable(
        api_get_course_int_id(),
        'forum_report_recipients'
    );
    $users = [];
    if ($value && isset($value['value'])) {
        $usersType = explode(';', $value['value']);

        foreach ($usersType as $type) {
            switch ($type) {
                case 'teachers':
                    $teachers = CourseManager::get_teacher_list_from_course_code(api_get_course_id());
                    if (!empty($teachers)) {
                        $users = array_merge($users, array_column($teachers, 'user_id'));
                    }

                break;
                case 'admins':
                    $admins = UserManager::get_all_administrators();
                    if (!empty($admins)) {
                        $users = array_merge($users, array_column($admins, 'user_id'));
                    }

                    break;
                case 'community_managers':
                    $managers = api_get_setting('forum.community_managers_user_list', true);
                    if (!empty($managers) && isset($managers['users'])) {
                        $users = array_merge($users, $managers['users']);
                    }

                    break;
            }
        }

        $users = array_unique(array_filter($users));
    }

    return $users;
}

/**
 * Sends an e-mail to all users from getReportRecipients() (users with extra field 'forum_report_recipients')
 */
function reportPost(CForumPost $post, CForum $forumInfo, CForumThread $threadInfo): bool
{
    if (!reportAvailable()) {
        return false;
    }

    if (empty($forumInfo) || empty($threadInfo)) {
        return false;
    }

    $currentUser = api_get_user_info();
    $users = getReportRecipients();
    if (!empty($users)) {
        $url = getForumToolUrl(
            api_get_course_int_id(),
            api_get_session_id(),
            api_get_group_id(),
            $forumInfo->getIid(),
            $threadInfo->getIid()
        );
        $postLink = Display::url(
            $post->getTitle(),
            $url
        );
        $subject = get_lang('Post reported');
        $content = sprintf(
            get_lang('User %s has reported the message %s in the forum %s'),
            $currentUser['complete_name'],
            $postLink,
            $forumInfo->getTitle()
        );
        foreach ($users as $userId) {
            MessageManager::send_message_simple($userId, $subject, $content);
        }
    }

    return true;
}

function getVisibleForums($courseId, $sessionId): array
{
    $forums = get_forums($courseId, $sessionId);
    $visibleForums = [];

    foreach ($forums as $forum) {
        $forumSession = $forum->getFirstResourceLink()->getSession();
        if ($sessionId > 0) {
            if (null === $forumSession) {
                $threads = get_threads($forum->getIid(), $courseId, $sessionId, true);
                if (!empty($threads)) {
                    $visibleForums[] = $forum;
                }
            } else {
                $visibleForums[] = $forum;
            }
        } else {
            $visibleForums[] = $forum;
        }
    }

    return $visibleForums;
}

function getVisibleForumsInCategory($categoryId, $courseId, $sessionId): array
{
    $forumsInCategory = get_forums_in_category($categoryId, $courseId, $sessionId);
    $visibleForums = [];

    foreach ($forumsInCategory as $forum) {
        $forumSession = $forum->getFirstResourceLink()->getSession();
        if ($sessionId > 0) {
            if (null === $forumSession) {
                $threads = get_threads($forum->getIid(), $courseId, $sessionId, true);
                if (!empty($threads)) {
                    $visibleForums[] = $forum;
                }
            } else {
                $visibleForums[] = $forum;
            }
        } else {
            $visibleForums[] = $forum;
        }
    }

    return $visibleForums;
}

/**
 * Batch-fetch thread counts for multiple forums in a single query.
 *
 * @param int[] $forumIds
 *
 * @return array<int, int> forumId => threadCount
 */
function getThreadCountBatch(array $forumIds, int $courseId = 0, int $sessionId = 0): array
{
    if (empty($forumIds)) {
        return [];
    }

    $repo = Container::getForumThreadRepository();
    $courseId = empty($courseId) ? api_get_course_int_id() : $courseId;
    $course = api_get_course_entity($courseId);
    $session = api_get_session_entity($sessionId);

    $qb = $repo->getResourcesByCourse($course, $session);
    $qb->select('IDENTITY(resource.forum) AS forumId, COUNT(resource.iid) AS cnt')
        ->andWhere('resource.forum IN (:forumIds)')
        ->setParameter('forumIds', $forumIds)
        ->groupBy('resource.forum');

    $map = [];
    foreach ($qb->getQuery()->getArrayResult() as $row) {
        $map[(int) $row['forumId']] = (int) $row['cnt'];
    }

    return $map;
}

/**
 * Batch-fetch waiting-moderation post counts for multiple forums in a single query.
 *
 * @param int   $status   e.g. CForumPost::STATUS_WAITING_MODERATION
 * @param int[] $forumIds
 *
 * @return array<int, int> forumId => count
 */
function getCountPostsWithStatusBatch(int $status, array $forumIds): array
{
    if (empty($forumIds)) {
        return [];
    }

    $em = Database::getManager();
    $qb = $em->getRepository(CForumPost::class)->createQueryBuilder('p');
    $qb->select('IDENTITY(p.forum) AS forumId, COUNT(p.iid) AS cnt')
        ->where('p.status = :status')
        ->andWhere('p.visible = 1')
        ->andWhere('p.forum IN (:forumIds)')
        ->setParameter('status', $status)
        ->setParameter('forumIds', $forumIds)
        ->groupBy('p.forum');

    $map = [];
    foreach ($qb->getQuery()->getArrayResult() as $row) {
        $map[(int) $row['forumId']] = (int) $row['cnt'];
    }

    return $map;
}

/**
 * URL of the forum tool (Vue) of a course, optionally pointing to a forum or to one of its threads.
 * Empty when the course has no resource node.
 */
function getForumToolUrl(
    int $courseId,
    int $sessionId = 0,
    int $groupId = 0,
    int $forumId = 0,
    int $threadId = 0,
    array $params = []
): string {
    $nodeId = (int) api_get_course_entity($courseId)?->getResourceNode()?->getId();
    if (0 === $nodeId) {
        return '';
    }

    $path = api_get_path(WEB_PATH).'resources/forum/'.$nodeId.'/';
    if ($forumId > 0) {
        $path .= 'forum/'.$forumId;
        if ($threadId > 0) {
            $path .= '/thread/'.$threadId;
        }
    }

    return $path.'?'.http_build_query(['cid' => $courseId, 'sid' => $sessionId, 'gid' => $groupId] + $params);
}
