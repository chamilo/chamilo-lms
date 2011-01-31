<?php
/* For licensing terms, see /license.txt */

/**
*	This class provides methods for the notebook management.
*	Include/require it in your code to use its features.
*	@package chamilo.library
*/

require_once 'model.lib.php';
require_once 'promotion.lib.php';


define ('CAREER_STATUS_ACTIVE',  1);
define ('CAREER_STATUS_INACTIVE',0);


class Career extends Model {
    
    var $table;
    var $columns = array('id', 'name','description','status');
    
	public function __construct() {
        $this->table =  Database::get_main_table(TABLE_CAREER);
	}    
    
    public function get_all() {
        return Database::select('*',$this->table, array('order' =>'name ASC'));
    }
    
    public function update_all_promotion_status_by_career_id($career_id, $status) {
        $promotion = new Promotion();        
        $promotion_list = $promotion->get_all_promotions_by_career_id($career_id);
        if (!empty($promotion_list)) {
            foreach($promotion_list  as $item) {                
                $params['id']     = $item['id'];
                $params['status'] = $status; 
                $promotion->update($params);        	
            }
        }
    }
    
    /**
     * Displays the title + grid
     */
	public function display() {
		// action links
		echo '<div class="actions" style="margin-bottom:20px">';
        echo '<a href="career_dashboard.php">'.Display::return_icon('back.png',get_lang('Back')).get_lang('Back').'</a>';     	
		echo '<a href="'.api_get_self().'?action=add">'.Display::return_icon('filenew.gif',get_lang('Add')).get_lang('Add').'</a>';        				
		echo '</div>';   
        echo Display::grid_html('careers');  
	}
    
    public function get_status_list() {
        return array(CAREER_STATUS_ACTIVE => get_lang('Active'), CAREER_STATUS_INACTIVE => get_lang('Inactive'));
    }
    
    /**
     * Returns a Form validator Obj
     * @todo the form should be auto generated
     * @param   string  url
     * @param   string  header name
     * @return  obj     form validator obj 
     */
    public function return_form($url, $header) {
        $form = new FormValidator('career', 'post', $url);
        // Settting the form elements
        $form->addElement('header', '', $header);
        $form->addElement('hidden', 'id',intval($_GET['id']));
        $form->addElement('text', 'name', get_lang('Name'), array('size' => '100'));
        $form->addElement('html_editor', 'description', get_lang('description'), null);
        
        $status_list = $this->get_status_list();         
        $form->addElement('select', 'status', get_lang('Status'), $status_list);
        $form->addElement('style_submit_button', 'submit', get_lang('Modify'), 'class="save"');
    
        // Setting the defaults
        $defaults = $this->get($_GET['id']);
        $form->setDefaults($defaults);
    
        // Setting the rules
        $form->addRule('name', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');
        return $form;
    }
    
}