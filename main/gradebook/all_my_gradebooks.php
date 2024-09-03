<?php

/* For licensing terms, see /license.txt */

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

if (api_get_configuration_value('show_all_my_gradebooks_page') !== true) {
    api_not_allowed(true);
}

// Setting the tabs
$this_section = SECTION_COURSES;

$htmlHeadXtra[] = '<script>
var show_icon = "'.Display::returnIconPath('view_more_stats.gif').'";
var hide_icon = "'.Display::returnIconPath('view_less_stats.gif').'";

$(function() {
    $("body").on("click", ".view_children", function() {
        var id = $(this).attr("data-cat-id");
        $(".hidden_"+id).removeClass("hidden");
        $(this).removeClass("view_children");
        $(this).find("img").attr("src", hide_icon);
        $(this).attr("class", "hide_children");
    });

    $("body").on("click", ".hide_children", function(event) {
        var id = $(this).attr("data-cat-id");
        $(".hidden_"+id).addClass("hidden");
        $(this).removeClass("hide_children");
        $(this).addClass("view_children");
        $(this).find("img").attr("src", show_icon);
    });

        for (i=0;i<$(".actions").length;i++) {
                if ($(".actions:eq("+i+")").html()=="<table border=\"0\"></table>" || $(".actions:eq("+i+")").html()=="" || $(".actions:eq("+i+")").html()==null || $(".actions:eq("+i+")").html().split("<TBODY></TBODY>").length==2) {
                        $(".actions:eq("+i+")").hide();
                }
        }
});
</script>';

Display::display_header(get_lang('GlobalGradebook'));

api_block_anonymous_users();

$user_id = api_get_user_id();
$userCoursesList = CourseManager::get_courses_list_by_user_id($user_id, true, false, false, [], true, true);

foreach ($userCoursesList as $course) {
    $course_code = $course['code'];
    $stud_id = $user_id;
    if (isset($course['session_id']) && $course['session_id'] > 0) {
        $session_id = $course['session_id'];
    } else {
        $session_id = 0;
    }
    $course_id = $course['real_id'];
    $courseInfo = api_get_course_info($course_code);

    if (!empty($course['session_name'])) {
        $title = "<h2>".$courseInfo['title']." (".$course['session_name'].")</h2>";
    } else {
        $title = "<h2>".$courseInfo['title']."</h2>";
    }

    $cats = Category::load(
        null,
        null,
        $course_code,
        null,
        null,
        $session_id,
        false
    );

    $showTitle = true;
    foreach ($cats as $cat) {
        $allcat = $cat->get_subcategories($stud_id, $course_code, $session_id);
        $alleval = $cat->get_evaluations($stud_id, false, $course_code, $session_id);
        $alllink = $cat->get_links($stud_id, true, $course_code, $session_id);

        if ($cat->get_parent_id() != 0) {
            $i++;
        } else {
            if (empty($allcat) && empty($alleval) && empty($alllink)) {
                continue;
            }
            if ($showTitle) {
                echo $title;
                $showTitle = false;
            }
            // This is the father
            // Create gradebook/add gradebook links.
            DisplayGradebook::header(
                $cat,
                0,
                $cat->get_id(),
                false,
                false,
                null,
                false,
                false,
                []
            );

            $gradebookTable = new GradebookTable(
                $cat,
                $allcat,
                $alleval,
                $alllink,
                null,
                false,
                null,
                api_get_user_id(),
                [],
                []
            );

            $table = '';
            $table = $gradebookTable->return_table();
            echo $table;
        }
    }
}

Display::display_footer();
