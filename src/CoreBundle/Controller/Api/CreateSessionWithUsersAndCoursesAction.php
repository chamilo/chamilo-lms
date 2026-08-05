<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use ApiPlatform\Validator\ValidatorInterface;
use Chamilo\CoreBundle\Dto\CreateSessionWithUsersAndCoursesInput;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionCategory;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
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

        // Ensure the session title is unique
        $originalTitle = $data->getTitle();
        $title = $originalTitle;
        $counter = 1;

        while (
            $this->em->getRepository(Session::class)->findOneBy(['title' => $title])
        ) {
            $title = $originalTitle.' #'.$counter++;
        }

        $session = new Session();
        $session
            ->setTitle($title)
            ->setDescription($data->getDescription() ?? '')
            ->setVisibility($data->getVisibility() ?? 1)
            ->setNbrCourses(\count($data->getCourseIds()))
            ->setNbrUsers(\count($data->getStudentIds()) + \count($data->getTutorIds()))
            ->setShowDescription($data->getShowDescription() ?? false)
            ->setDuration($data->getDuration() ?? 0)
            ->setDisplayStartDate($data->getDisplayStartDate() ?? new DateTime())
            ->setDisplayEndDate($data->getDisplayEndDate() ?? new DateTime())
            ->setAccessStartDate($data->getAccessStartDate() ?? new DateTime())
            ->setAccessEndDate($data->getAccessEndDate() ?? new DateTime())
            ->setCoachAccessStartDate($data->getCoachAccessStartDate() ?? new DateTime())
            ->setCoachAccessEndDate($data->getCoachAccessEndDate() ?? new DateTime())
            ->setValidityInDays($data->getValidityInDays() ?? 0)
        ;

        if ($data->getCategory()) {
            $category = $this->em->getRepository(SessionCategory::class)->find($data->getCategory());
            if (!$category) {
                throw new RuntimeException('Invalid category ID: '.$data->getCategory());
            }
            $session->setCategory($category);
        }

        $this->em->persist($session);

        $relCourses = [];
        $courses = [];
        foreach ($data->getCourseIds() as $courseId) {
            $course = $this->courseRepo->find($courseId);
            if (!$course) {
                continue;
            }

            $courses[$courseId] = $course;

            $relCourse = new SessionRelCourse();
            $relCourse->setSession($session);
            $relCourse->setCourse($course);
            $relCourse->setNbrUsers(0);
            $this->em->persist($relCourse);

            $relCourses[$courseId] = $relCourse;

            if ($data->getCopyEvaluation()) {
                $this->copyGradebookFromBaseCourse($course, $session);
            }
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

            foreach ($courses as $courseId => $course) {
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

    /**
     * Duplicates the base course's gradebook (categories, links and evaluations)
     * into the given session, mirroring the "Import gradebook from base course"
     * checkbox in add_courses_to_session.php.
     */
    private function copyGradebookFromBaseCourse(Course $course, Session $session): void
    {
        $categories = $this->em->getRepository(GradebookCategory::class)
            ->findBy(['course' => $course->getId(), 'session' => null])
        ;

        foreach ($categories as $category) {
            $newCategory = new GradebookCategory();
            $newCategory
                ->setTitle($category->getTitle())
                ->setDescription($category->getDescription())
                ->setWeight($category->getWeight())
                ->setVisible($category->getVisible())
                ->setCertifMinScore($category->getCertifMinScore())
                ->setGenerateCertificates($category->getGenerateCertificates())
                ->setIsRequirement($category->getIsRequirement())
                ->setCourse($course)
                ->setSession($session)
                ->setParent($category->getParent())
            ;
            $this->em->persist($newCategory);

            foreach ($category->getLinks() as $link) {
                $newLink = clone $link;
                $newLink->setCategory($newCategory);
                $this->em->persist($newLink);
            }

            foreach ($category->getEvaluations() as $evaluation) {
                $newEvaluation = clone $evaluation;
                $newEvaluation->setCategory($newCategory);
                $this->em->persist($newEvaluation);
            }
        }
    }
}
