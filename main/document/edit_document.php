<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This file allows editing documents.
 *
 * Based on create_document, this file allows
 * - edit name
 * - edit comments
 * - edit metadata (requires a document table entry)
 * - edit html content (only for htm/html files)
 *
 * For all files
 * - show editable name field
 * - show editable comments field
 * Additionally, for html and text files
 * - show RTE
 *
 * Remember, all files and folders must always have an entry in the
 * database, regardless of wether they are visible/invisible, have
 * comments or not.
 *
 * @package chamilo.document
 *
 * @todo improve script structure (FormValidator is used to display form, but
 * not for validation at the moment)
 */
require_once __DIR__.'/../inc/global.inc.php';

$groupRights = Session::read('group_member_with_upload_rights');

// Template's javascript
$htmlHeadXtra[] = '
<script>
$(function() {
    $(".scrollbar-light").scrollbar();

    expandColumnToogle("#hide_bar_template", {
        selector: "#template_col",
        width: 3
    }, {
        selector: "#doc_form",
        width: 9
    });

    CKEDITOR.on("instanceReady", function (e) {
        showTemplates();

        e.editor.on("beforeCommandExec", function (event) {
            if (event.data.name == "save") {
                $("#formEdit").append("<input type=hidden name=button_ck value=1 />");
            }
        });
    });
});

</script>';

$this_section = SECTION_COURSES;
$lib_path = api_get_path(LIBRARY_PATH);

$course_info = api_get_course_info();
$group_id = api_get_group_id();
$sessionId = api_get_session_id();
$dir = '/';
$currentDirPath = isset($_GET['curdirpath']) ? Security::remove_XSS($_GET['curdirpath']) : null;
$readonly = false;
if (isset($_GET['id'])) {
    $document_data = DocumentManager::get_document_data_by_id(
        $_GET['id'],
        api_get_course_id(),
        true,
        0
    );

    if (!empty($sessionId) && empty($document_data)) {
        $document_data = DocumentManager::get_document_data_by_id(
            $_REQUEST['id'],
            api_get_course_id(),
            true,
            $sessionId
        );
    }

    $document_id = $document_data['id'];
    $file = $document_data['path'];
    $parent_id = DocumentManager::get_document_id($course_info, dirname($file));
    $dir = dirname($document_data['path']);
    $dir_original = $dir;
    $doc = basename($file);
    $readonly = $document_data['readonly'];
    $file_type = $document_data['filetype'];
}

if (empty($document_data)) {
    api_not_allowed(true);
}

if (api_is_in_group()) {
    $group_properties = GroupManager::get_group_properties($group_id);
}

$is_certificate_mode = DocumentManager::is_certificate_mode($dir);

//Call from
$call_from_tool = api_get_origin();
$slide_id = isset($_GET['origin_opt']) ? Security::remove_XSS($_GET['origin_opt']) : null;
$file_name = $doc;
$group_document = false;
$_course = api_get_course_info();
$sessionId = api_get_session_id();
$user_id = api_get_user_id();
$doc_tree = explode('/', $file);
$count_dir = count($doc_tree) - 2; // "2" because at the begin and end there are 2 "/"

// Level correction for group documents.
if (!empty($group_properties['directory'])) {
    $count_dir = $count_dir > 0 ? $count_dir - 1 : 0;
}
$relative_url = '';
for ($i = 0; $i < ($count_dir); $i++) {
    $relative_url .= '../';
}

$editorConfig = [
    'ToolbarSet' => (api_is_allowed_to_edit(null, true) ? 'Documents' : 'DocumentsStudent'),
    'Width' => '100%',
    'Height' => '400',
    'cols-size' => [2, 10, 0],
    'FullPage' => true,
    'InDocument' => true,
    'CreateDocumentDir' => $relative_url,
    'CreateDocumentWebDir' => (empty($group_properties['directory']))
        ? api_get_path(WEB_COURSE_PATH).$_course['path'].'/document/'
        : api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document'.$group_properties['directory'].'/',
    'BaseHref' => api_get_path(WEB_COURSE_PATH).$_course['path'].'/document'.$dir,
];

if ($is_certificate_mode) {
    $editorConfig['CreateDocumentDir'] = api_get_path(WEB_COURSE_PATH).$_course['path'].'/document/';
    $editorConfig['CreateDocumentWebDir'] = api_get_path(WEB_COURSE_PATH).$_course['path'].'/document/';
    $editorConfig['BaseHref'] = api_get_path(WEB_COURSE_PATH).$_course['path'].'/document'.$dir;
}

$is_allowed_to_edit = api_is_allowed_to_edit(null, true) || $groupRights ||
    DocumentManager::is_my_shared_folder(api_get_user_id(), $dir, $sessionId);

$dbTable = Database::get_course_table(TABLE_DOCUMENT);
$course_id = api_get_course_int_id();

if (!empty($group_id)) {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq(),
        'name' => get_lang('GroupSpace'),
    ];
    $group_document = true;
}

if (!$is_certificate_mode) {
    $interbreadcrumb[] = [
        "url" => api_get_path(WEB_CODE_PATH)."document/document.php?curdirpath=".urlencode($currentDirPath).'&'.api_get_cidreq(),
        "name" => get_lang('Documents'),
    ];
} else {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('Gradebook'),
    ];
}

if (empty($document_data['parents'])) {
    $interbreadcrumb[] = ['url' => '#', 'name' => $document_data['title']];
} else {
    foreach ($document_data['parents'] as $document_sub_data) {
        if ($document_data['title'] == $document_sub_data['title']) {
            continue;
        }
        $interbreadcrumb[] = ['url' => $document_sub_data['document_url'], 'name' => $document_sub_data['title']];
    }
}

if (!($is_allowed_to_edit ||
    $groupRights ||
    DocumentManager::is_my_shared_folder($user_id, $dir, api_get_session_id()))
) {
    api_not_allowed(true);
}

Event::event_access_tool(TOOL_DOCUMENT);

//TODO:check the below code and his funcionality
if (!api_is_allowed_to_edit()) {
    if (DocumentManager::check_readonly($course_info, $user_id, $file)) {
        api_not_allowed();
    }
}

$document_info = api_get_item_property_info(
    api_get_course_int_id(),
    'document',
    $document_id,
    0
);

// Try to find this document in the session
if (!empty($sessionId)) {
    $document_info = api_get_item_property_info(
        api_get_course_int_id(),
        'document',
        $document_id,
        $sessionId
    );
}

if (api_is_in_group()) {
    $group_properties = GroupManager::get_group_properties($group_id);
    GroupManager::allowUploadEditDocument(
        api_get_user_id(),
        api_get_course_int_id(),
        $group_properties,
        $document_info,
        true
    );
}

/* MAIN TOOL CODE */
/* Code to change the comment */
if (isset($_POST['comment'])) {
    // Fixing the path if it is wrong
    $comment = trim($_POST['comment']);
    $title = trim($_POST['title']);

    // Just in case see BT#3525
    if (empty($title)) {
        $title = $document_data['title'];
    }

    if (empty($title)) {
        $title = get_document_title($_POST['filename']);
    }

    if (!empty($document_id)) {
        $linkExists = false;
        if ($file_type == 'link') {
            $linkExists = DocumentManager::cloudLinkExists($course_info, $file, $_POST['comment']);
        }

        if (!$linkExists || $linkExists == $document_id) {
            $params = [
                'comment' => $comment,
                'title' => $title,
            ];
            Database::update(
                $dbTable,
                $params,
                ['c_id = ? AND id = ?' => [$course_id, $document_id]]
            );

            if ($file_type != 'link') {
                Display::addFlash(Display::return_message(get_lang('fileModified')));
            } else {
                Display::addFlash(Display::return_message(get_lang('CloudLinkModified')));
            }
        } else {
            Display::addFlash(Display::return_message(get_lang('UrlAlreadyExists'), 'warning'));
        }
    }
}

/* WYSIWYG HTML EDITOR - Program Logic */
if ($is_allowed_to_edit) {
    if (isset($_POST['formSent']) && $_POST['formSent'] == 1 && !empty($document_id)) {
        //$content = isset($_POST['content']) ? trim(str_replace(["\r", "\n"], '', stripslashes($_POST['content']))) : null;
        $content = isset($_POST['content']) ? trim(stripslashes($_POST['content'])) : null;
        $content = Security::remove_XSS($content, COURSEMANAGERLOWSECURITY);
        if ($dir == '/') {
            $dir = '';
        }

        $read_only_flag = isset($_POST['readonly']) ? $_POST['readonly'] : null;
        $read_only_flag = empty($read_only_flag) ? 0 : 1;

        if ($file_type != 'link') {
            $file_size = filesize($document_data['absolute_path']);
        }

        if ($read_only_flag == 0) {
            if (!empty($content)) {
                if ($fp = @fopen($document_data['absolute_path'], 'w')) {
                    // For flv player, change absolute path temporarily to prevent
                    // from erasing it in the following lines
                    $content = str_replace(['flv=h', 'flv=/'], ['flv=h|', 'flv=/|'], $content);
                    fputs($fp, $content);
                    fclose($fp);
                    $filepath = $document_data['absolute_parent_path'];

                    update_existing_document(
                        $_course,
                        $document_id,
                        $file_size,
                        $read_only_flag
                    );
                    api_item_property_update(
                        $_course,
                        TOOL_DOCUMENT,
                        $document_id,
                        'DocumentUpdated',
                        api_get_user_id(),
                        null,
                        null,
                        null,
                        null,
                        $sessionId
                    );
                    // Update parent folders
                    item_property_update_on_folder(
                        $_course,
                        $dir,
                        api_get_user_id()
                    );
                } else {
                    Display::addFlash(Display::return_message(get_lang('Impossible'), 'warning'));
                }
            } else {
                if ($document_id) {
                    update_existing_document($_course, $document_id, $file_size, $read_only_flag);
                }
            }
        } else {
            if ($document_id) {
                update_existing_document($_course, $document_id, $file_size, $read_only_flag);
            }
        }

        // It saves extra fields values
        $extraFieldValue = new ExtraFieldValue('document');
        $values = $_REQUEST;
        $values['item_id'] = $document_id;
        $extraFieldValue->saveFieldValues($values);

        $url = 'document.php?id='.$document_data['parent_id'].'&'.api_get_cidreq().($is_certificate_mode ? '&curdirpath=/certificates&selectcat=1' : '');

        $redirectToEditPage = isset($_POST['button_ck']) && 1 === (int) $_POST['button_ck'];
        if ($redirectToEditPage) {
            $url = 'edit_document.php?'.api_get_cidreq().'&id='.$document_id.($is_certificate_mode ? '&curdirpath=/certificates&selectcat=1' : '');
        }

        header('Location: '.$url);
        exit;
    }
}

// Replace relative paths by absolute web paths (e.g. './' => 'http://www.chamilo.org/courses/ABC/document/')
$content = null;
$extension = null;
$filename = null;
if (file_exists($document_data['absolute_path'])) {
    $path_info = pathinfo($document_data['absolute_path']);
    $filename = $path_info['filename'];

    if (is_file($document_data['absolute_path'])) {
        $extension = $path_info['extension'];

        if (in_array($extension, ['html', 'htm'])) {
            $content = file($document_data['absolute_path']);
            $content = implode('', $content);
        }
    }
}

// Display the header
$nameTools = get_lang('EditDocument').': '.Security::remove_XSS($document_data['title']);
Display::display_header($nameTools, 'Doc');

$owner_id = $document_info['insert_user_id'];
$last_edit_date = $document_info['lastedit_date'];
$createdDate = $document_info['insert_date'];
$groupInfo = GroupManager::get_group_properties(api_get_group_id());

if ($owner_id == api_get_user_id() ||
    api_is_platform_admin() ||
    $is_allowed_to_edit || GroupManager::is_user_in_group(
        api_get_user_id(),
        $groupInfo
    )
) {
    $action = api_get_self().'?id='.$document_data['id'].'&'.api_get_cidreq();
    if ($is_certificate_mode) {
        $action .= '&curdirpath=/certificates&selectcat=1';
    }
    $form = new FormValidator(
        'formEdit',
        'post',
        $action,
        null,
        ['class' => 'form-vertical']
    );

    // Form title
    $form->addHeader($nameTools);
    $key_label_title = $file_type != 'link' ? 'Title' : 'LinkName';
    $form->addText(
        'title',
        get_lang($key_label_title),
        true,
        ['cols-size' => [2, 10, 0], 'autofocus']
    );

    $defaults['title'] = $document_data['title'];
    $read_only_flag = isset($_POST['readonly']) ? $_POST['readonly'] : null;

    // Desactivation of IE proprietary commenting tags inside the text before loading it on the online editor.
    // This fix has been proposed by Hubert Borderiou, see Bug #573, http://support.chamilo.org/issues/573
    $defaults['content'] = str_replace('<!--[', '<!-- [', $content);
    // HotPotatoes tests are html files, but they should not be edited in order their functionality to be preserved.
    $showSystemFolders = api_get_course_setting('show_system_folders');
    $condition = stripos($dir, '/HotPotatoes_files') === false;
    if ($showSystemFolders == 1) {
        $condition = true;
    }

    if (($extension == 'htm' || $extension == 'html') && $condition) {
        if (empty($readonly) && $readonly == 0) {
            $form->addHtmlEditor('content', get_lang('Content'), true, true, $editorConfig);
        }
    }

    if (!empty($createdDate)) {
        $form->addLabel(get_lang('CreatedOn'), Display::dateToStringAgoAndLongDate($createdDate));
    }

    if ($file_type != 'link') {
        if (!$group_document && !DocumentManager::is_my_shared_folder(api_get_user_id(), $currentDirPath, $sessionId)) {
            $form->addLabel(get_lang('UpdatedOn'), Display::dateToStringAgoAndLongDate($last_edit_date));
        }

        if (!empty($document_info['insert_user_id'])) {
            $insertByUserInfo = api_get_user_info($document_info['insert_user_id']);
            if (!empty($insertByUserInfo)) {
                $form->addLabel(get_lang('Author'), $insertByUserInfo['complete_name_with_message_link']);
            }
        }
    }

    if ($file_type == 'link') {
        // URLs in whitelist
        $urlWL = DocumentManager::getFileHostingWhiteList();
        sort($urlWL);
        //Matches any of the whitelisted urls preceded by // or .
        $urlWLRegEx = '/(\/\/|\.)('.implode('|', $urlWL).')/i';
        $urlWLText = "\n\t* ".implode("\n\t* ", $urlWL);
        $urlWLHTML = "<ul><li>".implode("</li><li>", $urlWL)."</li></ul>";
        $form->addText('comment', get_lang('Url'));
        $form->addElement(
            'static',
            'info',
            '',
            '<span class="text-primary" data-toggle="tooltip" title="'.$urlWLHTML.'">'.get_lang(
                'ValidDomainList'
            ).' <span class="glyphicon glyphicon-question-sign"></span></span>'
        );
    } else {
        $form->addElement('textarea', 'comment', get_lang('Comment'), ['cols-size' => [2, 10, 0]]);
    }

    $itemId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $extraField = new ExtraField('document');
    $extraField->addElements(
        $form,
        $itemId,
        [], //exclude
        false, // filter
        false, // tag as select
        [], //show only fields
        [], // order fields
        [] // extra data
    );

    if ($file_type != 'link') {
        if ($owner_id == api_get_user_id() || api_is_platform_admin()) {
            $checked = &$form->addElement('checkbox', 'readonly', null, get_lang('ReadOnly'));
            if ($readonly == 1) {
                $checked->setChecked(true);
            }
        }
    }

    if ($file_type == 'link') {
        $form->addRule('title', get_lang('PleaseEnterCloudLinkName'), 'required');
        $form->addRule('comment', get_lang('PleaseEnterURL'), 'required');
        // Well formed url pattern (must have the protocol)
        $urlRegEx = DocumentManager::getWellFormedUrlRegex();
        $form->addRule('comment', get_lang('NotValidURL'), 'regex', $urlRegEx, 'client');
        $form->addRule('comment', get_lang('NotValidURL'), 'regex', $urlRegEx, 'server');
        $form->addRule('comment', get_lang('NotValidDomain').$urlWLText, 'regex', $urlWLRegEx, 'client');
        $form->addRule('comment', get_lang('NotValidDomain').$urlWLHTML, 'regex', $urlWLRegEx, 'server');
    }

    if ($is_certificate_mode) {
        $form->addButtonUpdate(get_lang('SaveCertificate'));
    } elseif ($file_type == 'link') {
        $form->addButtonUpdate(get_lang('SaveLink'));
    } else {
        $form->addButtonUpdate(get_lang('SaveDocument'));
    }
    $form->addHidden('formSent', 1);
    $form->addHidden('filename', $filename);

    $defaults['extension'] = $extension;
    $defaults['file_path'] = isset($_GET['file']) ? Security::remove_XSS($_GET['file']) : null;
    $defaults['commentPath'] = $file;
    $defaults['renameTo'] = $file_name;
    $defaults['comment'] = $document_data['comment'];
    $defaults['origin'] = api_get_origin();
    $defaults['origin_opt'] = isset($_GET['origin_opt']) ? Security::remove_XSS($_GET['origin_opt']) : null;

    $form->setDefaults($defaults);

    show_return(
        $parent_id,
        $dir_original,
        $call_from_tool,
        $slide_id,
        $is_certificate_mode
    );

    if ($is_certificate_mode) {
        $all_information_by_create_certificate = DocumentManager::get_all_info_to_certificate(
            api_get_user_id(),
            api_get_course_id(),
            api_get_session_id()
        );
        $str_info = '';
        foreach ($all_information_by_create_certificate[0] as $info_value) {
            $str_info .= $info_value.'<br/>';
        }
        $create_certificate = get_lang('CreateCertificateWithTags');
        echo Display::return_message(
            $create_certificate.': <br /><br />'.$str_info,
            'normal',
            false
        );
    }

    if ($extension == 'svg' && !api_browser_support('svg') &&
        api_get_setting('enabled_support_svg') == 'true'
    ) {
        echo Display::return_message(get_lang('BrowserDontSupportsSVG'), 'warning');
    }
    if ($file_type != 'link') {
        // HTML-editor
        echo '<div class="page-create">
                <div class="row" style="overflow:hidden">
                <div id="template_col" class="col-md-3">
                    <div class="panel panel-default">
                    <div class="panel-body">
                        <div id="frmModel" class="items-templates scrollbar-light"></div>
                    </div>
                    </div>
                </div>
                <div id="doc_form" class="col-md-9">
                    '.$form->returnForm().'
                </div>
            </div></div>';
    } else {
        // Add tooltip and correctly parse its inner HTML
        echo '<script>
        $(function() {
            $("[data-toggle=\'tooltip\']").tooltip(
                {
                    content:
                        function() {
                            return $(this).attr("title");
                        }
                }
            );
        });
        </script>';

        echo $form->returnForm();
    }
}

Display::display_footer();

// return button back to
function show_return($document_id, $path, $call_from_tool = '', $slide_id = 0, $is_certificate_mode = false)
{
    $actionsLeft = null;
    global $parent_id;
    $url = api_get_path(WEB_CODE_PATH).'document/document.php?'.api_get_cidreq().'&id='.$parent_id;

    if ($is_certificate_mode) {
        $selectedCategory = (isset($_GET['curdirpath']) ? Security::remove_XSS($_GET['curdirpath']) : '');
        $actionsLeft .= '<a href="document.php?curdirpath='.$selectedCategory.'&selectcat='.$selectedCategory.'">'.
            Display::return_icon('back.png', get_lang('Back').' '.get_lang('To').' '.get_lang('CertificateOverview'), '', ICON_SIZE_MEDIUM).'</a>';
        $actionsLeft .= '<a id="hide_bar_template" href="#" role="button">'.Display::return_icon('expand.png', get_lang('Expand'), ['id' => 'expand'], ICON_SIZE_MEDIUM).Display::return_icon('contract.png', get_lang('Collapse'), ['id' => 'contract', 'class' => 'hide'], ICON_SIZE_MEDIUM).'</a>';
    } elseif ($call_from_tool == 'slideshow') {
        $actionsLeft .= '<a href="'.api_get_path(WEB_PATH).'main/document/slideshow.php?slide_id='.$slide_id.'&curdirpath='.Security::remove_XSS(urlencode($_GET['curdirpath'])).'">'.
            Display::return_icon('slideshow.png', get_lang('BackTo').' '.get_lang('ViewSlideshow'), '', ICON_SIZE_MEDIUM).'</a>';
    } elseif ($call_from_tool == 'editdraw') {
        $actionsLeft .= '<a href="'.$url.'">'.
            Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('DocumentsOverview'), '', ICON_SIZE_MEDIUM).'</a>';
        $actionsLeft .= '<a href="javascript:history.back(1)">'.Display::return_icon('draw.png', get_lang('BackTo').' '.get_lang('Draw'), [], 32).'</a>';
    } elseif ($call_from_tool == 'editodf') {
        $actionsLeft .= '<a href="'.$url.'">'.
            Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('DocumentsOverview'), '', ICON_SIZE_MEDIUM).'</a>';
        $actionsLeft .= '<a href="javascript:history.back(1)">'.Display::return_icon('draw.png', get_lang('BackTo').' '.get_lang('Write'), [], 32).'</a>';
        $actionsLeft .= '<a id="hide_bar_template" href="#" role="button">'.Display::return_icon('expand.png', get_lang('Expand'), ['id' => 'expand'], ICON_SIZE_MEDIUM).Display::return_icon('contract.png', get_lang('Collapse'), ['id' => 'contract', 'class' => 'hide'], ICON_SIZE_MEDIUM).'</a>';
    } elseif ($call_from_tool == 'editpaint' && api_get_setting('enabled_support_pixlr') === 'true') {
        $actionsLeft .= '<a href="'.$url.'">'.
            Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('DocumentsOverview'), [], ICON_SIZE_MEDIUM).'</a>';
        $actionsLeft .= '<a href="javascript:history.back(1)">'.Display::return_icon('paint.png', get_lang('BackTo').' '.get_lang('Paint'), [], 32).'</a>';
    } else {
        $actionsLeft .= '<a href="'.$url.'">'.
            Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('DocumentsOverview'), '', ICON_SIZE_MEDIUM).'</a>';
        $actionsLeft .= '<a id="hide_bar_template" href="#" role="button">'.Display::return_icon('expand.png', get_lang('Expand'), ['id' => 'expand'], ICON_SIZE_MEDIUM).Display::return_icon('contract.png', get_lang('Collapse'), ['id' => 'contract', 'class' => 'hide'], ICON_SIZE_MEDIUM).'</a>';
    }

    echo $toolbar = Display::toolbarAction('actions-documents', [$actionsLeft]);
}
