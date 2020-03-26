<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Block\BreadcrumbBlockService;
use Chamilo\CoreBundle\Component\Utils\Glide;
use Chamilo\CoreBundle\Repository\ResourceFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Each entity controller must extends this class.
 *
 * @abstract
 */
abstract class BaseController extends AbstractController
{
    protected $translator;

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services['translator'] = TranslatorInterface::class;
        $services['breadcrumb'] = BreadcrumbBlockService::class;
        $services['resource_factory'] = ResourceFactory::class;
        $services['glide'] = Glide::class;

        return $services;
    }
}
