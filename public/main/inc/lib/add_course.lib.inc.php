<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CGroupCategory;
use Chamilo\CourseBundle\Entity\CToolIntro;

/**
 * Class AddCourse.
 */
class AddCourse
{
    public const FIRST_EXPIRATION_DATE = 31536000; // 365 days in seconds

    /**
     * Defines the four needed keys to create a course based on several parameters.
     *
     * @param string    The code you want for this course
     * @param string    Prefix added for ALL keys
     * @param string    Prefix added for databases only
     * @param string    Prefix added for paths only
     * @param bool      Add unique prefix
     * @param bool      Use code-independent keys
     *
     * @return array An array with the needed keys ['currentCourseCode'], ['currentCourseId'], ['currentCourseDbName'],
     *               ['currentCourseRepository']
     *
     * @todo Eliminate the global variables.
     * @assert (null) === false
     */
    public static function define_course_keys(
        $wanted_code,
        $prefix_for_all = '',
        $prefix_for_base_name = '',
        $prefix_for_path = '',
        $add_unique_prefix = false,
        $use_code_indepedent_keys = true
    ) {
        $course_table = Database::get_main_table(TABLE_MAIN_COURSE);
        $wanted_code = CourseManager::generate_course_code($wanted_code);
        $keys_course_code = $wanted_code;
        if (!$use_code_indepedent_keys) {
            $wanted_code = '';
        }

        $unique_prefix = '';
        if ($add_unique_prefix) {
            $unique_prefix = substr(md5(uniqid(rand())), 0, 10);
        }

        $keys = [];
        $final_suffix = ['CourseId' => '', 'CourseDb' => '', 'CourseDir' => ''];
        $limit_numb_try = 100;
        $keys_are_unique = false;
        $try_new_fsc_id = $try_new_fsc_db = $try_new_fsc_dir = 0;

        while (!$keys_are_unique) {
            $keys_course_id = $prefix_for_all.$unique_prefix.$wanted_code.$final_suffix['CourseId'];
            $keys_course_repository = $prefix_for_path.$unique_prefix.$wanted_code.$final_suffix['CourseDir'];
            $keys_are_unique = true;

            // Check whether they are unique.
            $query = "SELECT 1 FROM $course_table
                      WHERE code='".$keys_course_id."'
                      LIMIT 0, 1";
            $result = Database::query($query);

            if (Database::num_rows($result)) {
                $keys_are_unique = false;
                $try_new_fsc_id++;
                $final_suffix['CourseId'] = substr(md5(uniqid(rand())), 0, 4);
            }

            if (($try_new_fsc_id + $try_new_fsc_db + $try_new_fsc_dir) > $limit_numb_try) {
                return $keys;
            }
        }

        $keys['currentCourseCode'] = $keys_course_code;
        $keys['currentCourseId'] = $keys_course_id;
        $keys['currentCourseRepository'] = $keys_course_repository;

        return $keys;
    }

    /**
     * Gets an array with all the course tables (deprecated?).
     *
     * @return array
     *
     * @assert (null) !== null
     */
    public static function get_course_tables()
    {
        $tables = [];
        //$tables[] = 'item_property';
        $tables[] = 'tool';
        $tables[] = 'tool_intro';
        $tables[] = 'group_info';
        $tables[] = 'group_category';
        $tables[] = 'group_rel_user';
        $tables[] = 'group_rel_tutor';
        $tables[] = 'userinfo_content';
        $tables[] = 'userinfo_def';
        $tables[] = 'course_description';
        $tables[] = 'calendar_event';
        $tables[] = 'calendar_event_repeat';
        $tables[] = 'calendar_event_repeat_not';
        $tables[] = 'calendar_event_attachment';
        $tables[] = 'announcement';
        $tables[] = 'announcement_attachment';
        //$tables[] = 'resource';
        $tables[] = 'student_publication';
        $tables[] = 'student_publication_assignment';
        $tables[] = 'document';
        /*$tables[] = 'forum_category';
        $tables[] = 'forum_forum';
        $tables[] = 'forum_thread';
        $tables[] = 'forum_post';
        $tables[] = 'forum_mailcue';
        $tables[] = 'forum_attachment';
        $tables[] = 'forum_notification';
        $tables[] = 'forum_thread_qualify';
        $tables[] = 'forum_thread_qualify_log';*/
        $tables[] = 'link';
        $tables[] = 'link_category';
        $tables[] = 'online_connected';
        $tables[] = 'online_link';
        $tables[] = 'chat_connected';
        $tables[] = 'quiz';
        $tables[] = 'quiz_rel_question';
        $tables[] = 'quiz_question';
        $tables[] = 'quiz_answer';
        $tables[] = 'quiz_question_option';
        $tables[] = 'quiz_question_category';
        $tables[] = 'quiz_question_rel_category';
        $tables[] = 'dropbox_post';
        $tables[] = 'dropbox_file';
        $tables[] = 'dropbox_person';
        $tables[] = 'dropbox_category';
        $tables[] = 'dropbox_feedback';
        $tables[] = 'lp';
        $tables[] = 'lp_item';
        $tables[] = 'lp_view';
        $tables[] = 'lp_item_view';
        $tables[] = 'lp_iv_interaction';
        $tables[] = 'lp_iv_objective';
        $tables[] = 'blog';
        $tables[] = 'blog_comment';
        $tables[] = 'blog_post';
        $tables[] = 'blog_rating';
        $tables[] = 'blog_rel_user';
        $tables[] = 'blog_task';
        $tables[] = 'blog_task_rel_user';
        $tables[] = 'blog_attachment';
        $tables[] = 'permission_group';
        $tables[] = 'permission_user';
        $tables[] = 'permission_task';
        $tables[] = 'role';
        $tables[] = 'role_group';
        $tables[] = 'role_permissions';
        $tables[] = 'role_user';
        $tables[] = 'survey';
        $tables[] = 'survey_question';
        $tables[] = 'survey_question_option';
        $tables[] = 'survey_invitation';
        $tables[] = 'survey_answer';
        $tables[] = 'survey_group';
        $tables[] = 'wiki';
        $tables[] = 'wiki_conf';
        $tables[] = 'wiki_discuss';
        $tables[] = 'wiki_mailcue';
        $tables[] = 'course_setting';
        $tables[] = 'glossary';
        $tables[] = 'notebook';
        $tables[] = 'attendance';
        $tables[] = 'attendance_sheet';
        $tables[] = 'attendance_calendar';
        $tables[] = 'attendance_result';
        $tables[] = 'attendance_sheet_log';
        $tables[] = 'thematic';
        $tables[] = 'thematic_plan';
        $tables[] = 'thematic_advance';

        return $tables;
    }

    /**
     * Executed only before create_course_tables().
     *
     * @assert (null) === null
     */
    public static function drop_course_tables()
    {
        $list = self::get_course_tables();
        foreach ($list as $table) {
            $sql = "DROP TABLE IF EXISTS ".DB_COURSE_PREFIX.$table;
            Database::query($sql);
        }
    }

    /**
     * Sorts pictures by type (used?).
     *
     * @param array List of files (sthg like array(0=>array('png'=>1)))
     * @param string $type
     *
     * @return array The received array without files not matching type
     * @assert (array(),null) === array()
     */
    public static function sort_pictures($files, $type)
    {
        $pictures = [];
        foreach ($files as $value) {
            if (isset($value[$type]) && '' != $value[$type]) {
                $pictures[][$type] = $value[$type];
            }
        }

        return $pictures;
    }

    /**
     * Populates the course with essential settings, group categories, example content, and installs course plugins.
     *
     * This method initializes a new course by setting up various course settings, creating a default group category,
     * inserting example content if required, and installing course-specific plugins. The method can also fill the course
     * with exemplary content based on the parameter provided or the system setting for example content creation.
     *
     * @param Course $course The course entity to be populated.
     * @param bool|null $fillWithExemplaryContent Determines whether to fill the course with exemplary content. If null,
     *                                            the system setting for example material course creation is used.
     * @param int $authorId The ID of the user who is considered the author of the exemplary content. Defaults to the
     *                      current user if not specified.
     *
     * @return bool Returns true if the course is successfully populated, false otherwise.
     *
     * @throws Exception Throws exception on error.
     */
    public static function fillCourse(Course $course, bool $fillWithExemplaryContent = null, int $authorId = 0): bool
    {
        $authorId = $authorId ?: api_get_user_id();

        self::insertCourseSettings($course);
        self::createGroupCategory($course);
        
        if ($fillWithExemplaryContent ?? api_get_setting('example_material_course_creation') !== 'false') {
            $gradebook = self::createRootGradebook($course);
            self::insertExampleContent($course, $authorId, $gradebook);
        }

        self::installCoursePlugins($course->getId());

        return true;
    }

    /**
     * Inserts default and specified settings for a given course.
     *
     * This method takes a Course object as input and applies a predefined
     * set of settings. These settings include configurations for email alerts,
     * permissions for users to edit various components like agenda and announcements,
     * theme settings, and more. It also handles the case where a course needs to
     * enable certain features by default based on platform-wide settings.
     *
     * @param Course $course The course object to which the settings will be applied.
     *
     * @return void
     * @throws Exception
     */
    private static function insertCourseSettings(Course $course): void
    {

        $TABLESETTING = Database::get_course_table(TABLE_COURSE_SETTING);

        $settingsManager = Container::getCourseSettingsManager();
        $settingsManager->setCourse($course);

        $alert = api_get_setting('email_alert_manager_on_new_quiz');
        $defaultEmailExerciseAlert = 0;
        if ('true' === $alert) {
            $defaultEmailExerciseAlert = 1;
        }

        /* course_setting table (courseinfo tool)   */
        $settings = [
            'email_alert_manager_on_new_doc' => ['title' => '', 'default' => 0, 'category' => 'work'],
            'email_alert_on_new_doc_dropbox' => ['default' => 0, 'category' => 'dropbox'],
            'allow_user_edit_agenda' => ['default' => 0, 'category' => 'agenda'],
            'allow_user_edit_announcement' => ['default' => 0, 'category' => 'announcement'],
            'email_alert_manager_on_new_quiz' => ['default' => $defaultEmailExerciseAlert, 'category' => 'quiz'],
            'allow_user_image_forum' => ['default' => 1, 'category' => 'forum'],
            'course_theme' => ['default' => '', 'category' => 'theme'],
            'allow_learning_path_theme' => ['default' => 1, 'category' => 'theme'],
            'allow_open_chat_window' => ['default' => 1, 'category' => 'chat'],
            'email_alert_to_teacher_on_new_user_in_course' => ['default' => 0, 'category' => 'registration'],
            'allow_user_view_user_list' => ['default' => 1, 'category' => 'user'],
            'display_info_advance_inside_homecourse' => ['default' => 1, 'category' => 'thematic_advance'],
            'email_alert_students_on_new_homework' => ['default' => 0, 'category' => 'work'],
            'enable_lp_auto_launch' => ['default' => 0, 'category' => 'learning_path'],
            'enable_exercise_auto_launch' => ['default' => 0, 'category' => 'exercise'],
            'enable_document_auto_launch' => ['default' => 0, 'category' => 'document'],
            'pdf_export_watermark_text' => ['default' => '', 'category' => 'learning_path'],
            'allow_public_certificates' => [
                'default' => 'true' === api_get_setting('allow_public_certificates') ? 1 : '',
                'category' => 'certificates',
            ],
            'documents_default_visibility' => ['default' => 'visible', 'category' => 'document'],
            'show_course_in_user_language' => ['default' => 2, 'category' => null],
            'email_to_teachers_on_new_work_feedback' => ['default' => 1, 'category' => null],
        ];

        $counter = 1;
        foreach ($settings as $variable => $setting) {
            $title = $setting['title'] ?? '';
            Database::query(
                "INSERT INTO $TABLESETTING (c_id, title, variable, value, category)
                      VALUES ({$course->getId()}, '".$title."', '".$variable."', '".$setting['default']."', '".$setting['category']."')"
            );
            $counter++;
        }
    }

    /**
     * Creates a default group category for the specified course.
     *
     * This method initializes a new group category with a default title
     * and associates it with the given course. The default group category
     * is essential for organizing groups within the course, allowing for
     * better management and classification of course participants.
     *
     * @param Course $course The course object for which the default group category is created.
     *
     * @return void
     */
    private static function createGroupCategory(Course $course): void
    {
        $groupCategory = new CGroupCategory();
        $groupCategory
            ->setTitle(get_lang('Default groups'))
            ->setParent($course)
            ->addCourseLink($course)
        ;
        Database::getManager()->persist($groupCategory);
        Database::getManager()->flush();
    }

    /**
     * Inserts example content into a given course.
     *
     * This method populates the specified course with a predefined set of example
     * content, including documents, links, announcements, and exercises. This content
     * serves as a template or starting point for instructors, showcasing the various
     * types of materials and activities that can be included in a course. The content
     * is added under the authority of the specified author ID.
     *
     * @param Course $course The course object into which the example content will be inserted.
     * @param int $authorId The ID of the user who will be listed as the author of the inserted content.
     * @param GradebookCategory|null $gradebook
     *
     * @return void
     * @throws Exception
     */
    private static function insertExampleContent(Course $course, int $authorId, ?GradebookCategory $gradebook): void
    {
        $now = api_get_utc_datetime();
        $files = [
            ['path' => '/audio', 'title' => get_lang('Audio'), 'filetype' => 'folder', 'size' => 0],
            ['path' => '/images', 'title' => get_lang('Images'), 'filetype' => 'folder', 'size' => 0],
            ['path' => '/images/gallery', 'title' => get_lang('Gallery'), 'filetype' => 'folder', 'size' => 0],
            ['path' => '/video', 'title' => get_lang('Video'), 'filetype' => 'folder', 'size' => 0],
        ];
        $paths = [];
        $courseInfo = ['real_id' => $course->getId(), 'code' => $course->getCode()];
        $counter = 1;
        foreach ($files as $file) {
            $doc = self::insertDocument($courseInfo, $counter, $file, $authorId);
            $paths[$file['path']] = $doc->getIid();
            $counter++;
        }

        $finder = new Symfony\Component\Finder\Finder();
        $defaultPath = api_get_path(SYS_PUBLIC_PATH).'img/document';
        $finder->in($defaultPath);

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $parentName = dirname(str_replace($defaultPath, '', $file->getRealPath()));
            if ('/' === $parentName || '/certificates' === $parentName) {
                continue;
            }

            $title = $file->getFilename();
            $parentId = $paths[$parentName];

            if ($file->isDir()) {
                $realPath = str_replace($defaultPath, '', $file->getRealPath());
                $document = DocumentManager::addDocument(
                    $courseInfo,
                    $realPath,
                    'folder',
                    null,
                    $title,
                    '',
                    null,
                    null,
                    null,
                    null,
                    null,
                    false,
                    null,
                    $parentId,
                    $file->getRealPath()
                );
                $paths[$realPath] = $document->getIid();
            } else {
                $realPath = str_replace($defaultPath, '', $file->getRealPath());
                $document = DocumentManager::addDocument(
                    $courseInfo,
                    $realPath,
                    'file',
                    $file->getSize(),
                    $title,
                    '',
                    null,
                    null,
                    null,
                    null,
                    null,
                    false,
                    null,
                    $parentId,
                    $file->getRealPath()
                );

                if ($document && 'default.html' === $document->getTitle()) {
                    $certificateId = $document->getIid();
                }
            }
        }

        $agenda = new Agenda('course');
        $agenda->set_course($courseInfo);
        $agenda->addEvent(
            $now,
            $now,
            0,
            get_lang('Course creation'),
            get_lang('This course was created at this time')
        );

        /*  Links tool */
        $link = new Link();
        $link->setCourse($courseInfo);
        $links = [
            [
                'c_id' => $course->getId(),
                'url' => 'http://www.google.com',
                'title' => 'Quick and powerful search engine',
                'description' => get_lang('Quick and powerful search engine'),
                'category_id' => 0,
                'on_homepage' => 0,
                'target' => '_self',
                'session_id' => 0,
            ],
            [
                'c_id' => $course->getId(),
                'url' => 'http://www.wikipedia.org',
                'title' => 'Free online encyclopedia',
                'description' => get_lang('Free online encyclopedia'),
                'category_id' => 0,
                'on_homepage' => 0,
                'target' => '_self',
                'session_id' => 0,
            ],
        ];

        foreach ($links as $params) {
            $link->save($params, false, false);
        }

        /* Announcement tool */
        AnnouncementManager::add_announcement(
            $courseInfo,
            0,
            get_lang('This is an announcement example'),
            get_lang('This is an announcement example. Only trainers are allowed to publish announcements.'),
            ['everyone' => 'everyone'],
            null,
            null,
            $now
        );

        /*  Exercise tool */
        $exercise = new Exercise($course->getId());
        $exercise->exercise = get_lang('Sample test');
        $html = '<table width="100%" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                        <td width="220" valign="top" align="left">
                            <img src="'.api_get_path(WEB_PUBLIC_PATH).'img/document/images/mr_chamilo/doubts.png">
                        </td>
                        <td valign="top" align="left">'.get_lang('Irony').'</td></tr>
                    </table>';
        $exercise->type = 1;
        $exercise->setRandom(0);
        $exercise->active = 1;
        $exercise->results_disabled = 0;
        $exercise->description = $html;
        $exercise->save();

        $question = new MultipleAnswer();
        $question->course = $courseInfo;
        $question->question = get_lang('Socratic irony is...');
        $question->description = get_lang('(more than one answer can be true)');
        $question->weighting = 10;
        $question->position = 1;
        $question->course = $courseInfo;
        $question->save($exercise);
        $questionId = $question->id;

        $answer = new Answer($questionId, $courseInfo['real_id']);
        $answer->createAnswer(get_lang('Ridiculise one\'s interlocutor in order to have him concede he is wrong.'), 0, get_lang('No. Socratic irony is not a matter of psychology, it concerns argumentation.'), -5, 1);
        $answer->createAnswer(get_lang('Admit one\'s own errors to invite one\'s interlocutor to do the same.'), 0, get_lang('No. Socratic irony is not a seduction strategy or a method based on the example.'), -5, 2);
        $answer->createAnswer(get_lang('Compell one\'s interlocutor, by a series of questions and sub-questions, to admit he doesn\'t know what he claims to know.'), 1, get_lang('Indeed'), 5, 3);
        $answer->createAnswer(get_lang('Use the Principle of Non Contradiction to force one\'s interlocutor into a dead end.'), 1, get_lang('This answer is not false. It is true that the revelation of the interlocutor\'s ignorance means showing the contradictory conclusions where lead his premisses.'), 5, 4);
        $answer->save();
        // Forums.
        $params = [
            'forum_category_title' => get_lang('Example Forum Category'),
            'forum_category_comment' => '',
        ];

        $forumCategoryId = saveForumCategory($params, $courseInfo, false);

        $params = [
            'forum_category' => $forumCategoryId,
            'forum_title' => get_lang('Example Forum'),
            'forum_comment' => '',
            'default_view_type_group' => ['default_view_type' => 'flat'],
        ];

        $forumId = store_forum($params, $courseInfo, true);
        $repo = Container::getForumRepository();
        $forumEntity = $repo->find($forumId);

        $params = [
            'post_title' => get_lang('Example Thread'),
            'forum_id' => $forumId,
            'post_text' => get_lang('Example ThreadContent'),
            'calification_notebook_title' => '',
            'numeric_calification' => '',
            'weight_calification' => '',
            'forum_category' => $forumCategoryId,
            'thread_peer_qualify' => 0,
        ];

        saveThread($forumEntity, $params, $courseInfo, false);

        self::createExampleGradebookContent($course, $gradebook, $exercise->id);
    }

    /**
     * Creates the gradebook structure for a course.
     *
     * This method sets up the initial gradebook categories and links for a new course.
     * It creates a parent gradebook category representing the course itself and a child
     * gradebook category for course activities. It then creates a gradebook link associated
     * with a specific course activity, identified by the $refId parameter.
     *
     * @param Course $course The course entity for which the gradebook structure will be created.
     * @param int $refId  The reference ID of the course activity to link in the gradebook.
     *
     * @return void
     */
    private static function createExampleGradebookContent(Course $course, GradebookCategory $parentCategory, int $refId): void
    {
        $manager = Database::getManager();

        /* Gradebook tool */
        $courseCode = $course->getCode();

        $childGradebookCategory = new GradebookCategory();
        $childGradebookCategory->setTitle($courseCode);
        $childGradebookCategory->setLocked(0);
        $childGradebookCategory->setGenerateCertificates(false);
        $childGradebookCategory->setDescription('');
        $childGradebookCategory->setCourse($course);
        $childGradebookCategory->setWeight(100);
        $childGradebookCategory->setVisible(true);
        $childGradebookCategory->setCertifMinScore(75);
        $childGradebookCategory->setParent($parentCategory);
        $childGradebookCategory->setUser(api_get_user_entity());

        $manager->persist($childGradebookCategory);
        $manager->flush();

        $gradebookLink = new GradebookLink();

        $gradebookLink->setType(1);
        $gradebookLink->setRefId($refId);
        $gradebookLink->setCourse($course);
        $gradebookLink->setCategory($childGradebookCategory);
        $gradebookLink->setCreatedAt(new \DateTime());
        $gradebookLink->setWeight(100);
        $gradebookLink->setVisible(1);
        $gradebookLink->setLocked(0);

        $manager->persist($gradebookLink);
        $manager->flush();
    }

    /**
     * Creates the basic gradebook structure for a course.
     *
     * This method sets up the initial gradebook categories and links for a new course.
     * It creates a parent gradebook category representing the course itself.
     *
     * @param Course $course The course entity for which the gradebook structure will be created.
     *
     * @return GradebookCategory The created gradebook's ID
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private static function createRootGradebook(Course $course): GradebookCategory
    {
        $manager = Database::getManager();

        /* Gradebook tool */
        $courseCode = $course->getCode();

        $parentGradebookCategory = new GradebookCategory();
        $parentGradebookCategory->setTitle($courseCode);
        $parentGradebookCategory->setLocked(0);
        $parentGradebookCategory->setGenerateCertificates(false);
        $parentGradebookCategory->setDescription('');
        $parentGradebookCategory->setCourse($course);
        $parentGradebookCategory->setWeight(100);
        $parentGradebookCategory->setVisible(false);
        $parentGradebookCategory->setCertifMinScore(75);
        $parentGradebookCategory->setUser(api_get_user_entity());

        $manager->persist($parentGradebookCategory);
        $manager->flush();

        return $parentGradebookCategory;
    }

    /**
     * Installs plugins for a given course.
     *
     * This method takes a course ID and uses the AppPlugin service to install
     * all necessary or default plugins for that specific course. These plugins
     * can enhance the functionality of the course by adding new features or
     * tools that are not part of the core Chamilo platform.
     *
     * @param int $courseId The ID of the course for which the plugins will be installed.
     *
     * @return void
     */
    private static function installCoursePlugins(int $courseId): void
    {
        $app_plugin = new AppPlugin();
        $app_plugin->install_course_plugins($courseId);
    }

    /**
     * @param array $courseInfo
     * @param int   $counter
     * @param array $file
     * @param int   $authorId
     */
    public static function insertDocument($courseInfo, $counter, $file, $authorId = 0)
    {
        return DocumentManager::addDocument(
            $courseInfo,
            $file['path'],
            $file['filetype'],
            $file['size'],
            $file['title'],
            null,
            0,
            null,
            0,
            0,
            0,
            false
        );
    }

    /**
     * string2binary converts the string "true" or "false" to the boolean true false (0 or 1)
     * This is used for the Chamilo Config Settings as these store true or false as string
     * and the api_get_setting('course_create_active_tools') should be 0 or 1 (used for
     * the visibility of the tool).
     *
     * @param string $variable
     *
     * @return bool
     *
     * @author Patrick Cool, patrick.cool@ugent.be
     * @assert ('true') === true
     * @assert ('false') === false
     */
    public static function string2binary($variable)
    {
        if ('true' == $variable) {
            return true;
        }
        if ('false' == $variable) {
            return false;
        }
    }

    /**
     * Function register_course to create a record in the course table of the main database.
     *
     * @param array $params Course details (see code for details).
     *
     * @throws Exception
     *
     * @return Course|null
     */
    public static function register_course(array $params): ?Course
    {
        global $error_msg;
        $title = $params['title'];
        // Fix amp
        $title = str_replace('&amp;', '&', $title);
        $code = $params['code'];
        $visual_code = $params['visual_code'];
        $directory = $params['directory'];
        $tutor_name = $params['tutor_name'] ?? null;
        $course_language = !empty($params['course_language']) ? $params['course_language'] : api_get_setting(
            'platformLanguage'
        );
        $department_name = $params['department_name'] ?? null;
        $department_url = $params['department_url'] ?? null;
        $disk_quota = $params['disk_quota'] ?? null;

        if (!isset($params['visibility'])) {
            $default_course_visibility = api_get_setting('courses_default_creation_visibility');
            $visibility = $default_course_visibility ?? Course::OPEN_PLATFORM;
        } else {
            $visibility = $params['visibility'];
        }

        $subscribe = false;
        if (isset($params['subscribe'])) {
            $subscribe = 1 === (int) $params['subscribe'];
        } elseif (Course::OPEN_PLATFORM == $visibility) {
            $subscribe = true;
        }

        //$subscribe = isset($params['subscribe']) ? (int) $params['subscribe'] : COURSE_VISIBILITY_OPEN_PLATFORM == $visibility ? 1 : 0;
        $unsubscribe = isset($params['unsubscribe']) ? (int) $params['unsubscribe'] : 0;
        $expiration_date = $params['expiration_date'] ?? null;
        $teachers = $params['teachers'] ?? null;
        $categories = $params['course_categories'] ?? null;
        $valid = true;

        // Check whether all the needed parameters are present.
        if (empty($code)) {
            $error_msg[] = 'courseSysCode is missing';
            $valid = false;
        }
        if (empty($visual_code)) {
            $error_msg[] = 'courseScreenCode is missing';
            $valid = false;
        }
        if (empty($directory)) {
            $error_msg[] = 'courseRepository is missing';
            $valid = false;
        }

        if (empty($title)) {
            $error_msg[] = 'title is missing';
            $valid = false;
        }

        if (empty($expiration_date)) {
            $expiration_date = api_get_utc_datetime(
                time() + self::FIRST_EXPIRATION_DATE
            );
        } else {
            $expiration_date = api_get_utc_datetime($expiration_date);
        }

        if ($visibility < 0 || $visibility > 4) {
            $error_msg[] = 'visibility is invalid';
            $valid = false;
        }

        if (empty($disk_quota)) {
            $disk_quota = api_get_setting('default_document_quotum');
        }

        if (false === stripos($department_url, 'http://') && false === stripos($department_url, 'https://')) {
            $department_url = 'http://'.$department_url;
        }

        // just in case
        if ('http://' === $department_url) {
            $department_url = '';
        }

        $userId = empty($params['user_id']) ? api_get_user_id() : (int) $params['user_id'];
        $user = api_get_user_entity($userId);
        if (null === $user) {
            error_log(sprintf('user_id "%s" is invalid', $userId));

            return null;
        }

        $course = null;
        if ($valid) {
            $repo = Container::getCourseRepository();
            $categoryRepo = Container::getCourseCategoryRepository();

            $course = new Course();
            $course
                ->setTitle($title)
                ->setCode($code)
                ->setCourseLanguage($course_language)
                ->setDescription(get_lang('Course Description'))
                ->setVisibility($visibility)
                ->setShowScore(1)
                ->setDiskQuota($disk_quota)
                ->setExpirationDate(new DateTime($expiration_date))
                ->setDepartmentName((string) $department_name)
                ->setDepartmentUrl($department_url)
                ->setSubscribe($subscribe)
                ->setSticky(1 === (int) ($params['sticky'] ?? 0))
                ->setVideoUrl($params['video_url'] ?? '')
                ->setUnsubscribe($unsubscribe)
                ->setVisualCode($visual_code)
                ->setCreator(api_get_user_entity())
            ;

            if (isset($params['duration'])) {
                $course->setDuration($params['duration']);
            }

            if (!empty($categories)) {
                if (!is_array($categories)) {
                    $categories = [$categories];
                }

                foreach ($categories as $key) {
                    if (empty($key)) {
                        continue;
                    }

                    $category = $categoryRepo->find($key);
                    if (null !== $category) {
                        $course->addCategory($category);
                    }
                }
            }

            $sort = api_max_sort_value('0', api_get_user_id());
            // Default true
            $addTeacher = $params['add_user_as_teacher'] ?? true;
            if ($addTeacher) {
                $iCourseSort = CourseManager::userCourseSort($userId, $code);
                $courseRelTutor = (new CourseRelUser())
                    ->setCourse($course)
                    ->setUser($user)
                    ->setStatus(1)
                    ->setTutor(true)
                    ->setSort($iCourseSort)
                    ->setRelationType(0)
                    ->setUserCourseCat(0)
                ;
                $course->addSubscription($courseRelTutor);
            }

            if (!empty($teachers)) {
                $sort = $user->getMaxSortValue();
                if (!is_array($teachers)) {
                    $teachers = [$teachers];
                }
                foreach ($teachers as $key) {
                    // Just in case.
                    if ($key == $userId) {
                        continue;
                    }
                    if (empty($key)) {
                        continue;
                    }
                    $teacher = api_get_user_entity($key);
                    if (is_null($teacher)) {
                        continue;
                    }
                    $courseRelTeacher = (new CourseRelUser())
                        ->setCourse($course)
                        ->setUser($teacher)
                        ->setStatus(1)
                        ->setTutor(false)
                        ->setSort($sort + 1)
                        ->setRelationType(0)
                        ->setUserCourseCat(0)
                    ;
                    $course->addSubscription($courseRelTeacher);
                }
            }

            $repo->create($course);

            $course_id = $course->getId();
            if ($course_id) {
                // Add event to the system log.
                Event::addEvent(
                    LOG_COURSE_CREATE,
                    LOG_COURSE_CODE,
                    $code,
                    api_get_utc_datetime(),
                    $userId,
                    $course_id
                );

                $send_mail_to_admin = api_get_setting('send_email_to_admin_when_create_course');

                // @todo Improve code to send to all current portal administrators.
                if ('true' === $send_mail_to_admin) {
                    $siteName = api_get_setting('siteName');
                    $recipient_email = api_get_setting('emailAdministrator');
                    $recipient_name = api_get_person_name(
                        api_get_setting('administratorName'),
                        api_get_setting('administratorSurname')
                    );
                    $iname = api_get_setting('Institution');
                    $subject = get_lang('NewCourseCreatedIn').' '.$siteName.' - '.$iname;
                    $message = get_lang('Dear').' '.$recipient_name.",\n\n".
                        get_lang('MessageOfNewCourseToAdmin').' '.$siteName.' - '.$iname."\n";
                    $message .= get_lang('Course name').' '.$title."\n";

                    if ($course->getCategories()->count() > 0) {
                        foreach ($course->getCategories() as $category) {
                            $message .= get_lang('Category').': '.$category->getCode()."\n";
                        }
                    }
                    $message .= get_lang('Coach').' '.$tutor_name."\n";
                    $message .= get_lang('Language').' '.$course_language;

                    api_mail_html(
                        $recipient_name,
                        $recipient_email,
                        $subject,
                        $message,
                        $siteName,
                        $recipient_email,
                        [],
                        [],
                        false
                    );
                }
            }
        }

        return $course;
    }

    /**
     * Generate a new id for c_tool table.
     *
     * @param int $courseId The course id
     *
     * @return int the new id
     */
    public static function generateToolId($courseId)
    {
        $newIdResultData = Database::select(
            'id + 1 AS new_id',
            Database::get_course_table(TABLE_TOOL_LIST),
            [
                'where' => ['c_id = ?' => intval($courseId)],
                'order' => 'id',
                'limit' => 1,
            ],
            'first'
        );

        if (false === $newIdResultData) {
            return 1;
        }

        return $newIdResultData['new_id'] > 0 ? $newIdResultData['new_id'] : 1;
    }
}
