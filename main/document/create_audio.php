<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This file allows creating audio files from a text.
 *
 * @package chamilo.document
 *
 * @author Juan Carlos Raña Trabado
 * @since 8/January/2011
 * TODO:clean all file
 */

require_once __DIR__.'/../inc/global.inc.php';
$_SESSION['whereami'] = 'document/createaudio';
$this_section = SECTION_COURSES;

$nameTools = get_lang('CreateAudio');

api_protect_course_script();
api_block_anonymous_users();

$groupRights = Session::read('group_member_with_upload_rights');
$groupId = api_get_group_id();

if (api_get_setting('enabled_text2audio') === 'false') {
    api_not_allowed(true);
}

$document_data = DocumentManager::get_document_data_by_id(
    $_REQUEST['id'],
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
    $interbreadcrumb[] = array(
        "url" => "../group/group_space.php?".api_get_cidreq(),
        "name" => get_lang('GroupSpace')
    );
    $group = GroupManager:: get_group_properties($groupId);
    $path = explode('/', $dir);
    if ('/'.$path[1] != $group['directory']) {
        api_not_allowed(true);
    }
}

$interbreadcrumb[] = array(
    "url" => "./document.php?curdirpath=".urlencode($dir)."&".api_get_cidreq(),
    "name" => get_lang('Documents')
);

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


/*	Header */
Event::event_access_tool(TOOL_DOCUMENT);

$display_dir = $dir;
if (isset ($group)) {
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
    $interbreadcrumb[] = array('url' => $url_dir, 'name' => $url_to_who);
    $dir_acum .= $dir_array[$i].'/';
}

$service = isset($_GET['service']) ? $_GET['service'] : 'pediaphon';

Display:: display_header($nameTools, 'Doc');

echo '<div class="actions">';
echo '<a href="document.php?id='.$document_id.'">'.
    Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('DocumentsOverview'), '',
        ICON_SIZE_MEDIUM).'</a>';

echo '<a href="create_audio.php?'.api_get_cidreq().'&id='.$document_id.'&service=pediaphon">'.
    Display::return_icon('pediaphon.png', get_lang('Pediaphon'), '', ICON_SIZE_MEDIUM).'</a>';

echo '<a href="create_audio.php?'.api_get_cidreq().'&id='.$document_id.'&service=google">'.
    Display::return_icon('google.png', get_lang('GoogleAudio'), '', ICON_SIZE_MEDIUM).'</a>';
echo '</div>';
?>
    <!-- javascript and styles for textareaCounter-->
    <script>
        var info;
        $(document).ready(function () {
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

if (isset($_POST['text2voice_mode']) && $_POST['text2voice_mode'] == 'google') {
    downloadMP3_google($filepath, $dir);
} elseif (isset($_POST['text2voice_mode']) && $_POST['text2voice_mode'] == 'pediaphon') {
    downloadMP3_pediaphon($filepath, $dir);
}

$tbl_admin_languages = Database::get_main_table(TABLE_MAIN_LANGUAGE);
$sql_select = "SELECT * FROM $tbl_admin_languages";
$result_select = Database::query($sql_select);

$options = $options_pedia = array();
$selected_language = null;

while ($row = Database::fetch_array($result_select)) {
    $options[$row['isocode']] = $row['original_name'].' ('.$row['english_name'].')';
    if (in_array($row['isocode'], array('de', 'en', 'es', 'fr'))) {
        $options_pedia[$row['isocode']] = $row['original_name'].' ('.$row['english_name'].')';
    }
}

if ($service == 'google') {
    $selected_language = api_get_language_isocode(); //lang default is the course language

    $form = new FormValidator('form1', 'post', api_get_self().'?'.api_get_cidreq(), '', array('id' => 'form1'));
    $form->addHeader(get_lang('HelpText2Audio'));
    $form->addElement('hidden', 'text2voice_mode', 'google');
    $form->addElement('hidden', 'id', $document_id);
    $form->addElement('text', 'title', get_lang('Title'));
    $form->addElement('select', 'lang', get_lang('Language'), $options);
    $form->addElement('textarea', 'text', get_lang('InsertText2Audio'), array('id' => 'textarea_google'));
    $form->addButtonSave(get_lang('SaveMP3'));
    $defaults = array();
    $defaults['lang'] = $selected_language;
    $form->setDefaults($defaults);
    $form->display();
}

if ($service == 'pediaphon') {
    //lang default is a default message
    $selected_language = "defaultmessage";
    $options_pedia['defaultmessage'] = get_lang('FirstSelectALanguage');
    $options['defaultmessage'] = get_lang('FirstSelectALanguage');

    $form = new FormValidator('form2', 'post', api_get_self().'?'.api_get_cidreq(), '', array('id' => 'form2'));
    $form->addHeader(get_lang('HelpText2Audio'));
    $form->addElement('hidden', 'text2voice_mode', 'pediaphon');
    $form->addElement('hidden', 'id', $document_id);
    $form->addElement('text', 'title', get_lang('Title'));
    $form->addSelect('lang', get_lang('Language'), $options_pedia, array('class' => 'lang'));
    $form->addSelect('voices', get_lang('Voice'), array(get_lang('FirstSelectALanguage')), array('id' => 'voices'));
    $speed_options = array();
    $speed_options['1'] = get_lang('Normal');
    $speed_options['0.75'] = get_lang('GoFaster');
    $speed_options['0.8'] = get_lang('Fast');
    $speed_options['1.2'] = get_lang('Slow');
    $speed_options['1.6'] = get_lang('SlowDown');

    $form->addElement('select', 'speed', get_lang('Speed'), $speed_options, array());
    $form->addElement('textarea', 'text', get_lang('InsertText2Audio'), array('id' => 'textarea_pediaphon'));
    //echo Display::return_icon('info3.gif', get_lang('HelpPediaphon'), array('align' => 'absmiddle', 'hspace' => '3px'), false);
    $form->addButtonSave(get_lang('SaveMP3'));
    $defaults = array();
    $defaults['lang'] = $selected_language;
    $form->setDefaults($defaults);
    $form->display();
    ?>
    <!-- javascript form name form2 update voices -->
    <script>
        var langslist = document.form2.lang
        var voiceslist = document.form2.voices
        var voices = new Array()

        <!--German -->
        voices['de'] = ["<?php echo get_lang('Female').' (de1)'; ?>|de1", "<?php echo get_lang('Male').' (de2)'; ?>|de2", "<?php echo get_lang('Female').' (de3)'; ?>|de3", "<?php echo get_lang('Male').' (de4)'; ?>|de4", "<?php echo get_lang('Female').' (de5)'; ?>|de5", "<?php echo get_lang('Male').' (de6)'; ?>|de6", "<?php echo get_lang('Female').' (de7)'; ?>|de7", "<?php echo get_lang('Female').' (de8 HQ)'; ?>|de8"]

        <!--English -->
        voices['en'] = ["<?php echo get_lang('Male').' (en1)'; ?>|en1", "<?php echo get_lang('Male').' (en2 HQ)'; ?>|en2", "<?php echo get_lang('Female').' (us1)'; ?>|us1", "<?php echo get_lang('Male').' (us2)'; ?>|us2", "<?php echo get_lang('Male').' (us3)'; ?>|us3", "<?php echo get_lang('Female').'(us4 HQ)'; ?>|us4"]

        <!--Spanish -->
        voices['es'] = ["<?php echo get_lang('Male').' (es5 HQ)'; ?>|es5"]

        <!--French -->
        voices['fr'] = ["<?php echo get_lang('Female').' (fr8 HQ)'; ?>|fr8"]

        $(document).ready(function () {
            $('.lang').on('change', function () {
                var selectedvoicegroup = this.value;
                $('#voices').empty();
                for (i = 0; i < voices[selectedvoicegroup].length; i++) {
                    var value = voices[selectedvoicegroup][i].split("|")[1];
                    var text = voices[selectedvoicegroup][i].split("|")[0];
                    var newOption = $('<option value="' + value + '">' + text + '</option>');
                    $('#voices').append(newOption);
                    $('#voices').selectpicker('refresh');
                }
            });
        });
    </script>
    <?php
}//end pediaphon

Display:: display_footer();

/**
 * This function save a post into a file mp3 from google services
 *
 * @param $filepath
 * @param $dir
 * @author Juan Carlos Raña Trabado <herodoto@telefonica.net>
 * @version january 2011, chamilo 1.8.8
 */
function downloadMP3_google($filepath, $dir)
{
    $location = 'create_audio.php?'.api_get_cidreq().'&id='.intval($_POST['id']).'&service=google';

    //security
    if (!isset($_POST['lang']) && !isset($_POST['text']) && !isset($_POST['title']) && !isset($filepath) && !isset($dir)) {
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
        $content = file_get_contents($url);
    } else {
        $context = stream_context_create($proxySettings);
        $content = file_get_contents($url, false, $context);
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
    //return to location
    echo '<script>window.location.href="'.$location.'"</script>';
}

/**
 * This function save a post into a file mp3 from pediaphon services
 *
 * @param $filepath
 * @param $dir
 * @author Juan Carlos Raña Trabado <herodoto@telefonica.net>
 * @version january 2011, chamilo 1.8.8
 */
function downloadMP3_pediaphon($filepath, $dir)
{
    $location = 'create_audio.php?'.api_get_cidreq().'&id='.intval($_POST['id']).'&service=pediaphon';
    //security
    if (!isset($_POST['lang']) && !isset($_POST['text']) && !isset($_POST['title']) && !isset($filepath) && !isset($dir)) {
        echo '<script>window.location.href="'.$location.'"</script>';
        return;
    }
    $_course = api_get_course_info();
    $_user = api_get_user_info();
    $clean_title = trim($_POST['title']);
    $clean_title = Database::escape_string($clean_title);
    $clean_text = trim($_POST['text']);
    $clean_voices = Security::remove_XSS($_POST['voices']);
    if (empty($clean_title) || empty($clean_text) || empty($clean_voices)) {
        echo '<script>window.location.href="'.$location.'"</script>';

        return;
    }
    $clean_title = Security::remove_XSS($clean_title);
    $clean_title = Database::escape_string($clean_title);
    $clean_title = str_replace(' ', '_', $clean_title); //compound file names
    $clean_text = Security::remove_XSS($clean_text);
    $clean_lang = Security::remove_XSS($_POST['lang']);
    $clean_speed = Security::remove_XSS($_POST['speed']);

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

    //adding the file

    if ($clean_lang == 'de') {
        $url_pediaphon = 'http://www.pediaphon.org/~bischoff/radiopedia/sprich_multivoice.cgi';
        $find_t2v = '/http\:\/\/www\.pediaphon\.org\/\~bischoff\/radiopedia\/mp3\/(.*)\.mp3\"/';
    } else {
        $url_pediaphon = 'http://www.pediaphon.org/~bischoff/radiopedia/sprich_multivoice_'.$clean_lang.'.cgi'; //en, es, fr
        $find_t2v = '/http\:\/\/www\.pediaphon\.org\/\~bischoff\/radiopedia\/mp3\/'.$clean_lang.'\/(.*)\.mp3\"/';
    }

    $data = "stimme=".$clean_voices."&inputtext=".$clean_text."&speed=".$clean_speed."&go=speak";
    $opts = array(
        'http' =>
            array(
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                "Content-Length: ".strlen($data)."\r\n",
                'content' => $data
            )
    );

    $context = stream_context_create($opts);
    // Download the whole HTML page
    $previous_returntext2voice = file_get_contents($url_pediaphon, false, $context);

    //extract the audio file path
    $search_source = preg_match($find_t2v, $previous_returntext2voice, $hits);
    $souce_end = substr($hits[0], 0, -1);
    //download file
    $returntext2voice = file_get_contents($souce_end);
    //save file
    $f = @file_put_contents($documentPath, $returntext2voice);
    if ($f === false && !empty($php_errormsg)) {
        error_log($php_errormsg);
    }
    //add document to database
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
    //return to location
    echo '<script>window.location.href="'.$location.'"</script>';
}
