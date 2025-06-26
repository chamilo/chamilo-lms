<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\Legal;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\TrackELoginRecordRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use OTPHP\TOTP;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecurityController extends AbstractController
{
    public function __construct(
        private SerializerInterface $serializer,
        private TrackELoginRecordRepository $trackELoginRecordRepository,
        private EntityManagerInterface $entityManager,
        private SettingsManager $settingsManager,
        private TokenStorageInterface $tokenStorage,
        private AuthorizationCheckerInterface $authorizationChecker,
        private readonly UserHelper $userHelper,
    ) {}

    #[Route('/login_json', name: 'login_json', methods: ['POST'])]
    public function loginJson(Request $request, EntityManager $entityManager, SettingsManager $settingsManager, TokenStorageInterface $tokenStorage, TranslatorInterface $translator): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->json(
                [
                    'error' => 'Invalid login request: check that the Content-Type header is "application/json".',
                ],
                400
            );
        }

        $user = $this->userHelper->getCurrent();

        if (1 !== $user->getActive()) {
            if (0 === $user->getActive()) {
                $message = $translator->trans('Account not activated.');
            } else {
                $message = $translator->trans('Invalid credentials. Please try again or contact support if you continue to experience issues.');
            }

            $tokenStorage->setToken(null);
            $request->getSession()->invalidate();

            return $this->json(['error' => $message], 401);
        }

        if ($user->getMfaEnabled()) {
            $totpCode = null;
            $data = json_decode($request->getContent(), true);
            if (isset($data['totp'])) {
                $totpCode = $data['totp'];
            }

            if (null === $totpCode || !$this->isTOTPValid($user, $totpCode)) {
                $tokenStorage->setToken(null);
                $request->getSession()->invalidate();

                return $this->json([
                    'requires2FA' => true,
                ], 200);
            }
        }

        if (null !== $user->getExpirationDate() && $user->getExpirationDate() <= new DateTime()) {
            $message = $translator->trans('Your account has expired.');

            $tokenStorage->setToken(null);
            $request->getSession()->invalidate();

            return $this->json(['error' => $message], 401);
        }

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
            }
            $request->getSession()->remove('term_and_condition');
        }

        $data = null;
        if ($user) {
            $data = $this->serializer->serialize($user, 'jsonld', ['groups' => ['user_json:read']]);
        }

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/check-session', name: 'check_session', methods: ['GET'])]
    public function checkSession(): JsonResponse
    {
        if ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $user = $this->userHelper->getCurrent();
            $data = $this->serializer->serialize($user, 'jsonld', ['groups' => ['user_json:read']]);

            return new JsonResponse(['isAuthenticated' => true, 'user' => json_decode($data)], Response::HTTP_OK);
        }

        throw $this->createAccessDeniedException();
    }

    /**
     * Validates the provided TOTP code for the given user.
     *
     * @param mixed $user
     */
    private function isTOTPValid($user, string $totpCode): bool
    {
        $decryptedSecret = $this->decryptTOTPSecret($user->getMfaSecret(), $_ENV['APP_SECRET']);
        $totp = TOTP::create($decryptedSecret);

        return $totp->verify($totpCode);
    }

    /**
     * Decrypts the stored TOTP secret.
     */
    private function decryptTOTPSecret(string $encryptedSecret, string $encryptionKey): string
    {
        $cipherMethod = 'aes-256-cbc';

        try {
            list($iv, $encryptedData) = explode('::', base64_decode($encryptedSecret), 2);

            return openssl_decrypt($encryptedData, $cipherMethod, $encryptionKey, 0, $iv);
        } catch (Exception $e) {
            error_log('Exception caught during decryption: '.$e->getMessage());

            return '';
        }
    }
}
