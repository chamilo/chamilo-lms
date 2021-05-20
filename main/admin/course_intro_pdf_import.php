<?php

/* For licensing terms, see /license.txt */

/**
 * This tool allows platform admins to upload a massive amount of PDFs to be
 * uploaded in each course.
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script();
// temporary configuration of in which folder to upload the file in each course.
// Should default to '', and start with a '/' and end without it, if defined
$subDir = '';
$tool_name = get_lang('ImportPDFIntroToCourses');
$errors = [];

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];

set_time_limit(0);

if (isset($_POST['formSent'])) {
    if (empty($_FILES['import_file']['tmp_name'])) {
        $error_message = get_lang('UplUploadFailed');
        Display::addFlash(Display::return_message($error_message, 'error', false));
    } else {
        $allowed_file_mimetype = ['zip'];
        $ext_import_file = substr($_FILES['import_file']['name'], (strrpos($_FILES['import_file']['name'], '.') + 1));

        if (!in_array($ext_import_file, $allowed_file_mimetype)) {
            Display::addFlash(
                Display::return_message(
                    get_lang('YouMustImportAZipFile'),
                    'error'
                )
            );
        } else {
            $errors = import_pdfs($subDir);
        }
    }
}

if (!empty($errors)) {
    $error_message = '<ul>';
    foreach ($errors as $index => $error_course) {
        $error_message .= '<li>'.get_lang('Course').': '.$error_course['Title'].' ('.$error_course['Code'].')</li>';
    }
    $error_message .= '</ul>';
    Display::addFlash(Display::return_message($error_message, 'normal', false));
}

Display::display_header($tool_name);
?>
    <form method="post" action="<?php echo api_get_self(); ?>" enctype="multipart/form-data" style="margin: 0;">
        <h3><?php echo $tool_name; ?></h3>
        <div class="control-group">
            <label><?php echo get_lang('ImportZipFileLocation'); ?></label>
            <div class="control">
                <input type="file" name="import_file"/>
            </div>
        </div>
        <div class="control-group">
            <div class="control">
                <button type="submit" class="save" value="<?php echo get_lang('Import'); ?>">
                    <?php echo get_lang('Import'); ?>
                </button>
            </div>
        </div>
        <input type="hidden" name="formSent" value="1"/>
    </form>
    <div style="clear: both;"></div>
    <p><?php echo get_lang('PDFsMustLookLike'); ?></p>

    <blockquote>
<pre>
<strong>CourseCode</strong>_<strong>NameOfDocument</strong>_<strong>CourseName</strong>.pdf
e.g.
MAT101_Introduction_to_Mathematics-101.pdf
MAT102_Introduction_to_Mathematics-102.pdf
ENG101_Introduction_to_English-101.pdf
</pre>
    </blockquote>

<?php
Display::display_footer();

/**
 * Import PDFs.
 *
 * @param string $subDir The subdirectory in which to put the files in each course
 *
 * @return bool|array List of possible errors found
 */
function import_pdfs($subDir = '/')
{
    $baseDir = api_get_path(SYS_ARCHIVE_PATH);
    $uploadPath = 'pdfimport/';
    $errors = [];
    if (!is_dir($baseDir.$uploadPath)) {
        @mkdir($baseDir.$uploadPath);
    }
    if (!unzip_uploaded_file($_FILES['import_file'], $uploadPath, $baseDir, 1024 * 1024 * 1024)) {
        return false;
    }
    $list = scandir($baseDir.$uploadPath);
    $i = 0;
    foreach ($list as $file) {
        if (substr($file, 0, 1) == '.' or !is_file($baseDir.$uploadPath.$file)) {
            continue;
        }
        $parts = preg_split('/_/', $file);
        $course = api_get_course_info($parts[0]);
        if (count($course) > 0) {
            // Build file info because handle_uploaded_document() needs it (name, type, size, tmp_name)
            $fileSize = filesize($baseDir.$uploadPath.$file);
            $docId = add_document(
                $course,
                $subDir.'/'.$file,
                'file',
                $fileSize,
                $parts[1].' '.substr($parts[2], 0, -4)
            );
            if ($docId > 0) {
                if (!is_file($baseDir.$uploadPath.$file)) {
                    error_log($baseDir.$uploadPath.$file.' does not exists in '.__FILE__);
                }
                if (is_file(api_get_path(SYS_COURSE_PATH).$course['path'].'/document'.$subDir.'/'.$file)) {
                    error_log(api_get_path(SYS_COURSE_PATH).$course['path'].'/document'.$subDir.'/'.$file.' exists at destination in '.__FILE__);
                }
                if (!is_writeable(api_get_path(SYS_COURSE_PATH).$course['path'].'/document'.$subDir)) {
                    error_log('Destination '.api_get_path(SYS_COURSE_PATH).$course['path'].'/document'.$subDir.' is NOT writeable in '.__FILE__);
                }
                // Place each file in its folder in each course
                rename(
                    $baseDir.$uploadPath.$file,
                    api_get_path(SYS_COURSE_PATH).$course['path'].'/document'.$subDir.'/'.$file
                );
                api_item_property_update(
                    $course,
                    TOOL_DOCUMENT,
                    $docId,
                    'DocumentAdded',
                    api_get_user_id()
                );
                // Redo visibility
                api_set_default_visibility($docId, TOOL_DOCUMENT);
                $errors[] = ['Line' => 0, 'Code' => $course['code'], 'Title' => $course['title']];
                // Now add a link to the file from the Course description tool
                $link = '<p>Sílabo de la asignatura
                 <a href="'.api_get_path(WEB_CODE_PATH).'document/document.php?'.api_get_cidreq_params($course['code']).'&action=download&id='.$docId.'" target="_blank">
                      '.Display::return_icon('pdf.png').'
                 </a></p>';
                $course_description = new CourseDescription();
                $session_id = api_get_session_id();
                $course_description->set_course_id($course['real_id']);
                $course_description->set_session_id($session_id);
                $course_description->set_title('Course presentation');
                $course_description->set_content($link);
                $course_description->set_description_type(1);
                $course_description->insert();
            }
        } else {
            $errors[] = ['Line' => 0, 'Code' => $parts[0], 'Title' => $parts[0].' - '.get_lang('CodeDoesNotExists')];
        }
        $i++; //found at least one entry that is not a dir or a .
    }
    if ($i == 0) {
        $errors[] = ['Line' => 0, 'Code' => '.', 'Title' => get_lang('NoPDFFoundAtRoot')];
    }

    if (empty($errors)) {
        Display::addFlash(
            Display::return_message(
                get_lang('CourseIntroductionsAllImportedSuccessfully'),
                'confirmation',
                false
            )
        );
    }

    return $errors;
}
