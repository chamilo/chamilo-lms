<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Entity\ConferenceMeeting;
use Chamilo\CoreBundle\Entity\ConferenceActivity;
use Chamilo\CoreBundle\Entity\ConferenceRecording;
use Doctrine\DBAL\Schema\Schema;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CoreBundle\Entity\Session;
use Exception;

final class Version20250310215900 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrates data from BBB and Zoom plugins to the new conference system using Doctrine persistence.';
    }

    public function up(Schema $schema): void
    {
        $this->entityManager->beginTransaction();

        try {
            // Migrate BBB Meetings
            if ($this->tableExists('plugin_bbb_meeting')) {
                $this->migrateBbbMeetings();
            }

            // Migrate BBB Activities
            if ($this->tableExists('plugin_bbb_room')) {
                $this->migrateBbbActivities();
            }

            // Migrate BBB Recordings
            if ($this->tableExists('plugin_bbb_meeting_format')) {
                $this->migrateBbbRecordings();
            }

            // Migrate Zoom Meetings
            if ($this->tableExists('plugin_zoom_meeting')) {
                $this->migrateZoomMeetings();
            }

            // Migrate Zoom Activities
            if ($this->tableExists('plugin_zoom_meeting_activity')) {
                $this->migrateZoomActivities();
            }

            // Migrate Zoom Recordings
            if ($this->tableExists('plugin_zoom_recording')) {
                $this->migrateZoomRecordings();
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Exception $e) {
            $this->entityManager->rollBack();
            error_log('Migration failed: ' . $e->getMessage());
        }
    }

    private function migrateBbbMeetings(): void
    {
        $bbbMeetings = $this->connection->fetchAllAssociative("SELECT * FROM plugin_bbb_meeting");

        foreach ($bbbMeetings as $bbb) {
            $course = $this->getEntityById(Course::class, $bbb['c_id']);
            $user = $this->getEntityById(User::class, $bbb['user_id']);
            $group = $this->getEntityById(CGroup::class, $bbb['group_id']);
            $session = $this->getEntityById(Session::class, $bbb['session_id']);

            if (!$course) {
                continue;
            }

            $meeting = new ConferenceMeeting();
            $meeting->setServiceProvider('bbb');
            $meeting->setTitle($bbb['meeting_name']);
            $meeting->setRemoteId($bbb['remote_id']);
            $meeting->setInternalMeetingId($bbb['remote_id']);
            $meeting->setAttendeePw($bbb['attendee_pw']);
            $meeting->setModeratorPw($bbb['moderator_pw']);
            $meeting->setRecord((bool) $bbb['record']);
            $meeting->setStatus((int) $bbb['status']);
            $meeting->setWelcomeMsg($bbb['welcome_msg']);
            $meeting->setVisibility((int) $bbb['visibility']);
            $meeting->setVoiceBridge($bbb['voice_bridge']);
            $meeting->setVideoUrl($bbb['video_url']);
            $meeting->setHasVideoM4v((bool) $bbb['has_video_m4v']);
            $meeting->setClosedAt($bbb['closed_at'] ? new \DateTime($bbb['closed_at']) : null);

            $meeting->setCourse($course);
            $meeting->setUser($user);
            $meeting->setGroup($group);

            if ($session) {
                $meeting->setSession($session);
            }

            $this->entityManager->persist($meeting);
            $this->entityManager->flush();

            $this->meetingIdMap[$bbb['id']] = $meeting->getId();
        }
    }

    private function migrateBbbActivities(): void
    {
        $bbbActivities = $this->connection->fetchAllAssociative("SELECT * FROM plugin_bbb_room");

        foreach ($bbbActivities as $activity) {
            if (!isset($this->meetingIdMap[$activity['meeting_id']])) {
                continue;
            }

            $meeting = $this->entityManager->find(ConferenceMeeting::class, $this->meetingIdMap[$activity['meeting_id']]);
            $participant = $this->getEntityById(User::class, $activity['participant_id']);

            if (!$meeting || !$participant) {
                continue;
            }

            $conferenceActivity = new ConferenceActivity();
            $conferenceActivity->setMeeting($meeting);
            $conferenceActivity->setParticipant($participant);
            $conferenceActivity->setInAt(new \DateTime($activity['in_at']));
            $conferenceActivity->setOutAt($activity['out_at'] ? new \DateTime($activity['out_at']) : null);
            $conferenceActivity->setType('participant');
            $conferenceActivity->setEvent('joined');

            $this->entityManager->persist($conferenceActivity);
        }
    }

    private function migrateBbbRecordings(): void
    {
        $bbbRecordings = $this->connection->fetchAllAssociative("SELECT * FROM plugin_bbb_meeting_format");

        foreach ($bbbRecordings as $recording) {
            $meeting = $this->getEntityById(ConferenceMeeting::class, $recording['meeting_id']);

            if (!$meeting) {
                continue;
            }

            $conferenceRecording = new ConferenceRecording();
            $conferenceRecording->setMeeting($meeting);
            $conferenceRecording->setFormatType($recording['format_type']);
            $conferenceRecording->setResourceUrl($recording['resource_url']);

            $this->entityManager->persist($conferenceRecording);
        }
    }

    private function migrateZoomMeetings(): void
    {
        $zoomMeetings = $this->connection->fetchAllAssociative("SELECT * FROM plugin_zoom_meeting");

        foreach ($zoomMeetings as $zoom) {
            $course = $this->getEntityById(Course::class, $zoom['course_id']);
            $user = $this->getEntityById(User::class, $zoom['user_id']);
            $group = $this->getEntityById(CGroup::class, $zoom['group_id']);
            $session = $this->getEntityById(Session::class, $zoom['session_id']);

            if (!$course) {
                continue;
            }

            $meeting = new ConferenceMeeting();
            $meeting->setServiceProvider('zoom');
            $meeting->setRemoteId($zoom['meeting_id']);
            $meeting->setTitle($zoom['meeting_list_item_json']);
            $meeting->setSignAttendance((bool) $zoom['sign_attendance']);
            $meeting->setReasonToSignAttendance($zoom['reason_to_sign_attendance']);
            $meeting->setAccountEmail($zoom['account_email']);

            $meeting->setCourse($course);
            $meeting->setUser($user);
            $meeting->setGroup($group);

            if ($session) {
                $meeting->setSession($session);
            }

            $this->entityManager->persist($meeting);
            $this->entityManager->flush();

            $this->meetingIdMap[$zoom['id']] = $meeting->getId();
        }
    }

    private function migrateZoomActivities(): void
    {
        $zoomActivities = $this->connection->fetchAllAssociative("SELECT * FROM plugin_zoom_meeting_activity");

        foreach ($zoomActivities as $activity) {
            if (!isset($this->meetingIdMap[$activity['meeting_id']])) {
                continue;
            }

            $meeting = $this->entityManager->find(ConferenceMeeting::class, $this->meetingIdMap[$activity['meeting_id']]);
            $participant = $this->getEntityById(User::class, $activity['user_id']);

            if (!$meeting || !$participant) {
                continue;
            }

            $conferenceActivity = new ConferenceActivity();
            $conferenceActivity->setMeeting($meeting);
            $conferenceActivity->setParticipant($participant);
            $conferenceActivity->setInAt(new \DateTime($activity['created_at']));
            $conferenceActivity->setEvent($activity['event']);

            $this->entityManager->persist($conferenceActivity);
        }
    }

    private function migrateZoomRecordings(): void
    {
        $zoomRecordings = $this->connection->fetchAllAssociative("SELECT * FROM plugin_zoom_recording");

        foreach ($zoomRecordings as $recording) {
            $meeting = $this->getEntityById(ConferenceMeeting::class, $recording['meeting_id']);

            if (!$meeting) {
                continue;
            }

            $conferenceRecording = new ConferenceRecording();
            $conferenceRecording->setMeeting($meeting);
            $conferenceRecording->setFormatType('zoom');
            $conferenceRecording->setResourceUrl($recording['recording_meeting_json']);

            $this->entityManager->persist($conferenceRecording);
        }
    }

    private function getEntityById(string $entityClass, ?int $id): ?object
    {
        return $id ? $this->entityManager->find($entityClass, $id) : null;
    }

    private function tableExists(string $tableName): bool
    {
        try {
            $this->connection->executeQuery("SELECT 1 FROM $tableName LIMIT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM conference_meeting WHERE service_provider = 'bbb';");
        $this->addSql("DELETE FROM conference_meeting WHERE service_provider = 'zoom';");
    }
}
