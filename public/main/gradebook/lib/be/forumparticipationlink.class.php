<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CForumThread;

/**
 * Class ForumParticipationLink.
 *
 * Scores a student by the number of messages posted in a forum thread:
 * 0 messages -> 0, exactly 1 -> pointsOne, 2 or more -> pointsMany.
 *
 * Designed to be used inside a POINTS_SUM gradebook category: calc_score()
 * returns [points, 1] and the link weight is expected to be 1, so the item
 * contributes its fixed points (pointsOne/pointsMany) directly.
 */
class ForumParticipationLink extends AbstractLink
{
    private $forum_thread_table;

    public function __construct()
    {
        parent::__construct();
        $this->set_type(LINK_FORUM_PARTICIPATION);
    }

    public function get_type_name()
    {
        return get_lang('Forum participation');
    }

    public function is_allowed_to_change_name(): bool
    {
        return false;
    }

    /**
     * List of forum threads available to attach this link to.
     *
     * @return array 2-dimensional array - every element contains 2 subelements (id, name)
     */
    public function get_all_links()
    {
        if (empty($this->getCourseId())) {
            return [];
        }

        $repo = Container::getForumThreadRepository();
        $course = api_get_course_entity($this->getCourseId());
        $session = api_get_session_entity($this->get_session_id());

        $qb = $repo->findAllByCourse($course, $session);
        /** @var CForumThread[] $threads */
        $threads = $qb->getQuery()->getResult();

        $cats = [];
        foreach ($threads as $thread) {
            $cats[] = [$thread->getIid(), $thread->getTitle()];
        }

        return $cats;
    }

    public function has_results(): bool
    {
        return $this->countPosts(null) > 0;
    }

    /**
     * @param int    $studentId
     * @param string $type
     *
     * @return array|null
     */
    public function calc_score($studentId = null, $type = null)
    {
        $pointsOne = (float) ($this->get_points_one() ?? 0);
        $pointsMany = (float) ($this->get_points_many() ?? 0);

        // The maximum a student can earn here is pointsMany; it is used as the item max so the
        // per-item display stays within 0-100% and, with weight = pointsMany in POINTS_SUM,
        // the contribution equals the earned points (score/max × weight = score).
        $max = $pointsMany > 0 ? $pointsMany : 1.0;

        // Aggregate (all students) is not meaningful for a fixed-points item.
        if (!isset($studentId)) {
            return [null, null];
        }

        $count = $this->countPosts((int) $studentId);

        if (0 === $count) {
            $score = 0.0;
        } elseif (1 === $count) {
            $score = $pointsOne;
        } else {
            $score = $pointsMany;
        }

        return [$score, $max];
    }

    public function needs_name_and_description(): bool
    {
        return false;
    }

    public function needs_max(): bool
    {
        return false;
    }

    public function needs_results(): bool
    {
        return false;
    }

    public function get_name()
    {
        $thread = $this->getThread();

        return $thread ? $thread->getTitle() : '';
    }

    public function get_description()
    {
        $one = $this->get_points_one();
        $many = $this->get_points_many();

        if (null === $one && null === $many) {
            return '';
        }

        // Surfaces both scoring values to the teacher, since the weight column only shows pointsMany.
        return get_lang('Points for one message').': '.api_float_val($one)
            .' · '.get_lang('Points for two or more messages').': '.api_float_val($many);
    }

    public function is_valid_link(): bool
    {
        return null !== $this->getThread();
    }

    public function get_link()
    {
        $thread = $this->getThread();
        if (null === $thread) {
            return '';
        }

        $forumId = $thread->getForum() ? $thread->getForum()->getIid() : 0;

        return api_get_path(WEB_CODE_PATH).'forum/viewthread.php?'.
            api_get_cidreq_params($this->getCourseId(), $this->get_session_id()).
            '&thread='.$this->get_ref_id().'&gradebook=view&forum='.$forumId;
    }

    public function get_icon_name(): string
    {
        return 'forum';
    }

    /**
     * Count visible posts authored by a student in the linked thread.
     * Passing null counts every student's posts.
     */
    private function countPosts(?int $studentId): int
    {
        $table = Database::get_course_table(TABLE_FORUM_POST);
        $threadId = $this->get_ref_id();

        $sql = "SELECT COUNT(iid) AS number
                FROM $table
                WHERE thread_id = $threadId
                  AND visible = 1";
        if (null !== $studentId) {
            $sql .= ' AND poster_id = '.$studentId;
        }

        $result = Database::query($sql);
        $row = Database::fetch_array($result);

        return (int) ($row['number'] ?? 0);
    }

    private function getThread(): ?CForumThread
    {
        $refId = $this->get_ref_id();
        if (empty($refId)) {
            return null;
        }

        return Container::getForumThreadRepository()->find($refId);
    }
}
