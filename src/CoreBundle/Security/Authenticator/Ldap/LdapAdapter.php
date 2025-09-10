<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authenticator\Ldap;

use Chamilo\CoreBundle\Helpers\AuthenticationConfigHelper;
use Symfony\Component\Ldap\Adapter\ExtLdap\Adapter;

class LdapAdapter extends Adapter
{
    public function __construct(
        protected readonly AuthenticationConfigHelper $configHelper
    ) {
        $config = [
            'connection_string' => '',
            'options' => [
                'protocol_version' => 3,
                'referrals' => false,
            ],
        ];

        parent::__construct($config);
    }
}