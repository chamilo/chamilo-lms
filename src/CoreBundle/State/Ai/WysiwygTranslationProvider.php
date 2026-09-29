<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Ai;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Ai\WysiwygTranslation;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Service\Ai\WysiwygTranslationService;
use Symfony\Bundle\SecurityBundle\Security;

/** @implements ProviderInterface<WysiwygTranslation> */
final readonly class WysiwygTranslationProvider implements ProviderInterface
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
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): WysiwygTranslation {
        $course = $this->resolveCourseAndAssertAccess($this->security);
        $languages = $this->translationService->getActiveLanguages();

        $result = new WysiwygTranslation();
        $result->enabled = $this->translationService->isEnabled();
        $result->sourceLanguage = $this->translationService->getSourceLanguage($course);
        $result->languages = $this->toOptions($languages);
        $result->allowAllLanguages = $this->translationService->isAllLanguagesAllowed();

        if ($result->enabled) {
            $result->providers = $this->translationService->getProviderOptions();
        }

        return $result;
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
