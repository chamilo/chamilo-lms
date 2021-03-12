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
        if (empty($this->course_code)) {
            return [];
        }

        $sessionId = $this->get_session_id();
        $session = api_get_session_entity($sessionId);
        /*
        if (empty($session_id)) {
            $session_condition = api_get_session_condition(0, true);
        } else {
            $session_condition = api_get_session_condition($session_id, true, true);
        }
        $sql = "SELECT id, url, title FROM $tbl_grade_links
                WHERE c_id = {$this->course_id}  AND filetype='folder' AND active = 1 $session_condition ";*/

        //Only show works from the session
        //AND has_properties != ''
        $repo = Container::getStudentPublicationRepository();
        $qb = $repo->findAllByCourse(api_get_course_entity($this->course_id), $session, null, 1, 'folder');
        $links = $qb->getQuery()->getResult();

        /*$links = Container::getStudentPublicationRepository()
            ->findBy([
                'cId' => $this->course_id,
                'active' => true,
                'filetype' => 'folder',
                'session' => $session,
            ]);*/
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
            ->findBy([
                'parentId' => $id,
                'session' => $session,
            ]);

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
        $em = Database::getManager();
        $assignment = $this->getStudentPublication();

        if (empty($assignment)) {
            return [];
        }
        $session = api_get_session_entity($this->get_session_id());

        // @todo check session id / course id access
        /*$id = $studentPublication->getIid();
        $assignment = Container::getStudentPublicationRepository()
            ->findOneBy([
                'cId' => $this->course_id,
                'iid' => $id,
                'session' => $session,
            ])
        ;
        $parentId = !$assignment ? 0 : $assignment->getId();
        */

        $parentId = $assignment->getIid();

        if (empty($session)) {
            $dql = 'SELECT a FROM ChamiloCourseBundle:CStudentPublication a
                    WHERE
                        a.active = :active AND
                        a.publicationParent = :parent AND
                        a.session is null AND
                        a.qualificatorId <> 0
                    ';
            $params = [
                'parent' => $parentId,
                'active' => true,
            ];
        } else {
            $dql = 'SELECT a FROM ChamiloCourseBundle:CStudentPublication a
                    WHERE
                        a.active = :active AND
                        a.publicationParent = :parent AND
                        a.session = :session AND
                        a.qualificatorId <> 0
                    ';

            $params = [
                'parent' => $parentId,
                'session' => $session,
                'active' => true,
            ];
        }

        if (!empty($studentId)) {
            $dql .= ' AND a.userId = :student ';
            $params['student'] = $studentId;
        }

        $order = api_get_setting('student_publication_to_take_in_gradebook');

        switch ($order) {
            case 'last':
                // latest attempt
                $dql .= ' ORDER BY a.sentDate DESC';
                break;
            case 'first':
            default:
                // first attempt
                $dql .= ' ORDER BY a.iid';
                break;
        }

        $scores = $em->createQuery($dql)->execute($params);

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

        $students = []; // user list, needed to make sure we only
        // take first attempts into account
        $rescount = 0;
        $sum = 0;
        $bestResult = 0;
        $weight = 0;
        $sumResult = 0;

        foreach ($scores as $data) {
            if (!(array_key_exists($data->getUserId(), $students))) {
                if (0 != $assignment->getQualification()) {
                    $students[$data->getUserId()] = $data->getQualification();
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
                break;
            case 'average':
                return [$sumResult / $rescount, $weight];
                break;
            case 'ranking':
                return AbstractLink::getCurrentUserRanking($studentId, $students);
                break;
            default:
                return [$sum, $rescount];
                break;
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
