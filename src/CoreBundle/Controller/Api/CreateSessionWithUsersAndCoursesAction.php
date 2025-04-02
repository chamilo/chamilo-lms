<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use ApiPlatform\Validator\ValidatorInterface;
use Chamilo\CoreBundle\Dto\CreateSessionWithUsersAndCoursesInput;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class CreateSessionWithUsersAndCoursesAction
{
    public function __construct(
        private EntityManagerInterface $em,
        private ValidatorInterface $validator,
        private UserRepository $userRepo,
        private CourseRepository $courseRepo
    ) {}

    public function __invoke(CreateSessionWithUsersAndCoursesInput $data): Session
    {
        $this->validator->validate($data);

        $session = new Session();
        $session
            ->setTitle($data->getTitle())
            ->setDescription($data->getDescription() ?? '')
            ->setVisibility($data->getVisibility() ?? 1)
            ->setNbrCourses(\count($data->getCourseIds()))
            ->setNbrUsers(\count($data->getStudentIds()) + \count($data->getTutorIds()))
        ;

        $this->em->persist($session);

        $relCourses = [];
        foreach ($data->getCourseIds() as $courseId) {
            $course = $this->courseRepo->find($courseId);
            if (!$course) {
                continue;
            }

            $relCourse = new SessionRelCourse();
            $relCourse->setSession($session);
            $relCourse->setCourse($course);
            $relCourse->setNbrUsers(0);
            $this->em->persist($relCourse);

            $relCourses[$courseId] = $relCourse;
        }

        foreach ($data->getStudentIds() as $userId) {
            $user = $this->userRepo->find($userId);
            if (!$user) {
                continue;
            }

            $rel = new SessionRelUser();
            $rel
                ->setSession($session)
                ->setUser($user)
                ->setRelationType(Session::STUDENT)
            ;

            $this->em->persist($rel);

            foreach ($data->getCourseIds() as $courseId) {
                $course = $this->courseRepo->find($courseId);
                if (!$course) {
                    continue;
                }

                $relCourseUser = new SessionRelCourseRelUser();
                $relCourseUser
                    ->setSession($session)
                    ->setUser($user)
                    ->setCourse($course)
                    ->setStatus(Session::STUDENT)
                    ->setVisibility(1)
                    ->setProgress(0)
                    ->setLegalAgreement(0)
                ;

                $this->em->persist($relCourseUser);

                if (isset($relCourses[$courseId])) {
                    $relCourses[$courseId]->setNbrUsers(
                        $relCourses[$courseId]->getNbrUsers() + 1
                    );
                }
            }
        }

        foreach ($data->getTutorIds() as $userId) {
            $user = $this->userRepo->find($userId);
            if (!$user) {
                continue;
            }

            $rel = new SessionRelUser();
            $rel
                ->setSession($session)
                ->setUser($user)
                ->setRelationType(Session::SESSION_ADMIN)
            ;

            $this->em->persist($rel);
        }

        $this->em->flush();

        return $session;
    }
}
