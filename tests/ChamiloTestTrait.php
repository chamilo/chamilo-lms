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
use Chamilo\CourseBundle\Entity\CGroup;
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
    public function createCourse(string $title): ?Course
    {
        $repo = static::getContainer()->get(CourseRepository::class);
        $course = (new Course())
            ->setTitle($title)
            ->addAccessUrl($this->getAccessUrl())
            ->setCreator($this->getUser('admin'))
            ->setVisibility(Course::OPEN_PLATFORM)
        ;

        $repo->create($course);

        return $course;
    }

    public function createSession(string $title): ?Session
    {
        $repo = static::getContainer()->get(SessionRepository::class);

        $session = (new Session())
            ->setName($title)
            ->setGeneralCoach($this->getUser('admin'))
            ->addAccessUrl($this->getAccessUrl())
        ;
        $repo->update($session);

        return $session;
    }

    public function createGroup(string $title, Course $course): ?CGroup
    {
        $em = $this->getEntityManager();

        $group = (new CGroup())
            ->setName($title)
            ->setParent($course)
            ->setCreator($this->getUser('admin'))
            ->setMaxStudent(100)
        ;
        $em->persist($group);
        $em->flush();

        return $group;
    }

    /**
     * Finds a user registered in the test DB, added by the DataFixtures classes.
     */
    public function getUser(string $username): ?User
    {
        /** @var UserRepository $repo */
        $repo = static::getContainer()->get(UserRepository::class);

        return $repo->findByUsername($username);
    }

    public function getCourse($courseId): ?Course
    {
        $repo = static::getContainer()->get(CourseRepository::class);

        return $repo->find($courseId);
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
        $filePath = $path.'/tests/fixtures/logo.png';
        $fileName = basename($filePath);

        return new UploadedFile(
            $filePath,
            $fileName,
            'image/png',
        );
    }

    public function getUploadedZipFile(): UploadedFile
    {
        $path = $this->getContainer()->get('kernel')->getProjectDir();
        $filePath = $path.'/tests/fixtures/logo.zip';
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
        return static::getContainer()->get('doctrine')->getManager();
    }
}
