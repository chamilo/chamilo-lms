<?php

/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Zoom\API\MeetingInfoGet;
use Chamilo\PluginBundle\Zoom\API\RecordingMeeting;
use Chamilo\PluginBundle\Zoom\MeetingEntity;
use Chamilo\PluginBundle\Zoom\RecordingEntity;
use Chamilo\PluginBundle\Zoom\RegistrantEntity;

if ('POST' !== $_SERVER['REQUEST_METHOD']) {
    http_response_code(404); // Not found
    exit;
}

$authorizationHeaderValue = apache_request_headers()['authorization']; // TODO handle non-apache installations

require __DIR__.'/config.php';

if (api_get_plugin_setting('zoom', 'verificationToken') !== $authorizationHeaderValue) {
    http_response_code(401); // Unauthorized
    exit;
}

$body = file_get_contents('php://input');
$decoded = json_decode($body);
if (is_null($decoded) || !is_object($decoded) || !isset($decoded->event) || !isset($decoded->payload->object)) {
    error_log(sprintf('Did not recognize event notification: %s', $body));
    http_response_code(422); // Unprocessable Entity
    exit;
}
$object = $decoded->payload->object;
list($objectType, $action) = explode('.', $decoded->event);
switch ($objectType) {
    case 'meeting':
        $meetingRepository = $entityManager->getRepository(MeetingEntity::class);
        $registrantRepository = $entityManager->getRepository(RegistrantEntity::class);
        switch ($action) {
            case 'deleted':
                $meetingEntity = $meetingRepository->find($object->id);
                if (!is_null($meetingEntity)) {
                    $entityManager->remove($meetingEntity);
                }
                break;
            case 'ended':
            case 'started':
                $entityManager->persist(
                    (
                    $meetingRepository->find($object->id)->setStatus($action)
                        ?: (new MeetingEntity())->setMeetingInfoGet(MeetingInfoGet::fromObject($object))
                    )
                );
                $entityManager->flush();
                break;
            case 'participant_joined':
            case 'participant_left':
                $registrant = $registrantRepository->find($object->participant->id);
                if (!is_null($registrant)) {
                    // TODO log attendance
                }
                break;
            case 'alert':
            default:
                error_log(sprintf('Event "%s" on %s was unhandled: %s', $action, $objectType, $body));
                http_response_code(501); // Not Implemented
        }
        break;
    case 'recording':
        $recordingRepository = $entityManager->getRepository(RecordingEntity::class);
        switch ($action) {
            case 'completed':
                $entityManager->persist(
                    (new RecordingEntity())->setRecordingMeeting(RecordingMeeting::fromObject($object))
                );
                $entityManager->flush();
                break;
            case 'recovered':
                if (is_null($recordingRepository->find($object->uuid))) {
                    $entityManager->persist(
                        (new RecordingEntity())->setRecordingMeeting(RecordingMeeting::fromObject($object))
                    );
                    $entityManager->flush();
                }
                break;
            case 'trashed':
            case 'deleted':
                $recordingEntity = $recordingRepository->find($object->uuid);
                if (!is_null($recordingEntity)) {
                    $entityManager->remove($recordingEntity);
                    $entityManager->flush();
                }
                break;
            default:
                error_log(sprintf('Event "%s" on %s was unhandled: %s', $action, $objectType, $body));
                http_response_code(501); // Not Implemented
        }
        break;
    default:
        error_log(sprintf('Event "%s" on %s was unhandled: %s', $action, $objectType, $body));
        http_response_code(501); // Not Implemented
}
