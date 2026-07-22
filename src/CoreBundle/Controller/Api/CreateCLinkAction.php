<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CLink;
use Chamilo\CourseBundle\Entity\CLinkCategory;
use Chamilo\CourseBundle\Repository\CLinkRepository;
use Chamilo\CourseBundle\Repository\CShortcutRepository;
use Doctrine\ORM\EntityManager;
use JsonException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use const JSON_THROW_ON_ERROR;

class CreateCLinkAction extends BaseResourceFileAction
{
    public function __invoke(
        Request $request,
        CLinkRepository $repo,
        EntityManager $em,
        CShortcutRepository $shortcutRepository,
        Security $security,
    ): CLink {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new BadRequestHttpException('Invalid JSON payload.', $exception);
        }

        if (!\is_array($data)) {
            throw new BadRequestHttpException('Invalid link payload.');
        }

        $url = $data['url'];
        $title = $data['title'];
        $description = $data['description'];
        $categoryId = (int) $data['category'];
        $onHomepage = isset($data['showOnHomepage']) && (bool) $data['showOnHomepage'];
        $target = $data['target'];
        $parentResourceNodeId = $data['parentResourceNodeId'];
        $resourceLinkList = $this->buildResourceLinkListFromContext(
            $request,
            $this->decodeResourceLinkList($data['resourceLinkList'] ?? []),
        );

        $link = (new CLink())
            ->setUrl($url)
            ->setTitle($title)
            ->setDescription($description)
            ->setTarget($target)
        ;

        if (0 !== $categoryId) {
            $linkCategory = $em
                ->getRepository(CLinkCategory::class)
                ->find($categoryId)
            ;

            if ($linkCategory) {
                $link->setCategory($linkCategory);
            }
        }

        if (!empty($parentResourceNodeId)) {
            $link->setParentResourceNode($parentResourceNodeId);
        }

        $link->setResourceLinkArray($resourceLinkList);

        $em->persist($link);
        $em->flush();

        $this->applyResourceLanguageFromRequest($link, $request, $em);
        $em->flush();

        $this->handleShortcutCreation(
            $resourceLinkList,
            $em,
            $security,
            $link,
            $shortcutRepository,
            $onHomepage,
        );

        return $link;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function decodeResourceLinkList(mixed $resourceLinkList): array
    {
        if (\is_array($resourceLinkList)) {
            return $resourceLinkList;
        }

        if (!\is_string($resourceLinkList) || '' === trim($resourceLinkList)) {
            return [];
        }

        try {
            $decoded = json_decode($resourceLinkList, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new BadRequestHttpException('Invalid resourceLinkList payload.', $exception);
        }

        if (!\is_array($decoded)) {
            throw new BadRequestHttpException('Invalid resourceLinkList payload.');
        }

        return $decoded;
    }

    /**
     * @param array<int, array<string, int>> $resourceLinkList
     */
    private function handleShortcutCreation(
        array $resourceLinkList,
        EntityManager $em,
        Security $security,
        CLink $link,
        CShortcutRepository $shortcutRepository,
        bool $onHomepage,
    ): void {
        if (!$onHomepage) {
            return;
        }

        $firstLink = $resourceLinkList[0] ?? [];
        $courseId = (int) ($firstLink['cid'] ?? 0);
        if ($courseId <= 0) {
            throw new BadRequestHttpException('Course context is required to publish a link on the course homepage.');
        }

        $course = $em->getRepository(Course::class)->find($courseId);
        if (!$course instanceof Course) {
            throw new BadRequestHttpException('Course context was not found.');
        }

        $session = null;
        $sessionId = (int) ($firstLink['sid'] ?? 0);
        if ($sessionId > 0) {
            $session = $em->getRepository(Session::class)->find($sessionId);
            if (!$session instanceof Session) {
                throw new BadRequestHttpException('Session context was not found.');
            }
        }

        $currentUser = $security->getUser();
        if (!$currentUser instanceof User) {
            throw new AccessDeniedHttpException('Authenticated user is required.');
        }

        $shortcutRepository->addShortCut($link, $currentUser, $course, $session);
    }
}
