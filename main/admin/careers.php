<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

$allowCareer = api_get_configuration_value('allow_session_admin_read_careers');
$useCareerHierarchy = api_get_configuration_value('career_hierarchy_enable');
api_protect_admin_script($allowCareer);

// Add the JS needed to use the jqgrid
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

$action = $_GET['action'] ?? null;

$check = Security::check_token('request');
$token = Security::get_token();

if ($action === 'add') {
    $interbreadcrumb[] = ['url' => 'careers.php', 'name' => get_lang('Careers')];
    $tool_name = get_lang('Add');
} elseif ($action === 'edit') {
    $interbreadcrumb[] = ['url' => 'careers.php', 'name' => get_lang('Careers')];
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Edit')];
    $tool_name = get_lang('Edit');
} else {
    $tool_name = get_lang('Careers');
}

//jqgrid will use this URL to do the selects
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_careers';

//The order is important you need to check the $column variable in the model.ajax.php file
$columns = [get_lang('Name'), get_lang('Description'), get_lang('Actions')];

// Column config
$column_model = [
    [
        'name' => 'name',
        'index' => 'name',
        'width' => '200',
        'align' => 'left',
    ],
    [
        'name' => 'description',
        'index' => 'description',
        'width' => '400',
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

$extra_params['autowidth'] = 'true';
$extra_params['height'] = 'auto';

$diagramLink = '';
$allow = api_get_configuration_value('allow_career_diagram');
if ($allow) {
    $careerDiagramUrl = api_get_path(WEB_CODE_PATH).'admin/career_diagram.php';
    if (api_get_configuration_value('use_career_external_id_as_identifier_in_diagrams')) {
        $diagramLink = '<a href="'.$careerDiagramUrl.'?id=\'+rowObject[3]+\'">'.get_lang('Diagram').'</a>';
    } else {
        $diagramLink = '<a href="'.$careerDiagramUrl.'?id=\'+options.rowId+\'">'.get_lang('Diagram').'</a>';
    }
}

// With this function we can add actions to the jgrid (edit, delete, etc)
if (api_is_platform_admin()) {
    $actionLinks = 'function action_formatter(cellvalue, options, rowObject) {
        return \'<a href="?action=edit&id=\'+options.rowId+\'">'.Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL).'</a>'.
            $diagramLink.
            '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES))."\'".')) return false;"  href="?sec_token='.$token.'&action=copy&id=\'+options.rowId+\'">'.Display::return_icon('copy.png', get_lang('Copy'), '', ICON_SIZE_SMALL).'</a>'.
            '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES))."\'".')) return false;"  href="?sec_token='.$token.'&action=delete&id=\'+options.rowId+\'">'.Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL).'</a>'.
            '\';
    }';
} else {
    $actionLinks = "function action_formatter(cellvalue, options, rowObject) {
        return '".$diagramLink."';
    }";
}

$career = new Career();
$content = '';
$listUrl = api_get_self();

// Action handling: Add
switch ($action) {
    case 'add':
        api_protect_admin_script();

        if (api_get_session_id() != 0 &&
            !api_is_allowed_to_session_edit(false, true)
        ) {
            api_not_allowed();
        }
        Session::write('notebook_view', 'creation_date');

        $url = api_get_self().'?action='.Security::remove_XSS($_GET['action']);
        $form = $career->return_form($url, 'add');

        // The validation or display
        if ($form->validate()) {
            $values = $form->exportValues();
            if (isset($values['parent_id']) && '0' === $values['parent_id']) {
                $values['parent_id'] = null;
            }
            $res = $career->save($values);
            if ($res) {
                Display::addFlash(
                    Display::return_message(get_lang('ItemAdded'), 'confirmation')
                );
            }
            header('Location: '.$listUrl);
            exit;
        } else {
            $content .= '<div class="actions">';
            $content .= '<a href="'.api_get_self().'">'.
                Display::return_icon('back.png', get_lang('Back'), '', ICON_SIZE_MEDIUM).'</a>';
            $content .= '</div>';
            $form->protect();
            $content .= $form->returnForm();
        }
        break;
    case 'edit':
        api_protect_admin_script();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        $careerInfo = $career->get($id);
        if (empty($careerInfo)) {
            api_not_allowed(true);
        }
        // Action handling: Editing
        $url = api_get_self().'?action=edit&id='.$id;
        $form = $career->return_form($url, 'edit');

        // The validation or display
        if ($form->validate()) {
            $values = $form->exportValues();
            $career->update_all_promotion_status_by_career_id($values['id'], $values['status']);
            $old_status = $career->get_status($values['id']);
            if (isset($values['parent_id']) && '0' === $values['parent_id']) {
                $values['parent_id'] = null;
            }
            $res = $career->update($values);

            $values['item_id'] = $values['id'];
            $sessionFieldValue = new ExtraFieldValue('career');
            $sessionFieldValue->saveFieldValues($values);

            if ($res) {
                Display::addFlash(
                    Display::return_message(get_lang('CareerUpdated'), 'confirmation')
                );
                if ($values['status'] && !$old_status) {
                    Display::addFlash(
                        Display::return_message(
                            sprintf(get_lang('CareerXUnarchived'), $values['name']),
                            'confirmation',
                            false
                        )
                    );
                } elseif (!$values['status'] && $old_status) {
                    Display::addFlash(
                        Display::return_message(
                            sprintf(get_lang('CareerXArchived'), $values['name']),
                            'confirmation',
                            false
                        )
                    );
                }
            }
            header('Location: '.$listUrl);
            exit;
        } else {
            $content .= '<div class="actions">';
            $content .= '<a href="'.api_get_self().'">'.Display::return_icon('back.png', get_lang('Back'), '', ICON_SIZE_MEDIUM).'</a>';
            $content .= '</div>';
            $form->protect();
            $content .= $form->returnForm();
        }
        break;
    case 'delete':
        api_protect_admin_script();
        // Action handling: delete
        if ($check) {
            $childCareers = $career->get_all(['parent_id' => $_GET['id']]);
            if (!empty($childCareers)) {
                Display::addFlash(
                    Display::return_message(get_lang('CareerCannotBeDeletedAsItHasChildren'), 'warning')
                );
            } else {
                $res = $career->delete($_GET['id']);
                if ($res) {
                    Display::addFlash(
                        Display::return_message(get_lang('ItemDeleted'), 'confirmation')
                    );
                }
            }
        }
        header('Location: '.$listUrl);
        exit;
        break;
    case 'copy':
        api_protect_admin_script();
        if (api_get_session_id() != 0 && !api_is_allowed_to_session_edit(false, true)) {
            api_not_allowed(true);
        }
        if ($check) {
            $res = $career->copy($_GET['id'], true); //copy career and promotions inside
            if ($res) {
                Display::addFlash(
                    Display::return_message(get_lang('ItemCopied'), 'confirmation')
                );
            }
        }

        header('Location: '.$listUrl);
        exit;
        break;
    default:
        $content = $career->display();
        break;
}

Display::display_header($tool_name);

?>
<script>
    $(function() {
        <?php
        // grid definition see the $career->display() function
        echo Display::grid_js(
            'careers',
            $url,
            $columns,
            $column_model,
            $extra_params,
            [],
            $actionLinks,
            true
        );
        ?>
    });
</script>
<?php

echo $content;

Display::display_footer();
