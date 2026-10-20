<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CGlossary;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * The glossary create, import and export endpoints must act on the course that authorized the request.
 *
 * The contextual teacher roles answer for the `cid` of the query string, so every attack here sends
 * the teacher's own `cid` there and the victim course in the body, the parent node or the resource
 * links. The endpoints answer 200/201 whether or not they honor the body, so each case asserts
 * where the data ended up, not only the status code. The positive cases prove the denials do not
 * come from a teacher the test failed to set up.
 */
final class CGlossaryCourseScopeApiTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private const VICTIM_TERM = 'VictimTerm';
    private const VICTIM_DEFINITION = 'secret-definition';

    /**
     * @var list<string>
     */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            @unlink($path);
        }

        parent::tearDown();
    }

    public function testImportWithReplaceCannotWipeAnotherCourseGlossary(): void
    {
        $ctx = $this->bootstrapScenario('imp_replace');

        $this->importCsv($ctx['teacher'], $ctx['course'], 'AttackerTerm', [
            'cid' => (string) $ctx['foreignCourse']->getId(),
            'replace' => 'true',
        ]);
        $this->assertResponseStatusCodeSame(200);

        $this->assertSame([$this->contextKey($ctx['foreignCourse'])], $this->glossaryContexts(self::VICTIM_TERM));
        $this->assertSame([$this->contextKey($ctx['course'])], $this->glossaryContexts('AttackerTerm'));
    }

    public function testImportCannotInjectTermsIntoAnotherCourse(): void
    {
        $ctx = $this->bootstrapScenario('imp_inject');

        $this->importCsv($ctx['teacher'], $ctx['course'], 'InjectedTerm', [
            'cid' => (string) $ctx['foreignCourse']->getId(),
            'replace' => 'false',
        ]);
        $this->assertResponseStatusCodeSame(200);

        $this->assertSame([$this->contextKey($ctx['course'])], $this->glossaryContexts('InjectedTerm'));
    }

    public function testExportReturnsOnlyTheAuthorizedCourseGlossary(): void
    {
        $ctx = $this->bootstrapScenario('export');

        $content = $this->exportCsv($ctx['teacher'], $ctx['course'], (int) $ctx['foreignCourse']->getId());
        $this->assertResponseStatusCodeSame(200);

        $this->assertStringNotContainsString(self::VICTIM_DEFINITION, $content);
        $this->assertStringContainsString(
            'glossary_course_'.$ctx['course']->getCode(),
            (string) self::getClient()->getResponse()->headers->get('Content-Disposition'),
        );
    }

    public function testExportToDocumentsCannotNestUnderAnotherCourse(): void
    {
        $ctx = $this->bootstrapScenario('doc_parent');
        $documentsBefore = $this->countDocuments();

        $this->exportToDocuments($ctx['teacher'], $ctx['course'], [
            'cid' => (int) $ctx['foreignCourse']->getId(),
            'parentResourceNodeId' => $this->nodeId($ctx['foreignCourse']),
            'resourceLinkList' => [['cid' => (int) $ctx['foreignCourse']->getId(), 'visibility' => 2]],
        ]);
        $this->assertResponseStatusCodeSame(403);

        $this->assertSame($documentsBefore, $this->countDocuments());
    }

    public function testExportToDocumentsCreatesTheDocumentInTheAuthorizedCourse(): void
    {
        $ctx = $this->bootstrapScenario('doc_links');
        $foreignCourseId = (int) $ctx['foreignCourse']->getId();

        $title = $this->exportToDocuments($ctx['teacher'], $ctx['course'], [
            'cid' => $foreignCourseId,
            'parentResourceNodeId' => $this->nodeId($ctx['course']),
            'resourceLinkList' => [
                ['cid' => $foreignCourseId, 'visibility' => 2],
                ['cid' => $foreignCourseId, 'sid' => 0, 'visibility' => 2],
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $this->assertSame([$this->contextKey($ctx['course'])], $this->documentContexts($title));
    }

    public function testCreateCannotNestTermUnderAnotherCourse(): void
    {
        $ctx = $this->bootstrapScenario('create_parent');

        $this->createTerm($ctx['teacher'], $ctx['course'], 'NestedTerm', [
            'cid' => (int) $ctx['foreignCourse']->getId(),
            'parentResourceNodeId' => $this->nodeId($ctx['foreignCourse']),
        ]);
        $this->assertResponseStatusCodeSame(403);

        $this->assertSame([], $this->glossaryContexts('NestedTerm'));
    }

    public function testCreateIgnoresForeignLinksEvenWhenThereAreSeveral(): void
    {
        $ctx = $this->bootstrapScenario('create_links');
        $foreignCourseId = (int) $ctx['foreignCourse']->getId();

        // Two entries skip ResourceListener::normalizeSingleLinkContextFromSession(), so only the
        // controller can keep the term inside the authorized course.
        $this->createTerm($ctx['teacher'], $ctx['course'], 'LinkedTerm', [
            'cid' => $foreignCourseId,
            'resourceLinkList' => json_encode([
                ['cid' => $foreignCourseId, 'visibility' => 2],
                ['cid' => $foreignCourseId, 'sid' => 0, 'visibility' => 2],
            ]),
        ]);
        $this->assertResponseStatusCodeSame(201);

        $this->assertSame([$this->contextKey($ctx['course'])], $this->glossaryContexts('LinkedTerm'));
    }

    public function testCreateDuplicateCheckUsesTheAuthorizedCourse(): void
    {
        $ctx = $this->bootstrapScenario('create_dup');

        // The same title only exists in the victim course: reading the body course would answer
        // "already exists" and disclose that the victim glossary contains the term.
        $this->createTerm($ctx['teacher'], $ctx['course'], self::VICTIM_TERM, [
            'cid' => (int) $ctx['foreignCourse']->getId(),
        ]);
        $this->assertResponseStatusCodeSame(201);

        $contexts = $this->glossaryContexts(self::VICTIM_TERM);
        sort($contexts);
        $expected = [$this->contextKey($ctx['course']), $this->contextKey($ctx['foreignCourse'])];
        sort($expected);
        $this->assertSame($expected, $contexts);
    }

    public function testTeacherCanImportAndExportOwnGlossary(): void
    {
        $ctx = $this->bootstrapScenario('own_import');

        $this->importCsv($ctx['teacher'], $ctx['course'], 'OwnTerm', ['replace' => 'false']);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame([$this->contextKey($ctx['course'])], $this->glossaryContexts('OwnTerm'));

        $content = $this->exportCsv($ctx['teacher'], $ctx['course']);
        $this->assertResponseStatusCodeSame(200);
        $this->assertStringContainsString('OwnTerm', $content);
    }

    public function testTeacherCanExportOwnGlossaryToDocuments(): void
    {
        $ctx = $this->bootstrapScenario('own_doc');

        $title = $this->exportToDocuments($ctx['teacher'], $ctx['course'], [
            'parentResourceNodeId' => $this->nodeId($ctx['course']),
            'resourceLinkList' => [['visibility' => 2]],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $this->assertSame([$this->contextKey($ctx['course'])], $this->documentContexts($title));
    }

    public function testForeignTeacherStillSeesTheirGlossary(): void
    {
        $ctx = $this->bootstrapScenario('foreign_view');

        $this->importCsv($ctx['teacher'], $ctx['course'], 'AttackerTerm', [
            'cid' => (string) $ctx['foreignCourse']->getId(),
            'replace' => 'true',
        ]);
        $this->assertResponseStatusCodeSame(200);

        $terms = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['foreignTeacher']))->request(
            'GET',
            '/api/glossaries?cid='.$ctx['foreignCourse']->getId().'&sid=0',
        )->toArray();
        $this->assertResponseStatusCodeSame(200);

        $this->assertSame([self::VICTIM_TERM], array_column($terms, 'title'));
        $this->assertSame(self::VICTIM_DEFINITION, $terms[0]['description']);
    }

    public function testImportUsesTheAuthorizedSession(): void
    {
        $ctx = $this->bootstrapScenario('session');

        // The API request of the bootstrap rebooted the kernel: reload what the sessions point to.
        $em = $this->getEntityManager();
        $course = $em->find(Course::class, $ctx['course']->getId());
        $teacher = $em->find(User::class, $ctx['teacher']->getId());

        /** @var SessionRepository $sessionRepo */
        $sessionRepo = self::getContainer()->get(SessionRepository::class);

        $session = $this->createSession('GLS authorized session');
        $session->addCourse($course)->addGeneralCoach($teacher);
        $sessionRepo->update($session);

        $otherSession = $this->createSession('GLS other session');
        $otherSession->addCourse($course);
        $sessionRepo->update($otherSession);

        $this->importCsv($teacher, $course, 'SessionTerm', [
            'cid' => (string) $course->getId(),
            'sid' => (string) $otherSession->getId(),
            'replace' => 'false',
        ], $session);
        $this->assertResponseStatusCodeSame(200);

        $this->assertSame([$this->contextKey($course, $session)], $this->glossaryContexts('SessionTerm'));
    }

    /**
     * @return array{teacher: User, course: Course, foreignTeacher: User, foreignCourse: Course}
     */
    private function bootstrapScenario(string $suffix): array
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = $this->createCourse('GLSA'.$suffix);
        $foreignCourse = $this->createCourse('GLSB'.$suffix);
        $teacher = $this->createUser('gls_teacher_'.$suffix);
        $foreignTeacher = $this->createUser('gls_fteacher_'.$suffix);

        $course->addUserAsTeacher($teacher);
        $courseRepo->update($course);
        $foreignCourse->addUserAsTeacher($foreignTeacher);
        $courseRepo->update($foreignCourse);

        $this->createTerm($foreignTeacher, $foreignCourse, self::VICTIM_TERM, [
            'description' => self::VICTIM_DEFINITION,
        ]);
        $this->assertResponseStatusCodeSame(201);

        return [
            'teacher' => $teacher,
            'course' => $course,
            'foreignTeacher' => $foreignTeacher,
            'foreignCourse' => $foreignCourse,
        ];
    }

    private function nodeId(Course $course): int
    {
        return (int) $course->getResourceNode()?->getId();
    }

    private function query(Course $course, ?Session $session = null): string
    {
        return '?cid='.$course->getId().'&sid='.(int) $session?->getId().'&gid=0';
    }

    private function contextKey(Course $course, ?Session $session = null): string
    {
        return $course->getId().':'.(int) $session?->getId();
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createTerm(User $teacher, Course $course, string $title, array $overrides = []): void
    {
        $this->createClientWithCredentials($this->getUserTokenFromUser($teacher))->request(
            'POST',
            '/api/glossaries'.$this->query($course),
            ['json' => array_merge([
                'title' => $title,
                'description' => 'definition of '.$title,
                'cid' => (int) $course->getId(),
                'sid' => 0,
                'parentResourceNodeId' => $this->nodeId($course),
                'resourceLinkList' => json_encode([['visibility' => 2]]),
            ], $overrides)],
        );
    }

    /**
     * @param array<string, string> $parameters
     */
    private function importCsv(
        User $teacher,
        Course $course,
        string $term,
        array $parameters,
        ?Session $session = null,
    ): void {
        $path = (string) tempnam(sys_get_temp_dir(), 'gls');
        $this->temporaryFiles[] = $path;
        file_put_contents($path, "term,definition,category\n".$term.",imported definition,\n");

        $this->createClientWithCredentials($this->getUserTokenFromUser($teacher))->request(
            'POST',
            '/api/glossaries/import'.$this->query($course, $session),
            [
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'extra' => [
                    'parameters' => array_merge([
                        'file_type' => 'csv',
                        'cid' => (string) $course->getId(),
                        'sid' => (string) (int) $session?->getId(),
                    ], $parameters),
                    'files' => ['file' => new UploadedFile($path, 'glossary.csv', 'text/csv', null, true)],
                ],
            ],
        );
    }

    private function exportCsv(User $teacher, Course $course, ?int $bodyCourseId = null): string
    {
        $this->createClientWithCredentials($this->getUserTokenFromUser($teacher))->request(
            'POST',
            '/api/glossaries/export'.$this->query($course),
            [
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'extra' => [
                    'parameters' => [
                        'format' => 'csv',
                        'cid' => (string) ($bodyCourseId ?? $course->getId()),
                        'sid' => '',
                    ],
                ],
            ],
        );

        // A BinaryFileResponse only has a body in the browser's internal response, which captured
        // the streamed file; the HttpFoundation response returns an empty string.
        return (string) self::getClient()->getInternalResponse()->getContent();
    }

    /**
     * The 2.0 endpoint answers with the path of the generated PDF, whose file name is the
     * title of the created document. An error answers with an object instead.
     *
     * @param array<string, mixed> $payload
     */
    private function exportToDocuments(User $teacher, Course $course, array $payload): string
    {
        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($teacher))->request(
            'POST',
            '/api/glossaries/export_to_documents'.$this->query($course),
            ['json' => array_merge(['cid' => (int) $course->getId(), 'sid' => 0], $payload)],
        );

        $path = json_decode((string) $response->getContent(false), true);

        return \is_string($path) ? basename($path) : '';
    }

    /**
     * Every course:session pair the glossary terms with this title are linked to.
     *
     * @return list<string>
     */
    private function glossaryContexts(string $title): array
    {
        $em = $this->getEntityManager();
        $em->clear();

        $contexts = [];
        foreach ($em->getRepository(CGlossary::class)->findBy(['title' => $title]) as $glossary) {
            foreach ($glossary->getResourceNode()?->getResourceLinks() ?? [] as $link) {
                $contexts[] = $link->getCourse()?->getId().':'.(int) $link->getSession()?->getId();
            }
        }

        return $contexts;
    }

    /**
     * @return list<string>
     */
    private function documentContexts(string $title): array
    {
        $em = $this->getEntityManager();
        $em->clear();

        $contexts = [];
        foreach ($em->getRepository(CDocument::class)->findBy(['title' => $title]) as $document) {
            foreach ($document->getResourceNode()?->getResourceLinks() ?? [] as $link) {
                $contexts[] = $link->getCourse()?->getId().':'.(int) $link->getSession()?->getId();
            }
        }

        return $contexts;
    }

    private function countDocuments(): int
    {
        return $this->getEntityManager()->getRepository(CDocument::class)->count([]);
    }
}
