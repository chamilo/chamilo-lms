<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Ai;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Ai\TermsAndConditionsTranslation;
use Chamilo\CoreBundle\Service\Ai\TermsAndConditionsTranslationService;

/** @implements ProviderInterface<TermsAndConditionsTranslation> */
final readonly class TermsAndConditionsTranslationProvider implements ProviderInterface
{
    public function __construct(
        private TermsAndConditionsTranslationService $translationService,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): TermsAndConditionsTranslation {
        $result = new TermsAndConditionsTranslation();
        $result->enabled = $this->translationService->isEnabled();
        $result->languages = $this->translationService->getActiveLanguageOptions();

        if ($result->enabled) {
            $result->providers = $this->translationService->getProviderOptions();
        }

        return $result;
    }
}
