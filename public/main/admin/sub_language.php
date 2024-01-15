<?php
/* For licensing terms, see /license.txt */

/**
 * Script for sub-language administration.
 */
$cidReset = true;
$this_script = 'sub_language';
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
$htmlHeadXtra[] = '<script>
 $(function () {
    $(".save").on("click", function() {
        var $this = $(this);
        var buttonId = $this.attr("id");
        var textareaId = "txtid_" + buttonId.split("_")[1] + "_" + buttonId.split("_")[2];
        var content = $("#" + textareaId).val();
        var filename = $this.data("filename");
        var msgidEncoded = $this.data("msgid");

        $this.prop("disabled", true).text("'.get_lang('Loading').'...");

        try {
            $.post(
                "' . api_get_path(WEB_CODE_PATH) . 'admin/sub_language_ajax.inc.php",
                {
                    content: content,
                    filename: filename,
                    msgidEncoded: msgidEncoded
                },
                function(data) {
                    if (data.success) {
                        localStorage.setItem("redirectTo", textareaId);
                        window.location.reload(true);
                    } else {
                        alert("Error: " + (data.error || "'.get_lang('Error, try it again').'"));
                        $this.prop("disabled", false).text("'.get_lang('Save').'");
                    }
                },
                "json"
            );
        } catch (e) {
            console.error("Error to check JSON:", e);
            $this.prop("disabled", false).text("'.get_lang('Save').'");
        }
    });
});
</script>';

/**
 * Main code.
 */
// setting the name of the tool
$tool_name = get_lang('Create sub-language');
// setting breadcrumbs
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => 'languages.php', 'name' => get_lang('Chamilo Portal Languages')];

$sublanguage_folder_error = false;

if (isset($_GET['id']) && $_GET['id'] == strval(intval($_GET['id']))) {
    $language_name = SubLanguageManager::get_name_of_language_by_id($_GET['id']);
    $sub_language_name = SubLanguageManager::get_name_of_language_by_id($_GET['sub_language_id']);
    $all_data_of_language = SubLanguageManager::get_all_information_of_language($_GET['id']);
    $all_data_of_sublanguage = SubLanguageManager::get_all_information_of_language($_GET['sub_language_id']);
    if (true === SubLanguageManager::check_if_exist_language_by_id($_GET['id'])) {
        $language_id_exist = true;
    } else {
        $language_id_exist = false;
    }
} else {
    $language_name = '';
    $language_id_exist = false;
}

$intro = sprintf(get_lang('Define new terms for sub-language %s by searching some term, then save each translation by clicking the save button. You will then have to switch your own user language to see the new terms appear.'), strtolower($sub_language_name));

Display :: display_header($language_name);

echo '<div class="actions-message" >';
echo $intro;
echo '<br />';
printf(get_lang('Parent language: %s'), $language_name);
echo '</div>';
echo '<br />';
$txt_search_word = (!empty($_REQUEST['txt_search_word']) ? Security::remove_XSS($_REQUEST['txt_search_word']) : '');
$html = '<div style="float:left" class="actions">';
$html .= '<form style="float:left"  id="Searchlanguage" name="Searchlanguage" method="GET" action="sub_language.php">';
$html .= '&nbsp;'.get_lang('Original name').'&nbsp; :&nbsp;';

$html .= '<input name="id" type="hidden"  id="id" value="'.Security::remove_XSS($_REQUEST['id']).'" />';
$html .= '<input name="sub_language_id" type="hidden"  id="id" value="'.Security::remove_XSS($_REQUEST['sub_language_id']).'" />';
$html .= '<input name="txt_search_word" type="text" size="50"  id="txt_search_word" value="'.$txt_search_word.'" />';
$html .= "&nbsp;".'<button name="SubmitSearchLanguage" class="search" type="submit">'.get_lang('Search').'</button>';
$html .= '</form>';
$html .= '</div>';
echo $html;
echo '<br /><br /><br />';
if (!empty($sublanguage_folder_error)) {
    echo Display::return_message($sublanguage_folder_error, 'warning');
}
echo '<div id="div_message_information_id">&nbsp;</div>';

/**
 * Searches for a term in language files and returns results.
 *
 * @param string $term         The term to search for.
 * @param int    $subLanguageId The ID of the sub-language to search in.
 *
 * @author Julio Montoya
 *
 * @return array An array containing search results.
 */
function search_language_term($term, $subLanguageId)
{
    $translations = SubLanguageManager::searchTranslations($term, $subLanguageId);
    $listInfo = [];
    if (!empty($translations)) {
        $i = 0;
        foreach ($translations as $trans) {
            $keys = array_keys($trans);
            $firstTranslationKey = $keys[4];
            $secondTranslationKey = $keys[5];
            $langFile = "messages.$secondTranslationKey.po";
            $objText = Display::tag(
                'textarea',
                $trans[$secondTranslationKey],
                [
                    'rows' => 10,
                    'cols' => 40,
                    'name' => 'txt|'.$trans['phpVarName'].'|'.$i,
                    'id' => 'txtid_'.$i.'_'.$trans['phpVarName'],
                ]
            );
            $objButton = Display::button(
                'btn|'.$trans['phpVarName'].'|'.$i,
                get_lang('Save'),
                [
                    'class' => 'save btn btn--plain btn-sm',
                    'type' => 'button',
                    'id' => 'btnid_'.$i.'_'.$trans['phpVarName'],
                    'data-filename' => $langFile,
                    'data-msgid' => base64_encode($trans['variable']),
                ]
            );
            $listInfo[$i] = [
                $langFile,
                $trans['variable'],
                htmlentities($trans['en']),
                htmlentities($trans[$firstTranslationKey]),
                $objText,
                $objButton,
            ];

            $i++;
        }
    }

    return $listInfo;
}

// Allow see data in sort table
$list_info = [];
if (isset($_REQUEST['txt_search_word'])) {
    //@todo fix to accept a char with 1 char
    if (strlen(trim($_REQUEST['txt_search_word'])) > 2) {
        $list_info = search_language_term(
            $_REQUEST['txt_search_word'],
            $_GET['sub_language_id'],
        );
    }
}

$parameters = [
    'id' => intval($_GET['id']),
    'sub_language_id' => intval($_GET['sub_language_id']),
    'txt_search_word' => $txt_search_word,
];
$table = new SortableTableFromArrayConfig($list_info, 1, 20, 'data_info');
$table->set_additional_parameters($parameters);
$table->set_header(0, get_lang('Language file'));
$table->set_header(1, get_lang('Language variable'));
$table->set_header(2, get_lang('English name'));
$table->set_header(3, get_lang('Original name'));
$table->set_header(4, get_lang('Translation'), false);
$table->set_header(5, get_lang('Action'), false);
$table->setHideColumn(0);
$table->display();

Display :: display_footer();
