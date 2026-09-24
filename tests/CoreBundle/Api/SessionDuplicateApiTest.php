<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * POST /api/sessions/{id}/duplicate replaces the 1.11.x create_session_from_model webservice
 * the EFC integration calls to open each new training run from a model session. The copy must
 * keep the model's courses and staff (general coaches, session admin) but start empty of
 * people who belong to one run only: course coaches, HR managers and students are
 * re-subscribed by the integration itself.
 */
class SessionDuplicateApiTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testDuplicateCopiesTheModelStructureButNotItsLearners(): void
    {
        $sessionAdmin = $this->createUser('dup_model_session_admin', '', '', 'ROLE_SESSION_MANAGER');
        $courseCoach = $this->createUser('dup_model_course_coach');
        $drh = $this->createUser('dup_model_drh');
        $student = $this->createUser('dup_model_student');
        $course = $this->createCourse('Duplicate model course');

        $model = $this->createSession('Duplicate model session');
        $model
            ->addSessionAdmin($sessionAdmin)
            ->addUserInSession(Session::DRH, $drh)
            ->addUserInSession(Session::STUDENT, $student)
            ->addCourse($course)
        ;
        $model->addUserInCourse(Session::COURSE_COACH, $courseCoach, $course);
        $model->addUserInCourse(Session::STUDENT, $student, $course);
        $this->getEntityManager()->flush();

        $client = $this->createClientWithCredentials($this->getUserToken());
        $client->request('POST', '/api/sessions/'.$model->getId().'/duplicate', [
            'json' => [
                'title' => 'Duplicate run 2026',
                'startDate' => '2026-10-01T09:00:00+02:00',
                'endDate' => '2026-12-15T18:00:00+01:00',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);

        $copy = self::getContainer()->get(SessionRepository::class)->find($client->getResponse()->toArray()['id']);
        $this->getEntityManager()->refresh($copy);

        $this->assertNotSame($model->getId(), $copy->getId());
        $this->assertSame('Duplicate run 2026', $copy->getTitle(), 'The requested title replaces copy()\'s "<title> Copy".');

        // The requested dates are stored in UTC and applied to all three date pairs.
        foreach ([$copy->getAccessStartDate(), $copy->getDisplayStartDate(), $copy->getCoachAccessStartDate()] as $start) {
            $this->assertSame('2026-10-01 07:00:00', $start->format('Y-m-d H:i:s'));
        }
        foreach ([$copy->getAccessEndDate(), $copy->getDisplayEndDate(), $copy->getCoachAccessEndDate()] as $end) {
            $this->assertSame('2026-12-15 17:00:00', $end->format('Y-m-d H:i:s'));
        }

        $this->assertTrue($copy->hasCourse($course), 'The model\'s courses are part of the structure.');
        $this->assertTrue($copy->hasUserAsGeneralCoach($this->getUser('admin')));
        $this->assertTrue(
            $copy->hasUserAsSessionAdmin($sessionAdmin),
            'The model\'s session admin keeps managing the copy, not the API caller.'
        );
        $this->assertFalse($copy->hasUserAsSessionAdmin($this->getUser('admin')));

        $this->assertFalse($copy->hasUserInCourse($courseCoach, $course), 'Course coaches belong to one run only.');
        $this->assertFalse($copy->hasUserInCourse($student, $course), 'Students are re-subscribed by the integration.');
        $this->assertTrue($copy->getUsers()->filter(
            fn ($subscription) => \in_array($subscription->getUser()->getId(), [$drh->getId(), $student->getId()], true)
        )->isEmpty(), 'HR managers and students of the model are not subscribed to the copy.');
    }

    /**
     * Documents written from the learning path editor carry the 'html' filetype. The restorer
     * used to accept only 'file', so they vanished from the copy and the copied learning path
     * items were left pointing at nothing.
     */
    public function testSessionContentCopyKeepsHtmlDocuments(): void
    {
        $course = $this->createCourse('Duplicate content course');
        $model = $this->createSession('Duplicate content model');
        $model->addCourse($course);
        $this->getEntityManager()->flush();

        $documentRepo = self::getContainer()->get(CDocumentRepository::class);
        $document = (new CDocument())
            ->setFiletype('html')
            ->setTitle('Intro written in the LP editor')
            ->setTemplate(false)
            ->setReadonly(false)
            ->setParent($course)
            ->setCreator($this->getUser('admin'))
            ->addCourseLink($course, $model, null, ResourceLink::VISIBILITY_PUBLISHED)
        ;
        $documentRepo->create($document);
        $documentRepo->addFileFromString($document, 'intro.html', 'text/html', '<p>Welcome</p>', true);

        $client = $this->createClientWithCredentials($this->getUserToken());
        $client->request('POST', '/api/sessions/'.$model->getId().'/duplicate', [
            'json' => [
                'title' => 'Duplicate content run',
                'startDate' => '2026-10-01T09:00:00Z',
                'endDate' => '2026-12-15T18:00:00Z',
                'copySessionContent' => true,
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $copyId = $client->getResponse()->toArray()['id'];
        $copies = $this->getEntityManager()->createQueryBuilder()
            ->select('d')
            ->from(CDocument::class, 'd')
            ->innerJoin('d.resourceNode', 'n')
            ->innerJoin('n.resourceLinks', 'l')
            ->where('l.session = :session')
            ->andWhere('d.title = :title')
            ->setParameter('session', $copyId)
            ->setParameter('title', 'Intro written in the LP editor')
            ->getQuery()
            ->getResult()
        ;

        $this->assertCount(1, $copies, 'The html document of the model session must be copied into the new one.');
        $this->assertSame('html', $copies[0]->getFiletype(), 'The copy keeps the learning path document type.');
    }

    public function testSessionManagerCannotDuplicateASessionTheyDoNotManage(): void
    {
        $model = $this->createSession('Duplicate foreign model');
        $manager = $this->createUser('dup_foreign_manager', '', '', 'ROLE_SESSION_MANAGER');

        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($manager));
        $client->request('POST', '/api/sessions/'.$model->getId().'/duplicate', [
            'json' => [
                'title' => 'Duplicate stolen run',
                'startDate' => '2026-10-01T09:00:00Z',
                'endDate' => '2026-12-15T18:00:00Z',
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);
        $this->assertNull(
            self::getContainer()->get(SessionRepository::class)->findOneBy(['title' => 'Duplicate stolen run'])
        );
    }

    public function testRejectedRequestLeavesNoSessionBehind(): void
    {
        $model = $this->createSession('Duplicate model for rejection');
        $repo = self::getContainer()->get(SessionRepository::class);
        $before = $repo->count([]);

        $client = $this->createClientWithCredentials($this->getUserToken());
        $client->request('POST', '/api/sessions/'.$model->getId().'/duplicate', [
            'json' => [
                'title' => 'Duplicate with unknown field',
                'startDate' => '2026-10-01T09:00:00Z',
                'endDate' => '2026-12-15T18:00:00Z',
                'extraFields' => ['no_such_session_field' => 'x'],
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame($before, $repo->count([]), 'Validation happens before copy(), so nothing is half-created.');
    }
}
