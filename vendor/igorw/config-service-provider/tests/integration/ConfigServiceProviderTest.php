<?php

/*
 * This file is part of ConfigServiceProvider.
 *
 * (c) Igor Wiedler <igor@wiedler.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Silex\Application;
use Igorw\Silex\ConfigServiceProvider;

/**
 * @author Igor Wiedler <igor@wiedler.ch>
 * @author Jérôme Macias <jerome.macias@gmail.com>
 */
class ConfigServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideFilenames
     */
    public function testRegisterWithoutReplacement($filename)
    {
        $app = new Application();

        $app->register(new ConfigServiceProvider($filename));

        $this->assertSame(true, $app['debug']);
        $this->assertSame('%data%', $app['data']);
    }

    /**
     * @dataProvider provideFilenames
     */
    public function testRegisterWithReplacement($filename)
    {
        $app = new Application();

        $app->register(new ConfigServiceProvider(__DIR__."/Fixtures/config.json", array(
            'data' => 'test-replacement'
        )));

        $this->assertSame(true, $app['debug']);
        $this->assertSame('test-replacement', $app['data']);
    }

    /**
     * @dataProvider provideEmptyFilenames
     */
    public function testEmptyConfigs($filename)
    {
        $readConfigMethod = new \ReflectionMethod('Igorw\Silex\ConfigServiceProvider', 'readConfig');
        $readConfigMethod->setAccessible(true);

        $this->assertEquals(
            array(),
            $readConfigMethod->invoke(new ConfigServiceProvider($filename))
        );
    }

    /**
     * @dataProvider provideReplacementFilenames
     */
    public function testInFileReplacements($filename)
    {
        $app = new Application();

        $app->register(new ConfigServiceProvider($filename));

        $this->assertSame('/var/www', $app['%path%']);
        $this->assertSame('/var/www/web/images', $app['path.images']);
        $this->assertSame('/var/www/upload', $app['path.upload']);
        $this->assertSame('http://example.com', $app['%url%']);
        $this->assertSame('http://example.com/images', $app['url.images']);
    }

    /**
     * @dataProvider provideMergeFilenames
     */
    public function testMergeConfigs($filenameBase, $filenameExtended)
    {
        $app = new Application();
        $app->register(new ConfigServiceProvider($filenameBase));
        $app->register(new ConfigServiceProvider($filenameExtended));

        $this->assertSame('pdo_mysql', $app['db.options']['driver']);
        $this->assertSame('utf8', $app['db.options']['charset']);
        $this->assertSame('127.0.0.1', $app['db.options']['host']);
        $this->assertSame('mydatabase', $app['db.options']['dbname']);
        $this->assertSame('root', $app['db.options']['user']);
        $this->assertSame(null, $app['db.options']['password']);

        $this->assertSame('123', $app['myproject.test']['param1']);
        $this->assertSame('456', $app['myproject.test']['param2']);
        $this->assertSame('123', $app['myproject.test']['param3']['param2A']);
        $this->assertSame('456', $app['myproject.test']['param3']['param2B']);
        $this->assertSame('456', $app['myproject.test']['param3']['param2C']);
        $this->assertSame(array(4, 5, 6), $app['myproject.test']['param4']);
        $this->assertSame('456', $app['myproject.test']['param5']);

        $this->assertSame(array(1,2,3,4), $app['test.noparent.key']['test']);
    }

    /**
     * @test
     * @expectedException RuntimeException
     * @expectedExceptionMessage Invalid JSON provided "Syntax error" in
     */
    public function invalidJsonShouldThrowException()
    {
        $app = new Application();
        $app->register(new ConfigServiceProvider(__DIR__."/Fixtures/broken.json"));
    }

    /**
     * @test
     * @expectedException Symfony\Component\Yaml\Exception\ParseException
     */
    public function invalidYamlShouldThrowException()
    {
        $app = new Application();
        $app->register(new ConfigServiceProvider(__DIR__."/Fixtures/broken.yml"));
    }

    public function provideFilenames()
    {
        return array(
            array(__DIR__."/Fixtures/config.php"),
            array(__DIR__."/Fixtures/config.json"),
            array(__DIR__."/Fixtures/config.yml"),
        );
    }

    public function provideReplacementFilenames()
    {
        return array(
            array(__DIR__."/Fixtures/config_replacement.php"),
            array(__DIR__."/Fixtures/config_replacement.json"),
            array(__DIR__."/Fixtures/config_replacement.yml"),
        );
    }

    public function provideEmptyFilenames()
    {
        return array(
            array(__DIR__."/Fixtures/config_empty.php"),
            array(__DIR__."/Fixtures/config_empty.json"),
            array(__DIR__."/Fixtures/config_empty.yml"),
        );
    }

    public function provideMergeFilenames()
    {
        return array(
            array(__DIR__."/Fixtures/config_base.php", __DIR__."/Fixtures/config_extend.php"),
            array(__DIR__."/Fixtures/config_base.json", __DIR__."/Fixtures/config_extend.json"),
            array(__DIR__."/Fixtures/config_base.yml", __DIR__."/Fixtures/config_extend.yml"),
        );
    }
}
