<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Resolver;

use Chamilo\ApiBundle\GraphQL\ApiGraphQLTrait;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CourseBundle\Entity\CTool;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class ToolDescriptionResolver.
 *
 * @package Chamilo\ApiBundle\GraphQL\Resolver
 */
class ToolDescriptionResolver implements ContainerAwareInterface
{
    use ApiGraphQLTrait;

    /**
     * @param CTool        $tool
     * @param \ArrayObject $context
     *
     * @return array
     */
    public function getDescriptions(CTool $tool, \ArrayObject $context): array
    {
        /** @var Course $course */
        $course = $context->offsetGet('course');
        $cd = new \CourseDescription();
        $cd->set_course_id($course->getId());

        if ($context->offsetExists('session')) {
            /** @var Session $session */
            $session = $context->offsetGet('session');

            if ($session) {
                $cd->set_session_id($session->getId());
            }
        }

        $descriptions = $cd->get_description_data();

        if (empty($descriptions)) {
            return [];
        }

        $qb = $this->em->createQueryBuilder();
        $qb
            ->select('d')
            ->from('ChamiloCourseBundle:CCourseDescription', 'd')
            ->where(
                $qb->expr()->in('d.id', array_keys($descriptions['descriptions']))
            );

        return $qb->getQuery()->getResult();
    }
}
