<?php

/* For licensing terms, see /license.txt */

/**
 * Careers dashboard.
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$allowCareer = api_get_configuration_value('allow_session_admin_read_careers');
$useCareerHierarchy = api_get_configuration_value('career_hierarchy_enable');

api_protect_admin_script($allowCareer);

$this_section = SECTION_PLATFORM_ADMIN;

//Adds the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jqgrid_js();

// setting breadcrumbs
$interbreadcrumb[] = [
    'url' => 'index.php',
    'name' => get_lang('PlatformAdmin'),
];
$interbreadcrumb[] = [
    'url' => 'career_dashboard.php',
    'name' => get_lang('CareersAndPromotions'),
];
$tpl = new Template(get_lang('CareersAndPromotions'));

$html = null;
$showHierarchy = $_GET['showHierarchy'] ?? null;
if ($useCareerHierarchy && is_null($showHierarchy)) {
    $showHierarchy = 1;
} elseif (!$useCareerHierarchy) {
    $showHierarchy = 0;
}
$form = new FormValidator('filter_form', 'GET', api_get_self());

$career = new Career();

$condition = ['status = ?' => 1];
if ($form->validate()) {
    $data = $form->getSubmitValues();
    $filter = (int) $data['filter'];
    if (!empty($filter) && $showHierarchy == 0) {
        $condition = ['status = ? AND id = ? ' => [1, $filter]];
    }
}

$careers = $career->get_all(['status = ?' => 1]); //only status =1
$career_select_list = [];
$career_select_list[0] = ' -- '.get_lang('Select').' --';
foreach ($careers as $item) {
    $career_select_list[$item['id']] = $item['name'];
}

$form->addSelect(
    'filter',
    get_lang('Career'),
    $career_select_list,
    ['id' => 'filter_1']
);
$form->addButtonSearch(get_lang('Filter'));

if ($useCareerHierarchy && $showHierarchy == 1) {
    $form->addHidden('showHierarchy', '1');
} else {
    $form->addHidden('showHierarchy', '0');
}
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
if ($useCareerHierarchy) {
    if ($showHierarchy) {
        $actionLeft .= Display::url(
            Display::return_icon(
                'forum_listview.png',
                get_lang('HideCareersHierarchy'),
                null,
                ICON_SIZE_MEDIUM
            ),
            'career_dashboard.php?showHierarchy=0'
        );
    } else {
        $actionLeft .= Display::url(
            Display::return_icon(
                'forum_nestedview.png',
                get_lang('ShowCareersHierarchy'),
                null,
                ICON_SIZE_MEDIUM
            ),
            'career_dashboard.php?showHierarchy=1'
        );
    }
}
if (api_is_platform_admin()) {
    $actionLeft .= Display::url(
        Display::return_icon(
            'promotion.png',
            get_lang('Promotions'),
            null,
            ICON_SIZE_MEDIUM
        ),
        'promotions.php'
    );
}

$actions = Display::toolbarAction('toolbar-career', [$actionLeft]);
$html .= $form->returnForm();
$careers = $career->get_all($condition); //only status =1

$column_count = 3;
$i = 0;
$grid_js = '';
$career_array = [];

if (!empty($careers)) {
    foreach ($careers as $career_item) {
        $promotion = new Promotion();
        // Getting all promotions
        $promotions = $promotion->get_all_promotions_by_career_id(
            $career_item['id'],
            'name ASC'
        );
        $career_content = '';
        $promotion_array = [];
        if (!empty($promotions)) {
            foreach ($promotions as $promotion_item) {
                if ($promotion_item['status'] == 0) {
                    continue; //avoid status = 0
                }

                $session_list = [];
                // Getting all sessions from this promotion
                if (!$useCareerHierarchy || 0 == $showHierarchy) {
                    $sessions = SessionManager::get_all_sessions_by_promotion(
                        $promotion_item['id']
                    );
                    foreach ($sessions as $session_item) {
                        $course_list = SessionManager::get_course_list_by_session_id($session_item['id']);
                        $session_list[] = [
                            'data' => $session_item,
                            'courses' => $course_list,
                        ];
                    }
                }
                $promotion_array[$promotion_item['id']] = [
                    'id' => $promotion_item['id'],
                    'name' => $promotion_item['name'],
                    'sessions' => $session_list,
                ];
            }
        }
        $career_array[$career_item['id']] = [
            'name' => $career_item['name'],
            'promotions' => $promotion_array,
        ];
        $careerList = [
            'promotions' => $promotion_array,
        ];
        $careers[$career_item['id']]['career'] = $careerList;
    }
}
if ($useCareerHierarchy && 1 == $showHierarchy) {
    $filter = $filter ?? 0;
    $careers = $career->orderCareersByHierarchy($careers, $filter);
}
$tpl->assign('actions', $actions);
$tpl->assign('form_filter', $html);
$tpl->assign('data', $careers);

if ($useCareerHierarchy && 1 == $showHierarchy) {
    $layout = $tpl->get_template('admin/career_dashboard_hierarchy.tpl');
} else {
    $layout = $tpl->get_template('admin/career_dashboard.tpl');
}
$tpl->display($layout);
