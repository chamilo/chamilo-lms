<?php

/*
 * This file is part of the FOSAdvancedEncoderBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\AdvancedEncoderBundle\Tests\Security\Encoder;

use FOS\AdvancedEncoderBundle\Security\Encoder\EncoderAwareInterface;
use FOS\AdvancedEncoderBundle\Security\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\User\UserInterface;

class EncoderFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testGetEncoderWithMessageDigestEncoder()
    {
        $innerFactory = $this->getMock('Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface');
        $innerFactory->expects($this->never())
            ->method('getEncoder')
        ;

        $factory = new EncoderFactory($innerFactory, array('test' => array(
            'class' => 'Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder',
            'arguments' => array('sha512', true, 5),
        )));

        $user = $this->getMock('FOS\AdvancedEncoderBundle\Tests\Security\Encoder\StubUserInterface');
        $user->expects($this->once())
            ->method('getEncoderName')
            ->will($this->returnValue('test'))
        ;
        $expectedEncoder = new MessageDigestPasswordEncoder('sha512', true, 5);

        $this->assertEquals($expectedEncoder, $factory->getEncoder($user));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testGetEncoderWithMissingClass()
    {
        $innerFactory = $this->getMock('Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface');
        $innerFactory->expects($this->never())
            ->method('getEncoder')
        ;

        $factory = new EncoderFactory($innerFactory, array('test' => array(
            'arguments' => array('sha512', true, 5),
        )));

        $user = $this->getMock('FOS\AdvancedEncoderBundle\Tests\Security\Encoder\StubUserInterface');
        $user->expects($this->once())
            ->method('getEncoderName')
            ->will($this->returnValue('test'))
        ;

        $factory->getEncoder($user);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testGetEncoderWithMissingArguments()
    {
        $innerFactory = $this->getMock('Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface');
        $innerFactory->expects($this->never())
            ->method('getEncoder')
        ;

        $factory = new EncoderFactory($innerFactory, array('test' => array(
            'class' => 'Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder',
        )));

        $user = $this->getMock('FOS\AdvancedEncoderBundle\Tests\Security\Encoder\StubUserInterface');
        $user->expects($this->once())
            ->method('getEncoderName')
            ->will($this->returnValue('test'))
        ;

        $factory->getEncoder($user);
    }

    public function testGetEncoderWithService()
    {
        $innerFactory = $this->getMock('Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface');
        $innerFactory->expects($this->never())
            ->method('getEncoder')
        ;

        $encoder = $this->getMock('Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface');
        $factory = new EncoderFactory($innerFactory, array(
            'test' => $encoder,
        ));

        $user = $this->getMock('FOS\AdvancedEncoderBundle\Tests\Security\Encoder\StubUserInterface');
        $user->expects($this->once())
            ->method('getEncoderName')
            ->will($this->returnValue('test'))
        ;

        $this->assertSame($encoder, $factory->getEncoder($user));
    }

    public function testGetEncoderWithNullName()
    {
        $user = $this->getMock('FOS\AdvancedEncoderBundle\Tests\Security\Encoder\StubUserInterface');
        $user->expects($this->once())
            ->method('getEncoderName')
            ->will($this->returnValue(null))
        ;

        $encoder = $this->getMock('Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface');
        $innerFactory = $this->getMock('Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface');
        $innerFactory->expects($this->once())
            ->method('getEncoder')
            ->with($this->equalTo($user))
            ->will($this->returnValue($encoder))
        ;

        $factory = new EncoderFactory($innerFactory, array('test' => array(
            'class' => 'Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder',
            'arguments' => array('sha512', true, 5),
        )));

        $this->assertSame($encoder, $factory->getEncoder($user));
    }

    public function testGetEncoderWithStandardUser()
    {
        $user = $this->getMock('Symfony\Component\Security\Core\User\UserInterface');
        $encoder = $this->getMock('Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface');

        $innerFactory = $this->getMock('Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface');
        $innerFactory->expects($this->once())
            ->method('getEncoder')
            ->with($this->equalTo($user))
            ->will($this->returnValue($encoder))
        ;

        $factory = new EncoderFactory($innerFactory, array('test' => array(
            'class' => 'Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder',
            'arguments' => array('sha512', true, 5),
        )));

        $this->assertSame($encoder, $factory->getEncoder($user));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetEncoderWithInvalidName()
    {
        $innerFactory = $this->getMock('Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface');
        $innerFactory->expects($this->never())
            ->method('getEncoder')
        ;

        $encoder = $this->getMock('Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface');
        $factory = new EncoderFactory($innerFactory, array(
            'test' => $encoder,
        ));

        $user = $this->getMock('FOS\AdvancedEncoderBundle\Tests\Security\Encoder\StubUserInterface');
        $user->expects($this->once())
            ->method('getEncoderName')
            ->will($this->returnValue('foo'))
        ;

        $factory->getEncoder($user);
    }
}

interface StubUserInterface extends UserInterface, EncoderAwareInterface {}
