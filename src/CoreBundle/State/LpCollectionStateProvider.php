<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session as CoreSession;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @template-implements ProviderInterface<CLp>
 */
final readonly class LpCollectionStateProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private CLpRepository $lpRepo,
        private Security $security,
        private CidReqHelper $cidReqHelper,
    ) {}

    public function supports(Operation $op, array $uriVariables = [], array $ctx = []): bool
    {
        return CLp::class === $op->getClass() && 'get_lp_collection_with_progress' === $op->getName();
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $f = $context['filters'] ?? [];
        $parentNodeId = (int) ($f['resourceNode.parent'] ?? 0);
        if ($parentNodeId <= 0) {
            return [];
        }

        // The operation's contextual-role security already authorized the current course,
        // so the course is taken from the session context (not from client input). The
        // client-supplied resourceNode.parent is then only accepted when it matches that
        // course's resource node, so a member of one course cannot list another course's
        // learning paths by pointing parent at a foreign course node (IDOR).
        $course = $this->cidReqHelper->getDoctrineCourseEntity();
        if (!$course instanceof Course) {
            return [];
        }

        $courseNode = $course->getResourceNode();
        if (null === $courseNode || $parentNodeId !== (int) $courseNode->getId()) {
            throw new AccessDeniedHttpException('resourceNode.parent does not match the current course.');
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
