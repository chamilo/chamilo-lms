<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Resolver;

use Chamilo\ApiBundle\GraphQL\ApiGraphQLTrait;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\UserBundle\Entity\User;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use Overblog\GraphQLBundle\Error\UserError;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class RootResolver.
 *
 * @package Chamilo\ApiBundle\GraphQL\Resolver
 */
class RootResolver implements ResolverInterface, AliasedInterface, ContainerAwareInterface
{
    use ApiGraphQLTrait;

    /**
     * Returns methods aliases.
     *
     * For instance:
     * array('myMethod' => 'myAlias')
     *
     * @return array
     */
    public static function getAliases()
    {
        return [
            'resolverViewer' => 'viewer',
            'resolveCourse' => 'course',
        ];
    }

    /**
     * @param \ArrayObject $context
     *
     * @return User
     */
    public function resolverViewer(\ArrayObject $context)
    {
        $this->checkAuthorization($context);

        /** @var User $user */
        $user = $context->offsetGet('user');

        return $user;
    }

    /**
     * @param int          $courseId
     * @param \ArrayObject $context
     *
     * @return Course|null
     */
    public function resolveCourse($courseId, \ArrayObject $context)
    {
        $this->checkAuthorization($context);
        $courseRepo = $this->em->getRepository('ChamiloCoreBundle:Course');
        $course = $courseRepo->find($courseId);

        $context->offsetSet('course', $course);

        if (!$course) {
            throw new UserError(get_lang('NoCourse'));
        }

        $checker = $this->container->get('security.authorization_checker');

        if (false === $checker->isGranted(CourseVoter::VIEW, $course)) {
            throw new UserError(get_lang('NotAllowed'));
        }

        return $course;
    }
}
