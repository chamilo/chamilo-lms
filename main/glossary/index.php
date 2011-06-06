<?php //$id: $
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.glossary
 * @author Christian Fasanando, initial version
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium, refactoring and tighter integration in Dokeos
 */

// The language file that needs to be included.
$language_file = array('glossary');

// Including the global initialization file.
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'sortabletable.class.php';
require_once api_get_path(LIBRARY_PATH).'glossary.lib.php';

// The section (tabs).
$this_section = SECTION_COURSES;

// Notification for unauthorized people.
api_protect_course_script(true);

// Including additional libraries.
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';

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

// Displaying the header

if (isset($_GET['action']) && ($_GET['action'] == 'addglossary' || $_GET['action'] == 'edit_glossary')) {
    $tool='GlossaryManagement';
    $interbreadcrumb[] = array ("url"=>"index.php", "name"=> get_lang('ToolGlossary'));
}

Display::display_header(get_lang(ucfirst($tool)));

// Tool introduction
Display::display_introduction_section(TOOL_GLOSSARY);

if ($_GET['action'] == 'changeview' AND in_array($_GET['view'],array('list','table'))) {
    $_SESSION['glossary_view'] = $_GET['view'];
} else {
  if (!isset($_SESSION['glossary_view'])) {
    $_SESSION['glossary_view'] = 'table';//Default option
  }
}

if (api_is_allowed_to_edit(null, true)) {
    // Adding a glossary
    if (isset($_GET['action']) && $_GET['action'] == 'addglossary') {
        // initiate the object
        $form = new FormValidator('glossary','post', api_get_self().'?action='.Security::remove_XSS($_GET['action']));
        // settting the form elements
        $form->addElement('header', '', get_lang('TermAddNew'));
        $form->addElement('text', 'glossary_title', get_lang('TermName'), array('size'=>'95', 'id'=>'glossary_title'));
        //$form->applyFilter('glossary_title', 'html_filter');
        $form->addElement('html_editor', 'glossary_comment', get_lang('TermDefinition'), null, array('ToolbarSet' => 'Glossary', 'Width' => '100%', 'Height' => '300'));
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
    }	else if (isset($_GET['action']) && $_GET['action'] == 'edit_glossary' && is_numeric($_GET['glossary_id']))  { // Editing a glossary
        // initiate the object
        $form = new FormValidator('glossary','post', api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&glossary_id='.Security::remove_XSS($_GET['glossary_id']));
        // settting the form elements
        $form->addElement('header', '', get_lang('TermEdit'));
        $form->addElement('hidden', 'glossary_id');
        $form->addElement('text', 'glossary_title', get_lang('TermName'),array('size'=>'100'));
        //$form->applyFilter('glossary_title', 'html_filter');
        $form->addElement('html_editor', 'glossary_comment', get_lang('TermDefinition'), null, array('ToolbarSet' => 'Glossary', 'Width' => '100%', 'Height' => '300'));
        
        
        
        $element = $form->addElement('text', 'insert_date', get_lang('CreationDate'),array('size'=>'100'));
        $element ->freeze();        
        $element = $form->addElement('text', 'update_date', get_lang('UpdateDate'),array('size'=>'100'));
        $element ->freeze();
       
        $form->addElement('style_submit_button', 'SubmitGlossary', get_lang('TermUpdateButton'), 'class="save"');
        
        

        // setting the defaults
        $glossary_data = GlossaryManager::get_glossary_information($_GET['glossary_id']);
        
        // Date treatment for timezones
        if (!empty($glossary_data['insert_date'])  && $glossary_data['insert_date'] != '0000-00-00 00:00:00:') {
            $glossary_data['insert_date'] = api_get_local_time($glossary_data['insert_date'] , null, date_default_timezone_get());
        } else {
            $glossary_data['insert_date']  = '';
        }
        
        if (!empty($glossary_data['update_date'])  && $glossary_data['update_date'] != '0000-00-00 00:00:00:') {
            $glossary_data['update_date'] = api_get_local_time($glossary_data['update_date'] , null, date_default_timezone_get());
        } else {
            $glossary_data['update_date']  = '';
        }
        
        $form->setDefaults($glossary_data);

        // setting the rules
        $form->addRule('glossary_title', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');

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
    } else if (isset($_GET['action']) && $_GET['action'] == 'delete_glossary' && is_numeric($_GET['glossary_id'])) 	{// deleting a glossary
        GlossaryManager::delete_glossary(Security::remove_XSS($_GET['glossary_id']));
        GlossaryManager::display_glossary();
    } else if (isset($_GET['action']) && $_GET['action'] == 'moveup' && is_numeric($_GET['glossary_id'])) {	// moving a glossary term up
        GlossaryManager::move_glossary('up',$_GET['glossary_id']);
        GlossaryManager::display_glossary();
    } else if (isset($_GET['action']) && $_GET['action'] == 'movedown' && is_numeric($_GET['glossary_id'])) { // moving a glossary term down
        GlossaryManager::move_glossary('down',$_GET['glossary_id']);
        GlossaryManager::display_glossary();
    } else {
        GlossaryManager::display_glossary();
    }
} else {
    GlossaryManager::display_glossary();
}

// Footer
Display::display_footer();