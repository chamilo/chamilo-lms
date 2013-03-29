<?php

/*
 * This file is part of ConfigServiceProvider.
 *
 * (c) Igor Wiedler <igor@wiedler.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Igorw\Silex;

/**
 * @author Igor Wiedler <igor@wiedler.ch>
 * @author Jérôme Macias <jerome.macias@gmail.com>
 */
class GetFileFormatTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideFilenamesForFormat
     */
    public function testGetFileFormat($expectedFormat, $filename)
    {
        $configServiceProvider = new ConfigServiceProvider($filename);
        $this->assertSame($expectedFormat, $configServiceProvider->getFileFormat());
    }

    public function provideFilenamesForFormat()
    {
        return array(
            'yaml'      => array('yaml', __DIR__."/Fixtures/config.yaml"),
            'yml'       => array('yaml', __DIR__."/Fixtures/config.yml"),
            'yaml.dist' => array('yaml', __DIR__."/Fixtures/config.yaml.dist"),
            'json'      => array('json', __DIR__."/Fixtures/config.json"),
            'json.dist' => array('json', __DIR__."/Fixtures/config.json.dist"),
            'php'       => array('php', __DIR__."/Fixtures/config.php"),
            'php.dist'  => array('php', __DIR__."/Fixtures/config.php.dist"),
        );
    }
}
