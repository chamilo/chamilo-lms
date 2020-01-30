<?php

/* For licensing terms, see /license.txt */

/**
 * This script allows to add cloud file links to the document structure.
 *
 * @package chamilo.document
 */
require_once __DIR__.'/../inc/global.inc.php';

$fileLinkEnabled = api_get_configuration_value('enable_add_file_link');
if (!$fileLinkEnabled) {
    api_not_allowed(true);
}

api_protect_course_script();

$courseInfo = api_get_course_info();

if (empty($courseInfo)) {
    api_not_allowed(true);
}

$documentId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$dir = '/';
$document_data = DocumentManager::get_document_data_by_id($documentId, api_get_course_id(), true);
if (empty($document_data)) {
    $document_id = $parent_id = 0;
    $path = '/';
} else {
    if ($document_data['filetype'] == 'folder') {
        $document_id = $document_data['id'];
        $path = $document_data['path'].'/';
        $parent_id = DocumentManager::get_document_id(api_get_course_info(), dirname($path));
    }
    $dir = dirname($document_data['path']);
}

$is_certificate_mode = DocumentManager::is_certificate_mode($dir);
if ($is_certificate_mode) {
    api_not_allowed(true);
}

$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

$groupIid = 0;
if (api_get_group_id()) {
    // If the group id is set, check if the user has the right to be here
    // Get group info
    $group_properties = GroupManager::get_group_properties(api_get_group_id());
    if ($is_allowed_to_edit || GroupManager::is_user_in_group(api_get_user_id(), $group_properties)) {
        // Only courseadmin or group members allowed
        $groupIid = $group_properties['iid'];
        $interbreadcrumb[] = [
            'url' => '../group/group_space.php?'.api_get_cidreq(),
            'name' => get_lang('GroupSpace'),
        ];
    } else {
        api_not_allowed(true);
    }
} elseif ($is_allowed_to_edit || DocumentManager::is_my_shared_folder(api_get_user_id(), $path, api_get_session_id())) {
    // Admin for "regular" upload, no group documents. And check if is my shared folder
} else { // No course admin and no group member...
    api_not_allowed(true);
}

// Group docs can only be uploaded in the group directory
if ($groupIid != 0 && $path == '/') {
    $path = $group_properties['directory']."/";
}

// Breadcrumbs
$interbreadcrumb[] = [
    'url' => './document.php?id='.$document_id.'&'.api_get_cidreq(),
    'name' => get_lang('Documents'),
];

// Interbreadcrumb for the current directory root path
if (empty($document_data['parents'])) {
    // Hack in order to not add the document to the breadcrumb in case it is a link
    if ($document_data && $document_data['filetype'] != 'link') {
        $interbreadcrumb[] = ['url' => '#', 'name' => $document_data['title']];
    }
} else {
    foreach ($document_data['parents'] as $document_sub_data) {
        // Hack in order to not add the document to the breadcrumb in case it is a link
        if ($document_data['filetype'] != 'link') {
            $interbreadcrumb[] = [
                'url' => $document_sub_data['document_url'],
                'name' => $document_sub_data['title'],
            ];
        }
    }
}

$this_section = SECTION_COURSES;

$nameTools = get_lang('LinkAdd');
$action = api_get_self().'?'.api_get_cidreq().'&id='.$document_id;

// URLs in whitelist
$urlWL = DocumentManager::getFileHostingWhiteList();

sort($urlWL);
$urlWLRegEx = '/(\/\/|\.)('.implode('|', $urlWL).')/i'; //Matches any of the whitelisted urls preceded by // or .
$urlWLText = "\n\t* ".implode("\n\t* ", $urlWL);
$urlWLHTML = "<ul><li>".implode("</li><li>", $urlWL)."</li></ul>";

$form = new FormValidator('upload', 'POST', $action, '', ['enctype' => 'multipart/form-data']);
$form->addHidden('linkid', $document_id);
$form->addHidden('curdirpath', $path);
$form->addElement('text', 'name', get_lang('LinkName'), ['id' => 'name_link']);
$form->addElement('text', 'url', get_lang('Url'), ['id' => 'url_link']);
$form->addElement(
    'static',
    'info',
    '',
    '<span class="text-primary" data-toggle="tooltip" title="'.$urlWLHTML.'">'.get_lang(
        'ValidDomainList'
    ).' <span class="glyphicon glyphicon-question-sign"></span></span>'
);
$form->addButtonSend(get_lang('AddCloudLink'), 'submitDocument');

$form->addRule('name', get_lang('PleaseEnterCloudLinkName'), 'required', null, 'client');
$form->addRule('name', get_lang('PleaseEnterCloudLinkName'), 'required', null, 'server');
$form->addRule('url', get_lang('PleaseEnterURL'), 'required', null, 'client');
$form->addRule('url', get_lang('PleaseEnterURL'), 'required', null, 'server');
// Well formed url pattern (must have the protocol)
$urlRegEx = DocumentManager::getWellFormedUrlRegex();
$form->addRule('url', get_lang('NotValidURL'), 'regex', $urlRegEx, 'client');
$form->addRule('url', get_lang('NotValidURL'), 'regex', $urlRegEx, 'server');
$form->addRule('url', get_lang('NotValidDomain').$urlWLText, 'regex', $urlWLRegEx, 'client');
$form->addRule('url', get_lang('NotValidDomain').$urlWLHTML, 'regex', $urlWLRegEx, 'server');

if ($form->validate()) {
    if (isset($_REQUEST['linkid'])) {
        $doc_id = DocumentManager::addCloudLink($courseInfo, $path, $_REQUEST['url'], $_REQUEST['name']);
        if ($doc_id) {
            Display::addFlash(Display::return_message(get_lang('CloudLinkAdded'), 'success', false));
        } else {
            if (DocumentManager::cloudLinkExists($courseInfo, $path, $_REQUEST['url'])) {
                Display::addFlash(Display::return_message(get_lang('UrlAlreadyExists'), 'warning', false));
            } else {
                Display::addFlash(Display::return_message(get_lang('ErrorAddCloudLink'), 'warning', false));
            }
        }
        header('Location: document.php?'.api_get_cidreq().'&id='.$documentId);
        exit;
    }
}

// Display the header
Display::display_header($nameTools, 'Doc');

// Actions
echo '<div class="actions">';
// Link back to the documents overview
echo '<a href="document.php?id='.$document_id.'&'.api_get_cidreq().'">'.
    Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('DocumentsOverview'), '', ICON_SIZE_MEDIUM).
    '</a>';
echo '</div>';

// Form to select directory
$folders = DocumentManager::get_all_document_folders(
    $_course,
    $groupIid,
    $is_allowed_to_edit
);

echo DocumentManager::build_directory_selector(
    $folders,
    $document_id,
    isset($group_properties['directory']) ? $group_properties['directory'] : []
);

// Add tooltip and correctly parse its inner HTML
echo '<script>
$(function() {
    $("[data-toggle=\'tooltip\']").tooltip({
        content:
            function() {
                return $(this).attr("title");
            }
    });
});
</script>';

echo $form->returnForm();
Display::display_footer();
