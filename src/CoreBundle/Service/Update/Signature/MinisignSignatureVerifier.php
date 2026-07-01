<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Update\Signature;

use Chamilo\CoreBundle\Service\Update\Dto\UpdateManifest;
use Chamilo\CoreBundle\Service\Update\Dto\UpdatePackageVerificationResult;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\RuntimeException as ProcessRuntimeException;
use Symfony\Component\Process\Process;

final class MinisignSignatureVerifier implements UpdateSignatureVerifierInterface
{
    public function supports(UpdateManifest $manifest): bool
    {
        return 'minisign' === strtolower((string) $manifest->getSignatureType());
    }

    public function verify(
        string $packagePath,
        string $signaturePath,
        UpdateManifest $manifest,
        ?string $trustedPublicKey
    ): UpdatePackageVerificationResult {
        if (null === $trustedPublicKey || '' === trim($trustedPublicKey)) {
            return UpdatePackageVerificationResult::failure([
                'A trusted Minisign public key is required to verify the update package.',
            ]);
        }

        if (!is_file($signaturePath) || !is_readable($signaturePath)) {
            return UpdatePackageVerificationResult::failure([
                'Minisign signature file is not readable: '.$signaturePath,
            ]);
        }

        $process = new Process([
            'minisign',
            '-Vm',
            $packagePath,
            '-x',
            $signaturePath,
            '-P',
            trim($trustedPublicKey),
        ]);
        $process->setTimeout(60);

        try {
            $process->mustRun();
        } catch (ProcessFailedException|ProcessRuntimeException $exception) {
            return UpdatePackageVerificationResult::failure([
                'Minisign verification failed: '.$exception->getMessage(),
            ], [
                'signature_type' => $manifest->getSignatureType(),
            ]);
        }

        return UpdatePackageVerificationResult::success([
            'signature_type' => $manifest->getSignatureType(),
            'signature_verified' => true,
        ]);
    }
}
