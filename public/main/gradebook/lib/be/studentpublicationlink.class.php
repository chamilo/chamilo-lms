<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CStudentPublication;

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
        $id = $studentPublication->getIid();
        $session = api_get_session_entity($this->get_session_id());
        $results = Container::getStudentPublicationRepository()
            ->getStudentAssignments($studentPublication, api_get_course_entity($this->course_id), $session);


        return 0 !== count($results);
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
            ->getStudentAssignments($assignment, $course, $session, null, $studentId ? api_get_user_entity($studentId) : null);

        $order = api_get_setting('student_publication_to_take_in_gradebook');

        switch ($order) {
            case 'last':
                $qb->orderBy('resource.sentDate', 'DESC');
                break;
            case 'first':
            default:
                $qb->orderBy('resource.iid', 'ASC');
                break;
        }

        $scores = $qb->getQuery()->getResult();

        // for 1 student
        if (!empty($studentId)) {
            if (!count($scores)) {
                return [null, null];
            }

            $data = $scores[0];

            return [
                $data->getQualification(),
                $assignment->getQualification(),
                api_get_local_time($assignment->getDateOfQualification()),
                1,
            ];
        }

        // multiple students
        $students = [];
        $rescount = 0;
        $sum = 0;
        $bestResult = 0;
        $weight = 0;
        $sumResult = 0;

        foreach ($scores as $data) {
            if (!array_key_exists($data->getUser()->getId(), $students)) {
                if (0 != $assignment->getQualification()) {
                    $students[$data->getUser()->getId()] = $data->getQualification();
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
        $sessionId = $this->get_session_id();
        $url = api_get_path(WEB_PATH).'main/work/work.php?'.
            api_get_cidreq_params($this->getCourseId(), $sessionId).
            '&id='.$studentPublication->getIid().'&gradebook=view';

        return $url;
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
