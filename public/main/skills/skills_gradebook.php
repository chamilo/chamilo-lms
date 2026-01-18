<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
SkillModel::isAllowed();

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'display';

// Breadcrumbs
$tool_name = get_lang('Skills and assessments');
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'admin/index.php',
    'name' => get_lang('Administration'),
];
if ('add_skill' === $action) {
    $interbreadcrumb[] = ['url' => 'skills_gradebook.php', 'name' => get_lang('Skills and assessments')];
    $tool_name = get_lang('Add');
}

$gradebook = new Gradebook();
switch ($action) {
    case 'display':
        $content = $gradebook->returnGrid();
        break;
    case 'add_skill':
        $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
        $gradebook_info = $gradebook->get($id);
        $url = api_get_self().'?action='.$action.'&id='.$id;
        $form = $gradebook->show_skill_form($id, $url, $gradebook_info['title']);
        if ($form->validate()) {
            $values = $form->exportValues();
            $gradebook->updateSkillsToGradeBook($values['id'], $values['skill']);
            Display::addFlash(Display::return_message(get_lang('Item added'), 'confirm'));
            header('Location: '.api_get_self());
            exit;
        }
        $content = $form->returnForm();
        break;
    default:
        $content = $gradebook->returnGrid();
        break;
}

Display::display_header($tool_name);

// jqGrid will use this URL to do the selects
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_gradebooks';

// Columns (order must match model.ajax.php)
$columns = [
    get_lang('Title'),
    get_lang('Certificates'),
    get_lang('Skills'),
    get_lang('Detail'),
];

// Column config
$column_model = [
    [
        'name' => 'title',
        'index' => 'title',
        'width' => '100',
        'align' => 'left',
    ],
    [
        'name' => 'certificate',
        'index' => 'certificate',
        'width' => '60',
        'align' => 'center',
        'sortable' => 'false',
        'fixed' => true,
        'resizable' => false,
    ],
    [
        'name' => 'skills',
        'index' => 'skills',
        'width' => '80',
        'align' => 'left',
        'sortable' => 'false',
    ],
    [
        'name' => 'actions',
        'index' => 'actions',
        'width' => '60',
        'align' => 'center',
        'formatter' => 'action_formatter',
        'sortable' => 'false',
        'fixed' => true,
        'resizable' => false,
    ],
];

$extra_params = [];
$extra_params['shrinkToFit'] = true;
$extra_params['forceFit'] = true;
$extra_params['height'] = 'auto';
$extra_params['autowidth'] = 'true';
$extra_params['height'] = 'auto';

/**
 * Keep jqGrid full width inside the wrapper (per-page only).
 */
$extra_params['gridComplete'] = '
    var $wrap = $("#gradebooks-grid-container");
    if ($wrap.length) {
        var w = $wrap.width();
        if (w && w > 0) {
            $("#careers").jqGrid("setGridWidth", w, true);
        }
    }
';

$iconAdd = Display::getMdiIcon(
    ActionIcon::ADD,
    'ch-tool-icon',
    null,
    ICON_SIZE_SMALL,
    addslashes(get_lang('Add skill'))
);

$iconAddNa = Display::getMdiIcon(
    ActionIcon::ADD,
    'ch-tool-icon-disabled',
    null,
    ICON_SIZE_SMALL,
    addslashes(get_lang('Your gradebook first needs a certificate in order to be linked to a skill'))
);

// With this function we can add actions to the jqGrid (edit, delete, etc)
$action_links = 'function action_formatter(cellvalue, options, rowObject) {
    // certificates
    if (rowObject[4] == 1) {
        return \'<a href="?action=add_skill&id=\'+options.rowId+\'">'.$iconAdd.'</a>\';
    } else {
        return \''.$iconAddNa.'\';
    }
}';
?>
    <style>
        #gradebooks-grid-container { width: 100% !important; }
        .skills-gradebook-header {
            margin: 0 0 16px 0;
            padding: 14px 16px;
            border: 1px solid rgba(0,0,0,.08);
            border-radius: 12px;
            background: #fff;
        }
        .skills-gradebook-header h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
        }
        .skills-gradebook-header p {
            margin: 6px 0 0;
            color: #6b7280;
            font-size: 13px;
        }

        /* jqGrid wrappers */
        #gradebooks-grid-container .ui-jqgrid,
        #gradebooks-grid-container .ui-jqgrid-view,
        #gradebooks-grid-container .ui-jqgrid-hdiv,
        #gradebooks-grid-container .ui-jqgrid-bdiv,
        #gradebooks-grid-container .ui-jqgrid-pager,
        #gradebooks-grid-container .ui-jqgrid .ui-jqgrid-htable,
        #gradebooks-grid-container .ui-jqgrid .ui-jqgrid-btable {
            width: 100% !important;
            box-sizing: border-box;
        }
    </style>

    <script>
        $(function() {
            <?php
            echo Display::grid_js(
                'gradebooks',
                $url,
                $columns,
                $column_model,
                $extra_params,
                [],
                $action_links,
                true
            );
            ?>
        });
    </script>
<?php

// Add a visible content header (some themes don't show the main title from display_header)
$visibleTitle = ('add_skill' === $action) ? get_lang('Add skill') : get_lang('Skills and assessments');
$visibleDesc  = ('add_skill' === $action)
    ? get_lang('Link a skill to this gradebook')
    : get_lang('Manage the link between certificates and skills for each gradebook');

$headerHtml = '
  <div class="skills-gradebook-header">
    <h2>'.htmlspecialchars($visibleTitle, ENT_QUOTES).'</h2>
    <p>'.htmlspecialchars($visibleDesc, ENT_QUOTES).'</p>
  </div>
';

// Print content. On display action, wrap it so we can force full width reliably.
if ('display' === $action) {
    echo '<div id="gradebooks-grid-container">';
    echo $headerHtml;
    echo $content;
    echo '</div>';
} else {
    echo $headerHtml;
    echo $content;
}

Display::display_footer();
