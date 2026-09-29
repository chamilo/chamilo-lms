<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProcessorInterface<object, JsonResponse>
 */
final readonly class LearningPathVisibilityProcessor implements ProcessorInterface
{
    use LearningPathStateHelperTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private Security $security,
        private CidReqHelper $cidReqHelper,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): JsonResponse
    {
        if (!$data instanceof CLp && !$data instanceof CLpCategory) {
            throw new BadRequestHttpException('Learning path resource is required.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $payload = $this->getJsonData($request);

        if ($data instanceof CLp && 1 === $data->getSubscribeUsers()) {
            throw new BadRequestHttpException('Visibility is managed by learning path subscriptions.');
        }
        $this->assertLearningPathTeacher($this->security);

        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $group = $this->getContextGroup($this->entityManager, $this->cidReqHelper, $course);
        $resourceLink = $this->getEditableResourceLink($data, $course, $session, $group, $this->security);
        $visible = $this->getTargetVisibility($payload, $resourceLink);

        $resourceLink->setVisibility(
            $visible ? ResourceLink::VISIBILITY_PUBLISHED : ResourceLink::VISIBILITY_DRAFT,
        );
        $this->entityManager->flush();

        $data->setVisible($visible);

        return new JsonResponse([
            'id' => (int) $data->getIid(),
            'visible' => $visible,
        ]);
    }
}
