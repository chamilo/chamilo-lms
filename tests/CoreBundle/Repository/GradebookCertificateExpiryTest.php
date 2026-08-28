<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Repository\GradebookCertificateRepository;
use DateTime;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Unit test for GradebookCertificateRepository::applyValidityPeriodExpiry(), the
 * single choke point that computes gradebook_certificate.expiry_date from the
 * category's certificateValidityPeriod (see the certificate-expiry feature plan).
 * The business rule: expiry = createdAt + N days when a validity period is set;
 * otherwise the field is never touched, so a manually-set date survives.
 *
 * This deliberately avoids the DB/container: the repository is instantiated via
 * ReflectionClass::newInstanceWithoutConstructor() (applyValidityPeriodExpiry()
 * only reads its two arguments, never $this), so this exercises the exact
 * production method with no mocking of Doctrine internals required.
 */
class GradebookCertificateExpiryTest extends TestCase
{
    private GradebookCertificateRepository $repository;

    protected function setUp(): void
    {
        $reflection = new ReflectionClass(GradebookCertificateRepository::class);
        $this->repository = $reflection->newInstanceWithoutConstructor();
    }

    private function callApplyValidityPeriodExpiry(GradebookCertificate $cert, ?GradebookCategory $category): void
    {
        $method = new ReflectionMethod(GradebookCertificateRepository::class, 'applyValidityPeriodExpiry');
        $method->setAccessible(true);
        $method->invoke($this->repository, $cert, $category);
    }

    public function testExpiryDateComputedWhenValidityPeriodIsSet(): void
    {
        $category = (new GradebookCategory())->setCertificateValidityPeriod(30);
        $cert = (new GradebookCertificate())->setCreatedAt(new DateTime('2026-01-01 10:00:00'));

        $this->callApplyValidityPeriodExpiry($cert, $category);

        $this->assertNotNull($cert->getExpiryDate());
        $this->assertSame('2026-01-31', $cert->getExpiryDate()->format('Y-m-d'));
    }

    public function testExpiryDateNotSetWhenNoValidityPeriod(): void
    {
        $category = new GradebookCategory(); // certificateValidityPeriod defaults to null
        $cert = (new GradebookCertificate())->setCreatedAt(new DateTime('2026-01-01'));

        $this->callApplyValidityPeriodExpiry($cert, $category);

        $this->assertNull($cert->getExpiryDate());
    }

    public function testExpiryDateNotSetWhenValidityPeriodIsZero(): void
    {
        $category = (new GradebookCategory())->setCertificateValidityPeriod(0);
        $cert = (new GradebookCertificate())->setCreatedAt(new DateTime('2026-01-01'));

        $this->callApplyValidityPeriodExpiry($cert, $category);

        $this->assertNull($cert->getExpiryDate());
    }

    public function testExpiryDateNotSetWhenCategoryIsNull(): void
    {
        $cert = (new GradebookCertificate())->setCreatedAt(new DateTime('2026-01-01'));

        $this->callApplyValidityPeriodExpiry($cert, null);

        $this->assertNull($cert->getExpiryDate());
    }

    public function testManualExpiryDateSurvivesWhenNoValidityPeriod(): void
    {
        $category = new GradebookCategory();
        $manualDate = new DateTime('2030-06-15');
        $cert = (new GradebookCertificate())
            ->setCreatedAt(new DateTime('2026-01-01'))
            ->setExpiryDate($manualDate)
        ;

        $this->callApplyValidityPeriodExpiry($cert, $category);

        $this->assertSame($manualDate, $cert->getExpiryDate(), 'A manually-set expiry date must be left untouched.');
    }

    public function testExpiryDateIsIdempotentAcrossRepeatedCalls(): void
    {
        // Mirrors GradebookCertificateGenerator::generate(), which calls
        // upsertCertificateResource() (and therefore this method) twice per request.
        $category = (new GradebookCategory())->setCertificateValidityPeriod(90);
        $cert = (new GradebookCertificate())->setCreatedAt(new DateTime('2026-03-10'));

        $this->callApplyValidityPeriodExpiry($cert, $category);
        $firstResult = $cert->getExpiryDate()->format('Y-m-d');

        $this->callApplyValidityPeriodExpiry($cert, $category);
        $secondResult = $cert->getExpiryDate()->format('Y-m-d');

        $this->assertSame($firstResult, $secondResult);
        $this->assertSame('2026-06-08', $firstResult);
    }

    public function testExpiryDateOnRegenerationUsesCreatedAtNotToday(): void
    {
        // Regeneration reuses the certificate's original createdAt (see
        // GradebookCertificateGenerator::generate(), which deliberately keeps the
        // issue date stable across re-renders) — expiry must move with it, not
        // reset from "today".
        $category = (new GradebookCategory())->setCertificateValidityPeriod(10);
        $cert = (new GradebookCertificate())->setCreatedAt(new DateTime('2020-01-01'));

        $this->callApplyValidityPeriodExpiry($cert, $category);

        $this->assertSame('2020-01-11', $cert->getExpiryDate()->format('Y-m-d'));
    }
}
