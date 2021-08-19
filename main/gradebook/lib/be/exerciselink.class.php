<?php

/* For licensing terms, see /license.txt */

/**
 * Class ExerciseLink
 * Defines a gradebook ExerciseLink object.
 *
 * @author Bert Steppé
 */
class ExerciseLink extends AbstractLink
{
    // This variable is used in the WSGetGradebookUserItemScore service, to check base course tests.
    public $checkBaseExercises = false;
    private $course_info;
    private $exercise_table;
    private $exercise_data = [];
    private $is_hp;

    /**
     * @param int $hp
     */
    public function __construct($hp = 0)
    {
        parent::__construct();
        $this->set_type(LINK_EXERCISE);
        $this->is_hp = $hp;
        if (1 == $this->is_hp) {
            $this->set_type(LINK_HOTPOTATOES);
        }
    }

    /**
     * Generate an array of all exercises available.
     *
     * @param bool $getOnlyHotPotatoes
     *
     * @return array 2-dimensional array - every element contains 2 subelements (id, name)
     */
    public function get_all_links($getOnlyHotPotatoes = false)
    {
        $TBL_DOCUMENT = Database::get_course_table(TABLE_DOCUMENT);
        $tableItemProperty = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $exerciseTable = $this->get_exercise_table();
        $lpItemTable = Database::get_course_table(TABLE_LP_ITEM);
        $lpTable = Database::get_course_table(TABLE_LP_MAIN);

        $documentPath = api_get_path(SYS_COURSE_PATH).$this->course_code.'/document';
        if (empty($this->course_code)) {
            return [];
        }
        $sessionId = $this->get_session_id();
        if (empty($sessionId)) {
            $session_condition = api_get_session_condition(0, true, false, 'e.session_id');
        } else {
            $session_condition = api_get_session_condition($sessionId, true, true, 'e.session_id');
        }

        // @todo
        $uploadPath = null;
        $courseId = $this->course_id;

        $sql = "SELECT iid, title FROM $exerciseTable e
				WHERE
				    c_id = $courseId AND
				    active = 1
				    $session_condition ";

        $sqlLp = "SELECT e.iid, e.title, lp.name lp_name
                  FROM $exerciseTable e
                  INNER JOIN $lpItemTable i
                  ON (e.c_id = i.c_id AND e.iid = i.path)
                  INNER JOIN $lpTable lp
                  ON (lp.c_id = e.c_id AND lp.id = i.lp_id)
				  WHERE
				    e.c_id = $courseId AND
				    active = 0 AND
				    item_type = 'quiz'
				    $session_condition";

        $sql2 = "SELECT d.path as path, d.comment as comment, ip.visibility as visibility, d.id
                FROM $TBL_DOCUMENT d
                INNER JOIN $tableItemProperty ip
                ON (d.id = ip.ref AND d.c_id = ip.c_id)
                WHERE
                    d.c_id = $courseId AND
                    ip.c_id = $courseId AND
                    ip.tool = '".TOOL_DOCUMENT."' AND
                    (d.path LIKE '%htm%') AND
                    (d.path LIKE '%HotPotatoes_files%') AND
                    d.path  LIKE '".Database::escape_string($uploadPath.'/%/%')."' AND
                    ip.visibility = '1'
                ";

        require_once api_get_path(SYS_CODE_PATH).'exercise/hotpotatoes.lib.php';

        $exerciseInLP = [];
        if (!$this->is_hp) {
            $result = Database::query($sql);
            $resultLp = Database::query($sqlLp);
            $exerciseInLP = Database::store_result($resultLp);
        } else {
            $result2 = Database::query($sql2);
        }

        $cats = [];
        $exerciseList = [];
        if (isset($result)) {
            if (Database::num_rows($result) > 0) {
                while ($data = Database::fetch_array($result)) {
                    $cats[] = [$data['iid'], $data['title']];
                    $exerciseList[] = $data;
                }
            }
        }
        $hotPotatoes = [];
        if (isset($result2)) {
            if (Database::num_rows($result2) > 0) {
                while ($row = Database::fetch_array($result2)) {
                    $attribute['path'][] = $row['path'];
                    $attribute['visibility'][] = $row['visibility'];
                    $attribute['comment'][] = $row['comment'];
                    $attribute['id'] = $row['id'];

                    if (isset($attribute['path']) && is_array($attribute['path'])) {
                        foreach ($attribute['path'] as $path) {
                            $title = GetQuizName($path, $documentPath);
                            if ($title == '') {
                                $title = basename($path);
                            }
                            $element = [$attribute['id'], $title.'(HP)'];
                            $cats[] = $element;
                            $hotPotatoes[] = $element;
                        }
                    }
                }
            }
        }

        if ($getOnlyHotPotatoes) {
            return $hotPotatoes;
        }

        if (!empty($exerciseInLP)) {
            $allExercises = array_column($exerciseList, 'iid');

            foreach ($exerciseInLP as $exercise) {
                if (in_array($exercise['iid'], $allExercises)) {
                    continue;
                }
                $allExercises[] = $exercise['iid'];
                //$lpName = strip_tags($exercise['lp_name']);
                /*$cats[] = [
                    $exercise['iid'],
                    strip_tags(Exercise::get_formated_title_variable($exercise['title'])).
                    ' ('.get_lang('ToolLearnpath').' - '.$lpName.')',
                ];*/
                $cats[] = [
                    $exercise['iid'],
                    strip_tags(Exercise::get_formated_title_variable($exercise['title'])),
                ];
            }
        }

        return $cats;
    }

    /**
     * Has anyone done this exercise yet ?
     */
    public function has_results()
    {
        $tbl_stats = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $sessionId = $this->get_session_id();
        $course_id = api_get_course_int_id($this->get_course_code());
        $sql = "SELECT count(exe_id) AS number
                FROM $tbl_stats
                WHERE
                    session_id = $sessionId AND
                    c_id = $course_id AND
                    exe_exo_id = ".$this->get_ref_id();
        $result = Database::query($sql);
        $number = Database::fetch_row($result);

        return $number[0] != 0;
    }

    /**
     * Get the score of this exercise. Only the first attempts are taken into account.
     *
     * @param int    $stud_id student id (default: all students who have results -
     *                        then the average is returned)
     * @param string $type
     *
     * @return array (score, max) if student is given
     *               array (sum of scores, number of scores) otherwise
     *               or null if no scores available
     */
    public function calc_score($stud_id = null, $type = null)
    {
        $allowStats = api_get_configuration_value('allow_gradebook_stats');
        if ($allowStats) {
            $link = $this->entity;
            if (!empty($link)) {
                $weight = $link->getScoreWeight();

                switch ($type) {
                    case 'best':
                        $bestResult = $link->getBestScore();

                        return [$bestResult, $weight];

                        break;
                    case 'average':
                        $count = count($link->getUserScoreList());
                        //$count = count($this->getStudentList());
                        if (empty($count)) {
                            return [0, $weight];
                        }
                        $sumResult = array_sum($link->getUserScoreList());

                        return [$sumResult / $count, $weight];

                        break;
                    case 'ranking':
                        return [null, null];
                        break;
                    default:
                        if (!empty($stud_id)) {
                            $scoreList = $link->getUserScoreList();
                            $result = [0, $weight];
                            if (isset($scoreList[$stud_id])) {
                                $result = [$scoreList[$stud_id], $weight];
                            }

                            return $result;
                        } else {
                            $studentCount = count($this->getStudentList());
                            $sumResult = array_sum($link->getUserScoreList());
                            $result = [$sumResult, $studentCount];
                        }

                        return $result;
                        break;
                }
            }
        }

        $tblStats = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $tblHp = Database::get_main_table(TABLE_STATISTIC_TRACK_E_HOTPOTATOES);
        $tblDoc = Database::get_course_table(TABLE_DOCUMENT);

        /* the following query should be similar (in conditions) to the one used
        in exercise/exercise.php, look for note-query-exe-results marker*/
        $sessionId = $this->get_session_id();
        $courseId = $this->getCourseId();
        $exerciseData = $this->get_exercise_data();
        $exerciseId = isset($exerciseData['iid']) ? (int) $exerciseData['iid'] : 0;
        $stud_id = (int) $stud_id;

        if (empty($exerciseId)) {
            return null;
        }

        $key = 'exercise_link_id:'.
            $this->get_id().
            'exerciseId:'.$exerciseId.'student:'.$stud_id.'session:'.$sessionId.'courseId:'.$courseId.'type:'.$type;

        $useCache = api_get_configuration_value('gradebook_use_apcu_cache');
        $cacheAvailable = api_get_configuration_value('apc') && $useCache;
        $cacheDriver = null;
        if ($cacheAvailable) {
            $cacheDriver = new \Doctrine\Common\Cache\ApcuCache();
            if ($cacheDriver->contains($key)) {
                return $cacheDriver->fetch($key);
            }
        }

        $exercise = new Exercise($courseId);
        $exercise->read($exerciseId);

        if (!$this->is_hp) {
            if (false == $exercise->exercise_was_added_in_lp) {
                $sql = "SELECT * FROM $tblStats
                        WHERE
                            exe_exo_id = $exerciseId AND
                            orig_lp_id = 0 AND
                            orig_lp_item_id = 0 AND
                            status <> 'incomplete' AND
                            session_id = $sessionId AND
                            c_id = $courseId
                        ";
            } else {
                $lpCondition = null;
                if (!empty($exercise->lpList)) {
                    //$lpId = $exercise->getLpBySession($sessionId);
                    $lpList = [];
                    foreach ($exercise->lpList as $lpData) {
                        if ($this->checkBaseExercises) {
                            if ((int) $lpData['session_id'] == 0) {
                                $lpList[] = $lpData['lp_id'];
                            }
                        } else {
                            if ((int) $lpData['session_id'] == $sessionId) {
                                $lpList[] = $lpData['lp_id'];
                            }
                        }
                    }

                    if (empty($lpList) && !empty($sessionId)) {
                        // Check also if an LP was added in the base course.
                        foreach ($exercise->lpList as $lpData) {
                            if ((int) $lpData['session_id'] == 0) {
                                $lpList[] = $lpData['lp_id'];
                            }
                        }
                    }

                    $lpCondition = ' (orig_lp_id = 0 OR (orig_lp_id IN ("'.implode('", "', $lpList).'"))) AND ';
                }

                $sql = "SELECT *
                        FROM $tblStats
                        WHERE
                            exe_exo_id = $exerciseId AND
                            $lpCondition
                            status <> 'incomplete' AND
                            session_id = $sessionId AND
                            c_id = $courseId ";
            }

            if (!empty($stud_id) && 'ranking' !== $type) {
                $sql .= " AND exe_user_id = $stud_id ";
            }
            $sql .= ' ORDER BY exe_id DESC ';
        } else {
            $sql = "SELECT * FROM $tblHp hp
                    INNER JOIN $tblDoc doc
                    ON (hp.exe_name = doc.path AND doc.c_id = hp.c_id)
                    WHERE
                        hp.c_id = $courseId AND
                        doc.id = $exerciseId";

            if (!empty($stud_id)) {
                $sql .= " AND hp.exe_user_id = $stud_id ";
            }
        }

        $scores = Database::query($sql);

        if (isset($stud_id) && empty($type)) {
            // for 1 student
            if ($data = Database::fetch_array($scores, 'ASSOC')) {
                $attempts = Database::query($sql);
                $counter = 0;
                while ($attempt = Database::fetch_array($attempts)) {
                    $counter++;
                }
                $result = [$data['exe_result'], $data['exe_weighting'], $data['exe_date'], $counter];
                if ($cacheAvailable) {
                    $cacheDriver->save($key, $result);
                }

                return $result;
            } else {
                if ($cacheAvailable) {
                    $cacheDriver->save($key, null);
                }

                return null;
            }
        } else {
            // all students -> get average
            // normal way of getting the info
            $students = []; // user list, needed to make sure we only
            // take first attempts into account
            $student_count = 0;
            $sum = 0;
            $bestResult = 0;
            $weight = 0;
            $sumResult = 0;
            $studentList = $this->getStudentList();
            $studentIdList = [];
            if (!empty($studentList)) {
                $studentIdList = array_column($studentList, 'user_id');
            }

            while ($data = Database::fetch_array($scores, 'ASSOC')) {
                // Only take into account users in the current student list.
                if (!empty($studentIdList)) {
                    if (!in_array($data['exe_user_id'], $studentIdList)) {
                        continue;
                    }
                }

                if (!isset($students[$data['exe_user_id']])) {
                    if ($data['exe_weighting'] != 0) {
                        $students[$data['exe_user_id']] = $data['exe_result'];
                        $student_count++;
                        if ($data['exe_result'] > $bestResult) {
                            $bestResult = $data['exe_result'];
                        }
                        $sum += $data['exe_result'] / $data['exe_weighting'];
                        $sumResult += $data['exe_result'];
                        $weight = $data['exe_weighting'];
                    }
                }
            }

            if ($student_count == 0) {
                if ($cacheAvailable) {
                    $cacheDriver->save($key, null);
                }

                return null;
            } else {
                switch ($type) {
                    case 'best':
                        $result = [$bestResult, $weight];
                        if ($cacheAvailable) {
                            $cacheDriver->save($key, $result);
                        }

                        return $result;
                        break;
                    case 'average':
                        $count = count($this->getStudentList());
                        if (empty($count)) {
                            $result = [0, $weight];
                            if ($cacheAvailable) {
                                $cacheDriver->save($key, $result);
                            }

                            return $result;
                        }

                        $result = [$sumResult / $count, $weight];

                        if ($cacheAvailable) {
                            $cacheDriver->save($key, $result);
                        }

                        return $result;
                        break;
                    case 'ranking':
                        $ranking = AbstractLink::getCurrentUserRanking($stud_id, $students);
                        if ($cacheAvailable) {
                            $cacheDriver->save($key, $ranking);
                        }

                        return $ranking;
                        break;
                    default:
                        $result = [$sum, $student_count];
                        if ($cacheAvailable) {
                            $cacheDriver->save($key, $result);
                        }

                        return $result;
                        break;
                }
            }
        }
    }

    /**
     * Get URL where to go to if the user clicks on the link.
     * First we go to exercise_jump.php and then to the result page.
     * Check this php file for more info.
     */
    public function get_link()
    {
        $sessionId = $this->get_session_id();
        $data = $this->get_exercise_data();
        $exerciseId = $data['iid'];
        $path = isset($data['path']) ? $data['path'] : '';

        return api_get_path(WEB_CODE_PATH).'gradebook/exercise_jump.php?'
            .http_build_query(
                [
                    'path' => $path,
                    'session_id' => $sessionId,
                    'cidReq' => $this->get_course_code(),
                    'gradebook' => 'view',
                    'exerciseId' => $exerciseId,
                    'type' => $this->get_type(),
                ]
            );
    }

    /**
     * Get name to display: same as exercise title.
     */
    public function get_name()
    {
        $documentPath = api_get_path(SYS_COURSE_PATH).$this->course_code.'/document';
        require_once api_get_path(SYS_CODE_PATH).'exercise/hotpotatoes.lib.php';
        $data = $this->get_exercise_data();

        if ($this->is_hp == 1) {
            if (isset($data['path'])) {
                $title = GetQuizName($data['path'], $documentPath);
                if ($title == '') {
                    $title = basename($data['path']);
                }

                return $title;
            }
        }

        return strip_tags(Exercise::get_formated_title_variable($data['title']));
    }

    public function getLpListToString()
    {
        $data = $this->get_exercise_data();
        $lpList = Exercise::getLpListFromExercise($data['iid'], $this->getCourseId());
        $lpListToString = '';
        if (!empty($lpList)) {
            foreach ($lpList as &$list) {
                $list['name'] = Display::label($list['name'], 'warning');
            }
            $lpListToString = implode('&nbsp;', array_column($lpList, 'name'));
        }

        return $lpListToString;
    }

    /**
     * Get description to display: same as exercise description.
     */
    public function get_description()
    {
        $data = $this->get_exercise_data();

        return isset($data['description']) ? $data['description'] : null;
    }

    /**
     * Check if this still links to an exercise.
     */
    public function is_valid_link()
    {
        $exerciseData = $this->get_exercise_data();

        return !empty($exerciseData);
    }

    /**
     * @return string
     */
    public function get_type_name()
    {
        if ($this->is_hp == 1) {
            return 'HotPotatoes';
        }

        return get_lang('Quiz');
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

    /**
     * @return string
     */
    public function get_icon_name()
    {
        return 'exercise';
    }

    /**
     * @param bool $hp
     */
    public function setHp($hp)
    {
        $this->hp = $hp;
    }

    public function getBestScore()
    {
        return $this->getStats('best');
    }

    public function getStats($type)
    {
        switch ($type) {
            case 'best':
                break;
        }
    }

    /**
     * Lazy load function to get the database contents of this exercise.
     */
    public function get_exercise_data()
    {
        $tableItemProperty = Database::get_course_table(TABLE_ITEM_PROPERTY);
        if ($this->is_hp == 1) {
            $table = Database::get_course_table(TABLE_DOCUMENT);
        } else {
            $table = Database::get_course_table(TABLE_QUIZ_TEST);
        }

        $exerciseId = $this->get_ref_id();

        if (empty($this->exercise_data)) {
            if ($this->is_hp == 1) {
                $sql = "SELECT * FROM $table ex
                    INNER JOIN $tableItemProperty ip
                    ON (ip.ref = ex.id AND ip.c_id = ex.c_id)
                    WHERE
                        ip.c_id = $this->course_id AND
                        ex.c_id = $this->course_id AND
                        ip.ref = $exerciseId AND
                        ip.tool = '".TOOL_DOCUMENT."' AND
                        ex.path LIKE '%htm%' AND
                        ex.path LIKE '%HotPotatoes_files%' AND
                        ip.visibility = 1";
                $result = Database::query($sql);
                $this->exercise_data = Database::fetch_array($result);
            } else {
                // Try with iid
                $sql = "SELECT * FROM $table
                    WHERE
                        iid = $exerciseId";
                $result = Database::query($sql);
                $rows = Database::num_rows($result);

                if (!empty($rows)) {
                    $this->exercise_data = Database::fetch_array($result);
                } else {
                    // Try wit id
                    $sql = "SELECT * FROM $table
                        WHERE
                            iid = $exerciseId";
                    $result = Database::query($sql);
                    $this->exercise_data = Database::fetch_array($result);
                }
            }
        }

        if (empty($this->exercise_data)) {
            return false;
        }

        return $this->exercise_data;
    }

    /**
     * Lazy load function to get the database table of the exercise.
     */
    private function get_exercise_table()
    {
        $this->exercise_table = Database::get_course_table(TABLE_QUIZ_TEST);

        return $this->exercise_table;
    }
}
