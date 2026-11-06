<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\State\Forum;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumCategory;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;

/**
 * forum.first_reply_min_words and forum.subsequent_reply_min_words ask learners for a substantial
 * first message in a thread, then for shorter follow-ups. A refused reply must tell the learner
 * which of the two minimums applies and how many words they wrote, or they cannot fix it. The words
 * are those the learner typed: the HTML of the editor does not add any, and an accented letter does
 * not split a word.
 */
final class ForumReplyWordMinimumTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->updateSetting('forum.first_reply_min_words', '5');
        $this->updateSetting('forum.subsequent_reply_min_words', '3');
    }

    public function testFirstMessageNeedsTheFirstMinimum(): void
    {
        $ctx = $this->bootstrapScenario('first');

        // 4 words, wrapped in markup that str_word_count() used to count as 6 more words
        $response = $this->reply($ctx, '<p style="text-align: left;">Les élèves ont</p><p>raison</p>');
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(
            'Your first message in this thread must contain at least 5 words (currently 4).',
            $response->toArray(false)['error'],
        );

        $this->assertSame(201, $this->reply($ctx, '<p>Les élèves ont tout à fait raison</p>')->getStatusCode());
    }

    public function testNextMessagesNeedTheSubsequentMinimum(): void
    {
        $ctx = $this->bootstrapScenario('next');
        $this->assertSame(201, $this->reply($ctx, '<p>One two three four five</p>')->getStatusCode());

        $response = $this->reply($ctx, '<p>Bien vu</p>');
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(
            'Your next messages in this thread must contain at least 3 words (currently 2).',
            $response->toArray(false)['error'],
        );

        $this->assertSame(201, $this->reply($ctx, '<p>Bien vu, merci</p>')->getStatusCode());
    }

    private function updateSetting(string $name, string $value): void
    {
        self::getContainer()->get(SettingsManager::class)->updateSetting($name, $value);
    }

    /**
     * @param array<string, mixed> $ctx
     */
    private function reply(array $ctx, string $text): mixed
    {
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['learner']));

        return $client->request('POST', '/api/forum_posts/reply?cid='.$ctx['course']->getId(), ['json' => [
            'forumId' => $ctx['forum']->getIid(),
            'threadId' => $ctx['thread']->getIid(),
            'title' => 'Answer',
            'text' => $text,
        ]]);
    }

    /**
     * A course with a teacher and a learner, and a forum thread opened by the teacher.
     *
     * @return array<string, mixed>
     */
    private function bootstrapScenario(string $suffix): array
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);
        $em = $this->getEntityManager();

        $course = $this->createCourse('Forum Word Minimum Course '.$suffix);
        $course->setVisibility(Course::REGISTERED);
        // The messages are compared in English, whatever the language of the test platform
        $course->setCourseLanguageEntity(self::getContainer()->get(LanguageRepository::class)->findByIsoCode('en_US'));
        $teacher = $this->createUser('forum_words_teacher_'.$suffix);
        $learner = $this->createUser('forum_words_learner_'.$suffix);
        $course->addUserAsTeacher($teacher);
        $course->addUserAsStudent($learner);
        $courseRepo->update($course);

        $category = (new CForumCategory())
            ->setTitle('Category '.$suffix)
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $em->persist($category);

        $forum = (new CForum())
            ->setTitle('Forum Word Minimum '.$suffix)
            ->setForumCategory($category)
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $em->persist($forum);

        $thread = (new CForumThread())
            ->setTitle('Thread '.$suffix)
            ->setThreadDate(new DateTime())
            ->setForum($forum)
            ->setUser($teacher)
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $em->persist($thread);

        $opening = (new CForumPost())
            ->setTitle('Opening question '.$suffix)
            ->setPostText('<p>What do you think?</p>')
            ->setThread($thread)
            ->setForum($forum)
            ->setUser($teacher)
            ->setCreator($teacher)
            ->setPostDate(new DateTime())
            ->setVisible(true)
            ->setStatus(CForumPost::STATUS_VALIDATED)
            ->setParent($thread)
            ->addCourseLink($course)
        ;
        $em->persist($opening);
        $em->flush();

        return [
            'course' => $course,
            'learner' => $learner,
            'forum' => $forum,
            'thread' => $thread,
        ];
    }
}
