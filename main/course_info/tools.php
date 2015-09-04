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

$toolName = get_lang('CustomizeIcons');

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

        $interbreadcrumb[] = array('url' => api_get_self().'?'.api_get_cidreq(), 'name' => get_lang('CustomizeIcons'));
        $toolName = $tool['name'];

        $currentUrl = api_get_self().'?action=edit_icon&id=' . $id.'&'.api_get_cidreq();

        $form = new FormValidator('icon_edit', 'post', $currentUrl);
        $form->addElement('header', get_lang('EditIcon'));
        $form->addHtml('<div class="col-md-7">');
        $form->addElement('text', 'name', get_lang('Name'));
        $form->addElement('text', 'link', get_lang('Links'));
        $allowed_picture_types = array ('jpg', 'jpeg', 'png');
        $form->addElement('file', 'icon', get_lang('CustomIcon'));
        $form->addRule('icon', get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')', 'filetype', $allowed_picture_types);
        $form->addElement('select', 'target', get_lang('Target'), array('_self' => '_self', '_blank' => '_blank'));
        $form->addElement('select', 'visibility', get_lang('Visibility'), array(1 => get_lang('Visible'), 0 => get_lang('Invisible')));
        $form->addElement('textarea', 'description', get_lang('Description'),array ('rows' => '3', 'cols' => '40'));
        $form->addButtonUpdate(get_lang('Update'));
        $form->addHtml('</div>');
        $form->addHtml('<div class="col-md-5">');
        if (isset($tool['custom_icon']) && !empty($tool['custom_icon'])) {
            $form->addLabel(
                get_lang('Icon'),
                Display::img(
                    CourseHome::getCustomWebIconPath().$tool['custom_icon']
                )
            );

            $form->addCheckBox('delete_icon', null, get_lang('DeletePicture'));
        }
        $form->addHtml('</div>');

        $form->setDefaults($tool);

        $content = $form->returnForm();

        if ($form->validate()) {
            $data = $form->getSubmitValues();
            CourseHome::updateTool($id, $data);
            Display::addFlash(Display::return_message(get_lang('Updated')));
            if (isset($data['delete_icon'])) {
                CourseHome::deleteIcon($id);
            }
            $currentUrlReturn = api_get_self().'?'.api_get_cidreq();
            header('Location: '.$currentUrlReturn);
            exit;
        }
        break;
    case 'list':
    default:
        $toolList = CourseHome::toolsIconsAction(
            api_get_course_int_id(),
            api_get_session_id()
        );
        $iconsTools = '<div id="custom-icons">';
        $iconsTools .= Display::page_header(get_lang('CustomizeIcons'), null, 'h4');
        $iconsTools .= '<div class="row">';
        foreach ($toolList as $tool) {

            if ($tool['id']>20) {
                $toolName = $tool['name'];
            } else {
                $toolName = get_lang('Tool'.api_underscore_to_camel_case($tool['name']));
            }

            $iconsTools .= '<div class="col-md-2">';
            $iconsTools .= '<div class="items-tools">';

            if (!empty($tool['custom_icon'])) {
                $image = getCustomWebIconPath().$tool['custom_icon'];
                $icon = Display::img($image, $toolName);
            } else {
                $image = (substr($tool['image'], 0, strpos($tool['image'], '.'))).'.png';
                $icon = Display::return_icon(
                    $image,
                    $toolName,
                    array('id' => 'tool_'.$tool['id']),
                    ICON_SIZE_BIG,
                    false
                );
            }

            $delete = (!empty($tool['custom_icon'])) ? "<a class=\"btn btn-default\" onclick=\"javascript:
                if(!confirm('".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES, $charset)).
                "')) return false;\" href=\"". api_get_self() . '?action=delete_icon&id=' . $tool['iid'] . '&'.api_get_cidreq()."\">
            <i class=\"fa fa-trash-o\"></i></a>" : "";
            $edit = '<a class="btn btn-default" href="' . api_get_self() . '?action=edit_icon&id=' . $tool['iid'] . '&'.api_get_cidreq().'"><i class="fa fa-pencil"></i></a>';

            $iconsTools .= '<div class="icon-tools">'. $icon . '</div>';
            $iconsTools .= '<div class="name-tools">' . $toolName . '</div>';
            $iconsTools .= '<div class="toolbar">' . $edit . $delete . '</div>';
            $iconsTools .= '</div>';
            $iconsTools .= '</div>';
        }
        $iconsTools .= '</div>';
        $iconsTools .= '</div>';

        $content = $iconsTools;

        break;
}

/**
 * @return string
 */
function getCustomWebIconPath()
{
    // Check if directory exists or create it if it doesn't
    $dir = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/upload/course_home_icons/';

    return $dir;
}
$tpl = new Template($toolName);

$tpl->assign('content', $content);
$template = $tpl->get_template('layout/layout_1_col.tpl');
$tpl->display($template);


