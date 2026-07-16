<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Wiki;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Wiki\WikiCategoryInput;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CWikiCategory;
use Chamilo\CourseBundle\Repository\CWikiCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Throwable;

/** @implements ProcessorInterface<WikiCategoryInput, void> */
final readonly class WikiCategoryProcessor implements ProcessorInterface
{
    use WikiAccessHelperTrait;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CWikiCategoryRepository $categoryRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private WikiCategoryService $categoryService,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (!$data instanceof WikiCategoryInput) {
            throw new BadRequestHttpException('The Wiki category payload is invalid.');
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

        if ($this->isWikiStudentView($request)) {
            throw new AccessDeniedHttpException('Wiki categories cannot be managed in student view.');
        }

        if (!$this->canManageWikiContext(
            $this->entityManager,
            $this->security,
            $this->settingsManager,
            $course,
            $session,
            null,
        )) {
            throw new AccessDeniedHttpException('You are not allowed to manage Wiki categories.');
        }

        if (!$this->isWikiCourseSettingEnabled(
            $this->entityManager,
            $course,
            'wiki_categories_enabled',
            false,
        )) {
            throw new AccessDeniedHttpException('Wiki categories are disabled for this course.');
        }

        $this->validateCsrfToken($data->csrfToken);
        $categoryId = isset($uriVariables['categoryId']) ? (int) $uriVariables['categoryId'] : 0;
        $operationName = (string) $operation->getName();

        if ('post_wiki_category_delete' === $operationName) {
            $category = $this->getCategory($categoryId, $course, $session);
            $connection = $this->entityManager->getConnection();
            $connection->beginTransaction();

            try {
                $this->entityManager->remove($category);
                $this->entityManager->flush();
                $connection->commit();
            } catch (Throwable $throwable) {
                $connection->rollBack();

                throw $throwable;
            }

            return;
        }

        $title = trim(strip_tags($data->title));
        if ('' === $title) {
            throw new BadRequestHttpException('The Wiki category title is required.');
        }

        if (mb_strlen($title) > 255) {
            throw new BadRequestHttpException('The Wiki category title cannot exceed 255 characters.');
        }

        $category = $categoryId > 0
            ? $this->getCategory($categoryId, $course, $session)
            : (new CWikiCategory())->setCourse($course)->setSession($session);
        $parent = null;

        if (null !== $data->parentId && $data->parentId > 0) {
            $parent = $this->categoryRepository->findOneInContext((int) $data->parentId, $course, $session);
            if (!$parent instanceof CWikiCategory) {
                throw new BadRequestHttpException('The selected parent category is invalid for the current context.');
            }
        }

        $this->categoryService->assertParentDoesNotCreateCycle($category, $parent);
        $category
            ->setTitle($title)
            ->setParent($parent)
        ;

        $this->entityManager->persist($category);
        $this->entityManager->flush();
    }

    private function getCategory(int $categoryId, Course $course, ?Session $session): CWikiCategory
    {
        if ($categoryId <= 0) {
            throw new BadRequestHttpException('A valid Wiki category id is required.');
        }

        $category = $this->categoryRepository->findOneInContext($categoryId, $course, $session);
        if (!$category instanceof CWikiCategory) {
            throw new NotFoundHttpException('The requested Wiki category was not found in the current course context.');
        }

        return $category;
    }

    private function validateCsrfToken(string $token): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(WikiCategoryProvider::CSRF_TOKEN_ID, $token))) {
            throw new AccessDeniedHttpException('The security token is invalid.');
        }
    }
}
