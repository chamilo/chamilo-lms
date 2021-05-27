<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
$category = $_GET['category'] ?? null;
$action = $_GET['action'] ?? null;
$categoryId = isset($_GET['id']) ? Security::remove_XSS($_GET['id']) : null;
$assetRepo = Container::getAssetRepository();

$urlId = api_get_current_access_url_id();
$categoryInfo = [];
$parentInfo = [];
if (!empty($categoryId)) {
    $categoryInfo = $parentInfo = CourseCategory::getCategoryById($categoryId);
}
$parentId = $parentInfo ? $parentInfo['id'] : null;

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

        CourseCategory::delete($categoryId);
        Display::addFlash(Display::return_message(get_lang('Deleted')));
        header('Location: '.api_get_self().'?category='.Security::remove_XSS($category));
        exit;
        break;
    case 'export':
        $courses = CourseCategory::getCoursesInCategory($categoryId);
        if (!empty($courses)) {
            $name = api_get_local_time().'_'.$categoryInfo['code'];
            $courseList = [];
            foreach ($courses as $course) {
                $courseList[] = $course->getTitle();
            }
            Export::arrayToCsv($courseList, $name);
        }

        header('Location: '.api_get_self());
        exit;
        break;
    case 'moveUp':
        CourseCategory::moveNodeUp($categoryId, $_GET['tree_pos'], $category);
        Display::addFlash(Display::return_message(get_lang('Update successful')));
        header('Location: '.api_get_self().'?category='.Security::remove_XSS($category));
        exit;
        break;
    case 'add':
        if (isset($_POST['formSent']) && $_POST['formSent']) {
            $categoryEntity = CourseCategory::add(
                $_POST['code'],
                $_POST['name'],
                $_POST['auth_course_child'],
                $_POST['description'],
                $parentId,
            );

            if (isset($_FILES['image']) && $categoryEntity) {
                CourseCategory::saveImage($categoryEntity, $_FILES['image']);
            }
            Display::addFlash(Display::return_message(get_lang('Item added')));
            header('Location: '.api_get_path(WEB_CODE_PATH).'admin/course_category.php?id='.$parentId);
            exit;
        }
        break;
    case 'edit':
        if (isset($_POST['formSent']) && $_POST['formSent']) {
            $categoryEntity = CourseCategory::edit(
                $categoryId,
                $_REQUEST['name'],
                $_REQUEST['auth_course_child'],
                $_REQUEST['code'],
                $_REQUEST['description']
            );

            // Delete Picture Category
            $deletePicture = $_POST['delete_picture'] ?? '';
            if ($deletePicture) {
                CourseCategory::deleteImage($categoryEntity);
            }

            if (isset($_FILES['image']) && $categoryEntity) {
                CourseCategory::saveImage($categoryEntity, $_FILES['image']);
            }

            Display::addFlash(Display::return_message(get_lang('Update successful')));
            header('Location: '.api_get_path(WEB_CODE_PATH).'admin/course_category.php?id='.$parentId);
            exit;
        }
        break;
}

$tool_name = get_lang('Courses categories');
$interbreadcrumb[] = [
    'url' => 'index.php',
    'name' => get_lang('Administration'),
];

Display::display_header($tool_name);

if ('add' === $action || 'edit' === $action) {
    $actions = Display::url(
        Display::return_icon('back.png', get_lang('Back'), '', ICON_SIZE_MEDIUM),
        api_get_path(WEB_CODE_PATH).'admin/course_category.php?id='.$categoryId
    );
    echo Display::toolbarAction('categories', [$actions]);

    $form_title = 'add' === $action ? get_lang('Add category') : get_lang('Edit this category');
    if (!empty($categoryInfo)) {
        $form_title .= ' '.get_lang('Into').' '.$categoryInfo['name'];
    }
    $url = api_get_self().'?action='.Security::remove_XSS($action).'&id='.Security::remove_XSS($categoryId);
    $form = new FormValidator('course_category', 'post', $url);
    $form->addElement('header', '', $form_title);
    $form->addElement('hidden', 'formSent', 1);
    $form->addElement('text', 'code', get_lang('Category code'));

    if (api_get_configuration_value('save_titles_as_html')) {
        $form->addHtmlEditor(
            'name',
            get_lang('Category name'),
            true,
            false,
            ['ToolbarSet' => 'TitleAsHtml']
        );
    } else {
        $form->addElement('text', 'name', get_lang('Category name'));
        $form->addRule('name', get_lang('Please enter a code and a name for the category'), 'required');
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
    if ('edit' === $action && !empty($categoryInfo['image'])) {
        $form->addElement('checkbox', 'delete_picture', null, get_lang('Delete picture'));

        $asset = $assetRepo->find($categoryInfo['image']);
        $image = $assetRepo->getAssetUrl($asset);

        $form->addLabel(get_lang('Image'), "<img src=$image />");
    }

    if ('edit' === $action  && !empty($categoryInfo)) {
        $class = 'save';
        $text = get_lang('Save');
        $form->setDefaults($categoryInfo);
        $form->addButtonSave($text);
    } else {
        $class = 'add';
        $text = get_lang('Add category');
        $form->setDefaults(['auth_course_child' => 'TRUE']);
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
            Display::return_icon('back.png', get_lang('Back'), '', ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'admin/course_category.php?id='.$realParentCode
        );
    }

    if (empty($parentInfo) || 'TRUE' === $parentInfo['auth_cat_child']) {
        $newCategoryLink = Display::url(
            Display::return_icon('new_folder.png', get_lang('Add category'), '', ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'admin/course_category.php?action=add&id='.$categoryId
        );

        if (!empty($parentInfo) && $parentInfo['access_url_id'] != $urlId) {
            $newCategoryLink = '';
        }
        $actions .= $newCategoryLink;
    }
    echo Display::toolbarAction('categories', [$actions]);
    if (!empty($parentInfo)) {
        echo Display::page_subheader($parentInfo['name'].' ('.$parentInfo['code'].')');
    }
    echo CourseCategory::listCategories($categoryInfo);
}

Display::display_footer();
