<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CBlog;
use Chamilo\CourseBundle\Repository\CBlogRepository;
use Chamilo\CourseBundle\Repository\CShortcutRepository;
use Doctrine\ORM\EntityManagerInterface as EntityManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

#[AsController]
class CreateCBlogAction extends BaseResourceFileAction
{
    public function __invoke(
        Request $request,
        CBlogRepository $repo,
        EntityManager $em,
        CShortcutRepository $shortcutRepository,
        Security $security
    ): CBlog {
        $data = json_decode($request->getContent(), true) ?: [];

        $title = (string) ($data['title'] ?? '');
        $subtitle = $data['blogSubtitle'] ?? null;
        $parentResourceNodeId = $data['parentResourceNodeId'] ?? null;
        $resourceLinkListRaw = $data['resourceLinkList'] ?? [];
        $showOnHomepage = isset($data['showOnHomepage']) ? (bool) $data['showOnHomepage'] : false;

        if (\is_string($resourceLinkListRaw)) {
            $decoded = json_decode($resourceLinkListRaw, true);
            $resourceLinkList = \is_array($decoded) ? $decoded : [];
        } else {
            $resourceLinkList = \is_array($resourceLinkListRaw) ? $resourceLinkListRaw : [];
        }

        // The `cid` (and optional `sid`/`gid`) query parameter establishes the
        // course context that gated the security expression. Any
        // resourceLinkList entry that points to a different context would
        // bypass that gate, so we reject the request outright.
        $this->assertResourceLinkListMatchesQueryContext($request, $resourceLinkList, $security);

        $blog = (new CBlog())
            ->setTitle($title)
            ->setBlogSubtitle($subtitle)
        ;

        if (!empty($parentResourceNodeId)) {
            $blog->setParentResourceNode((int) $parentResourceNodeId);
        }

        if (!empty($resourceLinkList)) {
            $blog->setResourceLinkArray($resourceLinkList);
        }

        $em->persist($blog);
        $em->flush();

        // Optional: create shortcut on homepage (same behavior as links)
        $this->handleShortcutCreation($resourceLinkList, $em, $security, $blog, $shortcutRepository, $showOnHomepage);

        return $blog;
    }

    private function handleShortcutCreation(
        array $resourceLinkList,
        EntityManager $em,
        Security $security,
        CBlog $blog,
        CShortcutRepository $shortcutRepository,
        bool $onHomepage
    ): void {
        if (!$onHomepage || empty($resourceLinkList)) {
            return;
        }

        $first = reset($resourceLinkList);
        $sid = (int) ($first['sid'] ?? 0);
        $cid = (int) ($first['cid'] ?? 0);

        $course = $cid ? $em->getRepository(Course::class)->find($cid) : null;
        $session = $sid ? $em->getRepository(Session::class)->find($sid) : null;

        /** @var User $currentUser */
        $currentUser = $security->getUser();
        if ($currentUser) {
            $shortcutRepository->addShortCut($blog, $currentUser, $course, $session);
        }
    }

    /**
     * @param array<int, mixed> $resourceLinkList
     */
    private function assertResourceLinkListMatchesQueryContext(
        Request $request,
        array $resourceLinkList,
        Security $security,
    ): void {
        if ($security->isGranted('ROLE_ADMIN')) {
            return;
        }

        if ([] === $resourceLinkList) {
            return;
        }

        $queryCid = (int) $request->query->get('cid');
        $querySid = (int) $request->query->get('sid');
        $queryGid = (int) $request->query->get('gid');

        foreach ($resourceLinkList as $entry) {
            if (!\is_array($entry)) {
                continue;
            }

            $entryCid = (int) ($entry['cid'] ?? 0);
            $entrySid = (int) ($entry['sid'] ?? 0);
            $entryGid = (int) ($entry['gid'] ?? 0);

            if ($entryCid > 0 && $entryCid !== $queryCid) {
                throw new AccessDeniedHttpException('resourceLinkList course does not match the request context.');
            }

            if ($entrySid > 0 && $entrySid !== $querySid) {
                throw new AccessDeniedHttpException('resourceLinkList session does not match the request context.');
            }

            if ($entryGid > 0 && $entryGid !== $queryGid) {
                throw new AccessDeniedHttpException('resourceLinkList group does not match the request context.');
            }
        }
    }
}
