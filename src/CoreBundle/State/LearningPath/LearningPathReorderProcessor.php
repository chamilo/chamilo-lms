<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProcessorInterface<mixed, JsonResponse>
 */
final readonly class LearningPathReorderProcessor implements ProcessorInterface
{
    use LearningPathStateHelperTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CLpRepository $learningPathRepository,
        private RequestStack $requestStack,
        private Security $security,
        private CidReqHelper $cidReqHelper,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): JsonResponse
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $payload = $this->getJsonData($request);
        $this->assertLearningPathTeacher($this->security);

        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $group = $this->getContextGroup($this->entityManager, $this->cidReqHelper, $course);

        $order = $payload['order'] ?? $payload['ids'] ?? null;
        if (!\is_array($order) || [] === $order) {
            throw new BadRequestHttpException('The learning path order is required.');
        }

        $orderedIds = array_values(array_map(static fn (mixed $value): int => (int) $value, $order));
        if (\count($orderedIds) !== \count(array_unique($orderedIds)) || min($orderedIds) <= 0) {
            throw new BadRequestHttpException('The learning path order contains invalid identifiers.');
        }

        $categoryId = null;
        if (\array_key_exists('categoryId', $payload) && null !== $payload['categoryId'] && '' !== $payload['categoryId']) {
            $categoryId = (int) $payload['categoryId'];
            if ($categoryId <= 0) {
                throw new BadRequestHttpException('Invalid category id.');
            }
        }

        try {
            $this->learningPathRepository->reorderByIds(
                (int) $course->getId(),
                $session?->getId(),
                $orderedIds,
                $categoryId,
                $group?->getIid(),
            );
        } catch (InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage(), $exception);
        }

        return new JsonResponse(null, 204);
    }
}
