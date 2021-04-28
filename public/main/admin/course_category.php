<?php

/* For licensing terms, see /license.txt */

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
$category = isset($_GET['category']) ? $_GET['category'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : null;
$categoryId = isset($_GET['id']) ? Security::remove_XSS($_GET['id']) : null;

$parentInfo = [];
if (!empty($category)) {
    $parentInfo = CourseCategory::getCategory($category);
}
if (!empty($categoryId)) {
    $categoryInfo = CourseCategory::getCategory($categoryId);
}

$myCourseListAsCategory = api_get_configuration_value('my_courses_list_as_category');

if (!empty($action)) {
    if ('delete' === $action) {
        CourseCategory::delete($categoryId);
        Display::addFlash(Display::return_message(get_lang('Deleted')));
        header('Location: '.api_get_self().'?category='.Security::remove_XSS($category));
        exit();
    } elseif (('add' === $action || 'edit' === $action) && isset($_POST['formSent']) && $_POST['formSent']) {
        if ('add' === $action) {
            $ret = CourseCategory::addNode(
                $_POST['code'],
                $_POST['name'],
                $_POST['auth_course_child'],
                $parentInfo ? $parentInfo['id'] : null
            );
            $errorMsg = Display::return_message(get_lang('Created'));
        } else {
            $ret = CourseCategory::editNode(
                $_POST['code'],
                $_POST['name'],
                $_POST['auth_course_child'],
                $categoryId
            );
            $categoryInfo = CourseCategory::getCategory($_POST['code']);
            $ret = $categoryInfo['id'];

            //Delete Picture Category
            /*$deletePicture = isset($_POST['delete_picture']) ? $_POST['delete_picture'] : '';
            if ($deletePicture) {
                CourseCategory::deletePictureCategory($ret);
            }*/

            $errorMsg = Display::return_message(get_lang('Update successful'));
        }
        if (!$ret) {
            $errorMsg = Display::return_message(get_lang('This category is already used'), 'error');
        } else {
            if ($myCourseListAsCategory) {
                /*if (isset($_FILES['image'])) {
                    CourseCategory::saveImage($ret, $_FILES['image']);
                }*/
                CourseCategory::saveDescription($ret, $_POST['description']);
            }
        }

        Display::addFlash($errorMsg);
        header('Location: '.api_get_path(WEB_CODE_PATH).'admin/course_category.php');
        exit;
    } elseif ('moveUp' === $action) {
        CourseCategory::moveNodeUp($categoryId, $_GET['tree_pos'], $category);
        header('Location: '.api_get_self().'?category='.Security::remove_XSS($category));
        Display::addFlash(Display::return_message(get_lang('Update successful')));
        exit();
    }
}

$tool_name = get_lang('Courses categories');
$interbreadcrumb[] = [
    'url' => 'index.php',
    'name' => get_lang('Administration'),
];

Display::display_header($tool_name);
$urlId = api_get_current_access_url_id();

if ('add' === $action || 'edit' === $action) {
    $actions = Display::url(
        Display::return_icon('folder_up.png', get_lang('Back'), '', ICON_SIZE_MEDIUM),
        api_get_path(WEB_CODE_PATH).'admin/course_category.php?category='.Security::remove_XSS($category)
    );
    echo Display::toolbarAction('categories', [$actions]);

    $form_title = 'add' === $action ? get_lang('Add category') : get_lang('Edit this category');
    if (!empty($category)) {
        $form_title .= ' '.get_lang('Into').' '.Security::remove_XSS($category);
    }
    $url = api_get_self().'?action='.Security::remove_XSS($action).
        '&category='.Security::remove_XSS($category).'&id='.Security::remove_XSS($categoryId);
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

    if ($myCourseListAsCategory) {
        $form->addHtmlEditor(
            'description',
            get_lang('Description'),
            false,
            false,
            ['ToolbarSet' => 'Minimal']
        );
        /*$form->addFile('image', get_lang('Image'), ['id' => 'picture', 'class' => 'picture-form', 'accept' => 'image/*', 'crop_image' => true]);
        if ('edit' === $action && !empty($categoryInfo['image'])) {
            $form->addElement('checkbox', 'delete_picture', null, get_lang('Delete picture'));
            $form->addHtml('
                <div class="form-group row">
                    <div class="offset-md-2 col-sm-8">'.
                Display::img(
                    api_get_path(WEB_UPLOAD_PATH).$categoryInfo['image'],
                    get_lang('Image'),
                    ['width' => 256, 'class' => 'img-thumbnail']
                ).'</div>
                </div>
            ');
        }*/
    }

    if (!empty($categoryInfo)) {
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
    // If multiple URLs and not main URL, prevent deletion and inform user
    if ('delete' == $action && api_get_multiple_access_url() && 1 != $urlId) {
        echo Display::return_message(
            get_lang(
                'Course categories are global over multiple portals configurations. Changes are only allowed in the main administrative portal.'
            ),
            'warning'
        );
    }
    $actions = '';
    $link = null;
    if (!empty($parentInfo)) {
        $realParentInfo = $parentInfo['parent_id'] ? CourseCategory::getCategoryById($parentInfo['parent_id']) : [];
        $realParentCode = $realParentInfo ? $realParentInfo['code'] : '';
        $actions .= Display::url(
            Display::return_icon('back.png', get_lang('Back'), '', ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'admin/course_category.php?category='.$realParentCode
        );
    }

    if (empty($parentInfo) || 'TRUE' == $parentInfo['auth_cat_child']) {
        $newCategoryLink = Display::url(
            Display::return_icon('new_folder.png', get_lang('Add category'), '', ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'admin/course_category.php?action=add&category='.Security::remove_XSS($category)
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
    echo CourseCategory::listCategories(
        CourseCategory::getCategory($category)
    );
}

Display::display_footer();
