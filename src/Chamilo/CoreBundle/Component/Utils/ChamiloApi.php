<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Utils;

/**
 * Class ChamiloApi
 * @package Chamilo\CoreBundle\Component
 */
class ChamiloApi
{
    private $configuration;
    private static $instance = null;

    protected function __construct($configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @return ChamiloApi|null
     */
    public function getInstance($configuration = null)
    {
        if (is_null(self::$instance)) {
            self::$instance = new ChamiloApi($configuration);
        }

        return self::$instance;
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
