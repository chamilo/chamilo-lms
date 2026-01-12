<?php

/* For licensing terms, see /license.txt */

/**
 * @author Julio Montoya <gugli100@gmail.com>
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();
SkillModel::isAllowed(api_get_user_id());

$origin = isset($_GET['origin']) ? strtolower((string) $_GET['origin']) : 'admin';
if ('social' === $origin) {
    $interbreadcrumb[] = [
        'url' => '/social',
        'name' => get_lang('Social'),
    ];
} elseif ('admin' === $origin) {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'admin/index.php',
        'name' => get_lang('Administration'),
    ];
} else {
    $interbreadcrumb[] = [
        'url' => '/home',
        'name' => get_lang('Home'),
    ];
}

$pageTitle = get_lang('Your skill ranking');

//jqgrid will use this URL to do the selects
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_user_skill_ranking';

//The order is important you need to check the $column variable in the model.ajax.php file
$columns = [
    get_lang('Photo'),
    get_lang('First name'),
    get_lang('Last name'),
    get_lang('Skills acquired'),
    get_lang('Currently learning'),
    get_lang('Rank'),
];

$column_model = [
    [
        'name' => 'photo',
        'index' => 'photo',
        'width' => '150px',
        'align' => 'center',
        'sortable' => 'false',
    ],
    [
        'name' => 'firstname',
        'index' => 'firstname',
        'width' => '250px',
        'align' => 'center',
        'sortable' => 'false',
    ],
    [
        'name' => 'lastname',
        'index' => 'lastname',
        'width' => '250px',
        'align' => 'center',
        'sortable' => 'false',
    ],
    [
        'name' => 'skills_acquired',
        'index' => 'skills_acquired',
        'width' => '100px',
        'align' => 'center',
        'sortable' => 'false',
    ],
    [
        'name' => 'currently_learning',
        'index' => 'currently_learning',
        'width' => '150px',
        'align' => 'center',
        'sortable' => 'false',
    ],
    [
        'name' => 'rank',
        'index' => 'rank',
        'width' => '100px',
        'align' => 'center',
        'sortable' => 'false',
    ],
];

$extra_params = [];
$extra_params['autowidth'] = 'true';
$extra_params['height'] = 'auto';
$extra_params['rowList'] = [10, 20, 50, 100];

$jqgrid = Display::grid_js(
    'skill_ranking',
    $url,
    $columns,
    $column_model,
    $extra_params,
    [],
    null,
    true
);

$tpl = new Template(get_lang('Skills ranking'));
$tpl->assign('jqgrid_html', $jqgrid);

$content = Display::grid_html('skill_ranking');

$headerHtml = '
  <div class="skill-ranking-header">
    <h2 class="skill-ranking-title">'.htmlspecialchars($pageTitle, ENT_QUOTES).'</h2>
  </div>
';

$content = $headerHtml.'
  <div class="skill-ranking-grid">
    '.$content.'
  </div>
';

$template = $tpl->get_template('skill/skill_ranking.tpl');
$content .= $tpl->fetch($template);
$tpl->assign('content', $content);

$tpl->display_one_col_template();
