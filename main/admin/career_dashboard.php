<?php
/* For licensing terms, see /license.txt */

/**
 * Careers dashboard
 * @package chamilo.admin.career
 */

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$libpath = api_get_path(LIBRARY_PATH);

api_protect_admin_script();

$this_section = SECTION_PLATFORM_ADMIN;

//Adds the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jqgrid_js();

// setting breadcrumbs
$interbreadcrumb[] = array(
    'url' => 'index.php',
    'name' => get_lang('PlatformAdmin')
);
$interbreadcrumb[] = array(
    'url' => 'career_dashboard.php',
    'name' => get_lang('CareersAndPromotions')
);
$tpl = new Template(get_lang('CareersAndPromotions'));

Display :: display_header(null);

$html = null;
$form = new FormValidator('filter_form', 'GET', api_get_self());

$career = new Career();

$condition = array('status = ?' => 1);
if ($form->validate()) {
    $data = $form->getSubmitValues();
    $filter = intval($data['filter']);
    if (!empty($filter)) {
        $condition = array('status = ? AND id = ? ' => array(1, $filter));
    }
}

$careers = $career->get_all(array('status = ?' => 1)); //only status =1
$career_select_list = array();
$career_select_list[0] = ' -- '.get_lang('Select').' --';
foreach ($careers as $item) {
    $career_select_list[$item['id']] = $item['name'];
}

$form->addSelect(
    'filter',
    get_lang('Career'),
    $career_select_list,
    array('id' => 'filter_1')
);
$form->addButtonSearch(get_lang('Filter'));

// action links
$actionLeft = Display::url(
    Display::return_icon(
        'back.png',
        get_lang('BackTo').' '.get_lang('PlatformAdmin'),
        null,
        ICON_SIZE_MEDIUM
    ),
    '../admin/index.php'
);
$actionLeft .= Display::url(
    Display::return_icon(
        'career.png',
        get_lang('Careers'),
        null,
        ICON_SIZE_MEDIUM
    ),
    'careers.php'
);
$actionLeft .= Display::url(
    Display::return_icon(
        'promotion.png',
        get_lang('Promotions'),
        null,
        ICON_SIZE_MEDIUM
    ),
    'promotions.php'
);

$actions = Display::toolbarAction('toolbar-career', array($actionLeft));
$html .= $form->returnForm();
$careers = $career->get_all($condition); //only status =1

$column_count = 3;
$i = 0;
$grid_js = '';
$career_array = array();

if (!empty($careers)) {
    foreach ($careers as $career_item) {
        $promotion = new Promotion();
        // Getting all promotions
        $promotions = $promotion->get_all_promotions_by_career_id(
            $career_item['id'],
            'name ASC'
        );
        $career_content = '';
        $promotion_array = array();
        if (!empty($promotions)) {
            foreach ($promotions as $promotion_item) {
                if ($promotion_item['status'] == 0) {
                    continue; //avoid status = 0
                }

                // Getting all sessions from this promotion
                $sessions = SessionManager::get_all_sessions_by_promotion(
                    $promotion_item['id']
                );
                $session_list = array();
                foreach ($sessions as $session_item) {
                    $course_list = SessionManager::get_course_list_by_session_id(
                        $session_item['id']
                    );
                    $session_list[] = array(
                        'data' => $session_item,
                        'courses' => $course_list,
                    );
                }
                $promotion_array[$promotion_item['id']] = array(
                    'id' => $promotion_item['id'],
                    'name' => $promotion_item['name'],
                    'sessions' => $session_list,
                );
            }
        }
        $career_array[$career_item['id']] = array(
            'name' => $career_item['name'],
            'promotions' => $promotion_array,
        );
        $careerList = array(
            'promotions' => $promotion_array,
        );
        $careers[$career_item['id']]['career'] = $careerList;
    }
}

$tpl->assign('actions', $actions);
$tpl->assign('form_filter', $html);
$tpl->assign('data', $careers);
$layout = $tpl->get_template('admin/career_dashboard.tpl');
$tpl->display($layout);

Display::display_footer();
