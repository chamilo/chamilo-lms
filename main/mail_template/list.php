<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();

//Adds the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jqgrid_js();

$action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : 'list';
$check = Security::check_token('request');
$token = Security::get_token();
$id = !empty($_REQUEST['id']) ? $_REQUEST['id'] : 0;

$mailTemplate = new MailTemplateManager();
$content = '';

switch ($action) {
    case 'add':
        $url = api_get_self().'?action='.Security::remove_XSS($_GET['action']);
        $form = $mailTemplate->returnForm($url, 'add');

        // The validation or display
        if ($form->validate()) {
            if ($check) {
                $values = $form->exportValues();
                $values['template'] = $values['email_template'];
                $values['author_id'] = api_get_user_id();
                $values['url_id'] = api_get_current_access_url_id();
                $res = $mailTemplate->save($values);
                if ($res) {
                    Display::addFlash(Display::return_message(get_lang('ItemAdded'), 'confirm'));
                }
            }
            header('Location: '.api_get_self());
            exit;
        } else {
            $content .= '<div class="actions">';
            $content .= Display::url(
                Display::return_icon('back.png', get_lang('Back'), '', ICON_SIZE_MEDIUM),
                api_get_self()
            );
            $content .= '</div>';
            $form->addElement('hidden', 'sec_token');
            $form->setConstants(['sec_token' => $token]);
            $content .= $form->returnForm();
        }
        break;
    case 'edit':
        $url = api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&id='.$id;
        $form = $mailTemplate->returnForm($url, 'edit');

        $content .= '<div class="actions">';
        $content .= Display::url(
            Display::return_icon('back.png', get_lang('Back'), '', ICON_SIZE_MEDIUM),
            api_get_self()
        );
        $content .= '</div>';
        $content .= $form->returnForm();

        // The validation or display
        if ($form->validate()) {
            //if ($check) {
            $values = $form->exportValues();
            $values['template'] = $values['email_template'];
            $res = $mailTemplate->update($values);
            if ($res) {
                Display::addFlash(
                        Display::return_message(get_lang('ItemUpdated').': '.$values['name'], 'confirm')
                    );
            }
            //}
            header('Location: '.api_get_self());
            exit;
        }
        break;
    case 'delete':
        $mailTemplate->delete($id);
        Display::addFlash(
            Display::return_message(get_lang('Deleted'), 'confirm')
        );
        header('Location: '.api_get_self());
        exit;
        break;
    case 'set_default':
        $mailTemplate->setDefault($id);
        Display::addFlash(
            Display::return_message(get_lang('Updated'), 'confirm')
        );
        header('Location: '.api_get_self());
        break;
    case 'list':
        $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_mail_template';
        $columns = [
            get_lang('Name'),
            get_lang('Type'),
            get_lang('Default'),
            get_lang('Actions'),
        ];
        $column_model = [
            [
                'name' => 'name',
                'index' => 'name',
                'width' => '180',
                'align' => 'left',
            ],
            [
                'name' => 'type',
                'index' => 'type',
                'width' => '100',
                'align' => 'left',
            ],
            [
                'name' => 'default_template',
                'index' => 'default_template',
                'width' => '100',
                'align' => 'left',
                'hidden' => 'true',
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
        
        var defaultIcon = "<i class=\"fa fa-circle fa-2x\"></i>";
        if (rowObject[2] == 1) {
            defaultIcon = "<i class=\"fa fa-check-circle fa-2x\"></i>";
        }
        return \'&nbsp;<a href="?action=edit&id=\'+options.rowId+\'"><i class=\"fa fa-pencil fa-2x\"></i></a>'.
            '&nbsp;<a href="?action=set_default&id=\'+options.rowId+\'" title=\"'.get_lang('Default').'\">\'+ defaultIcon + \'</a>'.
            '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES))."\'".')) return false;"  href="?sec_token='.$token.'&action=delete&id=\'+options.rowId+\'"><i class=\"fa fa-trash fa-2x\"></i></a> \';
        }';

        $content = $mailTemplate->display();
        $content .= '
        <script>
            $(function() {
                '.Display::grid_js('mail_template', $url, $columns, $column_model, $extra_params, [], $action_links, true).'
            });
        </script>';
        break;
}

$template = new Template();
$template->assign('content', $content);
$template->display_one_col_template();
