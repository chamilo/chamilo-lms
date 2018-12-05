<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;

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

        $this->tool = $tool;
    }

    /**
     * Build the form.
     *
     * @param bool $globalMode
     *
     * @throws Exception
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

        if (null === $parent) {
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

        if (null === $parent ||
            (null !== $parent && !$parent->isActiveDeepLinking())
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
        $this->addButtonUpdate($plugin->get_lang('EditExternalTool'));
        $this->addHidden('id', $this->tool->getId());
        $this->addHidden('action', 'edit');
        $this->applyFilter('__ALL__', 'trim');
    }

    public function setDefaultValues()
    {
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
            ]
        );
    }
}
