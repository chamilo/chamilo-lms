<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CDropboxFile;
use Chamilo\CourseBundle\Entity\CDropboxPerson;
use Chamilo\CourseBundle\Repository\CDropboxFileRepository;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Regression tests for the Dropbox file download advisory.
 *
 * The single-file endpoints only compared the file's course with the request course, so any
 * member of the course could read a file sent to somebody else. They now answer for the
 * uploader and the recipients of the file only, like the sent and received lists.
 */
final class DropboxFileRecipientAuthorizationTest extends WebTestCase
{
    use ChamiloTestTrait;

    /**
     * @return array{client: KernelBrowser, course: Course, uploader: User, recipient: User, classmate: User, file: CDropboxFile}
     */
    private function bootstrap(string $suffix): array
    {
        $client = self::createClient();

        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = $this->createCourse('Dropbox Recipient '.$suffix);
        $course->setVisibility(Course::REGISTERED);
        $uploader = $this->createUser('dropbox_uploader_'.$suffix);
        $recipient = $this->createUser('dropbox_recipient_'.$suffix);
        $classmate = $this->createUser('dropbox_classmate_'.$suffix);
        $course->addUserAsStudent($uploader);
        $course->addUserAsStudent($recipient);
        $course->addUserAsStudent($classmate);
        $courseRepo->update($course);

        /** @var CDropboxFileRepository $fileRepo */
        $fileRepo = self::getContainer()->get(CDropboxFileRepository::class);
        $file = (new CDropboxFile())
            ->setTitle('private-'.$suffix.'.txt')
            ->setFilename('private-'.$suffix.'.txt')
            ->setFilesize(0)
            ->setUploaderId((int) $uploader->getId())
            ->setLastUploadDate(new DateTime())
            ->setParent($course)
            ->setCreator($uploader)
            ->addCourseLink($course)
            ->setCId((int) $course->getId())
        ;
        $fileRepo->create($file);

        $person = (new CDropboxPerson())
            ->setCId((int) $course->getId())
            ->setFileId((int) $file->getIid())
            ->setUserId((int) $recipient->getId())
        ;
        $em = $this->getEntityManager();
        $em->persist($person);
        $em->flush();

        return [
            'client' => $client,
            'course' => $course,
            'uploader' => $uploader,
            'recipient' => $recipient,
            'classmate' => $classmate,
            'file' => $file,
        ];
    }

    private function get(KernelBrowser $client, User $user, CDropboxFile $file, Course $course, string $suffix = ''): int
    {
        $client->loginUser($user);
        $client->request('GET', '/dropbox/files/'.$file->getIid().$suffix.'?cid='.$course->getId());

        return $client->getResponse()->getStatusCode();
    }

    public function testMetadataIsReturnedToTheUploaderAndTheRecipientOnly(): void
    {
        $ctx = $this->bootstrap('metadata');

        $this->assertSame(Response::HTTP_OK, $this->get($ctx['client'], $ctx['uploader'], $ctx['file'], $ctx['course']));
        $this->assertSame(Response::HTTP_OK, $this->get($ctx['client'], $ctx['recipient'], $ctx['file'], $ctx['course']));
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->get($ctx['client'], $ctx['classmate'], $ctx['file'], $ctx['course']));
    }

    public function testClassmateCannotReadTheFeedbackOfAFileSentToSomeoneElse(): void
    {
        $ctx = $this->bootstrap('feedback_list');

        $this->assertSame(Response::HTTP_OK, $this->get($ctx['client'], $ctx['recipient'], $ctx['file'], $ctx['course'], '/feedback'));
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->get($ctx['client'], $ctx['classmate'], $ctx['file'], $ctx['course'], '/feedback'));
    }

    public function testFeedbackIsAcceptedFromTheRecipientOnly(): void
    {
        $ctx = $this->bootstrap('feedback_create');
        $uri = '/dropbox/files/'.$ctx['file']->getIid().'/feedback?cid='.$ctx['course']->getId();

        $ctx['client']->loginUser($ctx['classmate']);
        $ctx['client']->request('POST', $uri, [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_SEC_FETCH_SITE' => 'same-origin'], '{"text":"from a classmate"}');
        $this->assertSame(Response::HTTP_NOT_FOUND, $ctx['client']->getResponse()->getStatusCode());

        $ctx['client']->loginUser($ctx['recipient']);
        $ctx['client']->request('POST', $uri, [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_SEC_FETCH_SITE' => 'same-origin'], '{"text":"from the recipient"}');
        $this->assertSame(Response::HTTP_CREATED, $ctx['client']->getResponse()->getStatusCode());
    }

    public function testDownloadIsRefusedBeforeLookingForTheFileOfAClassmate(): void
    {
        $ctx = $this->bootstrap('download');

        // An authorized recipient reaches the storage lookup; a classmate is denied before it.
        // The test client exposes the exception dump instead of an HTML error page.
        // Compare the exception's message field, not incidental source-code excerpts.
        $missingFileMessage = '#message: "Resource file not found"';
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->get($ctx['client'], $ctx['recipient'], $ctx['file'], $ctx['course'], '/download'));
        $this->assertStringContainsString($missingFileMessage, (string) $ctx['client']->getResponse()->getContent());

        $this->assertSame(Response::HTTP_NOT_FOUND, $this->get($ctx['client'], $ctx['classmate'], $ctx['file'], $ctx['course'], '/download'));
        $this->assertStringNotContainsString($missingFileMessage, (string) $ctx['client']->getResponse()->getContent());
    }
}
