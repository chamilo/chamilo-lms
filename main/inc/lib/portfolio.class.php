<?php

/*
 * This file contains several classes related to portfolios management to avoid
 * having too much files under the lib/. 
 * 
 * Once external libraries are moved to their own directory it would be worth
 * moving them to their own files under a common portfolio directory.
 */

use Model\Document;
use Model\Course;

/**
 * A portfolio is used to present content to other people. In most cases it is
 * an external application. 
 * 
 * From the application point of view it is an end point to which the user can send
 * content.
 * 
 * Available portfolios are configured in /main/inc/config/portfolio.conf.php
 * 
 * The Portfolio class serves as an entry point to other portfolio components:
 * 
 *      - portfolio controller
 *      - portfolio share button
 *      - portfolio action
 * 
 * Note:
 * 
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class Portfolio extends Portfolio\Portfolio
{

    /**
     * Returns all portfolios available
     * 
     * @return array
     */
    public static function all()
    {
        $conf = Chamilo::path('/main/inc/conf/portfolio.conf.php');
        if (!is_readable($conf)) {
            return array();
        }
        include $conf;
        return isset($portfolios) ? $portfolios : array();
    }

    /**
     * Returns a portfolio from its name.
     * 
     * @param string $name
     * @return Portfolio\Portfolio
     */
    public static function get($name)
    {
        $items = self::all();
        foreach ($items as $item) {
            if ($item->get_name() == $name) {
                return $item;
            }
        }
        return Portfolio\Portfolio::none();
    }

    /**
     * True if portfolios are enabled. False otherwise.
     * 
     * @return boolean 
     */
    public static function is_enabled()
    {
        if (api_is_anonymous()) {
            return false;
        }
        $user_id = api_get_user_id();
        if (empty($user_id)) {
            return false;
        }
        $portfolios = self::all();
        if (count($portfolios) == 0) {
            return false;
        }
        return true;
    }

    /**
     * The controller for portfolio.
     * 
     * @return \PortfolioController 
     */
    public static function controller()
    {
        return PortfolioController::instance();
    }

    /**
     * Returns a share component/button.
     * 
     * @param string $tool
     * @param int $id
     * @param array $attributes
     * @return \PortfolioShare 
     */
    public static function share($tool, $id, $attributes = array())
    {
        return PortfolioShare::factory($tool, $id, $attributes);
    }

    /**
     * Returns the list of actions.
     * 
     * @return array 
     */
    public static function actions()
    {
        return PortfolioController::actions();
    }

    /**
     * Returns a temporary url to download files and/or folders.
     * 
     * @param string|array $ids
     * @return string 
     */
    public static function download_url($ids, $tool)
    {
        $ids = is_array($ids) ? implode(',', $ids) : $ids;

        $params = Uri::course_params();
        $params['id'] = $ids;
        $params[KeyAuth::PARAM_ACCESS_TOKEN] = KeyAuth::create_temp_token();
        $result = Uri::url("/main/$tool/file.php", $params, false);
        return $result;
    }

}

/**
 * The portfolio controller. Responsible to dispatch/process portfolio actions.
 * 
 * Usage:
 * 
 *      if(Porfolio::contoller()->accept()){
 *          Portfolio::controller()->run();
 *      }
 * 
 * 
 */
class PortfolioController
{

    const PARAM_ACTION = 'action';
    const PARAM_ID = 'id';
    const PARAM_TOOL = 'tool';
    const PARAM_PORTFOLIO = 'portfolio';
    const PARAM_CONTROLLER = 'controller';
    const PARAM_SECURITY_TOKEN = 'sec_token';
    const ACTION_SHARE = 'share';
    const NAME = 'portfolio';

    /**
     *
     * @return \PortfolioController 
     */
    static function instance()
    {
        static $result = null;
        if (empty($result)) {
            $result = new self();
        }
        return $result;
    }

    protected $message = '';

    protected function __construct()
    {
        
    }

    public static function portfolios()
    {
        return Portfolio::all();
    }

    /**
     * List of actions for the SortableTable. 
     * 
     * @return array 
     */
    public static function actions()
    {
        static $result = null;
        if (!is_null($result)) {
            return $result;
        }

        $items = self::portfolios();
        if (empty($items)) {
            $result = array();
            return $result;
        }

        $result = array();
        foreach ($items as $item) {
            $action = PortfolioBulkAction::create($item);
            $result[] = $action;
        }
        return $result;
    }

    /**
     * Returns true if the controller accept to process the current request. 
     * Returns false otherwise.
     * 
     * @return boolean 
     */
    function accept()
    {
        if (!Portfolio::is_enabled()) {
            return false;
        }
        $actions = self::actions();
        foreach ($actions as $action) {
            if ($action->accept()) {
                return true;
            }
        }

        if ($this->get_controller() != self::NAME) {
            return false;
        }
        if (!Security::check_token('get')) {
            return false;
        }
        $id = $this->get_id();
        if (empty($id)) {
            return false;
        }

        return $this->get_action() == self::ACTION_SHARE;
    }

    /**
     * Returns the value of the current controller request parameters. That is
     * the name of the controller which shall handle the current request. 
     * 
     * @return string
     */
    function get_controller()
    {
        return Request::get(self::PARAM_CONTROLLER);
    }

    /**
     * Returns the value of the action parameter. That is which action shall be
     * performed. That is share to send an object to a portfolio.
     * 
     * @return string 
     */
    function get_action()
    {
        $result = Request::get(self::PARAM_ACTION);
        return ($result == self::ACTION_SHARE) ? self::ACTION_SHARE : '';
    }

    /**
     * Returns the value of the id parameter: id of object to send.
     * 
     * @return int
     */
    function get_id()
    {
        return (int) Request::get(self::PARAM_ID);
    }

    /**
     * The course code (id) to which the object belongs.
     * 
     * @return string
     */
    function course_code()
    {
        return Chamilo::session()->course()->code();
    }

    /**
     * The name of the porfolio where to send.
     * 
     * @return type 
     */
    function get_portfolio()
    {
        return Request::get(self::PARAM_PORTFOLIO);
    }

    /**
     * Name of the tool: document, work, etc. Defaults to current_course_tool.
     * 
     * @global string $current_course_tool
     * @return string 
     */
    function get_tool()
    {
        global $current_course_tool;
        return Request::get(self::PARAM_TOOL, $current_course_tool);
    }

    /**
     * Returns the end user message after running the controller..
     * @return string
     */
    function message()
    {
        return $this->message;
    }

    /**
     * Execute the controller action as required. If a registered action accept 
     * the current request the controller calls it.
     * 
     * If not action is accept the current request and current action is "share"
     * the controller execute the "send to portfolio" action
     * 
     * @return PortfolioController
     */
    function run()
    {
        if (!$this->accept()) {
            return $this;
        }

        $actions = self::actions();
        foreach ($actions as $action) {
            if ($action->accept()) {
                return $action->run();
            }
        }

        $action = $this->get_action();
        if ($action == self::ACTION_SHARE) {

            $user = new \Portfolio\User();
            $user->email = Chamilo::user()->email();

            $tool = $this->get_tool();
            $id = $this->get_id();
            $url = Portfolio::download_url($id, $tool);

            $artefact = new Portfolio\Artefact($url);

            $name = $this->get_portfolio();
            $result = Portfolio::get($name)->send($user, $artefact);
            if ($result) {
                $this->message = Display::return_message(get_lang('SentSuccessfully'), 'normal');
            } else {
                $this->message = Display::return_message(get_lang('SentFailed'), 'error');
            }
            return $this;
        } else {
            $this->message = '';
        }
        return $this;
    }

}

/**
 * This component is used to display a "send to portfolio" button for a specific
 * object. 
 * 
 * Note that the component implement the __toString() magic method and can be 
 * therefore used in situation where a string is expected: for ex echo $button.
 * 
 * Usage
 * 
 *      $button = Portfolio::share(...);
 *      echo $button;
 * 
 */
class PortfolioShare
{

    /**
     * Create a "send to portfolio" button
     * 
     * @param string $tool          The name of the tool: document, work.
     * @param int $c_id             The id of the course
     * @param int $id               The id of the object 
     * @param array $attributes     Html attributes
     * @return \PortfolioShare  
     */
    static function factory($tool, $id, $attributes = array())
    {
        $result = new self($tool, $id, $attributes);
        return $result;
    }

    /**
     * Returns the current secuirty token. Used to avoid see surfing attacks.
     * 
     * @return type 
     */
    static function security_token()
    {
        static $result = null;
        if (empty($result)) {
            $result = Security::get_token();
        }
        return $result;
    }

    protected $id = 0;
    protected $attributes = array();
    protected $tool = '';

    function __construct($tool, $id, $attributes = array())
    {
        $this->tool = $tool;
        $this->id = (int) $id;
        $this->attributes = $attributes;
    }

    /**
     * Object id to send
     * @return int
     */
    function get_id()
    {
        return $this->id;
    }

    /**
     * Object id to send
     * @return int
     */
    function get_c_id()
    {
        return $this->c_id;
    }

    /**
     * Html attributes. 
     * 
     * @return array
     */
    function get_attributes()
    {
        return $this->attributes;
    }

    /**
     * Name of the tool. I.e. the type of the id parameter. Can be document, work.
     * 
     * @return string
     */
    function get_tool()
    {
        return $this->tool;
    }

    /**
     * Display the component.
     * 
     * @return string 
     */
    function display()
    {
        if (!Portfolio::is_enabled()) {
            return '';
        }
        $id = $this->id;
        $tool = $this->tool;

        $attributes = $this->attributes;
        $attributes['z-index'] = 100000;
        $s = ' ';
        foreach ($attributes as $key => $value) {
            $s .= $key . '="' . $value . '" ';
        }

        $result = array();
        $result[] = '<span ' . $s . ' >';
        $result[] = '<span class="dropdown" >';
        $result[] = '<a href="#" data-toggle="dropdown" class="dropdown-toggle">';
        $result[] = Display::return_icon('document_send.png', get_lang('Send'), array(), ICON_SIZE_SMALL) . '<b class="caret"></b>';
        $result[] = '</a>';
        $result[] = '<ul class="dropdown-menu">';

        $portfolios = Portfolio::all();
        foreach ($portfolios as $portfolio) {
            $parameters = Uri::course_params();
            $parameters[PortfolioController::PARAM_ACTION] = PortfolioController::ACTION_SHARE;
            $parameters[PortfolioController::PARAM_CONTROLLER] = PortfolioController::NAME;
            $parameters[PortfolioController::PARAM_PORTFOLIO] = $portfolio->get_name();
            $parameters[PortfolioController::PARAM_SECURITY_TOKEN] = self::security_token();
            $parameters[PortfolioController::PARAM_TOOL] = $this->get_tool();
            $parameters[PortfolioController::PARAM_ID] = $id;
            $parameters[PortfolioController::PARAM_TOOL] = $tool;
            $url = Chamilo::url('/main/portfolio/share.php', $parameters);
            $result[] = '<li>';
            $result[] = '<a href="' . $url . '">' . $portfolio->get_title() . '</a>';
            $result[] = '</li>';
        }
        $result[] = '</ul>';
        $result[] = '</span>';
        $result[] = '</span>';
        return implode("\n", $result);
    }

    function __toString()
    {
        return $this->display();
    }

}

/**
 * A "send to this portfolio" action. Actions are used by the SortableTable to 
 * perform actions on a set of objects. An action is composed of
 * 
 *      - a name
 *      - a title (displayed to the user)
 *      - code to execute
 * 
 * Usage:
 * 
 *       $form_actions = array();
 *       $form_action['...'] = get_lang('...');
 *       $portfolio_actions = Portfolio::actions();
 *       foreach($portfolio_actions as $action){
 *           $form_action[$action->get_name()] = $action->get_title();
 *       }
 *       $table->set_form_actions($form_action, 'path');
 * 
 * @see SortableTable
 */
class PortfolioBulkAction
{

    /**
     *
     * @param \Portfolio\Portfolio $portfolio
     * @return PortfolioBulkAction
     */
    public static function create($portfolio)
    {
        return new self($portfolio);
    }

    protected $name = '';
    protected $title = '';
    protected $portfolio = null;

    /**
     *
     * @param \Portfolio\Portfolio $portfolio 
     */
    public function __construct($portfolio)
    {
        $this->name = md5(__CLASS__) . '_' . $portfolio->get_name();
        $this->title = $portfolio->get_title() ? $portfolio->get_title() : get_lang('SendTo') . ' ' . $portfolio->get_name();
        $this->portfolio = $portfolio;
    }

    public function get_name()
    {
        return $this->name;
    }

    public function get_title()
    {
        return $this->title;
    }

    /**
     *
     * @return \Portfolio\Portfolio
     */
    public function get_portfolio()
    {
        return $this->portfolio;
    }

    public function accept()
    {
        $name = $this->get_name();
        $action = Request::get(PortfolioController::PARAM_ACTION);
        if ($name != $action) {
            return false;
        }
        $pathes = Request::get('path');
        if (empty($pathes)) {
            return false;
        }

        $course = Course::current();
        if (empty($course)) {
            return false;
        }
        return true;
    }

    public function run()
    {
        if (!$this->accept()) {
            return false;
        }

        $course = Course::current();

        $pathes = Request::get('path');
        $pathes = is_array($pathes) ? $pathes : array($pathes);

        $ids = array();
        foreach ($pathes as $path) {
            $doc = Document::get_by_path($course, $path);
            if ($doc) {
                $ids[] = $doc->get_id();
            }
        }
        if (empty($ids)) {
            return false;
        }

        $user = new \Portfolio\User();
        $user->email = Chamilo::user()->email();

        $artefact = new Portfolio\Artefact();
        $artefact->url = Portfolio::download_url($ids);

        $portfolio = $this->get_portfolio();
        $result = $portfolio->send($user, $artefact);
        return $result;
    }

}
