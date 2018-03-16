<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;

abstract class AbstractSettingsSchema implements SchemaInterface
{
    /**
     * @param array                    $allowedTypes
     * @param SettingsBuilderInterface $builder
     */
    public function setMultipleAllowedTypes($allowedTypes, $builder)
    {
        foreach ($allowedTypes as $name => $type) {
            $builder->setAllowedTypes($name, $type);
        }
    }
}
