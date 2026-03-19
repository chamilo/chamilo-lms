<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Verb;

/**
 * Class BaseVerb.
 */
abstract class BaseVerb
{
    protected string $iri;
    protected string $display;

    public function __construct(string $iri, string $display)
    {
        $this->iri = trim($iri);
        $this->display = trim($display);
    }

    /**
     * Build a plain xAPI verb payload.
     */
    public function generate(): array
    {
        $languageSource = function_exists('api_get_interface_language')
            ? api_get_interface_language()
            : api_get_setting('platformLanguage');

        $languageIso = !empty($languageSource)
            ? api_get_language_isocode($languageSource)
            : 'en';

        return [
            'id' => $this->iri,
            'display' => [
                $languageIso => get_lang($this->display),
            ],
        ];
    }
}
