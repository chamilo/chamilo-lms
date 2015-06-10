<?php
/* For licensing terms, see /license.txt */

require_once '../inc/global.inc.php';

// The section for the tabs
$this_section = SECTION_COURSES;

api_protect_course_script(true);

if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : '';

switch ($action) {
    case 'delete_icon':
        $tool = CourseHome::getTool($id);
        if (empty($tool)) {
            api_not_allowed(true);
        }

        $currentUrl = api_get_self().'?'.api_get_cidreq();
        Display::addFlash(Display::return_message(get_lang('Updated')));
        CourseHome::deleteIcon($id);
        header('Location: '.$currentUrl);
        exit;

        break;
    case 'edit_icon':
        $tool = CourseHome::getTool($id);
        if (empty($tool)) {
            api_not_allowed(true);
        }

        $interbreadcrumb[] = array('url' => api_get_self().'?'.api_get_cidreq(), 'name' => get_lang('Tools'));

        $toolName = $tool['name'];

        $currentUrl = api_get_self().'?action=edit_icon&id=' . $id.'&'.api_get_cidreq();

        $form = new FormValidator('icon_edit', 'post', $currentUrl);
        $form->addElement('header', get_lang('EditIcon'));
        $form->addElement('text', 'name', get_lang('Name'));
        $form->addElement('text', 'link', get_lang('Links'));

        if (isset($tool['custom_icon']) && !empty($tool['custom_icon'])) {
            $form->addLabel(
                get_lang('Icon'),
                Display::img(
                    CourseHome::getCustomWebIconPath().$tool['custom_icon']
                )
            );

            $form->addCheckBox('delete_icon', null, get_lang('DeletePicture'));
        }

        $allowed_picture_types = array ('jpg', 'jpeg', 'png');
        $form->addElement('file', 'icon', get_lang('CustomIcon'));
        $form->addRule('icon', get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')', 'filetype', $allowed_picture_types);

        $form->addElement('select', 'target', get_lang('Target'), array('_self' => '_self', '_blank' => '_blank'));
        $form->addElement('select', 'visibility', get_lang('Visibility'), array(1 => get_lang('Visible'), 0 => get_lang('Invisible')));
        $form->addElement('textarea', 'description', get_lang('Description'),array ('rows' => '3', 'cols' => '40'));
        $form->addButtonUpdate(get_lang('Update'));

        $form->setDefaults($tool);

        $content = $form->returnForm();

        if ($form->validate()) {
            $data = $form->getSubmitValues();
            CourseHome::updateTool($id, $data);
            Display::addFlash(Display::return_message(get_lang('Updated')));
            if (isset($data['delete_icon'])) {
                CourseHome::deleteIcon($id);
            }

            header('Location: '.$currentUrl);
            exit;
        }
        break;
    case 'list':
    default:
        $toolName = get_lang('Tools');
        $toolList = CourseHome::toolsIconsAction(
            api_get_course_int_id(),
            api_get_session_id()
        );

        $table = '<table class="data_table">';
        foreach ($toolList as $tool) {
            $table .= '<tr>';
            $table .= '<td><a href="' . api_get_path(WEB_CODE_PATH) . $tool['link'] . '?' . api_get_cidreq() . '">
                    <img src="' . api_get_path(WEB_IMG_PATH).$tool['image']. '"></a></td>';
            $table .= '<td><a href="' . api_get_path(WEB_CODE_PATH) . $tool['link'] . '?' . api_get_cidreq() . '">' .
                $tool['name']. '</a></td>';
            $table .= '<td>
                    <a class="btn btn-primary" href="' . api_get_self() . '?action=edit_icon&id=' . $tool['iid'] . '&'.api_get_cidreq().'">' .
                    get_lang('Edit'). '</a></td>';
            $delete = (!empty($tool['custom_icon'])) ? '<a class="btn btn-danger" href="' . api_get_self() . '?action=delete_icon&id=' . $tool['iid'] . '&'.api_get_cidreq().'">' .
                get_lang('Delete'). '</a>' : '';
            $table .= '<td>' . $delete . '</td>';
            $table .= '</tr>';
        }
        $table .= '</table>';
        $content = $table;

        break;
}

$tpl = new Template($toolName);

$tpl->assign('content', $content);
$template = $tpl->get_template('layout/layout_1_col.tpl');
$tpl->display($template);


