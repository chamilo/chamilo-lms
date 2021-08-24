<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\LtiProvider\Form;

use Chamilo\PluginBundle\Entity\LtiProvider\Platform;
use Exception;
use FormValidator;
use LtiProviderPlugin;

/**
 * Class FrmEdit.
 */
class FrmEdit extends FormValidator
{
    /**
     * @var Platform|null
     */
    private $platform;

    /**
     * FrmEdit constructor.
     */
    public function __construct(
        string $name,
        array $attributes = [],
        Platform $platform = null
    ) {
        parent::__construct($name, 'POST', '', '', $attributes, self::LAYOUT_HORIZONTAL);

        $this->platform = $platform;
    }

    /**
     * Build the form.
     */
    public function build()
    {
        $plugin = LtiProviderPlugin::create();
        $this->addHeader($plugin->get_lang('ConnectionDetails'));

        $this->addText('issuer', $plugin->get_lang('PlatformName'));
        $this->addUrl('auth_login_url', $plugin->get_lang('AuthLoginUrl'));
        $this->addUrl('auth_token_url', $plugin->get_lang('AuthTokenUrl'));
        $this->addUrl('key_set_url', $plugin->get_lang('KeySetUrl'));
        $this->addText('client_id', $plugin->get_lang('ClientId'));
        $this->addText('deployment_id', $plugin->get_lang('DeploymentId'));
        $this->addText('kid', $plugin->get_lang('KeyId'), false);
        $this->addElement('html', $plugin->getQuizzesSelect($this->platform->getIssuer()));

        $this->addButtonCreate($plugin->get_lang('EditPlatform'));
        $this->addHidden('id', $this->platform->getId());
        $this->addHidden('action', 'edit');
        $this->applyFilter('__ALL__', 'trim');
    }

    /**
     * @throws Exception
     */
    public function setDefaultValues(): void
    {
        $defaults = [];
        $defaults['issuer'] = $this->platform->getIssuer();
        $defaults['auth_login_url'] = $this->platform->getAuthLoginUrl();
        $defaults['auth_token_url'] = $this->platform->getAuthTokenUrl();
        $defaults['key_set_url'] = $this->platform->getKeySetUrl();
        $defaults['client_id'] = $this->platform->getClientId();
        $defaults['deployment_id'] = $this->platform->getDeploymentId();
        $defaults['kid'] = $this->platform->getKid();
        $defaults['tool_provider'] = $this->platform->getToolProvider();

        $this->setDefaults($defaults);
    }
}
