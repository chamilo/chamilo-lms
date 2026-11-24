<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use ApiPlatform\Symfony\Bundle\Test\Response;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Doctrine\ORM\EntityManagerInterface;

final class CourseIntroductionPositionApiTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testTeacherCanSaveIntroductionPosition(): void
    {
        $ctx = $this->bootstrapCourse('save');

        $response = $this->requestPosition($ctx['teacher'], $ctx['course'], 'left');
        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $this->assertTrue($data['success'] ?? false);
        $this->assertSame('left', $data['introduction_position'] ?? null);
        $this->assertSame('left', $this->getStoredPosition($ctx['course']));
    }

    public function testStudentCannotSaveIntroductionPosition(): void
    {
        $ctx = $this->bootstrapCourse('student');

        $this->requestPosition($ctx['student'], $ctx['course'], 'below');
        $this->assertResponseStatusCodeSame(403);
        $this->assertSame('above', $this->getStoredPosition($ctx['course']));
    }

    public function testInvalidIntroductionPositionIsRejected(): void
    {
        $ctx = $this->bootstrapCourse('invalid');

        $this->requestPosition($ctx['teacher'], $ctx['course'], 'diagonal');
        $this->assertResponseStatusCodeSame(400);
        $this->assertSame('above', $this->getStoredPosition($ctx['course']));
    }

    public function testForeignTeacherCannotSaveIntroductionPosition(): void
    {
        $ctx = $this->bootstrapCourse('foreign');

        $this->requestPosition($ctx['foreignTeacher'], $ctx['course'], 'right');
        $this->assertResponseStatusCodeSame(403);
        $this->assertSame('above', $this->getStoredPosition($ctx['course']));
    }

    /**
     * @return array{teacher: User, student: User, foreignTeacher: User, course: Course}
     */
    private function bootstrapCourse(string $suffix): array
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = $this->createCourse('IntroPos '.$suffix);
        $teacher = $this->createUser('intro_pos_t_'.$suffix);
        $student = $this->createUser('intro_pos_s_'.$suffix);
        $foreignTeacher = $this->createUser('intro_pos_f_'.$suffix);
        $foreignCourse = $this->createCourse('IntroPos Foreign '.$suffix);

        $course->addUserAsTeacher($teacher);
        $course->addUserAsStudent($student);
        $courseRepo->update($course);

        $foreignCourse->addUserAsTeacher($foreignTeacher);
        $courseRepo->update($foreignCourse);

        return [
            'teacher' => $teacher,
            'student' => $student,
            'foreignTeacher' => $foreignTeacher,
            'course' => $course,
        ];
    }

    private function requestPosition(User $user, Course $course, string $position): Response
    {
        return $this->createClientWithCredentials($this->getUserTokenFromUser($user))->request(
            'POST',
            '/api/course-settings/introduction-position?cid='.$course->getId(),
            [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => ['position' => $position],
            ]
        );
    }

    private function getStoredPosition(Course $course): string
    {
        self::getContainer()->get(EntityManagerInterface::class)->clear();

        $item = self::getContainer()->get(EntityManagerInterface::class)
            ->getRepository(CCourseSetting::class)
            ->findOneBy([
                'cId' => (int) $course->getId(),
                'variable' => 'introduction_position',
            ])
        ;

        if (!$item instanceof CCourseSetting || null === $item->getValue() || '' === $item->getValue()) {
            return 'above';
        }

        return $item->getValue();
    }
}
