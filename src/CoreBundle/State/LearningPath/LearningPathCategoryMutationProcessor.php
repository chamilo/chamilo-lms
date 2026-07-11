<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathCategoryInput;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ResourceLinkRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CourseBundle\Repository\CShortcutRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/** @implements ProcessorInterface<LearningPathCategoryInput|null, void> */
final readonly class LearningPathCategoryMutationProcessor implements ProcessorInterface
{
    use LearningPathStateHelperTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private CShortcutRepository $shortcutRepository,
        private ResourceLinkRepository $resourceLinkRepository,
        private SettingsManager $settingsManager,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('Request is missing.');
        }
        $this->assertLearningPathTeacher($this->security);
        $course = $this->getContextCourse($this->entityManager, $request);
        $session = $this->getContextSession($this->entityManager, $request, $course);
        $group = $this->getContextGroup($this->entityManager, $request, $course);
        $categoryId = (int) ($uriVariables['id'] ?? 0);
        $category = 0 < $categoryId ? $this->entityManager->getRepository(CLpCategory::class)->find($categoryId) : new CLpCategory();
        if (!$category instanceof CLpCategory) {
            throw new NotFoundHttpException('Learning path category not found.');
        }
        if (!$data instanceof LearningPathCategoryInput) {
            throw new BadRequestHttpException('Learning path category data is required.');
        }
        $this->validateActionToken($this->csrfTokenManager, $data->csrfToken);
        if (0 < $categoryId) {
            $this->assertExactCategoryContext($category, $course, $session, $group);

            if ('' !== $data->action) {
                $this->processManagementAction($category, $course, $session, $group, $data->action);

                return;
            }
        } else {
            if ($session instanceof Session && !$this->settingEnabled('lp.allow_session_lp_category')) {
                throw new AccessDeniedHttpException('Learning path categories are disabled in session context.');
            }

            $courseNode = $course->getResourceNode();
            if (null === $courseNode) {
                throw new BadRequestHttpException('Course resource node is missing.');
            }
            $category->setParentResourceNode((int) $courseNode->getId());
            $link = ['cid' => (int) $course->getId(), 'visibility' => 0];
            if (null !== $session) {
                $link['sid'] = (int) $session->getId();
            }
            if (null !== $group) {
                $link['gid'] = (int) $group->getIid();
            }
            $category->setResourceLinkArray([$link]);
        }
        $title = trim(strip_tags($data->title));
        if ('' === $title) {
            throw new BadRequestHttpException('Title is required.');
        }
        $category->setTitle($title);
        $this->entityManager->persist($category);
        $this->entityManager->flush();
    }

    private function processManagementAction(
        CLpCategory $category,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        string $action,
    ): void {
        if ('delete' === $action) {
            foreach ($category->getLps()->toArray() as $learningPath) {
                $learningPath->setCategory(null);
                $this->entityManager->persist($learningPath);
            }

            $this->shortcutRepository->removeShortCutFromCourse($category, $course);
            $this->resourceLinkRepository->removeByResourceInContext($category, $course, $session, $group);
            $this->entityManager->remove($category);
            $this->entityManager->flush();

            return;
        }

        if ('toggle_publish' !== $action) {
            throw new BadRequestHttpException('Unsupported learning path category management action.');
        }

        $shortcut = $this->shortcutRepository->findShortcutFromResourceInCourse($category, $course);
        if (null !== $shortcut) {
            $this->shortcutRepository->removeShortCutFromCourse($category, $course);

            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authenticated user is required.');
        }

        $this->shortcutRepository->addShortCut($category, $user, $course, $session);
    }

    private function settingEnabled(string $name): bool
    {
        $value = $this->settingsManager->getSetting($name);

        if (\is_bool($value)) {
            return $value;
        }

        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function assertExactCategoryContext(
        CLpCategory $category,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): void {
        $resourceNode = $category->getResourceNode();
        if (!$resourceNode instanceof ResourceNode || !$this->security->isGranted('EDIT', $resourceNode)) {
            throw new AccessDeniedHttpException('You are not allowed to edit this learning path category.');
        }

        $resourceLink = $resourceNode->getResourceLinkByContext($course, $session, $group);
        if (!$resourceLink instanceof ResourceLink) {
            throw new AccessDeniedHttpException('The learning path category is not owned by the current course context.');
        }
    }
}
