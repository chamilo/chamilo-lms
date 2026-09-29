<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder\MoodleExport;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Chamilo\CourseBundle\Entity\CTool;
use Database;
use Throwable;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const PATHINFO_EXTENSION;

/**
 * Course-level Chamilo sidecars for Moodle .mbz export.
 *
 * Writes:
 * - chamilo/course/illustration.json (pointer to course overview image in files/)
 * - chamilo/course/tools.json (base-course tool visibility)
 * - chamilo/course/settings.json (course settings, full exports only)
 *
 * Moodle ignores the chamilo/ tree. The illustration binary is also registered
 * in files.xml with component=course / filearea=overviewfiles so a real Moodle
 * restore can show the course image natively.
 */
class CourseMetaExport
{
    /**
     * @var object
     */
    private $course;

    public function __construct(object $course)
    {
        $this->course = $course;
    }

    /**
     * Write course-level sidecars. Course settings are included only for full exports.
     */
    public function export(string $exportDir, bool $includeCourseSettings = false): void
    {
        $base = rtrim($exportDir, '/').'/chamilo/course';
        if (!is_dir($base) && !@mkdir($base, api_get_permissions_for_new_directories(), true) && !is_dir($base)) {
            @error_log('[CourseMetaExport] ERROR cannot create '.$base);

            return;
        }

        $illustration = $this->buildIllustrationSidecar();
        if (null !== $illustration) {
            $path = $base.'/illustration.json';
            @file_put_contents(
                $path,
                json_encode($illustration, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
            );
            $this->appendToManifest($exportDir, [
                'kind' => 'course_illustration',
                'path' => 'chamilo/course/illustration.json',
                'contenthash' => (string) ($illustration['contenthash'] ?? ''),
            ]);
        }

        $tools = $this->buildToolsSidecar();
        if (!empty($tools['tools'])) {
            $path = $base.'/tools.json';
            @file_put_contents(
                $path,
                json_encode($tools, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
            );
            $this->appendToManifest($exportDir, [
                'kind' => 'course_tools',
                'path' => 'chamilo/course/tools.json',
                'count' => \count($tools['tools']),
            ]);
        }

        if ($includeCourseSettings) {
            $settings = $this->buildCourseSettingsSidecar();
            if (!empty($settings['settings'])) {
                $path = $base.'/settings.json';
                @file_put_contents(
                    $path,
                    json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
                );
                $this->appendToManifest($exportDir, [
                    'kind' => 'course_settings',
                    'path' => 'chamilo/course/settings.json',
                    'count' => \count($settings['settings']),
                ]);
            }
        }
    }

    /**
     * Build a files.xml row for the course illustration (Moodle overviewfiles).
     *
     * @return array<string,mixed>|null
     */
    public function getIllustrationFileEntry(): ?array
    {
        $meta = $this->resolveIllustrationResource();
        if (null === $meta) {
            return null;
        }

        /** @var ResourceFile $resourceFile */
        $resourceFile = $meta['resourceFile'];
        $absPath = (string) $meta['abs_path'];
        $courseId = (int) $meta['course_id'];
        $filename = (string) $meta['filename'];
        $contenthash = (string) $meta['contenthash'];
        $filesize = (int) $meta['filesize'];
        $mimetype = (string) $meta['mimetype'];

        $adminData = MoodleExport::getAdminUserData();
        $adminId = (int) ($adminData['id'] ?? 1);

        return [
            'id' => 910000000 + max(1, $courseId),
            'contenthash' => $contenthash,
            'contextid' => $courseId,
            'component' => 'course',
            'filearea' => 'overviewfiles',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $filename,
            'userid' => $adminId,
            'filesize' => $filesize,
            'mimetype' => $mimetype,
            'status' => 0,
            'timecreated' => time() - 3600,
            'timemodified' => time(),
            'source' => $filename,
            'author' => 'Unknown',
            'license' => 'allrightsreserved',
            'abs_path' => $absPath,
            'originalname' => $resourceFile->getOriginalName() ?: $filename,
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    private function buildIllustrationSidecar(): ?array
    {
        $meta = $this->resolveIllustrationResource();
        if (null === $meta) {
            return null;
        }

        return [
            'contenthash' => (string) $meta['contenthash'],
            'filename' => (string) $meta['filename'],
            'mimetype' => (string) $meta['mimetype'],
            'filesize' => (int) $meta['filesize'],
            'component' => 'course',
            'filearea' => 'overviewfiles',
            'itemid' => 0,
            'contextid' => (int) $meta['course_id'],
        ];
    }

    /**
     * @return array{tools: list<array{title: string, visibility: bool, position: int}>}
     */
    private function buildToolsSidecar(): array
    {
        $tools = [];
        $courseId = (int) ($this->course->info['real_id'] ?? 0);
        if ($courseId <= 0 || !\function_exists('api_get_course_entity')) {
            return ['tools' => $tools];
        }

        try {
            $courseEntity = api_get_course_entity($courseId);
            if (!$courseEntity instanceof Course) {
                return ['tools' => $tools];
            }

            $em = Database::getManager();

            /** @var CTool[] $rows */
            $rows = $em->getRepository(CTool::class)->findBy(
                [
                    'course' => $courseEntity,
                    'session' => null,
                ],
                ['position' => 'ASC']
            );

            foreach ($rows as $cTool) {
                if (!$cTool instanceof CTool) {
                    continue;
                }
                $title = trim((string) $cTool->getTitle());
                if ('' === $title) {
                    continue;
                }
                $tools[] = [
                    'title' => $title,
                    'visibility' => (bool) $cTool->getVisibility(),
                    'position' => (int) $cTool->getPosition(),
                ];
            }
        } catch (Throwable $e) {
            @error_log('[CourseMetaExport] tools export error: '.$e->getMessage());
        }

        return ['tools' => $tools];
    }

    /**
     * @return array{settings: list<array{
     *     variable: string,
     *     value: string|null,
     *     category: string|null,
     *     subkey: string|null,
     *     type: string|null,
     *     title: string,
     *     comment: string|null,
     *     subkeytext: string|null
     * }>}
     */
    private function buildCourseSettingsSidecar(): array
    {
        $settings = [];
        $courseId = (int) ($this->course->info['real_id'] ?? 0);
        if ($courseId <= 0) {
            return ['settings' => $settings];
        }

        try {
            $em = Database::getManager();

            /** @var CCourseSetting[] $rows */
            $rows = $em->getRepository(CCourseSetting::class)->findBy(
                ['cId' => $courseId],
                ['category' => 'ASC', 'variable' => 'ASC', 'iid' => 'ASC']
            );

            foreach ($rows as $setting) {
                if (!$setting instanceof CCourseSetting) {
                    continue;
                }

                $variable = trim($setting->getVariable());
                if ('' === $variable) {
                    continue;
                }

                $settings[] = [
                    'variable' => $variable,
                    'value' => $setting->getValue(),
                    'category' => $setting->getCategory(),
                    'subkey' => $setting->getSubkey(),
                    'type' => $setting->getType(),
                    'title' => (string) ($setting->getTitle() ?: $variable),
                    'comment' => $setting->getComment(),
                    'subkeytext' => $setting->getSubkeytext(),
                ];
            }
        } catch (Throwable $e) {
            @error_log('[CourseMetaExport] settings export error: '.$e->getMessage());
        }

        return ['settings' => $settings];
    }

    /**
     * Resolve the course illustration ResourceFile + absolute path.
     *
     * @return array{
     *     course_id: int,
     *     resourceFile: ResourceFile,
     *     abs_path: string,
     *     contenthash: string,
     *     filename: string,
     *     filesize: int,
     *     mimetype: string
     * }|null
     */
    private function resolveIllustrationResource(): ?array
    {
        $courseId = (int) ($this->course->info['real_id'] ?? 0);
        if ($courseId <= 0 || !class_exists(Container::class) || null === Container::$container) {
            return null;
        }

        try {
            $courseEntity = api_get_course_entity($courseId);
            if (!$courseEntity instanceof Course || null === $courseEntity->getResourceNode()) {
                return null;
            }

            /** @var IllustrationRepository $illRepo */
            $illRepo = Container::getIllustrationRepository();
            if (!$illRepo->hasIllustration($courseEntity)) {
                return null;
            }

            $illustrationNode = $illRepo->getIllustrationNodeFromParent($courseEntity->getResourceNode());
            if (!$illustrationNode instanceof ResourceNode) {
                return null;
            }

            $resourceFile = $illustrationNode->getFirstResourceFile();
            if (!$resourceFile instanceof ResourceFile) {
                return null;
            }

            /** @var ResourceNodeRepository $rnRepo */
            $rnRepo = Container::$container->get(ResourceNodeRepository::class);
            $storedRel = (string) $rnRepo->getFilename($resourceFile);
            if ('' === $storedRel) {
                return null;
            }

            $projectDir = (string) Container::$container->get('kernel')->getProjectDir();
            $absPath = rtrim($projectDir, '/').'/var/upload/resource'.$storedRel;
            if (!is_readable($absPath)) {
                @error_log('[CourseMetaExport] illustration file not readable: '.$absPath);

                return null;
            }

            $contenthash = (string) sha1_file($absPath);
            if ('' === $contenthash) {
                return null;
            }

            $filename = trim((string) ($resourceFile->getOriginalName() ?: basename($storedRel)));
            if ('' === $filename || '.' === $filename) {
                $filename = 'course_image'.(pathinfo($absPath, PATHINFO_EXTENSION) ? '.'.pathinfo($absPath, PATHINFO_EXTENSION) : '.jpg');
            }
            // Moodle files.xml expects a leaf name, never a path.
            $filename = basename(str_replace('\\', '/', $filename));

            $filesize = (int) ($resourceFile->getSize() ?? 0);
            if ($filesize <= 0) {
                $stat = @stat($absPath);
                $filesize = (int) ($stat['size'] ?? 0);
            }

            $mimetype = trim((string) ($resourceFile->getMimeType() ?? ''));
            if ('' === $mimetype) {
                $mimetype = 'application/octet-stream';
            }

            return [
                'course_id' => $courseId,
                'resourceFile' => $resourceFile,
                'abs_path' => $absPath,
                'contenthash' => $contenthash,
                'filename' => $filename,
                'filesize' => $filesize,
                'mimetype' => $mimetype,
            ];
        } catch (Throwable $e) {
            @error_log('[CourseMetaExport] illustration resolve error: '.$e->getMessage());

            return null;
        }
    }

    /**
     * @param array<string,mixed> $record
     */
    private function appendToManifest(string $exportDir, array $record): void
    {
        $dir = rtrim($exportDir, '/').'/chamilo';
        if (!is_dir($dir)) {
            @mkdir($dir, (int) octdec('0775'), true);
        }

        $manifestFile = $dir.'/manifest.json';
        $manifest = [
            'version' => 1,
            'exporter' => 'C2-MoodleExport',
            'generatedAt' => date('c'),
            'items' => [],
        ];

        if (is_file($manifestFile)) {
            $decoded = json_decode((string) file_get_contents($manifestFile), true);
            if (\is_array($decoded)) {
                $manifest = array_replace_recursive($manifest, $decoded);
            }
            if (!isset($manifest['items']) || !\is_array($manifest['items'])) {
                $manifest['items'] = [];
            }
        }

        $manifest['items'][] = $record;

        @file_put_contents(
            $manifestFile,
            json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );
    }
}
