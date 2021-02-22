<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Doctrine\ORM\NonUniqueResultException;
use Sylius\Bundle\SettingsBundle\Resolver\SettingsResolverInterface;
use Sylius\Bundle\SettingsBundle\Resource\RepositoryInterface;

class SettingsResolver implements SettingsResolverInterface
{
    /**
     * @var RepositoryInterface
     */
    private $settingsRepository;

    /**
     * @param RepositoryInterface $settingsRepository
     */
    public function __construct(RepositoryInterface $settingsRepository)
    {
        $this->settingsRepository = $settingsRepository;
    }

    public function resolve($schemaAlias, $namespace = null)
    {
        try {
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
        }
    }
}
