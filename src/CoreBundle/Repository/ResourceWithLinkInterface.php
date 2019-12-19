<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Resource\AbstractResource;
use Symfony\Component\Routing\RouterInterface;

interface ResourceWithLinkInterface
{
    public function getLink(AbstractResource $resource, RouterInterface $router): string;
}
