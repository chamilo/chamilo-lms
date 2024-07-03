<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_GRADEBOOK;

api_protect_course_script(true);
api_block_anonymous_users();
GradebookUtils::block_students();

$select_cat = isset($_GET['selectcat']) ? (int) $_GET['selectcat'] : 0;
$is_allowedToEdit = api_is_course_admin();

$userId = api_get_user_id();

$evaladd = new Evaluation();
$evaladd->set_user_id($userId);
if (!empty($select_cat)) {
    $evaladd->set_category_id($_GET['selectcat']);
    $cat = Category::load($_GET['selectcat']);
    $evaladd->set_course_code($cat[0]->get_course_code());
} else {
    $evaladd->set_category_id(0);
}

$form = new EvalForm(
    EvalForm::TYPE_ADD,
    $evaladd,
    null,
    'add_eval_form',
    null,
    api_get_self().'?selectcat='.$select_cat.'&'.api_get_cidreq()
);

if ($form->validate()) {
    $values = $form->exportValues();
    $eval = new Evaluation();
    $eval->set_name($values['name']);
    $eval->set_description($values['description']);
    $eval->set_user_id($values['hid_user_id']);

    if (!empty($values['hid_course_code'])) {
        $eval->set_course_code($values['hid_course_code']);
    }

    // Always add the gradebook to the course
    $eval->set_course_code(api_get_course_id());
    $eval->set_category_id($values['hid_category_id']);

    $parent_cat = Category::load($values['hid_category_id']);
    $global_weight = $cat[0]->get_weight();
    $values['weight'] = $values['weight_mask'];

    $eval->set_weight($values['weight']);
    $eval->set_max($values['max']);
    $visible = 1;
    if (empty($values['visible'])) {
        $visible = 0;
    }
    $eval->set_visible($visible);
    $eval->add();

    $logInfo = [
        'tool' => TOOL_GRADEBOOK,
        'action' => 'new-eval',
        'action_details' => 'selectcat='.$eval->get_category_id(),
    ];
    Event::registerLog($logInfo);

    Skill::saveSkills($form, ITEM_TYPE_GRADEBOOK_EVALUATION, $eval->get_id());

    if (null == $eval->get_course_code()) {
        if (1 == $values['adduser']) {
            //Disabling code when course code is null see issue #2705
            //header('Location: gradebook_add_user.php?selecteval=' . $eval->get_id());
            exit;
        } else {
            header('Location: '.Category::getUrl().'selectcat='.$eval->get_category_id());
            exit;
        }
    } else {
        $val_addresult = isset($values['addresult']) ? $values['addresult'] : null;
        if (1 == $val_addresult) {
            header('Location: gradebook_add_result.php?selecteval='.$eval->get_id().'&'.api_get_cidreq());
            exit;
        } else {
            header('Location: '.Category::getUrl().'selectcat='.$eval->get_category_id());
            exit;
        }
    }
}

$logInfo = [
    'tool' => TOOL_GRADEBOOK,
    'tool_id' => 0,
    'tool_id_detail' => 0,
    'action' => 'add-eval',
    'action_details' => 'selectcat='.$select_cat,
];
Event::registerLog($logInfo);

$interbreadcrumb[] = [
    'url' => Category::getUrl().'selectcat='.$select_cat,
    'name' => get_lang('Gradebook'), ]
;
$this_section = SECTION_COURSES;

$htmlHeadXtra[] = '<script>
$(function() {
    $("#hid_category_id").change(function() {
       $("#hid_category_id option:selected").each(function () {
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

if ($evaladd->get_course_code() == null) {
    Display::addFlash(Display::return_message(get_lang('CourseIndependentEvaluation'), 'normal', false));
}

Display::display_header(get_lang('NewEvaluation'));

$form->display();
Display::display_footer();
