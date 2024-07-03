<?php

/* For licensing terms, see /license.txt */

/**
 * Script.
 */
require_once __DIR__.'/../inc/global.inc.php';
api_block_anonymous_users();
GradebookUtils::block_students();

$evaledit = Evaluation::load($_GET['editeval']);
if ($evaledit[0]->is_locked() && !api_is_platform_admin()) {
    api_not_allowed();
}
$form = new EvalForm(
    EvalForm::TYPE_EDIT,
    $evaledit[0],
    null,
    'edit_eval_form',
    null,
    api_get_self().'?editeval='.intval($_GET['editeval']).'&'.api_get_cidreq()
);
if ($form->validate()) {
    $values = $form->exportValues();
    $eval = new Evaluation();
    $eval->set_id($values['hid_id']);
    $eval->set_name($values['name']);
    $eval->set_description($values['description']);
    $eval->set_user_id($values['hid_user_id']);
    $eval->set_course_code($values['hid_course_code']);
    $eval->set_category_id($values['hid_category_id']);

    $parent_cat = Category::load($values['hid_category_id']);
    $final_weight = $values['weight_mask'];

    $eval->set_weight($final_weight);
    $eval->set_max($values['max']);

    $visible = 1;
    if (empty($values['visible'])) {
        $visible = 0;
    }
    $eval->set_visible($visible);
    $eval->save();

    $logInfo = [
        'tool' => TOOL_GRADEBOOK,
        'action' => 'edit-eval',
        'action_details' => '',
    ];
    Event::registerLog($logInfo);

    Skill::saveSkills($form, ITEM_TYPE_GRADEBOOK_EVALUATION, $values['hid_id']);

    header('Location: '.Category::getUrl().'editeval=&selectcat='.$eval->get_category_id());
    exit;
}
$selectcat_inter = isset($_GET['selectcat']) ? (int) $_GET['selectcat'] : 0;
$interbreadcrumb[] = [
    'url' => Category::getUrl().'selectcat='.$selectcat_inter,
    'name' => get_lang('Gradebook'),
];

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

Display::display_header(get_lang('EditEvaluation'));
$form->display();
Display::display_footer();
