<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Update;

use Chamilo\CoreBundle\Service\Update\Dto\UpdateManifest;
use Chamilo\CoreBundle\Service\Update\Dto\UpdatePackageVerificationResult;
use Chamilo\CoreBundle\Service\Update\Signature\MinisignSignatureVerifier;
use Chamilo\CoreBundle\Service\Update\Signature\UpdateSignatureVerifierInterface;
use RuntimeException;

final readonly class UpdatePackageVerifier
{
    /**
     * @var UpdateSignatureVerifierInterface[]
     */
    private array $signatureVerifiers;

    public function __construct(
        private UpdateArchiveInspector $archiveInspector,
        MinisignSignatureVerifier $minisignSignatureVerifier,
    ) {
        $this->signatureVerifiers = [
            $minisignSignatureVerifier,
        ];
    }

    public function verify(
        string $packagePath,
        UpdateManifest $manifest,
        ?string $signaturePath = null,
        ?string $trustedPublicKey = null,
        bool $skipSignature = false,
    ): UpdatePackageVerificationResult {
        $errors = [];
        $warnings = [];
        $details = [
            'package_path' => $packagePath,
            'expected_sha256' => $manifest->getPackageSha256(),
        ];

        if (!is_file($packagePath) || !is_readable($packagePath)) {
            return UpdatePackageVerificationResult::failure([
                'Update package is not readable: '.$packagePath,
            ], $details);
        }

        $actualSha256 = hash_file('sha256', $packagePath);

        if (false === $actualSha256) {
            return UpdatePackageVerificationResult::failure([
                'Unable to calculate update package sha256.',
            ], $details);
        }

        $details['actual_sha256'] = $actualSha256;

        if ($manifest->getPackageSha256() !== strtolower($actualSha256)) {
            $errors[] = 'Update package sha256 does not match the manifest.';
        }

        if ($skipSignature) {
            $warnings[] = 'Signature verification was skipped by explicit command option.';
        } elseif ($manifest->requiresSignature()) {
            $signatureResult = $this->verifySignature($packagePath, $manifest, $signaturePath, $trustedPublicKey);

            $details['signature'] = $signatureResult->getDetails();
            $errors = array_merge($errors, $signatureResult->getErrors());
            $warnings = array_merge($warnings, $signatureResult->getWarnings());
        } else {
            $warnings[] = 'Update manifest does not define a package signature.';
        }

        try {
            $details['archive'] = $this->archiveInspector->inspect($packagePath);
        } catch (RuntimeException $exception) {
            $errors[] = $exception->getMessage();
        }

        if ([] !== $errors) {
            return UpdatePackageVerificationResult::failure($errors, $details, $warnings);
        }

        return UpdatePackageVerificationResult::success($details, $warnings);
    }

    private function verifySignature(
        string $packagePath,
        UpdateManifest $manifest,
        ?string $signaturePath,
        ?string $trustedPublicKey
    ): UpdatePackageVerificationResult {
        if (null === $signaturePath || '' === trim($signaturePath)) {
            return UpdatePackageVerificationResult::failure([
                'Update manifest requires a signature, but no signature file was provided.',
            ], [
                'signature_type' => $manifest->getSignatureType(),
            ]);
        }

        foreach ($this->signatureVerifiers as $verifier) {
            if ($verifier->supports($manifest)) {
                return $verifier->verify($packagePath, $signaturePath, $manifest, $trustedPublicKey);
            }
        }

        return UpdatePackageVerificationResult::failure([
            'Unsupported update signature type: '.(string) $manifest->getSignatureType(),
        ], [
            'signature_type' => $manifest->getSignatureType(),
        ]);
    }
}
