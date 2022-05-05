<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This file allows creating audio files from a text.
 *
 * @package chamilo.document
 *
 * @author Juan Carlos Raña Trabado
 *
 * @since 8/January/2011
 * TODO:clean all file
 */
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_COURSES;

$nameTools = get_lang('CreateAudio');

api_protect_course_script();
api_block_anonymous_users();

$groupRights = Session::read('group_member_with_upload_rights');
$groupId = api_get_group_id();

if (api_get_setting('enabled_text2audio') === 'false') {
    api_not_allowed(true);
}

$requestId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : null;

$document_data = DocumentManager::get_document_data_by_id(
    $requestId,
    api_get_course_id()
);
if (empty($document_data)) {
    if (api_is_in_group()) {
        $group_properties = GroupManager::get_group_properties(
            $groupId
        );
        $document_id = DocumentManager::get_document_id(
            api_get_course_info(),
            $group_properties['directory']
        );
        $document_data = DocumentManager::get_document_data_by_id(
            $document_id,
            api_get_course_id()
        );
    }
}
$document_id = $document_data['id'];
$dir = $document_data['path'];
//jquery textareaCounter
$htmlHeadXtra[] = '<script src="../inc/lib/javascript/textareacounter/jquery.textareaCounter.plugin.js" type="text/javascript"></script>';

$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

// Please, do not modify this dirname formatting

if (strstr($dir, '..')) {
    $dir = '/';
}

if ($dir[0] == '.') {
    $dir = substr($dir, 1);
}

if ($dir[0] != '/') {
    $dir = '/'.$dir;
}

if ($dir[strlen($dir) - 1] != '/') {
    $dir .= '/';
}

$filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document'.$dir;

if (!is_dir($filepath)) {
    $filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/';
    $dir = '/';
}

//groups //TODO: clean
if (!empty($groupId)) {
    $interbreadcrumb[] = [
        "url" => "../group/group_space.php?".api_get_cidreq(),
        "name" => get_lang('GroupSpace'),
    ];
    $group = GroupManager::get_group_properties($groupId);
    $path = explode('/', $dir);
    if ('/'.$path[1] != $group['directory']) {
        api_not_allowed(true);
    }
}

$interbreadcrumb[] = [
    "url" => "./document.php?curdirpath=".urlencode($dir)."&".api_get_cidreq(),
    "name" => get_lang('Documents'),
];

if (!api_is_allowed_in_course()) {
    api_not_allowed(true);
}

if (!($is_allowed_to_edit || $groupRights ||
    DocumentManager::is_my_shared_folder(
        api_get_user_id(),
        Security::remove_XSS($dir),
        api_get_session_id()
    ))
) {
    api_not_allowed(true);
}

Event::event_access_tool(TOOL_DOCUMENT);

$display_dir = $dir;
if (isset($group)) {
    $display_dir = explode('/', $dir);
    unset($display_dir[0]);
    unset($display_dir[1]);
    $display_dir = implode('/', $display_dir);
}

// Copied from document.php
$dir_array = explode('/', $dir);
$array_len = count($dir_array);

$dir_acum = '';
for ($i = 0; $i < $array_len; $i++) {
    $url_dir = 'document.php?&curdirpath='.$dir_acum.$dir_array[$i];
    //Max char 80
    $url_to_who = cut($dir_array[$i], 80);
    $interbreadcrumb[] = ['url' => $url_dir, 'name' => $url_to_who];
    $dir_acum .= $dir_array[$i].'/';
}

$service = isset($_GET['service']) ? $_GET['service'] : 'google';

if (isset($_POST['text2voice_mode']) && $_POST['text2voice_mode'] == 'google') {
    downloadAudioGoogle($filepath, $dir);
}

Display::display_header($nameTools, 'Doc');

echo '<div class="actions">';
echo '<a href="document.php?id='.$document_id.'">';
echo Display::return_icon(
    'back.png',
    get_lang('BackTo').' '.get_lang('DocumentsOverview'),
    '',
    ICON_SIZE_MEDIUM
);
echo '</a>';

echo '<a href="create_audio.php?'.api_get_cidreq().'&id='.$document_id.'&service=google">'.
    Display::return_icon('google.png', get_lang('GoogleAudio'), '', ICON_SIZE_MEDIUM).'</a>';
echo '</div>';
?>
    <!-- javascript and styles for textareaCounter-->
    <script>
        var info;
        $(function() {
            var options = {
                'maxCharacterSize': 100,
                'originalStyle': 'originalTextareaInfo',
                'warningStyle': 'warningTextareaInfo',
                'warningNumber': 20,
                'displayFormat': '#input/#max'
            };
            $('#textarea_google').textareaCount(options, function (data) {
                $('#textareaCallBack').html(data);
            });
        });

    </script>
    <style>
        .overview {
            background: #FFEC9D;
            padding: 10px;
            width: 90%;
            border: 1px solid #CCCCCC;
        }

        .originalTextareaInfo {
            font-size: 12px;
            color: #000000;
            text-align: right;
        }

        .warningTextareaInfo {
            color: #FF0000;
            font-weight: bold;
            text-align: right;
        }

        #showData {
            height: 70px;
            width: 200px;
            border: 1px solid #CCCCCC;
            padding: 10px;
            margin: 10px;
        }
    </style>
    <div id="textareaCallBack"></div>
<?php

$tbl_admin_languages = Database::get_main_table(TABLE_MAIN_LANGUAGE);
$sql_select = "SELECT * FROM $tbl_admin_languages";
$result_select = Database::query($sql_select);

$options = $options_pedia = [];
$selected_language = null;

while ($row = Database::fetch_array($result_select)) {
    $options[$row['isocode']] = $row['original_name'].' ('.$row['english_name'].')';
    if (in_array($row['isocode'], ['de', 'en', 'es', 'fr'])) {
        $options_pedia[$row['isocode']] = $row['original_name'].' ('.$row['english_name'].')';
    }
}

if ($service == 'google') {
    $selected_language = api_get_language_isocode(); //lang default is the course language
    $form = new FormValidator('form1', 'post', api_get_self().'?'.api_get_cidreq(), '', ['id' => 'form1']);
    $form->addHeader(get_lang('HelpText2Audio'));
    $form->addElement('hidden', 'text2voice_mode', 'google');
    $form->addElement('hidden', 'id', $document_id);
    $form->addElement('text', 'title', get_lang('Title'));
    $form->addElement('select', 'lang', get_lang('Language'), $options);
    $form->addElement('textarea', 'text', get_lang('InsertText2Audio'), ['id' => 'textarea_google']);
    $form->addButtonSave(get_lang('SaveMP3'));
    $defaults = [];
    $defaults['lang'] = $selected_language;
    $form->setDefaults($defaults);
    $form->display();
}

Display::display_footer();

/**
 * This function save a post into a file mp3 from google services.
 *
 * @param $filepath
 * @param $dir
 *
 * @author Juan Carlos Raña Trabado <herodoto@telefonica.net>
 *
 * @version january 2011, chamilo 1.8.8
 */
function downloadAudioGoogle($filepath, $dir)
{
    $location = 'create_audio.php?'.api_get_cidreq().'&id='.intval($_POST['id']).'&service=google';

    //security
    if (!isset($_POST['lang']) && !isset($_POST['text']) &&
        !isset($_POST['title']) && !isset($filepath) && !isset($dir)
    ) {
        echo '<script>window.location.href="'.$location.'"</script>';

        return;
    }

    $_course = api_get_course_info();
    $_user = api_get_user_info();

    $clean_title = trim($_POST['title']);
    $clean_text = trim($_POST['text']);
    if (empty($clean_title) || empty($clean_text)) {
        echo '<script>window.location.href="'.$location.'"</script>';

        return;
    }
    $clean_title = Security::remove_XSS($clean_title);
    $clean_title = Database::escape_string($clean_title);
    $clean_title = str_replace(' ', '_', $clean_title); //compound file names

    $clean_text = Security::remove_XSS($clean_text);
    $clean_lang = Security::remove_XSS($_POST['lang']);

    $extension = 'mp3';
    $audio_filename = $clean_title.'.'.$extension;
    $audio_title = str_replace('_', ' ', $clean_title);

    //prevent duplicates
    if (file_exists($filepath.'/'.$clean_title.'.'.$extension)) {
        $i = 1;
        while (file_exists($filepath.'/'.$clean_title.'_'.$i.'.'.$extension)) {
            $i++;
        }
        $audio_filename = $clean_title.'_'.$i.'.'.$extension;
        $audio_title = $clean_title.'_'.$i.'.'.$extension;
        $audio_title = str_replace('_', ' ', $audio_title);
    }

    $documentPath = $filepath.'/'.$audio_filename;
    $clean_text = api_replace_dangerous_char($clean_text);

    // adding the file
    // add new file to disk

    $proxySettings = api_get_configuration_value('proxy_settings');
    $key = api_get_configuration_value('translate_app_google_key');
    $url = "https://www.googleapis.com/language/translate/v2?key=$key&".$clean_lang."&target=$clean_lang&q=".urlencode($clean_text)."";

    if (empty($proxySettings)) {
        $content = @file_get_contents($url);
    } else {
        if (!empty($proxySettings['stream_context_create'])) {
            $context = stream_context_create($proxySettings['stream_context_create']);
        } else {
            $context = stream_context_create();
        }
        $content = file_get_contents($url, false, $context);
    }

    if (empty($content)) {
        Display::addFlash(Display::return_message(get_lang('GoogleTranslateApiReturnedEmptyAnswer'), 'error'));

        return;
    }

    file_put_contents(
        $documentPath,
        $content
    );

    // add document to database
    $current_session_id = api_get_session_id();
    $groupId = api_get_group_id();
    $groupInfo = GroupManager::get_group_properties($groupId);
    $relativeUrlPath = $dir;
    $doc_id = add_document(
        $_course,
        $relativeUrlPath.$audio_filename,
        'file',
        filesize($documentPath),
        $audio_title
    );
    api_item_property_update(
        $_course,
        TOOL_DOCUMENT,
        $doc_id,
        'DocumentAdded',
        $_user['user_id'],
        $groupInfo,
        null,
        null,
        null,
        $current_session_id
    );
    echo Display::return_message(get_lang('DocumentCreated'), 'confirm');
    echo '<script>window.location.href="'.$location.'"</script>';
}
