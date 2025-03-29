<?php

/**
 * This script initiates a video conference session, calling the BigBlueButton API.
 */

use Chamilo\CoreBundle\Entity\ConferenceActivity;
use Chamilo\CoreBundle\Repository\ConferenceActivityRepository;

$course_plugin = 'bbb'; //needed in order to load the plugin lang variables
require_once __DIR__.'/config.php';

$plugin = BbbPlugin::create();
/** @var ConferenceActivityRepository $activityRepo */
$em = Database::getManager();
$activityRepo = $em->getRepository(ConferenceActivity::class);

$bbb = new Bbb();
if ($bbb->pluginEnabled) {
    $activeSessions = $bbb->getActiveSessions();

    if (!empty($activeSessions)) {
        foreach ($activeSessions as $value) {
            $meetingId = $value['id'];
            $courseCode = null;
            $courseInfo = api_get_course_info_by_id($value['c_id']);
            if (!empty($courseInfo)) {
                $courseCode = $courseInfo['code'];
            }

            $meetingBBB = $bbb->getMeetingInfo(
                [
                    'meetingId' => $value['remote_id'],
                    'password' => $value['moderator_pw'],
                ]
            );

            if (false === $meetingBBB) {
                //checking with the remote_id didn't work, so just in case and
                // to provide backwards support, check with the id
                $params = [
                    'meetingId' => $value['id'],
                    'password' => $value['moderator_pw'],
                ];
                $meetingBBB = $bbb->getMeetingInfo($params);
            }

            if (!empty($meetingBBB)) {
                if (isset($meetingBBB['returncode'])) {
                    $action = (string) $meetingBBB['returncode'];
                    switch ($action) {
                        case 'FAILED':
                            $bbb->endMeeting($value['id'], $courseCode);
                            break;
                        case 'SUCCESS':
                            $activitiesToMark = $activityRepo->createQueryBuilder('a')
                                ->where('a.meeting = :meetingId')
                                ->andWhere('a.close = :open')
                                ->setParameter('meetingId', $meetingId)
                                ->setParameter('open', BbbPlugin::ROOM_OPEN)
                                ->getQuery()
                                ->getResult();

                            foreach ($activitiesToMark as $activity) {
                                $activity->setClose(BbbPlugin::ROOM_CHECK);
                            }
                            $em->flush();

                            $i = 0;
                            while ($i < $meetingBBB['participantCount']) {
                                $participantId = $meetingBBB[$i]['userId'];

                                $roomData = $activityRepo->createQueryBuilder('a')
                                    ->where('a.meeting = :meetingId')
                                    ->andWhere('a.participant = :participantId')
                                    ->andWhere('a.close = :check')
                                    ->setParameter('meetingId', $meetingId)
                                    ->setParameter('participantId', $participantId)
                                    ->setParameter('check', BbbPlugin::ROOM_CHECK)
                                    ->orderBy('a.id', 'DESC')
                                    ->setMaxResults(1)
                                    ->getQuery()
                                    ->getOneOrNullResult();

                                if ($roomData instanceof ConferenceActivity) {
                                    $roomData->setOutAt(new \DateTime());
                                    $roomData->setClose(BbbPlugin::ROOM_OPEN);
                                }
                                $i++;
                            }
                            $em->flush();

                            $activitiesToClose = $activityRepo->createQueryBuilder('a')
                                ->where('a.meeting = :meetingId')
                                ->andWhere('a.close = :check')
                                ->setParameter('meetingId', $meetingId)
                                ->setParameter('check', BbbPlugin::ROOM_CHECK)
                                ->getQuery()
                                ->getResult();

                            foreach ($activitiesToClose as $activity) {
                                $activity->setOutAt(new \DateTime());
                                $activity->setClose(BbbPlugin::ROOM_CLOSE);
                            }

                            $em->flush();
                            break;
                    }
                }
            }
        }
    }
}
