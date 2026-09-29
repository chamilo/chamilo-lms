<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\TrackEAttemptQualify;
use Chamilo\CoreBundle\Repository\TrackEExerciseRepository;
use Chamilo\CoreBundle\Security\ExerciseAttemptScopeFactory;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

readonly class TrackEAttemptQualifyExtension implements QueryCollectionExtensionInterface
{
    public function __construct(
        private Security $security,
        private ExerciseAttemptScopeFactory $exerciseAttemptScopeFactory,
        private TrackEExerciseRepository $trackEExerciseRepository,
    ) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if (TrackEAttemptQualify::class !== $resourceClass) {
            return;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $scope = $this->exerciseAttemptScopeFactory->fromToken($this->security->getToken());

        // The previous version only filtered students, so every other role read the whole table.
        if (null === $scope) {
            throw new AccessDeniedException('Access denied to exercise attempt corrections.');
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $queryBuilder->innerJoin("$alias.trackExercise", 'tee');

        $this->trackEExerciseRepository->addViewCriteria($queryBuilder, 'tee', $scope);
    }
}
