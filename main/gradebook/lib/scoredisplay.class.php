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
// Score display types constants
define('SCORE_DIV',1);
define('SCORE_PERCENT',2);
define('SCORE_DIV_PERCENT',3);
define('SCORE_AVERAGE',4);
define('SCORE_IGNORE_SPLIT', 8);
define('SCORE_BOTH',1);
define('SCORE_ONLY_DEFAULT',2);
define('SCORE_ONLY_CUSTOM',3);


/**
 * Class to display scores according to the settings made by the platform admin.
 * This class works as a singleton: call instance() to retrieve an object.
 * @author Bert Stepp�
 * @package dokeos.gradebook
 */
class ScoreDisplay
{

// Singleton stuff

	/**
	 * Get the instance of this class
	 */
	public static function instance() {
		static $instance;
		if (!isset ($instance)) {
			$instance = new ScoreDisplay();	
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
// As object

	private $coloring_enabled;
	private $color_split_value;

	private $custom_enabled;
	private $upperlimit_included;
	private $custom_display;
	private $custom_display_conv;

	/**
	 * Protected constructor - call instance() to instantiate
	 */
    protected function ScoreDisplay() {
    	$this->coloring_enabled = $this->load_bool_setting('gradebook_score_display_coloring',0);
    	if ($this->coloring_enabled) {
    		$this->color_split_value = $this->load_int_setting('gradebook_score_display_colorsplit',50);    		
    	}
    	$this->custom_enabled = $this->load_bool_setting('gradebook_score_display_custom', 0);
    	if ($this->custom_enabled) {
    		$this->upperlimit_included = $this->load_bool_setting('gradebook_score_display_upperlimit', 0);
    		$this->custom_display = $this->get_custom_displays();
    		$this->custom_display_conv = $this->convert_displays($this->custom_display);
    	}
    }
	/**
	 * Is coloring enabled ?
	 */
	public function is_coloring_enabled ()
	{
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
	 * Update the 'coloring' setting
	 * @param boolean $coloring coloring enabled or disabled
	 */
	public function set_coloring_enabled ($coloring) {
		$this->coloring_enabled = $coloring;
		$this->save_bool_setting ('gradebook_score_display_coloring', $coloring);
	}

	/**
	 * Update the 'colorsplit' setting
	 * @param int $colorsplit color split value, in percent
	 */
	public function set_color_split_value ($colorsplit) {
		$this->color_split_value = $colorsplit;
		$this->save_int_setting ('gradebook_score_display_colorsplit', $colorsplit);
	}


	/**
	 * Update the 'custom' setting
	 * @param boolean $custom custom enabled or disabled
	 */
	public function set_custom ($custom) {
		$this->custom_enabled = $custom;
		$this->save_bool_setting ('gradebook_score_display_custom', $custom);
	}

	/**
	 * Update the 'upperlimit' setting
	 * @param boolean $upperlimit_included true if upper limit must be included, false otherwise
	 */
	public function set_upperlimit_included ($upperlimit_included) {
		$this->upperlimit_incl = $upperlimit_included;
		$this->save_bool_setting ('gradebook_score_display_upperlimit', $upperlimit_included);
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
	 * Update custom score display settings
	 * @param array $displays 2-dimensional array - every subarray must have keys (score, display)
	 */
	public function update_custom_score_display_settings ($displays) {
		$this->custom_display = $displays;
   		$this->custom_display_conv = $this->convert_displays($this->custom_display);
		
		// remove previous settings
    	$tbl_display = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_SCORE_DISPLAY);
		$sql = 'TRUNCATE TABLE '.$tbl_display;
		api_sql_query($sql, __FILE__, __LINE__);

		// add new settings
		$sql = 'INSERT INTO '.$tbl_display.' (id, score, display) VALUES ';
		$count = 0;
		foreach ($displays as $display) {
			if ($count > 0) {
				$sql .= ',';				
			}
			$sql .= "(NULL, '".$display['score']."', '".$display['display']."')";
			$count++;
		}
		api_sql_query($sql, __FILE__, __LINE__);
	}

	/**
	 * Display a score according to the current settings
	 * @param array $score score data structure, as returned by the calc_score functions
	 * @param int $type one of the following constants: SCORE_DIV, SCORE_PERCENT, SCORE_DIV_PERCENT, SCORE_AVERAGE
	 * (ignored for student's view if custom score display is enabled)
	 * @param int $what one of the following constants: SCORE_BOTH, SCORE_ONLY_DEFAULT, SCORE_ONLY_CUSTOM (default: SCORE_BOTH)
	 * (only taken into account if custom score display is enabled and for course/platform admin)
	 */
	public function display_score($score,$type,$what = SCORE_BOTH) {
		$type2 = $type & 7;	// removes the 'SCORE_IGNORE_SPLIT' bit
		$split_enabled = ($type2 == $type);
		$my_score=($score==0) ? 1 : $score;
		if ($this->custom_enabled && isset($this->custom_display_conv)) {
				// students only see the custom display
				if (!api_is_allowed_to_create_course()) {
					$display = $this->display_custom($my_score);				
				}
				// course/platform admins
				elseif ($what == SCORE_ONLY_DEFAULT) {
					$display = $this->display_default ($my_score, $type2);				
				}
				elseif ($what == SCORE_ONLY_CUSTOM) {
					$display = $this->display_custom ($my_score);				
				} else {
					$display = $this->display_default ($my_score, $type2);
					if ($this->display_custom ($my_score)!='')
						$display.= ' ('.$this->display_custom ($my_score).')';				
			}

		} else {
		// if no custom display set, use default display
			$display = $this->display_default ($my_score, $type2);		
		}
		return (($split_enabled ? $this->get_color_display_start_tag($my_score) : '')
				. $display
				. ($split_enabled ? $this->get_color_display_end_tag($my_score) : ''));
	}
// Internal functions

	private function display_default ($score, $type) {
		switch ($type) {
			case SCORE_DIV :			// X / Y
				return $this->display_as_div($score);

			case SCORE_PERCENT :		// XX %
				return $this->display_as_percent($score);

			case SCORE_DIV_PERCENT :	// X / Y (XX %)
				return $this->display_as_div($score)
							. ' (' . $this->display_as_percent($score) . ')';

			case SCORE_AVERAGE :		// XX %
				return $this->display_as_percent($score);
		}
	}

	private function display_as_percent ($score) {
		$score_denom=($score[1]==0) ? 1 : $score[1];
		return round(($score[0] / $score_denom) * 100,2) . ' %';
	}

	private function display_as_div ($score) {
		if ($score==1) {
			return '0/0';
		} else {
			return  $score[0] . ' / ' . $score[1];	
		}
		
	}

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
			foreach ($this->custom_display_conv as $displayitem) {
				if ($scaledscore < $displayitem['score'] || $displayitem['score'] == 1) {
					return $displayitem['display'];
				}
			}
		}
	}
	private function load_bool_setting ($property, $default = 0) {
		$value = $this->load_int_setting($property, $default);
		return ($value == 'true' ? true : false);
	}

	private function load_int_setting ($property, $default = 0) {
    	$tbl_setting = Database :: get_main_table(TABLE_MAIN_SETTINGS_CURRENT);

		$sql = "SELECT selected_value FROM ".$tbl_setting
				." WHERE category = 'Gradebook' AND variable = '".$property."'";
		$result = api_sql_query($sql, __FILE__, __LINE__);

		if ($data = Database::fetch_row($result)) {
			return $data[0];
		}
		else {
			// if not present, add default setting into table...
			$sql = "INSERT INTO ".$tbl_setting
					." (variable, selected_value, category)"
					." VALUES ('".$property."', '".$default."','Gradebook')";
			api_sql_query($sql, __FILE__, __LINE__);
			// ...and return default value
			return $default;
		}
	}

	
	private function save_bool_setting ($property, $value) {
		$this->save_int_setting ($property, ($value ? 'true' : 'false') );
	}

	private function save_int_setting ($property, $value) {
    	$tbl_setting = Database :: get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
		$sql = 'UPDATE '.$tbl_setting
				." SET selected_value = '".$value."' "
				." WHERE variable = '".$property."' AND category='Gradebook'";
		api_sql_query($sql, __FILE__, __LINE__);
	}

	
	/**
	 * Get current custom score display settings
	 * @return array 2-dimensional array - every element contains 3 subelements (id, score, display)
	 */
	private function get_custom_displays() {
    	$tbl_display = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_SCORE_DISPLAY);
		$sql = 'SELECT * FROM '.$tbl_display.' ORDER BY score';
		//echo $sql;
		$result = api_sql_query($sql, __FILE__, __LINE__);
		return api_store_result($result);
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

	private function get_color_display_start_tag($score) {
		$my_score_denom=($score[1]==0)?1:$score[1];
		if ($this->coloring_enabled && ($score[0]/$my_score_denom) < ($this->color_split_value / 100)) {
			return '<font color="red">';
		} else {
			return '';	
		}
	}

	private function get_color_display_end_tag($score) {
		$my_score_denom=($score[1]==0)?1:$score[1];
		if ($this->coloring_enabled && ($score[0]/$my_score_denom) < ($this->color_split_value / 100)) {
			return '</font>';
		} else {
			return '';
		}
	}
}