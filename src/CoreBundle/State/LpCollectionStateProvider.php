<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session as CoreSession;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class LpCollectionStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CLpRepository $lpRepo,
        private readonly Security $security
    ) {}

    public function supports(Operation $op, array $uriVariables = [], array $ctx = []): bool
    {
        return CLp::class === $op->getClass() && 'get_lp_collection_with_progress' === $op->getName();
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $f = $context['filters'] ?? [];
        $parentNodeId = (int) ($f['resourceNode.parent'] ?? 0);
        if ($parentNodeId <= 0) {
            return [];
        }

        $course = $this->em->createQuery(
            'SELECT c
               FROM '.Course::class.' c
               JOIN c.resourceNode rn
              WHERE rn.id = :nid'
        )
            ->setParameter('nid', $parentNodeId)
            ->getOneOrNullResult()
        ;

        if (!$course) {
            return [];
        }

        $sid = isset($f['sid']) ? (int) $f['sid'] : null;
        $title = $f['title'] ?? null;

        $session = $sid ? $this->em->getReference(CoreSession::class, $sid) : null;

        $lps = $this->lpRepo->findAllByCourse($course, $session, $title)
            ->getQuery()
            ->getResult()
        ;

        if (!$lps) {
            return [];
        }

        $user = $this->security->getUser();
        if ($user instanceof User) {
            $progress = $this->lpRepo->lastProgressForUser($lps, $user, $session);
            foreach ($lps as $lp) {
                $lp->setProgress($progress[(int) $lp->getIid()] ?? 0);
            }
        }

        return $lps;
    }
}
