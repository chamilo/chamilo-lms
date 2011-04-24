<?php
/* For licensing terms, see /license.txt */

/**
 * This library provides methods for using views with MVC pattern
 * @package chamilo.library
 * @author Christian Fasanando <christian1827@gmail.com>
 */

class ViewException extends Exception {}
class View {
	
	private $data;
	private $template;
	private $layout;
    private $tool_path;
   
   	/**
   	 * Constructor, init tool path for rendering
   	 * @param string  tool name (optional)
   	 */
	public function __construct($toolname = '', $template_path=null) {
		if (!empty($toolname)) {
            if (isset($template_path)) {
                $path = $template_path.$toolname.'/';
            } else {
                $path = api_get_path(SYS_CODE_PATH).$toolname.'/';
            }
			if (is_dir($path)) {
				$this->tool_path = $path;
			} else {
				throw new ViewException('View::__construct() $path directory does not exist ' . $path);
			}
		}
	}
   
    /**
   	 * Set data sent from a controller 
   	 * @param array data
   	 */
	public function set_data($data) {
		if (!is_array($data)) {
			throw new ViewException('View::set_data() $data must to be an array, you have sent a' . gettype( $data ));
		}
		$this->data = $data;
	}
   
    /**
   	 * Set layout view sent from a controller 
   	 * @param string layout view
   	 */
	public function set_layout( $layout ) {		
		$this->layout = $layout;
	}
   
   	/**
   	 * Set template view sent from a controller 
   	 * @param string template view
   	 */
	public function set_template($template) {		
		$this->template = $template;
	}

	/**
   	 * Render data to the template and layout views  
   	 */
	public function render() {
		$content = $this->render_template();
		$target = $this->tool_path.$this->layout.'.php';
		if (file_exists($target)) {           
			require_once $target;
		} else {
			throw new ViewException('View::render() invalid file path '.$target);
		}
	}
    
    /**
   	 * It's used into render method for rendering data in the template and layout views
     * @return  String  Rendered template (as HTML, most of the time)   
   	 */   
	private function render_template() {		
		$target = $this->tool_path.$this->template.'.php';
		if (file_exists($target)) {           
			ob_start();
			@extract($this->data, EXTR_OVERWRITE); //pass the $this->data array into local scope
			require_once $target;
			$content = ob_get_clean();
			return $content;
		} else {
			throw new ViewException('View::render_template() invalid file path '.$target);
		}
	}
}
?>