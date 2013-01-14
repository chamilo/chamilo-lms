<?php
/* For licensing terms, see /license.txt */
/**
*	This class provides methods for the notebook management.
*	Include/require it in your code to use its features.
*	@package chamilo.library
*/
/**
 * Code
 */

/**
 * @package chamilo.library
 */
class EventEmailTemplate extends Model {
    
    var $table;
    var $columns = array('id', 'message','subject','event_type_name','activated');
    
	public function __construct() {
        $this->table =  Database::get_main_table(TABLE_EVENT_EMAIL_TEMPLATE);
	}    
    
    public function get_all($where_conditions = array()) {
        return Database::select('*',$this->table, array('where'=>$where_conditions,'order' =>'name ASC'));
    }    
    
    /**
     * Displays the title + grid
     */
	public function display() {
		// action links
		$content = Display::actions(array(
                array(
                    'url' => 'event_type.php' , 
                    'content' => Display::return_icon('new_document.png', get_lang('Add'), array(), ICON_SIZE_MEDIUM)
                 )
            )
        );
        $content .= Display::grid_html('event_email_template');  
        return $content;
	}
    
    public function get_status_list() {
        return array(EVENT_EMAIL_TEMPLATE_ACTIVE => get_lang('Enabled'), EVENT_EMAIL_TEMPLATE_INACTIVE=> get_lang('Disabled'));
    }
    
    /**
     * Returns a Form validator Obj
     * @todo the form should be auto generated
     * @param   string  url
     * @param   string  action add, edit
     * @return  obj     form validator obj 
     */
    public function return_form($url, $action) {
		
		$oFCKeditor = new FCKeditor('description') ;
		$oFCKeditor->ToolbarSet = 'careers';
		$oFCKeditor->Width		= '100%';
		$oFCKeditor->Height		= '200';
		$oFCKeditor->Value		= '';
		$oFCKeditor->CreateHtml();
		
        $form = new FormValidator('career', 'post', $url);
        // Settting the form elements
        $header = get_lang('Add');        
        if ($action == 'edit') {
            $header = get_lang('Modify');
        }
        
        $form->addElement('header', $header);
        $id = isset($_GET['id']) ? intval($_GET['id']) : '';
        $form->addElement('hidden', 'id', $id);
        
        $form->addElement('text', 'name', get_lang('Name'), array('size' => '70'));
        $form->add_html_editor('description', get_lang('Description'), false, false, array('ToolbarSet' => 'careers','Width' => '100%', 'Height' => '250'));	   
	    $status_list = $this->get_status_list();         
        $form->addElement('select', 'status', get_lang('Status'), $status_list);
        if ($action == 'edit') {
            $form->addElement('text', 'created_at', get_lang('CreatedAt'));
            $form->freeze('created_at');
        }
                
        if ($action == 'edit') {
        	$form->addElement('style_submit_button', 'submit', get_lang('Modify'), 'class="save"');
        } else {
        	$form->addElement('style_submit_button', 'submit', get_lang('Add'), 'class="save"');
        }
    
        // Setting the defaults
        $defaults = $this->get($id);
        
        if (!empty($defaults['created_at'])) {
        	$defaults['created_at'] = api_convert_and_format_date($defaults['created_at']);
        }
        if (!empty($defaults['updated_at'])) {
        	$defaults['updated_at'] = api_convert_and_format_date($defaults['updated_at']);
        }
        $form->setDefaults($defaults);
    
        // Setting the rules
        $form->addRule('name', get_lang('ThisFieldIsRequired'), 'required');
		return $form;                                
    }
    
      public function get_count() {        
        $row = Database::select('count(*) as count', $this->table, array(),'first');
        return $row['count'];
    }
    
    /*
    public function save($params) {
	    $id = parent::save($params);
	    if (!empty($id)) {
	    	event_system(LOG_CAREER_CREATE, LOG_CAREER_ID, $id, api_get_utc_datetime(), api_get_user_id());
   		}
   		return $id;
    }
    
    public function delete($id) {
	    parent::delete($id);
	    event_system(LOG_CAREER_DELETE, LOG_CAREER_ID, $id, api_get_utc_datetime(), api_get_user_id());
    } */   
}
