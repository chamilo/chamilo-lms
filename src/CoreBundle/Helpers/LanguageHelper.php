<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Repository\LanguageRepository;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class LanguageHelper
{
    public function __construct(
        private LanguageRepository $languageRepository,
        private RequestStack $requestStack,
    ) {}

    public function getWcagIso(): string
    {
        $locale = $this->requestStack->getMainRequest()?->getLocale();

        if ($locale) {
            $language = $this->languageRepository->findByIsoCode($locale);

            if ($language->getParent()) {
                $language = $language->getParent();
            }

            return str_replace('_', '-', $language->getIsocode());
        }

        return 'en-US';
    }
}
