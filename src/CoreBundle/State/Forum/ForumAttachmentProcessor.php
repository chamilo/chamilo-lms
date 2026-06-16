<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Forum;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumAttachment;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Repository\CForumAttachmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Handles forum attachment delete operations.
 *
 * @implements ProcessorInterface<CForumAttachment, JsonResponse>
 */
final class ForumAttachmentProcessor implements ProcessorInterface
{
    use ForumStateHelperTrait;
    use ForumWriteHelperTrait;

    public function __construct(
        private readonly CForumAttachmentRepository $attachmentRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly Security $security,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): JsonResponse
    {
        $request = $this->getCurrentRequest();
        $payload = $this->getJsonData($request);
        $this->validateCsrfToken($this->csrfTokenManager, $payload['csrfToken'] ?? null);

        if (!$data instanceof CForumAttachment) {
            throw new NotFoundHttpException('Forum attachment not found.');
        }

        $post = $data->getPost();
        $thread = $post->getThread();
        $forum = $post->getForum();
        if (!$thread instanceof CForumThread || !$forum instanceof CForum) {
            throw new NotFoundHttpException('Forum thread not found.');
        }

        $this->assertVisibleResource($data->getResourceNode());
        $this->assertVisibleResource($post->getResourceNode());
        $this->assertAttachmentCanBeDeleted($post, $forum, $thread);

        $attachmentId = (int) $data->getIid();
        $this->attachmentRepository->delete($data);
        $this->entityManager->flush();

        return new JsonResponse([
            'attachmentId' => $attachmentId,
            'deleted' => true,
            'message' => 'Attachment deleted.',
        ]);
    }

    private function assertVisibleResource(?ResourceNode $resourceNode): void
    {
        if (null === $resourceNode || !$this->security->isGranted('VIEW', $resourceNode)) {
            throw new AccessDeniedHttpException('You are not allowed to access this forum resource.');
        }
    }

    private function assertAttachmentCanBeDeleted(CForumPost $post, CForum $forum, CForumThread $thread): void
    {
        if ($this->isTeacher($this->security)) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User || $post->getUser()->getId() !== $user->getId()) {
            throw new AccessDeniedHttpException('You are not allowed to delete this attachment.');
        }

        if (1 !== (int) ($forum->getAllowEdit() ?? 0)) {
            throw new AccessDeniedHttpException('Deleting attachments is not allowed in this forum.');
        }

        $category = $forum->getForumCategory();
        if ((null !== $category && 0 !== $category->getLocked()) || 0 !== $forum->getLocked() || 0 !== $thread->getLocked()) {
            throw new AccessDeniedHttpException('The forum or thread is locked.');
        }
    }

    private function getCurrentRequest(): Request
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        return $request;
    }
}
