<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Lrs;

use Symfony\Component\HttpFoundation\Request;

/**
 * Base controller for xAPI legacy LRS endpoints.
 */
abstract class BaseController
{
    protected Request $httpRequest;

    public function __construct(Request $httpRequest)
    {
        $this->httpRequest = $httpRequest;
    }
}
