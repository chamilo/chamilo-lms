<?php
require_once __DIR__.'/../inc/global.inc.php';

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\ChamiloHelper;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use ChamiloSession as Session;

$return = $_POST['return'] ?? $_GET['return'] ?? 'index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && !empty($_POST['legal_accept_type'])
    && isset($_POST['legal_accept'])
) {
    $userId = 0;
    $termData = Session::read('term_and_condition');
    if (!empty($termData['user_id'])) {
        $userId = (int)$termData['user_id'];
    } else {
        $userId = api_get_user_id();
    }

    if ($userId > 0) {
        ChamiloHelper::saveUserTermsAcceptance($userId, $_POST['legal_accept_type']);

        // Re-login in Symfony security
        $userEntity = api_get_user_entity($userId);
        if ($userEntity) {
            $token = new UsernamePasswordToken(
                $userEntity,
                'main',
                $userEntity->getRoles()
            );

            $tokenStorage = Container::getTokenStorage();
            $tokenStorage->setToken($token);

            // Save the token to session so the firewall recognizes it on the next request
            $session = Container::getSession();
            if ($session) {
                $session->set('_security_main', serialize($token));
            }
        }

        Session::write('term_and_condition', null);

        ChamiloHelper::redirectTo($return);
    } else {
        die('Error: Unable to identify user accepting terms.');
    }
}

ChamiloHelper::displayLegalTermsPage($return);
