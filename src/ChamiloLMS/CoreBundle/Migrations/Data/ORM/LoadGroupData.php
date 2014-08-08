<?php

namespace ChamiloLMS\CoreBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

class LoadGroupData extends AbstractFixture implements
    ContainerAwareInterface,
    OrderedFixtureInterface,
    VersionedFixtureInterface
{
    private $container;

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return '1.0';
    }

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $groupManager = $this->getGroupManager();

        // Creating groups
        $studentGroup = $groupManager->createGroup('students');
        $studentGroup->addRole('ROLE_STUDENT');

        $groupManager->updateGroup($studentGroup);

        $teacherGroup = $groupManager->createGroup('teachers');
        $teacherGroup->addRole('ROLE_TEACHER');
        $groupManager->updateGroup($teacherGroup);
    }

    /**
     * @return \FOS\UserBundle\Model\UserManagerInterface
     */
    public function getUserManager()
    {
        return $this->container->get('fos_user.user_manager');
    }

    /**
     * @return \FOS\UserBundle\Entity\GroupManager
     */
    public function getGroupManager()
    {
        return $this->container->get('fos_user.group_manager');
    }

    /**
     * @return \Faker\Generator
     */
    public function getFaker()
    {
        return $this->container->get('faker.generator');
    }
}
