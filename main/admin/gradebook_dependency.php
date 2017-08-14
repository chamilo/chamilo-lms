<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\GradebookCategory;
use Doctrine\Common\Collections\Criteria;
use Knp\Component\Pager\Paginator;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();

$allow = api_get_configuration_value('gradebook_dependency');
if ($allow == false) {
    api_not_allowed(true);
}

$categoryId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 1;

$em = Database::getManager();
$repo = $em->getRepository('ChamiloCoreBundle:GradebookCategory');
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

$interbreadcrumb[] = array(
    'url' => api_get_path(WEB_CODE_PATH).'admin/gradebook_list.php',
    'name' => get_lang('Gradebook')
);

$tpl = new Template(get_lang('CourseList'));
$toolbar = Display::url(
    Display::return_icon('back.png', get_lang('Add'), [], ICON_SIZE_MEDIUM),
    api_get_path(WEB_CODE_PATH).'admin/gradebook_list.php'
);

if (empty($dependencies)) {
    Display::addFlash(
        Display::return_message(get_lang('ThisGradebookDoesntHaveDependencies'))
    );
}

$content = '';
$courseList = [];

/*$mandatoryList = api_get_configuration_value('gradebook_dependency_mandatory_courses');
$mandatoryList = isset($mandatoryList['courses']) ? $mandatoryList['courses'] : [];*/

foreach ($dependencies as $courseId) {
    $courseInfo = api_get_course_info_by_id($courseId);
    $courseCode = $courseInfo['code'];
    $subCategory = Category::load(null, null, $courseCode);
    /** @var Category $subCategory */
    $subCategory = $subCategory ? $subCategory[0] : [];
    if (empty($subCategory)) {
        continue;
    }
    $userList = CourseManager::get_student_list_from_course_code($courseCode);
    $users = [];
    foreach ($userList as $user) {
        $userInfo = api_get_user_info($user['user_id']);
        $result = Category::userFinishedCourse(
            $user['user_id'],
            $subCategory,
            true
        );
        $userInfo['result'] = $result;
        $users[] = $userInfo;
    }
    $courseInfo['users'] = $users;
    //$courseInfo['is_mandatory'] = in_array($courseCode, $mandatoryList);
    $courseList[] = $courseInfo;
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

$tpl->assign('gradebook_category', $category);
$tpl->assign('courses', $courseList);
$layout = $tpl->get_template('admin/gradebook_dependency.tpl');
$tpl->display($layout);
