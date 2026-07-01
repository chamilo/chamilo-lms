<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use ApiPlatform\Validator\ValidatorInterface;
use Chamilo\CoreBundle\Dto\CreateSessionWithUsersAndCoursesInput;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\CoreBundle\Entity\GradebookLink;
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

        // Copy gradebook categories/links/evaluations from each base course into the new session.
        foreach (array_keys($courses) as $courseId) {
            $this->copyGradebookToSession((int) $courseId, $session);
        }

        return $session;
    }

    /**
     * Copy gradebook categories, links and evaluations from the base course
     * (no session) to the given session, mirroring SessionRepetitionCommand::copyEvaluationsAndCategories().
     */
    private function copyGradebookToSession(int $courseId, Session $session): void
    {
        // Load base-course categories (session = null means the stand-alone course gradebook).
        $baseCategories = $this->em->getRepository(GradebookCategory::class)
            ->findBy(['course' => $courseId, 'session' => null])
        ;

        if (empty($baseCategories)) {
            return;
        }

        // Avoid duplicating if already copied (e.g. action called twice).
        $existing = $this->em->getRepository(GradebookCategory::class)
            ->findOneBy(['course' => $courseId, 'session' => $session])
        ;
        if (null !== $existing) {
            return;
        }

        foreach ($baseCategories as $baseCategory) {
            $newCategory = new GradebookCategory();
            $newCategory
                ->setTitle($baseCategory->getTitle())
                ->setDescription($baseCategory->getDescription())
                ->setWeight($baseCategory->getWeight())
                ->setVisible($baseCategory->getVisible())
                ->setCertifMinScore($baseCategory->getCertifMinScore())
                ->setGenerateCertificates($baseCategory->getGenerateCertificates())
                ->setIsRequirement($baseCategory->getIsRequirement())
                ->setCourse($baseCategory->getCourse())
                ->setSession($session)
                ->setParent($baseCategory->getParent())
            ;

            $this->em->persist($newCategory);
            $this->em->flush();

            // Copy links (quiz, LP, attendance, etc.).
            $links = $this->em->getRepository(GradebookLink::class)
                ->findBy(['category' => $baseCategory->getId()])
            ;
            foreach ($links as $link) {
                $newLink = clone $link;
                $newLink->setCategory($newCategory);
                $this->em->persist($newLink);
            }

            // Copy evaluations (manual grade items).
            $evaluations = $this->em->getRepository(GradebookEvaluation::class)
                ->findBy(['category' => $baseCategory->getId()])
            ;
            foreach ($evaluations as $evaluation) {
                $newEvaluation = clone $evaluation;
                $newEvaluation->setCategory($newCategory);
                $this->em->persist($newEvaluation);
            }

            $this->em->flush();
        }
    }
}
