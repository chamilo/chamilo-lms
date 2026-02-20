<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\ConferenceActivity;
use Chamilo\CoreBundle\Entity\ConferenceMeeting;
use Chamilo\CoreBundle\Entity\ConferenceRecording;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CGroup;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class VideoConferenceCallbackController
{
    private const SIGNATURE_TTL_SECONDS = 300; // 5 minutes window to limit replay
    private const SIGNATURE_HEADER = 'X-Chamilo-Signature';
    private const TIMESTAMP_HEADER = 'X-Chamilo-Timestamp';

    public function __invoke(Request $request, EntityManagerInterface $em, ParameterBagInterface $params): Response
    {
        $rawBody = (string) $request->getContent();

        // Decode payload (JSON preferred; form as fallback)
        $payload = $this->decodePayload($request, $rawBody);
        if (!\is_array($payload)) {
            return new Response('Invalid payload', Response::HTTP_BAD_REQUEST);
        }

        // Minimal required fields
        $provider = (string) ($payload['provider'] ?? '');
        $remoteId = (string) ($payload['meeting_id'] ?? '');

        if ('' === trim($provider) || '' === trim($remoteId)) {
            return new Response('Missing provider or meeting_id', Response::HTTP_BAD_REQUEST);
        }

        // We reuse the existing Symfony kernel secret (APP_SECRET).
        $kernelSecret = (string) ($params->get('kernel.secret') ?? '');
        $isAuthenticated = $this->isValidSignature($request, $rawBody, $kernelSecret);

        // Find existing meeting (do not create new meetings if unauthenticated)
        $meetingRepo = $em->getRepository(ConferenceMeeting::class);
        $meeting = $meetingRepo->findOneBy([
            'remoteId' => $remoteId,
            'serviceProvider' => $provider,
        ]);

        if (null === $meeting) {
            if (!$isAuthenticated) {
                // Security hardening: avoid creating meetings from unauthenticated sources.
                return new Response('Unauthorized (meeting not found)', Response::HTTP_UNAUTHORIZED);
            }

            $meeting = new ConferenceMeeting();
            $meeting->setServiceProvider($provider);
            $meeting->setRemoteId($remoteId);
            $em->persist($meeting);
        }

        // Map meeting associations (safe: only attach when entities exist)
        if (isset($payload['c_id'])) {
            $course = $em->find(Course::class, (int) $payload['c_id']);
            if (null !== $course) {
                $meeting->setCourse($course);
            }
        }
        if (isset($payload['session_id'])) {
            $session = $em->find(Session::class, (int) $payload['session_id']);
            if (null !== $session) {
                $meeting->setSession($session);
            }
        }
        if (isset($payload['access_url_id'])) {
            $url = $em->find(AccessUrl::class, (int) $payload['access_url_id']);
            if (null !== $url) {
                $meeting->setAccessUrl($url);
            }
        }
        if (isset($payload['group_id'])) {
            $group = $em->find(CGroup::class, (int) $payload['group_id']);
            if (null !== $group) {
                $meeting->setGroup($group);
            }
        }
        if (isset($payload['user_id'])) {
            $owner = $em->find(User::class, (int) $payload['user_id']);
            if (null !== $owner) {
                $meeting->setUser($owner);
            }
        }
        if (isset($payload['calendar_id'])) {
            $meeting->setCalendarId((int) $payload['calendar_id']);
        }

        // Map meeting scalar fields
        $meeting
            ->setServiceProvider($provider)
            ->setRemoteId($remoteId)
            ->setInternalMeetingId($payload['internal_meeting_id'] ?? $meeting->getInternalMeetingId())
            ->setTitle($payload['title'] ?? $meeting->getTitle())
            ->setAttendeePw($payload['attendee_pw'] ?? $meeting->getAttendeePw())
            ->setModeratorPw($payload['moderator_pw'] ?? $meeting->getModeratorPw())
            ->setRecord((bool) ($payload['record'] ?? $meeting->isRecord()))
            ->setStatus((int) ($payload['status'] ?? $meeting->getStatus()))
            ->setWelcomeMsg($payload['welcome_msg'] ?? $meeting->getWelcomeMsg())
            ->setVisibility((int) ($payload['visibility'] ?? $meeting->getVisibility()))
            ->setVoiceBridge($payload['voice_bridge'] ?? $meeting->getVoiceBridge())
            ->setVideoUrl($payload['video_url'] ?? $meeting->getVideoUrl())
            ->setHasVideoM4v((bool) ($payload['has_video_m4v'] ?? $meeting->isHasVideoM4v()))
            ->setMeetingListItem($payload['meeting_list_item'] ?? $meeting->getMeetingListItem())
            ->setMeetingInfoGet($payload['meeting_info_get'] ?? $meeting->getMeetingInfoGet())
            ->setSignAttendance((bool) ($payload['sign_attendance'] ?? $meeting->isSignAttendance()))
            ->setReasonToSignAttendance($payload['reason_to_sign_attendance'] ?? $meeting->getReasonToSignAttendance())
            ->setAccountEmail($payload['account_email'] ?? $meeting->getAccountEmail())
            ->setWebinarSchema($payload['webinar_schema'] ?? $meeting->getWebinarSchema())
        ;

        if (isset($payload['closed_at']) && \is_string($payload['closed_at'])) {
            $closedAt = $this->tryParseDateTime($payload['closed_at']);
            if (null !== $closedAt) {
                $meeting->setClosedAt($closedAt);
            }
        }

        // Recording insertion: require authentication OR existing meeting (already enforced)
        if (!empty($payload['recording_url']) && \is_string($payload['recording_url'])) {
            $recordingUrl = trim($payload['recording_url']);
            if ('' !== $recordingUrl) {
                // Avoid obvious duplicates
                $recRepo = $em->getRepository(ConferenceRecording::class);
                $existingRec = $recRepo->findOneBy([
                    'meeting' => $meeting,
                    'resourceUrl' => $recordingUrl,
                ]);

                if (null === $existingRec) {
                    $rec = new ConferenceRecording();
                    $rec
                        ->setMeeting($meeting)
                        ->setFormatType($payload['format_type'] ?? 'unknown')
                        ->setResourceUrl($recordingUrl)
                    ;
                    $em->persist($rec);
                }
            }
        }

        // Activity insertion: ONLY if request is authenticated
        if (isset($payload['participant_id'])) {
            if (!$isAuthenticated) {
                // Security hardening: do not allow arbitrary attendance logging without signature.
                $em->flush();
                return new Response('OK (activity skipped: missing signature)', Response::HTTP_OK);
            }

            $participant = $em->find(User::class, (int) $payload['participant_id']);
            if (null !== $participant) {
                // Minimal de-duplication: avoid inserting the exact same event twice
                $event = isset($payload['event']) ? (string) $payload['event'] : null;
                $tsRaw = isset($payload['timestamp']) ? (string) $payload['timestamp'] : null;
                $ts = $tsRaw ? $this->tryParseDateTime($tsRaw) : null;

                if ($event && $ts instanceof DateTimeInterface) {
                    $existingAct = $em->getRepository(ConferenceActivity::class)->findOneBy([
                        'meeting' => $meeting,
                        'participant' => $participant,
                        'event' => $event,
                        'inAt' => $ts,
                    ]);
                    if (null !== $existingAct) {
                        $em->flush();
                        return new Response('OK', Response::HTTP_OK);
                    }
                }

                $act = new ConferenceActivity();
                $act->setParticipant($participant);
                $act->setMeeting($meeting);

                // Map inAt/outAt either via explicit fields or via action+timestamp
                if (isset($payload['in_at']) && \is_string($payload['in_at'])) {
                    $dt = $this->tryParseDateTime($payload['in_at']);
                    if (null !== $dt) {
                        $act->setInAt($dt);
                    }
                } elseif (($payload['action'] ?? '') === 'join') {
                    $dt = $this->tryParseDateTime((string) ($payload['timestamp'] ?? 'now'));
                    $act->setInAt($dt ?? new DateTime('now'));
                }

                if (isset($payload['out_at']) && \is_string($payload['out_at'])) {
                    $dt = $this->tryParseDateTime($payload['out_at']);
                    if (null !== $dt) {
                        $act->setOutAt($dt);
                    }
                } elseif (($payload['action'] ?? '') === 'leave') {
                    $dt = $this->tryParseDateTime((string) ($payload['timestamp'] ?? 'now'));
                    $act->setOutAt($dt ?? new DateTime('now'));
                }

                if (isset($payload['close'])) {
                    $act->setClose((bool) $payload['close']);
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
                if (isset($payload['signed_at']) && \is_string($payload['signed_at'])) {
                    $dt = $this->tryParseDateTime($payload['signed_at']);
                    if (null !== $dt) {
                        $act->setSignedAt($dt);
                    }
                }

                $em->persist($act);
            }
        }

        // Flush everything once
        $em->flush();

        return new Response('OK', Response::HTTP_OK);
    }

    /**
     * Decode payload from JSON or x-www-form-urlencoded.
     */
    private function decodePayload(Request $request, string $rawBody): ?array
    {
        $payload = json_decode($rawBody, true);
        if (\is_array($payload)) {
            return $payload;
        }

        // Symfony parsed request body (form)
        $form = $request->request->all();
        if (\is_array($form) && !empty($form)) {
            return $form;
        }

        return null;
    }

    /**
     * Validate HMAC signature without introducing new settings:
     * - Secret: kernel.secret (APP_SECRET)
     * - Headers:
     *   - X-Chamilo-Timestamp: unix epoch seconds
     *   - X-Chamilo-Signature: v1=<hex> OR <hex>
     * - Signature: HMAC-SHA256 over "{ts}\n{rawBody}"
     */
    private function isValidSignature(Request $request, string $rawBody, string $kernelSecret): bool
    {
        $tsHeader = (string) $request->headers->get(self::TIMESTAMP_HEADER, '');
        $sigHeader = (string) $request->headers->get(self::SIGNATURE_HEADER, '');

        if ('' === $kernelSecret || '' === $tsHeader || '' === $sigHeader) {
            return false;
        }

        if (!ctype_digit($tsHeader)) {
            return false;
        }

        $ts = (int) $tsHeader;
        if ($ts <= 0) {
            return false;
        }

        // Anti-replay window
        if (abs(time() - $ts) > self::SIGNATURE_TTL_SECONDS) {
            return false;
        }

        // Support "v1=<hex>" and raw "<hex>"
        $sig = $sigHeader;
        if (str_starts_with($sigHeader, 'v1=')) {
            $sig = substr($sigHeader, 3);
        }
        $sig = trim($sig);

        if ('' === $sig) {
            return false;
        }

        $data = $tsHeader . "\n" . $rawBody;
        $expected = hash_hmac('sha256', $data, $kernelSecret);

        return hash_equals($expected, $sig);
    }

    private function tryParseDateTime(string $value): ?DateTime
    {
        try {
            return new DateTime($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
