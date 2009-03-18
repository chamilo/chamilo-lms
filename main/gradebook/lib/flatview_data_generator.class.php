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
 * used for the teacher's flat view
 * @author Bert Stepp�
 */
class FlatViewDataGenerator
{

	// Sorting types constants
	const FVDG_SORT_LASTNAME = 1;
	const FVDG_SORT_FIRSTNAME = 2;
	const FVDG_SORT_ASC = 4;
	const FVDG_SORT_DESC = 8;

	private $users;
	private $evals;
	private $links;
	private $evals_links;

	/**
	 * Constructor
	 */
    public function FlatViewDataGenerator ($users= array (), $evals= array (), $links= array ()) {
		$this->users = (isset($users) ? $users : array());
		$this->evals = (isset($evals) ? $evals : array());
		$this->links = (isset($links) ? $links : array());
		$this->evals_links = array_merge($this->evals, $this->links);
    }


	/**
	 * Get total number of users (rows)
	 */
	public function get_total_users_count() {
		return count($this->users);
	}
	
	/**
	 * Get total number of evaluations/links (columns) (the 2 users columns not included)
	 */
	public function get_total_items_count() {
		return count($this->evals_links);
	}
	

	/**
	 * Get array containing column header names (incl user columns)
	 */
	public function get_header_names ($items_start = 0, $items_count = null) {
		$headers = array();
		$headers[] = get_lang('LastName');
		$headers[] = get_lang('FirstName');
		if (!isset($items_count)) {
			$items_count = count($this->evals_links) - $items_start;			
		}
		for ($count=0;
			 ($count < $items_count ) && ($items_start + $count < count($this->evals_links));
			 $count++) {
			$item = $this->evals_links [$count + $items_start];
			$headers[] = $item->get_name();
		}
		$headers[] = get_lang('GradebookQualificationTotal');
		return $headers;
	}

	/**
	 * Get actual array data
	 * @return array 2-dimensional array - each array contains the elements:
	 * 0: user id
	 * 1: user lastname
	 * 2: user firstname
	 * 3+: evaluation/link scores
	 */
	public function get_data ($users_sorting = 0,
							  $users_start = 0, $users_count = null, 
							  $items_start = 0, $items_count = null,
							  $ignore_score_color = false) {
		// do some checks on users/items counts, redefine if invalid values
		if (!isset($users_count)) {
			$users_count = count ($this->users) - $users_start;			
		}
		if ($users_count < 0) {
			$users_count = 0;		
		}
		if (!isset($items_count)) {
			$items_count = count ($this->evals) + count ($this->links) - $items_start;			
		}
		if ($items_count < 0) {
			$items_count = 0;			
		}
		// copy users to a new array that we will sort
		// TODO - needed ?
		$usertable = array ();
		foreach ($this->users as $user) {
			$usertable[] = $user;			
		}
		// sort users array
		if ($users_sorting & self :: FVDG_SORT_LASTNAME) {
			usort($usertable, array ('FlatViewDataGenerator','sort_by_last_name'));
		} elseif ($users_sorting & self :: FVDG_SORT_FIRSTNAME) {
			usort($usertable, array ('FlatViewDataGenerator','sort_by_first_name'));			
		}
		if ($users_sorting & self :: FVDG_SORT_DESC) {
			$usertable = array_reverse($usertable);			
		}
		// select the requested users
		$selected_users = array_slice($usertable, $users_start, $users_count);
		// generate actual data array

		$scoredisplay = ScoreDisplay :: instance();
		
		$data= array ();
		$displaytype = SCORE_DIV;
		if ($ignore_score_color) {
			$displaytype |= SCORE_IGNORE_SPLIT;			
		}

		foreach ($selected_users as $user) {
			$row = array ();
			$row[] = $user[0];	// user id
			$row[] = $user[1];	// last name
			$row[] = $user[2];	// first name
			
			$item_value=0;
			$item_total=0;
			
			for ($count=0;
				 ($count < $items_count ) && ($items_start + $count < count($this->evals_links));
				 $count++) {
				$item = $this->evals_links [$count + $items_start];
				$score = $item->calc_score($user[0]);
				$divide=( ($score[1])==0 ) ? 1 : $score[1];
				$item_value+=round($score[0]/$divide*$item->get_weight(),2);
				$item_total+=$item->get_weight();					
				$row[] = $scoredisplay->display_score($score,SCORE_DIV_PERCENT);								
			}	
			$total_score=array($item_value,$item_total);
			$row[] = $scoredisplay->display_score($total_score,SCORE_DIV_PERCENT);

			unset($score);
			$data[] = $row;
		}
		return $data;
	}
	
	
	public function get_data_to_graph () {
		// do some checks on users/items counts, redefine if invalid values
		$usertable = array ();
		foreach ($this->users as $user) {
			$usertable[] = $user;			
		}
		// sort users array
		usort($usertable, array ('FlatViewDataGenerator','sort_by_first_name'));			
		
		// generate actual data array
		$scoredisplay = ScoreDisplay :: instance();		
		$data= array ();
		$displaytype = SCORE_DIV;		
		$selected_users = $usertable;
		foreach ($selected_users as $user) {
			$row = array ();
			$row[] = $user[0];	// user id			
			$item_value=0;
			$item_total=0;
			
			for ($count=0;$count < count($this->evals_links); $count++) {
				$item = $this->evals_links [$count];
				$score = $item->calc_score($user[0]);
				$divide=( ($score[1])==0 ) ? 1 : $score[1];
				$item_value+=round($score[0]/$divide*$item->get_weight(),2);
				$item_total+=$item->get_weight();									
				$score_denom=($score[1]==0) ? 1 : $score[1];
				$score_final = round(($score[0] / $score_denom) * 100,2);		
				$row[] = $score_final;								
			}	
			$total_score=array($item_value,$item_total);			
			$score_final = round(($item_value / $item_total) * 100,2);				
			$row[] = $score_final;		
			$data[] = $row;
		}
		return $data;
	}
	
	public function get_data_to_graph2 () {
		// do some checks on users/items counts, redefine if invalid values
		$usertable = array ();
		foreach ($this->users as $user) {
			$usertable[] = $user;			
		}
		// sort users array
		usort($usertable, array ('FlatViewDataGenerator','sort_by_first_name'));			
		
		// generate actual data array
		$scoredisplay = ScoreDisplay :: instance();		
		$data= array ();
		$displaytype = SCORE_DIV;		
		$selected_users = $usertable;
		foreach ($selected_users as $user) {
			$row = array ();
			$row[] = $user[0];	// user id			
			$item_value=0;
			$item_total=0;
			
			for ($count=0;$count < count($this->evals_links); $count++) {
				$item = $this->evals_links [$count];
				$score = $item->calc_score($user[0]);
				$divide=( ($score[1])==0 ) ? 1 : $score[1];
				$item_value+=round($score[0]/$divide*$item->get_weight(),2);
				$item_total+=$item->get_weight();									
				$score_denom=($score[1]==0) ? 1 : $score[1];
				$score_final = round(($score[0] / $score_denom) * 100,2);		
				$row[] = array ($score_final, strip_tags($scoredisplay->display_score($score,SCORE_DIV_PERCENT, SCORE_ONLY_CUSTOM)));								
			}	
			$total_score=array($item_value,$item_total);			
			$score_final = round(($item_value / $item_total) * 100,2);				
			$row[] = $score_final;		
			$data[] = $row;
		}
		return $data;
	}
	// Sort functions - used internally

	function sort_by_last_name($item1, $item2) {
		if (strtolower($item1[1]) == strtolower($item2[1])) {
			return 0;			
		} else {
			return (strtolower($item1[1]) < strtolower($item2[1]) ? -1 : 1);			
		}
	}

	function sort_by_first_name($item1, $item2)
	{
		if (strtolower($item1[2]) == strtolower($item2[2])) {
			return $this->sort_by_last_name($item1, $item2);			
		} else {
			return (strtolower($item1[2]) < strtolower($item2[2]) ? -1 : 1);			
		}
	}
}