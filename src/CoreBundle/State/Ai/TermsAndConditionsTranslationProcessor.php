<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Ai;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Ai\TermsAndConditionsTranslation;
use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CoreBundle\Service\Ai\TermsAndConditionsTranslationService;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/** @implements ProcessorInterface<TermsAndConditionsTranslation, TermsAndConditionsTranslation> */
final readonly class TermsAndConditionsTranslationProcessor implements ProcessorInterface
{
    public function __construct(
        private LanguageRepository $languageRepository,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private TermsAndConditionsTranslationService $translationService,
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
    ): TermsAndConditionsTranslation {
        if (!$data instanceof TermsAndConditionsTranslation) {
            throw new BadRequestHttpException('The terms and conditions translation payload is invalid.');
        }
        if (!$this->translationService->isEnabled()) {
            throw new AccessDeniedHttpException('AI translation of terms and conditions is disabled.');
        }
        if (!$this->csrfTokenManager->isTokenValid(
            new CsrfToken(TermsAndConditionsTranslationService::CSRF_TOKEN_ID, $data->csrfToken)
        )) {
            throw new AccessDeniedHttpException('The security token is invalid.');
        }

        $sourceLanguage = $this->languageRepository->find($data->sourceLanguageId);
        if (!$sourceLanguage instanceof Language) {
            throw new BadRequestHttpException('The source language was not found.');
        }

        $targetLanguage = $this->languageRepository->find($data->targetLanguageId);
        if (!$targetLanguage instanceof Language || !$targetLanguage->getAvailable()) {
            throw new BadRequestHttpException('The target language is not active on this platform.');
        }
        if ($sourceLanguage->getId() === $targetLanguage->getId()) {
            throw new BadRequestHttpException('The source and target languages must be different.');
        }

        try {
            $sections = $this->translationService->translateSections(
                sections: $data->sections,
                sourceLanguage: $sourceLanguage,
                targetLanguage: $targetLanguage,
                provider: $data->provider,
            );
        } catch (RuntimeException $exception) {
            throw new UnprocessableEntityHttpException($exception->getMessage(), $exception);
        }

        $result = new TermsAndConditionsTranslation();
        $result->enabled = true;
        $result->targetLanguageId = (int) $targetLanguage->getId();
        $result->sections = $sections;

        return $result;
    }
}
