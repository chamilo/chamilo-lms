<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_block_anonymous_users(false);

$plugin = ImsLtiPlugin::create();
$webPluginPath = api_get_path(WEB_PLUGIN_PATH).'ims_lti/';

$request = Request::createFromGlobals();
$response = new Response();

$em = Database::getManager();

try {
    if ($plugin->get('enabled') !== 'true') {
        throw new Exception(get_lang('Forbidden'));
    }

    /** @var ImsLtiTool $tool */
    $tool = $em->find('ChamiloPluginBundle:ImsLti\ImsLtiTool', $request->query->get('id'));

    if (!$tool) {
        throw new Exception($plugin->get_lang('NoTool'));
    }

    $html = '<div class="row">'
        .'<div class="col-xs-5 text-right"><strong>'.$plugin->get_lang('PlatformId').'</strong></div>'
        .'<div class="col-xs-7">'.ImsLtiPlugin::getIssuerUrl().'</div>'
        .'</div>'
        .'<div class="row">'
        .'<div class="col-xs-5 text-right"><strong>'.$plugin->get_lang('DeploymentId').'</strong></div>'
        .'<div class="col-xs-7">'.($tool->getParent() ? $tool->getParent()->getId() : $tool->getId()).'</div>'
        .'</div>'
        .'<div class="row">'
        .'<div class="col-xs-5 text-right"><strong>'.$plugin->get_lang('ClientId').'</strong></div>'
        .'<div class="col-xs-7">'.$tool->getClientId().'</div>'
        .'</div>'
        .'<div class="row">'
        .'<div class="col-xs-5 text-right"><strong>'.$plugin->get_lang('AuthUrl').'</strong></div>'
        .'<div class="col-xs-7">'.$webPluginPath.'auth.php</div>'
        .'</div>'
        .'<div class="row">'
        .'<div class="col-xs-5 text-right"><strong>'.$plugin->get_lang('TokenUrl').'</strong></div>'
        .'<div class="col-xs-7">'.$webPluginPath.'token.php</div>'
        .'</div>'
        .'<div class="row">'
        .'<div class="col-xs-5 text-right"><strong>'.$plugin->get_lang('KeySetUrl').'</strong></div>'
        .'<div class="col-xs-7">'.$webPluginPath.'jwks.php</div>'
        .'</div>';

    $response->setContent($html);
} catch (Exception $exception) {
    $response->setContent(
        Display::return_message($exception->getMessage(), 'error')
    );
}

$response->send();
