<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Listener;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CoreBundle\Traits\AccessUrlListenerTrait;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

/**
 * Class SessionListener
 * Session entity listener, when a session is created/updated.
 */
class SessionListener
{
    use AccessUrlListenerTrait;

    protected RequestStack $request;
    protected Security $security;

    public function __construct(RequestStack $request, Security $security)
    {
        $this->security = $security;
        $this->request = $request;
    }

    /**
     * This code is executed when a new session is created.
     */
    public function prePersist(Session $session, LifecycleEventArgs $args): void
    {
        $em = $args->getEntityManager();
        if (0 === $session->getUrls()->count()) {
            // The AccessUrl was not added using $resource->addAccessUrl(),
            // try getting the URL from the session if possible.
            $accessUrl = $this->getAccessUrl($em, $this->request);
            if (null === $accessUrl) {
                throw new Exception('This resource needs an AccessUrl use $resource->addAccessUrl();');
            }
            $session->addAccessUrl($accessUrl);
        }
        //$this->checkLimit($repo, $url);
    }

    /**
     * This code is executed when a session is updated.
     */
    public function preUpdate(Session $session, LifecycleEventArgs $args): void
    {
    }

    /*protected function checkLimit(SessionRepository $repo, AccessUrl $url): void
    {
        $limit = $url->getLimitSessions();

        if (!empty($limit)) {
            $count = $repo->getCountSessionByUrl($url);
            if ($count >= $limit) {
                api_warn_hosting_contact('hosting_limit_sessions', $limit);

                throw new \Exception('PortalSessionsLimitReached');
            }
        }
    }*/
}
