<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Database;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

/**
 * Class LogoutSuccessHandler.
 */
class LogoutSuccessHandler implements LogoutSuccessHandlerInterface
{
    protected $router;
    protected $checker;
    protected $storage;

    /**
     * @param UrlGeneratorInterface         $urlGenerator
     * @param AuthorizationCheckerInterface $checker
     * @param TokenStorageInterface         $storage
     */
    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        AuthorizationCheckerInterface $checker,
        TokenStorageInterface $storage
    ) {
        $this->router = $urlGenerator;
        $this->checker = $checker;
        $this->storage = $storage;
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse|null
     */
    public function onLogoutSuccess(Request $request)
    {
        // Chamilo logout
        $request->getSession()->remove('_locale');
        $request->getSession()->remove('_locale_user');

        if (api_is_global_chat_enabled()) {
            $chat = new \Chat();
            $chat->setUserStatus(0);
        }

        $userId = $this->storage->getToken()->getUser()->getId();

        $tbl_track_login = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);

        $sql = "SELECT login_id, login_date
                FROM $tbl_track_login
                WHERE login_user_id = $userId
                ORDER BY login_date DESC
                LIMIT 0,1";
        $row = Database::query($sql);
        $loginId = null;
        if (Database::num_rows($row) > 0) {
            $loginId = Database::result($row, 0, "login_id");
        }

        $loginAs = $this->checker->isGranted('ROLE_PREVIOUS_ADMIN');
        if (!$loginAs) {
            $current_date = api_get_utc_datetime();
            $sql = "UPDATE $tbl_track_login
                    SET logout_date='".$current_date."'
        		    WHERE login_id='$loginId'";
            Database::query($sql);
        }

        $online_table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ONLINE);
        $query = "DELETE FROM ".$online_table." WHERE login_user_id = $userId";
        Database::query($query);

        /*require_once api_get_path(SYS_PATH) . 'main/chat/chat_functions.lib.php';
        exit_of_chat($userId);*/

        $login = $this->router->generate('home');
        $response = new RedirectResponse($login);

        return $response;
    }
}
