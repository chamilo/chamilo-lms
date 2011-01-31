<?php
/* For licensing terms, see /license.txt */

/**
*	This class provides methods for the notebook management.
*	Include/require it in your code to use its features.
*	@package chamilo.library
*/

class Model {
    
    var $table;
    var $columns;
    
	public function __construct() {        
	}
    
    public function find() {
    	
    }
    
    
    /**
     * Delets an item
     */
    public function delete($id) {
        if (empty($id) or $id != strval(intval($id))) { return false; }
        // Database table definition
        $result = Database :: delete($this->table, array('id = ?' => $id));        
        if ($result != 1){
            return false;
        }       
        return true;
    }
    
    private function clean_parameters($params){
        $clean_params = array();
        if (!empty($params)) {
            foreach($params as $key=>$value) {
                if (in_array($key, $this->columns)) {
                    $clean_params[$key] = $value;
                }           
            }
        }
        return $clean_params;
    }
    
    /**
     * Displays the title + grid
     */
    public function display() { 
    }    
    

     
    /**
     * Gets an element
     */
    public function get($id) {
        if (empty($id)) { return array(); }     
        $result = Database::select('*',$this->table, array('where'=>array('id = ?'=>intval($id))),'first');
        return $result;
    }
    
    public function get_all() {
        return Database::select('*',$this->table);
    }
    
    /**
     * Get the count of elements
     */
    public function get_count() {        
        $row = Database::select('count(*) as count', $this->table, array(),'first');
        return $row['count'];
    }
        
    /**
     * a little bit of javascript to display a prettier warning when deleting a note
     *
     * @return unknown
     *
     */
	public function javascript() {
		
	}

	/**
	 * Saves an element into the DB
	 *
	 * @param array $values
	 * @return bool
	 *
	 */
	public function save($params) {
        $params = $this->clean_parameters($params);
        if (!empty($params)) {
            $id = Database::insert($this->table, $params);        
    		if (is_numeric($id)){
    			return $id;
    		}
        }
        return false;
	}
    
    /**
     * Updates the obj in the database. The $params['id'] must exist in order to update a record
     *
     * @param array $values
     *
     */
    public function update($params) {
        $params = $this->clean_parameters($params);
        if (!empty($params)) {
            $id = $params['id'];
            unset($params['id']); //To not overwrite the id 
            if (is_numeric($id)) {
                $result = Database::update($this->table, $params, array('id = ?'=>$id));        
                if ($result){
                    return true;
                }   
            }    
        }
        return false;
    }
}