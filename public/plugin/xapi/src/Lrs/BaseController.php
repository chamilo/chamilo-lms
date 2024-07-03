<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Lrs;

use Symfony\Component\HttpFoundation\Request;

/**
 * Class BaseController.
 *
 * @package Chamilo\PluginBundle\XApi\Lrs
 */
abstract class BaseController
{
    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $httpRequest;

    /**
     * BaseController constructor.
     */
    public function __construct(Request $httpRequest)
    {
        $this->httpRequest = $httpRequest;
    }
}
