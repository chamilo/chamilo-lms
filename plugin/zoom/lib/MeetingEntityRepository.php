<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\PageBundle\Entity\User;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;

/**
 * Class MeetingEntityRepository.
 */
class MeetingEntityRepository extends EntityRepository
{
    /**
     * Retrieves information about meetings having a start_time between two dates.
     *
     * @param DateTime $startDate
     * @param DateTime $endDate
     *
     * @return MeetingEntity[]
     */
    public function periodMeetings($startDate, $endDate)
    {
        $matching = [];
        $all = $this->findAll();
        foreach ($all as $candidate) {
            if ($candidate->startDateTime >= $startDate
            && $candidate->startDateTime <= $endDate
            ) {
                $matching[] = $candidate;
            }
        }

        return $matching;
    }

    /**
     * @return ArrayCollection|Collection|MeetingEntity[]
     */
    public function globalMeetings()
    {
        return $this->matching(
            Criteria::create()->where(
                Criteria::expr()->andX(
                    Criteria::expr()->eq('course', null),
                    Criteria::expr()->eq('user', null)
                )
            )
        );
    }

    /**
     * @return ArrayCollection|Collection|MeetingEntity[]
     */
    public function unfinishedGlobalMeetings()
    {
        return $this->globalMeetings()->filter(
            function ($meeting) {
                return 'finished' !== $meeting->getMeetingInfoGet()->status;
            }
        );
    }

    /**
     * Returns either a user's meetings or all user meetings.
     *
     * @param User|null $user
     *
     * @return ArrayCollection|Collection|MeetingEntity[]
     */
    public function userMeetings($user = null)
    {
        return $this->matching(
            Criteria::create()->where(
                Criteria::expr()->andX(
                    Criteria::expr()->eq('course', null),
                    is_null($user)
                        ? Criteria::expr()->neq('user', null)
                        : Criteria::expr()->eq('user', $user)
                )
            )
        );
    }

    /**
     * @param User|null $user
     *
     * @return ArrayCollection|Collection|MeetingEntity[]
     */
    public function unfinishedUserMeetings($user = null)
    {
        return $this->userMeetings($user)->filter(
            function ($meeting) {
                return 'finished' !== $meeting->getMeetingInfoGet()->status;
            }
        );
    }

    /**
     * @param DateTime  $start
     * @param DateTime  $end
     * @param User|null $user
     *
     * @return ArrayCollection|Collection|MeetingEntity[]
     */
    public function periodUserMeetings($start, $end, $user = null)
    {
        return $this->userMeetings($user)->filter(
            function ($meeting) use ($start, $end) {
                return $meeting->startDateTime >= $start
                    && $meeting->startDateTime <= $end;
            }
        );
    }

    /**
     * Returns either a course's meetings or all course meetings.
     *
     * @param Course|null  $course
     * @param Session|null $session
     *
     * @return ArrayCollection|Collection|MeetingEntity[]
     */
    public function courseMeetings($course = null, $session = null)
    {
        return $this->matching(
            Criteria::create()->where(
                is_null($course)
                    ? Criteria::expr()->neq('course', null)
                    : Criteria::expr()->andX(
                        Criteria::expr()->eq('course', $course),
                        Criteria::expr()->eq('session', $session)
                    )
            )
        );
    }
}
