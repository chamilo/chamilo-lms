<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy;

use Database;
use TestCategory;

/**
 * Class to delete items from a Chamilo-course.
 *
 * @author Bart Mollet <bart.mollet@hogent.be>
 *
 * @package chamilo.backup
 */
class CourseRecycler
{
    /**
     * A course-object with the items to delete.
     */
    public $course;
    public $type;

    /**
     * Create a new CourseRecycler.
     *
     * @param course $course The course-object which contains the items to
     *                       delete
     */
    public function __construct($course)
    {
        $this->course = $course;
        $this->course_info = api_get_course_info($this->course->code);
        $this->course_id = $this->course_info['real_id'];
    }

    /**
     * Delete all items from the course.
     * This deletes all items in the course-object from the current Chamilo-
     * course.
     *
     * @param string $backupType 'full_backup' or 'select_items'
     *
     * @return bool
     *
     * @assert (null) === false
     */
    public function recycle($backupType)
    {
        if (empty($backupType)) {
            return false;
        }

        $table_tool_intro = Database::get_course_table(TABLE_TOOL_INTRO);
        $table_item_properties = Database::get_course_table(TABLE_ITEM_PROPERTY);

        $this->type = $backupType;
        $this->recycle_links();
        $this->recycle_link_categories();
        $this->recycle_events();
        $this->recycle_announcements();
        $this->recycle_documents();
        $this->recycle_forums();
        $this->recycle_forum_categories();
        $this->recycle_quizzes();
        $this->recycle_test_category();
        $this->recycle_surveys();
        $this->recycle_learnpaths();
        $this->recycle_learnpath_categories();
        $this->recycle_cours_description();
        $this->recycle_wiki();
        $this->recycle_glossary();
        $this->recycle_thematic();
        $this->recycle_attendance();
        $this->recycle_work();

        foreach ($this->course->resources as $type => $resources) {
            foreach ($resources as $id => $resource) {
                if (is_numeric($id)) {
                    $sql = "DELETE FROM $table_item_properties
                            WHERE c_id = ".$this->course_id." AND tool ='".$resource->get_tool()."' AND ref=".$id;
                    Database::query($sql);
                } elseif ($type == RESOURCE_TOOL_INTRO) {
                    $sql = "DELETE FROM $table_tool_intro
                            WHERE c_id = ".$this->course_id." AND id='$id'";
                    Database::query($sql);
                }
            }
        }

        if ($backupType === 'full_backup') {
            \CourseManager::deleteCoursePicture($this->course_info['code']);
        }
    }

    /**
     * Delete documents.
     */
    public function recycle_documents()
    {
        $table = Database::get_course_table(TABLE_DOCUMENT);
        $tableItemProperty = Database::get_course_table(TABLE_ITEM_PROPERTY);

        if ($this->type === 'full_backup') {
            $sql = "DELETE FROM $tableItemProperty
                        WHERE
                            c_id = ".$this->course_id." AND
                            tool = '".TOOL_DOCUMENT."'";
            Database::query($sql);

            $sql = "DELETE FROM $table WHERE c_id = ".$this->course_id;
            Database::query($sql);

            // Delete all content in the documents.
            rmdirr($this->course->backup_path.'/document', true);
        } else {
            if ($this->course->has_resources(RESOURCE_DOCUMENT)) {
                foreach ($this->course->resources[RESOURCE_DOCUMENT] as $document) {
                    rmdirr($this->course->backup_path.'/'.$document->path);
                }

                $ids = implode(',', array_filter(array_keys($this->course->resources[RESOURCE_DOCUMENT])));
                if (!empty($ids)) {
                    $sql = "DELETE FROM $table
                            WHERE c_id = ".$this->course_id." AND id IN(".$ids.")";
                    Database::query($sql);
                }
            }
        }
    }

    /**
     * Delete wiki.
     */
    public function recycle_wiki()
    {
        if ($this->course->has_resources(RESOURCE_WIKI)) {
            $table_wiki = Database::get_course_table(TABLE_WIKI);
            $table_wiki_conf = Database::get_course_table(TABLE_WIKI_CONF);
            $pages = [];
            foreach ($this->course->resources[RESOURCE_WIKI] as $resource) {
                $pages[] = $resource->page_id;
            }

            $wiki_ids = implode(',', array_filter(array_keys($this->course->resources[RESOURCE_WIKI])));
            if (!empty($wiki_ids)) {
                $page_ids = implode(',', $pages);

                $sql = "DELETE FROM ".$table_wiki."
                        WHERE c_id = ".$this->course_id." AND id IN(".$wiki_ids.")";
                Database::query($sql);

                $sql = "DELETE FROM ".$table_wiki_conf."
                        WHERE c_id = ".$this->course_id." AND page_id IN(".$page_ids.")";
                Database::query($sql);
            }
        }
    }

    /**
     * Delete glossary.
     */
    public function recycle_glossary()
    {
        if ($this->course->has_resources(RESOURCE_GLOSSARY)) {
            $table = Database::get_course_table(TABLE_GLOSSARY);
            $ids = implode(',', array_filter(array_keys($this->course->resources[RESOURCE_GLOSSARY])));
            if (!empty($ids)) {
                $sql = "DELETE FROM $table
                        WHERE c_id = ".$this->course_id." AND glossary_id IN(".$ids.")";
                Database::query($sql);
            }
        }
    }

    /**
     * Delete links.
     */
    public function recycle_links()
    {
        if ($this->course->has_resources(RESOURCE_LINK)) {
            $table = Database::get_course_table(TABLE_LINK);
            $ids = implode(',', array_filter(array_keys($this->course->resources[RESOURCE_LINK])));
            if (!empty($ids)) {
                $sql = "DELETE FROM $table
                        WHERE c_id = ".$this->course_id." AND id IN(".$ids.")";
                Database::query($sql);
            }
        }
    }

    /**
     * Delete forums.
     */
    public function recycle_forums()
    {
        $table_category = Database::get_course_table(TABLE_FORUM_CATEGORY);
        $table_forum = Database::get_course_table(TABLE_FORUM);
        $table_thread = Database::get_course_table(TABLE_FORUM_THREAD);
        $table_post = Database::get_course_table(TABLE_FORUM_POST);
        $table_attachment = Database::get_course_table(TABLE_FORUM_ATTACHMENT);
        $table_notification = Database::get_course_table(TABLE_FORUM_NOTIFICATION);
        $table_mail_queue = Database::get_course_table(TABLE_FORUM_MAIL_QUEUE);
        $table_thread_qualify = Database::get_course_table(TABLE_FORUM_THREAD_QUALIFY);
        $table_thread_qualify_log = Database::get_course_table(TABLE_FORUM_THREAD_QUALIFY_LOG);

        if ($this->type === 'full_backup') {
            $sql = "DELETE FROM ".$table_category." WHERE c_id = ".$this->course_id;
            Database::query($sql);
            $sql = "DELETE FROM ".$table_forum." WHERE c_id = ".$this->course_id;
            Database::query($sql);
            $sql = "DELETE FROM ".$table_thread." WHERE c_id = ".$this->course_id;
            Database::query($sql);
            $sql = "DELETE FROM ".$table_post." WHERE c_id = ".$this->course_id;
            Database::query($sql);
            $sql = "DELETE FROM ".$table_attachment." WHERE c_id = ".$this->course_id;
            Database::query($sql);
            $sql = "DELETE FROM ".$table_notification." WHERE c_id = ".$this->course_id;
            Database::query($sql);
            $sql = "DELETE FROM ".$table_mail_queue." WHERE c_id = ".$this->course_id;
            Database::query($sql);
            $sql = "DELETE FROM ".$table_thread_qualify." WHERE c_id = ".$this->course_id;
            Database::query($sql);
            $sql = "DELETE FROM ".$table_thread_qualify_log." WHERE c_id = ".$this->course_id;
            Database::query($sql);
            $sql = "DELETE FROM ".$table_thread_qualify_log." WHERE c_id = ".$this->course_id;
            Database::query($sql);
        }

        if ($this->course->has_resources(RESOURCE_FORUMCATEGORY)) {
            $forum_ids = implode(',', array_filter(array_keys($this->course->resources[RESOURCE_FORUMCATEGORY])));
            if (!empty($forum_ids)) {
                $sql = "DELETE FROM ".$table_category."
                        WHERE c_id = ".$this->course_id." AND cat_id IN(".$forum_ids.");";
                Database::query($sql);
            }
        }

        if ($this->course->has_resources(RESOURCE_FORUM)) {
            $forum_ids = implode(',', array_filter(array_keys($this->course->resources[RESOURCE_FORUM])));

            if (empty($forum_ids)) {
                return false;
            }

            $sql = "DELETE FROM $table_attachment USING $table_attachment
                    INNER JOIN $table_post
                    WHERE   ".$table_post.".c_id = ".$this->course_id." AND
                            ".$table_attachment.".c_id = ".$this->course_id." AND
                            ".$table_attachment.".post_id = ".$table_post.".post_id".
                " AND ".$table_post.".forum_id IN(".$forum_ids.");";
            Database::query($sql);

            $sql = "DELETE FROM ".$table_mail_queue." USING ".$table_mail_queue." INNER JOIN $table_post
                    WHERE
                        ".$table_post.".c_id = ".$this->course_id." AND
                        ".$table_mail_queue.".c_id = ".$this->course_id." AND
                        ".$table_mail_queue.".post_id = ".$table_post.".post_id AND
                        ".$table_post.".forum_id IN(".$forum_ids.");";
            Database::query($sql);

            // Just in case, deleting in the same table using thread_id as record-linker.
            $sql = "DELETE FROM $table_mail_queue
                    USING ".$table_mail_queue." INNER JOIN $table_thread
                    WHERE
                        $table_mail_queue.c_id = ".$this->course_id." AND
                        $table_thread.c_id = ".$this->course_id." AND
                        $table_mail_queue.thread_id = ".$table_thread.".thread_id AND
                        $table_thread.forum_id IN(".$forum_ids.");";
            Database::query($sql);

            $sql = "DELETE FROM $table_thread_qualify
                    USING $table_thread_qualify INNER JOIN $table_thread
                    WHERE
                        $table_thread_qualify.c_id = ".$this->course_id." AND
                        $table_thread.c_id = ".$this->course_id." AND
                        $table_thread_qualify.thread_id = $table_thread.thread_id AND
                        $table_thread.forum_id IN(".$forum_ids.");";
            Database::query($sql);

            $sql = "DELETE FROM ".$table_thread_qualify_log.
                " USING ".$table_thread_qualify_log." INNER JOIN ".$table_thread.
                " WHERE
                    $table_thread_qualify_log.c_id = ".$this->course_id." AND
                    $table_thread.c_id = ".$this->course_id." AND
                    ".$table_thread_qualify_log.".thread_id = ".$table_thread.".thread_id AND
                    ".$table_thread.".forum_id IN(".$forum_ids.");";
            Database::query($sql);

            $sql = "DELETE FROM ".$table_notification."
                    WHERE c_id = ".$this->course_id." AND forum_id IN(".$forum_ids.")";
            Database::query($sql);

            $sql = "DELETE FROM ".$table_post."
                    WHERE c_id = ".$this->course_id." AND forum_id IN(".$forum_ids.")";
            Database::query($sql);

            $sql = "DELETE FROM ".$table_thread."
                    WHERE c_id = ".$this->course_id." AND forum_id IN(".$forum_ids.")";
            Database::query($sql);

            $sql = "DELETE FROM ".$table_forum."
                    WHERE c_id = ".$this->course_id." AND forum_id IN(".$forum_ids.")";
            Database::query($sql);
        }
    }

    /**
     * Deletes all forum-categories without forum from the current course.
     * Categories with forums in it are dealt with by recycle_forums()
     * This requires a check on the status of the forum item in c_item_property.
     */
    public function recycle_forum_categories()
    {
        $forumTable = Database::get_course_table(TABLE_FORUM);
        $forumCategoryTable = Database::get_course_table(TABLE_FORUM_CATEGORY);
        $itemPropertyTable = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $courseId = $this->course_id;
        // c_forum_forum.forum_category points to c_forum_category.cat_id and
        // has to be queried *with* the c_id to ensure a match
        $subQuery = "SELECT distinct(f.forum_category) as categoryId
              FROM $forumTable f, $itemPropertyTable i
              WHERE
                  f.c_id = $courseId AND
                  i.c_id = f.c_id AND
                  i.tool = 'forum' AND
        	      f.iid = i.ref AND
                  i.visibility = 1";
        $sql = "DELETE FROM $forumCategoryTable
                    WHERE c_id = $courseId AND cat_id NOT IN ($subQuery)";
        Database::query($sql);
    }

    /**
     * Deletes all empty link-categories (=without links) from current course.
     * Links are already dealt with by recycle_links() but if recycle is called
     * on categories and not on link, then non-empty categories will survive
     * the recycling.
     */
    public function recycle_link_categories()
    {
        $linkCategoryTable = Database::get_course_table(TABLE_LINK_CATEGORY);
        $linkTable = Database::get_course_table(TABLE_LINK);
        $itemPropertyTable = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $courseId = $this->course_id;
        // c_link.category_id points to c_link_category.id and
        // has to be queried *with* the c_id to ensure a match
        $subQuery = "SELECT distinct(l.category_id) as categoryId
              FROM $linkTable l, $itemPropertyTable i
              WHERE
                  l.c_id = $courseId AND
                  i.c_id = l.c_id AND
                  i.tool = 'link' AND
        	      l.iid = i.ref AND
                  i.visibility = 1";
        $sql = "DELETE FROM $linkCategoryTable
                    WHERE c_id = $courseId AND id NOT IN ($subQuery)";
        Database::query($sql);
    }

    /**
     * Delete events.
     */
    public function recycle_events()
    {
        if ($this->course->has_resources(RESOURCE_EVENT)) {
            $table = Database::get_course_table(TABLE_AGENDA);
            $table_attachment = Database::get_course_table(TABLE_AGENDA_ATTACHMENT);

            $ids = implode(',', array_filter(array_keys($this->course->resources[RESOURCE_EVENT])));
            if (!empty($ids)) {
                $sql = "DELETE FROM ".$table."
                        WHERE c_id = ".$this->course_id." AND id IN(".$ids.")";
                Database::query($sql);

                $sql = "DELETE FROM ".$table_attachment."
                        WHERE c_id = ".$this->course_id." AND agenda_id IN(".$ids.")";
                Database::query($sql);
            }
        }
    }

    /**
     * Delete announcements.
     */
    public function recycle_announcements()
    {
        if ($this->course->has_resources(RESOURCE_ANNOUNCEMENT)) {
            $table = Database::get_course_table(TABLE_ANNOUNCEMENT);
            $table_attachment = Database::get_course_table(TABLE_ANNOUNCEMENT_ATTACHMENT);

            $ids = implode(',', array_filter(array_keys($this->course->resources[RESOURCE_ANNOUNCEMENT])));
            if (!empty($ids)) {
                $sql = "DELETE FROM ".$table."
                        WHERE c_id = ".$this->course_id." AND id IN(".$ids.")";
                Database::query($sql);

                $sql = "DELETE FROM ".$table_attachment."
                        WHERE c_id = ".$this->course_id." AND announcement_id IN(".$ids.")";
                Database::query($sql);
            }
        }
    }

    /**
     * Recycle quizzes - doesn't remove the questions and their answers,
     * as they might still be used later.
     */
    public function recycle_quizzes()
    {
        if ($this->course->has_resources(RESOURCE_QUIZ)) {
            $table_qui_que = Database::get_course_table(TABLE_QUIZ_QUESTION);
            $table_qui_ans = Database::get_course_table(TABLE_QUIZ_ANSWER);
            $table_qui = Database::get_course_table(TABLE_QUIZ_TEST);
            $table_rel = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
            $table_qui_que_opt = Database::get_course_table(TABLE_QUIZ_QUESTION_OPTION);
            $table_qui_que_cat = Database::get_course_table(TABLE_QUIZ_QUESTION_CATEGORY);
            $table_qui_que_rel_cat = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);

            $ids = array_keys($this->course->resources[RESOURCE_QUIZ]);
            // If the value "-1" is in the ids of elements (questions) to
            // be deleted, then consider all orphan questions should be deleted
            // This value is set in CourseBuilder::quiz_build_questions()
            $delete_orphan_questions = in_array(-1, $ids);
            $ids = implode(',', $ids);

            if (!empty($ids)) {
                // Deletion of the tests first. Questions in these tests are
                //   not deleted and become orphan at this point
                $sql = "DELETE FROM ".$table_qui."
                        WHERE iid IN(".$ids.")";
                Database::query($sql);
                $sql = "DELETE FROM ".$table_rel."
                        WHERE c_id = ".$this->course_id." AND exercice_id IN(".$ids.")";
                Database::query($sql);
            }

            // Identifying again and deletion of the orphan questions, if it was desired.
            if ($delete_orphan_questions) {
                // If this query was ever too slow, there is an alternative here:
                // https://github.com/beeznest/chamilo-lms-icpna/commit/a38eab725402188dffff50245ee068d79bcef779
                $sql = " (
                        SELECT q.iid, ex.c_id FROM $table_qui_que q
                        INNER JOIN $table_rel r
                        ON q.iid = r.question_id

                        INNER JOIN $table_qui ex
                        ON (ex.iid = r.exercice_id AND ex.c_id = r.c_id)
                        WHERE ex.c_id = ".$this->course_id." AND (ex.active = '-1' OR ex.id = '-1')
                    )
                    UNION
                    (
                        SELECT q.iid, r.c_id FROM $table_qui_que q
                        LEFT OUTER JOIN $table_rel r
                        ON q.iid = r.question_id
                        WHERE q.c_id = ".$this->course_id." AND r.question_id is null
                    )
                    UNION
                    (
                        SELECT q.iid, r.c_id FROM $table_qui_que q
                        INNER JOIN $table_rel r
                        ON q.iid = r.question_id
                        WHERE r.c_id = ".$this->course_id." AND (r.exercice_id = '-1' OR r.exercice_id = '0')
                    )";
                $db_result = Database::query($sql);
                if (Database::num_rows($db_result) > 0) {
                    $orphan_ids = [];
                    while ($obj = Database::fetch_object($db_result)) {
                        $orphan_ids[] = $obj->iid;
                    }
                    $orphan_ids = implode(',', $orphan_ids);
                    $sql = "DELETE FROM ".$table_rel."
                            WHERE c_id = ".$this->course_id." AND question_id IN(".$orphan_ids.")";
                    Database::query($sql);
                    $sql = "DELETE FROM ".$table_qui_ans."
                            WHERE question_id IN(".$orphan_ids.")";
                    Database::query($sql);
                    $sql = "DELETE FROM ".$table_qui_que."
                            WHERE iid IN(".$orphan_ids.")";
                    Database::query($sql);
                }
                // Also delete questions categories and options
                $sql = "DELETE FROM $table_qui_que_rel_cat WHERE c_id = ".$this->course_id;
                Database::query($sql);
                $sql = "DELETE FROM $table_qui_que_cat WHERE c_id = ".$this->course_id;
                Database::query($sql);
                $sql = "DELETE FROM $table_qui_que_opt WHERE c_id = ".$this->course_id;
                Database::query($sql);
            }

            // Quizzes previously deleted are, in fact, kept with a status
            //  (active field) of "-1". Delete those, now.
            $sql = "DELETE FROM ".$table_qui." WHERE c_id = ".$this->course_id." AND active = -1";
            Database::query($sql);
        }
    }

    /**
     * Recycle tests categories.
     */
    public function recycle_test_category()
    {
        if (isset($this->course->resources[RESOURCE_TEST_CATEGORY])) {
            foreach ($this->course->resources[RESOURCE_TEST_CATEGORY] as $tab_test_cat) {
                $obj_cat = new TestCategory();
                $obj_cat->removeCategory($tab_test_cat->source_id);
            }
        }
    }

    /**
     * Recycle surveys - removes everything.
     */
    public function recycle_surveys()
    {
        if ($this->course->has_resources(RESOURCE_SURVEY)) {
            $table_survey = Database::get_course_table(TABLE_SURVEY);
            $table_survey_q = Database::get_course_table(TABLE_SURVEY_QUESTION);
            $table_survey_q_o = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);
            $table_survey_a = Database::get_course_Table(TABLE_SURVEY_ANSWER);
            $table_survey_i = Database::get_course_table(TABLE_SURVEY_INVITATION);
            $sql = "DELETE FROM $table_survey_i
                    WHERE c_id = ".$this->course_id;
            Database::query($sql);

            $ids = implode(',', array_filter(array_keys($this->course->resources[RESOURCE_SURVEY])));
            if (!empty($ids)) {
                $sql = "DELETE FROM $table_survey_a
                        WHERE c_id = ".$this->course_id."  AND survey_id IN(".$ids.")";
                Database::query($sql);

                $sql = "DELETE FROM $table_survey_q_o
                        WHERE c_id = ".$this->course_id."  AND survey_id IN(".$ids.")";
                Database::query($sql);

                $sql = "DELETE FROM $table_survey_q
                        WHERE c_id = ".$this->course_id."  AND survey_id IN(".$ids.")";
                Database::query($sql);

                $sql = "DELETE FROM $table_survey
                        WHERE c_id = ".$this->course_id."  AND survey_id IN(".$ids.")";
                Database::query($sql);
            }
        }
    }

    /**
     * Recycle learning paths.
     */
    public function recycle_learnpaths()
    {
        if ($this->course->has_resources(RESOURCE_LEARNPATH)) {
            $table_main = Database::get_course_table(TABLE_LP_MAIN);
            $table_item = Database::get_course_table(TABLE_LP_ITEM);
            $table_view = Database::get_course_table(TABLE_LP_VIEW);
            $table_iv = Database::get_course_table(TABLE_LP_ITEM_VIEW);
            $table_iv_int = Database::get_course_table(TABLE_LP_IV_INTERACTION);
            $table_tool = Database::get_course_table(TABLE_TOOL_LIST);

            foreach ($this->course->resources[RESOURCE_LEARNPATH] as $id => $learnpath) {
                // See task #875.
                if ($learnpath->lp_type == 2) {
                    // This is a learning path of SCORM type.
                    // A sanity check for avoiding removal of the parent folder scorm/
                    if (trim($learnpath->path) != '') {
                        // when $learnpath->path value is incorrect for some reason.
                        // The directory trat contains files of the SCORM package is to be deleted.
                        $scorm_package_dir = realpath($this->course->path.'scorm/'.$learnpath->path);
                        rmdirr($scorm_package_dir);
                    }
                }

                //remove links from course homepage
                $sql = "DELETE FROM $table_tool
                        WHERE
                            c_id = ".$this->course_id." AND
                            link LIKE '%lp_controller.php%lp_id=$id%' AND
                            image='scormbuilder.gif'";
                Database::query($sql);
                //remove elements from lp_* tables (from bottom-up)
                // by removing interactions, then item_view, then views and items, then paths
                $sql_items = "SELECT id FROM $table_item
                              WHERE c_id = ".$this->course_id."  AND lp_id=$id";
                $res_items = Database::query($sql_items);
                while ($row_item = Database::fetch_array($res_items)) {
                    //get item views
                    $sql_iv = "SELECT id FROM $table_iv
                               WHERE  c_id = ".$this->course_id."  AND lp_item_id=".$row_item['id'];
                    $res_iv = Database::query($sql_iv);
                    while ($row_iv = Database::fetch_array($res_iv)) {
                        //delete interactions
                        $sql_iv_int_del = "DELETE FROM $table_iv_int
                                           WHERE c_id = ".$this->course_id."  AND lp_iv_id = ".$row_iv['id'];
                        Database::query($sql_iv_int_del);
                    }
                    //delete item views
                    $sql_iv_del = "DELETE FROM $table_iv
                                   WHERE c_id = ".$this->course_id."  AND lp_item_id=".$row_item['id'];
                    Database::query($sql_iv_del);
                }
                //delete items
                $sql_items_del = "DELETE FROM $table_item WHERE c_id = ".$this->course_id."  AND lp_id=$id";
                Database::query($sql_items_del);
                //delete views
                $sql_views_del = "DELETE FROM $table_view WHERE c_id = ".$this->course_id."  AND lp_id=$id";
                Database::query($sql_views_del);
                //delete lps
                $sql_del = "DELETE FROM $table_main WHERE c_id = ".$this->course_id."  AND id = $id";
                Database::query($sql_del);
            }
        }
    }

    /**
     * Recycle selected learning path categories and dissociate learning paths
     * that are associated with it.
     */
    public function recycle_learnpath_categories()
    {
        $learningPathTable = Database::get_course_table(TABLE_LP_MAIN);
        $learningPathCategoryTable = Database::get_course_table(TABLE_LP_CATEGORY);
        $tblCTool = Database::get_course_table(TABLE_TOOL_LIST);

        if (isset($this->course->resources[RESOURCE_LEARNPATH_CATEGORY])) {
            foreach ($this->course->resources[RESOURCE_LEARNPATH_CATEGORY] as $id => $learnpathCategory) {
                $categoryId = $learnpathCategory->object->getId();
                //remove links from course homepage
                $sql = "DELETE FROM $tblCTool WHERE c_id = {$this->course_id}
                    AND link LIKE '%lp_controller.php%action=view_category&id=$categoryId%'";
                Database::query($sql);
                // Dissociate learning paths from categories that will be deleted
                $sql = "UPDATE $learningPathTable SET category_id = 0 WHERE category_id = ".$categoryId;
                Database::query($sql);
                $sql = "DELETE FROM $learningPathCategoryTable WHERE iid = ".$categoryId;
                Database::query($sql);
            }
        }
    }

    /**
     * Delete course description.
     */
    public function recycle_cours_description()
    {
        if ($this->course->has_resources(RESOURCE_COURSEDESCRIPTION)) {
            $table = Database::get_course_table(TABLE_COURSE_DESCRIPTION);
            $ids = implode(',', array_filter(array_keys($this->course->resources[RESOURCE_COURSEDESCRIPTION])));
            if (!empty($ids)) {
                $sql = "DELETE FROM $table
                        WHERE  c_id = ".$this->course_id."  AND id IN(".$ids.")";
                Database::query($sql);
            }
        }
    }

    /**
     * Recycle Thematics.
     */
    public function recycle_thematic($session_id = 0)
    {
        if ($this->course->has_resources(RESOURCE_THEMATIC)) {
            $table_thematic = Database::get_course_table(TABLE_THEMATIC);
            $table_thematic_advance = Database::get_course_table(TABLE_THEMATIC_ADVANCE);
            $table_thematic_plan = Database::get_course_table(TABLE_THEMATIC_PLAN);

            $resources = $this->course->resources;
            foreach ($resources[RESOURCE_THEMATIC] as $last_id => $thematic) {
                if (is_numeric($last_id)) {
                    foreach ($thematic->thematic_advance_list as $thematic_advance) {
                        $cond = [
                            'id = ? AND  c_id = ?' => [
                                $thematic_advance['id'],
                                $this->course_id,
                            ],
                        ];
                        api_item_property_update(
                            $this->course_info,
                            'thematic_advance',
                            $thematic_advance['id'],
                            'ThematicAdvanceDeleted',
                            api_get_user_id()
                        );
                        Database::delete($table_thematic_advance, $cond);
                    }

                    foreach ($thematic->thematic_plan_list as $thematic_plan) {
                        $cond = [
                            'id = ? AND  c_id = ?' => [
                                $thematic_plan['id'],
                                $this->course_id,
                            ],
                        ];
                        api_item_property_update(
                            $this->course_info,
                            'thematic_plan',
                            $thematic_advance['id'],
                            'ThematicPlanDeleted',
                            api_get_user_id()
                        );
                        Database::delete($table_thematic_plan, $cond);
                    }
                    $cond = [
                        'id = ? AND  c_id = ?' => [
                            $last_id,
                            $this->course_id,
                        ],
                    ];
                    api_item_property_update(
                        $this->course_info,
                        'thematic',
                        $last_id,
                        'ThematicDeleted',
                        api_get_user_id()
                    );
                    Database::delete($table_thematic, $cond);
                }
            }
        }
    }

    /**
     * Recycle Attendances.
     */
    public function recycle_attendance($session_id = 0)
    {
        if ($this->course->has_resources(RESOURCE_ATTENDANCE)) {
            $table_attendance = Database::get_course_table(TABLE_ATTENDANCE);
            $table_attendance_calendar = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR);

            $resources = $this->course->resources;
            foreach ($resources[RESOURCE_ATTENDANCE] as $last_id => $obj) {
                if (is_numeric($last_id)) {
                    foreach ($obj->attendance_calendar as $attendance_calendar) {
                        $cond = ['id = ? AND c_id = ? ' => [$attendance_calendar['id'], $this->course_id]];
                        Database::delete($table_attendance_calendar, $cond);
                    }
                    $cond = ['id = ? AND c_id = ?' => [$last_id, $this->course_id]];
                    Database::delete($table_attendance, $cond);
                    api_item_property_update(
                        $this->course_info,
                        TOOL_ATTENDANCE,
                        $last_id,
                        'AttendanceDeleted',
                        api_get_user_id()
                    );
                }
            }
        }
    }

    /**
     * Recycle Works.
     */
    public function recycle_work($session_id = 0)
    {
        if ($this->course->has_resources(RESOURCE_WORK)) {
            $table_work = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
            $table_work_assignment = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);

            $resources = $this->course->resources;
            foreach ($resources[RESOURCE_WORK] as $last_id => $obj) {
                if (is_numeric($last_id)) {
                    $cond = ['publication_id = ? AND c_id = ? ' => [$last_id, $this->course_id]];
                    Database::delete($table_work_assignment, $cond);
                    // The following also deletes student tasks
                    $cond = ['parent_id = ? AND c_id = ?' => [$last_id, $this->course_id]];
                    Database::delete($table_work, $cond);
                    // Finally, delete the main task registry
                    $cond = ['id = ? AND c_id = ?' => [$last_id, $this->course_id]];
                    Database::delete($table_work, $cond);
                    api_item_property_update(
                        $this->course_info,
                        TOOL_STUDENTPUBLICATION,
                        $last_id,
                        'StudentPublicationDeleted',
                        api_get_user_id()
                    );
                }
            }
        }
    }
}
