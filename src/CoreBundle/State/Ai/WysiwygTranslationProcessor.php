<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Ai;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Ai\WysiwygTranslation;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Service\Ai\WysiwygTranslationService;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/** @implements ProcessorInterface<WysiwygTranslation, WysiwygTranslation> */
final readonly class WysiwygTranslationProcessor implements ProcessorInterface
{
    use WysiwygTranslationAccessHelperTrait;

    public function __construct(
        private CidReqHelper $cidReqHelper,
        private Security $security,
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

        $course = $this->resolveCourseAndAssertAccess($this->security);
        if (!$this->translationService->isEnabled()) {
            throw new AccessDeniedHttpException('AI WYSIWYG translation is disabled.');
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
                sessionId: (int) ($this->cidReqHelper->getSessionId() ?? 0),
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
}
