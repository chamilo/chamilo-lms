<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\Legal;
use Chamilo\CoreBundle\Entity\TrackELoginRecord;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\TrackELoginRecordRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTime;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\SerializerInterface;

class SecurityController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly TrackELoginRecordRepository $trackELoginRecordRepository
    ) {
    }

    #[Route('/login_json', name: 'login_json', methods: ['POST'])]
    public function loginJson(Request $request, EntityManager $entityManager, SettingsManager $settingsManager, TokenStorageInterface $tokenStorage): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->json(
                [
                    'error' => 'Invalid login request: check that the Content-Type header is "application/json".',
                ],
                400
            );
        }

        /** @var User $user */
        $user = $this->getUser();
        $extraFieldValuesRepository = $entityManager->getRepository(ExtraFieldValues::class);
        $legalTermsRepo = $entityManager->getRepository(Legal::class);
        if ($user->hasRole('ROLE_STUDENT') &&
            'true' === $settingsManager->getSetting('allow_terms_conditions') &&
            'login' === $settingsManager->getSetting('load_term_conditions_section')
        ) {
            $termAndConditionStatus = false;
            $extraValue = $extraFieldValuesRepository->findLegalAcceptByItemId($user->getId());
            if (!empty($extraValue['value'])) {
                $result = $extraValue['value'];
                $userConditions = explode(':', $result);
                $version = $userConditions[0];
                $langId = (int) $userConditions[1];
                $realVersion = $legalTermsRepo->getLastVersion($langId);
                $termAndConditionStatus = ($version >= $realVersion);
            }

            if (false === $termAndConditionStatus) {
                $request->getSession()->set('term_and_condition', ['user_id' => $user->getId()]);
            } else {
                $request->getSession()->remove('term_and_condition');
            }

            $termsAndCondition = $request->getSession()->get('term_and_condition');
            if (null !== $termsAndCondition) {
                $tokenStorage->setToken(null);
                $responseData = [
                    'redirect' => '/main/auth/inscription.php',
                    'load_terms' => true,
                ];

                return new JsonResponse($responseData, Response::HTTP_OK);
            }
        }
        //$error = $authenticationUtils->getLastAuthenticationError();
        //$lastUsername = $authenticationUtils->getLastUsername();

        $data = null;
        if ($user) {
            // Log of connection attempts
            $trackELoginRecord = new TrackELoginRecord();
            $trackELoginRecord
                ->setUsername($user->getUsername())
                ->setLoginDate(new DateTime())
                ->setUserIp(api_get_real_ip())
                ->setSuccess(true)
            ;

            $this->trackELoginRecordRepository->create($trackELoginRecord);

            $data = $this->serializer->serialize($user, 'jsonld', ['groups' => ['user:read']]);
        }

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }
}
