<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\XApi\ToolLaunch;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Xabbuh\XApi\Common\Exception\NotFoundException;
use Xabbuh\XApi\Common\Exception\XApiException;
use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\Agent;
use Xabbuh\XApi\Model\InverseFunctionalIdentifier;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\State;

require_once __DIR__.'/../../../main/inc/global.inc.php';

$request = HttpRequest::createFromGlobals();

$course = api_get_course_entity();
$session = api_get_session_entity();

if (!$request->isXmlHttpRequest()
    || !api_is_allowed_to_edit()
    || !$course
) {
    echo Display::return_message(get_lang('NotAllowed'), 'error');
    exit;
}

$plugin = XApiPlugin::create();
$em = Database::getManager();

$toolLaunch = $em->find(
    ToolLaunch::class,
    $request->request->getInt('tool')
);

$student = api_get_user_entity($request->request->getInt('student'));

if (!$toolLaunch || !$student) {
    echo Display::return_message(get_lang('NoResults'), 'error');
    exit;
}

$userIsSubscribedToCourse = CourseManager::is_user_subscribed_in_course(
    $student->getId(),
    $course->getCode(),
    !!$session,
    $session ? $session->getId() : 0
);

if (!$userIsSubscribedToCourse) {
    echo Display::return_message(get_lang('NotAllowed'), 'error');
    exit;
}

$cidReq = api_get_cidreq();

$xApiStateClient = $plugin->getXApiStateClient(
    $toolLaunch->getLrsUrl(),
    $toolLaunch->getLrsAuthUsername(),
    $toolLaunch->getLrsAuthPassword()
);

$activity = new Activity(
    IRI::fromString($toolLaunch->getActivityId())
);

$actor = new Agent(
    InverseFunctionalIdentifier::withMbox(
        IRI::fromString('mailto:'.$student->getEmail())
    ),
    $student->getCompleteName()
);

try {
    $stateDocument = $xApiStateClient->getDocument(
        new State(
            $activity,
            $actor,
            $plugin->generateIri('tool-'.$toolLaunch->getId(), 'state')->getValue()
        )
    );
} catch (NotFoundException $notFoundException) {
    echo Display::return_message($notFoundException->getMessage(), 'error');
    exit;
} catch (XApiException $exception) {
    echo Display::return_message($exception->getMessage(), 'error');
    exit;
}

if ($stateDocument) {
    $table = new HTML_Table(['class' => 'data_table table table-bordered']);
    $table->setHeaderContents(0, 0, $plugin->get_lang('ActivityFirstLaunch'));
    $table->setHeaderContents(0, 1, $plugin->get_lang('ActivityLastLaunch'));

    $i = 1;

    foreach ($stateDocument->getData()->getData() as $attempt) {
        $firstLaunch = api_convert_and_format_date(
            $attempt[XApiPlugin::STATE_FIRST_LAUNCH],
            DATE_TIME_FORMAT_LONG
        );
        $lastLaunch = api_convert_and_format_date(
            $attempt[XApiPlugin::STATE_LAST_LAUNCH],
            DATE_TIME_FORMAT_LONG
        );

        $table->setCellContents($i, 0, $firstLaunch);
        $table->setCellContents($i, 1, $lastLaunch);

        $i++;
    }

    $table->updateColAttributes(0, ['class' => 'text-center']);
    $table->updateColAttributes(1, ['class' => 'text-center']);

    $table->display();
}
