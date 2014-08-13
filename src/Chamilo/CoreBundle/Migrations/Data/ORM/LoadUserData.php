<?php

namespace Chamilo\CoreBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

class LoadUserData extends AbstractFixture implements
    ContainerAwareInterface,
    OrderedFixtureInterface,
    VersionedFixtureInterface
{
    private $container;

    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return '1.0';
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 3;
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
        $manager = $this->getUserManager();
        $groupManager = $this->getGroupManager();
        $faker = $this->getFaker();

        $studentGroup = $groupManager->findGroupByName('students');
        $teacherGroup = $groupManager->findGroupByName('teachers');

        // Creating student user.

        $user = $manager->createUser();
        $user->setUserId(2);
        $user->setFirstname('student');
        $user->setLastname('student');
        //$user->setPhone($faker->phoneNumber);
        $user->setUsername('student');
        $user->setEmail($faker->safeEmail);
        $user->setPlainPassword('student');
        $user->setEnabled(true);
        $user->setLocked(false);
        $user->addGroup($studentGroup);

        $manager->updateUser($user);

        // Creating random student users.
        foreach (range(2, 100) as $id) {
            $user = $manager->createUser();
            $user->setUserId($id);
            $user->setFirstname($faker->firstName);
            $user->setLastname($faker->lastName);
            //$user->setPhone($faker->phoneNumber);
            $user->setUsername($faker->userName);
            $user->setEmail($faker->safeEmail);
            $user->setPlainPassword($faker->randomNumber());
            $user->setEnabled(true);
            $user->setLocked(false);
            $user->addGroup($studentGroup);
            $manager->updateUser($user);
        }
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
