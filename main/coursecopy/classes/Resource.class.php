<?php
// $Id: Resource.class.php 22200 2009-07-17 19:47:58Z iflorespaz $
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
/**
 * All possible resource-types
 */
define('RESOURCE_DOCUMENT', 'document');
define('RESOURCE_GLOSSARY', 'glossary');
define('RESOURCE_EVENT', 'calendar_event');
define('RESOURCE_LINK', 'link');
define('RESOURCE_COURSEDESCRIPTION', 'course_description');
define('RESOURCE_LEARNPATH', 'learnpath');
define('RESOURCE_ANNOUNCEMENT', 'announcement');
define('RESOURCE_FORUM', 'forum');
define('RESOURCE_FORUMTOPIC', 'thread');
define('RESOURCE_FORUMPOST', 'post');
define('RESOURCE_QUIZ', 'quiz');
define('RESOURCE_QUIZQUESTION', 'Exercise_Question');
define('RESOURCE_TOOL_INTRO', 'Tool introduction');
define('RESOURCE_LINKCATEGORY', 'Link_Category');
define('RESOURCE_FORUMCATEGORY', 'Forum_Category');
define('RESOURCE_SCORM', 'Scorm');
define('RESOURCE_SURVEY','survey');
define('RESOURCE_SURVEYQUESTION','survey_question');
define('RESOURCE_SURVEYINVITATION','survey_invitation');

/**
 * Representation of a resource in a Dokeos-course.
 * This is a base class of which real resource-classes (for Links,
 * Documents,...) should be derived.
 * @author Bart Mollet <bart.mollet@hogent.be>s
 * @package  dokeos.backup
 * @todo Use the gloabaly defined constants voor tools and remove the RESOURCE_*
 * constants
 */
class Resource
{
	/**
	 * The id from this resource in the source course
	 */
	var $source_id;
	/**
	 * The id from this resource in the destination course
	 */
	var $destination_id;
	/**
	 * The type of this resource
	 */
	var $type;
	/**
	 * Linked resources
	 */
	var $linked_resources;
	/**
	 * The properties of this resource
	 */
	var $item_properties;
	/**
	 * Create a new Resource
	 * @param int $id The id of this resource in the source course.
	 * @param constant $type The type of this resource.
	 */
	function Resource($id, $type)
	{
		$this->source_id = $id;
		$this->type = $type;
		$this->destination_id = -1;
		$this->linked_resources = array ();
		$this->item_properties = array ();
	}
	/**
	 * Add linked resource
	 */
	function add_linked_resource($type, $id)
	{
		$this->linked_resources[$type][] = $id;
	}
	/**
	 * Get linked resources
	 */
	function get_linked_resources()
	{
		return $this->linked_resources;
	}
	/**
	 * Checks if this resource links to a given resource
	 */
	function links_to(& $resource)
	{
		if (is_array($this->linked_resources[$resource->get_type()]))
		{
			return in_array($resource->get_id(), $this->linked_resources[$resource->get_type()]);
		}
		return false;
	}
	/**
	 * Returns the id of this resource.
	 * @return int The id of this resource in the source course.
	 */
	function get_id()
	{
		return $this->source_id;
	}
	/**
	 * Resturns the type of this resource
	 * @return constant The type.
	 */
	function get_type()
	{
		return $this->type;
	}
	/**
	 * Get the constant which defines the tool of this resource. This is
	 * used in the item_properties table.
	 * @todo once the RESOURCE_* constants are replaced by the globally
	 * defined TOOL_* constants, this function will be replaced by get_type()
	 */
	function get_tool()
	{
		switch($this->get_type())
		{
			case RESOURCE_DOCUMENT:
				return TOOL_DOCUMENT;
			case RESOURCE_LINK:
				return TOOL_LINK;
			case RESOURCE_EVENT:
				return TOOL_CALENDAR_EVENT;
			case RESOURCE_COURSEDESCRIPTION:
				return TOOL_COURSE_DESCRIPTION;
			case RESOURCE_LEARNPATH:
				return TOOL_LEARNPATH;
			case RESOURCE_ANNOUNCEMENT:
				return TOOL_ANNOUNCEMENT;
			case RESOURCE_FORUM:
				return TOOL_FORUM;
			case RESOURCE_FORUMTOPIC:
				return TOOL_THREAD;
			case RESOURCE_FORUMPOST:
				return TOOL_POST;
			case RESOURCE_QUIZ:
				return TOOL_QUIZ;
			//case RESOURCE_QUIZQUESTION: //no corresponding global constant
			//	return TOOL_QUIZ_QUESTION;
			//case RESOURCE_TOOL_INTRO:
			//	return TOOL_INTRO;
			//case RESOURCE_LINKCATEGORY:
			//	return TOOL_LINK_CATEGORY;
			//case RESOURCE_TOOL_FORUMCATEGORY:
			//	return TOOL_FORUM_CATEGORY;
			//case RESOURCE_SCORM:
			//	return TOOL_SCORM_DOCUMENT;
			case RESOURCE_SURVEY:
				return TOOL_SURVEY;
			//case RESOURCE_SURVEYQUESTION:
			//	return TOOL_SURVEY_QUESTION;
			//case RESOURCE_SURVEYINVITATION:
			//	return TOOL_SURVEY_INVITATION;
			case RESOURCE_GLOSSARY:
				return TOOL_GLOSSARY;
			default:
				return null;
		}
	}
	/**
	 * Set the destination id
	 * @param int $id The id of this resource in the destination course.
	 */
	function set_new_id($id)
	{
		$this->destination_id = $id;
	}
	/**
	 * Check if this resource is allready restored in the destination course.
	 * @return bool true if allready restored (i.e. destination_id is set).
	 */
	function is_restored()
	{
		return $this->destination_id > -1;
	}
	/**
	 * Show this resource
	 */
	function show()
	{
		//echo 'RESOURCE: '.$this->get_id().' '.$type[$this->get_type()].' ';
	}
}