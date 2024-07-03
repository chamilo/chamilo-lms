<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Form;

use Category;
use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;
use Display;
use Exception;
use FormValidator;
use ImsLti;
use ImsLtiPlugin;
use LtiAssignmentGradesService;
use LtiNamesRoleProvisioningService;

/**
 * Class FrmAdd.
 */
class FrmEdit extends FormValidator
{
    /**
     * @var ImsLtiTool|null
     */
    private $tool;

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
        $course = $this->tool->getCourse();
        $parent = $this->tool->getParent();

        $this->addHeader($plugin->get_lang('ToolSettings'));

        if (null !== $course && $globalMode) {
            $this->addHtml(
                Display::return_message(
                    sprintf($plugin->get_lang('ToolAddedOnCourseX'), $course->getTitle()),
                    'normal',
                    false
                )
            );
        }

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

        if (null === $parent) {
            $this->addUrl('launch_url', $plugin->get_lang('LaunchUrl'), true);
            if ($this->tool->getVersion() === ImsLti::V_1P1) {
                $this->addText('consumer_key', $plugin->get_lang('ConsumerKey'), false);
                $this->addText('shared_secret', $plugin->get_lang('SharedSecret'), false);
            } elseif ($this->tool->getVersion() === ImsLti::V_1P3) {
                $this->addText('client_id', $plugin->get_lang('ClientId'), true);
                $this->freeze(['client_id']);
                if (!empty($this->tool->getJwksUrl())) {
                    $this->addUrl('jwks_url', $plugin->get_lang('PublicKeyset'));
                } else {
                    $this->addTextarea(
                        'public_key',
                        $plugin->get_lang('PublicKey'),
                        ['style' => 'font-family: monospace;', 'rows' => 5],
                        true
                    );
                }
                $this->addUrl('login_url', $plugin->get_lang('LoginUrl'));
                $this->addUrl('redirect_url', $plugin->get_lang('RedirectUrl'));
            }
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

        if (null === $parent
            || (null !== $parent && !$parent->isActiveDeepLinking())
        ) {
            $this->addCheckBox(
                'deep_linking',
                [null, $plugin->get_lang('SupportDeppLinkingHelp'), null],
                $plugin->get_lang('SupportDeepLinking')
            );
        }

        if (null === $parent && $this->tool->getVersion() === ImsLti::V_1P3) {
            $showAGS = false;

            if (api_get_course_int_id()) {
                $caterories = Category::load(null, null, api_get_course_id());

                if (!empty($caterories)) {
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

        if (!$parent) {
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
        $advServices = $this->tool->getAdvantageServices();

        $this->setDefaults(
            [
                'name' => $this->tool->getName(),
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
                'public_key' => $this->tool->publicKey,
                'jwks_url' => $this->tool->getJwksUrl(),
                'login_url' => $this->tool->getLoginUrl(),
                'redirect_url' => $this->tool->getRedirectUrl(),
                '1p3_ags' => $advServices['ags'],
                '1p3_nrps' => $advServices['nrps'],
                'document_target' => $this->tool->getDocumentTarget(),
                'replacement_user_id' => $this->tool->getReplacementForUserId(),
            ]
        );
    }
}
