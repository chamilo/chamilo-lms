<?php
/* For licensing terms, see /license.txt */

/**
 * Defines a gradebook LearnpathLink object.
 *
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 * @author Bert Steppé
 */
class LearnpathLink extends AbstractLink
{
    private $course_info;
    private $learnpath_table;
    private $learnpath_data;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->set_type(LINK_LEARNPATH);
    }

    /**
     * Generate an array of all learnpaths available.
     *
     * @return array 2-dimensional array - every element contains 2 subelements (id, name)
     */
    public function get_all_links()
    {
        if (empty($this->course_code)) {
            return [];
        }

        $session_id = $this->get_session_id();
        if (empty($session_id)) {
            $session_condition = api_get_session_condition(0, true);
        } else {
            $session_condition = api_get_session_condition($session_id, true, true);
        }

        $sql = 'SELECT id, name FROM '.$this->get_learnpath_table().'
                WHERE c_id = '.$this->course_id.' '.$session_condition.' ';
        $result = Database::query($sql);

        $cats = [];
        while ($data = Database::fetch_array($result)) {
            $cats[] = [$data['id'], $data['name']];
        }

        return $cats;
    }

    /**
     * Has anyone used this learnpath yet ?
     */
    public function has_results()
    {
        $tbl_stats = Database::get_course_table(TABLE_LP_VIEW);
        $sql = "SELECT count(id) AS number FROM $tbl_stats
				WHERE c_id = ".$this->course_id." AND lp_id = ".$this->get_ref_id();
        $result = Database::query($sql);
        $number = Database::fetch_array($result, 'NUM');

        return 0 != $number[0];
    }

    /**
     * Get the progress of this learnpath. Only the last attempt are taken into account.
     *
     * @param $stud_id student id (default: all students who have results - then the average is returned)
     * @param $type The type of score we want to get: best|average|ranking
     *
     * @return array (score, max) if student is given
     *               array (sum of scores, number of scores) otherwise
     *               or null if no scores available
     */
    public function calc_score($stud_id = null, $type = null)
    {
        $tbl_stats = Database::get_course_table(TABLE_LP_VIEW);
        $session_id = $this->get_session_id();
        if (empty($session_id)) {
            $session_id = api_get_session_id();
        }

        $sql = "SELECT * FROM $tbl_stats
                WHERE
                	c_id = ".$this->course_id." AND
                    lp_id = ".$this->get_ref_id()." AND
                    session_id = $session_id ";

        if (isset($stud_id)) {
            $sql .= ' AND user_id = '.intval($stud_id);
        }

        // order by id, that way the student's first attempt is accessed first
        $sql .= ' ORDER BY view_count DESC';

        $scores = Database::query($sql);
        // for 1 student
        if (isset($stud_id)) {
            if ($data = Database::fetch_assoc($scores)) {
                return [$data['progress'], 100];
            } else {
                return null;
            }
        } else {
            // all students -> get average
            $students = []; // user list, needed to make sure we only
            // take first attempts into account
            $rescount = 0;
            $sum = 0;
            $bestResult = 0;
            $sumResult = 0;
            while ($data = Database::fetch_array($scores)) {
                if (!(array_key_exists($data['user_id'], $students))) {
                    $students[$data['user_id']] = $data['progress'];
                    $rescount++;
                    $sum += $data['progress'] / 100;
                    $sumResult += $data['progress'];

                    if ($data['progress'] > $bestResult) {
                        $bestResult = $data['progress'];
                    }
                }
            }

            if (0 == $rescount) {
                return [null, null];
            } else {
                switch ($type) {
                    case 'best':
                        return [$bestResult, 100];
                        break;
                    case 'average':
                        return [$sumResult / $rescount, 100];
                        break;
                    case 'ranking':
                        return AbstractLink::getCurrentUserRanking($stud_id, $students);
                        break;
                    default:
                        return [$sum, $rescount];
                        break;
                }
            }
        }
    }

    /**
     * Get URL where to go to if the user clicks on the link.
     */
    public function get_link()
    {
        $session_id = $this->get_session_id();
        $url = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.api_get_cidreq_params(
            $this->get_course_code(),
                $session_id
        ).'&gradebook=view';

        if (!api_is_allowed_to_edit() || null == $this->calc_score(api_get_user_id())) {
            $url .= '&action=view&lp_id='.$this->get_ref_id();
        } else {
            $url .= '&action=build&lp_id='.$this->get_ref_id();
        }

        return $url;
    }

    /**
     * Get name to display: same as learnpath title.
     */
    public function get_name()
    {
        $data = $this->get_learnpath_data();

        return $data['name'];
    }

    /**
     * Get description to display: same as learnpath description.
     */
    public function get_description()
    {
        $data = $this->get_learnpath_data();

        return $data['description'];
    }

    /**
     * Check if this still links to a learnpath.
     */
    public function is_valid_link()
    {
        $sql = 'SELECT count(id) FROM '.$this->get_learnpath_table().'
                WHERE c_id = '.$this->course_id.' AND id = '.$this->get_ref_id().' ';
        $result = Database::query($sql);
        $number = Database::fetch_row($result, 'NUM');

        return 0 != $number[0];
    }

    public function get_type_name()
    {
        return get_lang('LearningPaths');
    }

    public function needs_name_and_description()
    {
        return false;
    }

    public function needs_max()
    {
        return false;
    }

    public function needs_results()
    {
        return false;
    }

    public function is_allowed_to_change_name()
    {
        return false;
    }

    public function get_icon_name()
    {
        return 'learnpath';
    }

    /**
     * Lazy load function to get the database table of the learnpath.
     */
    private function get_learnpath_table()
    {
        $this->learnpath_table = Database::get_course_table(TABLE_LP_MAIN);

        return $this->learnpath_table;
    }

    /**
     * Lazy load function to get the database contents of this learnpath.
     */
    private function get_learnpath_data()
    {
        if (!isset($this->learnpath_data)) {
            $sql = 'SELECT * FROM '.$this->get_learnpath_table().'
                    WHERE c_id = '.$this->course_id.' AND id = '.$this->get_ref_id().' ';
            $result = Database::query($sql);
            $this->learnpath_data = Database::fetch_array($result);
        }

        return $this->learnpath_data;
    }
}
