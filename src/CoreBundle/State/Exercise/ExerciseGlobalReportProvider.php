<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseGlobalReport;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Read-only data provider for the migrated exercise global report selector.
 *
 * The legacy report is course-scoped but is launched from the global reports
 * catalog. The Vue screen therefore lets the platform admin choose a course
 * before downloading the CSV.
 *
 * @implements ProviderInterface<ExerciseGlobalReport>
 */
final readonly class ExerciseGlobalReportProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private Connection $connection,
        private Security $security,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ExerciseGlobalReport
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $this->assertAccessAllowed();

        $selectedCourseId = max(0, $request->query->getInt('cid', $request->query->getInt('courseId')));
        $data = new ExerciseGlobalReport();
        $data->courseOptions = $this->getCourseOptions();
        $data->selectedCourseId = $selectedCourseId;
        $data->canExport = true;
        $data->actionUrls = [
            'exportCsv' => 0 < $selectedCourseId
                ? '/api/exercise/global-report/export.csv?cid='.$selectedCourseId
                : '',
        ];

        return $data;
    }

    private function assertAccessAllowed(): void
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        if (\function_exists('api_is_platform_admin') && api_is_platform_admin()) {
            return;
        }

        throw new AccessDeniedHttpException('You are not allowed to access the exercise global report.');
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    private function getCourseOptions(): array
    {
        $rows = $this->connection->executeQuery(
            'SELECT id, code, title FROM course ORDER BY title ASC, code ASC'
        )->fetchAllAssociative();

        $options = [];
        foreach ($rows as $row) {
            $courseId = (int) ($row['id'] ?? 0);
            if (0 >= $courseId) {
                continue;
            }

            $title = trim((string) ($row['title'] ?? ''));
            $code = trim((string) ($row['code'] ?? ''));
            $label = $title;
            if ('' !== $code) {
                $label = '' !== $label ? sprintf('%s (%s)', $label, $code) : $code;
            }

            $options[] = [
                'label' => $label,
                'value' => $courseId,
            ];
        }

        return $options;
    }
}
