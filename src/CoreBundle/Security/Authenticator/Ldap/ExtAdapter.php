<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authenticator\Ldap;

use Chamilo\CoreBundle\Helpers\AuthenticationConfigHelper;
use Symfony\Component\Ldap\Adapter\ExtLdap\Adapter;

class ExtAdapter extends Adapter
{
    public function __construct(
        private readonly AuthenticationConfigHelper $authConfigHelper,
    ) {
        $params = $this->authConfigHelper->getLdapConfig();

        $config = [
            'connection_string' => $params['connection_string'],
            'options' => [
                'protocol_version' => $params['protocol_version'],
                'referrals' => $params['referrals'],
            ],
        ];

        parent::__construct($config);
    }
}
