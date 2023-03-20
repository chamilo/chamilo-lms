<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Component\CourseCopy\CourseBuilder;
use Chamilo\CourseBundle\Component\CourseCopy\CourseSelectForm;

/**
 * Special exports.
 *
 * @author Jhon Hinojosa
 * @author Julio Montoya Fixing pclzip folder + some clean <gugli100@gmail.com>
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script(true);
api_set_more_memory_and_time_limits();

$this_section = SECTION_PLATFORM_ADMIN;
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];

$nameTools = get_lang('Special exports');
$export = '';
$querypath = '';

Display::display_header($nameTools);

echo Display::page_header($nameTools);

if (0 == count($_POST)) {
    echo Display::return_message(get_lang('Special exportsIntroduction'));
}
$error = 0;
$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);

if ((isset($_POST['action']) && 'course_select_form' == $_POST['action']) ||
    (isset($_POST['backup_option']) && 'full_backup' == $_POST['backup_option'])
) {
    $export = false;
    if (isset($_POST['action']) && 'course_select_form' == $_POST['action']) {
        $FileZip = create_zip();
        $to_group_id = 0;
        $sql_session = "SELECT id, name FROM $tbl_session ";
        $query_session = Database::query($sql_session);
        $ListSession = [];
        while ($rows_session = Database::fetch_assoc($query_session)) {
            $ListSession[$rows_session['id']] = $rows_session['name'];
        }

        $groupCondition = " props.to_group_id = $to_group_id";
        if (empty($to_group_id)) {
            $groupCondition = " (props.to_group_id = 0 OR props.to_group_id IS NULL)";
        }

        $zip_folder = new PclZip($FileZip['TEMP_FILE_ZIP']);
        if (!isset($_POST['resource']) || 0 == count($_POST['resource'])) {
            echo Display::return_message(get_lang('There were no courses registered or may not have made the association with the sessions'), 'error');
        } else {
            $Resource = $_POST['resource'];

            foreach ($Resource as $Code_course => $Sessions) {
                $_course = api_get_course_info($Code_course);
                $tbl_document = Database::get_course_table(TABLE_DOCUMENT);
                $tbl_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
                $course_id = $_course['real_id'];

                //Add item to the zip file course
                $sql = "SELECT path FROM $tbl_document AS docs, $tbl_property AS props
                        WHERE props.tool='".TOOL_DOCUMENT."'
                        AND docs.id=props.ref
                        AND docs.path LIKE '".$querypath."/%'
                        AND docs.filetype='file'
                        AND (docs.session_id = '0' OR docs.session_id IS NULL)
                        AND props.visibility<>'2'
                        AND $groupCondition
                        AND docs.c_id = $course_id
                        AND props.c_id = $course_id";
                $query = Database::query($sql);
                while ($rows_course_file = Database::fetch_assoc($query)) {
                    $zip_folder->add(
                        $FileZip['PATH_COURSE'].$_course['directory']."/document".$rows_course_file['path'],
                        PCLZIP_OPT_ADD_PATH,
                        $_course['directory'],
                        PCLZIP_OPT_REMOVE_PATH,
                        $FileZip['PATH_COURSE'].$_course['directory']."/document".$FileZip['PATH_REMOVE']
                    );
                }

                foreach ($Sessions as $IdSession => $value) {
                    $session_id = (int) $IdSession;
                    //Add tem to the zip file session course
                    $sql_session_doc = "SELECT path FROM $tbl_document AS docs, $tbl_property AS props
                        WHERE props.tool='".TOOL_DOCUMENT."'
                            AND docs.id=props.ref
                            AND docs.path LIKE '".$querypath."/%'
                            AND docs.filetype='file'
                            AND docs.session_id = '$session_id'
                            AND props.visibility<>'2'
                            AND $groupCondition
                            AND docs.c_id = $course_id
                            AND props.c_id = $course_id";
                    $query_session_doc = Database::query($sql_session_doc);
                    while ($rows_course_session_file = Database::fetch_assoc($query_session_doc)) {
                        $zip_folder->add(
                            $FileZip['PATH_COURSE'].$_course['directory'].'/document'.$rows_course_session_file['path'],
                            PCLZIP_OPT_ADD_PATH,
                            $_course['directory']."/".$ListSession[$session_id],
                            PCLZIP_OPT_REMOVE_PATH,
                            $FileZip['PATH_COURSE'].$_course['directory'].'/document'.$FileZip['PATH_REMOVE']
                        );
                    }
                }
            }
            $name = rename_zip($FileZip);
            $export = true;
        }
    } else {
        $name = fullexportspecial();
    }
}

if ($export && $name) {
    echo Display::return_message(get_lang('The backup has been created. The download of this file will start in a few moments. If your download does not start, click the following link'), 'confirm');
    echo '<br /><a class="btn btn--plain" href="'.api_get_path(WEB_CODE_PATH).'course_info/download.php?archive_path=&archive='.urlencode($name).'">'.get_lang('Download').'</a>';
} else {
    // Display forms especial export
    if (isset($_POST['backup_option']) && 'select_items' == $_POST['backup_option']) {
        $cb = new CourseBuilder();
        $course = $cb->build_session_course();
        if (false === $course) {
            echo Display::return_message(get_lang('There were no courses registered or may not have made the association with the sessions'), 'error');
            form_special_export();
        } else {
            echo Display::return_message(get_lang('If you want to export courses containing sessions, which will ensure that these seansido included in the export to any of that will have to choose in the list.'), 'normal');
            CourseSelectForm :: display_form_session_export($course);
        }
    } else {
        form_special_export();
    }
}

Display::display_footer();

function form_special_export()
{
    $form = new FormValidator('special_exports', 'post');
    $renderer = $form->defaultRenderer();
    $renderer->setCustomElementTemplate('<div>{element}</div> ');
    $form->addElement('radio', 'backup_option', '', get_lang('Special create full backup'), 'full_backup');
    $form->addElement('radio', 'backup_option', '', get_lang('Special let me select items'), 'select_items');
    $form->addElement('html', '<br />');
    $form->addButtonExport(get_lang('Create a backup'));
    $form->addProgress();
    $values['backup_option'] = 'full_backup';
    $form->setDefaults($values);
    $form->display();
}

function create_zip()
{
    $path = '';
    if (empty($path)) {
        $path = '/';
    }
    $remove_dir = ('/' != $path) ? substr($path, 0, strlen($path) - strlen(basename($path))) : '/';
    $sys_archive_path = api_get_path(SYS_ARCHIVE_PATH).'special_export/';
    $sys_course_path = api_get_path(SYS_COURSE_PATH);
    $temp_zip_dir = $sys_archive_path;
    if (!is_dir($temp_zip_dir)) {
        mkdir($temp_zip_dir, api_get_permissions_for_new_directories());
    } else {
        $handle = opendir($temp_zip_dir);
        while (false !== ($file = readdir($handle))) {
            if ("." != $file && ".." != $file) {
                $Diff = (time() - filemtime("$temp_zip_dir/$file")) / 60 / 60; //the "age" of the file in hours
                if ($Diff > 4) {
                    unlink("$temp_zip_dir/$file");
                }   //delete files older than 4 hours
            }
        }
        closedir($handle);
    }
    $temp_zip_file = $temp_zip_dir."/".md5(time()).".zip"; //create zipfile of given directory

    return [
        'PATH' => $path,
        'PATH_TEMP_ARCHIVE' => $temp_zip_dir,
        'PATH_COURSE' => $sys_course_path,
        'TEMP_FILE_ZIP' => $temp_zip_file,
        'PATH_REMOVE' => $remove_dir,
    ];
}

function rename_zip($FileZip)
{
    Event::event_download(('/' == $FileZip['PATH']) ? 'full_export_'.date('Ymd').'.zip (folder)' : basename($FileZip['PATH']).'.zip (folder)');
    $name = ('/' == $FileZip['PATH']) ? 'full_export_'.date('Ymd').'.zip' : basename($FileZip['PATH']).'.zip';
    if (file_exists($FileZip['PATH_TEMP_ARCHIVE'].'/'.$name)) {
        unlink($FileZip['PATH_TEMP_ARCHIVE'].'/'.$name);
    }
    if (file_exists($FileZip['TEMP_FILE_ZIP'])) {
        rename(
            $FileZip['TEMP_FILE_ZIP'],
            $FileZip['PATH_TEMP_ARCHIVE'].'/'.$name
        );

        return $name;
    } else {
        return false;
    }
}

function fullexportspecial()
{
    global $tbl_session, $tbl_session_course, $export;
    $FileZip = create_zip();
    $to_group_id = 0;
    $zip_folder = new PclZip($FileZip['TEMP_FILE_ZIP']);
    $list_course = CourseManager::get_course_list();
    $tbl_document = Database::get_course_table(TABLE_DOCUMENT);
    $tbl_property = Database::get_course_table(TABLE_ITEM_PROPERTY);

    $groupCondition = " props.to_group_id = $to_group_id";
    if (empty($to_group_id)) {
        $groupCondition = " (props.to_group_id = 0 OR props.to_group_id IS NULL)";
    }

    if (count($list_course) > 0) {
        foreach ($list_course as $_course) {
            if ('/' == $FileZip['PATH']) {
                $querypath = ''; // to prevent ...path LIKE '//%'... in query
            } else {
                $querypath = $FileZip['PATH'];
            }
            $course_id = $_course['real_id'];

            //Add tem to the zip file course
            $sql = "SELECT path FROM $tbl_document AS docs, $tbl_property AS props
                WHERE props.tool='".TOOL_DOCUMENT."'
                    AND docs.id=props.ref
                    AND docs.path LIKE '".$querypath."/%'
                    AND docs.filetype='file'
                    AND (docs.session_id = '0' OR docs.session_id IS NULL)
                    AND props.visibility<>'2'
                    AND $groupCondition
                    AND docs.c_id = $course_id
                    AND props.c_id = $course_id";
            $query = Database::query($sql);
            while ($rows_course_file = Database::fetch_assoc($query)) {
                $zip_folder->add(
                    $FileZip['PATH_COURSE'].$_course['directory']."/document".$rows_course_file['path'],
                    PCLZIP_OPT_ADD_PATH,
                    $_course['directory'],
                    PCLZIP_OPT_REMOVE_PATH,
                    $FileZip['PATH_COURSE'].$_course['directory']."/document".$FileZip['PATH_REMOVE']
                );
            }

            //Add tem to the zip file session course
            $sql = "SELECT s.id, name, c_id
                    FROM $tbl_session_course sc
                    INNER JOIN $tbl_session s
                    ON sc.session_id = s.id
                    WHERE c_id = '$course_id' ";
            $query_session = Database::query($sql);
            while ($rows_session = Database::fetch_assoc($query_session)) {
                $session_id = $rows_session['id'];
                $sql_session_doc = "SELECT path
                    FROM $tbl_document AS docs, $tbl_property AS props
                    WHERE props.tool='".TOOL_DOCUMENT."'
                        AND docs.id=props.ref
                        AND docs.path LIKE '".$querypath."/%'
                        AND docs.filetype='file'
                        AND docs.session_id = '$session_id'
                        AND props.visibility<>'2'
                        AND $groupCondition
                        AND docs.c_id = $course_id
                        AND props.c_id = $course_id ";
                $query_session_doc = Database::query($sql_session_doc);
                while ($rows_course_session_file = Database::fetch_assoc($query_session_doc)) {
                    $zip_folder->add(
                        $FileZip['PATH_COURSE'].$_course['directory'].'/document'.$rows_course_session_file['path'],
                        PCLZIP_OPT_ADD_PATH,
                        $_course['directory']."/".$rows_session['name'],
                        PCLZIP_OPT_REMOVE_PATH,
                        $FileZip['PATH_COURSE'].$_course['directory'].'/document'.$FileZip['PATH_REMOVE']
                    );
                }
            }
        }

        $name = rename_zip($FileZip);
        if (false === $name) {
            $export = false;

            return false;
        } else {
            $export = true;

            return $name;
        }
    } else {
        echo Display::return_message(get_lang('There were no courses registered or may not have made the association with the sessions'), 'error'); //main API
        $export = false;

        return false;
    }
}
