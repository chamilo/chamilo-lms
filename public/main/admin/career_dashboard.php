<?php

/* For licensing terms, see /license.txt */

/**
 * Careers dashboard.
 */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ToolIcon;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$allowCareer = ('true' === api_get_setting('session.allow_session_admin_read_careers'));

api_protect_admin_script($allowCareer);

$this_section = SECTION_PLATFORM_ADMIN;

//Adds the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jqgrid_js();

// setting breadcrumbs
$interbreadcrumb[] = [
    'url' => 'index.php',
    'name' => get_lang('Administration'),
];
$interbreadcrumb[] = [
    'url' => 'career_dashboard.php',
    'name' => get_lang('Careers and promotions'),
];
$tpl = new Template(get_lang('Careers and promotions'));

$html = null;
$form = new FormValidator('filter_form', 'GET', api_get_self());

$career = new Career();

$condition = ['status = ?' => 1];
if ($form->validate()) {
    $data = $form->getSubmitValues();
    $filter = (int) $data['filter'];
    if (!empty($filter)) {
        $condition = ['status = ? AND id = ? ' => [1, $filter]];
    }
}

$careers = $career->get_all(['status = ?' => 1]); //only status =1
$career_select_list = [];
$career_select_list[0] = ' -- '.get_lang('Select').' --';
foreach ($careers as $item) {
    $career_select_list[$item['id']] = $item['title'];
}

$form->addSelect(
    'filter',
    get_lang('Career'),
    $career_select_list,
    ['id' => 'filter_1']
);
$form->addButtonSearch(get_lang('Filter'));

// action links
$actionLeft = Display::url(
    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back to').' '.get_lang('Administration')),
    '../admin/index.php'
);
$actionLeft .= Display::url(
    Display::getMdiIcon(ToolIcon::CAREER, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Careers')),
    'careers.php'
);

if (api_is_platform_admin()) {
    $actionLeft .= Display::url(
        Display::getMdiIcon(ToolIcon::PROMOTION, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Promotions')),
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
            'title ASC'
        );
        $career_content = '';
        $promotion_array = [];
        if (!empty($promotions)) {
            foreach ($promotions as $promotion_item) {
                if (0 == $promotion_item['status']) {
                    continue; //avoid status = 0
                }

                // Getting all sessions from this promotion
                $sessions = SessionManager::get_all_sessions_by_promotion(
                    $promotion_item['id']
                );
                $session_list = [];
                foreach ($sessions as $session_item) {
                    $course_list = SessionManager::get_course_list_by_session_id($session_item['id']);
                    $session_list[] = [
                        'data' => $session_item,
                        'courses' => $course_list,
                    ];
                }
                $promotion_array[$promotion_item['id']] = [
                    'id' => $promotion_item['id'],
                    'name' => $promotion_item['title'],
                    'sessions' => $session_list,
                ];
            }
        }
        $career_array[$career_item['id']] = [
            'name' => $career_item['title'],
            'promotions' => $promotion_array,
        ];
        $careerList = [
            'promotions' => $promotion_array,
        ];
        $careers[$career_item['id']]['career'] = $careerList;
    }
}

$tpl->assign('actions', $actions);
$tpl->assign('form_filter', $html);
$tpl->assign('data', $careers);
$layout = $tpl->get_template('admin/career_dashboard.tpl');
$tpl->display($layout);
