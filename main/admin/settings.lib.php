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
 * This function allows easy activating and inactivating of regions
 * @author Julio Montoya <gugli100@gmail.com> Beeznest 2012
 */
function handle_regions() {
    
    if (isset($_POST['submit_plugins'])) {
        store_regions();
        // Add event to the system log.        
        $user_id = api_get_user_id();
        $category = $_GET['category'];
        event_system(LOG_CONFIGURATION_SETTINGS_CHANGE, LOG_CONFIGURATION_SETTINGS_CATEGORY, $category, api_get_utc_datetime(), $user_id);
        Display :: display_confirmation_message(get_lang('SettingsStored'));
    }
    
    $plugin_obj = new AppPlugin();
    $possible_plugins  = $plugin_obj->read_plugins_from_path();
    $installed_plugins = $plugin_obj->get_installed_plugins(); 

    if (!empty($installed_plugins)) {
        $not_installed = array_diff($possible_plugins, $installed_plugins);
    } else {
        $not_installed = $possible_plugins;
    }    
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
    
    $plugin_list = array();
    $my_plugin_list = $plugin_obj->get_plugin_regions();
    foreach($my_plugin_list as $plugin_item) {
        $plugin_list[$plugin_item] = $plugin_item;
    }

    foreach ($installed_plugins as $plugin) {
        $plugin_info_file = api_get_path(SYS_PLUGIN_PATH).$plugin.'/plugin.php';
        
        if (file_exists($plugin_info_file)) {
            $plugin_info = array();
            require $plugin_info_file;
            if (isset($_GET['name']) && $_GET['name'] == $plugin) {
                echo '<tr class="row_selected">';
            } else {
                echo '<tr>';
            }
            echo '<td>';            
            echo '<h4>'.$plugin_info['title'].' <small>v'.$plugin_info['version'].'</small></h4>';
            echo '<p>'.$plugin_info['comment'].'</p>';              
            echo '</td><td>';                        
            $selected_plugins = $plugin_obj->get_areas_by_plugin($plugin);            
            echo Display::select('plugin_'.$plugin.'[]', $plugin_list, $selected_plugins, array('multiple' => 'multiple', 'style' => 'width:500px'), true, get_lang('None'));    
            echo '</td></tr>';
        }
    }
    echo '</table>';
    echo '<br />';
    echo '<button class="save" type="submit" name="submit_plugins">'.get_lang('EnablePlugins').'</button></form>';
    echo '<br />';    
}

function handle_extensions() {    
    echo '<div class="page-header"><h2>'.get_lang('ConfigureExtensions').'</h2></div>';
    echo '<a class="btn" href="configure_extensions.php?display=ppt2lp">'.get_lang('Ppt2lp').'</a>';    
    
}
/**
 * This function allows easy activating and inactivating of plugins
 * @todo: a similar function needs to be written to activate or inactivate additional tools.
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @author Julio Montoya <gugli100@gmail.com> Beeznest 2012
 */
function handle_plugins() {
    $plugin_obj = new AppPlugin();
    
     if (isset($_POST['submit_plugins'])) {
        store_plugins();
        // Add event to the system log.        
        $user_id = api_get_user_id();
        $category = $_GET['category'];
        event_system(LOG_CONFIGURATION_SETTINGS_CHANGE, LOG_CONFIGURATION_SETTINGS_CATEGORY, $category, api_get_utc_datetime(), $user_id);
        Display :: display_confirmation_message(get_lang('SettingsStored'));
    }
    
    $all_plugins = $plugin_obj->read_plugins_from_path(); 
    $installed_plugins = $plugin_obj->get_installed_plugins(); 
    $not_installed = array_diff($all_plugins, $installed_plugins);    
    
    //Plugins NOT installed
    echo '<div class="page-header"><h2>'.get_lang('Plugins').'</h2></div>';
    echo '<form name="plugins" method="post" action="'.api_get_self().'?category='.Security::remove_XSS($_GET['category']).'">';
    echo '<table class="data_table">';
    echo '<tr>';
    echo '<th width="20px">';
    echo get_lang('Action');
    echo '</th><th>';
    echo get_lang('Description');    
    echo '</th>';
    echo '</tr>';
    
    $plugin_list = array();
    $my_plugin_list = $plugin_obj->get_plugin_regions();
    foreach($my_plugin_list as $plugin_item) {
        $plugin_list[$plugin_item] = $plugin_item;
    }

    foreach ($all_plugins as $plugin) {
        $plugin_info_file = api_get_path(SYS_PLUGIN_PATH).$plugin.'/plugin.php';
        
        if (file_exists($plugin_info_file)) {
            $plugin_info = array();
            require $plugin_info_file;
            if (in_array($plugin, $installed_plugins)) {
                echo '<tr class="row_selected">';
            } else {
                echo '<tr>';
            }
            //echo '<tr>';
            echo '<td>';
            //Checkbox
            if (in_array($plugin, $installed_plugins)) {              
                echo '<input type="checkbox" name="plugin_'.$plugin.'[]" checked="checked">';
               
            } else {            
                echo '<input type="checkbox" name="plugin_'.$plugin.'[]">';
            }
            echo '</td><td>';
            
            echo '<h4>'.$plugin_info['title'].' <small>v '.$plugin_info['version'].'</small></h4>';
            echo '<p>'.$plugin_info['comment'].'</p>';
            echo '<p>'.get_lang('Author').': '.$plugin_info['author'].'</p>';
            
            echo '<div class="btn-group">';
            if (in_array($plugin, $installed_plugins)) {     
                 echo Display::url(get_lang('Configure'), 'configure_plugin.php?name='.$plugin, array('class' => 'btn'));
                 echo Display::url(get_lang('Regions'), 'settings.php?category=Regions&name='.$plugin, array('class' => 'btn'));
            }
            
            if (file_exists(api_get_path(SYS_PLUGIN_PATH).$plugin.'/readme.txt')) {
                 echo Display::url("readme.txt", api_get_path(WEB_PLUGIN_PATH).$plugin."/readme.txt", array('class' => 'btn ajax', '_target' => '_blank'));
            }
            echo '</div>';
            echo '</td></tr>';        
        }
    }
    echo '</table>';
    echo '<br />';
    echo '<button class="save" type="submit" name="submit_plugins">'.get_lang('EnablePlugins').'</button></form>';
    echo '<br />';
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
    echo '<form id="stylesheets_id" name="stylesheets" class="form-search" method="post" action="'.api_get_self().'?category='.Security::remove_XSS($_GET['category']).'">';
    echo '<br /><select name="style" onchange="load_preview(this)" >';

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
        echo '<button class="btn save" type="submit" name="submit_stylesheets"> '.get_lang('SaveSettings').' </button></form>';
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

function store_regions() {
     $plugin_obj = new AppPlugin();

    // Get a list of all current 'Plugins' settings
    $installed_plugins = $plugin_obj->get_installed_plugins();
    
    $shortlist_installed = array();
    foreach ($installed_plugins as $plugin) {
        $shortlist_installed[] = $plugin['subkey'];
    }
    $shortlist_installed = array_flip(array_flip($shortlist_installed));
    $plugin_list = $plugin_obj->read_plugins_from_path();

    foreach ($plugin_list as $plugin) {
        if (isset($_POST['plugin_'.$plugin])) {
            $areas_to_installed = $_POST['plugin_'.$plugin];            
            if (!empty($areas_to_installed)) {
                $plugin_obj->remove_all_regions($plugin);
                foreach ($areas_to_installed as $region) {
                    if (!empty($region) && $region != '-1' ) {
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
function store_plugins() {

    $plugin_obj = new AppPlugin();

    // Get a list of all current 'Plugins' settings
   
    $plugin_list = $plugin_obj->read_plugins_from_path();
    
    $installed_plugins = array();
    
    foreach ($plugin_list as $plugin) {
        if (isset($_POST['plugin_'.$plugin])) {
            $plugin_obj->install($plugin);
            $installed_plugins[] = $plugin;
        }
    }
    
    if (!empty($installed_plugins)) {
        $remove_plugins = array_diff($plugin_list, $installed_plugins);
    } else {
        $remove_plugins = $plugin_list;
    }    
    foreach ($remove_plugins as $plugin) {
        $plugin_obj->uninstall($plugin);
    }  
}

/**
 * This function allows the platform admin to choose which should be the default stylesheet
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function store_stylesheets() {
    global $_configuration;

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

function generate_settings_form($settings, $settings_by_access_list) {
    $table_settings_current = Database :: get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
    global $_configuration, $settings_to_avoid, $convert_byte_to_mega_list;
    
    $form = new FormValidator('settings', 'post', 'settings.php?category='.Security::remove_XSS($_GET['category']));
    $form ->addElement('hidden', 'search_field', Security::remove_XSS($_GET['search_field']));
    
    $default_values = array();
    $count_settings = count($settings);
    
    foreach ($settings as $row) {
        
    	if (in_array($row['variable'], $settings_to_avoid)) { continue; }

        $anchor_name = $row['variable'].(!empty($row['subkey']) ? '_'.$row['subkey'] : '');
        $form->addElement('html',"\n<a name=\"$anchor_name\"></a>\n");

        ($count_settings % 10) < 5 ? $b = $count_settings - 10 : $b = $count_settings;
        if ($i % 10 == 0 and $i < $b AND $i != 0) {
            $form->addElement('html', '<div align="right">');
            $form->addElement('style_submit_button', null, get_lang('SaveSettings'), 'class="save"');
            $form->addElement('html', '</div>');
        }

        $i++;

        if ( $_configuration['multiple_access_urls']) {
            if (api_is_global_platform_admin()) {
                if ($row['access_url_changeable'] == '1') {
                    $form->addElement('html', '<div style="float: right;"><a class="share_this_setting" data_status = "0"  data_to_send = "'.$row['id'].'" href="javascript:void(0);">'.Display::return_icon('shared_setting.png', get_lang('ChangeSharedSetting')).'</a></div>');
                } else {
                    $form->addElement('html', '<div style="float: right;"><a class="share_this_setting" data_status = "1" data_to_send = "'.$row['id'].'" href="javascript:void(0);">'.Display::return_icon('shared_setting_na.png', get_lang('ChangeSharedSetting')).'</a></div>');
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
                if (empty($row['variable']))
                    $row['variable'] = 0;
                if (empty($row['subkey']))
                    $row['subkey'] = 0;
                if (empty($row['category']))
                    $row['category'] = 0;

                if (is_array($settings_by_access_list[ $row['variable'] ] [ $row['subkey'] ] [ $row['category'] ])) {
                    // We are sure that the other site have a selected value.
                    if ($settings_by_access_list[ $row['variable'] ] [ $row['subkey'] ] [ $row['category'] ]['selected_value'] != '')
                        $row['selected_value'] =$settings_by_access_list[$row['variable']] [$row['subkey']] [ $row['category'] ]['selected_value'];
                }
                // There is no else{} statement because we load the default $row['selected_value'] of the main Chamilo site.
            }
        }		
		        
        switch ($row['type']) {
            case 'textfield':
                if (in_array($row['variable'], $convert_byte_to_mega_list)) {                    
                    $form->addElement('text', $row['variable'], array(get_lang($row['title']), get_lang($row['comment']), get_lang('MB')), array('maxlength' => '8'));
                    $form->applyFilter($row['variable'], 'html_filter');
                    $default_values[$row['variable']] = round($row['selected_value']/1024/1024, 1);                    
                } elseif ($row['variable'] == 'account_valid_duration') {
                    $form->addElement('text', $row['variable'], array(get_lang($row['title']), get_lang($row['comment'])), array('maxlength' => '5'));
                    $form->applyFilter($row['variable'], 'html_filter');
                    $default_values[$row['variable']] = $row['selected_value'];

                    // For platform character set selection: Conversion of the textfield to a select box with valid values.
                } elseif ($row['variable'] == 'platform_charset') {
                    $current_system_encoding = api_refine_encoding_id(trim($row['selected_value']));
                    $valid_encodings = array_flip(api_get_valid_encodings());
                    if (!isset($valid_encodings[$current_system_encoding])) {
                        $is_alias_encoding = false;
                        foreach ($valid_encodings as $encoding) {
                            if (api_equal_encodings($encoding, $current_system_encoding)) {
                                $is_alias_encoding = true;
                                $current_system_encoding = $encoding;
                                break;
                            }
                        }
                        if (!$is_alias_encoding) {
                            $valid_encodings[$current_system_encoding] = $current_system_encoding;
                        }
                    }
                    foreach ($valid_encodings as $key => &$encoding) {
                        if (api_is_encoding_supported($key) && Database::is_encoding_supported($key)) {
                            $encoding = $key;
                        } else {                            
                            unset($valid_encodings[$key]);
                        }
                    }                    
                    $form->addElement('select', $row['variable'], array(get_lang($row['title']), get_lang($row['comment'])), $valid_encodings);
                    $default_values[$row['variable']] = $current_system_encoding;                                
                } else {                    
                    $form->addElement('text', $row['variable'], array(get_lang($row['title']), get_lang($row['comment'])), $hideme);                    
                    $form->applyFilter($row['variable'],'html_filter');
                    $default_values[$row['variable']] = $row['selected_value'];
                }
                break;
            case 'textarea':            	
                if ($row['variable'] == 'header_extra_content') {
      	            $file = api_get_path(SYS_PATH).api_get_home_path().'header_extra_content.txt';
                    $value = '';
                    if (file_exists($file)) {
                        $value = file_get_contents($file);
                    }
                    $form->addElement('textarea', $row['variable'], array(get_lang($row['title']), get_lang($row['comment'])) , array('rows'=>'10','cols'=>'50'), $hideme);
            	    $default_values[$row['variable']] = $value;            	        
                } elseif ($row['variable'] == 'footer_extra_content') {
            		$file = api_get_path(SYS_PATH).api_get_home_path().'footer_extra_content.txt';
            		$value = '';
            		if (file_exists($file)) {
						$value = file_get_contents($file);
            		}
            	    $form->addElement('textarea', $row['variable'], array(get_lang($row['title']), get_lang($row['comment'])) , array('rows'=>'10','cols'=>'50'), $hideme);
            	    $default_values[$row['variable']] = $value;            	        
            	} else {
                	$form->addElement('textarea', $row['variable'], array(get_lang($row['title']), get_lang($row['comment'])) , array('rows'=>'10','cols'=>'50'), $hideme);
                	$default_values[$row['variable']] = $row['selected_value'];
            	}
                break;
            case 'radio':
                $values = api_get_settings_options($row['variable']);
                $group = array ();
                if (is_array($values )) {
                    foreach ($values as $key => $value) {
                        $element = & $form->createElement('radio', $row['variable'], '', get_lang($value['display_text']), $value['value']);
                        if ($hide_element) {
                            $element->freeze();
                        }
                        $group[] = $element;
                    }
                }
                $form->addGroup($group, $row['variable'], array(get_lang($row['title']), get_lang($row['comment'])), '', false); //julio
                $default_values[$row['variable']] = $row['selected_value'];
                break;
            case 'checkbox';
                // 1. We collect all the options of this variable.
                $sql = "SELECT * FROM $table_settings_current WHERE variable='".$row['variable']."' AND access_url =  1";

                $result = Database::query($sql);
                $group = array ();
                while ($rowkeys = Database::fetch_array($result)) {
                     //if ($rowkeys['variable'] == 'course_create_active_tools' && $rowkeys['subkey'] == 'enable_search') { continue; }

                     // Profile tab option should be hidden when the social tool is enabled.
                     if (api_get_setting('allow_social_tool') == 'true') {
                         if ($rowkeys['variable'] == 'show_tabs' && $rowkeys['subkey'] == 'my_profile') { continue; }
                     }

                     // Hiding the gradebook option.
                     if ($rowkeys['variable'] == 'show_tabs' && $rowkeys['subkey'] == 'my_gradebook') { continue; }

                    $element = & $form->createElement('checkbox', $rowkeys['subkey'], '', get_lang($rowkeys['subkeytext']));
                    if ($row['access_url_changeable'] == 1) {
                        // 2. We look into the DB if there is a setting for a specific access_url.
                        $access_url = $_configuration['access_url'];
                        if (empty($access_url )) $access_url = 1;
                        $sql = "SELECT selected_value FROM $table_settings_current WHERE variable='".$rowkeys['variable']."' AND subkey='".$rowkeys['subkey']."'  AND  subkeytext='".$rowkeys['subkeytext']."' AND access_url =  $access_url";
                        $result_access = Database::query($sql);
                        $row_access = Database::fetch_array($result_access);
                        if ($row_access['selected_value'] == 'true' && !$form->isSubmitted()) {
                            $element->setChecked(true);
                        }
                    } else {
                        if ($rowkeys['selected_value'] == 'true' && !$form->isSubmitted()) {
                            $element->setChecked(true);
                        }
                    }
                    if ($hide_element) {
                        $element->freeze();
                    }
                    $group[] = $element;
                }
                $form->addGroup($group, $row['variable'], array(get_lang($row['title']), get_lang($row['comment'])),'');
                break;
            case 'link':
                $form->addElement('static', null, array(get_lang($row['title']), get_lang($row['comment'])), get_lang('CurrentValue').' : '.$row['selected_value'], $hideme);
                break;
            /*
             * To populate its list of options, the select type dynamically calls a function that must be called select_ + the name of the variable being displayed.
             * The functions being called must be added to the file settings.lib.php.
             */
            case 'select':
                $form->addElement('select', $row['variable'], array(get_lang($row['title']), get_lang($row['comment'])), call_user_func('select_'.$row['variable']), $hideme);
                $default_values[$row['variable']] = $row['selected_value'];
                break;
            case 'custom_gradebook':
            case 'custom':
            	/*$values = api_get_settings_options($row['variable']);
            	
            	//$renderer = & $form->defaultRenderer();
            	//$renderer->setElementTemplate('{label} - {element}<!-- BEGIN label_2 --><span class="help-block">{label_2}</span><!-- END label_2 -->');
            	
            	$numbers = array();
            	for($j=1;$j<=20;$j++) {
            		$numbers[$j] = $j;
            	}
            	
            	if (!empty($values)) {            		
            		foreach($values as $option) {
            			$group = array();
            			$id = $option['id'];            			            			
            			$option_id = $row['variable']."[$id]";            			
            			$group[] = $form->createElement('text', $option_id.'[display_text]', array(get_lang($row['title']), get_lang($row['comment'])),'class="begin_model"');
            			
            			$default_values[$option_id.'[display_text]'] = $option['display_text'];
            			$parts = api_grading_model_functions($option['value'], 'to_array');            			
            			$denominator = $parts['denominator'];            			
            			$j = 1;            			
            			foreach($parts['items'] as $item) {  
            				$letter = $item['letter'];
            				$value  = $item['value'];
            				$group[] =$form->createElement('static','<div>');
            				$class = 'number';
            				if ($j == 1) {
            					$class = 'first_number'; 
            				}
            				$group[] = $form->createElement('select', $option_id.'[items]['.$j.']', array('dd'), $numbers, array('class'=>$class));
            				$sum = ' ';
            				if ($j != count($parts['items'])) {
            					$sum = ' + ';
            				}
            				//$group[] =$form->createElement('static',' * '.$letter.$sum);
            				
            				$default_values[$option_id.'[items]['.$j.']'] = $value;            				
            				$j++;            				
            			}
            			
            			$group[] = $form->createElement('select', $option_id.'[denominator]', array('/'), $numbers,'class="denominator"');            			
            			$group[] = $form->createElement('button', "delete", get_lang('Delete'), array('type'=>'button', 'class' => 'btn btn-danger','id'=>$id, 'onclick'=>"delete_grading_model('$id');"));
            			
            			$default_values[$option_id.'[denominator]'] = $denominator;
            			$form->addGroup($group, '', get_lang($row['title']), ' ');
            		}     		
            	}
            	
            	//New Grading Model form
            	$group = array();
            	
            	$group[] = $form->createElement('text',   'new_model', array(get_lang('AddNewModel')));            	
            	$group[] = $form->createElement('select', 'number_evaluations', array(''), $numbers,''); 
            	
            	$form->addGroup($group, '', get_lang('AddNewModel'), "&nbsp;&nbsp;".get_lang('NumberOfSubEvaluations')."&nbsp;");
            	
            	$form->addElement('style_submit_button', null, get_lang('Add'), 'class="add"');
            	*/
            	
            	break;
            /*
             * Used to display custom values for the gradebook score display
             */
            /* this configuration is moved now inside gradebook tool
            case 'gradebook_score_display_custom':
                if(api_get_setting('gradebook_score_display_custom', 'my_display_custom') == 'false') {
                    $form->addElement('static', null, null, get_lang('GradebookActivateScoreDisplayCustom'));
                } else {
                    // Get score displays.
                    require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/scoredisplay.class.php';
                    $scoredisplay = ScoreDisplay::instance();
                    $customdisplays = $scoredisplay->get_custom_score_display_settings();
                    $nr_items = (count($customdisplays)!='0') ? count($customdisplays) : '1';
                    $form->addElement('hidden', 'gradebook_score_display_custom_values_maxvalue', '100');
                    $form->addElement('hidden', 'gradebook_score_display_custom_values_minvalue', '0');
                    $form->addElement('static', null, null, get_lang('ScoreInfo'));
                    $scorenull[] = $form->CreateElement('static', null, null, get_lang('Between'));
                    $form->setDefaults(array (
                        'beginscore' => '0'
                    ));
                    $scorenull[] = $form->CreateElement('text', 'beginscore', null, array (
                        'size' => 5,
                        'maxlength' => 5,
                        'disabled' => 'disabled'
                    ));
                    $scorenull[] = $form->CreateElement('static', null, null, ' %');
                    $form->addGroup($scorenull, '', '', ' ');
                    for ($counter= 1; $counter <= 20; $counter++) {
                        $renderer = $form->defaultRenderer();
                        $elementTemplateTwoLabel =
                        '<div id=' . $counter . ' style="display: '.(($counter<=$nr_items)?'inline':'none').';">
                        <p><!-- BEGIN required --><span class="form_required">*</span> <!-- END required -->{label}
                        <div class="formw"><!-- BEGIN error --><span class="form_error">{error}</span><br /><!-- END error --> <b>'.get_lang('And').'</b>&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp{element} % =';

                        $elementTemplateTwoLabel2 =
                        '<!-- BEGIN error --><span class="form_error">{error}</span><br /><!-- END error -->&nbsp{element}
                        <a href="javascript:minItem(' . ($counter) . ')"><img style="display: '.(($counter >= $nr_items && $counter != 1) ? 'inline' : 'none').';" id="min-' . $counter . '" src="../img/gradebook_remove.gif" alt="'.get_lang('Delete').'" title="'.get_lang('Delete').'"></img></a>
                        <a href="javascript:plusItem(' . ($counter+1) . ')"><img style="display: '.(($counter >= $nr_items) ? 'inline' : 'none').';" id="plus-' . ($counter+1) . '" src="../img/gradebook_add.gif" alt="'.get_lang('Add').'" title="'.get_lang('Add').'"></img></a>
                        </div></p></div>';

                        $scorebetw= array ();
                        $form->addElement('text', 'gradebook_score_display_custom_values_endscore[' . $counter . ']', null, array (
                            'size' => 5,
                            'maxlength' => 5,
                            'id' => 'txta-'.$counter
                        ));
                        $form->addElement('text', 'gradebook_score_display_custom_values_displaytext[' . $counter . ']', null,array (
                            'size' => 40,
                            'maxlength' => 40,
                            'id' => 'txtb-'.$counter
                        ));
                        $renderer->setElementTemplate($elementTemplateTwoLabel,'gradebook_score_display_custom_values_endscore[' . $counter . ']');
                        $renderer->setElementTemplate($elementTemplateTwoLabel2,'gradebook_score_display_custom_values_displaytext[' . $counter . ']');
                        $form->addRule('gradebook_score_display_custom_values_endscore[' . $counter . ']', get_lang('OnlyNumbers'), 'numeric');
                        $form->addRule(array('gradebook_score_display_custom_values_endscore[' . $counter . ']', 'gradebook_score_display_custom_values_maxvalue'), get_lang('Over100'), 'compare', '<=');
                        $form->addRule(array('gradebook_score_display_custom_values_endscore[' . $counter . ']', 'gradebook_score_display_custom_values_minvalue'), get_lang('UnderMin'), 'compare', '>');
                        if ($customdisplays[$counter - 1]) {
                            $default_values['gradebook_score_display_custom_values_endscore['.$counter.']'] = $customdisplays[$counter - 1]['score'];
                            $default_values['gradebook_score_display_custom_values_displaytext['.$counter.']'] = $customdisplays[$counter - 1]['display'];
                        }
                    }
                }
                break;
                */
        }        
        
        if ($row['variable'] == 'pdf_export_watermark_enable') {
        	$url =  PDF::get_watermark($course_code);
            $form->addElement('file', 'pdf_export_watermark_path', get_lang('AddWaterMark'));
            if ($url != false) {                
                $delete_url = '<a href="?delete_watermark">'.Display::return_icon('delete.png',get_lang('DelImage')).'</a>';
                $form->addElement('html', '<a href="'.$url.'">'.$url.' '.$delete_url.'</a>');
            }   
            $allowed_picture_types = array ('jpg', 'jpeg', 'png', 'gif');
            $form->addRule('pdf_export_watermark_path', get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')', 'filetype', $allowed_picture_types);    
        }
        
        if ($row['variable'] == 'timezone_value') {
            $timezone = $row['selected_value'];
            if (empty($timezone)) {
                $timezone = _api_get_timezone();
            }
            $form->addElement('html', sprintf(get_lang('LocalTimeUsingPortalTimezoneXIsY'), $timezone, api_get_local_time()));
        }        
    }
    
    if (!empty($settings)) {
        $form->addElement('html', '<div style="text-align: right; clear: both;">');
        $form->addElement('style_submit_button', null, get_lang('SaveSettings'), 'class="save"');
        $form->addElement('html', '</div>');
        $form->setDefaults($default_values); 
    }
    return $form;
    
}

/**
 * Searchs a platform setting
 * @param string $search
 * @return array
 */
function search_setting($search) {
    $table_settings_current = Database :: get_main_table(TABLE_MAIN_SETTINGS_CURRENT);    
    $sql = "SELECT * FROM $table_settings_current GROUP BY variable ORDER BY id ASC ";
    $result = Database::store_result(Database::query($sql), 'ASSOC');
    $settings = array();
    
    $search = api_strtolower($search);
    
    if (!empty($result)) {
        foreach ($result as $setting) {
            $found = false;
            
            $title = api_strtolower(get_lang($setting['title']));
            //try the title
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
