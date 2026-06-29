<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathManagementInput;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SkillRelItem;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\ResourceHelper;
use Chamilo\CoreBundle\Repository\AssetRepository;
use Chamilo\CoreBundle\Repository\ResourceLinkRepository;
use Chamilo\CoreBundle\Service\LearningPath\LearningPathCopyService;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Chamilo\CourseBundle\Repository\CShortcutRepository;
use Chamilo\CourseBundle\Settings\SettingsCourseManager;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Throwable;

/** @implements ProcessorInterface<LearningPathManagementInput, void> */
final readonly class LearningPathManagementProcessor implements ProcessorInterface
{
    use LearningPathStateHelperTrait;

    private const ACTION_COPY = 'copy';
    private const ACTION_DELETE = 'delete';
    private const ACTION_SWITCH_ATTEMPT_MODE = 'switch_attempt_mode';
    private const ACTION_SWITCH_SCORM_DEBUG = 'switch_scorm_debug';
    private const ACTION_SWITCH_VIEW_MODE = 'switch_view_mode';
    private const ACTION_TOGGLE_AUTO_LAUNCH = 'toggle_auto_launch';
    private const ACTION_TOGGLE_PUBLISH = 'toggle_publish';
    private const ACTION_TOGGLE_SERIOUS_GAME = 'toggle_serious_game';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private LearningPathCopyService $learningPathCopyService,
        private CLpRepository $learningPathRepository,
        private CShortcutRepository $shortcutRepository,
        private RequestStack $requestStack,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private SettingsManager $settingsManager,
        private SettingsCourseManager $settingsCourseManager,
        private ResourceLinkRepository $resourceLinkRepository,
        private AssetRepository $assetRepository,
        private ResourceHelper $resourceHelper,
        private CDocumentRepository $documentRepository,
        private LoggerInterface $logger,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (!$data instanceof LearningPathManagementInput) {
            throw new BadRequestHttpException('Learning path management data is required.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $this->assertLearningPathTeacher($this->security);
        $this->validateActionToken($this->csrfTokenManager, $data->csrfToken);

        $course = $this->getContextCourse($this->entityManager, $request);
        $session = $this->getContextSession($this->entityManager, $request, $course);
        $group = $this->getContextGroup($this->entityManager, $request, $course);

        $lpId = (int) ($uriVariables['lpId'] ?? $data->lpId ?? 0);
        if ($lpId <= 0) {
            throw new BadRequestHttpException('Invalid learning path id.');
        }

        $learningPath = $this->learningPathRepository->find($lpId);
        if (!$learningPath instanceof CLp) {
            throw new NotFoundHttpException('Learning path not found.');
        }

        $resourceNode = $learningPath->getResourceNode();
        if (!$resourceNode instanceof ResourceNode || !$this->security->isGranted('EDIT', $resourceNode)) {
            throw new AccessDeniedHttpException('You are not allowed to manage this learning path.');
        }

        $resourceLink = $resourceNode->getResourceLinkByContext($course, $session, $group);
        if (!$resourceLink instanceof ResourceLink) {
            throw new AccessDeniedHttpException('The learning path is not owned by the current course context.');
        }

        switch ($data->action) {
            case self::ACTION_COPY:
                $this->copyLearningPath($learningPath, $course, $session, $group);

                return;

            case self::ACTION_DELETE:
                $this->deleteLearningPath($learningPath, $resourceLink, $course, $session, $group);

                return;

            case self::ACTION_TOGGLE_PUBLISH:
                $this->togglePublish($learningPath, $course, $session);
                break;

            case self::ACTION_SWITCH_ATTEMPT_MODE:
                $this->switchAttemptMode($learningPath);
                break;

            case self::ACTION_SWITCH_VIEW_MODE:
                $this->switchViewMode($learningPath);
                break;

            case self::ACTION_SWITCH_SCORM_DEBUG:
                $this->toggleScormDebug($learningPath);
                break;

            case self::ACTION_TOGGLE_SERIOUS_GAME:
                $this->toggleSeriousGame($learningPath);
                break;

            case self::ACTION_TOGGLE_AUTO_LAUNCH:
                $this->toggleAutoLaunch($learningPath, $course, $session, $data->enabled);
                break;

            default:
                throw new BadRequestHttpException('Unsupported learning path management action.');
        }

        $learningPath->setModifiedOn(new DateTime());
        $this->entityManager->persist($learningPath);
        $this->entityManager->flush();
    }

    private function copyLearningPath(
        CLp $learningPath,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): void {
        if ($this->isTruthy($this->settingsManager->getSetting('lp.hide_scorm_copy_link', true))) {
            throw new AccessDeniedHttpException('Learning path copy is disabled.');
        }

        if ($group instanceof CGroup) {
            throw new BadRequestHttpException('Learning paths cannot be copied from a group context.');
        }

        if (CLp::AICC_TYPE === $learningPath->getLpType()) {
            throw new AccessDeniedHttpException('AICC learning path copy is not supported.');
        }

        if (CLp::SCORM_TYPE === $learningPath->getLpType()
            && !$this->isTruthy($this->settingsManager->getSetting('lp.allow_import_scorm_package_in_course_builder', true))
        ) {
            throw new AccessDeniedHttpException('SCORM learning path copy is disabled.');
        }

        try {
            $this->learningPathCopyService->duplicate($learningPath, $course, $session);
        } catch (Throwable $exception) {
            $this->logger->error('Failed to copy the learning path.', [
                'learningPathId' => $learningPath->getIid(),
                'courseId' => $course->getId(),
                'sessionId' => $session?->getId(),
                'exception' => $exception,
            ]);

            throw new BadRequestHttpException('Learning path could not be copied.', $exception);
        }
    }

    private function deleteLearningPath(
        CLp $learningPath,
        ResourceLink $contextResourceLink,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): void {
        $learningPathId = (int) $learningPath->getIid();
        $resourceNode = $learningPath->getResourceNode();
        $hasOtherActiveLinks = false;

        if ($resourceNode instanceof ResourceNode) {
            foreach ($resourceNode->getResourceLinks() as $resourceLink) {
                if ($resourceLink instanceof ResourceLink
                    && $resourceLink !== $contextResourceLink
                    && null === $resourceLink->getDeletedAt()
                ) {
                    $hasOtherActiveLinks = true;
                    break;
                }
            }
        }

        if (!$hasOtherActiveLinks) {
            $asset = $learningPath->getAsset();
            if (null !== $asset) {
                $learningPath->setAsset(null);
                $this->entityManager->persist($learningPath);
                $this->entityManager->flush();

                try {
                    $this->assetRepository->delete($asset);
                } catch (Throwable $exception) {
                    $this->logger->warning('Failed to delete the learning path asset during learning path deletion.', [
                        'learningPathId' => $learningPathId,
                        'assetId' => $asset->getId(),
                        'exception' => $exception,
                    ]);
                }
            }
        }

        $this->shortcutRepository->removeShortCutFromCourse($learningPath, $course);
        $this->removeGradebookLinks($learningPathId, $course, $session);

        if (!$hasOtherActiveLinks) {
            foreach ($this->entityManager->getRepository(SkillRelItem::class)->findBy([
                'itemId' => $learningPathId,
                'itemType' => 4,
            ]) as $skillRelation) {
                $this->entityManager->remove($skillRelation);
            }
        }

        $this->resourceLinkRepository->removeByResourceInContext($learningPath, $course, $session, $group);

        if ($resourceNode instanceof ResourceNode) {
            $user = $this->security->getUser();
            $this->resourceHelper->createAndSaveResourceEvent(
                $resourceNode,
                'deletion',
                $user instanceof User ? $user->getId() : null,
                $course->getId(),
                $session?->getId(),
            );
        }

        $this->entityManager->flush();

        if ($hasOtherActiveLinks) {
            return;
        }

        try {
            $this->documentRepository->purgeScormZip($course, $learningPath);
        } catch (Throwable $exception) {
            $this->logger->error('Failed to purge the learning path SCORM ZIP.', [
                'learningPathId' => $learningPathId,
                'exception' => $exception,
            ]);
        }
    }

    private function removeGradebookLinks(int $learningPathId, Course $course, ?Session $session): void
    {
        foreach ($this->entityManager->getRepository(GradebookLink::class)->findBy([
            'course' => $course,
            'type' => 4,
            'refId' => $learningPathId,
        ]) as $gradebookLink) {
            if (!$gradebookLink instanceof GradebookLink) {
                continue;
            }

            $linkSession = $gradebookLink->getCategory()->getSession();
            if ($linkSession?->getId() !== $session?->getId()) {
                continue;
            }

            $this->entityManager->remove($gradebookLink);
        }
    }

    private function togglePublish(CLp $learningPath, Course $course, ?Session $session): void
    {
        $shortcut = $this->shortcutRepository->findShortcutFromResourceInCourse($learningPath, $course);
        if (null !== $shortcut) {
            $this->shortcutRepository->removeShortCutFromCourse($learningPath, $course);

            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authenticated user is required.');
        }

        $this->shortcutRepository->addShortCut($learningPath, $user, $course, $session);
    }

    private function switchAttemptMode(CLp $learningPath): void
    {
        if ($learningPath->getSeriousgameMode() && $learningPath->getPreventReinit()) {
            $learningPath->setSeriousgameMode(false);
            $learningPath->setPreventReinit(true);

            return;
        }

        if (!$learningPath->getSeriousgameMode() && $learningPath->getPreventReinit()) {
            $learningPath->setSeriousgameMode(false);
            $learningPath->setPreventReinit(false);

            return;
        }

        $learningPath->setSeriousgameMode(true);
        $learningPath->setPreventReinit(true);
    }

    private function switchViewMode(CLp $learningPath): void
    {
        $nextMode = match ($learningPath->getDefaultViewMod()) {
            'fullscreen' => 'embedded',
            'embedded' => 'embedframe',
            'embedframe' => 'impress',
            'impress' => 'fullscreen',
            default => 'embedded',
        };

        $learningPath->setDefaultViewMod($nextMode);
    }

    private function toggleScormDebug(CLp $learningPath): void
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('Only platform administrators can change SCORM debug mode.');
        }

        $learningPath->setDebug(!$learningPath->getDebug());
    }

    private function toggleSeriousGame(CLp $learningPath): void
    {
        if (!$this->isTruthy($this->settingsManager->getSetting('workflows.gamification_mode'))) {
            throw new AccessDeniedHttpException('Gamification mode is disabled.');
        }

        $learningPath->setSeriousgameMode(!$learningPath->getSeriousgameMode());
    }

    private function toggleAutoLaunch(CLp $learningPath, Course $course, ?Session $session, ?bool $enabled): void
    {
        if (null === $enabled) {
            throw new BadRequestHttpException('The auto-launch state is required.');
        }

        $this->settingsCourseManager->setCourse($course);
        if (1 !== (int) $this->settingsCourseManager->getCourseSettingValue('enable_lp_auto_launch')) {
            throw new AccessDeniedHttpException('Learning path auto-launch is disabled for this course.');
        }

        /** @var array<int, CLp> $learningPaths */
        $learningPaths = $this->learningPathRepository->getResourcesByCourse($course, $session)->getQuery()->getResult();
        foreach ($learningPaths as $candidate) {
            $candidate->setAutolaunch(0);
            $this->entityManager->persist($candidate);
        }

        if ($enabled) {
            $learningPath->setAutolaunch(1);
        }
    }

    private function isTruthy(mixed $value): bool
    {
        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
