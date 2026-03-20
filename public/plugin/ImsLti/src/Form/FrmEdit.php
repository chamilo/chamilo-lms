<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Form;

use Category;
use Chamilo\LtiBundle\Entity\ExternalTool;
use Display;
use Exception;
use FormValidator;
use ImsLti;
use ImsLtiPlugin;
use LtiAssignmentGradesService;
use LtiNamesRoleProvisioningService;

/**
 * Edit form for an IMS/LTI external tool.
 */
class FrmEdit extends FormValidator
{
    private ?ExternalTool $tool;

    /**
     * @param string $name
     * @param array $attributes
     */
    public function __construct(
        $name,
        $attributes = [],
        ?ExternalTool $tool = null
    ) {
        parent::__construct($name, 'POST', '', '', $attributes, self::LAYOUT_HORIZONTAL, true);

        $this->tool = $tool;
    }

    /**
     * Build the form.
     *
     * @param bool $globalMode
     */
    public function build($globalMode = true)
    {
        $plugin = ImsLtiPlugin::create();

        $this->addHeader($plugin->get_lang('ToolSettings'));

        $this->addHtml(
            Display::return_message(
                $plugin->get_lang('ChangesWillApplyToAllAssignedCourses'),
                'normal',
                false
            )
        );

        $this->addText('name', get_lang('Name'));
        $this->addTextarea('description', get_lang('Description'));

        $this->addRadio(
            'version',
            $plugin->get_lang('LtiVersion'),
            [
                ImsLti::V_1P1 => 'LTI 1.0 / 1.1',
                ImsLti::V_1P3 => 'LTI 1.3.0',
            ]
        );
        $this->freeze(['version']);

        $this->addUrl('launch_url', $plugin->get_lang('LaunchUrl'), true);

        if ($this->tool->getVersion() === ImsLti::V_1P1) {
            $this->addText('consumer_key', $plugin->get_lang('ConsumerKey'), false);
            $this->addText('shared_secret', $plugin->get_lang('SharedSecret'), false);
        } elseif ($this->tool->getVersion() === ImsLti::V_1P3) {
            $this->addText('client_id', $plugin->get_lang('ClientId'), true);
            $this->freeze(['client_id']);

            $this->addRadio(
                'public_key_type',
                $plugin->get_lang('PublicKeyType'),
                [
                    ImsLti::LTI_JWK_KEYSET => $plugin->get_lang('KeySetUrl'),
                    ImsLti::LTI_RSA_KEY => $plugin->get_lang('RsaKey'),
                ]
            );

            $this->addHtml('<div class="'.ImsLti::LTI_JWK_KEYSET.'" style="display: block;">');
            $this->addUrl('jwks_url', $plugin->get_lang('PublicKeyset'), false);
            $this->addHtml('</div>');

            $this->addHtml('<div class="'.ImsLti::LTI_RSA_KEY.'" style="display: none;">');
            $this->addTextarea(
                'public_key',
                $plugin->get_lang('PublicKey'),
                ['style' => 'font-family: monospace;', 'rows' => 5],
                false
            );
            $this->addHtml('</div>');

            $this->addUrl('login_url', $plugin->get_lang('LoginUrl'));
            $this->addUrl('redirect_url', $plugin->get_lang('RedirectUrl'));
        }

        $this->addButtonAdvancedSettings('lti_adv');
        $this->addHtml('<div id="lti_adv_options" style="display:none;">');

        $this->addTextarea(
            'custom_params',
            [$plugin->get_lang('CustomParams'), $plugin->get_lang('CustomParamsHelp')]
        );

        $this->addSelect(
            'document_target',
            $plugin->get_lang('LinkTarget'),
            ['iframe' => 'iframe', 'window' => 'window']
        );

        $this->addCheckBox(
            'deep_linking',
            [null, $plugin->get_lang('SupportDeppLinkingHelp'), null],
            $plugin->get_lang('SupportDeepLinking')
        );

        if ($this->tool->getVersion() === ImsLti::V_1P3) {
            $showAGS = false;

            if (api_get_course_int_id()) {
                $categories = Category::load(null, null, api_get_course_id());

                if (!empty($categories)) {
                    $showAGS = true;
                }
            } else {
                $showAGS = true;
            }

            if ($showAGS) {
                $this->addRadio(
                    '1p3_ags',
                    $plugin->get_lang('AssigmentAndGradesService'),
                    [
                        LtiAssignmentGradesService::AGS_NONE => $plugin->get_lang('DontUseService'),
                        LtiAssignmentGradesService::AGS_SIMPLE => $plugin->get_lang('AGServiceSimple'),
                        LtiAssignmentGradesService::AGS_FULL => $plugin->get_lang('AGServiceFull'),
                    ]
                );
            } else {
                $gradebookUrl = api_get_path(WEB_CODE_PATH).'gradebook/index.php?'.api_get_cidreq();

                $this->addLabel(
                    $plugin->get_lang('AssigmentAndGradesService'),
                    sprintf(
                        $plugin->get_lang('YouNeedCreateTheGradebokInCourseFirst'),
                        Display::url($gradebookUrl, $gradebookUrl)
                    )
                );
            }

            $this->addRadio(
                '1p3_nrps',
                $plugin->get_lang('NamesAndRoleProvisioningService'),
                [
                    LtiNamesRoleProvisioningService::NRPS_NONE => $plugin->get_lang('DontUseService'),
                    LtiNamesRoleProvisioningService::NRPS_CONTEXT_MEMBERSHIP => $plugin->get_lang('UseService'),
                ]
            );
        }

        $this->addText(
            'replacement_user_id',
            [
                $plugin->get_lang('ReplacementUserId'),
                $plugin->get_lang('ReplacementUserIdHelp'),
            ],
            false
        );
        $this->applyFilter('replacement_user_id', 'trim');

        $this->addHtml('</div>');

        $this->addButtonAdvancedSettings('lti_privacy', get_lang('Privacy'));
        $this->addHtml('<div id="lti_privacy_options" style="display:none;">');
        $this->addCheckBox('share_name', null, $plugin->get_lang('ShareLauncherName'));
        $this->addCheckBox('share_email', null, $plugin->get_lang('ShareLauncherEmail'));
        $this->addCheckBox('share_picture', null, $plugin->get_lang('ShareLauncherPicture'));
        $this->addHtml('</div>');

        $this->addButtonUpdate($plugin->get_lang('EditExternalTool'));
        $this->addHidden('id', $this->tool->getId());
        $this->addHidden('action', 'edit');

        $this->applyFilter('__ALL__', 'trim');
    }

    /**
     * @throws Exception
     */
    public function setDefaultValues()
    {
        $advServices = $this->tool->getAdvantageServices() ?? [];
        $hasJwksUrl = '' !== trim((string) $this->tool->getJwksUrl());
        $hasPublicKey = '' !== trim((string) $this->tool->publicKey);
        $defaultPublicKeyType = $hasJwksUrl || !$hasPublicKey
            ? ImsLti::LTI_JWK_KEYSET
            : ImsLti::LTI_RSA_KEY;

        $this->setDefaults(
            [
                'name' => $this->tool->getTitle(),
                'description' => $this->tool->getDescription(),
                'launch_url' => $this->tool->getLaunchUrl(),
                'consumer_key' => $this->tool->getConsumerKey(),
                'shared_secret' => $this->tool->getSharedSecret(),
                'custom_params' => $this->tool->getCustomParams(),
                'deep_linking' => $this->tool->isActiveDeepLinking(),
                'share_name' => $this->tool->isSharingName(),
                'share_email' => $this->tool->isSharingEmail(),
                'share_picture' => $this->tool->isSharingPicture(),
                'version' => $this->tool->getVersion(),
                'client_id' => $this->tool->getClientId(),
                'public_key_type' => $defaultPublicKeyType,
                'public_key' => $this->tool->publicKey,
                'jwks_url' => $this->tool->getJwksUrl(),
                'login_url' => $this->tool->getLoginUrl(),
                'redirect_url' => $this->tool->getRedirectUrl(),
                '1p3_ags' => $advServices['ags'] ?? LtiAssignmentGradesService::AGS_NONE,
                '1p3_nrps' => $advServices['nrps'] ?? LtiNamesRoleProvisioningService::NRPS_NONE,
                'document_target' => $this->tool->getDocumentTarget(),
                'replacement_user_id' => $this->tool->getReplacementForUserId(),
            ]
        );
    }

    public function returnForm(): string
    {
        $js = "<script>
            \$(function () {
                function togglePublicKeyType() {
                    var selectedValue = \$('[name=\"public_key_type\"]:checked').val();

                    if (!selectedValue) {
                        selectedValue = '".ImsLti::LTI_JWK_KEYSET."';
                        \$('[name=\"public_key_type\"][value=\"' + selectedValue + '\"]').prop('checked', true);
                    }

                    $('.".ImsLti::LTI_JWK_KEYSET.", .".ImsLti::LTI_RSA_KEY."').hide();
                    $('.' + selectedValue).show();
                }

                \$('[name=\"public_key_type\"]').on('change', function () {
                    togglePublicKeyType();
                });

                togglePublicKeyType();
            });
        </script>";

        return $js.parent::returnForm();
    }
}
