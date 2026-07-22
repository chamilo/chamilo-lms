<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\AssetRepository;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CLink;
use Chamilo\CourseBundle\Repository\CShortcutRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CLinkDetailsController extends AbstractController
{
    public function __invoke(
        CLink $link,
        Request $request,
        CShortcutRepository $shortcutRepository,
        AssetRepository $assetRepository,
        CourseRepository $courseRepository,
    ): Response {
        $course = $this->resolveCourse($request, $courseRepository);
        $shortcut = $course instanceof Course
            ? $shortcutRepository->findShortcutFromResourceInCourse($link, $course)
            : $shortcutRepository->getShortcutFromResource($link);

        $parentResourceNodeId = null;
        if ($link->getResourceNode() && $link->getResourceNode()->getParent()) {
            $parentResourceNodeId = $link->getResourceNode()->getParent()->getId();
        }

        $details = [
            'url' => $link->getUrl(),
            'title' => $link->getTitle(),
            'description' => $link->getDescription(),
            'onHomepage' => null !== $shortcut,
            'target' => $link->getTarget(),
            'parentResourceNodeId' => $parentResourceNodeId,
            'resourceLinkList' => $this->getResourceLinkList($link, $request, $course),
            'category' => $link->getCategory()?->getIid(),
            'language' => $link->getResourceNode()?->getLanguage()?->getIsocode() ?? '',
        ];

        if (null !== $link->getCustomImage()) {
            $details['customImageUrl'] = $assetRepository->getAssetUrl($link->getCustomImage());
        } else {
            $details['customImageUrl'] = null;
        }

        return $this->json($details, Response::HTTP_OK);
    }

    private function resolveCourse(Request $request, CourseRepository $courseRepository): ?Course
    {
        if ($request->hasSession()) {
            $course = $request->getSession()->get('course');
            if ($course instanceof Course) {
                return $course;
            }
        }

        $courseId = $request->query->getInt('cid');
        if ($courseId <= 0) {
            return null;
        }

        $course = $courseRepository->find($courseId);

        return $course instanceof Course ? $course : null;
    }

    /**
     * @return array<int, array<string, int>>
     */
    private function getResourceLinkList(CLink $link, Request $request, ?Course $course): array
    {
        $resourceNode = $link->getResourceNode();
        if (null === $resourceNode) {
            return [];
        }

        $courseId = $course?->getId() ?? $request->query->getInt('cid');
        $sessionId = $request->query->getInt('sid');
        $groupId = $request->query->getInt('gid');

        if ($request->hasSession()) {
            $session = $request->getSession()->get('session');
            if ($session instanceof Session) {
                $sessionId = (int) $session->getId();
            }

            $group = $request->getSession()->get('group');
            if ($group instanceof CGroup) {
                $groupId = (int) $group->getIid();
            }
        }

        $baseResourceLink = null;

        foreach ($resourceNode->getResourceLinks() as $resourceLink) {
            if (!$resourceLink instanceof ResourceLink
                || null !== $resourceLink->getUser()
                || null !== $resourceLink->getUserGroup()
                || (int) ($resourceLink->getCourse()?->getId() ?? 0) !== $courseId
            ) {
                continue;
            }

            $resourceGroupId = (int) ($resourceLink->getGroup()?->getIid() ?? 0);
            if ($resourceGroupId !== $groupId) {
                continue;
            }

            $resourceSessionId = (int) ($resourceLink->getSession()?->getId() ?? 0);
            if ($resourceSessionId === $sessionId) {
                return [$this->normalizeResourceLink($resourceLink)];
            }

            if ($sessionId > 0 && 0 === $resourceSessionId) {
                $baseResourceLink = $resourceLink;
            }
        }

        return $baseResourceLink instanceof ResourceLink
            ? [$this->normalizeResourceLink($baseResourceLink)]
            : [];
    }

    /**
     * @return array<string, int>
     */
    private function normalizeResourceLink(ResourceLink $resourceLink): array
    {
        $normalized = [
            'visibility' => $resourceLink->getVisibility(),
        ];

        $courseId = (int) ($resourceLink->getCourse()?->getId() ?? 0);
        if ($courseId > 0) {
            $normalized['cid'] = $courseId;
        }

        $sessionId = (int) ($resourceLink->getSession()?->getId() ?? 0);
        if ($sessionId > 0) {
            $normalized['sid'] = $sessionId;
        }

        $groupId = (int) ($resourceLink->getGroup()?->getIid() ?? 0);
        if ($groupId > 0) {
            $normalized['gid'] = $groupId;
        }

        return $normalized;
    }
}
