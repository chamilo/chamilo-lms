<?php
/* For licensing terms, see /license.txt */
/**
 * 	@package chamilo.admin
 */
/**
 * Code
 */
// name of the language file that needs to be included
$language_file = 'admin';
$cidReset = true;
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'course_category.lib.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
$category = isset($_GET['category']) ? $_GET['category'] : null;

if (!empty($category)) {
    $parentInfo = getCategory($category);
}
$categoryId = isset($_GET['id']) ? Security::remove_XSS($_GET['id']) : null;

if (!empty($categoryId)) {
    $categoryInfo = getCategory($categoryId);
}
$action = isset($_GET['action']) ? $_GET['action'] : null;

$errorMsg = '';
if (!empty($action)) {
    if ($action == 'delete') {
        if (api_get_multiple_access_url()) {
            if (api_get_current_access_url_id() == 1 ||
                (isset($_configuration['enable_multiple_url_support_for_course_category']) &&
                $_configuration['enable_multiple_url_support_for_course_category'])
            ) {
                deleteNode($categoryId);
                header('Location: ' . api_get_self() . '?category=' . Security::remove_XSS($category));
                exit();
            }
        } else {
            deleteNode($categoryId);
            header('Location: ' . api_get_self() . '?category=' . Security::remove_XSS($category));
            exit();
        }
    } elseif (($action == 'add' || $action == 'edit') && isset($_POST['formSent']) && $_POST['formSent']) {
        if ($action == 'add') {
            $ret = addNode($_POST['code'], $_POST['name'], $_POST['auth_course_child'], $category);
        } else {
            $ret = editNode($_POST['code'], $_POST['name'], $_POST['auth_course_child'], $categoryId);
        }
        if ($ret) {
            $action = '';
        } else {
            $errorMsg = get_lang('CatCodeAlreadyUsed');
        }
    } elseif ($action == 'moveUp') {
        moveNodeUp($categoryId, $_GET['tree_pos'], $category);
        header('Location: ' . api_get_self() . '?category=' . Security::remove_XSS($category));
        exit();
    }
}

$tool_name = get_lang('AdminCategories');
$interbreadcrumb[] = array('url' => 'index.php', "name" => get_lang('PlatformAdmin'));

Display::display_header($tool_name);

if ($action == 'add' || $action == 'edit') {
    if ((api_get_multiple_access_url() && api_get_current_access_url_id() == 1) ||
        !api_get_multiple_access_url() ||
        (isset($_configuration['enable_multiple_url_support_for_course_category']) &&
         $_configuration['enable_multiple_url_support_for_course_category'])
    ) {
        echo '<div class="actions">';
        echo Display::url(
            Display::return_icon('folder_up.png', get_lang("Back"), '', ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'admin/course_category.php?category='.$category
        );
        echo '</div>';

        $form_title = ($action == 'add') ? get_lang('AddACategory') : get_lang('EditNode');
        if (!empty($category)) {
            $form_title .= ' ' . get_lang('Into') . ' ' . Security::remove_XSS($category);
        }
        $url = api_get_self().'?action='.Security::remove_XSS($action).'&category='.Security::remove_XSS($category).'&id='.$categoryId;
        $form = new FormValidator('course_category', 'post', $url);
        $form->addElement('header', '', $form_title);
        $form->addElement('hidden', 'formSent', 1);
        $form->addElement('text', 'code', get_lang("CategoryCode"));
        $form->addElement('text', 'name', get_lang("CategoryName"));
        $form->addRule('name', get_lang('PleaseEnterCategoryInfo'), 'required');
        $form->addRule('code', get_lang('PleaseEnterCategoryInfo'), 'required');
        $group = array(
            $form->createElement('radio', 'auth_course_child', get_lang("AllowCoursesInCategory"), get_lang('Yes'), 'TRUE'),
            $form->createElement('radio', 'auth_course_child', null, get_lang('No'), 'FALSE')
        );
        $form->addGroup($group, null, get_lang("AllowCoursesInCategory"));

        if (!empty($categoryInfo)) {
            $class = "save";
            $text = get_lang('CategoryMod');
            $form->setDefaults($categoryInfo);
        } else {
            $class = "add";
            $text = get_lang('AddCategory');
            $form->setDefaults(array('auth_course_child' => 'TRUE'));
        }
        $form->addElement('button', 'submit', $text);
        $form->display();
    } elseif (api_get_multiple_access_url() && api_get_current_access_url_id() != 1) {
        // If multiple URLs and not main URL, prevent edition and inform user
        Display::display_warning_message(get_lang('CourseCategoriesAreGlobal'));
    }
} else {
    // If multiple URLs and not main URL, prevent deletion and inform user
    if ($action == 'delete' && api_get_multiple_access_url() && api_get_current_access_url_id() != 1) {
        Display::display_warning_message(get_lang('CourseCategoriesAreGlobal'));
    }
    echo '<div class="actions">';
    $link = null;
    if (!empty($parentInfo)) {
        $parentCode = $parentInfo['parent_id'];
        echo Display::url(
            Display::return_icon('back.png', get_lang("Back"), '', ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'admin/course_category.php?category='.$parentCode
        );
    }

    if (empty($parentInfo) || $parentInfo['auth_cat_child'] == 'TRUE') {
        echo Display::url(
            Display::return_icon('new_folder.png', get_lang("AddACategory"), '', ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'admin/course_category.php?action=add&category='.$category
        );
    }

    echo '</div>';
    if (!empty($parentInfo)) {
        echo Display::page_subheader($parentInfo['name'].' ('.$parentInfo['code'].')');
    }
    echo listCategories($category);
}

Display::display_footer();
