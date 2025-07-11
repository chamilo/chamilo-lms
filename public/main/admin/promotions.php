<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

//Adds the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jqgrid_js();

// setting breadcrumbs
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => 'career_dashboard.php', 'name' => get_lang('Careers and promotions')];

$action = isset($_GET['action']) ? $_GET['action'] : null;

$check = Security::check_token('request');
$token = Security::get_token();

if ('add' == $action) {
    $interbreadcrumb[] = [
        'url' => 'promotions.php',
        'name' => get_lang('Promotions'),
    ];
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Add')];
} elseif ('edit' == $action) {
    $interbreadcrumb[] = [
        'url' => 'promotions.php',
        'name' => get_lang('Promotions'),
    ];
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Edit')];
} else {
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Promotions')];
}

// The header.
Display::display_header('');

// Tool name
if (isset($_GET['action']) && 'add' == $_GET['action']) {
    $tool = 'Add';
    $interbreadcrumb[] = [
        'url' => api_get_self(),
        'name' => get_lang('Promotion'),
    ];
}
if (isset($_GET['action']) && 'edit' == $_GET['action']) {
    $tool = 'Modify';
    $interbreadcrumb[] = [
        'url' => api_get_self(),
        'name' => get_lang('Promotion'),
    ];
}

$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_promotions';
//The order is important you need to check the model.ajax.php the $column variable
$columns = [
    get_lang('Name'),
    get_lang('Career'),
    get_lang('Description'),
    get_lang('Detail'),
];
$column_model = [
    [
        'name' => 'name',
        'index' => 'name',
        'width' => '180',
        'align' => 'left',
    ],
    [
        'name' => 'career',
        'index' => 'career',
        'width' => '100',
        'align' => 'left',
    ],
    [
        'name' => 'description',
        'index' => 'description',
        'width' => '500',
        'align' => 'left',
        'sortable' => 'false',
    ],
    [
        'name' => 'actions',
        'index' => 'actions',
        'width' => '100',
        'align' => 'left',
        'formatter' => 'action_formatter',
        'sortable' => 'false',
    ],
];
$extra_params['autowidth'] = 'true'; //use the width of the parent
//$extra_params['editurl'] = $url; //use the width of the parent

$extra_params['height'] = 'auto'; //use the width of the parent
//With this function we can add actions to the jgrid
$action_links = 'function action_formatter (cellvalue, options, rowObject) {
    return \'<a href="add_sessions_to_promotion.php?id=\'+options.rowId+\'">'.Display::getMdiIcon(ObjectIcon::SESSION, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Subscribe sessions to promotions')).'</a>'.
    '&nbsp;<a href="?action=edit&id=\'+options.rowId+\'">'.Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit')).'</a>'.
    '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("Please confirm your choice"), ENT_QUOTES))."\'".')) return false;"  href="?sec_token='.$token.'&action=copy&id=\'+options.rowId+\'">'.Display::getMdiIcon(ActionIcon::COPY_CONTENT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Copy')).'</a>'.
    '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("Please confirm your choice"), ENT_QUOTES))."\'".')) return false;"  href="?sec_token='.$token.'&action=delete&id=\'+options.rowId+\'">'.Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete')).'</a> \';
}';

?>
<script>
$(function() {
<?php
     echo Display::grid_js('promotions', $url, $columns, $column_model, $extra_params, [], $action_links, true);
?>
});
</script>
<?php
$promotion = new Promotion();

switch ($action) {
    case 'add':
        if (0 != api_get_session_id() && !api_is_allowed_to_session_edit(false, true)) {
            api_not_allowed();
        }

        //First you need to create a Career
        $career = new Career();
        $careers = $career->get_all();
        if (empty($careers)) {
            $url = Display::url(
                get_lang(
                    'You will have to create a career before you can add promotions (promotions are sub-elements of a career)'
                ),
                'careers.php?action=add'
            );
            echo Display::return_message($url, 'normal', false);
            Display::display_footer();
            exit;
        }

        $url = api_get_self().'?action='.Security::remove_XSS($_GET['action']);
        $form = $promotion->return_form($url, 'add');

        // The validation or display
        if ($form->validate()) {
            if ($check) {
                $values = $form->exportValues();
                $res = $promotion->save($values);
                if ($res) {
                    echo Display::return_message(get_lang('Item added'), 'confirm');
                }
            }
            $promotion->display();
        } else {
            $actions = Display::url(
                Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')),
                api_get_self()
            );
            echo Display::toolbarAction('promotion_actions', [$actions]);
            $form->addElement('hidden', 'sec_token');
            $form->setConstants(['sec_token' => $token]);
            $form->display();
        }
        break;
    case 'edit':
        //Editing
        $url = api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&id='.intval($_GET['id']);
        $form = $promotion->return_form($url, 'edit');

        // The validation or display
        if ($form->validate()) {
            if ($check) {
                $values = $form->exportValues();
                $res = $promotion->update($values);
                $promotion->update_all_sessions_status_by_promotion_id($values['id'], $values['status']);
                if ($res) {
                    echo Display::return_message(get_lang('Promotion updated successfully').': '.$values['name'], 'confirm');
                }
            }
            $promotion->display();
        } else {
            $actions = Display::url(
                Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')),
                api_get_self()
            );
            echo Display::toolbarAction('promotion_actions', [$actions]);
            $form->addElement('hidden', 'sec_token');
            $form->setConstants(['sec_token' => $token]);
            $form->display();
        }
        break;
    case 'delete':
        if ($check) {
            // Action handling: deleting an obj
            $res = $promotion->delete($_GET['id']);
            if ($res) {
                return Display::return_message(get_lang('Item deleted'), 'confirm');
            }
        }
        $promotion->display();
        break;
    case 'copy':
        if (0 != api_get_session_id() && !api_is_allowed_to_session_edit(false, true)) {
            api_not_allowed();
        }
        if ($check) {
            $res = $promotion->copy($_GET['id'], null, true);
            if ($res) {
                echo Display::return_message(
                    get_lang('Item copied').' - '.get_lang(
                        'ExerciseAndLPsAreInvisibleInTheNewCourse'
                    ),
                    'confirm'
                );
            }
        }
        $promotion->display();
        break;
    default:
        $promotion->display();
        break;
}
Display::display_footer();
