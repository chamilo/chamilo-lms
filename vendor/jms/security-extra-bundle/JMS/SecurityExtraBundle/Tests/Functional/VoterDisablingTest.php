<?php

namespace JMS\SecurityExtraBundle\Tests\Functional;

class VoterDisablingTest extends BaseTestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testDisableAllVoters()
    {
        $client = $this->createClient(array('config' => 'all_voters_disabled.yml'));
        $client->insulate();

        $adm = self::$kernel->getContainer()->get('security.access.decision_manager');

        $this->assertEquals(1, count($voters = $this->getField($this->getField($adm, 'delegate'), 'voters')));
        $this->assertInstanceOf('JMS\SecurityExtraBundle\Security\Authorization\Expression\LazyLoadingExpressionVoter', $voters[0]);
    }

    /**
     * @runInSeparateProcess
     */
    public function testDefault()
    {
        $client = $this->createClient(array('config' => 'default.yml'));
        $client->insulate();

        $adm = self::$kernel->getContainer()->get('security.access.decision_manager');

        $this->assertEquals(2, count($voters = $this->getField($this->getField($adm, 'delegate'), 'voters')));
        $this->assertInstanceOf('Symfony\Component\Security\Core\Authorization\Voter\RoleVoter', $voters[0]);
        $this->assertInstanceOf('Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter', $voters[1]);
    }

    /**
     * @runInSeparateProcess
     */
    public function testSomeVotersDisabled()
    {
        $client = $this->createClient(array('config' => 'some_voters_disabled.yml'));
        $client->insulate();

        $adm = self::$kernel->getContainer()->get('security.access.decision_manager');

        $this->assertEquals(1, count($voters = $this->getField($this->getField($adm, 'delegate'), 'voters')));
        $this->assertInstanceOf('Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter', $voters[0]);
    }

    private function getField($obj, $field)
    {
        $ref = new \ReflectionProperty($obj, $field);
        $ref->setAccessible(true);

        return $ref->getValue($obj);
    }
}
