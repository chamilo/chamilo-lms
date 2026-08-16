<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookCertificates;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Service\Gradebook\GradebookCertificateGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProviderInterface<GradebookCertificates>
 */
final readonly class GradebookCertificatesProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private GradebookContextResolver $contextResolver,
        private GradebookCertificateGenerator $certificateGenerator,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): GradebookCertificates
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $resolved = $this->contextResolver->resolve($request);
        $rootCategory = $resolved['rootCategory'];
        if (!$rootCategory instanceof GradebookCategory) {
            throw new NotFoundHttpException('The Gradebook was not found.');
        }

        $category = $this->contextResolver->getSelectedCategory(
            $request,
            $resolved['course'],
            $resolved['session'],
            $rootCategory,
        );
        if (
            !$resolved['canManage']
            && (int) $category->getId() !== (int) $rootCategory->getId()
            && !$category->getVisible()
        ) {
            throw new NotFoundHttpException('The requested Gradebook category was not found.');
        }
        $allStudents = $this->contextResolver->getStudents($resolved['course'], $resolved['session']);
        $officialCodeOptions = $resolved['canManage'] ? $this->buildOfficialCodeOptions($allStudents) : [];
        $officialCode = trim((string) $request->query->get('officialCode', ''));

        if ($resolved['canManage']) {
            $students = $this->filterStudentsByOfficialCode($allStudents, $officialCode);
        } else {
            $students = [
                $this->contextResolver->getStudentInContext(
                    (int) $resolved['user']->getId(),
                    $resolved['course'],
                    $resolved['session'],
                ),
            ];
        }

        $useCustomCertificateFallback = $this->certificateGenerator->usesCustomCertificate($resolved['course']);
        $hideExport = $this->contextResolver->isSettingEnabled('certificate.hide_certificate_export_link');
        $hideStudentExport = $this->contextResolver->isSettingEnabled(
            'certificate.hide_certificate_export_link_students',
        );
        $hideDownload = $hideExport || (!$resolved['canManage'] && $hideStudentExport);
        $rows = [];

        foreach ($students as $student) {
            $certificate = $this->certificateGenerator->getCertificateSummary(
                $category,
                $student,
                $hideDownload,
            );
            if (null === $certificate) {
                continue;
            }

            $rows[] = [
                'user' => [
                    'id' => (int) $student->getId(),
                    'fullName' => $student->getFullName(),
                    'username' => $student->getUsername(),
                    'officialCode' => (string) ($student->getOfficialCode() ?? ''),
                ],
                'score' => (float) ($certificate['score'] ?? 0),
                'certificate' => $certificate,
            ];
        }

        usort(
            $rows,
            static fn (array $left, array $right): int => strcasecmp(
                (string) ($left['user']['fullName'] ?? ''),
                (string) ($right['user']['fullName'] ?? ''),
            ),
        );

        $resource = new GradebookCertificates();
        $resource->context = [
            'cid' => (int) $resolved['course']->getId(),
            'sid' => (int) ($resolved['session']?->getId() ?? 0),
            'gid' => $resolved['groupId'],
            'node' => $request->query->getInt('node'),
        ];
        $resourceWeight = $this->getResourceWeight($category);
        $categoryWeight = (float) $category->getWeight();
        $resource->category = [
            'id' => (int) $category->getId(),
            'title' => $category->getTitle(),
            'weight' => $categoryWeight,
            'resourceWeight' => $resourceWeight,
            'weightWarning' => abs($resourceWeight - $categoryWeight) > 0.00001,
        ];
        $resource->canManage = $resolved['canManage'];
        $resource->settings = [
            'filterByOfficialCode' => $this->contextResolver->isSettingEnabled(
                'certificate.certificate_filter_by_official_code',
            ),
            'hideExport' => $hideExport,
            'customCertificateFallback' => $useCustomCertificateFallback,
        ];
        $resource->officialCodeOptions = $officialCodeOptions;
        $resource->learners = $rows;
        if ($resolved['canManage']) {
            $resource->csrfToken = $this->csrfTokenManager
                ->getToken(GradebookCertificateActionProcessor::CSRF_TOKEN_ID)
                ->getValue()
            ;
        }
        if ($useCustomCertificateFallback) {
            $resource->customCertificateFallbackUrl = '/main/gradebook/gradebook_display_certificate.php?'.http_build_query([
                'cid' => (int) $resolved['course']->getId(),
                'sid' => (int) ($resolved['session']?->getId() ?? 0),
                'gid' => $resolved['groupId'],
                'cat_id' => (int) $category->getId(),
                'filter' => '' !== $officialCode ? $officialCode : 'all',
            ]);
        }

        return $resource;
    }

    /**
     * @param list<User> $students
     *
     * @return list<array{label: string, value: string}>
     */
    private function buildOfficialCodeOptions(array $students): array
    {
        $codes = [];
        foreach ($students as $student) {
            $code = trim((string) ($student->getOfficialCode() ?? ''));
            if ('' !== $code) {
                $codes[$code] = true;
            }
        }

        $values = array_keys($codes);
        natcasesort($values);

        return array_map(
            static fn (string $code): array => ['label' => $code, 'value' => $code],
            array_values($values),
        );
    }

    /**
     * @param list<User> $students
     *
     * @return list<User>
     */
    private function filterStudentsByOfficialCode(array $students, string $officialCode): array
    {
        if ('' === $officialCode) {
            return $students;
        }

        return array_values(array_filter(
            $students,
            static fn (User $student): bool => $officialCode === trim((string) ($student->getOfficialCode() ?? '')),
        ));
    }

    private function getResourceWeight(GradebookCategory $category): float
    {
        $weight = 0.0;
        foreach ($category->getSubCategories() as $subCategory) {
            if ($subCategory instanceof GradebookCategory) {
                $weight += (float) $subCategory->getWeight();
            }
        }
        foreach ($category->getEvaluations() as $evaluation) {
            if ($evaluation instanceof GradebookEvaluation) {
                $weight += (float) $evaluation->getWeight();
            }
        }
        foreach ($category->getLinks() as $link) {
            if ($link instanceof GradebookLink) {
                $weight += (float) $link->getWeight();
            }
        }

        return $weight;
    }
}
