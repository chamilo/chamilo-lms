<?php
/* For licensing terms, see /license.txt */

/**
 * Defines a gradebook Result object.
 *
 * @author Bert Steppé, Stijn Konings
 */
class Result
{
    private $id;
    private $user_id;
    private $evaluation;
    private $created_at;
    private $score;

    /**
     * Result constructor.
     */
    public function __construct()
    {
        $this->created_at = api_get_utc_datetime();
    }

    public function get_id()
    {
        return $this->id;
    }

    public function get_user_id()
    {
        return $this->user_id;
    }

    public function get_evaluation_id()
    {
        return $this->evaluation;
    }

    public function get_date()
    {
        return $this->created_at;
    }

    public function get_score()
    {
        return $this->score;
    }

    public function set_id($id)
    {
        $this->id = $id;
    }

    public function set_user_id($user_id)
    {
        $this->user_id = $user_id;
    }

    public function set_evaluation_id($evaluation_id)
    {
        $this->evaluation = $evaluation_id;
    }

    /**
     * @param string $creation_date
     */
    public function set_date($creation_date)
    {
        $this->created_at = $creation_date;
    }

    /**
     * @param float $score
     */
    public function set_score($score)
    {
        $this->score = $score;
    }

    /**
     * Retrieve results and return them as an array of Result objects.
     *
     * @param $id result id
     * @param $user_id user id (student)
     * @param $evaluation_id evaluation where this is a result for
     *
     * @return array
     */
    public static function load($id = null, $user_id = null, $evaluation_id = null)
    {
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_grade_results = Database::get_main_table(TABLE_MAIN_GRADEBOOK_RESULT);
        $tbl_course_rel_course = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $tbl_session_rel_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $sessionId = api_get_session_id();
        $list_user_course_list = [];

        if (is_null($id) && is_null($user_id) && !is_null($evaluation_id)) {
            // Verified_if_exist_evaluation
            $sql = 'SELECT COUNT(*) AS count
                    FROM '.$tbl_grade_results.'
                    WHERE evaluation_id="'.Database::escape_string($evaluation_id).'"';

            $result = Database::query($sql);
            $existEvaluation = Database::result($result, 0, 0);

            if (0 != $existEvaluation) {
                if ($sessionId) {
                    $sql = 'SELECT c_id, user_id as user_id, status
                            FROM '.$tbl_session_rel_course_user.'
							WHERE
							    status= 0 AND
							    c_id = "'.api_get_course_int_id().'" AND
							    session_id = '.$sessionId;
                } else {
                    $sql = 'SELECT c_id, user_id, status
                            FROM '.$tbl_course_rel_course.'
                            WHERE status ="'.STUDENT.'" AND c_id = "'.api_get_course_int_id().'" ';
                }

                $res_course_rel_user = Database::query($sql);
                while ($row_course_rel_user = Database::fetch_array($res_course_rel_user, 'ASSOC')) {
                    $list_user_course_list[] = $row_course_rel_user;
                }
                $current_date = api_get_utc_datetime();
                for ($i = 0; $i < count($list_user_course_list); $i++) {
                    $sql_verified = 'SELECT COUNT(*) AS count
                                    FROM '.$tbl_grade_results.'
                                    WHERE
                                        user_id="'.intval($list_user_course_list[$i]['user_id']).'" AND
                                        evaluation_id="'.intval($evaluation_id).'";';
                    $res_verified = Database::query($sql_verified);
                    $info_verified = Database::result($res_verified, 0, 0);
                    if (0 == $info_verified) {
                        $sql_insert = 'INSERT INTO '.$tbl_grade_results.'(user_id,evaluation_id,created_at,score)
									   VALUES ("'.intval($list_user_course_list[$i]['user_id']).'","'.intval($evaluation_id).'","'.$current_date.'",0);';
                        Database::query($sql_insert);
                    }
                }
            }
        }

        $userIdList = [];
        foreach ($list_user_course_list as $data) {
            $userIdList[] = $data['user_id'];
        }
        $userIdListToString = implode("', '", $userIdList);

        $sql = "SELECT lastname, gr.id, gr.user_id, gr.evaluation_id, gr.created_at, gr.score
                FROM $tbl_grade_results gr
                INNER JOIN $tbl_user u
                ON gr.user_id = u.user_id ";

        if (!empty($userIdList)) {
            $sql .= " AND u.user_id IN ('$userIdListToString')";
        }

        $paramcount = 0;
        if (!empty($id)) {
            $sql .= ' WHERE gr.id = '.intval($id);
            $paramcount++;
        }
        if (!empty($user_id)) {
            if (0 != $paramcount) {
                $sql .= ' AND';
            } else {
                $sql .= ' WHERE';
            }
            $sql .= ' gr.user_id = '.intval($user_id);
            $paramcount++;
        }
        if (!empty($evaluation_id)) {
            if (0 != $paramcount) {
                $sql .= ' AND';
            } else {
                $sql .= ' WHERE';
            }
            $sql .= ' gr.evaluation_id = '.intval($evaluation_id);
        }
        $sql .= ' ORDER BY u.lastname, u.firstname';

        $result = Database::query($sql);
        $allres = [];
        while ($data = Database::fetch_array($result)) {
            $res = new Result();
            $res->set_id($data['id']);
            $res->set_user_id($data['user_id']);
            $res->set_evaluation_id($data['evaluation_id']);
            $res->set_date(api_get_local_time($data['created_at']));
            $res->set_score($data['score']);
            $allres[] = $res;
        }

        return $allres;
    }

    /**
     * Insert this result into the database.
     */
    public function add()
    {
        if (isset($this->user_id) && isset($this->evaluation)) {
            $tbl_grade_results = Database::get_main_table(TABLE_MAIN_GRADEBOOK_RESULT);
            $sql = "INSERT INTO ".$tbl_grade_results
                ." (user_id, evaluation_id,
					created_at";
            if (isset($this->score)) {
                $sql .= ",score";
            }
            $sql .= ") VALUES
					(".(int) $this->get_user_id().", ".(int) $this->get_evaluation_id()
                .", '".$this->get_date()."' ";
            if (isset($this->score)) {
                $sql .= ", ".$this->get_score();
            }
            $sql .= ")";
            Database::query($sql);
        } else {
            exit('Error in Result add: required field empty');
        }
    }

    /**
     * insert log result.
     */
    public function addResultLog($userid, $evaluationid)
    {
        if (isset($userid) && isset($evaluationid)) {
            $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_RESULT_LOG);
            $result = new Result();

            $arr_result = $result->load(null, $userid, $evaluationid);
            $arr = get_object_vars($arr_result[0]);

            $sql = 'INSERT INTO '.$table
                .' (id_result,user_id, evaluation_id,created_at';
            if (isset($arr['score'])) {
                $sql .= ',score';
            }
            $sql .= ') VALUES
					('.(int) $arr['id'].','.(int) $arr['user_id'].', '.(int) $arr['evaluation']
                .", '".api_get_utc_datetime()."'";
            if (isset($arr['score'])) {
                $sql .= ', '.$arr['score'];
            }
            $sql .= ')';

            Database::query($sql);
        } else {
            exit('Error in Result add: required field empty');
        }
    }

    /**
     * Update the properties of this result in the database.
     */
    public function save()
    {
        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_RESULT);
        $sql = 'UPDATE '.$table.'
                SET user_id = '.$this->get_user_id()
            .', evaluation_id = '.$this->get_evaluation_id()
            .', score = ';
        if (isset($this->score)) {
            $sql .= $this->get_score();
        } else {
            $sql .= 'null';
        }
        if (isset($this->id)) {
            $sql .= " WHERE id = {$this->id}";
        } else {
            $sql .= " WHERE evaluation_id = {$this->evaluation}
                AND user_id = {$this->user_id}
            ";
        }

        // no need to update creation date
        Database::query($sql);

        Evaluation::generateStats($this->get_evaluation_id());
    }

    /**
     * Delete this result from the database.
     */
    public function delete()
    {
        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_RESULT);
        $sql = 'DELETE FROM '.$table.' WHERE id = '.$this->id;
        Database::query($sql);
        $allowMultipleAttempts = api_get_configuration_value('gradebook_multiple_evaluation_attempts');
        if ($allowMultipleAttempts) {
            $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_RESULT_ATTEMPT);
            $sql = "DELETE FROM $table WHERE result_id = ".$this->id;
            Database::query($sql);
        }

        Evaluation::generateStats($this->get_evaluation_id());
    }

    /**
     * Check if exists a result with its user and evaluation.
     *
     * @throws \Doctrine\ORM\Query\QueryException
     *
     * @return bool
     */
    public function exists()
    {
        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_RESULT);
        $sql = "SELECT COUNT(*) AS count
                FROM $table gr
                WHERE gr.evaluation_id = {$this->evaluation}
                AND gr.user_id = {$this->user_id}
        ";
        $result = Database::query($sql);
        $row = Database::fetch_array($result);
        $count = (int) $row['count'];

        return $count > 0;
    }
}
