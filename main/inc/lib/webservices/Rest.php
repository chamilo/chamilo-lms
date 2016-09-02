<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CourseBundle\Entity\Repository\CAnnouncementRepository;
use Chamilo\CourseBundle\Entity\Repository\CNotebookRepository;
use Chamilo\CourseBundle\Entity\CLpCategory;

/**
 * Class RestApi
 */
class Rest extends WebService
{
    const SERVIVE_NAME = 'MsgREST';
    const EXTRA_FIELD_GCM_REGISTRATION = 'gcm_registration_id';

    const ACTION_AUTH = 'authenticate';
    const ACTION_USER_MESSAGES = 'user_messages';
    const ACTION_GCM_ID = 'gcm_id';
    const ACTION_USER_COURSES = 'user_courses';
    const ACTION_PROFILE = 'user_profile';
    const ACTION_COURSE_INFO = 'course_info';
    const ACTION_COURSE_DESCRIPTIONS = 'course_descriptions';
    const ACTION_COURSE_DOCUMENTS = 'course_documents';
    const ACTION_COURSE_ANNOUNCEMENTS = 'course_announcements';
    const ACTION_COURSE_ANNOUNCEMENT = 'course_announcement';
    const ACTION_COURSE_AGENDA = 'course_agenda';
    const ACTION_COURSE_NOTEBOOKS = 'course_notebooks';
    const ACTION_COURSE_FORUM_CATEGORIES = 'course_forumcategories';
    const ACTION_COURSE_FORUM = 'course_forum';
    const ACTION_COURSE_FORUM_THREAD = 'course_forumthread';
    const ACTION_COURSE_LEARNPATHS = 'course_learnpaths';

    const EXTRAFIELD_GCM_ID = 'gcm_registration_id';

    /**
     * Rest constructor.
     * @param string $username
     * @param string $apiKey
     */
    public function __construct($username, $apiKey)
    {
        parent::__construct($username, $apiKey);
    }

    /**
     * @param string $username
     * @param string $apiKeyToValidate
     * @return Rest
     * @throws Exception
     */
    public static function validate($username, $apiKeyToValidate)
    {
        $apiKey = self::findUserApiKey($username, self::SERVIVE_NAME);

        if ($apiKey != $apiKeyToValidate) {
            throw new Exception(get_lang('InvalidApiKey'));
        }

        return new self($username, $apiKey);
    }

    /**
     * Create the gcm_registration_id extra field for users
     */
    public static function init()
    {
        $extraField = new ExtraField('user');
        $fieldInfo = $extraField->get_handler_field_info_by_field_variable(self::EXTRA_FIELD_GCM_REGISTRATION);

        if (empty($fieldInfo)) {
            $extraField->save([
                'variable' => self::EXTRA_FIELD_GCM_REGISTRATION,
                'field_type' => ExtraField::FIELD_TYPE_TEXT,
                'display_text' => self::EXTRA_FIELD_GCM_REGISTRATION
            ]);
        }
    }

    /**
     * @param string $registrationId
     * @return bool
     */
    public function setGcmId($registrationId)
    {
        $registrationId = Security::remove_XSS($registrationId);
        $extraFieldValue = new ExtraFieldValue('user');

        return $extraFieldValue->save([
            'variable' => self::EXTRA_FIELD_GCM_REGISTRATION,
            'value' => $registrationId,
            'item_id' => $this->user->getId()
        ]);
    }

    /**
     * @param int $lastMessageId
     * @return array
     */
    public function getUserMessages($lastMessageId = 0)
    {
        $lastMessages = MessageManager::getMessagesFromLastReceivedMessage($this->user->getId(), $lastMessageId);
        $messages = [];

        foreach ($lastMessages as $message) {
            $hasAttachments = MessageManager::hasAttachments($message['id']);

            $messages[] = array(
                'id' => $message['id'],
                'title' => $message['title'],
                'sender' => array(
                    'id' => $message['user_id'],
                    'lastname' => $message['lastname'],
                    'firstname' => $message['firstname'],
                    'completeName' => api_get_person_name($message['firstname'], $message['lastname']),
                ),
                'sendDate' => $message['send_date'],
                'content' => $message['content'],
                'hasAttachments' => $hasAttachments,
                'url' => ''
            );
        }

        return $messages;
    }

    /**
     * Get the user courses
     * @return array
     */
    public function getUserCourses()
    {
        $courses = CourseManager::get_courses_list_by_user_id($this->user->getId());
        $data = [];

        foreach ($courses as $courseId) {
            /** @var Course $course */
            $course = Database::getManager()->find('ChamiloCoreBundle:Course', $courseId['real_id']);

            $teachers = CourseManager::get_teacher_list_from_course_code_to_string($course->getCode());

            $data[] = [
                'id' => $course->getId(),
                'title' => $course->getTitle(),
                'code' => $course->getCode(),
                'directory' => $course->getDirectory(),
                'urlPicture' => $course->getPicturePath(true),
                'teachers' => $teachers
            ];
        }

        return $data;
    }

    /**
     * @param int $courseId
     * @return array
     * @throws Exception
     */
    public function getCourseInfo($courseId)
    {
        /** @var Course $course */
        $course = Database::getManager()->find('ChamiloCoreBundle:Course', $courseId);

        if (!$course) {
            throw new Exception(get_lang('NoCourse'));
        }

        $teachers = CourseManager::get_teacher_list_from_course_code_to_string($course->getCode());

        return [
            'id' => $course->getId(),
            'title' => $course->getTitle(),
            'code' => $course->getCode(),
            'directory' => $course->getDirectory(),
            'urlPicture' => $course->getPicturePath(true),
            'teachers' => $teachers
        ];
    }

    /**
     * Get the course descriptions
     * @param int $courseId
     * @return array
     * @throws Exception
     */
    public function getCourseDescriptions($courseId)
    {
        /** @var Course $course */
        $course = Database::getManager()->find('ChamiloCoreBundle:Course', $courseId);

        if (!$course) {
            throw new Exception(get_lang('NoCourse'));
        }

        $descriptions = CourseDescription::get_descriptions($course->getId());
        $results = [];

        /** @var CourseDescription $description */
        foreach ($descriptions as $description) {
            $results[] = [
                'id' => $description->get_description_type(),
                'title' => $description->get_title(),
                'content' => str_replace('src="/', 'src="' . api_get_path(WEB_PATH), $description->get_content())
            ];
        }

        return $results;
    }

    /**
     * @param int $courseId
     * @param int $directoryId
     * @return array
     * @throws Exception
     */
    public function getCourseDocuments($courseId, $directoryId = 0)
    {
        /** @var Course $course */
        $course = Database::getManager()->find('ChamiloCoreBundle:Course', $courseId);

        if (!$course) {
            throw new Exception(get_lang('NoCourse'));
        }

        /** @var string $path */
        $path = '/';

        if ($directoryId) {
            $directory = DocumentManager::get_document_data_by_id($directoryId, $course->getCode(), false, 0);

            if (!$directory) {
                throw new Exception('NoDataAvailable');
            }

            $path = $directory['path'];
        }

        require_once api_get_path(LIBRARY_PATH) . 'fileDisplay.lib.php';

        $courseInfo = api_get_course_info_by_id($course->getId());

        $documents = DocumentManager::get_all_document_data($courseInfo, $path);
        $results = [];

        if (is_array($documents)) {
            $webPath = api_get_path(WEB_CODE_PATH) . 'document/document.php?';

            /** @var array $document */
            foreach ($documents as $document) {
                if ($document['visibility'] != '1') {
                    continue;
                }

                $icon = $document['filetype'] == 'file'
                    ? choose_image($document['path'])
                    : chooseFolderIcon($document['path']);

                $results[] = [
                    'id' => $document['id'],
                    'type' => $document['filetype'],
                    'title' => $document['title'],
                    'path' => $document['path'],
                    'url' => $webPath . http_build_query([
                        'username' => $this->user->getUsername(),
                        'api_key' => $this->apiKey,
                        'cidReq' => $course->getCode(),
                        'id_session' => 0,
                        'gidReq' => 0,
                        'gradebook' => 0,
                        'origin' => '',
                        'action' => 'download',
                        'id' => $document['id']
                    ]),
                    'icon' => $icon,
                    'size' => format_file_size($document['size'])
                ];
            }
        }

        return $results;
    }

    /**
     * @param int $courseId
     * @return array
     * @throws Exception
     */
    public function getCourseAnnouncements($courseId)
    {
        /** @var Course $course */
        $course = Database::getManager()->find('ChamiloCoreBundle:Course', $courseId);

        if (!$course) {
            throw new Exception(get_lang('NoCourse'));
        }

        $announcements = AnnouncementManager::getAnnouncements(
            null,
            null,
            false,
            null,
            null,
            null,
            null,
            null,
            0,
            $this->user->getId(),
            $courseId
        );

        $announcements = array_map(function ($announcement) {
            return [
                'id' => intval($announcement['id']),
                'title' => strip_tags($announcement['title']),
                'creatorName' => strip_tags($announcement['username']),
                'date' => strip_tags($announcement['insert_date'])
            ];
        }, $announcements);

        return $announcements;
    }

    /**
     * @param int $announcementId
     * @param int $courseId
     * @return array
     * @throws Exception
     */
    public function getCourseAnnouncement($announcementId, $courseId)
    {
        /** @var Course $course */
        $course = Database::getManager()->find('ChamiloCoreBundle:Course', $courseId);

        if (!$course) {
            throw new Exception(get_lang('NoCourse'));
        }

        $announcement = AnnouncementManager::getAnnouncementInfoById(
            $announcementId,
            $course->getId(),
            $this->user->getId()
        );

        if (!$announcement) {
            throw new Exception(get_lang('NoAnnouncement'));
        }

        return [
            'id' => intval($announcement['announcement']->getIid()),
            'title' => $announcement['announcement']->getTitle(),
            'creatorName' => $announcement['item_property']->getInsertUser()->getCompleteName(),
            'date' => api_convert_and_format_date($announcement['item_property']->getInsertDate(), DATE_TIME_FORMAT_LONG_24H),
            'content' => AnnouncementManager::parse_content(
                $this->user->getId(),
                $announcement['announcement']->getContent(),
                $course->getCode()
            )
        ];
    }

    /**
     * @param int $courseId
     * @return array
     * @throws Exception
     */
    public function getCourseAgenda($courseId)
    {
        /** @var Course $course */
        $course = Database::getManager()->find('ChamiloCoreBundle:Course', $courseId);

        if (!$course) {
            throw new Exception(get_lang('NoCourse'));
        }

        $agenda = new Agenda();
        $agenda->setType('course');
        $result = $agenda->parseAgendaFilter(null);

        $start = new DateTime('now');
        $start->modify('first day of month');
        $end = new DateTime('now');
        $end->modify('first day of month');

        $groupId = current($result['groups']);
        $userId = current($result['users']);

        $events = $agenda->getEvents(
            $start->getTimestamp(),
            $end->getTimestamp(),
            $course->getId(),
            $groupId,
            $userId,
            'array'
        );

        if (!is_array($events)) {
            return [];
        }

        $webPath = api_get_path(WEB_PATH);

        return array_map(
            function ($event) use ($webPath) {
                return [
                    'id' => intval($event['unique_id']),
                    'title' => $event['title'],
                    'content' => str_replace('src="/', 'src="' . $webPath, $event['description']),
                    'startDate' => $event['start_date_localtime'],
                    'endDate' => $event['end_date_localtime'],
                    'isAllDay' => $event['allDay'] ? true : false
                ];
            },
            $events
        );
    }

    /**
     * @param int $courseId
     * @return array
     * @throws Exception
     */
    public function getCourseNotebooks($courseId)
    {
        $em = Database::getManager();
        /** @var Course $course */
        $course = $em->find('ChamiloCoreBundle:Course', $courseId);

        if (!$course) {
            throw new Exception(get_lang('NoCourse'));
        }

        /** @var CNotebookRepository $notebooksRepo */
        $notebooksRepo = $em->getRepository('ChamiloCourseBundle:CNotebook');
        $notebooks = $notebooksRepo->findByUser($this->user, $course, null);

        return array_map(
            function (\Chamilo\CourseBundle\Entity\CNotebook $notebook) {
                return [
                    'id' => $notebook->getIid(),
                    'title' => $notebook->getTitle(),
                    'description' => $notebook->getDescription(),
                    'creationDate' => api_format_date(
                        $notebook->getCreationDate()->getTimestamp()
                    ),
                    'updateDate' => api_format_date(
                        $notebook->getUpdateDate()->getTimestamp()
                    )
                ];
            },
            $notebooks
        );
    }

    /**
     * @param int $courseId
     * @return array
     * @throws Exception
     */
    public function getCourseForumCategories($courseId)
    {
        $em = Database::getManager();
        /** @var Course $course */
        $course = $em->find('ChamiloCoreBundle:Course', $courseId);

        if (!$course) {
            throw new Exception(get_lang('NoCourse'));
        }

        $webCoursePath = api_get_path(WEB_COURSE_PATH) . $course->getDirectory() . '/upload/forum/images/';

        require_once api_get_path(SYS_CODE_PATH) . 'forum/forumfunction.inc.php';

        $categoriesFullData = get_forum_categories('', $course->getId());
        $categories = [];
        $includeGroupsForums = api_get_setting('display_groups_forum_in_general_tool') === 'true';
        $forumsFullData = get_forums('', $course->getCode(), $includeGroupsForums);
        $forums = [];

        foreach ($forumsFullData as $forumId => $forumInfo) {
            $forum = [
                'id' => intval($forumInfo['iid']),
                'catId' => intval($forumInfo['forum_category']),
                'title' => $forumInfo['forum_title'],
                'description' => $forumInfo['forum_comment'],
                'image' => $forumInfo['forum_image'] ? ($webCoursePath . $forumInfo['forum_image']) : '',
                'numberOfThreads' => intval($forumInfo['number_of_threads']),
                'lastPost' => null
            ];

            $lastPostInfo = get_last_post_information($forumId, false, $course->getId());

            if ($lastPostInfo) {
                $forum['lastPost'] = [
                    'date' => api_convert_and_format_date($lastPostInfo['last_post_date']),
                    'user' => api_get_person_name(
                        $lastPostInfo['last_poster_firstname'],
                        $lastPostInfo['last_poster_lastname']
                    )
                ];
            }

            $forums[] = $forum;
        }

        foreach ($categoriesFullData as $category) {
            $categoryForums = array_filter(
                $forums,
                function (array $forum) use ($category) {
                    if ($forum['catId'] != $category['cat_id']) {
                        return false;
                    }

                    return true;
                }
            );

            $categories[] = [
                'id' => intval($category['iid']),
                'title' => $category['cat_title'],
                'catId' => intval($category['cat_id']),
                'description' => $category['cat_comment'],
                'forums' => $categoryForums,
                'courseId' => $course->getId()
            ];
        }

        return $categories;
    }

    /**
     * @param int $forumId
     * @return array
     * @throws Exception
     */
    public function getCourseForum($forumId)
    {
        require_once api_get_path(SYS_CODE_PATH) . 'forum/forumfunction.inc.php';

        $forumInfo = get_forums($forumId);

        if (!isset($forumInfo['iid'])) {
            throw new Exception(get_lang('NoForum'));
        }

        /** @var Course $course */
        $course = Database::getManager()->find('ChamiloCoreBundle:Course', $forumInfo['c_id']);

        if (!$course) {
            throw new Exception(get_lang('NoCourse'));
        }

        $webCoursePath = api_get_path(WEB_COURSE_PATH) . $course->getDirectory() . '/upload/forum/images/';
        $forum = [
            'id' => $forumInfo['iid'],
            'title' => $forumInfo['forum_title'],
            'description' => $forumInfo['forum_comment'],
            'image' => $forumInfo['forum_image'] ? ($webCoursePath . $forumInfo['forum_image']) : '',
            'threads' => []
        ];

        $threads = get_threads($forumInfo['iid']);

        foreach ($threads as $thread) {
            $forum['threads'][] = [
                'id' => $thread['iid'],
                'title' => $thread['thread_title'],
                'lastEditDate' => api_convert_and_format_date($thread['lastedit_date'], DATE_TIME_FORMAT_LONG_24H),
                'numberOfReplies' => $thread['thread_replies'],
                'numberOfViews' => $thread['thread_views'],
                'author' => api_get_person_name($thread['firstname'], $thread['lastname'])
            ];
        }

        return $forum;
    }

    /**
     * @param int $threadId
     * @return array
     */
    public function getCourseForumThread($threadId)
    {
        require_once api_get_path(SYS_CODE_PATH) . 'forum/forumfunction.inc.php';

        $threadInfo = get_thread_information($threadId);

        $thread = [
            'id' => intval($threadInfo['iid']),
            'cId' => intval($threadInfo['c_id']),
            'title' => $threadInfo['thread_title'],
            'forumId' => intval($threadInfo['forum_id']),
            'posts' => []
        ];

        $forumInfo = get_forums($threadInfo['forum_id']);

        $postsInfo = getPosts($forumInfo, $threadInfo['iid'], 'ASC');

        foreach ($postsInfo as $postInfo) {
            $thread['posts'][] = [
                'id' => $postInfo['iid'],
                'title' => $postInfo['post_title'],
                'text' => $postInfo['post_text'],
                'author' => api_get_person_name($postInfo['firstname'], $postInfo['lastname']),
                'date' => api_convert_and_format_date($postInfo['post_date'], DATE_TIME_FORMAT_LONG_24H),
                'parentId' => $postInfo['post_parent_id']
            ];
        }

        return $thread;
    }

    /**
     * @return array
     */
    public function getUserProfile()
    {
        $pictureInfo = UserManager::get_user_picture_path_by_id($this->user->getId(), 'web');

        $result = [
            'pictureUri' => $pictureInfo['dir'] . $pictureInfo['file'],
            'fullName' => $this->user->getCompleteName(),
            'username' => $this->user->getUsername(),
            'officialCode' => $this->user->getOfficialCode(),
            'phone' => $this->user->getPhone(),
            'extra' => []
        ];

        $fieldValue = new ExtraFieldValue('user');
        $extraInfo = $fieldValue->getAllValuesForAnItem($this->user->getId(), true);

        foreach ($extraInfo as $extra) {
            /** @var ExtraFieldValues $extraValue */
            $extraValue = $extra['value'];

            $result['extra'][] = [
                'title' => $extraValue->getField()->getDisplayText(true),
                'value' => $extraValue->getValue()
            ];
        }

        return $result;
    }

    /**
     * @param int $courseId
     * @return array
     * @throws Exception
     */
    public function getCourseLearnPaths($courseId)
    {
        $em = Database::getManager();
        /** @var Course $course */
        $course = $em->find('ChamiloCoreBundle:Course', $courseId);

        if (!$course) {
            throw new Exception(get_lang('NoCourse'));
        }

        $categoriesTempList = learnpath::getCategories($courseId);

        $categoryNone = new \Chamilo\CourseBundle\Entity\CLpCategory();
        $categoryNone->setId(0);
        $categoryNone->setName(get_lang('WithOutCategory'));
        $categoryNone->setPosition(0);

        $categories = array_merge([$categoryNone], $categoriesTempList);

        $userId = api_get_user_id();

        $categoryData = array();

        /** @var CLpCategory $category */
        foreach ($categories as $category) {
            $learnPathList = new LearnpathList(
                $this->user->getId(),
                $course->getCode(),
                null,
                null,
                false,
                $category->getId()
            );

            $flatLpList = $learnPathList->get_flat_list();

            if (empty($flatLpList)) {
                continue;
            }

            $listData = array();

            foreach ($flatLpList as $lpId => $lpDetails) {
                if ($lpDetails['lp_visibility'] == 0) {
                    continue;
                }

                if (!learnpath::is_lp_visible_for_student(
                    $lpId,
                    $this->user->getId(),
                    $course->getCode()
                )) {
                    continue;
                }

                $timeLimits = false;

                //This is an old LP (from a migration 1.8.7) so we do nothing
                if (
                    (empty($lpDetails['created_on']) || $lpDetails['created_on'] == '0000-00-00 00:00:00') &&
                    (empty($lpDetails['modified_on']) || $lpDetails['modified_on'] == '0000-00-00 00:00:00')
                ) {
                    $timeLimits = false;
                }

                //Checking if expired_on is ON
                if ($lpDetails['expired_on'] != '' && $lpDetails['expired_on'] != '0000-00-00 00:00:00') {
                    $timeLimits = true;
                }

                if ($timeLimits) {
                    if (
                        !empty($lpDetails['publicated_on']) && $lpDetails['publicated_on'] != '0000-00-00 00:00:00' &&
                        !empty($lpDetails['expired_on']) && $lpDetails['expired_on'] != '0000-00-00 00:00:00'
                    ) {
                        $startTime = api_strtotime($lpDetails['publicated_on'], 'UTC');
                        $endTime = api_strtotime($lpDetails['expired_on'], 'UTC');
                        $now = time();
                        $isActivedTime = false;

                        if ($now > $startTime && $endTime > $now) {
                            $isActivedTime = true;
                        }

                        if (!$isActivedTime) {
                            continue;
                        }
                    }
                }

                $lpStartUrl = api_get_cidreq() . '&action=view&lp_id=' . $lpId;
                $progress = learnpath::getProgress($lpId, $userId, $courseId);

                $listData[] = array(
                    'urlStart' => rawurlencode($lpStartUrl),
                    'title' => Security::remove_XSS($lpDetails['lp_name']),
                    'progress' => intval($progress),
                );
            }

            $categoryData[] = array(
                'id' => $category->getId(),
                'name' => $category->getName(),
                'learnpaths' => $listData
            );
        }

        return $categoryData;
    }
}
