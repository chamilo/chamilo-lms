<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Tests\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sonata\CoreBundle\Request\RequestBodyParamConverter;
use Symfony\Component\HttpFoundation\Request;

abstract class MyAbstractModel {

}

class MyModel extends MyAbstractModel {

}

class MyInvalidModel {

}

/**
 * Class RequestBodyParamConverterTest
 *
 * This is the test class of the request body param converter used to retrieve non-abstract model
 * classes used in bundles configuration
 *
 * @author Vincent Composieux <vincent.composieux@gmail.com>
 */
class RequestBodyParamConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the apply() method
     *
     * Should return correct non-abstract class
     */
    public function testApply()
    {
        // Given
        $request = new Request();
        $serializer = $this->getMock('JMS\Serializer\SerializerInterface');

        $configuration = new ParamConverter(array(
            'class' => 'Sonata\CoreBundle\Tests\Request\MyAbstractModel'
        ));

        $classes = array('Sonata\CoreBundle\Tests\Request\MyModel');

        $service = new RequestBodyParamConverter($serializer, null, null, null, null, $classes);

        // When
        $service->apply($request, $configuration);

        // Then
        $this->assertEquals('Sonata\CoreBundle\Tests\Request\MyModel', $configuration->getClass());
    }

    /**
     * Tests the apply() method with an invalid class
     *
     * Should not alter the configuration class and return the model (abstract) class
     */
    public function testApplyInvalidClass()
    {
        // Given
        $request = new Request();
        $serializer = $this->getMock('JMS\Serializer\SerializerInterface');

        $configuration = new ParamConverter(array(
            'class' => 'Sonata\CoreBundle\Tests\Request\MyAbstractModel'
        ));

        $classes = array('Sonata\CoreBundle\Tests\Request\MyInvalidModel');

        $service = new RequestBodyParamConverter($serializer, null, null, null, null, $classes);

        // When
        $service->apply($request, $configuration);

        // Then
        $this->assertEquals('Sonata\CoreBundle\Tests\Request\MyAbstractModel', $configuration->getClass());
    }
}
