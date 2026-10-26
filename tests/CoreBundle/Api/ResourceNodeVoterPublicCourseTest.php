<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * A course open to the world lets every user read its files, members or not. That is an
 * opening for reading only: changing or removing a file stays with the teachers of the course.
 */
final class ResourceNodeVoterPublicCourseTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testOutsiderReadsButCannotChangeFileOfPublicCourse(): void
    {
        $ctx = $this->bootstrapScenario('outsider');

        // Without context the voter takes the course from the resource itself; with it, the
        // open course hands the outsider the learner role. Neither may grant a write.
        foreach (['', '?cid='.$ctx['course']->getId()] as $query) {
            $token = $this->getUserTokenFromUser($ctx['outsider']);

            // API Platform 3.4 rejects an item read without cid before any access check.
            $this->createClientWithCredentials($token)->request('GET', '/api/documents/'.$ctx['document'].$query);
            $this->assertResponseStatusCodeSame('' === $query ? 400 : 200);

            $this->createClientWithCredentials($token)->request(
                'PUT',
                '/api/documents/'.$ctx['document'].$query,
                ['json' => ['title' => 'changed by outsider']]
            );
            $this->assertResponseStatusCodeSame(403);

            $this->createClientWithCredentials($token)->request('DELETE', '/api/documents/'.$ctx['document'].$query);
            $this->assertResponseStatusCodeSame(403);
        }
    }

    /**
     * @return array{course: Course, teacher: User, outsider: User, document: int}
     */
    private function bootstrapScenario(string $suffix): array
    {
        $_SERVER['REMOTE_ADDR'] = 'localhost';

        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = $this->createCourse('RNV public '.$suffix);
        $course->setVisibility(Course::OPEN_WORLD);

        $teacher = $this->createUser('rnv_pub_teacher_'.$suffix);
        $outsider = $this->createUser('rnv_pub_outsider_'.$suffix);

        $course->addUserAsTeacher($teacher);
        $courseRepo->update($course);

        return [
            'course' => $course,
            'teacher' => $teacher,
            'outsider' => $outsider,
            'document' => $this->document($course),
        ];
    }

    /**
     * Uploads a published file document to the course as admin and returns its iid.
     */
    private function document(Course $course): int
    {
        $file = $this->getUploadedFile();

        $adminToken = $this->getUserToken(['username' => 'admin', 'password' => 'admin'], true);
        $response = $this->createClientWithCredentials($adminToken)->request(
            'POST',
            '/api/documents?cid='.$course->getId(),
            [
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'extra' => ['files' => ['uploadFile' => $file]],
                'json' => [
                    'filetype' => 'file',
                    'size' => $file->getSize(),
                    'parentResourceNodeId' => $course->getResourceNode()->getId(),
                    'resourceLinkList' => [
                        ['cid' => $course->getId(), 'visibility' => ResourceLink::VISIBILITY_PUBLISHED],
                    ],
                ],
            ]
        );
        $this->assertResponseStatusCodeSame(201);

        return (int) json_decode($response->getContent())->iid;
    }
}
