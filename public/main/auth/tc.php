<?php
require_once __DIR__.'/../inc/global.inc.php';

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\ChamiloHelper;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use ChamiloSession as Session;

$return = $_POST['return'] ?? $_GET['return'] ?? '/home';

$canAccept = true;
$infoMessage = '';

$userId = 0;
$termData = Session::read('term_and_condition');
if (!empty($termData['user_id'])) {
    $userId = (int) $termData['user_id'];
} else {
    $userId = api_get_user_id();
}

$isPlatformAdmin = api_is_platform_admin();
if ('true' === api_get_setting('registration.allow_terms_conditions') && !$isPlatformAdmin) {
    if ('true' === api_get_setting('profile.show_terms_if_profile_completed')) {
        if (!empty($userId)) {
            $userInfo = api_get_user_info($userId);
            if ($userInfo && ANONYMOUS != $userInfo['status']) {
                $extraFieldValue = new ExtraFieldValue('user');
                $value = $extraFieldValue->get_values_by_handler_and_field_variable(
                    $userId,
                    'termactivated'
                );

                $termActivated = false;
                if (isset($value['value'])) {
                    $termActivated = !empty($value['value']) && 1 === (int) $value['value'];
                }

                if (false === $termActivated) {
                    $canAccept = false;
                    $infoMessage = Display::return_message(
                        get_lang('The terms and conditions have not yet been validated by your tutor.'),
                        'warning',
                        false
                    );
                }

                if ($canAccept && 1 !== (int) $userInfo['profile_completed']) {
                    $canAccept = false;
                    $infoMessage .= Display::return_message(
                        get_lang('You must first fill your profile to enable the terms and conditions validation.'),
                        'warning',
                        true
                    );
                }
            }
        }
    }
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && !empty($_POST['legal_accept_type'])
    && (isset($_POST['legal_accept']) || api_get_setting('registration.hide_legal_accept_checkbox') === 'true')
) {
    if (!$canAccept) {
        ChamiloHelper::displayLegalTermsPage($return, false, $infoMessage);
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

ChamiloHelper::displayLegalTermsPage($return, $canAccept, $infoMessage);
