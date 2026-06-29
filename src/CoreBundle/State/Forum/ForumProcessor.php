<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Forum;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Security\Upload\UploadFilenamePolicy;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumCategory;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Repository\CForumRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProcessorInterface<mixed, CForum|JsonResponse>
 */
final class ForumProcessor implements ProcessorInterface
{
    use ForumActionStateHelperTrait;
    use ForumNotificationHelperTrait;
    use ForumStateHelperTrait;
    use ForumWriteHelperTrait;

    private const FORUM_IMAGE_ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CForumRepository $forumRepository,
        private readonly RequestStack $requestStack,
        private readonly Security $security,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly SettingsManager $settingsManager,
        private readonly UploadFilenamePolicy $uploadFilenamePolicy,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CForum|JsonResponse
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $payload = $this->getJsonData($request);
        $this->validateCsrfToken($this->csrfTokenManager, $payload['csrfToken'] ?? null);
        $operationName = (string) $operation->getName();
        if ('toggle_forum_subscription' !== $operationName) {
            $this->assertTeacher($this->security);
        }

        if ('create_forum' === $operationName) {
            $course = $this->getCourse($this->entityManager, $request);
            $session = $this->getSession($this->entityManager, $request);
            $group = $this->getGroup($this->entityManager, $request);
            $parentResourceNodeId = $this->getRequiredInt($payload, 'parentResourceNodeId');
            $this->assertParentResourceNodeIsWritableInForumContext(
                $this->entityManager,
                $this->security,
                $parentResourceNodeId,
                $course,
                $session,
                $group,
            );

            $forum = (new CForum())
                ->setParentResourceNode($parentResourceNodeId)
                ->setResourceLinkArray($this->buildResourceLinkList($course, $session, $group))
            ;

            $learningPath = $this->getLearningPathFromPayload($payload, $course, $session, $group);
            $learningPathParentItemId = $this->getOptionalInt($payload, 'lpParentId');
            $this->applyPayloadToForum($forum, $payload, true, $course, $session, $group, $learningPath);
            $this->forumRepository->create($forum);
            $this->addForumToLearningPathIfNeeded($forum, $learningPath, $learningPathParentItemId);
            $subscribedUsers = $this->subscribeUsersToForumNotifications($this->entityManager, $course, $session, $forum);
            if ($subscribedUsers > 0) {
                $this->entityManager->flush();
            }
            $this->registerForumEventLog('new-forum', 'forum', (string) $forum->getIid());

            return $forum;
        }

        if (!$data instanceof CForum) {
            throw new BadRequestHttpException('Forum is required.');
        }

        return match ($operationName) {
            'toggle_forum_lock' => $this->toggleForumLock($data),
            'toggle_forum_visibility' => $this->toggleForumVisibility($data, $payload),
            'move_forum' => $this->moveForum($data, $payload),
            'toggle_forum_subscription' => $this->toggleForumSubscription($data, $payload),
            'upload_forum_image' => $this->uploadForumImage($data, $request, $payload),
            default => $this->updateForum($data, $payload),
        };
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function updateForum(CForum $forum, array $payload): CForum
    {
        $this->assertEditableForumResource($forum->getResourceNode(), $this->security);

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $course = $this->getCourse($this->entityManager, $request);
        $session = $this->getSession($this->entityManager, $request);
        $group = $this->getGroup($this->entityManager, $request);

        $this->applyPayloadToForum($forum, $payload, false, $course, $session, $group);
        $this->entityManager->persist($forum);
        $this->entityManager->flush();

        $this->registerForumEventLog('update-forum', 'forum', (string) $forum->getIid());

        return $forum;
    }

    private function toggleForumLock(CForum $forum): JsonResponse
    {
        $this->assertEditableForumResource($forum->getResourceNode(), $this->security);

        $forum->setLocked(0 === $forum->getLocked() ? 1 : 0);

        $this->entityManager->persist($forum);
        $this->entityManager->flush();

        $this->registerForumEventLog(1 === $forum->getLocked() ? 'lock-forum' : 'unlock-forum', 'forum', (string) $forum->getIid());

        return new JsonResponse([
            'id' => $forum->getIid(),
            'locked' => $forum->getLocked(),
            'message' => 1 === $forum->getLocked() ? 'Forum locked.' : 'Forum unlocked.',
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function toggleForumVisibility(CForum $forum, array $payload): JsonResponse
    {
        $this->assertEditableForumResource($forum->getResourceNode(), $this->security);

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $course = $this->getCourse($this->entityManager, $request);
        $session = $this->getSession($this->entityManager, $request);
        $targetVisible = $this->getTargetVisibility($payload, $forum, $course, $session);
        $visible = $this->setForumResourceVisibility($forum, $this->forumRepository, $course, $session, $targetVisible);
        $this->entityManager->flush();

        $this->registerForumEventLog($visible ? 'show-forum' : 'hide-forum', 'forum', (string) $forum->getIid());

        return new JsonResponse([
            'forumId' => $forum->getIid(),
            'visible' => $visible,
            'message' => $visible ? 'Forum shown.' : 'Forum hidden.',
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function moveForum(CForum $forum, array $payload): JsonResponse
    {
        $this->assertEditableForumResource($forum->getResourceNode(), $this->security);

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $course = $this->getCourse($this->entityManager, $request);
        $session = $this->getSession($this->entityManager, $request);
        $group = $this->getGroup($this->entityManager, $request);
        $position = $this->moveForumResource($forum, $course, $session, $group, (string) ($payload['direction'] ?? ''));
        $this->entityManager->flush();

        $this->registerForumEventLog('move-forum', 'forum', (string) $forum->getIid());

        return new JsonResponse([
            'forumId' => $forum->getIid(),
            'position' => $position,
            'message' => 'Forum moved.',
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function toggleForumSubscription(CForum $forum, array $payload): JsonResponse
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $course = $this->getCourse($this->entityManager, $request);
        if ($this->areForumPostNotificationsHidden($this->entityManager, $course)) {
            throw new AccessDeniedHttpException('Forum notifications are disabled for this course.');
        }

        $resourceNode = $forum->getResourceNode();
        if (null === $resourceNode || !$this->security->isGranted('VIEW', $resourceNode)) {
            throw new AccessDeniedHttpException('You are not allowed to access this forum.');
        }

        $category = $forum->getForumCategory();
        if ($category instanceof CForumCategory && null !== $category->getResourceNode() && !$this->security->isGranted('VIEW', $category->getResourceNode())) {
            throw new AccessDeniedHttpException('You are not allowed to access this forum category.');
        }

        $user = $this->getCurrentForumUser($this->security);
        $forumId = (int) $forum->getIid();
        $currentState = $this->isSubscribedToForum($this->entityManager, $course, $user, $forumId);
        $subscribed = $this->setForumSubscription(
            $this->entityManager,
            $course,
            $user,
            $forumId,
            $this->getRequestedSubscriptionState($payload, $currentState),
        );

        $this->entityManager->flush();

        return new JsonResponse([
            'forumId' => $forumId,
            'subscribed' => $subscribed,
            'message' => $subscribed ? 'Forum notifications enabled.' : 'Forum notifications disabled.',
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function uploadForumImage(CForum $forum, Request $request, array $payload): JsonResponse
    {
        $this->assertEditableForumResource($forum->getResourceNode(), $this->security);

        $uploadedImage = $this->getUploadedForumImage($request);
        $removeImage = $this->getBoolean($payload, 'removeImage');

        if (!$uploadedImage instanceof UploadedFile && !$removeImage) {
            throw new BadRequestHttpException('Forum image is missing.');
        }

        if ($removeImage) {
            $forum->setForumImage('');
        }

        if ($uploadedImage instanceof UploadedFile) {
            $forum->setForumImage($this->storeForumImage($forum, $uploadedImage));
        }

        $this->entityManager->persist($forum);
        $this->entityManager->flush();

        $this->registerForumEventLog('update-forum-image', 'forum', (string) $forum->getIid());

        return new JsonResponse([
            'forumId' => $forum->getIid(),
            'forumImage' => $forum->getForumImage(),
            'message' => '' === $forum->getForumImage() ? 'Forum image removed.' : 'Forum image updated.',
        ]);
    }

    private function getUploadedForumImage(Request $request): ?UploadedFile
    {
        $file = $request->files->get('image') ?? $request->files->get('forumImage') ?? $request->files->get('picture');

        return $file instanceof UploadedFile ? $file : null;
    }

    private function storeForumImage(CForum $forum, UploadedFile $file): string
    {
        if (!$file->isValid()) {
            throw new BadRequestHttpException('Invalid forum image upload.');
        }

        $this->assertAllowedForumImage($file);
        $this->forumRepository->addFile($forum, $file);
        $this->forumRepository->update($forum);

        $imageUrl = trim((string) $this->forumRepository->getResourceFileUrl($forum));
        if ('' === $imageUrl) {
            throw new BadRequestHttpException('Forum image URL could not be generated.');
        }

        return $imageUrl;
    }

    private function assertAllowedForumImage(UploadedFile $file): void
    {
        $originalName = $file->getClientOriginalName();
        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        if (!\in_array($extension, self::FORUM_IMAGE_ALLOWED_EXTENSIONS, true)) {
            throw new BadRequestHttpException('Only JPG, PNG, GIF, WEBP and BMP images are allowed.');
        }

        $mimeType = strtolower((string) ($file->getMimeType() ?: $file->getClientMimeType()));
        if (!str_starts_with($mimeType, 'image/') || \in_array($mimeType, ['image/svg', 'image/svg+xml'], true)) {
            throw new BadRequestHttpException('Only image files are allowed.');
        }

        $policy = $this->uploadFilenamePolicy->filter($originalName);
        if (false === ($policy['allowed'] ?? false)) {
            throw new BadRequestHttpException('File upload failed: this file extension or file type is prohibited.');
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function applyPayloadToForum(
        CForum $forum,
        array $payload,
        bool $isCreate,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        ?CLp $learningPath = null,
    ): void
    {
        $startTime = $this->getUtcDateTimeOrNull($payload['startTime'] ?? null);
        $endTime = $this->getUtcDateTimeOrNull($payload['endTime'] ?? null);
        if (null !== $startTime && null !== $endTime && $startTime >= $endTime) {
            throw new BadRequestHttpException('Start date must be before the end date.');
        }

        $forum
            ->setTitle($this->getRequiredText($payload, 'title', 255))
            ->setForumComment($this->getOptionalText($payload, 'comment'))
            ->setForumCategory($this->getCategory($this->getOptionalInt($payload, 'categoryId'), $course, $session, $group))
            ->setAllowEdit($this->getBooleanAsInt($payload, 'studentsCanEdit'))
            ->setApprovalDirectPost((string) $this->getBooleanAsInt($payload, 'requiresApproval'))
            ->setAllowAttachments($this->getBooleanAsInt($payload, 'allowAttachments', 1))
            ->setAllowNewThreads($this->getBooleanAsInt($payload, 'allowNewThreads', 1))
            ->setDefaultView($this->getDefaultView($payload, $forum, $isCreate))
            ->setForumOfGroup((string) $this->getOptionalInt($payload, 'groupForum'))
            ->setForumGroupPublicPrivate($this->getGroupVisibility($payload, $forum))
            ->setModerated($this->getBoolean($payload, 'moderated'))
            ->setLocked($this->getBooleanAsInt($payload, 'locked'))
            ->setStartTime($startTime)
            ->setEndTime($endTime)
        ;

        if ($isCreate) {
            $forum
                ->setAllowAnonymous(0)
                ->setLp($learningPath)
            ;
        }
    }

    private function addForumToLearningPathIfNeeded(
        CForum $forum,
        ?CLp $learningPath,
        int $parentItemId,
    ): void
    {
        if (!$learningPath instanceof CLp) {
            return;
        }

        $forumId = (int) $forum->getIid();
        if ($forumId <= 0) {
            return;
        }

        $existingItem = $this->entityManager->getRepository(CLpItem::class)->findOneBy([
            'lp' => $learningPath,
            'itemType' => 'forum',
            'path' => (string) $forumId,
        ]);

        if ($existingItem instanceof CLpItem) {
            return;
        }

        $rootItem = $this->getLearningPathRootItem($learningPath);
        if (!$rootItem instanceof CLpItem) {
            throw new NotFoundHttpException('Learning path root item not found.');
        }

        $parentItem = $this->getLearningPathParentItem($learningPath, $parentItemId, $rootItem);
        $learningPathItem = (new CLpItem())
            ->setLp($learningPath)
            ->setRoot($rootItem)
            ->setParent($parentItem)
            ->setItemType('forum')
            ->setTitle($forum->getTitle())
            ->setDescription($forum->getForumComment())
            ->setPath((string) $forumId)
            ->setRef((string) $forumId)
            ->setMinScore(0.0)
            ->setMaxScore(100.0)
            ->setLaunchData('')
            ->setDisplayOrder($this->getNextLearningPathItemDisplayOrder($learningPath))
        ;

        $this->entityManager->persist($learningPathItem);
        $this->entityManager->flush();
    }

    private function getLearningPathParentItem(
        CLp $learningPath,
        int $parentItemId,
        CLpItem $rootItem,
    ): CLpItem
    {
        if ($parentItemId <= 0) {
            return $rootItem;
        }

        $parentItem = $this->entityManager->getRepository(CLpItem::class)->find($parentItemId);
        if (!$parentItem instanceof CLpItem
            || $parentItem->getLp()->getIid() !== $learningPath->getIid()
            || 'dir' !== $parentItem->getItemType()
        ) {
            throw new BadRequestHttpException('The selected learning path parent is invalid.');
        }

        return $parentItem;
    }

    private function getLearningPathRootItem(CLp $learningPath): ?CLpItem
    {
        return $this->entityManager->getRepository(CLpItem::class)->findOneBy([
            'lp' => $learningPath,
            'itemType' => 'root',
            'path' => 'root',
        ]);
    }

    private function getNextLearningPathItemDisplayOrder(CLp $learningPath): int
    {
        $maxDisplayOrder = $this->entityManager->getRepository(CLpItem::class)
            ->createQueryBuilder('item')
            ->select('COALESCE(MAX(item.displayOrder), 0)')
            ->andWhere('item.lp = :learningPath')
            ->setParameter('learningPath', (int) $learningPath->getIid())
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return ((int) $maxDisplayOrder) + 1;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function getLearningPathFromPayload(array $payload, Course $course, ?Session $session, ?CGroup $group): ?CLp
    {
        $lpId = $this->getOptionalInt($payload, 'lpId');
        if ($lpId <= 0) {
            return null;
        }

        $learningPath = $this->entityManager->getRepository(CLp::class)->find($lpId);
        if (!$learningPath instanceof CLp) {
            throw new NotFoundHttpException('Learning path not found.');
        }

        $this->assertEditableResourceNodeInForumContext(
            $learningPath->getResourceNode(),
            $this->security,
            $course,
            $session,
            $group,
            'The selected learning path does not belong to this context.',
        );

        return $learningPath;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function getDefaultView(array $payload, CForum $forum, bool $isCreate): string
    {
        $fallback = $isCreate
            ? (string) ($this->settingsManager->getSetting('forum.default_forum_view', true) ?? 'flat')
            : (string) ($forum->getDefaultView() ?? 'flat');
        $defaultView = (string) ($payload['defaultView'] ?? $fallback);

        return \in_array($defaultView, ['flat', 'threaded', 'nested'], true) ? $defaultView : 'flat';
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function getGroupVisibility(array $payload, CForum $forum): string
    {
        $visibility = (string) ($payload['groupVisibility'] ?? $forum->getForumGroupPublicPrivate() ?: 'public');

        return \in_array($visibility, ['public', 'private'], true) ? $visibility : 'public';
    }

    private function getCategory(int $categoryId, Course $course, ?Session $session, ?CGroup $group): ?CForumCategory
    {
        if ($categoryId <= 0) {
            return null;
        }

        $category = $this->entityManager->getRepository(CForumCategory::class)->find($categoryId);
        if (!$category instanceof CForumCategory) {
            throw new NotFoundHttpException('Forum category not found.');
        }

        $this->assertEditableResourceNodeInForumContext(
            $category->getResourceNode(),
            $this->security,
            $course,
            $session,
            $group,
            'The selected forum category does not belong to this context.',
        );

        return $category;
    }
}
