<?php

namespace JMS\SecurityExtraBundle\Tests\Security\Authorization\Expression;

use JMS\SecurityExtraBundle\Security\Authorization\Expression\DefaultExpressionHandler;
use Symfony\Component\Security\Core\Role\Role;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\ExpressionCompiler;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Expression;
use Symfony\Component\Filesystem\Filesystem;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\ExpressionVoter;

class ExpressionVoterTest extends \PHPUnit_Framework_TestCase
{
    private $voter;
    private $cacheDir;
    private $fs;

    public function testVoteWithoutCache()
    {
        $this->voter->setCacheDir(null);

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->once())
        ->method('getRoles')
        ->will($this->returnValue(array(new Role('ROLE_FOO'))));

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote(
        $token,
        new \stdClass,
        array(new Expression('hasRole("ROLE_FOO")'))
        ));
    }

    /**
     * @dataProvider getVoteTests
     */
    public function testVote($token, $object, array $attributes, $expected)
    {
        $this->assertSame($expected, $this->voter->vote($token, $object, $attributes));
    }

    public function getVoteTests()
    {
        $tests = array();

        $tests[] = array(
        $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface'),
        new \stdClass(),
        array('ROLE_FOO'),
        VoterInterface::ACCESS_ABSTAIN,
        );

        $tests[] = array(
        $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\AnonymousToken')
        ->disableOriginalConstructor()->getMock(),
        new \stdClass(),
        array(new Expression('isAnonymous()')),
        VoterInterface::ACCESS_GRANTED,
        );

        $tests[] = array(
        $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\AnonymousToken')
        ->disableOriginalConstructor()->getMock(),
        new \stdClass(),
        array(new Expression('isAuthenticated()')),
        VoterInterface::ACCESS_DENIED,
        );

        return $tests;
    }

    public function testSupportsAttribute()
    {
        $this->assertFalse($this->voter->supportsAttribute('ROLE_FOO'));
        $this->assertFalse($this->voter->supportsAttribute('A'));
        $this->assertTrue($this->voter->supportsAttribute(new Expression('A')));
    }

    public function testSupportsClass()
    {
        $this->assertTrue($this->voter->supportsClass('stdClass'));
    }

    protected function setUp()
    {
        $handler = new DefaultExpressionHandler(new AuthenticationTrustResolver(
            'Symfony\Component\Security\Core\Authentication\Token\AnonymousToken',
            'Symfony\Component\Security\Core\Authentication\Token\RememberMeToken'));

        $this->voter = new ExpressionVoter($handler);
        $this->voter->setCompiler(new ExpressionCompiler());

        $this->fs = new Filesystem();
        $this->cacheDir = sys_get_temp_dir().'/'.uniqid('expression_voter', true);

        if (is_dir($this->cacheDir)) {
            $this->fs->remove($this->cacheDir);
        }

        if (false === @mkdir($this->cacheDir, 0777, true)) {
            throw new \RuntimeException(sprintf('Could not create cache dir "%s".', $this->cacheDir));
        }
    }

    protected function tearDown()
    {
        if (null !== $this->fs) {
            $this->fs->remove($this->cacheDir);
        }
    }
}
