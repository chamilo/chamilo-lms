<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy;

use Chamilo\CourseBundle\Component\CourseCopy\Resources\GradeBookBackup;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\LearnPathCategory;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\QuizQuestion;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use CourseManager;
use Database;
use DocumentManager;
use Question;
use stdClass;
use SurveyManager;
use TestCategory;

/**
 * Class CourseRestorer.
 *
 * Class to restore items from a course object to a Chamilo-course
 *
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @author Julio Montoya <gugli100@gmail.com> Several fixes/improvements
 */
class CourseRestorer
{
    /**
     * The course-object.
     */
    public $course;
    public $destination_course_info;

    /**
     * What to do with files with same name (FILE_SKIP, FILE_RENAME or
     * FILE_OVERWRITE).
     */
    public $file_option;
    public $set_tools_invisible_by_default;
    public $skip_content;
    // Restoration is done in the order listed for $tools_to_restore
    public $tools_to_restore = [
        'documents', // first restore documents
        'announcements',
        'attendance',
        'course_descriptions',
        'events',
        'forum_category',
        'forums',
       // 'forum_topics',
        'glossary',
        'quizzes',
        'test_category',
        'links',
        'works',
        'xapi_tool',
        'h5p_tool',
        'surveys',
        'learnpath_category',
        'learnpaths',
        //'scorm_documents', ??
        'tool_intro',
        'thematic',
        'wiki',
        'gradebook',
        'assets',
    ];

    /** Setting per tool */
    public $tool_copy_settings = [];
    public $isXapiEnabled = false;
    public $isH5pEnabled = false;

    /**
     * If true adds the text "copy" in the title of an item (only for LPs right now).
     */
    public $add_text_in_items = false;
    public $destination_course_id;

    public $copySessionContent = false;

    /**
     * CourseRestorer constructor.
     *
     * @param Course $course
     */
    public function __construct($course)
    {
        $this->course = $course;
        $courseInfo = api_get_course_info($this->course->code);
        $this->course_origin_id = null;
        if (!empty($courseInfo)) {
            $this->course_origin_id = $courseInfo['real_id'];
        }
        $this->file_option = FILE_RENAME;
        $this->set_tools_invisible_by_default = false;
        $this->skip_content = [];

        $forceImport = api_get_configuration_value('allow_import_scorm_package_in_course_builder');
        if ($forceImport) {
            $this->tools_to_restore[] = 'scorm_documents';
        }
    }

    /**
     * Set the file-option.
     *
     * @param int $option (optional) What to do with files with same name
     *                    FILE_SKIP, FILE_RENAME or FILE_OVERWRITE
     */
    public function set_file_option($option = FILE_OVERWRITE)
    {
        $this->file_option = $option;
    }

    /**
     * @param bool $status
     */
    public function set_add_text_in_items($status)
    {
        $this->add_text_in_items = $status;
    }

    /**
     * @param array $array
     */
    public function set_tool_copy_settings($array)
    {
        $this->tool_copy_settings = $array;
    }

    /**
     * Restore a course.
     *
     * @param string $destination_course_code code of the Chamilo-course in
     * @param int    $session_id
     * @param bool   $update_course_settings  Course settings are going to be restore?
     * @param bool   $respect_base_content
     *
     * @return false|null
     */
    public function restore(
        $destination_course_code = '',
        $session_id = 0,
        $update_course_settings = false,
        $respect_base_content = false
    ) {
        if ($destination_course_code == '') {
            $course_info = api_get_course_info();
            $this->destination_course_info = $course_info;
            $this->course->destination_path = $course_info['path'];
        } else {
            $course_info = api_get_course_info($destination_course_code);
            $this->destination_course_info = $course_info;
            $this->course->destination_path = $course_info['path'];
        }
        $this->destination_course_id = $course_info['real_id'];
        // Getting first teacher (for the forums)
        $teacher_list = CourseManager::get_teacher_list_from_course_code($course_info['code']);
        $this->first_teacher_id = api_get_user_id();
        $this->isXapiEnabled = \XApiPlugin::create()->isEnabled();
        $this->isH5pEnabled = \H5pImportPlugin::create()->isEnabled();

        if (!empty($teacher_list)) {
            foreach ($teacher_list as $teacher) {
                $this->first_teacher_id = $teacher['user_id'];
                break;
            }
        }

        if (empty($this->course)) {
            return false;
        }

        // Source platform encoding - reading/detection
        // The correspondent data field has been added as of version 1.8.6.1
        if (empty($this->course->encoding)) {
            // The archive has been created by a system which is prior to 1.8.6.1 version.
            // In this case we have to detect the encoding.
            $sample_text = $this->course->get_sample_text()."\n";
            // Let us exclude ASCII lines, probably they are English texts.
            $sample_text = explode("\n", $sample_text);
            foreach ($sample_text as $key => &$line) {
                if (api_is_valid_ascii($line)) {
                    unset($sample_text[$key]);
                }
            }
            $sample_text = implode("\n", $sample_text);
            $this->course->encoding = api_detect_encoding(
                $sample_text,
                $course_info['language']
            );
        }

        // Encoding conversion of the course, if it is needed.
        $this->course->to_system_encoding();

        foreach ($this->tools_to_restore as $tool) {
            if ('xapi_tool' == $tool && !$this->isXapiEnabled) {
                continue;
            }
            if ('h5p_tool' == $tool && !$this->isH5pEnabled) {
                continue;
            }
            $function_build = 'restore_'.$tool;
            $this->$function_build(
                $session_id,
                $respect_base_content,
                $destination_course_code
            );
        }

        if ($update_course_settings) {
            $this->restore_course_settings($destination_course_code);
        }

        // Restore the item properties
        $table = Database::get_course_table(TABLE_ITEM_PROPERTY);
        foreach ($this->course->resources as $type => $resources) {
            if (is_array($resources)) {
                foreach ($resources as $id => $resource) {
                    if (isset($resource->item_properties)) {
                        foreach ($resource->item_properties as $property) {
                            // First check if there isn't already a record for this resource
                            $sql = "SELECT * FROM $table
                                    WHERE
                                        c_id = ".$this->destination_course_id." AND
                                        tool = '".$property['tool']."' AND
                                        ref = '".$resource->destination_id."'";

                            $params = [];
                            if (!empty($session_id)) {
                                $params['session_id'] = (int) $session_id;
                            }

                            $res = Database::query($sql);
                            if (Database::num_rows($res) == 0) {
                                /* The to_group_id and to_user_id are set to default
                                values as users/groups possibly not exist in
                                the target course*/

                                $params['c_id'] = $this->destination_course_id;
                                $params['tool'] = self::DBUTF8($property['tool']);
                                $params['insert_user_id'] = $this->checkUserId($property['insert_user_id']) ?: null;
                                $params['insert_date'] = self::DBUTF8($property['insert_date']);
                                $params['lastedit_date'] = self::DBUTF8($property['lastedit_date']);
                                $params['ref'] = $resource->destination_id;
                                $params['lastedit_type'] = self::DBUTF8($property['lastedit_type']);
                                $params['lastedit_user_id'] = $this->checkUserId($property['lastedit_user_id']);
                                $params['visibility'] = self::DBUTF8($property['visibility']);
                                $params['start_visible'] = self::DBUTF8($property['start_visible']);
                                $params['end_visible'] = self::DBUTF8($property['end_visible']);
                                $params['to_user_id'] = $this->checkUserId($property['to_user_id']) ?: null;

                                $id = Database::insert($table, $params);
                                if ($id) {
                                    $sql = "UPDATE $table SET id = iid WHERE iid = $id";
                                    Database::query($sql);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Restore only harmless course settings:
     * course_language, visibility, department_name,department_url,
     * subscribe, unsubscribe ,category_code.
     *
     * @param string $destination_course_code
     */
    public function restore_course_settings($destination_course_code)
    {
        $origin_course_info = api_get_course_info($destination_course_code);
        $course_info = $this->course->info;
        $params['course_language'] = $course_info['language'];
        $params['visibility'] = $course_info['visibility'];
        $params['department_name'] = $course_info['department_name'];
        $params['department_url'] = $course_info['department_url'];
        $params['category_code'] = $course_info['categoryCode'];
        $params['subscribe'] = $course_info['subscribe_allowed'];
        $params['unsubscribe'] = $course_info['unsubscribe'];
        CourseManager::update_attributes($origin_course_info['real_id'], $params);
    }

    /**
     * Restore documents.
     *
     * @param int    $session_id
     * @param bool   $respect_base_content
     * @param string $destination_course_code
     */
    public function restore_documents(
        $session_id = 0,
        $respect_base_content = false,
        $destination_course_code = ''
    ) {
        $course_info = api_get_course_info($destination_course_code);

        if (!$this->course->has_resources(RESOURCE_DOCUMENT)) {
            return;
        }

        $webEditorCss = api_get_path(WEB_CSS_PATH).'editor.css';
        $table = Database::get_course_table(TABLE_DOCUMENT);
        $resources = $this->course->resources;
        $path = api_get_path(SYS_COURSE_PATH).$this->course->destination_path.'/';
        $originalFolderNameList = [];
        foreach ($resources[RESOURCE_DOCUMENT] as $id => $document) {
            $my_session_id = empty($document->item_properties[0]['session_id']) ? 0 : $session_id;

            if (false === $respect_base_content && $session_id) {
                if (empty($my_session_id)) {
                    $my_session_id = $session_id;
                }
            }

            if ($document->file_type == FOLDER) {
                $visibility = isset($document->item_properties[0]['visibility']) ? $document->item_properties[0]['visibility'] : '';
                $new = substr($document->path, 8);
                $folderList = explode('/', $new);
                $tempFolder = '';

                // Check if the parent path exists.
                foreach ($folderList as $folder) {
                    $folderToCreate = $tempFolder.$folder;
                    $sysFolderPath = $path.'document'.$folderToCreate;
                    $tempFolder .= $folder.'/';

                    if (empty($folderToCreate)) {
                        continue;
                    }
                    $title = $document->title;
                    $originalFolderNameList[basename($document->path)] = $document->title;
                    if (empty($title)) {
                        $title = basename($sysFolderPath);
                    }
                    //error_log($title); error_log($sysFolderPath);
                    // File doesn't exist in file system.
                    if (!is_dir($sysFolderPath)) {
                        /*error_log('Creating directory');
                        error_log("Creating $folderToCreate");*/
                        // Creating directory
                        create_unexisting_directory(
                            $course_info,
                            api_get_user_id(),
                            $my_session_id,
                            0,
                            0,
                            $path.'document',
                            $folderToCreate,
                            $title,
                            $visibility
                        );
                        continue;
                    }

                    // File exist in file system.
                    $documentData = DocumentManager::get_document_id(
                        $course_info,
                        $folderToCreate,
                        $session_id
                    );

                    //error_log("session_id $session_id");
                    if (empty($documentData)) {
                        if (!is_dir($sysFolderPath)) {
                            //error_log('$documentData empty');
                            //error_log('$folderToCreate '.$folderToCreate);
                            /* This means the folder exists in the
                            filesystem but not in the DB, trying to fix it */
                            add_document(
                                $course_info,
                                $folderToCreate,
                                'folder',
                                0,
                                $title,
                                null,
                                null,
                                false,
                                null,
                                $session_id,
                                0,
                                false
                            );
                        }
                    } else {
                        $insertUserId = isset($document->item_properties[0]['insert_user_id']) ? $document->item_properties[0]['insert_user_id'] : api_get_user_id();
                        $insertUserId = $this->checkUserId($insertUserId);
                        // Check if user exists in platform
                        $toUserId = isset($document->item_properties[0]['to_user_id']) ? $document->item_properties[0]['to_user_id'] : null;
                        $toUserId = $this->checkUserId($toUserId, true);
                        $groupId = isset($document->item_properties[0]['to_group_id']) ? $document->item_properties[0]['to_group_id'] : null;
                        $groupInfo = $this->checkGroupId($groupId);
                        //error_log(" if folder exists then just refresh it");
                        // if folder exists then just refresh it
                        api_item_property_update(
                            $course_info,
                            TOOL_DOCUMENT,
                            $documentData,
                            'FolderUpdated',
                            $insertUserId,
                            $groupInfo,
                            $toUserId,
                            null,
                            null,
                            $my_session_id
                        );
                    }
                }
            } elseif ($document->file_type == DOCUMENT) {
                // Checking if folder exists in the database otherwise we created it
                $dir_to_create = dirname($document->path);
                $originalFolderNameList[basename($document->path)] = $document->title;
                if (!empty($dir_to_create) && $dir_to_create != 'document' && $dir_to_create != '/') {
                    // it creates folder images if it doesn't exist , used for hotspot pictures.
                    if (false !== strpos($document->path, '/images/') && !is_dir(dirname($path.$document->path))) {
                        $perm = api_get_permissions_for_new_directories();
                        mkdir(dirname($path.$document->path), $perm, true);
                    }
                    if (is_dir($path.dirname($document->path))) {
                        $sql = "SELECT id FROM $table
                                WHERE
                                    c_id = ".$this->destination_course_id." AND
                                    path = '/".self::DBUTF8escapestring(substr(dirname($document->path), 9))."'";
                        $res = Database::query($sql);

                        if (Database::num_rows($res) == 0) {
                            $new = '/'.substr(dirname($document->path), 9);
                            // It adds the folder name as title
                            $title = str_replace('/', '', $new);

                            // This code fixes the possibility for a file without a directory entry to be
                            $document_id = add_document(
                                $course_info,
                                $new,
                                'folder',
                                0,
                                $title,
                                null,
                                null,
                                false,
                                0,
                                0,
                                0,
                                false
                            );

                            $itemProperty = isset($document->item_properties[0]) ? $document->item_properties[0] : '';
                            $insertUserId = isset($itemProperty['insert_user_id']) ? $itemProperty['insert_user_id'] : api_get_user_id();
                            $toGroupId = isset($itemProperty['to_group_id']) ? $itemProperty['to_group_id'] : 0;
                            $toUserId = isset($itemProperty['to_user_id']) ? $itemProperty['to_user_id'] : null;
                            $groupInfo = $this->checkGroupId($toGroupId);
                            $insertUserId = $this->checkUserId($insertUserId);
                            $toUserId = $this->checkUserId($toUserId, true);

                            api_item_property_update(
                                $course_info,
                                TOOL_DOCUMENT,
                                $document_id,
                                'FolderCreated',
                                $insertUserId,
                                $groupInfo,
                                $toUserId,
                                null,
                                null,
                                $my_session_id
                            );
                        }
                    }
                }

                //error_log(print_r($originalFolderNameList, 1));
                if (file_exists($path.$document->path)) {
                    switch ($this->file_option) {
                        case FILE_OVERWRITE:
                            $origin_path = $this->course->backup_path.'/'.$document->path;
                            if (file_exists($origin_path)) {
                                copy($origin_path, $path.$document->path);
                                $this->fixEditorHtmlContent($path.$document->path, $webEditorCss);
                                $sql = "SELECT id FROM $table
                                        WHERE
                                            c_id = ".$this->destination_course_id." AND
                                            path = '/".self::DBUTF8escapestring(substr($document->path, 9))."'";

                                $res = Database::query($sql);
                                $count = Database::num_rows($res);

                                if ($count == 0) {
                                    $params = [
                                        'path' => "/".self::DBUTF8(substr($document->path, 9)),
                                        'c_id' => $this->destination_course_id,
                                        'comment' => self::DBUTF8($document->comment),
                                        'title' => self::DBUTF8($document->title),
                                        'filetype' => self::DBUTF8($document->file_type),
                                        'size' => self::DBUTF8($document->size),
                                        'session_id' => $my_session_id,
                                        'readonly' => 0,
                                    ];

                                    $document_id = Database::insert($table, $params);

                                    if ($document_id) {
                                        $sql = "UPDATE $table SET id = iid WHERE iid = $document_id";
                                        Database::query($sql);
                                    }
                                    $this->course->resources[RESOURCE_DOCUMENT][$id]->destination_id = $document_id;

                                    $itemProperty = isset($document->item_properties[0]) ? $document->item_properties[0] : '';
                                    $insertUserId = isset($itemProperty['insert_user_id']) ? $itemProperty['insert_user_id'] : api_get_user_id();
                                    $toGroupId = isset($itemProperty['to_group_id']) ? $itemProperty['to_group_id'] : 0;
                                    $toUserId = isset($itemProperty['to_user_id']) ? $itemProperty['to_user_id'] : null;

                                    $insertUserId = $this->checkUserId($insertUserId);
                                    $toUserId = $this->checkUserId($toUserId, true);
                                    $groupInfo = $this->checkGroupId($toGroupId);

                                    api_item_property_update(
                                        $course_info,
                                        TOOL_DOCUMENT,
                                        $document_id,
                                        'DocumentAdded',
                                        $insertUserId,
                                        $groupInfo,
                                        $toUserId,
                                        null,
                                        null,
                                        $my_session_id
                                    );
                                } else {
                                    $obj = Database::fetch_object($res);
                                    $document_id = $obj->id;
                                    $params = [
                                        'path' => "/".self::DBUTF8(substr($document->path, 9)),
                                        'c_id' => $this->destination_course_id,
                                        'comment' => self::DBUTF8($document->comment),
                                        'title' => self::DBUTF8($document->title),
                                        'filetype' => self::DBUTF8($document->file_type),
                                        'size' => self::DBUTF8($document->size),
                                        'session_id' => $my_session_id,
                                    ];

                                    Database::update(
                                        $table,
                                        $params,
                                        [
                                            'c_id = ? AND path = ?' => [
                                                $this->destination_course_id,
                                                "/".self::DBUTF8escapestring(substr($document->path, 9)),
                                            ],
                                        ]
                                    );

                                    $this->course->resources[RESOURCE_DOCUMENT][$id]->destination_id = $obj->id;

                                    $itemProperty = isset($document->item_properties[0]) ? $document->item_properties[0] : '';
                                    $insertUserId = isset($itemProperty['insert_user_id']) ? $itemProperty['insert_user_id'] : api_get_user_id();
                                    $toGroupId = isset($itemProperty['to_group_id']) ? $itemProperty['to_group_id'] : 0;
                                    $toUserId = isset($itemProperty['to_user_id']) ? $itemProperty['to_user_id'] : null;

                                    $insertUserId = $this->checkUserId($insertUserId);
                                    $toUserId = $this->checkUserId($toUserId, true);
                                    $groupInfo = $this->checkGroupId($toGroupId);

                                    api_item_property_update(
                                        $course_info,
                                        TOOL_DOCUMENT,
                                        $obj->id,
                                        'default',
                                        $insertUserId,
                                        $groupInfo,
                                        $toUserId,
                                        null,
                                        null,
                                        $my_session_id
                                    );
                                }

                                // Replace old course code with the new destination code
                                $file_info = pathinfo($path.$document->path);

                                if (isset($file_info['extension']) && in_array($file_info['extension'], ['html', 'htm'])) {
                                    $content = file_get_contents($path.$document->path);
                                    if (UTF8_CONVERT) {
                                        $content = utf8_encode($content);
                                    }
                                    $content = DocumentManager::replaceUrlWithNewCourseCode(
                                        $content,
                                        $this->course->code,
                                        $this->course->destination_path,
                                        $this->course->backup_path,
                                        $this->course->info['path']
                                    );
                                    file_put_contents($path.$document->path, $content);
                                }

                                $params = [
                                    'comment' => self::DBUTF8($document->comment),
                                    'title' => self::DBUTF8($document->title),
                                    'size' => self::DBUTF8($document->size),
                                ];
                                Database::update(
                                    $table,
                                    $params,
                                    [
                                        'c_id = ? AND id = ?' => [
                                            $this->destination_course_id,
                                            $document_id,
                                        ],
                                    ]
                                );
                            }
                            break;
                        case FILE_SKIP:
                            $sql = "SELECT id FROM $table
                                    WHERE
                                        c_id = ".$this->destination_course_id." AND
                                        path='/".self::DBUTF8escapestring(substr($document->path, 9))."'";
                            $res = Database::query($sql);
                            $obj = Database::fetch_object($res);
                            $this->course->resources[RESOURCE_DOCUMENT][$id]->destination_id = $obj->id;
                            break;
                        case FILE_RENAME:
                            //error_log("Rename: ".$path.$document->path);
                            $i = 1;
                            $ext = explode('.', basename($document->path));
                            if (count($ext) > 1) {
                                $ext = array_pop($ext);
                                $file_name_no_ext = substr($document->path, 0, -(strlen($ext) + 1));
                                $ext = '.'.$ext;
                            } else {
                                $ext = '';
                                $file_name_no_ext = $document->path;
                            }
                            $new_file_name = $file_name_no_ext.'_'.$i.$ext;
                            $file_exists = file_exists($path.$new_file_name);
                            while ($file_exists) {
                                $i++;
                                $new_file_name = $file_name_no_ext.'_'.$i.$ext;
                                $file_exists = file_exists($path.$new_file_name);
                            }

                            if (!empty($session_id)) {
                                $originalPath = $document->path;
                                //error_log("document->path: ".$document->path);
                                $document_path = explode('/', $document->path, 3);
                                $course_path = $path;
                                $orig_base_folder = $document_path[1];
                                $orig_base_path = $course_path.$document_path[0].'/'.$document_path[1];

                                if (is_dir($orig_base_path)) {
                                    $new_base_foldername = $orig_base_folder;
                                    $new_base_path = $orig_base_path;

                                    if (isset($_SESSION['orig_base_foldername']) &&
                                        $_SESSION['orig_base_foldername'] != $new_base_foldername
                                    ) {
                                        unset($_SESSION['new_base_foldername']);
                                        unset($_SESSION['orig_base_foldername']);
                                        unset($_SESSION['new_base_path']);
                                    }

                                    $folder_exists = file_exists($new_base_path);
                                    if ($folder_exists) {
                                        // e.g: carpeta1 in session
                                        $_SESSION['orig_base_foldername'] = $new_base_foldername;
                                        $x = 0;
                                        while ($folder_exists) {
                                            $x = $x + 1;
                                            $new_base_foldername = $document_path[1].'_'.$x;
                                            $new_base_path = $orig_base_path.'_'.$x;
                                            if (isset($_SESSION['new_base_foldername']) &&
                                                $_SESSION['new_base_foldername'] == $new_base_foldername
                                            ) {
                                                break;
                                            }
                                            $folder_exists = file_exists($new_base_path);
                                        }
                                        $_SESSION['new_base_foldername'] = $new_base_foldername;
                                        $_SESSION['new_base_path'] = $new_base_path;
                                    }

                                    if (isset($_SESSION['new_base_foldername']) && isset($_SESSION['new_base_path'])) {
                                        $new_base_foldername = $_SESSION['new_base_foldername'];
                                        $new_base_path = $_SESSION['new_base_path'];
                                    }

                                    $dest_document_path = $new_base_path.'/'.$document_path[2]; // e.g: "/var/www/wiener/courses/CURSO4/document/carpeta1_1/subcarpeta1/collaborative.png"
                                    $basedir_dest_path = dirname($dest_document_path); // e.g: "/var/www/wiener/courses/CURSO4/document/carpeta1_1/subcarpeta1"
                                    $base_path_document = $course_path.$document_path[0]; // e.g: "/var/www/wiener/courses/CURSO4/document"
                                    $path_title = '/'.$new_base_foldername.'/'.$document_path[2];

                                    /*error_log("copy_folder_course_session");
                                    error_log("original: $orig_base_path");
                                    error_log($basedir_dest_path);
                                    error_log($document->title);*/

                                    copy_folder_course_session(
                                        $basedir_dest_path,
                                        $base_path_document,
                                        $session_id,
                                        $course_info,
                                        $document,
                                        $this->course_origin_id,
                                        $originalFolderNameList,
                                        $originalPath
                                    );

                                    if (file_exists($course_path.$document->path)) {
                                        copy($course_path.$document->path, $dest_document_path);
                                    }

                                    // Replace old course code with the new destination code see BT#1985
                                    if (file_exists($dest_document_path)) {
                                        $file_info = pathinfo($dest_document_path);
                                        if (in_array($file_info['extension'], ['html', 'htm'])) {
                                            $content = file_get_contents($dest_document_path);
                                            if (UTF8_CONVERT) {
                                                $content = utf8_encode($content);
                                            }
                                            $content = DocumentManager::replaceUrlWithNewCourseCode(
                                                $content,
                                                $this->course->code,
                                                $this->course->destination_path,
                                                $this->course->backup_path,
                                                $this->course->info['path']
                                            );
                                            file_put_contents($dest_document_path, $content);
                                            $this->fixEditorHtmlContent($dest_document_path, $webEditorCss);
                                        }
                                    }

                                    $title = basename($path_title);
                                    if (isset($originalFolderNameList[basename($path_title)])) {
                                        $title = $originalFolderNameList[basename($path_title)];
                                    }

                                    $params = [
                                        'path' => self::DBUTF8($path_title),
                                        'c_id' => $this->destination_course_id,
                                        'comment' => self::DBUTF8($document->comment),
                                        'title' => self::DBUTF8($title),
                                        'filetype' => self::DBUTF8($document->file_type),
                                        'size' => self::DBUTF8($document->size),
                                        'session_id' => $my_session_id,
                                    ];

                                    $document_id = Database::insert($table, $params);

                                    if ($document_id) {
                                        $sql = "UPDATE $table SET id = iid WHERE iid = $document_id";
                                        Database::query($sql);

                                        $this->course->resources[RESOURCE_DOCUMENT][$id]->destination_id = $document_id;

                                        $itemProperty = isset($document->item_properties[0]) ? $document->item_properties[0] : '';
                                        $insertUserId = isset($itemProperty['insert_user_id']) ? $itemProperty['insert_user_id'] : api_get_user_id();
                                        $toGroupId = isset($itemProperty['to_group_id']) ? $itemProperty['to_group_id'] : 0;
                                        $toUserId = isset($itemProperty['to_user_id']) ? $itemProperty['to_user_id'] : null;

                                        $insertUserId = $this->checkUserId($insertUserId);
                                        $toUserId = $this->checkUserId($toUserId, true);
                                        $groupInfo = $this->checkGroupId($toGroupId);

                                        api_item_property_update(
                                            $course_info,
                                            TOOL_DOCUMENT,
                                            $document_id,
                                            'DocumentAdded',
                                            $insertUserId,
                                            $groupInfo,
                                            $toUserId,
                                            null,
                                            null,
                                            $my_session_id
                                        );
                                    }
                                } else {
                                    if (file_exists($path.$document->path)) {
                                        copy($path.$document->path, $path.$new_file_name);
                                    }
                                    // Replace old course code with the new destination code see BT#1985
                                    if (file_exists($path.$new_file_name)) {
                                        $file_info = pathinfo($path.$new_file_name);
                                        if (in_array($file_info['extension'], ['html', 'htm'])) {
                                            $content = file_get_contents($path.$new_file_name);
                                            if (UTF8_CONVERT) {
                                                $content = utf8_encode($content);
                                            }
                                            $content = DocumentManager::replaceUrlWithNewCourseCode(
                                                $content,
                                                $this->course->code,
                                                $this->course->destination_path,
                                                $this->course->backup_path,
                                                $this->course->info['path']
                                            );
                                            file_put_contents($path.$new_file_name, $content);
                                            $this->fixEditorHtmlContent($path.$new_file_name, $webEditorCss);
                                        }
                                    }

                                    $params = [
                                        'path' => "/".self::DBUTF8escapestring(substr($new_file_name, 9)),
                                        'c_id' => $this->destination_course_id,
                                        'comment' => self::DBUTF8($document->comment),
                                        'title' => self::DBUTF8($document->title),
                                        'filetype' => self::DBUTF8($document->file_type),
                                        'size' => self::DBUTF8($document->size),
                                        'session_id' => $my_session_id,
                                    ];

                                    $document_id = Database::insert($table, $params);

                                    if ($document_id) {
                                        $sql = "UPDATE $table SET id = iid WHERE iid = $document_id";
                                        Database::query($sql);

                                        $this->course->resources[RESOURCE_DOCUMENT][$id]->destination_id = $document_id;

                                        $itemProperty = isset($document->item_properties[0]) ? $document->item_properties[0] : '';
                                        $insertUserId = isset($itemProperty['insert_user_id']) ? $itemProperty['insert_user_id'] : api_get_user_id();
                                        $toGroupId = isset($itemProperty['to_group_id']) ? $itemProperty['to_group_id'] : 0;
                                        $toUserId = isset($itemProperty['to_user_id']) ? $itemProperty['to_user_id'] : null;

                                        $insertUserId = $this->checkUserId($insertUserId);
                                        $toUserId = $this->checkUserId($toUserId, true);
                                        $groupInfo = $this->checkGroupId($toGroupId);

                                        api_item_property_update(
                                            $course_info,
                                            TOOL_DOCUMENT,
                                            $document_id,
                                            'DocumentAdded',
                                            $insertUserId,
                                            $groupInfo,
                                            $toUserId,
                                            null,
                                            null,
                                            $my_session_id
                                        );
                                    }
                                }
                            } else {
                                copy(
                                    $this->course->backup_path.'/'.$document->path,
                                    $path.$new_file_name
                                );

                                // Replace old course code with the new destination code see BT#1985
                                if (file_exists($path.$new_file_name)) {
                                    $file_info = pathinfo($path.$new_file_name);
                                    if (in_array($file_info['extension'], ['html', 'htm'])) {
                                        $content = file_get_contents($path.$new_file_name);
                                        if (UTF8_CONVERT) {
                                            $content = utf8_encode($content);
                                        }
                                        $content = DocumentManager::replaceUrlWithNewCourseCode(
                                            $content,
                                            $this->course->code,
                                            $this->course->destination_path,
                                            $this->course->backup_path,
                                            $this->course->info['path']
                                        );
                                        file_put_contents($path.$new_file_name, $content);
                                        $this->fixEditorHtmlContent($path.$new_file_name, $webEditorCss);
                                    }
                                }

                                $params = [
                                    'c_id' => $this->destination_course_id,
                                    'path' => "/".self::DBUTF8escapestring(substr($new_file_name, 9)),
                                    'comment' => self::DBUTF8($document->comment),
                                    'title' => self::DBUTF8($document->title),
                                    'filetype' => self::DBUTF8($document->file_type),
                                    'size' => self::DBUTF8($document->size),
                                    'session_id' => $my_session_id,
                                ];

                                $document_id = Database::insert($table, $params);

                                if ($document_id) {
                                    $sql = "UPDATE $table SET id = iid WHERE iid = $document_id";
                                    Database::query($sql);
                                    $this->course->resources[RESOURCE_DOCUMENT][$id]->destination_id = $document_id;

                                    $itemProperty = isset($document->item_properties[0]) ? $document->item_properties[0] : '';
                                    $insertUserId = isset($itemProperty['insert_user_id']) ? $itemProperty['insert_user_id'] : api_get_user_id();
                                    $toGroupId = isset($itemProperty['to_group_id']) ? $itemProperty['to_group_id'] : 0;
                                    $toUserId = isset($itemProperty['to_user_id']) ? $itemProperty['to_user_id'] : null;

                                    $insertUserId = $this->checkUserId($insertUserId);
                                    $toUserId = $this->checkUserId($toUserId, true);
                                    $groupInfo = $this->checkGroupId($toGroupId);

                                    api_item_property_update(
                                        $course_info,
                                        TOOL_DOCUMENT,
                                        $document_id,
                                        'DocumentAdded',
                                        $insertUserId,
                                        $groupInfo,
                                        $toUserId,
                                        null,
                                        null,
                                        $my_session_id
                                    );
                                }
                            }
                            break;
                    } // end switch
                } else {
                    // end if file exists

                    //make sure the source file actually exists
                    if (is_file($this->course->backup_path.'/'.$document->path) &&
                        is_readable($this->course->backup_path.'/'.$document->path) &&
                        is_dir(dirname($path.$document->path)) &&
                        is_writeable(dirname($path.$document->path))
                    ) {
                        copy(
                            $this->course->backup_path.'/'.$document->path,
                            $path.$document->path
                        );

                        // Replace old course code with the new destination code see BT#1985
                        if (file_exists($path.$document->path)) {
                            $file_info = pathinfo($path.$document->path);
                            if (isset($file_info['extension']) && in_array($file_info['extension'], ['html', 'htm'])) {
                                $content = file_get_contents($path.$document->path);
                                if (UTF8_CONVERT) {
                                    $content = utf8_encode($content);
                                }
                                $content = DocumentManager::replaceUrlWithNewCourseCode(
                                    $content,
                                    $this->course->code,
                                    $this->course->destination_path,
                                    $this->course->backup_path,
                                    $this->course->info['path']
                                );
                                file_put_contents($path.$document->path, $content);
                                $this->fixEditorHtmlContent($path.$document->path, $webEditorCss);
                            }
                        }

                        $params = [
                            'c_id' => $this->destination_course_id,
                            'path' => "/".self::DBUTF8(substr($document->path, 9)),
                            'comment' => self::DBUTF8($document->comment),
                            'title' => self::DBUTF8($document->title),
                            'filetype' => self::DBUTF8($document->file_type),
                            'size' => self::DBUTF8($document->size),
                            'session_id' => $my_session_id,
                            'readonly' => 0,
                        ];

                        $document_id = Database::insert($table, $params);

                        if ($document_id) {
                            $sql = "UPDATE $table SET id = iid WHERE iid = $document_id";
                            Database::query($sql);

                            $this->course->resources[RESOURCE_DOCUMENT][$id]->destination_id = $document_id;

                            $itemProperty = isset($document->item_properties[0]) ? $document->item_properties[0] : '';
                            $insertUserId = isset($itemProperty['insert_user_id']) ? $itemProperty['insert_user_id'] : api_get_user_id();
                            $toGroupId = isset($itemProperty['to_group_id']) ? $itemProperty['to_group_id'] : 0;
                            $toUserId = isset($itemProperty['to_user_id']) ? $itemProperty['to_user_id'] : null;

                            $insertUserId = $this->checkUserId($insertUserId);
                            $toUserId = $this->checkUserId($toUserId, true);
                            $groupInfo = $this->checkGroupId($toGroupId);

                            api_item_property_update(
                                $course_info,
                                TOOL_DOCUMENT,
                                $document_id,
                                'DocumentAdded',
                                $insertUserId,
                                $groupInfo,
                                $toUserId,
                                null,
                                null,
                                $my_session_id
                            );
                        }
                    } else {
                        // There was an error in checking existence and
                        // permissions for files to copy. Try to determine
                        // the exact issue
                        // Issue with origin document?
                        if (!is_file($this->course->backup_path.'/'.$document->path)) {
                            error_log(
                                'Course copy generated an ignorable error while trying to copy '.
                                $this->course->backup_path.'/'.$document->path.': origin file not found'
                            );
                        } elseif (!is_readable($this->course->backup_path.'/'.$document->path)) {
                            error_log(
                                'Course copy generated an ignorable error while trying to copy '.
                                $this->course->backup_path.'/'.$document->path.': origin file not readable'
                            );
                        }
                        // Issue with destination directories?
                        if (!is_dir(dirname($path.$document->path))) {
                            error_log(
                                'Course copy generated an ignorable error while trying to copy '.
                                $this->course->backup_path.'/'.$document->path.' to '.
                                dirname($path.$document->path).': destination directory not found'
                            );
                        }
                        if (!is_writeable(dirname($path.$document->path))) {
                            error_log(
                                'Course copy generated an ignorable error while trying to copy '.
                                $this->course->backup_path.'/'.$document->path.' to '.
                                dirname($path.$document->path).': destination directory not writable'
                            );
                        }
                    }
                } // end file doesn't exist
            }

            // add image information for area questions
            if (preg_match('/^quiz-.*$/', $document->title) &&
                preg_match('/^document\/images\/.*$/', $document->path)
            ) {
                $this->course->resources[RESOURCE_DOCUMENT]['image_quiz'][$document->title] = [
                    'path' => $document->path,
                    'title' => $document->title,
                    'source_id' => $document->source_id,
                    'destination_id' => $document->destination_id,
                ];
            }
        } // end for each

        // Delete sessions for the copy the new folder in session
        unset($_SESSION['new_base_foldername']);
        unset($_SESSION['orig_base_foldername']);
        unset($_SESSION['new_base_path']);
    }

    /**
     * Restore scorm documents
     * TODO @TODO check that the restore function with renaming doesn't break the scorm structure!
     * see #7029.
     */
    public function restore_scorm_documents()
    {
        $perm = api_get_permissions_for_new_directories();
        if ($this->course->has_resources(RESOURCE_SCORM)) {
            $resources = $this->course->resources;
            foreach ($resources[RESOURCE_SCORM] as $document) {
                $path = api_get_path(SYS_COURSE_PATH).$this->course->destination_path.'/';
                @mkdir(dirname($path.$document->path), $perm, true);
                if (file_exists($path.$document->path)) {
                    switch ($this->file_option) {
                        case FILE_OVERWRITE:
                            rmdirr($path.$document->path);
                            copyDirTo(
                                $this->course->backup_path.'/'.$document->path,
                                $path.$document->path,
                                false
                            );
                            break;
                        case FILE_SKIP:
                            break;
                        case FILE_RENAME:
                            $i = 1;
                            $ext = explode('.', basename($document->path));
                            if (count($ext) > 1) {
                                $ext = array_pop($ext);
                                $file_name_no_ext = substr($document->path, 0, -(strlen($ext) + 1));
                                $ext = '.'.$ext;
                            } else {
                                $ext = '';
                                $file_name_no_ext = $document->path;
                            }

                            $new_file_name = $file_name_no_ext.'_'.$i.$ext;
                            $file_exists = file_exists($path.$new_file_name);

                            while ($file_exists) {
                                $i++;
                                $new_file_name = $file_name_no_ext.'_'.$i.$ext;
                                $file_exists = file_exists($path.$new_file_name);
                            }

                            rename(
                                $this->course->backup_path.'/'.$document->path,
                                $this->course->backup_path.'/'.$new_file_name
                            );
                            copyDirTo(
                                $this->course->backup_path.'/'.$new_file_name,
                                $path.dirname($new_file_name),
                                false
                            );
                            rename(
                                $this->course->backup_path.'/'.$new_file_name,
                                $this->course->backup_path.'/'.$document->path
                            );
                            break;
                    } // end switch
                } else {
                    // end if file exists
                    copyDirTo(
                        $this->course->backup_path.'/'.$document->path,
                        $path.$document->path,
                        false
                    );
                }
            } // end for each
        }
    }

    /**
     * Restore forums.
     *
     * @param int $sessionId
     */
    public function restore_forums($sessionId = 0)
    {
        if ($this->course->has_resources(RESOURCE_FORUM)) {
            $sessionId = (int) $sessionId;
            $table_forum = Database::get_course_table(TABLE_FORUM);
            $resources = $this->course->resources;
            foreach ($resources[RESOURCE_FORUM] as $id => $forum) {
                $params = (array) $forum->obj;
                $cat_id = '';
                if (isset($this->course->resources[RESOURCE_FORUMCATEGORY]) &&
                    isset($this->course->resources[RESOURCE_FORUMCATEGORY][$params['forum_category']])) {
                    if ($this->course->resources[RESOURCE_FORUMCATEGORY][$params['forum_category']]->destination_id == -1) {
                        $cat_id = $this->restore_forum_category($params['forum_category'], $sessionId);
                    } else {
                        $cat_id = $this->course->resources[RESOURCE_FORUMCATEGORY][$params['forum_category']]->destination_id;
                    }
                }

                $params = self::DBUTF8_array($params);
                $params['c_id'] = $this->destination_course_id;
                $params['forum_category'] = $cat_id;
                $params['session_id'] = $sessionId;
                $params['start_time'] = isset($params['start_time']) && $params['start_time'] === '0000-00-00 00:00:00' ? null : $params['start_time'];
                $params['end_time'] = isset($params['end_time']) && $params['end_time'] === '0000-00-00 00:00:00' ? null : $params['end_time'];
                $params['forum_id'] = 0;
                unset($params['iid']);

                $params['forum_comment'] = DocumentManager::replaceUrlWithNewCourseCode(
                    $params['forum_comment'],
                    $this->course->code,
                    $this->course->destination_path,
                    $this->course->backup_path,
                    $this->course->info['path']
                );

                if (!empty($params['forum_image'])) {
                    $original_forum_image = $this->course->path.'upload/forum/images/'.$params['forum_image'];
                    if (file_exists($original_forum_image)) {
                        $new_forum_image = api_get_path(SYS_COURSE_PATH).
                            $this->destination_course_info['path'].'/upload/forum/images/'.$params['forum_image'];
                        @copy($original_forum_image, $new_forum_image);
                    }
                }

                $new_id = Database::insert($table_forum, $params);

                if ($new_id) {
                    $sql = "UPDATE $table_forum SET forum_id = iid WHERE iid = $new_id";
                    Database::query($sql);

                    api_item_property_update(
                        $this->destination_course_info,
                        TOOL_FORUM,
                        $new_id,
                        'ForumUpdated',
                        api_get_user_id(),
                        null,
                        null,
                        null,
                        null,
                        $sessionId
                    );

                    $this->course->resources[RESOURCE_FORUM][$id]->destination_id = $new_id;
                    $forum_topics = 0;
                    if (isset($this->course->resources[RESOURCE_FORUMTOPIC]) &&
                        is_array($this->course->resources[RESOURCE_FORUMTOPIC])
                    ) {
                        foreach ($this->course->resources[RESOURCE_FORUMTOPIC] as $topic_id => $topic) {
                            if ($topic->obj->forum_id == $id) {
                                $this->restore_topic($topic_id, $new_id, $sessionId);
                                $forum_topics++;
                            }
                        }
                    }
                    if ($forum_topics > 0) {
                        $sql = "UPDATE ".$table_forum." SET forum_threads = ".$forum_topics."
                                WHERE c_id = {$this->destination_course_id} AND forum_id = ".(int) $new_id;
                        Database::query($sql);
                    }
                }
            }
        }
    }

    /**
     * Restore forum-categories.
     */
    public function restore_forum_category($my_id = null, $sessionId = 0)
    {
        $forum_cat_table = Database::get_course_table(TABLE_FORUM_CATEGORY);
        $resources = $this->course->resources;
        $sessionId = (int) $sessionId;
        if (!empty($resources[RESOURCE_FORUMCATEGORY])) {
            foreach ($resources[RESOURCE_FORUMCATEGORY] as $id => $forum_cat) {
                if (!empty($my_id)) {
                    if ($my_id != $id) {
                        continue;
                    }
                }
                if ($forum_cat && !$forum_cat->is_restored()) {
                    $params = (array) $forum_cat->obj;
                    $params['c_id'] = $this->destination_course_id;
                    $params['cat_comment'] = DocumentManager::replaceUrlWithNewCourseCode(
                        $params['cat_comment'],
                        $this->course->code,
                        $this->course->destination_path,
                        $this->course->backup_path,
                        $this->course->info['path']
                    );
                    $params['session_id'] = $sessionId;
                    $params['cat_id'] = 0;
                    unset($params['iid']);

                    $params = self::DBUTF8_array($params);
                    $new_id = Database::insert($forum_cat_table, $params);

                    if ($new_id) {
                        $sql = "UPDATE $forum_cat_table SET cat_id = iid WHERE iid = $new_id";
                        Database::query($sql);

                        api_item_property_update(
                            $this->destination_course_info,
                            TOOL_FORUM_CATEGORY,
                            $new_id,
                            'ForumCategoryUpdated',
                            api_get_user_id(),
                            null,
                            null,
                            null,
                            null,
                            $sessionId
                        );
                        $this->course->resources[RESOURCE_FORUMCATEGORY][$id]->destination_id = $new_id;
                    }

                    if (!empty($my_id)) {
                        return $new_id;
                    }
                }
            }
        }
    }

    /**
     * Restore a forum-topic.
     *
     * @param false|string $forum_id
     *
     * @return int
     */
    public function restore_topic($thread_id, $forum_id, $sessionId = 0)
    {
        $table = Database::get_course_table(TABLE_FORUM_THREAD);
        $topic = $this->course->resources[RESOURCE_FORUMTOPIC][$thread_id];

        $sessionId = (int) $sessionId;
        $params = (array) $topic->obj;

        $params = self::DBUTF8_array($params);
        $params['c_id'] = $this->destination_course_id;
        $params['forum_id'] = $forum_id;
        $params['thread_poster_id'] = $this->first_teacher_id;
        $params['thread_date'] = api_get_utc_datetime();
        $params['thread_close_date'] = null;
        $params['thread_last_post'] = 0;
        $params['thread_replies'] = 0;
        $params['thread_views'] = 0;
        $params['session_id'] = $sessionId;
        $params['thread_id'] = 0;

        unset($params['iid']);

        $new_id = Database::insert($table, $params);

        if ($new_id) {
            $sql = "UPDATE $table SET thread_id = iid WHERE iid = $new_id";
            Database::query($sql);

            api_item_property_update(
                $this->destination_course_info,
                TOOL_FORUM_THREAD,
                $new_id,
                'ThreadAdded',
                api_get_user_id(),
                0,
                0,
                null,
                null,
                $sessionId
            );

            $this->course->resources[RESOURCE_FORUMTOPIC][$thread_id]->destination_id = $new_id;
            $topic_replies = -1;

            foreach ($this->course->resources[RESOURCE_FORUMPOST] as $post_id => $post) {
                if ($post->obj->thread_id == $thread_id) {
                    $topic_replies++;
                    $this->restore_post($post_id, $new_id, $forum_id, $sessionId);
                }
            }
        }

        return $new_id;
    }

    /**
     * Restore a forum-post.
     *
     * @TODO Restore tree-structure of posts. For example: attachments to posts.
     *
     * @param false|string $topic_id
     *
     * @return int
     */
    public function restore_post($id, $topic_id, $forum_id, $sessionId = 0)
    {
        $table_post = Database::get_course_table(TABLE_FORUM_POST);
        $post = $this->course->resources[RESOURCE_FORUMPOST][$id];
        $params = (array) $post->obj;
        $params['c_id'] = $this->destination_course_id;
        $params['forum_id'] = $forum_id;
        $params['thread_id'] = $topic_id;
        $params['poster_id'] = $this->first_teacher_id;
        $params['post_date'] = api_get_utc_datetime();
        $params['post_id'] = 0;
        unset($params['iid']);

        $params['post_text'] = DocumentManager::replaceUrlWithNewCourseCode(
            $params['post_text'],
            $this->course->code,
            $this->course->destination_path,
            $this->course->backup_path,
            $this->course->info['path']
        );
        $new_id = Database::insert($table_post, $params);

        if ($new_id) {
            $sql = "UPDATE $table_post SET post_id = iid WHERE iid = $new_id";
            Database::query($sql);

            api_item_property_update(
                $this->destination_course_info,
                TOOL_FORUM_POST,
                $new_id,
                'PostAdded',
                api_get_user_id(),
                0,
                0,
                null,
                null,
                $sessionId
            );
            $this->course->resources[RESOURCE_FORUMPOST][$id]->destination_id = $new_id;
        }

        return $new_id;
    }

    /**
     * Restore links.
     */
    public function restore_links($session_id = 0)
    {
        if ($this->course->has_resources(RESOURCE_LINK)) {
            $link_table = Database::get_course_table(TABLE_LINK);
            $resources = $this->course->resources;

            foreach ($resources[RESOURCE_LINK] as $oldLinkId => $link) {
                $cat_id = (int) $this->restore_link_category($link->category_id, $session_id);
                $sql = "SELECT MAX(display_order)
                        FROM $link_table
                        WHERE
                            c_id = ".$this->destination_course_id." AND
                            category_id='".$cat_id."'";
                $result = Database::query($sql);
                [$max_order] = Database::fetch_array($result);

                $params = [];
                $params['session_id'] = (int) $session_id;
                $params['c_id'] = $this->destination_course_id;
                $params['url'] = self::DBUTF8($link->url);
                $params['title'] = self::DBUTF8($link->title);
                $params['description'] = self::DBUTF8($link->description);
                $params['category_id'] = $cat_id;
                $params['on_homepage'] = $link->on_homepage;
                $params['display_order'] = $max_order + 1;
                $params['target'] = $link->target;

                $id = Database::insert($link_table, $params);

                if ($id) {
                    $sql = "UPDATE $link_table SET id = iid WHERE iid = $id";
                    Database::query($sql);

                    api_item_property_update(
                        $this->destination_course_info,
                        TOOL_LINK,
                        $id,
                        'LinkAdded',
                        api_get_user_id(),
                        null,
                        null,
                        null,
                        null,
                        $session_id
                    );

                    if (!isset($this->course->resources[RESOURCE_LINK][$oldLinkId])) {
                        $this->course->resources[RESOURCE_LINK][$oldLinkId] = new stdClass();
                    }
                    $this->course->resources[RESOURCE_LINK][$oldLinkId]->destination_id = $id;
                }
            }
        }
    }

    /**
     * Restore a link-category.
     *
     * @param int
     * @param int
     *
     * @return bool
     */
    public function restore_link_category($id, $sessionId = 0)
    {
        $params = [];
        $sessionId = (int) $sessionId;
        if (!empty($sessionId)) {
            $params['session_id'] = $sessionId;
        }

        if ($id == 0) {
            return 0;
        }
        $link_cat_table = Database::get_course_table(TABLE_LINK_CATEGORY);
        $resources = $this->course->resources;
        $link_cat = $resources[RESOURCE_LINKCATEGORY][$id];
        if (is_object($link_cat) && !$link_cat->is_restored()) {
            $sql = "SELECT MAX(display_order) FROM  $link_cat_table
                    WHERE c_id = ".$this->destination_course_id;
            $result = Database::query($sql);
            [$orderMax] = Database::fetch_array($result, 'NUM');
            $display_order = $orderMax + 1;

            $params['c_id'] = $this->destination_course_id;
            $params['category_title'] = self::DBUTF8($link_cat->title);
            $params['description'] = self::DBUTF8($link_cat->description);
            $params['display_order'] = $display_order;
            $new_id = Database::insert($link_cat_table, $params);

            if ($new_id) {
                $sql = "UPDATE $link_cat_table
                        SET id = iid
                        WHERE iid = $new_id";
                Database::query($sql);

                $courseInfo = api_get_course_info_by_id($this->destination_course_id);
                api_item_property_update(
                    $courseInfo,
                    TOOL_LINK_CATEGORY,
                    $new_id,
                    'LinkCategoryAdded',
                    api_get_user_id(),
                    null,
                    null,
                    null,
                    null,
                    $sessionId
                );
                api_set_default_visibility(
                    $new_id,
                    TOOL_LINK_CATEGORY,
                    0,
                    $courseInfo,
                    $sessionId
                );
            }

            $this->course->resources[RESOURCE_LINKCATEGORY][$id]->destination_id = $new_id;

            return $new_id;
        }

        return $this->course->resources[RESOURCE_LINKCATEGORY][$id]->destination_id;
    }

    /**
     * Restore tool intro.
     *
     * @param int
     */
    public function restore_tool_intro($sessionId = 0)
    {
        if ($this->course->has_resources(RESOURCE_TOOL_INTRO)) {
            $sessionId = (int) $sessionId;
            $tool_intro_table = Database::get_course_table(TABLE_TOOL_INTRO);
            $resources = $this->course->resources;
            foreach ($resources[RESOURCE_TOOL_INTRO] as $id => $tool_intro) {
                if (!$this->copySessionContent) {
                    $sql = "DELETE FROM $tool_intro_table
                        WHERE
                            c_id = ".$this->destination_course_id." AND
                            id='".self::DBUTF8escapestring($tool_intro->id)."'";
                    Database::query($sql);
                }

                $tool_intro->intro_text = DocumentManager::replaceUrlWithNewCourseCode(
                    $tool_intro->intro_text,
                    $this->course->code,
                    $this->course->destination_path,
                    $this->course->backup_path,
                    $this->course->info['path']
                );

                $params = [
                    'c_id' => $this->destination_course_id,
                    'id' => ($tool_intro->id === false ? '' : self::DBUTF8($tool_intro->id)),
                    'intro_text' => self::DBUTF8($tool_intro->intro_text),
                    'session_id' => $sessionId,
                ];

                $id = Database::insert($tool_intro_table, $params);
                if ($id) {
                    if (!isset($this->course->resources[RESOURCE_TOOL_INTRO][$id])) {
                        $this->course->resources[RESOURCE_TOOL_INTRO][$id] = new stdClass();
                    }

                    $this->course->resources[RESOURCE_TOOL_INTRO][$id]->destination_id = $id;
                }
            }
        }
    }

    /**
     * Restore events.
     *
     * @param int
     */
    public function restore_events($sessionId = 0)
    {
        if ($this->course->has_resources(RESOURCE_EVENT)) {
            $sessionId = (int) $sessionId;
            $table = Database::get_course_table(TABLE_AGENDA);
            $resources = $this->course->resources;
            foreach ($resources[RESOURCE_EVENT] as $id => $event) {
                // check resources inside html from ckeditor tool and copy correct urls into recipient course
                $event->content = DocumentManager::replaceUrlWithNewCourseCode(
                    $event->content,
                    $this->course->code,
                    $this->course->destination_path,
                    $this->course->backup_path,
                    $this->course->info['path']
                );

                $params = [
                    'c_id' => $this->destination_course_id,
                    'title' => self::DBUTF8($event->title),
                    'content' => ($event->content === false ? '' : self::DBUTF8($event->content)),
                    'all_day' => $event->all_day,
                    'start_date' => $event->start_date,
                    'end_date' => $event->end_date,
                    'session_id' => $sessionId,
                ];
                $new_event_id = Database::insert($table, $params);

                if ($new_event_id) {
                    $sql = "UPDATE $table SET id = iid WHERE iid = $new_event_id";
                    Database::query($sql);

                    // Choose default visibility
                    $toolVisibility = api_get_setting('tool_visible_by_default_at_creation');
                    $defaultLpVisibility = 'invisible';
                    if (isset($toolVisibility['learning_path']) && $toolVisibility['learning_path'] == 'true') {
                        $defaultLpVisibility = 'visible';
                    }

                    api_item_property_update(
                        $this->destination_course_info,
                        TOOL_CALENDAR_EVENT,
                        $new_event_id,
                        'AgendaAdded',
                        api_get_user_id(),
                        0,
                        0,
                        0,
                        0,
                        $sessionId
                    );

                    // Set the new Agenda to visible
                    api_item_property_update(
                        $this->destination_course_info,
                        TOOL_CALENDAR_EVENT,
                        $new_event_id,
                        $defaultLpVisibility,
                        api_get_user_id(),
                        0,
                        0,
                        0,
                        0,
                        $sessionId
                    );

                    if (!isset($this->course->resources[RESOURCE_EVENT][$id])) {
                        $this->course->resources[RESOURCE_EVENT][$id] = new stdClass();
                    }
                    $this->course->resources[RESOURCE_EVENT][$id]->destination_id = $new_event_id;
                }

                // Copy event attachment
                $origin_path = $this->course->backup_path.'/upload/calendar/';
                $destination_path = api_get_path(SYS_COURSE_PATH).$this->course->destination_path.'/upload/calendar/';

                if (!empty($this->course->orig)) {
                    $table_attachment = Database::get_course_table(TABLE_AGENDA_ATTACHMENT);
                    $sql = 'SELECT path, comment, size, filename
                            FROM '.$table_attachment.'
                            WHERE c_id = '.$this->destination_course_id.' AND agenda_id = '.$id;
                    $attachment_event = Database::query($sql);
                    $attachment_event = Database::fetch_object($attachment_event);

                    if (file_exists($origin_path.$attachment_event->path) &&
                        !is_dir($origin_path.$attachment_event->path)
                    ) {
                        $new_filename = uniqid(''); //ass seen in the add_agenda_attachment_file() function in agenda.inc.php
                        $copy_result = copy(
                            $origin_path.$attachment_event->path,
                            $destination_path.$new_filename
                        );
                        //$copy_result = true;
                        if ($copy_result) {
                            $table_attachment = Database::get_course_table(TABLE_AGENDA_ATTACHMENT);

                            $params = [
                                'c_id' => $this->destination_course_id,
                                'path' => self::DBUTF8($new_filename),
                                'comment' => self::DBUTF8($attachment_event->comment),
                                'size' => isset($attachment_event->size) ? $attachment_event->size : '',
                                'filename' => isset($attachment_event->filename) ? $attachment_event->filename : '',
                                'agenda_id' => $new_event_id,
                            ];
                            $id = Database::insert($table_attachment, $params);
                            if ($id) {
                                $sql = "UPDATE $table_attachment SET id = iid WHERE iid = $id";
                                Database::query($sql);
                            }
                        }
                    }
                } else {
                    // get the info of the file
                    if (!empty($event->attachment_path) &&
                        is_file($origin_path.$event->attachment_path) &&
                        is_readable($origin_path.$event->attachment_path)
                    ) {
                        $new_filename = uniqid(''); //ass seen in the add_agenda_attachment_file() function in agenda.inc.php
                        $copy_result = copy(
                            $origin_path.$event->attachment_path,
                            $destination_path.$new_filename
                        );
                        if ($copy_result) {
                            $table_attachment = Database::get_course_table(TABLE_AGENDA_ATTACHMENT);

                            $params = [
                                'c_id' => $this->destination_course_id,
                                'path' => self::DBUTF8($new_filename),
                                'comment' => self::DBUTF8($event->attachment_comment),
                                'size' => isset($event->attachment_size) ? $event->attachment_size : '',
                                'filename' => isset($event->attachment_filename) ? $event->attachment_filename : '',
                                'agenda_id' => $new_event_id,
                            ];
                            $id = Database::insert($table_attachment, $params);

                            if ($id) {
                                $sql = "UPDATE $table_attachment SET id = iid WHERE iid = $id";
                                Database::query($sql);

                                api_item_property_update(
                                    $this->destination_course_info,
                                    'calendar_event_attachment',
                                    $id,
                                    'AgendaAttachmentAdded',
                                    api_get_user_id()
                                );
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Restore course-description.
     *
     * @param int
     */
    public function restore_course_descriptions($session_id = 0)
    {
        if ($this->course->has_resources(RESOURCE_COURSEDESCRIPTION)) {
            $table = Database::get_course_table(TABLE_COURSE_DESCRIPTION);
            $resources = $this->course->resources;
            foreach ($resources[RESOURCE_COURSEDESCRIPTION] as $id => $cd) {
                $courseDescription = (array) $cd;

                $content = isset($courseDescription['content']) ? $courseDescription['content'] : '';
                $descriptionType = isset($courseDescription['description_type']) ? $courseDescription['description_type'] : '';
                $title = isset($courseDescription['title']) ? $courseDescription['title'] : '';

                // check resources inside html from ckeditor tool and copy correct urls into recipient course
                $description_content = DocumentManager::replaceUrlWithNewCourseCode(
                    $content,
                    $this->course->code,
                    $this->course->destination_path,
                    $this->course->backup_path,
                    $this->course->info['path']
                );

                $params = [];
                $session_id = (int) $session_id;
                $params['session_id'] = $session_id;
                $params['c_id'] = $this->destination_course_id;
                $params['description_type'] = self::DBUTF8($descriptionType);
                $params['title'] = self::DBUTF8($title);
                $params['content'] = ($description_content === false ? '' : self::DBUTF8($description_content));
                $params['progress'] = 0;

                $id = Database::insert($table, $params);
                if ($id) {
                    $sql = "UPDATE $table SET id = iid WHERE iid = $id";
                    Database::query($sql);

                    if (!isset($this->course->resources[RESOURCE_COURSEDESCRIPTION][$id])) {
                        $this->course->resources[RESOURCE_COURSEDESCRIPTION][$id] = new stdClass();
                    }
                    $this->course->resources[RESOURCE_COURSEDESCRIPTION][$id]->destination_id = $id;
                }
            }
        }
    }

    /**
     * Restore announcements.
     *
     * @param int
     */
    public function restore_announcements($sessionId = 0)
    {
        if ($this->course->has_resources(RESOURCE_ANNOUNCEMENT)) {
            $sessionId = (int) $sessionId;
            $table = Database::get_course_table(TABLE_ANNOUNCEMENT);
            $resources = $this->course->resources;
            foreach ($resources[RESOURCE_ANNOUNCEMENT] as $id => $announcement) {
                // check resources inside html from ckeditor tool and copy correct urls into recipient course
                $announcement->content = DocumentManager::replaceUrlWithNewCourseCode(
                    $announcement->content,
                    $this->course->code,
                    $this->course->destination_path,
                    $this->course->backup_path,
                    $this->course->info['path']
                );

                $params = [
                    'c_id' => $this->destination_course_id,
                    'title' => self::DBUTF8($announcement->title),
                    'content' => ($announcement->content === false ? '' : self::DBUTF8($announcement->content)),
                    'end_date' => $announcement->date,
                    'display_order' => $announcement->display_order,
                    'email_sent' => $announcement->email_sent,
                    'session_id' => $sessionId,
                ];

                $new_announcement_id = Database::insert($table, $params);

                if ($new_announcement_id) {
                    $sql = "UPDATE $table SET id = iid WHERE iid = $new_announcement_id";
                    Database::query($sql);

                    if (!isset($this->course->resources[RESOURCE_ANNOUNCEMENT][$id])) {
                        $this->course->resources[RESOURCE_ANNOUNCEMENT][$id] = new stdClass();
                    }
                    $this->course->resources[RESOURCE_ANNOUNCEMENT][$id]->destination_id = $new_announcement_id;
                }

                $origin_path = $this->course->backup_path.'/upload/announcements/';
                $destination_path = api_get_path(SYS_COURSE_PATH).$this->course->destination_path.'/upload/announcements/';

                // Copy announcement attachment file
                if (!empty($this->course->orig)) {
                    $table_attachment = Database::get_course_table(TABLE_ANNOUNCEMENT_ATTACHMENT);
                    $sql = 'SELECT path, comment, size, filename
                            FROM '.$table_attachment.'
                            WHERE
                                c_id = '.$this->destination_course_id.' AND
                                announcement_id = '.$id;
                    $attachment_event = Database::query($sql);
                    $attachment_event = Database::fetch_object($attachment_event);

                    if (file_exists($origin_path.$attachment_event->path) &&
                        !is_dir($origin_path.$attachment_event->path)
                    ) {
                        $new_filename = uniqid(''); //ass seen in the add_agenda_attachment_file() function in agenda.inc.php
                        $copy_result = copy(
                            $origin_path.$attachment_event->path,
                            $destination_path.$new_filename
                        );

                        if ($copy_result) {
                            $table_attachment = Database::get_course_table(TABLE_ANNOUNCEMENT_ATTACHMENT);

                            $params = [
                                'c_id' => $this->destination_course_id,
                                'path' => self::DBUTF8($new_filename),
                                'comment' => self::DBUTF8($attachment_event->comment),
                                'size' => $attachment_event->size,
                                'filename' => $attachment_event->filename,
                                'announcement_id' => $new_announcement_id,
                            ];

                            $attachmentId = Database::insert($table_attachment, $params);

                            if ($attachmentId) {
                                $sql = "UPDATE $table_attachment SET id = iid WHERE iid = $attachmentId";
                                Database::query($sql);
                            }
                        }
                    }
                } else {
                    // get the info of the file
                    if (!empty($announcement->attachment_path) &&
                        is_file($origin_path.$announcement->attachment_path) &&
                        is_readable($origin_path.$announcement->attachment_path)
                    ) {
                        $new_filename = uniqid(''); //ass seen in the add_agenda_attachment_file() function in agenda.inc.php
                        $copy_result = copy($origin_path.$announcement->attachment_path, $destination_path.$new_filename);

                        if ($copy_result) {
                            $table_attachment = Database::get_course_table(TABLE_ANNOUNCEMENT_ATTACHMENT);

                            $params = [
                                'c_id' => $this->destination_course_id,
                                'path' => self::DBUTF8($new_filename),
                                'comment' => self::DBUTF8($announcement->attachment_comment),
                                'size' => $announcement->attachment_size,
                                'filename' => $announcement->attachment_filename,
                                'announcement_id' => $new_announcement_id,
                            ];

                            $attachmentId = Database::insert($table_attachment, $params);

                            if ($attachmentId) {
                                $sql = "UPDATE $table_attachment SET id = iid WHERE iid = $attachmentId";
                                Database::query($sql);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Restore Quiz.
     *
     * @param int  $session_id
     * @param bool $respect_base_content
     */
    public function restore_quizzes(
        $session_id = 0,
        $respect_base_content = false
    ) {
        if ($this->course->has_resources(RESOURCE_QUIZ)) {
            $table_qui = Database::get_course_table(TABLE_QUIZ_TEST);
            $table_rel = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
            $table_doc = Database::get_course_table(TABLE_DOCUMENT);
            $resources = $this->course->resources;
            // Check if the "id" column still exists
            $idColumn = true;
            $columns = Database::listTableColumns($table_qui);
            if (!in_array('id', array_keys($columns))) {
                $idColumn = false;
            }

            foreach ($resources[RESOURCE_QUIZ] as $id => $quiz) {
                if (isset($quiz->obj)) {
                    // For new imports
                    $quiz = $quiz->obj;
                } else {
                    // For backward compatibility
                    $quiz->obj = $quiz;
                }

                $doc = '';
                if (!empty($quiz->sound)) {
                    if (isset($this->course->resources[RESOURCE_DOCUMENT][$quiz->sound]) &&
                        $this->course->resources[RESOURCE_DOCUMENT][$quiz->sound]->is_restored()) {
                        $sql = "SELECT path FROM $table_doc
                                WHERE
                                    c_id = ".$this->destination_course_id." AND
                                    id = ".$resources[RESOURCE_DOCUMENT][$quiz->sound]->destination_id;
                        $doc = Database::query($sql);
                        $doc = Database::fetch_object($doc);
                        $doc = str_replace('/audio/', '', $doc->path);
                    }
                }

                if ($id != -1) {
                    // check resources inside html from ckeditor tool and copy correct urls into recipient course
                    $quiz->description = DocumentManager::replaceUrlWithNewCourseCode(
                        $quiz->description,
                        $this->course->code,
                        $this->course->destination_path,
                        $this->course->backup_path,
                        $this->course->info['path']
                    );

                    $quiz->start_time = $quiz->start_time == '0000-00-00 00:00:00' ? null : $quiz->start_time;
                    $quiz->end_time = $quiz->end_time == '0000-00-00 00:00:00' ? null : $quiz->end_time;

                    global $_custom;
                    if (isset($_custom['exercises_clean_dates_when_restoring']) &&
                        $_custom['exercises_clean_dates_when_restoring']
                    ) {
                        $quiz->start_time = null;
                        $quiz->end_time = null;
                    }

                    $params = [
                        'c_id' => $this->destination_course_id,
                        'title' => self::DBUTF8($quiz->title),
                        'description' => ($quiz->description === false ? '' : self::DBUTF8($quiz->description)),
                        'type' => isset($quiz->quiz_type) ? (int) $quiz->quiz_type : $quiz->type,
                        'random' => (int) $quiz->random,
                        'active' => $quiz->active,
                        'sound' => self::DBUTF8($doc),
                        'max_attempt' => (int) $quiz->max_attempt,
                        'results_disabled' => (int) $quiz->results_disabled,
                        'access_condition' => $quiz->access_condition,
                        'pass_percentage' => $quiz->pass_percentage,
                        'feedback_type' => (int) $quiz->feedback_type,
                        'random_answers' => (int) $quiz->random_answers,
                        'random_by_category' => (int) $quiz->random_by_category,
                        'review_answers' => (int) $quiz->review_answers,
                        'propagate_neg' => (int) $quiz->propagate_neg,
                        'text_when_finished' => (string) $quiz->text_when_finished,
                        'expired_time' => (int) $quiz->expired_time,
                        'start_time' => $quiz->start_time,
                        'end_time' => $quiz->end_time,
                        'display_category_name' => 0,
                        'save_correct_answers' => isset($quiz->save_correct_answers) ? $quiz->save_correct_answers : 0,
                        'hide_question_title' => isset($quiz->hide_question_title) ? $quiz->hide_question_title : 0,
                    ];

                    $allow = api_get_configuration_value('allow_notification_setting_per_exercise');
                    if ($allow) {
                        $params['notifications'] = isset($quiz->notifications) ? $quiz->notifications : '';
                    }

                    if ($respect_base_content) {
                        $my_session_id = $quiz->session_id;
                        if (!empty($quiz->session_id)) {
                            $my_session_id = $session_id;
                        }
                        $params['session_id'] = $my_session_id;
                    } else {
                        if (!empty($session_id)) {
                            $session_id = (int) $session_id;
                            $params['session_id'] = $session_id;
                        }
                    }
                    $new_id = Database::insert($table_qui, $params);

                    if ($new_id && $idColumn) {
                        $sql = "UPDATE $table_qui SET id = iid WHERE iid = $new_id";
                        Database::query($sql);
                    }
                } else {
                    // $id = -1 identifies the fictional test for collecting
                    // orphan questions. We do not store it in the database.
                    $new_id = -1;
                }

                $this->course->resources[RESOURCE_QUIZ][$id]->destination_id = $new_id;
                $order = 0;
                if (!empty($quiz->question_ids)) {
                    foreach ($quiz->question_ids as $index => $question_id) {
                        $qid = $this->restore_quiz_question($question_id, $idColumn);
                        $question_order = $quiz->question_orders[$index] ? $quiz->question_orders[$index] : ++$order;
                        $sql = "INSERT IGNORE INTO $table_rel SET
                                c_id = ".$this->destination_course_id.",
                                question_id = $qid ,
                                exercice_id = $new_id ,
                                question_order = ".$question_order;
                        Database::query($sql);
                    }
                }
            }
        }
    }

    /**
     * Restore quiz-questions.
     *
     * @param int  $id       Question id
     * @param bool $idColumn Whether the 'id' column still exists in this table
     */
    public function restore_quiz_question($id, $idColumn = true)
    {
        $em = Database::getManager();
        $resources = $this->course->resources;
        /** @var QuizQuestion $question */
        $question = isset($resources[RESOURCE_QUIZQUESTION][$id]) ? $resources[RESOURCE_QUIZQUESTION][$id] : null;
        $new_id = 0;

        if (is_object($question)) {
            if ($question->is_restored()) {
                return $question->destination_id;
            }
            $table_que = Database::get_course_table(TABLE_QUIZ_QUESTION);
            $table_ans = Database::get_course_table(TABLE_QUIZ_ANSWER);
            $table_options = Database::get_course_table(TABLE_QUIZ_QUESTION_OPTION);

            // check resources inside html from ckeditor tool and copy correct urls into recipient course
            $question->description = DocumentManager::replaceUrlWithNewCourseCode(
                $question->description,
                $this->course->code,
                $this->course->destination_path,
                $this->course->backup_path,
                $this->course->info['path']
            );

            $imageNewId = '';
            if (preg_match('/^quiz-.*$/', $question->picture) &&
                isset($resources[RESOURCE_DOCUMENT]['image_quiz'][$question->picture])
            ) {
                $imageNewId = $resources[RESOURCE_DOCUMENT]['image_quiz'][$question->picture]['destination_id'];
            } else {
                if (isset($resources[RESOURCE_DOCUMENT][$question->picture])) {
                    $documentsToRestore = $resources[RESOURCE_DOCUMENT][$question->picture];
                    $imageNewId = $documentsToRestore->destination_id;
                }
            }
            $question->question = DocumentManager::replaceUrlWithNewCourseCode(
                $question->question,
                $this->course->code,
                $this->course->destination_path,
                $this->course->backup_path,
                $this->course->info['path']
            );
            $params = [
                'c_id' => $this->destination_course_id,
                'question' => self::DBUTF8($question->question),
                'description' => ($question->description === false ? '' : self::DBUTF8($question->description)),
                'ponderation' => self::DBUTF8($question->ponderation),
                'position' => self::DBUTF8($question->position),
                'type' => self::DBUTF8($question->quiz_type),
                'picture' => self::DBUTF8($imageNewId),
                'level' => self::DBUTF8($question->level),
                'extra' => self::DBUTF8($question->extra),
            ];

            $new_id = Database::insert($table_que, $params);

            if ($new_id) {
                // If the ID column is still present, update it, otherwise just
                // continue
                if ($idColumn) {
                    $sql = "UPDATE $table_que SET id = iid WHERE iid = $new_id";
                    Database::query($sql);
                }
            } else {
                // If no IID was generated, stop right there and return 0
                return 0;
            }

            $correctAnswers = [];
            $allAnswers = [];
            $onlyAnswers = [];

            if (in_array($question->quiz_type, [DRAGGABLE, MATCHING, MATCHING_DRAGGABLE])) {
                $tempAnswerList = $question->answers;
                foreach ($tempAnswerList as &$value) {
                    $value['answer'] = DocumentManager::replaceUrlWithNewCourseCode(
                        $value['answer'],
                        $this->course->code,
                        $this->course->destination_path,
                        $this->course->backup_path,
                        $this->course->info['path']
                    );
                }
                $allAnswers = array_column($tempAnswerList, 'answer', 'id');
            }

            if (in_array($question->quiz_type, [MATCHING, MATCHING_DRAGGABLE])) {
                $temp = [];
                foreach ($question->answers as $index => $answer) {
                    $temp[$answer['position']] = $answer;
                }

                foreach ($temp as $index => $answer) {
                    // check resources inside html from ckeditor tool and copy correct urls into recipient course
                    $answer['answer'] = DocumentManager::replaceUrlWithNewCourseCode(
                        $answer['answer'],
                        $this->course->code,
                        $this->course->destination_path,
                        $this->course->backup_path,
                        $this->course->info['path']
                    );

                    $answer['comment'] = DocumentManager::replaceUrlWithNewCourseCode(
                        $answer['comment'],
                        $this->course->code,
                        $this->course->destination_path,
                        $this->course->backup_path,
                        $this->course->info['path']
                    );

                    $quizAnswer = new CQuizAnswer();
                    $quizAnswer
                        ->setCId($this->destination_course_id)
                        ->setQuestionId($new_id)
                        ->setAnswer(self::DBUTF8($answer['answer']))
                        ->setCorrect($answer['correct'])
                        ->setComment($answer['comment'] === false ? '' : self::DBUTF8($answer['comment']))
                        ->setPonderation($answer['ponderation'])
                        ->setPosition($answer['position'])
                        ->setHotspotCoordinates($answer['hotspot_coordinates'])
                        ->setHotspotType($answer['hotspot_type'])
                        ->setIdAuto(0);

                    $em->persist($quizAnswer);
                    $em->flush();

                    $answerId = $quizAnswer->getId();

                    if ($answerId) {
                        $quizAnswer
                            ->setId($answerId)
                            ->setIdAuto($answerId);
                        $em->merge($quizAnswer);
                        $em->flush();

                        $correctAnswers[$answerId] = $answer['correct'];
                        $onlyAnswers[$answerId] = $answer['answer'];
                    }
                }
            } else {
                foreach ($question->answers as $index => $answer) {
                    // check resources inside html from ckeditor tool and copy correct urls into recipient course
                    $answer['answer'] = DocumentManager::replaceUrlWithNewCourseCode(
                        $answer['answer'],
                        $this->course->code,
                        $this->course->destination_path,
                        $this->course->backup_path,
                        $this->course->info['path']
                    );

                    $answer['comment'] = DocumentManager::replaceUrlWithNewCourseCode(
                        $answer['comment'],
                        $this->course->code,
                        $this->course->destination_path,
                        $this->course->backup_path,
                        $this->course->info['path']
                    );

                    $params = [
                        'c_id' => $this->destination_course_id,
                        'question_id' => $new_id,
                        'answer' => self::DBUTF8($answer['answer']),
                        'correct' => $answer['correct'],
                        'comment' => ($answer['comment'] === false ? '' : self::DBUTF8($answer['comment'])),
                        'ponderation' => $answer['ponderation'],
                        'position' => $answer['position'],
                        'hotspot_coordinates' => $answer['hotspot_coordinates'],
                        'hotspot_type' => $answer['hotspot_type'],
                        'id_auto' => 0,
                        'destination' => '',
                    ];

                    $answerId = Database::insert($table_ans, $params);

                    if ($answerId) {
                        if ($idColumn) {
                            $sql = "UPDATE $table_ans SET id = iid, id_auto = iid WHERE iid = $answerId";
                            Database::query($sql);
                        } else {
                            $sql = "UPDATE $table_ans SET id_auto = iid WHERE iid = $answerId";
                            Database::query($sql);
                        }
                    }

                    $correctAnswers[$answerId] = $answer['correct'];
                    $onlyAnswers[$answerId] = $answer['answer'];
                }
            }

            // Current course id
            $course_id = api_get_course_int_id();

            // Moving quiz_question_options
            if ($question->quiz_type == MULTIPLE_ANSWER_TRUE_FALSE) {
                if (count($question->question_options) < 3) {
                    $options = [1 => 'True', 2 => 'False', 3 => 'DoubtScore'];
                    $correct = [];
                    for ($i = 1; $i <= 3; $i++) {
                        $lastId = Question::saveQuestionOption(
                            $new_id,
                            $options[$i],
                            $this->destination_course_id,
                            $i
                        );
                        $correct[$i] = $lastId;
                    }

                    $correctAnswerValues = Database::select(
                        'DISTINCT(correct)',
                        $table_ans,
                        [
                            'WHERE' => [
                                'question_id = ? AND c_id = ? ' => [
                                    $new_id,
                                    $this->destination_course_id,
                                ],
                            ],
                            'ORDER' => 'correct ASC',
                        ]
                    );
                    $i = 1;
                    foreach ($correctAnswerValues as $correctAnswer) {
                        $params = [];
                        $params['correct'] = $correct[$i];
                        Database::update(
                            $table_ans,
                            $params,
                            [
                                'question_id = ? AND c_id = ? AND correct = ? ' => [
                                    $new_id,
                                    $this->destination_course_id,
                                    $correctAnswer['correct'],
                                ],
                            ],
                            false
                        );
                        $i++;
                    }
                } else {
                    $question_option_list = Question::readQuestionOption($id, $course_id);

                    // Question copied from the current platform
                    if ($question_option_list) {
                        $old_option_ids = [];
                        foreach ($question_option_list as $item) {
                            if (isset($item['iid'])) {
                                $old_id = $item['iid'];
                                unset($item['iid']);
                                unset($item['id']);
                            } else {
                                $old_id = $item['id'];
                                unset($item['id']);
                            }
                            $item['question_id'] = $new_id;
                            $item['c_id'] = $this->destination_course_id;
                            $question_option_id = Database::insert($table_options, $item);
                            if ($question_option_id && $idColumn) {
                                $old_option_ids[$old_id] = $question_option_id;
                                $sql = "UPDATE $table_options SET id = iid WHERE iid = $question_option_id";
                                Database::query($sql);
                            }
                        }
                        if ($old_option_ids) {
                            $new_answers = Database::select(
                                'iid, correct',
                                $table_ans,
                                [
                                    'WHERE' => [
                                        'question_id = ? AND c_id = ? ' => [
                                            $new_id,
                                            $this->destination_course_id,
                                        ],
                                    ],
                                ]
                            );

                            foreach ($new_answers as $answer_item) {
                                $params = [];
                                $params['correct'] = $old_option_ids[$answer_item['correct']];
                                Database::update(
                                    $table_ans,
                                    $params,
                                    [
                                        'iid = ? AND c_id = ? AND question_id = ? ' => [
                                            $answer_item['iid'],
                                            $this->destination_course_id,
                                            $new_id,
                                        ],
                                    ],
                                    false
                                );
                            }
                        }
                    } else {
                        $new_options = [];
                        if (isset($question->question_options)) {
                            foreach ($question->question_options as $obj) {
                                $item = [];
                                $item['question_id'] = $new_id;
                                $item['c_id'] = $this->destination_course_id;
                                $item['name'] = $obj->obj->name;
                                $item['position'] = $obj->obj->position;
                                $question_option_id = Database::insert($table_options, $item);

                                if ($question_option_id) {
                                    $new_options[$obj->obj->id] = $question_option_id;
                                    $sql = "UPDATE $table_options SET id = iid WHERE iid = $question_option_id";
                                    Database::query($sql);
                                }
                            }

                            foreach ($correctAnswers as $answer_id => $correct_answer) {
                                $params = [];
                                $params['correct'] = isset($new_options[$correct_answer]) ? $new_options[$correct_answer] : '';
                                Database::update(
                                    $table_ans,
                                    $params,
                                    [
                                        'iid = ? AND c_id = ? AND question_id = ? ' => [
                                            $answer_id,
                                            $this->destination_course_id,
                                            $new_id,
                                        ],
                                    ],
                                    false
                                );
                            }
                        }
                    }
                }
            }

            // Fix correct answers
            if (in_array($question->quiz_type, [DRAGGABLE, MATCHING, MATCHING_DRAGGABLE])) {
                foreach ($correctAnswers as $answer_id => $correct_answer) {
                    $params = [];

                    if (isset($allAnswers[$correct_answer])) {
                        $correct = '';
                        foreach ($onlyAnswers as $key => $value) {
                            if ($value == $allAnswers[$correct_answer]) {
                                $correct = $key;
                                break;
                            }
                        }

                        $params['correct'] = $correct;
                        Database::update(
                            $table_ans,
                            $params,
                            [
                                'iid = ? AND c_id = ? AND question_id = ? ' => [
                                    $answer_id,
                                    $this->destination_course_id,
                                    $new_id,
                                ],
                            ]
                        );
                    }
                }
            }

            $this->course->resources[RESOURCE_QUIZQUESTION][$id]->destination_id = $new_id;
        }

        return $new_id;
    }

    /**
     * @todo : add session id when used for session
     */
    public function restore_test_category($session_id, $respect_base_content, $destination_course_code)
    {
        // Cannot restore a test category to a session.
        if (!empty($session_id)) {
            return false;
        }

        $destinationCourseId = $this->destination_course_info['real_id'];
        // Let's restore the categories
        $categoryOldVsNewList = []; // used to build the quiz_question_rel_category table
        if ($this->course->has_resources(RESOURCE_TEST_CATEGORY)) {
            $resources = $this->course->resources;
            foreach ($resources[RESOURCE_TEST_CATEGORY] as $id => $courseCopyTestCategory) {
                $categoryOldVsNewList[$courseCopyTestCategory->source_id] = $id;
                // check if this test_category already exist in the destination BDD
                // do not Database::escape_string $title and $description, it will be done later
                $title = $courseCopyTestCategory->title;
                $description = $courseCopyTestCategory->description;
                if (TestCategory::categoryTitleExists($title, $destinationCourseId)) {
                    switch ($this->file_option) {
                        case FILE_SKIP:
                            //Do nothing
                            break;
                        case FILE_RENAME:
                            $new_title = $title.'_';
                            while (TestCategory::categoryTitleExists($new_title, $destinationCourseId)) {
                                $new_title .= '_';
                            }
                            $test_category = new TestCategory();
                            $test_category->name = $new_title;
                            $test_category->description = $description;
                            $new_id = $test_category->save($destinationCourseId);
                            $categoryOldVsNewList[$courseCopyTestCategory->source_id] = $new_id;
                            break;
                        case FILE_OVERWRITE:
                            // get category from source
                            $destinationCategoryId = TestCategory::get_category_id_for_title(
                                $title,
                                $destinationCourseId
                            );
                            if ($destinationCategoryId) {
                                $my_cat = new TestCategory();
                                $my_cat = $my_cat->getCategory($destinationCategoryId, $destinationCourseId);
                                $my_cat->name = $title;
                                $my_cat->description = $description;
                                $my_cat->modifyCategory($destinationCourseId);
                                $categoryOldVsNewList[$courseCopyTestCategory->source_id] = $destinationCategoryId;
                            }
                            break;
                    }
                } else {
                    // create a new test_category
                    $test_category = new TestCategory();
                    $test_category->name = $title;
                    $test_category->description = $description;
                    $new_id = $test_category->save($destinationCourseId);
                    $categoryOldVsNewList[$courseCopyTestCategory->source_id] = $new_id;
                }
                $this->course->resources[RESOURCE_TEST_CATEGORY][$id]->destination_id = $categoryOldVsNewList[$courseCopyTestCategory->source_id];
            }
        }

        // lets check if quizzes-question are restored too,
        // to redo the link between test_category and quizzes question for questions restored
        // we can use the source_id field
        // question source_id => category source_id
        if ($this->course->has_resources(RESOURCE_QUIZQUESTION)) {
            // check the category number of each question restored
            if (!empty($resources[RESOURCE_QUIZQUESTION])) {
                foreach ($resources[RESOURCE_QUIZQUESTION] as $id => $courseCopyQuestion) {
                    $newQuestionId = $resources[RESOURCE_QUIZQUESTION][$id]->destination_id;
                    $questionCategoryId = $courseCopyQuestion->question_category;
                    if ($newQuestionId > 0 &&
                        $questionCategoryId > 0 &&
                        isset($categoryOldVsNewList[$questionCategoryId])
                    ) {
                        TestCategory::addCategoryToQuestion(
                            $categoryOldVsNewList[$questionCategoryId],
                            $newQuestionId,
                            $destinationCourseId
                        );
                    }
                }
            }
        }
    }

    /**
     * Restore surveys.
     *
     * @param int $sessionId Optional. The session id
     */
    public function restore_surveys($sessionId = 0)
    {
        $sessionId = (int) $sessionId;
        if ($this->course->has_resources(RESOURCE_SURVEY)) {
            $table_sur = Database::get_course_table(TABLE_SURVEY);
            $table_que = Database::get_course_table(TABLE_SURVEY_QUESTION);
            $table_ans = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);
            $resources = $this->course->resources;
            foreach ($resources[RESOURCE_SURVEY] as $id => $survey) {
                $sql = 'SELECT survey_id FROM '.$table_sur.'
                        WHERE
                            c_id = '.$this->destination_course_id.' AND
                            code = "'.self::DBUTF8escapestring($survey->code).'" AND
                            lang = "'.self::DBUTF8escapestring($survey->lang).'" ';

                $result_check = Database::query($sql);

                // check resources inside html from ckeditor tool and copy correct urls into recipient course
                $survey->title = DocumentManager::replaceUrlWithNewCourseCode(
                    $survey->title,
                    $this->course->code,
                    $this->course->destination_path,
                    $this->course->backup_path,
                    $this->course->info['path']
                );

                $survey->subtitle = DocumentManager::replaceUrlWithNewCourseCode(
                    $survey->subtitle,
                    $this->course->code,
                    $this->course->destination_path,
                    $this->course->backup_path,
                    $this->course->info['path']
                );

                $survey->intro = DocumentManager::replaceUrlWithNewCourseCode(
                    $survey->intro,
                    $this->course->code,
                    $this->course->destination_path,
                    $this->course->backup_path,
                    $this->course->info['path']
                );

                $survey->surveythanks = DocumentManager::replaceUrlWithNewCourseCode(
                    $survey->surveythanks,
                    $this->course->code,
                    $this->course->destination_path,
                    $this->course->backup_path,
                    $this->course->info['path']
                );

                $params = [
                    'c_id' => $this->destination_course_id,
                    'code' => self::DBUTF8($survey->code),
                    'title' => ($survey->title === false ? '' : self::DBUTF8($survey->title)),
                    'subtitle' => ($survey->subtitle === false ? '' : self::DBUTF8($survey->subtitle)),
                    'author' => self::DBUTF8($survey->author),
                    'lang' => self::DBUTF8($survey->lang),
                    'avail_from' => self::DBUTF8($survey->avail_from),
                    'avail_till' => self::DBUTF8($survey->avail_till),
                    'is_shared' => self::DBUTF8($survey->is_shared),
                    'template' => self::DBUTF8($survey->template),
                    'intro' => $survey->intro === false ? '' : self::DBUTF8($survey->intro),
                    'surveythanks' => $survey->surveythanks === false ? '' : self::DBUTF8($survey->surveythanks),
                    'creation_date' => self::DBUTF8($survey->creation_date),
                    'invited' => '0',
                    'answered' => '0',
                    'invite_mail' => self::DBUTF8($survey->invite_mail),
                    'reminder_mail' => self::DBUTF8($survey->reminder_mail),
                    'session_id' => $sessionId,
                    'one_question_per_page' => isset($survey->one_question_per_page) ? $survey->one_question_per_page : 0,
                    'shuffle' => isset($survey->suffle) ? $survey->suffle : 0,
                ];

                // An existing survey exists with the same code and the same language
                if (Database::num_rows($result_check) == 1) {
                    switch ($this->file_option) {
                        case FILE_SKIP:
                            //Do nothing
                            break;
                        case FILE_RENAME:
                            $survey_code = $survey->code.'_';
                            $i = 1;
                            $temp_survey_code = $survey_code.$i;
                            while (!$this->is_survey_code_available($temp_survey_code)) {
                                $temp_survey_code = $survey_code.++$i;
                            }
                            $survey_code = $temp_survey_code;

                            $params['code'] = $survey_code;
                            $new_id = Database::insert($table_sur, $params);
                            if ($new_id) {
                                $sql = "UPDATE $table_sur SET survey_id = iid WHERE iid = $new_id";
                                Database::query($sql);

                                $this->course->resources[RESOURCE_SURVEY][$id]->destination_id = $new_id;
                                foreach ($survey->question_ids as $index => $question_id) {
                                    $qid = $this->restore_survey_question($question_id, $new_id);
                                    $sql = "UPDATE $table_que SET survey_id = $new_id
                                            WHERE c_id = ".$this->destination_course_id." AND question_id = $qid";
                                    Database::query($sql);
                                    $sql = "UPDATE $table_ans SET survey_id = $new_id
                                            WHERE  c_id = ".$this->destination_course_id." AND  question_id = $qid";
                                    Database::query($sql);
                                }
                            }
                            break;
                        case FILE_OVERWRITE:
                            // Delete the existing survey with the same code and language and
                            // import the one of the source course
                            // getting the information of the survey (used for when the survey is shared)
                            $sql = "SELECT * FROM $table_sur
                                    WHERE
                                        c_id = ".$this->destination_course_id." AND
                                        survey_id='".self::DBUTF8escapestring(Database::result($result_check, 0, 0))."'";
                            $result = Database::query($sql);
                            $survey_data = Database::fetch_array($result, 'ASSOC');

                            // if the survey is shared => also delete the shared content
                            if (isset($survey_data['survey_share']) && is_numeric($survey_data['survey_share'])) {
                                SurveyManager::delete_survey(
                                    $survey_data['survey_share'],
                                    true,
                                    $this->destination_course_id
                                );
                            }
                            SurveyManager::delete_survey(
                                $survey_data['survey_id'],
                                false,
                                $this->destination_course_id
                            );

                            // Insert the new source survey
                            $new_id = Database::insert($table_sur, $params);

                            if ($new_id) {
                                $sql = "UPDATE $table_sur SET survey_id = iid WHERE iid = $new_id";
                                Database::query($sql);

                                $this->course->resources[RESOURCE_SURVEY][$id]->destination_id = $new_id;
                                foreach ($survey->question_ids as $index => $question_id) {
                                    $qid = $this->restore_survey_question(
                                        $question_id,
                                        $new_id
                                    );
                                    $sql = "UPDATE $table_que SET survey_id = $new_id
                                            WHERE c_id = ".$this->destination_course_id." AND question_id = $qid";
                                    Database::query($sql);
                                    $sql = "UPDATE $table_ans SET survey_id = $new_id
                                            WHERE c_id = ".$this->destination_course_id." AND question_id = $qid";
                                    Database::query($sql);
                                }
                            }
                            break;
                        default:
                            break;
                    }
                } else {
                    // No existing survey with the same language and the same code, we just copy the survey
                    $new_id = Database::insert($table_sur, $params);

                    if ($new_id) {
                        $sql = "UPDATE $table_sur SET survey_id = iid WHERE iid = $new_id";
                        Database::query($sql);

                        $this->course->resources[RESOURCE_SURVEY][$id]->destination_id = $new_id;
                        foreach ($survey->question_ids as $index => $question_id) {
                            $qid = $this->restore_survey_question(
                                $question_id,
                                $new_id
                            );
                            $sql = "UPDATE $table_que SET survey_id = $new_id
                                    WHERE c_id = ".$this->destination_course_id." AND question_id = $qid";
                            Database::query($sql);
                            $sql = "UPDATE $table_ans SET survey_id = $new_id
                                    WHERE c_id = ".$this->destination_course_id." AND question_id = $qid";
                            Database::query($sql);
                        }
                    }
                }
            }
        }
    }

    /**
     * Check availability of a survey code.
     *
     * @param string $survey_code
     *
     * @return bool
     */
    public function is_survey_code_available($survey_code)
    {
        $table_sur = Database::get_course_table(TABLE_SURVEY);
        $sql = "SELECT * FROM $table_sur
                WHERE
                    c_id = ".$this->destination_course_id." AND
                    code = '".self::DBUTF8escapestring($survey_code)."'";
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Restore survey-questions.
     *
     * @param int    $id
     * @param string $survey_id
     */
    public function restore_survey_question($id, $survey_id)
    {
        $resources = $this->course->resources;
        $question = $resources[RESOURCE_SURVEYQUESTION][$id];
        $new_id = 0;

        if (is_object($question)) {
            if ($question->is_restored()) {
                return $question->destination_id;
            }
            $table_que = Database::get_course_table(TABLE_SURVEY_QUESTION);
            $table_ans = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);

            // check resources inside html from ckeditor tool and copy correct urls into recipient course
            $question->survey_question = DocumentManager::replaceUrlWithNewCourseCode(
                $question->survey_question,
                $this->course->code,
                $this->course->destination_path,
                $this->course->backup_path,
                $this->course->info['path']
            );

            $params = [
                'c_id' => $this->destination_course_id,
                'survey_id' => self::DBUTF8($survey_id),
                'survey_question' => ($question->survey_question === false ? '' : self::DBUTF8($question->survey_question)),
                'survey_question_comment' => self::DBUTF8($question->survey_question_comment),
                'type' => self::DBUTF8($question->survey_question_type),
                'display' => self::DBUTF8($question->display),
                'sort' => self::DBUTF8($question->sort),
                'shared_question_id' => self::DBUTF8($question->shared_question_id),
                'max_value' => self::DBUTF8($question->max_value),
            ];
            if (api_get_configuration_value('allow_required_survey_questions')) {
                if (isset($question->is_required)) {
                    $params['is_required'] = $question->is_required;
                }
            }

            $new_id = Database::insert($table_que, $params);
            if ($new_id) {
                $sql = "UPDATE $table_que SET question_id = iid WHERE iid = $new_id";
                Database::query($sql);

                foreach ($question->answers as $index => $answer) {
                    // check resources inside html from ckeditor tool and copy correct urls into recipient course
                    $answer['option_text'] = DocumentManager::replaceUrlWithNewCourseCode(
                        $answer['option_text'],
                        $this->course->code,
                        $this->course->destination_path,
                        $this->course->backup_path,
                        $this->course->info['path']
                    );

                    $params = [
                        'c_id' => $this->destination_course_id,
                        'question_id' => $new_id,
                        'option_text' => ($answer['option_text'] === false ? '' : self::DBUTF8($answer['option_text'])),
                        'sort' => $answer['sort'],
                        'survey_id' => self::DBUTF8($survey_id),
                    ];
                    $answerId = Database::insert($table_ans, $params);
                    if ($answerId) {
                        $sql = "UPDATE $table_ans SET question_option_id = iid
                                WHERE iid = $answerId";
                        Database::query($sql);
                    }
                }
                $this->course->resources[RESOURCE_SURVEYQUESTION][$id]->destination_id = $new_id;
            }
        }

        return $new_id;
    }

    /**
     * @param int  $sessionId
     * @param bool $baseContent
     */
    public function restore_learnpath_category($sessionId = 0, $baseContent = false)
    {
        $reuseExisting = false;

        if (isset($this->tool_copy_settings['learnpath_category']) &&
            isset($this->tool_copy_settings['learnpath_category']['reuse_existing']) &&
            true === $this->tool_copy_settings['learnpath_category']['reuse_existing']
        ) {
            $reuseExisting = true;
        }

        $tblLpCategory = Database::get_course_table(TABLE_LP_CATEGORY);

        if ($this->course->has_resources(RESOURCE_LEARNPATH_CATEGORY)) {
            $resources = $this->course->resources;
            /** @var LearnPathCategory $item */
            foreach ($resources[RESOURCE_LEARNPATH_CATEGORY] as $id => $item) {
                /** @var CLpCategory $lpCategory */
                $lpCategory = $item->object;

                if ($lpCategory) {
                    $existingLpCategory = Database::select(
                        'iid',
                        $tblLpCategory,
                        [
                            'WHERE' => [
                                'c_id = ? AND name = ?' => [$this->destination_course_id, $lpCategory->getName()],
                            ],
                        ],
                        'first'
                    );

                    if ($reuseExisting && !empty($existingLpCategory)) {
                        $categoryId = $existingLpCategory['iid'];
                    } else {
                        $values = [
                            'c_id' => $this->destination_course_id,
                            'name' => $lpCategory->getName(),
                        ];
                        $categoryId = \learnpath::createCategory($values);
                    }

                    if ($categoryId) {
                        $this->course->resources[RESOURCE_LEARNPATH_CATEGORY][$id]->destination_id = $categoryId;
                    }
                }
            }
        }
    }

    /**
     * Restoring learning paths.
     *
     * @param int        $session_id
     * @param bool|false $respect_base_content
     */
    public function restore_learnpaths($session_id = 0, $respect_base_content = false)
    {
        $session_id = (int) $session_id;
        if ($this->course->has_resources(RESOURCE_LEARNPATH)) {
            $table_main = Database::get_course_table(TABLE_LP_MAIN);
            $table_item = Database::get_course_table(TABLE_LP_ITEM);
            $table_tool = Database::get_course_table(TABLE_TOOL_LIST);

            $resources = $this->course->resources;
            $origin_path = $this->course->backup_path.'/upload/learning_path/images/';
            $destination_path = api_get_path(SYS_COURSE_PATH).
                $this->course->destination_path.'/upload/learning_path/images/';

            // Choose default visibility
            $toolVisibility = api_get_setting('tool_visible_by_default_at_creation');
            $defaultLpVisibility = 'invisible';
            if (isset($toolVisibility['learning_path']) && $toolVisibility['learning_path'] == 'true') {
                $defaultLpVisibility = 'visible';
            }

            $lpIds = [];
            foreach ($resources[RESOURCE_LEARNPATH] as $id => $lp) {
                $condition_session = '';
                if (!empty($session_id)) {
                    if ($respect_base_content) {
                        $my_session_id = $lp->session_id;
                        if (!empty($lp->session_id)) {
                            $my_session_id = $session_id;
                        }
                        $condition_session = $my_session_id;
                    } else {
                        $session_id = (int) $session_id;
                        $condition_session = $session_id;
                    }
                }

                // Adding the LP image
                if (!empty($lp->preview_image)) {
                    $new_filename = uniqid('').substr(
                        $lp->preview_image,
                        strlen($lp->preview_image) - 7,
                        strlen($lp->preview_image)
                    );

                    if (file_exists($origin_path.$lp->preview_image) &&
                        !is_dir($origin_path.$lp->preview_image)
                    ) {
                        $copy_result = copy(
                            $origin_path.$lp->preview_image,
                            $destination_path.$new_filename
                        );
                        if ($copy_result) {
                            $lp->preview_image = $new_filename;
                            // Create 64 version from original
                            $temp = new \Image($destination_path.$new_filename);
                            $temp->resize(64);
                            $pathInfo = pathinfo($new_filename);
                            if ($pathInfo) {
                                $filename = $pathInfo['filename'];
                                $extension = $pathInfo['extension'];
                                $temp->send_image($destination_path.'/'.$filename.'.64.'.$extension);
                            }
                        } else {
                            $lp->preview_image = '';
                        }
                    }
                }

                if ($this->add_text_in_items) {
                    $lp->name = $lp->name.' '.get_lang('CopyLabelSuffix');
                }

                if (isset($this->tool_copy_settings['learnpaths'])) {
                    if (isset($this->tool_copy_settings['learnpaths']['reset_dates']) &&
                        $this->tool_copy_settings['learnpaths']['reset_dates']
                    ) {
                        $lp->created_on = api_get_utc_datetime();
                        $lp->modified_on = api_get_utc_datetime();
                        $lp->publicated_on = null;
                    }
                }

                $lp->expired_on = isset($lp->expired_on) && $lp->expired_on === '0000-00-00 00:00:00' ? null : $lp->expired_on;
                $lp->publicated_on = isset($lp->publicated_on) && $lp->publicated_on === '0000-00-00 00:00:00' ? null : $lp->publicated_on;

                if (isset($lp->categoryId)) {
                    $lp->categoryId = (int) $lp->categoryId;
                }

                $categoryId = 0;
                if (!empty($lp->categoryId)) {
                    if (isset($resources[RESOURCE_LEARNPATH_CATEGORY][$lp->categoryId])) {
                        $categoryId = $resources[RESOURCE_LEARNPATH_CATEGORY][$lp->categoryId]->destination_id;
                    }
                }
                $params = [
                    'c_id' => $this->destination_course_id,
                    'lp_type' => $lp->lp_type,
                    'name' => self::DBUTF8($lp->name),
                    'path' => self::DBUTF8($lp->path),
                    'ref' => $lp->ref,
                    'description' => self::DBUTF8($lp->description),
                    'content_local' => self::DBUTF8($lp->content_local),
                    'default_encoding' => self::DBUTF8($lp->default_encoding),
                    'default_view_mod' => self::DBUTF8($lp->default_view_mod),
                    'prevent_reinit' => self::DBUTF8($lp->prevent_reinit),
                    'force_commit' => self::DBUTF8($lp->force_commit),
                    'content_maker' => self::DBUTF8($lp->content_maker),
                    'display_order' => self::DBUTF8($lp->display_order),
                    'js_lib' => self::DBUTF8($lp->js_lib),
                    'content_license' => self::DBUTF8($lp->content_license),
                    'author' => self::DBUTF8($lp->author),
                    'preview_image' => self::DBUTF8($lp->preview_image),
                    'use_max_score' => self::DBUTF8($lp->use_max_score),
                    'autolaunch' => self::DBUTF8(isset($lp->autolaunch) ? $lp->autolaunch : ''),
                    'created_on' => empty($lp->created_on) ? api_get_utc_datetime() : self::DBUTF8($lp->created_on),
                    'modified_on' => empty($lp->modified_on) ? api_get_utc_datetime() : self::DBUTF8($lp->modified_on),
                    'publicated_on' => empty($lp->publicated_on) ? api_get_utc_datetime() : self::DBUTF8($lp->publicated_on),
                    'expired_on' => self::DBUTF8($lp->expired_on),
                    'debug' => self::DBUTF8($lp->debug),
                    'theme' => '',
                    'session_id' => $session_id,
                    'prerequisite' => (int) $lp->prerequisite,
                    'hide_toc_frame' => self::DBUTF8(isset($lp->hideTableOfContents) ? $lp->hideTableOfContents : 0),
                    'subscribe_users' => self::DBUTF8(isset($lp->subscribeUsers) ? $lp->subscribeUsers : 0),
                    'seriousgame_mode' => 0,
                    'category_id' => $categoryId,
                    'max_attempts' => 0,
                ];

                if (api_get_configuration_value('lp_minimum_time')) {
                    if (isset($lp->accumulateWorkTime) && !empty($lp->accumulateWorkTime)) {
                        $params['accumulate_work_time'] = $lp->accumulateWorkTime;
                    }
                }

                if (!empty($condition_session)) {
                    $params['session_id'] = $condition_session;
                }

                $new_lp_id = Database::insert($table_main, $params);
                if ($new_lp_id) {
                    $lpIds[$id] = $new_lp_id;
                    // The following only makes sense if a new LP was
                    // created in the destination course
                    $sql = "UPDATE $table_main SET id = iid WHERE iid = $new_lp_id";
                    Database::query($sql);

                    if ($lp->visibility) {
                        $params = [
                            'c_id' => $this->destination_course_id,
                            'name' => self::DBUTF8($lp->name),
                            'link' => "lp/lp_controller.php?action=view&lp_id=$new_lp_id&id_session=$session_id",
                            'image' => 'scormbuilder.gif',
                            'visibility' => '0',
                            'admin' => '0',
                            'address' => 'squaregrey.gif',
                            'session_id' => $session_id,
                        ];
                        $insertId = Database::insert($table_tool, $params);
                        if ($insertId) {
                            $sql = "UPDATE $table_tool SET id = iid WHERE iid = $insertId";
                            Database::query($sql);
                        }
                    }

                    if (isset($lp->extraFields) && !empty($lp->extraFields)) {
                        $extraFieldValue = new \ExtraFieldValue('lp');
                        foreach ($lp->extraFields as $extraField) {
                            $params = [
                                'item_id' => $new_lp_id,
                                'value' => $extraField['value'],
                                'variable' => $extraField['variable'],
                            ];
                            $extraFieldValue->save($params);
                        }
                    }

                    api_item_property_update(
                        $this->destination_course_info,
                        TOOL_LEARNPATH,
                        $new_lp_id,
                        'LearnpathAdded',
                        api_get_user_id(),
                        0,
                        0,
                        0,
                        0,
                        $session_id
                    );

                    // Set the new LP to visible
                    api_item_property_update(
                        $this->destination_course_info,
                        TOOL_LEARNPATH,
                        $new_lp_id,
                        $defaultLpVisibility,
                        api_get_user_id(),
                        0,
                        0,
                        0,
                        0,
                        $session_id
                    );

                    $new_item_ids = [];
                    $parent_item_ids = [];
                    $previous_item_ids = [];
                    $next_item_ids = [];
                    $old_prerequisite = [];
                    $old_refs = [];
                    $prerequisite_ids = [];

                    foreach ($lp->get_items() as $index => $item) {
                        // we set the ref code here and then we update in a for loop
                        $ref = $item['ref'];

                        // Dealing with path the same way as ref as some data has
                        // been put into path when it's a local resource
                        // Only fix the path for no scos
                        if ($item['item_type'] === 'sco') {
                            $path = $item['path'];
                        } else {
                            $path = $this->get_new_id($item['item_type'], $item['path']);
                        }

                        $item['item_type'] = $item['item_type'] === 'dokeos_chapter' ? 'dir' : $item['item_type'];

                        $masteryScore = $item['mastery_score'];
                        // If item is a chamilo quiz, then use the max score as mastery_score.
                        if ($item['item_type'] === 'quiz') {
                            if (empty($masteryScore)) {
                                $masteryScore = $item['max_score'];
                            }
                        }

                        $prerequisiteMinScore = $item['prerequisite_min_score'] ?? null;
                        $prerequisiteMaxScore = $item['prerequisite_max_score'] ?? null;

                        $params = [
                            'c_id' => $this->destination_course_id,
                            'lp_id' => self::DBUTF8($new_lp_id),
                            'item_type' => self::DBUTF8($item['item_type']),
                            'ref' => self::DBUTF8($ref),
                            'path' => self::DBUTF8($path),
                            'title' => self::DBUTF8($item['title']),
                            'description' => self::DBUTF8($item['description']),
                            'min_score' => self::DBUTF8($item['min_score']),
                            'max_score' => self::DBUTF8($item['max_score']),
                            'mastery_score' => self::DBUTF8($masteryScore),
                            'prerequisite_min_score' => $prerequisiteMinScore,
                            'prerequisite_max_score' => $prerequisiteMaxScore,
                            'parent_item_id' => self::DBUTF8($item['parent_item_id']),
                            'previous_item_id' => self::DBUTF8($item['previous_item_id']),
                            'next_item_id' => self::DBUTF8($item['next_item_id']),
                            'display_order' => self::DBUTF8($item['display_order']),
                            'prerequisite' => self::DBUTF8($item['prerequisite']),
                            'parameters' => self::DBUTF8($item['parameters']),
                            'audio' => self::DBUTF8($item['audio']),
                            'launch_data' => self::DBUTF8($item['launch_data']),
                        ];

                        $new_item_id = Database::insert($table_item, $params);
                        if ($new_item_id) {
                            $sql = "UPDATE $table_item SET id = iid WHERE iid = $new_item_id";
                            Database::query($sql);

                            //save a link between old and new item IDs
                            $new_item_ids[$item['id']] = $new_item_id;
                            //save a reference of items that need a parent_item_id refresh
                            $parent_item_ids[$new_item_id] = $item['parent_item_id'];
                            //save a reference of items that need a previous_item_id refresh
                            $previous_item_ids[$new_item_id] = $item['previous_item_id'];
                            //save a reference of items that need a next_item_id refresh
                            $next_item_ids[$new_item_id] = $item['next_item_id'];

                            if (!empty($item['prerequisite'])) {
                                if ($lp->lp_type == '2') {
                                    // if is an sco
                                    $old_prerequisite[$new_item_id] = $item['prerequisite'];
                                } else {
                                    $old_prerequisite[$new_item_id] = isset($new_item_ids[$item['prerequisite']]) ? $new_item_ids[$item['prerequisite']] : '';
                                }
                            }

                            if (!empty($ref)) {
                                if ($lp->lp_type == '2') {
                                    // if is an sco
                                    $old_refs[$new_item_id] = $ref;
                                } elseif (isset($new_item_ids[$ref])) {
                                    $old_refs[$new_item_id] = $new_item_ids[$ref];
                                }
                            }
                            $prerequisite_ids[$new_item_id] = $item['prerequisite'];

                            // Upload audio.
                            if (!empty($item['audio'])) {
                                $courseInfo = api_get_course_info_by_id($this->destination_course_id);
                                // Create the audio folder if it does not exist yet.
                                $filepath = api_get_path(SYS_COURSE_PATH).$this->course->destination_path.'/document/';
                                if (!is_dir($filepath.'audio')) {
                                    mkdir(
                                        $filepath.'audio',
                                        api_get_permissions_for_new_directories()
                                    );
                                    $audioId = add_document(
                                        $courseInfo,
                                        '/audio',
                                        'folder',
                                        0,
                                        'audio',
                                        '',
                                        0,
                                        true,
                                        null,
                                        $session_id,
                                        api_get_user_id()
                                    );
                                    api_item_property_update(
                                        $courseInfo,
                                        TOOL_DOCUMENT,
                                        $audioId,
                                        'FolderCreated',
                                        api_get_user_id(),
                                        null,
                                        null,
                                        null,
                                        null,
                                        $session_id
                                    );
                                    api_item_property_update(
                                        $courseInfo,
                                        TOOL_DOCUMENT,
                                        $audioId,
                                        'invisible',
                                        api_get_user_id(),
                                        null,
                                        null,
                                        null,
                                        null,
                                        $session_id
                                    );
                                }
                                $originAudioFile = $this->course->backup_path.'/document'.$item['audio'];
                                $uploadedFile = [
                                    'name' => basename($originAudioFile),
                                    'tmp_name' => $originAudioFile,
                                    'size' => filesize($originAudioFile),
                                    'type' => null,
                                    'from_file' => true,
                                    'copy_file' => true,
                                ];
                                $filePath = handle_uploaded_document(
                                    $courseInfo,
                                    $uploadedFile,
                                    api_get_path(SYS_COURSE_PATH).$this->course->destination_path.'/document',
                                    '/audio',
                                    api_get_user_id(),
                                    '',
                                    '',
                                    '',
                                    '',
                                    false
                                );
                            }
                        }
                    }

                    // Updating prerequisites
                    foreach ($old_prerequisite as $key => $my_old_prerequisite) {
                        if ($my_old_prerequisite != '') {
                            $my_old_prerequisite = Database::escape_string($my_old_prerequisite);
                            $sql = "UPDATE $table_item SET prerequisite = '$my_old_prerequisite'
                                    WHERE c_id = ".$this->destination_course_id." AND id = '".$key."'  ";
                            Database::query($sql);
                        }
                    }

                    // Updating refs
                    foreach ($old_refs as $key => $my_old_ref) {
                        if ($my_old_ref != '') {
                            $my_old_ref = Database::escape_string($my_old_ref);
                            $sql = "UPDATE $table_item SET ref = '$my_old_ref'
                                    WHERE c_id = ".$this->destination_course_id." AND id = $key";
                            Database::query($sql);
                        }
                    }

                    foreach ($parent_item_ids as $new_item_id => $parent_item_old_id) {
                        $new_item_id = (int) $new_item_id;
                        $parent_new_id = 0;
                        if ($parent_item_old_id != 0) {
                            $parent_new_id = isset($new_item_ids[$parent_item_old_id]) ? $new_item_ids[$parent_item_old_id] : 0;
                        }

                        $parent_new_id = Database::escape_string($parent_new_id);
                        $sql = "UPDATE $table_item SET parent_item_id = '$parent_new_id'
                                WHERE c_id = ".$this->destination_course_id." AND id = $new_item_id";
                        Database::query($sql);
                    }

                    foreach ($previous_item_ids as $new_item_id => $previous_item_old_id) {
                        $new_item_id = (int) $new_item_id;
                        $previous_new_id = 0;
                        if ($previous_item_old_id != 0) {
                            $previous_new_id = isset($new_item_ids[$previous_item_old_id]) ? $new_item_ids[$previous_item_old_id] : 0;
                        }
                        $previous_new_id = Database::escape_string($previous_new_id);
                        $sql = "UPDATE $table_item SET previous_item_id = '$previous_new_id'
                                WHERE c_id = ".$this->destination_course_id." AND id = '".$new_item_id."'";
                        Database::query($sql);
                    }

                    foreach ($next_item_ids as $new_item_id => $next_item_old_id) {
                        $new_item_id = (int) $new_item_id;
                        $next_new_id = 0;
                        if ($next_item_old_id != 0) {
                            $next_new_id = isset($new_item_ids[$next_item_old_id]) ? $new_item_ids[$next_item_old_id] : 0;
                        }
                        $next_new_id = Database::escape_string($next_new_id);
                        $sql = "UPDATE $table_item SET next_item_id = '$next_new_id'
                                WHERE c_id = ".$this->destination_course_id." AND id = '".$new_item_id."'";
                        Database::query($sql);
                    }

                    foreach ($prerequisite_ids as $new_item_id => $prerequisite_old_id) {
                        $new_item_id = (int) $new_item_id;
                        $prerequisite_new_id = 0;
                        if ($prerequisite_old_id != 0) {
                            $prerequisite_new_id = $new_item_ids[$prerequisite_old_id];
                        }
                        $prerequisite_new_id = Database::escape_string($prerequisite_new_id);
                        $sql = "UPDATE $table_item SET prerequisite = '$prerequisite_new_id'
                                WHERE c_id = ".$this->destination_course_id." AND id = $new_item_id";
                        Database::query($sql);
                    }
                    $this->course->resources[RESOURCE_LEARNPATH][$id]->destination_id = $new_lp_id;
                }
            }
            // It updates the current lp id prerequisites
            if (!empty($lpIds)) {
                foreach ($lpIds as $oldLpId => $newLpId) {
                    $sql = "UPDATE $table_main SET prerequisite = '$newLpId'
                                WHERE c_id = ".$this->destination_course_id." AND prerequisite = '$oldLpId'";
                    Database::query($sql);
                }
            }
        }
    }

    /**
     * Copy all directory and sub directory.
     *
     * @param string $source The path origin
     * @param string $dest   The path destination
     * @param bool Option Overwrite
     *
     * @deprecated
     */
    public function allow_create_all_directory($source, $dest, $overwrite = false)
    {
        if (!is_dir($dest)) {
            mkdir($dest, api_get_permissions_for_new_directories());
        }
        if ($handle = opendir($source)) {
            // if the folder exploration is sucsessful, continue
            while (false !== ($file = readdir($handle))) {
                // as long as storing the next file to $file is successful, continue
                if ($file != '.' && $file != '..') {
                    $path = $source.'/'.$file;
                    if (is_file($path)) {
                        /* if (!is_file($dest . '/' . $file) || $overwrite)
                         if (!@copy($path, $dest . '/' . $file)) {
                             echo '<font color="red">File ('.$path.') '.get_lang('NotHavePermission').'</font>';
                         }*/
                    } elseif (is_dir($path)) {
                        if (!is_dir($dest.'/'.$file)) {
                            mkdir($dest.'/'.$file);
                        }
                        self::allow_create_all_directory($path, $dest.'/'.$file, $overwrite);
                    }
                }
            }
            closedir($handle);
        }
    }

    /**
     * Gets the new ID of one specific tool item from the tool name and the old ID.
     *
     * @param	string	Tool name
     * @param	int	Old ID
     *
     * @return int New ID
     */
    public function get_new_id($tool, $ref)
    {
        // Check if the value exist in the current array.
        if ($tool === 'hotpotatoes') {
            $tool = 'document';
        }

        if ($tool === 'student_publication') {
            $tool = RESOURCE_WORK;
        }

        if ('xapi' === $tool && $this->isXapiEnabled) {
            $tool = RESOURCE_XAPI_TOOL;
        }

        if ('h5p' === $tool && $this->isH5pEnabled) {
            $tool = RESOURCE_H5P_TOOL;
        }

        if (isset($this->course->resources[$tool][$ref]) &&
            isset($this->course->resources[$tool][$ref]->destination_id) &&
            !empty($this->course->resources[$tool][$ref]->destination_id)
        ) {
            return $this->course->resources[$tool][$ref]->destination_id;
        }

        // Check if the course is the same (last hope).
        if ($this->course_origin_id == $this->destination_course_id) {
            return $ref;
        }

        return '';
    }

    /**
     * Restore glossary.
     */
    public function restore_glossary($sessionId = 0)
    {
        $sessionId = (int) $sessionId;
        if ($this->course->has_resources(RESOURCE_GLOSSARY)) {
            $table_glossary = Database::get_course_table(TABLE_GLOSSARY);
            $resources = $this->course->resources;
            foreach ($resources[RESOURCE_GLOSSARY] as $id => $glossary) {
                $params = [];
                if (!empty($sessionId)) {
                    $params['session_id'] = $sessionId;
                }

                // check resources inside html from ckeditor tool and copy correct urls into recipient course
                $glossary->description = DocumentManager::replaceUrlWithNewCourseCode(
                    $glossary->description,
                    $this->course->code,
                    $this->course->destination_path,
                    $this->course->backup_path,
                    $this->course->info['path']
                );

                $params['c_id'] = $this->destination_course_id;
                $params['description'] = ($glossary->description === false ? '' : self::DBUTF8($glossary->description));
                $params['display_order'] = $glossary->display_order;
                $params['name'] = self::DBUTF8($glossary->name);
                $params['glossary_id'] = 0;
                $my_id = Database::insert($table_glossary, $params);
                if ($my_id) {
                    $sql = "UPDATE $table_glossary SET glossary_id = iid WHERE iid = $my_id";
                    Database::query($sql);

                    api_item_property_update(
                        $this->destination_course_info,
                        TOOL_GLOSSARY,
                        $my_id,
                        'GlossaryAdded',
                        api_get_user_id(),
                        null,
                        null,
                        null,
                        null,
                        $sessionId
                    );

                    if (!isset($this->course->resources[RESOURCE_GLOSSARY][$id])) {
                        $this->course->resources[RESOURCE_GLOSSARY][$id] = new stdClass();
                    }

                    $this->course->resources[RESOURCE_GLOSSARY][$id]->destination_id = $my_id;
                }
            }
        }
    }

    /**
     * @param int $sessionId
     */
    public function restore_wiki($sessionId = 0)
    {
        if ($this->course->has_resources(RESOURCE_WIKI)) {
            // wiki table of the target course
            $table_wiki = Database::get_course_table(TABLE_WIKI);
            $table_wiki_conf = Database::get_course_table(TABLE_WIKI_CONF);

            // storing all the resources that have to be copied in an array
            $resources = $this->course->resources;

            foreach ($resources[RESOURCE_WIKI] as $id => $wiki) {
                // the sql statement to insert the groups from the old course to the new course
                // check resources inside html from ckeditor tool and copy correct urls into recipient course
                $wiki->content = DocumentManager::replaceUrlWithNewCourseCode(
                    $wiki->content,
                    $this->course->code,
                    $this->course->destination_path,
                    $this->course->backup_path,
                    $this->course->info['path']
                );

                $params = [
                    'c_id' => $this->destination_course_id,
                    'page_id' => self::DBUTF8($wiki->page_id),
                    'reflink' => self::DBUTF8($wiki->reflink),
                    'title' => self::DBUTF8($wiki->title),
                    'content' => ($wiki->content === false ? '' : self::DBUTF8($wiki->content)),
                    'user_id' => intval($wiki->user_id),
                    'group_id' => intval($wiki->group_id),
                    'dtime' => self::DBUTF8($wiki->dtime),
                    'progress' => self::DBUTF8($wiki->progress),
                    'version' => intval($wiki->version),
                    'session_id' => !empty($sessionId) ? intval($sessionId) : 0,
                    'addlock' => 0,
                    'editlock' => 0,
                    'visibility' => 0,
                    'addlock_disc' => 0,
                    'visibility_disc' => 0,
                    'ratinglock_disc' => 0,
                    'assignment' => 0,
                    'comment' => '',
                    'is_editing' => 0,
                    'linksto' => 0,
                    'tag' => '',
                    'user_ip' => '',
                ];

                $new_id = Database::insert($table_wiki, $params);

                if ($new_id) {
                    $sql = "UPDATE $table_wiki SET page_id = '$new_id', id = iid
                            WHERE c_id = ".$this->destination_course_id." AND iid = '$new_id'";
                    Database::query($sql);

                    $this->course->resources[RESOURCE_WIKI][$id]->destination_id = $new_id;

                    // we also add an entry in wiki_conf
                    $params = [
                        'c_id' => $this->destination_course_id,
                        'page_id' => $new_id,
                        'task' => '',
                        'feedback1' => '',
                        'feedback2' => '',
                        'feedback3' => '',
                        'fprogress1' => '',
                        'fprogress2' => '',
                        'fprogress3' => '',
                        'max_size' => 0,
                        'max_text' => 0,
                        'max_version' => 0,
                        'startdate_assig' => null,
                        'enddate_assig' => null,
                        'delayedsubmit' => 0,
                    ];

                    Database::insert($table_wiki_conf, $params);
                }
            }
        }
    }

    /**
     * Restore xapi tool.
     *
     * @param int $sessionId
     */
    public function restore_xapi_tool()
    {
        if ($this->course->has_resources(RESOURCE_XAPI_TOOL) && $this->isXapiEnabled) {
            $resources = $this->course->resources;
            foreach ($resources[RESOURCE_XAPI_TOOL] as $id => $xapiTool) {
                $launchPath = str_replace(
                    api_get_path(WEB_COURSE_PATH).$this->course->info['path'].'/',
                    '',
                    dirname($xapiTool->params['launch_url'])
                );

                $originPath = $this->course->backup_path.'/'.$launchPath;
                $destinationPath = api_get_path(SYS_COURSE_PATH).$this->course->destination_path.'/'.$launchPath;
                $xapiDir = dirname($destinationPath);
                @mkdir($xapiDir, api_get_permissions_for_new_directories(), true);
                if (copyDirTo($originPath, $destinationPath, false)) {
                    $xapiTool->params['launch_url'] = str_replace(
                        '/'.$this->course->info['path'].'/',
                        '/'.$this->course->destination_path.'/',
                        $xapiTool->params['launch_url']
                    );
                    $ref = $xapiTool->params['id'];
                    $xapiTool->params['c_id'] = $this->destination_course_id;
                    unset($xapiTool->params['id']);

                    $lastId = Database::insert('xapi_tool_launch', $xapiTool->params, false);
                    $this->course->resources[RESOURCE_XAPI_TOOL][$ref]->destination_id = $lastId;
                }
            }
        }
    }

    /**
     * Restore Thematics.
     *
     * @param int $sessionId
     */
    public function restore_thematic($sessionId = 0)
    {
        if ($this->course->has_resources(RESOURCE_THEMATIC)) {
            $table_thematic = Database::get_course_table(TABLE_THEMATIC);
            $table_thematic_advance = Database::get_course_table(TABLE_THEMATIC_ADVANCE);
            $table_thematic_plan = Database::get_course_table(TABLE_THEMATIC_PLAN);

            $resources = $this->course->resources;
            foreach ($resources[RESOURCE_THEMATIC] as $id => $thematic) {
                // check resources inside html from ckeditor tool and copy correct urls into recipient course
                $thematic->params['content'] = DocumentManager::replaceUrlWithNewCourseCode(
                    $thematic->params['content'],
                    $this->course->code,
                    $this->course->destination_path,
                    $this->course->backup_path,
                    $this->course->info['path']
                );
                $thematic->params['c_id'] = $this->destination_course_id;
                unset($thematic->params['id']);
                unset($thematic->params['iid']);

                $last_id = Database::insert($table_thematic, $thematic->params, false);

                if ($last_id) {
                    $sql = "UPDATE $table_thematic SET id = iid WHERE iid = $last_id";
                    Database::query($sql);

                    api_item_property_update(
                        $this->destination_course_info,
                        'thematic',
                        $last_id,
                        'ThematicAdded',
                        api_get_user_id(),
                        null,
                        null,
                        null,
                        null,
                        $sessionId
                    );

                    foreach ($thematic->thematic_advance_list as $thematic_advance) {
                        unset($thematic_advance['id']);
                        unset($thematic_advance['iid']);
                        $thematic_advance['attendance_id'] = 0;
                        $thematic_advance['thematic_id'] = $last_id;
                        $thematic_advance['c_id'] = $this->destination_course_id;

                        $my_id = Database::insert(
                            $table_thematic_advance,
                            $thematic_advance,
                            false
                        );

                        if ($my_id) {
                            $sql = "UPDATE $table_thematic_advance SET id = iid WHERE iid = $my_id";
                            Database::query($sql);

                            api_item_property_update(
                                $this->destination_course_info,
                                'thematic_advance',
                                $my_id,
                                'ThematicAdvanceAdded',
                                api_get_user_id(),
                                null,
                                null,
                                null,
                                null,
                                $sessionId
                            );
                        }
                    }

                    foreach ($thematic->thematic_plan_list as $thematic_plan) {
                        unset($thematic_plan['id']);
                        unset($thematic_plan['iid']);
                        $thematic_plan['thematic_id'] = $last_id;
                        $thematic_plan['c_id'] = $this->destination_course_id;
                        $my_id = Database::insert($table_thematic_plan, $thematic_plan, false);

                        if ($my_id) {
                            $sql = "UPDATE $table_thematic_plan SET id = iid WHERE iid = $my_id";
                            Database::query($sql);

                            api_item_property_update(
                                $this->destination_course_info,
                                'thematic_plan',
                                $my_id,
                                'ThematicPlanAdded',
                                api_get_user_id(),
                                null,
                                null,
                                null,
                                null,
                                $sessionId
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * Restore Attendance.
     *
     * @param int $sessionId
     */
    public function restore_attendance($sessionId = 0)
    {
        if ($this->course->has_resources(RESOURCE_ATTENDANCE)) {
            $table_attendance = Database::get_course_table(TABLE_ATTENDANCE);
            $table_attendance_calendar = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR);

            $resources = $this->course->resources;
            foreach ($resources[RESOURCE_ATTENDANCE] as $id => $obj) {
                // check resources inside html from ckeditor tool and copy correct urls into recipient course
                $obj->params['description'] = DocumentManager::replaceUrlWithNewCourseCode(
                    $obj->params['description'],
                    $this->course->code,
                    $this->course->destination_path,
                    $this->course->backup_path,
                    $this->course->info['path']
                );

                unset($obj->params['id']);
                unset($obj->params['iid']);
                $obj->params['c_id'] = $this->destination_course_id;
                $last_id = Database::insert($table_attendance, $obj->params);

                if (is_numeric($last_id)) {
                    $sql = "UPDATE $table_attendance SET id = iid WHERE iid = $last_id";
                    Database::query($sql);

                    $this->course->resources[RESOURCE_ATTENDANCE][$id]->destination_id = $last_id;

                    api_item_property_update(
                        $this->destination_course_info,
                        TOOL_ATTENDANCE,
                        $last_id,
                        'AttendanceAdded',
                        api_get_user_id(),
                        null,
                        null,
                        null,
                        null,
                        $sessionId
                    );

                    foreach ($obj->attendance_calendar as $attendance_calendar) {
                        unset($attendance_calendar['id']);
                        unset($attendance_calendar['iid']);

                        $attendance_calendar['attendance_id'] = $last_id;
                        $attendance_calendar['c_id'] = $this->destination_course_id;
                        $attendanceCalendarId = Database::insert(
                            $table_attendance_calendar,
                            $attendance_calendar
                        );

                        $sql = "UPDATE $table_attendance_calendar SET id = iid WHERE iid = $attendanceCalendarId";
                        Database::query($sql);
                    }
                }
            }
        }
    }

    /**
     * Restore Works.
     *
     * @param int $sessionId
     */
    public function restore_works($sessionId = 0)
    {
        require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';
        if ($this->course->has_resources(RESOURCE_WORK)) {
            $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);

            $resources = $this->course->resources;
            foreach ($resources[RESOURCE_WORK] as $obj) {
                // check resources inside html from ckeditor tool and copy correct urls into recipient course
                $obj->params['description'] = DocumentManager::replaceUrlWithNewCourseCode(
                    $obj->params['description'],
                    $this->course->code,
                    $this->course->destination_path,
                    $this->course->backup_path,
                    $this->course->info['path']
                );

                $id_work = $obj->params['id'];
                $obj->params['id'] = null;
                $obj->params['c_id'] = $this->destination_course_info['real_id'];

                // re-create dir
                // @todo check security against injection of dir in crafted course backup here!
                $path = $obj->params['url'];
                $path = '/'.str_replace('/', '', substr($path, 1));

                $workData = [];

                switch ($this->file_option) {
                    case FILE_SKIP:
                        $workData = get_work_data_by_path(
                            $path,
                            $this->destination_course_info['real_id']
                        );
                        if (!empty($workData)) {
                            break;
                        }
                        break;
                    case FILE_OVERWRITE:
                        if (!empty($this->course_origin_id)) {
                            $sql = 'SELECT * FROM '.$table.'
                                    WHERE
                                        c_id = '.$this->course_origin_id.' AND
                                        publication_id = '.$id_work;
                            $result = Database::query($sql);
                            $cant = Database::num_rows($result);
                            if ($cant > 0) {
                                $row = Database::fetch_assoc($result);
                            }

                            $obj->params['enableExpiryDate'] = empty($row['expires_on']) ? false : true;
                            $obj->params['enableEndDate'] = empty($row['ends_on']) ? false : true;
                            $obj->params['expires_on'] = $row['expires_on'];
                            $obj->params['ends_on'] = $row['ends_on'];
                            $obj->params['enable_qualification'] = $row['enable_qualification'];
                            $obj->params['add_to_calendar'] = !empty($row['add_to_calendar']) ? 1 : 0;
                        }
                        //no break
                    case FILE_RENAME:
                        $workData = get_work_data_by_path(
                            $path,
                            $this->destination_course_info['real_id']
                        );
                        break;
                }

                $obj->params['work_title'] = $obj->params['title'];
                $obj->params['new_dir'] = $obj->params['title'];

                if (empty($workData)) {
                    $workId = addDir(
                        $obj->params,
                        api_get_user_id(),
                        $this->destination_course_info,
                        0,
                        $sessionId
                    );
                    $this->course->resources[RESOURCE_WORK][$id_work]->destination_id = $workId;
                } else {
                    $workId = $workData['iid'];
                    updateWork(
                        $workId,
                        $obj->params,
                        $this->destination_course_info,
                        $sessionId
                    );
                    updatePublicationAssignment(
                        $workId,
                        $obj->params,
                        $this->destination_course_info,
                        0
                    );
                    $this->course->resources[RESOURCE_WORK][$id_work]->destination_id = $workId;
                }
            }
        }
    }

    /**
     * Restore gradebook.
     *
     * @param int $sessionId
     *
     * @return bool
     */
    public function restore_gradebook($sessionId = 0)
    {
        if (in_array($this->file_option, [FILE_SKIP, FILE_RENAME])) {
            return false;
        }
        // if overwrite
        if ($this->course->has_resources(RESOURCE_GRADEBOOK)) {
            $resources = $this->course->resources;
            $destinationCourseCode = $this->destination_course_info['code'];
            // Delete destination gradebook
            $cats = \Category::load(
                null,
                null,
                $destinationCourseCode,
                null,
                null,
                $sessionId
            );

            if (!empty($cats)) {
                /** @var \Category $cat */
                foreach ($cats as $cat) {
                    $cat->delete_all();
                }
            }

            /** @var GradeBookBackup $obj */
            foreach ($resources[RESOURCE_GRADEBOOK] as $id => $obj) {
                if (!empty($obj->categories)) {
                    $categoryIdList = [];
                    /** @var \Category $cat */
                    foreach ($obj->categories as $cat) {
                        $cat->set_course_code($destinationCourseCode);
                        $cat->set_session_id($sessionId);

                        $parentId = $cat->get_parent_id();
                        if (!empty($parentId)) {
                            if (isset($categoryIdList[$parentId])) {
                                $cat->set_parent_id($categoryIdList[$parentId]);
                            }
                        }
                        $oldId = $cat->get_id();
                        $categoryId = $cat->add();
                        $categoryIdList[$oldId] = $categoryId;
                        if (!empty($cat->evaluations)) {
                            /** @var \Evaluation $evaluation */
                            foreach ($cat->evaluations as $evaluation) {
                                $evaluation->set_category_id($categoryId);
                                $evaluation->set_course_code($destinationCourseCode);
                                $evaluation->setSessionId($sessionId);
                                $evaluation->add();
                            }
                        }

                        if (!empty($cat->links)) {
                            /** @var \AbstractLink $link */
                            foreach ($cat->links as $link) {
                                $link->set_category_id($categoryId);
                                $link->set_course_code($destinationCourseCode);
                                $link->set_session_id($sessionId);
                                $import = false;
                                $itemId = $link->get_ref_id();
                                switch ($link->get_type()) {
                                    case LINK_EXERCISE:
                                        $type = RESOURCE_QUIZ;
                                        break;
                                    /*case LINK_DROPBOX:
                                        break;*/
                                    case LINK_STUDENTPUBLICATION:
                                        $type = RESOURCE_WORK;
                                        break;
                                    case LINK_LEARNPATH:
                                        $type = RESOURCE_LEARNPATH;
                                        break;
                                    case LINK_FORUM_THREAD:
                                        $type = RESOURCE_FORUMTOPIC;
                                        break;
                                    case LINK_ATTENDANCE:
                                        $type = RESOURCE_ATTENDANCE;
                                        break;
                                    case LINK_SURVEY:
                                        $type = RESOURCE_ATTENDANCE;
                                        break;
                                    case LINK_HOTPOTATOES:
                                        $type = RESOURCE_QUIZ;
                                        break;
                                }

                                if ($this->course->has_resources($type) &&
                                    isset($this->course->resources[$type][$itemId])
                                ) {
                                    $item = $this->course->resources[$type][$itemId];
                                    if ($item && $item->is_restored()) {
                                        $link->set_ref_id($item->destination_id);
                                        $import = true;
                                    }
                                }

                                if ($import) {
                                    $link->add();
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Restore course assets (not included in documents).
     */
    public function restore_assets()
    {
        if ($this->course->has_resources(RESOURCE_ASSET)) {
            $resources = $this->course->resources;
            $path = api_get_path(SYS_COURSE_PATH).$this->course->destination_path.'/';

            foreach ($resources[RESOURCE_ASSET] as $asset) {
                if (is_file($this->course->backup_path.'/'.$asset->path) &&
                    is_readable($this->course->backup_path.'/'.$asset->path) &&
                    is_dir(dirname($path.$asset->path)) &&
                    is_writeable(dirname($path.$asset->path))
                ) {
                    switch ($this->file_option) {
                        case FILE_SKIP:
                            break;
                        case FILE_OVERWRITE:
                            copy(
                                $this->course->backup_path.'/'.$asset->path,
                                $path.$asset->path
                            );
                            break;
                    }
                }
            }
        }
    }

    /**
     * @param string $str
     *
     * @return string
     */
    public function DBUTF8($str)
    {
        if (UTF8_CONVERT) {
            $str = utf8_encode($str);
        }

        return $str;
    }

    /**
     * @param string $str
     *
     * @return string
     */
    public function DBUTF8escapestring($str)
    {
        if (UTF8_CONVERT) {
            $str = utf8_encode($str);
        }

        return Database::escape_string($str);
    }

    /**
     * @param array $array
     *
     * @return mixed
     */
    public function DBUTF8_array($array)
    {
        if (UTF8_CONVERT) {
            foreach ($array as &$item) {
                $item = utf8_encode($item);
            }

            return $array;
        } else {
            return $array;
        }
    }

    /**
     * @param int $groupId
     *
     * @return array
     */
    public function checkGroupId($groupId)
    {
        return \GroupManager::get_group_properties($groupId);
    }

    /**
     * @param string $documentPath
     * @param string $webEditorCss
     */
    public function fixEditorHtmlContent($documentPath, $webEditorCss = '')
    {
        $extension = pathinfo(basename($documentPath), PATHINFO_EXTENSION);

        switch ($extension) {
            case 'html':
            case 'htm':
                $contents = file_get_contents($documentPath);
                $contents = str_replace(
                    '{{css_editor}}',
                    $webEditorCss,
                    $contents
                );
                file_put_contents($documentPath, $contents);
                break;
        }
    }

    /**
     * Check if user exist otherwise use current user.
     *
     * @param int  $userId
     * @param bool $returnNull
     *
     * @return int
     */
    private function checkUserId($userId, $returnNull = false)
    {
        if (!empty($userId)) {
            $userInfo = api_get_user_info($userId);
            if (empty($userInfo)) {
                return api_get_user_id();
            }
        }

        if ($returnNull) {
            return null;
        }

        if (empty($userId)) {
            return api_get_user_id();
        }

        return $userId;
    }
}
