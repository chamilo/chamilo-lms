<?php //$id:$
/**
 * Container for the aiccResource class that deals with elemens from AICC Course Structure file
 * @package	dokeos.learnpath
 * @author	Yannick Warnier <ywarnier@beeznest.org>
 * @license	GNU/GPL - See Dokeos license directory for details
 */
/**
 * Class defining the Block elements in an AICC Course Structure file
 *
 */
require_once('learnpathItem.class.php');
class aiccBlock extends learnpathItem{
	var $identifier = '';
	var $members = array();

    /**
     * Class constructor. Depending of the type of construction called ('db' or 'manifest'), will create a scormResource
     * object from database records or from the array given as second param
     * @param	string	Type of construction needed ('db' or 'config', default = 'config')
     * @param	mixed	Depending on the type given, DB id for the lp_item or parameters array
     */
    function aiccBlock($type='config',$params) {

    	if(isset($params))
    	{
    		switch($type){
    			case 'db':
    				//TODO implement this way of object creation
    				return false;
    			case 'config': //do the same as the default
    			default:
			     	foreach($params as $a => $value)
			     	{
			     		switch($a)
			     		{
   			     			case 'system_id':
   			     				$this->identifier = strtolower($value);
			     				break;
			     			case 'member':
			     				if(strstr($value,',')!==false){
			     					$temp = split(',',$value);
			     					foreach($temp as $val){
			     						if(!empty($val)){
			     							$this->members[] = $val;
			     						}
			     					}
			     				}
			     				break;
			     		}
			     	}
					return true;

    		}
    	}
    	return false;
     }
}
?>