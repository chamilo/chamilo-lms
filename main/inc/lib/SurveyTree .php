<?php
/* For licensing terms, see /license.txt */

/**
 * Manage the "versioning" of a conditional survey.
 *
 *	@package chamilo.survey
 */
class SurveyTree
{
    public $surveylist;
    public $plainsurveylist;
    public $numbersurveys;

    /**
     * Sets the surveylist and the plainsurveylist.
     */
    public function __construct()
    {
        // Database table definitions
        $table_survey = Database::get_course_table(TABLE_SURVEY);
        $table_survey_question = Database::get_course_table(TABLE_SURVEY_QUESTION);
        $table_user = Database::get_main_table(TABLE_MAIN_USER);

        // searching
        $search_restriction = SurveyUtil::survey_search_restriction();
        if ($search_restriction) {
            $search_restriction = ' AND '.$search_restriction;
        }

        $course_id = api_get_course_int_id();

        $sql = "SELECT
                    survey.survey_id,
                    survey.parent_id,
                    survey_version,
                    survey.code as name
				FROM $table_survey survey
				LEFT JOIN $table_survey_question  survey_question
				ON survey.survey_id = survey_question.survey_id , $table_user user
				WHERE
					survey.c_id =  $course_id AND
					survey_question.c_id = $course_id AND
					survey.author = user.user_id
				GROUP BY survey.survey_id";

        $res = Database::query($sql);
        $refs = [];
        $list = [];
        $plain_array = [];

        while ($survey = Database::fetch_array($res, 'ASSOC')) {
            $plain_array[$survey['survey_id']] = $survey;
            $surveys_parents[] = $survey['survey_version'];
            $thisref = &$refs[$survey['survey_id']];
            $thisref['parent_id'] = $survey['parent_id'];
            $thisref['name'] = $survey['name'];
            $thisref['id'] = $survey['survey_id'];
            $thisref['survey_version'] = $survey['survey_version'];
            if ($survey['parent_id'] == 0) {
                $list[$survey['survey_id']] = &$thisref;
            } else {
                $refs[$survey['parent_id']]['children'][$survey['survey_id']] = &$thisref;
            }
        }
        $this->surveylist = $list;
        $this->plainsurveylist = $plain_array;
    }

    /**
     * This function gets the parent id of a survey.
     *
     * @param int $id survey id
     *
     * @return int survey parent id
     *
     * @author Julio Montoya <gugli100@gmail.com>, Dokeos
     *
     * @version September 2008
     */
    public function getParentId($id)
    {
        $node = $this->plainsurveylist[$id];
        if (is_array($node) && !empty($node['parent_id'])) {
            return $node['parent_id'];
        } else {
            return -1;
        }
    }

    /**
     * This function creates a list of all surveys id.
     *
     * @param array $list of nodes
     *
     * @return array with the structure survey_id => survey_name
     *
     * @author Julio Montoya <gugli100@gmail.com>
     *
     * @version September 2008
     */
    public function createList($list)
    {
        $result = [];
        if (is_array($list)) {
            foreach ($list as $key => $node) {
                if (isset($node['children']) && is_array($node['children'])) {
                    $result[$key] = $node['name'];
                    $re = self::createList($node['children']);
                    if (!empty($re)) {
                        if (is_array($re)) {
                            foreach ($re as $key => $r) {
                                $result[$key] = ''.$r;
                            }
                        } else {
                            $result[] = $re;
                        }
                    }
                } else {
                    $result[$key] = $node['name'];
                }
            }
        }

        return $result;
    }
}
