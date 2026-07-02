<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Update\Signature;

use Chamilo\CoreBundle\Service\Update\Dto\UpdateManifest;
use Chamilo\CoreBundle\Service\Update\Dto\UpdatePackageVerificationResult;

interface UpdateSignatureVerifierInterface
{
    public function supports(UpdateManifest $manifest): bool;

    public function verify(
        string $packagePath,
        string $signaturePath,
        UpdateManifest $manifest,
        ?string $trustedPublicKey
    ): UpdatePackageVerificationResult;
}
