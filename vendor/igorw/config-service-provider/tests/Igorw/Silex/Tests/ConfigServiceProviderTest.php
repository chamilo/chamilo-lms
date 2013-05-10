<?php

/*
 * This file is part of ConfigServiceProvider.
 *
 * (c) Igor Wiedler <igor@wiedler.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Igorw\Silex\Tests;

use Silex\Application;
use Igorw\Silex\ConfigServiceProvider;

/**
 * ConfigServiceProvider test cases.
 *
 * @author Jérôme Macias <jerome.macias@gmail.com>
 */
class ConfigServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testRegisterWithoutReplacement()
    {
        $app = new Application();

        $app->register(new ConfigServiceProvider(__DIR__."/Fixtures/config.json"));

        $this->assertTrue($app['debug']);
        $this->assertEquals('%data%', $app['data']);
    }

    public function testRegisterWithReplacement()
    {
        $app = new Application();

        $app->register(new ConfigServiceProvider(__DIR__."/Fixtures/config.json", array(
            'data' => 'test-replacement'
        )));

        $this->assertEquals('test-replacement', $app['data']);
    }

    public function testRegisterYamlWithoutReplacement()
    {
        if (class_exists('Symfony\\Component\\Yaml\\Yaml')) {
            $app = new Application();

            $app->register(new ConfigServiceProvider(__DIR__."/Fixtures/config.yml"));

            $this->assertTrue($app['debug']);
            $this->assertEquals('%data%', $app['data']);
        }
        else {
            $this->markTestIncomplete();
        }
    }

    public function testRegisterYamlWithReplacement()
    {
        if (class_exists('Symfony\\Component\\Yaml\\Yaml')) {
            $app = new Application();

            $app->register(new ConfigServiceProvider(__DIR__."/Fixtures/config.yml", array(
                'data' => 'test-replacement'
            )));

            $this->assertEquals('test-replacement', $app['data']);
        }
        else {
            $this->markTestIncomplete();
        }
    }
}
