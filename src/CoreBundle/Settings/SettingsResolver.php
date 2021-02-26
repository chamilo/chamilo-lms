<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Doctrine\ORM\NonUniqueResultException;
use Sylius\Bundle\SettingsBundle\Resolver\SettingsResolverInterface;

class SettingsResolver implements SettingsResolverInterface
{
    public function resolve($schemaAlias, $namespace = null): void
    {
        /*try {
            $criteria = [];
            if (null !== $namespace) {
                $criteria['category'] = $namespace;
            }

            return $this->settingsRepository->findBy($criteria);
        } catch (NonUniqueResultException $e) {
            $message = sprintf(
                'Multiple schemas found for "%s". You should probably define a custom settings resolver for this schema.',
                $schemaAlias
            );
            throw new \LogicException($message);
        }*/
    }
}
