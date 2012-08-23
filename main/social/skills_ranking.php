<?php
/* For licensing terms, see /chamilo_license.txt */
/**
 * @package chamilo.social
 * @author Julio Montoya <gugli100@gmail.com>
 */
/**
 * Initialization
 */
$language_file = array('userInfo');
$cidReset = true;

require_once '../inc/global.inc.php';

//Add the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jqgrid_js();

$interbreadcrumb[] = array("url" => "index.php","name" => get_lang('Skills'));

//jqgrid will use this URL to do the selects
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_user_skill_ranking';

//The order is important you need to check the the $column variable in the model.ajax.php file 
$columns        = array(get_lang('Photo'), 
                        get_lang('Firstname'), 
                        get_lang('Lastname'), 
                        get_lang('SkillsAcquired'), 
                        get_lang('CurrentlyLearning'), 
                        get_lang('Rank'));
                        
$column_model   = array(
                        array('name'=>'photo',              'index'=>'photo',          'width'=>'10',  'align'=>'center', 'sortable' => 'false'),                        
                        array('name'=>'firstname',          'index'=>'firstname',   'width'=>'70',   'align'=>'left', 'sortable' => 'false'),
                        array('name'=>'lastname',           'index'=>'lastname',     'width'=>'70',   'align'=>'left', 'sortable' => 'false'),
                        array('name'=>'skills_acquired',    'index'=>'skills_acquired', 'width'=>'30	',   'align'=>'left', 'sortable' => 'false'),
                        array('name'=>'currently_learning', 'index'=>'currently_learning',    'width'=>'30',   'align'=>'left', 'sortable' => 'false'),
                        array('name'=>'rank',               'index'=>'rank',      'width'=>'30',   'align'=>'left', 'sortable' => 'false')
                       );

//Autowidth             
$extra_params['autowidth'] = 'true';

//height auto 
$extra_params['height'] = 'auto';
//$extra_params['excel'] = 'excel';

//$extra_params['rowList'] = array(10, 20 ,30);
                       
$jqgrid = Display::grid_js('skill_ranking', $url,$columns,$column_model,$extra_params, array(), $action_links,true);

$content = Display::grid_html('skill_ranking');


$tpl = new Template($tool_name);

$tpl->assign('actions', $actions);
$tpl->assign('message', $message);

$tpl->assign('jqgrid_html', $jqgrid);
$content .= $tpl->fetch('default/skill/skill_ranking.tpl');
$tpl->assign('content', $content);	

$tpl->display_one_col_template();

