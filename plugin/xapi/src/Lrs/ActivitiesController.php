<?php

namespace Chamilo\PluginBundle\XApi\Lrs;

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\XApi\ActivityState;
use Database;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Xabbuh\XApi\Model\Actor;
use Xabbuh\XApi\Serializer\Symfony\Serializer;

/**
 * Class ActivitiesController.
 *
 * @package Chamilo\PluginBundle\XApi\Lrs
 */
class ActivitiesController extends BaseController
{
    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function get()
    {
        $serializer = Serializer::createSerializer();

        $requestedAgent = $this->httpRequest->query->get('agent');
        $activityId = $this->httpRequest->query->get('activityId');
        $stateId = $this->httpRequest->query->get('stateId');

        $state = Database::select(
            '*',
            Database::get_main_table('xapi_activity_state'),
            [
                'where' => [
                    'state_id = ? AND activity_id = ? AND MD5(agent) = ?' => [
                        Database::escape_string($stateId),
                        Database::escape_string($activityId),
                        md5($requestedAgent),
                    ],
                ],
            ],
            'first'
        );

        if (empty($state)) {
            return JsonResponse::create([], Response::HTTP_NOT_FOUND);
        }

        $requestedAgent = $serializer->deserialize(
            $this->httpRequest->query->get('agent'),
            Actor::class,
            'json'
        );

        /** @var Actor $stateAgent */
        $stateAgent = $serializer->deserialize(
            $state['agent'],
            Actor::class,
            'json'
        );

        if (!$stateAgent->equals($requestedAgent)) {
            return JsonResponse::create([], Response::HTTP_NOT_FOUND);
        }

        $documentData = json_decode($state['document_data'], true);

        return JsonResponse::create($documentData);
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function put()
    {
        $activityId = $this->httpRequest->query->get('activityId');
        $agent = $this->httpRequest->query->get('agent');
        $stateId = $this->httpRequest->query->get('stateId');
        $documentData = $this->httpRequest->getContent();

        $state = Database::select(
            'id',
            Database::get_main_table('xapi_activity_state'),
            [
                'where' => [
                    'state_id = ? AND activity_id = ? AND MD5(agent) = ?' => [
                        Database::escape_string($stateId),
                        Database::escape_string($activityId),
                        md5($agent),
                    ],
                ],
            ],
            'first'
        );

        $em = Database::getManager();

        if (empty($state)) {
            $state = new ActivityState();
            $state
                ->setActivityId($activityId)
                ->setAgent(json_decode($agent, true))
                ->setStateId($stateId);
        } else {
            $state = $em->find(ActivityState::class, $state['id']);
        }

        $state->setDocumentData(json_decode($documentData, true));

        $em->persist($state);
        $em->flush();

        return Response::create('', Response::HTTP_NO_CONTENT);
    }
}
