<?php

namespace Chamilo\PluginBundle\XApi\ToolExperience\Activity;

use Chamilo\UserBundle\Entity\User;
use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\Definition;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\LanguageMap;

class Portfolio extends BaseActivity
{
    /** @var User */
    private $owner;

    public function __construct(User $owner)
    {
        $this->owner = $owner;
    }

    public function generate(): Activity
    {
        $langIso = api_get_language_isocode();

        $iri = $this->generateIri(
            WEB_CODE_PATH,
            'portfolio/index.php',
            [
                'action' => 'list',
                'user' => $this->owner->getId(),
            ]
        );

        return new Activity(
            IRI::fromString($iri),
            new Definition(
                LanguageMap::create(
                    [
                        $langIso => sprintf(
                            get_lang('XUserPortfolioItems'),
                            $this->owner->getCompleteNameWithUsername()
                        ),
                    ]
                ),
                null,
                IRI::fromString('http://id.tincanapi.com/activitytype/collection-simple')
            )
        );
    }
}
