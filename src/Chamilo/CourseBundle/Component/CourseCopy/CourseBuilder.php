<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy;

use Chamilo\CourseBundle\Component\CourseCopy\Resources\Asset;
use Database;
use TestCategory;
use Category;
use CourseManager;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Announcement;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Attendance;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\CalendarEvent;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\CourseCopyLearnpath;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\CourseCopyTestCategory;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\CourseDescription;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\CourseSession;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Document;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Forum;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\ForumCategory;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\ForumPost;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\ForumTopic;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Glossary;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\GradeBookBackup;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Link;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\LinkCategory;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Quiz;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\QuizQuestion;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\QuizQuestionOption;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\ScormDocument;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Survey;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\SurveyInvitation;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\SurveyQuestion;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Thematic;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\ToolIntro;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Wiki;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Work;

use \Link as LinkManager;

/**
 * Class CourseBuilder
 * Builds a course-object from a Chamilo-course.
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package chamilo.backup
 */
class CourseBuilder
{
    /** @var Course */
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
        'works',
        'gradebook'
    );

    /* With this array you can filter wich elements of the tools are going
    to be added in the course obj (only works with LPs) */
    public $specific_id_list = array();

    /**
     * Create a new CourseBuilder
     * @param string $type
     * @param null $course
     */
    public function __construct($type = '', $course = null)
    {
        $_course = api_get_course_info();

        if (!empty($course['official_code'])) {
            $_course = $course;
        }

        $this->course = new Course();
        $this->course->code = $_course['code'];
        $this->course->type = $type;
        $this->course->path = api_get_path(SYS_COURSE_PATH).$_course['path'].'/';
        $this->course->backup_path = api_get_path(SYS_COURSE_PATH).$_course['path'];
        $this->course->encoding = api_get_system_encoding();
        $this->course->info = $_course;
    }

    /**
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
     * @param int      $session_id
     * @param string   $courseCode
     * @param bool     true if you want to get the elements that exists in the course and
     *                 in the session, (session_id = 0 or session_id = X)
     * @return Course The course object structure
     */
    public function build(
        $session_id = 0,
        $courseCode = '',
        $with_base_content = false
    ) {
        $table_properties = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $course = api_get_course_info($courseCode);
        $courseId = $course['real_id'];

        foreach ($this->tools_to_build as $tool) {
            $function_build = 'build_'.$tool;
            $specificIdList = isset($this->specific_id_list[$tool]) ? $this->specific_id_list[$tool] : null;

            $this->$function_build(
                $session_id,
                $courseId,
                $with_base_content,
                $specificIdList
            );
        }

        // Add asset
        if (basename($course['course_image_source'] != 'course.png')) {
            // Add course image courses/XXX/course-pic85x85.png
            $asset = new Asset(
                $course['course_image_source'],
                basename($course['course_image_source']),
                basename($course['course_image_source'])
            );
            $this->course->add_resource($asset);

            $asset = new Asset(
                $course['course_image_large_source'],
                basename($course['course_image_large_source']),
                basename($course['course_image_large_source'])
            );
            $this->course->add_resource($asset);
        }

        // Once we've built the resources array a bit more, try to get items
        //  from the item_property table and order them in the "resources" array
        foreach ($this->course->resources as $type => $resources) {
            foreach ($resources as $id => $resource) {
                $tool = $resource->get_tool();
                if ($tool != null) {
                    $sql = "SELECT * FROM $table_properties
                            WHERE
                                c_id = $courseId AND
                                tool = '".$tool."' AND
                                ref = '".$resource->get_id()."'";
                    $res = Database::query($sql);
                    $all_properties = array();
                    while ($item_property = Database::fetch_array($res)) {
                        $all_properties[] = $item_property;
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
     * @param int $courseId
     * @param bool $with_base_content
     * @param array $id_list
     */
    public function build_documents(
        $session_id = 0,
        $courseId = 0,
        $with_base_content = false,
        $id_list = array()
    ) {
        $table_doc = Database::get_course_table(TABLE_DOCUMENT);
        $table_prop = Database::get_course_table(TABLE_ITEM_PROPERTY);

        // Remove chat_files and shared_folder files
        $avoid_paths = " path NOT LIKE '/shared_folder%' AND
                         path NOT LIKE '/chat_files%' ";

        if (!empty($courseId) && !empty($session_id)) {
            $session_id = intval($session_id);
            if ($with_base_content) {
                $session_condition = api_get_session_condition(
                    $session_id,
                    true,
                    true,
                    'd.session_id'
                );
            } else {
                $session_condition = api_get_session_condition(
                    $session_id,
                    true,
                    false,
                    'd.session_id'
                );
            }

            if (!empty($this->course->type) && $this->course->type == 'partial') {
                $sql = "SELECT d.id, d.path, d.comment, d.title, d.filetype, d.size
                        FROM $table_doc d 
                        INNER JOIN $table_prop p
                        ON (p.ref = d.id AND d.c_id = p.c_id)
                        WHERE
                            d.c_id = $courseId AND
                            p.c_id = $courseId AND
                            tool = '".TOOL_DOCUMENT."' AND
                            p.visibility != 2 AND
                            path NOT LIKE '/images/gallery%' AND
                            $avoid_paths
                            $session_condition
                        ORDER BY path";
            } else {
                $sql = "SELECT d.id, d.path, d.comment, d.title, d.filetype, d.size
                        FROM $table_doc d 
                        INNER JOIN $table_prop p
                        ON (p.ref = d.id AND d.c_id = p.c_id)
                        WHERE
                            d.c_id = $courseId AND
                            p.c_id = $courseId AND
                            tool = '".TOOL_DOCUMENT."' AND
                            $avoid_paths AND
                            p.visibility != 2 $session_condition
                        ORDER BY path";
            }

            $db_result = Database::query($sql);
            while ($obj = Database::fetch_object($db_result)) {
                $doc = new Document(
                    $obj->id,
                    $obj->path,
                    $obj->comment,
                    $obj->title,
                    $obj->filetype,
                    $obj->size
                );
                $this->course->add_resource($doc);
            }
        } else {
            if (!empty($this->course->type) && $this->course->type == 'partial') {
                $sql = "SELECT d.id, d.path, d.comment, d.title, d.filetype, d.size
                        FROM $table_doc d 
                        INNER JOIN $table_prop p
                        ON (p.ref = d.id AND d.c_id = p.c_id)
                        WHERE
                            d.c_id = $courseId AND
                            p.c_id = $courseId AND
                            tool = '".TOOL_DOCUMENT."' AND
                            p.visibility != 2 AND
                            path NOT LIKE '/images/gallery%' AND
                            $avoid_paths AND
                            (d.session_id = 0 OR d.session_id IS NULL)
                        ORDER BY path";
            } else {
                $sql = "SELECT d.id, d.path, d.comment, d.title, d.filetype, d.size
                        FROM $table_doc d 
                        INNER JOIN $table_prop p
                        ON (p.ref = d.id AND d.c_id = p.c_id)
                        WHERE
                            d.c_id = $courseId AND
                            p.c_id = $courseId AND
                            tool = '".TOOL_DOCUMENT."' AND
                            p.visibility != 2 AND
                            $avoid_paths AND
                            (d.session_id = 0 OR d.session_id IS NULL)
                        ORDER BY path";
            }

            $db_result = Database::query($sql);
            while ($obj = Database::fetch_object($db_result)) {
                $doc = new Document(
                    $obj->id,
                    $obj->path,
                    $obj->comment,
                    $obj->title,
                    $obj->filetype,
                    $obj->size
                );
                $this->course->add_resource($doc);
            }
        }
    }

    /**
     * Build the forums
     * @param int $session_id Internal session ID
     * @param int $courseId Internal course ID
     * @param bool $with_base_content Whether to include content from the course without session or not
     * @param array $id_list If you want to restrict the structure to only the given IDs
     * @return void
     */
    public function build_forums(
        $session_id = 0,
        $courseId = 0,
        $with_base_content = false,
        $id_list = array()
    ) {
        $table = Database::get_course_table(TABLE_FORUM);

        $sessionCondition = api_get_session_condition(
            $session_id,
            true,
            $with_base_content
        );

        $sql = "SELECT * FROM $table WHERE c_id = $courseId $sessionCondition";
        $sql .= " ORDER BY forum_title, forum_category";
        $db_result = Database::query($sql);
        while ($obj = Database::fetch_object($db_result)) {
            $forum = new Forum($obj);
            $this->course->add_resource($forum);
        }
    }

    /**
     * Build a forum-category
     * @param int $session_id Internal session ID
     * @param int $courseId Internal course ID
     * @param bool $with_base_content Whether to include content from the course without session or not
     * @param array $id_list If you want to restrict the structure to only the given IDs
     * @return void
     */
    public function build_forum_category(
        $session_id = 0,
        $courseId = 0,
        $with_base_content = false,
        $id_list = array()
    ) {
        $table = Database::get_course_table(TABLE_FORUM_CATEGORY);

        $sessionCondition = api_get_session_condition(
            $session_id,
            true,
            $with_base_content
        );

        $sql = "SELECT * FROM $table
                WHERE c_id = $courseId $sessionCondition
                ORDER BY cat_title";

        $result = Database::query($sql);
        while ($obj = Database::fetch_object($result)) {
            $forumCategory = new ForumCategory($obj);
            $this->course->add_resource($forumCategory);
        }
    }

    /**
     * Build the forum-topics
     * @param int $session_id Internal session ID
     * @param int $courseId Internal course ID
     * @param bool $with_base_content Whether to include content from the course without session or not
     * @param array $id_list If you want to restrict the structure to only the given IDs
     * @return void
     */
    public function build_forum_topics(
        $session_id = 0,
        $courseId = 0,
        $with_base_content = false,
        $id_list = array()
    ) {
        $table = Database::get_course_table(TABLE_FORUM_THREAD);

        $sessionCondition = api_get_session_condition(
            $session_id,
            true,
            $with_base_content
        );

        $sql = "SELECT * FROM $table WHERE c_id = $courseId
                $sessionCondition
                ORDER BY thread_title ";
        $result = Database::query($sql);

        while ($obj = Database::fetch_object($result)) {
            $forum_topic = new ForumTopic($obj);
            $this->course->add_resource($forum_topic);
            $this->build_forum_posts($courseId, $obj->thread_id, $obj->forum_id, true);
        }
    }

    /**
     * Build the forum-posts
     * TODO: All tree structure of posts should be built, attachments for example.
     * @param int $courseId Internal course ID
     * @param int $thread_id Internal thread ID
     * @param int $forum_id Internal forum ID
     * @param bool $only_first_post Whether to only copy the first post or not
     */
    public function build_forum_posts(
        $courseId = 0,
        $thread_id = null,
        $forum_id = null,
        $only_first_post = false
    ) {
        $table = Database::get_course_table(TABLE_FORUM_POST);
        $sql = "SELECT * FROM $table WHERE c_id = $courseId ";
        if (!empty($thread_id) && !empty($forum_id)) {
            $forum_id = intval($forum_id);
            $thread_id = intval($thread_id);
            $sql .= " AND thread_id = $thread_id AND forum_id = $forum_id ";
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
     * @param int $session_id Internal session ID
     * @param int $courseId Internal course ID
     * @param bool $with_base_content Whether to include content from the course without session or not
     * @param array $id_list If you want to restrict the structure to only the given IDs
     */
    public function build_links(
        $session_id = 0,
        $courseId = 0,
        $with_base_content = false,
        $id_list = array()
    ) {
        $categories = LinkManager::getLinkCategories(
            $courseId,
            $session_id,
            $with_base_content
        );

        // Adding empty category
        $categories[] = ['id' => 0];

        foreach ($categories as $category) {
            $this->build_link_category($category);

            $links = LinkManager::getLinksPerCategory(
                $category['id'],
                $courseId,
                $session_id,
                $with_base_content
            );

            foreach ($links as $item) {
                $link = new Link(
                    $item['id'],
                    $item['title'],
                    $item['url'],
                    $item['description'],
                    $item['category_id'],
                    $item['on_homepage']
                );
                $this->course->add_resource($link);
                $this->course->resources[RESOURCE_LINK][$item['id']]->add_linked_resource(
                    RESOURCE_LINKCATEGORY,
                    $item['category_id']
                );
            }
        }
    }

    /**
     * Build tool intro
     * @param int $session_id Internal session ID
     * @param int $courseId Internal course ID
     * @param bool $with_base_content Whether to include content from the course without session or not
     * @param array $id_list If you want to restrict the structure to only the given IDs
     */
    public function build_tool_intro(
        $session_id = 0,
        $courseId = 0,
        $with_base_content = false,
        $id_list = array()
    ) {
        $table = Database::get_course_table(TABLE_TOOL_INTRO);

        $sessionCondition = api_get_session_condition(
            $session_id,
            true,
            $with_base_content
        );

        $sql = "SELECT * FROM $table
                WHERE c_id = $courseId $sessionCondition";

        $db_result = Database::query($sql);
        while ($obj = Database::fetch_object($db_result)) {
            $tool_intro = new ToolIntro($obj->id, $obj->intro_text);
            $this->course->add_resource($tool_intro);
        }
    }

    /**
     * Build a link category
     * @param int $id Internal link ID
     * @param int $courseId Internal course ID
     * @return int
     */
    public function build_link_category($category)
    {
        if (empty($category) || empty($category['category_title'])) {
            return 0;
        }

        $linkCategory = new LinkCategory(
            $category['id'],
            $category['category_title'],
            $category['description'],
            $category['display_order']
        );
        $this->course->add_resource($linkCategory);

        return $category['id'];
    }

    /**
     * Build the Quizzes
     * @param int $session_id Internal session ID
     * @param int $courseId Internal course ID
     * @param bool $with_base_content Whether to include content from the course without session or not
     * @param array $idList If you want to restrict the structure to only the given IDs
     */
    public function build_quizzes(
        $session_id = 0,
        $courseId = 0,
        $with_base_content = false,
        $idList = array()
    ) {
        $table_qui = Database::get_course_table(TABLE_QUIZ_TEST);
        $table_rel = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $table_doc = Database::get_course_table(TABLE_DOCUMENT);

        if (!empty($courseId) && !empty($session_id)) {
            $session_id = intval($session_id);
            if ($with_base_content) {
                $session_condition = api_get_session_condition(
                    $session_id,
                    true,
                    true
                );
            } else {
                $session_condition = api_get_session_condition(
                    $session_id,
                    true
                );
            }
            $sql = "SELECT * FROM $table_qui
                    WHERE c_id = $courseId AND active >=0 $session_condition";
            //select only quizzes with active = 0 or 1 (not -1 which is for deleted quizzes)
        } else {
            $sql = "SELECT * FROM $table_qui
                    WHERE c_id = $courseId AND active >=0 AND (session_id = 0 OR session_id IS NULL)";
            //select only quizzes with active = 0 or 1 (not -1 which is for deleted quizzes)
        }

        $sql .= 'ORDER BY title';

        $db_result = Database::query($sql);
        while ($obj = Database::fetch_object($db_result)) {
            if (strlen($obj->sound) > 0) {
                $sql = "SELECT id FROM $table_doc
                        WHERE c_id = $courseId AND path = '/audio/".$obj->sound."'";
                $res = Database::query($sql);
                $doc = Database::fetch_object($res);
                $obj->sound = $doc->id;
            }
            $quiz = new Quiz($obj);

            $sql = 'SELECT * FROM '.$table_rel.'
                    WHERE c_id = '.$courseId.' AND exercice_id = '.$obj->id;
            $db_result2 = Database::query($sql);
            while ($obj2 = Database::fetch_object($db_result2)) {
                $quiz->add_question($obj2->question_id, $obj2->question_order);
            }
            $this->course->add_resource($quiz);
        }

        if (!empty($courseId)) {
            $this->build_quiz_questions($courseId);
        } else {
            $this->build_quiz_questions();
        }
    }

    /**
     * Build the Quiz-Questions
     * @param int $courseId Internal course ID
     */
    public function build_quiz_questions($courseId = 0)
    {
        $table_qui = Database::get_course_table(TABLE_QUIZ_TEST);
        $table_rel = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $table_que = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $table_ans = Database::get_course_table(TABLE_QUIZ_ANSWER);

        // Building normal tests.
        $sql = "SELECT * FROM $table_que
                WHERE c_id = $courseId ";
        $result = Database::query($sql);

        while ($obj = Database::fetch_object($result)) {
            // find the question category
            // @todo : need to be adapted for multi category questions in 1.10
            $question_category_id = TestCategory::getCategoryForQuestion(
                $obj->id,
                $courseId
            );

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

            $sql = 'SELECT * FROM '.$table_ans.'
                    WHERE c_id = '.$courseId.' AND question_id = '.$obj->id;
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
                    $table_options = Database::get_course_table(
                        TABLE_QUIZ_QUESTION_OPTION
                    );
                    $sql = 'SELECT * FROM '.$table_options.'
                            WHERE c_id = '.$courseId.' AND question_id = '.$obj->id;
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
                    SELECT question_id, q.* FROM $table_que q 
                    INNER JOIN $table_rel r
                    ON (q.c_id = r.c_id AND q.id = r.question_id)
                    INNER JOIN $table_qui ex
                    ON (ex.id = r.exercice_id AND ex.c_id = r.c_id )
                    WHERE ex.c_id = $courseId AND ex.active = '-1'
                 )
                 UNION
                 (
                    SELECT question_id, q.* FROM $table_que q 
                    left OUTER JOIN $table_rel r
                    ON (q.c_id = r.c_id AND q.id = r.question_id)
                    WHERE q.c_id = $courseId AND r.question_id is null
                 )
                 UNION
                 (
                    SELECT question_id, q.* FROM $table_que q
                    INNER JOIN $table_rel r
                    ON (q.c_id = r.c_id AND q.id = r.question_id)
                    WHERE r.c_id = $courseId AND (r.exercice_id = '-1' OR r.exercice_id = '0')
                 )
        ";

        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            $build_orphan_questions = true;
            $orphanQuestionIds = array();
            while ($obj = Database::fetch_object($result)) {
                // Orphan questions
                if (!empty($obj->question_id)) {
                    $obj->id = $obj->question_id;
                }

                // Avoid adding the same question twice
                if (!isset($this->course->resources[$obj->id])) {
                    // find the question category
                    // @todo : need to be adapted for multi category questions in 1.10
                    $question_category_id = TestCategory::getCategoryForQuestion(
                        $obj->id,
                        $courseId
                    );
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
                    $sql = "SELECT * FROM $table_ans
                            WHERE c_id = $courseId AND question_id = ".$obj->id;
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
            $newQuiz = new Quiz((object) $obj);
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
        $table_qui = Database::get_course_table(TABLE_QUIZ_TEST);
        $table_rel = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $table_que = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $table_ans = Database::get_course_table(TABLE_QUIZ_ANSWER);

        $courseId = api_get_course_int_id();

        $sql = 'SELECT *
                FROM '.$table_que.' as questions
                LEFT JOIN '.$table_rel.' as quizz_questions
                ON questions.id=quizz_questions.question_id
                LEFT JOIN '.$table_qui.' as exercises
                ON quizz_questions.exercice_id = exercises.id
                WHERE
                    questions.c_id = quizz_questions.c_id AND
                    questions.c_id = exercises.c_id AND
                    exercises.c_id = '.$courseId.' AND
                    (quizz_questions.exercice_id IS NULL OR
                    exercises.active = -1)';

        $db_result = Database::query($sql);
        if (Database::num_rows($db_result) > 0) {
            // This is the fictional test for collecting orphan questions.
            $orphan_questions = new Quiz(
                -1,
                get_lang('OrphanQuestions', ''),
                '',
                0,
                0,
                1,
                '',
                0
            );

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
     * @param int $sessionId Internal session ID
     * @param int $courseId Internal course ID
     * @param bool $withBaseContent Whether to include content from the course without session or not
     * @param array $idList If you want to restrict the structure to only the given IDs
     * @todo add course session
     */
    public function build_test_category(
        $sessionId = 0,
        $courseId = 0,
        $withBaseContent = false,
        $idList = array()
    ) {
        // get all test category in course
        $categories = TestCategory::getCategoryListInfo('', $courseId);
        foreach ($categories as $category) {
            /** @var TestCategory $category */
            $courseCopyTestCategory = new CourseCopyTestCategory(
                $category->id,
                $category->name,
                $category->description
            );
            $this->course->add_resource($courseCopyTestCategory);
        }
    }

    /**
     * Build the Surveys
     * @param int $session_id Internal session ID
     * @param int $courseId Internal course ID
     * @param bool $with_base_content Whether to include content from the course without session or not
     * @param array $id_list If you want to restrict the structure to only the given IDs
     */
    public function build_surveys(
        $session_id = 0,
        $courseId = 0,
        $with_base_content = false,
        $id_list = array()
    ) {
        $table_survey = Database::get_course_table(TABLE_SURVEY);
        $table_question = Database::get_course_table(TABLE_SURVEY_QUESTION);

        $sessionCondition = api_get_session_condition(
            $session_id,
            true,
            $with_base_content
        );

        $sql = 'SELECT * FROM '.$table_survey.'
                WHERE c_id = '.$courseId.' '.$sessionCondition;
        $db_result = Database::query($sql);
        while ($obj = Database::fetch_object($db_result)) {
            $survey = new Survey(
                $obj->survey_id, $obj->code, $obj->title,
                $obj->subtitle, $obj->author, $obj->lang,
                $obj->avail_from, $obj->avail_till, $obj->is_shared,
                $obj->template, $obj->intro, $obj->surveythanks,
                $obj->creation_date, $obj->invited, $obj->answered,
                $obj->invite_mail, $obj->reminder_mail
            );
            $sql = 'SELECT * FROM '.$table_question.'
                    WHERE c_id = '.$courseId.' AND survey_id = '.$obj->survey_id;
            $db_result2 = Database::query($sql);
            while ($obj2 = Database::fetch_object($db_result2)) {
                $survey->add_question($obj2->question_id);
            }
            $this->course->add_resource($survey);
        }
        $this->build_survey_questions($courseId);
    }

    /**
     * Build the Survey Questions
     * @param int $courseId Internal course ID
     */
    public function build_survey_questions($courseId)
    {
        $table_que = Database::get_course_table(TABLE_SURVEY_QUESTION);
        $table_opt = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);

        $sql = 'SELECT * FROM '.$table_que.' WHERE c_id = '.$courseId.'  ';
        $db_result = Database::query($sql);
        while ($obj = Database::fetch_object($db_result)) {
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
            $sql = 'SELECT * FROM '.$table_opt.'
                    WHERE c_id = '.$courseId.' AND question_id = '.$obj->question_id;
            $db_result2 = Database::query($sql);
            while ($obj2 = Database::fetch_object($db_result2)) {
                $question->add_answer($obj2->option_text, $obj2->sort);
            }
            $this->course->add_resource($question);
        }
    }

    /**
     * Build the announcements
     * @param int $session_id Internal session ID
     * @param int $courseId Internal course ID
     * @param bool $with_base_content Whether to include content from the course without session or not
     * @param array $id_list If you want to restrict the structure to only the given IDs
     */
    public function build_announcements(
        $session_id = 0,
        $courseId = 0,
        $with_base_content = false,
        $id_list = array()
    ) {
        $table = Database::get_course_table(TABLE_ANNOUNCEMENT);

        $sessionCondition = api_get_session_condition(
            $session_id,
            true,
            $with_base_content
        );

        $sql = 'SELECT * FROM '.$table.'
                WHERE c_id = '.$courseId.' '.$sessionCondition;
        $db_result = Database::query($sql);
        $table_attachment = Database::get_course_table(
            TABLE_ANNOUNCEMENT_ATTACHMENT
        );
        while ($obj = Database::fetch_object($db_result)) {
            if (empty($obj->id)) {
                continue;
            }
            $sql = 'SELECT path, comment, filename, size
                    FROM '.$table_attachment.'
                    WHERE c_id = '.$courseId.' AND announcement_id = '.$obj->id.'';
            $result = Database::query($sql);
            $attachment_obj = Database::fetch_object($result);
            $att_path = $att_filename = $att_size = $atth_comment = '';

            if (!empty($attachment_obj)) {
                $att_path = $attachment_obj->path;
                $att_filename = $attachment_obj->filename;
                $att_size = $attachment_obj->size;
                $atth_comment = $attachment_obj->comment;
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
     * @param int $session_id Internal session ID
     * @param int $courseId Internal course ID
     * @param bool $with_base_content Whether to include content from the course without session or not
     * @param array $id_list If you want to restrict the structure to only the given IDs
     */
    public function build_events(
        $session_id = 0,
        $courseId = 0,
        $with_base_content = false,
        $id_list = array()
    ) {
        $table = Database::get_course_table(TABLE_AGENDA);

        $sessionCondition = api_get_session_condition(
            $session_id,
            true,
            $with_base_content
        );

        $sql = 'SELECT * FROM '.$table.'
                WHERE c_id = '.$courseId.' '.$sessionCondition;
        $db_result = Database::query($sql);
        while ($obj = Database::fetch_object($db_result)) {
            $table_attachment = Database::get_course_table(
                TABLE_AGENDA_ATTACHMENT
            );
            $sql = 'SELECT path, comment, filename, size
                    FROM '.$table_attachment.'
                    WHERE c_id = '.$courseId.' AND agenda_id = '.$obj->id.'';
            $result = Database::query($sql);

            $attachment_obj = Database::fetch_object($result);
            $att_path = $att_filename = $att_size = $atth_comment = '';
            if (!empty($attachment_obj)) {
                $att_path = $attachment_obj->path;
                $att_filename = $attachment_obj->filename;
                $att_size = $attachment_obj->size;
                $atth_comment = $attachment_obj->comment;
            }
            $event = new CalendarEvent(
                $obj->id,
                $obj->title,
                $obj->content,
                $obj->start_date,
                $obj->end_date,
                $att_path,
                $att_filename,
                $att_size,
                $atth_comment,
                $obj->all_day
            );
            $this->course->add_resource($event);
        }
    }

    /**
     * Build the course-descriptions
     * @param int $session_id Internal session ID
     * @param int $courseId Internal course ID
     * @param bool $with_base_content Whether to include content from the course without session or not
     * @param array $id_list If you want to restrict the structure to only the given IDs
     */
    public function build_course_descriptions(
        $session_id = 0,
        $courseId = 0,
        $with_base_content = false,
        $id_list = array()
    ) {
        $table = Database::get_course_table(TABLE_COURSE_DESCRIPTION);

        if (!empty($session_id) && !empty($courseId)) {
            $session_id = intval($session_id);
            if ($with_base_content) {
                $session_condition = api_get_session_condition(
                    $session_id,
                    true,
                    true
                );
            } else {
                $session_condition = api_get_session_condition(
                    $session_id,
                    true
                );
            }
            $sql = 'SELECT * FROM '.$table.'
                    WHERE c_id = '.$courseId.' '.$session_condition;
        } else {
            $table = Database::get_course_table(TABLE_COURSE_DESCRIPTION);
            $sql = 'SELECT * FROM '.$table.'
                    WHERE c_id = '.$courseId.'  AND session_id = 0';
        }

        $db_result = Database::query($sql);
        while ($obj = Database::fetch_object($db_result)) {
            $cd = new CourseDescription(
                $obj->id,
                $obj->title,
                $obj->content,
                $obj->description_type
            );
            $this->course->add_resource($cd);
        }
    }

    /**
     * Build the learnpaths
     * @param int $session_id Internal session ID
     * @param int $courseId Internal course ID
     * @param bool $with_base_content Whether to include content from the course without session or not
     * @param array $id_list If you want to restrict the structure to only the given IDs
     */
    public function build_learnpaths(
        $session_id = 0,
        $courseId = 0,
        $with_base_content = false,
        $id_list = array()
    ) {
        $table_main = Database::get_course_table(TABLE_LP_MAIN);
        $table_item = Database::get_course_table(TABLE_LP_ITEM);
        $table_tool = Database::get_course_table(TABLE_TOOL_LIST);

        if (!empty($session_id) && !empty($courseId)) {
            $session_id = intval($session_id);
            if ($with_base_content) {
                $session_condition = api_get_session_condition(
                    $session_id,
                    true,
                    true
                );
            } else {
                $session_condition = api_get_session_condition(
                    $session_id,
                    true
                );
            }
            $sql = 'SELECT * FROM '.$table_main.'
                    WHERE c_id = '.$courseId.'  '.$session_condition;
        } else {
            $sql = 'SELECT * FROM '.$table_main.'
                    WHERE c_id = '.$courseId.' AND (session_id = 0 OR session_id IS NULL)';
        }

        if (!empty($id_list)) {
            $id_list = array_map('intval', $id_list);
            $sql .= " AND id IN (".implode(', ', $id_list).") ";
        }

        $db_result = Database::query($sql);
        if ($db_result) {
            while ($obj = Database::fetch_object($db_result)) {
                $items = array();
                $sql = "SELECT * FROM ".$table_item."
                        WHERE c_id = '$courseId' AND lp_id = ".$obj->id;
                $db_items = Database::query($sql);
                while ($obj_item = Database::fetch_object($db_items)) {
                    $item['id'] = $obj_item->id;
                    $item['item_type'] = $obj_item->item_type;
                    $item['ref'] = $obj_item->ref;
                    $item['title'] = $obj_item->title;
                    $item['description'] = $obj_item->description;
                    $item['path'] = $obj_item->path;
                    $item['min_score'] = $obj_item->min_score;
                    $item['max_score'] = $obj_item->max_score;
                    $item['mastery_score'] = $obj_item->mastery_score;
                    $item['parent_item_id'] = $obj_item->parent_item_id;
                    $item['previous_item_id'] = $obj_item->previous_item_id;
                    $item['next_item_id'] = $obj_item->next_item_id;
                    $item['display_order'] = $obj_item->display_order;
                    $item['prerequisite'] = $obj_item->prerequisite;
                    $item['parameters'] = $obj_item->parameters;
                    $item['launch_data'] = $obj_item->launch_data;
                    $item['audio'] = $obj_item->audio;
                    $items[] = $item;
                }

                $sql = "SELECT id FROM $table_tool
                        WHERE
                            c_id = $courseId AND
                            (link LIKE '%lp_controller.php%lp_id=".$obj->id."%' AND image='scormbuilder.gif') AND
                            visibility = '1' ";
                $db_tool = Database::query($sql);

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
                    $obj->autolaunch,
                    $obj->created_on,
                    $obj->modified_on,
                    $obj->publicated_on,
                    $obj->expired_on,
                    $obj->session_id,
                    $items
                );
                $this->course->add_resource($lp);
            }
        }

        // Save scorm directory (previously build_scorm_documents())
        $i = 1;
        if ($dir = @opendir($this->course->backup_path.'/scorm')) {
            while ($file = readdir($dir)) {
                if (is_dir($this->course->backup_path.'/scorm/'.$file) &&
                    !in_array($file, array('.', '..'))
                ) {
                    $doc = new ScormDocument($i++, '/'.$file, $file);
                    $this->course->add_resource($doc);
                }
            }
            closedir($dir);
        }
    }

    /**
     * Build the glossaries
     * @param int $session_id Internal session ID
     * @param int $courseId Internal course ID
     * @param bool $with_base_content Whether to include content from the course without session or not
     * @param array $id_list If you want to restrict the structure to only the given IDs
     */
    public function build_glossary(
        $session_id = 0,
        $courseId = 0,
        $with_base_content = false,
        $id_list = array()
    ) {
        $table_glossary = Database::get_course_table(TABLE_GLOSSARY);

        if (!empty($session_id) && !empty($courseId)) {
            $session_id = intval($session_id);
            if ($with_base_content) {
                $session_condition = api_get_session_condition(
                    $session_id,
                    true,
                    true
                );
            } else {
                $session_condition = api_get_session_condition(
                    $session_id,
                    true
                );
            }
            //@todo check this queries are the same ...
            if (!empty($this->course->type) && $this->course->type == 'partial') {
                $sql = 'SELECT * FROM '.$table_glossary.' g
                        WHERE g.c_id = '.$courseId.' '.$session_condition;
            } else {
                $sql = 'SELECT * FROM '.$table_glossary.' g
                        WHERE g.c_id = '.$courseId.' '.$session_condition;
            }
        } else {
            $table_glossary = Database::get_course_table(TABLE_GLOSSARY);
            //@todo check this queries are the same ... ayayay
            if (!empty($this->course->type) && $this->course->type == 'partial') {
                $sql = 'SELECT * FROM '.$table_glossary.' g
                        WHERE g.c_id = '.$courseId.' AND (session_id = 0 OR session_id IS NULL)';
            } else {
                $sql = 'SELECT * FROM '.$table_glossary.' g
                        WHERE g.c_id = '.$courseId.' AND (session_id = 0 OR session_id IS NULL)';
            }
        }
        $db_result = Database::query($sql);
        while ($obj = Database::fetch_object($db_result)) {
            $doc = new Glossary(
                $obj->glossary_id,
                $obj->name,
                $obj->description,
                $obj->display_order
            );
            $this->course->add_resource($doc);
        }
    }

    /*
     * Build session course by jhon
     */
    public function build_session_course()
    {
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $tbl_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $list_course = CourseManager::get_course_list();
        $list = array();
        foreach ($list_course as $_course) {
            $this->course = new Course();
            $this->course->code = $_course['code'];
            $this->course->type = 'partial';
            $this->course->path = api_get_path(SYS_COURSE_PATH).$_course['directory'].'/';
            $this->course->backup_path = api_get_path(SYS_COURSE_PATH).$_course['directory'];
            $this->course->encoding = api_get_system_encoding(); //current platform encoding
            $courseId = $_course['real_id'];
            $sql = "SELECT s.id, name, c_id
                    FROM $tbl_session_course sc
                    INNER JOIN $tbl_session s
                    ON sc.session_id = s.id
                    WHERE sc.c_id = '$courseId' ";
            $query_session = Database::query($sql);
            while ($rows_session = Database::fetch_assoc($query_session)) {
                $session = new CourseSession(
                    $rows_session['id'],
                    $rows_session['name']
                );
                $this->course->add_resource($session);
            }
            $list[] = $this->course;
        }

        return $list;
    }

    /**
     * @param int $session_id Internal session ID
     * @param int $courseId Internal course ID
     * @param bool $with_base_content Whether to include content from the course without session or not
     * @param array $id_list If you want to restrict the structure to only the given IDs
     */
    public function build_wiki(
        $session_id = 0,
        $courseId = 0,
        $with_base_content = false,
        $id_list = array()
    ) {
        $tbl_wiki = Database::get_course_table(TABLE_WIKI);

        if (!empty($session_id) && !empty($courseId)) {
            $session_id = intval($session_id);
            if ($with_base_content) {
                $session_condition = api_get_session_condition(
                    $session_id,
                    true,
                    true
                );
            } else {
                $session_condition = api_get_session_condition(
                    $session_id,
                    true
                );
            }
            $sql = 'SELECT * FROM '.$tbl_wiki.'
                    WHERE c_id = '.$courseId.' '.$session_condition;
        } else {
            $tbl_wiki = Database::get_course_table(TABLE_WIKI);
            $sql = 'SELECT * FROM '.$tbl_wiki.'
                    WHERE c_id = '.$courseId.' AND (session_id = 0 OR session_id IS NULL)';
        }
        $db_result = Database::query($sql);
        while ($obj = Database::fetch_object($db_result)) {
            $wiki = new Wiki(
                $obj->id,
                $obj->page_id,
                $obj->reflink,
                $obj->title,
                $obj->content,
                $obj->user_id,
                $obj->group_id,
                $obj->dtime,
                $obj->progress,
                $obj->version
            );
            $this->course->add_resource($wiki);
        }
    }

    /**
     * Build the Surveys
     * @param int $session_id Internal session ID
     * @param int $courseId Internal course ID
     * @param bool $with_base_content Whether to include content from the course without session or not
     * @param array $id_list If you want to restrict the structure to only the given IDs
     */
    public function build_thematic(
        $session_id = 0,
        $courseId = 0,
        $with_base_content = false,
        $id_list = array()
    ) {
        $table_thematic = Database::get_course_table(TABLE_THEMATIC);
        $table_thematic_advance = Database::get_course_table(TABLE_THEMATIC_ADVANCE);
        $table_thematic_plan = Database::get_course_table(TABLE_THEMATIC_PLAN);

        $courseInfo = api_get_course_info_by_id($courseId);
        $session_id = intval($session_id);
        if ($with_base_content) {
            $session_condition = api_get_session_condition(
                $session_id,
                true,
                true
            );
        } else {
            $session_condition = api_get_session_condition($session_id, true);
        }

        $sql = "SELECT * FROM $table_thematic
                WHERE c_id = $courseId $session_condition ";
        $db_result = Database::query($sql);
        while ($row = Database::fetch_array($db_result, 'ASSOC')) {
            $thematic = new Thematic($row);
            $sql = 'SELECT * FROM '.$table_thematic_advance.'
                    WHERE c_id = '.$courseId.' AND thematic_id = '.$row['id'];

            $result = Database::query($sql);
            while ($sub_row = Database::fetch_array($result, 'ASSOC')) {
                $thematic->add_thematic_advance($sub_row);
            }

            $items = api_get_item_property_by_tool(
                'thematic_plan',
                $courseInfo['code'],
                $session_id
            );

            $thematic_plan_id_list = array();
            if (!empty($items)) {
                foreach ($items as $item) {
                    $thematic_plan_id_list[] = $item['ref'];
                    //$thematic_plan_complete_list[$item['ref']] = $item;
                }
            }
            if (count($thematic_plan_id_list) > 0) {
                $sql = "SELECT tp.*
                        FROM $table_thematic_plan tp
                            INNER JOIN $table_thematic t ON (t.id=tp.thematic_id)
                        WHERE
                            t.c_id = $courseId AND
                            tp.c_id = $courseId AND
                            thematic_id = {$row['id']}  AND
                            tp.id IN (".implode(', ', $thematic_plan_id_list).") ";

                $result = Database::query($sql);
                while ($sub_row = Database::fetch_array($result, 'ASSOC')) {
                    $thematic->add_thematic_plan($sub_row);
                }
            }
            $this->course->add_resource($thematic);
        }
    }

    /**
     * Build the attendances
     * @param int $session_id Internal session ID
     * @param int $courseId Internal course ID
     * @param bool $with_base_content Whether to include content from the course without session or not
     * @param array $id_list If you want to restrict the structure to only the given IDs
     */
    public function build_attendance(
        $session_id = 0,
        $courseId = 0,
        $with_base_content = false,
        $id_list = array()
    ) {
        $table_attendance = Database::get_course_table(TABLE_ATTENDANCE);
        $table_attendance_calendar = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR);

        $sessionCondition = api_get_session_condition($session_id, true, $with_base_content);

        $sql = 'SELECT * FROM '.$table_attendance.'
                WHERE c_id = '.$courseId.' '.$sessionCondition;
        $db_result = Database::query($sql);
        while ($row = Database::fetch_array($db_result, 'ASSOC')) {
            $obj = new Attendance($row);
            $sql = 'SELECT * FROM '.$table_attendance_calendar.'
                    WHERE c_id = '.$courseId.' AND attendance_id = '.$row['id'];

            $result = Database::query($sql);
            while ($sub_row = Database::fetch_array($result, 'ASSOC')) {
                $obj->add_attendance_calendar($sub_row);
            }
            $this->course->add_resource($obj);
        }
    }

    /**
     * Build the works (or "student publications", or "assignments")
     * @param int $session_id Internal session ID
     * @param int $courseId Internal course ID
     * @param bool $with_base_content Whether to include content from the course without session or not
     * @param array $id_list If you want to restrict the structure to only the given IDs
     */
    public function build_works(
        $session_id = 0,
        $courseId = 0,
        $with_base_content = false,
        $id_list = array()
    ) {
        $table_work = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
        $sessionCondition = api_get_session_condition(
            $session_id,
            true,
            $with_base_content
        );

        $sql = "SELECT * FROM $table_work
                WHERE
                    c_id = $courseId
                    $sessionCondition AND
                    filetype = 'folder' AND
                    parent_id = 0 AND
                    active = 1";
        $db_result = Database::query($sql);
        while ($row = Database::fetch_array($db_result, 'ASSOC')) {
            $obj = new Work($row);
            $this->course->add_resource($obj);
        }
    }

    /**
     * @param int $session_id
     * @param int $courseId
     * @param bool $with_base_content
     */
    public function build_gradebook(
        $session_id = 0,
        $courseId = 0,
        $with_base_content = false
    ) {
        $courseInfo = api_get_course_info_by_id($courseId);
        $courseCode = $courseInfo['code'];
        $cats = Category:: load(
            null,
            null,
            $courseCode,
            null,
            null,
            $session_id
        );

        $obj = new GradeBookBackup($cats);
        $this->course->add_resource($obj);
    }
}
