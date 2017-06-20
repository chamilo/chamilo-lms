<?php
/* For licensing terms, see /license.txt */

use Symfony\Component\Filesystem\Filesystem;
use Chamilo\CoreBundle\Component\Utils\ChamiloApi;

/**
 * Library of the settings.php file
 *
 * @author Julio Montoya <gugli100@gmail.com>
 * @author Guillaume Viguier <guillaume@viguierjust.com>
 *
 * @since Chamilo 1.8.7
 * @package chamilo.admin
 */

define('CSS_UPLOAD_PATH', api_get_path(SYS_APP_PATH).'Resources/public/css/themes/');

/**
 * This function allows easy activating and inactivating of regions
 * @author Julio Montoya <gugli100@gmail.com> Beeznest 2012
 */
function handleRegions()
{
    if (isset($_POST['submit_plugins'])) {
        storeRegions();
        // Add event to the system log.
        $user_id = api_get_user_id();
        $category = $_GET['category'];
        Event::addEvent(
            LOG_CONFIGURATION_SETTINGS_CHANGE,
            LOG_CONFIGURATION_SETTINGS_CATEGORY,
            $category,
            api_get_utc_datetime(),
            $user_id
        );
        echo Display::return_message(get_lang('SettingsStored'), 'confirmation');
    }

    $plugin_obj = new AppPlugin();
    $possible_plugins  = $plugin_obj->read_plugins_from_path();
    $installed_plugins = $plugin_obj->get_installed_plugins();

    echo '<form name="plugins" method="post" action="'.api_get_self().'?category='.Security::remove_XSS($_GET['category']).'">';
    echo '<table class="data_table">';
    echo '<tr>';
    echo '<th width="400px">';
    echo get_lang('Plugin');
    echo '</th><th>';
    echo get_lang('Regions');
    echo '</th>';
    echo '</th>';
    echo '</tr>';

    /* We display all the possible plugins and the checkboxes */

    $plugin_region_list = array();
    $my_plugin_list = $plugin_obj->get_plugin_regions();
    foreach ($my_plugin_list as $plugin_item) {
        $plugin_region_list[$plugin_item] = $plugin_item;
    }

    // Removing course tool
    unset($plugin_region_list['course_tool_plugin']);

    foreach ($installed_plugins as $pluginName) {
        $plugin_info_file = api_get_path(SYS_PLUGIN_PATH).$pluginName.'/plugin.php';

        if (file_exists($plugin_info_file)) {
            $plugin_info = array();
            require $plugin_info_file;
            if (isset($_GET['name']) && $_GET['name'] === $pluginName) {
                echo '<tr class="row_selected">';
            } else {
                echo '<tr>';
            }
            echo '<td>';
            echo '<h4>'.$plugin_info['title'].' <small>v'.$plugin_info['version'].'</small></h4>';
            echo '<p>'.$plugin_info['comment'].'</p>';
            echo '</td><td>';
            $selected_plugins = $plugin_obj->get_areas_by_plugin($pluginName);

            $region_list = [];

            $isAdminPlugin = isset($plugin_info['is_admin_plugin']) && $plugin_info['is_admin_plugin'];
            $isCoursePlugin = isset($plugin_info['is_course_plugin']) && $plugin_info['is_course_plugin'];

            if (!$isAdminPlugin && !$isCoursePlugin) {
                $region_list = $plugin_region_list;
            } else {
                if ($isAdminPlugin) {
                    $region_list['menu_administrator'] = 'menu_administrator';
                }
                if ($isCoursePlugin) {
                    $region_list['course_tool_plugin'] = 'course_tool_plugin';
                }
            }
            echo Display::select(
                'plugin_'.$pluginName.'[]',
                $region_list,
                $selected_plugins,
                array('multiple' => 'multiple', 'style' => 'width:500px'),
                true,
                get_lang('None')
            );
            echo '</td></tr>';
        }
    }
    echo '</table>';
    echo '<br />';
    echo '<button class="btn btn-success" type="submit" name="submit_plugins">'.get_lang('EnablePlugins').'</button></form>';
}

function handleExtensions()
{
    echo Display::page_subheader(get_lang('ConfigureExtensions'));
    echo '<a class="btn btn-success" href="configure_extensions.php?display=ppt2lp" role="button">'.get_lang('Ppt2lp').'</a>';

}

/**
 * This function allows easy activating and inactivating of plugins
 * @todo: a similar function needs to be written to activate or inactivate additional tools.
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @author Julio Montoya <gugli100@gmail.com> Beeznest 2012
 */
function handlePlugins()
{
    $plugin_obj = new AppPlugin();
    $token = Security::get_token();
    if (isset($_POST['submit_plugins'])) {
        storePlugins();
        // Add event to the system log.
        $user_id = api_get_user_id();
        $category = $_GET['category'];
        Event::addEvent(
            LOG_CONFIGURATION_SETTINGS_CHANGE,
            LOG_CONFIGURATION_SETTINGS_CATEGORY,
            $category,
            api_get_utc_datetime(),
            $user_id
        );
        echo Display::return_message(get_lang('SettingsStored'), 'confirmation');
    }

    $all_plugins = $plugin_obj->read_plugins_from_path();
    $installed_plugins = $plugin_obj->get_installed_plugins();

    // Plugins NOT installed
    echo Display::page_subheader(get_lang('Plugins'));
    echo '<form class="form-horizontal" name="plugins" method="post" action="'.api_get_self().'?category='.Security::remove_XSS($_GET['category']).'&sec_token='.$token.'">';
    echo '<table class="data_table">';
    echo '<tr>';
    echo '<th width="20px">';
    echo get_lang('Action');
    echo '</th><th>';
    echo get_lang('Description');
    echo '</th>';
    echo '</tr>';

    /*$plugin_list = array();
    $my_plugin_list = $plugin_obj->get_plugin_regions();
    foreach($my_plugin_list as $plugin_item) {
        $plugin_list[$plugin_item] = $plugin_item;
    }*/

    foreach ($all_plugins as $pluginName) {
        $plugin_info_file = api_get_path(SYS_PLUGIN_PATH).$pluginName.'/plugin.php';
        if (file_exists($plugin_info_file)) {
            $plugin_info = array();
            require $plugin_info_file;

            if (in_array($pluginName, $installed_plugins)) {
                echo '<tr class="row_selected">';
            } else {
                echo '<tr>';
            }
            echo '<td>';
            //Checkbox
            if (in_array($pluginName, $installed_plugins)) {
                echo '<input type="checkbox" name="plugin_'.$pluginName.'[]" checked="checked">';

            } else {
                echo '<input type="checkbox" name="plugin_'.$pluginName.'[]">';
            }
            echo '</td><td>';
            echo '<h4>'.$plugin_info['title'].' <small>v '.$plugin_info['version'].'</small></h4>';
            echo '<p>'.$plugin_info['comment'].'</p>';
            echo '<p>'.get_lang('Author').': '.$plugin_info['author'].'</p>';

            echo '<div class="btn-group">';
            if (in_array($pluginName, $installed_plugins)) {
                echo Display::url('<em class="fa fa-cogs"></em> '.get_lang('Configure'), 'configure_plugin.php?name='.$pluginName, array('class' => 'btn btn-default'));
                echo Display::url('<em class="fa fa-th-large"></em> '.get_lang('Regions'), 'settings.php?category=Regions&name='.$pluginName, array('class' => 'btn btn-default'));
            }

            if (file_exists(api_get_path(SYS_PLUGIN_PATH).$pluginName.'/readme.txt')) {
                echo Display::url(
                    "<em class='fa fa-file-text-o'></em> readme.txt",
                    api_get_path(WEB_PLUGIN_PATH).$pluginName."/readme.txt",
                    [
                        'class' => 'btn btn-default ajax',
                        'data-title' => $plugin_info['title'],
                        'data-size' => 'lg',
                        '_target' => '_blank'
                    ]
                );
            }

            $readmeFile = api_get_path(SYS_PLUGIN_PATH).$pluginName.'/README.md';
            if (file_exists($readmeFile)) {
                echo Display::url(
                    "<em class='fa fa-file-text-o'></em> README.md",
                    api_get_path(WEB_AJAX_PATH).'plugin.ajax.php?a=md_to_html&plugin='.$pluginName,
                    [
                        'class' => 'btn btn-default ajax',
                        'data-title' => $plugin_info['title'],
                        'data-size' => 'lg',
                        '_target' => '_blank'
                    ]
                );
            }

            echo '</div>';
            echo '</td></tr>';
        }

    }
    echo '</table>';

    echo '<div class="form-actions bottom_actions">';
    echo '<button class="btn btn-success" type="submit" name="submit_plugins">'.
            get_lang('EnablePlugins').'</button>';
    echo '</div>';
    echo '</form>';
}

/**
 * This function allows the platform admin to choose the default stylesheet
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @author Julio Montoya <gugli100@gmail.com>, Chamilo
 */
function handleStylesheets()
{
    $is_style_changeable = isStyleChangeable();
    $allowedFileTypes = ['png'];

    $form = new FormValidator(
        'stylesheet_upload',
        'post',
        'settings.php?category=Stylesheets#tabs-3'
    );
    $form->addElement(
        'text',
        'name_stylesheet',
        get_lang('NameStylesheet'),
        array('size' => '40', 'maxlength' => '40')
    );
    $form->addRule(
        'name_stylesheet',
        get_lang('ThisFieldIsRequired'),
        'required'
    );
    $form->addElement(
        'file',
        'new_stylesheet',
        get_lang('UploadNewStylesheet')
    );
    $allowed_file_types = getAllowedFileTypes();

    $form->addRule(
        'new_stylesheet',
        get_lang('InvalidExtension').' ('.implode(',', $allowed_file_types).')',
        'filetype',
        $allowed_file_types
    );
    $form->addRule(
        'new_stylesheet',
        get_lang('ThisFieldIsRequired'),
        'required'
    );
    $form->addButtonUpload(get_lang('Upload'), 'stylesheet_upload');

    $show_upload_form = false;
    $urlId = api_get_current_access_url_id();

    if (!is_writable(CSS_UPLOAD_PATH)) {
        echo Display::return_message(
            CSS_UPLOAD_PATH.get_lang('IsNotWritable'),
            'error',
            false
        );
    } else {
        // Uploading a new stylesheet.
        if ($urlId == 1) {
            $show_upload_form = true;
        } else {
            if ($is_style_changeable) {
                $show_upload_form = true;
            }
        }
    }

    // Stylesheet upload.
    if (isset($_POST['stylesheet_upload'])) {
        if ($form->validate()) {
            $values = $form->exportValues();
            $picture_element = $form->getElement('new_stylesheet');
            $picture = $picture_element->getValue();
            $result = uploadStylesheet($values, $picture);

            // Add event to the system log.
            $user_id = api_get_user_id();
            $category = $_GET['category'];
            Event::addEvent(
                LOG_CONFIGURATION_SETTINGS_CHANGE,
                LOG_CONFIGURATION_SETTINGS_CATEGORY,
                $category,
                api_get_utc_datetime(),
                $user_id
            );

            if ($result) {
                echo Display::return_message(get_lang('StylesheetAdded'));
            }
        }
    }

    // Current style.
    $selected = $currentStyle = api_get_setting('stylesheets');
    $styleFromDatabase = api_get_settings_params_simple(
        ['variable = ? AND access_url = ?' => ['stylesheets', api_get_current_access_url_id()]]
    );
    if ($styleFromDatabase) {
        $selected = $currentStyle = $styleFromDatabase['selected_value'];
    }

    if (isset($_POST['preview'])) {
        $selected = $currentStyle = Security::remove_XSS($_POST['style']);
    }

    $themeDir = Template::getThemeDir($selected);
    $dir = api_get_path(SYS_PUBLIC_PATH).'css/'.$themeDir.'/images/';
    $url = api_get_path(WEB_CSS_PATH).'/'.$themeDir.'/images/';
    $logoFileName = 'header-logo.png';
    $newLogoFileName = 'header-logo-custom'.api_get_current_access_url_id().'.png';
    $webPlatformLogoPath = ChamiloApi::getWebPlatformLogoPath($selected);

    $logoForm = new FormValidator(
        'logo_upload',
        'post',
        'settings.php?category=Stylesheets#tabs-2'
    );

    $logoForm->addHtml(
        Display::return_message(
            sprintf(
                get_lang('TheLogoMustBeSizeXAndFormatY'),
                '250 x 70',
                'PNG'
            ),
            'info'
        )
    );

    if ($webPlatformLogoPath !== null) {
        $logoForm->addLabel(
            get_lang('CurrentLogo'),
            '<img id="header-logo-custom" src="'.$webPlatformLogoPath.'?'.time().'">'
        );
    }
    $logoForm->addFile('new_logo', get_lang('UpdateLogo'));
    if ($is_style_changeable) {
        $logoGroup = [
            $logoForm->addButtonUpload(get_lang('Upload'), 'logo_upload', true),
            $logoForm->addButtonCancel(get_lang('Reset'), 'logo_reset', true)
        ];

        $logoForm->addGroup($logoGroup);
    }

    if (isset($_POST['logo_reset'])) {
        if (is_file($dir.$newLogoFileName)) {
            unlink($dir.$newLogoFileName);
            echo Display::return_message(get_lang('ResetToTheOriginalLogo'));
            echo '<script>'
                . '$("#header-logo").attr("src","'.$url.$logoFileName.'");'
            . '</script>';
        }
    } elseif (isset($_POST['logo_upload'])) {
        $logoForm->addRule('new_logo', get_lang('InvalidExtension').' ('.implode(',', $allowedFileTypes).')', 'filetype', $allowedFileTypes);
        $logoForm->addRule('new_logo', get_lang('ThisFieldIsRequired'), 'required');

        if ($logoForm->validate()) {
            $imageInfo = getimagesize($_FILES['new_logo']['tmp_name']);
            $width = $imageInfo[0];
            $height = $imageInfo[1];
            if ($width <= 250 && $height <= 70) {
                if (is_file($dir.$newLogoFileName)) {
                    unlink($dir.$newLogoFileName);
                }

                $status = move_uploaded_file($_FILES['new_logo']['tmp_name'], $dir.$newLogoFileName);

                if ($status) {
                    echo Display::return_message(get_lang('NewLogoUpdated'));
                    echo '<script>'
                            . '$("#header-logo").attr("src","'.$url.$newLogoFileName.'");'
                        . '</script>';
                } else {
                    echo Display::return_message('Error - '.get_lang('UplNoFileUploaded'), 'error');
                }
            } else {
                Display::return_message('Error - '.get_lang('InvalidImageDimensions'), 'error');
            }
        }
    }

    if (isset($_POST['download'])) {
        generateCSSDownloadLink($selected);
    }

    $form_change = new FormValidator(
        'stylesheet_upload',
        'post',
        api_get_self().'?category=Stylesheets',
        null,
        array('id' => 'stylesheets_id')
    );

    $styles = $form_change->addElement(
        'selectTheme',
        'style',
        get_lang('NameStylesheet')
    );
    $styles->setSelected($currentStyle);

    if ($is_style_changeable) {
        $group = [
            $form_change->addButtonSave(get_lang('SaveSettings'), 'save', true),
            $form_change->addButtonPreview(get_lang('Preview'), 'preview', true),
            $form_change->addButtonDownload(get_lang('Download'), 'download', true)
        ];

        $form_change->addGroup($group);

        if ($show_upload_form) {
            echo Display::tabs(
                array(get_lang('Update'), get_lang('UpdateLogo'), get_lang('UploadNewStylesheet')),
                array($form_change->returnForm(), $logoForm->returnForm(), $form->returnForm())
            );
        } else {
            $form_change->display();
        }

        // Little hack to update the logo image in update form when submiting
        if (isset($_POST['logo_reset'])) {
            echo '<script>'
                    . '$("#header-logo-custom").attr("src","'.$url.$logoFileName.'");'
                . '</script>';
        } elseif (isset($_POST['logo_upload']) && is_file($dir.$newLogoFileName)) {
            echo '<script>'
                    . '$("#header-logo-custom").attr("src","'.$url.$newLogoFileName.'");'
                . '</script>';
        }
    } else {
        $form_change->freeze();
    }
}

/**
 * Creates the folder (if needed) and uploads the stylesheet in it
 * @param array $values the values of the form
 * @param array $picture the values of the uploaded file
 * @return bool
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version May 2008
 * @since v1.8.5
 */
function uploadStylesheet($values, $picture)
{
    $result = false;
    // Valid name for the stylesheet folder.
    $style_name = api_preg_replace('/[^A-Za-z0-9]/', '', $values['name_stylesheet']);
    $cssToUpload = CSS_UPLOAD_PATH;

    // Check if a virtual instance vchamilo is used
    $virtualInstanceTheme = api_get_configuration_value('virtual_css_theme_folder');
    if (!empty($virtualInstanceTheme)) {
        $cssToUpload = $cssToUpload.$virtualInstanceTheme.'/';
    }

    // Create the folder if needed.
    if (!is_dir($cssToUpload.$style_name.'/')) {
        mkdir($cssToUpload.$style_name.'/', api_get_permissions_for_new_directories());
    }

    $info = pathinfo($picture['name']);

    if ($info['extension'] == 'zip') {
        // Try to open the file and extract it in the theme.
        $zip = new ZipArchive();
        if ($zip->open($picture['tmp_name'])) {
            // Make sure all files inside the zip are images or css.
            $num_files = $zip->numFiles;
            $valid = true;
            $single_directory = true;
            $invalid_files = array();

            $allowedFiles = getAllowedFileTypes();

            for ($i = 0; $i < $num_files; $i++) {
                $file = $zip->statIndex($i);
                if (substr($file['name'], -1) != '/') {
                    $path_parts = pathinfo($file['name']);
                    if (!in_array($path_parts['extension'], $allowedFiles)) {
                        $valid = false;
                        $invalid_files[] = $file['name'];
                    }
                }

                if (strpos($file['name'], '/') === false) {
                    $single_directory = false;
                }
            }
            if (!$valid) {
                $error_string = '<ul>';
                foreach ($invalid_files as $invalid_file) {
                    $error_string .= '<li>'.$invalid_file.'</li>';
                }
                $error_string .= '</ul>';
                echo Display::return_message(
                    get_lang('ErrorStylesheetFilesExtensionsInsideZip').$error_string,
                    'error',
                    false
                );
            } else {
                // If the zip does not contain a single directory, extract it.
                if (!$single_directory) {
                    // Extract zip file.
                    $zip->extractTo($cssToUpload.$style_name.'/');
                    $result = true;
                } else {
                    $extraction_path = $cssToUpload.$style_name.'/';
                    for ($i = 0; $i < $num_files; $i++) {
                        $entry = $zip->getNameIndex($i);
                        if (substr($entry, -1) == '/') {
                            continue;
                        }

                        $pos_slash = strpos($entry, '/');
                        $entry_without_first_dir = substr($entry, $pos_slash + 1);
                        // If there is still a slash, we need to make sure the directories are created.
                        if (strpos($entry_without_first_dir, '/') !== false) {
                            if (!is_dir($extraction_path.dirname($entry_without_first_dir))) {
                                // Create it.
                                @mkdir($extraction_path.dirname($entry_without_first_dir), $mode = 0777, true);
                            }
                        }

                        $fp = $zip->getStream($entry);
                        $ofp = fopen($extraction_path.dirname($entry_without_first_dir).'/'.basename($entry), 'w');

                        while (!feof($fp)) {
                            fwrite($ofp, fread($fp, 8192));
                        }

                        fclose($fp);
                        fclose($ofp);
                    }
                    $result = true;
                }
            }
            $zip->close();
        } else {
            echo Display::return_message(get_lang('ErrorReadingZip').$info['extension'], 'error', false);
        }
    } else {
        // Simply move the file.
        move_uploaded_file($picture['tmp_name'], $cssToUpload.$style_name.'/'.$picture['name']);
        $result = true;
    }

    if ($result) {
        $fs = new Filesystem();
        $fs->mirror(
            CSS_UPLOAD_PATH,
            api_get_path(SYS_PATH).'web/css/themes/',
            null,
            ['override' => true]
        );
    }

    return $result;
}

/**
 * Store plugin regions.
 */
function storeRegions()
{
    $plugin_obj = new AppPlugin();

    // Get a list of all current 'Plugins' settings
    $installed_plugins = $plugin_obj->get_installed_plugins();

    $shortlist_installed = array();
    if (!empty($installed_plugins)) {
        foreach ($installed_plugins as $plugin) {
            if (isset($plugin['subkey'])) {
                $shortlist_installed[] = $plugin['subkey'];
            }
        }
    }
    $shortlist_installed = array_flip(array_flip($shortlist_installed));

    $plugin_list = $plugin_obj->read_plugins_from_path();

    foreach ($plugin_list as $plugin) {
        if (isset($_POST['plugin_'.$plugin])) {
            $areas_to_installed = $_POST['plugin_'.$plugin];
            if (!empty($areas_to_installed)) {
                $plugin_obj->remove_all_regions($plugin);
                foreach ($areas_to_installed as $region) {
                    if (!empty($region) && $region != '-1') {
                        $plugin_obj->add_to_region($plugin, $region);
                    }
                }
            }
        }
    }
}

/**
 * This function allows easy activating and inactivating of plugins
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function storePlugins()
{
    $appPlugin = new AppPlugin();

    // Get a list of all current 'Plugins' settings
    $plugin_list = $appPlugin->read_plugins_from_path();

    $installed_plugins = array();

    foreach ($plugin_list as $plugin) {
        if (isset($_POST['plugin_'.$plugin])) {
            $appPlugin->install($plugin);
            $installed_plugins[] = $plugin;
        }
    }

    if (!empty($installed_plugins)) {
        $remove_plugins = array_diff($plugin_list, $installed_plugins);
    } else {
        $remove_plugins = $plugin_list;
    }

    foreach ($remove_plugins as $plugin) {
        $appPlugin->uninstall($plugin);
    }
}

/**
 * This function allows the platform admin to choose which should be the default stylesheet
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function storeStylesheets()
{
    // Insert the stylesheet.
    if (isStyle($_POST['style'])) {
        api_set_setting(
            'stylesheets',
            $_POST['style'],
            null,
            'stylesheets',
            api_get_current_access_url_id()
        );
    }
    return true;
}

/**
 * This function checks if the given style is a recognize style that exists in the css directory as
 * a standalone directory.
 * @param string $style
 * @return bool     True if this style is recognized, false otherwise
 */
function isStyle($style)
{
    $themeList = api_get_themes();

    return in_array($style, array_keys($themeList));
}

/**
 * Search options
 * TODO: support for multiple site. aka $_configuration['access_url'] == 1
 * @author Marco Villegas <marvil07@gmail.com>
 */
function handleSearch()
{
    global $SettingsStored, $_configuration;

    require_once api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php';
    $search_enabled = api_get_setting('search_enabled');

    $form = new FormValidator('search-options', 'post', api_get_self().'?category=Search');
    $values = api_get_settings_options('search_enabled');
    $form->addElement('header', null, get_lang('SearchEnabledTitle'));

    $group = formGenerateElementsGroup($form, $values, 'search_enabled');

    //SearchEnabledComment
    $form->addGroup($group, 'search_enabled', array(get_lang('SearchEnabledTitle'), get_lang('SearchEnabledComment')), null, false);

    $search_enabled = api_get_setting('search_enabled');

    if ($form->validate()) {
        $formValues = $form->exportValues();
        setConfigurationSettingsInDatabase($formValues, $_configuration['access_url']);
        $search_enabled = $formValues['search_enabled'];
        echo Display::return_message($SettingsStored, 'confirm');
    }
    $specific_fields = get_specific_field_list();

    if ($search_enabled == 'true') {
        $values = api_get_settings_options('search_show_unlinked_results');

        $group = formGenerateElementsGroup($form, $values, 'search_show_unlinked_results');
        $form->addGroup($group, 'search_show_unlinked_results', array(get_lang('SearchShowUnlinkedResultsTitle'), get_lang('SearchShowUnlinkedResultsComment')), null, false);
        $default_values['search_show_unlinked_results'] = api_get_setting('search_show_unlinked_results');

        $sf_values = array();
        foreach ($specific_fields as $sf) {
            $sf_values[$sf['code']] = $sf['name'];
        }
        $url = Display::div(Display::url(get_lang('AddSpecificSearchField'), 'specific_fields.php'), array('class'=>'sectioncomment'));
        if (empty($sf_values)) {
            $form->addElement('label', [get_lang('SearchPrefilterPrefix'), $url]);
        } else {
            $form->addElement('select', 'search_prefilter_prefix', array(get_lang('SearchPrefilterPrefix'), $url), $sf_values, '');
            $default_values['search_prefilter_prefix'] = api_get_setting('search_prefilter_prefix');
        }
    }

    $default_values['search_enabled'] = $search_enabled;

    $form->addButtonSave(get_lang('Save'));
    $form->setDefaults($default_values);

    echo '<div id="search-options-form">';
    $form->display();
    echo '</div>';

    if ($search_enabled == 'true') {
        $xapianPath = api_get_path(SYS_UPLOAD_PATH).'plugins/xapian/searchdb';

        /*
        @todo Test the Xapian connection
        if (extension_loaded('xapian')) {
            require_once 'xapian.php';
            try {
                $db = new XapianDatabase($xapianPath.'/');
            } catch (Exception $e) {
                var_dump($e->getMessage());
            }

            require_once api_get_path(LIBRARY_PATH) . 'search/ChamiloIndexer.class.php';
            require_once api_get_path(LIBRARY_PATH) . 'search/IndexableChunk.class.php';
            require_once api_get_path(LIBRARY_PATH) . 'specific_fields_manager.lib.php';

            $indexable = new IndexableChunk();
            $indexable->addValue("content", 'Test');

            $di = new ChamiloIndexer();
            $di->connectDb(NULL, NULL, 'english');
            $di->addChunk($indexable);
            $did = $di->index();
        }
        */

        $xapianLoaded = Display::return_icon('bullet_green.png', get_lang('Ok'));
        $dir_exists = Display::return_icon('bullet_green.png', get_lang('Ok'));
        $dir_is_writable = Display::return_icon('bullet_green.png', get_lang('Ok'));
        $specific_fields_exists = Display::return_icon('bullet_green.png', get_lang('Ok'));

        //Testing specific fields
        if (empty($specific_fields)) {
            $specific_fields_exists = Display::return_icon('bullet_red.png', get_lang('AddSpecificSearchField'));
        }
        //Testing xapian extension
        if (!extension_loaded('xapian')) {
            $xapianLoaded = Display::return_icon('bullet_red.png', get_lang('Error'));
        }
        //Testing xapian searchdb path
        if (!is_dir($xapianPath)) {
            $dir_exists = Display::return_icon('bullet_red.png', get_lang('Error'));
        }
        //Testing xapian searchdb path is writable
        if (!is_writable($xapianPath)) {
            $dir_is_writable = Display::return_icon('bullet_red.png', get_lang('Error'));
        }

        $data = array();
        $data[] = array(get_lang('XapianModuleInstalled'), $xapianLoaded);
        $data[] = array(get_lang('DirectoryExists').' - '.$xapianPath, $dir_exists);
        $data[] = array(get_lang('IsWritable').' - '.$xapianPath, $dir_is_writable);
        $data[] = array(get_lang('SpecificSearchFieldsAvailable'), $specific_fields_exists);

        showSearchSettingsTable($data);
        showSearchToolsStatusTable();
    }
}

/**
 * Wrapper for the templates
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @author Julio Montoya.
 * @version August 2008
 * @since v1.8.6
 */
function handleTemplates()
{
    /* Drive-by fix to avoid undefined var warnings, without repeating
     * isset() combos all over the place. */
    $action = isset($_GET['action']) ? $_GET['action'] : "invalid";

    if ($action != 'add') {
        echo '<div class="actions" style="margin-left: 1px;">';
        echo '<a href="settings.php?category=Templates&action=add">'.
                Display::return_icon('new_template.png', get_lang('AddTemplate'), '', ICON_SIZE_MEDIUM).'</a>';
        echo '</div>';
    }

    if ($action == 'add' || ($action == 'edit' && is_numeric($_GET['id']))) {
        addEditTemplate();

        // Add event to the system log.
        $user_id = api_get_user_id();
        $category = $_GET['category'];
        Event::addEvent(
            LOG_CONFIGURATION_SETTINGS_CHANGE,
            LOG_CONFIGURATION_SETTINGS_CATEGORY,
            $category,
            api_get_utc_datetime(),
            $user_id
        );
    } else {
        if ($action == 'delete' && is_numeric($_GET['id'])) {
            deleteTemplate($_GET['id']);

            // Add event to the system log
            $user_id = api_get_user_id();
            $category = $_GET['category'];
            Event::addEvent(
                LOG_CONFIGURATION_SETTINGS_CHANGE,
                LOG_CONFIGURATION_SETTINGS_CATEGORY,
                $category,
                api_get_utc_datetime(),
                $user_id
            );
        }
        displayTemplates();
    }
}

/**
 * Display a sortable table with all the templates that the platform administrator has defined.
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version August 2008
 * @since v1.8.6
 */
function displayTemplates()
{
    $table = new SortableTable('templates', 'getNumberOfTemplates', 'getTemplateData', 1);
    $table->set_additional_parameters(array('category' => Security::remove_XSS($_GET['category'])));
    $table->set_header(0, get_lang('Image'), true, array('style' => 'width: 101px;'));
    $table->set_header(1, get_lang('Title'));
    $table->set_header(2, get_lang('Actions'), false, array('style' => 'width:50px;'));
    $table->set_column_filter(2, 'actionsFilter');
    $table->set_column_filter(0, 'searchImageFilter');
    $table->display();
}

/**
 * Gets the number of templates that are defined by the platform admin.
 *
 * @return integer
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version August 2008
 * @since v1.8.6
 */
function getNumberOfTemplates()
{
    // Database table definition.
    $table_system_template = Database::get_main_table('system_template');

    // The sql statement.
    $sql = "SELECT COUNT(id) AS total FROM $table_system_template";
    $result = Database::query($sql);
    $row = Database::fetch_array($result);

    // Returning the number of templates.
    return $row['total'];
}

/**
 * Gets all the template data for the sortable table.
 *
 * @param integer $from the start of the limit statement
 * @param integer $number_of_items the number of elements that have to be retrieved from the database
 * @param integer $column the column that is
 * @param string $direction the sorting direction (ASC or DESC�
 * @return array
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version August 2008
 * @since v1.8.6
 */
function getTemplateData($from, $number_of_items, $column, $direction)
{
    // Database table definition.
    $table_system_template = Database::get_main_table('system_template');

    // The sql statement.
    $sql = "SELECT image as col0, title as col1, id as col2 FROM $table_system_template";
    $sql .= " ORDER BY col$column $direction ";
    $sql .= " LIMIT $from,$number_of_items";
    $result = Database::query($sql);
    $return = array();
    while ($row = Database::fetch_array($result)) {
        $row['1'] = get_lang($row['1']);
        $return[] = $row;
    }
    // Returning all the information for the sortable table.
    return $return;
}

/**
 * display the edit and delete icons in the sortable table
 *
 * @param integer $id the id of the template
 * @return string code for the link to edit and delete the template
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version August 2008
 * @since v1.8.6
 */
function actionsFilter($id) {
    $return = '<a href="settings.php?category=Templates&action=edit&id='.Security::remove_XSS($id).'">'.Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL).'</a>';
    $return .= '<a href="settings.php?category=Templates&action=delete&id='.Security::remove_XSS($id).'" onClick="javascript:if(!confirm('."'".get_lang('ConfirmYourChoice')."'".')) return false;">'.Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL).'</a>';
    return $return;
}

/**
 * Display the image of the template in the sortable table
 *
 * @param string $image the image
 * @return string code for the image
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version August 2008
 * @since v1.8.6
 */
function searchImageFilter($image)
{
    if (!empty($image)) {
        return '<img src="'.api_get_path(WEB_APP_PATH).'home/default_platform_document/template_thumb/'.$image.'" alt="'.get_lang('TemplatePreview').'"/>';
    } else {
        return '<img src="'.api_get_path(WEB_APP_PATH).'home/default_platform_document/template_thumb/noimage.gif" alt="'.get_lang('NoTemplatePreview').'"/>';
    }
}

/**
 * Add (or edit) a template. This function displays the form and also takes
 * care of uploading the image and storing the information in the database
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version August 2008
 * @since v1.8.6
 */
function addEditTemplate()
{
    // Initialize the object.
    $id = isset($_GET['id']) ? '&id='.Security::remove_XSS($_GET['id']) : '';
    $form = new FormValidator('template', 'post', 'settings.php?category=Templates&action='.Security::remove_XSS($_GET['action']).$id);

    // Setting the form elements: the header.
    if ($_GET['action'] == 'add') {
        $title = get_lang('AddTemplate');
    } else {
        $title = get_lang('EditTemplate');
    }
    $form->addElement('header', '', $title);

    // Setting the form elements: the title of the template.
    $form->addText('title', get_lang('Title'), false);

    // Setting the form elements: the content of the template (wysiwyg editor).
    $form->addElement('html_editor', 'template_text', get_lang('Text'), null, array('ToolbarSet' => 'AdminTemplates', 'Width' => '100%', 'Height' => '400'));

    // Setting the form elements: the form to upload an image to be used with the template.
    $form->addElement('file', 'template_image', get_lang('Image'), '');

    // Setting the form elements: a little bit information about the template image.
    $form->addElement('static', 'file_comment', '', get_lang('TemplateImageComment100x70'));

    // Getting all the information of the template when editing a template.
    if ($_GET['action'] == 'edit') {
        // Database table definition.
        $table_system_template = Database::get_main_table('system_template');
        $sql = "SELECT * FROM $table_system_template WHERE id = ".intval($_GET['id'])."";
        $result = Database::query($sql);
        $row = Database::fetch_array($result);

        $defaults['template_id'] = intval($_GET['id']);
        $defaults['template_text'] = $row['content'];
        // Forcing get_lang().
        $defaults['title'] = get_lang($row['title']);

        // Adding an extra field: a hidden field with the id of the template we are editing.
        $form->addElement('hidden', 'template_id');

        // Adding an extra field: a preview of the image that is currently used.
        if (!empty($row['image'])) {
            $form->addElement('static', 'template_image_preview', '',
                '<img src="'.api_get_path(WEB_APP_PATH).'home/default_platform_document/template_thumb/'.$row['image'].'" alt="'.get_lang('TemplatePreview').'"/>');
        } else {
            $form->addElement('static', 'template_image_preview', '',
                '<img src="'.api_get_path(WEB_APP_PATH).'home/default_platform_document/template_thumb/noimage.gif" alt="'.get_lang('NoTemplatePreview').'"/>');
        }

        // Setting the information of the template that we are editing.
        $form->setDefaults($defaults);
    }
    // Setting the form elements: the submit button.
    $form->addButtonSave(get_lang('Ok'), 'submit');

    // Setting the rules: the required fields.
    $form->addRule('template_image', get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule('title', get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule('template_text', get_lang('ThisFieldIsRequired'), 'required');

    // if the form validates (complies to all rules) we save the information, else we display the form again (with error message if needed)
    if ($form->validate()) {
        $check = Security::check_token('post');
        if ($check) {
            // Exporting the values.
            $values = $form->exportValues();
            // Upload the file.
            if (!empty($_FILES['template_image']['name'])) {
                $upload_ok = process_uploaded_file($_FILES['template_image']);

                if ($upload_ok) {
                    // Try to add an extension to the file if it hasn't one.
                    $new_file_name = add_ext_on_mime(stripslashes($_FILES['template_image']['name']), $_FILES['template_image']['type']);

                    // The upload directory.
                    $upload_dir = api_get_path(SYS_APP_PATH).'home/default_platform_document/template_thumb/';

                    // Create the directory if it does not exist.
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, api_get_permissions_for_new_directories());
                    }

                    // Resize the preview image to max default and upload.
                    $temp = new Image($_FILES['template_image']['tmp_name']);
                    $picture_info = $temp->get_image_info();

                    $max_width_for_picture = 100;

                    if ($picture_info['width'] > $max_width_for_picture) {
                        $temp->resize($max_width_for_picture);
                    }
                    $temp->send_image($upload_dir.$new_file_name);
                }
            }

            // Store the information in the database (as insert or as update).
            $table_system_template = Database::get_main_table('system_template');
            if ($_GET['action'] == 'add') {
                $content_template = Security::remove_XSS($values['template_text'], COURSEMANAGERLOWSECURITY);
                $params = [
                    'title' =>  $values['title'],
                    'content' => $content_template,
                    'image' => $new_file_name
                ];
                Database::insert($table_system_template, $params);

                // Display a feedback message.
                echo Display::return_message(get_lang('TemplateAdded'), 'confirm');
                echo '<a href="settings.php?category=Templates&action=add">'.Display::return_icon('new_template.png', get_lang('AddTemplate'), '', ICON_SIZE_MEDIUM).'</a>';
            } else {
                $content_template = '<head>{CSS}<style type="text/css">.text{font-weight: normal;}</style></head><body>'.Database::escape_string($values['template_text']).'</body>';
                $sql = "UPDATE $table_system_template set title = '".Database::escape_string($values['title'])."', content = '".$content_template."'";
                if (!empty($new_file_name)) {
                    $sql .= ", image = '".Database::escape_string($new_file_name)."'";
                }
                $sql .= " WHERE id = ".intval($_GET['id'])."";
                Database::query($sql);

                // Display a feedback message.
                echo Display::return_message(get_lang('TemplateEdited'), 'confirm');
            }
        }
        Security::clear_token();
        displayTemplates();
    } else {
        $token = Security::get_token();
        $form->addElement('hidden', 'sec_token');
        $form->setConstants(array('sec_token' => $token));
        // Display the form.
        $form->display();
    }
}

/**
 * Delete a template
 *
 * @param integer $id the id of the template that has to be deleted
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version August 2008
 * @since v1.8.6
 */
function deleteTemplate($id)
{
    // First we remove the image.
    $table_system_template = Database::get_main_table('system_template');
    $sql = "SELECT * FROM $table_system_template WHERE id = ".intval($id)."";
    $result = Database::query($sql);
    $row = Database::fetch_array($result);
    if (!empty($row['image'])) {
        @unlink(api_get_path(SYS_APP_PATH).'home/default_platform_document/template_thumb/'.$row['image']);
    }

    // Now we remove it from the database.
    $sql = "DELETE FROM $table_system_template WHERE id = ".intval($id)."";
    Database::query($sql);

    // Display a feedback message.
    echo Display::return_message(get_lang('TemplateDeleted'), 'confirm');
}

/**
 * Returns the list of timezone identifiers used to populate the select
 * This function is called through a call_user_func() in the generate_settings_form function.
 * @return array List of timezone identifiers
 *
 * @author Guillaume Viguier <guillaume.viguier@beeznest.com>
 * @since Chamilo 1.8.7
 */
function select_timezone_value()
{
    return api_get_timezones();
}

/**
 * Returns an array containing the list of options used to populate the gradebook_number_decimals variable
 * This function is called through a call_user_func() in the generate_settings_form function.
 * @return array List of gradebook_number_decimals options
 *
 * @author Guillaume Viguier <guillaume.viguier@beeznest.com>
 */
function select_gradebook_number_decimals() {
    return array('0', '1', '2');
}

/**
 * Get the options for a select element to select gradebook default grade model
 * @return array
 */
function select_gradebook_default_grade_model_id()
{
    $grade_model = new GradeModel();
    $models = $grade_model->get_all();
    $options = array();
    $options[-1] = get_lang('None');

    if (!empty($models)) {
        foreach ($models as $model) {
            $options[$model['id']] = $model['name'];
        }
    }

    return $options;
}

/**
 * @param array $settings
 * @param array $settings_by_access_list
 *
 * @return FormValidator
 *
 * @throws \Doctrine\ORM\ORMException
 * @throws \Doctrine\ORM\OptimisticLockException
 * @throws \Doctrine\ORM\TransactionRequiredException
 */
function generateSettingsForm($settings, $settings_by_access_list)
{
    global $_configuration, $settings_to_avoid, $convert_byte_to_mega_list;
    $em = Database::getManager();
    $table_settings_current = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);

    $form = new FormValidator('settings', 'post', 'settings.php?category='.Security::remove_XSS($_GET['category']));

    $form->addElement(
        'hidden',
        'search_field',
        (!empty($_GET['search_field']) ? Security::remove_XSS($_GET['search_field']) : null)
    );

    $url_id = api_get_current_access_url_id();
    /*
    if (!empty($_configuration['multiple_access_urls']) && api_is_global_platform_admin() && $url_id == 1) {
        $group = array();
        $group[] = $form->createElement('button', 'mark_all', get_lang('MarkAll'));
        $group[] = $form->createElement('button', 'unmark_all', get_lang('UnmarkAll'));
        $form->addGroup($group, 'buttons_in_action_right');
    }*/

    $default_values = array();
    $url_info = api_get_access_url($url_id);
    $i = 0;
    $addedSettings = [];
    foreach ($settings as $row) {
        if (in_array($row['variable'], array_keys($settings_to_avoid))) {
            continue;
        }

        if (in_array($row['variable'], $addedSettings)) {
            continue;
        }

        $addedSettings[] = $row['variable'];

        if (!empty($_configuration['multiple_access_urls'])) {
            if (api_is_global_platform_admin()) {
                if ($row['access_url_locked'] == 0) {
                    if ($url_id == 1) {
                        if ($row['access_url_changeable'] == '1') {
                            $form->addElement(
                                'html',
                                '<div class="pull-right"><a class="share_this_setting" data_status = "0"  data_to_send = "'.$row['variable'].'" href="javascript:void(0);">'.
                                Display::return_icon('shared_setting.png', get_lang('ChangeSharedSetting'), null, ICON_SIZE_MEDIUM).'</a></div>'
                            );
                        } else {
                            $form->addElement(
                                'html',
                                '<div class="pull-right"><a class="share_this_setting" data_status = "1" data_to_send = "'.$row['variable'].'" href="javascript:void(0);">'.
                                Display::return_icon('shared_setting_na.png', get_lang('ChangeSharedSetting'), null, ICON_SIZE_MEDIUM).'</a></div>'
                            );
                        }
                    } else {
                        if ($row['access_url_changeable'] == '1') {
                            $form->addElement(
                                'html',
                                '<div class="pull-right">'.
                                Display::return_icon('shared_setting.png', get_lang('ChangeSharedSetting'), null, ICON_SIZE_MEDIUM).'</div>'
                            );
                        } else {
                            $form->addElement(
                                'html',
                                '<div class="pull-right">'.
                                Display::return_icon('shared_setting_na.png', get_lang('ChangeSharedSetting'), null, ICON_SIZE_MEDIUM).'</div>'
                            );
                        }
                    }
                }
            }
        }

        $hideme = array();
        $hide_element = false;

        if ($_configuration['access_url'] != 1) {
            if ($row['access_url_changeable'] == 0) {
                // We hide the element in other cases (checkbox, radiobutton) we 'freeze' the element.
                $hide_element = true;
                $hideme = array('disabled');
            } elseif ($url_info['active'] == 1) {
                // We show the elements.
                if (empty($row['variable'])) {
                    $row['variable'] = 0;
                }
                if (empty($row['subkey'])) {
                    $row['subkey'] = 0;
                }
                if (empty($row['category'])) {
                    $row['category'] = 0;
                }
                if (isset($settings_by_access_list[$row['variable']]) &&
                    is_array($settings_by_access_list[$row['variable']][$row['subkey']][$row['category']])) {
                    // We are sure that the other site have a selected value.
                    if ($settings_by_access_list[$row['variable']][$row['subkey']][$row['category']]['selected_value'] != '') {
                        $row['selected_value'] = $settings_by_access_list[$row['variable']][$row['subkey']][$row['category']]['selected_value'];
                    }
                }
                // There is no else{} statement because we load the default $row['selected_value'] of the main Chamilo site.
            }
        }

        switch ($row['type']) {
            case 'textfield':
                if (in_array($row['variable'], $convert_byte_to_mega_list)) {
                    $form->addElement(
                        'text',
                        $row['variable'],
                        array(
                            get_lang($row['title']),
                            get_lang($row['comment']),
                            get_lang('MB')
                        ),
                        array('maxlength' => '8', 'aria-label' => get_lang($row['title']))
                    );
                    $form->applyFilter($row['variable'], 'html_filter');
                    $default_values[$row['variable']] = round($row['selected_value'] / 1024 / 1024, 1);
                } elseif ($row['variable'] == 'account_valid_duration') {
                    $form->addElement(
                        'text',
                        $row['variable'],
                        array(
                            get_lang($row['title']),
                            get_lang($row['comment']),
                        ),
                        array('maxlength' => '5', 'aria-label' => get_lang($row['title']))
                    );
                    $form->applyFilter($row['variable'], 'html_filter');
                    $default_values[$row['variable']] = $row['selected_value'];

                    // For platform character set selection: Conversion of the textfield to a select box with valid values.
                } elseif ($row['variable'] == 'platform_charset') {
                    continue;
                } else {
                    $hideme['class'] = 'col-md-4';
                    $hideme['aria-label'] = get_lang($row['title']);
                    $form->addElement(
                        'text',
                        $row['variable'],
                        array(
                            get_lang($row['title']),
                            get_lang($row['comment'])
                        ),
                        $hideme
                    );
                    $form->applyFilter($row['variable'], 'html_filter');
                    $default_values[$row['variable']] = $row['selected_value'];
                }
                break;
            case 'textarea':
                if ($row['variable'] == 'header_extra_content') {
                    $file = api_get_home_path().'header_extra_content.txt';
                    $value = '';
                    if (file_exists($file)) {
                        $value = file_get_contents($file);
                    }
                    $form->addElement(
                        'textarea',
                        $row['variable'],
                        array(get_lang($row['title']), get_lang($row['comment'])),
                        array('rows' => '10', 'id' => $row['variable']),
                        $hideme
                    );
                    $default_values[$row['variable']] = $value;
                } elseif ($row['variable'] == 'footer_extra_content') {
                    $file = api_get_home_path().'footer_extra_content.txt';
                    $value = '';
                    if (file_exists($file)) {
                        $value = file_get_contents($file);
                    }
                    $form->addElement(
                        'textarea',
                        $row['variable'],
                        array(get_lang($row['title']), get_lang($row['comment'])),
                        array('rows' => '10', 'id' => $row['variable']),
                        $hideme
                    );
                    $default_values[$row['variable']] = $value;
                } else {
                    $form->addElement(
                        'textarea',
                        $row['variable'],
                        array(get_lang($row['title']),
                        get_lang($row['comment'])),
                        array('rows' => '10', 'id' => $row['variable']),
                        $hideme
                    );
                    $default_values[$row['variable']] = $row['selected_value'];
                }
                break;
            case 'radio':
                $values = api_get_settings_options($row['variable']);
                $group = array();
                if (is_array($values)) {
                    foreach ($values as $key => $value) {
                        $element = &$form->createElement(
                            'radio',
                            $row['variable'],
                            '',
                            get_lang($value['display_text']),
                            $value['value']
                        );
                        if ($hide_element) {
                            $element->freeze();
                        }
                        $group[] = $element;
                    }
                }
                $form->addGroup(
                    $group,
                    $row['variable'],
                    array(get_lang($row['title']), get_lang($row['comment'])),
                    null,
                    false
                );
                $default_values[$row['variable']] = $row['selected_value'];
                break;
            case 'checkbox':
                // 1. We collect all the options of this variable.
                $sql = "SELECT * FROM $table_settings_current
                        WHERE variable='".$row['variable']."' AND access_url =  1";

                $result = Database::query($sql);
                $group = array();
                while ($rowkeys = Database::fetch_array($result)) {
                    // Profile tab option should be hidden when the social tool is enabled.
                    if (api_get_setting('allow_social_tool') == 'true') {
                        if ($rowkeys['variable'] === 'show_tabs' && $rowkeys['subkey'] === 'my_profile') {
                            continue;
                        }
                    }

                    // Hiding the gradebook option.
                    if ($rowkeys['variable'] === 'show_tabs' && $rowkeys['subkey'] === 'my_gradebook') {
                        continue;
                    }

                    $element = &$form->createElement(
                        'checkbox',
                        $rowkeys['subkey'],
                        '',
                        get_lang($rowkeys['subkeytext'])
                    );

                    if ($row['access_url_changeable'] == 1) {
                        // 2. We look into the DB if there is a setting for a specific access_url.
                        $access_url = $_configuration['access_url'];
                        if (empty($access_url)) {
                            $access_url = 1;
                        }
                        $sql = "SELECT selected_value FROM $table_settings_current
                                WHERE
                                    variable='".$rowkeys['variable']."' AND
                                    subkey='".$rowkeys['subkey']."' AND
                                    subkeytext='".$rowkeys['subkeytext']."' AND
                                    access_url =  $access_url";
                        $result_access = Database::query($sql);
                        $row_access = Database::fetch_array($result_access);
                        if ($row_access['selected_value'] === 'true' && !$form->isSubmitted()) {
                            $element->setChecked(true);
                        }
                    } else {
                        if ($rowkeys['selected_value'] === 'true' && !$form->isSubmitted()) {
                            $element->setChecked(true);
                        }
                    }
                    if ($hide_element) {
                        $element->freeze();
                    }
                    $group[] = $element;
                }
                $form->addGroup(
                    $group,
                    $row['variable'],
                    array(get_lang($row['title']), get_lang($row['comment'])),
                    null
                );
                break;
            case 'link':
                $form->addElement('static', null, array(get_lang($row['title']), get_lang($row['comment'])),
                    get_lang('CurrentValue').' : '.$row['selected_value'], $hideme);
                break;
            case 'select':
                /*
                * To populate the list of options, the select type dynamically calls a function that must be called select_ + the name of the variable being displayed.
                * The functions being called must be added to the file settings.lib.php.
                */
                $form->addElement(
                    'select',
                    $row['variable'],
                    array(get_lang($row['title']), get_lang($row['comment'])),
                    call_user_func('select_'.$row['variable']),
                    $hideme
                );
                $default_values[$row['variable']] = $row['selected_value'];
                break;
            case 'custom':
                break;
            case 'select_course':
                $courseSelectOptions = [];

                if (!empty($row['selected_value'])) {
                    $course = $em->find('ChamiloCoreBundle:Course', $row['selected_value']);

                    $courseSelectOptions[$course->getId()] = $course->getTitle();
                }

                $form->addElement(
                    'select_ajax',
                    $row['variable'],
                    [get_lang($row['title']), get_lang($row['comment'])],
                    $courseSelectOptions,
                    ['url' => api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=search_course']
                );
                $default_values[$row['variable']] = $row['selected_value'];
                break;
        }

        switch ($row['variable']) {
            case 'pdf_export_watermark_enable':
                $url = PDF::get_watermark(null);

                if ($url != false) {
                    $delete_url = '<a href="?delete_watermark">'.get_lang('DelImage').' '.Display::return_icon('delete.png', get_lang('DelImage')).'</a>';
                    $form->addElement('html', '<div style="max-height:100px; max-width:100px; margin-left:162px; margin-bottom:10px; clear:both;"><img src="'.$url.'" style="margin-bottom:10px;" />'.$delete_url.'</div>');
                }

                $form->addElement('file', 'pdf_export_watermark_path', get_lang('AddWaterMark'));
                $allowed_picture_types = array('jpg', 'jpeg', 'png', 'gif');
                $form->addRule('pdf_export_watermark_path', get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')', 'filetype', $allowed_picture_types);

                break;
            case 'timezone_value':
                $timezone = $row['selected_value'];
                if (empty($timezone)) {
                    $timezone = api_get_timezone();
                }
                $form->addLabel('', sprintf(get_lang('LocalTimeUsingPortalTimezoneXIsY'), $timezone, api_get_local_time()));
                break;
        }
    } // end for

    if (!empty($settings)) {
        $form->setDefaults($default_values);
    }
    $form->addHtml('<div class="bottom_actions">');
    $form->addButtonSave(get_lang('SaveSettings'));
    $form->addHtml('</div>');
    return $form;
}

/**
 * Searches a platform setting in all categories except from the Plugins category
 * @param string $search
 * @return array
 */
function searchSetting($search)
{
    if (empty($search)) {
        return array();
    }
    $table_settings_current = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
    $sql = "SELECT * FROM $table_settings_current
            WHERE category <> 'Plugins' ORDER BY id ASC ";
    $result = Database::store_result(Database::query($sql), 'ASSOC');
    $settings = array();

    $search = api_strtolower($search);

    if (!empty($result)) {
        foreach ($result as $setting) {
            $found = false;

            $title = api_strtolower(get_lang($setting['title']));
            // try the title
            if (strpos($title, $search) === false) {
                $comment = api_strtolower(get_lang($setting['comment']));
                //Try the comment
                if (strpos($comment, $search) === false) {
                    //Try the variable name
                    if (strpos($setting['variable'], $search) === false) {
                        continue;
                    } else {
                        $found = true;
                    }
                } else {
                    $found = true;
                }

            } else {
                $found = true;
            }
            if ($found) {
                $settings[] = $setting;
            }
        }
    }
    return $settings;
}
/**
 * Helper function to generates a form elements group
 * @param object $form The form where the elements group has to be added
 * @param array $values Values to browse through
 * @return array
 */
function formGenerateElementsGroup($form, $values = array(), $elementName)
{
    $group = array();
    if (is_array($values)) {
        foreach ($values as $key => $value) {
            $element = &$form->createElement('radio', $elementName, '', get_lang($value['display_text']), $value['value']);
            $group[] = $element;
        }
    }
    return $group;
}
/**
 * Helper function with allowed file types for CSS
 * @return  array Array of file types (no indexes)
 */
function getAllowedFileTypes()
{
    $allowedFiles = array(
        'css',
        'zip',
        'jpeg',
        'jpg',
        'png',
        'gif',
        'ico',
        'psd',
        'xcf',
        'svg',
        'webp',
        'woff',
        'woff2'
    );
    return $allowedFiles;
}
/**
 * Helper function to set settings in the database
 * @param   array   $parameters     List of values
 * @param   int     $accessUrl      The current access URL
 * @return  void
 */
function setConfigurationSettingsInDatabase($parameters, $accessUrl)
{
    api_set_settings_category('Search', 'false', $accessUrl);
    // Save the settings.
    foreach ($parameters as $key => $value) {
        api_set_setting($key, $value, null, null);
    }
}

/**
 * Helper function to show the status of the search settings table
 * @param   array   $data   Data to show
 * @return void
 */
function showSearchSettingsTable($data)
{
    echo Display::tag('h3', get_lang('Settings'));
    $table = new SortableTableFromArray($data);
    $table->set_header(0, get_lang('Setting'), false);
    $table->set_header(1, get_lang('Status'), false);
    echo $table->display();
}
/**
 * Helper function to show status table for each command line tool installed
 * @return void
 */
function showSearchToolsStatusTable()
{
    //@todo windows support
    if (api_is_windows_os() == false) {
        $list_of_programs = array('pdftotext', 'ps2pdf', 'catdoc', 'html2text', 'unrtf', 'catppt', 'xls2csv');
        foreach ($list_of_programs as $program) {
            $output = [];
            $ret_val = null;
            exec("which $program", $output, $ret_val);

            if (!$output) {
                $output[] = '';
            }

            $icon = Display::return_icon('bullet_red.png', get_lang('NotInstalled'));
            if (!empty($output[0])) {
                $icon = Display::return_icon('bullet_green.png', get_lang('Installed'));
            }
            $data2[] = array($program, $output[0], $icon);
        }
        echo Display::tag('h3', get_lang('ProgramsNeededToConvertFiles'));
        $table = new SortableTableFromArray($data2);
        $table->set_header(0, get_lang('Program'), false);
        $table->set_header(1, get_lang('Path'), false);
        $table->set_header(2, get_lang('Status'), false);
        echo $table->display();
    } else {
        echo Display::return_message(
            get_lang('YouAreUsingChamiloInAWindowsPlatformSadlyYouCantConvertDocumentsInOrderToSearchTheContentUsingThisTool'),
            'warning'
        );
    }
}
/**
 * Helper function to generate and show CSS Zip download message
 * @param   string $style Style path
 * @return void
 */
function generateCSSDownloadLink($style)
{
    $arch = api_get_path(SYS_ARCHIVE_PATH).$style.'.zip';
    $themeDir = Template::getThemeDir($style);
    $dir = api_get_path(SYS_CSS_PATH).$themeDir;
    $check = Security::check_abs_path(
        $dir,
        api_get_path(SYS_CSS_PATH).'themes'
    );
    if (is_dir($dir) && $check) {
        $zip = new PclZip($arch);
        // Remove path prefix except the style name and put file on disk
        $zip->create($dir, PCLZIP_OPT_REMOVE_PATH, substr($dir, 0, -strlen($style)));
        $url = api_get_path(WEB_CODE_PATH).'course_info/download.php?archive_path=&archive='.str_replace(api_get_path(SYS_ARCHIVE_PATH), '', $arch);

        //@TODO: use more generic script to download.
        $str = '<a class="btn btn-primary btn-large" href="'.$url.'">'.get_lang('ClickHereToDownloadTheFile').'</a>';
        echo Display::return_message($str, 'normal', false);
    } else {
        echo Display::return_message(get_lang('FileNotFound'), 'warning');
    }
}

/**
 * Helper function to tell if the style is changeable in the current URL
 * @return bool $changeable Whether the style can be changed in this URL or not
 */
function isStyleChangeable()
{
    $changeable = false;
    $urlId = api_get_current_access_url_id();
    if ($urlId) {
        $style_info = api_get_settings('stylesheets', '', 1, 0);
        $url_info = api_get_access_url($urlId);
        if ($style_info[0]['access_url_changeable'] == 1 && $url_info['active'] == 1) {
            $changeable = true;
        }
    } else {
        $changeable = true;
    }
    return $changeable;
}

/**
 * Get all settings of one category prepared for display in admin/settings.php
 * @param string $category
 * @return array
 */
function getCategorySettings($category = '')
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