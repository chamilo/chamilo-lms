<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Ai;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Ai\WysiwygTranslation;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\CoreBundle\Service\Ai\WysiwygTranslationService;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/** @implements ProcessorInterface<WysiwygTranslation, WysiwygTranslation> */
final readonly class WysiwygTranslationProcessor implements ProcessorInterface
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
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): WysiwygTranslation {
        if (!$data instanceof WysiwygTranslation) {
            throw new BadRequestHttpException('The WYSIWYG translation payload is invalid.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->resolveCourseAndAssertAccess($request);
        if (!$this->translationService->isEnabled()) {
            throw new AccessDeniedHttpException('AI WYSIWYG translation is disabled.');
        }
        if (!$this->csrfTokenManager->isTokenValid(
            new CsrfToken(WysiwygTranslationService::CSRF_TOKEN_ID, $data->csrfToken)
        )) {
            throw new AccessDeniedHttpException('The security token is invalid.');
        }

        $requestedLanguages = array_values(array_unique(array_filter(array_map(
            static fn (mixed $language): string => trim((string) $language),
            $data->targetLanguages,
        ))));
        if ([] === $requestedLanguages) {
            throw new BadRequestHttpException('At least one target language is required.');
        }
        if (\count($requestedLanguages) > 1 && !$this->translationService->isAllLanguagesAllowed()) {
            throw new AccessDeniedHttpException('Translation to all active languages is disabled.');
        }

        $activeLanguages = $this->translationService->getActiveLanguages();
        $sourceLanguage = $this->translationService->getSourceLanguage($course);

        try {
            $translation = $this->translationService->translate(
                html: $data->html,
                sourceLanguage: $sourceLanguage,
                requestedLanguages: $requestedLanguages,
                activeLanguages: $activeLanguages,
                provider: $data->provider,
                courseId: (int) ($course?->getId() ?? 0),
                sessionId: $request->query->getInt('sid'),
            );
        } catch (RuntimeException $exception) {
            throw new UnprocessableEntityHttpException($exception->getMessage(), $exception);
        }

        $result = new WysiwygTranslation();
        $result->enabled = true;
        $result->sourceLanguage = $sourceLanguage;
        $result->html = $translation['html'];
        $result->addedLanguages = $translation['added'];
        $result->skippedLanguages = $translation['skipped'];

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
}
