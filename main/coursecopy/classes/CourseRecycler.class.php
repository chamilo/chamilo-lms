<?php
/* For licensing terms, see /license.txt */

require_once 'Course.class.php';

/**
 * Class to delete items from a Chamilo-course
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package chamilo.backup
 */
class CourseRecycler
{
    /**
     * A course-object with the items to delete
     */
    var $course;
    /**
     * Create a new CourseRecycler
     * @param course $course The course-object which contains the items to
     * delete
     */
    function CourseRecycler($course) {
        $this->course = $course;        
        $this->course_info = api_get_course_info($this->course->code);        
        $this->course_id = $this->course_info['real_id'];
    }
    /**
     * Delete all items from the course.
     * This deletes all items in the course-object from the current Chamilo-
     * course
     */
    function recycle() {
    	
        $table_tool_intro 		= Database::get_course_table(TABLE_TOOL_INTRO);
        $table_linked_resources = Database::get_course_table(TABLE_LINKED_RESOURCES);
        $table_item_properties 	= Database::get_course_table(TABLE_ITEM_PROPERTY);

        $this->recycle_links();
        $this->recycle_link_categories();
        $this->recycle_events();
        $this->recycle_announcements();
        $this->recycle_documents();
        $this->recycle_forums();
        $this->recycle_forum_categories();
        $this->recycle_quizzes();
        $this->recycle_surveys();
        $this->recycle_learnpaths();
        $this->recycle_cours_description();
        $this->recycle_wiki();
        $this->recycle_glossary();
        $this->recycle_thematic();
        $this->recycle_attendance();


        foreach ($this->course->resources as $type => $resources) {
            foreach ($resources as $id => $resource) {
                $sql = "DELETE FROM ".$table_linked_resources." WHERE c_id = ".$this->course_id." AND  (source_type = '".$type."' AND source_id = '".$id."') OR (resource_type = '".$type."' AND resource_id = '".$id."')  ";
                Database::query($sql);
                if (is_numeric($id)) {
                    $sql = "DELETE FROM ".$table_item_properties." WHERE c_id = ".$this->course_id." AND tool ='".$resource->get_tool()."' AND ref=".$id;
                    Database::query($sql);
                } elseif ($type == RESOURCE_TOOL_INTRO) {
                    $sql = "DELETE FROM $table_tool_intro WHERE c_id = ".$this->course_id." AND  id='$id'";
                    Database::query($sql);
                }
            }
        }

    }
    /**
     * Delete documents
     */
    function recycle_documents() {
        if ($this->course->has_resources(RESOURCE_DOCUMENT)) {
            $table = Database :: get_course_table(TABLE_DOCUMENT);
            foreach ($this->course->resources[RESOURCE_DOCUMENT] as $id => $document) {            	
                rmdirr($this->course->backup_path.'/'.$document->path);
            }
            $ids = implode(',', (array_keys($this->course->resources[RESOURCE_DOCUMENT])));
            $sql = "DELETE FROM ".$table." WHERE c_id = ".$this->course_id." AND id IN(".$ids.")";
            Database::query($sql);
        }
    }

    /**
     * Delete wiki
     */
    function recycle_wiki() {
        if ($this->course->has_resources(RESOURCE_WIKI)) {
            $table_wiki 		= Database::get_course_table(TABLE_WIKI);
            $table_wiki_conf 	= Database::get_course_table(TABLE_WIKI_CONF);
            //$table_wiki_discuss = Database::get_course_table(TABLE_WIKI_DISCUSS);
            //$table_wiki_mailcue = Database::get_course_table(TABLE_WIKI_MAILCUE);

            $pages = array();
            foreach ($this->course->resources[RESOURCE_WIKI] as $resource) {
                $pages[] = $resource->page_id;
            }
            $wiki_ids = implode(',', (array_keys($this->course->resources[RESOURCE_WIKI])));
            $page_ids = implode(',', $pages);

            $sql = "DELETE FROM ".$table_wiki." WHERE c_id = ".$this->course_id." AND id IN(".$wiki_ids.")";
            Database::query($sql);
            $sql = "DELETE FROM ".$table_wiki_conf." WHERE c_id = ".$this->course_id." AND page_id IN(".$page_ids.")";
            Database::query($sql);
        }
    }

    /**
     * Delete glossary
     */
    function recycle_glossary() {
        if ($this->course->has_resources(RESOURCE_GLOSSARY)) {
            $table_glossary	= Database::get_course_table(TABLE_GLOSSARY);
            $ids = implode(',', (array_keys($this->course->resources[RESOURCE_GLOSSARY])));
            $sql = "DELETE FROM ".$table_glossary." WHERE c_id = ".$this->course_id." AND glossary_id IN(".$ids.")";
            Database::query($sql);
        }
    }

    /**
     * Delete links
     */
    function recycle_links() {
        if ($this->course->has_resources(RESOURCE_LINK)) {
            $table = Database :: get_course_table(TABLE_LINK);
            $ids = implode(',', (array_keys($this->course->resources[RESOURCE_LINK])));
            $sql = "DELETE FROM ".$table." WHERE c_id = ".$this->course_id." AND id IN(".$ids.")";
            Database::query($sql);
        }
    }
    
    /**
     * Delete forums
     */
    function recycle_forums() {
        
        if ($this->course->has_resources(RESOURCE_FORUMCATEGORY)) {
            $table_category = Database :: get_course_table(TABLE_FORUM_CATEGORY);            
            $forum_ids = implode(',', (array_keys($this->course->resources[RESOURCE_FORUMCATEGORY])));
            echo $sql = "DELETE FROM ".$table_category." WHERE c_id = ".$this->course_id." AND cat_id IN(".$forum_ids.");";
            Database::query($sql);            
        }
        
        if ($this->course->has_resources(RESOURCE_FORUM)) {
            
            $table_forum = Database :: get_course_table(TABLE_FORUM);
            $table_thread = Database :: get_course_table(TABLE_FORUM_THREAD);
            $table_post = Database :: get_course_table(TABLE_FORUM_POST);
            $table_attachment = Database::get_course_table(TABLE_FORUM_ATTACHMENT);
            $table_notification = Database::get_course_table(TABLE_FORUM_NOTIFICATION);
            $table_mail_queue = Database::get_course_table(TABLE_FORUM_MAIL_QUEUE);
            $table_thread_qualify = Database::get_course_table(TABLE_FORUM_THREAD_QUALIFY);
            $table_thread_qualify_log = Database::get_course_table(TABLE_FORUM_THREAD_QUALIFY_LOG);
            
            

            $forum_ids = implode(',', (array_keys($this->course->resources[RESOURCE_FORUM])));

            $sql = "DELETE FROM ".$table_attachment.
                " USING ".$table_attachment." INNER JOIN ".$table_post.
                " WHERE c_id = ".$this->course_id." AND  ".$table_attachment.".post_id = ".$table_post.".post_id".
                " AND ".$table_post.".forum_id IN(".$forum_ids.");";
            Database::query($sql);

            $sql = "DELETE FROM ".$table_mail_queue." USING ".$table_mail_queue." INNER JOIN ".$table_post.
                " WHERE c_id = ".$this->course_id." AND  ".$table_mail_queue.".post_id = ".$table_post.".post_id".
                " AND ".$table_post.".forum_id IN(".$forum_ids.");";
            Database::query($sql);

            // Just in case, deleting in the same table using thread_id as record-linker.
            $sql = "DELETE FROM ".$table_mail_queue.
                " USING ".$table_mail_queue." INNER JOIN ".$table_thread.
                " WHERE c_id = ".$this->course_id." AND ".$table_mail_queue.".thread_id = ".$table_thread.".thread_id".
                " AND ".$table_thread.".forum_id IN(".$forum_ids.");";
            Database::query($sql);

            $sql = "DELETE FROM ".$table_thread_qualify.
                " USING ".$table_thread_qualify." INNER JOIN ".$table_thread.
                " WHERE c_id = ".$this->course_id." AND ".$table_thread_qualify.".thread_id = ".$table_thread.".thread_id".
                " AND ".$table_thread.".forum_id IN(".$forum_ids.");";
            Database::query($sql);

            $sql = "DELETE FROM ".$table_thread_qualify_log.
                " USING ".$table_thread_qualify_log." INNER JOIN ".$table_thread.
                " WHERE c_id = ".$this->course_id." AND ".$table_thread_qualify_log.".thread_id = ".$table_thread.".thread_id".
                " AND ".$table_thread.".forum_id IN(".$forum_ids.");";
            Database::query($sql);

            $sql = "DELETE FROM ".$table_notification." WHERE c_id = ".$this->course_id." AND forum_id IN(".$forum_ids.")";
            Database::query($sql);

            $sql = "DELETE FROM ".$table_post." WHERE c_id = ".$this->course_id." AND forum_id IN(".$forum_ids.")";
            Database::query($sql);

            $sql = "DELETE FROM ".$table_thread." WHERE c_id = ".$this->course_id." AND forum_id IN(".$forum_ids.")";
            Database::query($sql);

            $sql = "DELETE FROM ".$table_forum." WHERE c_id = ".$this->course_id." AND forum_id IN(".$forum_ids.")";
            Database::query($sql);
        }
    }
    
    /**
     * Delete forum-categories
     * Deletes all forum-categories from current course without forums
     */
    function recycle_forum_categories() {
        $table_forum = Database :: get_course_table(TABLE_FORUM);
        $table_forumcat = Database :: get_course_table(TABLE_FORUM_CATEGORY);
        $sql = "SELECT fc.cat_id FROM ".$table_forumcat." fc LEFT JOIN ".$table_forum." f ON fc.cat_id=f.forum_category 
        		WHERE fc.c_id = ".$this->course_id." AND f.c_id = ".$this->course_id." AND f.forum_id IS NULL";
        $res = Database::query($sql);
        while ($obj = Database::fetch_object($res)) {
            $sql = "DELETE FROM ".$table_forumcat." WHERE c_id = ".$this->course_id." AND cat_id = ".$obj->cat_id;
            Database::query($sql);
        }
    }
    /**
     * Delete link-categories
     * Deletes all empty link-categories (=without links) from current course
     */
    function recycle_link_categories() {
        $link_cat_table = Database :: get_course_table(TABLE_LINK_CATEGORY);
        $link_table = Database :: get_course_table(TABLE_LINK);
        $sql = "SELECT lc.id FROM ".$link_cat_table." lc LEFT JOIN ".$link_table." l ON lc.id=l.category_id 
        		WHERE l.id IS NULL";
        $res = Database::query($sql);
        while ($obj = Database::fetch_object($res)) {
            $sql = "DELETE FROM ".$link_cat_table." WHERE c_id = ".$this->course_id." AND id = ".$obj->id;
            Database::query($sql);
        }
    }
    
    /**
     * Delete events
     */
    function recycle_events() {
        if ($this->course->has_resources(RESOURCE_EVENT)) {
            $table = Database :: get_course_table(TABLE_AGENDA);
            $table_attachment = Database :: get_course_table(TABLE_AGENDA_ATTACHMENT);

            $ids = implode(',', (array_keys($this->course->resources[RESOURCE_EVENT])));
            $sql = "DELETE FROM ".$table." WHERE c_id = ".$this->course_id." AND id IN(".$ids.")";
            Database::query($sql);

            $sql = "DELETE FROM ".$table_attachment." WHERE c_id = ".$this->course_id." AND agenda_id IN(".$ids.")";
            Database::query($sql);
        }
    }
    /**
     * Delete announcements
     */
    function recycle_announcements() {
        if ($this->course->has_resources(RESOURCE_ANNOUNCEMENT)) {
            $table = Database :: get_course_table(TABLE_ANNOUNCEMENT);
            $table_attachment = Database :: get_course_table(TABLE_ANNOUNCEMENT_ATTACHMENT);

            $ids = implode(',', (array_keys($this->course->resources[RESOURCE_ANNOUNCEMENT])));
            $sql = "DELETE FROM ".$table." WHERE c_id = ".$this->course_id." AND id IN(".$ids.")";
            Database::query($sql);

            $sql = "DELETE FROM ".$table_attachment." WHERE c_id = ".$this->course_id." AND announcement_id IN(".$ids.")";
            Database::query($sql);
        }
    }
    /**
     * Recycle quizzes - doesn't remove the questions and their answers, as they might still be used later
     */
    function recycle_quizzes() {
    	
        if ($this->course->has_resources(RESOURCE_QUIZ)) {
            $table_qui_que 	= Database :: get_course_table(TABLE_QUIZ_QUESTION);
            $table_qui_ans 	= Database :: get_course_table(TABLE_QUIZ_ANSWER);
            $table_qui 		= Database :: get_course_table(TABLE_QUIZ_TEST);
            $table_rel 		= Database :: get_course_table(TABLE_QUIZ_TEST_QUESTION);

            $ids = array_keys($this->course->resources[RESOURCE_QUIZ]);
            $delete_orphan_questions = in_array(-1, $ids);
            $ids = implode(',', $ids);

            // Deletion of the normal tests, questions in them are not deleted, they become orphan at this moment.
            $sql = "DELETE FROM ".$table_qui." WHERE c_id = ".$this->course_id." AND id <> -1 AND id IN(".$ids.")";
            Database::query($sql);
            $sql = "DELETE FROM ".$table_rel." WHERE c_id = ".$this->course_id." AND exercice_id <> -1 AND exercice_id IN(".$ids.")";
            Database::query($sql);

            // Identifying again and deletion of the orphan questions, if it was desired.
            if ($delete_orphan_questions) {
                $sql = 'SELECT questions.id FROM '.$table_qui_que.' as questions LEFT JOIN '.$table_rel.' as quizz_questions 
                		ON questions.id=quizz_questions.question_id LEFT JOIN '.$table_qui.' as exercices 
                		ON exercice_id=exercices.id 
                		WHERE 
                			questions.c_id = '.$this->course_id.' AND
                			quizz_questions.c_id = '.$this->course_id.' AND
                			exercices.c_id = '.$this->course_id.' AND 
                			quizz_questions.exercice_id IS NULL OR exercices.active = -1'; // active = -1 means "deleted" test.
                $db_result = Database::query($sql);
                if (Database::num_rows($db_result) > 0) {
                    $orphan_ids = array();
                    while ($obj = Database::fetch_object($db_result)) {
                        $orphan_ids[] = $obj->id;
                    }
                    $orphan_ids = implode(',', $orphan_ids);
                    $sql = "DELETE FROM ".$table_rel." WHERE c_id = ".$this->course_id." AND question_id IN(".$orphan_ids.")";
                    Database::query($sql);
                    $sql = "DELETE FROM ".$table_qui_ans." WHERE c_id = ".$this->course_id." AND question_id IN(".$orphan_ids.")";
                    Database::query($sql);
                    $sql = "DELETE FROM ".$table_qui_que." WHERE c_id = ".$this->course_id." AND id IN(".$orphan_ids.")";
                    Database::query($sql);
                }
            }
            
            // Purge "deleted" tests (active = -1).
            $sql = "DELETE FROM ".$table_qui." WHERE c_id = ".$this->course_id." AND active = -1";
            Database::query($sql);            
        }        
    }
    
    /**
     * Recycle surveys - removes everything
     */
    function recycle_surveys() {
        if ($this->course->has_resources(RESOURCE_SURVEY)) {
            $table_survey = Database :: get_course_table(TABLE_SURVEY);
            $table_survey_q = Database :: get_course_table(TABLE_SURVEY_QUESTION);
            $table_survey_q_o = Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
            $table_survey_a = Database :: get_course_Table(TABLE_SURVEY_ANSWER);
            $table_survey_i = Database :: get_course_table(TABLE_SURVEY_INVITATION);
            $ids = implode(',', (array_keys($this->course->resources[RESOURCE_SURVEY])));
            $sql = "DELETE FROM ".$table_survey_i." WHERE c_id = ".$this->course_id." ";
            Database::query($sql);
            $sql = "DELETE FROM ".$table_survey_a." WHERE c_id = ".$this->course_id."  AND survey_id IN(".$ids.")";
            Database::query($sql);
            $sql = "DELETE FROM ".$table_survey_q_o." WHERE c_id = ".$this->course_id."  AND survey_id IN(".$ids.")";
            Database::query($sql);
            $sql = "DELETE FROM ".$table_survey_q." WHERE c_id = ".$this->course_id."  AND survey_id IN(".$ids.")";
            Database::query($sql);
            $sql = "DELETE FROM ".$table_survey." WHERE c_id = ".$this->course_id."  AND survey_id IN(".$ids.")";
            Database::query($sql);
        }
    }
    /**
     * Recycle learnpaths
     */
    function recycle_learnpaths() {
        if ($this->course->has_resources(RESOURCE_LEARNPATH)) {
            $table_main = Database :: get_course_table(TABLE_LP_MAIN);
            $table_item = Database :: get_course_table(TABLE_LP_ITEM);
            $table_view = Database :: get_course_table(TABLE_LP_VIEW);
            $table_iv   = Database :: get_course_table(TABLE_LP_ITEM_VIEW);
            $table_iv_int = Database::get_course_table(TABLE_LP_IV_INTERACTION);
            $table_tool = Database::get_course_table(TABLE_TOOL_LIST);
            
            foreach($this->course->resources[RESOURCE_LEARNPATH] as $id => $learnpath) {
                // See task #875.
                if ($learnpath->lp_type == 2) {
                    // This is a learning path of SCORM type.
                    if (trim($learnpath->path) != '')   // A sanity check for avoiding removal of the parent folder scorm/
                                                        // when $learnpath->path value is incorrect for some reason.
                    {
                        // The directory trat contains files of the SCORM package is to be deleted.
                        $scorm_package_dir = realpath($this->course->path . 'scorm/' . $learnpath->path);
                        rmdirr($scorm_package_dir);
                    }
                }

                //remove links from course homepage
                $sql = "DELETE FROM $table_tool WHERE c_id = ".$this->course_id."  AND link LIKE '%lp_controller.php%lp_id=$id%' AND image='scormbuilder.gif'";
                Database::query($sql);
                //remove elements from lp_* tables (from bottom-up) by removing interactions, then item_view, then views and items, then paths
                $sql_items = "SELECT id FROM $table_item WHERE  c_id = ".$this->course_id."  AND lp_id=$id";
                $res_items = Database::query($sql_items);
                while ($row_item = Database::fetch_array($res_items)) {
                    //get item views
                    $sql_iv = "SELECT id FROM $table_iv WHERE  c_id = ".$this->course_id."  AND lp_item_id=".$row_item['id'];
                    $res_iv = Database::query($sql_iv);
                    while ($row_iv = Database::fetch_array($res_iv)) {
                        //delete interactions
                        $sql_iv_int_del = "DELETE FROM $table_iv_int WHERE  c_id = ".$this->course_id."  AND lp_iv_id = ".$row_iv['id'];
                        $res_iv_int_del = Database::query($sql_iv_int_del);
                    }
                    //delete item views
                    $sql_iv_del = "DELETE FROM $table_iv WHERE c_id = ".$this->course_id."  AND lp_item_id=".$row_item['id'];
                    $res_iv_del = Database::query($sql_iv_del);
                }
                //delete items
                $sql_items_del = "DELETE FROM $table_item WHERE c_id = ".$this->course_id."  AND lp_id=$id";
                $res_items_del = Database::query($sql_items_del);
                //delete views
                $sql_views_del = "DELETE FROM $table_view WHERE c_id = ".$this->course_id."  AND lp_id=$id";
                $res_views_del = Database::query($sql_views_del);
                //delete lps
                $sql_del = "DELETE FROM $table_main WHERE c_id = ".$this->course_id."  AND id = $id";
                $res_del = Database::query($sql_del);
            }
        }
    }
    /**
     * Delete course description
     */
    function recycle_cours_description() {
        if ($this->course->has_resources(RESOURCE_COURSEDESCRIPTION)) {
            $table = Database :: get_course_table(TABLE_COURSE_DESCRIPTION);
            $ids = implode(',', (array_keys($this->course->resources[RESOURCE_COURSEDESCRIPTION])));
            $sql = "DELETE FROM ".$table." WHERE  c_id = ".$this->course_id."  AND id IN(".$ids.")";
            Database::query($sql);
        }
    }
    /**
    * Recycle Thematics
    */
    function recycle_thematic($session_id = 0) {
        if ($this->course->has_resources(RESOURCE_THEMATIC)) {
            $table_thematic         = Database :: get_course_table(TABLE_THEMATIC);
            $table_thematic_advance = Database :: get_course_table(TABLE_THEMATIC_ADVANCE);
            $table_thematic_plan    = Database :: get_course_table(TABLE_THEMATIC_PLAN);
        	
            $resources = $this->course->resources;
            foreach ($resources[RESOURCE_THEMATIC] as $last_id => $thematic) {
                if (is_numeric($last_id)) {
                    
                    foreach($thematic->thematic_advance_list as $thematic_advance) {
                        $cond = array('id = ? AND  c_id = ?'=>array($thematic_advance['id'], $this->course_id));
                        api_item_property_update($this->course_info, 'thematic_advance', $thematic_advance['id'],'ThematicAdvanceDeleted', api_get_user_id());
                        Database::delete($table_thematic_advance, $cond);
                    }
                    
                    foreach($thematic->thematic_plan_list as $thematic_plan) {
                        $cond = array('id = ? AND  c_id = ?'=>array($thematic_plan['id'], $this->course_id));
                        api_item_property_update($this->course_info, 'thematic_plan', $thematic_advance['id'], 'ThematicPlanDeleted', api_get_user_id());
                        Database::delete($table_thematic_plan, $cond);
                    }
                    $cond = array('id = ? AND  c_id = ?'=>array($last_id, $this->course_id));
                    api_item_property_update($this->course_info, 'thematic', $last_id,'ThematicDeleted', api_get_user_id());
                    Database::delete($table_thematic,$cond);
                }
            }            
        }
    }
    
    /**
    * Recycle Attendances
    */
    function recycle_attendance($session_id = 0) {
        if ($this->course->has_resources(RESOURCE_ATTENDANCE)) {
            $table_attendance          = Database :: get_course_table(TABLE_ATTENDANCE);            
            $table_attendance_calendar = Database :: get_course_table(TABLE_ATTENDANCE_CALENDAR);
           
            $resources = $this->course->resources;
            foreach ($resources[RESOURCE_ATTENDANCE] as $last_id => $obj) {
                //print_r($obj);
                if (is_numeric($last_id)) {                    
                    foreach($obj->attendance_calendar as $attendance_calendar) {
                        $cond = array('id = ? AND c_id = ? '=>array($attendance_calendar['id'], $this->course_id));
                        Database::delete($table_attendance_calendar, $cond);
                    }
                    $cond = array('id = ? AND c_id = ?'=>array($last_id, $this->course_id));
                    Database::delete($table_attendance, $cond);
                    api_item_property_update($this->destination_course_info, TOOL_ATTENDANCE, $last_id,'AttendanceDeleted', api_get_user_id());
                }
            }
        }
    }
}
