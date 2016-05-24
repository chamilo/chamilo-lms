<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Tests\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\AbstractConfigurationTestCase;
use Sonata\CoreBundle\DependencyInjection\Configuration;

class ConfigurationTest extends AbstractConfigurationTestCase
{
    public function getConfiguration()
    {
        return new Configuration();
    }

    public function testInvalidFormTypeValueLeadsToErrorMessage()
    {
        $this->assertConfigurationIsInvalid(
            array(
                array('form_type' => '3D'),
            ),
            'The form_type option value must be one of'
        );
    }

    public function testProcessedConfigurationLooksAsExpected()
    {
        $this->assertProcessedConfigurationEquals(array(
            array('form_type' => 'horizontal'), // this should be overwritten
            array('form_type' => 'standard'),    // by this during the merge
        ), array(
            'form_type'    => 'standard',
            'flashmessage' => array(),
            'form'         => array(
                'mapping' => array(
                    'enabled'   => true,
                    'type'      => array(),
                    'extension' => array(),
                ),
            ),
        ));
    }

    public function testFormMapping()
    {
        $this->assertProcessedConfigurationEquals(array(
            array('form' => array(
                'mapping' => array(
                    'type'      => array(
                        'foo' => 'Foo\Bar',
                    ),
                    'extension' => array(
                        'choice' => array(
                            'service.id',
                        ),
                    ),
                ),
            )),
        ), array(
            'form'         => array(
                'mapping' => array(
                    'enabled'   => true,
                    'type'      => array(
                        'foo' => 'Foo\Bar',
                    ),
                    'extension' => array(
                        'choice' => array(
                            'service.id',
                        ),
                    ),
                ),
            ),
            'form_type'    => 'standard',
            'flashmessage' => array(),
        ));
    }

    public function testDefault()
    {
        $this->assertProcessedConfigurationEquals(array(
            array(),
        ), array(
            'form'         => array(
                'mapping' => array(
                    'enabled'   => true,
                    'type'      => array(),
                    'extension' => array(),
                ),
            ),
            'form_type'    => 'standard',
            'flashmessage' => array(),
        ));
    }
}
