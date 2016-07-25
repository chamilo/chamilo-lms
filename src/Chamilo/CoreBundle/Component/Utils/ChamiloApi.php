<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Utils;

/**
 * Class ChamiloApi
 * @package Chamilo\CoreBundle\Component
 */
class ChamiloApi
{
    private static $configuration;

    /**
     * ChamiloApi constructor.
     * @param $configuration
     */
    public function __construct(array $configuration)
    {
        self::$configuration = $configuration;
    }

    /**
     * @return array
     */
    public static function getConfigurationArray()
    {
        return self::$configuration;
    }

    /**
     * @param string $variable
     * @return bool|string
     */
    public static function getConfigurationValue($variable)
    {
        $configuration = self::getConfigurationArray();
        if (array_key_exists($variable, $configuration)) {
            return $configuration[$variable];
        }

        return false;
    }


    /**
     * Returns an array of resolutions that can be used for the conversion of documents to images
     * @return array
     */
    public static function getDocumentConversionSizes()
    {
        return array(
            '540x405' => '540x405 (3/4)',
            '640x480' => '640x480 (3/4)',
            '720x540' => '720x540 (3/4)',
            '800x600' => '800x600 (3/4)',
            '1024x576' => '1024x576 (16/9)',
            '1024x768' => '1000x750 (3/4)',
            '1280x720' => '1280x720 (16/9)',
            '1280x860' => '1280x960 (3/4)',
            '1400x1050' => '1400x1050 (3/4)',
            '1600x900' => '1600x900 (16/9)',
        );
    }

}
