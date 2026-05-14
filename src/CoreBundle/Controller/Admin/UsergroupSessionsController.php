<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Entity\UsergroupRelSession;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/usergroup-sessions-data')]
class UsergroupSessionsController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly AccessUrlHelper $accessUrlHelper,
    ) {}

    #[Route('/{id}', name: 'admin_usergroup_sessions_data', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function data(int $id): JsonResponse
    {
        $usergroup = $this->em->find(Usergroup::class, $id);
        if (null === $usergroup) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->belongsToCurrentUrl($usergroup)) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $subscribedIds = $this->em->createQueryBuilder()
            ->select('IDENTITY(rs.session) AS sId')
            ->from(UsergroupRelSession::class, 'rs')
            ->where('rs.usergroup = :ugId')
            ->setParameter('ugId', $id, Types::INTEGER)
            ->getQuery()
            ->getSingleColumnResult()
        ;
        $subscribedIds = array_map('intval', $subscribedIds);

        $qb = $this->em->createQueryBuilder()
            ->select('s.id, s.title')
            ->from(Session::class, 's')
        ;

        if ($this->accessUrlHelper->isMultiple()) {
            $accessUrl = $this->accessUrlHelper->getCurrent();
            if (null !== $accessUrl) {
                $qb->join('s.urls', 'urlRel')
                    ->andWhere('urlRel.url = :urlId')
                    ->setParameter('urlId', $accessUrl->getId(), Types::INTEGER)
                ;
            }
        }

        $allSessions = $qb
            ->orderBy('s.title', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        $inGroup = [];
        $notInGroup = [];

        foreach ($allSessions as $session) {
            $item = [
                'id' => $session['id'],
                'label' => $session['title'],
            ];

            if (\in_array((int) $session['id'], $subscribedIds, true)) {
                $inGroup[] = $item;
            } else {
                $notInGroup[] = $item;
            }
        }

        return $this->json([
            'groupId' => $id,
            'groupTitle' => $usergroup->getTitle(),
            'sessionsInGroup' => $inGroup,
            'sessionsNotInGroup' => $notInGroup,
            'csrfToken' => $this->csrfTokenManager->getToken('usergroup_sessions')->getValue(),
        ]);
    }

    #[Route('/{id}', name: 'admin_usergroup_sessions_save', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function save(Request $request, int $id): JsonResponse
    {
        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('usergroup_sessions', $token)) {
            return $this->json(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        $usergroup = $this->em->find(Usergroup::class, $id);
        if (null === $usergroup) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->belongsToCurrentUrl($usergroup)) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $sessionIds = array_map('intval', (array) $request->request->all('sessionIds'));

        $this->em->createQueryBuilder()
            ->delete(UsergroupRelSession::class, 'rs')
            ->where('rs.usergroup = :ugId')
            ->setParameter('ugId', $id, Types::INTEGER)
            ->getQuery()
            ->execute()
        ;

        foreach ($sessionIds as $sessionId) {
            $session = $this->em->find(Session::class, $sessionId);
            if (null === $session) {
                continue;
            }

            $rel = new UsergroupRelSession();
            $rel->setUsergroup($usergroup);
            $rel->setSession($session);
            $this->em->persist($rel);
        }

        $this->em->flush();

        return $this->json(['success' => true]);
    }

    private function belongsToCurrentUrl(Usergroup $usergroup): bool
    {
        if (!$this->accessUrlHelper->isMultiple()) {
            return true;
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (null === $accessUrl) {
            return true;
        }

        $urlId = $accessUrl->getId();
        foreach ($usergroup->getUrls() as $urlRel) {
            $relUrl = $urlRel->getUrl();
            if (null !== $relUrl && $relUrl->getId() === $urlId) {
                return true;
            }
        }

        return false;
    }
}
