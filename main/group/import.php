<?php
/* For licensing terms, see /license.txt */

// Name of the language file that needs to be included
$language_file = 'group';

require_once '../inc/global.inc.php';
$this_section = SECTION_COURSES;
$current_course_tool  = TOOL_GROUP;

// Notice for unauthorized people.
api_protect_course_script(true);

if (!api_is_allowed_to_edit(false, true)) {
    api_not_allowed(true);
}

$nameTools = get_lang('Import');

/*	Libraries */

include_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';
include_once api_get_path(LIBRARY_PATH).'export.lib.inc.php';

$interbreadcrumb[] = array('url' => 'group.php', 'name' => get_lang('Groups'));

Display::display_header($nameTools, 'Group');

$form = new FormValidator('import', api_get_self());
$form->addElement('header', get_lang('ImportGroups'));
$form->addElement('file', 'file', get_lang('File'));
$form->addElement('button', 'submit', get_lang('Import'));

if ($form->validate()) {
    $groupData = Import::csv_reader($_FILES['file']['tmp_name']);
    $result = GroupManager::importCategoriesAndGroupsFromArray($groupData);
    if (!empty($result)) {
        $html = null;

        foreach ($result as $status => $data) {
            $html .= " <h3>".get_lang(ucfirst($status)).' </h3>';
            if (!empty($data['category'])) {
                $html .= "<h4> ".get_lang('Categories').':</h4>';
                foreach ($data['category'] as $category) {
                    $html .= "<div>".$category['category']."</div>";
                }
            }

            if (!empty($data['group'])) {
                $html .= "<h4> ".get_lang('Groups').':</h4>';
                foreach ($data['group'] as $group) {
                    $html .= "<div>".$group['group']."</div>";
                }
            }
        }
        echo $html;
    }
}
$form->display();

Display::display_footer();
