<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.glossary
 * @author Christian Fasanando, initial version
 * @author Bas Wijnen import/export to CSV
 */

// The language file that needs to be included.
$language_file = array('glossary', 'admin');

// Including the global initialization file.
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'glossary.lib.php';
require_once api_get_path(LIBRARY_PATH).'export.lib.inc.php';
require_once api_get_path(LIBRARY_PATH).'import.lib.php';

$current_course_tool  = TOOL_GLOSSARY;

// The section (tabs).
$this_section = SECTION_COURSES;

// Notification for unauthorized people.
api_protect_course_script(true);

// Additional javascripts.
$htmlHeadXtra[] = GlossaryManager::javascript_glossary();
$htmlHeadXtra[] = '<script type="text/javascript">
function setFocus(){
    $("#glossary_title").focus();
}
$(document).ready(function () {
    setFocus();
});
</script>';
// setting the tool constants
$tool = TOOL_GLOSSARY;

// Tracking
event_access_tool(TOOL_GLOSSARY);

function sorter($item1, $item2) {
	if ($item1[2] == $item2[2])
		return 0;
	return $item1[2] < $item2[2] ? -1 : 1;
}

// Displaying the header
$action = isset($_GET['action']) ? $_GET['action'] : null;

$tool = 'GlossaryManagement';

$interbreadcrumb[] = array ("url"=>"index.php", "name"=> get_lang('Glossary'));         

if (!empty($action)) {
    
}
switch ($action) {
    case 'addglossary':            
        $tool_name =  get_lang('Add');
        break;
    case 'edit_glossary':         
        $tool_name =  get_lang('Edit');                    
        break;                
    case 'import':
        $tool_name =  get_lang('ImportGlossary');        
        break;
    case 'changeview':
        $tool_name =  get_lang('List');        
        break;    
}
            
if (isset($_GET['action']) && $_GET['action'] == 'export') {	
	$data = GlossaryManager::get_glossary_data(0, GlossaryManager::get_number_glossary_terms (api_get_session_id()), 0, 'ASC');
    
    usort($data, "sorter");
    $list = array();
    $list[] = array('term','definition');
    foreach($data as $line) {
        $list[] = array ($line[0], $line[1]);
    }    
    $filename = 'glossary_course_'.api_get_course_id();
	Export::export_table_csv_utf8($list, $filename);
}

Display::display_header($tool_name);

// Tool introduction
Display::display_introduction_section(TOOL_GLOSSARY);

if (isset($_GET['action']) && $_GET['action'] == 'changeview' AND in_array($_GET['view'],array('list','table'))) {
    $_SESSION['glossary_view'] = $_GET['view'];
} else {
    if (!isset($_SESSION['glossary_view'])) {
        $_SESSION['glossary_view'] = 'table';//Default option
    }
}

if (api_is_allowed_to_edit(null, true)) {
    
    switch ($action) {
        case 'addglossary':          
            $form = new FormValidator('glossary','post', api_get_self().'?action='.Security::remove_XSS($_GET['action']));
            // settting the form elements
            $form->addElement('header', '', get_lang('TermAddNew'));
            $form->addElement('text', 'glossary_title', get_lang('TermName'), array('size'=>'80', 'id'=>'glossary_title'));
            //$form->applyFilter('glossary_title', 'html_filter');
            $form->addElement('html_editor', 'glossary_comment', get_lang('TermDefinition'), null, array('ToolbarSet' => 'Glossary', 'Width' => '90%', 'Height' => '300'));
            $form->addElement('style_submit_button', 'SubmitGlossary', get_lang('TermAddButton'), 'class="save"');
            // setting the rules
            $form->addRule('glossary_title',get_lang('ThisFieldIsRequired'), 'required');
            // The validation or display
            if ($form->validate()) {
                $check = Security::check_token('post');
                if ($check) {
                    $values = $form->exportValues();
                    GlossaryManager::save_glossary($values);
                }
                Security::clear_token();
                GlossaryManager::display_glossary();
            } else {
                $token = Security::get_token();
                $form->addElement('hidden','sec_token');
                $form->setConstants(array('sec_token' => $token));
                $form->display();                
            }
            break;
        case 'edit_glossary':
            if (is_numeric($_GET['glossary_id'])) {
                // initiate the object
                $form = new FormValidator('glossary','post', api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&glossary_id='.Security::remove_XSS($_GET['glossary_id']));
                // settting the form elements
                $form->addElement('header', '', get_lang('TermEdit'));
                $form->addElement('hidden', 'glossary_id');
                $form->addElement('text', 'glossary_title', get_lang('TermName'),array('size'=>'80'));
                //$form->applyFilter('glossary_title', 'html_filter');
                $form->addElement('html_editor', 'glossary_comment', get_lang('TermDefinition'), null, array('ToolbarSet' => 'Glossary', 'Width' => '90%', 'Height' => '300'));        
                $element = $form->addElement('text', 'insert_date', get_lang('CreationDate'),array('size'=>'100'));
                $element ->freeze();        
                $element = $form->addElement('text', 'update_date', get_lang('UpdateDate'),array('size'=>'100'));
                $element ->freeze();       
                $form->addElement('style_submit_button', 'SubmitGlossary', get_lang('TermUpdateButton'), 'class="save"');

                // setting the defaults
                $glossary_data = GlossaryManager::get_glossary_information($_GET['glossary_id']);

                // Date treatment for timezones
                if (!empty($glossary_data['insert_date'])  && $glossary_data['insert_date'] != '0000-00-00 00:00:00:') {
                    $glossary_data['insert_date'] = api_get_local_time($glossary_data['insert_date']);
                } else {
                    $glossary_data['insert_date']  = '';
                }

                if (!empty($glossary_data['update_date'])  && $glossary_data['update_date'] != '0000-00-00 00:00:00:') {
                    $glossary_data['update_date'] = api_get_local_time($glossary_data['update_date']);
                } else {
                    $glossary_data['update_date']  = '';
                }

                $form->setDefaults($glossary_data);

                // setting the rules
                $form->addRule('glossary_title', get_lang('ThisFieldIsRequired'), 'required');

                // The validation or display
                if ($form->validate()) {
                    $check = Security::check_token('post');
                    if ($check) {
                    $values = $form->exportValues();
                    GlossaryManager::update_glossary($values);
                    }
                    Security::clear_token();
                    GlossaryManager::display_glossary();
                } else {
                    $token = Security::get_token();
                    $form->addElement('hidden', 'sec_token');
                    $form->setConstants(array('sec_token' => $token));
                    $form->display();
                }                
            }
            break;
        case 'delete_glossary':
            GlossaryManager::delete_glossary($_GET['glossary_id']);
            GlossaryManager::display_glossary();
            break;
        case 'moveup':
            //GlossaryManager::move_glossary('up',$_GET['glossary_id']); //actions not available
            GlossaryManager::display_glossary();
            break;
        case 'movedown':
            //GlossaryManager::move_glossary('down',$_GET['glossary_id']); //actions not available
            GlossaryManager::display_glossary();
            break;
        case 'import':
            $form = new FormValidator('glossary','post', api_get_self().'?action=import');
            $form->addElement('header', '', get_lang('ImportGlossary'));            
            $form->addElement('file', 'file', get_lang('ImportCSVFileLocation'));
            $form->addElement('checkbox', 'replace', null, get_lang('DeleteAllGlossaryTerms'));
            $form->addElement('style_submit_button', 'SubmitImport', get_lang('Import'), 'class="save"');
            $form->display();       
            
            echo get_lang('CSVMustLookLike').' ('.get_lang('MandatoryFields').')';
            echo '<pre>
                    <strong>term</strong>;<strong>definition</strong>;
                    "Hello";"Hola";
                    "Good";"Bueno";</pre>';
            
            if ($form->validate()) {
                //this is a bad idea //jm
                if (isset($_POST['replace']) && $_POST['replace']) {
                    foreach (GlossaryManager::get_glossary_terms() as $term) {
                        if (!GlossaryManager::delete_glossary($term['id'], false)) {
                            Display::display_error_message (get_lang ("CannotDeleteGlossary") . ':' . $term['id']);
                        }
                    }
                }  
                //$data = Import::csv_to_array($_FILES['file']['tmp_name']);     
                $data = Import::csv_reader($_FILES['file']['tmp_name']);
                $good = 0;
                $bad = 0;                    
                foreach($data as $item) {                          
                    if (GlossaryManager::save_glossary(array('glossary_title' => $item['term'], 'glossary_comment' => $item['definition']), false)) 
                        $good++;
                    else
                        $bad++;
                }
                      
                Display::display_confirmation_message (get_lang ("TermsImported") . ':' . $good);
                
                if ($bad)
                    Display::display_error_message (get_lang ("TermsNotImported") . ':' . $bad);
                
                GlossaryManager::display_glossary();                
            }
            break;        
        default:
            GlossaryManager::display_glossary();
            break;        
    }
} else {
    GlossaryManager::display_glossary();
}

// Footer
Display::display_footer();