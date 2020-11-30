<?php
/* For licensing terms, see /license.txt */

/**
 * This file contains a class used like library provides functions for
 * course description tool. It's also used like model to
 * course_description_controller (MVC pattern).
 *
 * @author Christian Fasanando <christian1827@gmail.com>
 */

/**
 * Class CourseDescription course descriptions.
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

    /**
     * Constructor.
     */
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
        $sql = "SELECT * FROM $table
                WHERE c_id = $course_id AND session_id = '0'";
        $sql_result = Database::query($sql);
        $results = [];
        while ($row = Database::fetch_array($sql_result)) {
            $desc_tmp = new CourseDescription();
            $desc_tmp->set_id($row['id']);
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
     * @return array
     */
    public function get_description_data()
    {
        $table = Database::get_course_table(TABLE_COURSE_DESCRIPTION);
        $condition_session = api_get_session_condition(
            $this->session_id,
            true,
            true
        );
        $course_id = $this->course_id ?: api_get_course_int_id();

        if (empty($course_id)) {
            return [];
        }

        $sql = "SELECT * FROM $table
		        WHERE c_id = $course_id $condition_session
		        ORDER BY id ";
        $rs = Database::query($sql);
        $data = [];
        while ($description = Database::fetch_array($rs)) {
            $data['descriptions'][$description['id']] = $description;
        }

        return $data;
    }

    /**
     * Get all data by description and session id,
     * first you must set session_id property with the object CourseDescription.
     *
     * @param int    $description_type Description type
     * @param string $courseId         Course code (optional)
     * @param int    $session_id       Session id (optional)
     *
     * @return array List of fields from the descriptions found of the given type
     */
    public function get_data_by_description_type(
        $description_type,
        $courseId = null,
        $session_id = null
    ) {
        $table = Database::get_course_table(TABLE_COURSE_DESCRIPTION);
        $courseId = (int) $courseId;

        if (empty($courseId)) {
            $courseId = api_get_course_int_id();
        }

        if (!isset($session_id)) {
            $session_id = $this->session_id;
        }
        $condition_session = api_get_session_condition($session_id);
        $description_type = (int) $description_type;

        $sql = "SELECT * FROM $table
		        WHERE
		            c_id = $courseId AND
		            description_type = '$description_type'
		            $condition_session ";
        $rs = Database::query($sql);
        $data = [];
        if ($description = Database::fetch_array($rs)) {
            $data['description_title'] = $description['title'];
            $data['description_content'] = $description['content'];
            $data['progress'] = $description['progress'];
            $data['id'] = $description['id'];
        }

        return $data;
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
        $table = Database::get_course_table(TABLE_COURSE_DESCRIPTION);
        $course_id = api_get_course_int_id();
        $id = (int) $id;

        if (!isset($session_id)) {
            $session_id = $this->session_id;
        }
        $condition_session = api_get_session_condition($session_id);
        if (!empty($course_code)) {
            $course_info = api_get_course_info($course_code);
            $course_id = $course_info['real_id'];
        }

        $sql = "SELECT * FROM $table
		        WHERE c_id = $course_id AND id='$id' $condition_session ";
        $rs = Database::query($sql);
        $data = [];
        if ($description = Database::fetch_array($rs)) {
            $data['description_type'] = $description['description_type'];
            $data['description_title'] = $description['title'];
            $data['description_content'] = $description['content'];
            $data['progress'] = $description['progress'];
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
        $course_id = api_get_course_int_id();

        $sql = "SELECT MAX(description_type) as MAX
                FROM $table
		        WHERE c_id = $course_id AND session_id='".$this->session_id."'";
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
        $table = Database::get_course_table(TABLE_COURSE_DESCRIPTION);

        $params = [
            'c_id' => $course_id,
            'description_type' => $this->description_type,
            'title' => $this->title,
            'content' => $this->content,
            'progress' => intval($this->progress),
            'session_id' => $this->session_id,
        ];

        $last_id = Database::insert($table, $params);

        if ($last_id > 0) {
            $sql = "UPDATE $table SET id = iid WHERE iid = $last_id";
            Database::query($sql);

            // insert into item_property
            api_item_property_update(
                api_get_course_info(),
                TOOL_COURSE_DESCRIPTION,
                $last_id,
                'CourseDescriptionAdded',
                api_get_user_id()
            );
        }

        return $last_id > 0 ? 1 : 0;
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

        if ($this->id > 0) {
            // Insert into item_property
            api_item_property_update(
                api_get_course_info(),
                TOOL_COURSE_DESCRIPTION,
                $this->id,
                'CourseDescriptionUpdated',
                api_get_user_id()
            );
        }

        return 1;
    }

    /**
     * Delete a description, first you must set description_type and session_id
     * properties with the object CourseDescription.
     *
     * @return int affected rows
     */
    public function delete()
    {
        $table = Database::get_course_table(TABLE_COURSE_DESCRIPTION);
        $course_id = api_get_course_int_id();
        $sql = "DELETE FROM $table
			 	WHERE
			 	    c_id = $course_id AND
			 	    id = '".intval($this->id)."' AND
			 	    session_id = '".intval($this->session_id)."'";
        $result = Database::query($sql);
        $affected_rows = Database::affected_rows($result);
        if ($this->id > 0) {
            //insert into item_property
            api_item_property_update(
                api_get_course_info(),
                TOOL_COURSE_DESCRIPTION,
                $this->id,
                'CourseDescriptionDeleted',
                api_get_user_id()
            );
        }

        return $affected_rows;
    }

    /**
     * Get description titles by default.
     *
     * @return array
     */
    public function get_default_description_title()
    {
        $default_description_titles = [];
        $default_description_titles[1] = get_lang('GeneralDescription');
        $default_description_titles[2] = get_lang('Objectives');
        $default_description_titles[3] = get_lang('Topics');
        $default_description_titles[4] = get_lang('Methodology');
        $default_description_titles[5] = get_lang('CourseMaterial');
        $default_description_titles[6] = get_lang('HumanAndTechnicalResources');
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
        $question[1] = get_lang('GeneralDescriptionQuestions');
        $question[2] = get_lang('ObjectivesQuestions');
        $question[3] = get_lang('TopicsQuestions');
        $question[4] = get_lang('MethodologyQuestions');
        $question[5] = get_lang('CourseMaterialQuestions');
        $question[6] = get_lang('HumanAndTechnicalResourcesQuestions');
        $question[7] = get_lang('AssessmentQuestions');

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
        $information[1] = get_lang('GeneralDescriptionInformation');
        $information[2] = get_lang('ObjectivesInformation');
        $information[3] = get_lang('TopicsInformation');
        $information[4] = get_lang('MethodologyInformation');
        $information[5] = get_lang('CourseMaterialInformation');
        $information[6] = get_lang('HumanAndTechnicalResourcesInformation');
        $information[7] = get_lang('AssessmentInformation');

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
