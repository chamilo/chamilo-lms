<?php
/* For licensing terms, see /license.txt */

/**
 * Script
 * @package chamilo.gradebook
 */
require_once __DIR__.'/../inc/global.inc.php';
$_in_course = true;
$course_code = api_get_course_id();
if (empty($course_code)) {
    $_in_course = false;
}

api_block_anonymous_users();
GradebookUtils::block_students();

$edit_cat = isset($_REQUEST['editcat']) ? intval($_REQUEST['editcat']) : '';

$get_select_cat = intval($_GET['selectcat']);

$catadd = new Category();
$my_user_id = api_get_user_id();
$catadd->set_user_id($my_user_id);
$catadd->set_parent_id($get_select_cat);
$catcourse = Category :: load($get_select_cat);

if ($_in_course) {
    $catadd->set_course_code($course_code);
} else {
    $catadd->set_course_code($catcourse[0]->get_course_code());
}

$catadd->set_course_code(api_get_course_id());
$form = new CatForm(
    CatForm :: TYPE_ADD,
    $catadd,
    'add_cat_form',
    null,
    api_get_self().'?selectcat='.$get_select_cat.'&'.api_get_cidreq()
);

if ($form->validate()) {
    $values = $form->exportValues();
    $select_course = isset($values['select_course']) ? $values['select_course'] : array();
    $cat = new Category();
    if ($values['hid_parent_id'] == '0') {
        if ($select_course == 'COURSEINDEPENDENT') {
            $cat->set_name($values['name']);
            $cat->set_course_code(null);
        } else {
            $cat->set_course_code($select_course);
            $cat->set_name($values['name']);
        }
    } else {
        $cat->set_name($values['name']);
        $cat->set_course_code($values['course_code']);
    }

    $cat->set_session_id(api_get_session_id());
    //Always add the gradebook to the course
    $cat->set_course_code(api_get_course_id());

    if (isset($values['skills'])) {
        $cat->set_skills($values['skills']);
    }

    $cat->set_description($values['description']);
    $cat->set_user_id($values['hid_user_id']);
    $cat->set_parent_id($values['hid_parent_id']);
    $cat->set_weight($values['weight']);

    if (isset($values['generate_certificates'])) {
        $cat->setGenerateCertificates(true);
    } else {
        $cat->setGenerateCertificates(false);
    }

    if (isset($values['is_requirement'])) {
        $cat->setIsRequirement(true);
    } else {
        $cat->setIsRequirement(false);
    }

    if (empty ($values['visible'])) {
        $visible = 0;
    } else {
        $visible = 1;
    }
    $cat->set_visible($visible);
    $result = $cat->add();
    header('Location: '.Security::remove_XSS($_SESSION['gradebook_dest']).'?addcat=&selectcat='.$cat->get_parent_id().'&'.api_get_cidreq());
    exit;
}

if (!$_in_course) {
    $interbreadcrumb[] = array(
        'url' => Security::remove_XSS($_SESSION['gradebook_dest']).'?selectcat='.$get_select_cat.'&'.api_get_cidreq(),
        'name' => get_lang('Gradebook')
    );
}
$interbreadcrumb[] = array('url' => 'index.php?'.api_get_cidreq(), 'name' => get_lang('ToolGradebook'));
Display :: display_header(get_lang('NewCategory'));

$display_form = true;
if ($display_form) {
    $form->display();
}

Display :: display_footer();
