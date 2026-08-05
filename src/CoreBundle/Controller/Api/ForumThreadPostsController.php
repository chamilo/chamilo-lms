<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\State\Forum\ForumThreadPostsStateProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ForumThreadPostsController
{
    public function __construct(
        private readonly ForumThreadPostsStateProvider $threadPostsProvider,
    ) {}

    #[Route('/api/forum_threads/{threadId}/posts', name: 'api_forum_thread_posts', methods: ['GET'], priority: 20)]
    public function __invoke(int $threadId, Request $request): JsonResponse
    {
        return new JsonResponse($this->threadPostsProvider->getThreadPosts($threadId, $request));
    }
}
