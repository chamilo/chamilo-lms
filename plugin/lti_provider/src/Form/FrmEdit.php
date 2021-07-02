<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\LtiProvider\Platform;

/**
 * Class FrmAdd.
 */
class FrmEdit extends FormValidator
{
    /**
     * @var Platform|null
     */
    private $platform;

    /**
     * FrmAdd constructor.
     *
     * @param string          $name
     * @param array           $attributes
     * @param Platform|null   $platform
     */
    public function __construct(
        $name,
        $attributes = [],
        Platform $platform = null
    ) {
        parent::__construct($name, 'POST', '', '', $attributes, self::LAYOUT_HORIZONTAL, true);

        $this->platform = $platform;
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
        $plugin = LtiProviderPlugin::create();
        $this->addHeader($plugin->get_lang('ConnectionDetails'));

        $this->addText('issuer', $plugin->get_lang('PlatformName'));
        $this->addUrl('auth_login_url', $plugin->get_lang('AuthLoginUrl'));
        $this->addUrl('auth_token_url', $plugin->get_lang('AuthTokenUrl'));
        $this->addUrl('key_set_url', $plugin->get_lang('KeySetUrl'));
        $this->addText('client_id', $plugin->get_lang('ClientId'));
        $this->addText('deployment_id', $plugin->get_lang('DeploymentId'));
        $this->addText('kid', $plugin->get_lang('KeyId'));

        $this->addButtonCreate($plugin->get_lang('EditPlatform'));
        $this->addHidden('id', $this->platform->getId());
        $this->addHidden('action', 'edit');
        $this->applyFilter('__ALL__', 'trim');
    }

    /**
     * @throws Exception
     */
    public function setDefaultValues()
    {
        $defaults = [];
        $defaults['issuer'] =  $this->platform->getIssuer();
        $defaults['auth_login_url'] = $this->platform->getAuthLoginUrl();
        $defaults['auth_token_url'] = $this->platform->getAuthTokenUrl();
        $defaults['key_set_url'] = $this->platform->getKeySetUrl();
        $defaults['client_id'] = $this->platform->getClientId();
        $defaults['deployment_id'] = $this->platform->getDeploymentId();
        $defaults['kid'] = $this->platform->getKid();

        $this->setDefaults($defaults);
    }
}
