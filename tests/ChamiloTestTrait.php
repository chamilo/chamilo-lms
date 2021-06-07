<?php

namespace Chamilo\Tests;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

trait ChamiloTestTrait
{
    /**
     * Finds a user registered in the test DB, added by the DataFixtures classes.
     */
    public function getUser(string $username): ?User
    {
        /** @var UserRepository $repo */
        $repo = static::getContainer()->get(UserRepository::class);

        // retrieve user
        return $repo->findByUsername($username);
    }

    public function createUser(string $username, string $password, string $email): ?User
    {
        /** @var UserRepository $repo */
        $repo = static::getContainer()->get(UserRepository::class);

        $admin = $this->getUser('admin');

        $user = $repo->createUser()
            ->setLastname($username)
            ->setFirstname($username)
            ->setUsername($username)
            ->setStatus(1)
            ->setPlainPassword($password)
            ->setEmail($email)
            ->setCreator($admin)
        ;

        $repo->updateUser($user);

        return $user;
    }

    public function getAccessUrl(string $url = ''): ?AccessUrl
    {
        if (empty($url)) {
            $url = AccessUrl::DEFAULT_ACCESS_URL;
        }

        /** @var AccessUrlRepository $repo */
        $repo = static::getContainer()->get(AccessUrlRepository::class);

        return $repo->findOneBy(['url' => $url]);
    }

    public function assertHasNoEntityViolations($entity)
    {
        /** @var ValidatorInterface $validator */
        $validator = static::$kernel->getContainer()->get('validator');
        /** @var ConstraintViolationList $errors */
        $errors = $validator->validate($entity);

        $message = [];
        foreach ($errors as $error) {
            $message[] = $error->getPropertyPath().': '.$error->getMessage();
        }

        $this->assertEquals(0, $errors->count(), implode(', ', $message));
    }
}
