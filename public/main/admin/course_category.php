<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Framework\Container;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
$category = $_GET['category'] ?? null;
$action = $_GET['action'] ?? null;
$categoryId = isset($_GET['id']) ? Security::remove_XSS($_GET['id']) : null;
$assetRepo = Container::getAssetRepository();
$categoryRepo = Container::getCourseCategoryRepository();

$urlId = api_get_current_access_url_id();
$categoryInfo = [];
$parentInfo = [];
if (!empty($categoryId)) {
    $categoryInfo = $parentInfo = CourseCategory::getCategoryById($categoryId);
}
$parentId = $parentInfo ? $parentInfo['parent_id'] : null;

switch ($action) {
    case 'delete':
        // If multiple URLs and not main URL, prevent deletion and inform user
        if (api_get_multiple_access_url() && 1 != $urlId) {
            echo Display::return_message(
                get_lang(
                    'Course categories are global over multiple portals configurations. Changes are only allowed in the main administrative portal.'
                ),
                'warning'
            );
        }
        if (!empty($categoryId)) {
            $categoryRepo->delete($categoryRepo->find($categoryId));
            CourseCategory::reorganizeTreePos($parentId);
            Display::addFlash(Display::return_message(get_lang('Deleted')));
        }
        header('Location: '.api_get_path(WEB_CODE_PATH).'admin/course_category.php'.(
            !empty($parentId) ? '?id='.(int) $parentId : ''
            ));
        exit;
    case 'export':
        $courses = CourseCategory::getCoursesInCategory($categoryId);
        if ($courses && count($courses) > 0) {
            $name = api_get_local_time().'_'.$categoryInfo['code'];
            $courseList = [];

            /* @var Course $course */
            foreach ($courses as $course) {
                $courseList[] = [$course->getTitle()];
            }

            $header = [get_lang('Course title')];
            Export::arrayToCsvSimple($courseList, $name, false, $header);
        } else {
            Display::addFlash(Display::return_message(get_lang('No course found in this category'), 'warning'));
            header('Location: '.api_get_path(WEB_CODE_PATH).'admin/course_category.php'.(
                !empty($parentId) ? '?id='.(int) $parentId : ''
                ));
            exit;
        }
        break;
    case 'moveUp':
        if (CourseCategory::moveNodeUp($categoryId, $_GET['tree_pos'], $parentId)) {
            Display::addFlash(Display::return_message(get_lang('Update successful')));
        } else {
            Display::addFlash(Display::return_message(get_lang('Cannot move category up'), 'error'));
        }
        header('Location: '.api_get_path(WEB_CODE_PATH).'admin/course_category.php'.(
            !empty($parentId) ? '?id='.(int) $parentId : ''
            ));
        exit;
    case 'moveDown':
        if (CourseCategory::moveNodeDown($categoryId, $_GET['tree_pos'], $parentId)) {
            Display::addFlash(Display::return_message(get_lang('Update successful')));
        } else {
            Display::addFlash(Display::return_message(get_lang('Cannot move category down'), 'error'));
        }
        header('Location: '.api_get_path(WEB_CODE_PATH).'admin/course_category.php'.(
            !empty($parentId) ? '?id='.(int) $parentId : ''
            ));
        exit;
    case 'add':
        if (isset($_POST['formSent']) && $_POST['formSent']) {
            $categoryEntity = CourseCategory::add(
                $_POST['code'],
                $_POST['title'],
                $_POST['auth_course_child'],
                $_POST['description'],
                $_POST['parent_id'] ?? null,
            );

            if (isset($_FILES['image']) && $categoryEntity) {
                $crop = $_POST['picture_crop_result'] ?? '';
                CourseCategory::saveImage($categoryEntity, $_FILES['image'], $crop);
            }
            Display::addFlash(Display::return_message(get_lang('Item added')));
            header('Location: '.api_get_path(WEB_CODE_PATH).'admin/course_category.php'.(!empty($_POST['parent_id']) ? '?id='.(int) $_POST['parent_id'] : ''));
            exit;
        }
        break;
    case 'edit':
        if (isset($_POST['formSent']) && $_POST['formSent']) {
            $categoryEntity = CourseCategory::edit(
                $categoryId,
                $_REQUEST['title'],
                $_REQUEST['auth_course_child'],
                $_REQUEST['code'],
                $_REQUEST['description'],
                $_REQUEST['parent_id'] ?? null
            );

            // Delete Picture Category
            $deletePicture = $_POST['delete_picture'] ?? '';

            if ($deletePicture && $categoryEntity) {
                $categoryRepo->deleteAsset($categoryEntity);
            }

            if (isset($_FILES['image']) && $categoryEntity) {
                $crop = $_POST['picture_crop_result'] ?? '';
                CourseCategory::saveImage($categoryEntity, $_FILES['image'], $crop);
            }

            Display::addFlash(Display::return_message(get_lang('Update successful')));
            header('Location: '.api_get_path(WEB_CODE_PATH).'admin/course_category.php'.(!empty($_POST['parent_id']) ? '?id='.(int) $_POST['parent_id'] : ''));
            exit;
        }
        break;
}

$tool_name = get_lang('Course categories');
$interbreadcrumb[] = [
    'url' => 'index.php',
    'name' => get_lang('Administration'),
];

Display::display_header($tool_name);

if ('add' === $action || 'edit' === $action) {
    $actions = Display::url(
        Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')),
        api_get_path(WEB_CODE_PATH).'admin/course_category.php?id='.$categoryId
    );
    echo Display::toolbarAction('categories', [$actions]);

    $form_title = 'add' === $action ? get_lang('Add category') : get_lang('Edit this category');
    if (!empty($categoryInfo)) {
        $form_title .= ' '.get_lang('Into').' '.$categoryInfo['title'];
    }
    $url = api_get_self().'?action='.Security::remove_XSS($action).'&id='.Security::remove_XSS($categoryId);
    $form = new FormValidator('course_category', 'post', $url);
    $form->addHeader($form_title);
    $form->addHidden('formSent', 1);
    $form->addElement('text', 'code', get_lang('Category code'));

    if ('true' === api_get_setting('editor.save_titles_as_html')) {
        $form->addHtmlEditor(
            'title',
            get_lang('Category name'),
            true,
            false,
            ['ToolbarSet' => 'TitleAsHtml']
        );
    } else {
        $form->addElement('text', 'title', get_lang('Category name'));
        $form->addRule('title', get_lang('Please enter a code and a name for the category'), 'required');
    }

    $form->addRule('code', get_lang('Please enter a code and a name for the category'), 'required');
    $group = [
        $form->createElement(
            'radio',
            'auth_course_child',
            get_lang('Allow adding courses in this category?'),
            get_lang('Yes'),
            'TRUE'
        ),
        $form->createElement(
            'radio',
            'auth_course_child',
            null,
            get_lang('No'),
            'FALSE'
        ),
    ];
    $form->addGroup($group, null, get_lang('Allow adding courses in this category?'));
    if ('add' === $action && !empty($categoryId)) {
        $form->addHidden('parent_id', $categoryId);
        $form->addLabel(get_lang('Parent category'), $parentInfo['title'].' ('.$parentInfo['code'].')');
    } else {
        $allCategories = CourseCategory::getAllCategories();
        $parentOptions = ['' => get_lang('No parent')];
        foreach ($allCategories as $cat) {
            if ('edit' === $action && $cat['id'] == $categoryId) {
                continue;
            }
            $parentOptions[$cat['id']] = '('.$cat['code'].') '.$cat['title'];
        }
        $form->addSelect(
            'parent_id',
            get_lang('Parent category'),
            $parentOptions
        );
    }
    $form->addHtmlEditor(
        'description',
        get_lang('Description'),
        false,
        false,
        ['ToolbarSet' => 'Minimal']
    );
    $form->addFile(
        'image',
        get_lang('Image'),
        ['id' => 'picture', 'class' => 'picture-form', 'accept' => 'image/*', 'crop_image' => true]
    );
    if ('edit' === $action && !empty($categoryInfo['asset_id'])) {
        $form->addElement('checkbox', 'delete_picture', null, get_lang('Delete picture'));

        $asset = $assetRepo->find($categoryInfo['asset_id']);
        $image = $assetRepo->getAssetUrl($asset);
        $escapedImageUrl = htmlspecialchars($image, ENT_QUOTES, 'UTF-8');

        $form->addLabel(get_lang('Image'), "<img src='$escapedImageUrl' alt='Image' />");
    }

    if ('edit' === $action  && !empty($categoryInfo)) {
        $text = get_lang('Save');
        $form->setDefaults($categoryInfo);
        $form->setDefaults([
            'parent_id' => $categoryInfo['parent_id'] ?? '',
        ]);
        $form->addButtonSave($text);
    } else {
        $text = get_lang('Add category');
        $defaultValues = [
            'auth_course_child' => 'TRUE',
        ];

        if ('add' === $action && !empty($categoryId)) {
            $defaultValues['parent_id'] = $categoryId;
        }
        $form->setDefaults($defaultValues);
        $form->addButtonCreate($text);
    }
    $form->display();
} else {
    $actions = '';
    $link = null;
    if (!empty($parentInfo)) {
        $realParentInfo = $parentInfo['parent_id'] ? CourseCategory::getCategoryById($parentInfo['parent_id']) : [];
        $realParentCode = $realParentInfo ? $realParentInfo['id'] : 0;
        $actions .= Display::url(
            Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')),
            api_get_path(WEB_CODE_PATH).'admin/course_category.php?id='.$realParentCode
        );
    }

    if (empty($parentInfo) || 'TRUE' === $parentInfo['auth_cat_child']) {
        $newCategoryLink = Display::url(
            Display::getMdiIcon(ActionIcon::CREATE_FOLDER, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add category')),
            api_get_path(WEB_CODE_PATH).'admin/course_category.php?action=add&id='.$categoryId
        );

        if (!empty($parentInfo) && $parentInfo['access_url_id'] != $urlId) {
            $newCategoryLink = '';
        }
        $actions .= $newCategoryLink;
    }
    echo Display::toolbarAction('categories', [$actions]);
    if (!empty($parentInfo)) {
        echo Display::page_subheader($parentInfo['title'].' ('.$parentInfo['code'].')');
    }
    echo CourseCategory::listCategories($categoryInfo);
}

Display::display_footer();
