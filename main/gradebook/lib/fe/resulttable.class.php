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
 * Table to display results for an evaluation
 * @author Stijn Konings
 * @author Bert Steppé
 */
class ResultTable extends SortableTable
{

	private $datagen;
	private $evaluation;
	private $allresults;
	private $iscourse;


	/**
	 * Constructor
	 */
    function ResultTable ($evaluation, $results = array(), $iscourse, $addparams = null,$forprint = false) {
    	parent :: __construct ('resultlist', null, null, 1);

		$this->datagen = new ResultsDataGenerator($evaluation, $results, true);

		$this->evaluation = $evaluation;
		$this->iscourse = $iscourse;
		$this->forprint = $forprint;
		
		if (isset ($addparams))  {
			$this->set_additional_parameters($addparams);
		}
		$scoredisplay = ScoreDisplay :: instance();
		$column= 0;
		if ($this->iscourse == '1') {
			$this->set_header($column++, '', false);
			$this->set_form_actions(array (
					'delete' => get_lang('Delete')
			));
		}
		$this->set_header($column++, get_lang('LastName'));
		$this->set_header($column++, get_lang('FirstName'));
		$this->set_header($column++, get_lang('Score'));
		if ($scoredisplay->is_custom()) {
			$this->set_header($column++, get_lang('Display'));
		}
		if (!$this->forprint) {
			$this->set_header($column++, get_lang('Modify'),false);
		}
    }


	/**
	 * Function used by SortableTable to get total number of items in the table
	 */
	function get_total_number_of_items () {
		return $this->datagen->get_total_results_count();
	}


	/** 
	 * Function used by SortableTable to generate the data to display
	 */
	function get_table_data ($from = 1) {

		$scoredisplay = ScoreDisplay :: instance();

		// determine sorting type
		$col_adjust = ($this->iscourse == '1' ? 1 : 0);
		switch ($this->column) {
			// Type
			case (0 + $col_adjust):
				$sorting = ResultsDataGenerator :: RDG_SORT_LASTNAME;
				break;
			case (1 + $col_adjust):
				$sorting = ResultsDataGenerator :: RDG_SORT_FIRSTNAME;
				break;
			case (2 + $col_adjust):
				$sorting = ResultsDataGenerator :: RDG_SORT_SCORE;
				break;
			case (3 + $col_adjust):
				$sorting = ResultsDataGenerator :: RDG_SORT_MASK;
				break;
		}
		if ($this->direction == 'DESC') {
			$sorting |= ResultsDataGenerator :: RDG_SORT_DESC;
		} else {
			$sorting |= ResultsDataGenerator :: RDG_SORT_ASC;
		}
			
		$data_array = $this->datagen->get_data($sorting, $from, $this->per_page);


		// generate the data to display
		$sortable_data = array();
		foreach ($data_array as $item) {
			$row = array ();
			if ($this->iscourse == '1') {
				 $row[] = $item['result_id'];
			}	
			$row[] = $item['lastname'];
			$row[] = $item['firstname'];
			$row[] = $item['score'];
			if ($scoredisplay->is_custom()) {
				$row[] = $item['display'];
			}
			if (!$this->forprint) {
				$row[] = $this->build_edit_column ($item);
			}
			$sortable_data[] = $row;
		}
		
		return $sortable_data;
	}

// Other functions

	private function build_edit_column ($item) {
		$status=CourseManager::get_user_in_course_status(api_get_user_id(), api_get_course_id());
		if ($status==1) {//api_is_course_admin()
			$edit_column = '<a href="' . api_get_self() . '?editres=' . $item['result_id'] . '&selecteval=' . $this->evaluation->get_id() . '"><img src="../img/edit.gif" border="0" title="' . get_lang('Modify') . '" alt="" /></a>';
		}
		if ($this->evaluation->get_course_code() == null) {
			$edit_column.= '&nbsp;<a href="' . api_get_self() . '?resultdelete=' . $item['result_id'] . '&selecteval=' . $this->evaluation->get_id() . '" onclick="return confirmationuser();"><img src="../img/delete.gif" border="0" title="' . get_lang('Delete') . '" alt="" /></a>';
		$edit_column.= '&nbsp;<a href="user_stats.php?userid=' . $item['id'] . '&selecteval=' . $this->evaluation->get_id() . '"><img src="../img/statistics.gif" width="17px" border="0" title="' . get_lang('Statistics') . '" alt="" /></a>';

		}
		// evaluation's origin is a link
		if ($this->evaluation->get_category_id() < 0) {
			$link = LinkFactory :: get_evaluation_link ($this->evaluation->get_id());

			$doc_url = $link->get_view_url($item['id']);
			if ($doc_url != null) {
				$edit_column .= '&nbsp;<a href="'. $doc_url . '" target="_blank">'
								.'<img src="'. api_get_path(WEB_CODE_PATH) . 'img/link.gif" border="0" title="' . get_lang('OpenDocument') . '" alt="" />'
								.'</a>';
			}
		}
		return $edit_column;
	}
}