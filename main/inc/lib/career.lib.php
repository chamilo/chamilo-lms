<?php
/* For licensing terms, see /license.txt */

/**
*	This class provides methods for the notebook management.
*	Include/require it in your code to use its features.
*	@package chamilo.library
*/

class Career {
    
    var $table;
    var $columns = array('name','description');
    
	public function __construct() {
        $this->table =  Database::get_main_table(TABLE_CAREER);
	}
    
	/**
	 * a little bit of javascript to display a prettier warning when deleting a note
	 *
	 * @return unknown
	 *
	 */
	function javascript_notebook()
	{
		return "<script type=\"text/javascript\">
				function confirmation (name)
				{
					if (confirm(\" ". get_lang("NoteConfirmDelete") ." \"+ name + \" ?\"))
						{return true;}
					else
						{return false;}
				}
				</script>";
	}

	/**
	 * Saves an element into the DB
	 *
	 * @param array $values
	 * @return bool
	 *
	 */
	function save($values) {
		/*if (!is_array($values) or empty($values['note_title'])) { 
			return false; 
		}*/		        
        unset($values['submit']);
        $id = Database::insert($this->table, $values);        
		if (is_numeric($id)){
			return $id;
		}
	}
    
    /**
     * Gets an element
     */
	function get($id) {
		if (empty($id)) { return array(); }		
        $result = Database::select('*',$this->table, array('where'=>array('id = ?'=>intval($id))),'first');
        return $result;
	}
    
    function get_all() {
    	return $careers = Database::select('*',$this->table);
    }
    
    /**
     * Get the count of elements
     */
    function get_count() {        
        $row = Database::select('count(*) as count', $this->table, array(),'first');
        return $row['count'];
    }
    

	/**
	 * Updates the obj in the database
	 *
	 * @param array $values
	 *
	 */
	function update($values) {
		/*if (!is_array($values) or empty($values['note_title'])) {
			return false;
		}*/
        unset($values['submit']);
		$table = Database :: get_main_table(TABLE_CAREER);
        $id = $values['id'];
        unset($values['id']);
        $result = Database::update($this->table, $values, array('id = ?'=>$id));		
        if ($result != 1){
            return false;
        }       
        return true;
	}
    
    /**
     * Delets an item
     */
	function delete($id) {
		if (empty($id) or $id != strval(intval($id))) { return false; }
		// Database table definition
		$result = Database :: delete($this->table, array('id = ?' => $id));        
        if ($result != 1){
        	return false;
        }		
		return true;
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
        echo Display::grid_html('careers');  
	}    
}