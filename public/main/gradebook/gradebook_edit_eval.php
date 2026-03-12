<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Course;

require_once __DIR__.'/../inc/global.inc.php';
api_block_anonymous_users();
GradebookUtils::block_students();

$evaledit = Evaluation::load($_GET['editeval']);
if (empty($evaledit[0])) {
    api_not_allowed(true);
}
if (!api_is_platform_admin()) {
    $currentCourseId = api_get_course_int_id();

    if ($evaledit[0]->getCourseId() && $evaledit[0]->getCourseId() != $currentCourseId) {
        api_not_allowed(true);
    }

    if ($evaledit[0]->is_locked()) {
        api_not_allowed(true);
    }
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

    $entityManager = Database::getManager();

    $evaluationId = (int) $values['hid_id'];
    if ($evaluationId !== (int) $evaledit[0]->get_id()) {
        api_not_allowed(true);
    }
    $evaluation = $entityManager->getRepository(GradebookEvaluation::class)->find($evaluationId);
    if (!$evaluation) {
        api_not_allowed(true);
    }

    $evaluation->setTitle($values['name']);
    $evaluation->setDescription($values['description']);

    $course = $entityManager->getRepository(Course::class)->findOneBy(['code' => $values['hid_course_code']]);
    $evaluation->setCourse($course);

    $category = $entityManager->getRepository(GradebookCategory::class)->find($values['hid_category_id']);
    $evaluation->setCategory($category);

    $evaluation->setWeight($values['weight_mask']);
    $evaluation->setMax($values['max']);
    $evaluation->setVisible(empty($values['visible']) ? 0 : 1);

    if (isset($values['min_score'])) {
        $evaluation->setMinScore($values['min_score']);
    }

    $entityManager->flush();

    $logInfo = [
        'tool' => TOOL_GRADEBOOK,
        'tool_id' => 0,
        'tool_id_detail' => 0,
        'action' => $evaluationId ? 'edit-eval' : 'new-eval',
        'action_details' => '',
    ];
    Event::registerLog($logInfo);

    header('Location: '.Category::getUrl().'editeval=&selectcat='.$evaluation->getCategory()->getId());
    exit;
}
$selectcat_inter = isset($_GET['selectcat']) ? (int) $_GET['selectcat'] : 0;
$interbreadcrumb[] = [
    'url' => Category::getUrl().'selectcat='.$selectcat_inter,
    'name' => get_lang('Assessments'),
];

$htmlHeadXtra[] = '<script>
$(function() {
    $("#hid_category_id").change(function() {
       $("#hid_category_id option:selected").each(function () {
           var cat_id = $(this).val();
            $.ajax({
                url: "'.api_get_path(WEB_AJAX_PATH).'gradebook.ajax.php?'.api_get_cidreq().'&a=get_gradebook_weight",
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

Display::display_header(get_lang('Edit evaluation'));
$form->display();
Display::display_footer();
