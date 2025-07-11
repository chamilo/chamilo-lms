<?php
require_once __DIR__.'/../inc/global.inc.php';

use Chamilo\CoreBundle\Helpers\ChamiloHelper;
use ChamiloSession as Session;

$return = $_POST['return'] ?? $_GET['return'] ?? 'index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && !empty($_POST['legal_accept_type'])
    && isset($_POST['legal_accept'])
    && ($uid = api_get_user_id())
) {
    ChamiloHelper::saveUserTermsAcceptance($uid, $_POST['legal_accept_type']);

    Session::write('term_and_condition', null);

    ChamiloHelper::redirectTo($return);
}

ChamiloHelper::displayLegalTermsPage($return);
