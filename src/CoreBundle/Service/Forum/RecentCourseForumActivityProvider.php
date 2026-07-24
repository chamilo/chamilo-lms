<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Forum;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CourseBundle\Entity\CForumPost;
use DateInterval;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

use const ENT_HTML5;
use const ENT_QUOTES;

final readonly class RecentCourseForumActivityProvider
{
    private const MAX_POSTS = 50;

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function provide(
        Course $course,
        string $topic,
        int $days,
    ): array {
        $topic = trim($topic);
        if (2 > mb_strlen($topic)) {
            throw new InvalidArgumentException('The forum topic must contain at least two characters.');
        }
        if (1 > $days || 365 < $days) {
            throw new InvalidArgumentException('The recent activity range must be between 1 and 365 days.');
        }

        $terms = array_values(array_filter(
            preg_split('/\s+/u', mb_strtolower($topic)) ?: [],
            static fn (string $term): bool => mb_strlen($term) >= 2,
        ));
        $terms = array_slice(array_unique($terms), 0, 8);
        if ([] === $terms) {
            throw new InvalidArgumentException('The forum topic does not contain searchable terms.');
        }

        $since = (new DateTimeImmutable())->sub(new DateInterval('P'.$days.'D'));

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('post', 'thread', 'forum')
            ->from(CForumPost::class, 'post')
            ->innerJoin('post.thread', 'thread')
            ->innerJoin('post.forum', 'forum')
            ->innerJoin('post.resourceNode', 'postNode')
            ->innerJoin('postNode.resourceLinks', 'postLink')
            ->andWhere('postLink.course = :courseId')
            ->andWhere('postLink.session IS NULL')
            ->andWhere('postLink.group IS NULL')
            ->andWhere('post.visible = :visible')
            ->andWhere('(post.status IS NULL OR post.status = :validatedStatus)')
            ->andWhere('post.postDate >= :since')
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('visible', true, Types::BOOLEAN)
            ->setParameter('validatedStatus', CForumPost::STATUS_VALIDATED, Types::INTEGER)
            ->setParameter('since', $since, Types::DATETIME_IMMUTABLE)
            ->orderBy('post.postDate', 'DESC')
            ->addOrderBy('post.iid', 'DESC')
            ->setMaxResults(250)
        ;

        /** @var CForumPost[] $posts */
        $posts = $queryBuilder->getQuery()->getResult();

        $items = [];
        foreach ($posts as $post) {
            $haystack = mb_strtolower(
                $post->getTitle().' '
                .(string) $post->getPostText().' '
                .(string) $post->getThread()?->getTitle().' '
                .(string) $post->getForum()?->getTitle()
            );

            $matched = [];
            foreach ($terms as $term) {
                if (str_contains($haystack, $term)) {
                    $matched[] = $term;
                }
            }

            if ([] === $matched) {
                continue;
            }

            $thread = $post->getThread();
            $forum = $post->getForum();
            if (null === $thread || null === $forum) {
                continue;
            }

            $items[] = [
                'post_id' => (int) $post->getIid(),
                'thread_id' => (int) $thread->getIid(),
                'forum_id' => (int) $forum->getIid(),
                'forum_title' => $forum->getTitle(),
                'thread_title' => $thread->getTitle(),
                'post_title' => $post->getTitle(),
                'author' => $post->getPosterFullName(),
                'date' => $post->getPostDate()->format(DATE_ATOM),
                'matched_terms' => $matched,
                'relevance_score' => \count($matched),
                'snippet' => $this->snippet((string) $post->getPostText()),
                'content_url' => '/resources/forum/'
                    .(int) $course->getResourceNode()?->getId()
                    .'/forum/'
                    .(int) $forum->getIid()
                    .'/thread/'
                    .(int) $thread->getIid()
                    .'?cid='.(int) $course->getId(),
            ];

            if (self::MAX_POSTS <= \count($items)) {
                break;
            }
        }

        usort(
            $items,
            static function (array $left, array $right): int {
                $scoreComparison = (int) $right['relevance_score'] <=> (int) $left['relevance_score'];

                return 0 !== $scoreComparison
                    ? $scoreComparison
                    : strcmp((string) $right['date'], (string) $left['date']);
            },
        );

        return [
            'scope' => 'base_course',
            'course' => [
                'course_id' => (int) $course->getId(),
                'title' => $course->getTitle(),
            ],
            'topic' => $topic,
            'days' => $days,
            'relevant_activity_found' => [] !== $items,
            'match_count' => \count($items),
            'items' => $items,
        ];
    }

    private function snippet(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5);
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        return mb_substr($text, 0, 500);
    }
}
