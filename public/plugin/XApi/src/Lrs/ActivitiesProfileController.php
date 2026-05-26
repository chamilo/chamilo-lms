<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Lrs;

use Chamilo\CoreBundle\Entity\XApiActivityProfile;
use Database;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * xAPI activities/profile endpoint.
 */
class ActivitiesProfileController extends BaseController
{
    public function get(): Response
    {
        $profileId = (string) $this->httpRequest->query->get('profileId', '');
        $activityId = (string) $this->httpRequest->query->get('activityId', '');

        $em = Database::getManager();
        $profileRepo = $em->getRepository(XApiActivityProfile::class);

        /** @var XApiActivityProfile|null $activityProfile */
        $activityProfile = $profileRepo->findOneBy([
            'profileId' => $profileId,
            'activityId' => $activityId,
        ]);

        if (null === $activityProfile) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse($activityProfile->getDocumentData());
    }

    public function head(): Response
    {
        $response = $this->get();
        $response->setContent('');

        return $response;
    }

    public function put(): Response
    {
        $profileId = (string) $this->httpRequest->query->get('profileId', '');
        $activityId = (string) $this->httpRequest->query->get('activityId', '');
        $documentDataJson = $this->httpRequest->getContent();

        $em = Database::getManager();
        $profileRepo = $em->getRepository(XApiActivityProfile::class);

        /** @var XApiActivityProfile|null $activityProfile */
        $activityProfile = $profileRepo->findOneBy([
            'profileId' => $profileId,
            'activityId' => $activityId,
        ]);

        if (null === $activityProfile) {
            $activityProfile = new XApiActivityProfile();
            $activityProfile
                ->setProfileId($profileId)
                ->setActivityId($activityId)
            ;
        }

        $activityProfile->setDocumentData(json_decode($documentDataJson, true) ?? []);

        $em->persist($activityProfile);
        $em->flush();

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
