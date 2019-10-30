<?php
/* For licensing terms, see /license.txt */

/**
 *  This file will show documents in a separate frame.
 *  We don't like frames, but it was the best of two bad things.
 *
 *  display html files within Chamilo - html files have the Chamilo header.
 *
 *  --- advantages ---
 *  users "feel" like they are in Chamilo,
 *  and they can use the navigation context provided by the header.
 * --- design ---
 *  a file gets a parameter (an html file) and shows
 *    - chamilo header
 *    - html file from parameter
 *    - (removed) chamilo footer
 *
 * @version 0.6
 *
 * @author Roan Embrechts (roan.embrechts@vub.ac.be)
 *
 * @package chamilo.document
 */
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script();

$header_file = isset($_GET['file']) ? Security::remove_XSS($_GET['file']) : null;
$document_id = (int) $_GET['id'];
$originIsLearnpath = isset($_GET['origin']) && $_GET['origin'] === 'learnpathitem';
$courseInfo = api_get_course_info();
$course_code = api_get_course_id();
$session_id = api_get_session_id();

if (empty($courseInfo)) {
    api_not_allowed(true);
}

$show_web_odf = false;

// Generate path
if (!$document_id) {
    $document_id = DocumentManager::get_document_id($courseInfo, $header_file);
}
$document_data = DocumentManager::get_document_data_by_id(
    $document_id,
    $course_code,
    true,
    $session_id
);

if ($session_id != 0 && !$document_data) {
    $document_data = DocumentManager::get_document_data_by_id(
        $document_id,
        $course_code,
        true,
        0
    );
}

if (empty($document_data)) {
    api_not_allowed(true);
}

$header_file = $document_data['path'];
$name_to_show = $document_data['title'];
$path_array = explode('/', str_replace('\\', '/', $header_file));
$path_array = array_map('urldecode', $path_array);
$header_file = implode('/', $path_array);
$file = Security::remove_XSS(urldecode($document_data['path']));
$file_root = $courseInfo['path'].'/document'.str_replace('%2F', '/', $file);
$file_url_sys = api_get_path(SYS_COURSE_PATH).$file_root;
$file_url_web = api_get_path(WEB_COURSE_PATH).$file_root;

$is_allowed_to_edit = api_is_allowed_to_edit();
//fix the screen when you try to access a protected course through the url
$is_allowed_in_course = api_is_allowed_in_course() || $is_allowed_to_edit;
if ($is_allowed_in_course == false) {
    api_not_allowed(true);
}

// Check user visibility.
$is_visible = DocumentManager::check_visibility_tree(
    $document_id,
    api_get_course_info(),
    api_get_session_id(),
    api_get_user_id(),
    api_get_group_id(),
    false
);

if (!$is_allowed_to_edit && !$is_visible) {
    api_not_allowed(true);
}

$pathinfo = pathinfo($header_file);
$playerSupportedFiles = ['mp3', 'mp4', 'ogv', 'flv', 'm4v', 'webm'];
$playerSupported = false;
if (in_array(strtolower($pathinfo['extension']), $playerSupportedFiles)) {
    $playerSupported = true;
}

$group_id = api_get_group_id();
$current_group = GroupManager::get_group_properties($group_id);
$current_group_name = $current_group['name'];

if (isset($group_id) && $group_id != '') {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq(),
        'name' => get_lang('Groups'),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq(),
        'name' => get_lang('Group area').' '.$current_group_name,
    ];
    $name_to_show = explode('/', $name_to_show);
    unset($name_to_show[1]);
    $name_to_show = implode('/', $name_to_show);
}

$interbreadcrumb[] = [
    'url' => './document.php?curdirpath='.dirname($header_file).'&'.api_get_cidreq(),
    'name' => get_lang('Documents'),
];

if (empty($document_data['parents'])) {
    if (isset($_GET['createdir'])) {
        $interbreadcrumb[] = [
            'url' => $document_data['document_url'],
            'name' => $document_data['title'],
        ];
    } else {
        $interbreadcrumb[] = [
            'url' => '#',
            'name' => $document_data['title'],
        ];
    }
} else {
    foreach ($document_data['parents'] as $document_sub_data) {
        if (!isset($_GET['createdir']) && $document_sub_data['id'] == $document_data['id']) {
            $document_sub_data['document_url'] = '#';
        }
        $interbreadcrumb[] = [
            'url' => $document_sub_data['document_url'],
            'name' => $document_sub_data['title'],
        ];
    }
}

$this_section = SECTION_COURSES;
$nameTools = get_lang('Documents');

/**
 * Main code section.
 */
header('Expires: Wed, 01 Jan 1990 00:00:00 GMT');
//header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
header('Last-Modified: Wed, 01 Jan 2100 00:00:00 GMT');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
$browser_display_title = 'Documents - '.Security::remove_XSS($_GET['cidReq']).' - '.$file;
// Only admins get to see the "no frames" link in pageheader.php, so students get a header that's not so high
$frameheight = 135;
if (api_is_course_admin()) {
    $frameheight = 165;
}

$frameReady = Display::getFrameReadyBlock('#mainFrame');

$web_odf_supported_files = DocumentManager::get_web_odf_extension_list();
// PDF should be displayed with viewerJS
$web_odf_supported_files[] = 'pdf';
if (in_array(strtolower($pathinfo['extension']), $web_odf_supported_files)) {
    $show_web_odf = true;
    $htmlHeadXtra[] = '
    <script>
        resizeIframe = function() {
            var bodyHeight = $("body").height();
            var topbarHeight = $("#topbar").height();
            $("#viewerJSContent").height((bodyHeight - topbarHeight));
        }
        $(function() {
            $(window).resize(resizeIframe());
        });
    </script>'
    ;
}

// Activate code highlight.
$isChatFolder = false;
if (isset($document_data['parents']) && isset($document_data['parents'][0])) {
    $chatFolder = $document_data['parents'][0];
    if (isset($chatFolder['path']) && $chatFolder['path'] == '/chat_files') {
        $isChatFolder = true;
    }
}

if ($isChatFolder) {
    $htmlHeadXtra[] = api_get_js('highlight/highlight.pack.js');
    $htmlHeadXtra[] = api_get_css(api_get_path(WEB_CSS_PATH).'chat.css');
    $htmlHeadXtra[] = api_get_css(
        api_get_path(WEB_LIBRARY_PATH).'javascript/highlight/styles/github.css'
    );
    $htmlHeadXtra[] = '
    <script>
        hljs.initHighlightingOnLoad();
    </script>';
}

$execute_iframe = true;
if ($playerSupported) {
    $extension = api_strtolower($pathinfo['extension']);
    $execute_iframe = false;
}

if ($show_web_odf) {
    $execute_iframe = false;
}

if (!$playerSupported && $execute_iframe) {
    $htmlHeadXtra[] = '<script>
    <!--
        var jQueryFrameReadyConfigPath = \''.api_get_jquery_web_path().'\';
    -->
    </script>';
    $htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.frameready.js"></script>';
    $htmlHeadXtra[] = '<script>
        // Fixes the content height of the frame
        $(function() {
            $(\'#mainFrame\').on(\'load\', function () {
                this.style.height = (this.contentWindow.document.body.scrollHeight + 50) + \'px\';
            });
            
            '.$frameReady.'
        });
    </script>';
}

if ($originIsLearnpath) {
    Display::display_reduced_header();
} else {
    Display::display_header();
}

$file_url = api_get_path(WEB_COURSE_PATH).$courseInfo['path'].'/document'.$header_file;
$file_url_web = $file_url.'?'.api_get_cidreq();

if ($show_web_odf) {
    echo '<div class="text-center">';
    $browser = api_get_navigator();
    $pdfUrl = api_get_path(WEB_LIBRARY_PATH).'javascript/ViewerJS/index.html#'.$file_url;
    if ($browser['name'] == 'Mozilla' && preg_match('|.*\.pdf|i', $header_file)) {
        $pdfUrl = $file_url;
    }
    echo '<div id="viewerJS">';
    echo '<iframe id="viewerJSContent" frameborder="0" allowfullscreen="allowfullscreen" webkitallowfullscreen style="width:100%;"
            src="'.$pdfUrl.'">
        </iframe>';
    echo '</div>';
    echo '</div>';
}

if ($playerSupported) {
    echo DocumentManager::generateMediaPreview($file_url_web, $extension);
}

if ($execute_iframe) {
    if ($isChatFolder) {
        $content = Security::remove_XSS(file_get_contents($file_url_sys));
        echo $content;
    } else {
        $parentId = $document_data['parent_id'];
        $url = api_get_path(WEB_CODE_PATH).'document/document.php?'.api_get_cidreq().'&id='.$parentId;
        $actionsLeft = Display::url(
            Display::return_icon('back.png', get_lang('Back'), '', ICON_SIZE_MEDIUM),
            $url
        );

        $groupMemberWithEditRights = false;
        $groupId = api_get_group_id();
        if (!empty($groupId)) {
            $groupInfo = GroupManager::get_group_properties($groupId);
            if ($groupInfo) {
                $groupMemberWithEditRights = GroupManager::allowUploadEditDocument(
                    api_get_user_id(),
                    api_get_course_int_id(),
                    $groupInfo,
                        $document_data
                );
            }
        }

        $allowToEdit = api_is_allowed_to_edit(null, true) || $groupMemberWithEditRights;

        if ($allowToEdit) {
            $actionsLeft .= Display::url(
                Display::return_icon(
                    'edit.png',
                    get_lang('Edit'),
                    '',
                    ICON_SIZE_MEDIUM
                ),
                api_get_path(WEB_CODE_PATH).'document/edit_document.php?'.api_get_cidreq().'&id='.$document_id
            );

            $titleToShow = addslashes(basename($document_data['title']));

            $urlDeleteParams = http_build_query(
                [
                    'action' => 'delete_item',
                    'id' => $parentId,
                    'deleteid' => $document_data['id'],
                ]
            );
            $actionsLeft .= Display::url(
                Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_MEDIUM),
                '#',
                [
                    'data-item-title' => $titleToShow,
                    'data-href' => api_get_path(WEB_CODE_PATH).'document/document.php?'.api_get_cidreq(
                        ).'&'.$urlDeleteParams,
                    'data-toggle' => 'modal',
                    'data-target' => '#confirm-delete',
                ]
            );
            $actionsLeft .= Display::url(
                Display::return_icon('pdf.png', get_lang('Export to PDF format'), [], ICON_SIZE_MEDIUM),
                api_get_path(WEB_CODE_PATH).'document/document.php?'.api_get_cidreq(
                ).'&action=export_to_pdf&id='.$document_id
            );
        }

        echo $toolbar = Display::toolbarAction('actions-documents', [$actionsLeft]);

        echo '<iframe 
            id="mainFrame" 
            name="mainFrame" 
            border="0" 
            frameborder="0" 
            scrolling="no" 
            style="width:100%;" height="600" 
            src="'.$file_url_web.'&rand='.mt_rand(1, 10000).'" 
            height="500" allowfullscreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>';
    }
}
Display::display_footer();
