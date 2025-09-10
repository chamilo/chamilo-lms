<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Ldap;

use Chamilo\CoreBundle\Helpers\AuthenticationConfigHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Routing\Attribute\Route;

class LdapController extends AbstractController
{
    public function __construct(
        AuthenticationConfigHelper $authenticationConfigHelper,
    ) {
    }

    #[Route('/connect/ldap', name: 'chamilo.ldap_start')]
    public function connect(): Response
    {
        $ldap = Ldap::create('ext_ldap');
    }
}