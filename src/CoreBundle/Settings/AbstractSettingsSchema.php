<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;

/**
 * Class AbstractSettingsSchema.
 */
abstract class AbstractSettingsSchema implements SchemaInterface
{
    /**
     * @param array                   $allowedTypes
     * @param AbstractSettingsBuilder $builder
     */
    public function setMultipleAllowedTypes($allowedTypes, $builder)
    {
        foreach ($allowedTypes as $name => $type) {
            $builder->setAllowedTypes($name, $type);
        }
    }

    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @param string $repo
     */
    public function setRepository($repo)
    {
        $this->repository = $repo;
    }
}
