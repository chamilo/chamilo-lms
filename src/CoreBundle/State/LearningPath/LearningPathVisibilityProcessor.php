<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

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
        private CsrfTokenManagerInterface $csrfTokenManager,
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
        $this->validateActionToken($this->csrfTokenManager, $payload['csrfToken'] ?? null);

        if ($data instanceof CLp && 1 === $data->getSubscribeUsers()) {
            throw new BadRequestHttpException('Visibility is managed by learning path subscriptions.');
        }
        $this->assertLearningPathTeacher($this->security);

        $course = $this->getContextCourse($this->entityManager, $request);
        $session = $this->getContextSession($this->entityManager, $request, $course);
        $group = $this->getContextGroup($this->entityManager, $request, $course);
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
