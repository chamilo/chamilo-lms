<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\TrackELogin;
use Chamilo\CoreBundle\Entity\TrackEOnline;
use Chamilo\CoreBundle\Entity\User;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutListener
{
    protected UrlGeneratorInterface $router;
    protected AuthorizationCheckerInterface $checker;
    protected TokenStorageInterface $storage;
    protected EntityManagerInterface $em;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        AuthorizationCheckerInterface $checker,
        TokenStorageInterface $storage,
        EntityManagerInterface $em
    ) {
        $this->router = $urlGenerator;
        $this->checker = $checker;
        $this->storage = $storage;
        $this->em = $em;
    }

    /**
     * @throws Exception
     */
    public function __invoke(LogoutEvent $event): ?RedirectResponse
    {
        $request = $event->getRequest();

        // Chamilo logout operations
        $request->getSession()->remove('_selected_locale');
        $request->getSession()->remove('_locale');
        $request->getSession()->remove('_locale_user');

        $token = $this->storage->getToken();
        if (null === $token) {
            $login = $this->router->generate('index');

            return new RedirectResponse($login);
        }

        /** @var null|User $user */
        $user = $token->getUser();
        if ($user instanceof User) {
            $userId = $user->getId();

            $trackELoginRepository = $this->em->getRepository(TrackELogin::class);
            $loginAs = $this->checker->isGranted('ROLE_PREVIOUS_ADMIN');
            if (!$loginAs) {
                $currentDate = new DateTime('now', new DateTimeZone('UTC'));
                $trackELoginRepository->updateLastLoginLogoutDate($userId, $currentDate);
            }

            $trackEOnlineRepository = $this->em->getRepository(TrackEOnline::class);
            $trackEOnlineRepository->removeOnlineSessionsByUser($userId);
        }

        $login = $this->router->generate('index');

        return new RedirectResponse($login);
    }
}
