<?php
/* For licensing terms, see /license.txt */
/**
 * Script
 * @package chamilo.gradebook
 */
/**
 * Init
 */

// Score display types constants
define('SCORE_DIV',                      1);    // X / Y
define('SCORE_PERCENT',                  2);    // XX %
define('SCORE_DIV_PERCENT',              3);    // X / Y (XX %)
define('SCORE_AVERAGE',                  4);    // XX %
define('SCORE_DECIMAL',                  5);    // 0.50  (X/Y)

//@todo where is number 6?

define('SCORE_IGNORE_SPLIT',             8);    //  ??

define('SCORE_DIV_PERCENT_WITH_CUSTOM',  9);    // X / Y (XX %) - Good!
define('SCORE_CUSTOM',                  10);    // Good!
define('SCORE_DIV_SIMPLE_WITH_CUSTOM',  11);    // X - Good!



define('SCORE_BOTH',1);
define('SCORE_ONLY_DEFAULT',2);
define('SCORE_ONLY_CUSTOM',3);


/**
 * Class to display scores according to the settings made by the platform admin.
 * This class works as a singleton: call instance() to retrieve an object.
 * @author Bert Steppé
 * @package chamilo.gradebook
 */
class ScoreDisplay
{
    // Singleton stuff

	/**
	 * Get the instance of this class
	 */
	public static function instance($category_id = 0) {
		static $instance;
		if (!isset ($instance)) {
			$instance = new ScoreDisplay($category_id);
		}
		return $instance;
	}


// Static methods

	/**
	 * Compare the custom display of 2 scores, can be useful in sorting
	 */
	public static function compare_scores_by_custom_display ($score1, $score2)
	{
		if (!isset($score1)) {
			return (isset($score2) ? 1 : 0);
		} elseif (!isset($score2)) {
			return -1;
		} else {
			$scoredisplay = ScoreDisplay :: instance();
			$custom1 = $scoredisplay->display_custom($score1);
			$custom2 = $scoredisplay->display_custom($score2);
			if ($custom1 == $custom2) {
				return 0;
			} else {
				return (($score1[0]/$score1[1]) < ($score2[0]/$score2[1]) ? -1 : 1);
			}

		}
	}
	
	private $coloring_enabled;
	private $color_split_value;
	private $custom_enabled;
	private $upperlimit_included;
	private $custom_display;
	private $custom_display_conv;

	/**
	 * Protected constructor - call instance() to instantiate
	 */
    protected function ScoreDisplay($category_id = 0) {
        if (!empty($category_id)) {
            $this->category_id = $category_id;
        }
        
        //Loading portal settings
        
        $value = api_get_setting('gradebook_score_display_coloring');
        $value = $value['my_display_coloring'];        
        $this->coloring_enabled = $value == 'true' ? true : false;    

        if ($this->coloring_enabled) {
            $value = api_get_setting('gradebook_score_display_colorsplit');
            if (isset($value)) {    		
                $this->color_split_value = $this->get_score_color_percent();
            }
        }
        
        $value = api_get_setting('gradebook_score_display_custom');
        $value = $value['my_display_custom'];   
        $this->custom_enabled  = $value;
        
        if ($this->custom_enabled) {
            //$this->custom_display = $this->get_custom_displays();        
            
            $params = array('category = ? AND subkey = ?' =>  array('Gradebook', 'ranking'));
            $displays = api_get_settings_params($params);
            $portal_displays = array();
            if (!empty($displays)) {
                foreach ($displays as $display) {
                    $data = explode('::', $display['selected_value']);
                    $portal_displays[$data[0]] = array('score' => $data[0], 'display' =>$data[1]);
                }
                sort($portal_displays);
            }            
            $this->custom_display = $portal_displays;
            
            if (count($this->custom_display)>0) {
                $value = api_get_setting('gradebook_score_display_upperlimit');
                $value = $value['my_display_upperlimit'];        
                $this->upperlimit_included  = $value == 'true' ? true : false;    
                $this->custom_display_conv = $this->convert_displays($this->custom_display);                
            }
    	}
        
        if (api_get_setting('teachers_can_change_score_settings') == 'true') {
            //Load course settings
            //
            $this->custom_display = $this->get_custom_displays();    
            if (count($this->custom_display)>0) {
                $value = api_get_setting('gradebook_score_display_upperlimit');
                $value = $value['my_display_upperlimit'];        
                $this->upperlimit_included  = $value == 'true' ? true : false;    
                $this->custom_display_conv = $this->convert_displays($this->custom_display);                
            }
            
        }
        
    	
    }
    
	/**
	 * Is coloring enabled ?
	 */
	public function is_coloring_enabled () {
		return $this->coloring_enabled;
	}
	/**
	 * Is custom score display enabled ?
	 */
	public function is_custom ()
	{
		return $this->custom_enabled;
	}
	/**
	 * Is upperlimit included ?
	 */
	public function is_upperlimit_included ()
	{
		return $this->upperlimit_included;
	}

	/**
	 * If custom score display is enabled, this will return the current settings.
	 * See also update_custom_score_display_settings
	 * @return array current settings (or null if feature not enabled)
	 */
	public function get_custom_score_display_settings() {
		return $this->custom_display;
	}

	/**
	 * If coloring is enabled, scores below this value will be displayed in red.
	 * @return int color split value, in percent (or null if feature not enabled)
	 */
	public function get_color_split_value() {
		return $this->color_split_value;
	}

    /**
     * Get current gradebook category id
     * @return int  Category id
     */
    private function get_current_gradebook_category_id() {

        $tbl_gradebook_category = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
        $curr_course_code = api_get_course_id();
        $curr_session_id = api_get_session_id();

        $session_condition = '';
        if (empty($curr_session_id)) {
            $session_condition = ' AND session_id is null ';
        } else {
            $session_condition = ' AND session_id = '.$curr_session_id;
        }

        $sql = 'SELECT id FROM '.$tbl_gradebook_category.' WHERE course_code = "'.$curr_course_code.'" '. $session_condition;
        $rs  = Database::query($sql);
        $category_id = 0;
        if (Database::num_rows($rs) > 0) {
            $row = Database::fetch_row($rs);
            $category_id = $row[0];
        }
        return $category_id;
    }

	/**
	 * Update custom score display settings
	 * @param array $displays 2-dimensional array - every subarray must have keys (score, display)
         * @param int   score color percent (optional)
         * @param int   gradebook category id (optional)
	 */
	public function update_custom_score_display_settings ($displays, $scorecolpercent = 0, $category_id = null) {
		$this->custom_display = $displays;
   		$this->custom_display_conv = $this->convert_displays($this->custom_display);

        if (isset($category_id)) {
            $category_id = intval($category_id);
        } else {
            $category_id = $this->get_current_gradebook_category_id();
        }

		// remove previous settings
        $tbl_display = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_SCORE_DISPLAY);
		$sql = 'DELETE FROM '.$tbl_display.' WHERE category_id = '.$category_id;
		Database::query($sql);

		// add new settings
		$sql = 'INSERT INTO '.$tbl_display.' (id, score, display, category_id, score_color_percent) VALUES ';
		$count = 0;
		foreach ($displays as $display) {
			if ($count > 0) {
				$sql .= ',';
			}
			$sql .= "(NULL, '".$display['score']."', '".Database::escape_string($display['display'])."', ".$category_id.", ".intval($scorecolpercent).")";
			$count++;
		}
		Database::query($sql);
	}

	/**
	 * Display a score according to the current settings
	 * @param array $score score data structure, as returned by the calc_score functions
	 * @param int 	$type one of the following constants: SCORE_DIV, SCORE_PERCENT, SCORE_DIV_PERCENT, SCORE_AVERAGE
	 * 				(ignored for student's view if custom score display is enabled)
	 * @param int 	$what one of the following constants: SCORE_BOTH, SCORE_ONLY_DEFAULT, SCORE_ONLY_CUSTOM (default: SCORE_BOTH)
	 * 				(only taken into account if custom score display is enabled and for course/platform admin)
	 */
	public function display_score($score, $type = SCORE_DIV_PERCENT, $what = SCORE_BOTH, $no_color = false) {	  
		$my_score = ($score==0) ? 1 : $score;		
		if ($this->custom_enabled && isset($this->custom_display_conv)) {		    
	        $display = $this->display_default($my_score, $type);	        
		} else {
			// if no custom display set, use default display
			$display = $this->display_default($my_score, $type);
		}		
		if ($this->coloring_enabled && $no_color == false) {
		    $my_score_denom = ($score[1]==0)?1:$score[1];		
		    if (($score[0] / $my_score_denom) < ($this->color_split_value / 100)) {
		        $display = Display::tag('font', $display, array('color'=>'red'));
		    }		    
		}
		return $display;
	}
	
    // Internal functions
	private function display_default ($score, $type) {
		switch ($type) {
			case SCORE_DIV :			                // X / Y
				return $this->display_as_div($score);
			case SCORE_PERCENT :		                // XX %
				return $this->display_as_percent($score);
			case SCORE_DIV_PERCENT :	                // X / Y (XX %)
				return $this->display_as_div($score).' (' . $this->display_as_percent($score) . ')';
			case SCORE_AVERAGE :		                // XX %
				return $this->display_as_percent($score);
			case SCORE_DECIMAL :                        // 0.50  (X/Y)
				return $this->display_as_decimal($score);				
		    case SCORE_DIV_PERCENT_WITH_CUSTOM :        // X / Y (XX %) - Good!
		        $custom = $this->display_custom($score);
		        if (!empty($custom)) {
		            $custom = ' - '.$custom;
		        }
				return $this->display_as_div($score).' (' . $this->display_as_percent($score) . ')'.$custom;
		    case SCORE_DIV_SIMPLE_WITH_CUSTOM :         // X - Good!
                $custom = $this->display_custom($score);
		        if (!empty($custom)) {
		            $custom = ' - '.$custom;
		        }
		        return $this->display_simple_score($score).$custom;		        
		    case SCORE_CUSTOM:                          // Good!
		        return $this->display_custom($score);
		}
	}
	
	private function display_simple_score($score) {
	    if (isset($score[0])) {
	        return $score[0];
	    }
	    return '';
	}
	
    /**
     * Returns "1" for array("100", "100");
     */
	private function display_as_decimal($score) {
		$score_denom = ($score[1]==0) ? 1 : $score[1];
		return round(($score[0]/ $score_denom),2);
	}
	
	/**
	 * Returns "100 %" for array("100", "100");
	 */
	private function display_as_percent($score) {        
		$score_denom=($score[1]==0) ? 1 : $score[1];        
		return round(($score[0] / $score_denom) * 100,2) . ' %';
	}
	
    /**
     * 
     * Returns 10.00 / 10.00 for array("100", "100");
     * @param array $score
     */	
    private function display_as_div($score) {
		if ($score == 1) {
			return '0/0';
		} else {
			return  $score[0] . ' / ' . $score[1];
		}
	}
    /**
     * 
     * Depends in the user selections [0 50] Bad  [50:100] Good 
     * @param array $score
     */	
	private function display_custom ($score) {
		$my_score_denom= ($score[1]==0)?1:$score[1];
		$scaledscore = $score[0] / $my_score_denom;
		if ($this->upperlimit_included) {
			foreach ($this->custom_display_conv as $displayitem) {
				if ($scaledscore <= $displayitem['score']) {
					return $displayitem['display'];
				}
			}
		} else {
			if (!empty($this->custom_display_conv)) {
				foreach ($this->custom_display_conv as $displayitem) {
					if ($scaledscore < $displayitem['score'] || $displayitem['score'] == 1) {
						return $displayitem['display'];
					}
				}
			}
		}
	}
	private function load_bool_setting ($property, $default = 0) {
		$value = $this->load_int_setting($property, $default);
		return ($value == 'true' ? true : false);
	}

	private function load_int_setting ($property, $default = 0) {
    	$tbl_setting = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
        $property    = Database::escape_string($property);
        $default     = Database::escape_string($default);
		$sql = "SELECT selected_value FROM $tbl_setting WHERE category = 'Gradebook' AND variable = '".$property."'";
		$result = Database::query($sql);

		if ($data = Database::fetch_row($result)) {
			return $data[0];
		} else {
			// if not present, add default setting into table...
			$sql = "INSERT INTO ".$tbl_setting
					." (variable, selected_value, category)"
					." VALUES ('".$property."', '".$default."','Gradebook')";
			Database::query($sql);
			// ...and return default value
			return $default;
		}
	}

    /**
     * Get score color percent by category
     * @param   int Gradebook category id
     * @return  int Score
     */
    private function get_score_color_percent($category_id = null) {

        $tbl_display = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_SCORE_DISPLAY);

        if (isset($category_id)) {
            $category_id = intval($category_id);
        } else {
            $category_id = $this->get_current_gradebook_category_id();
        }

        $sql = 'SELECT score_color_percent FROM '.$tbl_display.' WHERE category_id = '.$category_id.' LIMIT 1';
        $result = Database::query($sql);
        $score = 0;
        if (Database::num_rows($result) > 0) {
            $row = Database::fetch_row($result);
            $score = $row[0];
        } else {
            $score = $this->load_int_setting('gradebook_score_display_colorsplit',50);
        }
        return $score;
    }

	/**
	 * Get current custom score display settings
         * @param   int     Gradebook category id
	 * @return  array   2-dimensional array - every element contains 3 subelements (id, score, display)
	 */
	private function get_custom_displays($category_id = null) {
        $tbl_display = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_SCORE_DISPLAY);
        if (isset($category_id)) {
            $category_id = intval($category_id);
        } else {
            $category_id = $this->get_current_gradebook_category_id();
        }
		$sql = 'SELECT * FROM '.$tbl_display.' WHERE category_id = '.$category_id.' ORDER BY score';
		$result = Database::query($sql);
		return Database::store_result($result,'ASSOC');
	}
    
 

	/**
	 * Convert display settings to internally used values
	 */
	private function convert_displays($custom_display) {
		if (isset($custom_display)) {
			// get highest score entry, and copy each element to a new array
			$converted = array();
			$highest = 0;
			foreach ($custom_display as $element) {
				if ($element['score'] > $highest) {
					$highest = $element['score'];
				}
				$converted[] = $element;
			}
			// sort the new array (ascending)
			usort($converted, array('ScoreDisplay', 'sort_display'));

			// adjust each score in such a way that
			// each score is scaled between 0 and 1
			// the highest score in this array will be equal to 1
			$converted2 = array();
			foreach ($converted as $element) {
				$newelement = array();
				$newelement['score'] = $element['score'] / $highest;
				$newelement['display'] = $element['display'];
				$converted2[] = $newelement;
			}
			return $converted2;
		} else {
			return null;
		}
	}

	private function sort_display ($item1, $item2) {
		if ($item1['score'] == $item2['score']) {
			return 0;
		} else {
			return ($item1['score'] < $item2['score'] ? -1 : 1);
		}
	}
}
