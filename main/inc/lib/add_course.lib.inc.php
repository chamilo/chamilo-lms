<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\AccessUrlRelCourse;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CourseBundle\Entity\CToolIntro;
use Doctrine\ORM\OptimisticLockException;

/**
 * Class AddCourse.
 */
class AddCourse
{
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
     * @return array An array with the needed keys ['currentCourseCode'], ['currentCourseId'], ['currentCourseDbName'], ['currentCourseRepository']
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
        $tables[] = 'forum_category';
        $tables[] = 'forum_forum';
        $tables[] = 'forum_thread';
        $tables[] = 'forum_post';
        $tables[] = 'forum_mailcue';
        $tables[] = 'forum_attachment';
        $tables[] = 'forum_notification';
        $tables[] = 'forum_thread_qualify';
        $tables[] = 'forum_thread_qualify_log';
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
     * Returns a list of all files in the given course directory. The requested
     * directory will be checked against a "checker" directory to avoid access to
     * protected/unauthorized files.
     *
     * @param string Complete path to directory we want to list
     * @param array A list of files to which we want to add the files found
     * @param string Type of base directory from which we want to recover the files
     * @param string $path
     * @param string $media
     *
     * @return array
     * @assert (null,null,null) === false
     * @assert ('abc',array(),'') === array()
     */
    public static function browse_folders($path, $files, $media)
    {
        if ($media == 'images') {
            $code_path = api_get_path(SYS_CODE_PATH).'default_course_document/images/';
        }
        if ($media == 'audio') {
            $code_path = api_get_path(SYS_CODE_PATH).'default_course_document/audio/';
        }
        if ($media == 'flash') {
            $code_path = api_get_path(SYS_CODE_PATH).'default_course_document/flash/';
        }
        if ($media == 'video') {
            $code_path = api_get_path(SYS_CODE_PATH).'default_course_document/video/';
        }
        if ($media == 'certificates') {
            $code_path = api_get_path(SYS_CODE_PATH).'default_course_document/certificates/';
        }
        if (is_dir($path)) {
            $handle = opendir($path);
            while (false !== ($file = readdir($handle))) {
                if (is_dir($path.$file) && strpos($file, '.') !== 0) {
                    $files[]['dir'] = str_replace(
                        $code_path,
                        '',
                        $path.$file.'/'
                    );
                    $files = self::browse_folders(
                        $path.$file.'/',
                        $files,
                        $media
                    );
                } elseif (is_file($path.$file) && strpos($file, '.') !== 0) {
                    $files[]['file'] = str_replace(
                        $code_path,
                        '',
                        $path.$file
                    );
                }
            }
        }

        return $files;
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
     * @param int Course (int) ID
     * @param string Course directory name (e.g. 'ABC')
     * @param string Language used for content (e.g. 'spanish')
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
    public static function fill_db_course(
        $course_id,
        $course_repository,
        $language,
        $fill_with_exemplary_content = null,
        $authorId = 0
    ) {
        if (is_null($fill_with_exemplary_content)) {
            $fill_with_exemplary_content = api_get_setting('example_material_course_creation') !== 'false';
        }
        $course_id = (int) $course_id;

        if (empty($course_id)) {
            return false;
        }

        $courseInfo = api_get_course_info_by_id($course_id);
        $authorId = empty($authorId) ? api_get_user_id() : (int) $authorId;

        $TABLEGROUPCATEGORIES = Database::get_course_table(TABLE_GROUP_CATEGORY);
        $TABLEITEMPROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $TABLETOOLDOCUMENT = Database::get_course_table(TABLE_DOCUMENT);
        $TABLEGRADEBOOK = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
        $TABLEGRADEBOOKLINK = Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK);

        $alert = api_get_setting('email_alert_manager_on_new_quiz');
        if ($alert === 'true') {
            $defaultEmailExerciseAlert = 1;
        } else {
            $defaultEmailExerciseAlert = 0;
        }

        /* Group tool */
        Database::insert(
            $TABLEGROUPCATEGORIES,
            [
                'c_id' => $course_id,
                'id' => 2,
                'title' => get_lang('DefaultGroupCategory'),
                'description' => '',
                'max_student' => 0,
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
            ['path' => '/certificates', 'title' => get_lang('CertificatesFiles'), 'filetype' => 'folder', 'size' => 0],
        ];

        $counter = 1;
        foreach ($files as $file) {
            self::insertDocument($course_id, $counter, $file, $authorId);
            $counter++;
        }

        $sys_course_path = api_get_path(SYS_COURSE_PATH);
        $perm = api_get_permissions_for_new_directories();
        $perm_file = api_get_permissions_for_new_files();
        $chat_path = $sys_course_path.$course_repository.'/document/chat_files';

        if (!is_dir($chat_path)) {
            @mkdir($chat_path, api_get_permissions_for_new_directories());
        }

        /*    Documents   */
        if ($fill_with_exemplary_content) {
            $files = [
                ['path' => '/images', 'title' => get_lang('Images'), 'filetype' => 'folder', 'size' => 0],
                ['path' => '/images/gallery', 'title' => get_lang('DefaultCourseImages'), 'filetype' => 'folder', 'size' => 0],
                ['path' => '/audio', 'title' => get_lang('Audio'), 'filetype' => 'folder', 'size' => 0],
                ['path' => '/flash', 'title' => get_lang('Flash'), 'filetype' => 'folder', 'size' => 0],
                ['path' => '/video', 'title' => get_lang('Video'), 'filetype' => 'folder', 'size' => 0],
            ];

            foreach ($files as $file) {
                self::insertDocument($course_id, $counter, $file, $authorId);
                $counter++;
            }

            // FILL THE COURSE DOCUMENT WITH DEFAULT COURSE PICTURES
            $folders_to_copy_from_default_course = [
                'images',
                'audio',
                'flash',
                'video',
            ];

            $default_course_path = api_get_path(SYS_CODE_PATH).'default_course_document/';

            $default_document_array = [];
            foreach ($folders_to_copy_from_default_course as $folder) {
                $default_course_folder_path = $default_course_path.$folder.'/';
                $files = self::browse_folders(
                    $default_course_folder_path,
                    [],
                    $folder
                );

                $sorted_array = self::sort_pictures($files, 'dir');
                $sorted_array = array_merge(
                    $sorted_array,
                    self::sort_pictures($files, 'file')
                );
                $default_document_array[$folder] = $sorted_array;
            }

            // Light protection (adding index.html in every document folder)
            $htmlpage = "<!DOCTYPE html>\n<html lang=\"en\">\n <head>\n <meta charset=\"utf-8\">\n <title>Not authorized</title>\n  </head>\n  <body>\n  </body>\n</html>";

            $example_cert_id = 0;
            if (is_array($default_document_array) && count(
                    $default_document_array
                ) > 0
            ) {
                foreach ($default_document_array as $media_type => $array_media) {
                    $path_documents = "/$media_type/";

                    //hack until feature #5242 is implemented
                    if ($media_type === 'images') {
                        $media_type = 'images/gallery';
                        $images_folder = $sys_course_path.$course_repository."/document/images/";

                        if (!is_dir($images_folder)) {
                            //Creating index.html
                            mkdir($images_folder, $perm);
                            $fd = fopen($images_folder.'index.html', 'w');
                            fwrite($fd, $htmlpage);
                            @chmod($images_folder.'index.html', $perm_file);
                        }
                    }

                    $course_documents_folder = $sys_course_path.$course_repository."/document/$media_type/";
                    $default_course_path = api_get_path(SYS_CODE_PATH).'default_course_document'.$path_documents;

                    if (!is_dir($course_documents_folder)) {
                        // Creating index.html
                        mkdir($course_documents_folder, $perm);
                        $fd = fopen(
                            $course_documents_folder.'index.html',
                            'w'
                        );
                        fwrite($fd, $htmlpage);
                        @chmod(
                            $course_documents_folder.'index.html',
                            $perm_file
                        );
                    }

                    if (is_array($array_media) && count($array_media) > 0) {
                        foreach ($array_media as $key => $value) {
                            if (isset($value['dir']) && !empty($value['dir'])) {
                                if (!is_dir($course_documents_folder.$value['dir'])) {
                                    //Creating folder
                                    mkdir(
                                        $course_documents_folder.$value['dir'],
                                        $perm
                                    );

                                    //Creating index.html (for light protection)
                                    $index_html = $course_documents_folder.$value['dir'].'/index.html';
                                    $fd = fopen($index_html, 'w');
                                    fwrite($fd, $htmlpage);
                                    @chmod($index_html, $perm_file);

                                    //Inserting folder in the DB
                                    $folder_path = substr(
                                        $value['dir'],
                                        0,
                                        strlen($value['dir']) - 1
                                    );
                                    $temp = explode('/', $folder_path);
                                    $title = $temp[count($temp) - 1];

                                    //hack until feature #5242 is implemented
                                    if ($title == 'gallery') {
                                        $title = get_lang(
                                            'DefaultCourseImages'
                                        );
                                    }

                                    if ($media_type == 'images/gallery') {
                                        $folder_path = 'gallery/'.$folder_path;
                                    }

                                    Database::query(
                                        "INSERT INTO $TABLETOOLDOCUMENT (c_id, path,title,filetype,size)
                                        VALUES ($course_id,'$path_documents".$folder_path."','".$title."','folder','0')"
                                    );
                                    $image_id = Database:: insert_id();

                                    Database::insert(
                                        $TABLEITEMPROPERTY,
                                        [
                                            'c_id' => $course_id,
                                            'tool' => 'document',
                                            'insert_user_id' => api_get_user_id(),
                                            'insert_date' => $now,
                                            'lastedit_date' => $now,
                                            'ref' => $image_id,
                                            'lastedit_type' => 'DocumentAdded',
                                            'lastedit_user_id' => api_get_user_id(),
                                            'to_group_id' => null,
                                            'to_user_id' => null,
                                            'visibility' => 0,
                                        ]
                                    );
                                }
                            }

                            if (isset($value['file']) && !empty($value['file'])) {
                                if (!file_exists(
                                    $course_documents_folder.$value['file']
                                )
                                ) {
                                    //Copying file
                                    copy(
                                        $default_course_path.$value['file'],
                                        $course_documents_folder.$value['file']
                                    );
                                    chmod(
                                        $course_documents_folder.$value['file'],
                                        $perm_file
                                    );
                                    //echo $default_course_path.$value['file']; echo ' - '; echo $course_documents_folder.$value['file']; echo '<br />';
                                    $temp = explode('/', $value['file']);
                                    $file_size = filesize(
                                        $course_documents_folder.$value['file']
                                    );

                                    //hack until feature #5242 is implemented
                                    if ($media_type == 'images/gallery') {
                                        $value["file"] = 'gallery/'.$value["file"];
                                    }

                                    //Inserting file in the DB
                                    Database::query(
                                        "INSERT INTO $TABLETOOLDOCUMENT (c_id, path,title,filetype,size)
                                        VALUES ($course_id,'$path_documents".$value["file"]."','".$temp[count($temp) - 1]."','file','$file_size')"
                                    );
                                    $image_id = Database:: insert_id();
                                    if ($image_id) {
                                        $sql = "UPDATE $TABLETOOLDOCUMENT SET id = iid WHERE iid = $image_id";
                                        Database::query($sql);

                                        if ($path_documents.$value['file'] == '/certificates/default.html') {
                                            $example_cert_id = $image_id;
                                        }
                                        $docId = Database::insert(
                                            $TABLEITEMPROPERTY,
                                            [
                                                'c_id' => $course_id,
                                                'tool' => 'document',
                                                'insert_user_id' => api_get_user_id(),
                                                'insert_date' => $now,
                                                'lastedit_date' => $now,
                                                'ref' => $image_id,
                                                'lastedit_type' => 'DocumentAdded',
                                                'lastedit_user_id' => api_get_user_id(),
                                                'to_group_id' => null,
                                                'to_user_id' => null,
                                                'visibility' => 1,
                                            ]
                                        );
                                        if ($docId) {
                                            $sql = "UPDATE $TABLEITEMPROPERTY SET id = iid WHERE iid = $docId";
                                            Database::query($sql);
                                        }
                                    }
                                }
                            }
                        }
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
                            <img src="'.api_get_path(WEB_CODE_PATH).'default_course_document/images/mr_chamilo/doubts.png">
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
                "INSERT INTO $TABLEGRADEBOOK (name, description, user_id, course_code, parent_id, weight, visible, certif_min_score, session_id, document_id)
                VALUES ('$course_code','',1,'$course_code',0,100,0,75,NULL,$example_cert_id)"
            );
            $gbid = Database:: insert_id();
            Database::query(
                "INSERT INTO $TABLEGRADEBOOK (name, description, user_id, course_code, parent_id, weight, visible, certif_min_score, session_id, document_id)
                VALUES ('$course_code','',1,'$course_code',$gbid,100,1,75,NULL,$example_cert_id)"
            );
            $gbid = Database:: insert_id();
            Database::query(
                "INSERT INTO $TABLEGRADEBOOKLINK (type, ref_id, user_id, course_code, category_id, created_at, weight, visible, locked)
                VALUES (1,$exercise_id,1,'$course_code',$gbid,'$now',100,1,0)"
            );
        }

        //Installing plugins in course
        $app_plugin = new AppPlugin();
        $app_plugin->install_course_plugins($course_id);

        return true;
    }

    /**
     * @param int   $course_id
     * @param int   $counter
     * @param array $file
     * @param int   $authorId
     */
    public static function insertDocument($course_id, $counter, $file, $authorId = 0)
    {
        $tableItem = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $tableDocument = Database::get_course_table(TABLE_DOCUMENT);

        $now = api_get_utc_datetime();
        $sql = "INSERT INTO $tableDocument (id, c_id, path,title,filetype,size, readonly, session_id)
                VALUES ($counter, $course_id, '".$file['path']."', '".$file['title']."', '".$file['filetype']."', '".$file['size']."', 0, 0)";
        Database::query($sql);
        $docId = Database:: insert_id();

        $authorId = empty($authorId) ? api_get_user_id() : (int) $authorId;

        if ($docId) {
            $sql = "UPDATE $tableDocument SET id = iid WHERE iid = $docId";
            Database::query($sql);

            $id = Database::insert(
                $tableItem,
                [
                    'id' => $counter,
                    'c_id' => $course_id,
                    'tool' => 'document',
                    'insert_user_id' => $authorId,
                    'insert_date' => $now,
                    'lastedit_date' => $now,
                    'ref' => $docId,
                    'lastedit_type' => 'DocumentAdded',
                    'lastedit_user_id' => $authorId,
                    'to_group_id' => null,
                    'to_user_id' => null,
                    'visibility' => 0,
                ]
            );

            if ($id) {
                $sql = "UPDATE $tableItem SET id = iid WHERE iid = $id";
                Database::query($sql);
            }
        }
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
     * @param array $params      Course details (see code for details).
     * @param int   $accessUrlId Optional.
     *
     * @return int Created course ID
     *
     * @todo use an array called $params instead of lots of params
     * @assert (null) === false
     */
    public static function register_course($params, $accessUrlId = 1)
    {
        $title = str_replace('&amp;', '&', $params['title']);
        $code = array_key_exists('code', $params) ? $params['code'] : null;
        $visualCode = array_key_exists('visual_code', $params) ? $params['visual_code'] : null;
        $directory = array_key_exists('directory', $params) ? $params['directory'] : null;
        $tutorName = array_key_exists('tutor_name', $params) ? $params['tutor_name'] : null;
        $categoryCode = array_key_exists('course_category', $params) ? $params['course_category'] : '';
        $courseLanguage = array_key_exists('course_language', $params) && !empty($params['course_language'])
            ? $params['course_language']
            : api_get_setting('platformLanguage');
        $userId = empty($params['user_id']) ? api_get_user_id() : (int) $params['user_id'];
        $departmentName = array_key_exists('department_name', $params) ? $params['department_name'] : null;
        $departmentUrl = array_key_exists('department_url', $params) ? $params['department_url'] : null;
        $diskQuota = array_key_exists('disk_quota', $params) ? $params['disk_quota'] : null;
        $visibility = array_key_exists('visibility', $params) ? $params['visibility'] : null;
        $subscribe = array_key_exists('subscribe', $params) ? $params['subscribe'] : null;
        $unsubscribe = array_key_exists('unsubscribe', $params) ? $params['unsubscribe'] : null;
        $teachers = array_key_exists('teachers', $params) ? $params['teachers'] : null;

        $expirationDate = null;
        if (array_key_exists('expiration_date', $params)) {
            $date = $params['expiration_date'];
            try {
                $expirationDate = new DateTime(api_get_utc_datetime($date), new DateTimeZone('utc'));
            } catch (Exception $exception) {
                error_log(sprintf('expiration_date "%s" is invalid', $date));

                return 0;
            }
        }

        $user = api_get_user_entity($userId);
        if (is_null($user)) {
            error_log(sprintf('user_id "%s" is invalid', $userId));

            return 0;
        }

        $course = (new \Chamilo\CoreBundle\Entity\Course())
            ->setCode($code)
            ->setDirectory($directory)
            ->setCourseLanguage($courseLanguage)
            ->setTitle($title)
            ->setCategoryCode($categoryCode)
            ->setVisibility($visibility)
            ->setDiskQuota($diskQuota)
            ->setExpirationDate($expirationDate)
            ->setTutorName($tutorName)
            ->setDepartmentName($departmentName)
            ->setDepartmentUrl($departmentUrl)
            ->setSubscribe($subscribe)
            ->setUnsubscribe($unsubscribe)
            ->setVisualCode($visualCode)
        ;
        Database::getManager()->persist($course);

        $addTeacher = isset($params['add_user_as_teacher']) ? $params['add_user_as_teacher'] : true;
        if ($addTeacher) {
            $iCourseSort = CourseManager::userCourseSort($userId, $code);
            $courseRelTutor = (new CourseRelUser())
                ->setCourse($course)
                ->setUser($user)
                ->setStatus(true)
                ->setTutor(true)
                ->setSort($iCourseSort)
                ->setRelationType(0)
                ->setUserCourseCat(0)
            ;
            Database::getManager()->persist($courseRelTutor);
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
                    ->setStatus(true)
                    ->setTutor(false)
                    ->setSort($sort + 1)
                    ->setRelationType(0)
                    ->setUserCourseCat(0)
                ;
                Database::getManager()->persist($courseRelTeacher);
            }
        }

        // Adding the course to an URL.
        $url = api_get_access_url_entity($accessUrlId);
        if (!is_null($url)) {
            $urlRelCourse = (new AccessUrlRelCourse())
                ->setCourse($course)
                ->setUrl($url);
            Database::getManager()->persist($urlRelCourse);
        }

        try {
            Database::getManager()->flush();
        } catch (OptimisticLockException $exception) {
            error_log($exception);

            return 0;
        }

        $courseId = $course->getId();

        // Add event to the system log.
        $userId = api_get_user_id();
        Event::addEvent(
            LOG_COURSE_CREATE,
            LOG_COURSE_CODE,
            $code,
            api_get_utc_datetime(),
            $userId,
            $courseId
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
            $subject = get_lang('NewCourseCreatedIn').' '.$siteName.' - '.$iname;
            $message = get_lang(
                    'Dear'
                ).' '.$recipient_name.",\n\n".get_lang(
                    'MessageOfNewCourseToAdmin'
                ).' '.$siteName.' - '.$iname."\n";
            $message .= get_lang('CourseName').' '.$title."\n";
            $message .= get_lang(
                    'Category'
                ).' '.$categoryCode."\n";
            $message .= get_lang('Tutor').' '.$tutorName."\n";
            $message .= get_lang('Language').' '.$courseLanguage;

            $userInfo = api_get_user_info($userId);
            $additionalParameters = [
                'smsType' => SmsPlugin::NEW_COURSE_BEEN_CREATED,
                'userId' => $userId,
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

        return $courseId;
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
