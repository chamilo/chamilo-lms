<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

// The section for the tabs
$this_section = SECTION_COURSES;

$sessionId = api_get_session_id();

if (!empty($sessionId)) {
    api_not_allowed();
}

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

        $interbreadcrumb[] = [
            'url' => api_get_self().'?'.api_get_cidreq(),
            'name' => get_lang('CustomizeIcons'),
        ];
        $toolName = Security::remove_XSS(stripslashes($tool['name']));

        $currentUrl = api_get_self().'?action=edit_icon&id='.$id.'&'.api_get_cidreq();

        $form = new FormValidator('icon_edit', 'post', $currentUrl);
        $form->addHeader(get_lang('EditIcon'));
        $form->addHtml('<div class="col-md-7">');
        $form->addText('name', get_lang('Name'));
        $form->addText('link', get_lang('Links'));
        $allowedPictureTypes = ['jpg', 'jpeg', 'png'];
        $form->addFile('icon', get_lang('CustomIcon'));
        $form->addRule(
            'icon',
            get_lang('OnlyImagesAllowed').' ('.implode(',', $allowedPictureTypes).')',
            'filetype',
            $allowedPictureTypes
        );
        $form->addSelect(
            'target',
            get_lang('LinkTarget'),
            [
                '_self' => get_lang('LinkOpenSelf'),
                '_blank' => get_lang('LinkOpenBlank'),
            ]
        );
        $form->addSelect(
            'visibility',
            get_lang('Visibility'),
            [1 => get_lang('Visible'), 0 => get_lang('Invisible')]
        );
        $form->addTextarea(
            'description',
            get_lang('Description'),
            ['rows' => '3', 'cols' => '40']
        );
        $form->addButtonUpdate(get_lang('Update'));
        $form->addHtml('</div>');
        $form->addHtml('<div class="col-md-5">');
        if (isset($tool['custom_icon']) && !empty($tool['custom_icon'])) {
            $form->addLabel(
                get_lang('CurrentIcon'),
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
        $list = [];
        $tmp = [];
        foreach ($toolList as $tool) {
            $tmp['id'] = $tool['id'];

            $tmp['name'] = Security::remove_XSS(stripslashes($tool['name']));
            $toolIconName = 'Tool'.api_underscore_to_camel_case($tool['name']);
            $toolIconName = isset($$toolIconName) ? get_lang($toolIconName) : $tool['name'];

            $tmp['name'] = $toolIconName;
            $tmp['link'] = $tool['link'];

            if (!empty($tool['custom_icon'])) {
                $image = CourseHome::getCustomWebIconPath().$tool['custom_icon'];
                $icon = Display::img($image, $tool['name']);
            } else {
                $image = 'tool_'.(substr($tool['image'], 0, strpos($tool['image'], '.'))).'.png';
                $icon = Display::return_icon(
                    $image,
                    null,
                    ['id' => 'tool_'.$tool['id']],
                    ICON_SIZE_BIG,
                    false,
                    true
                );
            }
            $tmp['image'] = $icon;
            $tmp['visibility'] = $tool['visibility'];

            $delete = (!empty($tool['custom_icon'])) ? "<a class=\"btn btn-default\" onclick=\"javascript:
                if(!confirm('".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES, $charset)).
                "')) return false;\" href=\"".api_get_self().'?action=delete_icon&id='.$tool['iid'].'&'.api_get_cidreq()."\">
            <i class=\"fas fa-trash-alt\"></i></a>" : "";
            $edit = '<a class="btn btn-outline-secondary btn-sm" href="'.api_get_self().'?action=edit_icon&id='.$tool['iid'].'&'.api_get_cidreq().'"><i class="fas fa-pencil-alt"></i></a>';

            $tmp['action'] = $edit.$delete;

            $list[] = $tmp;
        }
        $tpl->assign('tools', $list);
        $layout = $tpl->get_template("course_info/tools.html.twig");
        $content = $tpl->fetch($layout);

        break;
}

$tpl = new Template($toolName);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
