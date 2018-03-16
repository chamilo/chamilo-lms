<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.social
 *
 * @author Julio Montoya <gugli100@gmail.com>
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();
Skill::isAllowed(api_get_user_id());

//Add the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jqgrid_js();

$interbreadcrumb[] = ["url" => "index.php", "name" => get_lang('Skills')];

//jqgrid will use this URL to do the selects
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_user_skill_ranking';

//The order is important you need to check the the $column variable in the model.ajax.php file
$columns = [
    get_lang('Photo'),
    get_lang('FirstName'),
    get_lang('LastName'),
    get_lang('SkillsAcquired'),
    get_lang('CurrentlyLearning'),
    get_lang('Rank'),
];

$column_model = [
    [
        'name' => 'photo',
        'index' => 'photo',
        'width' => '10',
        'align' => 'center',
        'sortable' => 'false',
    ],
    [
        'name' => 'firstname',
        'index' => 'firstname',
        'width' => '70',
        'align' => 'center',
        'sortable' => 'false',
    ],
    [
        'name' => 'lastname',
        'index' => 'lastname',
        'width' => '70',
        'align' => 'center',
        'sortable' => 'false',
    ],
    [
        'name' => 'skills_acquired',
        'index' => 'skills_acquired',
        'width' => '30	',
        'align' => 'center',
        'sortable' => 'false',
    ],
    [
        'name' => 'currently_learning',
        'index' => 'currently_learning',
        'width' => '30',
        'align' => 'center',
        'sortable' => 'false',
    ],
    [
        'name' => 'rank',
        'index' => 'rank',
        'width' => '30',
        'align' => 'center',
        'sortable' => 'false',
    ],
];

//Autowidth
$extra_params['autowidth'] = 'true';

//height auto
$extra_params['height'] = 'auto';
//$extra_params['excel'] = 'excel';
//$extra_params['rowList'] = array(10, 20 ,30);

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
$content = Display::grid_html('skill_ranking');

$tpl = new Template(get_lang('Ranking'));
$tpl->assign('jqgrid_html', $jqgrid);
$template = $tpl->get_template('skill/skill_ranking.tpl');
$content .= $tpl->fetch($template);
$tpl->assign('content', $content);

$tpl->display_one_col_template();
