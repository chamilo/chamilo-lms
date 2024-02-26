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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class SecurityController extends AbstractController
{
    private $entityManager;
    private $settingsManager;
    private $tokenStorage;
    private $authorizationChecker;

    public function __construct(
        private SerializerInterface         $serializer,
        private TrackELoginRecordRepository $trackELoginRecordRepository,
        EntityManagerInterface              $entityManager,
        SettingsManager                     $settingsManager,
        TokenStorageInterface               $tokenStorage,
        AuthorizationCheckerInterface       $authorizationChecker
    ) {
        $this->entityManager = $entityManager;
        $this->settingsManager = $settingsManager;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
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
        $extraFieldValuesRepository = $this->entityManager->getRepository(ExtraFieldValues::class);
        $legalTermsRepo = $this->entityManager->getRepository(Legal::class);
        if ($user->hasRole('ROLE_STUDENT')
            && 'true' === $this->settingsManager->getSetting('allow_terms_conditions')
            && 'login' === $this->settingsManager->getSetting('load_term_conditions_section')
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
                $tempTermAndCondition = ['user_id' => $user->getId()];

                $this->tokenStorage->setToken(null);
                $request->getSession()->invalidate();

                $request->getSession()->start();
                $request->getSession()->set('term_and_condition', $tempTermAndCondition);

                $responseData = [
                    'redirect' => '/main/auth/inscription.php',
                    'load_terms' => true,
                ];

                return new JsonResponse($responseData, Response::HTTP_OK);
            } else {
                $request->getSession()->remove('term_and_condition');
            }
        }

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
