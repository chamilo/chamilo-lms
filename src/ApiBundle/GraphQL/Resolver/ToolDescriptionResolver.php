<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Resolver;

use Chamilo\ApiBundle\GraphQL\ApiGraphQLTrait;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CourseBundle\Entity\CTool;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class ToolDescriptionResolver
 *
 * @package Chamilo\ApiBundle\GraphQL\Resolver
 */
class ToolDescriptionResolver implements ResolverInterface, ContainerAwareInterface
{
    use ApiGraphQLTrait;

    /**
     * @param CTool        $tool
     * @param Argument     $args
     * @param ResolveInfo  $info
     * @param \ArrayObject $context
     *
     * @return mixed
     */
    public function __invoke(CTool $tool, Argument $args, ResolveInfo $info, \ArrayObject $context)
    {
        $method = 'resolve'.ucfirst($info->fieldName);

        if (method_exists($this, $method)) {
            return $this->$method($tool, $args, $context);
        }

        $method = 'get'.ucfirst($info->fieldName);

        if (method_exists($tool, $method)) {
            return $tool->$method();
        }

        return null;
    }

    /**
     * @param CTool        $tool
     * @param Argument     $args
     * @param \ArrayObject $context
     *
     * @return array
     */
    public function resolveDescriptions(CTool $tool, Argument $args, \ArrayObject $context): array
    {
        /** @var Course $course */
        $course = $context->offsetGet('course');
        $cd = new \CourseDescription();
        $cd->set_course_id($course->getId());

        if ($context->offsetExists('session')) {
            /** @var Session $session */
            $session = $context->offsetGet('session');

            $cd->set_session_id($session->getId());
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
