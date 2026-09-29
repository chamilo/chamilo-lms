<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Forum;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Repository\ResourceLinkRepository;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumCategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        private readonly Security $security,
        private readonly CidReqHelper $cidReqHelper,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $this->assertTeacher($this->security);

        if (!$data instanceof CForum && !$data instanceof CForumCategory) {
            throw new BadRequestHttpException('Forum resource is required.');
        }

        $course = $this->getCourse($this->cidReqHelper);
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $group = $this->getGroup($this->entityManager, $this->cidReqHelper);
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
