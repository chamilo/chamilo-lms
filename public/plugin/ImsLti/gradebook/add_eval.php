<?php
/* For licensing terms, see /license.txt */

/**
 * @package plugin.ims_lti
 */

use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;

require_once __DIR__.'/../../../main/inc/global.inc.php';

$current_course_tool = TOOL_GRADEBOOK;

api_protect_course_script(true);
api_block_anonymous_users();
GradebookUtils::block_students();

$select_cat = isset($_GET['selectcat']) ? (int) $_GET['selectcat'] : 0;
$sessionId = isset($_GET['id_session']) ? (int) $_GET['id_session'] : null;
$is_allowedToEdit = $is_courseAdmin;

$em = Database::getManager();
/** @var \Chamilo\CoreBundle\Entity\Course $course */
$course = $em->find('ChamiloCoreBundle:Course', api_get_course_int_id());
$ltiToolRepo = $em->getRepository('ChamiloPluginBundle:ImsLti\ImsLtiTool');

$categories = Category::load(null, null, $course->getCode(), null, null, $sessionId);

if (empty($categories)) {
    $message = Display::return_message(
        get_plugin_lang('GradebookNotSetWarning', 'ImsLtiPlugin'),
        'warning'
    );

    api_not_allowed(true, $message);
}

$evaladd = new Evaluation();
$evaladd->set_user_id($_user['user_id']);

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
$form->removeElement('name');
$form->removeElement('addresult');
$slcLtiTools = $form->createElement('select', 'name', get_lang('Tool'));
$form->insertElementBefore($slcLtiTools, 'hid_category_id');
$form->addRule('name', get_lang('ThisFieldIsRequired'), 'required');

$ltiTools = $ltiToolRepo->findBy(['course' => $course, 'gradebookEval' => null]);

/** @var ImsLtiTool $ltiTool */
foreach ($ltiTools as $ltiTool) {
    $slcLtiTools->addOption($ltiTool->getName(), $ltiTool->getId());
}

if ($form->validate()) {
    $values = $form->exportValues();

    /** @var ImsLtiTool $ltiTool */
    $ltiTool = $ltiToolRepo->find($values['name']);

    if (!$ltiTool) {
        api_not_allowed();
    }

    $eval = new Evaluation();
    $eval->set_name($ltiTool->getName());
    $eval->set_description($values['description']);
    $eval->set_user_id($values['hid_user_id']);

    if (!empty($values['hid_course_code'])) {
        $eval->set_course_code($values['hid_course_code']);
    }

    //Always add the gradebook to the course
    $eval->set_course_code(api_get_course_id());
    $eval->set_category_id($values['hid_category_id']);

    $parent_cat = Category::load($values['hid_category_id']);
    $global_weight = $cat[0]->get_weight();
    //$values['weight'] = $values['weight_mask']/$global_weight*$parent_cat[0]->get_weight();
    $values['weight'] = $values['weight_mask'];

    $eval->set_weight($values['weight']);
    $eval->set_max($values['max']);
    $eval->set_visible(empty($values['visible']) ? 0 : 1);
    $eval->add();

    /** @var GradebookEvaluation $gradebookEval */
    $gradebookEval = $em->find('ChamiloCoreBundle:GradebookEvaluation', $eval->get_id());
    $ltiTool->setGradebookEval($gradebookEval);

    $em->persist($ltiTool);
    $em->flush();

    header('Location: '.Category::getUrl().'selectcat='.$eval->get_category_id());

    exit;
}

$interbreadcrumb[] = [
    'url' => Category::getUrl().'selectcat='.$select_cat,
    'name' => get_lang('Gradebook'),
];
$this_section = SECTION_COURSES;

$htmlHeadXtra[] = '<script>
$(document).ready( function() {
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

Display::display_header(get_lang('NewEvaluation'));

$form->display();

Display::display_footer();
