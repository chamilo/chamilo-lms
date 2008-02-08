<?php //$id:$
/**
 * Container for the aiccItem class that deals with AICC Assignable Units (AUs)
 * @package	dokeos.learnpath
 * @author	Yannick Warnier	<ywarnier@beeznest.org>
 * @license	GNU/GPL - See Dokeos license directory for details
 */
/**
 * This class handles the elements from an AICC Descriptor file.
 */
require_once('learnpathItem.class.php');
class aiccItem extends learnpathItem{
	var $identifier = '';//AICC AU's system_id
	var $identifierref = '';
	var $parameters = ''; //AICC AU's web_launch
	var $title = ''; //no AICC equivalent
	var $sub_items = array(); //AICC elements (des)
	//var $prerequisites = ''; - defined in learnpathItem.class.php
	//var $max_score = ''; //defined in learnpathItem
	//var $path = ''; //defined in learnpathItem	
	var $maxtimeallowed = '00:00:00'; //AICC AU's max_time_allowed
	var $timelimitaction = ''; //AICC AU's time_limit_action
	var $masteryscore = ''; //AICC AU's mastery_score
	var $core_vendor = ''; //AICC AU's core_vendor
	var $system_vendor = ''; //AICC AU's system_vendor
	var $au_type = ''; //AICC AU's type
	var $command_line = ''; //AICC AU's command_line
	var $debug=0;

    /**
     * Class constructor. Depending of the type of construction called ('db' or 'manifest'), will create a scormItem
     * object from database records or from the array given as second parameter
     * @param	string	Type of construction needed ('db' or 'config', default = 'config')
     * @param	mixed	Depending on the type given, DB id for the lp_item or parameters array
     */
    function aiccItem($type='config',$params) {
    	if(isset($params))
    	{
    		switch($type){
    			case 'db':
    				parent::learnpathItem($params,api_get_user_id());
    				$this->aicc_contact = false;
    				//TODO implement this way of metadata object creation
    				return false;
    			case 'config': //do the same as the default
    			default:
			     	//if($first_item->type == XML_ELEMENT_NODE) this is already check prior to the call to this function
			     	foreach($params as $a => $value)
			     	{
			     		switch($a)
			     		{
            				case 'system_id':
            					$this->identifier = mysql_real_escape_string(strtolower($value));
            					break;
            				case 'type':
            					$this->au_type = mysql_real_escape_string($value);
            					break;
            				case 'command_line':
            					$this->command_line = mysql_real_escape_string($value);
            					break;
            				case 'max_time_allowed':
            					$this->maxtimeallowed = mysql_real_escape_string($value);
            					break;
            				case 'time_limit_action':
            					$this->timelimitaction = mysql_real_escape_string($value);
            					break;
            				case 'max_score':
            					$this->max_score = mysql_real_escape_string($value);
            					break;
            				case 'core_vendor':
            					$this->core_vendor = mysql_real_escape_string($value);
            					break;
            				case 'system_vendor':
            					$this->system_vendor = mysql_real_escape_string($value);
            					break;
            				case 'file_name':
            					$this->path = mysql_real_escape_string($value);
            					break;
            				case 'mastery_score':
            					$this->masteryscore = mysql_real_escape_string($value);
            					break;
            				case 'web_launch':
            					$this->parameters = mysql_real_escape_string($value);
            					break;
			     		}
			     	}
					return true;
    		}
    	}
    	return false;
    }
    /**
     * Builds a flat list with the current item and calls itself recursively on all children
     * @param	array	Reference to the array to complete with the current item
     * @param	integer	Optional absolute order (pointer) of the item in this learning path
     * @param	integer	Optional relative order of the item at this level
     * @param	integer	Optional level. If not given, assumes it's level 0
     */
    function get_flat_list(&$list,&$abs_order,$rel_order=1,$level=0)
    {
    	$list[] = array(
    		'au_type' => $this->au_type,
			'command_line' => $this->command_line,
			'core_vendor'	=> $this->core_vendor,
			'identifier' => $this->identifier,
			'identifierref' => $this->identifierref,
			'masteryscore' => $this->masteryscore,
			'maxtimeallowed' => $this->maxtimeallowed,
			'level' => $level,
			'parameters' => $this->parameters,
			'prerequisites' => (!empty($this->prereq_string)?$this->prereq_string:''),
			'timelimitaction' => $this->timelimitaction,
			);
		$abs_order++;
		$i = 1;
		foreach($this->sub_items as $id => $dummy)
		{
			$oSubitem =& $this->sub_items[$id];
			$oSubitem->get_flat_list($list,$abs_order,$i,$level+1);
			$i++;
		}
    }
    /**
     * Save function. Uses the parent save function and adds a layer for AICC.
     * @param	boolean	Save from URL params (1) or from object attributes (0)
     */
    function save($from_outside=true, $prereqs_complete=false)
    {
    	parent::save($from_outside, $prereqs_complete=false);
    	//under certain conditions, the scorm_contact should not be set, because no scorm signal was sent
    	$this->aicc_contact = true;
    	if(!$this->aicc_contact){
    		//error_log('New LP - was expecting SCORM message but none received',0);	
    	}
    }
}
?>