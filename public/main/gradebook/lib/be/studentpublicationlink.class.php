<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Doctrine\ORM\QueryBuilder;

/**
 * Gradebook link to student publication item.
 *
 * @author Bert Steppé
 */
class StudentPublicationLink extends AbstractLink
{
    private $studpub_table;

    public function __construct()
    {
        parent::__construct();
        $this->set_type(LINK_STUDENTPUBLICATION);
    }

    /**
     * @return string
     */
    public function get_type_name()
    {
        return get_lang('Assignments');
    }

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
        if (empty($this->course_id)) {
            return [];
        }

        $sessionId = $this->get_session_id();
        $session = api_get_session_entity($sessionId);
        $repo = Container::getStudentPublicationRepository();
        $qb = $repo->findAllByCourse(api_get_course_entity($this->course_id), $session, null, 1, 'folder');
        $links = $qb->getQuery()->getResult();
        $cats = [];
        foreach ($links as $data) {
            $work_name = $data->getTitle();
            if (empty($work_name)) {
                $work_name = basename($data->getUrl());
            }
            $cats[] = [$data->getIid(), $work_name];
        }

        return $cats;
    }

    /**
     * Has anyone done this exercise yet ?
     */
    public function has_results()
    {
        $studentPublication = $this->getStudentPublication();

        if (empty($studentPublication)) {
            return '';
        }

        $session = api_get_session_entity($this->get_session_id());
        $course = api_get_course_entity($this->course_id);

        $qbOrResults = Container::getStudentPublicationRepository()
            ->getStudentAssignments($studentPublication, $course, $session);

        // In most cases this repository returns a QueryBuilder.
        if ($qbOrResults instanceof QueryBuilder) {
            $qbOrResults->setMaxResults(1);
            $one = $qbOrResults->getQuery()->getOneOrNullResult();

            return null !== $one;
        }

        // Fallback if it returns an array (older behavior).
        if (is_array($qbOrResults)) {
            return 0 !== count($qbOrResults);
        }

        return false;
    }

    /**
     * @param null $studentId
     *
     * @return array
     */
    public function calc_score($studentId = null, $type = null)
    {
        $studentId = (int) $studentId;
        $assignment = $this->getStudentPublication();

        if (empty($assignment)) {
            return [];
        }

        $session = api_get_session_entity($this->get_session_id());
        $course = api_get_course_entity($this->course_id);

        $qb = Container::getStudentPublicationRepository()
            ->getStudentAssignments(
                $assignment,
                $course,
                $session,
                null,
                $studentId ? api_get_user_entity($studentId) : null
            );

        // Safety net: ensure we only fetch submissions for the requested user.
        // This prevents a single student's grade from being shown for all users in gradebook.
        if (!empty($studentId) && $qb instanceof QueryBuilder) {
            $studentUser = api_get_user_entity($studentId);
            if ($studentUser) {
                $qb
                    ->andWhere('resource.user = :studentUser')
                    ->setParameter('studentUser', $studentUser);
            }
        }

        $order = api_get_setting('student_publication_to_take_in_gradebook');

        if ($qb instanceof QueryBuilder) {
            switch ($order) {
                case 'last':
                    $qb->orderBy('resource.sentDate', 'DESC');
                    break;
                case 'first':
                default:
                    $qb->orderBy('resource.iid', 'ASC');
                    break;
            }
        }

        // For 1 student
        if (!empty($studentId)) {
            if ($qb instanceof QueryBuilder) {
                $qb->setMaxResults(1);
                $data = $qb->getQuery()->getOneOrNullResult();
            } else {
                // Fallback if repository returns array
                $scores = is_array($qb) ? $qb : [];
                $data = $scores[0] ?? null;
            }

            if (empty($data)) {
                return [null, null];
            }

            // Prefer the submission qualification date; fallback to assignment date if needed.
            $date = null;
            if (method_exists($data, 'getDateOfQualification')) {
                $date = $data->getDateOfQualification();
            } elseif (method_exists($assignment, 'getDateOfQualification')) {
                $date = $assignment->getDateOfQualification();
            }

            return [
                $data->getQualification(),
                $assignment->getQualification(),
                $date ? api_get_local_time($date) : null,
                1,
            ];
        }

        // Multiple students
        $scores = ($qb instanceof QueryBuilder) ? $qb->getQuery()->getResult() : (is_array($qb) ? $qb : []);

        $students = [];
        $rescount = 0;
        $sum = 0;
        $bestResult = 0;
        $weight = 0;
        $sumResult = 0;

        foreach ($scores as $data) {
            $userId = $data->getUser()->getId();

            if (!array_key_exists($userId, $students)) {
                if (0 != $assignment->getQualification()) {
                    $students[$userId] = $data->getQualification();
                    $rescount++;
                    $sum += $data->getQualification() / $assignment->getQualification();
                    $sumResult += $data->getQualification();

                    if ($data->getQualification() > $bestResult) {
                        $bestResult = $data->getQualification();
                    }
                    $weight = $assignment->getQualification();
                }
            }
        }

        if (0 == $rescount) {
            return [null, null];
        }

        switch ($type) {
            case 'best':
                return [$bestResult, $weight];
            case 'average':
                return [$sumResult / $rescount, $weight];
            case 'ranking':
                return AbstractLink::getCurrentUserRanking($studentId, $students);
            default:
                return [$sum, $rescount];
        }
    }

    public function needs_name_and_description()
    {
        return false;
    }

    public function get_name()
    {
        $studentPublication = $this->getStudentPublication();
        $title = $studentPublication->getTitle();

        return empty($title) ? get_lang('Untitled') : $title;
    }

    public function get_description()
    {
        $studentPublication = $this->getStudentPublication();

        return $studentPublication->getDescription();
    }

    public function get_link()
    {
        $studentPublication = $this->getStudentPublication();

        if (
            !$studentPublication ||
            !method_exists($studentPublication, 'getResourceNode') ||
            null === $studentPublication->getResourceNode()
        ) {
            return '';
        }

        $nodeId = (int) $studentPublication->getResourceNode()->getId();

        $query = [
            'cid' => (int) $this->getCourseId(),
            'sid' => (int) $this->get_session_id(),
            'gid' => 0,
            'gradebook' => 'view',
        ];

        return api_get_path(WEB_PATH).'resources/assignment/'.$nodeId.'/?'.http_build_query($query);
    }

    public function needs_max()
    {
        return false;
    }

    public function needs_results()
    {
        return false;
    }

    public function is_valid_link()
    {
        $studentPublication = $this->getStudentPublication();

        return null !== $studentPublication;
    }

    public function get_icon_name()
    {
        return 'studentpublication';
    }

    public function save_linked_data()
    {
        $studentPublication = $this->getStudentPublication();

        if (empty($studentPublication)) {
            return '';
        }

        $weight = api_float_val($this->get_weight());
        $studentPublication->setWeight($weight);

        $repo = Container::getStudentPublicationRepository();
        $repo->update($studentPublication);
    }

    /**
     * @return string
     */
    public function delete_linked_data()
    {
        /*$data = $this->get_exercise_data();
        if (empty($data)) {
            return '';
        }*/

        /*if (!empty($id)) {
            //Cleans works
            $sql = 'UPDATE '.$this->get_studpub_table().'
                    SET weight = 0
                    WHERE c_id = '.$this->course_id.' AND id ='.$id;
            Database::query($sql);
        }*/
    }

    /**
     * Lazy load function to get the database table of the student publications.
     */
    private function get_studpub_table()
    {
        return $this->studpub_table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    }

    private function getStudentPublication(): ?CStudentPublication
    {
        $repo = Container::getStudentPublicationRepository();
        if (!empty($this->get_ref_id())) {
            return $repo->find($this->get_ref_id());
        }

        return null;
    }
}
