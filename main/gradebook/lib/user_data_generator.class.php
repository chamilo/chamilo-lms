<?php

/**
 * Class to select, sort and transform object data into array data,
 * used for a student's general view
 * @author Bert Stepp�
 */
class UserDataGenerator
{

	// Sorting types constants
	const UDG_SORT_TYPE = 1;
	const UDG_SORT_NAME = 2;
	const UDG_SORT_COURSE = 4;
	const UDG_SORT_CATEGORY = 8;
	const UDG_SORT_AVERAGE = 16;
	const UDG_SORT_SCORE = 32;
	const UDG_SORT_MASK = 64;

	const UDG_SORT_ASC = 128;
	const UDG_SORT_DESC = 256;


	private $items;
	private $userid;

	private $coursecodecache;
	private $categorycache;
	private $scorecache;
	private $avgcache;


    function UserDataGenerator($userid, $evals = array(), $links = array()) {
		$this->userid = $userid;
		$evals_filtered = array();
		foreach ($evals as $eval) {
			$toadd = true;
			$coursecode = $eval->get_course_code();
			if (isset($coursecode)) {
				$result = Result :: load (null, $userid, $eval->get_id());
				if (count($result) == 0) {
					$toadd = false;
				}	
			}
			if ($toadd) {
				$evals_filtered_copy = $evals;
			}
				
		}//isset($coursecode) && strcmp($coursecode,api_get_course_id())===0
		if (count($result) == 0) {
			$evals_filtered=$evals;
		} else {
			$evals_filtered=$evals_filtered_copy;
		}
		$this->items = array_merge ($evals_filtered, $links);


		$this->coursecodecache = array();
		$this->categorycache = array();
		$this->scorecache = null;
		$this->avgcache = null;

    }


	/**
	 * Get total number of items (rows)
	 */
	public function get_total_items_count() {
		return count($this->items);
	}



	/**
	 * Get actual array data
	 * @return array 2-dimensional array - each array contains the elements:
	 * 0: eval/link object
	 * 1: item name
	 * 2: course name
	 * 3: category name
	 * 4: average score
	 * 5: student's score
	 * 6: student's score as custom display (only if custom scoring enabled)
	 */
	public function get_data ($sorting = 0, $start = 0, $count = null, $ignore_score_color = false) {
		// do some checks on count, redefine if invalid value
		if (!isset($count)) {
			$count = count ($this->items) - $start;
		}
		if ($count < 0) {
			$count = 0;
		}
		$allitems = $this->items;

		// sort users array
		if ($sorting & self :: UDG_SORT_TYPE) {
			usort($allitems, array('UserDataGenerator', 'sort_by_type'));
		}elseif ($sorting & self :: UDG_SORT_NAME) {
			usort($allitems, array('UserDataGenerator', 'sort_by_name'));
		} elseif ($sorting & self :: UDG_SORT_COURSE) {
			usort($allitems, array('UserDataGenerator', 'sort_by_course'));
		} elseif ($sorting & self :: UDG_SORT_CATEGORY) {
			usort($allitems, array('UserDataGenerator', 'sort_by_category'));
		} elseif ($sorting & self :: UDG_SORT_AVERAGE) {
			// if user sorts on average scores, first calculate them and cache them
			foreach ($allitems as $item) {
				$this->avgcache[$item->get_item_type() . $item->get_id()]
								= $item->calc_score();
			}
			usort($allitems, array('UserDataGenerator', 'sort_by_average'));
		} elseif ($sorting & self :: UDG_SORT_SCORE) {
			// if user sorts on student's scores, first calculate them and cache them
			foreach ($allitems as $item) {
				$this->scorecache[$item->get_item_type() . $item->get_id()]
								= $item->calc_score($this->userid);
			}		
			usort($allitems, array('UserDataGenerator', 'sort_by_score'));
		} elseif ($sorting & self :: UDG_SORT_MASK) {
			// if user sorts on student's masks, first calculate scores and cache them
			foreach ($allitems as $item) {
				$this->scorecache[$item->get_item_type() . $item->get_id()]
								= $item->calc_score($this->userid);
			}
			usort($allitems, array('UserDataGenerator', 'sort_by_mask'));
		}

		if ($sorting & self :: UDG_SORT_DESC) {
			$allitems = array_reverse($allitems);
		}
		// select the items we have to display
		$visibleitems = array_slice($allitems, $start, $count);

		// fill score cache if not done yet
		if (!isset ($this->scorecache)) {
			foreach ($visibleitems as $item) {
				$this->scorecache[$item->get_item_type() . $item->get_id()]
								= $item->calc_score($this->userid);
			}
				
		}
		// generate the data to display
		$scoredisplay = ScoreDisplay :: instance();
		$data = array();
		foreach ($visibleitems as $item) {
			$row = array ();
			$row[] = $item;
			$row[] = $item->get_name();
			$row[] = $this->build_course_name ($item);
			$row[] = $this->build_category_name ($item);
			$row[] = $this->build_average_column ($item, $ignore_score_color);
			$row[] = $this->build_result_column ($item, $ignore_score_color);
			if ($scoredisplay->is_custom())
				$row[] = $this->build_mask_column ($item, $ignore_score_color);
			$data[] = $row;
		}
		
		return $data;

	}

// Sort functions
// Make sure to only use functions as defined in the GradebookItem interface !

	function sort_by_type($item1, $item2) {
		if ($item1->get_item_type() == $item2->get_item_type()) {
			return $this->sort_by_name($item1,$item2);
		} else {
			return ($item1->get_item_type() < $item2->get_item_type() ? -1 : 1);
		}
	}

	function sort_by_course($item1, $item2) {
		$name1 = api_strtolower($this->get_course_name_from_code_cached($item1->get_course_code()));
		$name2 = api_strtolower($this->get_course_name_from_code_cached($item2->get_course_code()));
		if ($name1 == $name2) {
			return 0;
		} else {
			return ($name1 < $name2 ? -1 : 1);
		}
	}

	function sort_by_category($item1, $item2) {
		$cat1 = $this->get_category_cached($item1->get_category_id());
		$cat2 = $this->get_category_cached($item2->get_category_id());

		$name1 = api_strtolower($this->get_category_name_to_display($cat1));
		$name2 = api_strtolower($this->get_category_name_to_display($cat2));
		if ($name1 == $name2) {
			return 0;
		} else {
			return ($name1 < $name2 ? -1 : 1);
		}
	}


	function sort_by_name($item1, $item2) {
		if (api_strtolower($item1->get_name()) == api_strtolower($item2->get_name())) {
			return 0;
		} else {
			return (api_strtolower($item1->get_name()) < api_strtolower($item2->get_name()) ? -1 : 1);
		}		
	}


	function sort_by_average($item1, $item2) {
		$score1 = $this->avgcache[$item1->get_item_type() . $item1->get_id()];
		$score2 = $this->avgcache[$item2->get_item_type() . $item2->get_id()];
		return $this->compare_scores($score1, $score2);
	}	

	function sort_by_score($item1, $item2) {
		$score1 = $this->scorecache[$item1->get_item_type() . $item1->get_id()];
		$score2 = $this->scorecache[$item2->get_item_type() . $item2->get_id()];
		return $this->compare_scores($score1, $score2);
	}	

	function sort_by_mask($item1, $item2) {
		$score1 = $this->scorecache[$item1->get_item_type() . $item1->get_id()];
		$score2 = $this->scorecache[$item2->get_item_type() . $item2->get_id()];
		return ScoreDisplay :: compare_scores_by_custom_display($score1, $score2);
	}	

	function compare_scores ($score1, $score2) {
		if (!isset($score1)) {
			return (isset($score2) ? 1 : 0);
		} elseif (!isset($score2)) {
			return -1;
		} elseif (($score1[0]/$score1[1]) == ($score2[0]/$score2[1])) {
			return 0;
		} else {
			return (($score1[0]/$score1[1]) < ($score2[0]/$score2[1]) ? -1 : 1);
		}
			
	}		

// Other functions
	private function build_course_name ($item) {
		return $this->get_course_name_from_code_cached($item->get_course_code());
	}

	private function build_category_name ($item) {
		$cat = $this->get_category_cached($item->get_category_id());
		return $this->get_category_name_to_display($cat);
	}


	private function build_average_column ($item, $ignore_score_color) {
		if (isset($this->avgcache)) {
			$avgscore = $this->avgcache[$item->get_item_type() . $item->get_id()];
		} else {
			$avgscore = $item->calc_score();
		}
		$scoredisplay = ScoreDisplay :: instance();
		$displaytype = SCORE_AVERAGE;
		if ($ignore_score_color)
			$displaytype |= SCORE_IGNORE_SPLIT;
		return $scoredisplay->display_score($avgscore, $displaytype);
	}

	private function build_result_column ($item, $ignore_score_color) {
		$studscore = $this->scorecache[$item->get_item_type() . $item->get_id()];
		$scoredisplay = ScoreDisplay :: instance();
		$displaytype = SCORE_DIV_PERCENT;
		if ($ignore_score_color) {
			$displaytype |= SCORE_IGNORE_SPLIT;
		}
		return $scoredisplay->display_score($studscore, $displaytype, SCORE_ONLY_DEFAULT);
	}

	private function build_mask_column ($item, $ignore_score_color) {
		$studscore = $this->scorecache[$item->get_item_type() . $item->get_id()];
		$scoredisplay = ScoreDisplay :: instance();
		$displaytype = SCORE_DIV_PERCENT;
		if ($ignore_score_color) {
			$displaytype |= SCORE_IGNORE_SPLIT;
		}
		return $scoredisplay->display_score($studscore, $displaytype, SCORE_ONLY_CUSTOM);
	}


	private function get_course_name_from_code_cached ($coursecode) {
		if (isset ($this->coursecodecache)
		 && isset ($this->coursecodecache[$coursecode])) {
		 	return $this->coursecodecache[$coursecode];
		 } else {
			$name = get_course_name_from_code($coursecode);
			$this->coursecodecache[$coursecode] = $name;
			return $name;
		}
	}

	private function get_category_cached ($category_id) {
		if (isset ($this->categorycache)
		 && isset ($this->categorycache[$category_id])) {
		 	return $this->categorycache[$category_id];
		 }else {
			$cat = Category::load($category_id);
			if (isset($cat)){
				$this->categorycache[$category_id] = $cat[0];
				return $cat[0];
			}else
				return null;
		}
	}

	private function get_category_name_to_display ($cat) {
		
		if (isset($cat)){
			
			if ($cat->get_parent_id() == '0' || $cat->get_parent_id() == null){
					return '';
				} else {
					return $cat->get_name();
				}
				
			} else {
					return '';
			}
	}
}
