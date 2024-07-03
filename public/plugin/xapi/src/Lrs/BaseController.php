<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Lrs;

use Symfony\Component\HttpFoundation\Request;

/**
 * Class BaseController.
 */
abstract class BaseController
{
    /**
     * @var Request
     */
    protected $httpRequest;

    public function __construct(Request $httpRequest)
    {
        $this->httpRequest = $httpRequest;
    }
}
