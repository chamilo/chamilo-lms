<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Repository\TrackEExerciseRepository;
use Chamilo\CoreBundle\Security\ExerciseAttemptScopeFactory;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class TrackEExerciseExtension implements QueryCollectionExtensionInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly ExerciseAttemptScopeFactory $exerciseAttemptScopeFactory,
        private readonly TrackEExerciseRepository $trackEExerciseRepository,
    ) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if (TrackEExercise::class !== $resourceClass) {
            return;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $scope = $this->exerciseAttemptScopeFactory->fromToken($this->security->getToken());

        // The previous version only narrowed the query for students, so every other role read
        // the attempts of the whole platform.
        if (null === $scope) {
            throw new AccessDeniedException('Access denied to exercise attempts.');
        }

        // The root alias is the TrackEExercise itself, so no join is needed here.
        $this->trackEExerciseRepository->addViewCriteria(
            $queryBuilder,
            $queryBuilder->getRootAliases()[0],
            $scope
        );
    }
}
