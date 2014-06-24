<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\RoutingBundle\Tests\Unit\DependencyInjection;

use Symfony\Cmf\Component\Testing\Unit\XmlSchemaTestCase;

class XmlSchemaTest extends XmlSchemaTestCase
{
    protected $fixturesPath;
    protected $schemaPath;

    public function setUp()
    {
        $this->fixturesPath = __DIR__.'/../../Resources/Fixtures/config/';
        $this->schemaPath = __DIR__.'/../../../Resources/config/schema/routing-1.0.xsd';
    }

    public function testSchema()
    {
        $fixturesPath = $this->fixturesPath;
        $xmlFiles = array_map(function ($file) use ($fixturesPath) {
            return $fixturesPath.$file;
        }, array(
            'config.xml',
            'config1.xml',
            'config2.xml',
            'config3.xml',
            'config4.xml',
        ));

        $this->assertSchemaAcceptsXml($xmlFiles, $this->schemaPath);
    }

    public function testSchemaInvalidesTwoPersistenceLayers()
    {
        $this->assertSchemaRefusesXml($this->fixturesPath.'config_invalid1.xml', $this->schemaPath);
    }
}
