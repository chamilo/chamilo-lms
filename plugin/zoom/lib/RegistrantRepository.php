<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Chamilo\UserBundle\Entity\User;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityRepository;

/**
 * Class RegistrantEntityRepository.
 */
class RegistrantRepository extends EntityRepository
{
    /**
     * Returns the upcoming meeting registrations for the given user.
     *
     * @param User $user
     *
     * @return array|Registrant[]
     */
    public function meetingsComingSoonRegistrationsForUser($user)
    {
        $start = new DateTime();
        $end = new DateTime();
        $end->add(new DateInterval('P7D'));
        $meetings = $this->getEntityManager()->getRepository(Meeting::class)->periodMeetings($start, $end);

        return $this->findBy(['meeting' => $meetings, 'user' => $user]);
    }
}
