<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Service\Update\Dto\UpdateManifest;
use Chamilo\CoreBundle\Service\Update\UpdateManifestClient;
use Chamilo\CoreBundle\Service\Update\UpdatePackageDownloader;
use Chamilo\CoreBundle\Service\Update\UpdatePackageVerifier;
use Chamilo\CoreBundle\Service\Update\UpdatePreflightChecker;
use Chamilo\CoreBundle\Service\Update\UpdateStagingManager;
use Composer\InstalledVersions;
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
    ) {}

    #[Route('/status', name: 'status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        return $this->json([
            'installedVersion' => $this->getInstalledVersion(),
            'updateDirectory' => $this->getProjectDir().'/var/update/downloads',
            'stagingDirectory' => $this->getProjectDir().'/var/update/staging',
            'verificationOnly' => true,
        ]);
    }

    #[Route('/check', name: 'check', methods: ['POST'])]
    public function check(Request $request): JsonResponse
    {
        $payload = $this->readJsonPayload($request);

        try {
            $manifestSource = $this->normalizeLocalSource($this->readRequiredString($payload, 'manifestSource'));
            $manifest = $this->manifestClient->load($manifestSource);

            return $this->json([
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
            $manifestSource = $this->normalizeLocalSource($this->readRequiredString($payload, 'manifestSource'));
            $packagePath = $this->normalizeNullableLocalPath($this->readNullableString($payload, 'packagePath'));
            $signaturePath = $this->normalizeNullableLocalPath($this->readNullableString($payload, 'signaturePath'));
            $trustedPublicKey = $this->readNullableString($payload, 'trustedPublicKey');
            $skipSignature = true === ($payload['skipSignature'] ?? false);
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
                'manifest' => $this->manifestToArray($manifest),
                'packagePath' => $packagePath,
                'signaturePath' => $signaturePath,
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
            $manifestSource = $this->normalizeLocalSource($this->readRequiredString($payload, 'manifestSource'));
            $packagePath = $this->normalizeNullableLocalPath($this->readNullableString($payload, 'packagePath'));
            $manifest = $this->manifestClient->load($manifestSource);
            $result = $this->preflightChecker->check($manifest, $packagePath);

            return $this->json([
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
            $manifestSource = $this->normalizeLocalSource($this->readRequiredString($payload, 'manifestSource'));
            $packagePath = $this->normalizeNullableLocalPath($this->readNullableString($payload, 'packagePath'));
            $signaturePath = $this->normalizeNullableLocalPath($this->readNullableString($payload, 'signaturePath'));
            $trustedPublicKey = $this->readNullableString($payload, 'trustedPublicKey');
            $skipSignature = true === ($payload['skipSignature'] ?? false);
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
                    'manifest' => $this->manifestToArray($manifest),
                    'packagePath' => $packagePath,
                    'signaturePath' => $signaturePath,
                    'verification' => $verificationResult->toArray(),
                    'preflight' => $preflightResult->toArray(),
                    'staging' => $stagingResult->toArray(),
                ], Response::HTTP_BAD_REQUEST);
            }

            return $this->json([
                'manifest' => $this->manifestToArray($manifest),
                'packagePath' => $packagePath,
                'signaturePath' => $signaturePath,
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
            throw new \InvalidArgumentException('Missing required field: '.$key);
        }

        return $value;
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
        if ($this->isHttpUrl($source) || $this->isAbsolutePath($source)) {
            return $source;
        }

        return $this->getProjectDir().'/'.ltrim($source, '/');
    }

    private function normalizeNullableLocalPath(?string $path): ?string
    {
        if (null === $path || $this->isHttpUrl($path) || $this->isAbsolutePath($path)) {
            return $path;
        }

        return $this->getProjectDir().'/'.ltrim($path, '/');
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
            throw new \InvalidArgumentException('Unable to resolve project directory.');
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
