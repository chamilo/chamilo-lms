<?php
/* For licensing terms, see /license.txt */

/**
 * This page allows the platform admin to decide which languages should
 * be available in the language selection menu in the login page. This can be
 * useful for countries with more than one official language (like Belgium:
 * Dutch, French and German) or international organisations that are active in
 * a limited number of countries.
 *
 * @author Patrick Cool, main author
 * @author Roan EMbrechts, code cleaning
 */

use Chamilo\CoreBundle\Component\Utils\ActionIcon;
use Chamilo\CoreBundle\Component\Utils\ToolIcon;
use Chamilo\CoreBundle\Component\Utils\StateIcon;

// we are in the admin area so we do not need a course id
$cidReset = true;

// include global script
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
$action = isset($_GET['action']) ? $_GET['action'] : null;

//Ajax request
if (isset($_POST['sent_http_request'])) {
    if (isset($_POST['visibility']) &&
        $_POST['visibility'] == strval(intval($_POST['visibility'])) && 0 == $_POST['visibility']) {
        if (isset($_POST['id']) && $_POST['id'] == strval(intval($_POST['id']))) {
            if (false == SubLanguageManager::check_if_language_is_used($_POST['id'])) {
                SubLanguageManager::make_unavailable_language($_POST['id']);
                echo 'set_hidden';
            } else {
                echo 'confirm:'.intval($_POST['id']);
            }
        }
    }
    if (isset($_POST['visibility']) &&
        $_POST['visibility'] == strval(intval($_POST['visibility'])) && 1 == $_POST['visibility']
    ) {
        if (isset($_POST['id']) && $_POST['id'] == strval(intval($_POST['id']))) {
            SubLanguageManager::make_available_language($_POST['id']);
            echo 'set_visible';
        }
    }
    exit;
}

$msgLang = isset($_SESSION['disabled_languages']) ? 1 : 0;
$disabledLang = isset($_SESSION['disabled_languages']) ? $_SESSION['disabled_languages'] : null;

$htmlHeadXtra[] = '<script>
 $(function () {
    var msgLang = '.$msgLang.';
    var disabledLang = "'.$disabledLang.'"

    if (msgLang == 1) {
        $("#id_content_message").html("<div class=\"warning-message alert alert-warning\">'.get_lang('There are users currently using the following language. Please disable manually.').' <br /> " + disabledLang + "</div");
    }

    $("#disable_all_except_default").click(function () {
        if(confirm("'.get_lang('Please confirm your choice').'")) {
            $.ajax({
                contentType: "application/x-www-form-urlencoded",
                beforeSend: function(myObject) {
                    $("#id_content_message").html("<div class=\"warning-message alert alert-warning\"><em class=\"fa fa-refresh fa-spin\"></em>  '.get_lang('Loading').'</div>");
                },
                type: "GET",
                url: "../admin/languages.php",
                data: "action=disable_all_except_default",
                success: function(datos) {
                    window.location.href = "'.api_get_self().'";
                }
            });
        }

        return false;
    });

    $(".make_visible_and_invisible").click(function(e) {
        e.preventDefault();

        var id_link_tool = $(this).attr("id");
        var link_id = id_link_tool.split("linktool_")[1];
        var currentIcon = $("#imglinktool_" + link_id);

        $.ajax({
            type: "POST",
            url: "../admin/languages.php",
            data: { id: link_id, visibility: currentIcon.hasClass("mdi-toggle-switch") ? 0 : 1, sent_http_request: 1 },
            beforeSend: function() {
                $("#id_content_message").html("<div class=\'warning-message alert alert-warning\'><em class=\'fa fa-refresh fa-spin\'></em>'.get_lang('Loading'). '...</div>");
            },
            success: function(response) {
                if (response === "set_visible" || response === "set_hidden") {
                    var newIconClass = (response === "set_visible") ? "mdi-toggle-switch" : "mdi-toggle-switch-off";
                    var oldIconClass = (response === "set_visible") ? "mdi-toggle-switch-off" : "mdi-toggle-switch";

                    currentIcon.removeClass(oldIconClass).addClass(newIconClass);
                }
            }
        });
    });

 });
</script>';

// unset the msg session variable
unset($_SESSION['disabled_languages']);

// setting the table that is needed for the styles management (there is a check if it exists later in this code)
$tbl_admin_languages = Database::get_main_table(TABLE_MAIN_LANGUAGE);
$tbl_settings_current = Database::get_main_table(TABLE_MAIN_SETTINGS);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$url = api_get_self();

// we change the availability
switch ($action) {
    case 'makeunavailable':
        if (!empty($id)) {
            SubLanguageManager::make_unavailable_language($id);
            Display::addFlash(Display::return_message(get_lang('Update successful'), 'success'));
        }
        header("Location: $url");
        exit;
        break;
    case 'makeavailable':
        if (!empty($id)) {
            SubLanguageManager::make_available_language($id);
            Display::addFlash(Display::return_message(get_lang('Update successful'), 'success'));
        }
        header("Location: $url");
        exit;
    case 'setplatformlanguage':
        if (!empty($id)) {
            SubLanguageManager::set_platform_language($id);
            Display::addFlash(Display::return_message(get_lang('Update successful'), 'success'));
        }
        header("Location: $url");
        exit;
    case 'disable_all_except_default':
        $allLanguages = SubLanguageManager::getAllLanguages();
        $failedDisabledLanguages = '';
        $checkFailed = false;
        foreach ($allLanguages as $language) {
            if (false == SubLanguageManager::check_if_language_is_used($language['id'])) {
                SubLanguageManager::make_unavailable_language($language['id']);
            } else {
                if (intval(SubLanguageManager::get_platform_language_id()) !== intval($language['id'])) {
                    $failedDisabledLanguages .= ' - '.$language['english_name'].'<br />';
                    $checkFailed = true;
                }
            }
        }

        if ($checkFailed) {
            $_SESSION['disabled_languages'] = $failedDisabledLanguages;
        }
        Display::addFlash(Display::return_message(get_lang('Update successful'), 'success'));
        header("Location: $url");
        exit;
        break;
    case 'make_unavailable_confirmed':
        $language_info = SubLanguageManager::get_all_information_of_language($id);
        if (1 == $language_info['available']) {
            SubLanguageManager::make_unavailable_language($id);
            $platform_language = api_get_setting('platformLanguage');
            UserManager::update_all_user_languages($language_info['english_name'], $platform_language);
            Display::addFlash(Display::return_message(get_lang('The language has been hidden. It will not be possible to use it until it becomes visible again.'), 'confirm'));
            header("Location: $url");
            exit;
        }
        break;
}

if (isset($_POST['Submit']) && $_POST['Submit']) {
    // changing the name
    $name = Database::escape_string($_POST['txt_name']);
    $postId = (int) $_POST['edit_id'];
    $sql = "UPDATE $tbl_admin_languages SET original_name='$name'
            WHERE id='$postId'";
    $result = Database::query($sql);
    // changing the Platform language
    if ($_POST['platformlanguage'] && '' != $_POST['platformlanguage']) {
        api_set_setting('platformLanguage', $_POST['platformlanguage'], null, null, api_get_current_access_url_id());
        header("Location: $url");
        exit;
    }
} elseif (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'makeavailable':
            if (count($_POST['id']) > 0) {
                $ids = [];
                foreach ($_POST['id'] as $index => $id) {
                    $ids[] = intval($id);
                }
                $sql = "UPDATE $tbl_admin_languages SET available='1' WHERE id IN ('".implode("','", $ids)."')";
                Database::query($sql);
                header("Location: $url");
                exit;
            }
            break;
        case 'makeunavailable':
            if (count($_POST['id']) > 0) {
                $ids = [];
                foreach ($_POST['id'] as $index => $id) {
                    $ids[] = intval($id);
                }
                $sql = "UPDATE $tbl_admin_languages SET available='0' WHERE id IN ('".implode("','", $ids)."')";
                Database::query($sql);
                header("Location: $url");
                exit;
            }
            break;
    }
}

// setting the name of the tool
$tool_name = get_lang('Chamilo Portal Languages');

// setting breadcrumbs
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];

// displaying the explanation for this tool
Display::addFlash(Display::return_message(get_lang('Chamilo Portal LanguagesExplanation'), 'normal'));

// including the header file (which includes the banner itself)
Display::display_header($tool_name);

echo '<a id="disable_all_except_default" href="javascript:void(0)" class="btn btn--primary">
<em class="fa fa-eye"></em> '.get_lang('Disable all languages except the platform default').'</a><br /><br />';

// selecting all the languages
$sql_select = "SELECT * FROM $tbl_admin_languages";
$result_select = Database::query($sql_select);
$currentLanguage = api_get_setting('language.platform_language');

// the table data
$language_data = [];
while ($row = Database::fetch_array($result_select)) {
    $row_td = [];
    $row_td[] = $row['id'];
    $checked = '';
    // the first column is the original name of the language OR a form containing the original name
    if ('edit' == $action && $row['id'] == $id) {
        if ($row['english_name'] == api_get_setting('platformLanguage')) {
            $checked = ' checked="checked" ';
        }

        $row_td[] = '
            <input type="hidden" name="edit_id" value="'.$id.'" />
            <input type="text" name="txt_name" value="'.$row['original_name'].'" />
            <input type="checkbox" '.$checked.'name="platformlanguage" id="platformlanguage" value="'.$row['english_name'].'" />
            <label for="platformlanguage">'.$row['original_name'].' '.get_lang('as platformlanguage').'</label>
            <input type="submit" name="Submit" value="'.get_lang('Validate').'" />
            <a name="value" />';
    } else {
        $row_td[] = $row['original_name'];
    }

    // the second column
    $row_td[] = $row['english_name'].' ('.$row['isocode'].')';

    if ($row['isocode'] == $currentLanguage) {
        $setplatformlanguage = Display::getMdiIcon(ToolIcon::TRANSLATION, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Current portal\'s language'));
    } else {
        $setplatformlanguage =
            "<a href=\"javascript:if (confirm('".addslashes(get_lang('Are you sure you want to set this language as the portal\'s default?'))."')) { location.href='".api_get_self()."?action=setplatformlanguage&id=".$row['id']."'; }\">".
            Display::getMdiIcon(ToolIcon::TRANSLATION, 'ch-tool-icon-disabled', null, ICON_SIZE_SMALL, get_lang('Set language as default'))."</a>";
    }

    $allow_delete_sub_language = null;
    $allow_add_term_sub_language = null;
    $allow_use_sub_language = '';
    $allow_add_term_sub_language = '';
    if ('true' === api_get_setting('allow_use_sub_language')) {
        $verified_if_is_sub_language = SubLanguageManager::check_if_language_is_sub_language($row['id']);
        if (false === $verified_if_is_sub_language) {
            $verified_if_is_father = SubLanguageManager::check_if_language_is_father($row['id']);
            $allow_use_sub_language = "&nbsp;<a href='sub_language_add.php?action=definenewsublanguage&id=".$row['id']."'>".
                Display::getMdiIcon(ToolIcon::TRANSLATION, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Create sub-language'))."</a>";
            if (true === $verified_if_is_father) {
                $allow_add_term_sub_language = '';
            } else {
                $allow_add_term_sub_language = '';
            }
        } else {
            $allow_use_sub_language = '';
            $all_information_of_sub_language = SubLanguageManager::get_all_information_of_language($row['id']);
            $allow_add_term_sub_language = "&nbsp;<a href='sub_language.php?action=registersublanguage&id=".Security::remove_XSS($all_information_of_sub_language['parent_id'])."&sub_language_id=".Security::remove_XSS($row['id'])."'>".Display::getMdiIcon(ActionIcon::VIEW_DETAILS, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Add terms to the sub-language'))."</a>";
            $allow_delete_sub_language = "&nbsp;<a href='sub_language_add.php?action=deletesublanguage&id=".Security::remove_XSS($all_information_of_sub_language['parent_id'])."&sub_language_id=".Security::remove_XSS($row['id'])."'>".Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete sub-language'))."</a>";
        }
    }

    if ($row['isocode'] == $currentLanguage) {
        $row_td[] = Display::getMdiIcon(StateIcon::ACTIVE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Visible')).
            "<a href='".api_get_self()."?action=edit&id=".$row['id']."#value'>".
            Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit'))."</a>
                     &nbsp;".$setplatformlanguage.$allow_use_sub_language.$allow_add_term_sub_language.$allow_delete_sub_language;
    } else {
        $action = ($row['available'] == 1) ? 'makeunavailable' : 'makeavailable';
        $icon = ($row['available'] == 1) ? StateIcon::ACTIVE : StateIcon::INACTIVE;
        $tooltip = ($row['available'] == 1) ? get_lang('Make unavailable') : get_lang('Make available');

        $row_td[] = "<a class=\"make_visible_and_invisible\" id=\"linktool_".$row['id']."\" href='".api_get_self()."?action=$action&id=".$row['id']."'>".
            Display::getMdiIcon($icon, 'ch-tool-icon', null, ICON_SIZE_SMALL, $tooltip, ['id' => 'imglinktool_'.$row['id']])."</a>
            <a href='".api_get_self()."?action=edit&id=".$row['id']."#value'>".Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit'))."</a>
            &nbsp;".$setplatformlanguage.$allow_use_sub_language.$allow_add_term_sub_language.$allow_delete_sub_language;
    }
    $language_data[] = $row_td;
}

$table = new SortableTableFromArrayConfig($language_data, 1, count($language_data));
$table->set_header(0, '');
$table->set_header(1, get_lang('Original name'));
$table->set_header(2, get_lang('English name'));
//$table->set_header(3, get_lang('Chamilo folder'));
$table->set_header(4, get_lang('Properties'));
$form_actions = [];
$form_actions['makeavailable'] = get_lang('Make available');
$form_actions['makeunavailable'] = get_lang('Make unavailable');
$table->set_form_actions($form_actions);
echo '<div id="id_content_message">&nbsp;</div>';
$table->display();

Display :: display_footer();
