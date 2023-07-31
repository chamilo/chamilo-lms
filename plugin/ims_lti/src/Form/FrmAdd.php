<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\ImsLti\Form;

use Category;
use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;
use Display;
use FormValidator;
use ImsLti;
use ImsLtiPlugin;
use LtiAssignmentGradesService;
use LtiNamesRoleProvisioningService;

/**
 * Class FrmAdd.
 */
class FrmAdd extends FormValidator
{
    /**
     * @var ImsLtiTool|null
     */
    private $baseTool;
    /**
     * @var bool
     */
    private $toolIsV1p3;

    /**
     * FrmAdd constructor.
     *
     * @param string $name
     * @param array  $attributes
     */
    public function __construct(
        $name,
        $attributes = [],
        ImsLtiTool $tool = null
    ) {
        parent::__construct($name, 'POST', '', '', $attributes, self::LAYOUT_HORIZONTAL, true);

        $this->baseTool = $tool;
        $this->toolIsV1p3 = $this->baseTool
            && !empty($this->baseTool->publicKey)
            && !empty($this->baseTool->getClientId())
            && !empty($this->baseTool->getLoginUrl())
            && !empty($this->baseTool->getRedirectUrl());
    }

    /**
     * Build the form.
     */
    public function build()
    {
        $plugin = ImsLtiPlugin::create();

        $this->addHeader($plugin->get_lang('ToolSettings'));
        $this->addText('name', get_lang('Name'));
        $this->addTextarea('description', get_lang('Description'));

        if (null === $this->baseTool) {
            $this->addUrl('launch_url', $plugin->get_lang('LaunchUrl'), true);
            $this->addRadio(
                'version',
                $plugin->get_lang('LtiVersion'),
                [
                    ImsLti::V_1P1 => 'LTI 1.0 / 1.1',
                    ImsLti::V_1P3 => 'LTI 1.3.0',
                ]
            );
            $this->addHtml('<div class="'.ImsLti::V_1P1.'" style="display: none;">');
            $this->addText('consumer_key', $plugin->get_lang('ConsumerKey'), false);
            $this->addText('shared_secret', $plugin->get_lang('SharedSecret'), false);
            $this->addHtml('</div>');
            $this->addHtml('<div class="'.ImsLti::V_1P3.'" style="display: block;">');
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
                ['style' => 'font-family: monospace;', 'rows' => 5]
            );
            $this->addHtml('</div>');
            $this->addUrl('login_url', $plugin->get_lang('LoginUrl'), false);
            $this->addUrl('redirect_url', $plugin->get_lang('RedirectUrl'), false);
            $this->addHtml('</div>');
        }

        $this->addButtonAdvancedSettings('lti_adv');
        $this->addHtml('<div id="lti_adv_options" style="display:none;">');
        $this->addTextarea(
            'custom_params',
            [$plugin->get_lang('CustomParams'), $plugin->get_lang('CustomParamsHelp')]
        );
        $this->addSelect(
            'document_target',
            get_lang('LinkTarget'),
            ['iframe' => 'iframe', 'window' => 'window']
        );

        if (null === $this->baseTool
            || ($this->baseTool && !$this->baseTool->isActiveDeepLinking())
        ) {
            $this->addCheckBox(
                'deep_linking',
                [null, $plugin->get_lang('SupportDeppLinkingHelp'), null],
                $plugin->get_lang('SupportDeepLinking')
            );
        }

        $showAGS = false;

        if (api_get_course_int_id()) {
            $caterories = Category::load(null, null, api_get_course_id());

            if (!empty($caterories)) {
                $showAGS = true;
            }
        } else {
            $showAGS = true;
        }

        $this->addHtml('<div class="'.ImsLti::V_1P3.'" style="display: none;">');

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
        $this->addHtml('</div>');

        if (!$this->baseTool) {
            $this->addText(
                'replacement_user_id',
                [
                    $plugin->get_lang('ReplacementUserId'),
                    $plugin->get_lang('ReplacementUserIdHelp'),
                ],
                false
            );
            $this->applyFilter('replacement_user_id', 'trim');
        }

        $this->addHtml('</div>');
        $this->addButtonAdvancedSettings('lti_privacy', get_lang('Privacy'));
        $this->addHtml('<div id="lti_privacy_options" style="display:none;">');
        $this->addCheckBox('share_name', null, $plugin->get_lang('ShareLauncherName'));
        $this->addCheckBox('share_email', null, $plugin->get_lang('ShareLauncherEmail'));
        $this->addCheckBox('share_picture', null, $plugin->get_lang('ShareLauncherPicture'));
        $this->addHtml('</div>');
        $this->addButtonCreate($plugin->get_lang('AddExternalTool'));
        $this->applyFilter('__ALL__', 'trim');
    }

    public function setDefaultValues()
    {
        $defaults = [];
        $defaults['version'] = ImsLti::V_1P3;
        $defaults['public_key_type'] = ImsLti::LTI_JWK_KEYSET;

        if ($this->baseTool) {
            $defaults['name'] = $this->baseTool->getName();
            $defaults['description'] = $this->baseTool->getDescription();
            $defaults['custom_params'] = $this->baseTool->getCustomParams();
            $defaults['document_target'] = $this->baseTool->getDocumentTarget();
            $defaults['share_name'] = $this->baseTool->isSharingName();
            $defaults['share_email'] = $this->baseTool->isSharingEmail();
            $defaults['share_picture'] = $this->baseTool->isSharingPicture();
            $defaults['public_key'] = $this->baseTool->publicKey;
            $defaults['login_url'] = $this->baseTool->getLoginUrl();
            $defaults['redirect_url'] = $this->baseTool->getRedirectUrl();

            if ($this->toolIsV1p3) {
                $advServices = $this->baseTool->getAdvantageServices();

                $defaults['1p3_ags'] = $advServices['ags'];
                $defaults['1p3_nrps'] = $advServices['nrps'];
            }
        }

        $this->setDefaults($defaults);
    }

    public function returnForm(): string
    {
        $js = "<script>
                \$(function () {
                    \$('[name=\"version\"]').on('change', function () {
                        $('.".ImsLti::V_1P1.", .".ImsLti::V_1P3."').hide();

                        $('.' + this.value).show();
                    })
                    \$('[name=\"public_key_type\"]').on('change', function () {
                        $('.".ImsLti::LTI_JWK_KEYSET.", .".ImsLti::LTI_RSA_KEY."').hide();
                        $('[name=\"public_key\"], [name=\"jwks_url\"]').val('');

                        $('.' + this.value).show();
                    })
                });
            </script>";

        return $js.parent::returnForm(); // TODO: Change the autogenerated stub
    }
}
