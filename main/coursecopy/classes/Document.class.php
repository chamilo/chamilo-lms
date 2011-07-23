<?php
/* For licensing terms, see /license.txt */
/**
 * Document class file
 * @package chamilo.backup
 */
require_once 'Resource.class.php';

define('DOCUMENT','file');
define('FOLDER','folder');

/**
 * An document
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package chamilo.backup
 */
class Document extends Resource
{
	var $path;
	var $comment;
	var $file_type;
	var $size;
	var $title;
	/**
	 * Create a new Document
	 * @param int $id
	 * @param string $path
	 * @param string $comment
	 * @param string $title
	 * @param string $file_type (DOCUMENT or FOLDER);
	 * @param int $size
	 */
	function Document($id,$path,$comment,$title,$file_type,$size)
	{
		parent::Resource($id,RESOURCE_DOCUMENT);
		$this->path = 'document'.$path;
		$this->comment = $comment;
		$this->title = $title;
		$this->file_type = $file_type;
		$this->size = $size;
	}
	/**
	 * Show this document
	 */
	function show()
	{
		parent::show();
		echo preg_replace('@^document@', '', $this->path);
		if (!empty($this->title) && (api_get_setting('use_document_title') == 'true'))
		{
			if (strpos($this->path, $this->title) === false)
			{
				echo " - ".$this->title;
			}
		}
	}
}
