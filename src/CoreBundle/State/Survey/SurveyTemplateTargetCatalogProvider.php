<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Survey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Survey\SurveyTemplateTargetCatalog;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProviderInterface<SurveyTemplateTargetCatalog>
 */
final readonly class SurveyTemplateTargetCatalogProvider implements ProviderInterface
{
    public function __construct(
        private Connection $connection,
        private AccessUrlHelper $accessUrlHelper,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SurveyTemplateTargetCatalog
    {
        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (null === $accessUrl) {
            throw new BadRequestHttpException('The current access URL is required.');
        }

        $accessUrlId = (int) $accessUrl->getId();
        $directCourses = $this->connection->fetchAllAssociative(
            'SELECT c.id, c.code, c.title
               FROM course c
         INNER JOIN access_url_rel_course aurc ON aurc.c_id = c.id
              WHERE aurc.access_url_id = :accessUrlId
           ORDER BY c.title ASC, c.code ASC, c.id ASC',
            ['accessUrlId' => $accessUrlId],
        );

        $sessionCourses = $this->connection->fetchAllAssociative(
            'SELECT DISTINCT src.c_id AS course_id, c.code, c.title AS course_title, s.id, s.title
               FROM session_rel_course src
         INNER JOIN course c ON c.id = src.c_id
         INNER JOIN session s ON s.id = src.session_id
         INNER JOIN access_url_rel_session aurs ON aurs.session_id = s.id
              WHERE aurs.access_url_id = :accessUrlId
           ORDER BY c.title ASC, s.title ASC, s.id ASC',
            ['accessUrlId' => $accessUrlId],
        );

        /** @var array<int, array{id:int,code:string,title:string,label:string,canAssignBase:bool,sessions:array<int, array{id:int,title:string}>}> $coursesById */
        $coursesById = [];
        foreach ($directCourses as $course) {
            $courseId = (int) ($course['id'] ?? 0);
            if ($courseId <= 0) {
                continue;
            }

            $title = trim((string) ($course['title'] ?? ''));
            $code = trim((string) ($course['code'] ?? ''));
            $coursesById[$courseId] = [
                'id' => $courseId,
                'code' => $code,
                'title' => $title,
                'label' => '' !== $code ? $title.' ('.$code.')' : $title,
                'canAssignBase' => true,
                'sessions' => [],
            ];
        }

        foreach ($sessionCourses as $row) {
            $courseId = (int) ($row['course_id'] ?? 0);
            $sessionId = (int) ($row['id'] ?? 0);
            if ($courseId <= 0 || $sessionId <= 0) {
                continue;
            }

            if (!isset($coursesById[$courseId])) {
                $title = trim((string) ($row['course_title'] ?? ''));
                $code = trim((string) ($row['code'] ?? ''));
                $coursesById[$courseId] = [
                    'id' => $courseId,
                    'code' => $code,
                    'title' => $title,
                    'label' => '' !== $code ? $title.' ('.$code.')' : $title,
                    'canAssignBase' => false,
                    'sessions' => [],
                ];
            }

            $coursesById[$courseId]['sessions'][] = [
                'id' => $sessionId,
                'title' => (string) ($row['title'] ?? ''),
            ];
        }

        $items = array_values($coursesById);
        usort(
            $items,
            static fn (array $left, array $right): int => strcasecmp(
                $left['label'].' #'.$left['id'],
                $right['label'].' #'.$right['id'],
            ),
        );

        $response = new SurveyTemplateTargetCatalog();
        $response->courses = $items;

        return $response;
    }
}
