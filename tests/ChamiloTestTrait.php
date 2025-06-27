<?php

namespace Chamilo\Tests;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CoreBundle\Helpers\ContainerHelper;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;
use Symfony\Component\Validator\ConstraintViolationList;


trait ChamiloTestTrait
{
    public function createUser(string $username, string $password = '', string $email = '', string $role = ''): ?User
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
            ->setCreator($admin)
            ->setCurrentUrl($this->getAccessUrl())
        ;

        if (!empty($role)) {
            $user->addRole($role);
        }

        $repo->updateUser($user);

        return $user;
    }
    public function createCourse(string $title): ?Course
    {
        /* @var CourseRepository $repo */
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
            ->setTitle($title)
            ->addGeneralCoach($this->getUser('admin'))
            ->addAccessUrl($this->getAccessUrl())
        ;
        $repo->update($session);

        return $session;
    }

    public function createGroup(string $title, Course $course): ?CGroup
    {
        $em = $this->getEntityManager();

        $group = (new CGroup())
            ->setTitle($title)
            ->setParent($course)
            ->setCreator($this->getUser('admin'))
            ->setMaxStudent(100)
        ;
        $em->persist($group);
        $em->flush();

        return $group;
    }

    public function createUserGroup(string $title): ?Usergroup
    {
        $em = $this->getEntityManager();
        $creator = $this->createUser('usergroup_creator');

        $group = (new Usergroup())
            ->setTitle($title)
            ->setDescription('desc')
            ->setCreator($creator)
            ->addAccessUrl($this->getAccessUrl())
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

    public function getAdmin(): User
    {
        return $this->getUser('admin');
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

    public function getUploadedFileArray(): array
    {
        return [
            'tmp_name' => $this->getUploadedFile()->getRealPath(),
            'name' => $this->getUploadedFile()->getFilename(),
            'type' => $this->getUploadedFile()->getMimeType(),
            'size' => $this->getUploadedFile()->getSize(),
            'error' => UPLOAD_ERR_OK,
        ];
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
        /** @var ContainerHelper $containerHelper */
        $containerHelper = static::$kernel->getContainer()->get(ContainerHelper::class);

        $validator = $containerHelper->getValidator();

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

    /**
     * Helper to mock a request stack with a given current request.
     *
     * Builds a request based on parameters, and adds it to the returned
     * request stack.
     *
     * @param array $data
     *   A map where keys can be the following:
     *   - <request_parameter>: A parameter following symfony http foundation
     *   Request constructor.
     *   - 'session': and array with session data to set.
     *
     * @see \Symfony\Component\HttpFoundation\Request::__construct()
     */
    public function getMockedRequestStack(array $data = []) : RequestStack
    {
        $request_keys = ['query', 'request', 'attributes', 'cookies', 'files', 'server', 'content'];
        $request_parameters = [];
        foreach ($request_keys as $request_key) {
            $request_parameter_default = ($request_key == 'content') ? null : [];
            $request_parameter = !empty($data[$request_key]) ? $data[$request_key] : $request_parameter_default;
            $request_parameters[] = $request_parameter;
        }
        $request = new Request();
        call_user_func_array(array($request, 'initialize'), $request_parameters);
        if (!empty($data['session'])) {
            $session = new SymfonySession(new MockFileSessionStorage);
            foreach ($data['session'] as $session_key => $session_value) {
                $session->set($session_key, $session_value);
            }
            $request->setSession($session);
        }
        $request_stack = $this->createMock(RequestStack::class);
        $request_stack
            ->method('getCurrentRequest')
            ->willReturn($request)
        ;
        return $request_stack;
    }

}
