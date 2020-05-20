<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\ResourceInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Interface ResourceWithLinkInterface
 * Allows resources to connect with a custom URL.
 */
interface ResourceWithLinkInterface
{
    public function getLink(ResourceInterface $resource, RouterInterface $router): string;
}
