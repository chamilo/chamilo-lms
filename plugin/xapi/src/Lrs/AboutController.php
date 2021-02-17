<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Lrs;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AboutController.
 *
 * @package Chamilo\PluginBundle\XApi\Lrs
 */
class AboutController extends BaseController
{
    public function get(): Response
    {
        $json = [
            'version' => [
                '1.0.3',
                '1.0.2',
                '1.0.1',
                '1.0.0',
            ],
        ];

        return JsonResponse::create($json);
    }
}
