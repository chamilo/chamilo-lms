<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\XApi\LrsAuth;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script();

$request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
$plugin = XApiPlugin::create();
$em = Database::getManager();

$pageBaseUrl = api_get_self();
$pageActions = '';
$pageContent = '';

/**
 * @throws \Exception
 *
 * @return \FormValidator
 */
function createForm(LrsAuth $auth = null)
{
    $pageBaseUrl = api_get_self();

    $action = $pageBaseUrl.'?action=add';

    if (null != $auth) {
        $action = $pageBaseUrl."?action=edit&id={$auth->getId()}";
    }

    $form = new FormValidator('frm_xapi_auth', 'post', $action);
    $form->addText('username', get_lang('Username'), true);
    $form->addText('password', get_lang('Password'), true);
    $form->addCheckBox('enabled', get_lang('Enabled'), get_lang('Yes'));

    $form->addButtonSave(get_lang('Save'));

    if (null != $auth) {
        $form->setDefaults(
            [
                'username' => $auth->getUsername(),
                'password' => $auth->getPassword(),
                'enabled' => $auth->isEnabled(),
            ]
        );
    }

    return $form;
}

switch ($request->query->getAlpha('action')) {
    case 'add':
        $form = createForm();

        if ($form->validate()) {
            $values = $form->exportValues();

            $auth = new LrsAuth();
            $auth
                ->setUsername($values['username'])
                ->setPassword($values['password'])
                ->setEnabled(isset($values['enabled']))
                ->setCreatedAt(
                    api_get_utc_datetime(null, false, true)
                );

            $em->persist($auth);
            $em->flush();

            Display::addFlash(
                Display::return_message(get_lang('ItemAdded'), 'success')
            );

            header('Location: '.$pageBaseUrl);
            exit;
        }

        $pageActions = Display::url(
            Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
            $pageBaseUrl
        );
        $pageContent = $form->returnForm();
        break;
    case 'edit':
        $auth = $em->find(LrsAuth::class, $request->query->getInt('id'));

        if (null == $auth) {
            api_not_allowed(true);
        }

        $form = createForm($auth);

        if ($form->validate()) {
            $values = $form->exportValues();

            $auth
                ->setUsername($values['username'])
                ->setPassword($values['password'])
                ->setEnabled(isset($values['enabled']))
                ->setCreatedAt(
                    api_get_utc_datetime(null, false, true)
                );

            $em->persist($auth);
            $em->flush();

            Display::addFlash(
                Display::return_message(get_lang('ItemUpdated'), 'success')
            );

            header('Location: '.$pageBaseUrl);
            exit;
        }

        $pageActions = Display::url(
            Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
            $pageBaseUrl
        );
        $pageContent = $form->returnForm();
        break;
    case 'delete':
        $auth = $em->find(LrsAuth::class, $request->query->getInt('id'));

        if (null == $auth) {
            api_not_allowed(true);
        }

        $em->remove($auth);
        $em->flush();

        Display::addFlash(
            Display::return_message(get_lang('ItemDeleted'), 'success')
        );

        header('Location: '.$pageBaseUrl);
        exit;
    case 'list':
    default:
        $pageActions = Display::url(
            Display::return_icon('add.png', get_lang('Add'), [], ICON_SIZE_MEDIUM),
            $pageBaseUrl.'?action=add'
        );
        $pageContent = Display::return_message(get_lang('NoData'), 'warning');

        $auths = $em->getRepository(LrsAuth::class)->findAll();

        if (count($auths) > 0) {
            $row = 0;

            $table = new HTML_Table(['class' => 'table table-striped table-hover']);
            $table->setHeaderContents($row, 0, get_lang('Username'));
            $table->setHeaderContents($row, 1, get_lang('Password'));
            $table->setHeaderContents($row, 2, get_lang('Enabled'));
            $table->setHeaderContents($row, 3, get_lang('CreatedAt'));
            $table->setHeaderContents($row, 4, get_lang('Actions'));

            foreach ($auths as $auth) {
                $row++;

                $actions = [
                    Display::url(
                        Display::return_icon('edit.png', get_lang('Edit')),
                        $pageBaseUrl.'?action=edit&id='.$auth->getId()
                    ),
                    Display::url(
                        Display::return_icon('delete.png', get_lang('Edit')),
                        $pageBaseUrl.'?action=delete&id='.$auth->getId()
                    ),
                ];

                $table->setCellContents($row, 0, $auth->getUsername());
                $table->setCellContents($row, 1, $auth->getPassword());
                $table->setCellContents($row, 2, $auth->isEnabled() ? get_lang('Yes') : get_lang('No'));
                $table->setCellContents($row, 3, api_convert_and_format_date($auth->getCreatedAt()));
                $table->setCellContents($row, 4, implode(PHP_EOL, $actions));
            }

            $pageContent = $table->toHtml();
        }
        break;
}

$interbreadcrumb[] = [
    'name' => get_lang('Administration'),
    'url' => api_get_path(WEB_CODE_PATH).'admin/index.php',
];

$view = new Template($plugin->get_title());
$view->assign('actions', Display::toolbarAction('xapi_actions', [$pageActions]));
$view->assign('content', $pageContent);
$view->display_one_col_template();
