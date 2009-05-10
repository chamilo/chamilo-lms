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
 * Class to select, sort and transform object data into array data,
 * used for the teacher's evaluation results view
 * @author Bert Steppé
 */
class ResultsDataGenerator
{

	// Sorting types constants
	const RDG_SORT_LASTNAME = 1;
	const RDG_SORT_FIRSTNAME = 2;
	const RDG_SORT_SCORE = 4;
	const RDG_SORT_MASK = 8;

	const RDG_SORT_ASC = 16;
	const RDG_SORT_DESC = 32;


	private $evaluation;
	private $results;
	private $is_course_ind;
	private $include_edit;


	/**
	 * Constructor
	 */
    function ResultsDataGenerator ( $evaluation,
    								$results = array(),
    								$include_edit = false) {
    	$this->evaluation = $evaluation;
		$this->results = (isset($results) ? $results : array());
    }


	/**
	 * Get total number of results (rows)
	 */
	public function get_total_results_count () {
		return count($this->results);
	}


	/**
	 * Get actual array data
	 * @return array 2-dimensional array - each array contains the elements:
	 * 0 ['id']        : user id
	 * 1 ['result_id'] : result id
	 * 2 ['lastname']  : user lastname
	 * 3 ['firstname'] : user firstname
	 * 4 ['score']     : student's score
	 * 5 ['display']   : custom score display (only if custom scoring enabled)
	 */
	public function get_data ($sorting = 0, $start = 0, $count = null, $ignore_score_color = false) {

		// do some checks on count, redefine if invalid value
		if (!isset($count)) {
			$count = count ($this->results) - $start;
		}
		if ($count < 0) {
			$count = 0;
		}
		$scoredisplay = ScoreDisplay :: instance();
		// generate actual data array
		$table = array();
		foreach($this->results as $result) {
			$user = array();
			$info = get_user_info_from_id($result->get_user_id());
			$user['id'] = $result->get_user_id();
			$user['result_id'] = $result->get_id();
			$user['lastname'] = $info['lastname'];
			$user['firstname'] = $info['firstname'];
			$user['score'] = $this->get_score_display($result->get_score(),true, $ignore_score_color);
			if ($scoredisplay->is_custom())
				$user['display'] = $this->get_score_display($result->get_score(),false, $ignore_score_color);;
			$table[] = $user;
		}


		// sort array
		if ($sorting & self :: RDG_SORT_LASTNAME) {
			usort($table, array('ResultsDataGenerator', 'sort_by_last_name'));
		} elseif ($sorting & self :: RDG_SORT_FIRSTNAME) {
			usort($table, array('ResultsDataGenerator', 'sort_by_first_name'));	
		} elseif ($sorting & self :: RDG_SORT_SCORE) {
			usort($table, array('ResultsDataGenerator', 'sort_by_score'));
		} elseif ($sorting & self :: RDG_SORT_MASK) {
			usort($table, array('ResultsDataGenerator', 'sort_by_mask'));			
		}
		if ($sorting & self :: RDG_SORT_DESC) {
			$table = array_reverse($table);			
		}
		return array_slice($table, $start, $count);

	}

	private function get_score_display ($score, $realscore, $ignore_score_color) {
		if ($score != null) {
			$display_type = SCORE_DIV_PERCENT;
			if ($ignore_score_color) {
				$display_type |= SCORE_IGNORE_SPLIT;				
			}
			$scoredisplay = ScoreDisplay :: instance();
			return $scoredisplay->display_score
					(array($score,$this->evaluation->get_max()),
					 $display_type,
					 $realscore ? SCORE_ONLY_DEFAULT : SCORE_ONLY_CUSTOM);
			}
			else {
				return '';			
		  }
	}

	// Sort functions - used internally
	function sort_by_last_name($item1, $item2) {
		if (api_strtolower($item1['lastname']) == api_strtolower($item2['lastname'])) {
			return 0;
		} else {
			return (api_strtolower($item1['lastname']) < api_strtolower($item2['lastname']) ? -1 : 1);			
		}
	}

	function sort_by_first_name($item1, $item2) {
		if (api_strtolower($item1['firstname']) == api_strtolower($item2['firstname'])) {
			return 0;
		}
		else {
			return (api_strtolower($item1['firstname']) < api_strtolower($item2['firstname']) ? -1 : 1);			
		}

	}
	
	function sort_by_score($item1, $item2) {
		if ($item1['score'] == $item2['score']) {
			return 0;			
		}else {
			return ($item1['score'] < $item2['score'] ? -1 : 1);			
		}
	}
	
	function sort_by_mask ($item1, $item2) {
		$score1 = (isset($item1['score']) ? array($item1['score'],$this->evaluation->get_max()) : null);
		$score2 = (isset($item2['score']) ? array($item2['score'],$this->evaluation->get_max()) : null);
		return ScoreDisplay :: compare_scores_by_custom_display($score1, $score2);
	}
}
