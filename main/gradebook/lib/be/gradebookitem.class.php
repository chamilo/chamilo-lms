<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2008 Dokeos Latinoamerica SAC
	Copyright (c) 2006 Dokeos SPRL
	Copyright (c) 2006 Ghent University (UGent)
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/**
 * Interface for all displayable items in the gradebook.
 * @author Bert Steppé
 */
interface GradebookItem
{
	public function get_item_type();

	public function get_id();
	public function get_name();
	public function get_description();
	public function get_course_code();
	public function get_weight();
	public function get_date();
	public function is_visible();

	public function get_icon_name();

	public function calc_score($stud_id = null);

}