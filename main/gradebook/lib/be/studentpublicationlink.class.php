<?php
/* For licensing terms, see /license.txt */

/**
 * Gradebook link to student publication item
 * @author Bert Steppé
 * @package chamilo.gradebook
 */
class StudentPublicationLink extends AbstractLink
{
    private $studpub_table = null;
    private $itemprop_table = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->set_type(LINK_STUDENTPUBLICATION);
    }

    /**
     *
     * Returns the URL of a document
     * This function is loaded when using a gradebook as a tab (gradebook = -1)
     * see issue #2705
     *
     */
    public function get_view_url($stud_id)
    {
        return null;
        // find a file uploaded by the given student,
        // with the same title as the evaluation name

        $eval = $this->get_evaluation();
        $stud_id = intval($stud_id);
        $itemProperty = $this->get_itemprop_table();
        $workTable = $this->get_studpub_table();
        $courseId = $this->course_id;

        $sql = "SELECT pub.url
                FROM $itemProperty prop INNER JOIN $workTable pub
                ON (prop.c_id = pub.c_id AND prop.ref = pub.id)
                WHERE
                    prop.c_id = ".$courseId." AND
                    pub.c_id = ".$courseId." AND
                    prop.tool = 'work' AND 
                    prop.insert_user_id = $stud_id AND                     
                    pub.title = '".Database::escape_string($eval->get_name())."' AND 
                    pub.session_id=".api_get_session_id();

        $result = Database::query($sql);
        if ($fileurl = Database::fetch_row($result)) {
            return null;
        } else {
            return null;
        }
    }

    /**
     * @return string
     */
    public function get_type_name()
    {
        return get_lang('Works');
    }

    public function is_allowed_to_change_name()
    {
        return false;
    }

    /**
     * Generate an array of exercises that a teacher hasn't created a link for.
     * @return array 2-dimensional array - every element contains 2 subelements (id, name)
     */
    public function get_not_created_links()
    {
        return false;
        if (empty($this->course_code)) {
            die('Error in get_not_created_links() : course code not set');
        }
        $tbl_grade_links = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);

        $sql = 'SELECT id, url from '.$this->get_studpub_table()
            .' pup WHERE c_id = '.$this->course_id.' AND has_properties != '."''".' AND id NOT IN'
            .' (SELECT ref_id FROM '.$tbl_grade_links
            .' WHERE type = '.LINK_STUDENTPUBLICATION
            ." AND course_code = '".Database::escape_string($this->get_course_code())."'"
            .') AND pub.session_id='.api_get_session_id().'';

        $result = Database::query($sql);

        $cats=array();
        while ($data = Database::fetch_array($result)) {
            $cats[] = array($data['id'], $data['url']);
        }
        return $cats;
    }

    /**
     * Generate an array of all exercises available.
     * @return array 2-dimensional array - every element contains 2 subelements (id, name)
     */
    public function get_all_links()
    {
        if (empty($this->course_code)) {
            die('Error in get_not_created_links() : course code not set');
        }
        $em = Database::getManager();
        $session = $em->find('ChamiloCoreBundle:Session', api_get_session_id());
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
        $links = $em
            ->getRepository('ChamiloCourseBundle:CStudentPublication')
            ->findBy([
                'cId' => $this->course_id,
                'active' => true,
                'filetype' => 'folder',
                'session' => $session
            ]);

        foreach ($links as $data) {
            $work_name = $data->getTitle();
            if (empty($work_name)) {
                $work_name = basename($data->getUrl());
            }
            $cats[] = array ($data->getId(), $work_name);
        }
        $cats=isset($cats) ? $cats : array();
        return $cats;
    }

    /**
     * Has anyone done this exercise yet ?
     */
    public function has_results()
    {
        $data = $this->get_exercise_data();

        if (empty($data)) {
            return '';
        }
        $id = $data['id'];

        $em = Database::getManager();
        $session = $em->find('ChamiloCoreBundle:Session', api_get_session_id());
        $results = $em
            ->getRepository('ChamiloCourseBundle:CStudentPublication')
            ->findBy([
                'cId' => $this->course_id,
                'parentId' => $id,
                'session' => $session
            ]);

        return count($results) != 0;
    }

    /**
     * @param null $stud_id
     * @return array|null
     */
    public function calc_score($stud_id = null, $type = null)
    {
        $stud_id = (int) $stud_id;
        $em = Database::getManager();
        $data = $this->get_exercise_data();

        if (empty($data)) {
            return '';
        }
        $id = $data['id'];

        $session = $em->find('ChamiloCoreBundle:Session', api_get_session_id());

        $assignment = $em
            ->getRepository('ChamiloCourseBundle:CStudentPublication')
            ->findOneBy([
                'cId' => $this->course_id,
                'id' => $id,
                'session' => $session
            ])
        ;

        $parentId = !$assignment ? 0 : $assignment->getId();

        if (empty($session)) {
           $dql = 'SELECT a FROM ChamiloCourseBundle:CStudentPublication a
                   WHERE
                        a.cId = :course AND
                        a.active = :active AND
                        a.parentId = :parent AND
                        a.session is null AND
                        a.qualificatorId <> 0
                    ';

            $params = [
                'course' => $this->course_id,
                'parent' => $parentId,
                'active' => true
            ];

        } else {
            $dql = 'SELECT a FROM ChamiloCourseBundle:CStudentPublication a
                    WHERE
                        a.cId = :course AND
                        a.active = :active AND
                        a.parentId = :parent AND
                        a.session = :session AND
                        a.qualificatorId <> 0
                    ';

            $params = [
                'course' => $this->course_id,
                'parent' => $parentId,
                'session' => $session,
                'active' => true,
            ];
        }

        if (!empty($stud_id)) {
            $dql .= ' AND a.userId = :student ';
            $params['student'] = $stud_id;
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
                $dql .= ' ORDER BY a.id';
                break;
        }

        $scores = $em->createQuery($dql)->execute($params);

        // for 1 student
        if (!empty($stud_id)) {
            if (!count($scores)) {
                return '';
            }

            $data = $scores[0];

            return [
                $data->getQualification(),
                $assignment->getQualification()
            ];
        }

        $students = array();  // user list, needed to make sure we only
        // take first attempts into account
        $rescount = 0;
        $sum = 0;
        $bestResult = 0;
        $weight = 0;
        $sumResult = 0;

        foreach ($scores as $data) {
            if (!(array_key_exists($data->getUserId(), $students))) {
                if ($assignment->getQualification() != 0) {
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

        if ($rescount == 0) {
            return null;
        }

        switch ($type) {
            case 'best':
                return array($bestResult, $weight);
                break;
            case 'average':
                return array($sumResult/$rescount, $weight);
                break;
            case 'ranking':
                return AbstractLink::getCurrentUserRanking($stud_id, $students);
                break;
            default:
                return array($sum, $rescount);
                break;
        }
    }

    /**
     * Lazy load function to get the database table of the student publications
     */
    private function get_studpub_table()
    {
        return $this->studpub_table = Database :: get_course_table(TABLE_STUDENT_PUBLICATION);
    }

    /**
     * Lazy load function to get the database table of the item properties
     */
    private function get_itemprop_table()
    {
        return $this->itemprop_table = Database :: get_course_table(TABLE_ITEM_PROPERTY);
    }

    public function needs_name_and_description()
    {
        return false;
    }

    public function get_name()
    {
        $this->get_exercise_data();
        $name = isset($this->exercise_data['title']) && !empty($this->exercise_data['title']) ? $this->exercise_data['title'] : get_lang('Untitled');
        return $name;
    }

    public function get_description()
    {
        $this->get_exercise_data();
        return isset($this->exercise_data['description']) ? $this->exercise_data['description'] : null;
    }

    public function get_test_id()
    {
        return 'DEBUG:ID';
    }

    public function get_link()
    {
        $session_id = api_get_session_id();
        $url = api_get_path(WEB_PATH).'main/work/work.php?'.api_get_cidreq_params($this->get_course_code(), $session_id).'&id='.$this->exercise_data['id'].'&gradebook=view';

        return $url;
    }

    /**
     * @return array
     */
    private function get_exercise_data()
    {
        $course_info = api_get_course_info($this->get_course_code());
        if (!isset($this->exercise_data)) {
            $sql = 'SELECT * FROM '.$this->get_studpub_table()."
                    WHERE
                        c_id ='".$course_info['real_id']."' AND
                        id = '".$this->get_ref_id()."' ";
            $query = Database::query($sql);
            $this->exercise_data = Database::fetch_array($query);

            // Try with iid
            if (empty($this->exercise_data)) {
                $sql = 'SELECT * FROM '.$this->get_studpub_table()."
                        WHERE
                            c_id ='".$course_info['real_id']."' AND
                            iid = '".$this->get_ref_id()."' ";
                $query = Database::query($sql);
                $this->exercise_data = Database::fetch_array($query);
            }
        }

        return $this->exercise_data;
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
        $data = $this->get_exercise_data();

        if (empty($data)) {
            return '';
        }
        $id = $data['id'];
        $sql = 'SELECT count(id) FROM '.$this->get_studpub_table().'
                WHERE 
                    c_id = "'.$this->course_id.'" AND 
                    id = '.$id.'';
        $result = Database::query($sql);
        $number = Database::fetch_row($result);
        return ($number[0] != 0);
    }

    public function get_icon_name()
    {
        return 'studentpublication';
    }

    public function save_linked_data()
    {
        $data = $this->get_exercise_data();

        if (empty($data)) {
            return '';
        }
        $id = $data['id'];

        $weight = (float) $this->get_weight();
        if (!empty($id)) {
            //Cleans works
            $sql = 'UPDATE '.$this->get_studpub_table().' 
                    SET weight= '.$weight.'
                    WHERE c_id = '.$this->course_id.' AND id ='.$id;
            Database::query($sql);
        }
    }

    public function delete_linked_data()
    {
        $data = $this->get_exercise_data();

        if (empty($data)) {
            return '';
        }

        if (!empty($id)) {
            //Cleans works
            $sql = 'UPDATE '.$this->get_studpub_table().' 
                    SET weight=0
                    WHERE c_id = '.$this->course_id.' AND id ='.$id;
            Database::query($sql);
        }
    }
}
