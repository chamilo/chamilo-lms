<?php

/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Zoom\API\MeetingInfoGet;
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

require __DIR__.'/config.php';

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

switch ($objectType) {
    case 'meeting':
        $meetingRepository = $em->getRepository(MeetingEntity::class);
        $registrantRepository = $em->getRepository(RegistrantEntity::class);

        $meetingEntity = null;
        if ($object->id) {
            /** @var MeetingEntity $meetingEntity */
            $meetingEntity = $meetingRepository->findBy(['meetingId' => $object->id]);
        }

        error_log("Meeting: $action");

        switch ($action) {
            case 'deleted':
                if (null !== $meetingEntity) {
                    error_log('Meeting deleted '.$meetingEntity->getId());
                    $em->remove($meetingEntity);
                }
                break;
            case 'ended':
            case 'started':
                error_log("Meeting $action #".$meetingEntity->getId());
                $em->persist(
                    (
                    $meetingEntity->setStatus($action)
                        ?: (new MeetingEntity())->setMeetingInfoGet(MeetingInfoGet::fromObject($object))
                    )
                );
                $em->flush();
                break;
            case 'participant_joined':
            case 'participant_left':
                // @todo check find
                $registrant = $registrantRepository->find($object->participant->id);
                if (null === $registrant) {
                    exit;
                }
                // TODO log attendance
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
            $recordingEntity = $recordingRepository->findBy(['uuid' => $object->uuid]);
        }

        error_log("Recording: $action");
        switch ($action) {
            case 'completed':
                $em->persist(
                    (new RecordingEntity())->setRecordingMeeting(RecordingMeeting::fromObject($object))
                );
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
