<?php

namespace Braincrafted\Bundle\BootstrapBundle\Tests\DependencyInjection;

use Braincrafted\Bundle\BootstrapBundle\DependencyInjection\AsseticConfiguration;

/**
 * AsseticConfigurationTest
 *
 * @group unit
 */
class AsseticConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->asseticConfig = new AsseticConfiguration;
    }

    /**
     * @covers Braincrafted\Bundle\BootstrapBundle\DependencyInjection\AsseticConfiguration::build()
     * @covers Braincrafted\Bundle\BootstrapBundle\DependencyInjection\AsseticConfiguration::buildCssWithLess()
     * @covers Braincrafted\Bundle\BootstrapBundle\DependencyInjection\AsseticConfiguration::buildJs()
     * @covers Braincrafted\Bundle\BootstrapBundle\DependencyInjection\AsseticConfiguration::buildJquery()
     */
    public function testBuild()
    {
        $this->asseticConfig->build(array(
            'less_filter'   => 'less',
            'assets_dir'    => './assets',
            'output_dir'    => './web',
            'jquery_path'   => './assets/jquery.js',
            'customize' => array(
                'variables_file'    => './assets/variables.less',
                'bootstrap_output'  => './assets/bootstrap.less'
            )
        ));
    }

    /**
     * @covers Braincrafted\Bundle\BootstrapBundle\DependencyInjection\AsseticConfiguration::build()
     * @covers Braincrafted\Bundle\BootstrapBundle\DependencyInjection\AsseticConfiguration::buildCssWithoutLess()
     * @covers Braincrafted\Bundle\BootstrapBundle\DependencyInjection\AsseticConfiguration::buildJs()
     * @covers Braincrafted\Bundle\BootstrapBundle\DependencyInjection\AsseticConfiguration::buildJquery()
     */
    public function testBuildWithoutLess()
    {
        $this->asseticConfig->build(array(
            'less_filter'   => 'none',
            'assets_dir'    => './assets',
            'output_dir'    => './web',
            'jquery_path'   => './assets/jquery.js'
        ));
    }
}
