<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Wiki;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\StudentViewHelper;
use Chamilo\CoreBundle\Helpers\WikiHelper;
use Chamilo\CoreBundle\Service\EtherpadClient;
use Chamilo\CourseBundle\Entity\CWiki;
use Chamilo\CourseBundle\Repository\CWikiRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/wiki/page/{pageId}/etherpad', requirements: ['pageId' => '\d+'])]
final class WikiEtherpadController extends AbstractController
{
    public function __construct(
        private readonly CidReqHelper $cidReqHelper,
        private readonly StudentViewHelper $studentViewHelper,
        private readonly CWikiRepository $wikiRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        private readonly WikiHelper $wikiHelper,
        private readonly EtherpadClient $etherpadClient,
    ) {}

    #[Route('/session', name: 'wiki_etherpad_session', methods: ['POST'])]
    public function session(int $pageId, Request $request): JsonResponse
    {
        [$wiki, $user] = $this->resolveEditablePage($pageId, $request);

        try {
            $this->etherpadClient->assertReady($request);
            $padId = $this->etherpadClient->ensurePad($wiki);
            if ($wiki->getEtherpadId() !== $padId) {
                $wiki->setEtherpadId($padId);
                $this->entityManager->persist($wiki);
                $this->entityManager->flush();
            }

            $session = $this->etherpadClient->createEditorSession($user, $padId);
        } catch (RuntimeException $exception) {
            throw new ServiceUnavailableHttpException(null, $exception->getMessage(), $exception);
        }

        $response = new JsonResponse([
            'editorUrl' => $session['editorUrl'],
            'expiresAt' => $session['expiresAt'],
        ]);
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->setCookie(
            Cookie::create('sessionID')
                ->withValue($this->mergeSessionIds(
                    (string) $request->cookies->get('sessionID', ''),
                    $session['sessionId'],
                ))
                ->withExpires($session['expiresAt'])
                ->withPath('/')
                ->withSecure($request->isSecure())
                ->withHttpOnly(true)
                ->withSameSite(Cookie::SAMESITE_LAX)
        );

        return $response;
    }

    #[Route('/snapshot', name: 'wiki_etherpad_snapshot', methods: ['POST'])]
    public function snapshot(int $pageId, Request $request): JsonResponse
    {
        [$wiki] = $this->resolveEditablePage($pageId, $request);
        $padId = trim((string) $wiki->getEtherpadId());
        if ('' === $padId) {
            throw new ConflictHttpException('This Wiki page does not have an Etherpad pad yet.');
        }

        try {
            $this->etherpadClient->assertReady($request);
            $content = $this->etherpadClient->getHtml($padId);
        } catch (RuntimeException $exception) {
            throw new ServiceUnavailableHttpException(null, $exception->getMessage(), $exception);
        }

        $response = new JsonResponse(['content' => $content]);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }

    /**
     * @return array{0:CWiki,1:User}
     */
    private function resolveEditablePage(int $pageId, Request $request): array
    {
        if ($pageId <= 0) {
            throw new BadRequestHttpException('A valid Wiki page id is required.');
        }

        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $this->wikiHelper->assertToolEnabled($course);
        $this->wikiHelper->assertRouteNode($course, $request);
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $this->wikiHelper->assertSessionBelongsToCourse($session, $course);
        $group = $this->cidReqHelper->getDoctrineGroupEntity();
        $this->wikiHelper->assertGroupBelongsToContext($group, $course, $session);

        if ($this->studentViewHelper->isActive()) {
            throw new AccessDeniedHttpException('Wiki pages cannot be edited in student view.');
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('An authenticated user is required.');
        }

        $sessionId = null !== $session ? (int) $session->getId() : 0;
        $groupId = null !== $group?->getIid() ? (int) $group->getIid() : 0;
        $wiki = $this->wikiRepository->findLatestVersionInContext(
            (int) $course->getId(),
            $pageId,
            $groupId,
            $sessionId,
        );

        if (!$wiki instanceof CWiki) {
            throw new NotFoundHttpException('The requested Wiki page was not found in the current context.');
        }

        $canManage = $this->wikiHelper->canManage($course, $session, $group);
        $this->wikiHelper->assertPageVisible($wiki, $canManage);
        if (!$this->wikiHelper->canEditPage($course, $session, $group, $wiki)) {
            throw new AccessDeniedHttpException('You are not allowed to edit this Wiki page.');
        }

        return [$wiki, $user];
    }

    private function mergeSessionIds(string $existing, string $newSessionId): string
    {
        $sessionIds = [];
        foreach (explode(',', $existing) as $sessionId) {
            $sessionId = trim($sessionId);
            if (str_starts_with($sessionId, 's.') && mb_strlen($sessionId) <= 128) {
                $sessionIds[$sessionId] = true;
            }
        }
        $sessionIds[$newSessionId] = true;

        return implode(',', \array_slice(array_keys($sessionIds), -10));
    }
}
