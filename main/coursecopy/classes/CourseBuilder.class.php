<?php
/* For licensing terms, see /license.txt */

require_once 'Course.class.php';
require_once 'Event.class.php';
require_once 'Link.class.php';
require_once 'ToolIntro.class.php';
require_once 'Document.class.php';
require_once 'ScormDocument.class.php';
require_once 'LinkCategory.class.php';
require_once 'CourseDescription.class.php';
require_once 'ForumPost.class.php';
require_once 'ForumTopic.class.php';
require_once 'Forum.class.php';
require_once 'ForumCategory.class.php';
require_once 'Quiz.class.php';
require_once 'QuizQuestion.class.php';
require_once 'CourseCopyTestCategory.php';
require_once 'CourseCopyLearnpath.class.php';
require_once 'Survey.class.php';
require_once 'SurveyQuestion.class.php';
require_once 'Glossary.class.php';
require_once 'CourseSession.class.php';
require_once 'wiki.class.php';
require_once 'Thematic.class.php';
require_once 'Attendance.class.php';
require_once 'Work.class.php';

require_once api_get_path(SYS_CODE_PATH).'exercice/question.class.php';

/**
 * Class which can build a course-object from a Chamilo-course.
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package chamilo.backup
 */
class CourseBuilder
{
    /** Course */
    public $course;

    /* With this array you can filter the tools you want to be parsed by
    default all tools are included */
    public $tools_to_build = array(
        'announcements',
        'attendance',
        'course_descriptions',
        'documents',
        'events',
        'forum_category',
        'forums',
        'forum_topics',
        'glossary',
        'quizzes',
        'test_category',
        'learnpaths',
        'links',
        'surveys',
        'tool_intro',
        'thematic',
        'wiki',
        'works'
    );

    /* With this array you can filter wich elements of the tools are going
    to be added in the course obj (only works with LPs) */
    public $specific_id_list = array();

    /**
     * Create a new CourseBuilder
     */
	public function __construct($type='', $course = null)
    {
        $_course = api_get_course_info();

        if (!empty($course['official_code'])){
            $_course = $course;
        }

        $this->course               = new Course();
        $this->course->code         = $_course['official_code'];
        $this->course->type         = $type;
        $this->course->path         = api_get_path(SYS_COURSE_PATH).$_course['path'].'/';
        $this->course->backup_path  = api_get_path(SYS_COURSE_PATH).$_course['path'];
        $this->course->encoding     = api_get_system_encoding(); //current platform encoding
        // db_name is deprecated (only one database now)
        $this->course->db_name      = $_course['dbName'];
        $this->course->info         = $_course;
    }

    /**
     *
     * @param array $array
     */
    public function set_tools_to_build($array)
    {
        $this->tools_to_build = $array;
    }

    /**
     *
     * @param array $array
     */
    public function set_tools_specific_id_list($array)
    {
        $this->specific_id_list = $array;
    }

    /**
     * Get the created course
     * @return course The course
     */
    public function get_course()
    {
        return $this->course;
    }

    /**
     * Build the course-object
     *
     * @param int      session_id
     * @param string   course_code
     * @param bool     true if you want to get the elements that exists in the course and
     *                 in the session, (session_id = 0 or session_id = X)
     */
	public function build($session_id = 0, $course_code = '', $with_base_content = false)
    {
        $table_link       = Database :: get_course_table(TABLE_LINKED_RESOURCES);
        $table_properties = Database :: get_course_table(TABLE_ITEM_PROPERTY);

        $course_info      = api_get_course_info($course_code);
        $course_id        = $course_info['real_id'];
        foreach ($this->tools_to_build as $tool) {
            $function_build = 'build_'.$tool;
            $specificIdList = isset($this->specific_id_list[$tool]) ? $this->specific_id_list[$tool] : null;

            $this->$function_build(
                $session_id,
                $course_code,
                $with_base_content,
                $specificIdList
            );
        }

        //TABLE_LINKED_RESOURCES is the "resource" course table, which is deprecated, apparently
        foreach ($this->course->resources as $type => $resources) {
            foreach ($resources as $id => $resource) {
                $sql = "SELECT * FROM $table_link
                        WHERE
                            c_id = $course_id AND
                            source_type = '".$resource->get_type()."' AND
                            source_id = '".$resource->get_id()."'";
                $res = Database::query($sql);
                while ($link = Database::fetch_object($res)) {
                    $this->course->resources[$type][$id]->add_linked_resource($link->resource_type, $link->resource_id);
                }
            }
        }
        // Once we've built the resources array a bit more, try to get items
        //  from the item_property table and order them in the "resources" array
        foreach ($this->course->resources as $type => $resources) {
            foreach ($resources as $id => $resource) {
                $tool = $resource->get_tool();
                if ($tool != null) {
                    $sql = "SELECT * FROM $table_properties
                            WHERE
                                c_id = $course_id AND
                                tool = '".$tool."' AND
                                ref='".$resource->get_id()."'";
                    $res = Database::query($sql);
                    $all_properties = array ();
                    while ($item_property = Database::fetch_array($res)) {
                        $all_properties[]= $item_property;
                    }
                    $this->course->resources[$type][$id]->item_properties = $all_properties;
                }
            }
        }
        return $this->course;
    }

    /**
     * Build the documents
     * @param int $session_id
     * @param string $course_code
     * @param bool $with_base_content
     * @param array $id_list
     */
    public function build_documents($session_id = 0, $course_code = '', $with_base_content = false, $id_list = array())
    {
        $course_info = api_get_course_info($course_code);
        $course_id = $course_info['real_id'];

        $table_doc = Database::get_course_table(TABLE_DOCUMENT);
        $table_prop = Database::get_course_table(TABLE_ITEM_PROPERTY);

        //Remove chat_files and shared_folder files
        $avoid_paths = " path NOT LIKE '/shared_folder%' AND
                         path NOT LIKE '/chat_files%' ";
        //$avoid_paths = " 1 = 1 ";

        if (!empty($course_code) && !empty($session_id)) {
            $session_id  = intval($session_id);
            if ($with_base_content) {
                $session_condition = api_get_session_condition($session_id, true, true);
            } else {
                $session_condition = api_get_session_condition($session_id, true);
            }

            if (!empty($this->course->type) && $this->course->type == 'partial') {
                $sql = "SELECT d.id, d.path, d.comment, d.title, d.filetype, d.size
                        FROM $table_doc d INNER JOIN $table_prop p
                        ON (p.ref = d.id AND d.c_id = p.c_id)
                        WHERE
                            d.c_id = $course_id AND
                            p.c_id = $course_id AND
                            tool = '".TOOL_DOCUMENT."' AND
                            p.visibility != 2 AND
                            path NOT LIKE '/images/gallery%' AND
                            $avoid_paths
                            $session_condition
                        ORDER BY path";
            } else {
                $sql = "SELECT d.id, d.path, d.comment, d.title, d.filetype, d.size
                        FROM $table_doc d INNER JOIN $table_prop p
                        ON (p.ref = d.id AND d.c_id = p.c_id)
                        WHERE
                            d.c_id = $course_id AND
                            p.c_id = $course_id AND
                            tool = '".TOOL_DOCUMENT."' AND
                            $avoid_paths AND
                            p.visibility != 2 $session_condition
                        ORDER BY path";
            }

            $db_result = Database::query($sql);
            while ($obj = Database::fetch_object($db_result)) {
                $doc = new Document($obj->id, $obj->path, $obj->comment, $obj->title, $obj->filetype, $obj->size);
                $this->course->add_resource($doc);
            }
        } else {
            if (!empty($this->course->type) && $this->course->type=='partial') {
                $sql = "SELECT d.id, d.path, d.comment, d.title, d.filetype, d.size
                        FROM $table_doc d INNER JOIN $table_prop p
                        ON (p.ref = d.id AND d.c_id = p.c_id)
                        WHERE
                            d.c_id = $course_id AND
                            p.c_id = $course_id AND
                            tool = '".TOOL_DOCUMENT."' AND
                            p.visibility != 2 AND
                            path NOT LIKE '/images/gallery%' AND
                            $avoid_paths AND
                            d.session_id = 0
                        ORDER BY path";
                var_dump($sql);
            } else {
                $sql = "SELECT d.id, d.path, d.comment, d.title, d.filetype, d.size
                        FROM $table_doc d INNER JOIN $table_prop p
                        ON (p.ref = d.id AND d.c_id = p.c_id)
                        WHERE
                            d.c_id = $course_id AND
                            p.c_id = $course_id AND
                            tool = '".TOOL_DOCUMENT."' AND
                            p.visibility != 2 AND
                            $avoid_paths AND
                            d.session_id = 0
                        ORDER BY path";
            }

            $db_result = Database::query($sql);
            while ($obj = Database::fetch_object($db_result)) {
                $doc = new Document(
                    $obj->id, $obj->path, $obj->comment, $obj->title, $obj->filetype, $obj->size
                );
                $this->course->add_resource($doc);
            }
        }
    }

    /**
     * Build the forums
     */
    public function build_forums($session_id = 0, $course_code = null, $with_base_content = false, $id_list = array())
    {
        $course_info     = api_get_course_info($course_code);
        $course_id         = $course_info['real_id'];

        $table = Database :: get_course_table(TABLE_FORUM);

        $sql = "SELECT * FROM $table WHERE c_id = $course_id ";
        $sql .= " ORDER BY forum_title, forum_category";
        $db_result = Database::query($sql);
        while ($obj = Database::fetch_object($db_result)) {
            $forum = new Forum($obj);
            $this->course->add_resource($forum);
        }
    }

    /**
     * Build a forum-category
     */
    public function build_forum_category($session_id = 0, $course_code = null, $with_base_content = false, $id_list = array())
    {
        $table = Database :: get_course_table(TABLE_FORUM_CATEGORY);
        $course_info     = api_get_course_info($course_code);
        $course_id         = $course_info['real_id'];

        $sql = "SELECT * FROM $table WHERE c_id = $course_id ORDER BY cat_title";
        $db_result = Database::query($sql);
        while ($obj = Database::fetch_object($db_result)) {
            $forum_category = new ForumCategory($obj);
            $this->course->add_resource($forum_category);
        }
    }

    /**
     * Build the forum-topics
     */
    public function build_forum_topics($session_id = 0, $course_code = null, $with_base_content = false, $id_list = array())
    {
        $table = Database :: get_course_table(TABLE_FORUM_THREAD);
        $course_info     = api_get_course_info($course_code);
        $course_id         = $course_info['real_id'];
        $sql = "SELECT * FROM $table WHERE c_id = $course_id
                ORDER BY thread_title ";
        $db_result = Database::query($sql);
        while ($obj = Database::fetch_object($db_result)) {
            $forum_topic = new ForumTopic($obj);
            $this->course->add_resource($forum_topic);
            $this->build_forum_posts($obj->thread_id, $obj->forum_id, true);
        }
    }

    /**
     * Build the forum-posts
     * TODO: All tree structure of posts should be built, attachments for example.
     */
    public function build_forum_posts($thread_id = null, $forum_id = null, $only_first_post = false)
    {
        $table = Database :: get_course_table(TABLE_FORUM_POST);
        $course_id = api_get_course_int_id();
        $sql = "SELECT * FROM $table WHERE c_id = $course_id ";
        if (!empty($thread_id) && !empty($forum_id)) {
            $forum_id = intval($forum_id);
            $thread_id = intval($thread_id);
            $sql     .= " AND thread_id = $thread_id AND forum_id = $forum_id ";
        }
        $sql .= " ORDER BY post_id ASC LIMIT 1";
        $db_result = Database::query($sql);
        while ($obj = Database::fetch_object($db_result)) {
            $forum_post = new ForumPost($obj);
            $this->course->add_resource($forum_post);
        }
    }

    /**
     * Build the links
     */
    public function build_links($session_id = 0, $course_code = '', $with_base_content = false, $id_list = array())
    {
        $course_info = api_get_course_info($course_code);
        $course_id         = $course_info['real_id'];

        $table = Database :: get_course_table(TABLE_LINK);
        $table_prop = Database :: get_course_table(TABLE_ITEM_PROPERTY);

        if (!empty($session_id) && !empty($course_code)) {
            $session_id = intval($session_id);
            if ($with_base_content) {
                $session_condition = api_get_session_condition($session_id, true, true);
            } else {
                $session_condition = api_get_session_condition($session_id, true);
            }
            $sql = "SELECT  l.id, l.title, l.url, l.description, l.category_id, l.on_homepage
                    FROM $table l, $table_prop p
                    WHERE l.c_id = $course_id AND p.c_id = $course_id AND p.ref=l.id AND p.tool = '".TOOL_LINK."' AND p.visibility != 2 $session_condition
                    ORDER BY l.display_order";
        } else {
            $sql = "SELECT l.id, l.title, l.url, l.description, l.category_id, l.on_homepage
                    FROM $table l, $table_prop p
                    WHERE l.c_id = $course_id AND p.c_id = $course_id AND p.ref=l.id AND p.tool = '".TOOL_LINK."' AND p.visibility != 2 AND l.session_id = 0
                    ORDER BY l.display_order";
        }

        $db_result = Database::query($sql);
        while ($obj = Database::fetch_object($db_result)) {
            $link = new Link($obj->id, $obj->title, $obj->url, $obj->description, $obj->category_id, $obj->on_homepage);
            $this->course->add_resource($link);

            if (!empty($course_code)) {
                $res = $this->build_link_category($obj->category_id,$course_code);
            } else {
                $res = $this->build_link_category($obj->category_id);
            }

            if($res > 0) {
                $this->course->resources[RESOURCE_LINK][$obj->id]->add_linked_resource(RESOURCE_LINKCATEGORY, $obj->category_id);
            }
        }
    }

    /**
     * Build tool intro
     */
    public function build_tool_intro($session_id = 0, $course_code = '', $with_base_content = false, $id_list = array())
    {
        $table = Database :: get_course_table(TABLE_TOOL_INTRO);
        $course_id = api_get_course_int_id();
        $sql = "SELECT * FROM $table WHERE c_id = $course_id ";
        $db_result = Database::query($sql);
        while ($obj = Database::fetch_object($db_result)) {
            $tool_intro = new ToolIntro($obj->id, $obj->intro_text);
            $this->course->add_resource($tool_intro);
        }
    }

    /**
     * Build a link category
     */
    public function build_link_category($id, $course_code = '')
    {
        $course_info = api_get_course_info($course_code);
        $course_id         = $course_info['real_id'];

        $link_cat_table = Database :: get_course_table(TABLE_LINK_CATEGORY);

        $sql = "SELECT * FROM $link_cat_table WHERE c_id = $course_id AND id = $id";
        $db_result = Database::query($sql);
        while ($obj = Database::fetch_object($db_result)) {
            $link_category = new LinkCategory($obj->id, $obj->category_title, $obj->description, $obj->display_order);
            $this->course->add_resource($link_category);
            return $id;
        }
        return 0;
    }

    /**
     * Build the Quizzes
     */
    public function build_quizzes($session_id = 0, $course_code = '', $with_base_content = false, $id_list = array())
    {
        $course_info = api_get_course_info($course_code);
        $table_qui = Database :: get_course_table(TABLE_QUIZ_TEST);
        $table_rel = Database :: get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $table_doc = Database :: get_course_table(TABLE_DOCUMENT);

        $course_id = $course_info['real_id'];

        if (!empty($course_code) && !empty($session_id)) {
            $session_id  = intval($session_id);
            if ($with_base_content) {
                $session_condition = api_get_session_condition($session_id, true, true);
            } else {
                $session_condition = api_get_session_condition($session_id, true);
            }
            $sql = "SELECT * FROM $table_qui WHERE c_id = $course_id AND active >=0 $session_condition";
            //select only quizzes with active = 0 or 1 (not -1 which is for deleted quizzes)
        } else {
            $sql = "SELECT * FROM $table_qui WHERE c_id = $course_id AND active >=0 AND session_id = 0";
             //select only quizzes with active = 0 or 1 (not -1 which is for deleted quizzes)
        }

        $db_result = Database::query($sql);
        while ($obj = Database::fetch_object($db_result)) {
            if (strlen($obj->sound) > 0) {
                $sql = "SELECT id FROM $table_doc WHERE c_id = $course_id AND path = '/audio/".$obj->sound."'";
                $res = Database::query($sql);
                $doc = Database::fetch_object($res);
                $obj->sound = $doc->id;
            }
            $quiz = new Quiz($obj);

            $sql = 'SELECT * FROM '.$table_rel.' WHERE c_id = '.$course_id.' AND exercice_id = '.$obj->id;
            $db_result2 = Database::query($sql);
            while ($obj2 = Database::fetch_object($db_result2)) {
                $quiz->add_question($obj2->question_id, $obj2->question_order);
            }
            $this->course->add_resource($quiz);
        }

        if (!empty($course_code)) {
            $this->build_quiz_questions($course_code);
        } else {
            $this->build_quiz_questions();
        }
    }

    /**
     * Build the Quiz-Questions
     */
    public function build_quiz_questions($course_code = null)
    {
        $course_info = api_get_course_info($course_code);
        $course_id   = $course_info['real_id'];

        $table_qui = Database :: get_course_table(TABLE_QUIZ_TEST);
        $table_rel = Database :: get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $table_que = Database :: get_course_table(TABLE_QUIZ_QUESTION);
        $table_ans = Database :: get_course_table(TABLE_QUIZ_ANSWER);

        // Building normal tests.
        $sql = "SELECT * FROM $table_que WHERE c_id = $course_id ";

        $db_result = Database::query($sql);
        while ($obj = Database::fetch_object($db_result)) {
            // find the question category
            // @todo : need to be adapted for multi category questions in 1.10
            $question_category_id = Testcategory::getCategoryForQuestion($obj->id, $course_id);
            // build the backup resource question object
            $question = new QuizQuestion(
                $obj->id,
                $obj->question,
                $obj->description,
                $obj->ponderation,
                $obj->type,
                $obj->position,
                $obj->picture,
                $obj->level,
                $obj->extra,
                $question_category_id
            );
            $sql = 'SELECT * FROM '.$table_ans.' WHERE c_id = '.$course_id.' AND question_id = '.$obj->id;
            $db_result2 = Database::query($sql);

            while ($obj2 = Database::fetch_object($db_result2)) {
                $question->add_answer(
                    $obj2->id,
                    $obj2->answer,
                    $obj2->correct,
                    $obj2->comment,
                    $obj2->ponderation,
                    $obj2->position,
                    $obj2->hotspot_coordinates,
                    $obj2->hotspot_type
                );
                if ($obj->type == MULTIPLE_ANSWER_TRUE_FALSE) {
                    $table_options	= Database::get_course_table(TABLE_QUIZ_QUESTION_OPTION);
                    $sql = 'SELECT * FROM '.$table_options.' WHERE c_id = '.$course_id.' AND question_id = '.$obj->id;
                    $db_result3 = Database::query($sql);
                    while ($obj3 = Database::fetch_object($db_result3)) {
                        $question_option = new QuizQuestionOption($obj3);
                        $question->add_option($question_option);
                    }
                }
            }
            $this->course->add_resource($question);
        }

        // Building a fictional test for collecting orphan questions.
        // When a course is emptied this option should be activated (true).
        $build_orphan_questions = !empty($_POST['recycle_option']);

        // 1st union gets the orphan questions from deleted exercises
        // 2nd union gets the orphan questions from question that were deleted in a exercise.

        $sql = " (
                    SELECT question_id, q.* FROM $table_que q INNER JOIN $table_rel r
                    ON (q.c_id = r.c_id AND q.id = r.question_id)
                    INNER JOIN $table_qui ex
                    ON (ex.id = r.exercice_id AND ex.c_id = r.c_id )
                    WHERE ex.c_id = $course_id AND ex.active = '-1'
                 )
                 UNION
                 (
                    SELECT question_id, q.* FROM $table_que q left
                    OUTER JOIN $table_rel r
                    ON (q.c_id = r.c_id AND q.id = r.question_id)
                    WHERE q.c_id = $course_id AND r.question_id is null
                 )
                 UNION
                 (
                    SELECT question_id, q.* FROM $table_que q
                    INNER JOIN $table_rel r
                    ON (q.c_id = r.c_id AND q.id = r.question_id)
                    WHERE r.c_id = $course_id AND (r.exercice_id = '-1' OR r.exercice_id = '0')
                 )
        ";

        $db_result = Database::query($sql);
        if (Database::num_rows($db_result) > 0) {
            $build_orphan_questions = true;
            $orphanQuestionIds = array();
            while ($obj = Database::fetch_object($db_result)) {

                // Orphan questions
                if (!empty($obj->question_id)) {
                    $obj->id = $obj->question_id;
                }

                // Avoid adding the same question twice
                if (!isset($this->course->resources[$obj->id])) {
                    // find the question category
                    // @todo : need to be adapted for multi category questions in 1.10
                    $question_category_id = Testcategory::getCategoryForQuestion($obj->id, $course_id);
                    $question = new QuizQuestion(
                        $obj->id,
                        $obj->question,
                        $obj->description,
                        $obj->ponderation,
                        $obj->type,
                        $obj->position,
                        $obj->picture,
                        $obj->level,
                        $obj->extra,
                        $question_category_id
                    );
                    $sql = "SELECT * FROM $table_ans WHERE c_id = $course_id AND question_id = ".$obj->id;
                    $db_result2 = Database::query($sql);
                    if (Database::num_rows($db_result2)) {
                        while ($obj2 = Database::fetch_object($db_result2)) {
                            $question->add_answer(
                                $obj2->id,
                                $obj2->answer,
                                $obj2->correct,
                                $obj2->comment,
                                $obj2->ponderation,
                                $obj2->position,
                                $obj2->hotspot_coordinates,
                                $obj2->hotspot_type
                            );
                        }
                        $orphanQuestionIds[] = $obj->id;
                    }
                    $this->course->add_resource($question);
                }
            }
        }

        if ($build_orphan_questions) {
            $obj = array(
                'id' => -1,
                'title' => get_lang('OrphanQuestions', ''),
                'type' => 2
            );
            $newQuiz = new Quiz((object)$obj);
            if (!empty($orphanQuestionIds)) {
                foreach ($orphanQuestionIds as $index => $orphanId) {
                    $order = $index + 1;
                    $newQuiz->add_question($orphanId, $order);
                }
            }
            $this->course->add_resource($newQuiz);
        }
    }

    /**
     * Build the orphan questions
     */
    public function build_quiz_orphan_questions()
    {
        $table_qui = Database :: get_course_table(TABLE_QUIZ_TEST);
        $table_rel = Database :: get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $table_que = Database :: get_course_table(TABLE_QUIZ_QUESTION);
        $table_ans = Database :: get_course_table(TABLE_QUIZ_ANSWER);

        $course_id = api_get_course_int_id();

        $sql = 'SELECT *
                FROM '.$table_que.' as questions
                LEFT JOIN '.$table_rel.' as quizz_questions
                ON questions.id=quizz_questions.question_id
                LEFT JOIN '.$table_qui.' as exercises
                ON quizz_questions.exercice_id = exercises.id
                WHERE   questions.c_id = quizz_questions.c_id AND
                        questions.c_id = exercises.c_id AND
                        exercises.c_id = '.$course_id.' AND
                        (quizz_questions.exercice_id IS NULL OR
                        exercises.active = -1)';
        $db_result = Database::query($sql);
        if (Database::num_rows($db_result) > 0) {
            // This is the fictional test for collecting orphan questions.
            $orphan_questions = new Quiz(-1, get_lang('OrphanQuestions', ''), '', 0, 0, 1, '', 0);

            $this->course->add_resource($orphan_questions);
            while ($obj = Database::fetch_object($db_result)) {
                $question = new QuizQuestion(
                    $obj->id,
                    $obj->question,
                    $obj->description,
                    $obj->ponderation,
                    $obj->type,
                    $obj->position,
                    $obj->picture,
                    $obj->level,
                    $obj->extra
                );
                $sql = 'SELECT * FROM '.$table_ans.' WHERE question_id = '.$obj->id;
                $db_result2 = Database::query($sql);
                while ($obj2 = Database::fetch_object($db_result2)) {
                    $question->add_answer(
                        $obj2->id,
                        $obj2->answer,
                        $obj2->correct,
                        $obj2->comment,
                        $obj2->ponderation,
                        $obj2->position,
                        $obj2->hotspot_coordinates,
                        $obj2->hotspot_type
                    );
                }
                $this->course->add_resource($question);
            }
        }
    }

    /**
     * Build the test category
     * $session_id, $course_code, $with_base_content, $this->specific_id_list[$tool]
     * @todo add course session
     */
    public function build_test_category($session_id = 0, $course_code = '', $with_base_content = false, $id_list = array())
    {
        $course_id = api_get_course_int_id();

        // get all test category in course
        $tab_test_categories_id = Testcategory::getCategoryListInfo("id", $course_id);
        foreach ($tab_test_categories_id as $test_category_id)
        {
            $test_category = new Testcategory($test_category_id);
            $copy_course_test_category = new CourseCopyTestcategory($test_category_id, $test_category->name, $test_category->description);
            $this->course->add_resource($copy_course_test_category);
        }
    }

    /**
     * Build the Surveys
     */
    public function build_surveys($session_id = 0, $course_code = '', $with_base_content = false, $id_list = array())
    {
        $table_survey = Database :: get_course_table(TABLE_SURVEY);
        $table_question = Database :: get_course_table(TABLE_SURVEY_QUESTION);
        $course_id = api_get_course_int_id();

        $sql = 'SELECT * FROM '.$table_survey.' WHERE c_id = '.$course_id.' AND session_id = 0 ';
        $db_result = Database::query($sql);
        while ($obj = Database::fetch_object($db_result)) {
            $survey = new Survey($obj->survey_id, $obj->code,$obj->title,
                                $obj->subtitle, $obj->author, $obj->lang,
                                $obj->avail_from, $obj->avail_till, $obj->is_shared,
                                $obj->template, $obj->intro, $obj->surveythanks,
                                $obj->creation_date, $obj->invited, $obj->answered,
                                $obj->invite_mail, $obj->reminder_mail);
            $sql = 'SELECT * FROM '.$table_question.' WHERE c_id = '.$course_id.' AND survey_id = '.$obj->survey_id;
            $db_result2 = Database::query($sql);
            while ($obj2 = Database::fetch_object($db_result2)){
                $survey->add_question($obj2->question_id);
            }
            $this->course->add_resource($survey);
        }
        $this->build_survey_questions();
    }

    /**
     * Build the Survey Questions
     */
    public function build_survey_questions() {
        $table_que = Database :: get_course_table(TABLE_SURVEY_QUESTION);
        $table_opt = Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);

        $course_id = api_get_course_int_id();

        $sql = 'SELECT * FROM '.$table_que.' WHERE c_id = '.$course_id.'  ';
        $db_result = Database::query($sql);
        while ($obj = Database::fetch_object($db_result)){
            $question = new SurveyQuestion(
                $obj->question_id,
                $obj->survey_id,
                $obj->survey_question,
                $obj->survey_question_comment,
                $obj->type,
                $obj->display,
                $obj->sort,
                $obj->shared_question_id,
                $obj->max_value
            );
            $sql = 'SELECT * FROM '.$table_opt.' WHERE c_id = '.$course_id.' AND question_id = '.$obj->question_id;
            $db_result2 = Database::query($sql);
            while ($obj2 = Database::fetch_object($db_result2)) {
                $question->add_answer($obj2->option_text, $obj2->sort);
            }
            $this->course->add_resource($question);
        }
    }

    /**
     * Build the announcements
     */
    public function build_announcements($session_id = 0, $course_code = '', $with_base_content = false, $id_list = array()) {
        $table = Database :: get_course_table(TABLE_ANNOUNCEMENT);
        $course_id = api_get_course_int_id();

        $sql = 'SELECT * FROM '.$table.' WHERE c_id = '.$course_id.' AND session_id = 0';
        $db_result = Database::query($sql);
        $table_attachment = Database :: get_course_table(TABLE_ANNOUNCEMENT_ATTACHMENT);
        while ($obj = Database::fetch_object($db_result)) {
            $sql = 'SELECT path, comment, filename, size  FROM '.$table_attachment.' WHERE c_id = '.$course_id.' AND announcement_id = '.$obj->id.'';
            $result = Database::query($sql);
            $attachment_obj = Database::fetch_object($result);
            $att_path = $att_filename = $att_size = $atth_comment = '';
            if (!empty($attachment_obj)) {
                $att_path         = $attachment_obj->path;
                $att_filename     = $attachment_obj->filename;
                $att_size         = $attachment_obj->size;
                $atth_comment     = $attachment_obj->comment;
            }
            $announcement = new Announcement(
                $obj->id,
                $obj->title,
                $obj->content,
                $obj->end_date,
                $obj->display_order,
                $obj->email_sent,
                $att_path,
                $att_filename,
                $att_size,
                $atth_comment
            );
            $this->course->add_resource($announcement);

        }
    }

    /**
     * Build the events
     */
    public function build_events($session_id = 0, $course_code = '', $with_base_content = false, $id_list = array())
    {
        $table = Database :: get_course_table(TABLE_AGENDA);
        $course_id = api_get_course_int_id();

        $sql = 'SELECT * FROM '.$table.' WHERE c_id = '.$course_id.' AND session_id = 0';
        $db_result = Database::query($sql);
        while ($obj = Database::fetch_object($db_result)) {
            $table_attachment = Database :: get_course_table(TABLE_AGENDA_ATTACHMENT);
            $sql = 'SELECT path, comment, filename, size  FROM '.$table_attachment.' WHERE c_id = '.$course_id.' AND agenda_id = '.$obj->id.'';
            $result = Database::query($sql);

            $attachment_obj = Database::fetch_object($result);
            $att_path = $att_filename = $att_size = $atth_comment = '';
            if (!empty($attachment_obj)) {
                $att_path         = $attachment_obj->path;
                $att_filename     = $attachment_obj->filename;
                $att_size         = $attachment_obj->size;
                $atth_comment     = $attachment_obj->comment;
            }
            $event = new Event($obj->id, $obj->title, $obj->content, $obj->start_date, $obj->end_date, $att_path, $att_filename, $att_size, $atth_comment, $obj->all_day);
            $this->course->add_resource($event);
        }
    }

    /**
     * Build the course-descriptions
     */
    public function build_course_descriptions($session_id = 0, $course_code = '', $with_base_content = false, $id_list = array())
    {
        $course_info    = api_get_course_info($course_code);
        $course_id         = $course_info['real_id'];

        $table = Database :: get_course_table(TABLE_COURSE_DESCRIPTION);

        if (!empty($session_id) && !empty($course_code)) {
            $session_id = intval($session_id);
            if ($with_base_content) {
                $session_condition = api_get_session_condition($session_id, true, true);
            } else {
                $session_condition = api_get_session_condition($session_id, true);
            }
            $sql = 'SELECT * FROM '.$table. ' WHERE c_id = '.$course_id.' '.$session_condition;
        } else {
            $table = Database :: get_course_table(TABLE_COURSE_DESCRIPTION);
            $sql = 'SELECT * FROM '.$table. ' WHERE c_id = '.$course_id.'  AND session_id = 0';
        }

        $db_result = Database::query($sql);
        while ($obj = Database::fetch_object($db_result)) {
            $cd = new CourseDescription($obj->id, $obj->title, $obj->content, $obj->description_type);
            $this->course->add_resource($cd);
        }
    }

    /**
     * Build the learnpaths
     */
    public function build_learnpaths($session_id = 0, $course_code = '', $with_base_content = false, $id_list = array())
    {
        $course_info     = api_get_course_info($course_code);
        $course_id         = $course_info['real_id'];
        $table_main     = Database::get_course_table(TABLE_LP_MAIN);
        $table_item     = Database::get_course_table(TABLE_LP_ITEM);
        $table_tool     = Database::get_course_table(TABLE_TOOL_LIST);

        if (!empty($session_id) && !empty($course_code)) {
            $session_id = intval($session_id);
            if ($with_base_content) {
                $session_condition = api_get_session_condition($session_id, true, true);
            } else {
                $session_condition = api_get_session_condition($session_id, true);
            }
            $sql = 'SELECT * FROM '.$table_main.' WHERE c_id = '.$course_id.'  '.$session_condition;
        } else {
            $sql = 'SELECT * FROM '.$table_main.' WHERE c_id = '.$course_id.' AND session_id = 0';
        }

        if (!empty($id_list)) {
            $id_list = array_map('intval', $id_list);
            $sql .=" AND id IN (".implode(', ', $id_list).") ";
        }

        $db_result = Database::query($sql);
        if ($db_result)
        while ($obj = Database::fetch_object($db_result)) {
            $items = array();
            $sql_items = "SELECT * FROM ".$table_item." WHERE c_id = '$course_id' AND lp_id = ".$obj->id;
            $db_items = Database::query($sql_items);
            while ($obj_item = Database::fetch_object($db_items)) {
                $item['id']                   = $obj_item->id;
                $item['item_type']            = $obj_item->item_type;
                $item['ref']                = $obj_item->ref;
                $item['title']                = $obj_item->title;
                $item['description']        = $obj_item->description;
                $item['path']                = $obj_item->path;
                $item['min_score']            = $obj_item->min_score;
                $item['max_score']            = $obj_item->max_score;
                $item['mastery_score']        = $obj_item->mastery_score;
                $item['parent_item_id']    = $obj_item->parent_item_id;
                $item['previous_item_id']  = $obj_item->previous_item_id;
                $item['next_item_id']        = $obj_item->next_item_id;
                $item['display_order']     = $obj_item->display_order;
                $item['prerequisite']       = $obj_item->prerequisite;
                $item['parameters']        = $obj_item->parameters;
                $item['launch_data']        = $obj_item->launch_data;
                $item['audio']                = $obj_item->audio;
                $items[] = $item;
            }

            $sql_tool = "SELECT id FROM $table_tool
                         WHERE
                            c_id = $course_id AND
                            (link LIKE '%lp_controller.php%lp_id=".$obj->id."%' AND image='scormbuilder.gif') AND
                            visibility = '1' ";
            $db_tool = Database::query($sql_tool);

            if (Database::num_rows($db_tool)) {
                $visibility = '1';
            } else {
                $visibility = '0';
            }

            $lp = new CourseCopyLearnpath(
                $obj->id,
                $obj->lp_type,
                $obj->name,
                $obj->path,
                $obj->ref,
                $obj->description,
                $obj->content_local,
                $obj->default_encoding,
                $obj->default_view_mod,
                $obj->prevent_reinit,
                $obj->force_commit,
                $obj->content_maker,
                $obj->display_order,
                $obj->js_lib,
                $obj->content_license,
                $obj->debug,
                $visibility,
                $obj->author,
                $obj->preview_image,
                $obj->use_max_score,
                $obj->autolunch,
                $obj->created_on,
                $obj->modified_on,
                $obj->publicated_on,
                $obj->expired_on,
                $obj->session_id,
                $items
            );
            $this->course->add_resource($lp);
        }

        //save scorm directory (previously build_scorm_documents())
        $i = 1;
        if ($dir=@opendir($this->course->backup_path.'/scorm')) {
            while($file=readdir($dir)) {
                if(is_dir($this->course->backup_path.'/scorm/'.$file) && !in_array($file,array('.','..'))) {
                    $doc = new ScormDocument($i++, '/'.$file, $file);
                    $this->course->add_resource($doc);
                }
            }
            closedir($dir);
        }
    }

    /**
     * Build the glossarys
     */
    public function build_glossary($session_id = 0, $course_code = '', $with_base_content = false, $id_list = array())
    {
        $course_info     = api_get_course_info($course_code);
        $table_glossary = Database :: get_course_table(TABLE_GLOSSARY);
        $course_id         = $course_info['real_id'];

        if (!empty($session_id) && !empty($course_code)) {
            $session_id = intval($session_id);
            if ($with_base_content) {
                $session_condition = api_get_session_condition($session_id, true, true);
            } else {
                $session_condition = api_get_session_condition($session_id, true);
            }
            //@todo check this queries are the same ...
            if (!empty($this->course->type) && $this->course->type=='partial') {
                $sql = 'SELECT * FROM '.$table_glossary.' g WHERE g.c_id = '.$course_id.' '.$session_condition;
            } else {
                $sql = 'SELECT * FROM '.$table_glossary.' g WHERE g.c_id = '.$course_id.' '.$session_condition;
            }
        } else {
            $table_glossary = Database :: get_course_table(TABLE_GLOSSARY);
            //@todo check this queries are the same ... ayayay
            if (!empty($this->course->type) && $this->course->type=='partial') {
                $sql = 'SELECT * FROM '.$table_glossary.' g WHERE g.c_id = '.$course_id.' AND session_id = 0';
            } else {
                $sql = 'SELECT * FROM '.$table_glossary.' g WHERE g.c_id = '.$course_id.' AND session_id = 0';
            }
        }
        $db_result = Database::query($sql);
        while ($obj = Database::fetch_object($db_result)) {
            $doc = new Glossary($obj->glossary_id, $obj->name, $obj->description, $obj->display_order);
            $this->course->add_resource($doc);
        }
    }

    /*
     * build session course by jhon
     * */
    public function build_session_course()
    {
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $tbl_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $list_course = Database::get_course_list();
        $list = array();
        foreach($list_course as $_course) {
            $this->course = new Course();
            $this->course->code = $_course['code'];
            $this->course->type = 'partial';
            $this->course->path = api_get_path(SYS_COURSE_PATH).$_course['directory'].'/';
            $this->course->backup_path = api_get_path(SYS_COURSE_PATH).$_course['directory'];
            $this->course->encoding = api_get_system_encoding(); //current platform encoding
            $code_course = $_course['code'];
            $sql_session = "SELECT id, name, course_code  FROM $tbl_session_course
                INNER JOIN  $tbl_session ON id_session = id
                WHERE course_code = '$code_course' ";
            $query_session = Database::query($sql_session);
            while ($rows_session = Database::fetch_assoc($query_session)) {
                $session = new CourseSession($rows_session['id'], $rows_session['name']);
                $this->course->add_resource($session);
            }
            $list[] = $this->course;
        }
        return $list;
    }

    /**
     * @param int $session_id
     * @param string $course_code
     * @param bool $with_base_content
     * @param array $id_list
     */
    public function build_wiki($session_id = 0, $course_code = '', $with_base_content = false, $id_list = array())
    {
        $course_info     = api_get_course_info($course_code);
        $tbl_wiki         = Database::get_course_table(TABLE_WIKI);

        $course_id         = $course_info['real_id'];

        if (!empty($session_id) && !empty($course_code)) {
             $session_id = intval($session_id);
            if ($with_base_content) {
                $session_condition = api_get_session_condition($session_id, true, true);
            } else {
                $session_condition = api_get_session_condition($session_id, true);
            }
            $sql = 'SELECT * FROM ' . $tbl_wiki . ' WHERE c_id = '.$course_id.' '.$session_condition;
        } else {
            $tbl_wiki = Database::get_course_table(TABLE_WIKI);
            $sql = 'SELECT * FROM ' . $tbl_wiki . ' WHERE c_id = '.$course_id.' AND session_id = 0';
        }
        $db_result = Database::query($sql);
        while ($obj = Database::fetch_object($db_result)) {
            $wiki = new Wiki($obj->id, $obj->page_id, $obj->reflink, $obj->title, $obj->content, $obj->user_id, $obj->group_id, $obj->dtime, $obj->progress, $obj->version);
            $this->course->add_resource($wiki);
        }
    }

    /**
    * Build the Surveys
    */
    public function build_thematic($session_id = 0, $course_code = '', $with_base_content = false, $id_list = array())
    {
        $table_thematic            = Database :: get_course_table(TABLE_THEMATIC);
        $table_thematic_advance = Database :: get_course_table(TABLE_THEMATIC_ADVANCE);
        $table_thematic_plan    = Database :: get_course_table(TABLE_THEMATIC_PLAN);

        $session_id = intval($session_id);
        if ($with_base_content) {
            $session_condition = api_get_session_condition($session_id, true, true);
        } else {
            $session_condition = api_get_session_condition($session_id, true);
        }

        $course_id = api_get_course_int_id();

        $sql = "SELECT * FROM $table_thematic WHERE c_id = $course_id $session_condition ";
        $db_result = Database::query($sql);
        while ($row = Database::fetch_array($db_result,'ASSOC')) {
            $thematic = new Thematic($row);
            $sql = 'SELECT * FROM '.$table_thematic_advance.' WHERE c_id = '.$course_id.' AND thematic_id = '.$row['id'];

            $result = Database::query($sql);
            while ($sub_row = Database::fetch_array($result,'ASSOC')) {
                $thematic->add_thematic_advance($sub_row);
            }

            $items  = api_get_item_property_by_tool('thematic_plan', api_get_course_id(), $session_id);
            //$items_from_session = api_get_item_property_by_tool('thematic_plan', api_get_course_id(), api_get_session_id());

            $thematic_plan_id_list = array();
            if (!empty($items)) {
                foreach($items as $item) {
                    $thematic_plan_id_list[] = $item['ref'];
                    //$thematic_plan_complete_list[$item['ref']] = $item;
                }
            }
            //$sql = 'SELECT * FROM '.$table_thematic_plan.' WHERE c_id = '.$course_id.' AND thematic_id = '.$row['id'];
            if (count($thematic_plan_id_list) > 0) {
                $sql = "SELECT tp.*
                        FROM $table_thematic_plan tp
                            INNER JOIN $table_thematic t ON (t.id=tp.thematic_id)
                        WHERE
                            t.c_id = $course_id AND
                            tp.c_id = $course_id AND
                            thematic_id = {$row['id']}  AND
                            tp.id IN (".implode(', ', $thematic_plan_id_list).") ";

                $result = Database::query($sql);
                while ($sub_row = Database::fetch_array($result,'ASSOC')) {
                    $thematic->add_thematic_plan($sub_row);
                }
            }
            $this->course->add_resource($thematic);
        }
    }

    /**
    * Build the attendances
    */
    public function build_attendance($session_id = 0, $course_code = '', $with_base_content = false, $id_list = array())
    {
        $table_attendance            = Database :: get_course_table(TABLE_ATTENDANCE);
        $table_attendance_calendar  = Database :: get_course_table(TABLE_ATTENDANCE_CALENDAR);

        $course_id = api_get_course_int_id();

        $sql = 'SELECT * FROM '.$table_attendance.' WHERE c_id = '.$course_id.' AND session_id = 0 ';
        $db_result = Database::query($sql);
        while ($row = Database::fetch_array($db_result,'ASSOC')) {
            $obj = new Attendance($row);
            $sql = 'SELECT * FROM '.$table_attendance_calendar.' WHERE c_id = '.$course_id.' AND attendance_id = '.$row['id'];

            $result = Database::query($sql);
            while ($sub_row = Database::fetch_array($result,'ASSOC')) {
                $obj->add_attendance_calendar($sub_row);
            }
            $this->course->add_resource($obj);
        }
    }

    /**
     * Build the works (or "student publications", or "assignments")
     */
    public function build_works($session_id = 0, $course_code = '', $with_base_content = false, $id_list = array())
    {
        $table_work  = Database :: get_course_table(TABLE_STUDENT_PUBLICATION);
        //$table_work_assignment  = Database :: get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);

        $course_id = api_get_course_int_id();

        $sql = 'SELECT * FROM '.$table_work.'
                WHERE
                    c_id = '.$course_id.' AND
                    session_id = 0 AND
                    filetype = \'folder\' AND
                    parent_id = 0 AND
                    active = 1';
        $db_result = Database::query($sql);
        while ($row = Database::fetch_array($db_result,'ASSOC')) {
            $obj = new Work($row);
            $this->course->add_resource($obj);
        }
    }
}
