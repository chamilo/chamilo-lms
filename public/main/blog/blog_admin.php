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
    $nameTools = get_lang('Project management');

    // showing the header if we are not in the learning path, if we are in
    // the learning path, we do not include the banner so we have to explicitly
    // include the stylesheet, which is normally done in the header
    if ('learnpath' != $origin) {
        $interbreadcrumb[] = [
            'url' => 'blog_admin.php?'.api_get_cidreq(),
            'name' => $nameTools,
        ];
        $my_url = '';
        if ('add' == $action) {
            $current_section = get_lang('Create a new project');
            $my_url = 'action=add';
        } elseif ('edit' == $action) {
            $current_section = get_lang('Edit a project');
            $my_url = 'action=edit&amp;blog_id='.Security::remove_XSS($_GET['blog_id']);
        }
        Display::display_header('');
    }
    echo '<div class="actions">';
    echo "<a href='".api_get_self()."?".api_get_cidreq()."&action=add'>",
        Display::return_icon('new_blog.png', get_lang('Create a new project'), '', ICON_SIZE_MEDIUM)."</a>";
    echo '</div>';

    if (!empty($_POST['new_blog_submit']) && !empty($_POST['blog_name'])) {
        if (isset($_POST['blog_name'])) {
            Blog::addBlog($_POST['blog_name'], $_POST['blog_subtitle']);
            echo Display::return_message(get_lang('The project has been added.'), 'confirmation');
        }
    }
    if (!empty($_POST['edit_blog_submit']) && !empty($_POST['blog_name'])) {
        if (strlen(trim($_POST['blog_name'])) > 0) {
            Blog::editBlog($_POST['blog_id'], $_POST['blog_name'], $_POST['blog_subtitle']);
            echo Display::return_message(get_lang('The project has been edited.'), 'confirmation');
        }
    }
    if (isset($_GET['action']) && 'visibility' == $_GET['action']) {
        Blog::changeBlogVisibility(intval($_GET['blog_id']));
        echo Display::return_message(get_lang('The visibility has been changed.'), 'confirmation');
    }
    if (isset($_GET['action']) && 'delete' == $_GET['action']) {
        Blog::deleteBlog(intval($_GET['blog_id']));
        echo Display::return_message(get_lang('The project has been deleted.'), 'confirmation');
    }

    if (isset($_GET['action']) && 'add' == $_GET['action']) {
        // we show the form if
        // 1. no post data
        // 2. there is post data and one of the required form elements is empty
        if (!$_POST || (!empty($_POST) && (empty($_POST['new_blog_submit']) || empty($_POST['blog_name'])))) {
            Blog::displayBlogCreateForm();
        }
    }

    if (isset($_GET['action']) && 'edit' == $_GET['action']) {
        // we show the form if
        // 1. no post data
        // 2. there is post data and one of the three form elements is empty
        if (!$_POST || (!empty($_POST) && (empty($_POST['edit_blog_submit']) || empty($_POST['blog_name'])))) {
            // if there is post data there is certainly an error in the form
            if ($_POST) {
                echo Display::return_message(get_lang('The form contains incorrect or incomplete data. Please check your input.'), 'error');
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
