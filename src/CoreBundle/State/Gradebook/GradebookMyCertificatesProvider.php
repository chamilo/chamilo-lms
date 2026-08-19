<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookMyCertificates;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Service\Gradebook\GradebookCertificateGenerator;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use const DATE_ATOM;

/**
 * @implements ProviderInterface<GradebookMyCertificates>
 */
final readonly class GradebookMyCertificatesProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AccessUrlHelper $accessUrlHelper,
        private Security $security,
        private SettingsManager $settingsManager,
        private GradebookCertificateGenerator $certificateGenerator,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): GradebookMyCertificates
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('A valid user is required.');
        }

        $currentAccessUrlId = (int) ($this->accessUrlHelper->getCurrent()?->getId() ?? 0);
        if ($currentAccessUrlId <= 0) {
            throw new AccessDeniedHttpException('A valid access URL is required.');
        }

        $hideExport = $this->isSettingEnabled('certificate.hide_certificate_export_link');
        $hideStudentExport = $this->isSettingEnabled('certificate.hide_certificate_export_link_students');
        $allowExport = !$hideExport && !($user->isStudent() && $hideStudentExport);

        $resource = new GradebookMyCertificates();
        $resource->allowExport = $allowExport;
        $resource->allowSearch = $this->isSettingEnabled('certificate.allow_certificates_search');
        $resource->searchUrl = $resource->allowSearch ? '/certificates/search' : '';

        $certificates = $this->entityManager->getRepository(GradebookCertificate::class)->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC'],
        );

        foreach ($certificates as $certificate) {
            if (!$certificate instanceof GradebookCertificate) {
                continue;
            }

            $category = $certificate->getCategory();
            if (!$category instanceof GradebookCategory
                || null !== $category->getParent()
                || !$category->getGenerateCertificates()
            ) {
                continue;
            }

            $course = $category->getCourse();
            if (!$this->courseBelongsToAccessUrl($course, $currentAccessUrlId)) {
                continue;
            }

            $session = $category->getSession();
            if (null === $session) {
                $subscription = $this->entityManager->getRepository(CourseRelUser::class)->findOneBy([
                    'course' => $course,
                    'user' => $user,
                ]);
                if (!$subscription instanceof CourseRelUser) {
                    continue;
                }
            } else {
                $subscription = $this->entityManager->getRepository(SessionRelCourseRelUser::class)->findOneBy([
                    'course' => $course,
                    'session' => $session,
                    'user' => $user,
                ]);
                if (!$subscription instanceof SessionRelCourseRelUser) {
                    continue;
                }
            }

            $summary = $this->certificateGenerator->normalizeCertificate($certificate, !$allowExport);
            $row = [
                'id' => (int) $certificate->getId(),
                'course' => [
                    'id' => (int) $course->getId(),
                    'code' => (string) $course->getCode(),
                    'title' => (string) $course->getTitle(),
                ],
                'score' => (float) $certificate->getScoreCertificate(),
                'issuedAt' => $certificate->getCreatedAt()->format(DATE_ATOM),
                'certificate' => $summary,
            ];

            if (null === $session) {
                $resource->courseCertificates[] = $row;

                continue;
            }

            $row['session'] = [
                'id' => (int) $session->getId(),
                'title' => (string) $session->getTitle(),
            ];
            $row['certificate']['downloadUrl'] = '';
            $resource->sessionCertificates[] = $row;
        }

        return $resource;
    }

    private function courseBelongsToAccessUrl(Course $course, int $accessUrlId): bool
    {
        foreach ($course->getUrls() as $relation) {
            if ((int) $relation->getUrl()->getId() === $accessUrlId) {
                return true;
            }
        }

        return false;
    }

    private function isSettingEnabled(string $name): bool
    {
        $value = $this->settingsManager->getSetting($name, true);

        return true === $value || 'true' === strtolower((string) $value) || '1' === (string) $value;
    }
}
