<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Repository\MessageRepository;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

final class AnnouncementEmailPreviewApiTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testPreviewRendersTheDraftWithoutSavingOrSendingAndRespectsContext(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $course = $this->createCourse('Announcement preview '.$suffix);
        $foreign = $this->createCourse('Foreign announcement preview '.$suffix);
        $teacherName = 'announcement_teacher_'.$suffix;
        $teacher = $this->createUser($teacherName);
        $learner = $this->createUser('announcement_learner_'.$suffix);
        $course->addUserAsTeacher($teacher);
        $course->addSubscriptionForUser($learner, 0, null, CourseRelUser::STUDENT);
        $foreign->addUserAsTeacher($teacher);
        $courseRepository = self::getContainer()->get(CourseRepository::class);
        $courseRepository->update($course);
        $courseRepository->update($foreign);
        $cid = (int) $course->getId();
        $foreignId = (int) $foreign->getId();
        $courseTitle = $course->getTitle();
        $teacherToken = $this->getUserTokenFromUser($teacher);
        $learnerToken = $this->getUserTokenFromUser($learner);
        $client = $this->createClientWithCredentials($teacherToken);

        $saved = $client->request('POST', '/api/announcement?cid='.$cid, [
            'json' => [
                'title' => 'Original subject '.$suffix,
                'content' => '<p>Original content.</p>',
                'recipients' => ['everyone'],
                'sendByEmail' => false,
                'sendCopyToSelf' => false,
            ],
        ])->toArray();
        self::assertResponseIsSuccessful();
        $id = (int) $saved['id'];
        self::assertGreaterThan(0, $id);
        $connection = $this->getEntityManager()->getConnection();
        $countBefore = (int) $connection->fetchOne('SELECT COUNT(*) FROM c_announcement');
        $messagesBefore = self::getContainer()->get(MessageRepository::class)->count([]);
        $original = $connection->fetchAssociative('SELECT title, content, email_sent FROM c_announcement WHERE iid = ?', [$id]);

        $payload = [
            'title' => '<b>Unsaved subject</b>',
            'content' => '<p><strong>Unsaved content</strong> ((user_name))</p>',
            'recipients' => ['everyone'],
            'sendByEmail' => true,
            'sendCopyToSelf' => true,
        ];
        $preview = $client->request('POST', '/api/announcement/preview?cid='.$cid, ['json' => $payload])->toArray();
        self::assertResponseIsSuccessful();
        self::assertNotEmpty($preview['previewRecipients']);
        self::assertSame($courseTitle.' - Unsaved subject', $preview['previewEmail']['subject']);
        self::assertStringContainsString($teacherName, $preview['previewEmail']['recipient']);
        self::assertStringContainsString('<strong>Unsaved content</strong>', $preview['previewEmail']['html']);
        self::assertStringContainsString($teacherName, $preview['previewEmail']['html']);
        self::assertStringNotContainsString('((user_name))', $preview['previewEmail']['html']);
        self::assertStringContainsString('<html', $preview['previewEmail']['html']);

        $payload['previewAnnouncementId'] = $id;
        $payload['title'] = 'Changed but not saved';
        $preview = $client->request('POST', '/api/announcement/preview?cid='.$cid, ['json' => $payload])->toArray();
        self::assertResponseIsSuccessful();
        self::assertSame($courseTitle.' - Changed but not saved', $preview['previewEmail']['subject']);
        self::assertStringNotContainsString('Original content.', $preview['previewEmail']['html']);

        $client->request('POST', '/api/announcement/preview?cid='.$foreignId, ['json' => $payload]);
        self::assertResponseStatusCodeSame(403);
        $learnerClient = $this->createClientWithCredentials($learnerToken);
        unset($payload['previewAnnouncementId']);
        $learnerClient->request('POST', '/api/announcement/preview?cid='.$cid, ['json' => $payload]);
        self::assertResponseStatusCodeSame(403);

        $connection = $this->getEntityManager()->getConnection();
        self::assertSame($countBefore, (int) $connection->fetchOne('SELECT COUNT(*) FROM c_announcement'));
        self::assertSame($original, $connection->fetchAssociative('SELECT title, content, email_sent FROM c_announcement WHERE iid = ?', [$id]));
        self::assertSame($messagesBefore, self::getContainer()->get(MessageRepository::class)->count([]));
    }
}
