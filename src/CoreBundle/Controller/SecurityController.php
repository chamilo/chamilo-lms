<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\TrackELoginRecord;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\TrackELoginRecordRepository;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;

class SecurityController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly TrackELoginRecordRepository $trackELoginRecordRepository
    ) {
    }

    #[Route('/login_json', name: 'login_json', methods: ['POST'])]
    public function loginJson(): Response
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

        /** @var User $user */
        $user = $this->getUser();
        $data = null;
        if ($user) {
            // Log of connection attempts
            $trackELoginRecord = new TrackELoginRecord();
            $trackELoginRecord
                ->setUsername($user->getUsername())
                ->setLoginDate(new DateTime())
                ->setUserIp(api_get_real_ip())
                ->setSuccess(true);

            $this->trackELoginRecordRepository->create($trackELoginRecord);

            $data = $this->serializer->serialize($user, JsonEncoder::FORMAT, ['groups' => ['user:read']]);
        }

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }
}
