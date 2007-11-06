<?php //$id:$
/**
 * Container for the scormOrganization class
 * @package	scorm.learnpath
 * @author	Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * Class defining the <organization> tag in an imsmanifest.xml file
 */
class scormOrganization {
	var $identifier = '';
	var $structure = '';
	var $title = '';
	var $items = array();
	var $metadata;
    /**
     * Class constructor. Depending of the type of construction called ('db' or 'manifest'), will create a scormOrganization
     * object from database records or from the DOM element given as parameter
     * @param	string	Type of construction needed ('db' or 'manifest', default = 'manifest')
     * @param	mixed	Depending on the type given, DB id for the lp_item or reference to the DOM element
     */
    function scormOrganization($type='manifest',&$element,$scorm_charset='UTF-8') {
    	if(isset($element))
    	{
			$v = substr(phpversion(),0,1);
			if($v == 4){
	    		switch($type){
	    			case 'db':
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
				     					case 'item':
				     						$oItem = new scormItem('manifest',$child);
				     						if($oItem->identifier != ''){
												$this->items[$oItem->identifier] = $oItem;
				     						}
											break;
				     					case 'metadata':
				     						$this->metadata = new scormMetadata('manifest',$child);
				     						break;
				     					case 'title':
						     				$tmp_children = $child->children();
						     				if(count($tmp_children)==1 and $tmp_children[0]->content!='' )
						     				{
						     					$this->title = $tmp_children[0]->content;
						     				}
						     				break;
				     				}
				     				break;
				     			case XML_TEXT_NODE:
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
				     			case 'structure':
				     				$this->structure = $attrib->value;
				     				break;
				     		}
				     	}
						return true;
				     	
	    		}
	    	}elseif($v == 5){
	    		//parsing using PHP5 DOMXML methods
	    		switch($type){
	    			case 'db':
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
				     					case 'item':
				     						$oItem = new scormItem('manifest',$child);
				     						if($oItem->identifier != ''){
												$this->items[$oItem->identifier] = $oItem;
				     						}
											break;
				     					case 'metadata':
				     						$this->metadata = new scormMetadata('manifest',$child);
				     						break;
				     					case 'title':
						     				$tmp_children = $child->childNodes;
						     				if($tmp_children->length==1 and $child->firstChild->nodeValue != '' )
						     				{
						     					$this->title = html_entity_decode(html_entity_decode($child->firstChild->nodeValue,ENT_QUOTES,$scorm_charset));
						     				}
						     				break;
				     				}
				     				break;
				     			case XML_TEXT_NODE:
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
					     			case 'structure':
					     				$this->structure = $attrib->value;
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
	 * Get a flat list of items in the organization
	 * @return	array	Array containing an ordered list of all items with their level and all information related to each item
	 */
	function get_flat_items_list()
	{
		$list = array();
		$i = 1;
		foreach($this->items as $id=>$dummy)
		{
			$abs_order = 0;
			$this->items[$id]->get_flat_list($list,$abs_order,$i,0); //passes the array as a pointer so it is modified in $list directly
			$i++;
		}
		return $list;
	}
    /**
     * Name getter
     * @return	string	Name or empty string
     */
    function get_name()
    {
    	if(!empty($this->title)){
    		return mysql_real_escape_string($this->title);
    	}else{
    		return '';
    	}
    }
    /**
     * Reference identifier getter
     * @return	string	Identifier or empty string
     */
    function get_ref()
    {
    	if(!empty($this->identifier)){
    		return mysql_real_escape_string($this->identifier);
    	}else{
    		return '';
    	}
    }
    /**
     * Sets the title element
     * @param	string	New title to set
     */
    function set_name($title){
    	if(!empty($title)){
    		$this->title = mysql_real_escape_string($title);
    	}
    }
}
?>