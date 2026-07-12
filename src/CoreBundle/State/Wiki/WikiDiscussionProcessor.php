<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Wiki;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Wiki\WikiDiscussion;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CWiki;
use Chamilo\CourseBundle\Entity\CWikiDiscuss;
use Chamilo\CourseBundle\Repository\CWikiRepository;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProcessorInterface<WikiDiscussion, WikiDiscussion>
 */
final readonly class WikiDiscussionProcessor implements ProcessorInterface
{
    use WikiAccessHelperTrait;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CWikiRepository $wikiRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private WikiDiscussionScoreCalculator $scoreCalculator,
        private WikiNotificationService $notificationService,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): WikiDiscussion
    {
        if (!$data instanceof WikiDiscussion) {
            throw new BadRequestHttpException('The request payload is invalid.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getWikiCourse($this->entityManager, $request);
        $this->assertWikiToolEnabled($this->entityManager, $course);
        $this->assertWikiRouteNode($course, $request);
        $session = $this->getWikiSession($this->entityManager, $request);
        $this->assertWikiSessionBelongsToCourse($session, $course);
        $group = $this->getWikiGroup($this->entityManager, $request);
        $this->assertWikiGroupBelongsToContext($group, $course, $session);

        if (!$this->canReadWikiContext($this->security, $this->settingsManager, $course, $session, $group)) {
            throw new AccessDeniedHttpException('You are not allowed to use Wiki discussions in this context.');
        }

        if ($this->isWikiStudentView($request)) {
            throw new AccessDeniedHttpException('Wiki discussion comments are not available in student view.');
        }

        $pageId = isset($uriVariables['pageId']) ? (int) $uriVariables['pageId'] : 0;
        if ($pageId <= 0) {
            throw new BadRequestHttpException('A valid Wiki page id is required.');
        }

        $courseId = (int) $course->getId();
        $sessionId = null !== $session ? (int) $session->getId() : 0;
        $groupId = null !== $group?->getIid() ? (int) $group->getIid() : 0;
        $latest = $this->wikiRepository->findLatestVersionInContext($courseId, $pageId, $groupId, $sessionId);

        if (!$latest instanceof CWiki) {
            throw new NotFoundHttpException('The requested Wiki discussion was not found in the current context.');
        }

        $canManage = $this->canManageWikiContext(
            $this->entityManager,
            $this->security,
            $this->settingsManager,
            $course,
            $session,
            $group,
        );

        if (null !== $session && !$canManage) {
            throw new AccessDeniedHttpException('Wiki discussions in sessions are available to session editors only.');
        }

        $this->assertWikiPageVisible($this->security, $latest, $canManage);

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('An authenticated user is required.');
        }

        $isWorkOwner = 2 === $latest->getAssignment() && $latest->getUserId() === (int) $user->getId();
        if (1 !== $latest->getVisibilityDisc() && !$canManage && !$isWorkOwner) {
            throw new AccessDeniedHttpException('This Wiki discussion is not visible in the current context.');
        }

        if (1 !== $latest->getAddlockDisc() && !$canManage) {
            throw new AccessDeniedHttpException('New comments are blocked in this Wiki discussion.');
        }

        $this->validateCsrfToken($data->writeCsrfToken);

        $commentText = trim($data->comment);
        if ('' === $commentText) {
            throw new BadRequestHttpException('A Wiki discussion comment is required.');
        }

        $rating = $data->rating;
        if (null !== $rating) {
            if (1 !== $latest->getRatinglockDisc() && !$canManage) {
                throw new AccessDeniedHttpException('Ratings are blocked in this Wiki discussion.');
            }

            if ($rating < 0 || $rating > 10) {
                throw new BadRequestHttpException('The Wiki discussion rating must be between 0 and 10.');
            }
        }

        $existingScores = $this->entityManager->getRepository(CWikiDiscuss::class)->createQueryBuilder('d')
            ->select('d.pScore AS score')
            ->andWhere('d.cId = :courseId')
            ->andWhere('d.publicationId = :pageId')
            ->setParameter('courseId', $courseId, Types::INTEGER)
            ->setParameter('pageId', $pageId, Types::INTEGER)
            ->getQuery()
            ->getArrayResult()
        ;
        $scores = array_map(
            static fn (array $row): mixed => $row['score'] ?? null,
            $existingScores,
        );
        if (null !== $rating) {
            $scores[] = $rating;
        }

        $discussion = (new CWikiDiscuss())
            ->setCId($courseId)
            ->setPublicationId($pageId)
            ->setUsercId((int) $user->getId())
            ->setComment($commentText)
            ->setPScore(null !== $rating ? (string) $rating : '-')
            ->setDtime(new DateTime('now', new DateTimeZone('UTC')))
        ;
        $this->entityManager->persist($discussion);

        $average = $this->scoreCalculator->average($scores);
        $versions = $this->wikiRepository->findPageVersionsInContext($courseId, $pageId, $groupId, $sessionId);
        foreach ($versions as $version) {
            $version->setScore((int) round($average));
        }

        $this->entityManager->flush();
        $this->notificationService->notifyDiscussionComment($latest, $course, $session, $group, $user);

        return $data;
    }

    private function validateCsrfToken(string $token): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(WikiDiscussion::CSRF_TOKEN_ID, $token))) {
            throw new AccessDeniedHttpException('The Wiki discussion CSRF token is invalid.');
        }
    }
}
