<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Entity\CForumThreadQualify;

/**
 * Class ForumThreadLink.
 *
 * @author Bert Steppé
 */
class ForumThreadLink extends AbstractLink
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->set_type(LINK_FORUM_THREAD);
    }

    /**
     * @return string
     */
    public function get_type_name()
    {
        return get_lang('Forum threads');
    }

    /**
     * @return bool
     */
    public function is_allowed_to_change_name()
    {
        return false;
    }

    /**
     * Generate an array of all exercises available.
     *
     * @return array 2-dimensional array - every element contains 2 subelements (id, name)
     */
    public function get_all_links()
    {
        if (empty($this->getCourseId())) {
            return [];
        }

        $tbl_grade_links = Database::get_course_table(TABLE_FORUM_THREAD);
        $sessionId = $this->get_session_id();
        $repo = Container::getForumThreadRepository();
        $course = api_get_course_entity($this->course_id);
        $session = api_get_session_entity($sessionId);

        $qb = $repo->findAllByCourse($course, $session);
        /** @var CForumThread[] $threads */
        $threads = $qb->getQuery()->getResult();
        $cats = [];
        foreach ($threads as $thread) {
            $title = $thread->getTitle();
            $threadQualify = $thread->getThreadTitleQualify();
            if (!empty($threadQualify)) {
                $title = $threadQualify;
            }
            $cats[] = [$thread->getIid(), $title];
        }

        return $cats;
    }

    /**
     * Has anyone done this exercise yet ?
     *
     * @return bool
     */
    public function has_results()
    {
        $table = Database::get_course_table(TABLE_FORUM_POST);

        // TODO(db-neutral): broken today, the unterminated quote is a SQL syntax error (not migrated, not pinned).
        $sql = "SELECT count(*) AS number FROM $table
                WHERE iid = '".$this->get_ref_id();
        $result = Database::query($sql);
        $number = Database::fetch_row($result);

        return 0 != $number[0];
    }

    /**
     * @param int    $studentId
     * @param string $type
     *
     * @return array|null
     */
    public function calc_score($studentId = null, $type = null)
    {
        $threadInfo = $this->getThreadData();
        $sessionId = $this->get_session_id();
        $sessionCondition = api_get_session_condition(
            $sessionId,
            true,
            false,
            'session_id'
        );

        $assignment = Container::getEntityManager()->createQueryBuilder()
            ->select('t.threadQualifyMax AS thread_qualify_max')
            ->from(CForumThread::class, 't')
            ->where('t.iid = :threadId')
            ->setParameter('threadId', $this->get_ref_id())
            ->getQuery()
            ->getOneOrNullResult();
        $threadPeerQualify = (int) (
            $threadInfo['thread_peer_qualify']
            ?? $threadInfo['threadPeerQualify']
            ?? 0
        );
        $threadQualifyMax = (float) (
            $assignment['thread_qualify_max']
            ?? $threadInfo['thread_qualify_max']
            ?? $threadInfo['threadQualifyMax']
            ?? 0.0
        );

        // The qualify table is filtered by its own iid, as the legacy query did.
        $qb = Container::getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(q.user) AS user_id', 'q.qualify AS qualify')
            ->from(CForumThreadQualify::class, 'q')
            ->where('q.iid = :qualifyId')
            ->setParameter('qualifyId', $this->get_ref_id());
        if (isset($studentId)) {
            $qb->andWhere('IDENTITY(q.user) = :studentId')
                ->setParameter('studentId', (int) $studentId);
        }

        // order by id, that way the student's first attempt is accessed first
        $qb->orderBy('q.qualifyTime', 'DESC');
        $scores = $qb->getQuery()->getArrayResult();

        // for 1 student
        if (isset($studentId)) {
            if (0 === $threadPeerQualify) {
                // Classic way of calculate score
                if ($data = ($scores[0] ?? false)) {
                    return [
                        $data['qualify'],
                        $threadQualifyMax,
                    ];
                } else {
                    // We sent the 0/thread_qualify_max instead of null for correct calculations
                    return [0, $threadQualifyMax];
                }
            } else {
                // Take average
                $score = 0;
                $counter = 0;
                foreach ($scores as $data) {
                    $score += $data['qualify'];
                    $counter++;
                }
                // If no result
                if (empty($counter) || $counter <= 2) {
                    return [0, $threadQualifyMax];
                }

                return [$score / $counter, $threadQualifyMax];
            }
        } else {
            // All students -> get average
            $students = []; // user list, needed to make sure we only
            // take first attempts into account
            $counter = 0;
            $sum = 0;
            $bestResult = 0;
            $weight = 0;
            $sumResult = 0;

            foreach ($scores as $data) {
                if (!(array_key_exists($data['user_id'], $students))) {
                    if (0 != $threadQualifyMax) {
                        $students[$data['user_id']] = $data['qualify'];
                        $counter++;
                        $sum += $data['qualify'] / $threadQualifyMax;
                        $sumResult += $data['qualify'];
                        if ($data['qualify'] > $bestResult) {
                            $bestResult = $data['qualify'];
                        }
                        $weight = $threadQualifyMax;
                    }
                }
            }

            if (0 == $counter) {
                return [null, null];
            } else {
                switch ($type) {
                    case 'best':
                        return [$bestResult, $weight];
                        break;
                    case 'average':
                        return [$sumResult / $counter, $weight];
                        break;
                    case 'ranking':
                        return AbstractLink::getCurrentUserRanking($studentId, $students);
                        break;
                    default:
                        return [$sum, $counter];
                        break;
                }
            }
        }
    }

    public function needs_name_and_description()
    {
        return false;
    }

    public function needs_max()
    {
        return false;
    }

    public function needs_results()
    {
        return false;
    }

    /**
     * @return string
     */
    public function get_name()
    {
        $this->getThreadData();
        $thread_title = isset($this->exercise_data['title']) ? $this->exercise_data['title'] : '';
        $thread_title_qualify = isset($this->exercise_data['thread_title_qualify']) ? $this->exercise_data['thread_title_qualify'] : '';
        if (isset($thread_title_qualify) && '' != $thread_title_qualify) {
            return $this->exercise_data['thread_title_qualify'];
        }

        return $thread_title;
    }

    /**
     * @return string
     */
    public function get_description()
    {
        return ''; //$this->exercise_data['description'];
    }

    /**
     * Check if this still links to an exercise.
     */
    public function is_valid_link()
    {
        $sessionId = $this->get_session_id();
        $number = (int) Container::getEntityManager()->createQueryBuilder()
            ->select('COUNT(t.iid)')
            ->from(CForumThread::class, 't')
            ->where('t.iid = :threadId')
            ->setParameter('threadId', $this->get_ref_id())
            ->getQuery()
            ->getSingleScalarResult();

        return 0 != $number;
    }

    public function get_link()
    {
        $sessionId = $this->get_session_id();
        $row = Container::getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(t.forum) AS forum_id')
            ->from(CForumThread::class, 't')
            ->where('t.iid = :threadId')
            ->setParameter('threadId', $this->get_ref_id())
            ->getQuery()
            ->getOneOrNullResult();

        if ($row) {
            $forum_id = (int) $row['forum_id'];
            $courseId = (int) $this->getCourseId();
            $course = Container::getCourseRepository()->find($courseId);
            $nodeId = $course?->getResourceNode()?->getId();

            if (!empty($nodeId)) {
                $query = http_build_query([
                    'cid' => $courseId,
                    'sid' => (int) $sessionId,
                    'gid' => 0,
                    'gradebook' => 'view',
                ]);

                return api_get_path(WEB_PATH).'resources/forum/'.$nodeId.'/forum/'.$forum_id.'/thread/'.$this->get_ref_id().'?'.$query;
            }
        }

        return '';
    }

    public function get_icon_name()
    {
        return 'forum';
    }

    public function save_linked_data()
    {
        $weight = $this->get_weight();
        $ref_id = $this->get_ref_id();

        if (!empty($ref_id)) {
            Container::getEntityManager()->createQueryBuilder()
                ->update(CForumThread::class, 't')
                ->set('t.threadWeight', ':weight')
                ->where('t.iid = :threadId')
                ->setParameter('weight', api_float_val($weight))
                ->setParameter('threadId', $ref_id)
                ->getQuery()
                ->execute();
        }
    }

    public function delete_linked_data()
    {
        $ref_id = $this->get_ref_id();
        if (!empty($ref_id)) {
            // Cleans forum
            Container::getEntityManager()->createQueryBuilder()
                ->update(CForumThread::class, 't')
                ->set('t.threadQualifyMax', '0')
                ->set('t.threadWeight', '0')
                ->set('t.threadTitleQualify', "''")
                ->where('t.iid = :threadId')
                ->setParameter('threadId', $ref_id)
                ->getQuery()
                ->execute();
        }
    }

    private function getThreadData()
    {
        if (!isset($this->exercise_data)) {
            // Only the columns read through $this->exercise_data (get_name() and calc_score()).
            $row = Container::getEntityManager()->createQueryBuilder()
                ->select(
                    't.iid AS iid',
                    't.title AS title',
                    't.threadTitleQualify AS thread_title_qualify',
                    't.threadQualifyMax AS thread_qualify_max',
                    't.threadPeerQualify AS thread_peer_qualify'
                )
                ->from(CForumThread::class, 't')
                ->where('t.iid = :threadId')
                ->setParameter('threadId', (int) $this->get_ref_id())
                ->getQuery()
                ->getOneOrNullResult();
            $this->exercise_data = $row ?? false;
        }

        return $this->exercise_data;
    }
}
