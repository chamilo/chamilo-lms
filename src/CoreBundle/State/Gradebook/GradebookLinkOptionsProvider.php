<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookLinkOptions;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Service\Gradebook\GradebookLinkManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProviderInterface<GradebookLinkOptions>
 */
final readonly class GradebookLinkOptionsProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private GradebookContextResolver $contextResolver,
        private GradebookLinkManager $linkManager,
        private GradebookLinkResourceResolver $resourceResolver,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): GradebookLinkOptions
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $resolved = $this->contextResolver->resolve($request, true, false);
        $course = $resolved['course'];
        $session = $resolved['session'];
        $this->validateRequestedNode($request, $course, $session);
        $rootCategory = $resolved['rootCategory'] ?? $this->linkManager->getRootCategory($course, $session, true);
        if (!$rootCategory instanceof GradebookCategory) {
            throw new NotFoundHttpException('The Gradebook was not found.');
        }

        $categoryId = $request->query->getInt('categoryId');
        $category = $categoryId > 0
            ? $this->linkManager->requireCategory($course, $session, $categoryId, true)
            : $this->linkManager->requireCategory($course, $session, (int) $rootCategory->getId(), true);

        if (null !== $category->getGradeModel()) {
            throw new AccessDeniedHttpException('The selected Gradebook category cannot be modified.');
        }

        $result = new GradebookLinkOptions();
        $result->types = $this->resourceResolver->getAvailableTypes($course, $session);
        $result->categories = $this->linkManager->getCategoryOptions($course, $session);
        $result->context = [
            'cid' => (int) $course->getId(),
            'sid' => null !== $session ? (int) $session->getId() : 0,
            'gid' => (int) $resolved['groupId'],
            'node' => $request->query->getInt('node'),
        ];
        $result->csrfToken = $this->csrfTokenManager
            ->getToken(GradebookLinkActionProcessor::CSRF_TOKEN_ID)
            ->getValue()
        ;

        $link = $this->resolveRequestedLink($request, $course, $session);
        if ($link instanceof GradebookLink) {
            $normalized = $this->resourceResolver->normalizeLink(
                $link,
                $course,
                $session,
                (int) $resolved['groupId'],
                true,
            );
            $result->link = [
                ...$normalized,
                'categoryId' => (int) $link->getCategory()->getId(),
            ];
        }

        return $result;
    }

    private function validateRequestedNode(Request $request, Course $course, ?Session $session): void
    {
        $nodeId = $request->query->getInt('node');
        if ($nodeId <= 0) {
            throw new BadRequestHttpException('A valid resource node id is required.');
        }

        $courseNodeId = (int) ($course->getResourceNode()?->getId() ?? 0);
        if ($courseNodeId > 0 && $nodeId === $courseNodeId) {
            return;
        }

        $type = $request->query->getInt('type');
        $refId = $request->query->getInt('refId');
        if ($type <= 0 || $refId <= 0) {
            throw new AccessDeniedHttpException('The requested resource node does not belong to the current Gradebook context.');
        }

        $resource = $this->resourceResolver->requireResource($type, $refId, $course, $session);
        if (!method_exists($resource, 'getResourceNode')) {
            throw new AccessDeniedHttpException('The linked resource has no valid resource node.');
        }

        $resourceNode = $resource->getResourceNode();
        if (null === $resourceNode || (int) $resourceNode->getId() !== $nodeId) {
            throw new AccessDeniedHttpException('The requested resource node does not match the linked course resource.');
        }
    }

    private function resolveRequestedLink(Request $request, Course $course, ?Session $session): ?GradebookLink
    {
        $linkId = $request->query->getInt('linkId');
        if ($linkId > 0) {
            return $this->linkManager->getLinkById($course, $session, $linkId);
        }

        $type = $request->query->getInt('type');
        $refId = $request->query->getInt('refId');
        if ($type > 0 && $refId > 0) {
            $this->resourceResolver->requireResource($type, $refId, $course, $session);

            return $this->linkManager->findLink($course, $session, $type, $refId);
        }

        return null;
    }
}
