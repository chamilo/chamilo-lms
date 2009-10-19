<?php //$id:$
/**
 * Container for the scormResource class
 * @package dokeos.learnpath.scorm
 * @author	Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * Class defining the <resource> tag in an imsmanifest.xml file
 *
 */
class scormResource {
	var $identifier = '';
	var $type = 'webcontent';
	//var $identifierref = '';
	var $scormtype = 'sco'; //fix problems with ENI content where asset is not defined
	var $base = '';
	var $href = '';
	var $metadata;
	//var $file_href;
	//var $file_metadata;
	var $files = array();
	var $dependencies = array();

    /**
     * Class constructor. Depending of the type of construction called ('db' or 'manifest'), will create a scormResource
     * object from database records or from the DOM element given as parameter
     * @param	string	Type of construction needed ('db' or 'manifest', default = 'manifest')
     * @param	mixed	Depending on the type given, DB id for the lp_item or reference to the DOM element
     */
    function scormResource($type='manifest',&$element) {
    	/*
    	echo "<pre>Analysing resource:<br />\n";
    	var_dump($element);
    	echo "</pre><br />\n";
    	*/
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
				     					case 'file':
				     						//echo "Child is a file tag<br />\n";
				     						$this->files[] = $child->get_attribute('href');
				     						//var_dump($this->files);
				     						//ignoring file metadata
											//files[] array contains all <file href='x'> tags one by one
											break;
				     					case 'metadata':
				     						//echo "Child is a metadata tag<br />\n";
				     						$this->metadata = new scormMetadata('manifest',$child);
				     						break;
				     					case 'dependency':
				     						//echo "Child is a dependency tag<br />\n";
				     						//need to get identifierref attribute inside dependency node
				     						//dependencies[] array represents all <dependency identifierref='x'> tags united
				     						$this->dependencies[] = $child->get_attribute('identifierref');
				     						break;
				     				}
				     				break;
				     		}
				     	}
				     	$attributes = $element->attributes();
				     	//$keep_href = '';
				     	if(count($attributes)>0){ //in some cases we get here with an empty attributes array
				     	//TODO find when and why we get such a case (empty array)
					     	foreach($attributes as $a1 => $dummy)
					     	{
					     		$attrib =& $attributes[$a1];
					     		switch($attrib->name){
					     			case 'identifier':
					     				$this->identifier = $attrib->value;
					     				break;
					     			case 'type':
					     				if(!empty($attrib->value)){
					     					$this->type = $attrib->value;
					     				}
					     				break;
					     			case 'scormtype':
					     				if(!empty($attrib->value)){
					     					$this->scormtype = $attrib->value;
					     				}
					     				break;
					     			case 'base':
					     				$this->base = $attrib->value;
					     				break;
					     			case 'href':
					     				$this->href = $attrib->value;
					     				break;
					     		}
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
				    	if(is_array($children)){
					    	foreach($children as $child)
					     	{
					     		switch($child->nodeType)
					     		{
					     			case XML_ELEMENT_NODE:
										switch($child->tagName){
					     					case 'file':
					     						//echo "Child is a file tag<br />\n";
					     						$this->files[] = $child->getAttribute('href');
												break;
					     					case 'metadata':
					     						//echo "Child is a metadata tag<br />\n";
					     						$this->metadata = new scormMetadata('manifest',$child);
					     						break;
					     					case 'dependency':
					     						//need to get identifierref attribute inside dependency node
					     						//dependencies[] array represents all <dependency identifierref='x'> tags united
					     						$this->dependencies[] = $child->getAttribute('identifierref');
					     						break;
					     				}
					     				break;
					     		}
					     	}
				     	}
				     	//$keep_href = '';
				     	if($element->hasAttributes()){ //in some cases we get here with an empty attributes array
				     	//TODO find when and why we get such a case (empty array)
					     	$attributes = $element->attributes;
					     	foreach($attributes as $attrib)
					     	{
					     		switch($attrib->name){
					     			case 'identifier':
					     				$this->identifier = $attrib->value;
					     				break;
					     			case 'type':
					     				if(!empty($attrib->value)){
					     					$this->type = $attrib->value;
					     				}
					     				break;
					     			case 'scormtype':
					     				if(!empty($attrib->value)){
					     					$this->scormtype = $attrib->value;
					     				}
					     				break;
					     			case 'base':
					     				$this->base = $attrib->value;
					     				break;
					     			case 'href':
					     				$this->href = $attrib->value;
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
      * Path getter
      * @return	string	Path for this resource
      */
     function get_path()
     {
     	if(!empty($this->href))
     	{
     		require_once('learnpath.class.php');
     		return learnpath::escape_string($this->href);
     	}else{
     		return '';
     	}
     }
     /**
      * Scorm type getter
      * @return	string	generally 'asset' or 'sco' as these are the only two values defined in SCORM 1.2
      */
     function get_scorm_type()
     {
     	if(!empty($this->scormtype)){
     		require_once('learnpath.class.php');
     		return learnpath::escape_string($this->scormtype);
     	}else{
     		return '';
     	}
     }
}
?>
