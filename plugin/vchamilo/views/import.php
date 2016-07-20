<?php
/* For licensing terms, see /license.txt */

$cidReset = true;
require_once '../../../main/inc/global.inc.php';

// Security
api_protect_admin_script();
Virtual::checkSettings();

$plugin = VChamiloPlugin::create();
$form = new FormValidator('import', 'post', api_get_self());

// Database host.
$form->addText('db_host', $plugin->get_lang('dbhost'), array('id' => 'id_vdbhost'));
$form->applyFilter('db_host', 'trim');

// Database login.
$form->addText('db_user', $plugin->get_lang('dbuser'), array('id' => 'id_vdbuser'));
$form->applyFilter('db_user', 'trim');

// Database password.
$form->addElement(
    'password',
    'db_password',
    $plugin->get_lang('dbpassword'),
    array('id' => 'id_vdbpassword')
);

// Database name.
$form->addText('main_database', [$plugin->get_lang('maindatabase'), $plugin->get_lang('DatabaseDescription')]);

$form->addText(
    'path',
    [
        $plugin->get_lang('ConfigurationPath'),
        get_lang('Example').': /var/www/site/app/config/configuration.php'
    ],
    true,
    array('id' => 'id_vdbhost')
);

$form->addButtonSave($plugin->get_lang('savechanges'), 'submitbutton');
$content = $form->returnForm();

if ($form->validate()) {
    $values = $form->getSubmitValues();
    $file = $values['path'];

    if (file_exists($file)) {
        // @todo
        $data = file_get_contents($file).' return $_configuration;';
        $data = str_replace('$_configuration', '$configurationFile', $data);
        $temp = api_get_path(SYS_ARCHIVE_PATH).''.uniqid('file_').'.php';
        file_put_contents($temp, $data);
        if (file_exists($temp)) {

            $currentHost = api_get_configuration_value('db_host');
            $currentDatabase = api_get_configuration_value('main_database');
            $currentUser = api_get_configuration_value('db_user');
            $currentPassword = api_get_configuration_value('db_password');

            if ($values['main_database'] !== $currentDatabase &&
                $values['db_user'] !== $currentUser &&
                $values['db_password'] !== $currentPassword
            ) {

            } else {
                Display::addFlash(
                    Display::return_message(
                        $plugin->get_lang('DatabaseAccessShouldBeDifferentThanMasterChamilo')
                    )
                );
            }

            $configuration = include $temp;

            $vchamilo = new stdClass();
            $vchamilo->main_database = $configuration['main_database'];
            $vchamilo->db_user = $configuration['db_user'];
            $vchamilo->db_password = $configuration['db_password'];
            $vchamilo->db_host = $configuration['db_host'];

            $vchamilo->import_to_main_database = $values['main_database'];
            $vchamilo->import_to_db_user = $values['db_user'];
            $vchamilo->import_to_db_password = $values['db_password'];
            $vchamilo->import_to_db_host = $values['db_host'];
            $vchamilo->import = true;

            $plugin->addInstance($vchamilo);
        }
    }
}

$tpl = new Template(get_lang('VChamilo'), true, true, false, true, false);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
