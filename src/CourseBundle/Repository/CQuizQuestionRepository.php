<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CQuizQuestion;

/**
 * Class CQuizQuestionRepository.
 */
final class CQuizQuestionRepository extends ResourceRepository
{
    public function getHotSpotImageUrl(CQuizQuestion $resource): string
    {
        $params = [
            'mode' => 'show',
            'filter' => 'hotspot_question',
        ];

        return $this->getResourceFileUrl($resource, $params);
    }
}
