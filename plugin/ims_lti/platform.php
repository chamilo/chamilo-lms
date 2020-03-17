<?php
/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Entity\ImsLti\Platform;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script();

$plugin = ImsLtiPlugin::create();

if ($plugin->get('enabled') !== 'true') {
    api_not_allowed(true);
}

/** @var Platform $platform */
$platform = Database::getManager()
    ->getRepository('ChamiloPluginBundle:ImsLti\Platform')
    ->findOneBy([]);

$table = new HTML_Table(['class' => 'table table-striped']);
$table->setHeaderContents(0, 0, $plugin->get_lang('KeyId'));
$table->setHeaderContents(0, 1, $plugin->get_lang('PublicKey'));
$table->setHeaderContents(0, 2, $plugin->get_lang('PrivateKey'));
$table->setCellContents(1, 0, $platform ? $platform->getKid() : '');
$table->setCellContents(1, 1, $platform ? nl2br($platform->publicKey) : '');
$table->setCellContents(1, 2, $platform ? nl2br($platform->getPrivateKey()) : '');
$table->updateCellAttributes(1, 1, ['style' => 'font-family: monospace; font-size: 10px;']);
$table->updateCellAttributes(1, 2, ['style' => 'font-family: monospace; font-size: 10px;']);

$interbreadcrumb[] = ['url' => api_get_path(WEB_CODE_PATH).'admin/index.php', 'name' => get_lang('PlatformAdmin')];
$interbreadcrumb[] = ['url' => api_get_path(WEB_PLUGIN_PATH).'ims_lti/admin.php', 'name' => $plugin->get_title()];

$template = new Template($plugin->get_lang('PlatformKeys'));
$template->assign('header', $plugin->get_lang('PlatformKeys'));
$template->assign('content', $table->toHtml());
$template->display_one_col_template();
