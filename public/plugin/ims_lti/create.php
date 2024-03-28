<?php
/* For license terms, see /license.txt */
use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;
use Chamilo\PluginBundle\ImsLti\Form\FrmAdd;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script();

$plugin = ImsLtiPlugin::create();

$em = Database::getManager();

$form = new FrmAdd('ism_lti_create_tool');
$form->build();

if ($form->validate()) {
    $formValues = $form->exportValues();

    $externalTool = new ImsLtiTool();
    $externalTool
        ->setName($formValues['name'])
        ->setDescription(
            empty($formValues['description']) ? null : $formValues['description']
        )
        ->setCustomParams(
            empty($formValues['custom_params']) ? null : $formValues['custom_params']
        )
        ->setDocumenTarget($formValues['document_target'])
        ->setCourse(null)
        ->setActiveDeepLinking(
            isset($formValues['deep_linking'])
        )
        ->setPrivacy(
            isset($formValues['share_name']),
            isset($formValues['share_email']),
            isset($formValues['share_picture'])
        );

    if (!empty($formValues['replacement_user_id'])) {
        $externalTool->setReplacementForUserId($formValues['replacement_user_id']);
    }

    if (ImsLti::V_1P3 === $formValues['version']) {
        $externalTool
            ->setVersion(ImsLti::V_1P3)
            ->setLaunchUrl($formValues['launch_url'])
            ->setClientId(
                ImsLti::generateClientId()
            )
            ->setLoginUrl($formValues['login_url'])
            ->setRedirectUrl($formValues['redirect_url'])
            ->setAdvantageServices(
                [
                    'ags' => $formValues['1p3_ags'],
                ]
            )
            ->setJwksUrl($formValues['jwks_url'])
            ->publicKey = $formValues['public_key'];
    } else {
        $externalTool->setVersion(ImsLti::V_1P1);

        if (empty($formValues['consumer_key']) && empty($formValues['shared_secret'])) {
            try {
                $launchUrl = $plugin->getLaunchUrlFromCartridge($formValues['launch_url']);
            } catch (Exception $e) {
                Display::addFlash(
                    Display::return_message($e->getMessage(), 'error')
                );

                header('Location: '.api_get_path(WEB_PLUGIN_PATH).'ims_lti/admin.php');
                exit;
            }

            $externalTool->setLaunchUrl($launchUrl);
        } else {
            $externalTool
                ->setLaunchUrl($formValues['launch_url'])
                ->setConsumerKey(
                    empty($formValues['consumer_key']) ? null : $formValues['consumer_key']
                )
                ->setSharedSecret(
                    empty($formValues['shared_secret']) ? null : $formValues['shared_secret']
                );
        }
    }

    $em->persist($externalTool);
    $em->flush();

    Display::addFlash(
        Display::return_message($plugin->get_lang('ToolAdded'), 'success')
    );

    header('Location: '.api_get_path(WEB_PLUGIN_PATH).'ims_lti/admin.php');
    exit;
}

$form->setDefaultValues();

$interbreadcrumb[] = ['url' => api_get_path(WEB_CODE_PATH).'admin/index.php', 'name' => get_lang('PlatformAdmin')];
$interbreadcrumb[] = ['url' => api_get_path(WEB_PLUGIN_PATH).'ims_lti/admin.php', 'name' => $plugin->get_title()];

$pageTitle = $plugin->get_lang('AddExternalTool');

$template = new Template($pageTitle);
$template->assign('form', $form->returnForm());

$content = $template->fetch('ims_lti/view/add.tpl');

$template->assign('header', $pageTitle);
$template->assign('content', $content);
$template->display_one_col_template();
