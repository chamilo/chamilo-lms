<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Resolver;

use Chamilo\ApiBundle\GraphQL\ApiGraphQLTrait;
use Chamilo\CourseBundle\Entity\CTool;
use Doctrine\ORM\EntityManager;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use Overblog\GraphQLBundle\Resolver\TypeResolver;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class CourseToolResolver.
 *
 * @package Chamilo\ApiBundle\GraphQL\Resolver
 */
class CourseToolResolver implements ResolverInterface, ContainerAwareInterface
{
    use ApiGraphQLTrait;

    /**
     * @var TypeResolver
     */
    private $typeResolver;

    /**
     * CourseToolResolver constructor.
     *
     * @param EntityManager       $entityManager
     * @param TranslatorInterface $translator
     * @param TypeResolver        $typeResolver
     */
    public function __construct(
        EntityManager $entityManager,
        TranslatorInterface $translator,
        TypeResolver $typeResolver
    ) {
        $this->em = $entityManager;
        $this->translator = $translator;
        $this->typeResolver = $typeResolver;
    }

    /**
     * @param CTool $tool
     *
     * @return \GraphQL\Type\Definition\Type
     */
    public function __invoke(CTool $tool)
    {
        switch ($tool->getName()) {
            case TOOL_COURSE_DESCRIPTION:
                return $this->typeResolver->resolve('ToolDescription');
            case TOOL_ANNOUNCEMENT:
                return $this->typeResolver->resolve('ToolAnnouncements');
        }
    }
}
