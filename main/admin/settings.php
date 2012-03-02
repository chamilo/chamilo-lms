<?php
/* For licensing terms, see /license.txt */

/**
 * With this tool you can easily adjust non critical configuration settings.
 * Non critical means that changing them will not result in a broken campus.
 *
 * @author Patrick Cool
 * @author Julio Montoya - Multiple URL site
 * @package chamilo.admin
 */

/* INIT SECTION */

// Language files that need to be included.
if (isset($_GET['category']) && $_GET['category'] == 'Templates') {
    $language_file = array('admin', 'document');
} else if(isset($_GET['category']) && $_GET['category'] == 'Gradebook') {
    $language_file = array('admin', 'gradebook');
} else {
    $language_file = array('admin', 'document');
}

// Resetting the course id.
$cidReset = true;

// Including some necessary library files.
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
require_once api_get_path(LIBRARY_PATH).'dashboard.lib.php';
require_once api_get_path(LIBRARY_PATH).'pdf.lib.php';
require_once api_get_path(LIBRARY_PATH).'plugin.lib.php';
require_once 'settings.lib.php';

// Setting the section (for the tabs).
$this_section = SECTION_PLATFORM_ADMIN;
$_SESSION['this_section'] = $this_section;

// Access restrictions.
api_protect_admin_script();

// Settings to avoid
$settings_to_avoid = array(
    'gradebook_enable'                  => 'false', 
    'use_document_title'                => 'true',
    'example_material_course_creation'  => 'true' // ON by default - now we have this option when  we create a course 
);

$convert_byte_to_mega_list = array('dropbox_max_filesize', 'message_max_upload_filesize', 'default_document_quotum', 'default_group_quotum');

// Submit stylesheets.
if (isset($_POST['submit_stylesheets'])) {
    $message = store_stylesheets();
    header("Location: ".api_get_self()."?category=stylesheets");
    exit;
}

if (isset($_POST['style'])) {
    Display::$preview_style = $_POST['style'];
}


// Database table definitions.
$table_settings_current = Database :: get_main_table(TABLE_MAIN_SETTINGS_CURRENT);

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
}

if (isset($_POST['new_model']) && isset($_POST['number_evaluations']) && !empty($_POST['new_model'])) {
	$count = intval($_POST['number_evaluations']);
	$string_to_save = '';
	for ($i = 1; $i<=$count;$i++) {
		$sum = "+";
		if ($i == $count) {
			$sum = "";
		}
		//Note: We use rand here because there is a unique index in the settings_options table. The 'variable' and 'value' fields must be unique 
		$string_to_save .= rand(1, 3).'*X'.$sum;
	}
	$string_to_save .= "/".$count;
	$array_to_save = array();
	
	$array_to_save['variable'] 		= 'grading_model';
	$array_to_save['display_text'] 	= $_POST['new_model'];
	$array_to_save['value']		 	= $string_to_save;
	
	$result = api_set_setting_option($array_to_save);
}

if (isset($_GET['action']) &&  $_GET['action'] == 'delete_grading') {
	$id = intval($_GET['id']);
	api_delete_setting_option($id);
}

$settings = null;

// Build the form.
if (!empty($_GET['category']) && !in_array($_GET['category'], array('Plugins', 'stylesheets', 'Search'))) {
    $my_category = Database::escape_string($_GET['category']);

    if ($_configuration['access_url'] == 1) {
        $settings = api_get_settings($my_category, 'group', $_configuration['access_url']);
    } else {
        $url_info = api_get_access_url($_configuration['access_url']);
        if ($url_info['active'] == 1) {
            // The default settings of Chamilo
            $settings = api_get_settings($my_category, 'group', 1, 0);
            // The settings that are changeable from a particular site.
            $settings_by_access = api_get_settings($my_category, 'group', $_configuration['access_url'], 1);
            
            $settings_by_access_list = array();
            foreach ($settings_by_access as $row) {
                if (empty($row['variable']))
                    $row['variable'] = 0;
                if (empty($row['subkey']))
                    $row['subkey'] = 0;
                if (empty($row['category']))
                    $row['category'] = 0;
                // One more validation if is changeable.
                if ($row['access_url_changeable'] == 1)
                    $settings_by_access_list[ $row['variable'] ] [ $row['subkey'] ] [ $row['category'] ]  = $row;
                else
                    $settings_by_access_list[ $row['variable'] ] [ $row['subkey'] ] [ $row['category'] ]  = array();
            }
        }
    }
    
    if (isset($_GET['category']) && $_GET['category'] == 'search_setting') {  
        if (!empty($_REQUEST['search_field'])) {
            $settings = search_setting($_REQUEST['search_field']);                               
        }
    }
    
    $form = generate_settings_form($settings, $settings_by_access_list);    
    $message = array();
    
    if ($form->validate()) {       
        $values = $form->exportValues();         
        $pdf_export_watermark_path = $_FILES['pdf_export_watermark_path'];
         
        if (isset($pdf_export_watermark_path) && !empty($pdf_export_watermark_path['name'])) {       
            $pdf_export_watermark_path_result = PDF::upload_watermark($pdf_export_watermark_path['name'], $pdf_export_watermark_path['tmp_name']);  
            if ($pdf_export_watermark_path_result) {
                $message['confirmation'][] = get_lang('UplUploadSucceeded');
            } else {                
                $message['warning'][] = get_lang('UplUnableToSaveFile').' '.get_lang('Folder').': '.api_get_path(SYS_CODE_PATH).'default_course_document/images';
            }
            unset($update_values['pdf_export_watermark_path']);
        }

        // Set true for allow_message_tool variable if social tool is actived       
        foreach ($convert_byte_to_mega_list as $item) {
			if (isset($values[$item])) {
				$values[$item]        = round($values[$item]*1024*1024);
			}
		}
		         
        if ($values['allow_social_tool'] == 'true') {
            $values['allow_message_tool'] = 'true';
        }
        
      
        // The first step is to set all the variables that have type=checkbox of the category
        // to false as the checkbox that is unchecked is not in the $_POST data and can
        // therefore not be set to false.
        // This, however, also means that if the process breaks on the third of five checkboxes, the others
        // will be set to false.
        
        //$r = api_set_settings_category($my_category, 'false', $_configuration['access_url'], array('checkbox', 'radio'));
        
        
        //This is amore accurate way of updating to false the checboxes and radios the settings
        
        foreach ($values as $key => $value) {          
            if (in_array($key, $settings_to_avoid)) { continue; }            
            $key = Database::escape_string($key);
            $sql = "UPDATE $table_settings_current SET selected_value = 'false' WHERE variable = '".$key."' AND access_url = ".intval($_configuration['access_url'])."  AND type IN ('checkbox', 'radio') ";            
            $res = Database::query($sql);            
        }
        
        /*foreach($settings_to_avoid as $key => $value) {
            api_set_setting($key, $value, null, null, $_configuration['access_url']);    
        }*/
        
        // Save the settings.
        $keys = array();
               
        foreach ($values as $key => $value) {
            if (in_array($key, $settings_to_avoid)) { continue; }
                      	
            // Treat gradebook values in separate function.
            //if (strpos($key, 'gradebook_score_display_custom_values') === false) {
                if (!is_array($value)) {
                    $old_value = api_get_setting($key);                    
                    switch ($key) {                    	
                    	case 'header_extra_content':
                    		file_put_contents(api_get_path(SYS_PATH).api_get_home_path().'/header_extra_content.txt', $value);                    		
                    		$value = api_get_home_path().'/header_extra_content.txt';
                    		break;
                    	case 'footer_extra_content':
                    		file_put_contents(api_get_path(SYS_PATH).api_get_home_path().'/footer_extra_content.txt', $value);                    		
                    		$value = api_get_home_path().'/footer_extra_content.txt';
                    		break;
                        // URL validation for some settings.
                        case 'InstitutionUrl':
                        case 'course_validation_terms_and_conditions_url':
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

                        // Validation against e-mail address for some settings.
                        case 'emailAdministrator':
                            $value = trim(Security::remove_XSS($value));
                            if ($value != '' && !api_valid_email($value)) {
                                // If the new (non-empty) e-mail address is invalid, then the old e-mail address stays.
                                // If the new e-mail address is empty, then it will be stored (i.e. the setting will be deleted).
                                $value = $old_value;
                            }
                            break;
                    }
                    if ($old_value != $value) $keys[] = $key;
                    $result = api_set_setting($key, $value, null, null, $_configuration['access_url']);
                } else {
                	if ($key == 'grading_model') {            		
                		foreach ($value as $my_key => $option) {
                			$array_to_save = array();               			
                			//build this: 1*X+2*X+3*X/4
                			$string_to_save = '';
                			foreach ($option['items'] as $item) {
                				$string_to_save .= $item.'*X+';
                			}
                			$string_to_save = substr($string_to_save, 0, strlen($string_to_save)- 1) . '/'.$option['denominator'];
                			
                			$array_to_save['display_text'] 	= $option['display_text'];
                			$array_to_save['value'] 		= $string_to_save;
                			$array_to_save['id'] 			= $my_key;               			
                			
                			$result = api_set_setting_option($array_to_save);
                		}
                	} else {
	                    $sql = "SELECT subkey FROM $table_settings_current WHERE variable = '$key'";
	                    $res = Database::query($sql);
	                    $subkeys = array();
	                    while ($row_subkeys = Database::fetch_array($res)) {
	                        // If subkey is changed:
	                        if ((isset($value[$row_subkeys['subkey']]) && api_get_setting($key, $row_subkeys['subkey']) == 'false') ||
	                            (!isset($value[$row_subkeys['subkey']]) && api_get_setting($key, $row_subkeys['subkey']) == 'true')) {
	                            $keys[] = $key;
	                            break;
	                        }
	                    }
	                    foreach ($value as $subkey => $subvalue) {	             
	                        $result = api_set_setting($key, 'true', $subkey, null, $_configuration['access_url']);	
	                    }
                	}
                }
        }

        // Add event configuration settings category to the system log.        
        $user_id = api_get_user_id();
        $category = $_GET['category'];
        event_system(LOG_CONFIGURATION_SETTINGS_CHANGE, LOG_CONFIGURATION_SETTINGS_CATEGORY, $category, api_get_utc_datetime(), $user_id);
        
        // Add event configuration settings variable to the system log.
        if (is_array($keys) && count($keys) > 0) {
            foreach ($keys as $variable) {
                if (in_array($key, $settings_to_avoid)) { continue; }
                event_system(LOG_CONFIGURATION_SETTINGS_CHANGE, LOG_CONFIGURATION_SETTINGS_VARIABLE, $variable, api_get_utc_datetime(), $user_id);
            }
        }
    }
}


$htmlHeadXtra[] = '<script language="javascript">

function delete_grading_model(id) {
	window.location = "settings.php?category=Gradebook&action=delete_grading&id=" + id;
}

	$(document).ready(function() {
		var elements = ["B","C","D","E","F","G","H","I", "J", "K", "L","M","N","O","P","Q","R"];		
		var i = 0;		
		$(".formw").each(function(index) {
			 $(this).find("*").each(function(index2) {			 		
				if ($(this).hasClass("first_number")) {
					$(this).before("<b>( A * </b>");	
				}
				if ($(this).hasClass("denominator")) {
					$(this).before("<b> \) /  </b>");						
				}
				if ($(this).hasClass("number")) {
					$(this).before("<b> + " + elements[i] + " * </b>");
					i++;
				}					
			});
			i = 0;
		});
	});
</script>';

// Including the header (banner).
Display :: display_header($tool_name);



// The action images.
$action_images['platform']      = 'platform.png';
$action_images['course']        = 'course.png';
$action_images['tools']         = 'tools.png';
$action_images['user']          = 'user.png';
$action_images['gradebook']     = 'gradebook.png';
$action_images['ldap']          = 'ldap.png';
$action_images['cas'] 	        = 'user_access.png';
$action_images['security']      = 'security.png';
$action_images['languages']     = 'languages.png';
$action_images['tuning']        = 'tuning.png';
$action_images['plugins']       = 'plugins.png';
$action_images['stylesheets']   = 'stylesheets.png';
$action_images['templates']     = 'template.png';
$action_images['search']        = 'search.png';
$action_images['editor']        = 'html_editor.png';
$action_images['timezones']     = 'timezone.png';
$action_images['extra']     	= 'wizard.png';
$action_images['tracking']     	= 'statistics.png';
$action_images['gradebook2']    = 'gradebook.png';

// Grabbing the categories.
$resultcategories = api_get_settings_categories(array('stylesheets', 'Plugins', 'Templates', 'Search'));

echo "<div class=\"actions\">";
foreach ($resultcategories as $row) {
    echo "<a href=\"".api_get_self()."?category=".$row['category']."\">".Display::return_icon($action_images[strtolower($row['category'])], api_ucfirst(get_lang($row['category'])),'',ICON_SIZE_MEDIUM)."</a>";
}
echo "<a href=\"".api_get_self()."?category=Search\">".Display::return_icon($action_images['search'], api_ucfirst(get_lang('Search')),'',ICON_SIZE_MEDIUM)."</a>";
echo "<a href=\"".api_get_self()."?category=stylesheets\">".Display::return_icon($action_images['stylesheets'], api_ucfirst(get_lang('Stylesheets')),'',ICON_SIZE_MEDIUM)."</a>";
echo "<a href=\"".api_get_self()."?category=Templates\">".Display::return_icon($action_images['templates'], api_ucfirst(get_lang('Templates')),'',ICON_SIZE_MEDIUM)."</a>";
echo "<a href=\"".api_get_self()."?category=Plugins\">".Display::return_icon($action_images['plugins'], api_ucfirst(get_lang('Plugins')),'',ICON_SIZE_MEDIUM)."</a>";
echo "</div>";

$form_search = new FormValidator('search_settings', 'get', api_get_self() , null, array('class'=>'vertical'));
$form_search->addElement('text', 'search_field');
$form_search->addElement('hidden', 'category', 'search_setting');
$form_search->addElement('style_submit_button', 'submit_button', get_lang('Search'), 'value="submit_button", class="search"');         
$form_search->setDefaults(array('search_field' => $_REQUEST['search_field']));
$form_search->display();

if ($watermark_deleted) {    
    Display :: display_normal_message(get_lang('FileDeleted'));
}

// Displaying the message that the settings have been stored.
if (isset($form) && $form->validate()) {
    
    Display::display_confirmation_message(get_lang('SettingsStored'));
    if (is_array($message)) {
        foreach($message as $type => $content) {
            foreach($content as $msg) {
                echo Display::return_message($msg, $type);
            }
        }
    }
}


if (!empty($_GET['category'])) {
    switch ($_GET['category']) {
        case 'Plugins':
            // Displaying the extensions: Plugins.
            // This will be available to all the sites (access_urls).
            if (isset($_POST['submit_dashboard_plugins'])) {
                $affected_rows = DashboardManager::store_dashboard_plugins($_POST);
                if ($affected_rows) {
                    // add event to system log                    
                    $user_id = api_get_user_id();
                    $category = $_GET['category'];
                    event_system(LOG_CONFIGURATION_SETTINGS_CHANGE, LOG_CONFIGURATION_SETTINGS_CATEGORY, $category, api_get_utc_datetime(), $user_id);
                    Display :: display_confirmation_message(get_lang('DashboardPluginsHaveBeenUpdatedSucesslly'));
                }
            }
            handle_plugins();
            DashboardManager::handle_dashboard_plugins();

            break;
        case 'stylesheets':
            // Displaying the extensions: Stylesheets.
            handle_stylesheets();
            break;
        case 'Search':
            handle_search();
            break;
        case 'Templates':
            handle_templates();
            break;
        case 'search_setting':            
            search_setting($_REQUEST['search_field']);
            if (isset($_REQUEST['search_field'])) {                
                $form->display();    
            }            
            break;
        default:
            $form->display();
    }
}

/* FOOTER */
Display :: display_footer();