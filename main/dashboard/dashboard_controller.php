<?php
/* For licensing terms, see /license.txt */

/**
 * This file contains class used like controller, it should be included inside a dispatcher file (e.g: index.php)
 * @author Christian Fasanando <christian1827@gmail.com>
 * @package chamilo.dashboard
 */

/**
 * Controller script. Prepares the common background variables to give to the scripts corresponding to
 * the requested action 
 */
class DashboardController { // extends Controller {	
		
	private $toolname;
	private $view; 
	private $user_id;
	
	/**
	 * Constructor
	 */
	public function __construct() {		
		$this->user_id = api_get_user_id();
		$this->toolname = 'dashboard';	
		$this->view = new View($this->toolname);			
	}
	
	/**
	 * Display blocks from dashboard plugin paths
	 * @param string message (optional)
	 * render to dashboard.php view
	 */
	public function display($msg = false) {

		$data = array();		
		$user_id = $this->user_id;
		
		$block_data_without_plugin = DashboardManager::get_block_data_without_plugin();
		$dashboard_blocks = DashboardManager::get_enabled_dashboard_blocks();
		$user_block_data  = DashboardManager::get_user_block_data($user_id);		
		$user_blocks_id = array_keys($user_block_data);

		if (!empty($dashboard_blocks)) {
			foreach ($dashboard_blocks as $block) {
				
				// display only user blocks
				if (!in_array($block['id'], $user_blocks_id)) continue;								 			
				
				$path = $block['path'];
				$controller_class = $block['controller'];
				$filename_controller = $path.'.class.php';			
				$dashboard_plugin_path = api_get_path(SYS_PLUGIN_PATH).'dashboard/'.$path.'/';	
				require_once $dashboard_plugin_path.$filename_controller;
				if (class_exists($controller_class)) {
    				$obj = new $controller_class($user_id);
    				
    				// check if user is allowed to see the block
    				if (method_exists($obj, 'is_block_visible_for_user')) {					
    					$is_block_visible_for_user = $obj->is_block_visible_for_user($user_id);					
    					if (!$is_block_visible_for_user) continue;
    				}
    				
    				$data_block[$path] = $obj->get_block();
    				// set user block column 
    				$data_block[$path]['column'] = $user_block_data[$block['id']]['column'];
				}				
			}
			
			$data['blocks'] = $data_block; 
			$data['dashboard_view'] = 'blocks';	
		}
		
		if ($msg) {
			$data['msg'] = $msg;
		}
			
		// render to the view
		$this->view->set_data($data);
		$this->view->set_layout('layout'); 
		$this->view->set_template('dashboard');		       
		$this->view->render();		
	}
	
	/**
	 * This method allow store user blocks from dashboard manager
	 * render to dashboard.php view
	 */
	public function store_user_block() {
		
		$data = array();
		$user_id = $this->user_id;
		if (strtoupper($_SERVER['REQUEST_METHOD']) == "POST") {
			$enabled_blocks = $_POST['enabled_blocks'];
			$columns = $_POST['columns'];
			$affected_rows = DashboardManager::store_user_blocks($user_id, $enabled_blocks, $columns);			
			if ($affected_rows) {
				$data['success'] = true;
			}
		}

		$data['dashboard_view'] = 'list'; 
				
		// render to the view
		$this->view->set_data($data);
		$this->view->set_layout('layout'); 
		$this->view->set_template('dashboard');		       
		$this->view->render();		
	}
	
	/**
	 * This method is used when you close a block from dashboard block interface
	 * render to dashboard.php view
	 */
	public function close_user_block($path) {				
		$user_id = $this->user_id;		
		$result = DashboardManager::close_user_block($user_id, $path);
		$this->display($result);
	}
	
}
?>
