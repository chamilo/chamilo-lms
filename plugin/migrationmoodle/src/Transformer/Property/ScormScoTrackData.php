<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\TransformPropertyInterface;

/**
 * Class ScormScoTrackData.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class ScormScoTrackData implements TransformPropertyInterface
{
    const SEPARATOR_COMPONENTS = '|@|';
    const SEPARATOR_VALUES = '==>>';

    /**
     * {@inheritdoc}
     */
    public function transform(array $data)
    {
        $trackData = current($data);
        $strComponents = explode(self::SEPARATOR_COMPONENTS, $trackData);

        $itemData = [];

        $elements = [
            'x.start.time' => 'start_time',
            'cmi.core.lesson_status' => 'status',
            'cmi.core.total_time' => 'total_time',
            'cmi.core.exit' => 'core_exit',
            'cmi.suspend_data' => 'suspend_data',
            'cmi.core.score.raw' => 'score',
            'cmi.core.score.max' => 'max_score',
        ];

        foreach ($strComponents as $strComponent) {
            list($component, $value) = explode(self::SEPARATOR_VALUES, $strComponent);

            if ('cmi.core.total_time' === $component) {
                $value = $this->hmsToSeconds($value);
            } elseif ('cmi.core.exit' === $component) {
                $value = $this->coreExit($value);
            }

            if (isset($elements[$component])) {
                $component = $elements[$component];
            }

            $itemData[$component] = $value;
        }

        return $itemData;
    }

    /**
     * @param string $hms
     *
     * @return int
     */
    private function hmsToSeconds($hms)
    {
        if (empty($hms)) {
            return 0;
        }

        list($h, $m, $s) = explode(':', $hms);

        return ((int) $h * 3600) + ((int) $m * 60) + ceil($s);
    }

    /**
     * @param string $value
     *
     * @return string
     */
    private function coreExit($value)
    {
        if (empty($value)) {
            return 'none';
        }

        $value;
    }
}
