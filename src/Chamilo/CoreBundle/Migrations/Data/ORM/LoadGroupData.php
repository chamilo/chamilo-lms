<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

/**
 * Class LoadGroupData
 * @package Chamilo\CoreBundle\Migrations\Data\ORM
 */
class LoadGroupData extends AbstractFixture implements
    ContainerAwareInterface,
    OrderedFixtureInterface,
    VersionedFixtureInterface
{
    /** @var ContainerInterface */
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
        $group = $groupManager->createGroup('Administrators');
        $group->addRole('ROLE_ADMIN');
        $manager->persist($group);
        $groupManager->updateGroup($group);
        $this->setReference('group_admin', $group);

        $group = $groupManager->createGroup('Students');
        $group->addRole('ROLE_STUDENT');
        $groupManager->updateGroup($group);
        $this->setReference('group_student', $group);

        $group = $groupManager->createGroup('Teachers');
        $group->addRole('ROLE_TEACHER');
        $groupManager->updateGroup($group);
        $this->setReference('group_teacher', $group);

        $group = $groupManager->createGroup('Human resources manager');
        $group->addRole('ROLE_RRHH');
        $groupManager->updateGroup($group);
        $this->setReference('group_drh', $group);

        $group = $groupManager->createGroup('Session manager');
        $group->addRole('ROLE_SESSION_MANAGER');
        $groupManager->updateGroup($group);

        $group = $groupManager->createGroup('Question manager');
        $group->addRole('ROLE_QUESTION_MANAGER');
        $groupManager->updateGroup($group);

        $manager->flush();
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
