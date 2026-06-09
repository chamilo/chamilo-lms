<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CourseBundle\Entity\CForumCategory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Chamilo\CourseBundle\Repository\CForumCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProcessorInterface<mixed, CForumCategory|JsonResponse>
 */
final class ForumCategoryProcessor implements ProcessorInterface
{
    use ForumStateHelperTrait;
    use ForumWriteHelperTrait;
    use ForumActionStateHelperTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CForumCategoryRepository $categoryRepository,
        private readonly RequestStack $requestStack,
        private readonly Security $security,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CForumCategory|JsonResponse
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $payload = $this->getJsonData($request);
        $this->validateCsrfToken($this->csrfTokenManager, $payload['csrfToken'] ?? null);
        $this->assertTeacher($this->security);

        if ('create_forum_category' === $operation->getName()) {
            $course = $this->getCourse($this->entityManager, $request);
            $session = $this->getSession($this->entityManager, $request);
            $group = $this->getGroup($this->entityManager, $request);
            $parentResourceNodeId = $this->getRequiredInt($payload, 'parentResourceNodeId');

            $category = (new CForumCategory())
                ->setTitle($this->getRequiredText($payload, 'title', 255))
                ->setCatComment($this->getOptionalText($payload, 'comment'))
                ->setLocked($this->getBooleanAsInt($payload, 'locked'))
                ->setParentResourceNode($parentResourceNodeId)
                ->setResourceLinkArray($this->buildResourceLinkList($course, $session, $group))
            ;

            $this->categoryRepository->create($category);
            $this->registerForumEventLog('new-forumcategory', 'forumcategory', (string) $category->getIid());

            return $category;
        }

        if (!$data instanceof CForumCategory) {
            throw new BadRequestHttpException('Forum category is required.');
        }

        return match ((string) $operation->getName()) {
            'toggle_forum_category_lock' => $this->toggleCategoryLock($data),
            'toggle_forum_category_visibility' => $this->toggleCategoryVisibility($data, $payload),
            'move_forum_category' => $this->moveCategory($data, $payload),
            default => $this->updateCategory($data, $payload),
        };
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function updateCategory(CForumCategory $category, array $payload): CForumCategory
    {
        $category
            ->setTitle($this->getRequiredText($payload, 'title', 255))
            ->setCatComment($this->getOptionalText($payload, 'comment'))
            ->setLocked($this->getBooleanAsInt($payload, 'locked'))
        ;

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        $this->registerForumEventLog('update-forumcategory', 'forumcategory', (string) $category->getIid());

        return $category;
    }

    private function toggleCategoryLock(CForumCategory $category): JsonResponse
    {
        $category->setLocked(0 === $category->getLocked() ? 1 : 0);

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        $this->registerForumEventLog(1 === $category->getLocked() ? 'lock-forumcategory' : 'unlock-forumcategory', 'forumcategory', (string) $category->getIid());

        return new JsonResponse([
            'id' => $category->getIid(),
            'locked' => $category->getLocked(),
            'message' => 1 === $category->getLocked() ? 'Forum category locked.' : 'Forum category unlocked.',
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function toggleCategoryVisibility(CForumCategory $category, array $payload): JsonResponse
    {
        $this->assertEditableForumResource($category->getResourceNode(), $this->security);

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $course = $this->getCourse($this->entityManager, $request);
        $session = $this->getSession($this->entityManager, $request);
        $targetVisible = $this->getTargetVisibility($payload, $category, $course, $session);
        $visible = $this->setForumResourceVisibility($category, $this->categoryRepository, $course, $session, $targetVisible);
        $this->entityManager->flush();

        $this->registerForumEventLog($visible ? 'show-forumcategory' : 'hide-forumcategory', 'forumcategory', (string) $category->getIid());

        return new JsonResponse([
            'categoryId' => $category->getIid(),
            'visible' => $visible,
            'message' => $visible ? 'Forum category shown.' : 'Forum category hidden.',
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function moveCategory(CForumCategory $category, array $payload): JsonResponse
    {
        $this->assertEditableForumResource($category->getResourceNode(), $this->security);

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $course = $this->getCourse($this->entityManager, $request);
        $session = $this->getSession($this->entityManager, $request);
        $group = $this->getGroup($this->entityManager, $request);
        $position = $this->moveForumResource($category, $course, $session, $group, (string) ($payload['direction'] ?? ''));
        $this->entityManager->flush();

        $this->registerForumEventLog('move-forumcategory', 'forumcategory', (string) $category->getIid());

        return new JsonResponse([
            'categoryId' => $category->getIid(),
            'position' => $position,
            'message' => 'Forum category moved.',
        ]);
    }
}
