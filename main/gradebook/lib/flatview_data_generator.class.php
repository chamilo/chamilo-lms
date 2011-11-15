<?php
/* For licensing terms, see /license.txt */
/**
 * Class to select, sort and transform object data into array data,
 * used for the teacher's flat view
 * @author Bert Steppé
 * @package chamilo.gradebook
 */
/**
 * Class
 * @package chamilo.gradebook
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
	public  $category = array();

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
	public function get_header_names ($items_start = 0, $items_count = null , $show_detail = false) {
		$headers = array();
		$headers[] = get_lang('LastName');
		$headers[] = get_lang('FirstName');
        
		if (!isset($items_count)) {
			$items_count = count($this->evals_links) - $items_start;
		}		
		//@todo move these in a function
		$sum_categories_weight_array = array();		
		if (isset($this->category) && !empty($this->category)) {		    
			$categories = Category::load(null, null, null, $this->category->get_id());
			if (!empty($categories)) {
			    foreach($categories as $category) {			         
				    $sum_categories_weight_array[$category->get_id()] = $category->get_weight();
                }
			} else {
			    $sum_categories_weight_array[$this->category->get_id()] = $this->category->get_weight();
			}
		}
        
		for ($count=0; ($count < $items_count ) && ($items_start + $count < count($this->evals_links)); $count++) {
			$item = $this->evals_links[$count + $items_start];
			
			//$headers[] = $item->get_name().' <br /> '.get_lang('Max').' '.$this->get_max_result_by_link($count + $items_start).' ';
			$sub_cat_percentage = $sum_categories_weight_array[$item->get_category_id()];
			$weight = round($item->get_weight()/($sub_cat_percentage) *  $sub_cat_percentage/$this->category->get_weight() *100, 2);
			$headers[] = $item->get_name().'  '.$weight.' % ';
			if ($show_detail) {
				//$headers[] = $item->get_name().' ('.get_lang('Detail').')';
			}
		}

		$headers[] = get_lang('GradebookQualificationTotal').' 100%';
		if ($show_detail) {
			//$headers[] = get_lang('GradebookQualificationTotal').' ('.get_lang('Detail').')';
		}
        
		return $headers;
	}
	
	function get_max_result_by_link($id) {		
		$usertable = array ();
		
		$items_count = count ($this->evals) + count ($this->links);
		
		$item_value = 0;
		$item_total = 0;
		$max = 0;
		foreach ($this->users as $user) {
			$item  = $this->evals_links [$id];					
			$score = $item->calc_score($user[0]);			
			$divide=( ($score[1])==0 ) ? 1 : $score[1];
			//$item_value = round($score[0]/$divide*$item->get_weight(),2);			
			if ($score[0] > $max) {
				$max = $score[0];
			}			
		}		
		return $max ;
	}

	/**
	 * Get array containing evaluation items
	 */
	public function get_evaluation_items($items_start = 0, $items_count = null) {
		$headers = array();
		if (!isset($items_count)) {
			$items_count = count($this->evals_links) - $items_start;
		}
		for ($count=0;
			 ($count < $items_count ) && ($items_start + $count < count($this->evals_links));
			 $count++) {
			$item = $this->evals_links [$count + $items_start];
			$headers[] = $item->get_name();
		}
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
	public function get_data ($users_sorting = 0, $users_start = 0, $users_count = null,
							  $items_start = 0, $items_count = null,
							  $ignore_score_color = false, $show_all = false) {
		
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

		$data = array ();
		$displaytype = SCORE_DIV;
		if ($ignore_score_color) {
			$displaytype |= SCORE_IGNORE_SPLIT;
		}
		//@todo move these in a function
		$sum_categories_weight_array = array();     
        if (isset($this->category) && !empty($this->category)) {            
            $categories = Category::load(null, null, null, $this->category->get_id());
            if (!empty($categories)) {
                foreach($categories as $category) {                  
                    $sum_categories_weight_array[$category->get_id()] = $category->get_weight();
                }
            } else {
                $sum_categories_weight_array[$this->category->get_id()] = $this->category->get_weight();
            }
        }
		
		foreach ($selected_users as $user) {
			$row = array ();
			$row[] = $user[0];	// user id
			$row[] = $user[2];	// last name
			$row[] = $user[3];	// first name

			$item_value = 0;
            $item_value_total = 0;
			$item_total = 0;
            

			for ($count=0; ($count < $items_count ) && ($items_start + $count < count($this->evals_links)); $count++) {
				$item  			= $this->evals_links[$count + $items_start];
                
				$score 			= $item->calc_score($user[0]);
                
				$divide			= ( ($score[1])==0 ) ? 1 : $score[1];
                
                $sub_cat_percentage = $sum_categories_weight_array[$item->get_category_id()];
                
                $item_value     = round($score[0]/$divide,2)*100;
                $percentage     = round($item->get_weight()/($sub_cat_percentage) *  $sub_cat_percentage/$this->category->get_weight(), 2);
                $item_value     = $percentage*$item_value;
                
				$item_total		+= $item->get_weight();
                
				$temp_score = $scoredisplay->display_score($score,SCORE_DIV_PERCENT, SCORE_ONLY_SCORE);
                
                $temp_score = $temp_score . ' '.$item_value;
                
				if (!$show_all) {
					//$row[] = $scoredisplay->display_score($score,SCORE_DIV_PERCENT);
					if (in_array($item->get_type() , array(LINK_EXERCISE, LINK_DROPBOX, LINK_STUDENTPUBLICATION, 
					                                       LINK_LEARNPATH, LINK_FORUM_THREAD,  LINK_ATTENDANCE,LINK_SURVEY))) {
					                                           
                        
					    if (!empty($score[0])) {																		
                            $row[] = $temp_score.' ';
                        } else {
                            $row[] = '';
                        }
                        //$row[] = $scoredisplay->display_score($score,SCORE_DIV_PERCENT, SCORE_ONLY_SCORE);	
					} else {
						//$row[] = $scoredisplay->display_score($score,SCORE_DIV_PERCENT);
                        //$row[] = $score[0];
                        $row[] = $temp_score.' ';
					}					
				} else {
					//$row[] = $scoredisplay->display_score($score, SCORE_DECIMAL);
					$row[] = $temp_score;
					//$row[] = $scoredisplay->display_score($score, SCORE_DIV_PERCENT);
				}
                $item_value_total +=$item_value;              
            }

			$total_score = array($item_value_total, $item_total);
            
			
			if (!$show_all) {				
				$row[] = $scoredisplay->display_score($total_score);
			} else {
				$row[] = $scoredisplay->display_score($total_score, SCORE_DIV_PERCENT_WITH_CUSTOM);
				//$row[] = $scoredisplay->display_score($total_score, SCORE_DIV_PERCENT);
			}
			unset($score);
			$data[] = $row;
		}
		return $data;
	}

	/**
	 * Get actual array data evaluation/link scores
	 */
	public function get_evaluation_sumary_results ($session_id = null) {

		$usertable = array ();
		foreach ($this->users as $user) { $usertable[] = $user; }
		$selected_users = $usertable;

		// generate actual data array for all selected users
		$data = array();

		foreach ($selected_users as $user) {
			$row = array ();
			$item_value=0;
			$item_total=0;
			for ($count=0;$count < count($this->evals_links); $count++) {
				$item = $this->evals_links [$count];
				$score = $item->calc_score($user[0]);				
				$porcent_score = isset($score[1]) &&  $score[1] > 0 ? round(($score[0]*100)/$score[1]):0;
				$row[$item->get_name()] = $porcent_score;
			}
			$data[$user[0]] = $row;
		}

		// get evaluations for every user by item
		$data_by_item = array();
		foreach ($data as $uid => $items) {
			$tmp = array();
			foreach ($items as $item => $value) {
				$tmp[] = $item;
				if (in_array($item,$tmp)) {
					$data_by_item[$item][$uid] = $value;
				}
			}
		}

		// get evaluation sumary results (maximum, minimum and average of evaluations for all students)
		$result = array();
		$maximum = $minimum = $average = 0;
		foreach ($data_by_item as $k => $v) {
			$average = round(array_sum($v)/count($v));
			arsort($v);
			$maximum = array_shift($v);
			$minimum = array_pop($v);
			$sumary_item = array('max'=>$maximum, 'min'=>$minimum, 'avg'=>$average);
			$result[$k] = $sumary_item;
		}

		return $result;
	}


	public function get_data_to_graph () {
		// do some checks on users/items counts, redefine if invalid values
		$usertable = array ();
		foreach ($this->users as $user) {
			$usertable[] = $user;
		}
		// sort users array
		usort($usertable, array ('FlatViewDataGenerator','sort_by_first_name'));

		$data = array ();
		
		$selected_users = $usertable;
		foreach ($selected_users as $user) {
			$row = array ();
			$row[] = $user[0];	// user id
			$item_value = 0;
			$item_total = 0;

			for ($count=0;$count < count($this->evals_links); $count++) {
				$item = $this->evals_links[$count];
				$score = $item->calc_score($user[0]);
			
				$divide =( ($score[1])==0 ) ? 1 : $score[1];
                $item_value += round($score[0]/$divide*$item->get_weight(),2);
				$item_total += $item->get_weight();
				
				
				$score_denom = ($score[1]==0) ? 1 : $score[1];
				$score_final = round(($score[0] / $score_denom) * 100,2);
				$row[] = $score_final;
			}
			$total_score = array($item_value, $item_total);
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
				$row[] = array ($score_final, trim($scoredisplay->display_score($score, SCORE_CUSTOM,null, true)));

			}
			$total_score=array($item_value,$item_total);
			$score_final = round(($item_value / $item_total) * 100,2);
			$row[] =array ($score_final, trim($scoredisplay->display_score($total_score, SCORE_CUSTOM, null, true)));

			$data[] = $row;
		}
		return $data;
	}
	// Sort functions - used internally

	function sort_by_last_name($item1, $item2) {
		return api_strcmp($item1[2], $item2[2]);
	}

	function sort_by_first_name($item1, $item2) {
		return api_strcmp($item1[3], $item2[3]);
	}
}
