<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathCategoryInput;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/** @implements ProcessorInterface<LearningPathCategoryInput|null, LearningPathCategoryInput|null> */
final readonly class LearningPathCategoryMutationProcessor implements ProcessorInterface
{
    use LearningPathStateHelperTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?LearningPathCategoryInput
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('Request is missing.');
        }
        $this->assertLearningPathTeacher($this->security);
        $course = $this->getContextCourse($this->entityManager, $request);
        $session = $this->getContextSession($this->entityManager, $request, $course);
        $group = $this->getContextGroup($this->entityManager, $request, $course);
        $categoryId = (int) ($uriVariables['categoryId'] ?? 0);
        $category = 0 < $categoryId ? $this->entityManager->getRepository(CLpCategory::class)->find($categoryId) : new CLpCategory();
        if (!$category instanceof CLpCategory) {
            throw new NotFoundHttpException('Learning path category not found.');
        }
        if ($operation instanceof DeleteOperationInterface) {
            $payload = $this->getJsonData($request);
            $this->validateActionToken($this->csrfTokenManager, $payload['csrfToken'] ?? null);
            $this->getEditableResourceLink($category, $course, $session, $group, $this->security);
            if (0 < $category->getLps()->count()) {
                throw new BadRequestHttpException('A non-empty learning path category cannot be deleted.');
            }
            $this->entityManager->remove($category);
            $this->entityManager->flush();

            return null;
        }
        if (!$data instanceof LearningPathCategoryInput) {
            throw new BadRequestHttpException('Learning path category data is required.');
        }
        $this->validateActionToken($this->csrfTokenManager, $data->csrfToken);
        if (0 < $categoryId) {
            $this->getEditableResourceLink($category, $course, $session, $group, $this->security);
        } else {
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
        $data->id = $category->getIid();

        return $data;
    }
}
