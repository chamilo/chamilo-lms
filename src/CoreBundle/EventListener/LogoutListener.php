<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\User;
use Database;
use Doctrine\DBAL\Cache\ArrayStatement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
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
     * @return null|RedirectResponse
     */
    public function onSymfonyComponentSecurityHttpEventLogoutEvent(LogoutEvent $event)
    {
        $request = $event->getRequest();

        // Chamilo logout
        $request->getSession()->remove('_locale');
        $request->getSession()->remove('_locale_user');

        /*if (api_is_global_chat_enabled()) {
            $chat = new \Chat();
            $chat->setUserStatus(0);
        }*/
        $token = $this->storage->getToken();
        /** @var null|User $user */
        $user = $token->getUser();
        if ($user instanceof User) {
            $userId = $user->getId();
            $table = Database:: get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);

            $sql = "SELECT login_id, login_date
                    FROM {$table}
                    WHERE login_user_id = {$userId}
                    ORDER BY login_date DESC
                    LIMIT 0,1";
            $loginId = null;
            $connection = $this->em->getConnection();
            /** @var ArrayStatement $result */
            $result = $connection->executeQuery($sql);
            if ($result->rowCount() > 0) {
                $row = $result->fetchAssociative();
                if ($row) {
                    $loginId = $row['login_id'];
                }
            }

            $loginAs = $this->checker->isGranted('ROLE_PREVIOUS_ADMIN');
            if (!$loginAs) {
                $current_date = api_get_utc_datetime();
                $sql = "UPDATE {$table}
                        SET logout_date='".$current_date."'
                        WHERE login_id='{$loginId}'";
                $connection->executeQuery($sql);
            }

            $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ONLINE);
            $sql = "DELETE FROM $table WHERE login_user_id = $userId";
            $connection->executeQuery($sql);
        }

        $login = $this->router->generate('home');

        return new RedirectResponse($login);

        //return new JsonResponse('logout out', 200);
    }
}
