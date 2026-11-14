<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\StudentPublicationCommentVisibilityHelper;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationComment;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Hides the teacher-only comments of a submission from whoever is not one of its teachers.
 *
 * The teachers are those of the filtered submissions (publication.iid). A list that is not
 * narrowed to known submissions never carries private comments, whoever asks for it.
 */
final readonly class CStudentPublicationCommentExtension implements QueryCollectionExtensionInterface
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager,
    ) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if (CStudentPublicationComment::class !== $resourceClass) {
            return;
        }

        if ($this->maySeeTeacherOnlyComments($context['filters']['publication.iid'] ?? null)) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $parameter = $queryNameGenerator->generateParameterName('teacherOnly');

        $queryBuilder
            ->andWhere($rootAlias.'.teacherOnly = :'.$parameter)
            ->setParameter($parameter, false)
        ;
    }

    private function maySeeTeacherOnlyComments(mixed $publicationFilter): bool
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // The Vue client always asks for one submission by its plain numeric id. Anything else
        // (no filter, several ids, nested arrays, IRIs) gets the public list only.
        if (\is_array($publicationFilter) && 1 === \count($publicationFilter)) {
            $publicationFilter = reset($publicationFilter);
        }

        if ((!\is_int($publicationFilter) && !\is_string($publicationFilter))
            || !ctype_digit((string) $publicationFilter)
            || (int) $publicationFilter <= 0
        ) {
            return false;
        }

        $submission = $this->entityManager->find(CStudentPublication::class, (int) $publicationFilter);

        return $submission instanceof CStudentPublication
            && StudentPublicationCommentVisibilityHelper::canSeeTeacherOnlyComments($submission, $user);
    }
}
