<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class CQuizQuestionRepository.
 */
final class CQuizQuestionRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CQuizQuestion::class);
    }

    public function getHotSpotImageUrl(CQuizQuestion $resource): string
    {
        $params = [
            'mode' => 'view',
            'filter' => 'hotspot_question',
        ];

        return $this->getResourceFileUrl($resource, $params);
    }
}
