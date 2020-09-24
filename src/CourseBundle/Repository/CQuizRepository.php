<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CoreBundle\Repository\ResourceWithLinkInterface;
use Chamilo\CourseBundle\Entity\CQuiz;
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

    public function deleteAllByCourse($course)
    {
        $qb = $this->getResourcesByCourse($course);
        $resources = $qb->getQuery()->getResult();

        /** @var CQuiz $quiz */
        foreach ($resources as $quiz) {
            $questions = $quiz->getQuestions();
            foreach ($questions as $question) {
                //$this->getEntityManager()->remove($question);
            }
            $this->getEntityManager()->remove($quiz);
        }
        $this->getEntityManager()->flush();
    }
}
