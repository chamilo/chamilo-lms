<?php
/* For licensing terms, see /license.txt */
/**
 * SCORM document backup script
 * @package chamilo.backup
 */
require_once 'Resource.class.php';

/**
 * ScormDocument class
 * @author Olivier Brouckaert <oli.brouckaert@dokeos.com>
 * @package chamilo.backup
 */
class ScormDocument extends Resource
{
	var $path;
	var $title;

	/**
	 * Create a new Scorm Document
	 * @param int $id
	 * @param string $path
	 * @param string $title
	 */
	function ScormDocument($id,$path,$title)
	{
		parent::Resource($id,RESOURCE_SCORM);
		$this->path = 'scorm'.$path;
		$this->title = $title;
	}

	/**
	 * Show this document
	 */
	function show()
	{
		parent::show();
		$path = preg_replace('@^scorm/@', '', $this->path);
		echo $path;
		if (!empty($this->title) && (api_get_setting('use_document_title') == 'true'))
		{
			if (strpos($path, $this->title) === false)
			{
				echo " - ".$this->title;
			}
		}
	}
}
