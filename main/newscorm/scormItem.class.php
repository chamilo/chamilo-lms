<?php //$id:$
/**
 * Container for the scormItem class that deals with <item> elements in an imsmanifest file
 * @package	dokeos.learnpath.scorm
 * @author	Yannick Warnier	<ywarnier@beeznest.org>
 */
/**
 * This class handles the <item> elements from an imsmanifest file.
 */
require_once('learnpathItem.class.php');
class scormItem extends learnpathItem{
	var $identifier = '';
	var $identifierref = '';
	var $isvisible = '';
	var $parameters = '';
	var $title = '';
	var $sub_items = array();
	var $metadata;
	//var $prerequisites = ''; - defined in learnpathItem.class.php
	var $max_time_allowed = ''; //should be something like HHHH:MM:SS.SS
	var $timelimitaction = '';
	var $datafromlms = '';
	var $mastery_score = '';
	var $scorm_contact;

    /**
     * Class constructor. Depending of the type of construction called ('db' or 'manifest'), will create a scormItem
     * object from database records or from the DOM element given as parameter
     * @param	string	Type of construction needed ('db' or 'manifest', default = 'manifest')
     * @param	mixed	Depending on the type given, DB id for the lp_item or reference to the DOM element
     */
    function scormItem($type='manifest',&$element) {
    	if(isset($element))
    	{

			$v = substr(phpversion(),0,1);
			if($v == 4){
	    		switch($type){
	    			case 'db':
	    				parent::learnpathItem($element,api_get_user_id());
	    				$this->scorm_contact = false;
	    				//TODO implement this way of metadata object creation
	    				return false;
	    			case 'manifest': //do the same as the default
	    			default:
				     	//if($first_item->type == XML_ELEMENT_NODE) this is already check prior to the call to this function
				     	$children = $element->children();
				    	foreach($children as $a => $dummy)
				     	{
				     		$child =& $children[$a];
				     		switch($child->type)
				     		{
				     			case XML_ELEMENT_NODE:
									switch($child->tagname){
				     					case 'title':
						     				$tmp_children = $child->children();
						     				if(count($tmp_children)==1 and $tmp_children[0]->content!='' )
						     				{
						     					$this->title = $tmp_children[0]->content;
						     				}
						     				break;
				     					case 'maxtimeallowed':
						     				$tmp_children = $child->children();
						     				if(count($tmp_children)==1 and $tmp_children[0]->content!='' )
						     				{
						     					$this->max_time_allowed = $tmp_children[0]->content;
						     				}
						     				break;
										case 'prerequisites':
						     				$tmp_children = $child->children();
						     				if(count($tmp_children)==1 and $tmp_children[0]->content!='' )
						     				{
						     					$this->prereq_string = $tmp_children[0]->content;
						     				}
						     				break;
										case 'timelimitaction':
						     				$tmp_children = $child->children();
						     				if(count($tmp_children)==1 and $tmp_children[0]->content!='' )
						     				{
						     					$this->timelimitaction = $tmp_children[0]->content;
						     				}
						     				break;
										case 'datafromlms':
						     				$tmp_children = $child->children();
						     				if(count($tmp_children)==1 and $tmp_children[0]->content!='' )
						     				{
						     					$this->datafromlms = $tmp_children[0]->content;
						     				}
						     				break;
										case 'masteryscore':
						     				$tmp_children = $child->children();
						     				if(count($tmp_children)==1 and $tmp_children[0]->content!='' )
						     				{
						     					$this->mastery_score = $tmp_children[0]->content;
						     				}
						     				break;
				     					case 'item':
				     						$oItem = new scormItem('manifest',$child);
				     						if($oItem->identifier != ''){
				     							$this->sub_items[$oItem->identifier] = $oItem;
				     						}
											break;
				     					case 'metadata':
				     						$this->metadata = new scormMetadata('manifest',$child);
				     						break;
				     				}
				     				break;
				     			case XML_TEXT_NODE:
				     				//this case is actually treated by looking into ELEMENT_NODEs above
				     				break;
				     		}
				     	}
				     	$attributes = $element->attributes();
				     	//$keep_href = '';
				     	foreach($attributes as $a1 => $dummy)
				     	{
				     		$attrib =& $attributes[$a1];
				     		switch($attrib->name){
				     			case 'identifier':
				     				$this->identifier = $attrib->value;
				     				break;
				     			case 'identifierref':
				     				$this->identifierref = $attrib->value;
				     				break;
				     			case 'isvisible':
				     				$this->isvisible = $attrib->value;
				     				break;
				     			case 'parameters':
				     				$this->parameters = $attrib->value;
				     				break;
				     		}
				     	}
						return true;

	    		}
	    	}elseif($v == 5){
	    		//parsing using PHP5 DOMXML methods
	    		switch($type){
	    			case 'db':
	    				parent::learnpathItem($element,api_get_user_id());
	    				$this->scorm_contact = false;
	    				//TODO implement this way of metadata object creation
	    				return false;
	    			case 'manifest': //do the same as the default
	    			default:
				     	//if($first_item->type == XML_ELEMENT_NODE) this is already check prior to the call to this function
				     	$children = $element->childNodes;
				    	foreach($children as $child)
				     	{
				     		switch($child->nodeType)
				     		{
				     			case XML_ELEMENT_NODE:
									switch($child->tagName){
				     					case 'title':
						     				$tmp_children = $child->childNodes;
						     				//if(count($tmp_children)==1 and $tmp_children[0]->textContent!='' )
						     				if($tmp_children->length==1 and $child->firstChild->nodeValue!='' )
						     				{
						     					$this->title = $child->firstChild->nodeValue;
						     				}
						     				break;
						     			case 'max_score':
					     					if($tmp_children->length==1 and $child->firstChild->nodeValue!='' ) {
						     					$this->max_score = $child->firstChild->nodeValue;
						     				}
						     				break;
				     					case 'maxtimeallowed':
				     					case 'adlcp:maxtimeallowed':
						     				$tmp_children = $child->childNodes;
						     				//if(count($tmp_children)==1 and $tmp_children[0]->textContent!='' )
						     				if($tmp_children->length==1 and $child->firstChild->nodeValue!='' )
						     				{
						     					$this->max_time_allowed = $child->firstChild->nodeValue;
						     				}
						     				break;
										case 'prerequisites':
										case 'adlcp:prerequisites':
						     				$tmp_children = $child->childNodes;
						     				//if(count($tmp_children)==1 and $tmp_children[0]->textContent!='' )
						     				if($tmp_children->length==1 and $child->firstChild->nodeValue!='' )
						     				{
						     					$this->prereq_string = $child->firstChild->nodeValue;
						     				}
						     				break;
										case 'timelimitaction':
										case 'adlcp:timelimitaction':
						     				$tmp_children = $child->childNodes;
						     				//if(count($tmp_children)==1 and $tmp_children[0]->textContent!='' )
						     				if($tmp_children->length==1 and $child->firstChild->nodeValue!='' )
						     				{
						     					$this->timelimitaction = $child->firstChild->nodeValue;
						     				}
						     				break;
										case 'datafromlms':
										case 'adlcp:datafromlms':
										case 'adlcp:launchdata': //in some cases (Wouters)
						     				$tmp_children = $child->childNodes;
						     				//if(count($tmp_children)==1 and $tmp_children[0]->textContent!='' )
						     				if($tmp_children->length==1 and $child->firstChild->nodeValue!='' )
						     				{
						     					$this->datafromlms = $child->firstChild->nodeValue;
						     				}
						     				break;
										case 'masteryscore':
										case 'adlcp:masteryscore':
						     				$tmp_children = $child->childNodes;
						     				//if(count($tmp_children)==1 and $tmp_children[0]->textContent!='' )
						     				if($tmp_children->length==1 and $child->firstChild->nodeValue!='' )
						     				{
						     					$this->mastery_score = $child->firstChild->nodeValue;
						     				}
						     				break;
				     					case 'item':
				     						$oItem = new scormItem('manifest',$child);
				     						if($oItem->identifier != ''){
				     							$this->sub_items[$oItem->identifier] = $oItem;
				     						}
											break;
				     					case 'metadata':
				     						$this->metadata = new scormMetadata('manifest',$child);
				     						break;
				     				}
				     				break;
				     			case XML_TEXT_NODE:
				     				//this case is actually treated by looking into ELEMENT_NODEs above
				     				break;
				     		}
				     	}
				     	if($element->hasAttributes()){
					     	$attributes = $element->attributes;
					     	//$keep_href = '';
					     	foreach($attributes as $attrib)
					     	{
					     		switch($attrib->name){
					     			case 'identifier':
					     				$this->identifier = $attrib->value;
					     				break;
					     			case 'identifierref':
					     				$this->identifierref = $attrib->value;
					     				break;
					     			case 'isvisible':
					     				$this->isvisible = $attrib->value;
					     				break;
					     			case 'parameters':
					     				$this->parameters = $attrib->value;
					     				break;
					     		}
					     	}
				     	}
						return true;

	    		}
			}else{
				//cannot parse because not PHP4 nor PHP5... We should not even be here anyway...
				return false;
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
    		'abs_order' => $abs_order,
			'datafromlms' => $this->datafromlms,
			'identifier' => $this->identifier,
			'identifierref' => $this->identifierref,
			'isvisible' => $this->isvisible,
			'level' => $level,
			'masteryscore' => $this->mastery_score,
			'maxtimeallowed' => $this->max_time_allowed,
			'metadata' => $this->metadata,
			'parameters' => $this->parameters,
			'prerequisites' => (!empty($this->prereq_string)?$this->prereq_string:''),
			'rel_order' => $rel_order,
			'timelimitaction' => $this->timelimitaction,
			'title' => $this->title,
			'max_score' => $this->max_score
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
     * Save function. Uses the parent save function and adds a layer for SCORM.
     * @param	boolean	Save from URL params (1) or from object attributes (0)
     */
    function save($from_outside=true,$prereqs_complete=false)
    {
    	parent::save($from_outside,$prereqs_complete);
    	//under certain conditions, the scorm_contact should not be set, because no scorm signal was sent
    	$this->scorm_contact = true;
    	if(!$this->scorm_contact){
    		//error_log('New LP - was expecting SCORM message but none received',0);
    	}
    }
}
?>