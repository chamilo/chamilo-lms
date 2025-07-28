<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CourseBundle\Entity\CGroup;;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\ConferenceActivity;
use Chamilo\CoreBundle\Entity\ConferenceMeeting;
use Chamilo\CoreBundle\Entity\ConferenceRecording;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class VideoConferenceCallbackController
{
    public function __invoke(Request $request, EntityManagerInterface $em): Response
    {
        // 1) Decode JSON payload
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return new Response('Invalid JSON', Response::HTTP_BAD_REQUEST);
        }

        // 2) Find or create the ConferenceMeeting by remoteId + provider
        $meetingRepo = $em->getRepository(ConferenceMeeting::class);
        $meeting = $meetingRepo->findOneBy([
            'remoteId'        => $payload['meeting_id']   ?? null,
            'serviceProvider' => $payload['provider']     ?? null,
        ]) ?? new ConferenceMeeting();

        // 3) Map all conference_meeting fields
        // --- associations via getReference() to avoid extra queries ---
        if (isset($payload['c_id'])) {
            // Try to actually fetch the Course entity
            $course = $em->find(Course::class, (int)$payload['c_id']);
            if ($course !== null) {
                $meeting->setCourse($course);
            }
        }
        if (isset($payload['session_id'])) {
            $session = $em->find(Session::class, (int)$payload['session_id']);
            if ($session !== null) {
                $meeting->setSession($session);
            }
        }
        if (isset($payload['access_url_id'])) {
            $url = $em->find(AccessUrl::class, (int)$payload['access_url_id']);
            if ($url) {
                $meeting->setAccessUrl($url);
            }
        }
        if (isset($payload['group_id'])) {
            $group = $em->find(CGroup::class, (int)$payload['group_id']);
            if ($group) {
                $meeting->setGroup($group);
            }
        }
        if (isset($payload['user_id'])) {
            $user = $em->find(User::class, (int)$payload['user_id']);
            if ($user) {
                $meeting->setUser($user);
            }
        }
        if (isset($payload['calendar_id'])) {
            $meeting->setCalendarId((int)$payload['calendar_id']);
        }

        // --- simple fields ---
        $meeting
            ->setServiceProvider($payload['provider']     ?? $meeting->getServiceProvider())
            ->setRemoteId($payload['meeting_id']          ?? $meeting->getRemoteId())
            ->setInternalMeetingId($payload['internal_meeting_id'] ?? $meeting->getInternalMeetingId())
            ->setTitle($payload['title']                  ?? $meeting->getTitle())
            ->setAttendeePw($payload['attendee_pw']       ?? $meeting->getAttendeePw())
            ->setModeratorPw($payload['moderator_pw']     ?? $meeting->getModeratorPw())
            ->setRecord((bool)($payload['record']         ?? $meeting->isRecord()))
            ->setStatus((int)($payload['status']          ?? $meeting->getStatus()))
            ->setWelcomeMsg($payload['welcome_msg']       ?? $meeting->getWelcomeMsg())
            ->setVisibility((int)($payload['visibility']  ?? $meeting->getVisibility()))
            ->setVoiceBridge($payload['voice_bridge']     ?? $meeting->getVoiceBridge())
            ->setVideoUrl($payload['video_url']           ?? $meeting->getVideoUrl())
            ->setHasVideoM4v((bool)($payload['has_video_m4v'] ?? $meeting->isHasVideoM4v()))
            ->setMeetingListItem($payload['meeting_list_item'] ?? $meeting->getMeetingListItem())
            ->setMeetingInfoGet($payload['meeting_info_get']   ?? $meeting->getMeetingInfoGet())
            ->setSignAttendance((bool)($payload['sign_attendance'] ?? $meeting->isSignAttendance()))
            ->setReasonToSignAttendance($payload['reason_to_sign_attendance'] ?? $meeting->getReasonToSignAttendance())
            ->setAccountEmail($payload['account_email']   ?? $meeting->getAccountEmail())
            ->setWebinarSchema($payload['webinar_schema'] ?? $meeting->getWebinarSchema());

        // --- timestamps: createdAt set in constructor, override closedAt if provided ---
        if (isset($payload['closed_at'])) {
            $meeting->setClosedAt(new \DateTime($payload['closed_at']));
        }

        // Persist if this is a new meeting
        if ($meeting->getId() === null) {
            $em->persist($meeting);
        }

        // 4) Persist a ConferenceRecording if recording_url is present
        if (!empty($payload['recording_url'])) {
            $rec = new ConferenceRecording();
            $rec
                ->setMeeting($meeting)
                ->setFormatType($payload['format_type'] ?? 'unknown')
                ->setResourceUrl($payload['recording_url']);
            $em->persist($rec);
        }

        // 5) Always persist the ConferenceActivity event
        if (isset($payload['participant_id'])) {

            $user = $em->find(User::class, (int)$payload['participant_id']);
            if ($user !== null) {
                $act = new ConferenceActivity();
                $act->setParticipant($user);
                $act->setMeeting($meeting);

                // map inAt/outAt either via explicit fields or via action+timestamp
                if (isset($payload['in_at'])) {
                    $act->setInAt(new \DateTime($payload['in_at']));
                } elseif (($payload['action'] ?? '') === 'join') {
                    $act->setInAt(new \DateTime($payload['timestamp'] ?? 'now'));
                }

                if (isset($payload['out_at'])) {
                    $act->setOutAt(new \DateTime($payload['out_at']));
                } elseif (($payload['action'] ?? '') === 'leave') {
                    $act->setOutAt(new \DateTime($payload['timestamp'] ?? 'now'));
                }

                // other activity fields
                if (isset($payload['close'])) {
                    $act->setClose((bool)$payload['close']);
                }
                if (isset($payload['type'])) {
                    $act->setType($payload['type']);
                }
                if (isset($payload['event'])) {
                    $act->setEvent($payload['event']);
                }
                if (isset($payload['activity_data'])) {
                    $act->setActivityData($payload['activity_data']);
                }
                if (isset($payload['signature_file'])) {
                    $act->setSignatureFile($payload['signature_file']);
                }
                if (isset($payload['signed_at'])) {
                    $act->setSignedAt(new \DateTime($payload['signed_at']));
                }

                $em->persist($act);
            }
        }

        // 6) Flush everything in one transaction
        $em->flush();

        return new Response('OK', Response::HTTP_OK);
    }
}
