<?php
/* For licensing terms, see /license.txt */

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
Skill::isAllowed();

//Adds the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jqgrid_js();

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'display';

// setting breadcrumbs
$tool_name = get_lang('SkillsAndGradebooks');
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];
if ($action == 'add_skill') {
    $interbreadcrumb[] = ['url' => 'skills_gradebook.php', 'name' => get_lang('SkillsAndGradebooks')];
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
        $form = $gradebook->show_skill_form($id, $url, $gradebook_info['name']);
        if ($form->validate()) {
            $values = $form->exportValues();
            $gradebook->updateSkillsToGradeBook($values['id'], $values['skill']);
            Display::addFlash(Display::return_message(get_lang('ItemAdded'), 'confirm'));
            header('Location: '.api_get_self());
            exit;
        }
        $content = $form->returnForm();
        break;
}

Display::display_header($tool_name);

//jqgrid will use this URL to do the selects
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_gradebooks';

//The order is important you need to check the the $column variable in the model.ajax.php file
$columns = [
    get_lang('Name'),
    get_lang('CertificatesFiles'),
    get_lang('Skills'),
    get_lang('Actions'),
];

//Column config
$column_model = [
    [
        'name' => 'name',
        'index' => 'name',
        'width' => '150',
        'align' => 'left',
    ],
    [
        'name' => 'certificate',
        'index' => 'certificate',
        'width' => '25',
        'align' => 'left',
        'sortable' => 'false',
    ],
    [
        'name' => 'skills',
        'index' => 'skills',
        'width' => '300',
        'align' => 'left',
        'sortable' => 'false',
    ],
    [
        'name' => 'actions',
        'index' => 'actions',
        'width' => '30',
        'align' => 'left',
        'formatter' => 'action_formatter',
        'sortable' => 'false',
    ],
];
//Autowidth
$extra_params['autowidth'] = 'true';
//height auto
$extra_params['height'] = 'auto';

$iconAdd = Display::return_icon('add.png', addslashes(get_lang('AddSkill')));
$iconAddNa = Display::return_icon(
    'add_na.png',
    addslashes(get_lang('YourGradebookFirstNeedsACertificateInOrderToBeLinkedToASkill'))
);

//With this function we can add actions to the jgrid (edit, delete, etc)
$action_links = 'function action_formatter(cellvalue, options, rowObject) {
    //certificates
    if (rowObject[4] == 1) {
        return \'<a href="?action=add_skill&id=\'+options.rowId+\'">'.$iconAdd.'</a>'.'\';
    } else {
        return \''.$iconAddNa.'\';
    }
}';
?>
<script>
$(function() {
<?php
    // grid definition see the $career->display() function
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

echo $content;

Display::display_footer();
