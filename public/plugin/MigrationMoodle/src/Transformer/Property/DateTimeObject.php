<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\TransformPropertyInterface;

/**
 * Class DateTimeObject.
 */
class DateTimeObject implements TransformPropertyInterface
{
    /**
     * @throws \Exception
     *
     * @return \DateTime|null
     */
    public function transform(array $data)
    {
        $timeCreated = (int) current($data);

        if ($timeCreated <= 0) {
            return null;
        }

        $date = new \DateTime();
        $date->setTimezone(new \DateTimeZone('UTC'));
        $date->setTimestamp($timeCreated);

        return $date;
    }
}
