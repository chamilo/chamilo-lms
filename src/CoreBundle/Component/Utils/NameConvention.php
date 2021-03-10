<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Utils;

use Chamilo\CoreBundle\Entity\User;
use Symfony\Component\HttpFoundation\RequestStack;

class NameConvention
{
    protected RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getPersonName(User $user): string
    {
        $format = $this->getFormat()['format'];

        return str_replace(
            ['title ', 'first_name', 'last_name'],
            ['', $user->getFirstname(), $user->getLastname()],
            $format
        );
    }

    public function getFormat(): array
    {
        $locale = $this->requestStack->getCurrentRequest()->getLocale();

        $format = $this->getDefaultList()[$locale] ?? null;
        if (null === $format) {
            // English as default
            $format = $this->getDefaultList()['en'];
        }

        return $format;
    }

    public function getSortBy(): string
    {
        return $this->getFormat()['sort_by'];
    }

    public function getDefaultList(): array
    {
        return [
            'ast_ES' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'bs' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'pt_BR' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            //'breton' => ['format' => 'title first_name last_name', 'sort_by' => 'first_name'],
            'bg' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'ca_ES' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'hr' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'cs' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'da' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'fa_AF' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'nl' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'en' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'eo' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'et' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'eu_ES' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            //basque
            'fi' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'fr' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            //'frisian' => ['format' => 'title first_name last_name', 'sort_by' => 'first_name'],
            'fur_IT' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'gl_ES' => [
                'format' => 'title last_name first_name',
                'sort_by' => 'last_name',
            ],
            'ka' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'de' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'el' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            //'hawaiian' => ['format' => 'title first_name last_name', 'sort_by' => 'first_name'],
            'he' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'hi' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'hu' => [
                'format' => 'title last_name first_name',
                'sort_by' => 'last_name',
            ],
            // Eastern order
            //'icelandic' => ['format' => 'title first_name last_name', 'sort_by' => 'first_name'],
            'id' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            //'irish' => ['format' => 'title first_name last_name', 'sort_by' => 'first_name'],
            'it' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'ja' => [
                'format' => 'title last_name first_name',
                'sort_by' => 'last_name',
            ],
            // Eastern order
            'ko' => [
                'format' => 'title last_name first_name',
                'sort_by' => 'last_name',
            ],
            // Eastern order
            //'latin' => ['format' => 'title first_name last_name', 'sort_by' => 'first_name'],
            'lv' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'lt' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'mk' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'ms' => [
                'format' => 'title last_name first_name',
                'sort_by' => 'last_name',
            ],
            // Eastern order
            //'manx' => ['format' => 'title first_name last_name', 'sort_by' => 'first_name'],
            //'marathi' => ['format' => 'title first_name last_name', 'sort_by' => 'first_name'],
            //'middle_frisian' => ['format' => 'title first_name last_name', 'sort_by' => 'first_name'],
            //'mingo' => ['format' => 'title first_name last_name', 'sort_by' => 'first_name'],
            //'nepali' => ['format' => 'title first_name last_name', 'sort_by' => 'first_name'],
            'nn' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'oc_FR' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'ps' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'fa' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'pl' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'pt' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'qu' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'ro' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            //'rumantsch' => ['format' => 'title first_name last_name', 'sort_by' => 'first_name'],
            'ru' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            //'sanskrit' => ['format' => 'title first_name last_name', 'sort_by' => 'first_name'],
            'sr' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            //'serbian_cyrillic' => ['format' => 'title first_name last_name', 'sort_by' => 'first_name'],
            'zh_CN' => [
                'format' => 'title last_name first_name',
                'sort_by' => 'last_name',
            ],
            // Eastern order
            'sk' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'sl' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'es' => [
                'format' => 'title last_name, first_name',
                'sort_by' => 'last_name',
            ],
            'sw' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'sv' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'tl' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            //'tamil' => ['format' => 'title first_name last_name', 'sort_by' => 'first_name'],
            'th' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'zh_TW' => [
                'format' => 'title last_name first_name',
                'sort_by' => 'last_name',
            ],
            // Eastern order
            'tr' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'uk' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            'vi' => [
                'format' => 'title last_name first_name',
                'sort_by' => 'last_name',
            ],
            // Eastern order
            //'welsh' => ['format' => 'title first_name last_name', 'sort_by' => 'first_name'],
            //'yiddish' => ['format' => 'title first_name last_name', 'sort_by' => 'first_name'],
            'yo' => [
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
        ];
    }
}
