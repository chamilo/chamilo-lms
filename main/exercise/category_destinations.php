<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CQuizCategory;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_COURSES;

api_protect_course_script(true);

$exerciseId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

if (!api_is_allowed_to_edit(null, true) ||
    !api_get_configuration_value('quiz_question_category_destinations') ||
    !$exerciseId
) {
    api_not_allowed(true);
}

$courseId = api_get_course_int_id();

$objExercise = new Exercise();

if (!$objExercise->read($exerciseId)) {
    api_not_allowed(
        true,
        Display::return_message(get_lang('ExerciseNotFound'), 'error')
    );
}

if (EXERCISE_FEEDBACK_TYPE_PROGRESSIVE_ADAPTIVE != $objExercise->selectFeedbackType()) {
    api_not_allowed(true);
}

$em = Database::getManager();
$quizCategoryRepo = $em->getRepository('ChamiloCourseBundle:CQuizCategory');

$webCodePath = api_get_path(WEB_CODE_PATH);
$cidReq = api_get_cidreq();

$quizCategory = new TestCategory();
$categoriesInfo = $quizCategory->getListOfCategoriesForTest($objExercise);
$savedCategories = $objExercise->getCategoriesInExercise();

$form = new FormValidator('category_destinations');

foreach ($categoriesInfo as $categoryId => $categoryInfo) {
    $firstQuestionInCategory = $objExercise->getFirstQuestionInCategory($categoryId);
    $questionPosition = $objExercise->getPositionInCompressedQuestionList($firstQuestionInCategory);

    $url = "{$webCodePath}exercise/overview.php?"
        .http_build_query(
            [
                'exerciseId' => $exerciseId,
                'cs' => $categoryId,
            ]
        );
    $txtId = "copy-link-$categoryId";

    $table = '<div id="tbl-category-'.$categoryId.'" class="table-responsive">
        <table class="data_table">
            <thead>
            <tr>
                <th>'.get_lang('From').'</th>
                <th>&nbsp;</th>
                <th>'.get_lang('To').'</th>
                <th>'.get_lang('GoTo').'</th>
                <th>&nbsp;</th>
            </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
        <p>
            <strong>'.get_lang('DestinationUrl').'</strong>
            <div class="input-group">
                <input type="text" id="'.$txtId.'" class="form-control" readonly value="'.$url.'">
                <span class="input-group-btn">
                    <button class="btn btn-info" type="button" onclick="copyTextToClipBoard(\''.$txtId.'\');">'
        .get_lang('CopyTextToClipboard')
        .'</button>
                </span>
            </div>
        </p>
    </div>';

    $form->addLabel($categoryInfo['name'], $table);
    $form->addHidden("category_destination[$categoryId]", '');
}

$form->addHeader($objExercise->selectTitle());
$form->addHidden('id', $exerciseId);
$form->addButtonSave(get_lang('Save'));

if (!empty($_POST)) {
    foreach ($categoriesInfo as $categoryId => $categoryInfo) {
        $destinations = [];

        foreach ($_POST['min'][$categoryId] as $key => $value) {
            $destinations[] .= "{$_POST['max'][$categoryId][$key]}:{$_POST['destination'][$categoryId][$key]}";
        }

        /** @var CQuizCategory $cQuizRelCategory */
        $cQuizRelCategory = $quizCategoryRepo->findOneBy(['exerciseId' => $exerciseId, 'categoryId' => $categoryId]);

        $cQuizRelCategory->setDestinations(
            implode('@@', $destinations)
        );

        $em->persist($cQuizRelCategory);
    }

    $em->flush();

    Display::addFlash(
        Display::return_message(get_lang('CategoryDestinationsSaved'), 'success')
    );

    header('Location: '.api_get_self().'?id='.$exerciseId.'&'.api_get_cidreq());
    exit;
}

$interbreadcrumb[] = ['url' => 'exercise.php', 'name' => get_lang('Exercises')];
$interbreadcrumb[] = ['url' => 'admin.php?exerciseId='.$exerciseId, 'name' => $objExercise->selectTitle(true)];

$actions = Display::url(
    Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
    'admin.php?exerciseId='.$exerciseId.'&'.api_get_cidreq()
);

$pageTitle = get_lang('CategoryDestinations');

$view = new Template($pageTitle);
$view->assign('form', $form->returnForm());
$view->assign('categories', $categoriesInfo);
$view->assign('saved_categories', $savedCategories);
$layout = $view->get_template('exercise/category_destinations.tpl');
$view->assign('header', $pageTitle);
$view->assign(
    'actions',
    Display::toolbarAction('category_destinations_actions', [$actions])
);
$view->assign('content', $view->fetch($layout));
$view->display_one_col_template();
