<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Doctrine\ORM\EntityRepository;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;

abstract class AbstractSettingsSchema implements SchemaInterface
{
    protected EntityRepository $repository;

    /**
     * @param array                   $allowedTypes
     * @param AbstractSettingsBuilder $builder
     */
    public function setMultipleAllowedTypes($allowedTypes, $builder): void
    {
        foreach ($allowedTypes as $name => $type) {
            $builder->setAllowedTypes($name, $type);
        }
    }

    public function getRepository()
    {
        return $this->repository;
    }

    public function setRepository($repo): void
    {
        $this->repository = $repo;
    }
}
