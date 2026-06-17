<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Forum;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Repository\ResourceLinkRepository;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumCategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Deletes the forum resource link in the current course/session/group context.
 *
 * @implements ProcessorInterface<CForum|CForumCategory, void>
 */
final class ForumDeleteProcessor implements ProcessorInterface
{
    use ForumStateHelperTrait;
    use ForumWriteHelperTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ResourceLinkRepository $resourceLinkRepository,
        private readonly RequestStack $requestStack,
        private readonly Security $security,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $payload = $this->getJsonData($request);
        $this->validateCsrfToken($this->csrfTokenManager, $payload['csrfToken'] ?? null);
        $this->assertTeacher($this->security);

        if (!$data instanceof CForum && !$data instanceof CForumCategory) {
            throw new BadRequestHttpException('Forum resource is required.');
        }

        $course = $this->getCourse($this->entityManager, $request);
        $session = $this->getSession($this->entityManager, $request);
        $group = $this->getGroup($this->entityManager, $request);
        $resourceNode = $data->getResourceNode();

        if (null === $resourceNode) {
            throw new NotFoundHttpException('Forum resource node not found.');
        }

        $resourceLink = $resourceNode->getResourceLinkByContext($course, $session, $group);
        if (!$resourceLink instanceof ResourceLink) {
            throw new NotFoundHttpException('Forum resource link not found in this context.');
        }

        $this->resourceLinkRepository->removeByResourceInContext($data, $course, $session, $group);
    }
}
