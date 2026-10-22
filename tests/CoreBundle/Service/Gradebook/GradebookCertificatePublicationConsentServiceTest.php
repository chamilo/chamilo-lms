<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Service\Gradebook;

use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Service\Gradebook\GradebookCertificatePublicationConsentService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class GradebookCertificatePublicationConsentServiceTest extends TestCase
{
    public function testApprovalIsIdempotentAndWithdrawalRecordsItsOwnTimestamp(): void
    {
        $certificate = new GradebookCertificate();
        $service = new GradebookCertificatePublicationConsentService();
        $first = new DateTimeImmutable('2026-10-21T12:00:00+00:00');
        $later = new DateTimeImmutable('2026-10-22T12:00:00+00:00');

        $service->update($certificate, true, $first);
        $service->update($certificate, true, $later);

        self::assertEquals($first, $certificate->getUserPublicationApprovalDate());
        self::assertNull($certificate->getUserPublicationRevocationDate());

        $service->update($certificate, false, $later);
        self::assertNull($certificate->getUserPublicationApprovalDate());
        self::assertEquals($later, $certificate->getUserPublicationRevocationDate());

        $service->update($certificate, false, $first);
        self::assertEquals($later, $certificate->getUserPublicationRevocationDate());
    }

    public function testReapprovalClearsPriorWithdrawalAndRecordsNewConsent(): void
    {
        $certificate = new GradebookCertificate();
        $service = new GradebookCertificatePublicationConsentService();
        $first = new DateTimeImmutable('2026-10-21T12:00:00+00:00');
        $later = new DateTimeImmutable('2026-10-22T12:00:00+00:00');

        $service->update($certificate, true, $first);
        $service->update($certificate, false, $later);
        $service->update($certificate, true, $later);

        self::assertEquals($later, $certificate->getUserPublicationApprovalDate());
        self::assertNull($certificate->getUserPublicationRevocationDate());
    }
}
