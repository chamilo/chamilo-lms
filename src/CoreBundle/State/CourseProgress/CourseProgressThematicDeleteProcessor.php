<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseProgress;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\CourseProgress\CourseProgressThematic;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\ResourceLinkRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CThematic;
use Chamilo\CourseBundle\Repository\CThematicRepository;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

use const JSON_THROW_ON_ERROR;

/**
 * @implements ProcessorInterface<CourseProgressThematic, void>
 */
final readonly class CourseProgressThematicDeleteProcessor implements ProcessorInterface
{
    use CourseProgressAccessHelperTrait;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CThematicRepository $thematicRepository,
        private ResourceLinkRepository $resourceLinkRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getCourseProgressCourse($request, $this->entityManager);
        $this->assertCourseProgressToolEnabled($this->entityManager, $course);
        $session = $this->getCourseProgressSession($request, $this->entityManager);
        $this->assertSessionBelongsToCourse($session, $course);

        if ($this->isCourseProgressStudentView($request, (int) $course->getId())
            || !$this->canManageCourseProgress(
                $this->entityManager,
                $this->security,
                $this->settingsManager,
                $course,
                $session,
            )
        ) {
            throw new AccessDeniedHttpException('You are not allowed to delete course progress thematics in this context.');
        }

        $this->validateCsrfToken($this->getSubmittedCsrfToken($request));

        $thematicId = isset($uriVariables['iid']) ? (int) $uriVariables['iid'] : 0;
        $thematic = $this->getDeletableThematic($thematicId, $course, $session);

        $this->resourceLinkRepository->removeByResourceInContext($thematic, $course, $session);
    }

    private function getDeletableThematic(int $thematicId, Course $course, ?Session $session): CThematic
    {
        if ($thematicId <= 0) {
            throw new BadRequestHttpException('A valid thematic id is required.');
        }

        $thematic = $this->thematicRepository->find($thematicId);
        if (!$thematic instanceof CThematic) {
            throw new NotFoundHttpException('The requested thematic was not found.');
        }

        if (!$this->thematicBelongsToExactContext($thematic, $course, $session)) {
            throw new AccessDeniedHttpException('The requested thematic does not belong to the current course context.');
        }

        $resourceNode = $thematic->getResourceNode();
        if (null === $resourceNode || !$this->security->isGranted('DELETE', $resourceNode)) {
            throw new AccessDeniedHttpException('You are not allowed to delete this thematic.');
        }

        return $thematic;
    }

    private function getSubmittedCsrfToken(Request $request): string
    {
        $content = trim($request->getContent());
        if ('' === $content) {
            return '';
        }

        try {
            $payload = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new BadRequestHttpException('The request payload is invalid.');
        }

        if (!\is_array($payload)) {
            return '';
        }

        $token = $payload['csrfToken'] ?? '';

        return \is_string($token) ? $token : '';
    }

    private function validateCsrfToken(string $token): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(CourseProgressThematicProvider::CSRF_TOKEN_ID, $token))) {
            throw new AccessDeniedHttpException('The security token is invalid.');
        }
    }
}
