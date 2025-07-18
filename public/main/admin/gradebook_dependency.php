<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Framework\Container;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();

$allow = ('true' === api_get_setting('gradebook.gradebook_dependency'));
if (false == $allow) {
    api_not_allowed(true);
}

$categoryId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 1;

$em = Database::getManager();
$repo = $em->getRepository(GradebookCategory::class);
/** @var GradebookCategory $category */
$category = $repo->find($categoryId);
if (!$category) {
    api_not_allowed(true);
}

$categoryObj = Category::load($categoryId);
/** @var Category $categoryObj */
$categoryObj = $categoryObj[0];
$dependencies = $categoryObj->getCourseListDependency();

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

$currentUrl = api_get_self().'?';
$table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'admin/gradebook_list.php',
    'name' => get_lang('Assessments'),
];

$tpl = new Template(get_lang('Course list'));
$toolbar = Display::url(
    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add')),
    api_get_path(WEB_CODE_PATH).'admin/gradebook_list.php'
);

if (empty($dependencies)) {
    Display::addFlash(
        Display::return_message(get_lang('ThisAssessmentsDoesntHaveDependencies'))
    );
}

$content = '';
$courseList = [];
$mandatoryList = api_get_setting('gradebook.gradebook_dependency_mandatory_courses', true);
$mandatoryList = isset($mandatoryList['courses']) ? $mandatoryList['courses'] : [];
$mandatoryListCompleteList = [];
foreach ($mandatoryList as $courseMandatoryId) {
    $mandatoryListCompleteList[] = api_get_course_info_by_id($courseMandatoryId);
}
$totalDependencies = count($dependencies);
$min = $categoryObj->getMinimumToValidate();
$gradeBooksToValidateInDependence = $categoryObj->getGradeBooksToValidateInDependence();
$userResult = [];

$dependencyList = [];
foreach ($dependencies as $courseId) {
    $dependencyList[$courseId] = api_get_course_info_by_id($courseId);
}
$courseUserLoaded = [];

$gradeBookRepo = Container::getGradeBookCategoryRepository();
foreach ($dependencyList as $courseId => $courseInfo) {
    $courseCode = $courseInfo['code'];
    //$subCategory = Category::load(null, null, $courseCode);
    $subCategory = $gradeBookRepo->findOneBy(['course' => $courseId]);
    //$subCategory = $subCategory ? $subCategory[0] : [];
    if (null === $subCategory) {
        continue;
    }

    $userList = CourseManager::get_student_list_from_course_code($courseCode);
    foreach ($userList as $user) {
        $userId = $user['user_id'];
        $userInfo = api_get_user_info($userId);
        $courseId = $courseInfo['real_id'];

        $userCourseList = CourseManager::get_courses_list_by_user_id(
            $userId,
            false,
            false,
            true,
            [],
            false
        );
        $userCourseListCode = array_column($userCourseList, 'code');

        if (!isset($userResult[$userId]['result_mandatory_20'])) {
            $userResult[$userId]['result_mandatory_20'] = 0;
        }
        if (!isset($userResult[$userId]['result_not_mandatory_80'])) {
            $userResult[$userId]['result_not_mandatory_80'] = 0;
        }

        foreach ($userCourseList as $courseItem) {
            $myCourseCode = $courseItem['code'];
            $myCourseId = $courseItem['real_id'];
            if (in_array($myCourseId, $dependencies)) {
                continue;
            }

            if (isset($courseUserLoaded[$userId][$myCourseId])) {
                continue;
            } else {
                $courseUserLoaded[$userId][$myCourseId] = true;
            }

            /*$courseCategory = Category::load(
                null,
                null,
                $myCourseCode
            );*/
            $courseCategory = $gradeBookRepo->findOneBy(['course' => $myCourseId]);
            $userResult[$userId]['result_out_dependencies'][$myCourseCode] = false;
            if (!empty($courseCategory)) {
                $result = Category::userFinishedCourse(
                    $userId,
                    $courseCategory,
                    true
                );
                $userResult[$userId]['result_out_dependencies'][$myCourseCode] = $result;

                if (in_array($myCourseId, $mandatoryList)) {
                    if ($userResult[$userId]['result_mandatory_20'] < 20 && $result) {
                        $userResult[$userId]['result_mandatory_20'] += 10;
                    }
                } else {
                    if ($userResult[$userId]['result_not_mandatory_80'] < 80 && $result) {
                        $userResult[$userId]['result_not_mandatory_80'] += 10;
                        //  var_dump($userResult[$userId]['result_80'] );
                    }
                }
            }
        }

        $result = Category::userFinishedCourse(
            $userId,
            $subCategory,
            true
        );

        $userResult[$userId]['result_dependencies'][$courseCode] = $result;
        $userResult[$userId]['user_info'] = $userInfo;

        if (in_array($courseId, $mandatoryList)) {
            if ($userResult[$userId]['result_mandatory_20'] < 20 && $result) {
                $userResult[$userId]['result_mandatory_20'] += 10;
            }
        } else {
            if ($userResult[$userId]['result_not_mandatory_80'] < 80 && $result) {
                $userResult[$userId]['result_not_mandatory_80'] += 10;
            }
        }
    }
    $courseList[] = $courseInfo;
}

foreach ($userResult as $userId => &$userData) {
    $courseListPassedOutDependency = count(array_filter($userData['result_out_dependencies']));
    $courseListPassedDependency = count(array_filter($userData['result_dependencies']));
    $total = $courseListPassedDependency + $courseListPassedOutDependency;
    $userData['course_list_passed_out_dependency'] = $courseListPassedOutDependency;
    $userData['course_list_passed_out_dependency_count'] = count($userData['result_out_dependencies']);
    // Min req must apply + mandatory should be 20
    //$userData['final_result'] = $total >= $min && $userData['result_mandatory_20'] == 20;
    //$userData['final_result'] = $total >= $min && $courseListPassedDependency == $totalDependencies;
    $userData['final_result'] = $total >= $min && $courseListPassedDependency >= $gradeBooksToValidateInDependence;
}

$tpl->assign('current_url', $currentUrl);
$tpl->assign(
    'actions',
    Display::toolbarAction(
        'toolbar',
        [$toolbar],
        [1, 4]
    )
);

$tpl->assign('mandatory_courses', $mandatoryListCompleteList);
$tpl->assign('min_to_validate', $min);
$tpl->assign('gradebook_category', $category);
$tpl->assign('courses', $courseList);
$tpl->assign('users', $userResult);
$layout = $tpl->get_template('admin/gradebook_dependency.tpl');
$tpl->display($layout);
