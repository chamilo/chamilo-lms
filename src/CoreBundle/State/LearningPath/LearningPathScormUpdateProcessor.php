<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Service\LearningPath\ScormPackageImporter;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/** @implements ProcessorInterface<mixed, JsonResponse> */
final readonly class LearningPathScormUpdateProcessor implements ProcessorInterface
{
    use LearningPathStateHelperTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private CLpRepository $learningPathRepository,
        private ScormPackageImporter $packageImporter,
    ) {}

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): JsonResponse {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $this->assertLearningPathTeacher($this->security);
        $this->validateActionToken($this->csrfTokenManager, $request->request->get('csrfToken'));

        $course = $this->getContextCourse($this->entityManager, $request);
        $requestedNodeId = $request->query->getInt('node');
        $courseNodeId = (int) ($course->getResourceNode()?->getId() ?? 0);
        if ($requestedNodeId > 0 && $requestedNodeId !== $courseNodeId) {
            throw new AccessDeniedHttpException('The requested resource node does not belong to this course.');
        }

        $session = $this->getContextSession($this->entityManager, $request, $course);
        $group = $this->getContextGroup($this->entityManager, $request, $course);

        $learningPathId = (int) ($uriVariables['lpId'] ?? 0);
        if ($learningPathId <= 0) {
            throw new BadRequestHttpException('Invalid learning path id.');
        }

        $learningPath = $this->learningPathRepository->find($learningPathId);
        if (!$learningPath instanceof CLp) {
            throw new NotFoundHttpException('Learning path not found.');
        }

        if (CLp::SCORM_TYPE !== $learningPath->getLpType()) {
            throw new BadRequestHttpException('Only SCORM learning paths can be updated.');
        }

        $resourceNode = $learningPath->getResourceNode();
        if (!$resourceNode instanceof ResourceNode || !$this->security->isGranted('EDIT', $resourceNode)) {
            throw new AccessDeniedHttpException('You are not allowed to update this learning path.');
        }

        $this->getEditableResourceLink(
            $learningPath,
            $course,
            $session,
            $group,
            $this->security,
        );

        $package = $request->files->get('package');
        if (!$package instanceof UploadedFile) {
            throw new BadRequestHttpException('A SCORM ZIP package is required.');
        }

        try {
            $this->packageImporter->update($package, $learningPath, $course, false);
        } catch (RuntimeException $exception) {
            throw new BadRequestHttpException($exception->getMessage(), $exception);
        }

        return new JsonResponse([
            'id' => (int) $learningPath->getIid(),
            'title' => $learningPath->getTitle(),
        ]);
    }
}
