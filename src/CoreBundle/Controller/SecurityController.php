<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\TrackELoginRecord;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use DateTime;

class SecurityController extends AbstractController
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @Route("/login_json", name="login_json")
     */
    public function loginJson(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->json(
                [
                    'error' => 'Invalid login request: check that the Content-Type header is "application/json".',
                ],
                400
            );
        }

        //$error = $authenticationUtils->getLastAuthenticationError();
        //$lastUsername = $authenticationUtils->getLastUsername();

        // Log of connection attempts
        $params = json_decode($request->getContent(), true);
        $trackELoginRecord = new TrackELoginRecord();
        $trackELoginRecord
            ->setUsername($params['username'])
            ->setLoginDate(new DateTime())
            ->setUserIp(api_get_real_ip())
            ->setSuccess(true)
        ;
        $repo = Container::getTrackELoginRecordRepository();
        $repo->create($trackELoginRecord);

        /** @var User $user */
        $user = $this->getUser();
        $data = null;
        if ($user) {
            $userClone = clone $user;
            $userClone->setPassword('');
            $data = $this->serializer->serialize($userClone, JsonEncoder::FORMAT);
        }

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    /**
     * @Route("/login_failed", name="login_failed")
     */
    public function loginFailed(Request $request): JsonResponse
    {
        $params = json_decode($request->getContent(), true);
        // Log of connection attempts
        $trackELoginRecord = new TrackELoginRecord();
        $trackELoginRecord
            ->setUsername($params['username'])
            ->setLoginDate(new DateTime())
            ->setUserIp(api_get_real_ip())
            ->setSuccess(false)
        ;
        $repo = Container::getTrackELoginRecordRepository();
        $repo->create($trackELoginRecord);
        $data = $this->serializer->serialize($params, JsonEncoder::FORMAT);

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }
}
