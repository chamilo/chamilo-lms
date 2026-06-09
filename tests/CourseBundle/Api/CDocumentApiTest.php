<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

use const JSON_THROW_ON_ERROR;

/**
 * Regression tests for the documents-listing IDOR / draft-leak advisory.
 *
 * The advisory reported that authenticated users outside the course scope could
 * enumerate documents (including DRAFT/unpublished ones) of any course via:
 *
 *     GET /api/documents?cid={courseId}
 *
 * The endpoint declares a custom provider (DocumentCollectionStateProvider) and
 * therefore bypasses CDocumentExtension — losing the course-link visibility
 * filter that excludes drafts for non-teachers. The provider also lacks an
 * is_granted('VIEW', $course) check, so foreign users obtain document metadata
 * from any course (including REGISTERED / private ones).
 *
 * These tests assert the expected post-fix behaviour:
 *  - foreign authenticated user listing a private course's documents → 403
 *  - enrolled student → 200 with PUBLISHED documents only, never DRAFT
 *  - enrolled teacher → 200, may see DRAFT documents
 *  - admin → 200, sees DRAFT documents
 */
final class CDocumentApiTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    /**
     * Builds a private (REGISTERED) course owned by a teacher, subscribes a
     * student and leaves an attacker user out of the course. Adds two documents
     * to the course's root: one PUBLISHED and one DRAFT.
     *
     * @return array{
     *     course: Course,
     *     teacher: User,
     *     student: User,
     *     attacker: User,
     *     publishedDoc: CDocument,
     *     draftDoc: CDocument
     * }
     */
    private function bootstrapDocumentScenario(string $suffix): array
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        /** @var CDocumentRepository $documentRepo */
        $documentRepo = self::getContainer()->get(CDocumentRepository::class);

        $course = $this->createCourse('Doc Sec Course '.$suffix);
        $course->setVisibility(Course::REGISTERED);

        $teacher = $this->createUser('doc_sec_teacher_'.$suffix);
        $student = $this->createUser('doc_sec_student_'.$suffix);
        $attacker = $this->createUser('doc_sec_attacker_'.$suffix);

        $course->addUserAsTeacher($teacher);
        $course->addUserAsStudent($student);
        $courseRepo->update($course);

        $admin = $this->getAdmin();

        $publishedDoc = (new CDocument())
            ->setFiletype('file')
            ->setTitle('published-doc-'.$suffix)
            ->setTemplate(false)
            ->setReadonly(false)
            ->setParent($course)
            ->setCreator($admin)
            ->addCourseLink($course, null, null, ResourceLink::VISIBILITY_PUBLISHED)
        ;
        $documentRepo->create($publishedDoc);

        $draftDoc = (new CDocument())
            ->setFiletype('file')
            ->setTitle('draft-doc-'.$suffix)
            ->setTemplate(false)
            ->setReadonly(false)
            ->setParent($course)
            ->setCreator($admin)
            ->addCourseLink($course, null, null, ResourceLink::VISIBILITY_DRAFT)
        ;
        $documentRepo->create($draftDoc);

        return [
            'course' => $course,
            'teacher' => $teacher,
            'student' => $student,
            'attacker' => $attacker,
            'publishedDoc' => $publishedDoc,
            'draftDoc' => $draftDoc,
        ];
    }

    /**
     * Extracts the list of document titles from a Hydra collection payload.
     *
     * @param array<string, mixed> $payload
     *
     * @return array<int, string>
     */
    private function extractTitles(array $payload): array
    {
        $members = $payload['hydra:member'] ?? [];
        $titles = [];
        foreach ($members as $member) {
            if (isset($member['title'])) {
                $titles[] = (string) $member['title'];
            }
        }

        return $titles;
    }

    // -------------------------------------------------------------------------
    // Cross-course enumeration
    // -------------------------------------------------------------------------

    public function testListDocumentsOfPrivateCourseAsForeignUserIsForbidden(): void
    {
        $ctx = $this->bootstrapDocumentScenario('private_foreign');

        $token = $this->getUserTokenFromUser($ctx['attacker']);

        $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/documents?cid='.$ctx['course']->getId().'&itemsPerPage=5000',
        );

        // After the fix the foreign user has no VIEW on the course → 403.
        // Returning an empty 200 collection would also be acceptable defence,
        // but we prefer the explicit denial (matches the suggested remediation).
        $this->assertResponseStatusCodeSame(403);
    }

    public function testListDocumentsWithoutAnyCidAsForeignUserDoesNotLeakOtherCourses(): void
    {
        $ctx = $this->bootstrapDocumentScenario('no_cid_foreign');

        $token = $this->getUserTokenFromUser($ctx['attacker']);

        $response = $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/documents?itemsPerPage=5000',
        );

        // No cid context → API Platform rejects the request because `cid` is
        // declared as a required QueryParameter on the GetCollection. The
        // validator returns 422 Unprocessable Entity.
        $this->assertResponseStatusCodeSame(422);
    }

    // -------------------------------------------------------------------------
    // Draft visibility — non-teachers must never see DRAFT documents
    // -------------------------------------------------------------------------

    public function testListDocumentsAsEnrolledStudentExcludesDrafts(): void
    {
        $ctx = $this->bootstrapDocumentScenario('student_draft');

        $token = $this->getUserTokenFromUser($ctx['student']);

        $response = $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/documents?cid='.$ctx['course']->getId().'&itemsPerPage=5000',
        );

        $this->assertResponseStatusCodeSame(200);

        $titles = $this->extractTitles($response->toArray());
        $this->assertContains('published-doc-student_draft', $titles, 'Published document must be visible to enrolled student.');
        $this->assertNotContains('draft-doc-student_draft', $titles, 'DRAFT document must be filtered out for students.');
    }

    public function testListDocumentsAsEnrolledTeacherIncludesDrafts(): void
    {
        $ctx = $this->bootstrapDocumentScenario('teacher_draft');

        $token = $this->getUserTokenFromUser($ctx['teacher']);

        $response = $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/documents?cid='.$ctx['course']->getId().'&itemsPerPage=5000',
        );

        $this->assertResponseStatusCodeSame(200);

        $titles = $this->extractTitles($response->toArray());
        $this->assertContains('published-doc-teacher_draft', $titles);
        $this->assertContains('draft-doc-teacher_draft', $titles, 'Teachers must keep access to DRAFT documents.');
    }

    public function testListDocumentsAsAdminIncludesDrafts(): void
    {
        $ctx = $this->bootstrapDocumentScenario('admin_draft');

        // Default getUserToken() returns the admin/admin token.
        $response = $this->createClientWithCredentials()->request(
            'GET',
            '/api/documents?cid='.$ctx['course']->getId().'&itemsPerPage=5000',
        );

        $this->assertResponseStatusCodeSame(200);

        $titles = $this->extractTitles($response->toArray());
        $this->assertContains('published-doc-admin_draft', $titles);
        $this->assertContains('draft-doc-admin_draft', $titles);
    }

    // -------------------------------------------------------------------------
    // Unauthenticated access
    // -------------------------------------------------------------------------

    public function testListDocumentsRequiresAuthentication(): void
    {
        $ctx = $this->bootstrapDocumentScenario('auth_required');

        $response = self::createClient()->request(
            'GET',
            '/api/documents?cid='.$ctx['course']->getId(),
        );

        // The API firewall is JWT-gated. Without a token Symfony evaluates the
        // operation-level `is_granted('ROLE_USER')` expression against an
        // anonymous user and denies — yielding 401 (when an entry point sets
        // the challenge) or 403 (default JWT firewall behaviour). Either is a
        // valid "unauthenticated" outcome; what must never happen is a 200
        // with data.
        $status = $response->getStatusCode();
        $this->assertContains(
            $status,
            [401, 403],
            'Expected 401 or 403 for an unauthenticated request, got '.$status
        );
    }

    // -------------------------------------------------------------------------
    // Audit findings — separate from the original advisory.
    // The helpers below piggy-back on bootstrapDocumentScenario(); each test
    // documents a distinct cross-course / IDOR vector found while auditing.
    // -------------------------------------------------------------------------

    /**
     * H1 — `GET /api/documents/{iid}/lp-usage` declares only `ROLE_USER` and
     * the action does not check VIEW on the target document. A foreign user
     * must not be able to enumerate the learning paths that reference a
     * document of a private course they are not enrolled in.
     */
    public function testLpUsageOfPrivateDocumentAsForeignUserIsForbidden(): void
    {
        $ctx = $this->bootstrapDocumentScenario('lp_usage_foreign');

        $token = $this->getUserTokenFromUser($ctx['attacker']);

        $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/documents/'.$ctx['publishedDoc']->getIid().'/lp-usage',
        );

        // After the fix the endpoint must require VIEW on object.resourceNode.
        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * H2 — `GET /api/documents/{cid}/usage` returns storage quotas and
     * breakdowns of any course as long as the caller is authenticated.
     * A foreign user must not be able to read the operational metrics of a
     * private course they are not enrolled in.
     */
    public function testUsageOfPrivateCourseAsForeignUserIsForbidden(): void
    {
        $ctx = $this->bootstrapDocumentScenario('usage_foreign');

        $token = $this->getUserTokenFromUser($ctx['attacker']);

        $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/documents/'.$ctx['course']->getId().'/usage',
        );

        // After the fix the controller must check CourseVoter::VIEW.
        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * H3 — `POST /api/documents` does not require `cid` and does not validate
     * EDIT on every course referenced in `resourceLinkList`. A teacher of
     * course A must not be able to create a document linked to course B.
     */
    public function testCreateDocumentLinkedToForeignCourseIsForbidden(): void
    {
        $victimCtx = $this->bootstrapDocumentScenario('create_victim');
        $attackerCtx = $this->bootstrapDocumentScenario('create_attacker');

        // The attacker is a teacher in their own course but NOT in victim's.
        $attackerTeacher = $attackerCtx['teacher'];
        $token = $this->getUserTokenFromUser($attackerTeacher);

        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/documents?cid='.$attackerCtx['course']->getId(),
            [
                'json' => [
                    'title' => 'injected-from-foreign-course',
                    'filetype' => 'folder',
                    'parentResourceNodeId' => $victimCtx['course']->getResourceNode()->getId(),
                    'resourceLinkList' => [[
                        'cid' => $victimCtx['course']->getId(),
                        'visibility' => ResourceLink::VISIBILITY_PUBLISHED,
                    ]],
                ],
            ]
        );

        // After the fix any resourceLinkList entry pointing to a course where
        // the user is not teacher/admin must be rejected.
        $this->assertResponseStatusCodeSame(403);

        // Sanity: no rogue document landed in the victim course.
        $em = $this->getEntityManager();
        $em->clear();
        $rogue = $em->getRepository(CDocument::class)
            ->findOneBy(['title' => 'injected-from-foreign-course'])
        ;
        $this->assertNull($rogue, 'A foreign teacher must not be able to seed documents in another course.');
    }

    /**
     * H4 — `POST /api/documents/{iid}/replace` is gated by global/contextual
     * teacher roles instead of `is_granted('EDIT', object.resourceNode)`. A
     * teacher of course A must not be able to replace the binary of a
     * document that belongs to course B.
     */
    public function testReplaceDocumentOfForeignCourseIsForbidden(): void
    {
        $victimCtx = $this->bootstrapDocumentScenario('replace_victim');
        $attackerCtx = $this->bootstrapDocumentScenario('replace_attacker');

        $attackerTeacher = $attackerCtx['teacher'];
        $token = $this->getUserTokenFromUser($attackerTeacher);

        $file = $this->getUploadedFile();

        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/documents/'.$victimCtx['publishedDoc']->getIid().'/replace?cid='.$attackerCtx['course']->getId(),
            [
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'extra' => ['files' => ['file' => $file]],
            ]
        );

        // After the fix the operation must require EDIT on object.resourceNode.
        $this->assertResponseStatusCodeSame(403);
    }

    // -------------------------------------------------------------------------
    // Phase 3 — Regression tests for contextual-role migration (issue #8486).
    //
    // The following endpoints used to require only `ROLE_USER`, letting any
    // authenticated user invoke them. They now require contextual roles
    // (ROLE_CURRENT_COURSE_STUDENT / ROLE_CURRENT_COURSE_SESSION_STUDENT)
    // populated by CourseContextRoleListener once CidReqListener has resolved
    // the `cid` query parameter.
    // -------------------------------------------------------------------------

    public function testDownloadSelectedAsForeignUserIsForbidden(): void
    {
        $ctx = $this->bootstrapDocumentScenario('dl_sel_foreign');

        $token = $this->getUserTokenFromUser($ctx['attacker']);

        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/documents/download-selected?cid='.$ctx['course']->getId(),
            [
                'headers' => [
                    'Accept' => 'application/zip',
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode([
                    'ids' => [$ctx['publishedDoc']->getIid()],
                    'compressed' => true,
                ], JSON_THROW_ON_ERROR),
            ]
        );

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDownloadSelectedWithoutCidIsForbidden(): void
    {
        $ctx = $this->bootstrapDocumentScenario('dl_sel_no_cid');

        $token = $this->getUserTokenFromUser($ctx['student']);

        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/documents/download-selected',
            [
                'headers' => [
                    'Accept' => 'application/zip',
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode([
                    'ids' => [$ctx['publishedDoc']->getIid()],
                    'compressed' => true,
                ], JSON_THROW_ON_ERROR),
            ]
        );

        // Without cid the listener cannot publish contextual roles → security
        // expression fails even for an otherwise-enrolled student.
        $this->assertResponseStatusCodeSame(403);
    }

    public function testDownloadSelectedAsEnrolledStudentIsAllowed(): void
    {
        $ctx = $this->bootstrapDocumentScenario('dl_sel_student');

        $token = $this->getUserTokenFromUser($ctx['student']);

        $response = $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/documents/download-selected?cid='.$ctx['course']->getId(),
            [
                'headers' => [
                    'Accept' => 'application/zip',
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode([
                    'ids' => [$ctx['publishedDoc']->getIid()],
                    'compressed' => true,
                ], JSON_THROW_ON_ERROR),
            ]
        );

        // Security must pass for the enrolled student. The controller may
        // still fail later if the physical file is missing in the test
        // environment, but the security gate must not reject (403).
        $this->assertNotSame(
            403,
            $response->getStatusCode(),
            'Enrolled student with valid cid must pass the security expression.'
        );
    }

    public function testDownloadAllAsForeignUserIsForbidden(): void
    {
        $ctx = $this->bootstrapDocumentScenario('dl_all_foreign');

        $token = $this->getUserTokenFromUser($ctx['attacker']);

        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/documents/download-all?cid='.$ctx['course']->getId(),
            [
                'headers' => [
                    'Accept' => 'application/zip',
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode([
                    'rootNodeId' => $ctx['course']->getResourceNode()->getId(),
                ], JSON_THROW_ON_ERROR),
            ]
        );

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDownloadAllWithoutCidIsForbidden(): void
    {
        $ctx = $this->bootstrapDocumentScenario('dl_all_no_cid');

        $token = $this->getUserTokenFromUser($ctx['student']);

        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/documents/download-all',
            [
                'headers' => [
                    'Accept' => 'application/zip',
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode([
                    'rootNodeId' => $ctx['course']->getResourceNode()->getId(),
                ], JSON_THROW_ON_ERROR),
            ]
        );

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDownloadAllAsEnrolledStudentIsAllowed(): void
    {
        $ctx = $this->bootstrapDocumentScenario('dl_all_student');

        $token = $this->getUserTokenFromUser($ctx['student']);

        $response = $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/documents/download-all?cid='.$ctx['course']->getId(),
            [
                'headers' => [
                    'Accept' => 'application/zip',
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode([
                    'rootNodeId' => $ctx['course']->getResourceNode()->getId(),
                ], JSON_THROW_ON_ERROR),
            ]
        );

        $this->assertNotSame(
            403,
            $response->getStatusCode(),
            'Enrolled student with valid cid must pass the security expression.'
        );
    }
}
