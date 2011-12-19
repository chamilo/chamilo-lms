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


// Submit stylesheets.
if (isset($_POST['submit_stylesheets'])) {
    $message = store_stylesheets();
    header("Location: ".api_get_self()."?category=stylesheets");
    exit;
}

// Database table definitions.
$table_settings_current = Database :: get_main_table(TABLE_MAIN_SETTINGS_CURRENT);

// Setting breadcrumbs.
$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));

// Setting the name of the tool.
$tool_name = get_lang('DokeosConfigSettings');
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

// Build the form.
if (!empty($_GET['category']) && !in_array($_GET['category'], array('Plugins', 'stylesheets', 'Search'))) {
    $form = new FormValidator('settings', 'post', 'settings.php?category='.$_GET['category']);
    
    $renderer = & $form->defaultRenderer();
    $renderer->setElementTemplate('<div class="row">
        <div class="label">{label}</div>
        <div class="formw">{element}
           <!-- BEGIN label_3 -->
                {label_3}            
            <!-- END label_3 -->            
            <!-- BEGIN label_2 -->
                <span class="help-block">{label_2}</span>
            <!-- END label_2 -->
            </div>
        </div>');
    
    /*$renderer->setHeaderTemplate('<div class="sectiontitle">{header}</div>');
    $renderer->setElementTemplate('<div class="sectionvalue">{element}</div><div class="sectioncomment">{label}</div>'."\n");*/
    
    //$renderer->setHeaderTemplate('<div class="sectiontitle">{header}</div>');
    
    $my_category = Database::escape_string($_GET['category']);

    $sqlcountsettings = "SELECT COUNT(*) FROM $table_settings_current WHERE category='".$my_category."' AND type<>'checkbox'";
    $resultcountsettings = Database::query($sqlcountsettings);
    $countsetting = Database::fetch_array($resultcountsettings);

    if ($_configuration['access_url'] == 1) {
        $settings = api_get_settings($my_category, 'group', $_configuration['access_url']);
    } else {
        $url_info = api_get_access_url($_configuration['access_url']);
        if ($url_info['active'] == 1) {
            // The default settings of Chamilo
            $settings = api_get_settings($my_category, 'group', 1, 0);
            // The settings that are changeable from a particular site.
            $settings_by_access = api_get_settings($my_category, 'group', $_configuration['access_url'], 1);
            //echo '<pre>';
            //print_r($settings_by_access);
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
    
    $default_values = array();
    foreach ($settings as $row) {
    	if (in_array($row['variable'], $settings_to_avoid)) { continue; }

        $anchor_name = $row['variable'].(!empty($row['subkey']) ? '_'.$row['subkey'] : '');
        $form->addElement('html',"\n<a name=\"$anchor_name\"></a>\n");

        ($countsetting['0'] % 10) < 5 ? $b = $countsetting['0'] - 10 : $b = $countsetting['0'];
        if ($i % 10 == 0 and $i < $b) {
            $form->addElement('html', '<div align="right">');
            $form->addElement('style_submit_button', null, get_lang('SaveSettings'), 'class="save"');
            $form->addElement('html', '</div>');
        }

        $i++;

        //$form->addElement('header', null, get_lang($row['title']));

        if ($row['access_url_changeable'] == '1' && $_configuration['multiple_access_urls']) {
            $form->addElement('html', '<div style="float: right;">'.Display::return_icon('shared_setting.png', get_lang('SharedSettingIconComment')).'</div>');
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
                if (in_array($row['variable'], array('dropbox_max_filesize', 'message_max_upload_filesize'))) {                    
                    $form->addElement('text', $row['variable'], array(get_lang($row['title']), get_lang($row['comment']), get_lang('MB')), array('maxlength' => '5'));
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
                            //$encoding = $key.' (n.a.)';
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
                $form->addGroup($group, $row['variable'], array(get_lang($row['title']), get_lang($row['comment'])), '<div></div>', false); //julio
                $default_values[$row['variable']] = $row['selected_value'];
                break;
            case 'checkbox';
                // 1. We collect all the options of this variable.
                $sql = "SELECT * FROM settings_current WHERE variable='".$row['variable']."' AND access_url =  1";

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
                        $sql = "SELECT selected_value FROM settings_current WHERE variable='".$rowkeys['variable']."' AND subkey='".$rowkeys['subkey']."'  AND  subkeytext='".$rowkeys['subkeytext']."' AND access_url =  $access_url";
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
                $form->addGroup($group, $row['variable'], array(get_lang($row['title']), get_lang($row['comment'])),'<div></div>');
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
            	$values = api_get_settings_options($row['variable']);
            	
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
            			$group[] = $form->createElement('button', "delete", get_lang('Delete'), array('type'=>'button', 'id'=>$id, 'onclick'=>"delete_grading_model('$id');"));
            			
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
            $form->addElement('html', sprintf(get_lang('LocalTimeUsingPortalTimezoneXIsY'),$row['selected_value'],api_get_local_time()));
        }       	
    }

    $form->addElement('html', '<div style="text-align: right; clear: both;">');
    $form->addElement('style_submit_button', null, get_lang('SaveSettings'), 'class="save"');
    $form->addElement('html', '</div>');

    $form->setDefaults($default_values);    

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
        
        $values['dropbox_max_filesize']        = $values['dropbox_max_filesize']*1024*1024;
        $values['message_max_upload_filesize'] = $values['message_max_upload_filesize']*1024*1024;
         
        if ($values['allow_social_tool'] == 'true') {
            $values['allow_message_tool'] = 'true';
        }
        // quick patch to avoid gradebook_enable's value to be blanked
        /*
        if ($my_category == 'Gradebook') {
            $gb = 'false';
        	$gb = api_get_setting('gradebook_enable');
        }*/

        // The first step is to set all the variables that have type=checkbox of the category
        // to false as the checkbox that is unchecked is not in the $_POST data and can
        // therefore not be set to false.
        // This, however, also means that if the process breaks on the third of five checkboxes, the others
        // will be set to false.
        $r = api_set_settings_category($my_category, 'false', $_configuration['access_url'], array('checkbox', 'radio'));
        // quick patch to avoid gradebook_enable's value to be blanked
        if ($my_category == 'Gradebook') {
            //api_set_setting('gradebook_enable', $gb, null, $my_category, $_configuration['access_url']);
        }
        
        foreach($settings_to_avoid as $key => $value) {
            api_set_setting($key, $value, null, null, $_configuration['access_url']);    
        }        
        
        
        
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
    echo "<a href=\"".api_get_self()."?category=".$row['category']."\">".Display::return_icon($action_images[strtolower($row['category'])], api_ucfirst(get_lang($row['category'])),'','32')."</a>";
}
echo "<a href=\"".api_get_self()."?category=Search\">".Display::return_icon($action_images['search'], api_ucfirst(get_lang('Search')),'','32')."</a>";
echo "<a href=\"".api_get_self()."?category=stylesheets\">".Display::return_icon($action_images['stylesheets'], api_ucfirst(get_lang('Stylesheets')),'','32')."</a>";
echo "<a href=\"".api_get_self()."?category=Templates\">".Display::return_icon($action_images['templates'], api_ucfirst(get_lang('Templates')),'','32')."</a>";
echo "<a href=\"".api_get_self()."?category=Plugins\">".Display::return_icon($action_images['plugins'], api_ucfirst(get_lang('Plugins')),'','32')."</a>";
echo "</div>";


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
        default:
            $form->display();
    }
}

/* FOOTER */
Display :: display_footer();
