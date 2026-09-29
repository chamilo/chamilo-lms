<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Gradebook;

use Category;
use Certificate;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\GradebookCertificateRepository;
use RuntimeException;

use const PATHINFO_FILENAME;

/**
 * Isolates the remaining legacy certificate-rendering compatibility path.
 *
 * Gradebook navigation, permissions and data operations stay in the modern stack. This bridge is used only when
 * the existing certificate renderer or CustomCertificate integration is required to preserve template/plugin parity.
 */
final readonly class LegacyGradebookCertificateBridge
{
    public function __construct(
        private GradebookCertificateRepository $certificateRepository,
    ) {}

    public function generate(GradebookCategory $category, User $user): GradebookCertificate
    {
        if (!class_exists(Category::class)) {
            throw new RuntimeException('The legacy Gradebook certificate compatibility bridge is unavailable.');
        }

        Category::generateUserCertificate($category, (int) $user->getId());

        $certificate = $this->certificateRepository->getCertificateByUserId(
            (int) $category->getId(),
            (int) $user->getId(),
        );
        if (!$certificate instanceof GradebookCertificate) {
            throw new RuntimeException('The Gradebook certificate could not be generated.');
        }

        return $certificate;
    }

    public function notify(
        GradebookCertificate $certificate,
        User $user,
        string $courseTitle,
        string $subject,
        string $message,
        int $senderId,
    ): bool {
        if (!class_exists(Certificate::class)) {
            throw new RuntimeException('The legacy Gradebook certificate notification bridge is unavailable.');
        }

        $path = trim((string) $certificate->getPathCertificate());
        $hash = pathinfo(basename($path), PATHINFO_FILENAME);
        $viewUrl = 1 === preg_match('/^[A-Za-z0-9_-]+$/', $hash)
            ? '/certificates/'.rawurlencode($hash).'.html'
            : '';

        return Certificate::sendNotification(
            $subject,
            $message,
            [
                'id' => (int) $user->getId(),
                'firstname' => (string) $user->getFirstname(),
                'lastname' => (string) $user->getLastname(),
            ],
            ['title' => $courseTitle],
            [
                'score_certificate' => (float) ($certificate->getScoreCertificate() ?? 0),
                'path_certificate' => $path,
                'html_url' => $viewUrl,
            ],
            false,
            $senderId,
        );
    }
}
