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

    $entityManager = Database::getManager();

    $evaluationId = $values['hid_id'];
    if ($evaluationId) {
        $evaluation = $entityManager->getRepository(GradebookEvaluation::class)->find($evaluationId);
    } else {
        $evaluation = new GradebookEvaluation();
        $entityManager->persist($evaluation);
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

Display::display_header(get_lang('Edit evaluation'));
$form->display();
Display::display_footer();
