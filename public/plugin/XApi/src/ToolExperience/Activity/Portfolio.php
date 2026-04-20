<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Activity;

use Chamilo\CoreBundle\Entity\User;

/**
 * Class Portfolio.
 */
class Portfolio extends BaseActivity
{
    private User $owner;

    public function __construct(User $owner)
    {
        $this->owner = $owner;
    }

    public function generate(): array
    {
        $languageIso = $this->resolveLanguageIso();
        $title = sprintf(
            get_lang("%s's portfolio items"),
            $this->owner->getFullNameWithUsername()
        );

        $iri = $this->generateIri(
            WEB_CODE_PATH,
            'portfolio/index.php',
            [
                'action' => 'list',
                'user' => $this->owner->getId(),
            ]
        );

        return $this->buildActivity(
            $iri,
            $title,
            null,
            'http://id.tincanapi.com/activitytype/collection-simple'
        );
    }
}
