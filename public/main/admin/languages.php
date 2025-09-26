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

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\StateIcon;
use Chamilo\CoreBundle\Enums\ToolIcon;

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
        $_POST['visibility'] == strval(intval($_POST['visibility'])) && 0 == $_POST['visibility']
    ) {
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
$disabledLang = $msgLang ? (string) $_SESSION['disabled_languages'] : '';
unset($_SESSION['disabled_languages']);

$tbl_admin_languages = Database::get_main_table(TABLE_MAIN_LANGUAGE);
$tbl_settings_current = Database::get_main_table(TABLE_MAIN_SETTINGS);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$url = api_get_self();

switch ($action) {
    case 'makeunavailable':
        if (!empty($id)) {
            SubLanguageManager::make_unavailable_language($id);
            Display::addFlash(Display::return_message(get_lang('Update successful'), 'success'));
        }
        header("Location: $url");
        exit;
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
            if (false == SubLanguageManager::check_if_language_is_used((int) $language['id'])) {
                SubLanguageManager::make_unavailable_language((int) $language['id']);
            } else {
                if ((int) SubLanguageManager::get_platform_language_id() !== (int) $language['id']) {
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
    case 'make_unavailable_confirmed':
        $language_info = SubLanguageManager::get_all_information_of_language($id);
        if ($language_info && 1 == (int) $language_info['available']) {
            SubLanguageManager::make_unavailable_language($id);
            $platform_language = api_get_setting('platformLanguage');
            UserManager::update_all_user_languages($language_info['english_name'], $platform_language);
            Display::addFlash(
                Display::return_message(
                    get_lang('The language has been hidden. It will not be possible to use it until it becomes visible again.'),
                    'confirm'
                )
            );
            header("Location: $url");
            exit;
        }
        break;
}

if (isset($_POST['Submit']) && $_POST['Submit']) {
    $name = Security::remove_XSS($_POST['txt_name']);
    $postId = (int) $_POST['edit_id'];
    Database::update(
        $tbl_admin_languages,
        ['original_name' => $name],
        ['id = ?' => $postId]
    );
    if (isset($_POST['platformlanguage']) && '' != $_POST['platformlanguage']) {
        api_set_setting('platform_language', $_POST['platformlanguage'], null, 'language', api_get_current_access_url_id());
        header("Location: $url");
        exit;
    }
} elseif (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'makeavailable':
            if (!empty($_POST['id'])) {
                $ids = array_map('intval', (array) $_POST['id']);
                if ($ids) {
                    $sql = "UPDATE $tbl_admin_languages SET available='1' WHERE id IN ('".implode("','", $ids)."')";
                    Database::query($sql);
                }
                header("Location: $url");
                exit;
            }
            break;
        case 'makeunavailable':
            if (!empty($_POST['id'])) {
                $ids = array_map('intval', (array) $_POST['id']);
                if ($ids) {
                    $sql = "UPDATE $tbl_admin_languages SET available='0' WHERE id IN ('".implode("','", $ids)."')";
                    Database::query($sql);
                }
                header("Location: $url");
                exit;
            }
            break;
    }
}

$tool_name = get_lang('Chamilo Portal Languages');

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];

Display::addFlash(
    Display::return_message(
        get_lang('This tool manages the language selection menu on the login page. As a platform administrator you can decide which languages should be available for your users.')
    )
);

$jsMsg      = addslashes(get_lang('There are users currently using the following language. Please disable manually.'));
$jsConfirm  = addslashes(get_lang('Please confirm your choice'));
$jsLoading  = addslashes(get_lang('Loading'));
$jsUpdated  = addslashes(get_lang('Update successful'));
$selfUrl    = api_get_self();
$htmlHeadXtra[] = '
<style>
.lang-toggle-icon { font-size: 28px !important;width: 1em;height: 1em;line-height: 1em;display: inline-flex;align-items: center;justify-content: center;vertical-align: middle; }
.lang-toggle-icon::before { line-height: 1em; }
</style>
<script>
$(function () {
    var msgLang = ' . (int) $msgLang . ';
    var disabledLang = ' . json_encode($disabledLang) . ';

    if (msgLang === 1) {
        $("#id_content_message").html(
            "<div class=\\"warning-message alert alert-warning\\">' . $jsMsg . '<br />" + disabledLang + "</div>"
        );
    }

    $("#disable_all_except_default").on("click", function () {
        if (confirm("' . $jsConfirm . '")) {
            $.ajax({
                contentType: "application/x-www-form-urlencoded",
                beforeSend: function() {
                    $("#id_content_message").html(
                        "<div class=\\"warning-message alert alert-warning\\"><em class=\\"fa fa-refresh fa-spin\\"></em> ' . $jsLoading . '</div>"
                    );
                },
                type: "GET",
                url: ' . json_encode($selfUrl) . ',
                data: { action: "disable_all_except_default" },
                success: function() {
                    window.location.href = ' . json_encode($selfUrl) . ';
                }
            });
        }
        return false;
    });

    $(".make_visible_and_invisible").on("click", function (e) {
        e.preventDefault();
        var $link = $(this);
        var id = parseInt($link.data("id"), 10);
        var available = parseInt($link.data("available"), 10);
        var nextVisibility = available ? 0 : 1;
        var $icon = $("#imglinktool_" + id);

        // Optional: prevent double click while request is pending
        $link.prop("disabled", true).addClass("is-busy");

        $.ajax({
            type: "POST",
            url: "../admin/languages.php",
            data: { id: id, visibility: nextVisibility, sent_http_request: 1 },
            beforeSend: function () {
                $("#id_content_message").html(
                    "<div class=\\"warning-message alert alert-warning\\"><em class=\\"fa fa-refresh fa-spin\\"></em> ' . $jsLoading . '...</div>"
                );
            },
            success: function (response) {
                if (response === "set_visible" || response === "set_hidden") {
                    // Determine new availability
                    var nowAvailable = (response === "set_visible") ? 1 : 0;

                    // Persist new state and accessibility
                    $link
                        .data("available", nowAvailable)
                        .attr("aria-pressed", nowAvailable ? "true" : "false");

                    // Switch ONLY between the two MDI toggle classes
                    $icon
                        .removeClass("mdi-toggle-switch mdi-toggle-switch-off-outline")
                        .addClass(nowAvailable ? "mdi-toggle-switch" : "mdi-toggle-switch-off-outline");

                    // Feedback
                    $("#id_content_message").html("<div class=\\"alert alert-success\\">' . $jsUpdated . '</div>");
                } else if (typeof response === "string" && response.indexOf("confirm:") === 0) {
                    window.location.href = ' . json_encode($selfUrl) . ' + "?action=make_unavailable_confirmed&id=" + id;
                }
            },
            complete: function () {
                $link.prop("disabled", false).removeClass("is-busy");
            }
        });
    });
});
</script>';

Display::display_header($tool_name);

echo '<a id="disable_all_except_default" href="javascript:void(0)" class="btn btn--primary">
<em class="fa fa-eye"></em> '.get_lang('Disable all languages except the platform default').'</a><br /><br />';

$sql_select = "SELECT * FROM $tbl_admin_languages";
$result_select = Database::query($sql_select);
$currentLanguage = api_get_setting('language.platform_language');

$language_data = [];
while ($row = Database::fetch_array($result_select)) {
    $row_td = [];
    $row_td[] = $row['id'];
    $checked = '';

    if ('edit' == $action && $row['id'] == $id) {
        if ($row['english_name'] == api_get_setting('platformLanguage')) {
            $checked = ' checked="checked" ';
        }

        $row_td[] = '
            <input type="hidden" name="edit_id" value="'.$id.'" />
            <input type="text" name="txt_name" value="'.$row['original_name'].'" />
            <input type="checkbox" '.$checked.' name="platformlanguage" id="platformlanguage" value="'.$row['isocode'].'" />
            <label for="platformlanguage">'.sprintf(get_lang('%s as platform language'), $row['original_name']).'</label>
            <input class="btn btn--primary" type="submit" name="Submit" value="'.get_lang('Validate').'" />
            <a name="value" />';
    } else {
        $row_td[] = $row['original_name'];
    }

    $row_td[] = $row['english_name'].' ('.$row['isocode'].')';

    if ($row['isocode'] == $currentLanguage) {
        $setplatformlanguage = Display::getMdiIcon(
            ToolIcon::TRANSLATION,
            'ch-tool-icon',
            null,
            ICON_SIZE_SMALL,
            get_lang('Current portal\'s language')
        );
    } else {
        $confirmSet = addslashes(get_lang('Are you sure you want to set this language as the portal\'s default?'));
        $setplatformlanguage =
            "<a href=\"javascript:if (confirm('".$confirmSet."')) { location.href='".api_get_self()."?action=setplatformlanguage&id=".$row['id']."'; }\">".
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
        $row_td[] = '&nbsp;'.Display::getMdiIcon(StateIcon::ACTIVE, 'ch-tool-icon lang-toggle-icon', null, ICON_SIZE_SMALL, get_lang('Visible')).'&nbsp;'.
            "<a href='".api_get_self()."?action=edit&id=".$row['id']."#value'>".
            Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit'))."</a>
            &nbsp;".$setplatformlanguage.$allow_use_sub_language.$allow_add_term_sub_language.$allow_delete_sub_language;
    } else {
        $icon = ($row['available'] == 1) ? StateIcon::ACTIVE : StateIcon::INACTIVE;
        $tooltip = ($row['available'] == 1) ? get_lang('Make unavailable') : get_lang('Make available');
        $toggleClass = ($row['available'] == 1) ? 'mdi-toggle-switch' : 'mdi-toggle-switch-off-outline';

        $row_td[] = "<a class=\"make_visible_and_invisible\"
                id=\"linktool_".$row['id']."\"
                href=\"".api_get_self()."?action=".(($row['available']==1)?'makeunavailable':'makeavailable')."&id=".$row['id']."\"
                data-id=\"".$row['id']."\"
                data-available=\"".$row['available']."\"
                aria-pressed=\"".($row['available'] ? 'true' : 'false')."\">
                    <i id=\"imglinktool_".$row['id']."\" class=\"mdi ".$toggleClass." ch-tool-icon lang-toggle-icon\" title=\"".$tooltip."\" aria-hidden=\"true\"></i>
                </a>
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
