<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CQuiz;

/**
 * Process exercises before pass it to search listing scripts.
 *
 * @package chamilo.include.search
 */
class quiz_processor extends search_processor
{
    public $exercices = [];

    public function __construct($rows)
    {
        $this->rows = $rows;
        // group by exercise
        foreach ($rows as $row_id => $row_val) {
            $courseid = $row_val['courseid'];
            $se_data = $row_val['xapian_data'][SE_DATA];
            switch ($row_val['xapian_data'][SE_DATA]['type']) {
                case SE_DOCTYPE_EXERCISE_EXERCISE:
                    $exercise_id = $se_data['exercise_id'];
                    $question = null;
                    $item = [
                        'courseid' => $courseid,
                        'question' => $question,
                        'total_score' => $row_val['score'],
                        'row_id' => $row_id,
                    ];
                    $this->exercises[$courseid][$exercise_id] = $item;
                    $this->exercises[$courseid][$exercise_id]['total_score'] += $row_val['score'];
                    break;
                case SE_DOCTYPE_EXERCISE_QUESTION:
                    if (is_array($se_data['exercise_ids'])) {
                        foreach ($se_data['exercise_ids'] as $exercise_id) {
                            $question = $se_data['question_id'];
                            $item = [
                                'courseid' => $courseid,
                                'question' => $question,
                                'total_score' => $row_val['score'],
                                'row_id' => $row_id,
                            ];
                            $this->exercises[$courseid][$exercise_id] = $item;
                            $this->exercises[$courseid][$exercise_id]['total_score'] += $row_val['score'];
                        }
                    }
                    break;
            }
        }
    }

    public function process()
    {
        $results = [];
        foreach ($this->exercises as $courseid => $exercises) {
            $search_show_unlinked_results = (api_get_setting('search_show_unlinked_results') == 'true');
            $course_visible_for_user = api_is_course_visible_for_user(null, $courseid);
            // can view course?
            if ($course_visible_for_user || $search_show_unlinked_results) {
                foreach ($exercises as $exercise_id => $exercise) {
                    // is visible?
                    $visibility = api_get_item_visibility(api_get_course_info($courseid), TOOL_QUIZ, $exercise_id);
                    if ($visibility) {
                        list($thumbnail, $image, $name, $author) = $this->get_information($courseid, $exercise_id);
                        $url = api_get_path(WEB_CODE_PATH).'exercise/exercise_submit.php?cidReq=%s&exerciseId=%s';
                        $url = sprintf($url, $courseid, $exercise_id);
                        $result = [
                            'toolid' => TOOL_QUIZ,
                            'total_score' => $exercise['total_score'] / (count($exercise) - 1), // not count total_score array item
                            'url' => $url,
                            'thumbnail' => $thumbnail,
                            'image' => $image,
                            'title' => $name,
                            'author' => $author,
                        ];
                        if ($course_visible_for_user) {
                            $results[] = $result;
                        } else { // course not visible for user
                            if ($search_show_unlinked_results) {
                                $result['url'] = '';
                                $results[] = $result;
                            }
                        }
                    }
                }
            }
        }

        // get information to sort
        foreach ($results as $key => $row) {
            $score[$key] = $row['total_score'];
        }
        // Sort results with score descending
        array_multisort($score, SORT_DESC, $results);

        return $results;
    }

    /**
     * Get learning path information.
     */
    private function get_information($courseCode, $exercise_id)
    {
        $course_information = api_get_course_info($courseCode);
        $course_id = $course_information['real_id'];

        $em = Database::getManager();

        if (!empty($course_information)) {
            $exercise_id = intval($exercise_id);
            $dk_result = $em->find(CQuiz::class, $exercise_id);

            $name = '';
            if ($dk_result) {
                // Get the image path
                $thumbnail = Display::returnIconPath('quiz.png');
                $image = $thumbnail; //FIXME: use big images
                $name = $dk_result->getTitle();
                // get author
                $author = '';
                $item_result = $em
                    ->getRepository('ChamiloCourseBundle:CItemProperty')
                    ->findOneBy([
                        'ref' => $exercise_id,
                        'tool' => TOOL_QUIZ,
                        'course' => $course_id,
                    ]);

                if ($item_result) {
                    $author = UserManager::formatUserFullName($item_result->getInsertUser());
                }
            }

            return [$thumbnail, $image, $name, $author];
        } else {
            return [];
        }
    }
}
