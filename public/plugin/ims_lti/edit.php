<?php
/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;
use Chamilo\PluginBundle\Form\FrmEdit;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script();

if (!isset($_REQUEST['id'])) {
    api_not_allowed(true);
}

$toolId = intval($_REQUEST['id']);

$plugin = ImsLtiPlugin::create();
$em = Database::getManager();

/** @var ImsLtiTool $tool */
$tool = $em->find('ChamiloPluginBundle:ImsLti\ImsLtiTool', $toolId);

if (!$tool) {
    Display::addFlash(
        Display::return_message($plugin->get_lang('NoTool'), 'error')
    );

    header('Location: '.api_get_path(WEB_PLUGIN_PATH).'ims_lti/admin.php');
    exit;
}

$form = new FrmEdit('ims_lti_edit_tool', [], $tool);
$form->build();

if ($form->validate()) {
    $formValues = $form->exportValues();

    $tool
        ->setName($formValues['name'])
        ->setDescription(
            empty($formValues['description']) ? null : $formValues['description']
        )
        ->setCustomParams(
            empty($formValues['custom_params']) ? null : $formValues['custom_params']
        )
        ->setDocumenTarget($formValues['document_target'])
        ->setPrivacy(
            !empty($formValues['share_name']),
            !empty($formValues['share_email']),
            !empty($formValues['share_picture'])
        );

    if (null === $tool->getParent()) {
        $tool->setLaunchUrl($formValues['launch_url']);

        if ($tool->getVersion() === ImsLti::V_1P1) {
            $tool
                ->setConsumerKey(
                    empty($formValues['consumer_key']) ? null : $formValues['consumer_key']
                )
                ->setSharedSecret(
                    empty($formValues['shared_secret']) ? null : $formValues['shared_secret']
                );
        } elseif ($tool->getVersion() === ImsLti::V_1P3) {
            $tool
                ->setLoginUrl($formValues['login_url'])
                ->setRedirectUrl($formValues['redirect_url'])
                ->setAdvantageServices(
                    [
                        'ags' => $formValues['1p3_ags'] ?? LtiAssignmentGradesService::AGS_NONE,
                        'nrps' => $formValues['1p3_nrps'],
                    ]
                )
                ->setJwksUrl($formValues['jwks_url'])
                ->publicKey = $formValues['public_key'];
        }

        if (!empty($formValues['replacement_user_id'])) {
            $tool->setReplacementForUserId($formValues['replacement_user_id']);
        }
    }

    if (null === $tool->getParent() ||
        (null !== $tool->getParent() && !$tool->getParent()->isActiveDeepLinking())
    ) {
        $tool->setActiveDeepLinking(!empty($formValues['deep_linking']));
    }

    if (null == $tool->getParent()) {
        /** @var ImsLtiTool $child */
        foreach ($tool->getChildren() as $child) {
            $child
                ->setLaunchUrl($tool->getLaunchUrl())
                ->setLoginUrl($tool->getLoginUrl())
                ->setRedirectUrl($tool->getRedirectUrl())
                ->setAdvantageServices(
                    $tool->getAdvantageServices()
                )
                ->setDocumenTarget($tool->getDocumentTarget())
                ->publicKey = $tool->publicKey;

            $em->persist($child);

            $courseTool = $plugin->findCourseToolByLink(
                $child->getCourse(),
                $child
            );

            $plugin->updateCourseTool($courseTool, $child);
        }
    } else {
        $courseTool = $plugin->findCourseToolByLink(
            $tool->getCourse(),
            $tool
        );

        $plugin->updateCourseTool($courseTool, $tool);
    }

    $em->persist($tool);
    $em->flush();

    Display::addFlash(
        Display::return_message($plugin->get_lang('ToolEdited'), 'success')
    );

    header('Location: '.api_get_path(WEB_PLUGIN_PATH).'ims_lti/admin.php');
    exit;
} else {
    $form->setDefaultValues();
}

$interbreadcrumb[] = ['url' => api_get_path(WEB_CODE_PATH).'admin/index.php', 'name' => get_lang('PlatformAdmin')];
$interbreadcrumb[] = ['url' => api_get_path(WEB_PLUGIN_PATH).'ims_lti/admin.php', 'name' => $plugin->get_title()];

$template = new Template($plugin->get_lang('EditExternalTool'));
$template->assign('form', $form->returnForm());

$content = $template->fetch('ims_lti/view/add.tpl');

$template->assign('header', $plugin->get_title());
$template->assign('content', $content);
$template->display_one_col_template();
