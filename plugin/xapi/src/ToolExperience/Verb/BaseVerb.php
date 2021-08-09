<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Verb;

use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\LanguageMap;
use Xabbuh\XApi\Model\Verb;

/**
 * Class BaseVerb.
 *
 * @package Chamilo\PluginBundle\XApi\ToolExperience\Verb
 */
abstract class BaseVerb
{
    /**
     * @var string
     */
    protected $iri;
    /**
     * @var string
     */
    protected $display;

    public function __construct(string $iri, string $display)
    {
        $this->iri = $iri;
        $this->display = $display;
    }

    public function generate(): Verb
    {
        $langIso = api_get_language_isocode();

        return new Verb(
            IRI::fromString($this->iri),
            LanguageMap::create(
                [
                    $langIso => get_lang($this->display),
                ]
            )
        );
    }
}
