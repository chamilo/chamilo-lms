<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\CoreBundle\Enums\ActionIcon;

require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_GRADEBOOK;

api_protect_course_script(true);
api_block_anonymous_users();
GradebookUtils::block_students();

$select_cat = isset($_GET['selectcat']) ? (int) $_GET['selectcat'] : 0;
$userId = api_get_user_id();

$evaladd = new Evaluation();
$evaladd->set_user_id($userId);
$evaladd->set_category_id($select_cat);
$evaladd->setCourseId(api_get_course_int_id());

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
    $entityManager = Database::getManager();
    $course = $entityManager->getRepository(Course::class)->find(api_get_course_int_id());
    if (!$course && !empty($values['hid_course_code'])) {
        $course = $entityManager->getRepository(Course::class)->findOneBy(['code' => (string) $values['hid_course_code']]);
    }
    if (!$course) {
        Display::addFlash(Display::return_message(get_lang('Course not found'), 'error', false));
        header('Location: '.Category::getUrl().'selectcat='.$select_cat);
        exit;
    }

    // Resolve Category entity
    $categoryId = isset($values['hid_category_id']) ? (int) $values['hid_category_id'] : 0;
    $category = $entityManager->getRepository(GradebookCategory::class)->find($categoryId);
    if (!$category) {
        Display::addFlash(Display::return_message(get_lang('Select assessment'), 'error', false));
        header('Location: '.Category::getUrl().'selectcat='.$select_cat);
        exit;
    }

    // Normalize numeric inputs
    $weight = isset($values['weight_mask']) && $values['weight_mask'] !== '' ? (float) $values['weight_mask'] : 0.0;
    $max = isset($values['max']) && $values['max'] !== '' ? (float) $values['max'] : 0.0;
    $visible = empty($values['visible']) ? 0 : 1;

    // Min score can be empty => store NULL
    $minScore = null;
    if (array_key_exists('min_score', $values) && $values['min_score'] !== '' && $values['min_score'] !== null) {
        $minScore = (float) $values['min_score'];
    }

    $evaluation = new GradebookEvaluation();
    $evaluation->setTitle((string) $values['name']);
    $evaluation->setDescription($values['description'] !== '' ? (string) $values['description'] : '');

    $evaluation->setCourse($course);
    $evaluation->setCategory($category);

    $evaluation->setWeight($weight);
    $evaluation->setMax($max);
    $evaluation->setVisible($visible);

    // REQUIRED (DB NOT NULL)
    $evaluation->setType('evaluation');

    // Defensive: ensure locked has a value even if constructor changes later
    $evaluation->setLocked(0);

    // This is the missing piece for the reported issue
    $evaluation->setMinScore($minScore);

    $entityManager->persist($evaluation);
    $entityManager->flush();

    $logInfo = [
        'tool' => TOOL_GRADEBOOK,
        'tool_id' => 0,
        'tool_id_detail' => 0,
        'action' => 'new-eval',
        'action_details' => 'selectcat='.$evaluation->getCategory()->getId(),
    ];
    Event::registerLog($logInfo);

    $val_addresult = isset($values['addresult']) ? (int) $values['addresult'] : 0;
    if (1 === $val_addresult) {
        header('Location: gradebook_add_result.php?selecteval='.$evaluation->getId().'&'.api_get_cidreq());
        exit;
    }

    header('Location: '.Category::getUrl().'selectcat='.$evaluation->getCategory()->getId());
    exit;
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
    'name' => get_lang('Assessments'),
];
$this_section = SECTION_COURSES;

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

Display::display_header(get_lang('Add classroom activity'));

$defaultBackUrl = Category::getUrl().'selectcat='.$select_cat;
$backUrl = $defaultBackUrl;
$referer = $_SERVER['HTTP_REFERER'] ?? '';
if (!empty($referer)) {
    $platformHost = (string) parse_url(api_get_path(WEB_PATH), PHP_URL_HOST);
    $refererHost = (string) parse_url($referer, PHP_URL_HOST);
    if (empty($refererHost)) {
        $backUrl = $referer;
    } elseif (!empty($platformHost) && $refererHost === $platformHost) {
        $backUrl = $referer;
    }
}

echo '<div class="mb-4">';

// Back icon only
echo '<div class="mb-2">';
echo Display::url(
    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')),
    $backUrl
);
echo '</div>';

echo '</div>';

$form->display();
Display::display_footer();
