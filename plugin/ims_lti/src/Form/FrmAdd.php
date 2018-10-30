<?php
/* For licensing terms, see /license.txt */

/**
 * Class FrmAdd.
 */
class FrmAdd extends FormValidator
{
    /**
     * FrmAdd constructor.
     *
     * @param string $name
     * @param array  $attributes
     */
    public function __construct(
        $name,
        $attributes = []
    ) {
        parent::__construct($name, 'POST', '', '', $attributes, self::LAYOUT_HORIZONTAL, true);
    }

    /**
     * Build the form
     */
    public function build()
    {
        $plugin = ImsLtiPlugin::create();

        $this->addHeader($plugin->get_lang('ToolSettings'));
        $this->addText('name', get_lang('Name'));
        $this->addText('base_url', $plugin->get_lang('LaunchUrl'));
        $this->addText('consumer_key', $plugin->get_lang('ConsumerKey'));
        $this->addText('shared_secret', $plugin->get_lang('SharedSecret'));
        $this->addTextarea('description', get_lang('Description'), ['rows' => 3]);
        $this->addButtonAdvancedSettings('lti_adv');
        $this->addHtml('<div id="lti_adv_options" style="display:none;">');
        $this->addTextarea(
            'custom_params',
            [$plugin->get_lang('CustomParams'), $plugin->get_lang('CustomParamsHelp')]
        );
        $this->addCheckBox('deep_linking', null, $plugin->get_lang('SupportDeepLinking'));
        $this->addHtml('</div>');
        $this->addButtonAdvancedSettings('lti_privacy', get_lang('Privacy'));
        $this->addHtml('<div id="lti_privacy_options" style="display:none;">');
        $this->addCheckBox('share_name', null, $plugin->get_lang('ShareLauncherName'));
        $this->addCheckBox('share_email', null, $plugin->get_lang('ShareLauncherEmail'));
        $this->addCheckBox('share_picture', null, $plugin->get_lang('ShareLauncherPicture'));
        $this->addHtml('</div>');
        $this->addButtonCreate($plugin->get_lang('AddExternalTool'));
    }
}
