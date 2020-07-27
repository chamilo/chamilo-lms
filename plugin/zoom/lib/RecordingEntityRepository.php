<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Chamilo\ClassificationBundle\Entity\Collection;
use Chamilo\UserBundle\Entity\User;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;

/**
 * Class RecordingEntityRepository.
 */
class RecordingEntityRepository extends EntityRepository
{
    public function getPeriodRecordings($startDate, $endDate)
    {
        $matching = [];
        foreach ($this->findAll() as $candidate) {
            if ($candidate->startDateTime >= $startDate
                && $candidate->startDateTime <= $endDate
            ) {
                $matching[] = $candidate;
            }
        }

        return $matching;
    }

    /**
     * Returns a user's meeting recordings.
     *
     * @param User $user
     *
     * @return ArrayCollection|Collection|RecordingEntity[]
     */
    public function userRecordings($user)
    {
        return $this->matching(
            Criteria::create()->where(
                Criteria::expr()->in(
                    'meeting',
                    $this->getEntityManager()->getRepository(MeetingEntity::class)->userMeetings($user)->toArray()
                )
            )
        );
    }

    /**
     * @param DateTime $start
     * @param DateTime $end
     * @param User     $user
     *
     * @return ArrayCollection|RecordingEntity[]
     */
    public function getPeriodUserRecordings($start, $end, $user)
    {
        return $this->userRecordings($user)->filter(
            function ($meeting) use ($start, $end) {
                return $meeting->startDateTime >= $start
                    && $meeting->startDateTime <= $end;
            }
        );
    }
}
