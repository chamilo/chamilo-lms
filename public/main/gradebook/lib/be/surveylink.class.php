<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CSurvey;

/**
 * Gradebook link to a survey item.
 *
 * @author Ivan Tcholakov <ivantcholakov@gmail.com>, 2010
 */
class SurveyLink extends AbstractLink
{
    /** @var CSurvey */
    private $survey_data;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->set_type(LINK_SURVEY);
    }

    public function get_name(): string
    {
        $survey = $this->get_survey_data();

        if (!$survey instanceof CSurvey) {
            return get_lang('Untitled Survey');
        }

        return $survey->getCode() . ': ' . self::html_to_text($survey->getTitle());
    }

    public function get_description(): string
    {
        $survey = $this->get_survey_data();

        if (!$survey instanceof CSurvey) {
            return '';
        }

        return $survey->getSubtitle();
    }

    public function get_type_name(): string
    {
        return get_lang('Survey');
    }

    public function is_allowed_to_change_name(): bool
    {
        return false;
    }

    public function needs_name_and_description(): bool
    {
        return false;
    }

    public function needs_max(): bool
    {
        return false;
    }

    public function needs_results(): bool
    {
        return false;
    }

    /**
     * Generates an array of all surveys available.
     *
     * @return array 2-dimensional array - every element contains 2 subelements (id, name)
     */
    public function get_all_links(): array
    {
        if (empty($this->course_id)) {
            return [];
        }

        $session = api_get_session_entity($this->get_session_id());
        $course = api_get_course_entity($this->getCourseId());
        $repo = Container::getSurveyRepository();

        $qb = $repo->getResourcesByCourse($course, $session);
        $surveys = $qb->getQuery()->getResult();
        $links = [];
        /** @var CSurvey $survey */
        foreach ($surveys as $survey) {
            $links[] = [
                $survey->getIid(),
                api_trunc_str(
                    $survey->getCode() . ': ' . self::html_to_text($survey->getTitle()),
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
    public function has_results(): bool
    {
        $survey = $this->get_survey_data();
        if (!$survey) {
            return false;
        }

        $repo = Container::getSurveyInvitationRepository();
        $course = api_get_course_entity($this->course_id);
        $session = api_get_session_entity($this->get_session_id());

        $results = $repo->getAnsweredInvitations($survey, $course, $session);

        return count($results) > 0;
    }

    /**
     * Calculate score for a student (to show in the gradebook).
     *
     * @param int    $studentId
     * @param string $type      Type of result we want (best|average|ranking)
     *
     * @return array|null
     */
    public function calc_score($studentId = null, $type = null): ?array
    {
        $survey = $this->get_survey_data();
        if (!$survey) {
            return [null, null];
        }

        $course = api_get_course_entity($this->course_id);
        $session = api_get_session_entity($this->get_session_id());
        $repo = Container::getSurveyInvitationRepository();
        $max_score = 1;

        if ($studentId) {
            $user = api_get_user_entity($studentId);
            $answered = $repo->hasUserAnswered($survey, $course, $user, $session);

            return [$answered ? $max_score : 0, $max_score];
        }

        $results = $repo->getAnsweredInvitations($survey, $course, $session);
        $rescount = count($results);

        if ($rescount === 0) {
            return [null, null];
        }

        switch ($type) {
            case 'best':
            case 'average':
            default:
                return [$rescount, $rescount];
        }
    }

    /**
     * Check if this still links to a survey.
     */
    public function is_valid_link(): bool
    {
        return null !== $this->get_survey_data();
    }

    public function get_link(): ?string
    {
        if ('true' === api_get_setting('survey.hide_survey_reporting_button')) {
            return null;
        }

        if (api_is_allowed_to_edit()) {
            $survey = $this->get_survey_data();
            $sessionId = $this->get_session_id();

            if ($survey) {
                return api_get_path(WEB_CODE_PATH) . 'survey/reporting.php?' .
                    api_get_cidreq_params($this->getCourseId(), $sessionId) .
                    '&survey_id=' . $survey->getIid();
            }
        }

        return null;
    }

    /**
     * Get the name of the icon for this tool.
     */
    public function get_icon_name(): string
    {
        return 'survey';
    }

    /**
     * Get the survey data from the c_survey table with the current object id.
     */
    private function get_survey_data(): ?CSurvey
    {
        if (empty($this->survey_data)) {
            $repo = Container::getSurveyRepository();
            $survey = $repo->find($this->get_ref_id());

            if (!$survey instanceof CSurvey) {
                return null;
            }

            $this->survey_data = $survey;
        }

        return $this->survey_data;
    }

    /**
     * @param string $string
     */
    private static function html_to_text($string): string
    {
        return strip_tags($string);
    }
}
