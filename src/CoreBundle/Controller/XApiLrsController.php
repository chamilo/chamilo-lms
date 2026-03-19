<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\PluginBundle\XApi\Lrs\LrsRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class XApiLrsController
{
    #[Route(
        path: '/plugin/XApi/lrs',
        name: 'chamilo_xapi_lrs_root',
        methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS']
    )]
    #[Route(
        path: '/plugin/XApi/lrs/{path}',
        name: 'chamilo_xapi_lrs_path',
        requirements: ['path' => '.+'],
        methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS']
    )]
    public function __invoke(Request $request): Response
    {
        return (new LrsRequest($request))->handle();
    }
}
