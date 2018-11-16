<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;

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
     * FrmAdd constructor.
     *
     * @param string          $name
     * @param array           $attributes
     * @param ImsLtiTool|null $tool
     */
    public function __construct(
        $name,
        $attributes = [],
        ImsLtiTool $tool = null
    ) {
        parent::__construct($name, 'POST', '', '', $attributes, self::LAYOUT_HORIZONTAL, true);

        $this->baseTool = $tool;
    }

    /**
     * Build the form
     */
    public function build()
    {
        $plugin = ImsLtiPlugin::create();

        $this->addHeader($plugin->get_lang('ToolSettings'));
        $this->addText('name', get_lang('Name'));
        $this->addTextarea('description', get_lang('Description'));

        if (null === $this->baseTool) {
            $this->addUrl('launch_url', $plugin->get_lang('LaunchUrl'), true);
            $this->addText('consumer_key', $plugin->get_lang('ConsumerKey'), false);
            $this->addText('shared_secret', $plugin->get_lang('SharedSecret'), false);
        }

        $this->addButtonAdvancedSettings('lti_adv');
        $this->addHtml('<div id="lti_adv_options" style="display:none;">');
        $this->addTextarea(
            'custom_params',
            [$plugin->get_lang('CustomParams'), $plugin->get_lang('CustomParamsHelp')]
        );

        if (null === $this->baseTool ||
            ($this->baseTool && !$this->baseTool->isActiveDeepLinking())
        ) {
            $this->addCheckBox(
                'deep_linking',
                [null, $plugin->get_lang('SupportDeppLinkingHelp'), null],
                $plugin->get_lang('SupportDeepLinking')
            );
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
        if (null !== $this->baseTool) {
            $this->setDefaults(
                [
                    'name' => $this->baseTool->getName(),
                    'description' => $this->baseTool->getDescription(),
                    'custom_params' => $this->baseTool->getCustomParams(),
                    'share_name' => $this->baseTool->isSharingName(),
                    'share_email' => $this->baseTool->isSharingEmail(),
                    'share_picture' => $this->baseTool->isSharingPicture(),
                ]
            );
        }
    }
}
