<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Service\PersonalDataExport\PersonalDataExportDocumentFactory;
use Chamilo\CoreBundle\Service\PersonalDataExport\PersonalDataExportPasswordGuard;
use Chamilo\CoreBundle\Service\PersonalDataExport\PersonalDataExportPusher;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_USER')]
#[Route('/social-network/personal-data')]
class PersonalDataExportController extends AbstractController
{
    public const string CSRF_INTENT = 'personal_data_export';
    public const string SETTING_ALLOW_PUSH = 'privacy.allow_personal_data_export_to_url';

    public function __construct(
        private readonly SettingsManager $settingsManager,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly PersonalDataExportPasswordGuard $passwordGuard,
        private readonly PersonalDataExportDocumentFactory $documentFactory,
        private readonly PersonalDataExportPusher $pusher,
        private readonly TranslatorInterface $translator,
    ) {}

    #[Route('/export-options', name: 'chamilo_core_social_personal_data_export_options', methods: ['GET'])]
    public function exportOptions(): JsonResponse
    {
        if ($this->isGdprDisabled()) {
            return $this->json([
                'available' => false,
                'allow_export_to_url' => false,
                'csrf_token' => null,
            ], Response::HTTP_FORBIDDEN);
        }

        return $this->json([
            'available' => true,
            'allow_export_to_url' => $this->isPushEnabled(),
            'csrf_token' => $this->csrfTokenManager->getToken(self::CSRF_INTENT)->getValue(),
        ]);
    }

    #[Route('/export-download', name: 'chamilo_core_social_personal_data_export_download', methods: ['POST'])]
    public function download(
        Request $request,
        #[CurrentUser]
        User $currentUser,
    ): Response {
        if ($denied = $this->denyIfGdprDisabled()) {
            return $denied;
        }

        $payload = $this->decodeJsonBody($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        if ($csrfError = $this->denyIfCsrfInvalid($payload)) {
            return $csrfError;
        }

        $this->passwordGuard->assertCurrentPassword($currentUser, (string) ($payload['password'] ?? ''));

        $format = $this->normalizeExportFormat($payload['format'] ?? 'json');
        if (null === $format) {
            return $this->json([
                'success' => false,
                'message' => $this->translator->trans('The export format must be JSON or XML.'),
            ], Response::HTTP_BAD_REQUEST);
        }

        $stamp = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Ymd\THis\Z');
        $basename = \sprintf(
            'chamilo-personal-data-%s-%s',
            $this->safeFilenamePart((string) $currentUser->getUsername()),
            $stamp
        );

        if ('xml' === $format) {
            $body = $this->documentFactory->buildExportXml($currentUser);
            $filename = $basename.'.xml';
            $contentType = 'application/xml; charset=UTF-8';
        } else {
            $body = $this->documentFactory->buildExportJson($currentUser);
            $filename = $basename.'.json';
            $contentType = 'application/ld+json; charset=UTF-8';
        }

        $response = new Response($body, Response::HTTP_OK, [
            'Content-Type' => $contentType,
        ]);
        $response->headers->set(
            'Content-Disposition',
            HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename)
        );

        return $response;
    }

    #[Route('/export-to-url', name: 'chamilo_core_social_personal_data_export_to_url', methods: ['POST'])]
    public function exportToUrl(
        Request $request,
        #[CurrentUser]
        User $currentUser,
    ): JsonResponse {
        if ($denied = $this->denyIfGdprDisabled()) {
            return $denied;
        }

        if (!$this->isPushEnabled()) {
            return $this->json([
                'success' => false,
                'message' => $this->translator->trans('Sending personal data to an external URL is disabled on this platform.'),
            ], Response::HTTP_FORBIDDEN);
        }

        $payload = $this->decodeJsonBody($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        if ($csrfError = $this->denyIfCsrfInvalid($payload)) {
            return $csrfError;
        }

        $this->passwordGuard->assertCurrentPassword($currentUser, (string) ($payload['password'] ?? ''));

        $destinationUrl = trim((string) ($payload['url'] ?? ''));
        $json = $this->documentFactory->buildExportJson($currentUser);

        try {
            $result = $this->pusher->push($currentUser, $destinationUrl, $json);
        } catch (InvalidArgumentException $exception) {
            return $this->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (RuntimeException $exception) {
            return $this->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], Response::HTTP_BAD_GATEWAY);
        }

        return $this->json([
            'success' => true,
            'message' => $this->translator->trans('Your personal data was sent to the destination URL.'),
            'third_party_data_exchange_id' => $result['third_party_data_exchange_id'],
        ]);
    }

    private function isGdprDisabled(): bool
    {
        return 'true' === (string) $this->settingsManager->getSetting('privacy.disable_gdpr');
    }

    private function isPushEnabled(): bool
    {
        return 'true' === (string) $this->settingsManager->getSetting(self::SETTING_ALLOW_PUSH, true);
    }

    private function denyIfGdprDisabled(): ?JsonResponse
    {
        if (!$this->isGdprDisabled()) {
            return null;
        }

        return $this->json([
            'success' => false,
            'message' => $this->translator->trans('This action is not available.'),
        ], Response::HTTP_FORBIDDEN);
    }

    /**
     * @return array<string, mixed>|JsonResponse
     */
    private function decodeJsonBody(Request $request): array|JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json([
                'success' => false,
                'message' => $this->translator->trans('Invalid JSON body'),
            ], Response::HTTP_BAD_REQUEST);
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function denyIfCsrfInvalid(array $payload): ?JsonResponse
    {
        $token = (string) ($payload['csrf_token'] ?? '');
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_INTENT, $token))) {
            return $this->json([
                'success' => false,
                'message' => $this->translator->trans('Invalid CSRF token.'),
            ], Response::HTTP_FORBIDDEN);
        }

        return null;
    }

    /**
     * @return 'json'|'xml'|null
     */
    private function normalizeExportFormat(mixed $format): ?string
    {
        $value = strtolower(trim((string) $format));
        if ('' === $value) {
            return 'json';
        }
        if (\in_array($value, ['json', 'json-ld', 'jsonld'], true)) {
            return 'json';
        }
        if ('xml' === $value) {
            return 'xml';
        }

        return null;
    }

    private function safeFilenamePart(string $value): string
    {
        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '-', $value) ?? 'user';
        $safe = trim($safe, '.-');

        return '' === $safe ? 'user' : $safe;
    }
}
