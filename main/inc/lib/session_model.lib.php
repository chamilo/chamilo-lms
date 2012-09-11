<?php

class SessionModel extends model {
       
    public $columns = array(
        'id', 
        'id_coach', 
        'name', 
        'nbr_courses', 
        'nbr_users', 
        'nbr_classes', 
        'session_admin_id', 
        'visibility', 
        'session_category_id', 
        'promotion_id', 
        'display_start_date',
        'display_end_date',
        'access_start_date',
        'access_end_date',
        'coach_access_start_date',
        'coach_access_end_date',        
    );
            
    public function __construct() {
        $this->table = Database::get_main_table(TABLE_MAIN_SESSION);        
    }
    
    
    public function clean_parameters($params) {
        //Convert dates          
        $params['display_start_date']       = api_get_utc_datetime($params['display_start_date'], true);
        $params['display_end_date']         = api_get_utc_datetime($params['display_end_date'], true);
        $params['access_start_date']        = api_get_utc_datetime($params['access_start_date'], true);
        $params['access_end_date']          = api_get_utc_datetime($params['access_end_date'], true);
        $params['coach_access_start_date']  = api_get_utc_datetime($params['coach_access_start_date'], true);
        $params['coach_access_end_date']    = api_get_utc_datetime($params['coach_access_end_date'], true);
        $params['id_coach']                 = is_array($params['id_coach']) ? $params['id_coach'][0] : $params['id_coach'];
               
        if (empty($params['access_end_date'])) {
            $params['visibility'] = SessionManager::DEFAULT_VISIBILITY;
        }
        
        unset($params['submit']);        
        return $params;  
    }
    
    function save($params, $show_query = false) {
        $params = self::clean_parameters($params);  
        return parent::save($params, $show_query);
    }
    
    function update($params) {                   
        $params = self::clean_parameters($params);         
        $result = parent::update($params);
        return $result;        
    }
}