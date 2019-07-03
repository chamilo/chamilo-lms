<?php
/* For licensing terms, see /license.txt */

use Chamilo\UserBundle\Entity\User;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Audio\Wav;

$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

$action = isset($_POST['action']) ? $_POST['action'] : 'enrollment';
$license = !empty($_POST['license']) ? true : false;
$isEnrollment = 'enrollment' === $action;
$isAuthentify = 'authentify' === $action;

$isAllowed = true;

if ($isEnrollment) {
    api_block_anonymous_users(false);

    $isAllowed = !empty($_FILES['audio']);
} elseif ($isAuthentify) {
    $isAllowed = !empty($_POST['username']) && !empty($_FILES['audio']);
}

if (!$isAllowed) {
    echo Display::return_message(get_lang('NotAllowed'), 'error');

    exit;
}

$plugin = WhispeakAuthPlugin::create();

$plugin->protectTool(false);

if ($isAuthentify) {
    $em = Database::getManager();
    /** @var User|null $user */
    $user = $em->getRepository('ChamiloUserBundle:User')->findOneBy(['username' => $_POST['username']]);
} else {
    /** @var User $user */
    $user = api_get_user_entity(api_get_user_id());
}

if (empty($user)) {
    echo Display::return_message(get_lang('NoUser'), 'error');

    exit;
}

$path = api_upload_file('whispeakauth', $_FILES['audio'], $user->getId());

if (false === $path) {
    echo Display::return_message(get_lang('UploadError'), 'error');

    exit;
}

$newFullPath = $originFullPath = api_get_path(SYS_UPLOAD_PATH).'whispeakauth'.$path['path_to_save'];
$fileType = mime_content_type($originFullPath);

if ('wav' !== substr($fileType, -3)) {
    $directory = dirname($originFullPath);
    $newFullPath = $directory.'/audio.wav';

    try {
        $ffmpeg = FFMpeg::create();

        $audio = $ffmpeg->open($originFullPath);
        $audio->save(new Wav(), $newFullPath);
    } catch (Exception $exception) {
        echo Display::return_message($exception->getMessage(), 'error');

        exit;
    }
}

if ($isEnrollment) {
    try {
        $wsid = $plugin->generateWsid();

        $wsid = $plugin->license($wsid['wsid'], $license);

        $enrollmentResult = $plugin->enrollment($wsid['wsid'], $user, $newFullPath);
    } catch (Exception $exception) {
        echo Display::return_message($plugin->get_lang('EnrollmentFailed'));

        exit;
    }

    $reliability = (int) $enrollmentResult['reliability'];
    $qualityNote = !empty($enrollmentResult['quality']) ? explode('|', $enrollmentResult['quality']) : [];
    $qualityNote = array_map('ucfirst', $qualityNote);

    if ($reliability <= 0) {
        $message = $plugin->get_lang('EnrollmentSignature0');
    } else {
        $plugin->saveEnrollment($user, $enrollmentResult['wsid']);

        $message = '<strong>'.$plugin->get_lang('EnrollmentSuccess').'</strong>';
        $message .= PHP_EOL;
        $message .= $plugin->get_lang("EnrollmentSignature$reliability");
    }
    
    foreach ($qualityNote as $note) {
        $message .= PHP_EOL.'<br>'.$plugin->get_lang("AudioQuality$note");
    }

    echo Display::return_message(
        $message,
        $reliability <= 0 ? 'error' : 'success',
        false
    );
}

if ($isAuthentify) {
    $wsid = WhispeakAuthPlugin::getAuthUidValue($user->getId());

    try {
        if (empty($wsid)) {
            throw new Exception($plugin->get_lang('AuthentifyFailed'));
        }

        $authentifyResult = $plugin->authentify($wsid->getValue(), $newFullPath);
    } catch (Exception $exception) {
        echo Display::return_message($plugin->get_lang('AuthentifyFailed'), 'error');

        exit;
    }

    $success = (bool) $authentifyResult['result'];
    $qualityNote = !empty($authentifyResult['quality']) ? explode('|', $authentifyResult['quality']) : [];
    $qualityNote = array_map('ucfirst', $qualityNote);

    if (!$success) {
        $message = $plugin->get_lang('TryAgain');
    } else {
        $message = $plugin->get_lang('AuthentifySuccess');
    }

    foreach ($qualityNote as $note) {
        $message .= PHP_EOL.'<br>'.$plugin->get_lang("AudioQuality$note");
    }

    echo Display::return_message(
        $message,
        $success ? 'success' : 'warning',
        false
    );

    if ($success) {
        $loggedUser = [
            'user_id' => $user->getId(),
            'status' => $user->getStatus(),
            'uidReset' => true,
        ];

        ChamiloSession::write('_user', $loggedUser);
        Login::init_user($user->getId(), true);

        echo '<script>window.location.href = "'.api_get_path(WEB_PATH).'";</script>';
    }

    exit;
}
