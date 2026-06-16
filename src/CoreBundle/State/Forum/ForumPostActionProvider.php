<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Forum;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Repository\CForumPostRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides forum posts for action operations without API Platform eager loading.
 *
 * @implements ProviderInterface<CForumPost|null>
 */
final readonly class ForumPostActionProvider implements ProviderInterface
{
    public function __construct(
        private CForumPostRepository $postRepository,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?CForumPost
    {
        $postId = $uriVariables['iid'] ?? null;
        if (null === $postId || !is_numeric($postId)) {
            throw new NotFoundHttpException('Forum post not found.');
        }

        $post = $this->postRepository->find((int) $postId);
        if (!$post instanceof CForumPost) {
            throw new NotFoundHttpException('Forum post not found.');
        }

        return $post;
    }
}
