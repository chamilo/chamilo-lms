<?php
/* For licensing terms, see /license.txt */

// Name of the language file that needs to be included
$language_file = array('group', 'admin', 'registration');

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

$form = new FormValidator('import', 'post', api_get_self().'?'.api_get_cidreq());
$form->addElement('header', get_lang('ImportGroups'));
$form->addElement('file', 'file', get_lang('ImportCSVFileLocation'));
$form->addRule('file', get_lang('ThisFieldIsRequired'), 'required');
$form->addElement('checkbox', 'delete_not_in_file', null, get_lang('DeleteItemsNotInFile'));
$form->addElement('label', null, Display::url(get_lang('ExampleCSVFile'), api_get_path(WEB_CODE_PATH).'group/example.csv'));
$form->addElement('button', 'submit', get_lang('Import'));

if ($form->validate()) {
    if (isset($_FILES['file']['tmp_name']) && !empty($_FILES['file']['tmp_name'])) {
        $groupData = Import::csv_reader($_FILES['file']['tmp_name']);
        $deleteNotInArray = $form->getSubmitValue('delete_not_in_file') == 1 ? true : false;

        $result = GroupManager::importCategoriesAndGroupsFromArray(
            $groupData,
            $deleteNotInArray
        );

        if (!empty($result)) {
            $html = null;

            foreach ($result as $status => $data) {
                if ($status != 'error') {
                    if (empty($data['category']) && empty($data['group'])) {
                        continue;
                    }
                }

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

                if ($status == 'error') {
                    if (!empty($data)) {
                        foreach ($data as $message) {
                            if (!empty($message)) {
                                $html .= "<div>".$message."</div>";
                            }
                        }
                    }
                }
            }

            echo $html;
        }
    }
}

$form->display();

Display::display_footer();
