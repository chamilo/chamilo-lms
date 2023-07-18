<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy;

use Category;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Announcement;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Asset;
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
use Chamilo\CourseBundle\Component\CourseCopy\Resources\H5pTool;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\LearnPathCategory;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Link;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\LinkCategory;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Quiz;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\QuizQuestion;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\QuizQuestionOption;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\ScormDocument;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Survey;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\SurveyQuestion;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Thematic;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\ToolIntro;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Wiki;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Work;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\XapiTool;
use Chamilo\CourseBundle\Entity\CLpCategory;
use CourseManager;
use Database;
use Link as LinkManager;
use TestCategory;

/**
 * Class CourseBuilder
 * Builds a course-object from a Chamilo-course.
 *
 * @author Bart Mollet <bart.mollet@hogent.be>
 *
 * @package chamilo.backup
 */
class CourseBuilder
{
    /** @var Course */
    public $course;

    /* With this array you can filter the tools you want to be parsed by
    default all tools are included */
    public $tools_to_build = [
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
        'learnpath_category',
        'learnpaths',
        'links',
        'surveys',
        'tool_intro',
        'thematic',
        'wiki',
        'works',
        'gradebook',
    ];

    public $toolToName = [
        'announcements' => RESOURCE_ANNOUNCEMENT,
        'attendance' => RESOURCE_ATTENDANCE,
        'course_descriptions' => RESOURCE_COURSEDESCRIPTION,
        'documents' => RESOURCE_DOCUMENT,
        'events' => RESOURCE_EVENT,
        'forum_category' => RESOURCE_FORUMCATEGORY,
        'forums' => RESOURCE_FORUM,
        'forum_topics' => RESOURCE_FORUMTOPIC,
        'glossary' => RESOURCE_GLOSSARY,
        'quizzes' => RESOURCE_QUIZ,
        'test_category' => RESOURCE_TEST_CATEGORY,
        'learnpath_category' => RESOURCE_LEARNPATH_CATEGORY,
        'learnpaths' => RESOURCE_LEARNPATH,
        'links' => RESOURCE_LINK,
        'surveys' => RESOURCE_SURVEY,
        'tool_intro' => RESOURCE_TOOL_INTRO,
        'thematic' => RESOURCE_THEMATIC,
        'wiki' => RESOURCE_WIKI,
        'works' => RESOURCE_WORK,
        'gradebook' => RESOURCE_GRADEBOOK,
    ];

    /* With this array you can filter wich elements of the tools are going
    to be added in the course obj (only works with LPs) */
    public $specific_id_list = [];
    public $documentsAddedInText = [];
    public $itemListToAdd = [];

    public $isXapiEnabled = false;
    public $isH5pEnabled = false;

    /**
     * Create a new CourseBuilder.
     *
     * @param string $type
     * @param null   $course
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
     * @param array $list
     */
    public function addDocumentList($list)
    {
        foreach ($list as $item) {
            if (!in_array($item[0], $this->documentsAddedInText)) {
                $this->documentsAddedInText[$item[0]] = $item;
            }
        }
    }

    /**
     * @param string $text
     */
    public function findAndSetDocumentsInText($text)
    {
        $documentList = \DocumentManager::get_resources_from_source_html($text);
        $this->addDocumentList($documentList);
    }

    /**
     * Parse documents added in the documentsAddedInText variable.
     */
    public function restoreDocumentsFromList()
    {
        if (!empty($this->documentsAddedInText)) {
            $list = [];
            $courseInfo = api_get_course_info();
            foreach ($this->documentsAddedInText as $item) {
                // Get information about source url
                $url = $item[0]; // url
                $scope = $item[1]; // scope (local, remote)
                $type = $item[2]; // type (rel, abs, url)

                $origParseUrl = parse_url($url);
                $realOrigPath = isset($origParseUrl['path']) ? $origParseUrl['path'] : null;

                if ($scope == 'local') {
                    if ($type == 'abs' || $type == 'rel') {
                        $documentFile = strstr($realOrigPath, 'document');
                        if (strpos($realOrigPath, $documentFile) !== false) {
                            $documentFile = str_replace('document', '', $documentFile);
                            $itemDocumentId = \DocumentManager::get_document_id($courseInfo, $documentFile);
                            // Document found! Add it to the list
                            if ($itemDocumentId) {
                                $list[] = $itemDocumentId;
                            }
                        }
                    }
                }
            }

            $this->build_documents(
                api_get_session_id(),
                api_get_course_int_id(),
                true,
                $list
            );
        }
    }

    /**
     * @param array $array
     */
    public function set_tools_to_build($array)
    {
        $this->tools_to_build = $array;
    }

    /**
     * @param array $array
     */
    public function set_tools_specific_id_list($array)
    {
        $this->specific_id_list = $array;
    }

    /**
     * Get the created course.
     *
     * @return course The course
     */
    public function get_course()
    {
        return $this->course;
    }

    /**
     * Build the course-object.
     *
     * @param int    $session_id
     * @param string $courseCode
     * @param bool   $withBaseContent   true if you want to get the elements that exists in the course and
     *                                  in the session, (session_id = 0 or session_id = X)
     * @param array  $parseOnlyToolList
     * @param array  $toolsFromPost
     *
     * @return Course The course object structure
     */
    public function build(
        $session_id = 0,
        $courseCode = '',
        $withBaseContent = false,
        $parseOnlyToolList = [],
        $toolsFromPost = []
    ) {
        $course = api_get_course_info($courseCode);
        $courseId = $course['real_id'];

        $xapiEnabled = \XApiPlugin::create()->isEnabled();
        if ($xapiEnabled) {
            $this->tools_to_build[] = 'xapi_tool';
            $this->toolToName['xapi_tool'] = RESOURCE_XAPI_TOOL;
            $this->isXapiEnabled = $xapiEnabled;
        }

        $h5pEnabled = \H5pImportPlugin::create()->isEnabled();
        if ($h5pEnabled) {
            $this->tools_to_build[] = 'h5p_tool';
            $this->toolToName['h5p_tool'] = RESOURCE_H5P_TOOL;
            $this->isH5pEnabled = $h5pEnabled;
        }

        foreach ($this->tools_to_build as $tool) {
            if (!empty($parseOnlyToolList) && !in_array($this->toolToName[$tool], $parseOnlyToolList)) {
                continue;
            }
            $function_build = 'build_'.$tool;
            $specificIdList = isset($this->specific_id_list[$tool]) ? $this->specific_id_list[$tool] : null;
            $buildOrphanQuestions = true;
            if ($tool === 'quizzes') {
                if (!empty($toolsFromPost['quiz'])) {
                    $specificIdList = array_keys($toolsFromPost['quiz']);
                }
                if (!isset($toolsFromPost[RESOURCE_QUIZ][-1])) {
                    $buildOrphanQuestions = false;
                }

                // Force orphan load
                if ($this->course->type === 'complete') {
                    $buildOrphanQuestions = true;
                }

                $this->build_quizzes(
                    $session_id,
                    $courseId,
                    $withBaseContent,
                    $specificIdList,
                    $buildOrphanQuestions
                );
            } else {
                if (!empty($toolsFromPost[RESOURCE_LEARNPATH]) && 'learnpaths' === $tool) {
                    $specificIdList = array_keys($toolsFromPost[RESOURCE_LEARNPATH]);
                }
                $this->$function_build(
                    $session_id,
                    $courseId,
                    $withBaseContent,
                    $specificIdList
                );
            }
        }

        // Add asset
        if ($course['course_image_source'] && basename($course['course_image_source']) !== 'course.png') {
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
        // from the item_property table and order them in the "resources" array
        $table = Database::get_course_table(TABLE_ITEM_PROPERTY);
        foreach ($this->course->resources as $type => $resources) {
            if (!empty($parseOnlyToolList) && !in_array($this->toolToName[$tool], $parseOnlyToolList)) {
                continue;
            }
            foreach ($resources as $id => $resource) {
                if ($resource) {
                    $tool = $resource->get_tool();
                    if ($tool != null) {
                        $sql = "SELECT * FROM $table
                                WHERE
                                    c_id = $courseId AND
                                    tool = '".$tool."' AND
                                    ref = '".$resource->get_id()."'";
                        $res = Database::query($sql);
                        $properties = [];
                        while ($property = Database::fetch_array($res)) {
                            $properties[] = $property;
                        }
                        $this->course->resources[$type][$id]->item_properties = $properties;
                    }
                }
            }
        }

        return $this->course;
    }

    /**
     * Build the documents.
     *
     * @param int   $session_id
     * @param int   $courseId
     * @param bool  $withBaseContent
     * @param array $idList
     */
    public function build_documents(
        $session_id = 0,
        $courseId = 0,
        $withBaseContent = false,
        $idList = []
    ) {
        $table_doc = Database::get_course_table(TABLE_DOCUMENT);
        $table_prop = Database::get_course_table(TABLE_ITEM_PROPERTY);

        // Remove chat_files, shared_folder, exercises files
        $avoid_paths = "
                         path NOT LIKE '/shared_folder%' AND
                         path NOT LIKE '/chat_files%' AND
                         path NOT LIKE '/../exercises/%'
                         ";
        $documentCondition = '';
        if (!empty($idList)) {
            $idList = array_unique($idList);
            $idList = array_map('intval', $idList);
            $documentCondition = ' d.iid IN ("'.implode('","', $idList).'") AND ';
        }

        if (!empty($courseId) && !empty($session_id)) {
            $session_id = (int) $session_id;
            if ($withBaseContent) {
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
                $sql = "SELECT d.iid, d.path, d.comment, d.title, d.filetype, d.size
                        FROM $table_doc d
                        INNER JOIN $table_prop p
                        ON (p.ref = d.id AND d.c_id = p.c_id)
                        WHERE
                            d.c_id = $courseId AND
                            p.c_id = $courseId AND
                            tool = '".TOOL_DOCUMENT."' AND
                            $documentCondition
                            p.visibility != 2 AND
                            path NOT LIKE '/images/gallery%' AND
                            $avoid_paths
                            $session_condition
                        ORDER BY path";
            } else {
                $sql = "SELECT d.iid, d.path, d.comment, d.title, d.filetype, d.size
                        FROM $table_doc d
                        INNER JOIN $table_prop p
                        ON (p.ref = d.id AND d.c_id = p.c_id)
                        WHERE
                            d.c_id = $courseId AND
                            p.c_id = $courseId AND
                            tool = '".TOOL_DOCUMENT."' AND
                            $documentCondition
                            $avoid_paths AND
                            p.visibility != 2 $session_condition
                        ORDER BY path";
            }

            $db_result = Database::query($sql);
            while ($obj = Database::fetch_object($db_result)) {
                $doc = new Document(
                    $obj->iid,
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
                $sql = "SELECT d.iid, d.path, d.comment, d.title, d.filetype, d.size
                        FROM $table_doc d
                        INNER JOIN $table_prop p
                        ON (p.ref = d.id AND d.c_id = p.c_id)
                        WHERE
                            d.c_id = $courseId AND
                            p.c_id = $courseId AND
                            tool = '".TOOL_DOCUMENT."' AND
                            $documentCondition
                            p.visibility != 2 AND
                            path NOT LIKE '/images/gallery%' AND
                            $avoid_paths AND
                            (d.session_id = 0 OR d.session_id IS NULL)
                        ORDER BY path";
            } else {
                $sql = "SELECT d.iid, d.path, d.comment, d.title, d.filetype, d.size
                        FROM $table_doc d
                        INNER JOIN $table_prop p
                        ON (p.ref = d.id AND d.c_id = p.c_id)
                        WHERE
                            d.c_id = $courseId AND
                            p.c_id = $courseId AND
                            tool = '".TOOL_DOCUMENT."' AND
                            $documentCondition
                            p.visibility != 2 AND
                            $avoid_paths AND
                            (d.session_id = 0 OR d.session_id IS NULL)
                        ORDER BY path";
            }

            $result = Database::query($sql);
            while ($obj = Database::fetch_object($result)) {
                $doc = new Document(
                    $obj->iid,
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
     * Build the forums.
     *
     * @param int   $session_id      Internal session ID
     * @param int   $courseId        Internal course ID
     * @param bool  $withBaseContent Whether to include content from the course without session or not
     * @param array $idList          If you want to restrict the structure to only the given IDs
     */
    public function build_forums(
        $session_id = 0,
        $courseId = 0,
        $withBaseContent = false,
        $idList = []
    ) {
        $table = Database::get_course_table(TABLE_FORUM);
        $sessionCondition = api_get_session_condition(
            $session_id,
            true,
            $withBaseContent
        );

        $idCondition = '';
        if (!empty($idList)) {
            $idList = array_unique($idList);
            $idList = array_map('intval', $idList);
            $idCondition = ' AND iid IN ("'.implode('","', $idList).'") ';
        }

        $sql = "SELECT * FROM $table WHERE c_id = $courseId $sessionCondition $idCondition";
        $sql .= " ORDER BY forum_title, forum_category";
        $db_result = Database::query($sql);
        while ($obj = Database::fetch_object($db_result)) {
            $forum = new Forum($obj);
            $this->course->add_resource($forum);
        }
    }

    /**
     * Build a forum-category.
     *
     * @param int   $session_id      Internal session ID
     * @param int   $courseId        Internal course ID
     * @param bool  $withBaseContent Whether to include content from the course without session or not
     * @param array $idList          If you want to restrict the structure to only the given IDs
     */
    public function build_forum_category(
        $session_id = 0,
        $courseId = 0,
        $withBaseContent = false,
        $idList = []
    ) {
        $table = Database::get_course_table(TABLE_FORUM_CATEGORY);

        $sessionCondition = api_get_session_condition(
            $session_id,
            true,
            $withBaseContent
        );

        $idCondition = '';
        if (!empty($idList)) {
            $idList = array_unique($idList);
            $idList = array_map('intval', $idList);
            $idCondition = ' AND iid IN ("'.implode('","', $idList).'") ';
        }

        $sql = "SELECT * FROM $table
                WHERE c_id = $courseId $sessionCondition $idCondition
                ORDER BY cat_title";

        $result = Database::query($sql);
        while ($obj = Database::fetch_object($result)) {
            $forumCategory = new ForumCategory($obj);
            $this->course->add_resource($forumCategory);
        }
    }

    /**
     * Build the forum-topics.
     *
     * @param int   $session_id      Internal session ID
     * @param int   $courseId        Internal course ID
     * @param bool  $withBaseContent Whether to include content from the course without session or not
     * @param array $idList          If you want to restrict the structure to only the given IDs
     */
    public function build_forum_topics(
        $session_id = 0,
        $courseId = 0,
        $withBaseContent = false,
        $idList = []
    ) {
        $table = Database::get_course_table(TABLE_FORUM_THREAD);

        $sessionCondition = api_get_session_condition(
            $session_id,
            true,
            $withBaseContent
        );

        $idCondition = '';
        if (!empty($idList)) {
            $idList = array_map('intval', $idList);
            $idCondition = ' AND iid IN ("'.implode('","', $idList).'") ';
        }

        $sql = "SELECT * FROM $table WHERE c_id = $courseId
                $sessionCondition
                $idCondition
                ORDER BY thread_title ";
        $result = Database::query($sql);

        while ($obj = Database::fetch_object($result)) {
            $forumTopic = new ForumTopic($obj);
            $this->course->add_resource($forumTopic);
            $this->build_forum_posts($courseId, $obj->thread_id, $obj->forum_id);
        }
    }

    /**
     * Build the forum-posts
     * TODO: All tree structure of posts should be built, attachments for example.
     *
     * @param int   $courseId  Internal course ID
     * @param int   $thread_id Internal thread ID
     * @param int   $forum_id  Internal forum ID
     * @param array $idList
     */
    public function build_forum_posts(
        $courseId = 0,
        $thread_id = null,
        $forum_id = null,
        $idList = []
    ) {
        $table = Database::get_course_table(TABLE_FORUM_POST);
        $courseId = (int) $courseId;
        $sql = "SELECT * FROM $table WHERE c_id = $courseId ";
        if (!empty($thread_id) && !empty($forum_id)) {
            $forum_id = intval($forum_id);
            $thread_id = intval($thread_id);
            $sql .= " AND thread_id = $thread_id AND forum_id = $forum_id ";
        }

        if (!empty($idList)) {
            $idList = array_map('intval', $idList);
            $sql .= ' AND iid IN ("'.implode('","', $idList).'") ';
        }

        $sql .= " ORDER BY post_id ASC LIMIT 1";
        $db_result = Database::query($sql);
        while ($obj = Database::fetch_object($db_result)) {
            $forum_post = new ForumPost($obj);
            $this->course->add_resource($forum_post);
        }
    }

    /**
     * Build the links.
     *
     * @param int   $session_id      Internal session ID
     * @param int   $courseId        Internal course ID
     * @param bool  $withBaseContent Whether to include content from the course without session or not
     * @param array $idList          If you want to restrict the structure to only the given IDs
     */
    public function build_links(
        $session_id = 0,
        $courseId = 0,
        $withBaseContent = false,
        $idList = []
    ) {
        $categories = LinkManager::getLinkCategories(
            $courseId,
            $session_id,
            $withBaseContent
        );

        // Adding empty category
        $categories[] = ['id' => 0];

        foreach ($categories as $category) {
            $this->build_link_category($category);

            $links = LinkManager::getLinksPerCategory(
                $category['id'],
                $courseId,
                $session_id,
                $withBaseContent
            );

            foreach ($links as $item) {
                if (!empty($idList)) {
                    if (!in_array($item['id'], $idList)) {
                        continue;
                    }
                }

                $link = new Link(
                    $item['id'],
                    $item['title'],
                    $item['url'],
                    $item['description'],
                    $item['category_id'],
                    $item['on_homepage']
                );
                $link->target = $item['target'];
                $this->course->add_resource($link);
                $this->course->resources[RESOURCE_LINK][$item['id']]->add_linked_resource(
                    RESOURCE_LINKCATEGORY,
                    $item['category_id']
                );
            }
        }
    }

    /**
     * Build tool intro.
     *
     * @param int   $session_id      Internal session ID
     * @param int   $courseId        Internal course ID
     * @param bool  $withBaseContent Whether to include content from the course without session or not
     * @param array $idList          If you want to restrict the structure to only the given IDs
     */
    public function build_tool_intro(
        $session_id = 0,
        $courseId = 0,
        $withBaseContent = false,
        $idList = []
    ) {
        $table = Database::get_course_table(TABLE_TOOL_INTRO);

        $sessionCondition = api_get_session_condition(
            $session_id,
            true,
            $withBaseContent
        );

        $courseId = (int) $courseId;

        $sql = "SELECT * FROM $table
                WHERE c_id = $courseId $sessionCondition";

        $db_result = Database::query($sql);
        while ($obj = Database::fetch_object($db_result)) {
            $tool_intro = new ToolIntro($obj->id, $obj->intro_text);
            $this->course->add_resource($tool_intro);
        }
    }

    /**
     * Build a link category.
     *
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
     * Build the Quizzes.
     *
     * @param int   $session_id           Internal session ID
     * @param int   $courseId             Internal course ID
     * @param bool  $withBaseContent      Whether to include content from the course without session or not
     * @param array $idList               If you want to restrict the structure to only the given IDs
     * @param bool  $buildOrphanQuestions
     */
    public function build_quizzes(
        $session_id = 0,
        $courseId = 0,
        $withBaseContent = false,
        $idList = [],
        $buildOrphanQuestions = true
    ) {
        $table_qui = Database::get_course_table(TABLE_QUIZ_TEST);
        $table_rel = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $table_doc = Database::get_course_table(TABLE_DOCUMENT);

        $courseId = (int) $courseId;
        $idCondition = '';
        if (!empty($idList)) {
            $idList = array_map('intval', $idList);
            $idCondition = ' iid IN ("'.implode('","', $idList).'") AND ';
        }

        if (!empty($courseId) && !empty($session_id)) {
            $session_id = (int) $session_id;
            if ($withBaseContent) {
                $sessionCondition = api_get_session_condition(
                    $session_id,
                    true,
                    true
                );
            } else {
                $sessionCondition = api_get_session_condition(
                    $session_id,
                    true
                );
            }

            // Select only quizzes with active = 0 or 1 (not -1 which is for deleted quizzes)
            $sql = "SELECT * FROM $table_qui
                    WHERE
                      c_id = $courseId AND
                      $idCondition
                      active >=0
                      $sessionCondition ";
        } else {
            // Select only quizzes with active = 0 or 1 (not -1 which is for deleted quizzes)
            $sql = "SELECT * FROM $table_qui
                    WHERE
                      c_id = $courseId AND
                      $idCondition
                      active >=0 AND
                      (session_id = 0 OR session_id IS NULL)";
        }

        $sql .= ' ORDER BY title';
        $db_result = Database::query($sql);
        $questionList = [];
        while ($obj = Database::fetch_object($db_result)) {
            if (strlen($obj->sound) > 0) {
                $sql = "SELECT iid FROM $table_doc
                        WHERE c_id = $courseId AND path = '/audio/".$obj->sound."'";
                $res = Database::query($sql);
                $doc = Database::fetch_object($res);
                $obj->sound = $doc->iid;
            }
            $this->findAndSetDocumentsInText($obj->description);

            $quiz = new Quiz($obj);
            $sql = "SELECT * FROM $table_rel
                WHERE c_id = $courseId AND exercice_id = {$obj->iid}";
            $db_result2 = Database::query($sql);
            while ($obj2 = Database::fetch_object($db_result2)) {
                $quiz->add_question($obj2->question_id, $obj2->question_order);
                $questionList[] = $obj2->question_id;
            }
            $this->course->add_resource($quiz);
        }

        if (!empty($courseId)) {
            $this->build_quiz_questions($courseId, $questionList, $buildOrphanQuestions);
        } else {
            $this->build_quiz_questions(0, $questionList, $buildOrphanQuestions);
        }
    }

    /**
     * Build the Quiz-Questions.
     *
     * @param int   $courseId             Internal course ID
     * @param array $questionList
     * @param bool  $buildOrphanQuestions
     */
    public function build_quiz_questions($courseId = 0, $questionList = [], $buildOrphanQuestions = true)
    {
        $table_qui = Database::get_course_table(TABLE_QUIZ_TEST);
        $table_rel = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $table_que = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $table_ans = Database::get_course_table(TABLE_QUIZ_ANSWER);
        $courseId = (int) $courseId;
        $questionListToString = implode("','", $questionList);

        // Building normal tests (many queries)
        $sql = "SELECT * FROM $table_que
                WHERE iid IN ('$questionListToString')";
        $result = Database::query($sql);

        while ($obj = Database::fetch_object($result)) {
            // find the question category
            // @todo : need to be adapted for multi category questions in 1.10
            $question_category_id = TestCategory::getCategoryForQuestion(
                $obj->iid,
                $courseId
            );

            $this->findAndSetDocumentsInText($obj->description);
            // It searches images from hotspot to build
            if (HOT_SPOT == $obj->type) {
                if (is_numeric($obj->picture)) {
                    $itemDocumentId = (int) $obj->picture;
                    $document = \DocumentManager::get_document_data_by_id($itemDocumentId, api_get_course_id());
                    if (file_exists($document['absolute_path'])) {
                        $directUrl = $document['direct_url'];
                        $path = str_replace(api_get_path(WEB_PATH), '/', $directUrl);
                        $this->documentsAddedInText[] = [
                            0 => $path,
                            1 => 'local',
                            2 => 'rel',
                        ];
                    }
                }
            }

            // build the backup resource question object
            $question = new QuizQuestion(
                $obj->iid,
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
            $question->addPicture($this);

            $sql = "SELECT * FROM $table_ans
                WHERE question_id = {$obj->iid}";
            $db_result2 = Database::query($sql);
            while ($obj2 = Database::fetch_object($db_result2)) {
                $question->add_answer(
                    $obj2->iid,
                    $obj2->answer,
                    $obj2->correct,
                    $obj2->comment,
                    $obj2->ponderation,
                    $obj2->position,
                    $obj2->hotspot_coordinates,
                    $obj2->hotspot_type
                );

                $this->findAndSetDocumentsInText($obj2->answer);
                $this->findAndSetDocumentsInText($obj2->comment);

                if ($obj->type == MULTIPLE_ANSWER_TRUE_FALSE) {
                    $table_options = Database::get_course_table(TABLE_QUIZ_QUESTION_OPTION);
                    $sql = "SELECT * FROM $table_options
                        WHERE question_id = {$obj->iid}";
                    $db_result3 = Database::query($sql);
                    while ($obj3 = Database::fetch_object($db_result3)) {
                        $question_option = new QuizQuestionOption($obj3);
                        $question->add_option($question_option);
                    }
                }
            }
            $this->course->add_resource($question);
        }

        // Check if a global setting has been set to avoid copying orphan questions
        if (true === api_get_configuration_value('quiz_discard_orphan_in_course_export')) {
            $buildOrphanQuestions = false;
        }

        if ($buildOrphanQuestions) {
            // Building a fictional test for collecting orphan questions.
            // When a course is emptied this option should be activated (true).
            //$build_orphan_questions = !empty($_POST['recycle_option']);

            // 1st union gets the orphan questions from deleted exercises
            // 2nd union gets the orphan questions from question that were deleted in a exercise.
            $sql = " (
                        SELECT question_id, q.* FROM $table_que q
                        INNER JOIN $table_rel r
                        ON q.iid = r.question_id
                        INNER JOIN $table_qui ex
                        ON (ex.iid = r.exercice_id AND ex.c_id = r.c_id)
                        WHERE ex.c_id = $courseId AND ex.active = '-1'
                    )
                    UNION
                     (
                        SELECT question_id, q.* FROM $table_que q
                        left OUTER JOIN $table_rel r
                        ON q.iid = r.question_id
                        WHERE q.c_id = $courseId AND r.question_id is null
                     )
                     UNION
                     (
                        SELECT question_id, q.* FROM $table_que q
                        INNER JOIN $table_rel r
                        ON q.iid = r.question_id
                        WHERE r.c_id = $courseId AND (r.exercice_id = '-1' OR r.exercice_id = '0')
                     )
                 ";

            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                $orphanQuestionIds = [];
                while ($obj = Database::fetch_object($result)) {
                    // Orphan questions
                    if (!empty($obj->question_id)) {
                        $obj->iid = $obj->question_id;
                    }

                    // Avoid adding the same question twice
                    if (!isset($this->course->resources[$obj->iid])) {
                        // find the question category
                        // @todo : need to be adapted for multi category questions in 1.10
                        $question_category_id = TestCategory::getCategoryForQuestion($obj->iid, $courseId);
                        $question = new QuizQuestion(
                            $obj->iid,
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
                        $question->addPicture($this);
                        $sql = "SELECT * FROM $table_ans
                                WHERE question_id = {$obj->iid}";
                        $db_result2 = Database::query($sql);
                        if (Database::num_rows($db_result2)) {
                            while ($obj2 = Database::fetch_object($db_result2)) {
                                $question->add_answer(
                                    $obj2->iid,
                                    $obj2->answer,
                                    $obj2->correct,
                                    $obj2->comment,
                                    $obj2->ponderation,
                                    $obj2->position,
                                    $obj2->hotspot_coordinates,
                                    $obj2->hotspot_type
                                );
                            }
                            $orphanQuestionIds[] = $obj->iid;
                        }
                        $this->course->add_resource($question);
                    }
                }
            }
        }

        $obj = [
            'iid' => -1,
            'title' => get_lang('OrphanQuestions'),
            'type' => 2,
        ];
        $newQuiz = new Quiz((object) $obj);
        if (!empty($orphanQuestionIds)) {
            foreach ($orphanQuestionIds as $index => $orphanId) {
                $order = $index + 1;
                $newQuiz->add_question($orphanId, $order);
            }
        }
        $this->course->add_resource($newQuiz);
    }

    /**
     * @deprecated
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
                ON questions.iid=quizz_questions.question_id
                LEFT JOIN '.$table_qui.' as exercises
                ON quizz_questions.exercice_id = exercises.iid
                WHERE
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
                    $obj->iid,
                    $obj->question,
                    $obj->description,
                    $obj->ponderation,
                    $obj->type,
                    $obj->position,
                    $obj->picture,
                    $obj->level,
                    $obj->extra
                );
                $question->addPicture($this);

                $sql = 'SELECT * FROM '.$table_ans.' WHERE question_id = '.$obj->iid;
                $db_result2 = Database::query($sql);
                while ($obj2 = Database::fetch_object($db_result2)) {
                    $question->add_answer(
                        $obj2->iid,
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
     * Build the test category.
     *
     * @param int   $sessionId       Internal session ID
     * @param int   $courseId        Internal course ID
     * @param bool  $withBaseContent Whether to include content from the course without session or not
     * @param array $idList          If you want to restrict the structure to only the given IDs
     *
     * @todo add course session
     */
    public function build_test_category(
        $sessionId = 0,
        $courseId = 0,
        $withBaseContent = false,
        $idList = []
    ) {
        // get all test category in course
        $categories = TestCategory::getCategoryListInfo('', $courseId);
        foreach ($categories as $category) {
            $this->findAndSetDocumentsInText($category->description);

            /** @var TestCategory $category */
            $courseCopyTestCategory = new CourseCopyTestCategory(
                $category->iid,
                $category->name,
                $category->description
            );
            $this->course->add_resource($courseCopyTestCategory);
        }
    }

    /**
     * Build the Surveys.
     *
     * @param int   $session_id      Internal session ID
     * @param int   $courseId        Internal course ID
     * @param bool  $withBaseContent Whether to include content from the course without session or not
     * @param array $id_list         If you want to restrict the structure to only the given IDs
     */
    public function build_surveys(
        $session_id = 0,
        $courseId = 0,
        $withBaseContent = false,
        $id_list = []
    ) {
        $table_survey = Database::get_course_table(TABLE_SURVEY);
        $table_question = Database::get_course_table(TABLE_SURVEY_QUESTION);

        $courseId = (int) $courseId;

        $sessionCondition = api_get_session_condition(
            $session_id,
            true,
            $withBaseContent
        );

        $sql = 'SELECT * FROM '.$table_survey.'
                WHERE c_id = '.$courseId.' '.$sessionCondition;
        if ($id_list) {
            $sql .= " AND iid IN (".implode(', ', $id_list).")";
        }
        $db_result = Database::query($sql);
        while ($obj = Database::fetch_object($db_result)) {
            $survey = new Survey(
                $obj->survey_id,
                $obj->code,
                $obj->title,
                $obj->subtitle,
                $obj->author,
                $obj->lang,
                $obj->avail_from,
                $obj->avail_till,
                $obj->is_shared,
                $obj->template,
                $obj->intro,
                $obj->surveythanks,
                $obj->creation_date,
                $obj->invited,
                $obj->answered,
                $obj->invite_mail,
                $obj->reminder_mail,
                $obj->one_question_per_page,
                $obj->shuffle
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
     * Build the Survey Questions.
     *
     * @param int $courseId Internal course ID
     */
    public function build_survey_questions($courseId)
    {
        $table_que = Database::get_course_table(TABLE_SURVEY_QUESTION);
        $table_opt = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);

        $courseId = (int) $courseId;
        $idList = isset($this->specific_id_list['surveys']) ? $this->specific_id_list['surveys'] : [];

        $sql = 'SELECT * FROM '.$table_que.' WHERE c_id = '.$courseId.'  ';

        if (!empty($idList)) {
            $sql .= " AND survey_id IN (".implode(', ', $idList).")";
        }

        $db_result = Database::query($sql);
        $is_required = 0;
        while ($obj = Database::fetch_object($db_result)) {
            if (api_get_configuration_value('allow_required_survey_questions')) {
                if (isset($obj->is_required)) {
                    $is_required = $obj->is_required;
                }
            }
            $question = new SurveyQuestion(
                $obj->question_id,
                $obj->survey_id,
                $obj->survey_question,
                $obj->survey_question_comment,
                $obj->type,
                $obj->display,
                $obj->sort,
                $obj->shared_question_id,
                $obj->max_value,
                $is_required
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
     * Build the announcements.
     *
     * @param int   $session_id      Internal session ID
     * @param int   $courseId        Internal course ID
     * @param bool  $withBaseContent Whether to include content from the course without session or not
     * @param array $id_list         If you want to restrict the structure to only the given IDs
     */
    public function build_announcements(
        $session_id = 0,
        $courseId = 0,
        $withBaseContent = false,
        $id_list = []
    ) {
        $table = Database::get_course_table(TABLE_ANNOUNCEMENT);

        $sessionCondition = api_get_session_condition(
            $session_id,
            true,
            $withBaseContent
        );

        $courseId = (int) $courseId;

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
     * Build the events.
     *
     * @param int   $session_id      Internal session ID
     * @param int   $courseId        Internal course ID
     * @param bool  $withBaseContent Whether to include content from the course without session or not
     * @param array $id_list         If you want to restrict the structure to only the given IDs
     */
    public function build_events(
        $session_id = 0,
        $courseId = 0,
        $withBaseContent = false,
        $id_list = []
    ) {
        $table = Database::get_course_table(TABLE_AGENDA);

        $sessionCondition = api_get_session_condition(
            $session_id,
            true,
            $withBaseContent
        );

        $courseId = (int) $courseId;

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
     * Build the course-descriptions.
     *
     * @param int   $session_id      Internal session ID
     * @param int   $courseId        Internal course ID
     * @param bool  $withBaseContent Whether to include content from the course without session or not
     * @param array $id_list         If you want to restrict the structure to only the given IDs
     */
    public function build_course_descriptions(
        $session_id = 0,
        $courseId = 0,
        $withBaseContent = false,
        $id_list = []
    ) {
        $table = Database::get_course_table(TABLE_COURSE_DESCRIPTION);
        $courseId = (int) $courseId;

        if (!empty($session_id) && !empty($courseId)) {
            $session_id = intval($session_id);
            if ($withBaseContent) {
                $sessionCondition = api_get_session_condition(
                    $session_id,
                    true,
                    true
                );
            } else {
                $sessionCondition = api_get_session_condition(
                    $session_id,
                    true
                );
            }
            $sql = 'SELECT * FROM '.$table.'
                    WHERE c_id = '.$courseId.' '.$sessionCondition;
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
     * @param int   $session_id
     * @param int   $courseId
     * @param bool  $withBaseContent
     * @param array $idList
     */
    public function build_learnpath_category($session_id = 0, $courseId = 0, $withBaseContent = false, $idList = [])
    {
        $categories = \learnpath::getCategories($courseId);

        /** @var CLpCategory $item */
        foreach ($categories as $item) {
            $categoryId = $item->getId();
            if (!empty($idList)) {
                if (!in_array($categoryId, $idList)) {
                    continue;
                }
            }
            $category = new LearnPathCategory($categoryId, $item);
            $this->course->add_resource($category);
        }
    }

    /**
     * Build the learnpaths.
     *
     * @param int   $session_id      Internal session ID
     * @param int   $courseId        Internal course ID
     * @param bool  $withBaseContent Whether to include content from the course without session or not
     * @param array $id_list         If you want to restrict the structure to only the given IDs
     * @param bool  $addScormFolder
     */
    public function build_learnpaths(
        $session_id = 0,
        $courseId = 0,
        $withBaseContent = false,
        $id_list = [],
        $addScormFolder = true
    ) {
        $lpTable = Database::get_course_table(TABLE_LP_MAIN);
        $table_item = Database::get_course_table(TABLE_LP_ITEM);
        $table_tool = Database::get_course_table(TABLE_TOOL_LIST);

        $courseId = (int) $courseId;

        if (!empty($session_id) && !empty($courseId)) {
            $session_id = (int) $session_id;
            if ($withBaseContent) {
                $sessionCondition = api_get_session_condition(
                    $session_id,
                    true,
                    true
                );
            } else {
                $sessionCondition = api_get_session_condition(
                    $session_id,
                    true
                );
            }
            $sql = 'SELECT * FROM '.$lpTable.'
                    WHERE c_id = '.$courseId.'  '.$sessionCondition;
        } else {
            $sql = 'SELECT * FROM '.$lpTable.'
                    WHERE c_id = '.$courseId.' AND (session_id = 0 OR session_id IS NULL)';
        }

        if (!empty($id_list)) {
            $id_list = array_map('intval', $id_list);
            $sql .= " AND id IN (".implode(', ', $id_list).") ";
        }

        $result = Database::query($sql);
        if ($result) {
            while ($obj = Database::fetch_object($result)) {
                $items = [];
                $sql = "SELECT * FROM $table_item
                        WHERE c_id = '$courseId' AND lp_id = ".$obj->id;
                $resultItem = Database::query($sql);
                while ($obj_item = Database::fetch_object($resultItem)) {
                    $item['id'] = $obj_item->iid;
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
                    $item['prerequisite_min_score'] = $obj_item->prerequisite_min_score;
                    $item['prerequisite_max_score'] = $obj_item->prerequisite_max_score;
                    $item['parameters'] = $obj_item->parameters;
                    $item['launch_data'] = $obj_item->launch_data;
                    $item['audio'] = $obj_item->audio;
                    $items[] = $item;
                    $this->itemListToAdd[$obj_item->item_type][] = $obj_item->path;

                    if (!empty($obj_item->audio)) {
                        $audioTitle = basename($obj_item->audio);
                        // Add LP item audio
                        $assetAudio = new Asset(
                            $audioTitle,
                            '/document'.$obj_item->audio,
                            '/document'.$obj_item->audio
                        );
                        $this->course->add_resource($assetAudio);
                    }
                }

                $sql = "SELECT id FROM $table_tool
                        WHERE
                            c_id = $courseId AND
                            (link LIKE '%lp_controller.php%lp_id=".$obj->id."%' AND image='scormbuilder.gif') AND
                            visibility = '1' ";
                $db_tool = Database::query($sql);
                $visibility = '0';
                if (Database::num_rows($db_tool)) {
                    $visibility = '1';
                }

                $accumulateWorkTime = 0;
                if (api_get_configuration_value('lp_minimum_time')) {
                    if (isset($obj->accumulate_work_time) && !empty($obj->accumulate_work_time)) {
                        $accumulateWorkTime = $obj->accumulate_work_time;
                    }
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
                    $obj->category_id,
                    $obj->subscribe_users,
                    $obj->hide_toc_frame,
                    $items,
                    $accumulateWorkTime,
                    $obj->prerequisite
                );
                $extraFieldValue = new \ExtraFieldValue('lp');
                $lp->extraFields = $extraFieldValue->getAllValuesByItem($obj->id);
                $this->course->add_resource($lp);

                if (!empty($obj->preview_image)) {
                    // Add LP image
                    $asset = new Asset(
                        $obj->preview_image,
                        '/upload/learning_path/images/'.$obj->preview_image,
                        '/upload/learning_path/images/'.$obj->preview_image
                    );
                    $this->course->add_resource($asset);
                }
            }
        }

        // Save scorm directory (previously build_scorm_documents())
        if ($addScormFolder) {
            $i = 1;
            if ($dir = @opendir($this->course->backup_path.'/scorm')) {
                while ($file = readdir($dir)) {
                    if (is_dir($this->course->backup_path.'/scorm/'.$file) &&
                        !in_array($file, ['.', '..'])
                    ) {
                        $doc = new ScormDocument($i++, '/'.$file, $file);
                        $this->course->add_resource($doc);
                    }
                }
                closedir($dir);
            }
        }
    }

    /**
     * It builds the resources used in a LP , also it adds the documents related.
     */
    public function exportToCourseBuildFormat()
    {
        if (empty($this->itemListToAdd)) {
            return false;
        }
        $itemList = $this->itemListToAdd;
        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();
        $courseInfo = api_get_course_info_by_id($courseId);
        if (isset($itemList['document'])) {
            // Get parents
            foreach ($itemList['document'] as $documentId) {
                $documentInfo = \DocumentManager::get_document_data_by_id($documentId, $courseInfo['code'], true);
                if (!empty($documentInfo['parents'])) {
                    foreach ($documentInfo['parents'] as $parentInfo) {
                        if (in_array($parentInfo['iid'], $itemList['document'])) {
                            continue;
                        }
                        $itemList['document'][] = $parentInfo['iid'];
                    }
                }
            }

            foreach ($itemList['document'] as $documentId) {
                $documentInfo = \DocumentManager::get_document_data_by_id($documentId, $courseInfo['code']);
                $items = \DocumentManager::get_resources_from_source_html(
                    $documentInfo['absolute_path'],
                    true,
                    TOOL_DOCUMENT
                );

                if (!empty($items)) {
                    foreach ($items as $item) {
                        // Get information about source url
                        $url = $item[0]; // url
                        $scope = $item[1]; // scope (local, remote)
                        $type = $item[2]; // type (rel, abs, url)

                        $origParseUrl = parse_url($url);
                        $realOrigPath = isset($origParseUrl['path']) ? $origParseUrl['path'] : null;

                        if ($scope == 'local') {
                            if ($type == 'abs' || $type == 'rel') {
                                $documentFile = strstr($realOrigPath, 'document');
                                $documentFile = (string) $documentFile;
                                $realOrigPath = (string) $realOrigPath;
                                if (!empty($documentFile) && false !== strpos($realOrigPath, $documentFile)) {
                                    $documentFile = str_replace('document', '', $documentFile);
                                    $itemDocumentId = \DocumentManager::get_document_id($courseInfo, $documentFile);
                                    // Document found! Add it to the list
                                    if ($itemDocumentId) {
                                        $itemList['document'][] = $itemDocumentId;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $this->build_documents(
                $sessionId,
                $courseId,
                true,
                $itemList['document']
            );
        }

        if (isset($itemList['quiz'])) {
            $this->build_quizzes(
                $sessionId,
                $courseId,
                true,
                $itemList['quiz']
            );
        }

        require_once api_get_path(SYS_CODE_PATH).'forum/forumfunction.inc.php';

        if (!empty($itemList['thread'])) {
            $threadList = [];
            $em = Database::getManager();
            $repo = $em->getRepository('ChamiloCourseBundle:CForumThread');
            foreach ($itemList['thread'] as $threadId) {
                /** @var \Chamilo\CourseBundle\Entity\CForumThread $thread */
                $thread = $repo->find($threadId);
                if ($thread) {
                    $itemList['forum'][] = $thread->getForumId();
                    $threadList[] = $thread->getIid();
                }
            }

            if (!empty($threadList)) {
                $this->build_forum_topics(
                    $sessionId,
                    $courseId,
                    null,
                    $threadList
                );
            }
        }

        $forumCategoryList = [];
        if (isset($itemList['forum'])) {
            foreach ($itemList['forum'] as $forumId) {
                $forumInfo = get_forums($forumId);
                $forumCategoryList[] = $forumInfo['forum_category'];
            }
        }

        if (!empty($forumCategoryList)) {
            $this->build_forum_category(
                $sessionId,
                $courseId,
                true,
                $forumCategoryList
            );
        }

        if (!empty($itemList['forum'])) {
            $this->build_forums(
                $sessionId,
                $courseId,
                true,
                $itemList['forum']
            );
        }

        if (isset($itemList['link'])) {
            $this->build_links(
                $sessionId,
                $courseId,
                true,
                $itemList['link']
            );
        }

        if (isset($itemList['xapi']) && $this->isXapiEnabled) {
            $this->build_xapi_tool(
                $sessionId,
                $courseId,
                true,
                $itemList['xapi']
            );
        }

        if (isset($itemList['h5p']) && $this->isH5pEnabled) {
            $this->buildH5pTool(
                $sessionId,
                $courseId,
                true,
                $itemList['h5p']
            );
        }

        if (!empty($itemList['student_publication'])) {
            $this->build_works(
                $sessionId,
                $courseId,
                true,
                $itemList['student_publication']
            );
        }
    }

    /**
     * Build the glossaries.
     *
     * @param int   $session_id      Internal session ID
     * @param int   $courseId        Internal course ID
     * @param bool  $withBaseContent Whether to include content from the course without session or not
     * @param array $id_list         If you want to restrict the structure to only the given IDs
     */
    public function build_glossary(
        $session_id = 0,
        $courseId = 0,
        $withBaseContent = false,
        $id_list = []
    ) {
        $table_glossary = Database::get_course_table(TABLE_GLOSSARY);

        $courseId = (int) $courseId;

        if (!empty($session_id) && !empty($courseId)) {
            $session_id = intval($session_id);
            if ($withBaseContent) {
                $sessionCondition = api_get_session_condition(
                    $session_id,
                    true,
                    true
                );
            } else {
                $sessionCondition = api_get_session_condition(
                    $session_id,
                    true
                );
            }

            //@todo check this queries are the same ...
            if (!empty($this->course->type) && $this->course->type == 'partial') {
                $sql = 'SELECT * FROM '.$table_glossary.' g
                        WHERE g.c_id = '.$courseId.' '.$sessionCondition;
            } else {
                $sql = 'SELECT * FROM '.$table_glossary.' g
                        WHERE g.c_id = '.$courseId.' '.$sessionCondition;
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
        $list = [];
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
     * @param int   $session_id      Internal session ID
     * @param int   $courseId        Internal course ID
     * @param bool  $withBaseContent Whether to include content from the course without session or not
     * @param array $id_list         If you want to restrict the structure to only the given IDs
     */
    public function build_wiki(
        $session_id = 0,
        $courseId = 0,
        $withBaseContent = false,
        $id_list = []
    ) {
        $tbl_wiki = Database::get_course_table(TABLE_WIKI);
        $courseId = (int) $courseId;

        if (!empty($session_id) && !empty($courseId)) {
            $session_id = intval($session_id);
            if ($withBaseContent) {
                $sessionCondition = api_get_session_condition(
                    $session_id,
                    true,
                    true
                );
            } else {
                $sessionCondition = api_get_session_condition(
                    $session_id,
                    true
                );
            }
            $sql = 'SELECT * FROM '.$tbl_wiki.'
                    WHERE c_id = '.$courseId.' '.$sessionCondition;
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
     * Build the xapi tool.
     */
    public function build_xapi_tool(
        $sessionId = 0,
        $courseId = 0,
        $withBaseContent = false,
        $idList = []
    ) {
        if (!$this->isXapiEnabled) {
            return false;
        }

        $courseId = (int) $courseId;
        $sessionId = (int) $sessionId;
        if ($withBaseContent) {
            $sessionCondition = api_get_session_condition(
                $sessionId,
                true,
                true
            );
        } else {
            $sessionCondition = api_get_session_condition($sessionId, true);
        }

        $idCondition = '';
        if (!empty($idList)) {
            $idList = array_map('intval', $idList);
            $idCondition = ' AND id IN ("'.implode('","', $idList).'") ';
        }

        $sql = "SELECT * FROM xapi_tool_launch WHERE c_id = $courseId $sessionCondition $idCondition";
        $rs = Database::query($sql);
        while ($row = Database::fetch_array($rs, 'ASSOC')) {
            $xapiTool = new XapiTool($row);
            $this->course->add_resource($xapiTool);
        }
    }

    public function buildH5pTool(
        $sessionId = 0,
        $courseId = 0,
        $withBaseContent = false,
        $idList = []
    ) {
        if (!$this->isH5pEnabled) {
            return false;
        }

        $courseId = (int) $courseId;
        $sessionId = (int) $sessionId;
        $sessionCondition = api_get_session_condition($sessionId, true, $withBaseContent);

        $idCondition = '';
        if (!empty($idList)) {
            $idList = array_map('intval', $idList);
            $idCondition = ' AND iid IN ("'.implode('","', $idList).'") ';
        }

        $sql = "SELECT * FROM plugin_h5p_import WHERE c_id = $courseId $sessionCondition $idCondition";
        $rs = Database::query($sql);
        while ($row = Database::fetch_array($rs, 'ASSOC')) {
            $h5pTool = new H5pTool($row);
            $this->course->add_resource($h5pTool);
        }
    }

    /**
     * Build the Surveys.
     *
     * @param int   $session_id      Internal session ID
     * @param int   $courseId        Internal course ID
     * @param bool  $withBaseContent Whether to include content from the course without session or not
     * @param array $id_list         If you want to restrict the structure to only the given IDs
     */
    public function build_thematic(
        $session_id = 0,
        $courseId = 0,
        $withBaseContent = false,
        $id_list = []
    ) {
        $table_thematic = Database::get_course_table(TABLE_THEMATIC);
        $table_thematic_advance = Database::get_course_table(TABLE_THEMATIC_ADVANCE);
        $table_thematic_plan = Database::get_course_table(TABLE_THEMATIC_PLAN);
        $courseId = (int) $courseId;

        $courseInfo = api_get_course_info_by_id($courseId);
        $session_id = intval($session_id);
        if ($withBaseContent) {
            $sessionCondition = api_get_session_condition(
                $session_id,
                true,
                true
            );
        } else {
            $sessionCondition = api_get_session_condition($session_id, true);
        }

        $sql = "SELECT * FROM $table_thematic
                WHERE c_id = $courseId AND active = 1 $sessionCondition ";
        $db_result = Database::query($sql);
        while ($row = Database::fetch_array($db_result, 'ASSOC')) {
            $thematic = new Thematic($row);
            $sql = 'SELECT * FROM '.$table_thematic_advance.'
                    WHERE c_id = '.$courseId.' AND thematic_id = '.$row['id'];

            $result = Database::query($sql);
            while ($sub_row = Database::fetch_array($result, 'ASSOC')) {
                $thematic->addThematicAdvance($sub_row);
            }

            $items = api_get_item_property_by_tool(
                'thematic_plan',
                $courseInfo['code'],
                $session_id
            );

            $thematic_plan_id_list = [];
            if (!empty($items)) {
                foreach ($items as $item) {
                    $thematic_plan_id_list[] = $item['ref'];
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
                    $thematic->addThematicPlan($sub_row);
                }
            }
            $this->course->add_resource($thematic);
        }
    }

    /**
     * Build the attendances.
     *
     * @param int   $session_id      Internal session ID
     * @param int   $courseId        Internal course ID
     * @param bool  $withBaseContent Whether to include content from the course without session or not
     * @param array $id_list         If you want to restrict the structure to only the given IDs
     */
    public function build_attendance(
        $session_id = 0,
        $courseId = 0,
        $withBaseContent = false,
        $id_list = []
    ) {
        $table_attendance = Database::get_course_table(TABLE_ATTENDANCE);
        $table_attendance_calendar = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR);
        $sessionCondition = api_get_session_condition($session_id, true, $withBaseContent);
        $courseId = (int) $courseId;

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
     * Build the works (or "student publications", or "assignments").
     *
     * @param int   $session_id      Internal session ID
     * @param int   $courseId        Internal course ID
     * @param bool  $withBaseContent Whether to include content from the course without session or not
     * @param array $idList          If you want to restrict the structure to only the given IDs
     */
    public function build_works(
        $session_id = 0,
        $courseId = 0,
        $withBaseContent = false,
        $idList = []
    ) {
        $table_work = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
        $sessionCondition = api_get_session_condition(
            $session_id,
            true,
            $withBaseContent
        );
        $courseId = (int) $courseId;

        $idCondition = '';
        if (!empty($idList)) {
            $idList = array_map('intval', $idList);
            $idCondition = ' AND iid IN ("'.implode('","', $idList).'") ';
        }

        $sql = "SELECT * FROM $table_work
                WHERE
                    c_id = $courseId
                    $sessionCondition AND
                    filetype = 'folder' AND
                    parent_id = 0 AND
                    active = 1
                    $idCondition
                ";
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $obj = new Work($row);
            $this->findAndSetDocumentsInText($row['description']);
            $this->course->add_resource($obj);
        }
    }

    /**
     * @param int  $session_id
     * @param int  $courseId
     * @param bool $withBaseContent
     */
    public function build_gradebook(
        $session_id = 0,
        $courseId = 0,
        $withBaseContent = false
    ) {
        $courseInfo = api_get_course_info_by_id($courseId);
        $courseCode = $courseInfo['code'];
        $cats = Category::load(
            null,
            null,
            $courseCode,
            null,
            null,
            $session_id
        );

        if (!empty($cats)) {
            /** @var Category $cat */
            foreach ($cats as $cat) {
                $cat->evaluations = $cat->get_evaluations(null, false);
                $cat->links = $cat->get_links(null, false);
                $cat->subCategories = $cat->get_subcategories(
                    null,
                    $courseCode,
                    $session_id
                );
            }
            $obj = new GradeBookBackup($cats);
            $this->course->add_resource($obj);
        }
    }
}
