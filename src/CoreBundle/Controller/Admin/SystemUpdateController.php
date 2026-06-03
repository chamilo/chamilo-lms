<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Service\Update\Dto\UpdateManifest;
use Chamilo\CoreBundle\Service\Update\UpdateApplyPlanner;
use Chamilo\CoreBundle\Service\Update\UpdateConfiguration;
use Chamilo\CoreBundle\Service\Update\UpdateFileApplier;
use Chamilo\CoreBundle\Service\Update\UpdateManifestClient;
use Chamilo\CoreBundle\Service\Update\UpdateOperationLogger;
use Chamilo\CoreBundle\Service\Update\UpdatePackageDownloader;
use Chamilo\CoreBundle\Service\Update\UpdatePackageVerifier;
use Chamilo\CoreBundle\Service\Update\UpdatePreflightChecker;
use Chamilo\CoreBundle\Service\Update\UpdateStagingManager;
use Composer\InstalledVersions;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/system-update', name: 'admin_system_update_')]
final class SystemUpdateController extends AbstractController
{
    public function __construct(
        private readonly UpdateManifestClient $manifestClient,
        private readonly UpdatePackageDownloader $packageDownloader,
        private readonly UpdatePackageVerifier $packageVerifier,
        private readonly UpdatePreflightChecker $preflightChecker,
        private readonly UpdateStagingManager $stagingManager,
        private readonly UpdateConfiguration $updateConfiguration,
        private readonly UpdateApplyPlanner $applyPlanner,
        private readonly UpdateFileApplier $fileApplier,
        private readonly UpdateOperationLogger $operationLogger,
    ) {}

    #[Route('/status', name: 'status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        return $this->json([
            'installedVersion' => $this->getInstalledVersion(),
            'updateDirectory' => $this->getProjectDir().'/var/update/downloads',
            'stagingDirectory' => $this->getProjectDir().'/var/update/staging',
            'backupDirectory' => $this->getProjectDir().'/var/update/backups',
            'lockPath' => $this->getProjectDir().'/var/update/update.lock',
            'verificationOnly' => false,
            'fileApplyAvailable' => true,
            'defaultManifestSource' => $this->updateConfiguration->getDefaultManifestSource(),
            'allowLocalPaths' => $this->updateConfiguration->allowsLocalPaths(),
            'allowSkipSignature' => $this->updateConfiguration->allowsSkipSignature(),
            'productionMode' => $this->updateConfiguration->isProduction(),
            'trustedPublicKeyConfigured' => $this->updateConfiguration->hasTrustedPublicKey(),
            'trustedPublicKeyFingerprint' => $this->updateConfiguration->getTrustedPublicKeyFingerprint(),
        ]);
    }

    #[Route('/check', name: 'check', methods: ['POST'])]
    public function check(Request $request): JsonResponse
    {
        $payload = $this->readJsonPayload($request);

        try {
            $manifestSource = $this->readManifestSource($payload);
            $manifest = $this->manifestClient->load($manifestSource);

            return $this->json([
                'manifestSource' => $manifestSource,
                'manifest' => $this->manifestToArray($manifest),
            ]);
        } catch (Throwable $exception) {
            return $this->json([
                'error' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/verify', name: 'verify', methods: ['POST'])]
    public function verify(Request $request): JsonResponse
    {
        $payload = $this->readJsonPayload($request);

        try {
            $manifestSource = $this->readManifestSource($payload);
            $packagePath = $this->normalizeNullableLocalPath($this->readNullableString($payload, 'packagePath'), 'package');
            $signaturePath = $this->normalizeNullableLocalPath($this->readNullableString($payload, 'signaturePath'), 'signature');
            $trustedPublicKey = $this->resolveTrustedPublicKey($payload);
            $skipSignature = $this->readSkipSignature($payload);
            $manifest = $this->manifestClient->load($manifestSource);

            if (null === $packagePath) {
                $packagePath = $this->packageDownloader->download($manifest->getPackageUrl());
            }

            if (!$skipSignature && null === $signaturePath && null !== $manifest->getSignatureUrl()) {
                $signaturePath = $this->packageDownloader->download($manifest->getSignatureUrl());
            }

            $result = $this->packageVerifier->verify(
                $packagePath,
                $manifest,
                $signaturePath,
                $trustedPublicKey,
                $skipSignature,
            );

            return $this->json([
                'manifestSource' => $manifestSource,
                'manifest' => $this->manifestToArray($manifest),
                'packagePath' => $packagePath,
                'signaturePath' => $signaturePath,
                'trustedPublicKeyConfigured' => $this->updateConfiguration->hasTrustedPublicKey(),
                'skipSignature' => $skipSignature,
                'result' => $result->toArray(),
            ]);
        } catch (Throwable $exception) {
            return $this->json([
                'error' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/preflight', name: 'preflight', methods: ['POST'])]
    public function preflight(Request $request): JsonResponse
    {
        $payload = $this->readJsonPayload($request);

        try {
            $manifestSource = $this->readManifestSource($payload);
            $packagePath = $this->normalizeNullableLocalPath($this->readNullableString($payload, 'packagePath'), 'package');
            $manifest = $this->manifestClient->load($manifestSource);
            $result = $this->preflightChecker->check($manifest, $packagePath);

            return $this->json([
                'manifestSource' => $manifestSource,
                'manifest' => $this->manifestToArray($manifest),
                'packagePath' => $packagePath,
                'result' => $result->toArray(),
            ]);
        } catch (Throwable $exception) {
            return $this->json([
                'error' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/stage', name: 'stage', methods: ['POST'])]
    public function stage(Request $request): JsonResponse
    {
        $payload = $this->readJsonPayload($request);

        try {
            $manifestSource = $this->readManifestSource($payload);
            $packagePath = $this->normalizeNullableLocalPath($this->readNullableString($payload, 'packagePath'), 'package');
            $signaturePath = $this->normalizeNullableLocalPath($this->readNullableString($payload, 'signaturePath'), 'signature');
            $trustedPublicKey = $this->resolveTrustedPublicKey($payload);
            $skipSignature = $this->readSkipSignature($payload);
            $manifest = $this->manifestClient->load($manifestSource);

            if (null === $packagePath) {
                $packagePath = $this->packageDownloader->download($manifest->getPackageUrl());
            }

            if (!$skipSignature && null === $signaturePath && null !== $manifest->getSignatureUrl()) {
                $signaturePath = $this->packageDownloader->download($manifest->getSignatureUrl());
            }

            $verificationResult = $this->packageVerifier->verify(
                $packagePath,
                $manifest,
                $signaturePath,
                $trustedPublicKey,
                $skipSignature,
            );

            if (!$verificationResult->isValid()) {
                return $this->json([
                    'error' => 'Update package verification failed.',
                    'manifestSource' => $manifestSource,
                    'manifest' => $this->manifestToArray($manifest),
                    'packagePath' => $packagePath,
                    'signaturePath' => $signaturePath,
                    'verification' => $verificationResult->toArray(),
                ], Response::HTTP_BAD_REQUEST);
            }

            $preflightResult = $this->preflightChecker->check($manifest, $packagePath);

            if (!$preflightResult->isValid()) {
                return $this->json([
                    'error' => 'Update preflight checks failed.',
                    'manifestSource' => $manifestSource,
                    'manifest' => $this->manifestToArray($manifest),
                    'packagePath' => $packagePath,
                    'signaturePath' => $signaturePath,
                    'verification' => $verificationResult->toArray(),
                    'preflight' => $preflightResult->toArray(),
                ], Response::HTTP_BAD_REQUEST);
            }

            $stagingResult = $this->stagingManager->stage($manifest, $packagePath);

            if (!$stagingResult->isValid()) {
                return $this->json([
                    'error' => 'Unable to stage update package.',
                    'manifestSource' => $manifestSource,
                    'manifest' => $this->manifestToArray($manifest),
                    'packagePath' => $packagePath,
                    'signaturePath' => $signaturePath,
                    'verification' => $verificationResult->toArray(),
                    'preflight' => $preflightResult->toArray(),
                    'staging' => $stagingResult->toArray(),
                ], Response::HTTP_BAD_REQUEST);
            }

            return $this->json([
                'manifestSource' => $manifestSource,
                'manifest' => $this->manifestToArray($manifest),
                'packagePath' => $packagePath,
                'signaturePath' => $signaturePath,
                'trustedPublicKeyConfigured' => $this->updateConfiguration->hasTrustedPublicKey(),
                'skipSignature' => $skipSignature,
                'verification' => $verificationResult->toArray(),
                'preflight' => $preflightResult->toArray(),
                'staging' => $stagingResult->toArray(),
            ]);
        } catch (Throwable $exception) {
            return $this->json([
                'error' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }


    #[Route('/apply-plan', name: 'apply_plan', methods: ['POST'])]
    public function applyPlan(Request $request): JsonResponse
    {
        $payload = $this->readJsonPayload($request);

        try {
            $stagingPath = $this->readRequiredString($payload, 'stagingPath');
            $result = $this->applyPlanner->buildPlan($stagingPath);

            return $this->json([
                'applyPlan' => $result->toArray(),
            ], $result->isValid() ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);
        } catch (Throwable $exception) {
            return $this->json([
                'error' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }


    #[Route('/progress/{operationId}', name: 'progress', methods: ['GET'])]
    public function progress(string $operationId): JsonResponse
    {
        try {
            return $this->json([
                'progress' => $this->operationLogger->read($operationId),
            ]);
        } catch (Throwable $exception) {
            return $this->json([
                'error' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }


    #[Route('/apply-files', name: 'apply_files', methods: ['POST'])]
    public function applyFiles(Request $request): JsonResponse
    {
        $payload = $this->readJsonPayload($request);

        try {
            $stagingPath = $this->readRequiredString($payload, 'stagingPath');
            $confirmed = $this->readApplyFilesConfirmation($payload);
            $operationId = $this->readNullableString($payload, 'operationId');
            $result = $this->fileApplier->apply($stagingPath, $confirmed, $operationId);

            return $this->json([
                'operationId' => $operationId,
                'applyFiles' => $result->toArray(),
            ], $result->isValid() ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);
        } catch (Throwable $exception) {
            return $this->json([
                'error' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function readJsonPayload(Request $request): array
    {
        try {
            $payload = $request->toArray();
        } catch (Throwable) {
            return [];
        }

        return \is_array($payload) ? $payload : [];
    }


    /**
     * @param array<string, mixed> $payload
     */
    private function readRequiredString(array $payload, string $key): string
    {
        $value = $this->readNullableString($payload, $key);

        if (null === $value) {
            throw new InvalidArgumentException('Missing required field: '.$key);
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function readManifestSource(array $payload): string
    {
        $source = $this->readNullableString($payload, 'manifestSource') ?? $this->updateConfiguration->getDefaultManifestSource();

        if (null === $source) {
            throw new InvalidArgumentException('No update manifest source was provided and no default update manifest URL is configured.');
        }

        return $this->normalizeLocalSource($source);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function readNullableString(array $payload, string $key): ?string
    {
        $value = $payload[$key] ?? null;

        if (!\is_string($value)) {
            return null;
        }

        $value = trim($value);

        return '' !== $value ? $value : null;
    }

    private function normalizeLocalSource(string $source): string
    {
        if ($this->isHttpUrl($source)) {
            return $source;
        }

        if (!$this->updateConfiguration->allowsLocalPaths()) {
            throw new InvalidArgumentException('Local update manifest paths are disabled in this environment. Configure CHAMILO_UPDATE_MANIFEST_URL or provide an HTTPS manifest URL.');
        }

        if ($this->isAbsolutePath($source)) {
            return $source;
        }

        return $this->getProjectDir().'/'.ltrim($source, '/');
    }

    private function normalizeNullableLocalPath(?string $path, string $label): ?string
    {
        if (null === $path) {
            return null;
        }

        if ($this->isHttpUrl($path)) {
            throw new InvalidArgumentException('Do not provide an HTTP '.$label.' path. Leave this field empty to download it from the update manifest.');
        }

        if (!$this->updateConfiguration->allowsLocalPaths()) {
            throw new InvalidArgumentException('Local update '.$label.' paths are disabled in this environment.');
        }

        if ($this->isAbsolutePath($path)) {
            return $path;
        }

        return $this->getProjectDir().'/'.ltrim($path, '/');
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveTrustedPublicKey(array $payload): ?string
    {
        $configuredPublicKey = $this->updateConfiguration->getTrustedPublicKey();

        if (null !== $configuredPublicKey) {
            return $configuredPublicKey;
        }

        $payloadPublicKey = $this->readNullableString($payload, 'trustedPublicKey');

        if (null === $payloadPublicKey) {
            return null;
        }

        if (!$this->updateConfiguration->allowsLocalPaths()) {
            throw new InvalidArgumentException('Trusted update public keys must be configured on the server in this environment.');
        }

        return $payloadPublicKey;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function readSkipSignature(array $payload): bool
    {
        $skipSignature = true === ($payload['skipSignature'] ?? false);

        if ($skipSignature && !$this->updateConfiguration->allowsSkipSignature()) {
            throw new InvalidArgumentException('Skipping update signature verification is disabled in this environment.');
        }

        return $skipSignature;
    }


    /**
     * @param array<string, mixed> $payload
     */
    private function readApplyFilesConfirmation(array $payload): bool
    {
        $confirmed = true === ($payload['confirmApply'] ?? false);
        $confirmationText = $this->readNullableString($payload, 'confirmationText');

        if (!$confirmed || 'APPLY UPDATE FILES' !== $confirmationText) {
            throw new InvalidArgumentException('Applying staged update files requires the confirmation text "APPLY UPDATE FILES".');
        }

        return true;
    }

    private function isHttpUrl(string $source): bool
    {
        return 1 === preg_match('/^https?:\/\//i', $source);
    }

    private function isAbsolutePath(string $path): bool
    {
        if ('' === $path) {
            return false;
        }

        if ('/' === $path[0]) {
            return true;
        }

        return 1 === preg_match('/^[A-Za-z]:[\\\\\/]/', $path);
    }

    private function getProjectDir(): string
    {
        $projectDir = $this->getParameter('kernel.project_dir');

        if (!\is_string($projectDir)) {
            throw new InvalidArgumentException('Unable to resolve project directory.');
        }

        return rtrim($projectDir, '/');
    }

    /**
     * @return array<string, mixed>
     */
    private function manifestToArray(UpdateManifest $manifest): array
    {
        return [
            'channel' => $manifest->getChannel(),
            'version' => $manifest->getVersion(),
            'releasedAt' => $manifest->getReleasedAt(),
            'packageUrl' => $manifest->getPackageUrl(),
            'packageSha256' => $manifest->getPackageSha256(),
            'signatureType' => $manifest->getSignatureType(),
            'signatureUrl' => $manifest->getSignatureUrl(),
            'requirements' => $manifest->getRequirements(),
            'requiresSignature' => $manifest->requiresSignature(),
        ];
    }

    private function getInstalledVersion(): string
    {
        try {
            $version = InstalledVersions::getPrettyVersion('chamilo/chamilo-lms');

            if (\is_string($version) && '' !== trim($version)) {
                return $version;
            }
        } catch (Throwable) {
        }

        return 'unknown';
    }
}
