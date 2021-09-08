<?php

namespace Chamilo\Tests;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

trait ChamiloTestTrait
{
    public function createUser(string $username, string $password = '', string $email = ''): ?User
    {
        /** @var UserRepository $repo */
        $repo = static::getContainer()->get(UserRepository::class);

        $admin = $this->getUser('admin');

        if (empty($email)) {
            $email = "$username@example.com";
        }

        if (empty($password)) {
            $password = $username;
        }

        $user = $repo->createUser()
            ->setLastname($username)
            ->setFirstname($username)
            ->setUsername($username)
            ->setStatus(1)
            ->setPlainPassword($password)
            ->setEmail($email)
            ->setCreator($admin);

        $repo->updateUser($user);

        return $user;
    }

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

    public function createCourse($title): ?Course
    {
        $repo = self::getContainer()->get(CourseRepository::class);
        $course = (new Course())
            ->setTitle($title)
            ->addAccessUrl($this->getAccessUrl())
            ->setCreator($this->getUser('admin'))
        ;

        $repo->create($course);

        return $course;
    }

    public function createSession($title): ?Session
    {
        $repo = self::getContainer()->get(SessionRepository::class);

        $session = (new Session())
            ->setName($title)
            ->setGeneralCoach($this->getUser('admin'))
            ->addAccessUrl($this->getAccessUrl())
        ;
        $repo->update($session);

        return $session;
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
        /** @var ConstraintViolationList $errors */
        $errors = $this->getViolations($entity);

        $message = [];
        foreach ($errors as $error) {
            $message[] = $error->getPropertyPath().': '.$error->getMessage();
        }

        $this->assertEquals(0, $errors->count(), implode(', ', $message));
    }

    public function getUploadedFile(): UploadedFile
    {
        $path = $this->getContainer()->get('kernel')->getProjectDir();
        $filePath = $path.'/public/img/logo.png';
        $fileName = basename($filePath);

        return new UploadedFile(
            $filePath,
            $fileName,
            'image/png',
        );
    }

    public function getViolations($entity)
    {
        /** @var ValidatorInterface $validator */
        $validator = static::$kernel->getContainer()->get('validator');

        /** @var ConstraintViolationList $errors */
        return $validator->validate($entity);
    }

    public function convertToUTCAndFormat(\DateTime $localTime) : string
    {
        return $localTime->setTimezone(new \DateTimeZone('UTC'))->format('c');
    }

    public function getEntityManager(): EntityManager
    {
        return self::getContainer()->get('doctrine')->getManager();
    }
}
