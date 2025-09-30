<?php
/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post as PostOp;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CBlogComment;
use Chamilo\CourseBundle\Entity\CBlogPost;
use Chamilo\CourseBundle\Entity\CBlogTask;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Assigns current authenticated user as author on POST if none provided.
 */
final readonly class CBlogAssignAuthorProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $persistProcessor,
        private Security $security,
    ) {}

    /**
     * @param CBlogPost|CBlogComment|CBlogTask|null $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $isCreate = $operation instanceof PostOp;

        if ($isCreate && ($data instanceof CBlogPost || $data instanceof CBlogComment || $data instanceof CBlogTask)) {
            /** @var User|null $user */
            $user = $this->security->getUser();

            if ($user instanceof User) {
                if (method_exists($data, 'getAuthor') && method_exists($data, 'setAuthor') && null === $data->getAuthor()) {
                    $data->setAuthor($user);
                }
            }

            // Extra: for comments, if blog is missing, inherit from post
            if ($data instanceof CBlogComment && null === $data->getBlog() && $data->getPost()) {
                $data->setBlog($data->getPost()->getBlog());
            }
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
