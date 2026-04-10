<?php
/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CShortcut;
use Chamilo\CourseBundle\Repository\CShortcutRepository;
use Chamilo\LtiBundle\Entity\ExternalTool;
use Chamilo\PluginBundle\Form\FrmEdit;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script();

if (!isset($_REQUEST['id'])) {
    api_not_allowed(true);
}

$plugin = ImsLtiPlugin::create();

$pluginEntity = Container::getPluginRepository()->findOneByTitle('ImsLti');
$currentAccessUrl = Container::getAccessUrlUtil()->getCurrent();
$pluginConfiguration = $pluginEntity?->getConfigurationsByAccessUrl($currentAccessUrl);

$isPluginEnabled = $pluginEntity
    && $pluginEntity->isInstalled()
    && $pluginConfiguration
    && $pluginConfiguration->isActive();

if (!$isPluginEnabled) {
    api_not_allowed(true);
}

$toolId = (int) $_REQUEST['id'];
$em = Database::getManager();

/** @var ExternalTool|null $tool */
$tool = $em->find(ExternalTool::class, $toolId);

if (!$tool) {
    Display::addFlash(
        Display::return_message($plugin->get_lang('NoTool'), 'error')
    );

    header('Location: '.api_get_path(WEB_PLUGIN_PATH).'ImsLti/admin.php');
    exit;
}

/** @var CShortcutRepository $shortcutRepository */
$shortcutRepository = $em->getRepository(CShortcut::class);

$form = new FrmEdit('ims_lti_edit_tool', [], $tool);
$form->build();

if ($form->validate()) {
    $formValues = $form->exportValues();

    $normalizeOptionalValue = static function ($value): string {
        return empty($value) ? '' : trim((string) $value);
    };

    $tool
        ->setTitle($formValues['name'])
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
        )
        ->setLaunchUrl($normalizeOptionalValue($formValues['launch_url'] ?? ''));

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
            ->setLoginUrl($normalizeOptionalValue($formValues['login_url'] ?? ''))
            ->setRedirectUrl($normalizeOptionalValue($formValues['redirect_url'] ?? ''))
            ->setAdvantageServices(
                [
                    'ags' => $formValues['1p3_ags'] ?? LtiAssignmentGradesService::AGS_NONE,
                    'nrps' => $formValues['1p3_nrps'] ?? LtiNamesRoleProvisioningService::NRPS_NONE,
                ]
            );

        $jwksUrl = $normalizeOptionalValue($formValues['jwks_url'] ?? '');
        $publicKey = empty($formValues['public_key'])
            ? null
            : trim((string) $formValues['public_key']);

        $publicKeyType = $formValues['public_key_type'] ?? ImsLti::LTI_JWK_KEYSET;

        /* Prefer JWKS when a value is provided, even if the radio button state is inconsistent. */
        if ('' !== $jwksUrl) {
            $publicKeyType = ImsLti::LTI_JWK_KEYSET;
        } elseif (!empty($publicKey)) {
            $publicKeyType = ImsLti::LTI_RSA_KEY;
        }

        if (ImsLti::LTI_JWK_KEYSET === $publicKeyType) {
            $tool->setJwksUrl($jwksUrl);
            $tool->publicKey = null;
        } else {
            $tool->setJwksUrl('');
            $tool->publicKey = $publicKey;
        }
    }

    $tool->setActiveDeepLinking(!empty($formValues['deep_linking']));

    if (isset($formValues['replacement_user_id'])) {
        $replacementUserId = trim((string) $formValues['replacement_user_id']);
        $tool->setReplacementForUserId($replacementUserId !== '' ? $replacementUserId : null);
    }

    $em->persist($tool);

    $shortcuts = $shortcutRepository->getShortcutsFromResource($tool);

    /** @var CShortcut $shortcut */
    foreach ($shortcuts as $shortcut) {
        $shortcut->setTitle($tool->getTitle());
        $shortcut->target = 'iframe' === $tool->getDocumentTarget() ? '_self' : '_blank';

        $em->persist($shortcut);
    }

    $em->flush();

    Display::addFlash(
        Display::return_message($plugin->get_lang('ToolEdited'), 'success')
    );

    header('Location: '.api_get_path(WEB_PLUGIN_PATH).'ImsLti/admin.php');
    exit;
}

$form->setDefaultValues();

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'admin/index.php',
    'name' => get_lang('PlatformAdmin'),
];
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_PLUGIN_PATH).'ImsLti/admin.php',
    'name' => $plugin->get_title(),
];

$pageTitle = $plugin->get_lang('EditExternalTool');

$template = new Template($pageTitle);
$template->assign('form', $form->returnForm());
$template->assign('page_title', $pageTitle);
$template->assign('page_description', 'Edit and update this external tool configuration.');
$template->assign('back_url', api_get_path(WEB_PLUGIN_PATH).'ImsLti/admin.php');
$template->assign('back_label', 'Back to tools');

$content = $template->fetch('ImsLti/view/add.tpl');

$template->assign('header', $pageTitle);
$template->assign('content', $content);
$template->display_one_col_template();
