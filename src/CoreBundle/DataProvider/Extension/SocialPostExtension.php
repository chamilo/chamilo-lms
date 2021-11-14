<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\QueryBuilder;

class SocialPostExtension implements QueryCollectionExtensionInterface
{
    public function __construct(
        private SettingsManager $settingsManager
    ) {
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        string $operationName = null
    ): void {
        if ('true' !== $this->settingsManager->getSetting('social.allow_social_tool')) {
            $queryBuilder->andWhere('1 = 0');
        }
    }
}
