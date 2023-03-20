<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

/**
 * Gradebook link to a survey item.
 *
 * @author Ivan Tcholakov <ivantcholakov@gmail.com>, 2010
 */
class SurveyLink extends AbstractLink
{
    private $survey_table;
    /** @var \Chamilo\CourseBundle\Entity\CSurvey */
    private $survey_data;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->set_type(LINK_SURVEY);
    }

    /**
     * @return string
     */
    public function get_name()
    {
        $this->get_survey_data();

        return $this->survey_data->getCode().': '.self::html_to_text($this->survey_data->getTitle());
    }

    /**
     * @return string
     */
    public function get_description()
    {
        $this->get_survey_data();

        return $this->survey_data->getSubtitle();
    }

    /**
     * @return string
     */
    public function get_type_name()
    {
        return get_lang('Survey');
    }

    public function is_allowed_to_change_name()
    {
        return false;
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
     * Generates an array of all surveys available.
     *
     * @return array 2-dimensional array - every element contains 2 subelements (id, name)
     */
    public function get_all_links()
    {
        if (empty($this->course_code)) {
            exit('Error in get_all_links() : course code not set');
        }
        $sessionId = $this->get_session_id();
        $course_id = $this->getCourseId();

        $repo = Container::getSurveyRepository();
        $course = api_get_course_entity($course_id);
        $session = !empty($sessionId) ? api_get_session_entity($sessionId) : null;

        $qb = $repo->getResourcesByCourse($course, $session);
        $surveys = $qb->getQuery()->getResult();
        $links = [];
        /** @var \Chamilo\CourseBundle\Entity\CSurvey $survey */
        foreach ($surveys as $survey) {
            $links[] = [
                $survey->getIid(),
                api_trunc_str(
                    $survey->getCode().': '.self::html_to_text($survey->getTitle()),
                    80
                ),
            ];
        }

        return $links;
    }

    /**
     * Has anyone done this survey yet?
     * Implementation of the AbstractLink class, mainly used dynamically in gradebook/lib/fe.
     */
    public function has_results()
    {
        $ref_id = $this->get_ref_id();
        $sessionId = $this->get_session_id();
        $courseId = $this->getCourseId();

        $tbl_survey = Database::get_course_table(TABLE_SURVEY);
        $table = Database::get_course_table(TABLE_SURVEY_INVITATION);
        $sql = "SELECT
                COUNT(i.answered)
                FROM $tbl_survey AS s
                INNER JOIN $table AS i
                ON s.code = i.survey_code
                WHERE
                    i.c_id = $courseId AND
                    s.iid = $ref_id AND
                    i.session_id = $sessionId";

        $sql_result = Database::query($sql);
        $data = Database::fetch_array($sql_result);

        return 0 != $data[0];
    }

    /**
     * Calculate score for a student (to show in the gradebook).
     *
     * @param int    $studentId
     * @param string $type      Type of result we want (best|average|ranking)
     *
     * @return array|null
     */
    public function calc_score($studentId = null, $type = null)
    {
        // Note: Max score is assumed to be always 1 for surveys,
        // only student's participation is to be taken into account.
        $max_score = 1;
        $ref_id = $this->get_ref_id();
        $sessionId = $this->get_session_id();
        $courseId = $this->getCourseId();
        $tbl_survey = Database::get_course_table(TABLE_SURVEY);
        $tbl_survey_invitation = Database::get_course_table(TABLE_SURVEY_INVITATION);
        $get_individual_score = !is_null($studentId);

        $sql = "SELECT i.answered
                FROM $tbl_survey AS s
                JOIN $tbl_survey_invitation AS i
                ON s.code = i.survey_code
                WHERE
                    i.c_id = $courseId AND
                    s.iid = $ref_id AND
                    i.session_id = $sessionId
                ";

        if ($get_individual_score) {
            $sql .= ' AND i.user = '.intval($studentId);
        }

        $sql_result = Database::query($sql);

        if ($get_individual_score) {
            // for 1 student
            if ($data = Database::fetch_array($sql_result)) {
                return [$data['answered'] ? $max_score : 0, $max_score];
            }

            return [0, $max_score];
        } else {
            // for all the students -> get average
            $rescount = 0;
            $sum = 0;
            $bestResult = 0;
            while ($data = Database::fetch_array($sql_result)) {
                $sum += $data['answered'] ? $max_score : 0;
                $rescount++;
                if ($data['answered'] > $bestResult) {
                    $bestResult = $data['answered'];
                }
            }
            $sum = $sum / $max_score;

            if (0 == $rescount) {
                return [null, null];
            }

            switch ($type) {
                case 'best':
                    return [$bestResult, $rescount];
                    break;
                case 'average':
                    return [$sum, $rescount];
                    break;
                case 'ranking':
                    return null;
                    break;
                default:
                    return [$sum, $rescount];
                    break;
            }
        }
    }

    /**
     * Check if this still links to a survey.
     */
    public function is_valid_link()
    {
        $sessionId = $this->get_session_id();
        $courseId = $this->getCourseId();

        $sql = 'SELECT count(iid) FROM '.$this->get_survey_table().'
                 WHERE
                    c_id = '.$courseId.' AND
                    iid = '.$this->get_ref_id().' AND
                    session_id = '.$sessionId;
        $result = Database::query($sql);
        $number = Database::fetch_row($result);

        return 0 != $number[0];
    }

    public function get_link()
    {
        if (api_get_configuration_value('hide_survey_reporting_button')) {
            return null;
        }

        if (api_is_allowed_to_edit()) {
            // Let students make access only through "Surveys" tool.
            $sessionId = $this->get_session_id();
            $courseId = $this->getCourseId();
            $survey = $this->get_survey_data();
            if ($survey) {
                $survey_id = $survey->getIid();

                return api_get_path(WEB_CODE_PATH).'survey/reporting.php?'.
                    api_get_cidreq_params($this->getCourseId(), $sessionId).'&survey_id='.$survey_id;
            }
        }

        return null;
    }

    /**
     * Get the name of the icon for this tool.
     *
     * @return string
     */
    public function get_icon_name()
    {
        return 'survey';
    }

    /**
     * Lazy load function to get the database table of the surveys.
     */
    private function get_survey_table()
    {
        $this->survey_table = Database::get_course_table(TABLE_SURVEY);

        return $this->survey_table;
    }

    /**
     * Get the survey data from the c_survey table with the current object id.
     *
     * @return \Chamilo\CourseBundle\Entity\CSurvey
     */
    private function get_survey_data()
    {
        if (empty($this->survey_data)) {
            $courseId = $this->getCourseId();
            $sessionId = $this->get_session_id();
            $repo = Container::getSurveyRepository();
            $survey = $repo->find($this->get_ref_id());
            $this->survey_data = $survey;
        }

        return $this->survey_data;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    private static function html_to_text($string)
    {
        return strip_tags($string);
    }
}
