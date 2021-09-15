<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CCourseDescription;

/**
 * This file contains a class used like library provides functions for
 * course description tool. It's also used like model to
 * course_description_controller (MVC pattern).
 *
 * @author Christian Fasanando <christian1827@gmail.com>
 */
class CourseDescription
{
    private $id;
    private $course_id;
    private $title;
    private $content;
    private $session_id;
    private $description_type;
    private $progress;

    public function __construct()
    {
    }

    /**
     * Returns an array of objects of type CourseDescription corresponding to
     * a specific course, without session ids (session id = 0).
     *
     * @param int $course_id
     *
     * @return array Array of CourseDescriptions
     */
    public static function get_descriptions($course_id)
    {
        $course_id = (int) $course_id;
        // Get course code
        $course_info = api_get_course_info_by_id($course_id);
        if (!empty($course_info)) {
            $course_id = $course_info['real_id'];
        } else {
            return [];
        }
        $table = Database::get_course_table(TABLE_COURSE_DESCRIPTION);
        $sql = "SELECT * FROM $table";
        $sql_result = Database::query($sql);
        $results = [];
        while ($row = Database::fetch_array($sql_result)) {
            $desc_tmp = new CourseDescription();
            $desc_tmp->set_id($row['iid']);
            $desc_tmp->set_title($row['title']);
            $desc_tmp->set_content($row['content']);
            $desc_tmp->set_session_id($row['session_id']);
            $desc_tmp->set_description_type($row['description_type']);
            $desc_tmp->set_progress($row['progress']);
            $results[] = $desc_tmp;
        }

        return $results;
    }

    /**
     * Get all data of course description by session id,
     * first you must set session_id property with the object CourseDescription.
     *
     * @return CCourseDescription[]
     */
    public function get_description_data()
    {
        $repo = Container::getCourseDescriptionRepository();
        $course_id = $this->course_id ?: api_get_course_int_id();

        $course = api_get_course_entity($course_id);
        $session = api_get_session_entity($this->session_id);

        $qb = $repo->getResourcesByCourse($course, $session);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get all data by description and session id,
     * first you must set session_id property with the object CourseDescription.
     *
     * @param int $description_type Description type
     * @param int $courseId         Course code (optional)
     * @param int $session_id       Session id (optional)
     *
     * @return array List of fields from the descriptions found of the given type
     */
    public function get_data_by_description_type(
        $description_type,
        $courseId = null,
        $session_id = null
    ) {
        $result = Container::getCourseDescriptionRepository()->findByTypeInCourse(
            (int) $description_type,
            api_get_course_entity($courseId),
            api_get_session_entity($session_id),
            api_get_group_entity()
        );

        if (empty($result)) {
            return [];
        }

        $description = $result[0];

        return [
            'description_title' => $description->getTitle(),
            'description_content' => $description->getContent(),
            'progress' => $description->getProgress(),
            'iid' => $description->getIid(),
        ];
    }

    /**
     * @param int    $id
     * @param string $course_code
     * @param int    $session_id
     *
     * @return array
     */
    public function get_data_by_id($id, $course_code = '', $session_id = null)
    {
        $description = Container::getCourseDescriptionRepository()->find($id);

        $data = [];

        if ($description) {
            $data['description_type'] = $description->getDescriptionType();
            $data['description_title'] = $description->getTitle();
            $data['description_content'] = $description->getContent();
            $data['progress'] = $description->getProgress();
        }

        return $data;
    }

    /**
     * Get maximum description type by session id,
     * first you must set session_id properties with the object CourseDescription.
     *
     * @return int maximum description time adding one
     */
    public function get_max_description_type()
    {
        $table = Database::get_course_table(TABLE_COURSE_DESCRIPTION);

        $sql = "SELECT MAX(description_type) as MAX
                FROM $table
		        ";
        $rs = Database::query($sql);
        $max = Database::fetch_array($rs);

        if ($max['MAX'] >= 8) {
            $description_type = 8;
        } else {
            $description_type = $max['MAX'] + 1;
        }

        if ($description_type < ADD_BLOCK) {
            $description_type = ADD_BLOCK;
        }

        return $description_type;
    }

    /**
     * Insert a description to the course_description table,
     * first you must set description_type, title, content, progress and
     * session_id properties with the object CourseDescription.
     *
     * @return int affected rows
     */
    public function insert()
    {
        if (empty($this->course_id)) {
            $course_id = api_get_course_int_id();
        } else {
            $course_id = $this->course_id;
        }

        $courseDescription = new CCourseDescription();
        $courseDescription
            ->setTitle($this->title)
            ->setContent($this->content)
            ->setProgress((int) $this->progress)
            ->setDescriptionType((int) $this->description_type)
        ;

        $course = api_get_course_entity($course_id);
        $session = api_get_session_entity($this->session_id);
        $courseDescription->setParent($course);
        $courseDescription->addCourseLink($course, $session);

        $repo = Container::getCourseDescriptionRepository();
        $repo->create($courseDescription);

        return true;
    }

    /**
     * Update a description, first you must set description_type, title, content, progress
     * and session_id properties with the object CourseDescription.
     *
     * @return int affected rows
     */
    public function update()
    {
        $table = Database::get_course_table(TABLE_COURSE_DESCRIPTION);
        $params = [
            'title' => $this->title,
            'content' => $this->content,
            'progress' => intval($this->progress),
        ];

        Database::update(
            $table,
            $params,
            [
                'id = ? AND session_id = ? AND c_id = ?' => [
                    $this->id,
                    $this->session_id,
                    $this->course_id ? $this->course_id : api_get_course_int_id(),
                ],
            ]
        );

        return 1;
    }

    /**
     * Delete a description, first you must set description_type and session_id
     * properties with the object CourseDescription.
     *
     * @return int affected rows
     */
    public function delete($id)
    {
        $repo = Container::getCourseDescriptionRepository();

        /** @var CCourseDescription $courseDescription */
        $courseDescription = $repo->find($id);
        if ($courseDescription) {
            $repo->delete($courseDescription);

            return true;
        }

        return false;
    }

    /**
     * Get description titles by default.
     *
     * @return array
     */
    public function get_default_description_title()
    {
        $default_description_titles = [];
        $default_description_titles[1] = get_lang('Description');
        $default_description_titles[2] = get_lang('Objectives');
        $default_description_titles[3] = get_lang('Topics');
        $default_description_titles[4] = get_lang('Methodology');
        $default_description_titles[5] = get_lang('Course material');
        $default_description_titles[6] = get_lang('Resources');
        $default_description_titles[7] = get_lang('Assessment');
        $default_description_titles[8] = get_lang('Other');

        return $default_description_titles;
    }

    /**
     * Get description titles editable by default.
     *
     * @return array
     */
    public function get_default_description_title_editable()
    {
        $default_description_title_editable = [];
        $default_description_title_editable[1] = true;
        $default_description_title_editable[2] = true;
        $default_description_title_editable[3] = true;
        $default_description_title_editable[4] = true;
        $default_description_title_editable[5] = true;
        $default_description_title_editable[6] = true;
        $default_description_title_editable[7] = true;
        //$default_description_title_editable[8] = true;

        return $default_description_title_editable;
    }

    /**
     * Get description icons by default.
     *
     * @return array
     */
    public function get_default_description_icon()
    {
        $default_description_icon = [];
        $default_description_icon[1] = 'info.png';
        $default_description_icon[2] = 'objective.png';
        $default_description_icon[3] = 'topics.png';
        $default_description_icon[4] = 'strategy.png';
        $default_description_icon[5] = 'laptop.png';
        $default_description_icon[6] = 'teacher.png';
        $default_description_icon[7] = 'assessment.png';
        $default_description_icon[8] = 'wizard.png';

        return $default_description_icon;
    }

    /**
     * Get questions by default for help.
     *
     * @return array
     */
    public function get_default_question()
    {
        $question = [];
        $question[1] = get_lang('DescriptionQuestions');
        $question[2] = get_lang('What should the end results be when the learner has completed the course? What are the activities performed during the course?');
        $question[3] = get_lang('How does the course progress? Where should the learner pay special care? Are there identifiable problems in understanding different areas? How much time should one dedicate to the different areas of the course?');
        $question[4] = get_lang('What methods and activities help achieve the objectives of the course?  What would the schedule be?');
        $question[5] = get_lang('Course materialQuestions');
        $question[6] = get_lang('ResourcesQuestions');
        $question[7] = get_lang('How will learners be assessed? Are there strategies to develop in order to master the topic?');
        //$question[8]= get_lang('What is the current progress you have reached with your learners inside your course? How much do you think is remaining in comparison to the complete program?');

        return $question;
    }

    /**
     * Get informations by default for help.
     *
     * @return array
     */
    public function get_default_information()
    {
        $information = [];
        $information[1] = get_lang('DescriptionInformation');
        $information[2] = get_lang('What are the objectives of the course (competences, skills, outcomes)?');
        $information[3] = get_lang('List of topics included in the training. Importance of each topic. Level of difficulty. Structure and inter-dependence of the different parts.');
        $information[4] = get_lang('Presentation of the activities (conference, papers, group research, labs...).');
        $information[5] = get_lang('Course materialInformation');
        $information[6] = get_lang('ResourcesInformation');
        $information[7] = get_lang('Criteria for skills acquisition.');
        //$information[8]= get_lang('The thematic advance tool allows you to organize your course through time.');

        return $information;
    }

    /**
     * Set description id.
     */
    public function set_id($id)
    {
        $this->id = $id;
    }

    /**
     * Set description's course id.
     *
     * @param int $id Course ID
     */
    public function set_course_id($id)
    {
        $this->course_id = intval($id);
    }

    /**
     * Set description title.
     *
     * @param string $title
     */
    public function set_title($title)
    {
        $this->title = $title;
    }

    /**
     * Set description content.
     *
     * @param string $content
     */
    public function set_content($content)
    {
        $this->content = $content;
    }

    /**
     * Set description session id.
     *
     * @param int $session_id
     */
    public function set_session_id($session_id)
    {
        $this->session_id = $session_id;
    }

    /**
     * Set description type.
     */
    public function set_description_type($description_type)
    {
        $this->description_type = $description_type;
    }

    /**
     * Set progress of a description.
     *
     * @param string $progress
     */
    public function set_progress($progress)
    {
        $this->progress = $progress;
    }

    /**
     * get description id.
     *
     * @return int
     */
    public function get_id()
    {
        return $this->id;
    }

    /**
     * get description title.
     *
     * @return string
     */
    public function get_title()
    {
        return $this->title;
    }

    /**
     * get description content.
     *
     * @return string
     */
    public function get_content()
    {
        return $this->content;
    }

    /**
     * get session id.
     *
     * @return int
     */
    public function get_session_id()
    {
        return $this->session_id;
    }

    /**
     * get description type.
     *
     * @return int
     */
    public function get_description_type()
    {
        return $this->description_type;
    }

    /**
     * get progress of a description.
     *
     * @return int
     */
    public function get_progress()
    {
        return $this->progress;
    }
}
