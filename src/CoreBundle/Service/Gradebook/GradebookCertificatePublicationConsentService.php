<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\Gradebook;

use Chamilo\CoreBundle\Entity\GradebookCertificate;
use DateTimeImmutable;

/**
 * Update the owner's voluntary publication approval, without publishing any data.
 */
final class GradebookCertificatePublicationConsentService
{
    public function update(GradebookCertificate $certificate, bool $approve, DateTimeImmutable $now): void
    {
        if ($approve) {
            // A legacy approval cannot authorize a new, more specific testnet scope.
            if ('sepolia-hash-only-v1' !== $certificate->getUserPublicationConsentScope()) {
                $certificate->setUserPublicationApprovalDate(null);
                $certificate->setUserPublicationConsentScope('sepolia-hash-only-v1');
            }
            // Repeated requests must not reset the original approval timestamp.
            if (null === $certificate->getUserPublicationApprovalDate()) {
                $certificate->setUserPublicationApprovalDate($now);
                $certificate->setUserPublicationRevocationDate(null);
            }

            return;
        }

        if (null !== $certificate->getUserPublicationApprovalDate()) {
            $certificate->setUserPublicationApprovalDate(null);
            $certificate->setUserPublicationRevocationDate($now);
            $certificate->setUserPublicationConsentScope(null);
        }
    }
}
