<?php
/* For licensing terms, see /license.txt */
/**
 * Script for sub-language administration.
 *
 * @package chamilo.admin.sub_language
 */
$cidReset = true;
$this_script = 'sub_language';
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
$htmlHeadXtra[] = '<script>
 $(function () {
    $(".save").click(function() {
        var button_name=$(this).attr("name");
        var button_array=button_name.split("|");
        var button_name=button_array[1];
        var file_id=button_array[2];
        var is_variable_language="$"+button_name;

        var is_new_language = $("#txtid_"+file_id+"_"+button_name).val();
        if (is_new_language == undefined) {
            is_new_language="_";
        }
        if (is_new_language.length>0 && is_new_language!="_" && file_id!="" && button_name!="") {
            $.ajax({
                contentType: "application/x-www-form-urlencoded",
                beforeSend: function(myObject) {
                    $("#div_message_information_id").html("<div class=\"alert alert-info\"><img src=\'../inc/lib/javascript/indicator.gif\' /></div>");
                },
                type: "POST",
                url: "../admin/sub_language_ajax.inc.php",
                data: {
                    \'new_language\': is_new_language,
                    \'variable_language\': is_variable_language,
                    \'file_id\': file_id,
                    \'id\': '.intval($_REQUEST['id']).',
                    \'sub\': '.intval($_REQUEST['sub_language_id']).',
                    \'sub_language_id\': '.intval($_REQUEST['sub_language_id']).'
                },
                success: function(datos) {
                    if (datos == "1") {
                        $("#div_message_information_id").html(\''.Display::return_message(get_lang('TheNewWordHasBeenAdded'), 'success').'\');
                    } else {
                        $("#div_message_information_id").html("<div class=\"alert alert-warning\">" + datos +"</div>");
                    }
                }
            });
        } else {
            $("#div_message_information_id").html(\''.Display::return_message(get_lang('FormHasErrorsPleaseComplete'), 'error').'\');
        }
    });
});
</script>';

/**
 * Main code.
 */
// setting the name of the tool
$tool_name = get_lang('CreateSubLanguage');
// setting breadcrumbs
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];
$interbreadcrumb[] = ['url' => 'languages.php', 'name' => get_lang('PlatformLanguages')];

$sublanguage_folder_error = false;

if (isset($_GET['id']) && $_GET['id'] == strval(intval($_GET['id']))) {
    $language_name = SubLanguageManager::get_name_of_language_by_id($_GET['id']);
    $sub_language_name = SubLanguageManager::get_name_of_language_by_id($_GET['sub_language_id']);
    $all_data_of_language = SubLanguageManager::get_all_information_of_language($_GET['id']);
    $all_data_of_sublanguage = SubLanguageManager::get_all_information_of_language($_GET['sub_language_id']);
    $sub_language_file = api_get_path(SYS_LANG_PATH).$all_data_of_sublanguage['dokeos_folder'];

    if (!file_exists($sub_language_file) || !is_writable($sub_language_file)) {
        $sublanguage_folder_error = $sub_language_file.' '.get_lang('IsNotWritable');
    }
    if (SubLanguageManager::check_if_exist_language_by_id($_GET['id']) === true) {
        $language_id_exist = true;
    } else {
        $language_id_exist = false;
    }
} else {
    $language_name = '';
    $language_id_exist = false;
}

$intro = sprintf(get_lang('RegisterTermsOfSubLanguageForX'), strtolower($sub_language_name));
$path_folder = api_get_path(SYS_LANG_PATH).$all_data_of_language['dokeos_folder'];

if (!is_dir($path_folder) || strlen($all_data_of_language['dokeos_folder']) == 0) {
    api_not_allowed(true);
}

Display::display_header($language_name);

echo '<div class="actions-message" >';
echo $intro;
echo '<br />';
printf(get_lang('ParentLanguageX'), $language_name);
echo '</div>';
echo '<br />';
$txt_search_word = (!empty($_REQUEST['txt_search_word']) ? Security::remove_XSS($_REQUEST['txt_search_word']) : '');
$html = '<div style="float:left" class="actions">';
$html .= '<form style="float:left"  id="Searchlanguage" name="Searchlanguage" method="GET" action="sub_language.php">';
$html .= '&nbsp;'.get_lang('OriginalName').'&nbsp; :&nbsp;';

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
 * @param $term The term to search
 * @param bool $search_in_variable     The search will include the variable definition of the term
 * @param bool $search_in_english      The search will include the english language variables
 * @param bool $search_in_parent       The search will include the parent language variables of the sub language
 * @param bool $search_in_sub_language The search will include the sub language variables
 *
 * @author Julio Montoya
 *
 * @return array
 */
function search_language_term(
    $term,
    $search_in_variable = true,
    $search_in_english = true,
    $search_in_parent = true,
    $search_in_sub_language = true
) {
    //These the $_REQUEST['id'] and the $_REQUEST['sub_language_id'] variables are process in global.inc.php (LOAD LANGUAGE FILES SECTION)
    /*
        These 4 arrays are set in global.inc.php with the condition that will be load from sub_language.php or sub_language_ajax.inc.php
        $english_language_array
        $parent_language_array
        $sub_language_array
        $language_files_to_load
    */
    global $language_files_to_load, $sub_language_array, $english_language_array, $parent_language_array;
    $language_files_to_load_keys = array_flip($language_files_to_load);
    $array_to_search = $parent_language_array;
    $list_info = [];
    $term = '/'.Security::remove_XSS(trim($_REQUEST['txt_search_word'])).'/i';
    //@todo optimize this foreach
    foreach ($language_files_to_load as $lang_file) {
        //searching in parent language of the sub language
        if ($search_in_parent) {
            $variables = $parent_language_array[$lang_file];
            foreach ($variables as $parent_name_variable => $parent_variable_value) {
                //arrays are avoided
                if (is_array($parent_variable_value)) {
                    continue;
                }
                $founded = false;
                // searching the item in the parent tool
                if (preg_match($term, $parent_variable_value) !== 0) {
                    $founded = true;
                }
                if ($founded) {
                    //loading variable from the english array
                    $sub_language_name_variable = isset($sub_language_array[$lang_file][$parent_name_variable])
                        ? $sub_language_array[$lang_file][$parent_name_variable]
                        : '';
                    //loading variable from the english array
                    $english_name_variable = $english_language_array[$lang_file][$parent_name_variable];

                    //config buttons
                    /*if (strlen($english_name_variable)>1500) {
                        $size =20;
                    } else {
                        $size =4;
                    }*/

                    $obj_text = Display::tag(
                        'textarea',
                        $sub_language_name_variable,
                        [
                            'rows' => 10,
                            'cols' => 40,
                            'name' => 'txt|'.$parent_name_variable.'|'.$language_files_to_load_keys[$lang_file],
                            'id' => 'txtid_'.$language_files_to_load_keys[$lang_file].'_'.$parent_name_variable,
                        ]
                    );
                    $obj_button = Display::button(
                        'btn|'.$parent_name_variable.'|'.$language_files_to_load_keys[$lang_file],
                        get_lang('Save'),
                        [
                            'class' => 'save  btn btn-default btn-sm',
                            'type' => 'button',
                            'id' => 'btnid_'.$parent_name_variable,
                        ]
                    );

                    $list_info[$parent_name_variable] = [
                        $lang_file.'.inc.php',
                        $parent_name_variable,
                        htmlentities($english_name_variable),
                        htmlentities($parent_variable_value),
                        $obj_text,
                        $obj_button,
                    ];
                }
            }
        }

        //search in english
        if ($search_in_english || $search_in_variable) {
            $variables = $english_language_array[$lang_file];
            foreach ($variables as $name_variable => $variable_value) {
                if (isset($list_info[$name_variable])) {
                    continue;
                }

                if (is_array($variable_value)) {
                    continue;
                }

                if (is_array($variable_value)) {
                    echo $lang_file;
                }
                $founded = false;
                if ($search_in_english && $search_in_variable) {
                    // searching the item in the parent tool
                    if (preg_match($term, $variable_value) !== 0 || preg_match($term, $name_variable) !== 0) {
                        $founded = true;
                    }
                } else {
                    if ($search_in_english) {
                        if (preg_match($term, $variable_value) !== 0) {
                            $founded = true;
                        }
                    } else {
                        if (preg_match($term, $name_variable) !== 0) {
                            $founded = true;
                        }
                    }
                }

                if ($founded) {
                    //loading variable from the english array
                    $sub_language_name_variable = null;
                    if (isset($sub_language_array[$lang_file][$name_variable])) {
                        $sub_language_name_variable = $sub_language_array[$lang_file][$name_variable];
                    }
                    $parent_variable_value = null;
                    if (isset($parent_language_array[$lang_file][$name_variable])) {
                        $parent_variable_value = $parent_language_array[$lang_file][$name_variable];
                    }
                    //config buttons
                    $obj_text = Display::tag(
                        'textarea',
                        $sub_language_name_variable,
                        [
                            'rows' => 10,
                            'cols' => 40,
                            'name' => 'txt|'.$name_variable.'|'.$language_files_to_load_keys[$lang_file],
                            'id' => 'txtid_'.$language_files_to_load_keys[$lang_file].'_'.$name_variable,
                        ]
                    );
                    $obj_button = Display::button(
                        'btn|'.$name_variable.'|'.$language_files_to_load_keys[$lang_file],
                        get_lang('Save'),
                        [
                            'class' => 'save btn btn-default btn-sm',
                            'type' => 'button',
                            'id' => 'btnid_'.$name_variable,
                        ]
                    );

                    //loading variable from the english array
                    $english_name_variable = $english_language_array[$lang_file][$name_variable];

                    $list_info[] = [
                        $lang_file.'.inc.php',
                        $name_variable,
                        htmlentities($english_name_variable),
                        htmlentities($parent_variable_value),
                        $obj_text,
                        $obj_button,
                    ];
                }
            }
        }

        // Search in sub language
        if ($search_in_sub_language) {
            $variables = $sub_language_array[$lang_file];
            foreach ($variables as $name_variable => $variable_value) {
                if (is_array($parent_variable_value)) {
                    continue;
                }

                if (is_array($variable_value)) {
                    continue;
                }

                $founded = false;
                // searching the item in the parent tool
                if (preg_match($term, $variable_value) !== 0) {
                    $founded = true;
                }
                if ($founded) {
                    //loading variable from the english array
                    $sub_language_name_variable = isset($sub_language_array[$lang_file][$name_variable])
                        ? $sub_language_array[$lang_file][$name_variable]
                        : '';
                    $parent_variable_value = isset($parent_language_array[$lang_file][$name_variable])
                        ? $parent_language_array[$lang_file][$name_variable]
                        : '';
                    //config buttons
                    $obj_text = Display::tag(
                        'textarea',
                        $sub_language_name_variable,
                        [
                            'rows' => 10,
                            'cols' => 40,
                            'name' => 'txt|'.$name_variable.'|'.$language_files_to_load_keys[$lang_file],
                            'id' => 'txtid_'.$language_files_to_load_keys[$lang_file].'_'.$name_variable,
                        ]
                    );
                    $obj_button = Display::button(
                        'btn|'.$name_variable.'|'.$language_files_to_load_keys[$lang_file],
                        get_lang('Save'),
                        [
                            'class' => 'save btn btn-default btn-sm',
                            'type' => 'button',
                            'id' => 'btnid_'.$name_variable,
                        ]
                    );

                    //loading variable from the english array
                    $english_name_variable = $english_language_array[$lang_file][$name_variable];
                    $list_info[] = [$lang_file.'.inc.php',
                        $name_variable,
                        $english_name_variable,
                        $parent_variable_value, $obj_text, $obj_button, ];
                }
            }
        }
    }

    $list_info = array_unique_dimensional($list_info);

    return $list_info;
}

// Allow see data in sort table
$list_info = [];
if (isset($_REQUEST['txt_search_word'])) {
    //@todo fix to accept a char with 1 char
    if (strlen(trim($_REQUEST['txt_search_word'])) > 2) {
        $list_info = search_language_term(
            $_REQUEST['txt_search_word'],
            true,
            true,
            true,
            true
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
$table->set_header(0, get_lang('LanguageFile'));
$table->set_header(1, get_lang('LanguageVariable'));
$table->set_header(2, get_lang('EnglishName'));
$table->set_header(3, get_lang('OriginalName'));
$table->set_header(4, get_lang('Translation'), false);
$table->set_header(5, get_lang('Action'), false);
$table->setHideColumn(0);
$table->display();

Display::display_footer();
