<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\UserBundle\Entity\User;
use DateTime;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class OnlineListener
 * Adds objects into the session like the old global.inc.
 *
 * @package Chamilo\CoreBundle\EventListener
 */
class OnlineListener
{
    protected $context;
    protected $em;

    /**
     * @param TokenStorageInterface $context
     * @param ObjectManager         $em
     */
    public function __construct(TokenStorageInterface $context, ObjectManager $em)
    {
        $this->em = $em;
        $this->context = $context;
    }

    /**
     * Update the user "lastActivity" on each request.
     *
     * @param FilterControllerEvent $event
     */
    public function onCoreController(FilterControllerEvent $event)
    {
        /*  Here we are checking that the current request is a "MASTER_REQUEST",
            and ignore any subrequest in the process (for example when doing a
            render() in a twig template)*/

        if ($event->getRequestType() !== HttpKernel::MASTER_REQUEST) {
            return;
        }

        // We are checking a token authentication is available before using the User
        if ($this->context->getToken()) {
            $user = $this->context->getToken()->getUser();

            /* We are using a delay during which the user will be considered as
            still active, in order to avoid too much UPDATE in the database*/
            $delay = new \DateTime();
            $delay->setTimestamp(strtotime('2 minutes ago'));
            // We are checking the User class in order to be certain we can call "getLastActivity".
            if ($user instanceof User && $user->getLastLogin() < $delay) {
                // User
                $user->setLastLogin(new DateTime());

                $this->em->flush($user);
            }
        }
    }
}
