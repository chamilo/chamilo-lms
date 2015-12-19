<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\Tests\Form\Transformer;

use Sonata\UserBundle\Form\Transformer\RestoreRolesTransformer;

class RestoreRolesTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException RuntimeException
     */
    public function testInvalidStateTransform()
    {
        $roleBuilder = $this->getMockBuilder('Sonata\UserBundle\Security\EditableRolesBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $transformer = new RestoreRolesTransformer($roleBuilder);
        $transformer->transform(array());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testInvalidStateReverseTransform()
    {
        $roleBuilder = $this->getMockBuilder('Sonata\UserBundle\Security\EditableRolesBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $transformer = new RestoreRolesTransformer($roleBuilder);
        $transformer->reverseTransform(array());
    }

    public function testValidTransform()
    {
        $roleBuilder = $this->getMockBuilder('Sonata\UserBundle\Security\EditableRolesBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $transformer = new RestoreRolesTransformer($roleBuilder);
        $transformer->setOriginalRoles(array());

        $data = array('ROLE_FOO');

        $this->assertEquals($data, $transformer->transform($data));
    }

    public function testValidReverseTransform()
    {
        $roleBuilder = $this->getMockBuilder('Sonata\UserBundle\Security\EditableRolesBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $roleBuilder->expects($this->once())->method('getRoles')->will($this->returnValue(array(array(), array())));

        $transformer = new RestoreRolesTransformer($roleBuilder);
        $transformer->setOriginalRoles(array('ROLE_HIDDEN'));

        $data = array('ROLE_FOO');

        $this->assertEquals(array('ROLE_FOO', 'ROLE_HIDDEN'), $transformer->reverseTransform($data));
    }
}
