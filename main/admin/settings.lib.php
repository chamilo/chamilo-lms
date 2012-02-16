<?php
/* For licensing terms, see /license.txt */

/**
 * Library of the settings.php file
 *
 * @author Julio Montoya <gugli100@gmail.com>
 * @author Guillaume Viguier <guillaume@viguierjust.com>
 *
 * @since Chamilo 1.8.7
 * @package chamilo.admin
 */



/**
 * This function allows easy activating and inactivating of plugins
 * @todo: a similar function needs to be written to activate or inactivate additional tools.
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function handle_plugins() {
    global $SettingsStored;
    $userplugins = array();
    $table_settings_current = Database :: get_main_table(TABLE_MAIN_SETTINGS_CURRENT);

    if (isset($_POST['submit_plugins'])) {
        store_plugins();
        // Add event to the system log.        
        $user_id = api_get_user_id();
        $category = $_GET['category'];
        event_system(LOG_CONFIGURATION_SETTINGS_CHANGE, LOG_CONFIGURATION_SETTINGS_CATEGORY, $category, api_get_utc_datetime(), $user_id);
        Display :: display_confirmation_message(get_lang('SettingsStored'));
    }

    //echo get_lang('AvailablePlugins').'<br />';
    echo '<br />';

    /* We scan the plugin directory. Each folder is a potential plugin. */
    $pluginpath = api_get_path(SYS_PLUGIN_PATH);

    $handle = @opendir($pluginpath);
    while (false !== ($file = readdir($handle))) {
        if ($file != '.' && $file != '..' && is_dir(api_get_path(SYS_PLUGIN_PATH).$file)) {
            $possibleplugins[] = $file;
        }
    }
    @closedir($handle);

    /*  For each of the possible plugin directories we check whether a file named "plugin.php" exists
        (it contains all the needed information about this plugin).
        This "plugin.php" file looks like:
        $plugin_info['title'] = 'The title of the plugin';
        $plugin_info['comment'] = 'Some comment about the plugin';
        $plugin_info['location'] = array('loginpage_menu', 'campushomepage_menu', 'banner'); // The possible locations where the plugins can be used.
        $plugin_info['version'] = '0.1 alpha'; // The version number of the plugin.
        $plugin_info['author'] = 'Patrick Cool'; // The author of the plugin.
    */
    echo '<form name="plugins" method="post" action="'.api_get_self().'?category='.$_GET['category'].'">';
    echo '<table class="data_table">';
    echo '<tr>';
    echo '<th>';
    echo get_lang('Plugin');
    echo '</th><th>';
    echo get_lang('LoginPageMainArea');
    echo '</th><th>';
    echo get_lang('LoginPageMenu');
    echo '</th><th>';
    echo get_lang('CampusHomepageMainArea');
    echo '</th><th>';
    echo get_lang('CampusHomepageMenu');
    echo '</th><th>';
    echo get_lang('MyCoursesMainArea');
    echo '</th><th>';
    echo get_lang('MyCoursesMenu');
    echo '</th><th>';
    echo get_lang('Header');
    echo '</th><th>';
    echo get_lang('Footer');
    echo '</th><th>';
    echo get_lang('CourseTool');
    echo '</th>';
    echo '</tr>';

    /* We retrieve all the active plugins. */
    //$sql = "SELECT * FROM $table_settings_current WHERE category='Plugins'";
    //$result = Database::query($sql);
    $result = api_get_settings('Plugins');
    //while ($row = Database::fetch_array($result))
    foreach ($result as $row) {
        $usedplugins[$row['variable']][] = $row['selected_value'];
    }

    /* We display all the possible plugins and the checkboxes */
    foreach ($possibleplugins as $testplugin) {
        $plugin_info_file = api_get_path(SYS_PLUGIN_PATH).$testplugin.'/plugin.php';
        if (file_exists($plugin_info_file)) {
            $plugin_info = array();
            include ($plugin_info_file);

            echo '<tr>';
            echo '<td>';
            foreach ($plugin_info as $key => $value) {
                if ($key != 'location') {
                    if ($key == 'title') {
                        $value = '<strong>'.$value.'</strong>';
                    }
                    echo get_lang(ucwords($key)).': '.$value.'<br />';
                }
            }
            if (file_exists(api_get_path(SYS_PLUGIN_PATH).$testplugin.'/readme.txt')) {
                echo "<a href='".api_get_path(WEB_PLUGIN_PATH).$testplugin."/readme.txt'>readme.txt</a>";
            }
            echo '</td>';

            // column: LoginPageMainArea
            if (empty($usedplugins)) {
                $usedplugins = array();
            }
            display_plugin_cell('loginpage_main', $plugin_info, $testplugin, $usedplugins);
            display_plugin_cell('loginpage_menu', $plugin_info, $testplugin, $usedplugins);
            display_plugin_cell('campushomepage_main', $plugin_info, $testplugin, $usedplugins);
            display_plugin_cell('campushomepage_menu', $plugin_info, $testplugin, $usedplugins);
            display_plugin_cell('mycourses_main', $plugin_info, $testplugin, $usedplugins);
            display_plugin_cell('mycourses_menu', $plugin_info, $testplugin, $usedplugins);
            display_plugin_cell('header', $plugin_info, $testplugin, $usedplugins);
            display_plugin_cell('footer', $plugin_info, $testplugin, $usedplugins);
            display_plugin_cell('course_tool_plugin', $plugin_info, $testplugin, $usedplugins);
            echo '</tr>';
        }
    }
    echo '</table>';
    echo '<br />';
    echo '<button class="save" type="submit" name="submit_plugins">'.get_lang('EnablePlugins').'</button></form>';
    echo '<br />';
}


function display_plugin_cell($location, $plugin_info, $current_plugin, $active_plugins) {
    echo '<td align="center">';
    if (in_array($location, $plugin_info['location'])) {
        if (isset($active_plugins[$location]) && is_array($active_plugins[$location])
            && in_array($current_plugin, $active_plugins[$location])) {
            $checked = 'checked';
        } else {
            $checked = '';
        }
        echo '<input type="checkbox" name="'.$current_plugin.'-'.$location.'" value="true" '.$checked.'/>';
    }
    echo "</td>";
}

/**
 * This function allows the platform admin to choose the default stylesheet
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @author Julio Montoya <gugli100@gmail.com>, Chamilo
*/
function handle_stylesheets() {
    global $_configuration;
    // Current style.
    $currentstyle = api_get_setting('stylesheets');
    $is_style_changeable = false;

    if ($_configuration['access_url'] != 1) {
        $style_info = api_get_settings('stylesheets', '', 1, 0);
        $url_info = api_get_access_url($_configuration['access_url']);
        if ($style_info[0]['access_url_changeable'] == 1 && $url_info['active'] == 1) {
            $is_style_changeable = true;
            echo '<div class="actions" id="stylesheetuploadlink">';
            	Display::display_icon('upload_stylesheets.png',get_lang('UploadNewStylesheet'),'',ICON_SIZE_MEDIUM);
            	echo '<a href="" onclick="javascript: document.getElementById(\'newstylesheetform\').style.display = \'block\'; document.getElementById(\'stylesheetuploadlink\').style.display = \'none\'; return false; ">'.get_lang('UploadNewStylesheet').'</a>';
            echo '</div>';
        }
    } else {
        $is_style_changeable = true;
        echo '<div class="actions" id="stylesheetuploadlink">';
			Display::display_icon('upload_stylesheets.png',get_lang('UploadNewStylesheet'),'',ICON_SIZE_MEDIUM);
        	echo '<a href="" onclick="javascript: document.getElementById(\'newstylesheetform\').style.display = \'block\'; document.getElementById(\'stylesheetuploadlink\').style.display = \'none\'; return false; ">'.get_lang('UploadNewStylesheet').'</a>';
        echo '</div>';
    }

    $form = new FormValidator('stylesheet_upload', 'post', 'settings.php?category=stylesheets&showuploadform=true');
    $form->addElement('text', 'name_stylesheet', get_lang('NameStylesheet'), array('size' => '40', 'maxlength' => '40'));
    $form->addRule('name_stylesheet', get_lang('ThisFieldIsRequired'), 'required');
    $form->addElement('file', 'new_stylesheet', get_lang('UploadNewStylesheet'));
    $allowed_file_types = array('css', 'zip', 'jpeg', 'jpg', 'png', 'gif');
    $form->addRule('new_stylesheet', get_lang('InvalidExtension').' ('.implode(',', $allowed_file_types).')', 'filetype', $allowed_file_types);
    $form->addRule('new_stylesheet', get_lang('ThisFieldIsRequired'), 'required');
    $form->addElement('style_submit_button', 'stylesheet_upload', get_lang('Ok'), array('class'=>'save'));
    if ($form->validate() && is_writable(api_get_path(SYS_CODE_PATH).'css/')) {
        $values = $form->exportValues();
        $picture_element = & $form->getElement('new_stylesheet');
        $picture = $picture_element->getValue();
        $result = upload_stylesheet($values, $picture);

        // Add event to the system log.
        $user_id = api_get_user_id();
        $category = $_GET['category'];
        event_system(LOG_CONFIGURATION_SETTINGS_CHANGE, LOG_CONFIGURATION_SETTINGS_CATEGORY, $category, api_get_utc_datetime(), $user_id);
        
        if ($result) {
            Display::display_confirmation_message(get_lang('StylesheetAdded'));
        }
    } else {
        if (!is_writable(api_get_path(SYS_CODE_PATH).'css/')) {
            Display::display_error_message(api_get_path(SYS_CODE_PATH).'css/'.get_lang('IsNotWritable'));
        } else {
            if ($_GET['showuploadform'] == 'true') {
                echo '<div id="newstylesheetform">';
            } else {
                echo '<div id="newstylesheetform" style="display: none;">';
            }
            // Uploading a new stylesheet.
            if ($_configuration['access_url'] == 1) {
                $form->display();
            } else {
                if ($is_style_changeable) {
                    $form->display();
                }
            }
            echo '</div>';
        }
    }

?>
    <script type="text/javascript">
    function load_preview(){
        $('#stylesheets_id').submit();        
    }
    </script>
<?php
    echo '<form id="stylesheets_id" name="stylesheets" method="post" action="'.api_get_self().'?category='.Security::remove_XSS($_GET['category']).'">';
    echo '<br /><select name="style" onChange="load_preview(this)" >';

    $list_of_styles = array();
    $list_of_names  = array();

    if ($handle = @opendir(api_get_path(SYS_PATH).'main/css/')) {
        $counter = 1;
        while (false !== ($style_dir = readdir($handle))) {
            if (substr($style_dir, 0, 1) == '.') { // Skip directories starting with a '.'
                continue;
            }
            $dirpath = api_get_path(SYS_PATH).'main/css/'.$style_dir;
            
            if (is_dir($dirpath)) {
                if ($style_dir != '.' && $style_dir != '..') {
                    if (isset($_POST['style']) && $_POST['style'] == $style_dir) {
                        $selected = 'selected="true"';
                    } else {
                        if (!isset($_POST['style'])  && ($currentstyle == $style_dir || ($style_dir == 'chamilo' && !$currentstyle))) {
                            $selected = 'selected="true"';
                        } else {
                            $selected = '';
                        }
                    }
                    $show_name = ucwords(str_replace('_', ' ', $style_dir));

                    if ($is_style_changeable) {
                        $list_of_styles[$style_dir] = "<option  value=\"".$style_dir."\" ".$selected." /> $show_name </option>";
                        $list_of_names[$style_dir]  = $show_name;
                        //echo "<input type=\"radio\" name=\"style\" value=\"".$style_dir."\" ".$selected." onClick=\"parent.preview.location='style_preview.php?style=".$style_dir."';\"/>";
                        //echo '<a href="style_preview.php?style='.$style_dir.'" target="preview">'.$show_name.'</a>';
                    } else {
                        echo '<a href="style_preview.php?style='.$style_dir.'" target="preview">'.$show_name.'</a>';
                    }
                    echo '<br />';
                    $counter++;
                }
            }
        }
        @closedir($handle);
    }
    
    //Sort styles in alphabetical order
    asort($list_of_names);
    foreach($list_of_names as $style_dir=>$item) {
        echo $list_of_styles[$style_dir];
    }

    //echo '</select><br />';
    echo '</select>&nbsp;&nbsp;';
    if ($is_style_changeable){
        echo '<button class="save" type="submit" name="submit_stylesheets"> '.get_lang('SaveSettings').' </button></form>';
    }
}

/**
 * Creates the folder (if needed) and uploads the stylesheet in it
 *
 * @param array $values the values of the form
 * @param array $picture the values of the uploaded file
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version May 2008
 * @since Dokeos 1.8.5
 */
function upload_stylesheet($values, $picture) {
    $result = false;
    // Valid name for the stylesheet folder.
    $style_name = api_preg_replace('/[^A-Za-z0-9]/', '', $values['name_stylesheet']);

    // Create the folder if needed.
    if (!is_dir(api_get_path(SYS_CODE_PATH).'css/'.$style_name.'/')) {
        mkdir(api_get_path(SYS_CODE_PATH).'css/'.$style_name.'/', api_get_permissions_for_new_directories());
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

            for ($i = 0; $i < $num_files; $i++) {
                $file = $zip->statIndex($i);
                if (substr($file['name'], -1) != '/') {
                    $path_parts = pathinfo($file['name']);
                    if (!in_array($path_parts['extension'], array('jpg', 'jpeg', 'png', 'gif', 'css'))) {
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
                Display::display_error_message(get_lang('ErrorStylesheetFilesExtensionsInsideZip').$error_string, false);
            } else {
                // If the zip does not contain a single directory, extract it.
                if (!$single_directory) {
                    // Extract zip file.
                    $zip->extractTo(api_get_path(SYS_CODE_PATH).'css/'.$style_name.'/');
                    $result = true;
                } else {
                    $extraction_path = api_get_path(SYS_CODE_PATH).'css/'.$style_name.'/';
                    for ($i = 0; $i < $num_files; $i++) {
                        $entry = $zip->getNameIndex($i);
                        if (substr($entry, -1) == '/') continue;

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
            Display::display_error_message(get_lang('ErrorReadingZip').$info['extension'], false);
        }
    } else {
        // Simply move the file.
        move_uploaded_file($picture['tmp_name'], api_get_path(SYS_CODE_PATH).'css/'.$style_name.'/'.$picture['name']);
        $result = true;
    }
    return $result;
}

/**
 * This function allows easy activating and inactivating of plugins
 * @todo: A similar function needs to be written to activate or inactivate additional tools.
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function store_plugins() {
    $table_settings_current = Database :: get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
    global $_configuration;

    // Get a list of all current 'Plugins' settings
    $installed_plugins = api_get_settings('Plugins','list',$_configuration['access_url']);
    $shortlist_installed = array();
    foreach ($installed_plugins as $plugin) {
        $shortlist_installed[] = $plugin['subkey'];
    }
    $shortlist_installed = array_flip(array_flip($shortlist_installed));
    // Step 1 : We remove all the plugins.
    //$sql = "DELETE FROM $table_settings_current WHERE category='Plugins'";
    //Database::query($sql);
    $r = api_delete_category_settings('Plugins', $_configuration['access_url']);
    $shortlist_required = array();
    // Step 2: Looping through all the post values we only store these which are really a valid plugin location.
    foreach ($_POST as $form_name => $formvalue) {
        $form_name_elements = explode('-', $form_name);
        if (is_valid_plugin_location($form_name_elements[1])) {
            $shortlist_required[] = $form_name_elements[0];
            //$sql = "INSERT into $table_settings_current (variable,category,selected_value) VALUES ('".$form_name_elements['1']."','Plugins','".$form_name_elements['0']."')";
            //Database::query($sql);
            api_add_setting($form_name_elements['0'], $form_name_elements['1'], $form_name_elements['0'], null, 'Plugins', $form_name_elements['0'], null, null, null, $_configuration['access_url'], 1);
            // check if there is an install procedure
            $pluginpath = api_get_path(SYS_PLUGIN_PATH).$form_name_elements[0].'/install.php';
            if (is_file($pluginpath) && is_readable($pluginpath)) {
                //execute the install procedure
            	include $pluginpath;
            }
        }
    }
    foreach ($shortlist_installed as $plugin) {
        // if one plugin was really deleted, execute the uninstall script
    	if (!in_array($plugin,$shortlist_required)) {
            // check if there is an install procedure
            $pluginpath = api_get_path(SYS_PLUGIN_PATH).$plugin.'/uninstall.php';
            if (is_file($pluginpath) && is_readable($pluginpath)) {
                //execute the install procedure
                include $pluginpath;
            }    		
    	}
    }
}

/**
 * Check if the post information is really a valid plugin location.
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function is_valid_plugin_location($location) {
    static $valid_locations = array('loginpage_main', 'loginpage_menu', 'campushomepage_main', 'campushomepage_menu', 'mycourses_main', 'mycourses_menu', 'header', 'footer', 'course_tool_plugin');
    return in_array($location, $valid_locations);
}


/**
 * This function allows the platform admin to choose which should be the default stylesheet
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function store_stylesheets() {
    global $_configuration;
    // Database table definitions.
    $table_settings_current = Database :: get_main_table(TABLE_MAIN_SETTINGS_CURRENT);

    // Insert the stylesheet.
    $style = Database::escape_string($_POST['style']);
    
    if (is_style($style)) {
        api_set_setting('stylesheets', $style, null, 'stylesheets', $_configuration['access_url']);
    }

    return true;
}

/**
 * This function checks if the given style is a recognize style that exists in the css directory as
 * a standalone directory.
 * @param string    Style
 * @return bool     True if this style is recognized, false otherwise
 */
function is_style($style) {
    $dir = api_get_path(SYS_PATH).'main/css/';
    $dirs = scandir($dir);
    $style = str_replace(array('/', '\\'), array('', ''), $style); // Avoid slashes or backslashes.
    if (in_array($style, $dirs) && is_dir($dir.$style)) {
        return true;
    }
    return false;
}

/**
 * Search options
 * TODO: support for multiple site. aka $_configuration['access_url'] == 1
 * @author Marco Villegas <marvil07@gmail.com>
 */
function handle_search() {
    global $SettingsStored, $_configuration;

    require_once api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php';
    require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
    $search_enabled = api_get_setting('search_enabled');
    
    $form = new FormValidator('search-options', 'post', api_get_self().'?category=Search');
        
    $renderer = & $form->defaultRenderer();
    //$renderer->setHeaderTemplate('<div class="sectiontitle">{header}</div>'."\n");
    //$renderer->setElementTemplate('<div class="sectioncomment">{label}</div>'."\n".'<div class="sectionvalue">{element}</div>'."\n");
    $renderer->setElementTemplate('<div class="row"><div class="label">{label}</div><div class="formw">{element}<!-- BEGIN label_2 --><span class="help-block">{label_2}</span><!-- END label_2 --></div></div>');
    
    
    
    $values = api_get_settings_options('search_enabled');   
    $form->addElement('header', null, get_lang('SearchEnabledTitle'));    
     
    $group = array ();
    if (is_array($values)) {
        foreach ($values as $key => $value) {
            $element = & $form->createElement('radio', 'search_enabled', '', get_lang($value['display_text']), $value['value']);
            /* $hide_element is not defined
            if ($hide_element) {
                $element->freeze();
            }
            */
            $group[] = $element;
        }
    }
    //SearchEnabledComment
    $form->addGroup($group, 'search_enabled', array(get_lang('SearchEnabledTitle'), get_lang('SearchEnabledComment')), '<br />', false);
    
    $search_enabled = api_get_setting('search_enabled');
    
    if ($form->validate()) {
        $formvalues = $form->exportValues();
        $r = api_set_settings_category('Search', 'false', $_configuration['access_url']);
        // Save the settings.
        foreach ($formvalues as $key => $value) {
            $result = api_set_setting($key, $value, null, null);
        }
        $search_enabled = $formvalues['search_enabled'];
        Display::display_confirmation_message($SettingsStored);
    }
    $specific_fields = get_specific_field_list();
    
    if ($search_enabled == 'true') {
    
        // Search_show_unlinked_results.
        //$form->addElement('header', null, get_lang('SearchShowUnlinkedResultsTitle'));
        //$form->addElement('label', null, get_lang('SearchShowUnlinkedResultsComment'));
        $values = api_get_settings_options('search_show_unlinked_results');
        $group = array ();
        foreach ($values as $key => $value) {
            $element = & $form->createElement('radio', 'search_show_unlinked_results', '', get_lang($value['display_text']), $value['value']);
            $group[] = $element;
        }
        $form->addGroup($group, 'search_show_unlinked_results', array(get_lang('SearchShowUnlinkedResultsTitle'),get_lang('SearchShowUnlinkedResultsComment')), '', false);
        $default_values['search_show_unlinked_results'] = api_get_setting('search_show_unlinked_results');
    
        // Search_prefilter_prefix.
        //$form->addElement('header', null, get_lang('SearchPrefilterPrefix'));
        //$form->addElement('label', null, get_lang('SearchPrefilterPrefixComment'));
        
        $sf_values = array();
        foreach ($specific_fields as $sf) {
           $sf_values[$sf['code']] = $sf['name'];
        }
        $group = array();
        $url =  Display::div(Display::url(get_lang('AddSpecificSearchField'), 'specific_fields.php'), array('class'=>'sectioncomment'));
        if (empty($sf_values)) {    
            $form->addElement('html', get_lang('SearchPrefilterPrefix').$url);
        } else {
            $form->addElement('select', 'search_prefilter_prefix', array(get_lang('SearchPrefilterPrefix'), $url), $sf_values, '');            
            $default_values['search_prefilter_prefix'] = api_get_setting('search_prefilter_prefix');
        }  
                  
    }

    $default_values['search_enabled'] = $search_enabled;

    //$form->addRule('search_show_unlinked_results', get_lang('ThisFieldIsRequired'), 'required');
    $form->addElement('style_submit_button', 'submit', get_lang('Save'),'class="save"');
    $form->setDefaults($default_values);    
    
    echo '<div id="search-options-form">';
    $form->display();
    echo '</div>';
    
    if ($search_enabled == 'true') {
        require_once api_get_path(LIBRARY_PATH).'sortabletable.class.php';        
        $xapian_path = api_get_path(SYS_PATH).'searchdb';
        
        /*
        @todo Test the Xapian connection
        if (extension_loaded('xapian')) {
            require_once 'xapian.php';
            try {               
                $db = new XapianDatabase($xapian_path.'/');
            } catch (Exception $e) {        
                var_dump($e->getMessage());            
            }
            
            require_once api_get_path(LIBRARY_PATH) . 'search/DokeosIndexer.class.php';
            require_once api_get_path(LIBRARY_PATH) . 'search/IndexableChunk.class.php';
            require_once api_get_path(LIBRARY_PATH) . 'specific_fields_manager.lib.php';
            
            $indexable = new IndexableChunk();
            $indexable->addValue("content", 'Test');
            
            $di = new DokeosIndexer();            
            $di->connectDb(NULL, NULL, 'english');
            $di->addChunk($indexable);
            $did = $di->index();
        }
        */
        
        $xapian_loaded          = Display::return_icon('bullet_green.gif', get_lang('Ok'));
        $dir_exists             = Display::return_icon('bullet_green.gif', get_lang('Ok'));
        $dir_is_writable        = Display::return_icon('bullet_green.gif', get_lang('Ok'));        
        $specific_fields_exists = Display::return_icon('bullet_green.gif', get_lang('Ok'));
        
        //Testing specific fields
        if (empty($specific_fields)) {
            $specific_fields_exists = Display::return_icon('bullet_red.gif', get_lang('AddSpecificSearchField'));
        }
        //Testing xapian extension 
        if (!extension_loaded('xapian')) {
            $xapian_loaded = Display::return_icon('bullet_red.gif', get_lang('Error'));
        }
        //Testing xapian searchdb path
        if (!is_dir($xapian_path)) {
            $dir_exists = Display::return_icon('bullet_red.gif', get_lang('Error'));
        }
        //Testing xapian searchdb path is writable
        if (!is_writable($xapian_path)) {
            $dir_is_writable = Display::return_icon('bullet_red.gif', get_lang('Error'));   
        }
               
        $data[] = array(get_lang('XapianModuleInstalled'),$xapian_loaded);
        $data[] = array(get_lang('DirectoryExists').' - '.$xapian_path,$dir_exists);
        $data[] = array(get_lang('IsWritable').' - '.$xapian_path,$dir_is_writable);
        $data[] = array(get_lang('SpecificSearchFieldsAvailable') ,$specific_fields_exists);

        echo Display::tag('h3', get_lang('Settings'));
        $table = new SortableTableFromArray($data);
        $table->set_header(0, get_lang('Setting'), false);
        $table->set_header(1, get_lang('Status'), false);
        echo  $table->display();    
       
        //@todo windows support
        if (api_is_windows_os() == false) {
            $list_of_programs = array('pdftotext','ps2pdf', 'catdoc','html2text','unrtf', 'catppt', 'xls2csv');
            
            foreach($list_of_programs as $program) {
                $output = $ret_val = null;
                exec("which $program", $output, $ret_val);
                $icon = Display::return_icon('bullet_red.gif', get_lang('NotInstalled'));
                if (!empty($output[0])) {
                    $icon = Display::return_icon('bullet_green.gif', get_lang('Installed'));    
                }
                $data2[]= array($program, $output[0], $icon);             
            }            
            echo Display::tag('h3', get_lang('ProgramsNeededToConvertFiles'));
            $table = new SortableTableFromArray($data2);
            $table->set_header(0, get_lang('Program'), false);
            $table->set_header(1, get_lang('Path'), false);
            $table->set_header(2, get_lang('Status'), false);
            echo  $table->display();
        } else {
            Display::display_warning_message(get_lang('YouAreUsingChamiloInAWindowsPlatformSadlyYouCantConvertDocumentsInOrderToSearchTheContentUsingThisTool'));
        }
    }    
}

/**
 * Wrapper for the templates
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version August 2008
 * @since Dokeos 1.8.6
 */
function handle_templates() {
    if ($_GET['action'] != 'add') {
        echo '<div class="actions" style="margin-left: 1px;">';
        echo '<a href="settings.php?category=Templates&amp;action=add">'.Display::return_icon('new_template.png', get_lang('AddTemplate'),'',ICON_SIZE_MEDIUM).'</a>';
        echo '</div>';
    }

    if ($_GET['action'] == 'add' || ($_GET['action'] == 'edit' && is_numeric($_GET['id']))) {
        add_edit_template();

        // Add event to the system log.
        $user_id = api_get_user_id();
        $category = $_GET['category'];
        event_system(LOG_CONFIGURATION_SETTINGS_CHANGE, LOG_CONFIGURATION_SETTINGS_CATEGORY, $category, api_get_utc_datetime(), $user_id);

    } else {
        if ($_GET['action'] == 'delete' && is_numeric($_GET['id'])) {
            delete_template($_GET['id']);

            // Add event to the system log            
            $user_id = api_get_user_id();
            $category = $_GET['category'];
            event_system(LOG_CONFIGURATION_SETTINGS_CHANGE, LOG_CONFIGURATION_SETTINGS_CATEGORY, $category, api_get_utc_datetime(), $user_id);
        }
        display_templates();
    }
}

/**
 * Display a sortable table with all the templates that the platform administrator has defined.
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version August 2008
 * @since Dokeos 1.8.6
 */
function display_templates() {
    $table = new SortableTable('templates', 'get_number_of_templates', 'get_template_data', 1);
    $table->set_additional_parameters(array('category' => Security::remove_XSS($_GET['category'])));
    $table->set_header(0, get_lang('Image'), true, array('style' => 'width: 101px;'));
    $table->set_header(1, get_lang('Title'));
    $table->set_header(2, get_lang('Actions'), false, array('style' => 'width:50px;'));
    $table->set_column_filter(2, 'actions_filter');
    $table->set_column_filter(0, 'image_filter');
    $table->display();
}

/**
 * Gets the number of templates that are defined by the platform admin.
 *
 * @return integer
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version August 2008
 * @since Dokeos 1.8.6
 */
function get_number_of_templates() {
    // Database table definition.
    $table_system_template = Database :: get_main_table('system_template');

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
 * @since Dokeos 1.8.6
 */
function get_template_data($from, $number_of_items, $column, $direction) {
    // Database table definition.
    $table_system_template = Database :: get_main_table('system_template');

    // The sql statement.
    $sql = "SELECT image as col0, title as col1, id as col2 FROM $table_system_template";
    $sql .= " ORDER BY col$column $direction ";
    $sql .= " LIMIT $from,$number_of_items";
    $result = Database::query($sql);
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
 * @return html code for the link to edit and delete the template
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version August 2008
 * @since Dokeos 1.8.6
 */
function actions_filter($id) {
    $return = '<a href="settings.php?category=Templates&amp;action=edit&amp;id='.Security::remove_XSS($id).'">'.Display::return_icon('edit.png', get_lang('Edit'),'',ICON_SIZE_SMALL).'</a>';
    $return .= '<a href="settings.php?category=Templates&amp;action=delete&amp;id='.Security::remove_XSS($id).'" onClick="javascript:if(!confirm('."'".get_lang('ConfirmYourChoice')."'".')) return false;">'.Display::return_icon('delete.png', get_lang('Delete'),'',ICON_SIZE_SMALL).'</a>';
    return $return;
}

/**
 * Display the image of the template in the sortable table
 *
 * @param string $image the image
 * @return html code for the image
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version August 2008
 * @since Dokeos 1.8.6
 */
function image_filter($image) {
    if (!empty($image)) {
        return '<img src="'.api_get_path(WEB_PATH).'home/default_platform_document/template_thumb/'.$image.'" alt="'.get_lang('TemplatePreview').'"/>';
    } else {
        return '<img src="'.api_get_path(WEB_PATH).'home/default_platform_document/template_thumb/noimage.gif" alt="'.get_lang('NoTemplatePreview').'"/>';
    }
}

/**
 * Add (or edit) a template. This function displays the form and also takes care of uploading the image and storing the information in the database
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version August 2008
 * @since Dokeos 1.8.6
 */
function add_edit_template() {
    // Initialize the object.
    $form = new FormValidator('template', 'post', 'settings.php?category=Templates&action='.Security::remove_XSS($_GET['action']).'&id='.Security::remove_XSS($_GET['id']));

    // Settting the form elements: the header.
    if ($_GET['action'] == 'add') {
        $title = get_lang('AddTemplate');
    } else {
        $title = get_lang('EditTemplate');
    }
    $form->addElement('header', '', $title);

    // Settting the form elements: the title of the template.
    $form->add_textfield('title', get_lang('Title'), false);

    // Settting the form elements: the content of the template (wysiwyg editor).
    $form->addElement('html_editor', 'template_text', get_lang('Text'), null, array('ToolbarSet' => 'AdminTemplates', 'Width' => '100%', 'Height' => '400'));

    // Settting the form elements: the form to upload an image to be used with the template.
    $form->addElement('file','template_image',get_lang('Image'),'');

    // Settting the form elements: a little bit information about the template image.
    $form->addElement('static', 'file_comment', '', get_lang('TemplateImageComment100x70'));

    // Getting all the information of the template when editing a template.
    if ($_GET['action'] == 'edit') {
        // Database table definition.
        $table_system_template = Database :: get_main_table('system_template');
        $sql = "SELECT * FROM $table_system_template WHERE id = '".Database::escape_string($_GET['id'])."'";
        $result = Database::query($sql);
        $row = Database::fetch_array($result);

        $defaults['template_id']    = intval($_GET['id']);
        $defaults['template_text']  = $row['content'];
        // Forcing get_lang().
        $defaults['title']          = get_lang($row['title']);

        // Adding an extra field: a hidden field with the id of the template we are editing.
        $form->addElement('hidden', 'template_id');

        // Adding an extra field: a preview of the image that is currently used.
        if (!empty($row['image'])) {
            $form->addElement('static', 'template_image_preview', '', '<img src="'.api_get_path(WEB_PATH).'home/default_platform_document/template_thumb/'.$row['image'].'" alt="'.get_lang('TemplatePreview').'"/>');
        } else {
            $form->addElement('static', 'template_image_preview', '', '<img src="'.api_get_path(WEB_PATH).'home/default_platform_document/template_thumb/noimage.gif" alt="'.get_lang('NoTemplatePreview').'"/>');
        }

        // Setting the information of the template that we are editing.
        $form->setDefaults($defaults);
    }
    // Settting the form elements: the submit button.
    $form->addElement('style_submit_button' , 'submit', get_lang('Ok') ,'class="save"');

    // Setting the rules: the required fields.
    $form->addRule('title', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule('template_text', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');

    // if the form validates (complies to all rules) we save the information, else we display the form again (with error message if needed)
    if ($form->validate()) {

        $check = Security::check_token('post');
        if ($check) {
            // Exporting the values.
            $values = $form->exportValues();
            // Upload the file.
            if (!empty($_FILES['template_image']['name'])) {
                require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
                $upload_ok = process_uploaded_file($_FILES['template_image']);

                if ($upload_ok) {
                    // Try to add an extension to the file if it hasn't one.
                    $new_file_name = add_ext_on_mime(stripslashes($_FILES['template_image']['name']), $_FILES['template_image']['type']);

                    // The upload directory.
                    $upload_dir = api_get_path(SYS_PATH).'home/default_platform_document/template_thumb/';

                    // Create the directory if it does not exist.
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, api_get_permissions_for_new_directories());
                    }

                    // Resize the preview image to max default and upload.
                    $temp = new Image($_FILES['template_image']['tmp_name']);
                    $picture_info = $temp->get_image_info();

                    $max_width_for_picture = 100;

                    if ($picture_info['width'] > $max_width_for_picture) {
                        $thumbwidth = $max_width_for_picture;
                        if (empty($thumbwidth) || $thumbwidth == 0) {
                            $thumbwidth = $max_width_for_picture;
                        }
                        $new_height = round(($thumbwidth / $picture_info['width']) * $picture_info['height']);
                        $temp->resize($thumbwidth, $new_height, 0);
                    }                    
                    $temp->send_image($upload_dir.$new_file_name);
                }
           }

           // Store the information in the database (as insert or as update).
           $table_system_template = Database :: get_main_table('system_template');
           if ($_GET['action'] == 'add') {
               $content_template = '<head>{CSS}<style type="text/css">.text{font-weight: normal;}</style></head><body>'.Database::escape_string($values['template_text']).'</body>';
               $sql = "INSERT INTO $table_system_template (title, content, image) VALUES ('".Database::escape_string($values['title'])."','".$content_template."','".Database::escape_string($new_file_name)."')";
               $result = Database::query($sql);

               // Display a feedback message.
               Display::display_confirmation_message(get_lang('TemplateAdded'));
               echo '<a href="settings.php?category=Templates&amp;action=add">'.Display::return_icon('new_template.png', get_lang('AddTemplate'),'',ICON_SIZE_MEDIUM).'</a>';
           } else {
               $content_template = '<head>{CSS}<style type="text/css">.text{font-weight: normal;}</style></head><body>'.Database::escape_string($values['template_text']).'</body>';
               $sql = "UPDATE $table_system_template set title = '".Database::escape_string($values['title'])."', content = '".$content_template."'";
               if (!empty($new_file_name)) {
                   $sql .= ", image = '".Database::escape_string($new_file_name)."'";
               }
               $sql .= " WHERE id='".Database::escape_string($_GET['id'])."'";
               $result = Database::query($sql);

               // Display a feedback message.
               Display::display_confirmation_message(get_lang('TemplateEdited'));
           }

        }
       Security::clear_token();
       display_templates();

    } else {

        $token = Security::get_token();
        $form->addElement('hidden','sec_token');
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
 * @since Dokeos 1.8.6
 */
function delete_template($id) {
    // First we remove the image.
    $table_system_template = Database :: get_main_table('system_template');
    $sql = "SELECT * FROM $table_system_template WHERE id = '".Database::escape_string($id)."'";
    $result = Database::query($sql);
    $row = Database::fetch_array($result);
    if (!empty($row['image'])) {
        @unlink(api_get_path(SYS_PATH).'home/default_platform_document/template_thumb/'.$row['image']);
    }

    // Now we remove it from the database.
    $sql = "DELETE FROM $table_system_template WHERE id = '".Database::escape_string($id)."'";
    $result = Database::query($sql);

    // Display a feedback message.
    Display::display_confirmation_message(get_lang('TemplateDeleted'));
}

/**
 * Returns the list of timezone identifiers used to populate the select
 *
 * @return array List of timezone identifiers
 *
 * @author Guillaume Viguier <guillaume.viguier@beeznest.com>
 * @since Chamilo 1.8.7
 */
function select_timezone_value() {
    return api_get_timezones();
}

/**
 * Returns an array containing the list of options used to populate the gradebook_number_decimals variable
 *
 * @return array List of gradebook_number_decimals options
 *
 * @author Guillaume Viguier <guillaume.viguier@beeznest.com>
 */
function select_gradebook_number_decimals() {
    return array('0', '1', '2');
}

/**
 * Updates the gradebook score custom values using the scoredisplay class of the
 * gradebook module
 *
 * @param array List of gradebook score custom values
 *
 * @author Guillaume Viguier <guillaume.viguier@beeznest.com>
 */
function update_gradebook_score_display_custom_values($values) {
    require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/scoredisplay.class.php';
    $scoredisplay = ScoreDisplay::instance();
    $scores = $values['gradebook_score_display_custom_values_endscore'];
    $displays = $values['gradebook_score_display_custom_values_displaytext'];
    $nr_displays = count($displays);
    $final = array();
    for ($i = 1; $i < $nr_displays; $i++) {
        if (!empty($scores[$i]) && !empty($displays[$i])) {
            $final[$i]['score'] = $scores[$i];
            $final[$i]['display'] = $displays[$i];
        }
    }
    $scoredisplay->update_custom_score_display_settings($final);
}
