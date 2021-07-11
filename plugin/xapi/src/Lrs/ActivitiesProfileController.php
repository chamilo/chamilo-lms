<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Lrs;

use Chamilo\PluginBundle\Entity\XApi\ActivityProfile;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ActivitiesProfileController.
 *
 * @package Chamilo\PluginBundle\XApi\Lrs
 */
class ActivitiesProfileController extends BaseController
{
    public function get(): Response
    {
        $profileId = $this->httpRequest->query->get('profileId');
        $activityId = $this->httpRequest->query->get('activityId');

        $em = \Database::getManager();
        $profileRepo = $em->getRepository(ActivityProfile::class);

        /** @var ActivityProfile $activityProfile */
        $activityProfile = $profileRepo->findOneBy(
            [
                'profileId' => $profileId,
                'activityId' => $activityId,
            ]
        );

        if (empty($activityProfile)) {
            return Response::create(null, Response::HTTP_NO_CONTENT);
        }

        return Response::create(
            json_encode($activityProfile->getDocumentData())
        );
    }

    public function head(): Response
    {
        return $this->get()->setContent('');
    }

    public function put(): Response
    {
        $profileId = $this->httpRequest->query->get('profileId');
        $activityId = $this->httpRequest->query->get('activityId');
        $documentData = $this->httpRequest->getContent();

        $em = \Database::getManager();
        $profileRepo = $em->getRepository(ActivityProfile::class);

        /** @var ActivityProfile $activityProfile */
        $activityProfile = $profileRepo->findOneBy(
            [
                'profileId' => $profileId,
                'activityId' => $activityId,
            ]
        );

        if (empty($activityProfile)) {
            $activityProfile = new ActivityProfile();
            $activityProfile
                ->setProfileId($profileId)
                ->setActivityId($activityId);
        }

        $activityProfile->setDocumentData(json_decode($documentData, true));

        $em->persist($activityProfile);
        $em->flush();

        return Response::create(null, Response::HTTP_NO_CONTENT);
    }
}
