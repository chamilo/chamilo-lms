<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\TransformPropertyInterface;

/**
 * Class ScormScoTrackData.
 */
class ScormScoTrackData implements TransformPropertyInterface
{
    public const SEPARATOR_COMPONENTS = '|@|';
    public const SEPARATOR_VALUES = '==>>';

    /**
     * {@inheritdoc}
     */
    public function transform(array $data)
    {
        $trackData = current($data);
        $strComponents = explode(self::SEPARATOR_COMPONENTS, $trackData);

        $trackData = [];

        foreach ($strComponents as $strComponent) {
            list($component, $value) = explode(self::SEPARATOR_VALUES, $strComponent);

            $trackData[$component] = $value;
        }

        $elements = [
            'x.start.time' => 'start_time',
            'cmi.core.lesson_status' => 'status',
            'cmi.core.total_time' => 'total_time',
            'cmi.core.exit' => 'core_exit',
            'cmi.suspend_data' => 'suspend_data',
            'cmi.core.score.raw' => 'score',
            'cmi.core.score.max' => 'max_score',
            'cmi.total_time' => 'total_time',
            'cmi.score.scaled' => 'score',
            'cmi.completion_status' => 'status',
        ];

        $itemData = [];

        foreach ($trackData as $component => $value) {
            if ('cmi.core.total_time' === $component) {
                $value = $this->hmsToSeconds($value);
            } elseif ('cmi.core.exit' === $component) {
                $value = $this->coreExit($value);
            } elseif ('cmi.total_time' === $component) {
                if (empty($trackData['x.start.time'])) {
                    $value = 0;
                } else {
                    $value = $this->cmiTotalTime($trackData['x.start.time'], $value);
                }
            } elseif ('cmi.score.scaled' === $component) {
                $value = $this->cmiScoreScaled($value);
            }

            if (isset($elements[$component])) {
                $variable = $elements[$component];

                $itemData[$variable] = $value;
            }
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
    }

    /**
     * @param int    $startTimeValue
     * @param string $value
     *
     * @throws \Exception
     *
     * @return int
     */
    private function cmiTotalTime($startTimeValue, $value)
    {
        $startTime = new \DateTime();
        $startTime->setTimestamp($startTimeValue);
        $startTime->setTimezone(new \DateTimeZone('UTC'));

        $endTime = clone $startTime;
        $endTime->add(new \DateInterval($value));

        return $endTime->getTimestamp() - $startTime->getTimestamp();
    }

    /**
     * @param string $value
     *
     * @return float
     */
    private function cmiScoreScaled($value)
    {
        return (float) $value * 100;
    }
}
