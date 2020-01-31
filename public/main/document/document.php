<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CDocument;
use ChamiloSession as Session;

/**
 * Homepage script for the documents tool.
 *
 * This script allows the user to manage files and directories.
 * The user can : - navigate through files and directories.
 *                 - upload a file
 *                 - delete, copy a file or a directory
 *                 - edit properties & content (name, comments, html content)
 */
//require_once __DIR__.'/../inc/global.inc.php';

$courseCode = isset($_GET['cid']) ? (int) $_GET['cid'] : 0;
$sessionId = isset($_GET['sid']) ? (int) $_GET['sid'] : 0;

$url = "../../../resources/document/files?cid=$courseCode&sid=$sessionId";
header("Location: $url");
exit;

$allowDownloadDocumentsByApiKey = 'true' === api_get_setting('allow_download_documents_by_api_key');
$current_course_tool = TOOL_DOCUMENT;
$this_section = SECTION_COURSES;
$to_user_id = null;
$parent_id = null;
$lib_path = api_get_path(LIBRARY_PATH);
$actionsRight = '';
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$em = Database::getManager();
$repo = Container::getDocumentRepository();
$allowUseTool = false;

if ($allowDownloadDocumentsByApiKey) {
    try {
        if ('download' !== $action) {
            throw new Exception(get_lang('Select an action'));
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
$tempRealPath = Session::read('temp_realpath_image');
if (!empty($tempRealPath) &&
    file_exists($tempRealPath)
) {
    unlink($tempRealPath);
}
$_user = api_get_user_info();
$courseInfo = api_get_course_info();
$courseId = $courseInfo['real_id'];
$course_dir = $courseInfo['directory'].'/document';
$usePpt2lp = 'true' == api_get_setting('service_ppt2lp', 'active');
$currentUrl = api_get_self().'?'.api_get_cidreq();

// I'm in the certification module?
$is_certificate_mode = false;
if (isset($_GET['curdirpath'])) {
    $is_certificate_mode = DocumentManager::is_certificate_mode($_GET['curdirpath']);
}
if (isset($_REQUEST['certificate']) && 'true' == $_REQUEST['certificate']) {
    $is_certificate_mode = true;
}

// Removing sessions
Session::erase('draw_dir');
Session::erase('paint_dir');
Session::erase('temp_audio_nanogong');

$plugin = new AppPlugin();
$pluginList = $plugin->getInstalledPlugins();
$capturePluginInstalled = in_array('jcapture', $pluginList);

if ($capturePluginInstalled) {
    $jcapturePath = api_get_path(WEB_PLUGIN_PATH).'jcapture/plugin_applet.php';
    $htmlHeadXtra[]
        = '<script>
        $(function() {
            $("#jcapture").click(function(){
                $("#appletplace").load("'.$jcapturePath.'");
            });
        });
        </script>
    ';
}

$htmlHeadXtra[]
    = '<script>
        $(function () {
            $(".select").click(function (e) {
                e.preventDefault();
                window.opener.CKEDITOR.tools.callFunction(1, $(this).attr("href"));
                window.close();
            });
        });
        </script>
    ';

if (empty($courseInfo)) {
    api_not_allowed(true);
}

// Create directory certificates.
DocumentManager::create_directory_certificate_in_course($courseInfo);

// Used for avoiding double-click.
$dbl_click_id = 0;
$selectcat = isset($_GET['selectcat']) ? Security::remove_XSS($_GET['selectcat']) : null;
$moveTo = isset($_POST['move_to']) ? Security::remove_XSS($_POST['move_to']) : null;
$moveFile = isset($_POST['move_file']) ? (int) $_POST['move_file'] : 0;

$certificateLink = '';
if ($is_certificate_mode) {
    $certificateLink = '&certificate=true&selectcat='.$selectcat;
}

/* Constants and variables */
$userId = api_get_user_id();
$userInfo = api_get_user_info();
$sessionId = api_get_session_id();
$course_code = api_get_course_id();
$groupId = api_get_group_id();
$isAllowedToEdit = api_is_allowed_to_edit(null, true) || api_is_platform_admin();
$groupMemberWithUploadRights = false;

// If the group id is set, we show them group documents
$group_properties = [];
$group_properties['directory'] = null;

// For sessions we should check the parameters of visibility
if (0 != api_get_session_id()) {
    $groupMemberWithUploadRights = $groupMemberWithUploadRights && api_is_allowed_to_session_edit(false, true);
}

// Get group info
$groupIid = 0;
$groupMemberWithEditRights = false;
// Setting group variables.
if (!empty($groupId)) {
    $group_properties = GroupManager::get_group_properties($groupId);
    $groupIid = isset($group_properties['iid']) ? $group_properties['iid'] : 0;
    $groupMemberWithEditRights = GroupManager::allowUploadEditDocument(
        $userId,
        $courseId,
        $group_properties,
        null
    );

    // Let's assume the user cannot upload files for the group
    $groupMemberWithUploadRights = false;
    if (2 == $group_properties['doc_state']) {
        // Documents are private
        if ($isAllowedToEdit || GroupManager::is_user_in_group($userId, $group_properties)) {
            // Only courseadmin or group members (members + tutors) allowed
            $interbreadcrumb[] = [
                'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq(),
                'name' => get_lang('Groups'),
            ];
            $interbreadcrumb[] = [
                'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq(),
                'name' => get_lang('Group area').' '.$group_properties['name'],
            ];
            //they are allowed to upload
            $groupMemberWithUploadRights = true;
        } else {
            $groupId = 0;
        }
    } elseif (1 == $group_properties['doc_state']) {
        // Documents are public
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq(),
            'name' => get_lang('Groups'),
        ];
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq(),
            'name' => get_lang('Group area').' '.$group_properties['name'],
        ];

        // Allowed to upload?
        if ($isAllowedToEdit ||
            GroupManager::is_subscribed($userId, $group_properties) ||
            GroupManager::is_tutor_of_group($userId, $group_properties, $courseId)
        ) {
            // Only course admin or group members can upload
            $groupMemberWithUploadRights = true;
        }
    }

    // Group mode
    if (!GroupManager::allowUploadEditDocument($userId, $courseId, $group_properties)) {
        $groupMemberWithUploadRights = false;
    }
    Session::write('group_member_with_upload_rights', $groupMemberWithUploadRights);
} else {
    Session::write('group_member_with_upload_rights', false);
}

// Detail.
$document_id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : null;
$documentIdToEdit = isset($_REQUEST['document_id']) ? (int) $_REQUEST['document_id'] : 0;

$currentUrl = api_get_self().'?'.api_get_cidreq().'&id='.$document_id;
$curdirpath = isset($_GET['curdirpath']) ? Security::remove_XSS($_GET['curdirpath']) : null;

switch ($action) {
    case 'set_visible':
    case 'set_invisible':
        if (!$isAllowedToEdit) {
            if (api_is_coach()) {
                if (!DocumentManager::is_visible_by_id($documentIdToEdit, $courseInfo, $sessionId, api_get_user_id())) {
                    api_not_allowed(true);
                }
            }
            if (DocumentManager::check_readonly($courseInfo, api_get_user_id(), $documentIdToEdit)) {
                api_not_allowed(true);
            }
        }

        /** @var CDocument $document */
        $document = $repo->find($documentIdToEdit);

        // Make visible or invisible?
        if ('set_visible' === $action) {
            $repo->setVisibilityPublished($document);
        } else {
            $repo->setVisibilityDraft($document);
        }

        Display::addFlash(Display::return_message(get_lang('The visibility has been changed.'), 'confirmation'));
        header('Location: '.$currentUrl);
        exit;
        break;
    case 'delete_item':
        if ($isAllowedToEdit ||
            $groupMemberWithUploadRights ||
            DocumentManager::isBasicCourseFolder($curdirpath, $sessionId) ||
            DocumentManager::is_my_shared_folder(api_get_user_id(), $curdirpath, $sessionId) ||
            DocumentManager::is_my_shared_folder(api_get_user_id(), $moveTo, $sessionId)
        ) {
            if (isset($_GET['deleteid'])) {
                if (!$isAllowedToEdit) {
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

                    if (DocumentManager::check_readonly($courseInfo, api_get_user_id(), $_GET['deleteid'], true)) {
                        api_not_allowed();
                    }
                }

                $documentInfo = DocumentManager::get_document_data_by_id(
                    $_GET['deleteid'],
                    $courseInfo['code'],
                    false,
                    $sessionId
                );

                GroupManager::allowUploadEditDocument(
                    $userId,
                    $courseId,
                    $group_properties,
                    $documentInfo,
                    true
                );

                // Check whether the document is in the database.
                if (!empty($documentInfo)) {
                    if ('link' != $documentInfo['filetype']) {
                        $deleteDocument = DocumentManager::delete_document(
                            $courseInfo,
                            null,
                            '',
                            $sessionId,
                            $_GET['deleteid'],
                            $groupIid
                        );
                        if ($deleteDocument) {
                            $certificateId = isset($_GET['delete_certificate_id']) ? $_GET['delete_certificate_id'] : null;
                            DocumentManager::remove_attach_certificate(
                                api_get_course_int_id(),
                                $certificateId
                            );
                            Display::addFlash(
                                Display::return_message(
                                    get_lang('Document deleted').': '.$documentInfo['title'],
                                    'success'
                                )
                            );
                        } else {
                            Display::addFlash(Display::return_message(get_lang('Error during the delete of document'), 'warning'));
                        }
                    } else {
                        // Cloud Links
                        $deleteDocument = DocumentManager::deleteCloudLink($courseInfo, $_GET['deleteid']);
                        if ($deleteDocument) {
                            Display::addFlash(Display::return_message(
                                get_lang('Cloud link deleted').': '.$documentInfo['title'],
                                'success'
                            ));
                        } else {
                            Display::addFlash(Display::return_message(
                                get_lang('Error deleting the cloud link.').': '.$documentInfo['title'],
                                'error'
                            ));
                        }
                    }
                } else {
                    Display::addFlash(Display::return_message(get_lang('The file was not found'), 'warning'));
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

        if (0 != $sessionId && !$document_data) {
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

        $publicPath = api_get_path(WEB_PUBLIC_PATH);
        $courseCode = api_get_course_id();
        $path = $document_data['path'];
        $url = $publicPath."courses/$courseCode/document$path&id_session=$sessionId";
        header("Location: $url");
        exit;
        break;
    case 'downloadfolder':
        if ('true' == api_get_setting('students_download_folders') || $isAllowedToEdit) {
            // Get the document data from the ID
            $document_data = DocumentManager::get_document_data_by_id(
                $document_id,
                api_get_course_id(),
                false,
                $sessionId
            );

            if (0 != $sessionId && !$document_data) {
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
                if (DocumentManager::is_my_shared_folder(api_get_user_id(), $document_data['path'], $sessionId) ||
                    $isAllowedToEdit
                ) {
                    require 'downloadfolder.inc.php';
                }
            } else {
                require 'downloadfolder.inc.php';
            }
            exit;
        }
        break;
    case 'export_to_pdf':
        // @todo disabled for v2
        break;
        if ('true' == api_get_setting('students_export2pdf') ||
            $isAllowedToEdit || api_is_platform_admin()
        ) {
            $orientation = api_get_configuration_value('document_pdf_orientation');
            $orientation = in_array($orientation, ['landscape', 'portrait']) ? $orientation : 'landscape';

            $showHeaderAndFooter = true;
            if ($is_certificate_mode) {
                $certificateOrientation = api_get_configuration_value('certificate_pdf_orientation');
                $orientation = in_array($certificateOrientation, ['landscape', 'portrait'])
                    ? $certificateOrientation
                    : 'landscape';
                $showHeaderAndFooter = 'true' === !api_get_configuration_value('certificate.hide_header_footer');
            }

            DocumentManager::export_to_pdf($document_id, $course_code, $orientation, $showHeaderAndFooter);
        }
        break;
    case 'copytomyfiles':
        // @todo disabled for v2
        break;
        // Copy a file to general my files user's
        if ('true' == api_get_setting('allow_my_files') &&
            'true' == api_get_setting('users_copy_files') &&
            0 != api_get_user_id() &&
            !api_is_anonymous()
        ) {
            // Get the document data from the ID
            $document_info = DocumentManager::get_document_data_by_id(
                $document_id,
                api_get_course_id(),
                true,
                $sessionId
            );
            if (0 != $sessionId && !$document_info) {
                /* If there is a session defined and asking for the document
                  from the session didn't work, try it from the course
                (out of a session context)*/
                $document_info = DocumentManager::get_document_data_by_id(
                    $document_id,
                    api_get_course_id(),
                    0
                );
            }

            GroupManager::allowUploadEditDocument(
                $userId,
                $courseId,
                $group_properties,
                $document_info,
                true
            );

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
                get_lang('See file'),
                api_get_path(WEB_CODE_PATH).'social/myfiles.php?'
                .api_get_cidreq_params($cidReq, $id_session, $gidReq).
                '&parent_id='.$parent_id
            );

            if ('false' === api_get_setting('allow_my_files')) {
                $file_link = '';
            }

            if (file_exists($copyfile)) {
                $message = get_lang('There are a file with the same name in your private user file area. Do you want replace it?').'</p><p>';
                $message .= '<a class = "btn btn-default" '
                    .'href="'.api_get_self().'?'.api_get_cidreq().'&amp;id='
                    .$parent_id.'">'
                    .get_lang("No")
                    .'</a>'
                    .'&nbsp;&nbsp;|&nbsp;&nbsp;'
                    .'<a class = "btn btn-default" href="'.api_get_self().'?'
                    .api_get_cidreq().'&amp;action=copytomyfiles&amp;id='
                    .$document_info['id']
                    .'&amp;copy=yes">'
                    .get_lang('Yes')
                    .'</a></p>';
                if (!isset($_GET['copy'])) {
                    Display::addFlash(Display::return_message($message, 'warning', false));
                }
                if (isset($_GET['copy']) && 'yes' === $_GET['copy']) {
                    if (!copy($file, $copyfile)) {
                        Display::addFlash(Display::return_message(get_lang('Copy failed'), 'error'));
                    } else {
                        Display::addFlash(Display::return_message(
                            get_lang('File replaced').' '.$file_link,
                            'confirmation',
                            false
                        ));
                    }
                }
            } else {
                if (!copy($file, $copyfile)) {
                    Display::addFlash(Display::return_message(get_lang('Copy failed'), 'error'));
                } else {
                    Display::addFlash(
                        Display::return_message(get_lang('The copy has been made').' '.$file_link, 'confirmation', false)
                    );
                }
            }
        }
        break;
    case 'convertToPdf':
        // @todo disabled for v2
        break;
        // PDF format as target by default
        $formatTarget = $_REQUEST['formatTarget']
            ? strtolower(Security::remove_XSS($_REQUEST['formatTarget']))
            : 'pdf';
        $formatType = $_REQUEST['formatType']
            ? strtolower(Security::remove_XSS($_REQUEST['formatType']))
            : 'text';
        // Get the document data from the ID
        $document_info = DocumentManager::get_document_data_by_id(
            $document_id,
            api_get_course_id(),
            true,
            $session_id
        );
        $file = $sys_course_path.$courseInfo['directory'].'/document'.$document_info['path'];
        $fileInfo = pathinfo($file);
        if ($fileInfo['extension'] == $formatTarget) {
            Display::addFlash(Display::return_message(
                get_lang('Conversion to same file format. Please choose another.'),
                'warning'
            ));
        } elseif (
            !(in_array($fileInfo['extension'], DocumentManager::getJodconverterExtensionList('from', $formatType))) ||
            !(in_array($formatTarget, DocumentManager::getJodconverterExtensionList('to', $formatType)))
        ) {
            Display::addFlash(Display::return_message(
                get_lang('File format not supported'),
                'warning'
            ));
        } else {
            $convertedFile = $fileInfo['dirname'].
                DIRECTORY_SEPARATOR.$fileInfo['filename'].'_from_'.$fileInfo['extension'].'.'.$formatTarget;
            $convertedTitle = $document_info['title'];
            $obj = new OpenofficePresentation(true);
            if (file_exists($convertedFile)) {
                Display::addFlash(Display::return_message(
                    get_lang('The operation is impossible, a file with this name already exists.'),
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
                        get_lang('Copy failed'),
                        'error'
                    ));
                } else {
                    $cidReq = Security::remove_XSS($_GET['cidReq']);
                    $id_session = api_get_session_id();
                    $gidReq = Security::remove_XSS($_GET['gidReq']);
                    $file_link = Display::url(
                        get_lang('See file'),
                        api_get_path(WEB_CODE_PATH)
                        .'document/showinframes.php?'
                        .api_get_cidreq_params($cidReq, $id_session, $gidReq)
                        .'&id='.current($result)
                    );
                    Display::addFlash(Display::return_message(
                        get_lang('The copy has been made').' '.$file_link,
                        'confirmation',
                        false
                    ));
                }
            }
        }
        break;
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

    if (0 != $sessionId && !$document_data) {
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
        // Hack in order to clean the document id in case of false positive from links
        if ('link' == $document_data['filetype']) {
            $document_id = null;
        }

        $visibility = DocumentManager::check_visibility_tree(
            $document_id,
            api_get_course_info(),
            $sessionId,
            api_get_user_id(),
            $groupIid
        );

        if (!empty($document_data['filetype']) &&
            ('file' == $document_data['filetype'] || 'link' == $document_data['filetype'])
        ) {
            if ($visibility && api_is_allowed_to_session_edit()) {
                $url = api_get_path(WEB_COURSE_PATH).$courseInfo['path'].'/document'.$document_data['path'].'?'.api_get_cidreq();
                header("Location: $url");
                exit;
            }
        } else {
            if (!$visibility && !$isAllowedToEdit) {
                api_not_allowed(true);
            }
        }
        $_GET['curdirpath'] = $document_data['path'];
    }

    // What's the current path?
    // We will verify this a bit further down
    if (isset($_GET['curdirpath']) && '' != $_GET['curdirpath']) {
        $curdirpath = Security::remove_XSS($_GET['curdirpath']);
    } elseif (isset($_POST['curdirpath']) && '' != $_POST['curdirpath']) {
        $curdirpath = Security::remove_XSS($_POST['curdirpath']);
    } else {
        $curdirpath = '/';
    }
    $curdirpathurl = urlencode($curdirpath);
} else {
    // What's the current path?
    // We will verify this a bit further down
    if (isset($_GET['curdirpath']) && '' != $_GET['curdirpath']) {
        $curdirpath = Security::remove_XSS($_GET['curdirpath']);
    } elseif (isset($_POST['curdirpath']) && '' != $_POST['curdirpath']) {
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

if (isset($document_data) && '/certificates' == $document_data['path']) {
    $is_certificate_mode = true;
}

if (!$parent_id) {
    $testParentId = 0;
    // Get parent id from current path
    if (!empty($document_data['path'])) {
        $testParentId = DocumentManager::get_document_id(
            api_get_course_info(),
            dirname($document_data['path']),
            0
        );
    }

    $parent_id = 0;
    if (!empty($testParentId)) {
        $parent_id = $testParentId;
    }
}

$current_folder_id = $document_id;

// Show preview
if (isset($_GET['curdirpath']) && '/certificates' == $_GET['curdirpath'] &&
    isset($_GET['set_preview']) &&
    $_GET['set_preview'] == strval(intval($_GET['set_preview']))
) {
    if (isset($_GET['set_preview'])) {
        // Generate document HTML
        $content_html = DocumentManager::replace_user_info_into_html(
            api_get_user_id(),
            api_get_course_info(),
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
        $text = $certificate->parseCertificateVariables($content_html['variables']);
        $result = $certificate->generateQRImage($text, $qr_code_filename);

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
            SYS_CODE_PATH.'img/',
            api_get_path(WEB_IMG_PATH),
            $new_content_html
        );

        // Remove media=screen to be available when printing a document
        $new_content_html = str_replace(
            ' media="screen"',
            '',
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

$htmlHeadXtra[] = '<script>
function confirmation (name) {
    if (confirm(" '.addslashes(get_lang('Are you sure to delete')).' "+ name + " ?")) {
        return true;
    } else {
        return false;
    }
}

$(function() {
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
        window.location.href = "'
            .api_get_self().'?'.api_get_cidreq()
            .'&curdirpath='.$curdirpath
            .'&action=convertToPdf&formatTarget='
            .'" + formatTarget + "&id=" + id + "&'
            .api_get_cidreq().'&formatType=" + format;
    });
    $("#convertModal").on("hidden", function(){
        $("." + format + "FormatType").hide();
    });
}
</script>';

// If they are looking at group documents they can't see the root
if (0 != $groupId && '/' == $curdirpath) {
    $curdirpath = $group_properties['directory'];
    $curdirpathurl = urlencode($group_properties['directory']);
}

// Check visibility of the current dir path. Don't show anything if not allowed
//@todo check this validation for coaches
//if (!$isAllowedToEdit || api_is_coach()) { before

if (!$isAllowedToEdit && api_is_coach()) {
    if ('/' != $curdirpath &&
        !(DocumentManager::is_visible($curdirpath, $courseInfo, $sessionId, 'folder'))
    ) {
        api_not_allowed(true);
    }
}

/* Create shared folders */
DocumentManager::createUserSharedFolder(api_get_user_id(), $courseInfo, $sessionId);

if ($is_certificate_mode) {
    $interbreadcrumb[] = [
        'url' => '../gradebook/index.php?'.api_get_cidreq(),
        'name' => get_lang('Assessments'),
    ];
} else {
    if ((isset($_GET['id']) && 0 != $_GET['id']) || isset($_GET['curdirpath']) || isset($_GET['createdir'])) {
        $interbreadcrumb[] = [
            'url' => 'document.php?'.api_get_cidreq(),
            'name' => get_lang('Documents'),
        ];
    } else {
        $interbreadcrumb[] = [
            'url' => '#',
            'name' => get_lang('Documents'),
        ];
    }
}

// Interbreadcrumb for the current directory root path
if (empty($document_data['parents'])) {
    if (isset($_GET['createdir'])) {
        $interbreadcrumb[] = [
            'url' => $document_data['document_url'],
            'name' => $document_data['title'],
        ];
    } else {
        // Hack in order to not add the document to the breadcrumb in case it is a link
        if ('link' != $document_data['filetype']) {
            $interbreadcrumb[] = [
                'url' => '#',
                'name' => $document_data['title'],
            ];
        }
    }
} else {
    $counter = 0;
    foreach ($document_data['parents'] as $document_sub_data) {
        //fixing double group folder in breadcrumb
        if ($groupId) {
            if (0 == $counter) {
                $counter++;
                continue;
            }
        }
        if (!isset($_GET['createdir']) && $document_sub_data['id'] == $document_data['id']) {
            $document_sub_data['document_url'] = '#';
        }
        $interbreadcrumb[] = [
            'url' => $document_sub_data['document_url'],
            'name' => $document_sub_data['title'],
        ];
        $counter++;
    }
}

if (isset($_GET['createdir'])) {
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Create folder')];
}

$documentAndFolders = DocumentManager::getAllDocumentData(
    $courseInfo,
    $curdirpath,
    $groupIid,
    null,
    $isAllowedToEdit || $groupMemberWithUploadRights,
    false
);

$count = 1;
$jquery = null;

if (!empty($documentAndFolders)) {
    foreach ($documentAndFolders as $file) {
        if ('file' == $file['filetype']) {
            $path_info = pathinfo($file['path']);
            $extension = '';
            if (!empty($path_info['extension'])) {
                $extension = strtolower($path_info['extension']);
            }

            //@todo use a js loop to auto generate this code
            if (in_array($extension, ['ogg', 'mp3', 'wav'])) {
                // Get the document data from the ID
                $document_data = DocumentManager::get_document_data_by_id(
                    $file['id'],
                    api_get_course_id(),
                    false,
                    $sessionId
                );
                if (0 != $sessionId && !$document_data) {
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

                if ('ogg' == $extension) {
                    $extension = 'oga';
                }

                $params = [
                    'url' => $document_data['direct_url'],
                    'extension' => $extension,
                    'count' => $count,
                ];
                $jquery .= DocumentManager::generateAudioJavascript($params);
                $count++;
            }
        }
    }
}

$htmlHeadXtra[] = '
    <script>
        $(function() {
            //Experimental changes to preview mp3, ogg files'
            .$jquery.'
        });
    </script>
';

// Lib for event log, stats & tracking & record of the access
Event::event_access_tool(TOOL_DOCUMENT);

/* DISPLAY */
if (0 != $groupId) { // Add group name after for group documents
    $add_group_to_title = ' ('.$group_properties['name'].')';
}
$moveForm = '';
/* MOVE FILE OR DIRECTORY */
//Only teacher and all users into their group and each user into his/her shared folder
if ($isAllowedToEdit || $groupMemberWithUploadRights ||
    DocumentManager::is_my_shared_folder(api_get_user_id(), $curdirpath, $sessionId) ||
    DocumentManager::is_my_shared_folder(api_get_user_id(), $moveTo, $sessionId)
) {
    if (isset($_GET['move']) && '' != $_GET['move']) {
        $my_get_move = intval($_REQUEST['move']);

        if (api_is_coach()) {
            if (!DocumentManager::is_visible_by_id($my_get_move, $courseInfo, $sessionId, api_get_user_id())) {
                api_not_allowed(true);
            }
        }

        if (!$isAllowedToEdit) {
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

        GroupManager::allowUploadEditDocument(
            $userId,
            $courseId,
            $group_properties,
            $document_to_move,
            true
        );

        $move_path = $document_to_move['path'];
        if (!empty($document_to_move)) {
            $folders = DocumentManager::get_all_document_folders(
                $courseInfo,
                $groupIid,
                $isAllowedToEdit || $groupMemberWithUploadRights,
                false,
                $curdirpath
            );
            $moveForm .= '<legend>'.get_lang('Move').': '.$document_to_move['title'].'</legend>';

            // filter if is my shared folder. TODO: move this code to build_move_to_selector function
            if (DocumentManager::is_my_shared_folder(api_get_user_id(), $curdirpath, $sessionId) &&
                !$isAllowedToEdit
            ) {
                //only main user shared folder
                $main_user_shared_folder_main = '/shared_folder/sf_user_'.api_get_user_id();
                $main_user_shared_folder_sub = '/shared_folder\/sf_user_'.api_get_user_id().'\//'; //all subfolders
                $user_shared_folders = [];

                foreach ($folders as $fold) {
                    if ($main_user_shared_folder_main == $fold ||
                        preg_match($main_user_shared_folder_sub, $fold)
                    ) {
                        $user_shared_folders[] = $fold;
                    }
                }
                $moveForm .= DocumentManager::build_move_to_selector(
                    $user_shared_folders,
                    $move_path,
                    $my_get_move,
                    $group_properties['directory']
                );
            } else {
                $moveForm .= DocumentManager::build_move_to_selector(
                    $folders,
                    $move_path,
                    $my_get_move,
                    $group_properties['directory']
                );
            }
        }
    }

    if (!empty($moveTo) && isset($moveFile)) {
        if (!$isAllowedToEdit) {
            if (DocumentManager::check_readonly($courseInfo, api_get_user_id(), $moveFile)) {
                api_not_allowed(true);
            }
        }

        if (api_is_coach()) {
            if (!DocumentManager::is_visible_by_id($moveFile, $courseInfo, $sessionId, api_get_user_id())) {
                api_not_allowed(true);
            }
        }

        // Get the document data from the ID
        $document_to_move = DocumentManager::get_document_data_by_id(
            $moveFile,
            api_get_course_id(),
            false,
            $sessionId
        );

        GroupManager::allowUploadEditDocument(
            $userId,
            $courseId,
            $group_properties,
            $document_to_move,
            true
        );

        // Security fix: make sure they can't move files that are not in the document table
        if (!empty($document_to_move)) {
            if ('link' === $document_to_move['filetype']) {
                if (!DocumentManager::cloudLinkExists($_course, $moveTo, $document_to_move['comment'])) {
                    DocumentManager::updateDbInfo(
                        'update',
                        $document_to_move['path'],
                        $moveTo.'/'.basename($document_to_move['path'])
                    );

                    Display::addFlash(
                        Display::return_message(
                            get_lang('Cloud link moved'),
                            'success'
                        )
                    );
                } else {
                    Display::addFlash(
                        Display::return_message(
                            get_lang('This URL already exists'),
                            'error'
                        )
                    );
                }
                // Set the current path
                $curdirpath = $moveTo;
                $curdirpathurl = urlencode($moveTo);
            } else {
                if ('/' === $moveTo) {
                    // Move to course root
                    $newParent = api_get_course_entity();
                } else {
                    // Move to folder
                    $moveTo = DocumentManager::get_document_id(
                        $courseInfo,
                        $moveTo
                    );
                    /** @var CDocument $newParent */
                    $newParent = $repo->find($moveTo);
                }

                /** @var CDocument $document */
                $document = $repo->find($document_to_move['iid']);

                if ($moveTo && $document && $newParent) {
                    DocumentManager::updateDbInfo(
                        'update',
                        $document_to_move['path'],
                        $moveTo.'/'.basename($document_to_move['path'])
                    );

                    $document->getResourceNode()->setParent($newParent->getResourceNode());
                    $em->persist($document);
                    $em->flush();

                    Display::addFlash(Display::return_message(get_lang('Element moved'), 'confirmation'));
                }
            }
        } else {
            Display::addFlash(Display::return_message(get_lang('Operation impossible'), 'error'));
        }
    }
}

/* DELETE FILE OR DIRECTORY */
//Only teacher and all users into their group
if ($isAllowedToEdit ||
    $groupMemberWithUploadRights ||
    DocumentManager::is_my_shared_folder(api_get_user_id(), $curdirpath, $sessionId)
) {
    if (isset($_POST['action']) && isset($_POST['ids'])) {
        $files = $_POST['ids'];
        $readonlyAlreadyChecked = false;
        $messages = '';
        $items = [
            '/audio',
            '/flash',
            '/images',
            '/shared_folder',
            '/video',
            '/chat_files',
            '/certificates',
        ];
        foreach ($files as $documentId) {
            $data = DocumentManager::get_document_data_by_id($documentId, $courseInfo['code']);
            /** @var CDocument $document */
            $document = $repo->find($documentId);

            if (in_array($data['path'], $items)) {
                // exclude system directories (do not allow deletion)
                continue;
            } else {
                switch ($_POST['action']) {
                    case 'set_invisible':
                        $repo->setVisibilityPublished($document);
                        break;
                    case 'set_visible':
                        $repo->setVisibilityDraft($document);
                        $messages .= Display::return_message(
                            get_lang('The visibility has been changed.').': '.$data['title'],
                            'confirmation'
                        );
                        break;
                    case 'delete':
                        // Check all documents scheduled for deletion
                        // If one of them is read-only, abandon deletion
                        // Note: this is only executed once
                        if (!$readonlyAlreadyChecked) {
                            foreach ($files as $id) {
                                if (!$isAllowedToEdit) {
                                    if (DocumentManager::check_readonly(
                                        $courseInfo,
                                        api_get_user_id(),
                                        $id,
                                        false,
                                        $sessionId
                                    )) {
                                        $messages .= Display::return_message(
                                            get_lang('Cannot delete files that are configured in read-only mode.'),
                                            'error'
                                        );
                                        break 2;
                                    }
                                }
                            }
                            $readonlyAlreadyChecked = true;
                        }
                        if ('link' != $data['filetype']) {
                            // Files and folders
                            $deleteDocument = DocumentManager::delete_document(
                                $courseInfo,
                                null,
                                '',
                                $sessionId,
                                $documentId,
                                $groupIid
                            );
                            if (!empty($deleteDocument)) {
                                $messages .= Display::return_message(
                                    get_lang('Document deleted').': '.$data['title'],
                                    'confirmation'
                                );
                            }
                        } else {
                            // Cloud Links
                            if (DocumentManager::deleteCloudLink($_course, $documentId)) {
                                $messages .= Display::return_message(
                                    get_lang('Cloud link deleted'),
                                    'confirmation'
                                );
                            } else {
                                $messages .= Display::return_message(
                                    get_lang('Error deleting the cloud link.'),
                                    'error'
                                );
                            }
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

$dirForm = '';
/* CREATE DIRECTORY */
//Only teacher and all users into their group and any user into his/her shared folder
if ($isAllowedToEdit ||
    $groupMemberWithUploadRights ||
    DocumentManager::is_my_shared_folder(api_get_user_id(), $curdirpath, $sessionId)
) {
    // Create directory with $_POST data
    if (isset($_POST['create_dir']) && '' != $_POST['dirname']) {
        // Needed for directory creation
        $post_dir_name = $_POST['dirname'];
        if ('../' == $post_dir_name || '.' == $post_dir_name || '..' == $post_dir_name) {
            $message = Display::return_message(get_lang('CannotCreate folder'), 'error');
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
                if (0 != $sessionId && !$document_data) {
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
            $added_slash = '/' == $curdirpath ? '' : '/';
            $dir_name = $curdirpath.$added_slash.api_replace_dangerous_char($post_dir_name);
            $dir_name = disable_dangerous_file($dir_name);
            $visibility = empty($groupId) ? null : 1;
            $newFolderData = create_unexisting_directory(
                $courseInfo,
                api_get_user_id(),
                $sessionId,
                api_get_group_id(),
                $to_user_id,
                '',
                $dir_name,
                $post_dir_name,
                $visibility,
                false,
                true,
                $document_data
            );

            if (!empty($newFolderData)) {
                $message = Display::return_message(
                    get_lang('Folder created').' '.$newFolderData->getTitle(),
                    'confirmation'
                );
            } else {
                $message = Display::return_message(
                    get_lang('CannotCreate folder'),
                    'error'
                );
            }
        }
        Display::addFlash($message);

        header('Location: '.$currentUrl);
        exit;
    }

    // Show them the form for the directory name
    if (isset($_GET['createdir'])) {
        $dirForm = DocumentManager::create_dir_form($document_id);
    }
}

$templateForm = '';

/* TEMPLATE ACTION */
//Only teacher and all users into their group
if ($isAllowedToEdit ||
    $groupMemberWithUploadRights ||
    DocumentManager::is_my_shared_folder(api_get_user_id(), $curdirpath, $sessionId)
) {
    if (isset($_GET['add_as_template']) && !isset($_POST['create_template'])) {
        $document_id_for_template = intval($_GET['add_as_template']);
        // Create the form that asks for the directory name
        $templateForm .= '
            <form name="set_document_as_new_template" class="form-horizontal" enctype="multipart/form-data"
                action="'.api_get_self().'?add_as_template='.$document_id_for_template.'" method="post">
                <fieldset>
                    <legend>'.get_lang('Add as a template').'</legend>
                    <div class="form-group">
                        <label for="template_title" class="col-sm-2 control-label">'.get_lang('Template name').'</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="template_title" name="template_title">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="template_image" class="col-sm-2 control-label">'.get_lang("Template's icon").'</label>
                        <div class="col-sm-10">
                            <input type="file" name="template_image" id="template_image">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-10">
                            <button type="submit" name="create_template" class="btn btn-primary">'
                                .get_lang('Create template').'
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="curdirpath" value="'.$curdirpath.'" />
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
                $new_file_name = $courseInfo['code'].'-'
                    .add_ext_on_mime(
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

        DocumentManager::setDocumentAsTemplate(
            $title,
            '',
            $document_id_for_template,
            api_get_course_int_id(),
            $user_id,
            $new_file_name
        );
        Display::addFlash(
            Display::return_message(get_lang('Document set as a new template'), 'confirmation')
        );
    }

    if (isset($_GET['remove_as_template'])) {
        $user_id = api_get_user_id();
        DocumentManager::unsetDocumentAsTemplate(
            $_GET['remove_as_template'],
            api_get_course_int_id(),
            $user_id
        );

        Display::addFlash(
            Display::return_message(get_lang('Document unset as template'), 'confirmation')
        );
    }
}

// END ACTION MENU
// Attach certificate in the gradebook
if (isset($_GET['curdirpath']) &&
    false !== strpos($_GET['curdirpath'], '/certificates') &&
    isset($_GET['set_certificate']) &&
    $_GET['set_certificate'] == strval(intval($_GET['set_certificate']))
) {
    if (isset($_GET['cidReq'])) {
        $course_id = Security::remove_XSS($_GET['cidReq']); // course code
        $document_id = Security::remove_XSS($_GET['set_certificate']); // document id
        $courseInfoTemp = api_get_course_info($course_id);
        DocumentManager::attach_gradebook_certificate($courseInfoTemp['real_id'], $document_id);
        $message = Display::return_message(get_lang('Certificate set to default'), 'normal');
        Display::addFlash(
            $message
        );
    }
}

/* GET ALL DOCUMENT DATA FOR CURDIRPATH */
if (isset($_GET['keyword']) && !empty($_GET['keyword'])) {
    $documentAndFolders = DocumentManager::getAllDocumentData(
        $courseInfo,
        $curdirpath,
        $groupIid,
        null,
        $isAllowedToEdit || $groupMemberWithUploadRights,
        true
    );
} else {
    $documentAndFolders = DocumentManager::getAllDocumentData(
        $courseInfo,
        $curdirpath,
        $groupIid,
        null,
        $isAllowedToEdit || $groupMemberWithUploadRights,
        false
    );
}

if (0 != $groupId) {
    $userAccess = GroupManager::user_has_access(
        api_get_user_id(),
        $groupIid,
        GroupManager::GROUP_TOOL_DOCUMENTS
    );
    if ($userAccess) {
        $folders = DocumentManager::get_all_document_folders(
            $courseInfo,
            $groupIid,
            $isAllowedToEdit || $groupMemberWithUploadRights,
            false,
            $curdirpath
        );
    }
} else {
    $folders = DocumentManager::get_all_document_folders(
        $courseInfo,
        0,
        $isAllowedToEdit || $groupMemberWithUploadRights,
        false,
        $curdirpath
    );
}

if (!isset($folders) || false === $folders) {
    $folders = [];
}
$btngroup = ['class' => 'btn btn-default'];
/* GO TO PARENT DIRECTORY */
$actionsLeft = '';
if ('/' != $curdirpath &&
    $curdirpath != $group_properties['directory'] &&
    !$is_certificate_mode
) {
    $actionsLeft = '<a href="'.api_get_self().'?'.api_get_cidreq().'&id='.$parent_id.$certificateLink.'">';
    $actionsLeft .= Display::return_icon('folder_up.png', get_lang('Up'), '', ICON_SIZE_MEDIUM);
    $actionsLeft .= '</a>';
}

if ($is_certificate_mode && '/certificates' != $curdirpath) {
    $actionsLeft .= Display::url(
        Display::return_icon('folder_up.png', get_lang('Up'), '', ICON_SIZE_MEDIUM),
        api_get_self().'?'.api_get_cidreq().'&id='.$parent_id.$certificateLink
    );
}

$column_show = [];

if ($isAllowedToEdit ||
    $groupMemberWithUploadRights ||
    DocumentManager::is_my_shared_folder(api_get_user_id(), $curdirpath, $sessionId)
) {
    // TODO:check enable more options for shared folders
    /* CREATE NEW DOCUMENT OR NEW DIRECTORY / GO TO UPLOAD / DOWNLOAD ZIPPED FOLDER */
    // Create new document
    if (!$is_certificate_mode) {
        $actionsLeft .= Display::url(
            Display::return_icon(
                'new_document.png',
                get_lang('Create a rich media page / activity'),
                '',
                ICON_SIZE_MEDIUM
            ),
            api_get_path(WEB_CODE_PATH).'document/create_document.php?'
            .api_get_cidreq().'&id='.$document_id
        );

        // Create new draw
        if ('true' == api_get_setting('enabled_support_svg')) {
            if (api_browser_support('svg')) {
                $actionsLeft .= Display::url(
                    Display::return_icon('new_draw.png', get_lang('Draw'), '', ICON_SIZE_MEDIUM),
                    api_get_path(WEB_CODE_PATH).'document/create_draw.php?'.api_get_cidreq().'&id='.$document_id
                );
            } else {
                $actionsLeft .= Display::return_icon(
                    'new_draw_na.png',
                    get_lang('Your browser does not support SVG files. To use the drawing tool you must have an advanced browser such as Firefox or Chrome'),
                    '',
                    ICON_SIZE_MEDIUM
                );
            }
        }

        // Create new paint
        if ('true' == api_get_setting('enabled_support_pixlr')) {
            $actionsLeft .= Display::url(
                Display::return_icon(
                    'new_paint.png',
                    get_lang('Photo retouching'),
                    '',
                    ICON_SIZE_MEDIUM
                ),
                api_get_path(WEB_CODE_PATH).'document/create_paint.php?'
                .api_get_cidreq().'&id='.$document_id
            );
        }

        // Record an image clip from my webcam
        if ('true' == api_get_setting('enable_webcam_clip')) {
            $actionsLeft .= Display::url(
                Display::return_icon('webcam.png', get_lang('Webcam Clip'), '', ICON_SIZE_MEDIUM),
                api_get_path(WEB_CODE_PATH).'document/webcam_clip.php?'.api_get_cidreq().'&id='.$document_id
            );
        }

        // Record audio (nanogong)
        if ('true' === api_get_setting('enable_record_audio')) {
            $actionsLeft .= Display::url(
                Display::return_icon('new_recording.png', get_lang('Record my voice'), '', ICON_SIZE_MEDIUM),
                api_get_path(WEB_CODE_PATH).'document/record_audio.php?'.api_get_cidreq().'&id='.$document_id
            );
        }

        // Create new audio from text
        if ('true' == api_get_setting('enabled_text2audio')) {
            $actionsLeft .= Display::url(
                Display::return_icon('new_sound.png', get_lang('Create audio'), '', ICON_SIZE_MEDIUM),
                api_get_path(WEB_CODE_PATH).'document/create_audio.php?'.api_get_cidreq().'&id='.$document_id
            );
        }
    }

    // Create new certificate
    if ($is_certificate_mode) {
        $actionsLeft .= Display::url(
            Display::return_icon(
                'new_certificate.png',
                get_lang('Create certificate'),
                '',
                ICON_SIZE_MEDIUM
            ),
            api_get_path(WEB_CODE_PATH).'document/create_document.php?'
                .api_get_cidreq().'&id='.$document_id.'&certificate=true&selectcat='
                .$selectcat
        );
    }
    // File upload link
    if ($is_certificate_mode) {
        $actionsLeft .= Display::url(
            Display::return_icon('upload_certificate.png', get_lang('Upload certificate'), '', ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'document/upload.php?'.api_get_cidreq()
                .'&id='.$current_folder_id.'&certificate=true'
        );
    } else {
        $actionsLeft .= Display::url(
            Display::return_icon('upload_file.png', get_lang('Upload documents'), '', ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'document/upload.php?'.api_get_cidreq().'&id='.$current_folder_id
        );
    }

    if ($capturePluginInstalled && !$is_certificate_mode) {
        $actionsLeft .= '<span id="appletplace"></span>';
        $actionsLeft .= Display::url(
            Display::return_icon('capture.png', get_lang('Capture screenshot/screencast'), '', ICON_SIZE_MEDIUM),
            '#',
            ['id' => 'jcapture']
        );
    }

    // Create directory
    if (!$is_certificate_mode) {
        $actionsLeft .= Display::url(
            Display::return_icon('new_folder.png', get_lang('Create folder'), '', ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'document/document.php?'.api_get_cidreq().'&id='.$document_id.'&createdir=1'
        );
    }

    // "Add cloud link" icon
    $fileLinkEnabled = api_get_configuration_value('enable_add_file_link');
    if ($fileLinkEnabled && !$is_certificate_mode) {
        $actionsLeft .= Display::url(
            Display::return_icon('clouddoc_new.png', get_lang('Add link to Cloud file'), '', ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'document/add_link.php?'.api_get_cidreq().'&id='.$document_id
        );
    }
}
if (!isset($_GET['keyword']) && !$is_certificate_mode) {
    /*$actionsLeft .= Display::url(
        Display::return_icon('slideshow.png', get_lang('View Slideshow'), '', ICON_SIZE_MEDIUM),
        api_get_path(WEB_CODE_PATH).'document/slideshow.php?'.api_get_cidreq().'&curdirpath='.$curdirpathurl
    );*/
}

if ($isAllowedToEdit && !$is_certificate_mode) {
    $actionsLeft .= Display::url(
        Display::return_icon('percentage.png', get_lang('Space Available'), '', ICON_SIZE_MEDIUM),
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
        [],
        FormValidator::LAYOUT_INLINE
    );
    $form->addText('keyword', '', false, ['class' => 'col-md-2']);
    $form->addHidden('cidReq', api_get_course_id());
    $form->addHidden('id_session', api_get_session_id());
    $form->addHidden('gidReq', $groupId);
    $form->addButtonSearch(get_lang('Search'));
    $actionsRight = $form->returnForm();
}
$total_size = 0;
$getSizeURL = api_get_path(WEB_AJAX_PATH).'document.ajax.php?a=get_dir_size&'.api_get_cidreq();

$sortable_data = [];
if (!empty($documentAndFolders) && (0 === $groupId || $userAccess)) {
    $sortable_data = DocumentManager::processDocumentAndFolders(
        $documentAndFolders,
        $courseInfo,
        $is_certificate_mode,
        $groupMemberWithUploadRights,
        $curdirpath
    );
}

if (!empty($documentAndFolders)) {
    // Show download zipped folder icon
    if (!$is_certificate_mode && (
            'true' == api_get_setting('students_download_folders') ||
            $isAllowedToEdit ||
            api_is_platform_admin()
        )
    ) {
        //for student does not show icon into other shared folder, and does not show into main path (root)
        if (DocumentManager::is_my_shared_folder(api_get_user_id(), $curdirpath, $sessionId) &&
            '/' != $curdirpath ||
            $isAllowedToEdit ||
            api_is_platform_admin()
        ) {
            $actionsLeft .= Display::url(
                Display::return_icon(
                    'save_pack.png',
                    get_lang('Save').' (ZIP)',
                    '',
                    ICON_SIZE_MEDIUM
                ),
                api_get_path(WEB_CODE_PATH).'document/document.php?'
                .api_get_cidreq().'&action=downloadfolder&id='.$document_id
            );
        }
    }
}

if (api_is_platform_admin() && api_get_configuration_value('document_manage_deleted_files')) {
    $actionsLeft .= Display::url(
        get_lang('Recycle course'),
        api_get_path(WEB_CODE_PATH).'document/recycle.php?'.api_get_cidreq(),
        ['class' => 'btn btn-default']
    );
}

if (!empty($moveTo)) {
    $document_id = DocumentManager::get_document_id($courseInfo, $moveTo);
}

if (isset($_GET['createdir']) && isset($_POST['dirname']) && '' != $_POST['dirname']) {
    $post_dir_name = $_POST['dirname'];
    $document_id = DocumentManager::get_document_id($courseInfo, $_POST['dirname']);
}

$selector = '';
if (!$is_certificate_mode && !isset($_GET['move'])) {
    $selector = DocumentManager::build_directory_selector(
        $folders,
        $document_id,
        (isset($group_properties['directory']) ? $group_properties['directory'] : [])
    );
}

$tableToString = '';
if (!empty($documentAndFolders) && !empty($sortable_data)) {
    $column_show = [];
    if (($isAllowedToEdit || $groupMemberWithUploadRights) && count($documentAndFolders) > 1) {
        $column_show[] = 1;
    }

    $column_show[] = 1;
    $column_show[] = 1;
    $column_show[] = 1;
    $column_show[] = 1;

    if ($isAllowedToEdit ||
        $groupMemberWithUploadRights ||
        DocumentManager::is_my_shared_folder(api_get_user_id(), $curdirpath, $sessionId)
    ) {
        $column_show[] = 1;
    }
    $column_show[] = 0;
    $column_show[] = 0;
    $column_order = [];

    if (12 == count(reset($sortable_data))) {
        //teacher
        $column_order[2] = 8; //name
        $column_order[3] = 7;
        $column_order[4] = 6;
    } elseif (10 == count(reset($sortable_data))) {
        //student
        $column_order[1] = 6;
        $column_order[2] = 5;
        $column_order[3] = 4;
    }

    $default_column = $isAllowedToEdit ? 2 : 1;
    $tableName = $isAllowedToEdit ? 'teacher_table' : 'student_table';
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
    $queryVars = [];
    if (isset($_GET['keyword'])) {
        $queryVars['keyword'] = Security::remove_XSS($_GET['keyword']);
    } else {
        $queryVars['curdirpath'] = $curdirpath;
    }

    if ($groupId) {
        $queryVars['gidReq'] = $groupId;
    }
    $queryVars['cidReq'] = api_get_course_id();
    $queryVars['id_session'] = api_get_session_id();
    $queryVars['id'] = $document_id;
    $table->set_additional_parameters($queryVars);
    $column = 0;

    if (($isAllowedToEdit || $groupMemberWithUploadRights) &&
        count($documentAndFolders) > 1
    ) {
        $table->set_header($column++, '', false, ['style' => 'width:12px;']);
    }
    $table->set_header($column++, get_lang('Type'), true, ['style' => 'width:30px;']);
    $table->set_header($column++, get_lang('Name'));
    $table->set_header($column++, get_lang('Size'), true, ['style' => 'width:50px;']);
    $table->set_header($column++, get_lang('Date'), true, ['style' => 'width:150px;']);
    // Admins get an edit column
    if ($isAllowedToEdit ||
        $groupMemberWithUploadRights ||
        DocumentManager::is_my_shared_folder(api_get_user_id(), $curdirpath, $sessionId)
    ) {
        $table->set_header($column++, get_lang('Detail'), false, ['class' => 'td_actions']);
    }

    // Detail on multiple selected documents
    // TODO: Currently only delete action -> take only DELETE permission into account

    if (count($documentAndFolders) > 1) {
        if ($isAllowedToEdit || $groupMemberWithEditRights) {
            $form_actions = [];
            $form_action['set_invisible'] = get_lang('Set invisible');
            $form_action['set_visible'] = get_lang('Set visible');
            $form_action['delete'] = get_lang('Delete');
            $table->set_form_actions($form_action, 'ids');
        }
    }

    $tableToString = $table->return_table();
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
    [$actionsLeft, $actionsRight]
);

echo $toolbar;
echo $templateForm;
echo $moveForm;
echo $dirForm;
echo $selector;
echo $tableToString;

$ajaxURL = api_get_path(WEB_AJAX_PATH).'document.ajax.php?a=get_document_quota&'.api_get_cidreq();

if (count($documentAndFolders) > 1) {
    echo '<script>
    $(function() {
        $.ajax({
            url:"'.$ajaxURL.'",
            success:function(data){
                $("#course_quota").html(data);
            }
        });
        
        $(".document_size").each(function(i, obj) {
            var path = obj.getAttribute("data-path");
                            
            $.ajax({
                url:"'.$getSizeURL.'&path="+path,
                success:function(data){
                    $(obj).html(data);
                }
            });            
        });    
    });
    </script>';
    echo '<span id="course_quota"></span>';
}

if (empty($documentAndFolders)) {
    echo Display::return_message(get_lang('There are no documents to be displayed.'), 'warning');
}
echo '
    <div id="convertModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="text-align: center;">
                <button type="button" class="close" data-dismiss="modal" aria-label="'.get_lang('Close').'">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">'.get_lang('Convert').'</h4>
            </div>
            <div class="modal-body">
                <form action="#" class="form-horizontal">
                    <div class="form-group">
                        <label class="col-sm-4 control-label" for="convertSelect">'.get_lang('Convert format').'</label>
                        <div class="col-sm-8">
                            <select id="convertSelect">
                                <option value="">'.get_lang('Select').'</option>
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
              <button type="button" class="btn btn-default" data-dismiss="modal">'.get_lang('Close').'</button>
            </div>
        </div>
    </div>
';

Session::erase('slideshow_'.api_get_course_id().api_get_session_id());
Display::display_footer();
