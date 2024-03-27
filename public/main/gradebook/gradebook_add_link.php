<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_GRADEBOOK;

api_protect_course_script(true);
api_block_anonymous_users();
GradebookUtils::block_students();

$courseCode = isset($_GET['course_code']) ? Security::remove_XSS($_GET['course_code']) : null;
$selectCat = isset($_GET['selectcat']) ? (int) $_GET['selectcat'] : 0;

$course_info = api_get_course_info($courseCode);
$tbl_forum_thread = Database::get_course_table(TABLE_FORUM_THREAD);
$tbl_link = Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK);

$session_id = api_get_session_id();
$typeSelected = isset($_GET['typeselected']) ? (int) $_GET['typeselected'] : null;

if (0 == $session_id) {
    $all_categories = Category::load(
        null,
        null,
        api_get_course_int_id(),
        null,
        null,
        $session_id
    );
} else {
    $all_categories = Category::loadSessionCategories(null, $session_id);
}
$category = Category :: load($selectCat);
$url = api_get_self().'?selectcat='.$selectCat.'&newtypeselected='.$typeSelected.'&course_code='.api_get_course_id().'&'.api_get_cidreq();
$typeform = new LinkForm(
    LinkForm::TYPE_CREATE,
    $category[0],
    null,
    'create_link',
    null,
    $url,
    $typeSelected
);

// if user selected a link type
if ($typeform->validate() && isset($_GET['newtypeselected'])) {
    // reload page, this time with a parameter indicating the selected type
    header(
        'Location: '.api_get_self().'?selectcat='.$selectCat
        .'&typeselected='.$typeform->exportValue('select_link')
        .'&course_code='.Security::remove_XSS($_GET['course_code']).'&'.api_get_cidreq()
    );
    exit;
}

// link type selected, show 2nd form to retrieve the link data
if (isset($typeSelected) && '0' != $typeSelected) {
    $url = api_get_self().'?selectcat='.$selectCat.'&typeselected='.$typeSelected.'&course_code='.$courseCode.'&'.api_get_cidreq();

    $addform = new LinkAddEditForm(
        LinkAddEditForm::TYPE_ADD,
        $all_categories,
        $typeSelected,
        null,
        'add_link',
        $url
    );

    if ($addform->validate()) {
        $addvalues = $addform->exportValues();
        $link = LinkFactory::create($typeSelected);
        $link->set_user_id(api_get_user_id());
        // Set course code *and* id
        $link->setCourseId(api_get_course_int_id());
        $link->set_category_id($addvalues['select_gradebook']);

        if ($link->needs_name_and_description()) {
            $link->set_name($addvalues['name']);
        } else {
            $link->set_ref_id($addvalues['select_link']);
        }

        $parent_cat = Category::load($addvalues['select_gradebook']);
        $global_weight = $category[0]->get_weight();
        $link->set_weight($addvalues['weight_mask']);

        if ($link->needs_max()) {
            $link->set_max($addvalues['max']);
        }

        if ($link->needs_name_and_description()) {
            $link->set_description($addvalues['description']);
        }
        $link->set_visible(empty($addvalues['visible']) ? 0 : 1);

        // Update view_properties
        if (isset($typeSelected) &&
            5 == $typeSelected &&
            (isset($addvalues['select_link']) && "" != $addvalues['select_link'])
        ) {
            $sql1 = 'SELECT title from '.$tbl_forum_thread.'
					 WHERE
					    c_id = '.$course_info['real_id'].' AND
					    iid  = '.$addvalues['select_link'];
            $res1 = Database::query($sql1);
            $rowtit = Database::fetch_row($res1);
            $course_id = api_get_course_id();
            $sql_l = 'SELECT count(*) FROM '.$tbl_link.'
                      WHERE
                            ref_id='.$addvalues['select_link'].' AND
                            course_code="'.$course_id.'" AND
                            type = 5;';
            $res_l = Database::query($sql_l);
            $row = Database::fetch_row($res_l);
            if (0 == $row[0]) {
                $link->add();
                $sql = 'UPDATE '.$tbl_forum_thread.' SET
                            thread_qualify_max= "'.api_float_val($addvalues['weight']).'",
                            thread_weight= "'.api_float_val($addvalues['weight']).'",
                            thread_title_qualify = "'.$rowtit[0].'"
						WHERE
						    iid ='.$addvalues['select_link'].' AND
						    c_id = '.$course_info['real_id'].' ';
                Database::query($sql);
            }
        }

        $link->add();
        $logInfo = [
            'tool' => TOOL_GRADEBOOK,
            'action' => 'new-link',
            'action_details' => 'selectcat='.$selectCat,
        ];
        Event::registerLog($logInfo);

        $addvalue_result = !empty($addvalues['addresult']) ? $addvalues['addresult'] : [];
        if (1 == $addvalue_result) {
            header('Location: gradebook_add_result.php?selecteval='.$link->get_ref_id().'&'.api_get_cidreq());
            exit;
        } else {
            header('Location: '.Category::getUrl().'linkadded=&selectcat='.$selectCat);
            exit;
        }
    }
}

$action_details = '';
$current_id = 0;
if (isset($_GET['selectcat'])) {
    $action_details = 'selectcat';
    $current_id = (int) $_GET['selectcat'];
}

$logInfo = [
    'tool' => TOOL_GRADEBOOK,
    'action' => 'add-link',
    'action_details' => 'selectcat='.$selectCat,
];
Event::registerLog($logInfo);

$interbreadcrumb[] = [
    'url' => Category::getUrl().'selectcat='.$selectCat,
    'name' => get_lang('Assessments'),
];
$this_section = SECTION_COURSES;

$htmlHeadXtra[] = '<script>
$(function() {
    $("#hide_category_id").change(function() {
       $("#hide_category_id option:selected").each(function () {
           var cat_id = $(this).val();
            $.ajax({
                url: "'.api_get_path(WEB_AJAX_PATH).'gradebook.ajax.php?a=get_gradebook_weight",
                data: "cat_id="+cat_id,
                success: function(return_value) {
                    if (return_value != 0 ) {
                        $("#max_weight").html(return_value);
                    }
                }
            });
       });
    });
});
</script>';

Display::display_header(get_lang('Add online activity'));
if (isset($typeform)) {
    echo Display::return_message(
        get_lang(
            'Warning: It is possible to use, in the gradebook, tests that are part of learning paths. If the learning path itself is already included, this test might be part of the gradebook already. The learning paths evaluation is made on the basis of a progress percentage, while the evaluation on tests is made on the basis of a score. Survey evaluation is based on whether the user has answered (1) or not (0). Make sure you test your combinations of gradebook evaluations to avoid mind-boggling situations.'
        ),
        'warning'
    );
    $typeform->display();
}
if (isset($addform)) {
    $addform->display();
}
Display::display_footer();
