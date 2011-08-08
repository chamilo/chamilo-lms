<?php
/* For licensing terms, see /license.txt */
/**
*	This class provides basic methods to implement a CRUD for a new table in the database see examples in: career.lib.php and promotion.lib.php
*	Include/require it in your code to use its features.
*	@package chamilo.library
*/
/**
 * Class
 * @package chamilo.library
 */
class Model {
    
    var $table;
    var $columns;
    // var $pk; some day this will be implemented
    
	public function __construct() {        
	}
    
    /**
     * Useful finder - experimental akelos like only use in notification.lib.php send function
     */
    public function find($type, $options = null) {
        switch($type) {
            case 'all':
                return self::get_all($options);
                break;
            case (is_numeric($type)) :
                return self::get($type);
                break;
        }
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
    
    public function get_all($options = null) {        
        return Database::select('*',$this->table, $options);
    }
    
    /**
     * Get the count of elements
     */
    public function get_count() {        
        $row = Database::select('count(*) as count', $this->table, array(),'first');
        return $row['count'];
    }
        
    /**
     * a little bit of javascript to display
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
	public function save($params, $show_query = false) {
        $params = $this->clean_parameters($params);
        
        if (in_array('created_at', $this->columns)) {        	
            $params['created_at'] = api_get_utc_datetime();
        }
        if (!empty($params)) {
            $id = Database::insert($this->table, $params, $show_query);        
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
        //If the class has the updated_at field we update the date
        if (in_array('updated_at', $this->columns)) {           
            $params['updated_at'] = api_get_utc_datetime();
        }
        //If the class has the created_at field then we remove it
        if (in_array('created_at', $this->columns)) {
            unset($params['created_at']);
        }
        
        if (!empty($params) && !empty($params['id'])) {
            $id = intval($params['id']);
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
