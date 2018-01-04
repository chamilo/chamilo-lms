<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Listener;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\AccessUrlRelSession;
use Chamilo\CoreBundle\Entity\Repository\SessionRepository;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Chamilo\CoreBundle\Entity\Session;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class SessionListener
 * Session entity listener, when a session is created/updated
 * @package Chamilo\CoreBundle\EventListener
 */
class SessionListener
{
    /**
     * This code is executed when a new session is created.
     *
     * new object : prePersist
     * edited object: preUpdate
     *
     * @param Session $session
     * @param LifecycleEventArgs $args
     *
     * @throws \Exception
     */
    public function prePersist(Session $session, LifecycleEventArgs $args)
    {
        /** @var AccessUrlRelSession $urlRelSession */
        $urlRelSession = $session->getUrls()->first();

        $url = $urlRelSession->getUrl();
        $repo = $args->getEntityManager()->getRepository('ChamiloCoreBundle:Session');

        $this->checkLimit($repo, $url);
    }

    /**
     * This code is executed when a session is updated
     *
     * @param Session $session
     * @param LifecycleEventArgs $args
     * @throws \Exception
     */
    public function preUpdate(Session $session, LifecycleEventArgs $args)
    {
    }

    /**
     * @param SessionRepository $repo
     * @param AccessUrl $url
     * @throws \Exception
     */
    private function checkLimit($repo, AccessUrl $url)
    {
        $limit = $url->getLimitSessions();

        if (!empty($limit)) {
            $count = $repo->getCountSessionByUrl($url);
            if ($count >= $limit) {
                api_warn_hosting_contact('hosting_limit_sessions', $limit);

                throw new \Exception('PortalSessionsLimitReached');
            }
        }
    }
}
