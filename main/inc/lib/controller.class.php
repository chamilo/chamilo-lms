<?php

/**
 * Controller
 * 
 * 
 * 
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Genevas
 * @license see /license.txt
 */
class Controller 	
{

    const PARAM_ACTION = 'action';
    
    protected $access;
    
    protected function __construct($access = null)
    {
        $access = $access ? $access : Access::all();
        $this->access = $access;
    }
    
    /**
     *
     * @return \Access
     */
    public function access()
    {
        return $this->access;
    }

    /**
     * List of actions accepted by the controller.
     * 
     * @return array
     */
    public function get_actions()
    {
        $reflector = new ReflectionClass($this);
        $constants = $reflector->getConstants();
        $result = array();
        foreach ($constants as $key => $value) {
            if (strpos($key, 'ACTION') !== false) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Action to perform. 
     * Returns the request parameter.
     * 
     * @return string
     */
    public function get_action()
    {
        $result = Request::get(self::PARAM_ACTION);
        $actions = $this->get_actions();
        $result = in_array($result, $actions) ? $result : '';
        return $result;
    }

    /**
     * Set up the environment. Set up breadcrumps, raise tracking event, etc.
     */
    protected function prolog()
    {
        
    }

    /**
     * Whether the call is authorized or not.
     * 
     * @return boolean 
     */
    public function authorize()
    {
        return $this->access()->authorize();
    }

    /**
     * Returns a string containing dynamic javascript to be included in the template.
     * This requires a {{javascript}} tag in a twigg template to appear.
     * 
     * Note:
     * 
     * A better approach to this method is to create a twigg "javascript"
     * template and to include it where required.
     * 
     * @return string 
     */
    public function javascript()
    {
        return '';
    }

//    public function check_token()
//    {
//        return (bool) Security::check_token('get');
//    }

    /**
     * Run the controller. Dispatch action and execute requested tasks.
     */
    public function run()
    {
        if (!$this->authorize()) {
            $this->forbidden();
            return false;
        }

        $this->prolog();
        $action = $this->get_action();
        if (empty($action)) {
            $this->unknown();
            return;
        }
        $f = array($this, $action);
        if (is_callable($f)) {
            call_user_func($f);
        } else {
            $this->missing();
        }
    }

    /**
     * Unknown action. I.e. the action has not been registered. 
     * Possibly missing action declaration:
     * 
     *      const ACTION_XXX = 'XXX';
     * 
     * @return boolean 
     */
    public function unknown()
    {
        return false;
    }

    /**
     * 
     * @return boolean 
     */
    public function forbidden()
    {
        api_not_allowed();
        return false;
    }

    /**
     * Action exists but implementation is missing. 
     */
    public function missing()
    {
        echo 'No implementation';
        return false;
    }

    /**
     * Render a template using data. Adds a few common parameters to data.
     * 
     * @see /main/template/default/course_description/
     * @param string $template
     * @param array $data 
     */
    protected function render($template_name, $data)
    {
        $data = (object) $data;
        $data->www = \Chamilo::url();
        $data->messages = isset($data->messages) ? $data->messages : array();
        $javascript = $this->javascript();
        if ($javascript) {
            $data->javascript = $javascript;
        }

        $tpl = new Template();
        foreach ($data as $key => $value) {
            $tpl->assign($key, $value);
        }
        $template = $tpl->get_template($template_name);
        $content = $tpl->fetch($template);
        $tpl->assign('content', $content);
        $tpl->display_one_col_template();
    }

    /**
     * Render data as JSON
     * 
     * @param any $data 
     */
    protected function render_json($data)
    {
        Header::content_type_json();
        echo json_encode($data);
    }

}