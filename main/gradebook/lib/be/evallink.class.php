<?php
/* For licensing terms, see /license.txt */
/**
 * Class to be used as basis for links referring to Evaluation objects.
 * @author Bert Steppé
 * @package chamilo.gradebook
 */
/**
 * Class
 * @package chamilo.gradebook
 */
abstract class EvalLink extends AbstractLink
{

    protected $evaluation = null;

	/**
	 * Constructor
	 */
    function __construct() {
    	parent::__construct();
    }
    // Functions implementing AbstractLink

    public function has_results() {
    	$eval = $this->get_evaluation();
		return $eval->has_results();
    }

    public function calc_score($stud_id = null) {        
    	$eval = $this->get_evaluation();
		return $eval->calc_score($stud_id);
    }

	public function get_link() {
    	$eval = $this->get_evaluation();
		// course/platform admin can go to the view_results page
		if (api_is_allowed_to_edit())
			return 'gradebook_view_result.php?selecteval=' . $eval->get_id();
		// students can go to the statistics page (if custom display enabled)
		elseif (ScoreDisplay :: instance()->is_custom())
			return 'gradebook_statistics.php?selecteval=' . $eval->get_id();
		else
			return null;
	}

    public function get_name() {
    	$eval = $this->get_evaluation();
    	return $eval->get_name();
    }

    public function get_description() {
    	$eval = $this->get_evaluation();
    	return $eval->get_description();
    }

    public function get_max() {
    	$eval = $this->get_evaluation();
    	return $eval->get_max();
    }

    public function is_valid_link() {
    	$eval = $this->get_evaluation();
    	return (isset($eval));
    }
	public function needs_name_and_description() {
		return true;
	}

	public function needs_max() {
		return true;
	}

	public function needs_results() {
		return true;
	}


	public function add_linked_data() {
		if ($this->is_valid_link()) {
			$this->evaluation->add();
			$this->set_ref_id($this->evaluation->get_id());
		}
	}

	public function save_linked_data() {
		if ($this->is_valid_link()) {
			$this->evaluation->save();
		}
	}

	public function delete_linked_data() {
		if ($this->is_valid_link()) {
			$this->evaluation->delete_with_results();
		}
	}


	public function set_name ($name) {
		if ($this->is_valid_link()) {
			$this->evaluation->set_name($name);
		}
	}

	public function set_description ($description) {
		if ($this->is_valid_link()) {
			$this->evaluation->set_description($description);
		}
	}

	public function set_max ($max) {
		if ($this->is_valid_link()) {
			$this->evaluation->set_max($max);
		}
	}
// Functions overriding non-trivial implementations from AbstractLink
	public function set_date ($date) {
		$this->created_at = $date;
		if ($this->is_valid_link()) {
			$this->evaluation->set_date($date);
		}
	}

	public function set_weight ($weight) {
		$this->weight = $weight;
		if ($this->is_valid_link()) {
			$this->evaluation->set_weight($weight);
		}
	}

	public function set_visible ($visible) {
		$this->visible = $visible;
		if ($this->is_valid_link()) {
			$this->evaluation->set_visible($visible);
		}
	}



// INTERNAL FUNCTIONS

	/**
	 * Lazy load function to get the linked evaluation
	 */
	protected function get_evaluation () {
		if (!isset($this->evaluation)) {
			if (isset($this->ref_id)) {
		    	$evalarray = Evaluation::load($this->get_ref_id());
				$this->evaluation = $evalarray[0];
			} else {
				$eval = new Evaluation();
				$eval->set_category_id(-1);
				$eval->set_date(api_get_utc_datetime()); // these values will be changed
				$eval->set_weight(0);    //   when the link setter
				$eval->set_visible(0);   //     is called
				$eval->set_id(-1); // a 'real' id will be set when eval is added to db
				$eval->set_user_id($this->get_user_id());
				$eval->set_course_code($this->get_course_code());
				$this->evaluation = $eval;
				$this->set_ref_id($eval->get_id());

			}
		}
		return $this->evaluation;
	}
}
