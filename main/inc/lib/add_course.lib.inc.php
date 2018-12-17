<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
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

        if ($add_unique_prefix) {
            $unique_prefix = substr(md5(uniqid(rand())), 0, 10);
        } else {
            $unique_prefix = '';
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
            if (file_exists(api_get_path(SYS_COURSE_PATH).$keys_course_repository)) {
                $keys_are_unique = false;
                $try_new_fsc_dir++;
                $final_suffix['CourseDir'] = substr(md5(uniqid(rand())), 0, 4);
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
     * Initializes a file repository for a newly created course.
     *
     * @param string Course repository
     * @param string Course code
     *
     * @return int
     * @assert (null,null) === false
     */
    public static function prepare_course_repository($course_repository)
    {
        $perm = api_get_permissions_for_new_directories();
        $perm_file = api_get_permissions_for_new_files();
        $htmlpage = "<!DOCTYPE html>\n<html lang=\"en\">\n  <head>\n    <meta charset=\"utf-8\">\n    <title>Not authorized</title>\n  </head>\n  <body>\n  </body>\n</html>";
        $cp = api_get_path(SYS_COURSE_PATH).$course_repository;

        //Creating document folder
        mkdir($cp, $perm);
        mkdir($cp.'/document', $perm);
        $cpt = $cp.'/document/index.html';
        $fd = fopen($cpt, 'w');
        fwrite($fd, $htmlpage);
        fclose($fd);

        /*
        @chmod($cpt, $perm_file);
        @copy($cpt, $cp . '/document/index.html');
        mkdir($cp . '/document/images', $perm);
        @copy($cpt, $cp . '/document/images/index.html');
        mkdir($cp . '/document/images/gallery/', $perm);
        @copy($cpt, $cp . '/document/images/gallery/index.html');
        mkdir($cp . '/document/shared_folder/', $perm);
        @copy($cpt, $cp . '/document/shared_folder/index.html');
        mkdir($cp . '/document/audio', $perm);
        @copy($cpt, $cp . '/document/audio/index.html');
        mkdir($cp . '/document/flash', $perm);
        @copy($cpt, $cp . '/document/flash/index.html');
        mkdir($cp . '/document/video', $perm);
        @copy($cpt, $cp . '/document/video/index.html');    */

        //Creatind dropbox folder
        mkdir($cp.'/dropbox', $perm);
        $cpt = $cp.'/dropbox/index.html';
        $fd = fopen($cpt, 'w');
        fwrite($fd, $htmlpage);
        fclose($fd);
        @chmod($cpt, $perm_file);
        mkdir($cp.'/group', $perm);
        @copy($cpt, $cp.'/group/index.html');
        mkdir($cp.'/page', $perm);
        @copy($cpt, $cp.'/page/index.html');
        mkdir($cp.'/scorm', $perm);
        @copy($cpt, $cp.'/scorm/index.html');
        mkdir($cp.'/upload', $perm);
        @copy($cpt, $cp.'/upload/index.html');
        mkdir($cp.'/upload/forum', $perm);
        @copy($cpt, $cp.'/upload/forum/index.html');
        mkdir($cp.'/upload/forum/images', $perm);
        @copy($cpt, $cp.'/upload/forum/images/index.html');
        mkdir($cp.'/upload/test', $perm);
        @copy($cpt, $cp.'/upload/test/index.html');
        mkdir($cp.'/upload/blog', $perm);
        @copy($cpt, $cp.'/upload/blog/index.html');
        mkdir($cp.'/upload/learning_path', $perm);
        @copy($cpt, $cp.'/upload/learning_path/index.html');
        mkdir($cp.'/upload/learning_path/images', $perm);
        @copy($cpt, $cp.'/upload/learning_path/images/index.html');
        mkdir($cp.'/upload/calendar', $perm);
        @copy($cpt, $cp.'/upload/calendar/index.html');
        mkdir($cp.'/upload/calendar/images', $perm);
        @copy($cpt, $cp.'/upload/calendar/images/index.html');
        mkdir($cp.'/work', $perm);
        @copy($cpt, $cp.'/work/index.html');
        mkdir($cp.'/upload/announcements', $perm);
        @copy($cpt, $cp.'/upload/announcements/index.html');
        mkdir($cp.'/upload/announcements/images', $perm);
        @copy($cpt, $cp.'/upload/announcements/images/index.html');

        //Oral expression question type
        mkdir($cp.'/exercises', $perm);
        @copy($cpt, $cp.'/exercises/index.html');

        // Create .htaccess in the dropbox directory.
        $fp = fopen($cp.'/dropbox/.htaccess', 'w');
        fwrite(
            $fp,
            "AuthName AllowLocalAccess
                       AuthType Basic

                       order deny,allow
                       deny from all

                       php_flag zlib.output_compression off"
        );
        fclose($fp);

        // Build index.php of the course.
        /*$fd = fopen($cp . '/index.php', 'w');

        // str_replace() removes \r that cause squares to appear at the end of each line
        //@todo fix the harcoded include
        $string = str_replace(
            "\r",
            "",
            "<?" . "php
        \$cidReq = \"$course_code\";
        \$dbname = \"$course_code\";

        include(\"" . api_get_path(SYS_CODE_PATH) . "course_home/course_home.php\");
        ?>"
        );
        fwrite($fd, $string);
        @chmod($cp . '/index.php', $perm_file);*/
        return 0;
    }

    /**
     * Gets an array with all the course tables (deprecated?).
     *
     * @return string[]
     * @assert (null) !== null
     */
    public static function get_course_tables()
    {
        $tables = [];
        $tables[] = 'item_property';
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
        $tables[] = 'resource';
        $tables[] = 'student_publication';
        $tables[] = 'student_publication_assignment';
        $tables[] = 'document';
        $tables[] = 'forum_post';
        $tables[] = 'forum_thread';
        $tables[] = 'forum_mailcue';
        $tables[] = 'forum_attachment';
        $tables[] = 'forum_notification';
        $tables[] = 'forum_thread_qualify';
        $tables[] = 'forum_thread_qualify_log';
        $tables[] = 'forum_forum';
        $tables[] = 'forum_category';
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
            if (isset($value[$type]) && $value[$type] != '') {
                $pictures[][$type] = $value[$type];
            }
        }

        return $pictures;
    }

    /**
     * Fills the course database with some required content and example content.
     *
     * @param array $courseInfo
     * @param bool Whether to fill the course with example content
     * @param int $authorId
     *
     * @return bool False on error, true otherwise
     *
     * @version 1.2
     * @assert (null, '', '', null) === false
     * @assert (1, 'ABC', null, null) === false
     * @assert (1, 'TEST', 'spanish', true) === true
     */
    public static function fillCourse(
        $courseInfo,
        $fill_with_exemplary_content = null,
        $authorId = 0
    ) {
        if (is_null($fill_with_exemplary_content)) {
            $fill_with_exemplary_content = api_get_setting('example_material_course_creation') !== 'false';
        }

        $course_id = (int) $courseInfo['real_id'];

        if (empty($courseInfo)) {
            return false;
        }
        $authorId = empty($authorId) ? api_get_user_id() : (int) $authorId;

        $TABLEGROUPCATEGORIES = Database::get_course_table(TABLE_GROUP_CATEGORY);
        $TABLESETTING = Database::get_course_table(TABLE_COURSE_SETTING);
        $TABLEGRADEBOOK = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
        $TABLEGRADEBOOKLINK = Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
        $visible_for_course_admin = 0;
        $em = Database::getManager();
        $course = api_get_course_entity($course_id);
        $settingsManager = CourseManager::getCourseSettingsManager();
        $settingsManager->setCourse($course);

        $alert = api_get_setting('email_alert_manager_on_new_quiz');
        $defaultEmailExerciseAlert = 0;
        if ($alert === 'true') {
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
                'default' => api_get_setting('allow_public_certificates') === 'true' ? 1 : '',
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
                "INSERT INTO $TABLESETTING (id, c_id, title, variable, value, category)
                      VALUES ($counter, $course_id, '".$title."', '".$variable."', '".$setting['default']."', '".$setting['category']."')"
            );
            $counter++;
        }

        /* Course homepage tools for platform admin only */
        /* Group tool */
        Database::insert(
            $TABLEGROUPCATEGORIES,
            [
                'c_id' => $course_id,
                'id' => 2,
                'title' => get_lang('DefaultGroupCategory'),
                'description' => '',
                'max_student' => 8,
                'self_reg_allowed' => 0,
                'self_unreg_allowed' => 0,
                'groups_per_user' => 0,
                'display_order' => 0,
                'doc_state' => 1,
                'calendar_state' => 1,
                'work_state' => 1,
                'announcements_state' => 1,
                'forum_state' => 1,
                'wiki_state' => 1,
                'chat_state' => 1,
            ]
        );

        $now = api_get_utc_datetime();

        $files = [
            ['path' => '/shared_folder', 'title' => get_lang('UserFolders'), 'filetype' => 'folder', 'size' => 0],
            ['path' => '/chat_files', 'title' => get_lang('ChatFiles'), 'filetype' => 'folder', 'size' => 0],
        ];

        $counter = 1;
        foreach ($files as $file) {
            self::insertDocument($courseInfo, $counter, $file, $authorId);
            $counter++;
        }

        $certificateId = 'NULL';

        /*    Documents   */
        if ($fill_with_exemplary_content) {
            $files = [
                ['path' => '/images', 'title' => get_lang('Images'), 'filetype' => 'folder', 'size' => 0],
                ['path' => '/images/gallery', 'title' => get_lang('DefaultCourseImages'), 'filetype' => 'folder', 'size' => 0],
                ['path' => '/audio', 'title' => get_lang('Audio'), 'filetype' => 'folder', 'size' => 0],
                ['path' => '/flash', 'title' => get_lang('Flash'), 'filetype' => 'folder', 'size' => 0],
                ['path' => '/video', 'title' => get_lang('Video'), 'filetype' => 'folder', 'size' => 0],
                ['path' => '/certificates', 'title' => get_lang('Certificates'), 'filetype' => 'folder', 'size' => 0],
            ];

            foreach ($files as $file) {
                self::insertDocument($courseInfo, $counter, $file, $authorId);
                $counter++;
            }

            $finder = new Symfony\Component\Finder\Finder();
            $defaultPath = api_get_path(SYS_PUBLIC_PATH).'img/document';
            $finder->in($defaultPath);
            /** @var SplFileInfo $file */
            foreach ($finder as $file) {
                $path = str_replace($defaultPath, '', $file->getRealPath());
                $parentName = dirname(str_replace($defaultPath, '', $file->getRealPath()));
                $title = $file->getFilename();
                if ($file->isDir()) {
                    create_unexisting_directory(
                        $courseInfo,
                        api_get_user_id(),
                        0,
                        0,
                        0,
                        $path,
                        $path,
                        $title
                    );
                } else {
                    $parent = DocumentManager::getDocumentByPathInCourse($courseInfo, $parentName);
                    $parentId = 0;
                    if (!empty($parent)) {
                        $parent = $parent[0];
                        $parentId = $parent['iid'];
                    }

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

                    if ($document && $document->getTitle() === 'default.html') {
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
                get_lang('AgendaCreationTitle'),
                get_lang('AgendaCreationContenu')
            );

            /*  Links tool */
            $link = new Link();
            $link->setCourse($courseInfo);
            $links = [
                [
                    'c_id' => $course_id,
                    'url' => 'http://www.google.com',
                    'title' => 'Google',
                    'description' => get_lang('Google'),
                    'category_id' => 0,
                    'on_homepage' => 0,
                    'target' => '_self',
                    'session_id' => 0,
                ],
                [
                    'c_id' => $course_id,
                    'url' => 'http://www.wikipedia.org',
                    'title' => 'Wikipedia',
                    'description' => get_lang('Wikipedia'),
                    'category_id' => 0,
                    'on_homepage' => 0,
                    'target' => '_self',
                    'session_id' => 0,
                ],
            ];

            foreach ($links as $params) {
                $link->save($params);
            }

            /* Announcement tool */
            AnnouncementManager::add_announcement(
                $courseInfo,
                0,
                get_lang('AnnouncementExampleTitle'),
                get_lang('AnnouncementEx'),
                ['everyone' => 'everyone'],
                null,
                null,
                $now
            );

            $manager = Database::getManager();

            /* Introduction text */
            $intro_text = '<p style="text-align: center;">
                            <img src="'.api_get_path(REL_CODE_PATH).'img/mascot.png" alt="Mr. Chamilo" title="Mr. Chamilo" />
                            <h2>'.get_lang('IntroductionText').'</h2>
                         </p>';

            $toolIntro = new CToolIntro();
            $toolIntro
                ->setCId($course_id)
                ->setId(TOOL_COURSE_HOMEPAGE)
                ->setSessionId(0)
                ->setIntroText($intro_text);
            $manager->persist($toolIntro);

            $toolIntro = new CToolIntro();
            $toolIntro
                ->setCId($course_id)
                ->setId(TOOL_STUDENTPUBLICATION)
                ->setSessionId(0)
                ->setIntroText(get_lang('IntroductionTwo'));
            $manager->persist($toolIntro);

            $toolIntro = new CToolIntro();
            $toolIntro
                ->setCId($course_id)
                ->setId(TOOL_WIKI)
                ->setSessionId(0)
                ->setIntroText(get_lang('IntroductionWiki'));
            $manager->persist($toolIntro);

            $manager->flush();

            /*  Exercise tool */
            $exercise = new Exercise($course_id);
            $exercise->exercise = get_lang('ExerciceEx');
            $html = '<table width="100%" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                        <td width="220" valign="top" align="left">
                            <img src="'.api_get_path(WEB_PUBLIC_PATH).'img/document/images/mr_chamilo/doubts.png">
                        </td>
                        <td valign="top" align="left">'.get_lang('Antique').'</td></tr>
                    </table>';
            $exercise->type = 1;
            $exercise->setRandom(0);
            $exercise->active = 1;
            $exercise->results_disabled = 0;
            $exercise->description = $html;
            $exercise->save();

            $exercise_id = $exercise->id;

            $question = new MultipleAnswer();
            $question->question = get_lang('SocraticIrony');
            $question->description = get_lang('ManyAnswers');
            $question->weighting = 10;
            $question->position = 1;
            $question->course = $courseInfo;
            $question->save($exercise);
            $questionId = $question->id;

            $answer = new Answer($questionId, $courseInfo['real_id']);

            $answer->createAnswer(get_lang('Ridiculise'), 0, get_lang('NoPsychology'), -5, 1);
            $answer->createAnswer(get_lang('AdmitError'), 0, get_lang('NoSeduction'), -5, 2);
            $answer->createAnswer(get_lang('Force'), 1, get_lang('Indeed'), 5, 3);
            $answer->createAnswer(get_lang('Contradiction'), 1, get_lang('NotFalse'), 5, 4);
            $answer->save();

            /* Forum tool */

            require_once api_get_path(SYS_CODE_PATH).'forum/forumfunction.inc.php';

            $params = [
                'forum_category_title' => get_lang('ExampleForumCategory'),
                'forum_category_comment' => '',
            ];

            $forumCategoryId = store_forumcategory($params, $courseInfo, false);

            $params = [
                'forum_category' => $forumCategoryId,
                'forum_title' => get_lang('ExampleForum'),
                'forum_comment' => '',
                'default_view_type_group' => ['default_view_type' => 'flat'],
            ];

            $forumId = store_forum($params, $courseInfo, true);

            $forumInfo = get_forum_information($forumId, $courseInfo['real_id']);

            $params = [
                'post_title' => get_lang('ExampleThread'),
                'forum_id' => $forumId,
                'post_text' => get_lang('ExampleThreadContent'),
                'calification_notebook_title' => '',
                'numeric_calification' => '',
                'weight_calification' => '',
                'forum_category' => $forumCategoryId,
                'thread_peer_qualify' => 0,
            ];

            store_thread($forumInfo, $params, $courseInfo, false);

            /* Gradebook tool */
            $course_code = $courseInfo['code'];
            // father gradebook
            Database::query(
                "INSERT INTO $TABLEGRADEBOOK (name, locked, generate_certificates, description, user_id, c_id, parent_id, weight, visible, certif_min_score, session_id, document_id)
                VALUES ('$course_code','0',0,'',1,$course_id,0,100,0,75,NULL,$certificateId)"
            );
            $gbid = Database:: insert_id();
            Database::query(
                "INSERT INTO $TABLEGRADEBOOK (name, locked, generate_certificates, description, user_id, c_id, parent_id, weight, visible, certif_min_score, session_id, document_id)
                VALUES ('$course_code','0',0,'',1,$course_id,$gbid,100,1,75,NULL,$certificateId)"
            );
            $gbid = Database:: insert_id();
            Database::query(
                "INSERT INTO $TABLEGRADEBOOKLINK (type, ref_id, user_id, c_id, category_id, created_at, weight, visible, locked)
                VALUES (1,$exercise_id,1,$course_id,$gbid,'$now',100,1,0)"
            );
        }

        // Installing plugins in course
        $app_plugin = new AppPlugin();
        $app_plugin->install_course_plugins($course_id);

        return true;
    }

    /**
     * @param array $courseInfo
     * @param int   $counter
     * @param array $file
     * @param int   $authorId
     */
    public static function insertDocument($courseInfo, $counter, $file, $authorId = 0)
    {
        DocumentManager::addDocument(
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
        if ($variable == 'true') {
            return true;
        }
        if ($variable == 'false') {
            return false;
        }
    }

    /**
     * Function register_course to create a record in the course table of the main database.
     *
     * @param array Course details (see code for details)
     *
     * @return int Created course ID
     *
     * @todo use an array called $params instead of lots of params
     * @assert (null) === false
     */
    public static function register_course($params)
    {
        global $error_msg;
        $title = $params['title'];
        // Fix amp
        $title = str_replace('&amp;', '&', $title);
        $code = $params['code'];
        $visual_code = $params['visual_code'];
        $directory = $params['directory'];
        $tutor_name = isset($params['tutor_name']) ? $params['tutor_name'] : null;
        $category_code = isset($params['course_category']) ? $params['course_category'] : '';
        $course_language = isset($params['course_language']) && !empty($params['course_language']) ? $params['course_language'] : api_get_setting(
            'platformLanguage'
        );
        $user_id = empty($params['user_id']) ? api_get_user_id() : intval($params['user_id']);
        $department_name = isset($params['department_name']) ? $params['department_name'] : null;
        $department_url = isset($params['department_url']) ? $params['department_url'] : null;
        $disk_quota = isset($params['disk_quota']) ? $params['disk_quota'] : null;

        if (!isset($params['visibility'])) {
            $default_course_visibility = api_get_setting(
                'courses_default_creation_visibility'
            );
            if (isset($default_course_visibility)) {
                $visibility = $default_course_visibility;
            } else {
                $visibility = COURSE_VISIBILITY_OPEN_PLATFORM;
            }
        } else {
            $visibility = $params['visibility'];
        }

        $subscribe = isset($params['subscribe']) ? (int) $params['subscribe'] : $visibility == COURSE_VISIBILITY_OPEN_PLATFORM ? 1 : 0;
        $unsubscribe = isset($params['unsubscribe']) ? (int) $params['unsubscribe'] : 0;
        $expiration_date = isset($params['expiration_date']) ? $params['expiration_date'] : null;
        $teachers = isset($params['teachers']) ? $params['teachers'] : null;
        $status = isset($params['status']) ? $params['status'] : null;

        $TABLECOURSUSER = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $ok_to_register_course = true;

        // Check whether all the needed parameters are present.
        if (empty($code)) {
            $error_msg[] = 'courseSysCode is missing';
            $ok_to_register_course = false;
        }
        if (empty($visual_code)) {
            $error_msg[] = 'courseScreenCode is missing';
            $ok_to_register_course = false;
        }
        if (empty($directory)) {
            $error_msg[] = 'courseRepository is missing';
            $ok_to_register_course = false;
        }

        if (empty($title)) {
            $error_msg[] = 'title is missing';
            $ok_to_register_course = false;
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
            $ok_to_register_course = false;
        }

        if (empty($disk_quota)) {
            $disk_quota = api_get_setting('default_document_quotum');
        }

        if (stripos($department_url, 'http://') === false && stripos(
                $department_url,
                'https://'
            ) === false
        ) {
            $department_url = 'http://'.$department_url;
        }

        // just in case
        if ($department_url == 'http://') {
            $department_url = '';
        }
        $course_id = 0;

        if ($ok_to_register_course) {
            $courseManager = Container::$container->get('chamilo_core.entity.manager.course_manager');
            /** @var \Chamilo\CoreBundle\Entity\Course $course */
            $course = $courseManager->create();
            $urlId = 1;
            if (api_get_current_access_url_id() !== -1) {
                $urlId = api_get_current_access_url_id();
            }

            $url = api_get_url_entity($urlId);
            $course
                ->setCode($code)
                ->setDirectory($directory)
                ->setCourseLanguage($course_language)
                ->setTitle($title)
                ->setDescription(get_lang('CourseDescription'))
                ->setCategoryCode($category_code)
                ->setVisibility($visibility)
                ->setShowScore(1)
                ->setDiskQuota($disk_quota)
                ->setCreationDate(new \DateTime())
                ->setExpirationDate(new \DateTime($expiration_date))
                //->setLastEdit()
                ->setDepartmentName($department_name)
                ->setDepartmentUrl($department_url)
                ->setSubscribe($subscribe)
                ->setUnsubscribe($unsubscribe)
                ->setVisualCode($visual_code)
                ->addUrl($url)
            ;

            $courseManager->save($course, true);
            $course_id = $course->getId();

            if ($course_id) {
                $sort = api_max_sort_value('0', api_get_user_id());
                // Default true
                $addTeacher = isset($params['add_user_as_teacher']) ? $params['add_user_as_teacher'] : true;
                if ($addTeacher) {
                    $i_course_sort = CourseManager::userCourseSort(
                        $user_id,
                        $code
                    );
                    if (!empty($user_id)) {
                        $sql = "INSERT INTO ".$TABLECOURSUSER." SET
                                c_id     = '".$course_id."',
                                user_id         = '".intval($user_id)."',
                                status          = '1',
                                is_tutor        = '0',
                                sort            = '".($i_course_sort)."',
                                relation_type = 0,
                                user_course_cat = '0'";
                        Database::query($sql);
                    }
                }

                if (!empty($teachers)) {
                    if (!is_array($teachers)) {
                        $teachers = [$teachers];
                    }
                    foreach ($teachers as $key) {
                        //just in case
                        if ($key == $user_id) {
                            continue;
                        }
                        if (empty($key)) {
                            continue;
                        }
                        $sql = "INSERT INTO ".$TABLECOURSUSER." SET
                            c_id     = '".Database::escape_string($course_id)."',
                            user_id         = '".Database::escape_string($key)."',
                            status          = '1',
                            is_tutor        = '0',
                            sort            = '".($sort + 1)."',
                            relation_type = 0,
                            user_course_cat = '0'";
                        Database::query($sql);
                    }
                }

                // Adding the course to an URL.
                // Already added by when saving the entity
                /*if (api_is_multiple_url_enabled()) {
                    $url_id = 1;
                    if (api_get_current_access_url_id() != -1) {
                        $url_id = api_get_current_access_url_id();
                    }
                    UrlManager::add_course_to_url($course_id, $url_id);
                } else {
                    UrlManager::add_course_to_url($course_id, 1);
                }*/

                // Add event to the system log.
                $user_id = api_get_user_id();
                Event::addEvent(
                    LOG_COURSE_CREATE,
                    LOG_COURSE_CODE,
                    $code,
                    api_get_utc_datetime(),
                    $user_id,
                    $course_id
                );

                $send_mail_to_admin = api_get_setting('send_email_to_admin_when_create_course');

                // @todo Improve code to send to all current portal administrators.
                if ($send_mail_to_admin === 'true') {
                    $siteName = api_get_setting('siteName');
                    $recipient_email = api_get_setting('emailAdministrator');
                    $recipient_name = api_get_person_name(
                        api_get_setting('administratorName'),
                        api_get_setting('administratorSurname')
                    );
                    $iname = api_get_setting('Institution');
                    $subject = get_lang(
                            'NewCourseCreatedIn'
                        ).' '.$siteName.' - '.$iname;
                    $message = get_lang(
                            'Dear'
                        ).' '.$recipient_name.",\n\n".get_lang(
                            'MessageOfNewCourseToAdmin'
                        ).' '.$siteName.' - '.$iname."\n";
                    $message .= get_lang('CourseName').' '.$title."\n";
                    $message .= get_lang(
                            'Category'
                        ).' '.$category_code."\n";
                    $message .= get_lang('Tutor').' '.$tutor_name."\n";
                    $message .= get_lang('Language').' '.$course_language;

                    $userInfo = api_get_user_info($user_id);

                    $additionalParameters = [
                        'smsType' => SmsPlugin::NEW_COURSE_BEEN_CREATED,
                        'userId' => $user_id,
                        'courseName' => $title,
                        'creatorUsername' => $userInfo['username'],
                    ];

                    api_mail_html(
                        $recipient_name,
                        $recipient_email,
                        $subject,
                        $message,
                        $siteName,
                        $recipient_email,
                        null,
                        null,
                        null,
                        $additionalParameters
                    );
                }
            }
        }

        return $course_id;
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

        if ($newIdResultData === false) {
            return 1;
        }

        return $newIdResultData['new_id'] > 0 ? $newIdResultData['new_id'] : 1;
    }
}
