<?php
/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

class CStudentPublicationExtension implements QueryCollectionExtensionInterface
{
    use CourseLinkExtensionTrait;

    public function __construct(private readonly Security $security, private readonly RequestStack $requestStack)
    {
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        Operation $operation = null,
        array $context = []
    ): void {
        if (CStudentPublication::class !== $resourceClass) {
            return;
        }

        if (null === $this->security->getUser()) {
            throw new AccessDeniedException();
        }

        $request = $this->requestStack->getCurrentRequest();

        $courseId = $request->query->getInt('cid');
        $sessionId = $request->query->getInt('sid');
        $groupId = $request->query->getInt('gid');

        $this->addCourseLinkWithVisibilityConditions($queryBuilder, true, $courseId, $sessionId, $groupId);
    }
}
