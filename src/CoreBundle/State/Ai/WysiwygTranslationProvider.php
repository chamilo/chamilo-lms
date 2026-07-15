<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Ai;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Ai\WysiwygTranslation;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\CoreBundle\Service\Ai\WysiwygTranslationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/** @implements ProviderInterface<WysiwygTranslation> */
final readonly class WysiwygTranslationProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private WysiwygTranslationService $translationService,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): WysiwygTranslation {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->resolveCourseAndAssertAccess($request);
        $languages = $this->translationService->getActiveLanguages();

        $result = new WysiwygTranslation();
        $result->enabled = $this->translationService->isEnabled();
        $result->sourceLanguage = $this->translationService->getSourceLanguage($course);
        $result->languages = $this->toOptions($languages);
        $result->allowAllLanguages = $this->translationService->isAllLanguagesAllowed();
        $result->csrfToken = (string) $this->csrfTokenManager->getToken(WysiwygTranslationService::CSRF_TOKEN_ID);

        if ($result->enabled) {
            $result->providers = $this->translationService->getProviderOptions();
        }

        return $result;
    }

    private function resolveCourseAndAssertAccess(Request $request): ?Course
    {
        $courseId = $request->query->getInt('cid');
        if ($courseId <= 0) {
            if (!$this->security->isGranted('ROLE_ADMIN')) {
                throw new AccessDeniedHttpException('Only administrators may use AI WYSIWYG translation outside a course.');
            }

            return null;
        }

        $course = $this->entityManager->find(Course::class, $courseId);
        if (!$course instanceof Course) {
            throw new NotFoundHttpException('The course was not found.');
        }
        if (!$this->security->isGranted(CourseVoter::EDIT, $course)
            && !$this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
        ) {
            throw new AccessDeniedHttpException('You are not allowed to translate content in this course.');
        }

        return $course;
    }

    /**
     * @param array<string, string> $values
     *
     * @return array<int, array{label: string, value: string}>
     */
    private function toOptions(array $values): array
    {
        $options = [];
        foreach ($values as $value => $label) {
            $options[] = [
                'label' => $label,
                'value' => $value,
            ];
        }

        return $options;
    }
}
