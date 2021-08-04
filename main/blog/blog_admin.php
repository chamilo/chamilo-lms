<?php
/* For licensing terms, see /license.txt */

/**
 * BLOG HOMEPAGE
 * This file takes care of all blog navigation and displaying.
 */
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_BLOGS;

$this_section = SECTION_COURSES;

api_protect_course_script(true);

//	 ONLY USERS REGISTERED IN THE COURSE
if ((!api_is_allowed_in_course() || !api_is_allowed_in_course()) && !api_is_allowed_to_edit()) {
    api_not_allowed(true); //print headers/footers
}

$origin = api_get_origin();
$action = isset($_GET['action']) ? $_GET['action'] : '';

if (api_is_allowed_to_edit()) {
    $nameTools = get_lang('blog_management');

    // showing the header if we are not in the learning path, if we are in
    // the learning path, we do not include the banner so we have to explicitly
    // include the stylesheet, which is normally done in the header
    if ($origin != 'learnpath') {
        $interbreadcrumb[] = [
            'url' => 'blog_admin.php?'.api_get_cidreq(),
            'name' => $nameTools,
        ];
        $my_url = '';
        if ($action == 'add') {
            $current_section = get_lang('AddBlog');
            $my_url = 'action=add';
        } elseif ($action == 'edit') {
            $current_section = get_lang('EditBlog');
            $my_url = 'action=edit&amp;blog_id='.Security::remove_XSS($_GET['blog_id']);
        }
        Display::display_header('');
    }
    echo '<div class="actions">';
    echo "<a href='".api_get_self()."?".api_get_cidreq()."&action=add'>",
        Display::return_icon('new_blog.png', get_lang('AddBlog'), '', ICON_SIZE_MEDIUM)."</a>";
    echo '</div>';

    if (!empty($_POST['new_blog_submit']) && !empty($_POST['blog_name'])) {
        if (isset($_POST['blog_name'])) {
            Blog::addBlog($_POST['blog_name'], $_POST['blog_subtitle']);
            echo Display::return_message(get_lang('BlogStored'), 'confirmation');
        }
    }
    if (!empty($_POST['edit_blog_submit']) && !empty($_POST['blog_name'])) {
        if (strlen(trim($_POST['blog_name'])) > 0) {
            Blog::editBlog($_POST['blog_id'], $_POST['blog_name'], $_POST['blog_subtitle']);
            echo Display::return_message(get_lang('BlogEdited'), 'confirmation');
        }
    }
    if (isset($_GET['action']) && $_GET['action'] == 'visibility') {
        Blog::changeBlogVisibility(intval($_GET['blog_id']));
        echo Display::return_message(get_lang('VisibilityChanged'), 'confirmation');
    }
    if (isset($_GET['action']) && $_GET['action'] == 'delete') {
        Blog::deleteBlog(intval($_GET['blog_id']));
        echo Display::return_message(get_lang('BlogDeleted'), 'confirmation');
    }

    if (isset($_GET['action']) && $_GET['action'] == 'add') {
        // we show the form if
        // 1. no post data
        // 2. there is post data and one of the required form elements is empty
        if (!$_POST || (!empty($_POST) && (empty($_POST['new_blog_submit']) || empty($_POST['blog_name'])))) {
            Blog::displayBlogCreateForm();
        }
    }

    if (isset($_GET['action']) && $_GET['action'] == 'edit') {
        // we show the form if
        // 1. no post data
        // 2. there is post data and one of the three form elements is empty
        if (!$_POST || (!empty($_POST) && (empty($_POST['edit_blog_submit']) || empty($_POST['blog_name'])))) {
            // if there is post data there is certainly an error in the form
            if ($_POST) {
                echo Display::return_message(get_lang('FormHasErrorsPleaseComplete'), 'error');
            }
            Blog::displayBlogEditForm(intval($_GET['blog_id']));
        }
    }
    Blog::displayBlogsList();
} else {
    api_not_allowed(true);
}

// Display the footer
Display::display_footer();
