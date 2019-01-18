<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;

/**
 * Class AbstractSettingsSchema.
 *
 * @package Chamilo\CoreBundle\Settings
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
}
