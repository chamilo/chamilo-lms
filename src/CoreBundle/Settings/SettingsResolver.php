<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Doctrine\ORM\NonUniqueResultException;
use Sylius\Bundle\SettingsBundle\Resolver\SettingsResolverInterface;

/**
 * Class SessionSettingsSchema.
 */
class SettingsResolver implements SettingsResolverInterface
{
    public function resolve($schemaAlias, $namespace = null)
    {
        try {
            /*$criteria = [
                'category' => $schemaAlias,
            ];*/
            $criteria = [];
            if (null !== $namespace) {
                $criteria['category'] = $namespace;
            }

            return $this->settingsRepository->findBy($criteria);
        } catch (NonUniqueResultException $e) {
            throw new \LogicException(sprintf('Multiple schemas found for "%s". You should probably define a custom settings resolver for this schema.', $schemaAlias));
        }
    }
}
