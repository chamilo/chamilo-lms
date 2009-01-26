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
require_once (dirname(__FILE__).'/../../../inc/global.inc.php');
require_once (dirname(__FILE__).'/../be.inc.php');

/**
 * Table to display flat view of a student's evaluations and links
 * @author Stijn Konings
 * @author Bert Stepp� (refactored, optimised, use of caching, datagenerator class)
 */
class UserTable extends SortableTable
{

	private $userid;
	private $datagen;


	/**
	 * Constructor
	 */
    function UserTable ($userid, $evals = array(), $links = array(), $addparams = null) {
    	parent :: SortableTable ('userlist', null, null, 0);

		$this->userid = $userid;

		$this->datagen = new UserDataGenerator($userid, $evals, $links);

		if (isset($addparams)) {
			$this->set_additional_parameters($addparams);
		}
		$column = 0;
		$this->set_header($column++, get_lang('Type'));
		$this->set_header($column++, get_lang('Evaluation'));
		$this->set_header($column++, get_lang('Course'));
		$this->set_header($column++, get_lang('Category'));
		$this->set_header($column++, get_lang('EvaluationAverage'));
		$this->set_header($column++, get_lang('Result'));
		
		$scoredisplay = ScoreDisplay :: instance();
		if ($scoredisplay->is_custom()) {
			$this->set_header($column++, get_lang('Display'));
		}
    }


	/**
	 * Function used by SortableTable to get total number of items in the table
	 */
	function get_total_number_of_items () {
		return $this->datagen->get_total_items_count();
	}


	/** 
	 * Function used by SortableTable to generate the data to display
	 */
	function get_table_data ($from = 1) {

		$scoredisplay = ScoreDisplay :: instance();

		// determine sorting type
		switch ($this->column) {
			// Type
			case 0:
				$sorting = UserDataGenerator :: UDG_SORT_TYPE;
				break;
			case 1:
				$sorting = UserDataGenerator :: UDG_SORT_NAME;
				break;
			case 2:
				$sorting = UserDataGenerator :: UDG_SORT_COURSE;
				break;
			case 3:
				$sorting = UserDataGenerator :: UDG_SORT_CATEGORY;
				break;
			case 4:
				$sorting = UserDataGenerator :: UDG_SORT_AVERAGE;
				break;
			case 5:
				$sorting = UserDataGenerator :: UDG_SORT_SCORE;
				break;
			case 6:
				$sorting = UserDataGenerator :: UDG_SORT_MASK;
				break;
		}
		if ($this->direction == 'DESC') {
			$sorting |= UserDataGenerator :: UDG_SORT_DESC;
		} else {
			$sorting |= UserDataGenerator :: UDG_SORT_ASC;
		}
		$data_array = $this->datagen->get_data($sorting, $from, $this->per_page);
		// generate the data to display
		$sortable_data = array();
		foreach ($data_array as $data) {
			if ($data[2]!="") {//filter by course removed
				$row = array ();
				$row[] = $this->build_type_column ($data[0]);
				$row[] = $this->build_name_link ($data[0]);
				$row[] = $data[2];
				$row[] = $data[3];
				$row[] = $data[4];
				$row[] = $data[5];
				if ($scoredisplay->is_custom())
					$row[] = $data[6];
				$sortable_data[] = $row;
			}
		}
		return $sortable_data;
	}


// Other functions

	private function build_type_column ($item) {
		return build_type_icon_tag($item->get_icon_name());
	}

	private function build_name_link ($item) {
		switch ($item->get_item_type()) {
			// evaluation
			case 'E' :
				return '&nbsp;'
					. '<a href="gradebook_view_result.php?selecteval=' . $item->get_id() . '">'
					. $item->get_name()
					. '</a>';
			// link
			case 'L' :
				return '&nbsp;<a href="' . $item->get_link() . '">'
						. $item->get_name()
						. '</a>'
						. '&nbsp;[' . $item->get_type_name() . ']';
		}
	}

}