<?php // $Id: ScormDocument.class.php 9246 2006-09-25 13:24:53Z bmol $
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

/**
 * ScormDocument class
 * @author Olivier Brouckaert <oli.brouckaert@dokeos.com>
 * @package dokeos.backup
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
