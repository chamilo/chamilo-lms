<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Homepage script for the documents tool
 *
 * This script allows the user to manage files and directories on a remote http
 * server.
 * The user can : - navigate through files and directories.
 * 				 - upload a file
 * 				 - delete, copy a file or a directory
 * 				 - edit properties & content (name, comments, html content)
 * The script is organised in four sections.
 *
 * 1) Execute the command called by the user
 * 				Note: somme commands of this section are organised in two steps.
 * 			    The script always begins with the second step,
 * 			    so it allows to return more easily to the first step.
 *
 * 				Note (March 2004) some editing functions (renaming, commenting)
 * 				are moved to a separate page, edit_document.php. This is also
 * 				where xml and other stuff should be added.
 * 2) Define the directory to display
 * 3) Read files and directories from the directory defined in part 2
 * 4) Display all of that on an HTML page
 *
 * @package chamilo.document
 */

require_once '../inc/global.inc.php';

$allowDownloadDocumentsByApiKey = api_get_setting('allow_download_documents_by_api_key') === 'true';

$current_course_tool = TOOL_DOCUMENT;
$this_section = SECTION_COURSES;
$to_user_id = null;
$parent_id = null;

$lib_path = api_get_path(LIBRARY_PATH);
$actionsRight = '';

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;

$allowUseTool = false;

if ($allowDownloadDocumentsByApiKey) {
    try {
        if ($action != 'download') {
            throw new Exception(get_lang('SelectAnAction'));
        }

        $username = isset($_GET['username']) ? Security::remove_XSS($_GET['username']) : null;
        $apiKey = isset($_GET['api_key']) ? Security::remove_XSS($_GET['api_key']) : null;

        $restApi = Rest::validate($username, $apiKey);

        $allowUseTool = $restApi ? true : false;
    } catch (Exception $e) {
        $allowUseTool = false;
    }
}

if (!$allowUseTool) {
    api_protect_course_script(true);
    api_protect_course_group(GroupManager::GROUP_TOOL_DOCUMENTS);
}

DocumentManager::removeGeneratedAudioTempFile();

if (
    isset($_SESSION['temp_realpath_image']) &&
    !empty($_SESSION['temp_realpath_image']) &&
    file_exists($_SESSION['temp_realpath_image'])
) {
    unlink($_SESSION['temp_realpath_image']);
}
$_user = api_get_user_info();
$courseInfo = api_get_course_info();
$courseId = $courseInfo['real_id'];
$course_dir = $courseInfo['directory'] . '/document';
$sys_course_path = api_get_path(SYS_COURSE_PATH);
$base_work_dir = $sys_course_path . $course_dir;
$http_www = api_get_path(WEB_COURSE_PATH).$courseInfo['directory'] . '/document';
$document_path = $base_work_dir;
$usePpt2lp = api_get_setting('service_ppt2lp', 'active') == 'true';

$course_dir = $courseInfo['directory'].'/document';
$sys_course_path = api_get_path(SYS_COURSE_PATH);
$base_work_dir = $sys_course_path.$course_dir;
$http_www = api_get_path(WEB_COURSE_PATH).$courseInfo['directory'].'/document';
$document_path = $base_work_dir;

$currentUrl = api_get_self().'?'.api_get_cidreq();

// Removing sessions
unset($_SESSION['draw_dir']);
unset($_SESSION['paint_dir']);
unset($_SESSION['temp_audio_nanogong']);

$plugin = new AppPlugin();
$pluginList = $plugin->get_installed_plugins();
$capturePluginInstalled = in_array('jcapture', $pluginList);

if ($capturePluginInstalled) {
    $jcapturePath = api_get_path(WEB_PLUGIN_PATH).'jcapture/plugin_applet.php';
    $htmlHeadXtra[] = '<script>
    $(function() {
        function insertAtCarret() {
        }
        $("#jcapture").click(function(){
            $("#appletplace").load("'.$jcapturePath.'");
        });
    });
    </script>';
}
// Create directory certificates.
DocumentManager::create_directory_certificate_in_course(api_get_course_id());

if (empty($courseInfo)) {
    api_not_allowed(true);
}

// Used for avoiding double-click.
$dbl_click_id = 0;
$selectcat = isset($_GET['selectcat']) ? Security::remove_XSS($_GET['selectcat']) : null;
$moveTo = isset($_POST['move_to']) ? Security::remove_XSS($_POST['move_to']) : null;

/* 	Constants and variables */
$userId = api_get_user_id();
$userInfo = api_get_user_info();
$sessionId = api_get_session_id();
$course_code = api_get_course_id();
$groupId = api_get_group_id();
$is_allowed_to_edit = api_is_allowed_to_edit(null, true);
$group_member_with_upload_rights = false;

// If the group id is set, we show them group documents
$group_properties = array();
$group_properties['directory'] = null;

// For sessions we should check the parameters of visibility
if (api_get_session_id() != 0) {
    $group_member_with_upload_rights = $group_member_with_upload_rights && api_is_allowed_to_session_edit(false, true);
}

// Get group info
$group_properties = GroupManager::get_group_properties($groupId);
$groupIid = isset($group_properties['iid']) ? $group_properties['iid'] : 0;

$groupMemberWithEditRights = $is_allowed_to_edit || GroupManager::is_tutor_of_group($userId, $group_properties['iid'], $courseId);

// Setting group variables.
if (!empty($groupId)) {
    // Let's assume the user cannot upload files for the group
    $group_member_with_upload_rights = false;

    if ($group_properties['doc_state'] == 2) {
        // Documents are private
        if ($is_allowed_to_edit || GroupManager::is_user_in_group($userId, $group_properties['iid'])) {
            // Only courseadmin or group members (members + tutors) allowed
            $interbreadcrumb[] = array(
                'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq(),
                'name' => get_lang('Groups')
            );
            $interbreadcrumb[] = array(
                'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq(),
                'name' => get_lang('GroupSpace').' '.$group_properties['name']
            );
            //they are allowed to upload
            $group_member_with_upload_rights = true;
        } else {
            $groupId = 0;
        }
    } elseif ($group_properties['doc_state'] == 1) {
        // Documents are public
        $interbreadcrumb[] = array(
            'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq(),
            'name' => get_lang('Groups')
        );
        $interbreadcrumb[] = array(
            'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq(),
            'name' => get_lang('GroupSpace').' '.$group_properties['name']
        );

        // Allowed to upload?
        if ($is_allowed_to_edit ||
            GroupManager::is_subscribed($userId, $group_properties['iid']) ||
            GroupManager::is_tutor_of_group($userId, $group_properties['iid'], $courseId)
        ) {
            // Only course admin or group members can upload
            $group_member_with_upload_rights = true;
        }
    }

    Session::write('group_member_with_upload_rights', $group_member_with_upload_rights);
} else {
    Session::write('group_member_with_upload_rights', false);
}

// Actions.
$document_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : null;
$currentUrl = api_get_self().'?'.api_get_cidreq().'&id='.$document_id;

if (Portfolio::controller()->accept()) {
    Portfolio::controller()->run();
}

$curdirpath = isset($_GET['curdirpath']) ? Security::remove_XSS($_GET['curdirpath']) : null;

switch ($action) {
    case 'delete_item':
        if ($is_allowed_to_edit ||
            $group_member_with_upload_rights ||
            DocumentManager::is_my_shared_folder(api_get_user_id(), $curdirpath, $sessionId) ||
            DocumentManager::is_my_shared_folder(api_get_user_id(), $moveTo, $sessionId)
        ) {
            if (isset($_GET['deleteid'])) {
                if (!$is_allowed_to_edit) {
                    if (api_is_coach()) {
                        if (!DocumentManager::is_visible_by_id(
                            $_GET['deleteid'],
                            $courseInfo,
                            $sessionId,
                            api_get_user_id()
                        )
                        ) {
                            api_not_allowed();
                        }
                    }

                    if (DocumentManager::check_readonly(
                        $courseInfo,
                        api_get_user_id(),
                        '',
                        $_GET['deleteid'],
                        true
                    )
                    ) {
                        api_not_allowed();
                    }
                }

                $documentInfo = DocumentManager::get_document_data_by_id(
                    $_GET['deleteid'],
                    $courseInfo['code'],
                    false,
                    $sessionId
                );

                // Check whether the document is in the database.
                if (!empty($documentInfo)) {
                    $deleteDocument = DocumentManager::delete_document(
                        $courseInfo,
                        null,
                        $base_work_dir,
                        $sessionId,
                        $_GET['deleteid'],
                        $groupIid
                    );

                    if ($deleteDocument) {
                        $certificateId = isset($_GET['delete_certificate_id']) ? $_GET['delete_certificate_id'] : null;
                        DocumentManager::remove_attach_certificate(
                            api_get_course_id(),
                            $certificateId
                        );
                        Display::addFlash(Display::return_message(
                            get_lang('DocDeleted') . ': ' . $documentInfo['title'],
                            'success'
                        ));
                    } else {
                        Display::addFlash(Display::return_message(get_lang('DocDeleteError'), 'warning'));
                    }
                } else {
                    Display::addFlash(Display::return_message(get_lang('FileNotFound'), 'warning'));
                }
                header("Location: $currentUrl");
                exit;
            }
        }
        break;
    case 'download':
        // Get the document data from the ID
        $document_data = DocumentManager::get_document_data_by_id(
            $document_id,
            api_get_course_id(),
            false,
            $sessionId
        );
        if ($sessionId != 0 && !$document_data) {
            // If there is a session defined and asking for the document *from
            // the session* didn't work, try it from the course (out of a
            // session context)
            $document_data = DocumentManager::get_document_data_by_id(
                $document_id,
                api_get_course_id(),
                false,
                0
            );
        }
        // Check whether the document is in the database
        if (empty($document_data)) {
            api_not_allowed();
        }
        // Launch event
        Event::event_download($document_data['url']);
        // Check visibility of document and paths
        if (!($is_allowed_to_edit || $group_member_with_upload_rights)
            && !DocumentManager::is_visible_by_id($document_id, $courseInfo, $sessionId, api_get_user_id())) {
            api_not_allowed(true);
        }
        $full_file_name = $base_work_dir.$document_data['path'];
        if (Security::check_abs_path($full_file_name, $base_work_dir.'/')) {
            $result = DocumentManager::file_send_for_download($full_file_name, true);
            if ($result === false) {
                api_not_allowed(true);
            }
        }
        exit;
        break;
    case 'downloadfolder':
        if (api_get_setting('students_download_folders') == 'true'
            || api_is_allowed_to_edit()
            || api_is_platform_admin()
        ) {
            // Get the document data from the ID
            $document_data = DocumentManager::get_document_data_by_id(
                $document_id,
                api_get_course_id(),
                false,
                $sessionId
            );

            if ($sessionId != 0 && !$document_data) {
                // If there is a session defined and asking for the
                // document * from the session* didn't work, try it from the
                // course (out of a session context)
                $document_data = DocumentManager::get_document_data_by_id(
                    $document_id,
                    api_get_course_id(),
                    false,
                    0
                );
            }

            //filter when I am into shared folder, I can download only my shared folder
            if (DocumentManager::is_any_user_shared_folder($document_data['path'], $sessionId)) {
                if (DocumentManager::is_my_shared_folder(api_get_user_id(), $document_data['path'], $sessionId)
                    || api_is_allowed_to_edit()
                    || api_is_platform_admin()) {
                    require 'downloadfolder.inc.php';
                }
            } else {
                require 'downloadfolder.inc.php';
            }
            // Launch event
            Event::event_download($document_data['url']);
            exit;
        }
        break;
    case 'export_to_pdf':
        if (api_get_setting('students_export2pdf') == 'true' || api_is_allowed_to_edit() || api_is_platform_admin()) {
            DocumentManager::export_to_pdf($document_id, $course_code);
        }
        break;
    case 'copytomyfiles':
        // Copy a file to general my files user's
        if (api_get_setting('allow_social_tool') == 'true' &&
            api_get_setting('users_copy_files') == 'true'
            && api_get_user_id() != 0
            && !api_is_anonymous()
        ) {
            // Get the document data from the ID
            $document_info = DocumentManager::get_document_data_by_id(
                $document_id,
                api_get_course_id(),
                true,
                $sessionId
            );
            if ($sessionId != 0 && !$document_info) {
                /* If there is a session defined and asking for the document
                  from the session didn't work, try it from the course
                (out of a session context)*/
                $document_info = DocumentManager::get_document_data_by_id(
                    $document_id,
                    api_get_course_id(),
                    0
                );
            }
            $parent_id = $document_info['parent_id'];
            $my_path = UserManager::getUserPathById(api_get_user_id(), 'system');
            $user_folder = $my_path.'my_files/';
            $my_path = null;

            if (!file_exists($user_folder)) {
                $perm = api_get_permissions_for_new_directories();
                @mkdir($user_folder, $perm, true);
            }

            $file = $sys_course_path.$courseInfo['directory'].'/document'.$document_info['path'];
            $copyfile = $user_folder.basename($document_info['path']);
            $cidReq = Security::remove_XSS($_GET['cidReq']);
            $id_session = Security::remove_XSS($_GET['id_session']);
            $gidReq = Security::remove_XSS($_GET['gidReq']);
            $id = Security::remove_XSS($_GET['id']);
            if (empty($parent_id)) {
                $parent_id = 0;
            }
            $file_link = Display::url(
                get_lang('SeeFile'),
                api_get_path(WEB_CODE_PATH).'social/myfiles.php?'.api_get_cidreq_params($cidReq, $id_session, $gidReq).
                '&parent_id='.$parent_id
            );

            if (api_get_setting('allow_my_files') === 'false') {
                $file_link = '';
            }

            if (file_exists($copyfile)) {
                $message = get_lang('CopyAlreadyDone').'</p><p>';
                $message .= '<a class = "btn btn-default" '.
                        'href="'.api_get_self().'?'.api_get_cidreq().'&amp;id='.$parent_id.'">'.
                        get_lang("No").
                    '</a>'.
                    '&nbsp;&nbsp;|&nbsp;&nbsp;'.
                    '<a class = "btn btn-default" href="'.api_get_self().'?'.
                        api_get_cidreq().'&amp;action=copytomyfiles&amp;id='.$document_info['id'].
                        '&amp;copy=yes">'.
                    get_lang('Yes').
                    '</a></p>';
                if (!isset($_GET['copy'])) {
                    Display::addFlash(Display::return_message($message, 'warning', false));
                }
                if ($_GET['copy'] === 'yes') {
                    if (!copy($file, $copyfile)) {
                        Display::addFlash(Display::return_message(get_lang('CopyFailed'), 'error'));
                    } else {
                        Display::addFlash(Display::return_message(
                            get_lang('OverwritenFile').' '.$file_link,
                            'confirmation',
                            false
                        ));
                    }
                }
            } else {
                if (!copy($file, $copyfile)) {
                    Display::addFlash(Display::return_message(get_lang('CopyFailed'), 'error'));
                } else {
                    Display::addFlash(
                        Display::return_message(get_lang('CopyMade').' '.$file_link, 'confirmation', false)
                    );
                }
            }
        }
        break;
    case 'convertToPdf':
        // PDF format as target by default
        $formatTarget = $_REQUEST['formatTarget'] ?
            strtolower(Security::remove_XSS($_REQUEST['formatTarget'])) :
            'pdf';
        $formatType = $_REQUEST['formatType'] ?
            strtolower(Security::remove_XSS($_REQUEST['formatType'])) :
            'text';
        // Get the document data from the ID
        $document_info = DocumentManager::get_document_data_by_id(
            $document_id,
            api_get_course_id(),
            true,
            $session_id
        );
        $file = $sys_course_path . $courseInfo['directory'] .
            '/document' . $document_info['path'];
        $fileInfo = pathinfo($file);
        if ($fileInfo['extension'] == $formatTarget) {
            Display::addFlash(Display::return_message(
                get_lang('ConversionToSameFileFormat'),
                'warning'
            ));
        } elseif (
            !(
                in_array(
                    $fileInfo['extension'],
                    DocumentManager::getJodconverterExtensionList(
                        'from',
                        $formatType
                    )
                )
            ) || !(
                in_array(
                    $formatTarget,
                    DocumentManager::getJodconverterExtensionList(
                        'to',
                        $formatType
                    )
                )
            )
        ) {
            Display::addFlash(Display::return_message(
                get_lang('FileFormatNotSupported'),
                'warning'
            ));
        } else {
            $convertedFile = $fileInfo['dirname'] . DIRECTORY_SEPARATOR .
                $fileInfo['filename'] . '_from_' . $fileInfo['extension'] .
                '.' . $formatTarget;
            $convertedTitle = $document_info['title'];
            $obj = new OpenofficePresentation(true);
            if (file_exists($convertedFile)) {
                Display::addFlash(Display::return_message(
                    get_lang('FileExists'),
                    'error'
                ));
            } else {
                $result = $obj->convertCopyDocument(
                    $file,
                    $convertedFile,
                    $convertedTitle
                );
                if (empty($result)) {
                    Display::addFlash(Display::return_message(
                        get_lang('CopyFailed'),
                        'error'
                    ));
                } else {
                    $cidReq = Security::remove_XSS($_GET['cidReq']);
                    $id_session = api_get_session_id();
                    $gidReq = Security::remove_XSS($_GET['gidReq']);
                    $file_link = Display::url(
                        get_lang('SeeFile'),
                        api_get_path(WEB_CODE_PATH) .
                        'document/showinframes.php?'.api_get_cidreq_params($cidReq, $id_session, $gidReq).'&id=' . current($result)
                    );
                    Display::addFlash(Display::return_message(
                        get_lang('CopyMade') . ' ' . $file_link,
                        'confirmation',
                        false
                    ));
                }
            }
        }
        break;
}

// I'm in the certification module?
$is_certificate_mode = false;
if (isset($_GET['curdirpath'])) {
    $is_certificate_mode = DocumentManager::is_certificate_mode($_GET['curdirpath']);
}
if (isset($_REQUEST['certificate']) && $_REQUEST['certificate'] == 'true') {
    $is_certificate_mode = true;
}

// If no actions we proceed to show the document (Hack in order to use document.php?id=X)
if (isset($document_id) && empty($action)) {
    // Get the document data from the ID
    $document_data = DocumentManager::get_document_data_by_id(
        $document_id,
        api_get_course_id(),
        true,
        $sessionId
    );
    if ($sessionId != 0 && !$document_data) {
        // If there is a session defined and asking for the
        // document * from the session* didn't work, try it from the course
        // (out of a session context)
        $document_data = DocumentManager::get_document_data_by_id(
            $document_id,
            api_get_course_id(),
            true,
            0
        );
    }
    // If the document is not a folder we show the document.
    if ($document_data) {
        $parent_id = $document_data['parent_id'];
        $visibility = DocumentManager::check_visibility_tree(
            $document_id,
            api_get_course_id(),
            $sessionId,
            api_get_user_id(),
            $groupIid
        );

        if (!empty($document_data['filetype']) && $document_data['filetype'] == 'file') {
            if ($visibility && api_is_allowed_to_session_edit()) {
                $url = api_get_path(WEB_COURSE_PATH).
                    $courseInfo['path'].'/document'.$document_data['path'].'?'
                    .api_get_cidreq();
                header("Location: $url");
            }
            exit;
        } else {
            if (!$visibility && !api_is_allowed_to_edit()) {
                api_not_allowed();
            }
        }
        $_GET['curdirpath'] = $document_data['path'];
    }

    // What's the current path?
    // We will verify this a bit further down
    if (isset($_GET['curdirpath']) && $_GET['curdirpath'] != '') {
        $curdirpath = Security::remove_XSS($_GET['curdirpath']);
    } elseif (isset($_POST['curdirpath']) && $_POST['curdirpath'] != '') {
        $curdirpath = Security::remove_XSS($_POST['curdirpath']);
    } else {
        $curdirpath = '/';
    }

    $curdirpathurl = urlencode($curdirpath);
} else {
    // What's the current path?
    // We will verify this a bit further down
    if (isset($_GET['curdirpath']) && $_GET['curdirpath'] != '') {
        $curdirpath = Security::remove_XSS($_GET['curdirpath']);
    } elseif (isset($_POST['curdirpath']) && $_POST['curdirpath'] != '') {
        $curdirpath = Security::remove_XSS($_POST['curdirpath']);
    } else {
        $curdirpath = '/';
    }

    $curdirpathurl = urlencode($curdirpath);

    // Check the path
    // If the path is not found (no document id), set the path to /
    $document_id = DocumentManager::get_document_id($courseInfo, $curdirpath);

    if (!$document_id) {
        $document_id = DocumentManager::get_document_id($courseInfo, $curdirpath, 0);
    }

    $document_data = DocumentManager::get_document_data_by_id(
        $document_id,
        api_get_course_id(),
        true
    );

    $parent_id = $document_data['parent_id'];
}

if (isset($document_data) && $document_data['path'] == '/certificates') {
    $is_certificate_mode = true;
}

if (!$parent_id) {
    $parent_id = 0;
}

$current_folder_id = $document_id;

// Show preview
if (isset($_GET['curdirpath']) &&
    $_GET['curdirpath'] == '/certificates' &&
    isset($_GET['set_preview']) &&
    $_GET['set_preview'] == strval(intval($_GET['set_preview']))
) {
    if (isset($_GET['set_preview'])) {
        // Generate document HTML
        $content_html = DocumentManager::replace_user_info_into_html(
            api_get_user_id(),
            api_get_course_id(),
            api_get_session_id(),
            true
        );

        $filename = 'certificate_preview/'.api_get_unique_id().'.png';
        $qr_code_filename = api_get_path(SYS_ARCHIVE_PATH).$filename;

        $temp_folder = api_get_path(SYS_ARCHIVE_PATH).'certificate_preview';
        if (!is_dir($temp_folder)) {
            mkdir($temp_folder, api_get_permissions_for_new_directories());
        }

        $qr_code_web_filename = api_get_path(WEB_ARCHIVE_PATH).$filename;

        $certificate = new Certificate();
        $text = $certificate->parse_certificate_variables($content_html['variables']);
        $result = $certificate->generate_qr($text, $qr_code_filename);

        $new_content_html = $content_html['content'];
        $path_image = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document/images/gallery';
        $new_content_html = str_replace('../images/gallery', $path_image, $new_content_html);

        $path_image_in_default_course = api_get_path(WEB_CODE_PATH).'default_course_document';
        $new_content_html = str_replace(
            '/main/default_course_document',
            $path_image_in_default_course,
            $new_content_html
        );
        $new_content_html = str_replace(
            SYS_CODE_PATH . 'img/',
            api_get_path(WEB_IMG_PATH),
            $new_content_html
        );

        Display::display_reduced_header();

        echo '<style>body {background:none;}</style>
              <style media="print" type="text/css"> #print_div { visibility:hidden; } </style>';
        echo '<a href="javascript:window.print();" style="float:right; padding:4px;" id="print_div">';
        echo Display::return_icon('printmgr.gif', get_lang('Print'));
        echo '</a>';
        if (is_file($qr_code_filename) && is_readable($qr_code_filename)) {
            $new_content_html = str_replace(
                '((certificate_barcode))',
                Display::img($qr_code_web_filename),
                $new_content_html
            );
        }
        print_r($new_content_html);
        exit;
    }
}

// Is the document tool visible?
// Check whether the tool is actually visible
/*$table_course_tool = Database::get_course_table(TABLE_TOOL_LIST);
$course_id = api_get_course_int_id();
$tool_sql = 'SELECT visibility FROM '.$table_course_tool.'
             WHERE c_id = '.$course_id.' AND name = "'.TOOL_DOCUMENT.'"
             LIMIT 1';
$tool_result = Database::query($tool_sql);
$tool_row = Database::fetch_array($tool_result);
$tool_visibility = $tool_row['visibility'];*/

$htmlHeadXtra[] = '<script>
function confirmation (name) {
    if (confirm(" '.get_lang('AreYouSureToDeleteJS').' "+ name + " ?")) {
        return true;
    } else {
        return false;
    }
}

$(document).ready(function() {
    $(".convertAction").click(function() {
        var id = $(this).attr("data-documentId");
        var format = $(this).attr("data-formatType");
        convertModal(id, format);
    });
});
function convertModal (id, format) {
    $("#convertModal").modal("show");
    $("." + format + "FormatType").show();
    $("#convertSelect").change(function() {
        var formatTarget = $(this).val();
        window.location.href = "'.
            api_get_self() . '?' . api_get_cidreq() .
            '&curdirpath=' . $curdirpath .
            '&action=convertToPdf&formatTarget=' .
            '" + formatTarget + "&id=" + id + "&' .
            api_get_cidreq() . '&formatType=" + format;
    });
    $("#convertModal").on("hidden", function(){
        $("." + format + "FormatType").hide();
    });
}
</script>';

// If they are looking at group documents they can't see the root
if ($groupId != 0 && $curdirpath == '/') {
    $curdirpath = $group_properties['directory'];
    $curdirpathurl = urlencode($group_properties['directory']);
}

// Check visibility of the current dir path. Don't show anything if not allowed
//@todo check this validation for coaches
//if (!$is_allowed_to_edit || api_is_coach()) { before

if (!$is_allowed_to_edit && api_is_coach()) {
    if ($curdirpath != '/' && !(DocumentManager::is_visible($curdirpath, $courseInfo, $sessionId, 'folder'))) {
        api_not_allowed(true);
    }
}

/* 	Create shared folders */
if ($sessionId == 0) {
    //Create shared folder. Necessary for recycled courses.
    // session_id should always be zero and should always be created from a
    // base course, never from a session.
    if (!file_exists($base_work_dir.'/shared_folder')) {
        $usf_dir_title = get_lang('UserFolders');
        $usf_dir_name = '/shared_folder';
        //$groupId = 0;
        $visibility = 0;
        create_unexisting_directory(
            $courseInfo,
            api_get_user_id(),
            $sessionId,
            0,
            $to_user_id,
            $base_work_dir,
            $usf_dir_name,
            $usf_dir_title,
            $visibility
        );
    }
    // Create dynamic user shared folder
    if (!file_exists($base_work_dir.'/shared_folder/sf_user_'.$userId)) {
        $usf_dir_title = $userInfo['complete_name'];
        $usf_dir_name = '/shared_folder/sf_user_'.$userId;
        //$groupId = 0;
        $visibility = 1;
        create_unexisting_directory(
            $courseInfo,
            api_get_user_id(),
            $sessionId,
            0,
            $to_user_id,
            $base_work_dir,
            $usf_dir_name,
            $usf_dir_title,
            $visibility
        );
    }
} else {
    // Create shared folder session.
    if (!file_exists($base_work_dir.'/shared_folder_session_'.$sessionId)) {
        $usf_dir_title = get_lang('UserFolders').' ('.api_get_session_name($sessionId).')';
        $usf_dir_name = '/shared_folder_session_'.$sessionId;
        //$groupId = 0;
        $visibility = 0;
        create_unexisting_directory(
            $courseInfo,
            api_get_user_id(),
            $sessionId,
            0,
            $to_user_id,
            $base_work_dir,
            $usf_dir_name,
            $usf_dir_title,
            $visibility
        );
    }
    //Create dynamic user shared folder into a shared folder session
    if (!file_exists($base_work_dir.'/shared_folder_session_'.$sessionId.'/sf_user_'.$userId)) {
        $usf_dir_title = $userInfo['complete_name'].'('.api_get_session_name($sessionId).')';
        $usf_dir_name = '/shared_folder_session_'.$sessionId.'/sf_user_'.$userId;
        //$groupId = 0;
        $visibility = 1;
        create_unexisting_directory(
            $courseInfo,
            $userId,
            $sessionId,
            0,
            $to_user_id,
            $base_work_dir,
            $usf_dir_name,
            $usf_dir_title,
            $visibility
        );
    }
}

/* 	MAIN SECTION */

// Slideshow inititalisation
$_SESSION['image_files_only'] = '';
$image_files_only = '';

if ($is_certificate_mode) {
    $interbreadcrumb[] = array('url' => '../gradebook/index.php', 'name' => get_lang('Gradebook'));
} else {
    if ((isset($_GET['id']) && $_GET['id'] != 0) || isset($_GET['curdirpath']) || isset($_GET['createdir'])) {
        $interbreadcrumb[] = array('url' => 'document.php', 'name' => get_lang('Documents'));
    } else {
        $interbreadcrumb[] = array('url' => '#', 'name' => get_lang('Documents'));
    }
}

// Interbreadcrumb for the current directory root path
if (empty($document_data['parents'])) {
    if (isset($_GET['createdir'])) {
        $interbreadcrumb[] = array(
            'url' => $document_data['document_url'],
            'name' => $document_data['title'],
        );
    } else {
        $interbreadcrumb[] = array('url' => '#', 'name' => $document_data['title']);
    }
} else {
    $counter = 0;
    foreach ($document_data['parents'] as $document_sub_data) {
        //fixing double group folder in breadcrumb
        if ($groupId) {
            if ($counter == 0) {
                $counter++;
                continue;
            }
        }
        if (!isset($_GET['createdir']) && $document_sub_data['id'] == $document_data['id']) {
            $document_sub_data['document_url'] = '#';
        }
        $interbreadcrumb[] = array(
            'url' => $document_sub_data['document_url'],
            'name' => $document_sub_data['title'],
        );
        $counter++;
    }
}

if (isset($_GET['createdir'])) {
    $interbreadcrumb[] = array('url' => '#', 'name' => get_lang('CreateDir'));
}

$js_path = api_get_path(WEB_LIBRARY_PATH).'javascript/';

$htmlHeadXtra[] = '<link rel="stylesheet" href="'.$js_path.'jquery-jplayer/skin/chamilo/jplayer.blue.monday.css" type="text/css">';
$htmlHeadXtra[] = '<script type="text/javascript" src="'.$js_path.'jquery-jplayer/jplayer/jquery.jplayer.min.js"></script>';
$mediaplayer_path = api_get_path(WEB_LIBRARY_PATH).'mediaplayer/player.swf';

$documentAndFolders = DocumentManager::get_all_document_data(
    $courseInfo,
    $curdirpath,
    $groupIid,
    null,
    $is_allowed_to_edit || $group_member_with_upload_rights,
    false
);

$count = 1;
$jquery = null;

if (!empty($documentAndFolders)) {
    foreach ($documentAndFolders as $file) {
        if ($file['filetype'] == 'file') {
            $path_info = pathinfo($file['path']);
            $extension = '';
            if (!empty($path_info['extension'])) {
                $extension = strtolower($path_info['extension']);
            }

            //@todo use a js loop to auto generate this code
            if (in_array($extension, array('ogg', 'mp3', 'wav'))) {
                // Get the document data from the ID
                $document_data = DocumentManager::get_document_data_by_id(
                    $file['id'],
                    api_get_course_id(),
                    false,
                    $sessionId
                );
                if ($sessionId != 0 && !$document_data) {
                    /* If there is a session defined and asking for the document
                     * from the session* didn't work, try it from the
                     course (out of a session context) */
                    $document_data = DocumentManager::get_document_data_by_id(
                        $file['id'],
                        api_get_course_id(),
                        false,
                        0
                    );
                }

                if ($extension == 'ogg') {
                    $extension = 'oga';
                }

                $params = array('url' => $document_data['direct_url'],
                    'extension' => $extension,
                    'count' => $count
                );
                $jquery .= DocumentManager::generate_jplayer_jquery($params);
                $count++;
            }
        }
    }
}

$htmlHeadXtra[] = '<script>
$(document).ready( function() {
    //Experimental changes to preview mp3, ogg files
     '.$jquery.'
});
</script>';

// Lib for event log, stats & tracking & record of the access
Event::event_access_tool(TOOL_DOCUMENT);

/* 	DISPLAY */
if ($groupId != 0) { // Add group name after for group documents
    $add_group_to_title = ' ('.$group_properties['name'].')';
}

$moveForm = '';

/* 	MOVE FILE OR DIRECTORY */
//Only teacher and all users into their group and each user into his/her shared folder
if ($is_allowed_to_edit ||
    $group_member_with_upload_rights ||
    DocumentManager::is_my_shared_folder(api_get_user_id(), $curdirpath, $sessionId) ||
    DocumentManager::is_my_shared_folder(api_get_user_id(), $moveTo, $sessionId)
) {
    if (isset($_GET['move']) && $_GET['move'] != '') {
        $my_get_move = intval($_REQUEST['move']);

        if (api_is_coach()) {
            if (!DocumentManager::is_visible_by_id($my_get_move, $courseInfo, $sessionId, api_get_user_id())) {
                api_not_allowed(true);
            }
        }

        if (!$is_allowed_to_edit) {
            if (DocumentManager::check_readonly($courseInfo, api_get_user_id(), $my_get_move)) {
                api_not_allowed(true);
            }
        }
        // Get the document data from the ID
        $document_to_move = DocumentManager::get_document_data_by_id(
            $my_get_move,
            api_get_course_id(),
            false,
            $sessionId
        );

        $move_path = $document_to_move['path'];
        if (!empty($document_to_move)) {
            $folders = DocumentManager::get_all_document_folders(
                $courseInfo,
                $groupIid,
                $is_allowed_to_edit || $group_member_with_upload_rights
            );

            // filter if is my shared folder. TODO: move this code to build_move_to_selector function
            if (DocumentManager::is_my_shared_folder(api_get_user_id(), $curdirpath, $sessionId) &&
                !$is_allowed_to_edit
            ) {
                //only main user shared folder
                $main_user_shared_folder_main = '/shared_folder/sf_user_'.api_get_user_id();
                $main_user_shared_folder_sub = '/shared_folder\/sf_user_'.api_get_user_id().'\//'; //all subfolders
                $user_shared_folders = array();

                foreach ($folders as $fold) {
                    if ($main_user_shared_folder_main == $fold || preg_match($main_user_shared_folder_sub, $fold)) {
                        $user_shared_folders[] = $fold;
                    }
                }

                $moveForm .= '<legend>'.get_lang('Move').'</legend>';
                $moveForm .= DocumentManager::build_move_to_selector(
                    $user_shared_folders,
                    $move_path,
                    $my_get_move,
                    $group_properties['directory']
                );
            } else {
                $moveForm .= '<legend>'.get_lang('Move').'</legend>';
                $moveForm .= DocumentManager::build_move_to_selector(
                    $folders,
                    $move_path,
                    $my_get_move,
                    $group_properties['directory']
                );
            }
        }
    }

    if (!empty($moveTo) && isset($_POST['move_file'])) {
        if (!$is_allowed_to_edit) {
            if (DocumentManager::check_readonly($courseInfo, api_get_user_id(), $_POST['move_file'])) {
                api_not_allowed(true);
            }
        }

        if (api_is_coach()) {
            if (!DocumentManager::is_visible_by_id($_POST['move_file'], $courseInfo, $sessionId, api_get_user_id())) {
                api_not_allowed(true);
            }
        }

        // Get the document data from the ID
        $document_to_move = DocumentManager::get_document_data_by_id(
            $_POST['move_file'],
            api_get_course_id(),
            false,
            $sessionId
        );

        // Security fix: make sure they can't move files that are not in the document table
        if (!empty($document_to_move)) {

            $real_path_target = $base_work_dir.$moveTo.'/'.basename($document_to_move['path']);
            $fileExist = false;
            if (file_exists($real_path_target)) {
                $fileExist = true;
            }
            if (move($base_work_dir.$document_to_move['path'], $base_work_dir.$moveTo)) {
                DocumentManager::updateDbInfo(
                    'update',
                    $document_to_move['path'],
                    $moveTo . '/' . basename($document_to_move['path'])
                );

                //update database item property
                $doc_id = $_POST['move_file'];

                if (is_dir($real_path_target)) {
                    api_item_property_update(
                        $courseInfo,
                        TOOL_DOCUMENT,
                        $doc_id,
                        'FolderMoved',
                        api_get_user_id(),
                        $groupIid,
                        null,
                        null,
                        null,
                        $sessionId
                    );
                    Display::addFlash(Display::return_message(get_lang('DirMv'), 'confirmation'));
                } elseif (is_file($real_path_target)) {
                    api_item_property_update(
                        $courseInfo,
                        TOOL_DOCUMENT,
                        $doc_id,
                        'DocumentMoved',
                        api_get_user_id(),
                        $groupIid,
                        null,
                        null,
                        null,
                        $sessionId
                    );
                    Display::addFlash(Display::return_message(get_lang('DocMv'), 'confirmation'));
                }

                // Set the current path
                $curdirpath = $_POST['move_to'];
                $curdirpathurl = urlencode($_POST['move_to']);
            } else {
                if ($fileExist) {
                    if (is_dir($real_path_target)) {
                        $message = Display::return_message(get_lang('DirExists'), 'error');
                    } elseif (is_file($real_path_target)) {
                        $message = Display::return_message(get_lang('FileExists'), 'v');
                    }
                    Display::addFlash($message);
                } else {
                    Display::addFlash(Display::return_message(get_lang('Impossible'), 'error'));
                }
            }
        } else {
            Display::addFlash(Display::return_message(get_lang('Impossible'), 'error'));
        }
    }
}

/* 	DELETE FILE OR DIRECTORY */
//Only teacher and all users into their group
if ($is_allowed_to_edit ||
    $group_member_with_upload_rights ||
    DocumentManager::is_my_shared_folder(api_get_user_id(), $curdirpath, $sessionId)
) {
    if (isset($_POST['action']) && isset($_POST['ids'])) {
        $files = $_POST['ids'];
        $readonlyAlreadyChecked = false;
        $messages = '';
        $items = array(
            '/audio',
            '/flash',
            '/images',
            '/shared_folder',
            '/video',
            '/chat_files',
            '/certificates'
        );
        foreach ($files as $documentId) {
            $data = DocumentManager::get_document_data_by_id($documentId, $courseInfo['code']);
            if (in_array($data['path'], $items)) {
                // exclude system directories (do not allow deletion)
                continue;
            } else {
                switch ($_POST['action']) {
                    case 'set_invisible':
                        $visibilityCommand = 'invisible';
                        if (api_item_property_update(
                            $courseInfo,
                            TOOL_DOCUMENT,
                            $documentId,
                            $visibilityCommand,
                            api_get_user_id(),
                            null,
                            null,
                            null,
                            null,
                            $sessionId
                        )) {
                            $messages .= Display::return_message(get_lang('VisibilityChanged').': '.$data['title'], 'confirmation');
                        } else {
                            $messages .= Display::return_message(get_lang('ViModProb'), 'error');
                        }
                        break;
                    case 'set_visible':
                        $visibilityCommand = 'visible';
                        if (api_item_property_update(
                            $courseInfo,
                            TOOL_DOCUMENT,
                            $documentId,
                            $visibilityCommand,
                            api_get_user_id(),
                            null,
                            null,
                            null,
                            null,
                            $sessionId
                        )) {
                            $messages .= Display::return_message(get_lang('VisibilityChanged').': '.$data['title'], 'confirmation');
                        } else {
                            $messages .=  Display::return_message(get_lang('ViModProb'), 'error');
                        }
                        break;
                    case 'delete':
                        // Check all documents scheduled for deletion
                        // If one of them is read-only, abandon deletion
                        // Note: this is only executed once
                        if (!$readonlyAlreadyChecked) {
                            foreach ($files as $id) {
                                if (!$is_allowed_to_edit) {
                                    if (DocumentManager::check_readonly(
                                        $courseInfo,
                                        api_get_user_id(),
                                        null,
                                        $id,
                                        false,
                                        $sessionId
                                    )) {
                                        $messages .= Display::return_message(
                                            get_lang('CantDeleteReadonlyFiles'),
                                            'error'
                                        );
                                        break 2;
                                    }
                                }
                            }
                            $readonlyAlreadyChecked = true;
                        }

                        $deleteDocument = DocumentManager::delete_document(
                            $courseInfo,
                            null,
                            $base_work_dir,
                            $sessionId,
                            $documentId,
                            $groupIid
                        );
                        if (!empty($deleteDocument)) {
                            $messages .= Display::return_message(
                                get_lang('DocDeleted').': '.$data['title'],
                                'confirmation'
                            );
                        }
                        break;
                }
            }
        } // endforeach

        Display::addFlash($messages);
        header('Location: '.$currentUrl);
        exit;
    }
}

$dirForm = null;

/* 	CREATE DIRECTORY */
//Only teacher and all users into their group and any user into his/her shared folder
if ($is_allowed_to_edit ||
    $group_member_with_upload_rights ||
    DocumentManager::is_my_shared_folder(api_get_user_id(), $curdirpath, $sessionId)
) {
    // Create directory with $_POST data
    if (isset($_POST['create_dir']) && $_POST['dirname'] != '') {
        // Needed for directory creation
        $post_dir_name = $_POST['dirname'];

        if ($post_dir_name == '../' || $post_dir_name == '.' || $post_dir_name == '..') {
            $message = Display::return_message(get_lang('CannotCreateDir'), 'error');
        } else {
            // dir_id is the parent folder id.
            if (!empty($_POST['dir_id'])) {
                // Get the document data from the ID
                $document_data = DocumentManager::get_document_data_by_id(
                    $_POST['dir_id'],
                    api_get_course_id(),
                    false,
                    $sessionId
                );
                if ($sessionId != 0 && !$document_data) {
                    // If there is a session defined and asking for the
                    // document * from the session* didn't work, try it from
                    // the course (out of a session context)
                    $document_data = DocumentManager::get_document_data_by_id(
                        $_POST['dir_id'],
                        api_get_course_id(),
                        false,
                        0
                    );
                }
                $curdirpath = $document_data['path'];
            }
            $added_slash = ($curdirpath == '/') ? '' : '/';
            $dir_name = $curdirpath.$added_slash.api_replace_dangerous_char($post_dir_name);
            $dir_name = disable_dangerous_file($dir_name);
            $dir_check = $base_work_dir.$dir_name;
            $visibility = empty($groupId) ? null : 1;

            $newFolderData = create_unexisting_directory(
                $courseInfo,
                api_get_user_id(),
                $sessionId,
                $groupId,
                $to_user_id,
                $base_work_dir,
                $dir_name,
                $post_dir_name,
                $visibility
            );

            if (!empty($newFolderData)) {
                $message = Display::return_message(
                    get_lang('DirCr') . ' ' . $newFolderData['title'],
                    'confirmation'
                );
            } else {
                $message = Display::return_message(
                    get_lang('CannotCreateDir'),
                    'error'
                );
            }

        }
        Display::addFlash($message);
    }

    // Show them the form for the directory name
    if (isset($_GET['createdir'])) {
        $dirForm = DocumentManager::create_dir_form($document_id);
    }
}

/* 	VISIBILITY COMMANDS */
if ($is_allowed_to_edit) {
    if ((isset($_GET['set_invisible']) && !empty($_GET['set_invisible'])) ||
        (isset($_GET['set_visible']) && !empty($_GET['set_visible']))
    ) {
        // Make visible or invisible?
        if (isset($_GET['set_visible'])) {
            $update_id = intval($_GET['set_visible']);
            $visibility_command = 'visible';
        } else {
            $update_id = intval($_GET['set_invisible']);
            $visibility_command = 'invisible';
        }

        if (!$is_allowed_to_edit) {
            if (api_is_coach()) {
                if (!DocumentManager::is_visible_by_id($update_id, $courseInfo, $sessionId, api_get_user_id())) {
                    api_not_allowed(true);
                }
            }
            if (DocumentManager::check_readonly($courseInfo, api_get_user_id(), '', $update_id)) {
                api_not_allowed(true);
            }
        }

        // Update item_property to change visibility
        if (api_item_property_update(
            $courseInfo,
            TOOL_DOCUMENT,
            $update_id,
            $visibility_command,
            api_get_user_id(),
            null,
            null,
            null,
            null,
            $sessionId)
        ) {
            Display::addFlash(
                Display::return_message(get_lang('VisibilityChanged'), 'confirmation')
            );
        } else {
            Display::addFlash(
                Display::return_message(get_lang('ViModProb'), 'error')
            );
        }

        header('Location: '.$currentUrl);
        exit;
    }
}
$templateForm = null;

/* 	TEMPLATE ACTION */
//Only teacher and all users into their group
if ($is_allowed_to_edit ||
    $group_member_with_upload_rights ||
    DocumentManager::is_my_shared_folder(api_get_user_id(), $curdirpath, $sessionId)
) {
    if (isset($_GET['add_as_template']) && !isset($_POST['create_template'])) {

        $document_id_for_template = intval($_GET['add_as_template']);

        // Create the form that asks for the directory name
        $templateForm .= '
            <form name="set_document_as_new_template" class="form-horizontal" enctype="multipart/form-data" action="' . api_get_self() . '?add_as_template=' . $document_id_for_template . '" method="post">
                <fieldset>
                    <legend>' . get_lang('AddAsTemplate') . '</legend>
                    <div class="form-group">
                        <label for="template_title" class="col-sm-2 control-label">' . get_lang('TemplateName') . '</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="template_title" name="template_title">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="template_image" class="col-sm-2 control-label">' . get_lang('TemplateImage') . '</label>
                        <div class="col-sm-10">
                            <input type="file" name="template_image" id="template_image">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-10">
                            <button type="submit" name="create_template" class="btn btn-primary">' . get_lang('CreateTemplate') . '</button>
                        </div>
                    </div>
                    <input type="hidden" name="curdirpath" value="' . $curdirpath . '" />
                </fieldset>
            </form>
            <hr>
        ';
    } elseif (isset($_GET['add_as_template']) && isset($_POST['create_template'])) {

        $document_id_for_template = intval($_GET['add_as_template']);
        $title = Security::remove_XSS($_POST['template_title']);
        $user_id = api_get_user_id();

        // Create the template_thumbnails folder in the upload folder (if needed)
        if (!is_dir(api_get_path(SYS_COURSE_PATH).$courseInfo['directory'].'/upload/template_thumbnails/')) {
            @mkdir(
                api_get_path(SYS_COURSE_PATH).$courseInfo['directory'].'/upload/template_thumbnails/',
                api_get_permissions_for_new_directories()
            );
        }

        // Upload the file
        if (!empty($_FILES['template_image']['name'])) {
            $upload_ok = process_uploaded_file($_FILES['template_image']);

            if ($upload_ok) {
                // Try to add an extension to the file if it hasn't one
                $new_file_name = $courseInfo['code'].'-'.add_ext_on_mime(
                    stripslashes($_FILES['template_image']['name']),
                    $_FILES['template_image']['type']
                );

                // Upload dir
                $upload_dir = api_get_path(SYS_COURSE_PATH).$courseInfo['directory'].'/upload/template_thumbnails/';

                // Resize image to max default and end upload
                $temp = new Image($_FILES['template_image']['tmp_name']);
                $picture_info = $temp->get_image_info();

                $max_width_for_picture = 100;

                if ($picture_info['width'] > $max_width_for_picture) {
                    $temp->resize($max_width_for_picture);
                }
                $temp->send_image($upload_dir.$new_file_name);
            }
        }

        DocumentManager::set_document_as_template(
            $title,
            '',
            $document_id_for_template,
            $course_code,
            $user_id,
            $new_file_name
        );
        Display::addFlash(
            Display::return_message(get_lang('DocumentSetAsTemplate'), 'confirmation')
        );
    }

    if (isset($_GET['remove_as_template'])) {
        $document_id_for_template = intval($_GET['remove_as_template']);
        $user_id = api_get_user_id();
        DocumentManager::unset_document_as_template(
            $document_id_for_template,
            $course_code,
            $user_id
        );

        Display::addFlash(
            Display::return_message(get_lang('DocumentUnsetAsTemplate'), 'confirmation')
        );
    }
}

// END ACTION MENU
// Attach certificate in the gradebook
if (isset($_GET['curdirpath']) &&
    $_GET['curdirpath'] == '/certificates' &&
    isset($_GET['set_certificate']) &&
    $_GET['set_certificate'] == strval(intval($_GET['set_certificate']))
) {
    if (isset($_GET['cidReq'])) {
        $course_id = Security::remove_XSS($_GET['cidReq']); // course id
        $document_id = Security::remove_XSS($_GET['set_certificate']); // document id
        DocumentManager::attach_gradebook_certificate($course_id, $document_id);
        $message = Display::return_message(get_lang('IsDefaultCertificate'), 'normal');
        Display::addFlash(
            $message
        );
    }
}

/* 	GET ALL DOCUMENT DATA FOR CURDIRPATH */
if (isset($_GET['keyword']) && !empty($_GET['keyword'])) {
    $documentAndFolders = DocumentManager::get_all_document_data(
        $courseInfo,
        $curdirpath,
        $groupIid,
        null,
        $is_allowed_to_edit || $group_member_with_upload_rights,
        true
    );
} else {
    $documentAndFolders = DocumentManager::get_all_document_data(
        $courseInfo,
        $curdirpath,
        $groupIid,
        null,
        $is_allowed_to_edit || $group_member_with_upload_rights,
        false
    );
}

if ($groupId != 0) {
    $userAccess = GroupManager::user_has_access(
        api_get_user_id(),
        $groupIid,
        GroupManager::GROUP_TOOL_DOCUMENTS
    );
    if ($userAccess) {
        $folders = DocumentManager::get_all_document_folders(
            $courseInfo,
            $groupIid,
            $is_allowed_to_edit || $group_member_with_upload_rights
        );
    }
} else {
    $folders = DocumentManager::get_all_document_folders(
        $courseInfo,
        $groupIid,
        $is_allowed_to_edit || $group_member_with_upload_rights
    );
}

if (!isset($folders) || $folders === false) {
    $folders = array();
}
$btngroup = array('class' => 'btn btn-default');

/* GO TO PARENT DIRECTORY */
$actionsLeft = '';

if ($curdirpath != '/' && $curdirpath != $group_properties['directory'] && !$is_certificate_mode) {
    $actionsLeft = '<a href="'.api_get_self().'?'.api_get_cidreq().'&id='.$parent_id.'">';
    $actionsLeft .= Display::return_icon('folder_up.png', get_lang('Up'), '', ICON_SIZE_MEDIUM);
    $actionsLeft .= '</a>';
}

if ($is_certificate_mode && $curdirpath != '/certificates') {
    $actionsLeft .= Display::url(
        Display::return_icon('folder_up.png', get_lang('Up'), '', ICON_SIZE_MEDIUM),
        api_get_self().'?'.api_get_cidreq().'&curdirpath='.$curdirpath
    );
}

$column_show = array();

if ($is_allowed_to_edit ||
    $group_member_with_upload_rights ||
    DocumentManager::is_my_shared_folder(api_get_user_id(), $curdirpath, $sessionId)
) {
    // TODO:check enable more options for shared folders
    /* CREATE NEW DOCUMENT OR NEW DIRECTORY / GO TO UPLOAD / DOWNLOAD ZIPPED FOLDER */
    // Create new document
    if (!$is_certificate_mode) {
        $actionsLeft .= Display::url(
            Display::return_icon('new_document.png', get_lang('CreateDoc'), '', ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'document/create_document.php?'.api_get_cidreq().'&id='.$document_id
        );

        // Create new draw
        if (api_get_setting('enabled_support_svg') == 'true') {
            if (api_browser_support('svg')) {
                $actionsLeft .= Display::url(
                    Display::return_icon('new_draw.png', get_lang('Draw'), '', ICON_SIZE_MEDIUM),
                    api_get_path(WEB_CODE_PATH).'document/create_draw.php?'.api_get_cidreq().'&id='.$document_id
                );
            } else {
                $actionsLeft .= Display::return_icon('new_draw_na.png', get_lang('BrowserDontSupportsSVG'), '', ICON_SIZE_MEDIUM);
            }
        }

        // Create new paint
        if (api_get_setting('enabled_support_pixlr') == 'true') {
            $actionsLeft .= Display::url(
                Display::return_icon('new_paint.png', get_lang('PhotoRetouching'), '', ICON_SIZE_MEDIUM),
                api_get_path(WEB_CODE_PATH).'document/create_paint.php?'.api_get_cidreq().'&id='.$document_id
            );
        }

        // Record an image clip from my webcam
        if (api_get_setting('enable_webcam_clip') == 'true') {
            $actionsLeft .= Display::url(
                Display::return_icon('webcam.png', get_lang('WebCamClip'), '', ICON_SIZE_MEDIUM),
                api_get_path(WEB_CODE_PATH).'document/webcam_clip.php?'.api_get_cidreq().'&id='.$document_id
            );
        }

        // Record audio (nanogong)
        if (api_get_setting('enable_record_audio') === 'true') {
            $actionsLeft .= Display::url(
                Display::return_icon('new_recording.png', get_lang('RecordMyVoice'), '', ICON_SIZE_MEDIUM),
                api_get_path(WEB_CODE_PATH) . 'document/record_audio.php?' . api_get_cidreq() . '&id=' . $document_id
            );
        }

        // Create new audio from text
        if (api_get_setting('enabled_text2audio') == 'true') {
            $actionsLeft .= Display::url(
                Display::return_icon('new_sound.png', get_lang('CreateAudio'), '', ICON_SIZE_MEDIUM),
                api_get_path(WEB_CODE_PATH).'document/create_audio.php?'.api_get_cidreq().'&id='.$document_id
            );
        }
    }

    // Create new certificate
    if ($is_certificate_mode) {
        $actionsLeft .= Display::url(
            Display::return_icon('new_certificate.png', get_lang('CreateCertificate'), '', ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'document/create_document.php?'.api_get_cidreq().'&id='.$document_id.'&certificate=true&selectcat='.$selectcat
        );
    }
    // File upload link
    if ($is_certificate_mode) {
        $actionsLeft .= Display::url(
            Display::return_icon('upload_certificate.png', get_lang('UploadCertificate'), '', ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'document/upload.php?'.api_get_cidreq().'&id='.$current_folder_id.'&certificate=true'
        );
    } else {
        $actionsLeft .= Display::url(
            Display::return_icon('upload_file.png', get_lang('UplUploadDocument'), '', ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'document/upload.php?'.api_get_cidreq().'&id='.$current_folder_id
        );
    }

    /*echo '<a href="#" id="jcapture">';
    echo Display::display_icon('capture.png', get_lang('CatchScreenCasts'), '', ICON_SIZE_MEDIUM).'</a>';*/

    if ($capturePluginInstalled) {
        $actionsLeft .= '<span id="appletplace"></span>';
        $actionsLeft .= Display::url(
            Display::return_icon('capture.png', get_lang('CatchScreenCasts'), '', ICON_SIZE_MEDIUM),
            '#',
            array('id' => 'jcapture')
        );
    }

    // Create directory
    if (!$is_certificate_mode) {
        $actionsLeft .= Display::url(
            Display::return_icon('new_folder.png', get_lang('CreateDir'), '', ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'document/document.php?'.api_get_cidreq().'&id='.$document_id.'&createdir=1'
        );
    }
}

require 'document_slideshow.inc.php';
if ($image_present && !isset($_GET['keyword'])) {
    $actionsLeft .= Display::url(
        Display::return_icon('slideshow.png', get_lang('ViewSlideshow'), '', ICON_SIZE_MEDIUM),
        api_get_path(WEB_CODE_PATH).'document/slideshow.php?'.api_get_cidreq().'&curdirpath='.$curdirpathurl
    );
}

if (api_is_allowed_to_edit(null, true)) {
    $actionsLeft .= Display::url(
        Display::return_icon('percentage.png', get_lang('DocumentQuota'), '', ICON_SIZE_MEDIUM),
        api_get_path(WEB_CODE_PATH).'document/document_quota.php?'.api_get_cidreq()
    );
}

if (!$is_certificate_mode) {
    /* BUILD SEARCH FORM */
    $form = new FormValidator(
        'search_document',
        'get',
        api_get_self().'?'.api_get_cidreq(),
        '',
        array(),
        FormValidator::LAYOUT_INLINE
    );
    $form->addText('keyword', '', false, array('class' => 'col-md-2'));
    $form->addElement('hidden', 'cidReq', api_get_course_id());
    $form->addElement('hidden', 'id_session', api_get_session_id());
    $form->addElement('hidden', 'gidReq', $groupId);
    $form->addButtonSearch(get_lang('Search'));
    $actionsRight = $form->returnForm();
}

$table_footer = '';
$total_size = 0;
$sortable_data = array();

if (isset($documentAndFolders) && is_array($documentAndFolders)) {
    if ($groupId == 0 ||
        GroupManager::user_has_access(
            $userId,
            $groupIid,
            GroupManager::GROUP_TOOL_DOCUMENTS
        )
    ) {
        $count = 1;
        $countedPaths = array();
        $countedPaths = array();

        foreach ($documentAndFolders as $key => $document_data) {
            $row = array();
            $row['id'] = $document_data['id'];
            $row['type'] = $document_data['filetype'];

            // If the item is invisible, wrap it in a span with class invisible.
            $is_visible = DocumentManager::is_visible_by_id(
                $document_data['id'],
                $courseInfo,
                $sessionId,
                api_get_user_id(),
                false
            );

            $invisibility_span_open = ($is_visible == 0) ? '<span class="muted">' : '';
            $invisibility_span_close = ($is_visible == 0) ? '</span>' : '';

            // Size (or total size of a directory)
            $size = $document_data['filetype'] == 'folder' ? get_total_folder_size($document_data['path'], $is_allowed_to_edit) : $document_data['size'];

            // Get the title or the basename depending on what we're using
            if ($document_data['title'] != '') {
                $document_name = $document_data['title'];
            } else {
                $document_name = basename($document_data['path']);
            }

            $row['name'] = $document_name;
            // Data for checkbox
            if (($is_allowed_to_edit || $group_member_with_upload_rights) && count($documentAndFolders) > 1) {
                $row[] = $document_data['id'];
            }

            if (DocumentManager::is_folder_to_avoid($document_data['path'], $is_certificate_mode)) {
                continue;
            }

            // Show the owner of the file only in groups
            $user_link = '';
            if (!empty($groupId)) {
                if (!empty($document_data['insert_user_id'])) {
                    $user_info = api_get_user_info($document_data['insert_user_id']);
                    $user_link = '<div class="document_owner">'.
                        get_lang('Owner').': '.UserManager::getUserProfileLink($user_info).'</div>';
                }
            }

            // Icons (clickable)
            $row[] = DocumentManager::create_document_link(
                $document_data,
                true,
                $count,
                $is_visible
            );

            $path_info = pathinfo($document_data['path']);

            if (isset($path_info['extension']) &&
                in_array($path_info['extension'], array('ogg', 'mp3', 'wav'))
            ) {
                $count++;
            }

            // Validation when belongs to a session
            $session_img = api_get_session_image($document_data['session_id'], $_user['status']);

            // Document title with link
            $row[] = DocumentManager::create_document_link($document_data, false, null, $is_visible).
                $session_img.'<br />'.$invisibility_span_open.
                '<i>'.nl2br(htmlspecialchars($document_data['comment'], ENT_QUOTES, $charset)).'</i>'.
                $invisibility_span_close.
                $user_link;

            // Comments => display comment under the document name
            $display_size = format_file_size($size);

            $row[] = '<span style="display:none;">'.$size.'</span>'.
                $invisibility_span_open.
                $display_size.
                $invisibility_span_close;
            // Last edit date
            $last_edit_date = api_get_local_time($document_data['lastedit_date']);
            $display_date = date_to_str_ago($document_data['lastedit_date']).
                ' <div class="muted"><small>'.$last_edit_date."</small></div>";

            $row[] = $invisibility_span_open.$display_date.$invisibility_span_close;

            // Admins get an edit column
            if ($is_allowed_to_edit ||
                $groupMemberWithEditRights ||
                DocumentManager::is_my_shared_folder(api_get_user_id(), $curdirpath, $sessionId) ||
                $document_data['insert_user_id'] == api_get_user_id()
            ) {
                $is_template = isset($document_data['is_template']) ? $document_data['is_template'] : false;

                // If readonly, check if it the owner of the file or if the user is an admin
                if ($document_data['insert_user_id'] == api_get_user_id() || api_is_platform_admin()) {
                    $edit_icons = DocumentManager::build_edit_icons(
                        $document_data,
                        $key,
                        $is_template,
                        0,
                        $is_visible
                    );
                } else {
                    $edit_icons = DocumentManager::build_edit_icons(
                        $document_data,
                        $key,
                        $is_template,
                        $document_data['readonly'],
                        $is_visible
                    );
                }
                $row[] = $edit_icons;
            } else {
                $row[] = '';
            }
            $row[] = $last_edit_date;
            $row[] = $size;
            $row[] = $document_name;

            $total_size = $total_size + $size;
            if (!isset($countedPaths[$document_data['path']])) {
                $total_size = $total_size + $size;
                $countedPaths[$document_data['path']] = true;
            }

            if ((isset($_GET['keyword']) &&
                DocumentManager::search_keyword($document_name, $_GET['keyword'])) ||
                !isset($_GET['keyword']) || empty($_GET['keyword'])
            ) {
                $sortable_data[] = $row;
            }
        }
    }
} else {
    $sortable_data = '';
    $table_footer = get_lang('NoDocsInFolder');
}

if (!is_null($documentAndFolders)) {
    // Show download zipped folder icon
    global $total_size;
    if (!$is_certificate_mode && $total_size != 0
        && (api_get_setting('students_download_folders') == 'true'
        || api_is_allowed_to_edit()
        || api_is_platform_admin()
        )
    ) {
        //for student does not show icon into other shared folder, and does not show into main path (root)
        if (DocumentManager::is_my_shared_folder(api_get_user_id(), $curdirpath, $sessionId)
            && $curdirpath != '/'
            || api_is_allowed_to_edit()
            || api_is_platform_admin()
        ) {
            $actionsLeft .= Display::url(
                Display::return_icon(
                    'save_pack.png',
                    get_lang('Save').' (ZIP)',
                    '',
                    ICON_SIZE_MEDIUM
                ),
                api_get_path(WEB_CODE_PATH).'document/document.php?'.api_get_cidreq().'&action=downloadfolder&id='.$document_id
            );
        }
    }
}

if (api_is_platform_admin()) {
    if (api_get_configuration_value('document_manage_deleted_files')) {
        $actionsLeft .= Display::url(
            get_lang('Recycle'),
            api_get_path(WEB_CODE_PATH).'document/recycle.php?'.api_get_cidreq(),
            array('class' => 'btn btn-default')
        );
    }
}

if (!empty($moveTo)) {
    $document_id = DocumentManager::get_document_id($courseInfo, $moveTo);
}

if (isset($_GET['createdir']) && isset($_POST['dirname']) && $_POST['dirname'] != '') {
    $post_dir_name = $_POST['dirname'];
    $document_id = DocumentManager::get_document_id($courseInfo, $_POST['dirname']);
}
$selector = null;

if (!$is_certificate_mode) {
    $selector = DocumentManager::build_directory_selector(
        $folders,
        $document_id,
        (isset($group_properties['directory']) ? $group_properties['directory'] : array()),
        true
    );
}

if (($is_allowed_to_edit || $group_member_with_upload_rights) &&
    count($documentAndFolders) > 1
) {
    $column_show[] = 1;
}

$column_show[] = 1;
$column_show[] = 1;
$column_show[] = 1;
$column_show[] = 1;

if ($is_allowed_to_edit
    || $group_member_with_upload_rights
    || DocumentManager::is_my_shared_folder(api_get_user_id(), $curdirpath, $sessionId)
) {
    $column_show[] = 1;
}
$column_show[] = 0;
$column_show[] = 0;

$column_order = array();

if (count($row) == 12) {
    //teacher
    $column_order[2] = 8; //name
    $column_order[3] = 7;
    $column_order[4] = 6;
} elseif (count($row) == 10) {
    //student
    $column_order[1] = 6;
    $column_order[2] = 5;
    $column_order[3] = 4;
}

$default_column = $is_allowed_to_edit ? 2 : 1;
$tableName = $is_allowed_to_edit ? 'teacher_table' : 'student_table';

$table = new SortableTableFromArrayConfig(
    $sortable_data,
    $default_column,
    20,
    $tableName,
    $column_show,
    $column_order,
    'ASC',
    true
);
$query_vars = array();
if (isset($_GET['keyword'])) {
    $query_vars['keyword'] = Security::remove_XSS($_GET['keyword']);
} else {
    $query_vars['curdirpath'] = $curdirpath;
}

if ($groupId) {
    $query_vars['gidReq'] = $groupId;
}
$query_vars['cidReq'] = api_get_course_id();
$table->set_additional_parameters($query_vars);

$column = 0;

if (($is_allowed_to_edit || $group_member_with_upload_rights) && count($documentAndFolders) > 1) {
    $table->set_header($column++, '', false, array('style' => 'width:12px;'));
}
$table->set_header($column++, get_lang('Type'), true, array('style' => 'width:30px;'));
$table->set_header($column++, get_lang('Name'));
$table->set_header($column++, get_lang('Size'), true, array('style' => 'width:50px;'));
$table->set_header($column++, get_lang('Date'), true, array('style' => 'width:150px;'));
// Admins get an edit column
if ($is_allowed_to_edit
    || $group_member_with_upload_rights
    || DocumentManager::is_my_shared_folder(api_get_user_id(), $curdirpath, $sessionId)) {
    $table->set_header($column++, get_lang('Actions'), false, array('class' => 'td_actions'));
}

// Actions on multiple selected documents
// TODO: Currently only delete action -> take only DELETE permission into account

if (count($documentAndFolders) > 1) {
    if ($is_allowed_to_edit || $groupMemberWithEditRights) {
        $form_actions = array();
        $form_action['set_invisible'] = get_lang('SetInvisible');
        $form_action['set_visible'] = get_lang('SetVisible');
        $form_action['delete'] = get_lang('Delete');
        $portfolio_actions = Portfolio::actions();
        foreach ($portfolio_actions as $action) {
            $form_action[$action->get_name()] = $action->get_title();
        }
        $table->set_form_actions($form_action, 'ids');
    }
}

Display::display_header('', 'Doc');

/* Introduction section (editable by course admins) */

if (!empty($groupId)) {
    Display::display_introduction_section(TOOL_DOCUMENT.$groupId);
} else {
    Display::display_introduction_section(TOOL_DOCUMENT);
}
$toolbar = Display::toolbarAction(
    'toolbar-document',
    array(0 => $actionsLeft, 1 => $actionsRight)
);

echo $toolbar;
echo $templateForm;
echo $moveForm;
echo $dirForm;
echo $selector;

$table->display();

if (count($documentAndFolders) > 1) {
    if ($is_allowed_to_edit || $group_member_with_upload_rights) {
        // Getting the course quota
        $course_quota = DocumentManager::get_course_quota();

        // Calculating the total space
        $already_consumed_space_course = DocumentManager::documents_total_space(
            api_get_course_int_id()
        );

        // Displaying the quota
        DocumentManager::display_simple_quota(
            $course_quota,
            $already_consumed_space_course
        );
    }
}
if (!empty($table_footer)) {
    Display::display_warning_message($table_footer);
}

echo '
    <div id="convertModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="text-align: center;">
                <button type="button" class="close" data-dismiss="modal" aria-label="' . get_lang('Close') . '">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">' . get_lang('Convert') . '</h4>
            </div>
            <div class="modal-body">
              <form action="#" class="form-horizontal">
                  <div class="form-group">
                      <label class="col-sm-4 control-label" for="convertSelect">' . get_lang('ConvertFormats') . '</label>
                      <div class="col-sm-8">
                          <select id="convertSelect">
                              <option value="">' . get_lang('Select') . '</option>
                              <option value="pdf">
                                  PDF - Portable Document File
                              </option>
                              <option value="odt" style="display:none;" class="textFormatType">
                                  ODT - Open Document Text
                              </option>
                              <option value="odp" style="display:none;" class="presentationFormatType">
                                  ODP - Open Document Portable
                              </option>
                              <option value="ods" style="display:none;" class="spreadsheetFormatType">
                                  ODS - Open Document Spreadsheet
                              </option>
                          </select>
                      </div>
                  </div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-dismiss="modal">' . get_lang('Close') . '</button>
            </div>
        </div>
    </div>';

// Footer
Display::display_footer();
