<?php //$id:$
/**
 * Container for the scormMetadata class, setup to hold information about the <metadata> element in imsmanifest files
 * @package dokeos.learnpath.scorm
 */
/**
 * scormMetadata class, handling each <metadata> element found in an imsmanifest file
 */
class scormMetadata {
	var	$lom = '';
	var $schema = '';
	var $schemaversion = '';
	var $location = '';
	var $text = '';
	var $attribs = array();

	/**
	 * Class constructor. Works in two different ways defined by the first element, being 'db' or 'manifest'.
	 * If 'db', then it is built using the information available in the Dokeos database. If 'manifest', then it
	 * is built using the element given as a parameter, expecting it to be a <metadata> element pointer from the
	 * DOM parser.
	 * @param	string	Type of creation required. Can be 'db' or 'manifest' (default)
	 * @param	mixed	Depending on the type, can be the DB ID of the learnpath item or the pointer to the <metadata> element in the imsmanifest.xml file
	 * @return	boolean	True on success, false on failure
	 */
    function scormMetadata($type='manifest', &$element) {
    	if(isset($element))
    	{
    		$v = substr(phpversion(),0,1);
			if($v == 4){
	    		switch($type){
	    			case 'db':
	    				//TODO implement this way of metadata object creation
	    				return false;
	    				//break;
	    			case 'manifest': //do the same as the default
				     	//if($first_item->type == XML_ELEMENT_NODE) this is already check prior to the call to this function
				     	$children = $element->children();
				    	foreach($children as $a => $dummy)
				     	{
				     		$child =& $children[$a];
				     		switch($child->type)
				     		{
				     			case XML_ELEMENT_NODE:
									//could be 'lom','schema','schemaversion' or 'location'
				     				switch($child->tagname){
				     					case 'lom':
				     						$childchildren = $child->children();
				     						foreach($childchildren as $index => $dummy)
				     						{
				     							$my_elem = $childchildren[$index];
				     							//there is generally only one child here
				     							//$this->lom[] = $my_elem->content;
				     							$this->lom = $my_elem->content;
				     						}
				     						break;
				     					case 'schema':
				     						$childchildren = $child->children();
				     						foreach($childchildren as $index => $dummy)
				     						{
				     							$my_elem = $childchildren[$index];
				     							//there is generally only one child here
				     							//$this->schema[] = $my_elem->content;
				     							$this->schema = $my_elem->content;
				     						}
				     						break;
				     					case 'schemaversion':
				     						$childchildren = $child->children();
				     						foreach($childchildren as $index => $dummy)
				     						{
				     							$my_elem = $childchildren[$index];
				     							//there is generally only one child here
				     							//$this->schemaversion[] = $my_elem->content;
				     							$this->schemaversion = $my_elem->content;
				     						}
				     						break;
				     					case 'location':
				     						$childchildren = $child->children();
				     						foreach($childchildren as $index => $dummy)
				     						{
				     							$my_elem = $childchildren[$index];
				     							//there is generally only one child here
				     							//$this->location[] = $my_elem->content;
				     							$this->location = $my_elem->content;
				     						}
				     						break;
				     				}
				     				break;
				     			case XML_TEXT_NODE:
				     				if(trim($child->content) != '')
				     				{
				     					if(count($children == 1)){
				     						//if this is the only child at this level and it is a content... save differently
				     						$this->text = $child->content;
				     					}else{
				     						$this->text[$element->tagname] = $child->content;
				     					}
				     				}
				     				break;
				     		}
				     	}
				     	$attributes = $element->attributes();
				     	//$keep_href = '';
				     	if(is_array($attributes)){
					     	foreach($attributes as $a1 => $dummy)
					     	{
					     		$attrib =& $attributes[$a1];
					     		if(trim($attrib->value) != ''){
					     			$this->attribs[$attrib->name] = $attrib->value;
					     		}
					     	}
				     	}
						return true;
	    				//break;
	    		}
	    	}elseif($v == 5){
	    		//parsing using PHP5 DOMXML methods
	    		switch($type){
	    			case 'db':
	    				//TODO implement this way of metadata object creation
	    				return false;
	    				//break;
	    			case 'manifest': //do the same as the default
				     	$children = $element->childNodes;
				    	foreach($children as $child)
				     	{
				     		switch($child->nodeType)
				     		{
				     			case XML_ELEMENT_NODE:
									//could be 'lom','schema','schemaversion' or 'location'
				     				switch($child->tagName){
				     					case 'lom':
				     						$childchildren = $child->childNodes;
				     						foreach($childchildren as $childchild)
				     						{
				     							//$this->lom = $childchild->textContent;
				     							$this->lom = $childchild->nodeValue;
				     						}
				     						break;
				     					case 'schema':
				     						$childchildren = $child->childNodes;
				     						foreach($childchildren as $childchild)
				     						{
				     							//there is generally only one child here
				     							//$this->schema = $childchildren[$index]->textContent;
				     							$this->schema = $childchild->nodeValue;
				     						}
				     						break;
				     					case 'schemaversion':
				     						$childchildren = $child->childNodes;
				     						foreach($childchildren as $childchild)
				     						{
				     							//there is generally only one child here
				     							//$this->schemaversion = $childchildren[$index]->textContent;
				     							$this->schemaversion = $childchild->nodeValue;
				     						}
				     						break;
				     					case 'location':
				     						$childchildren = $child->childNodes;
				     						foreach($childchildren as $childchild)
				     						{
				     							//there is generally only one child here
				     							//$this->location = $childchildren[$index]->textContent;
				     							$this->location = $childchild->nodeValue;
				     						}
				     						break;
				     				}
				     				break;
				     			case XML_TEXT_NODE:
				     				if(trim($child->textContent) != '')
				     				{
				     					if(count($children == 1)){
				     						//if this is the only child at this level and it is a content... save differently
				     						$this->text = $child->textContent;
				     					}else{
				     						$this->text[$element->tagName] = $child->textContent;
				     					}
				     				}
				     				break;
				     		}
				     	}
				     	$attributes = $element->attributes;
				     	//$keep_href = '';
				     	if(is_array($attributes)){
					     	foreach($attributes as $attrib)
					     	{
					     		if(trim($attrib->value) != ''){
					     			$this->attribs[$attrib->name] = $attrib->value;
					     		}
					     	}
				     	}
						return true;
	    				//break;
	    		}
			}else{
				//cannot parse because not PHP4 nor PHP5... We should not even be here anyway...
				return false;
			}
    	}
    	return false;
    }
}
?>