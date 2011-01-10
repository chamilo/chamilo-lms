<?php
/* For licensing terms, see /license.txt */

/**
*	This class provides methods for the promotion management.
*	Include/require it in your code to use its features.
*	@package chamilo.library
*/

require_once 'model.lib.php';

class Promotion extends Model {
    
    var $table;
    var $columns = array('id','name','description','career_id');
    
	public function __construct() {
        $this->table =  Database::get_main_table(TABLE_PROMOTION);
	}
    
    function get_all_promotions_by_career_id($career_id) {        
        return Database::select('*', $this->table, array('where'=>array('career_id = ?'=>$career_id)));
    }    
   
    /**
     * Displays the title + grid
     */
	function display() {
		// action links
		echo '<div class="actions" style="margin-bottom:20px">';
        echo '<a href="career_dashboard.php">'.Display::return_icon('back.png',get_lang('Back')).get_lang('Back').'</a>';
		echo '<a href="'.api_get_self().'?action=add">'.Display::return_icon('filenew.gif',get_lang('Add')).get_lang('Add').'</a>';	
		echo '</div>';
        echo Display::grid_html('promotions');  
	}
    
 
}