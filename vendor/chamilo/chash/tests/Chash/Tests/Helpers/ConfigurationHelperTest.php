<?php
require_once 'vfsStream/vfsStream.php';

use Chash\Helpers\ConfigurationHelper;

/**
 * Class ConfigurationHelper
 * @package Chash\Helpers
 */
class ConfigurationHelperTest extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('chamilo'));

    }

    public function testGetConfigurationPath()
    {
        $configHelper = new ConfigurationHelper();
        $configHelper->getConfigurationPath('/var/www/chamilo');

    }
}
