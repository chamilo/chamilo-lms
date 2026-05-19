<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post as PostOp;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CourseBundle\Entity\CBlog;
use Chamilo\CourseBundle\Entity\CBlogComment;
use Chamilo\CourseBundle\Entity\CBlogPost;
use Chamilo\CourseBundle\Entity\CBlogTask;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Assigns the current authenticated user as author on POST if none provided.
 *
 * @implements ProcessorInterface<CBlogPost|CBlogComment|CBlogTask, CBlogPost|CBlogComment|CBlogTask>
 */
final readonly class CBlogAssignAuthorProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $persistProcessor,
        private UserHelper $userHelper,
        private Security $security,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * @param CBlogPost|CBlogComment|CBlogTask|null $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $isCreate = $operation instanceof PostOp;

        if ($isCreate && ($data instanceof CBlogPost || $data instanceof CBlogComment || $data instanceof CBlogTask)) {
            /** @var User|null $user */
            $user = $this->userHelper->getCurrent();

            // Extra: for comments, if blog is missing, inherit from post
            if ($data instanceof CBlogComment && null === $data->getBlog() && $data->getPost()) {
                $data->setBlog($data->getPost()->getBlog());
            }

            $this->assertCanWriteToTargetBlog($data);

            if ($user instanceof User) {
                if (method_exists($data, 'getAuthor') && method_exists($data, 'setAuthor') && null === $data->getAuthor()) {
                    // Use a managed reference so Doctrine does not treat the
                    // security-token User (often DETACHED) as a NEW entity.
                    $managedUser = $this->entityManager->getReference(User::class, $user->getId());

                    $data->setAuthor($managedUser);
                }
            }
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }

    /**
     * Defense-in-depth check executed before persistence: ensures the current user
     * has at least VIEW access on the target blog's resource node, so a comment,
     * post or task cannot be created against a blog the user cannot reach.
     */
    private function assertCanWriteToTargetBlog(CBlogPost|CBlogComment|CBlogTask $data): void
    {
        $blog = $data->getBlog();

        if (!$blog instanceof CBlog) {
            throw new AccessDeniedHttpException('Target blog is required.');
        }

        $resourceNode = $blog->getResourceNode();
        if (null === $resourceNode) {
            throw new AccessDeniedHttpException('Target blog is not accessible.');
        }

        if (!$this->security->isGranted('VIEW', $resourceNode)) {
            throw new AccessDeniedHttpException('You are not allowed to write to this blog.');
        }
    }
}
