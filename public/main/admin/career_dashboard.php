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

// Setting breadcrumbs
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
    $filter = (int) ($data['filter'] ?? 0);

    if (!empty($filter)) {
        // Filter by active career + selected career ID
        $condition = ['status = ? AND id = ?' => [1, $filter]];
    }
}

// Build filter select list (only active careers)
$careers = $career->get_all(['status = ?' => 1]); // Only status = 1
$career_select_list = [];
$career_select_list[0] = ' -- '.get_lang('Select').' --';

foreach ($careers as $item) {
    $career_select_list[(int) $item['id']] = $item['title'];
}

$form->addSelect(
    'filter',
    get_lang('Career'),
    $career_select_list,
    [
        'id' => 'filter_1',
        'class' => 'w-full max-w-none',
    ]
);

$form->addButtonSearch(get_lang('Filter'));

// Action links
$actionLeft = Display::url(
    Display::getMdiIcon(
        ActionIcon::BACK,
        'ch-tool-icon',
        null,
        ICON_SIZE_MEDIUM,
        get_lang('Back to').' '.get_lang('Administration')
    ),
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

// Render filter form HTML
$html .= $form->returnForm();

// Load careers with filter applied (only active careers)
$careers = $career->get_all($condition);

$career_array = [];

if (!empty($careers)) {
    foreach ($careers as $career_item) {
        $promotion = new Promotion();

        // Get promotions for this career
        $promotions = $promotion->get_all_promotions_by_career_id(
            (int) $career_item['id'],
            'title ASC'
        );

        $promotion_array = [];

        if (!empty($promotions)) {
            foreach ($promotions as $promotion_item) {
                if (0 == (int) ($promotion_item['status'] ?? 0)) {
                    continue; // Skip inactive promotions
                }

                // Get sessions for this promotion
                $sessions = SessionManager::get_all_sessions_by_promotion((int) $promotion_item['id']);

                $session_list = [];
                foreach ($sessions as $session_item) {
                    $course_list = SessionManager::get_course_list_by_session_id((int) $session_item['id']);

                    $session_list[] = [
                        'data' => $session_item,
                        'courses' => $course_list,
                    ];
                }

                $promotion_array[(int) $promotion_item['id']] = [
                    'id' => (int) $promotion_item['id'],
                    'title' => $promotion_item['title'],
                    'sessions' => $session_list,
                ];
            }
        }

        $career_array[(int) $career_item['id']] = [
            'title' => $career_item['title'],
            'promotions' => $promotion_array,
        ];

        $careerList = [
            'promotions' => $promotion_array,
        ];

        $careers[(int) $career_item['id']]['career'] = $careerList;
    }
}

$tpl->assign('actions', $actions);
$tpl->assign('form_filter', $html);
$tpl->assign('data', $careers);

$layout = $tpl->get_template('admin/career_dashboard.html.twig');
if (empty($layout)) {
    $layout = $tpl->get_template('admin/career_dashboard.tpl');
}

$tpl->display($layout);
