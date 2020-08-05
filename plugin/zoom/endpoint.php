<?php

/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Zoom\API\RecordingMeeting;
use Chamilo\PluginBundle\Zoom\MeetingEntity;
use Chamilo\PluginBundle\Zoom\RecordingEntity;
use Chamilo\PluginBundle\Zoom\RegistrantEntity;
use Symfony\Component\HttpFoundation\Response;

if ('POST' !== $_SERVER['REQUEST_METHOD']) {
    http_response_code(Response::HTTP_NOT_FOUND); // Not found
    exit;
}

// @todo handle non-apache installations
$authorizationHeaderValue = apache_request_headers()['Authorization'];

require_once __DIR__.'/config.php';

if (api_get_plugin_setting('zoom', 'verificationToken') !== $authorizationHeaderValue) {
    http_response_code(Response::HTTP_UNAUTHORIZED);
    exit;
}

$body = file_get_contents('php://input');
$decoded = json_decode($body);
if (is_null($decoded) || !is_object($decoded) || !isset($decoded->event) || !isset($decoded->payload->object)) {
    error_log(sprintf('Did not recognize event notification: %s', $body));
    http_response_code(Response::HTTP_UNPROCESSABLE_ENTITY);
    exit;
}

$object = $decoded->payload->object;
list($objectType, $action) = explode('.', $decoded->event);

$em = Database::getManager();

$meetingRepository = $em->getRepository(MeetingEntity::class);
$meetingEntity = null;
if ($object->id) {
    /** @var MeetingEntity $meetingEntity */
    $meetingEntity = $meetingRepository->findOneBy(['meetingId' => $object->id]);
}

switch ($objectType) {
    case 'meeting':
        $registrantRepository = $em->getRepository(RegistrantEntity::class);

        if (null === $meetingEntity) {
            exit;
        }

        error_log('Meeting '.$action.' - '.$meetingEntity->getId());
        error_log(print_r($object, 1));

        switch ($action) {
            case 'deleted':
                $em->remove($meetingEntity);
                $em->flush();
                break;
            case 'ended':
            case 'started':
                $meetingEntity->setStatus($action);
                $em->persist($meetingEntity);
                $em->flush();
                break;
            case 'participant_joined':
            case 'participant_left':
                error_log('Participant: #'.$object->participant->id);
                error_log(print_r($object->participant, 1));
                /*$registrant = $registrantRepository->findOneBy(['meeting' => $meetingEntity, '' => $object->participant->id]);
                if (null === $registrant) {
                    exit;
                }*/
                break;
            default:
                error_log(sprintf('Event "%s" on %s was unhandled: %s', $action, $objectType, $body));

                http_response_code(Response::HTTP_NOT_IMPLEMENTED); // Not Implemented
        }
        break;
    case 'recording':
        $recordingRepository = $em->getRepository(RecordingEntity::class);

        $recordingEntity = null;
        if ($object->uuid) {
            $recordingEntity = $recordingRepository->findBy(['uuid' => $object->uuid, 'meeting' => $meetingEntity]);
        }

        error_log("Recording: $action");
        error_log(print_r($object, 1));

        switch ($action) {
            case 'completed':
                $recording = new RecordingEntity();
                $recording->setRecordingMeeting(RecordingMeeting::fromObject($object));
                $recording->setMeeting($meetingEntity);
                $em->persist($recording);
                $em->flush();
                break;
            case 'recovered':
                if (null === $recordingEntity) {
                    $em->persist(
                        (new RecordingEntity())->setRecordingMeeting(RecordingMeeting::fromObject($object))
                    );
                    $em->flush();
                }
                break;
            case 'trashed':
            case 'deleted':
                if (null !== $recordingEntity) {
                    $em->remove($recordingEntity);
                    $em->flush();
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
