<?php // $Id: Document.class.php 4733 2005-05-02 08:54:49Z bmol $
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Bart Mollet (bart.mollet@hogent.be)
	
	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".
	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	See the GNU General Public License for more details.
	
	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
============================================================================== 
*/
require_once('Resource.class.php');
define('DOCUMENT','file');
define('FOLDER','folder');
/**
 * An document
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package dokeos.backup
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
		echo substr($this->path,8);	
	}
}
?>