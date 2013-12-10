<?php

namespace ChamiloLMS\Component\Editor;

/**
 * elFinder - file manager for web.
 * Core class.
 *
 * @package elfinder
 * @author Dmitry (dio) Levashov
 * @author Troex Nevelin
 * @author Alexey Sukhotin
 **/
class Finder extends \elFinder
{
    /**
     * @param string $target
     * @return bool|\elFinderVolumeDriver
     */
    public function getVolumeByTarget($target)
    {
        $volumeParts = explode('_', $target);
        $requestVolume = $volumeParts[0];
        /** @var \elFinderVolumeDriver $volume */
        foreach ($this->volumes as $volume) {
            if ($volume->id() == $requestVolume.'_') {
                return $volume;
            }
        }
        return false;
    }

    /**
     * @param string $target
     * @return bool|\elFinderVolumeDriver
     */
    public function getVolumeDriverNameByTarget($target)
    {
        $volumeParts = explode('_', $target);
        $requestVolume = $volumeParts[0];
        /** @var \elFinderVolumeDriver $volume */
        foreach ($this->volumes as $volume) {
            if ($volume->id() == $requestVolume.'_') {
                $options = $volume->getOptionsPlugin();
                return $options['name'];
            }
        }
        return false;
    }

    /**
     * @return array
     */
    public function getVolumes()
    {
        return $this->volumes;
    }
}
