<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authorization;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\AccessUrlRelSession;
use Chamilo\CoreBundle\Entity\AccessUrlRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlScopeHelper;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Who can read the transcript of a user and who can issue their official transcript.
 *
 * A user reads their own transcript but never issues it: the official transcript is issued by
 * an administrator of the user's access URL, an HR manager following the user, or a session
 * administrator of a session the user is a learner of. Those can also read the user's transcript.
 */
final readonly class TranscriptAccessChecker
{
    public function __construct(
        private Security $security,
        private AccessUrlScopeHelper $accessUrlScopeHelper,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    public function canView(User $actor, User $target, AccessUrl $url): bool
    {
        return $actor->getId() === $target->getId() || $this->canIssue($actor, $target, $url);
    }

    public function canIssue(User $actor, User $target, AccessUrl $url): bool
    {
        if ($actor->getId() === $target->getId() || !$this->isUserInUrl($target, $url)) {
            return false;
        }

        if ($this->security->isGranted('ROLE_ADMIN')
            && $this->accessUrlScopeHelper->isUserManaged($actor, (int) $target->getId())
        ) {
            return true;
        }

        if ($this->security->isGranted('ROLE_HR')
            && $this->userRepository->isUserFollowedByHrm((int) $target->getId(), (int) $actor->getId())
        ) {
            return true;
        }

        return $this->security->isGranted('ROLE_SESSION_MANAGER')
            && $this->isLearnerOfAdministratedSession($actor, $target, $url);
    }

    public function canManageSignature(): bool
    {
        return $this->security->isGranted('ROLE_ADMIN');
    }

    private function isUserInUrl(User $user, AccessUrl $url): bool
    {
        return null !== $this->entityManager->getRepository(AccessUrlRelUser::class)->findOneBy([
            'user' => $user,
            'url' => $url,
        ]);
    }

    private function isLearnerOfAdministratedSession(User $sessionAdmin, User $learner, AccessUrl $url): bool
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(learnerSubscription.id)')
            ->from(SessionRelUser::class, 'learnerSubscription')
            ->innerJoin(AccessUrlRelSession::class, 'sessionUrl', Join::ON, 'sessionUrl.session = learnerSubscription.session')
            ->innerJoin(
                SessionRelUser::class,
                'adminSubscription',
                Join::ON,
                'adminSubscription.session = learnerSubscription.session AND adminSubscription.user = :sessionAdmin AND adminSubscription.relationType = :sessionAdminRelation',
            )
            ->andWhere('learnerSubscription.user = :learner')
            ->andWhere('learnerSubscription.relationType = :learnerRelation')
            ->andWhere('sessionUrl.url = :url')
            ->setParameter('sessionAdmin', $sessionAdmin->getId(), Types::INTEGER)
            ->setParameter('sessionAdminRelation', Session::SESSION_ADMIN, Types::INTEGER)
            ->setParameter('learner', $learner->getId(), Types::INTEGER)
            ->setParameter('learnerRelation', Session::STUDENT, Types::INTEGER)
            ->setParameter('url', $url->getId(), Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult() > 0
        ;
    }
}
