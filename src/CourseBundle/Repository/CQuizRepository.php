<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CoreBundle\Repository\ResourceWithLinkInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class CQuizRepository.
 */
final class CQuizRepository extends ResourceRepository implements ResourceWithLinkInterface
{
    public function getLink(ResourceInterface $resource, RouterInterface $router, $extraParams = []): string
    {
        $params = ['name' => 'exercise/overview.php', 'exerciseId' => $resource->getResourceIdentifier()];
        if (!empty($extraParams)) {
            $params = array_merge($params, $extraParams);
        }

        return $router->generate('legacy_main', $params);
    }
}
