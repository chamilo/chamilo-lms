<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Resource\ResourceInterface;
use Symfony\Component\Routing\RouterInterface;

interface ResourceWithLinkInterface
{
    public function getLink(ResourceInterface $resource, RouterInterface $router): string;
}
