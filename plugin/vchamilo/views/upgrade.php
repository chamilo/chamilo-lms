<?php
/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

$interbreadcrumb[] = ['url' => 'manage.php', 'name' => get_lang('VChamilo')];

// Security
api_protect_admin_script();
Virtual::checkSettings();

$plugin = VChamiloPlugin::create();

$id = isset($_REQUEST['vid']) ? (int) $_REQUEST['vid'] : 0;

$instance = Virtual::getInstance($id);
$canBeUpgraded = Virtual::canBeUpgraded($instance);

$form = new FormValidator('upgrade', 'post', api_get_self().'?vid='.$id);
// Database host.
$form->addHeader(get_lang('Upgrade'));

$form->addText('root_web', $plugin->get_lang('rootweb'));
$form->addText('db_host', $plugin->get_lang('dbhost'));
$form->addText('db_user', $plugin->get_lang('dbuser'));
$form->addText('main_database', [$plugin->get_lang('maindatabase')]);

$form->setDefaults((array) $instance);
if ($canBeUpgraded) {
    $form->addLabel(get_lang('From'), $canBeUpgraded);
    $form->addLabel(get_lang('To'), api_get_setting('chamilo_database_version'));
    $form->addButtonSave(get_lang('Upgrade'));
} else {
    Display::addFlash(Display::return_message(get_lang('NothingToUpgrade')));
}

$form->freeze();
$content = $form->returnForm();

if ($form->validate() && $canBeUpgraded) {
    $values = $form->getSubmitValues();

    require_once api_get_path(SYS_CODE_PATH).'install/install.lib.php';

    $manager = Virtual::getConnectionFromInstance($instance, true);
    if ($manager) {
        ob_start();
        $result = migrateSwitch($canBeUpgraded, $manager, false);
        $data = ob_get_clean();
        if ($result) {
            Display::addFlash(Display::return_message(get_lang('Upgraded')));
        } else {
            Display::addFlash(Display::return_message(get_lang('Error')));
        }
        $content = $data;
    } else {
        Display::addFlash(Display::return_message(get_lang('Error')));
    }
}

$tpl = new Template(get_lang('Upgrade'), true, true, false, true, false);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
