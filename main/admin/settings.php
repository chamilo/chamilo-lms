<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * With this tool you can easily adjust non critical configuration settings.
 * Non critical means that changing them will not result in a broken campus.
 *
 * @author Patrick Cool
 * @author Julio Montoya - Multiple URL site
 * @package chamilo.admin
 */

// Resetting the course id.
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';
require_once 'settings.lib.php';

// Setting the section (for the tabs).
$this_section = SECTION_PLATFORM_ADMIN;
$_SESSION['this_section'] = $this_section;

// Access restrictions.
api_protect_admin_script();

 // Submit stylesheets.
if (isset($_POST['save']) && isset($_GET['category']) && $_GET['category'] === 'Stylesheets') {
    storeStylesheets();
    Display::addFlash(Display::return_message(get_lang('Saved')));
}

// Settings to avoid
$settings_to_avoid = array(
    'use_session_mode' => 'true',
    'gradebook_enable' => 'false',
    // ON by default - now we have this option when  we create a course
    'example_material_course_creation' => 'true'
);

$convert_byte_to_mega_list = array(
    'dropbox_max_filesize',
    'message_max_upload_filesize',
    'default_document_quotum',
    'default_group_quotum'
);

if (isset($_POST['style'])) {
    Display::$preview_style = $_POST['style'];
}

// Database table definitions.
$table_settings_current = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);

// Setting breadcrumbs.
$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));

// Setting the name of the tool.
$tool_name = get_lang('PlatformConfigSettings');
if (empty($_GET['category'])) {
    $_GET['category'] = 'Platform';
}
$watermark_deleted = false;
if (isset($_GET['delete_watermark'])) {
    $watermark_deleted = PDF::delete_watermark();
    Display::addFlash(Display::return_message(get_lang('FileDeleted')));
}

if (isset($_GET['action']) && $_GET['action'] == 'delete_grading') {
    $id = intval($_GET['id']);
    api_delete_setting_option($id);
}

$form_search = new FormValidator(
    'search_settings',
    'get',
    api_get_self(),
    null,
    array(),
    FormValidator::LAYOUT_INLINE
);
$form_search->addElement('text', 'search_field', null, array(
    'id' => 'search_field',
    'aria-label' => get_lang('Search')
));
$form_search->addElement('hidden', 'category', 'search_setting');
$form_search->addButtonSearch(get_lang('Search'), 'submit_button');
$form_search->setDefaults(
    array('search_field' => isset($_REQUEST['search_field']) ? $_REQUEST['search_field'] : null)
);

$form_search_html = $form_search->returnForm();

$url_id = api_get_current_access_url_id();

$settings = null;
/**
 * @param string $category
 * @return array
 */
function get_settings($category = '')
{
    $url_id = api_get_current_access_url_id();
    $settings_by_access_list = array();

    if ($url_id == 1) {
        $settings = api_get_settings($category, 'group', $url_id);
    } else {
        $url_info = api_get_access_url($url_id);
        if ($url_info['active'] == 1) {
            $categoryToSearch = $category;
            if ($category == 'search_setting') {
                $categoryToSearch = '';
            }
            // The default settings of Chamilo
            $settings = api_get_settings($categoryToSearch, 'group', 1, 0);
            // The settings that are changeable from a particular site.
            $settings_by_access = api_get_settings($categoryToSearch, 'group', $url_id, 1);

            foreach ($settings_by_access as $row) {
                if (empty($row['variable'])) {
                    $row['variable'] = 0;
                }
                if (empty($row['subkey'])) {
                    $row['subkey'] = 0;
                }
                if (empty($row['category'])) {
                    $row['category'] = 0;
                }

                // One more validation if is changeable.
                if ($row['access_url_changeable'] == 1) {
                    $settings_by_access_list[$row['variable']][$row['subkey']][$row['category']] = $row;
                } else {
                    $settings_by_access_list[$row['variable']][$row['subkey']][$row['category']] = array();
                }
            }
        }
    }

    if (isset($category) && $category == 'search_setting') {
        if (!empty($_REQUEST['search_field'])) {
            $settings = searchSetting($_REQUEST['search_field']);
        }
    }

    return array(
        'settings' => $settings,
        'settings_by_access_list' => $settings_by_access_list
    );
}

// Build the form.
if (!empty($_GET['category']) &&
    !in_array($_GET['category'], array('Plugins', 'stylesheets', 'Search'))
) {
    $my_category = isset($_GET['category']) ? $_GET['category'] : null;
    $settings_array = get_settings($my_category);
    $settings = $settings_array['settings'];
    $settings_by_access_list = $settings_array['settings_by_access_list'];
    $form = generateSettingsForm($settings, $settings_by_access_list);

    if ($form->validate()) {
        $values = $form->exportValues();

        $mark_all = false;
        $un_mark_all = false;

        if (api_is_multiple_url_enabled()) {
            if (isset($values['buttons_in_action_right']) &&
                isset($values['buttons_in_action_right']['mark_all'])
            ) {
                $mark_all = true;
            }

            if (isset($values['buttons_in_action_right']) &&
                isset($values['buttons_in_action_right']['unmark_all'])
            ) {
                $un_mark_all = true;
            }
        }

        if ($mark_all || $un_mark_all) {
            if (api_is_global_platform_admin()) {
                $locked_settings = api_get_locked_settings();
                foreach ($values as $key => $value) {
                    if (!in_array($key, $locked_settings)) {
                        $changeable = 0;
                        if ($mark_all) {
                            $changeable = 1;
                        }

                        $params = array('variable = ?' => array($key));
                        $data = api_get_settings_params($params);

                        if (!empty($data)) {
                            foreach ($data as $item) {
                                $params = array(
                                    'id' => $item['id'],
                                    'access_url_changeable' => $changeable,
                                );
                                api_set_setting_simple($params);
                            }
                        }
                    }
                }
                //Reload settings
                $settings_array = get_settings($my_category);
                $settings = $settings_array['settings'];
                $settings_by_access_list = $settings_array['settings_by_access_list'];
                $form = generateSettingsForm(
                    $settings,
                    $settings_by_access_list
                );
            }
        }
        if (!empty($_FILES['pdf_export_watermark_path'])) {
            $pdf_export_watermark_path = $_FILES['pdf_export_watermark_path'];
        }

        if (isset($pdf_export_watermark_path) && !empty($pdf_export_watermark_path['name'])) {
            $pdf_export_watermark_path_result = PDF::upload_watermark(
                $pdf_export_watermark_path['name'],
                $pdf_export_watermark_path['tmp_name']
            );
            if ($pdf_export_watermark_path_result) {
                Display::addFlash(Display::return_message(get_lang('UplUploadSucceeded')));
            } else {
                $message = get_lang('UplUnableToSaveFile').' '.get_lang('Folder').': '.api_get_path(SYS_CODE_PATH).'default_course_document/images';
                Display::addFlash(Display::return_message($message), 'warning');
            }
            unset($update_values['pdf_export_watermark_path']);
        }

        // Set true for allow_message_tool variable if social tool is actived
        foreach ($convert_byte_to_mega_list as $item) {
            if (isset($values[$item])) {
                $values[$item] = round($values[$item] * 1024 * 1024);
            }
        }

        if (isset($values['allow_social_tool']) && $values['allow_social_tool'] == 'true') {
            $values['allow_message_tool'] = 'true';
        }

        foreach ($settings as $item) {
            $key = $item['variable'];
            if ($key === 'prevent_multiple_simultaneous_login') {
                Session::write('first_user_login', 1);
            }
            if (in_array($key, $settings_to_avoid)) {
                continue;
            }
            if ($key == 'search_field' || $key == 'submit_fixed_in_bottom') {
                continue;
            }
            $key = Database::escape_string($key);
            $sql = "UPDATE $table_settings_current
                    SET selected_value = 'false'
                    WHERE
                        variable = '".$key."' AND
                        access_url = ".intval($url_id)." AND
                        type IN ('checkbox', 'radio') ";
            $res = Database::query($sql);
        }

        // Save the settings.
        $keys = array();

        foreach ($values as $key => $value) {
            if (strcmp($key, 'MAX_FILE_SIZE') === 0) {
                continue;
            }
            if (in_array($key, $settings_to_avoid)) {
                continue;
            }
            // Avoid form elements which have nothing to do with settings
            if ($key == 'search_field' || $key == 'submit_fixed_in_bottom') {
                continue;
            }

            // Treat gradebook values in separate function.
            //if (strpos($key, 'gradebook_score_display_custom_values') === false) {
            if (!is_array($value)) {
                $old_value = api_get_setting($key);
                switch ($key) {
                    case 'header_extra_content':
                        file_put_contents(api_get_home_path().'header_extra_content.txt', $value);
                        $value = api_get_home_path().'header_extra_content.txt';
                        break;
                    case 'footer_extra_content':
                        file_put_contents(api_get_home_path().'footer_extra_content.txt', $value);
                        $value = api_get_home_path().'footer_extra_content.txt';
                        break;
                    case 'InstitutionUrl':
                    case 'course_validation_terms_and_conditions_url':
                        // URL validation for some settings.
                        $value = trim(Security::remove_XSS($value));
                        if ($value != '') {
                            // Here we accept absolute URLs only.
                            if (strpos($value, '://') === false) {
                                $value = 'http://'.$value;
                            }
                            if (!api_valid_url($value, true)) {
                                // If the new (non-empty) URL value is invalid, then the old URL value stays.
                                $value = $old_value;
                            }
                        }
                        // If the new URL value is empty, then it will be stored (i.e. the setting will be deleted).
                        break;
                    case 'emailAdministrator':
                        // Validation against e-mail address for some settings.
                        $value = trim(Security::remove_XSS($value));
                        if ($value != '' && !api_valid_email($value)) {
                            // If the new (non-empty) e-mail address is invalid, then the old e-mail address stays.
                            // If the new e-mail address is empty, then it will be stored (i.e. the setting will be deleted).
                            $value = $old_value;
                        }
                        break;
                }
                if ($old_value != $value) {
                    $keys[] = $key;
                }
                $result = api_set_setting($key, $value, null, null, $url_id);
            } else {
                $sql = "SELECT subkey FROM $table_settings_current
                        WHERE variable = '$key'";
                $res = Database::query($sql);

                while ($row_subkeys = Database::fetch_array($res)) {
                    // If subkey is changed:
                    if ((isset($value[$row_subkeys['subkey']]) && api_get_setting($key, $row_subkeys['subkey']) == 'false') ||
                        (!isset($value[$row_subkeys['subkey']]) && api_get_setting($key, $row_subkeys['subkey']) == 'true')
                    ) {
                        $keys[] = $key;
                        break;
                    }
                }

                foreach ($value as $subkey => $subvalue) {
                    $result = api_set_setting($key, 'true', $subkey, null, $url_id);
                }
            }
        }

        // Add event configuration settings category to the system log.
        $user_id = api_get_user_id();
        $category = $_GET['category'];
        Event::addEvent(
            LOG_CONFIGURATION_SETTINGS_CHANGE,
            LOG_CONFIGURATION_SETTINGS_CATEGORY,
            $category,
            api_get_utc_datetime(),
            $user_id
        );

        // Add event configuration settings variable to the system log.
        if (is_array($keys) && count($keys) > 0) {
            foreach ($keys as $variable) {
                if (in_array($key, $settings_to_avoid)) {
                    continue;
                }
                Event::addEvent(
                    LOG_CONFIGURATION_SETTINGS_CHANGE,
                    LOG_CONFIGURATION_SETTINGS_VARIABLE,
                    $variable,
                    api_get_utc_datetime(),
                    $user_id
                );
            }
        }

        Display::addFlash(Display::return_message(get_lang('Updated')));

        header('Location: '.api_get_self().'?category='.Security::remove_XSS($my_category));
        exit;
    }
}
$htmlHeadXtra[] = '<script>    
    var hide_icon = "'.api_get_path(WEB_IMG_PATH).'/icons/32/shared_setting_na.png";
    var show_icon = "'.api_get_path(WEB_IMG_PATH).'/icons/32/shared_setting.png";
    var url       = "'.api_get_path(WEB_AJAX_PATH).'admin.ajax.php?a=update_changeable_setting";

    $(function() {
        $(".share_this_setting").on("click", function() {
            var my_img = $(this).find("img");
            var link = $(this);
            $.ajax({
                url: url,
                data: {
                    changeable: $(this).attr("data_status"),
                    id: $(this).attr("data_to_send")
                },
                success: function(data) {
                    if (data == 1) {
                        if (link.attr("data_status") == 1) {
                            my_img.attr("src", show_icon);
                            link.attr("data_status", 0);
                        } else {
                            my_img.attr("src", hide_icon);
                            link.attr("data_status", 1);
                        }
                    }
                }
            });
        });
    });
</script>';

// The action images.
$action_images['platform'] = 'platform.png';
$action_images['course'] = 'course.png';
$action_images['session'] = 'session.png';
$action_images['tools'] = 'tools.png';
$action_images['user'] = 'user.png';
$action_images['gradebook'] = 'gradebook.png';
$action_images['ldap'] = 'ldap.png';
$action_images['cas'] = 'cas.png';
$action_images['security'] = 'security.png';
$action_images['languages'] = 'languages.png';
$action_images['tuning'] = 'tuning.png';
$action_images['templates'] = 'template.png';
$action_images['search'] = 'search.png';
$action_images['editor'] = 'html_editor.png';
$action_images['timezones'] = 'timezone.png';
$action_images['extra'] = 'wizard.png';
$action_images['tracking'] = 'statistics.png';
$action_images['gradebook'] = 'gradebook.png';
$action_images['search'] = 'search.png';
$action_images['stylesheets'] = 'stylesheets.png';
$action_images['templates'] = 'template.png';
$action_images['plugins'] = 'plugins.png';
$action_images['shibboleth'] = 'shibboleth.png';
$action_images['facebook'] = 'facebook.png';
$action_images['crons'] = 'crons.png';
$action_images['webservices'] = 'webservices.png';

$action_array = array();
$resultcategories = array();

$resultcategories[] = array('category' => 'Platform');
$resultcategories[] = array('category' => 'Course');
$resultcategories[] = array('category' => 'Session');
$resultcategories[] = array('category' => 'Languages');
$resultcategories[] = array('category' => 'User');
$resultcategories[] = array('category' => 'Tools');
$resultcategories[] = array('category' => 'Editor');
$resultcategories[] = array('category' => 'Security');
$resultcategories[] = array('category' => 'Tuning');
$resultcategories[] = array('category' => 'Gradebook');
$resultcategories[] = array('category' => 'Timezones');
$resultcategories[] = array('category' => 'Tracking');
$resultcategories[] = array('category' => 'Search');
$resultcategories[] = array('category' => 'Stylesheets');
$resultcategories[] = array('category' => 'Templates');
$resultcategories[] = array('category' => 'Plugins');
$resultcategories[] = array('category' => 'LDAP');
$resultcategories[] = array('category' => 'CAS');
$resultcategories[] = array('category' => 'Shibboleth');
$resultcategories[] = array('category' => 'Facebook');
$resultcategories[] = ['category' => 'Crons'];
$resultcategories[] = ['category' => 'WebServices'];

foreach ($resultcategories as $row) {
    $url = array();
    $url['url'] = api_get_self()."?category=".$row['category'];
    $url['content'] = Display::return_icon(
        $action_images[strtolower($row['category'])],
        api_ucfirst(get_lang($row['category'])),
        '',
        ICON_SIZE_MEDIUM
    );
    if (strtolower($row['category']) == strtolower($_GET['category'])) {
        $url['active'] = true;
    }
    $action_array[] = $url;
}

ob_start();
if (!empty($_GET['category'])) {
    switch ($_GET['category']) {
        case 'Regions':
            handleRegions();
            break;
        case 'Plugins':
            // Displaying the extensions: Plugins.
            // This will be available to all the sites (access_urls).
            $securityToken = isset($_GET['sec_token']) ? Security::remove_XSS($_GET['sec_token']) : null;
            if (isset($_POST['submit_dashboard_plugins']) && Security::check_token($securityToken)) {
                Security::clear_token();
                $affected_rows = DashboardManager::store_dashboard_plugins($_POST);
                if ($affected_rows) {
                    // add event to system log
                    $user_id = api_get_user_id();
                    $category = $_GET['category'];
                    Event::addEvent(
                        LOG_CONFIGURATION_SETTINGS_CHANGE,
                        LOG_CONFIGURATION_SETTINGS_CATEGORY,
                        $category,
                        api_get_utc_datetime(),
                        $user_id
                    );
                    echo Display::return_message(get_lang('DashboardPluginsUpdatedSuccessfully'), 'confirmation');
                }
            }
            echo '<script>
                $(function(){
                    $("#tabs").tabs();
                });
                </script>';
            echo '<div id="tabs">';
            echo '<ul>';
            echo '<li><a href="#tabs-1">'.get_lang('Plugins').'</a></li>';
            echo '<li><a href="#tabs-2">'.get_lang('DashboardPlugins').'</a></li>';
            echo '<li><a href="#tabs-3">'.get_lang('ConfigureExtensions').'</a></li>';
            echo '</ul>';

            echo '<div id="tabs-1">';
            handlePlugins();
            echo '</div>';

            echo '<div id="tabs-2">';
            DashboardManager::handle_dashboard_plugins();
            echo '</div>';

            echo '<div id="tabs-3">';
            handleExtensions();
            echo '</div>';
            echo '</div>';
            break;
        case 'Stylesheets':
            // Displaying the extensions: Stylesheets.
            handleStylesheets();
            break;
        case 'Search':
            handleSearch();
            break;
        case 'Templates':
            handleTemplates();
            break;
        case 'search_setting':
            if (isset($_REQUEST['search_field'])) {
                searchSetting($_REQUEST['search_field']);
                $form->display();
            }
            break;
        default:
            if (isset($form)) {
                $form->display();
            }
    }
}
$content = ob_get_clean();

// Including the header (banner).
Display :: display_header($tool_name);
echo Display::actions($action_array);
echo '<br />';
echo $form_search_html;
echo $content;

Display :: display_footer();
