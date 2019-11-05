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
     * @param array $data
     *
     * @throws \Exception
     *
     * @return \DateTime
     */
    public function transform(array $data)
    {
        $timeCreated = (int) $data['timecreated'];

        $date = new \DateTime();
        $date->setTimezone(new \DateTimeZone('UTC'));
        $date->setTimestamp($timeCreated);

        return $date;
    }
}
