<?php
/* For licensing terms, see /license.txt */

/**
 * Class ForumThreadLink.
 *
 * @author Bert Steppé
 */
class ForumThreadLink extends AbstractLink
{
    private $forum_thread_table;
    private $itemprop_table;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->set_type(LINK_FORUM_THREAD);
    }

    /**
     * @return string
     */
    public function get_type_name()
    {
        return get_lang('ForumThreads');
    }

    /**
     * @return bool
     */
    public function is_allowed_to_change_name()
    {
        return false;
    }

    /**
     * Generate an array of all exercises available.
     *
     * @return array 2-dimensional array - every element contains 2 subelements (id, name)
     */
    public function get_all_links()
    {
        if (empty($this->course_code)) {
            return [];
        }

        $tbl_grade_links = Database::get_course_table(TABLE_FORUM_THREAD);
        $tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $sessionId = $this->get_session_id();

        if ($sessionId) {
            $session_condition = 'tl.session_id='.$sessionId;
        } else {
            $session_condition = '(tl.session_id = 0 OR tl.session_id IS NULL)';
        }

        $sql = 'SELECT tl.thread_id, tl.thread_title, tl.thread_title_qualify
                FROM '.$tbl_grade_links.' tl INNER JOIN '.$tbl_item_property.' ip
                ON (tl.thread_id = ip.ref AND tl.c_id = ip.c_id)
                WHERE
                    tl.c_id = '.$this->course_id.' AND
                    ip.c_id = '.$this->course_id.' AND
                    ip.tool = "forum_thread" AND
                    ip.visibility <> 2 AND
                    '.$session_condition.'
                ';

        $result = Database::query($sql);
        while ($data = Database::fetch_array($result)) {
            if (isset($data['thread_title_qualify']) && '' != $data['thread_title_qualify']) {
                $cats[] = [$data['thread_id'], $data['thread_title_qualify']];
            } else {
                $cats[] = [$data['thread_id'], $data['thread_title']];
            }
        }
        $my_cats = isset($cats) ? $cats : [];

        return $my_cats;
    }

    /**
     * Has anyone done this exercise yet ?
     *
     * @return bool
     */
    public function has_results()
    {
        $table = Database::get_course_table(TABLE_FORUM_POST);

        $sql = "SELECT count(*) AS number FROM $table
                WHERE
                    c_id = ".$this->course_id." AND
                    thread_id = '".$this->get_ref_id()."'                    
                ";
        $result = Database::query($sql);
        $number = Database::fetch_row($result);

        return 0 != $number[0];
    }

    /**
     * @param int    $stud_id
     * @param string $type
     *
     * @return array|null
     */
    public function calc_score($stud_id = null, $type = null)
    {
        require_once api_get_path(SYS_CODE_PATH).'forum/forumfunction.inc.php';
        $threadInfo = get_thread_information('', $this->get_ref_id());
        $thread_qualify = Database::get_course_table(TABLE_FORUM_THREAD_QUALIFY);
        $sessionId = $this->get_session_id();
        $sessionCondition = api_get_session_condition(
            $sessionId,
            true,
            false,
            'session_id'
        );

        $sql = 'SELECT thread_qualify_max
                FROM '.Database::get_course_table(TABLE_FORUM_THREAD)."
                WHERE 
                    c_id = ".$this->course_id." AND 
                    thread_id = '".$this->get_ref_id()."'
                    $sessionCondition
                ";
        $query = Database::query($sql);
        $assignment = Database::fetch_array($query);

        $sql = "SELECT * FROM $thread_qualify
                WHERE 
                    c_id = ".$this->course_id." AND 
                    thread_id = ".$this->get_ref_id()."
                    $sessionCondition
                ";
        if (isset($stud_id)) {
            $sql .= ' AND user_id = '.intval($stud_id);
        }

        // order by id, that way the student's first attempt is accessed first
        $sql .= ' ORDER BY qualify_time DESC';
        $scores = Database::query($sql);

        // for 1 student
        if (isset($stud_id)) {
            if (0 == $threadInfo['thread_peer_qualify']) {
                // Classic way of calculate score
                if ($data = Database::fetch_array($scores)) {
                    return [
                        $data['qualify'],
                        $assignment['thread_qualify_max'],
                    ];
                } else {
                    // We sent the 0/thread_qualify_max instead of null for correct calculations
                    return [0, $assignment['thread_qualify_max']];
                }
            } else {
                // Take average
                $score = 0;
                $counter = 0;
                if (Database::num_rows($scores)) {
                    while ($data = Database::fetch_array($scores, 'ASSOC')) {
                        $score += $data['qualify'];
                        $counter++;
                    }
                }
                // If no result
                if (empty($counter) || $counter <= 2) {
                    return [0, $assignment['thread_qualify_max']];
                }

                return [$score / $counter, $assignment['thread_qualify_max']];
            }
        } else {
            // All students -> get average
            $students = []; // user list, needed to make sure we only
            // take first attempts into account
            $counter = 0;
            $sum = 0;
            $bestResult = 0;
            $weight = 0;
            $sumResult = 0;

            while ($data = Database::fetch_array($scores)) {
                if (!(array_key_exists($data['user_id'], $students))) {
                    if (0 != $assignment['thread_qualify_max']) {
                        $students[$data['user_id']] = $data['qualify'];
                        $counter++;
                        $sum += $data['qualify'] / $assignment['thread_qualify_max'];
                        $sumResult += $data['qualify'];
                        if ($data['qualify'] > $bestResult) {
                            $bestResult = $data['qualify'];
                        }
                        $weight = $assignment['thread_qualify_max'];
                    }
                }
            }

            if (0 == $counter) {
                return [null, null];
            } else {
                switch ($type) {
                    case 'best':
                        return [$bestResult, $weight];
                        break;
                    case 'average':
                        return [$sumResult / $counter, $weight];
                        break;
                    case 'ranking':
                        return AbstractLink::getCurrentUserRanking($stud_id, $students);
                        break;
                    default:
                        return [$sum, $counter];
                        break;
                }
            }
        }
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

    /**
     * @return string
     */
    public function get_name()
    {
        $this->get_exercise_data();
        $thread_title = isset($this->exercise_data['thread_title']) ? $this->exercise_data['thread_title'] : '';
        $thread_title_qualify = isset($this->exercise_data['thread_title_qualify']) ? $this->exercise_data['thread_title_qualify'] : '';
        if (isset($thread_title_qualify) && '' != $thread_title_qualify) {
            return $this->exercise_data['thread_title_qualify'];
        }

        return $thread_title;
    }

    /**
     * @return string
     */
    public function get_description()
    {
        return ''; //$this->exercise_data['description'];
    }

    /**
     * Check if this still links to an exercise.
     */
    public function is_valid_link()
    {
        $sessionId = $this->get_session_id();
        $sql = 'SELECT count(id) from '.$this->get_forum_thread_table().'
                WHERE 
                    c_id = '.$this->course_id.' AND 
                    thread_id = '.$this->get_ref_id().' AND 
                    session_id='.$sessionId;
        $result = Database::query($sql);
        $number = Database::fetch_row($result);

        return 0 != $number[0];
    }

    public function get_link()
    {
        $sessionId = $this->get_session_id();
        //it was extracts the forum id
        $sql = 'SELECT * FROM '.$this->get_forum_thread_table()."
                WHERE
                    c_id = '.$this->course_id.' AND 
                    thread_id = '".$this->get_ref_id()."' AND 
                    session_id = $sessionId ";
        $result = Database::query($sql);
        $row = Database::fetch_array($result, 'ASSOC');
        $forum_id = $row['forum_id'];

        $url = api_get_path(WEB_PATH).'main/forum/viewthread.php?'.api_get_cidreq_params($this->get_course_code(), $sessionId).'&thread='.$this->get_ref_id().'&gradebook=view&forum='.$forum_id;

        return $url;
    }

    public function get_icon_name()
    {
        return 'forum';
    }

    public function save_linked_data()
    {
        $weight = $this->get_weight();
        $ref_id = $this->get_ref_id();

        if (!empty($ref_id)) {
            $sql = 'UPDATE '.$this->get_forum_thread_table().' SET 
                    thread_weight='.api_float_val($weight).'
                    WHERE c_id = '.$this->course_id.' AND thread_id= '.$ref_id;
            Database::query($sql);
        }
    }

    public function delete_linked_data()
    {
        $ref_id = $this->get_ref_id();
        if (!empty($ref_id)) {
            // Cleans forum
            $sql = 'UPDATE '.$this->get_forum_thread_table().' SET
                    thread_qualify_max = 0,
                    thread_weight = 0,
                    thread_title_qualify = ""
                    WHERE c_id = '.$this->course_id.' AND thread_id= '.$ref_id;
            Database::query($sql);
        }
    }

    /**
     * Lazy load function to get the database table of the student publications.
     */
    private function get_forum_thread_table()
    {
        return $this->forum_thread_table = Database::get_course_table(TABLE_FORUM_THREAD);
    }

    private function get_exercise_data()
    {
        $sessionId = $this->get_session_id();
        if ($sessionId) {
            $session_condition = 'session_id = '.$sessionId;
        } else {
            $session_condition = '(session_id = 0 OR session_id IS NULL)';
        }

        if (!isset($this->exercise_data)) {
            $sql = 'SELECT * FROM '.$this->get_forum_thread_table().'
                    WHERE 
                        c_id = '.$this->course_id.' AND  
                        thread_id = '.$this->get_ref_id().' AND 
                        '.$session_condition;
            $query = Database::query($sql);
            $this->exercise_data = Database::fetch_array($query);
        }

        return $this->exercise_data;
    }
}
