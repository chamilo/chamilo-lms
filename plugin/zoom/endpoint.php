<?php

/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Zoom\API\RecordingMeeting;
use Chamilo\PluginBundle\Zoom\Meeting;
use Chamilo\PluginBundle\Zoom\MeetingActivity;
use Chamilo\PluginBundle\Zoom\Recording;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response;

require_once __DIR__.'/config.php';

$request = HttpRequest::createFromGlobals();

if (!$request->isMethod('POST')) {
    http_response_code(Response::HTTP_NOT_FOUND);
    exit;
}

$configAccountId = api_get_plugin_setting('zoom', ZoomPlugin::SETTING_ACCOUNT_ID);
$configClientId = api_get_plugin_setting('zoom', ZoomPlugin::SETTING_CLIENT_ID);
$configClientSecret = api_get_plugin_setting('zoom', ZoomPlugin::SETTING_CLIENT_SECRET);
$configSecretToken = api_get_plugin_setting('zoom', ZoomPlugin::SETTING_SECRET_TOKEN);

$isS2SApp = !empty($configAccountId) && !empty($configClientId) && !empty($configClientSecret);
$isJwtApp = !$isS2SApp;

$authorizationHeaderValue = $request->headers->get('Authorization');

if ($isJwtApp && api_get_plugin_setting('zoom', 'verificationToken') !== $authorizationHeaderValue) {
    error_log('verificationToken not valid, please check your zoom configuration');
    http_response_code(Response::HTTP_UNAUTHORIZED);
    exit;
}

$body = file_get_contents('php://input');
$decoded = json_decode($body);

if ('endpoint.url_validation' === $decoded->event) {
    $json = json_encode([
        'plainToken' => $decoded->payload->plainToken,
        'encryptedToken' => hash_hmac('sha256', $decoded->payload->plainToken, $configSecretToken),
    ]);

    echo $json;
    exit();
}

if (is_null($decoded) || !is_object($decoded) || !isset($decoded->event) || !isset($decoded->payload->object)) {
    error_log(sprintf('Did not recognize event notification: %s', $body));
    http_response_code(Response::HTTP_UNPROCESSABLE_ENTITY);
    exit;
}

$object = $decoded->payload->object;
list($objectType, $action) = explode('.', $decoded->event);

$em = Database::getManager();

$meetingRepository = $em->getRepository(Meeting::class);
$meeting = null;
if ($object->id) {
    /** @var Meeting $meeting */
    $meeting = $meetingRepository->findOneBy(['meetingId' => $object->id]);
}

if (null === $meeting) {
    error_log("Meeting not found");
    error_log(sprintf('Event "%s" on %s was unhandled: %s', $action, $objectType, $body));
    http_response_code(Response::HTTP_NOT_FOUND);
    exit;
}

$activity = new MeetingActivity();
$activity->setName($action);
$activity->setType($objectType);
$activity->setEvent(json_encode($object));

switch ($objectType) {
    case 'meeting':
        error_log('Meeting '.$action.' - '.$meeting->getId());
        error_log(print_r($object, 1));

        switch ($action) {
            case 'deleted':
                $em->remove($meeting);
                break;
            case 'ended':
            case 'started':
                $meeting->setStatus($action);
                $meeting->addActivity($activity);
                $em->persist($meeting);
                break;
            default:
                $meeting->addActivity($activity);
                $em->persist($meeting);
                break;
        }
        $em->flush();
        break;
    case 'webinar':
        $meeting->addActivity($activity);
        $em->persist($meeting);
        $em->flush();
        break;
    case 'recording':
        $recordingRepository = $em->getRepository(Recording::class);

        $recordingEntity = null;
        if ($object->uuid) {
            /** @var Recording $recordingEntity */
            $recordingEntity = $recordingRepository->findOneBy(['uuid' => $object->uuid, 'meeting' => $meeting]);
        }

        error_log("Recording: $action");
        error_log(print_r($object, 1));

        switch ($action) {
            case 'completed':
                $recording = new Recording();
                $recording->setRecordingMeeting(RecordingMeeting::fromObject($object));
                $recording->setMeeting($meeting);
                $meeting->addActivity($activity);
                $em->persist($meeting);
                $em->persist($recording);
                $em->flush();
                break;
            case 'recovered':
                /*if (null === $recordingEntity) {
                    $em->persist(
                        (new Recording())->setRecordingMeeting(RecordingMeeting::fromObject($object))
                    );
                    $em->flush();
                }*/
                break;
            case 'trashed':
            case 'deleted':
                $meeting->addActivity($activity);
                if (null !== $recordingEntity) {
                    $recordMeeting = $recordingEntity->getRecordingMeeting();
                    $recordingToDelete = RecordingMeeting::fromObject($object);
                    $files = [];
                    if ($recordingToDelete->recording_files) {
                        foreach ($recordingToDelete->recording_files as $fileToDelete) {
                            foreach ($recordMeeting->recording_files as $file) {
                                if ($fileToDelete->id != $file->id) {
                                    $files[] = $file;
                                }
                            }
                        }
                    }

                    if (empty($files)) {
                        $em->remove($recordingEntity);
                    } else {
                        $recordMeeting->recording_files = $files;
                        $recordingEntity->setRecordingMeeting($recordMeeting);
                        $em->persist($recordingEntity);
                    }
                }
                $em->persist($meeting);
                $em->flush();
                break;
            default:
                $meeting->addActivity($activity);
                $em->persist($meeting);
                $em->flush();
                break;
        }
        break;
    default:
        error_log(sprintf('Event "%s" on %s was unhandled: %s', $action, $objectType, $body));
        http_response_code(501); // Not Implemented
        break;
}
