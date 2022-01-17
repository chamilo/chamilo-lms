<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy;

use Chamilo\CourseBundle\Component\CourseCopy\Resources\Document;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Resource;
use Database;
use Display;

/**
 * Class to show a form to select resources.
 *
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @author Julio Montoya <gugli100@gmail.com>
 *
 * @package chamilo.backup
 */
class CourseSelectForm
{
    /**
     * @return array
     */
    public static function getResourceTitleList()
    {
        $list = [];
        $list[RESOURCE_LEARNPATH_CATEGORY] = get_lang('Learnpath').' '.get_lang('Category');
        $list[RESOURCE_ASSET] = get_lang('Assets');
        $list[RESOURCE_GRADEBOOK] = get_lang('Gradebook');
        $list[RESOURCE_EVENT] = get_lang('Events');
        $list[RESOURCE_ANNOUNCEMENT] = get_lang('Announcements');
        $list[RESOURCE_DOCUMENT] = get_lang('Documents');
        $list[RESOURCE_LINK] = get_lang('Links');
        $list[RESOURCE_COURSEDESCRIPTION] = get_lang('CourseDescription');
        $list[RESOURCE_FORUM] = get_lang('Forums');
        $list[RESOURCE_FORUMCATEGORY] = get_lang('ForumCategory');
        $list[RESOURCE_QUIZ] = get_lang('Tests');
        $list[RESOURCE_TEST_CATEGORY] = get_lang('QuestionCategory');
        $list[RESOURCE_LEARNPATH] = get_lang('ToolLearnpath');
        $list[RESOURCE_LEARNPATH_CATEGORY] = get_lang('LearnpathCategory');
        $list[RESOURCE_SCORM] = 'SCORM';
        $list[RESOURCE_TOOL_INTRO] = get_lang('ToolIntro');
        $list[RESOURCE_SURVEY] = get_lang('Survey');
        $list[RESOURCE_GLOSSARY] = get_lang('Glossary');
        $list[RESOURCE_WIKI] = get_lang('Wiki');
        $list[RESOURCE_THEMATIC] = get_lang('Thematic');
        $list[RESOURCE_ATTENDANCE] = get_lang('Attendance');
        $list[RESOURCE_WORK] = get_lang('ToolStudentPublication');
        if (\XApiPlugin::create()->isEnabled()) {
            $list[RESOURCE_XAPI_TOOL] = get_lang('ToolXapiActivity');
        }

        return $list;
    }

    /**
     * Display the form.
     *
     * @param array $course
     * @param array $hidden_fields     hidden fields to add to the form
     * @param bool  $avoidSerialize    the document array will be serialize.
     *                                 This is used in the course_copy.php file
     * @param bool  $avoidCourseInForm
     */
    public static function display_form(
        $course,
        $hidden_fields = null,
        $avoidSerialize = false,
        $avoidCourseInForm = false
    ) {
        global $charset; ?>
        <script>
            function exp(item) {
                el = document.getElementById('div_'+item);
                if (el.style.display == 'none') {
                    el.style.display = '';
                    $('#img_'+item).removeClass();
                    $('#img_'+item).addClass('fa fa-minus-square-o fa-lg');

                } else {
                    el.style.display = 'none';
                    $('#img_'+item).removeClass();
                    $('#img_'+item).addClass('fa fa-plus-square-o fa-lg');
                }
            }

            function setCheckboxForum(type, value, item_id) {
                d = document.course_select_form;
                for (i = 0; i < d.elements.length; i++) {
                    if (d.elements[i].type == "checkbox") {
                        var name = d.elements[i].attributes.getNamedItem('name').nodeValue;
                        if (name.indexOf(type) > 0 || type == 'all') {
                            if ($(d.elements[i]).attr('rel') == item_id) {
                                d.elements[i].checked = value;
                            }
                        }
                    }
                }
            }

            function setCheckbox(type,value) {
                d = document.course_select_form;
                for (i = 0; i < d.elements.length; i++) {
                    if (d.elements[i].type == "checkbox") {
                        var name = d.elements[i].attributes.getNamedItem('name').nodeValue;
                        if( name.indexOf(type) > 0 || type == 'all' ){
                             d.elements[i].checked = value;
                        }
                    }
                }
            }

            function checkLearnPath(message){
                d = document.course_select_form;
                var backup = (typeof d.destination_course === 'undefined');
                for (i = 0; i < d.elements.length; i++) {
                    if (d.elements[i].type == "checkbox") {
                        var name = d.elements[i].attributes.getNamedItem('name').nodeValue;
                        if( name.indexOf('learnpath') > 0 || name.indexOf('quiz') > 0){
                            if(d.elements[i].checked){
                                if (!backup) {
                                    //setCheckbox('document', true);
                                }
                                alert(message);
                                break;
                            }
                        }
                    }
                }
            }

            function check_forum(obj) {
                var id = $(obj).attr('rel');
                var my_id = $(obj).attr('my_rel');
                var checked = false;
                if ($('#resource_forum_'+my_id).attr('checked')) {
                    checked = true;
                }
                setCheckboxForum('thread', checked, my_id);
                $('#resource_Forum_Category_'+id).attr('checked','checked');
            }

             function check_category(obj) {
                var my_id = $(obj).attr('my_rel');
                var checked = false;
                if ($('#resource_Forum_Category_'+my_id).attr('checked')) {
                    checked = true;
                }
                $('.resource_forum').each(function(index, value) {
                    if ($(value).attr('rel') == my_id) {
                        $(value).attr('checked', checked);
                    }
                });

                $('.resource_topic').each(function(index, value) {
                    if ($(value).attr('cat_id') == my_id) {
                        $(value).attr('checked', checked);
                    }
                });
            }

            function check_topic(obj) {
                var my_id = $(obj).attr('cat_id');
                var forum_id = $(obj).attr('forum_id');
                $('#resource_Forum_Category_'+my_id).attr('checked','checked');
                $('#resource_forum_'+forum_id).attr('checked','checked');
            }
        </script>
        <?php
        // get destination course title
        if (!empty($hidden_fields['destination_course'])) {
            $sessionTitle = !empty($hidden_fields['destination_session']) ? ' ('.api_get_session_name($hidden_fields['destination_session']).')' : null;
            $courseInfo = api_get_course_info($hidden_fields['destination_course']);
            echo '<h3>';
            echo get_lang('DestinationCourse').' : '.$courseInfo['title'].' ('.$courseInfo['code'].') '.$sessionTitle;
            echo '</h3>';
        }

        echo '<script src="'.api_get_path(WEB_CODE_PATH).'inc/lib/javascript/upload.js" type="text/javascript"></script>';
        echo '<div class="tool-backups-options">';
        echo '<form method="post" id="upload_form" name="course_select_form">';
        echo '<input type="hidden" name="action" value="course_select_form"/>';

        if (!empty($hidden_fields['destination_course']) &&
            !empty($hidden_fields['origin_course']) &&
            !empty($hidden_fields['destination_session']) &&
            !empty($hidden_fields['origin_session'])
        ) {
            echo '<input type="hidden" name="destination_course" value="'.$hidden_fields['destination_course'].'"/>';
            echo '<input type="hidden" name="origin_course" value="'.$hidden_fields['origin_course'].'"/>';
            echo '<input type="hidden" name="destination_session" value="'.$hidden_fields['destination_session'].'"/>';
            echo '<input type="hidden" name="origin_session" value="'.$hidden_fields['origin_session'].'"/>';
        }

        $forum_categories = [];
        $forums = [];
        $forum_topics = [];

        echo '<p>';
        echo get_lang('SelectResources');
        echo '</p>';
        echo Display::return_message(get_lang('DontForgetToSelectTheMediaFilesIfYourResourceNeedIt'));

        $resource_titles = self::getResourceTitleList();
        $element_count = self::parseResources($resource_titles, $course->resources, true, true);

        // Fixes forum order
        if (!empty($forum_categories)) {
            $type = RESOURCE_FORUMCATEGORY;
            echo '<div class="item-backup" onclick="javascript:exp('."'$type'".');">';
            echo '<em id="img_'.$type.'" class="fa fa-minus-square-o fa-lg"></em>';
            echo '<span class="title">'.$resource_titles[RESOURCE_FORUM].'</span></div>';
            echo '<div class="item-content" id="div_'.$type.'">';
            echo '<ul class="list-backups-options">';
            foreach ($forum_categories as $forum_category_id => $forum_category) {
                echo '<li>';
                echo '<label class="checkbox">';
                echo '<input type="checkbox"
                    id="resource_'.RESOURCE_FORUMCATEGORY.'_'.$forum_category_id.'"
                    my_rel="'.$forum_category_id.'"
                    onclick="javascript:check_category(this);"
                    name="resource['.RESOURCE_FORUMCATEGORY.']['.$forum_category_id.']" /> ';
                $forum_category->show();
                echo '</label>';
                echo '</li>';

                if (isset($forums[$forum_category_id])) {
                    $my_forums = $forums[$forum_category_id];
                    echo '<ul>';
                    foreach ($my_forums as $forum_id => $forum) {
                        echo '<li>';
                        echo '<label class="checkbox">';
                        echo '<input type="checkbox"
                            class="resource_forum"
                            id="resource_'.RESOURCE_FORUM.'_'.$forum_id.'"
                            onclick="javascript:check_forum(this);"
                            my_rel="'.$forum_id.'"
                            rel="'.$forum_category_id.'"
                            name="resource['.RESOURCE_FORUM.']['.$forum_id.']" />';
                        $forum->show();
                        echo '</label>';
                        echo '</li>';
                        if (isset($forum_topics[$forum_id])) {
                            $my_forum_topics = $forum_topics[$forum_id];
                            if (!empty($my_forum_topics)) {
                                echo '<ul>';
                                foreach ($my_forum_topics as $topic_id => $topic) {
                                    echo '<li>';
                                    echo '<label class="checkbox">';
                                    echo '<input
                                        type="checkbox"
                                        id="resource_'.RESOURCE_FORUMTOPIC.'_'.$topic_id.'"
                                        onclick="javascript:check_topic(this);" class="resource_topic"
                                        forum_id="'.$forum_id.'"
                                        rel="'.$forum_id.'"
                                        cat_id="'.$forum_category_id.'"
                                        name="resource['.RESOURCE_FORUMTOPIC.']['.$topic_id.']" />';
                                    $topic->show();
                                    echo '</label>';
                                    echo '</li>';
                                }
                                echo '</ul>';
                            }
                        }
                    }
                    echo '</ul>';
                }
                echo '<hr/>';
            }
            echo '</ul>';
            echo '</div>';
            echo '<script language="javascript">exp('."'$type'".')</script>';
        }

        if ($avoidSerialize) {
            /*Documents are avoided due the huge amount of memory that the serialize php function "eats"
            (when there are directories with hundred/thousand of files) */
            // this is a known issue of serialize
            $course->resources['document'] = null;
        }

        if ($avoidCourseInForm === false) {
            /** @var Course $course */
            $courseSerialized = base64_encode(Course::serialize($course));
            echo '<input type="hidden" name="course" value="'.$courseSerialized.'"/>';
        }

        if (is_array($hidden_fields)) {
            foreach ($hidden_fields as $key => $value) {
                echo '<input type="hidden" name="'.$key.'" value="'.$value.'"/>';
            }
        }

        $recycleOption = isset($_POST['recycle_option']) ? true : false;
        if (empty($element_count)) {
            echo Display::return_message(get_lang('NoDataAvailable'), 'warning');
        } else {
            if (!empty($hidden_fields['destination_session'])) {
                echo '<br />
                      <button
                        class="save"
                        type="submit"
                        onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES, $charset))."'".')) return false;" >'.
                    get_lang('Ok').'</button>';
            } else {
                if ($recycleOption) {
                    echo '<br /><button class="save" type="submit">'.get_lang('Ok').'</button>';
                } else {
                    echo '<br />
                          <button
                                class="save btn btn-primary"
                                type="submit"
                                onclick="checkLearnPath(\''.addslashes(get_lang('DocumentsWillBeAddedToo')).'\')">'.
                    get_lang('Ok').'</button>';
                }
            }
        }

        self::display_hidden_quiz_questions($course);
        self::display_hidden_scorm_directories($course);
        echo '</form>';
        echo '</div>';
        echo '<div id="dynamic_div" style="display:block;margin-left:40%;margin-top:10px;height:50px;"></div>';
    }

    /**
     * @param array $resource_titles
     * @param array $resourceList
     * @param bool  $showHeader
     * @param bool  $showItems
     *
     * @return int
     */
    public static function parseResources(
        $resource_titles,
        $resourceList,
        $showHeader = true,
        $showItems = true
    ) {
        $element_count = 0;
        foreach ($resourceList as $type => $resources) {
            if (count($resources) > 0) {
                switch ($type) {
                    // Resources to avoid
                    case RESOURCE_FORUMCATEGORY:
                        foreach ($resources as $id => $resource) {
                            $forum_categories[$id] = $resource;
                        }
                        $element_count++;
                        break;
                    case RESOURCE_FORUM:
                        foreach ($resources as $id => $resource) {
                            $forums[$resource->obj->forum_category][$id] = $resource;
                        }
                        $element_count++;
                        break;
                    case RESOURCE_FORUMTOPIC:
                        foreach ($resources as $id => $resource) {
                            $forum_topics[$resource->obj->forum_id][$id] = $resource;
                        }
                        $element_count++;
                        break;
                    case RESOURCE_LINKCATEGORY:
                    case RESOURCE_FORUMPOST:
                    case RESOURCE_QUIZQUESTION:
                    case RESOURCE_SURVEYQUESTION:
                    case RESOURCE_SURVEYINVITATION:
                    case RESOURCE_SCORM:
                        break;
                    default:
                        if ($showHeader) {
                            echo '<div class="item-backup" onclick="javascript:exp('."'$type'".');">';
                            echo '<em id="img_'.$type.'" class="fa fa-plus-square-o fa-lg"></em>';
                            echo '<span class="title">'.$resource_titles[$type].'</span>';
                            echo '</div>';
                            echo '<div class="item-content" id="div_'.$type.'">';
                        }

                        if ($type == RESOURCE_LEARNPATH) {
                            echo Display::return_message(
                                get_lang(
                                    'ToExportLearnpathWithQuizYouHaveToSelectQuiz'
                                ),
                                'warning'
                            );
                            echo Display::return_message(
                                get_lang(
                                    'IfYourLPsHaveAudioFilesIncludedYouShouldSelectThemFromTheDocuments'
                                ),
                                'warning'
                            );
                        }

                        if ($type == RESOURCE_DOCUMENT) {
                            if (api_get_setting('show_glossary_in_documents') != 'none') {
                                echo Display::return_message(
                                    get_lang(
                                        'ToExportDocumentsWithGlossaryYouHaveToSelectGlossary'
                                    ),
                                    'warning'
                                );
                            }
                        }

                        if ($type == RESOURCE_QUIZ) {
                            echo Display::return_message(
                                get_lang(
                                    'IfYourQuizHaveHotspotQuestionsIncludedYouShouldSelectTheImagesFromTheDocuments'
                                ),
                                'warning'
                            );
                        }

                        if ($showItems) {
                            echo '<div class="well">';
                            echo '<div class="btn-group">';
                            echo "<a class=\"btn btn-default\"
                                        href=\"javascript: void(0);\"
                                        onclick=\"javascript: setCheckbox('$type',true);\" >".get_lang('All')."</a>";
                            echo "<a class=\"btn btn-default\"
                                        href=\"javascript: void(0);\"
                                        onclick=\"javascript:setCheckbox('$type',false);\" >".get_lang('None')."</a>";
                            echo '</div>';
                            echo '<ul class="list-backups-options">';
                            foreach ($resources as $id => $resource) {
                                if ($resource) {
                                    echo '<li>';
                                    // Event obj in 1.9.x in 1.10.x the class is CalendarEvent
                                    Resource::setClassType($resource);
                                    echo '<label class="checkbox">';
                                    echo '<input
                                        type="checkbox"
                                        name="resource['.$type.']['.$id.']"
                                        id="resource['.$type.']['.$id.']" />';
                                    $resource->show();
                                    echo '</label>';
                                    echo '</li>';
                                }
                            }
                            echo '</ul>';
                            echo '</div>';
                        }

                        if ($showHeader) {
                            echo '</div>';
                            echo '<script language="javascript">exp('."'$type'".')</script>';
                        }
                        $element_count++;
                }
            }
        }

        return $element_count;
    }

    /**
     * @param $course
     */
    public static function display_hidden_quiz_questions($course)
    {
        if (is_array($course->resources)) {
            foreach ($course->resources as $type => $resources) {
                if (!empty($resources) && count($resources) > 0) {
                    switch ($type) {
                        case RESOURCE_QUIZQUESTION:
                            foreach ($resources as $id => $resource) {
                                echo '<input
                                    type="hidden"
                                    name="resource['.RESOURCE_QUIZQUESTION.']['.$id.']"
                                    id="resource['.RESOURCE_QUIZQUESTION.']['.$id.']" value="On" />';
                            }
                            break;
                    }
                }
            }
        }
    }

    /**
     * @param $course
     */
    public static function display_hidden_scorm_directories($course)
    {
        if (is_array($course->resources)) {
            foreach ($course->resources as $type => $resources) {
                if (!empty($resources) && count($resources) > 0) {
                    switch ($type) {
                        case RESOURCE_SCORM:
                            foreach ($resources as $id => $resource) {
                                echo '<input
                                    type="hidden"
                                    name="resource['.RESOURCE_SCORM.']['.$id.']"
                                    id="resource['.RESOURCE_SCORM.']['.$id.']" value="On" />';
                            }
                            break;
                    }
                }
            }
        }
    }

    /**
     * Get the posted course.
     *
     * @param string $from         who calls the function?
     *                             It can be copy_course, create_backup, import_backup or recycle_course
     * @param int    $session_id
     * @param string $course_code
     * @param Course $postedCourse
     *
     * @return Course The course-object with all resources selected by the user
     *                in the form given by display_form(...)
     */
    public static function get_posted_course($from = '', $session_id = 0, $course_code = '', $postedCourse = null)
    {
        $course = $postedCourse;
        if (empty($postedCourse)) {
            $cb = new CourseBuilder();
            $postResource = isset($_POST['resource']) ? $_POST['resource'] : [];
            $course = $cb->build(0, null, false, array_keys($postResource), $postResource);
        }

        if (empty($course)) {
            return false;
        }

        // Create the resource DOCUMENT objects
        // Loading the results from the checkboxes of ethe javascript
        $resource = isset($_POST['resource'][RESOURCE_DOCUMENT]) ? $_POST['resource'][RESOURCE_DOCUMENT] : null;

        $course_info = api_get_course_info($course_code);
        $table_doc = Database::get_course_table(TABLE_DOCUMENT);
        $table_prop = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $course_id = $course_info['real_id'];

        /* Searching the documents resource that have been set to null because
        $avoidSerialize is true in the display_form() function*/
        if ($from === 'copy_course') {
            if (is_array($resource)) {
                $resource = array_keys($resource);
                foreach ($resource as $resource_item) {
                    $conditionSession = '';
                    if (!empty($session_id)) {
                        $session_id = (int) $session_id;
                        $conditionSession = ' AND d.session_id ='.$session_id;
                    }

                    $sql = 'SELECT d.id, d.path, d.comment, d.title, d.filetype, d.size
                            FROM '.$table_doc.' d
                            INNER JOIN '.$table_prop.' p
                            ON (d.c_id = p.c_id)
                            WHERE
                                d.c_id = '.$course_id.' AND
                                p.c_id = '.$course_id.' AND
                                tool = \''.TOOL_DOCUMENT.'\' AND
                                p.ref = d.id AND p.visibility != 2 AND
                                d.id = '.$resource_item.$conditionSession.'
                            ORDER BY path';
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
                        if ($doc) {
                            $course->add_resource($doc);
                            // adding item property
                            $sql = "SELECT * FROM $table_prop
                                    WHERE
                                        c_id = $course_id AND
                                        tool = '".RESOURCE_DOCUMENT."' AND
                                        ref = $resource_item ";
                            $res = Database::query($sql);
                            $all_properties = [];
                            while ($item_property = Database::fetch_array($res, 'ASSOC')) {
                                $all_properties[] = $item_property;
                            }
                            $course->resources[RESOURCE_DOCUMENT][$resource_item]->item_properties = $all_properties;
                        }
                    }
                }
            }
        }

        if (is_array($course->resources)) {
            foreach ($course->resources as $type => $resources) {
                switch ($type) {
                    case RESOURCE_SURVEYQUESTION:
                        foreach ($resources as $id => $obj) {
                            if (isset($_POST['resource'][RESOURCE_SURVEY]) &&
                                is_array($_POST['resource'][RESOURCE_SURVEY]) &&
                                !in_array($obj->survey_id, array_keys($_POST['resource'][RESOURCE_SURVEY]))
                            ) {
                                unset($course->resources[$type][$id]);
                            }
                        }
                        break;
                    case RESOURCE_FORUMTOPIC:
                    case RESOURCE_FORUMPOST:
                       //Add post from topic
                        if ($type == RESOURCE_FORUMTOPIC) {
                            $posts_to_save = [];
                            $posts = $course->resources[RESOURCE_FORUMPOST];
                            foreach ($resources as $thread_id => $obj) {
                                if (!isset($_POST['resource'][RESOURCE_FORUMTOPIC][$thread_id])) {
                                    unset($course->resources[RESOURCE_FORUMTOPIC][$thread_id]);
                                    continue;
                                }
                                $forum_id = $obj->obj->forum_id;
                                $title = $obj->obj->thread_title;
                                foreach ($posts as $post_id => $post) {
                                    if ($post->obj->thread_id == $thread_id &&
                                        $forum_id == $post->obj->forum_id &&
                                        $title == $post->obj->post_title
                                    ) {
                                        $posts_to_save[] = $post_id;
                                    }
                                }
                            }
                            if (!empty($posts)) {
                                foreach ($posts as $post_id => $post) {
                                    if (!in_array($post_id, $posts_to_save)) {
                                        unset($course->resources[RESOURCE_FORUMPOST][$post_id]);
                                    }
                                }
                            }
                        }
                        break;
                    case RESOURCE_LEARNPATH:
                        $lps = isset($_POST['resource'][RESOURCE_LEARNPATH]) ? $_POST['resource'][RESOURCE_LEARNPATH] : null;

                        if (!empty($lps)) {
                            foreach ($lps as $id => $obj) {
                                $lp_resource = $course->resources[RESOURCE_LEARNPATH][$id];

                                if (isset($lp_resource) && !empty($lp_resource) && isset($lp_resource->items)) {
                                    foreach ($lp_resource->items as $item) {
                                        switch ($item['item_type']) {
                                            //Add links added in a LP see #5760
                                            case 'link':
                                                $_POST['resource'][RESOURCE_LINK][$item['path']] = 1;
                                                break;
                                        }
                                    }
                                }
                            }
                        }
                        // no break
                    case RESOURCE_LINKCATEGORY:
                    case RESOURCE_FORUMCATEGORY:
                    case RESOURCE_QUIZQUESTION:
                    case RESOURCE_DOCUMENT:
                        // Mark folders to import which are not selected by the user to import,
                        // but in which a document was selected.
                        $documents = isset($_POST['resource'][RESOURCE_DOCUMENT]) ? $_POST['resource'][RESOURCE_DOCUMENT] : null;
                        if (!empty($resources) && is_array($resources)) {
                            foreach ($resources as $id => $obj) {
                                if (isset($obj->file_type) && $obj->file_type === 'folder' &&
                                    !isset($_POST['resource'][RESOURCE_DOCUMENT][$id]) &&
                                    is_array($documents)
                                ) {
                                    foreach ($documents as $id_to_check => $post_value) {
                                        if (isset($resources[$id_to_check])) {
                                            $obj_to_check = $resources[$id_to_check];
                                            $shared_path_part = substr(
                                                $obj_to_check->path,
                                                0,
                                                strlen($obj->path)
                                            );
                                            if ($id_to_check != $id && $obj->path == $shared_path_part) {
                                                $_POST['resource'][RESOURCE_DOCUMENT][$id] = 1;
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        // no break
                    default:
                        if (!empty($resources) && is_array($resources)) {
                            foreach ($resources as $id => $obj) {
                                $resource_is_used_elsewhere = $course->is_linked_resource($obj);
                                // check if document is in a quiz (audio/video)
                                if ($type == RESOURCE_DOCUMENT && $course->has_resources(RESOURCE_QUIZ)) {
                                    foreach ($course->resources[RESOURCE_QUIZ] as $quiz) {
                                        $quiz = $quiz->obj;
                                        if (isset($quiz->media) && $quiz->media == $id) {
                                            $resource_is_used_elsewhere = true;
                                        }
                                    }
                                }
                                // quiz question can be, not attached to an exercise
                                if ($type != RESOURCE_QUIZQUESTION) {
                                    if (!isset($_POST['resource'][$type][$id]) && !$resource_is_used_elsewhere) {
                                        unset($course->resources[$type][$id]);
                                    }
                                }
                            }
                        }
                }
            }
        }

        return $course;
    }

    /**
     * Display the form session export.
     *
     * @param array $list_course
     * @param array $hidden_fields  hidden fields to add to the form
     * @param bool  $avoidSerialize the document array will be serialize. This is used in the course_copy.php file
     */
    public static function display_form_session_export(
        $list_course,
        $hidden_fields = null,
        $avoidSerialize = false
    ) {
        ?>
        <script>
            function exp(item) {
                el = document.getElementById('div_'+item);
                if (el.style.display == 'none') {
                    el.style.display = '';
                    if (document.getElementById('img_'+item).length)
                    document.getElementById('img_'+item).className('fa fa-minus-square-o fa-lg');
                } else {
                    el.style.display = 'none';
                    if (document.getElementById('img_'+item).length)
                    document.getElementById('img_'+item).className('fa fa-plus-square-o fa-lg');
                }
            }

            function setCheckbox(type,value) {
                d = document.course_select_form;
                for (i = 0; i < d.elements.length; i++) {
                    if (d.elements[i].type == "checkbox") {
                        var name = d.elements[i].attributes.getNamedItem('name').nodeValue;
                        if( name.indexOf(type) > 0 || type == 'all' ){
                             d.elements[i].checked = value;
                        }
                    }
                }
            }
            function checkLearnPath(message){
                d = document.course_select_form;
                var backup = (typeof d.destination_course === 'undefined');
                for (i = 0; i < d.elements.length; i++) {
                    if (d.elements[i].type == "checkbox") {
                        var name = d.elements[i].attributes.getNamedItem('name').nodeValue;
                        if( name.indexOf('learnpath') > 0 || name.indexOf('quiz') > 0){
                            if(d.elements[i].checked){
                                if (!backup) {
                                    //setCheckbox('document', true);
                                }
                                alert(message);
                                break;
                            }
                        }
                    }
                }
            }
        </script>
        <?php

        //get destination course title
        if (!empty($hidden_fields['destination_course'])) {
            $sessionTitle = null;
            if (!empty($hidden_fields['destination_session'])) {
                $sessionTitle = ' ('.api_get_session_name($hidden_fields['destination_session']).')';
            }
            $courseInfo = api_get_course_info($hidden_fields['destination_course']);
            echo '<h3>';
            echo get_lang('DestinationCourse').' : '.$courseInfo['title'].$sessionTitle;
            echo '</h3>';
        }

        echo '<script src="'.api_get_path(WEB_CODE_PATH).'inc/lib/javascript/upload.js" type="text/javascript"></script>';
        $icon = Display::returnIconPath('progress_bar.gif');
        echo '<div class="tool-backups-options">';
        echo '<form method="post" id="upload_form" name="course_select_form">';
        echo '<input type="hidden" name="action" value="course_select_form"/>';
        foreach ($list_course as $course) {
            foreach ($course->resources as $type => $resources) {
                if (count($resources) > 0) {
                    echo '<div class="item-backup" onclick="javascript:exp('."'$course->code'".');">';
                    echo '<em id="img_'.$course->code.'" class="fa fa-minus-square-o fa-lg"></em>';
                    echo '<span class="title"> '.$course->code.'</span></div>';
                    echo '<div class="item-content" id="div_'.$course->code.'">';
                    echo '<blockquote>';

                    echo '<div class="btn-group">';
                    echo "<a class=\"btn\" href=\"#\" onclick=\"javascript:setCheckbox('".$course->code."',true);\" >".get_lang('All')."</a>";
                    echo "<a class=\"btn\" href=\"#\" onclick=\"javascript:setCheckbox('".$course->code."',false);\" >".get_lang('None')."</a>";
                    echo '</div>';

                    foreach ($resources as $id => $resource) {
                        echo '<label class="checkbox" for="resource['.$course->code.']['.$id.']">';
                        echo '<input type="checkbox" name="resource['.$course->code.']['.$id.']" id="resource['.$course->code.']['.$id.']"/>';
                        $resource->show();
                        echo '</label>';
                    }
                    echo '</blockquote>';
                    echo '</div>';
                    echo '<script type="text/javascript">exp('."'$course->code'".')</script>';
                }
            }
        }
        if ($avoidSerialize) {
            // Documents are avoided due the huge amount of memory that the serialize php
            // function "eats" (when there are directories with hundred/thousand of files)
            // this is a known issue of serialize
            $course->resources['document'] = null;
        }
        echo '<input type="hidden" name="course" value="'.base64_encode(Course::serialize($course)).'"/>';
        if (is_array($hidden_fields)) {
            foreach ($hidden_fields as $key => $value) {
                echo "\n";
                echo '<input type="hidden" name="'.$key.'" value="'.$value.'"/>';
            }
        }
        echo '<br /><button class="save" type="submit"
            onclick="checkLearnPath(\''.addslashes(get_lang('DocumentsWillBeAddedToo')).'\')">'.
            get_lang('Ok').'</button>';
        self::display_hidden_quiz_questions($course);
        self::display_hidden_scorm_directories($course);
        echo '</form>';
        echo '</div>';
        echo '<div id="dynamic_div" style="display:block;margin-left:40%;margin-top:10px;height:50px;"></div>';
    }
}
