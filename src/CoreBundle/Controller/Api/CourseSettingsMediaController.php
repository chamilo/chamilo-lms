<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\PluginHelper;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\State\CourseSettings\CourseSettingsManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use const UPLOAD_ERR_OK;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class CourseSettingsMediaController extends AbstractController
{
    private const array CERTIFICATE_MEDIA_FIELDS = [
        'logo_left',
        'logo_center',
        'logo_right',
        'seal',
        'signature1',
        'signature2',
        'signature3',
        'signature4',
        'background',
    ];

    public function __construct(
        private readonly CourseSettingsManager $manager,
        private readonly IllustrationRepository $illustrationRepository,
        private readonly Connection $connection,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly PluginHelper $pluginHelper,
        private readonly ParameterBagInterface $parameterBag,
        #[Autowire(service: 'oneup_flysystem.plugins_filesystem')]
        private readonly FilesystemOperator $pluginsFilesystem,
    ) {}

    #[Route('/api/course-settings/picture', name: 'api_course_settings_picture_upload', methods: ['POST'])]
    public function uploadPicture(Request $request): JsonResponse
    {
        [$course, $session] = $this->manager->resolveContext();
        $this->manager->assertCanEdit($course);
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile || UPLOAD_ERR_OK !== $file->getError()) {
            throw new BadRequestHttpException('A valid image file is required.');
        }
        $this->assertImage($file);
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new BadRequestHttpException('The current user is not available.');
        }
        if ($this->illustrationRepository->hasIllustration($course)) {
            $this->illustrationRepository->deleteIllustration($course);
        }
        $this->illustrationRepository->addIllustration(
            $course,
            $user,
            $file,
            trim((string) $request->request->get('crop', '')),
        );
        $this->manager->logMediaUpdate($course, $session, 'course_picture', $file->getClientOriginalName());

        return $this->json([
            'success' => true,
            'url' => $this->illustrationRepository->getIllustrationUrl($course, 'course_picture_medium'),
        ]);
    }

    #[Route('/api/course-settings/picture', name: 'api_course_settings_picture_delete', methods: ['DELETE'])]
    public function deletePicture(Request $request): JsonResponse
    {
        [$course, $session] = $this->manager->resolveContext();
        $this->manager->assertCanEdit($course);
        if ($this->illustrationRepository->hasIllustration($course)) {
            $this->illustrationRepository->deleteIllustration($course);
            $this->manager->logMediaUpdate($course, $session, 'course_picture', 'deleted');
        }

        return $this->json(['success' => true]);
    }

    #[Route('/api/course-settings/watermark', name: 'api_course_settings_watermark_upload', methods: ['POST'])]
    public function uploadWatermark(Request $request): JsonResponse
    {
        $this->assertWatermarkAvailable();
        [$course, $session] = $this->manager->resolveContext();
        $this->manager->assertCanEdit($course);
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile || UPLOAD_ERR_OK !== $file->getError()) {
            throw new BadRequestHttpException('A valid watermark image is required.');
        }
        $this->assertImage($file);
        $target = $this->getWatermarkPath($course);
        $directory = \dirname($target);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new BadRequestHttpException('The course directory could not be created.');
        }
        $image = imagecreatefromstring((string) file_get_contents($file->getPathname()));
        if (false === $image || !imagepng($image, $target)) {
            throw new BadRequestHttpException('The watermark image could not be stored.');
        }
        imagedestroy($image);
        $this->manager->logMediaUpdate($course, $session, 'pdf_export_watermark_path', $file->getClientOriginalName());

        return $this->json(['success' => true]);
    }

    #[Route('/api/course-settings/watermark', name: 'api_course_settings_watermark_delete', methods: ['DELETE'])]
    public function deleteWatermark(Request $request): JsonResponse
    {
        $this->assertWatermarkAvailable();
        [$course, $session] = $this->manager->resolveContext();
        $this->manager->assertCanEdit($course);
        $target = $this->getWatermarkPath($course);
        if (is_file($target)) {
            unlink($target);
            $this->manager->logMediaUpdate($course, $session, 'pdf_export_watermark_path', 'deleted');
        }

        return $this->json(['success' => true]);
    }

    #[Route('/api/course-settings/course-legal-file', name: 'api_course_settings_legal_file_download', methods: ['GET'])]
    public function downloadCourseLegalFile(Request $request): BinaryFileResponse
    {
        $this->assertCourseLegalAvailable();
        [$course, $session] = $this->manager->resolveContext();
        $this->manager->assertCanEdit($course);
        $courseId = (int) $course->getId();
        $sessionId = (int) ($session?->getId() ?? 0);
        $row = $this->connection->fetchAssociative(
            'SELECT filename FROM session_rel_course_legal WHERE c_id = :courseId AND session_id = :sessionId LIMIT 1',
            ['courseId' => $courseId, 'sessionId' => $sessionId],
            ['courseId' => Types::INTEGER, 'sessionId' => Types::INTEGER],
        );
        $filename = false === $row ? '' : basename((string) ($row['filename'] ?? ''));
        $path = $this->parameterBag->get('kernel.project_dir')
            .'/var/upload/course_legal/course_'.$courseId.'/session_'.$sessionId.'/'.$filename;
        if ('' === $filename || !is_file($path)) {
            throw $this->createNotFoundException('The course agreement file was not found.');
        }

        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);

        return $response;
    }

    #[Route('/api/course-settings/course-legal-file', name: 'api_course_settings_legal_file_upload', methods: ['POST'])]
    public function uploadCourseLegalFile(Request $request): JsonResponse
    {
        $this->assertCourseLegalAvailable();
        [$course, $session] = $this->manager->resolveContext();
        $this->manager->assertCanEdit($course);
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile || UPLOAD_ERR_OK !== $file->getError()) {
            throw new BadRequestHttpException('A valid agreement file is required.');
        }
        if ($file->getSize() > 20 * 1024 * 1024) {
            throw new BadRequestHttpException('The agreement file is too large.');
        }
        $courseId = (int) $course->getId();
        $sessionId = (int) ($session?->getId() ?? 0);
        $directory = $this->parameterBag->get('kernel.project_dir').'/var/upload/course_legal/course_'.$courseId.'/session_'.$sessionId;
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new BadRequestHttpException('The agreement directory could not be created.');
        }
        $filename = date('YmdHis').'_'.bin2hex(random_bytes(8)).'_'.$this->sanitizeFilename($file->getClientOriginalName());
        $file->move($directory, $filename);
        $row = $this->connection->fetchAssociative(
            'SELECT id, filename FROM session_rel_course_legal WHERE c_id = :courseId AND session_id = :sessionId LIMIT 1',
            ['courseId' => $courseId, 'sessionId' => $sessionId],
            ['courseId' => Types::INTEGER, 'sessionId' => Types::INTEGER],
        );
        if (false !== $row && !empty($row['filename'])) {
            $oldPath = $directory.'/'.basename((string) $row['filename']);
            if (is_file($oldPath)) {
                unlink($oldPath);
            }
        }
        if (false === $row) {
            $this->connection->insert(
                'session_rel_course_legal',
                [
                    'c_id' => $courseId,
                    'session_id' => $sessionId,
                    'content' => '',
                    'filename' => $filename,
                ],
                [
                    'c_id' => Types::INTEGER,
                    'session_id' => Types::INTEGER,
                    'content' => Types::TEXT,
                    'filename' => Types::STRING,
                ],
            );
        } else {
            $this->connection->update(
                'session_rel_course_legal',
                ['filename' => $filename],
                ['id' => (int) $row['id']],
                ['filename' => Types::STRING],
                ['id' => Types::INTEGER],
            );
        }

        return $this->json(['success' => true, 'filename' => $filename]);
    }

    #[Route('/api/course-settings/course-legal-file', name: 'api_course_settings_legal_file_delete', methods: ['DELETE'])]
    public function deleteCourseLegalFile(Request $request): JsonResponse
    {
        $this->assertCourseLegalAvailable();
        [$course, $session] = $this->manager->resolveContext();
        $this->manager->assertCanEdit($course);
        $courseId = (int) $course->getId();
        $sessionId = (int) ($session?->getId() ?? 0);
        $row = $this->connection->fetchAssociative(
            'SELECT id, filename FROM session_rel_course_legal WHERE c_id = :courseId AND session_id = :sessionId LIMIT 1',
            ['courseId' => $courseId, 'sessionId' => $sessionId],
            ['courseId' => Types::INTEGER, 'sessionId' => Types::INTEGER],
        );
        if (false !== $row) {
            $directory = $this->parameterBag->get('kernel.project_dir').'/var/upload/course_legal/course_'.$courseId.'/session_'.$sessionId;
            $path = $directory.'/'.basename((string) ($row['filename'] ?? ''));
            if (is_file($path)) {
                unlink($path);
            }
            $this->connection->update(
                'session_rel_course_legal',
                ['filename' => ''],
                ['id' => (int) $row['id']],
                ['filename' => Types::STRING],
                ['id' => Types::INTEGER],
            );
        }

        return $this->json(['success' => true]);
    }

    #[Route('/api/course-settings/custom-certificate-media/{field}', name: 'api_course_settings_certificate_media_download', methods: ['GET'])]
    public function downloadCertificateMedia(string $field, Request $request): BinaryFileResponse
    {
        $this->assertCustomCertificateAvailable();
        if (!\in_array($field, self::CERTIFICATE_MEDIA_FIELDS, true)) {
            throw new BadRequestHttpException('Invalid certificate image field.');
        }
        [$course, $session] = $this->manager->resolveContext();
        $this->manager->assertCanEdit($course);
        $courseId = (int) $course->getId();
        $sessionId = (int) ($session?->getId() ?? 0);
        $accessUrlId = (int) ($this->accessUrlHelper->getCurrent()?->getId() ?? 0);
        $row = $this->connection->fetchAssociative(
            'SELECT '.$field.' AS media_path FROM plugin_customcertificate WHERE c_id = :courseId AND session_id = :sessionId AND access_url_id = :accessUrlId LIMIT 1',
            ['courseId' => $courseId, 'sessionId' => $sessionId, 'accessUrlId' => $accessUrlId],
            ['courseId' => Types::INTEGER, 'sessionId' => Types::INTEGER, 'accessUrlId' => Types::INTEGER],
        );
        $relativePath = false === $row ? '' : ltrim((string) ($row['media_path'] ?? ''), '/');
        $storagePath = 'customcertificate/certificates/'.$relativePath;
        if ('' === $relativePath || !$this->pluginsFilesystem->fileExists($storagePath)) {
            throw $this->createNotFoundException('The certificate image was not found.');
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'certificate_media_');
        if (false === $temporaryPath) {
            throw new BadRequestHttpException('The certificate image could not be prepared.');
        }
        $stream = $this->pluginsFilesystem->readStream($storagePath);
        if (false === $stream) {
            throw new BadRequestHttpException('The certificate image could not be read.');
        }
        $target = fopen($temporaryPath, 'wb');
        if (false === $target) {
            fclose($stream);

            throw new BadRequestHttpException('The certificate image could not be prepared.');
        }
        stream_copy_to_stream($stream, $target);
        fclose($stream);
        fclose($target);

        $response = new BinaryFileResponse($temporaryPath);
        $response->deleteFileAfterSend();
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, basename($relativePath));

        return $response;
    }

    #[Route('/api/course-settings/custom-certificate-media/{field}', name: 'api_course_settings_certificate_media_upload', methods: ['POST'])]
    public function uploadCertificateMedia(string $field, Request $request): JsonResponse
    {
        $this->assertCustomCertificateAvailable();
        if (!\in_array($field, self::CERTIFICATE_MEDIA_FIELDS, true)) {
            throw new BadRequestHttpException('Invalid certificate image field.');
        }
        [$course, $session] = $this->manager->resolveContext();
        $this->manager->assertCanEdit($course);
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile || UPLOAD_ERR_OK !== $file->getError()) {
            throw new BadRequestHttpException('A valid certificate image is required.');
        }
        $this->assertImage($file);
        $certificateId = $this->ensureCertificateRow($course, (int) ($session?->getId() ?? 0));
        $oldRelativePath = (string) ($this->connection->fetchOne(
            'SELECT '.$field.' FROM plugin_customcertificate WHERE id = :id',
            ['id' => $certificateId],
            ['id' => Types::INTEGER],
        ) ?: '');
        $extension = $this->getImageExtension($file);
        $filename = $field.'_'.bin2hex(random_bytes(8)).'.'.$extension;
        $storedPath = 'customcertificate/certificates/'.$certificateId.'/'.$filename;
        $stream = fopen($file->getPathname(), 'rb');
        if (false === $stream) {
            throw new BadRequestHttpException('The certificate image could not be opened.');
        }

        try {
            $this->pluginsFilesystem->writeStream($storedPath, $stream);
        } finally {
            fclose($stream);
        }
        $relative = $certificateId.'/'.$filename;
        $this->connection->update(
            'plugin_customcertificate',
            [$field => $relative],
            ['id' => $certificateId],
            [$field => Types::STRING],
            ['id' => Types::INTEGER],
        );
        $this->deleteCertificateFileIfUnused($field, $oldRelativePath, $certificateId);

        return $this->json(['success' => true, 'path' => $relative]);
    }

    #[Route('/api/course-settings/custom-certificate-media/{field}', name: 'api_course_settings_certificate_media_delete', methods: ['DELETE'])]
    public function deleteCertificateMedia(string $field, Request $request): JsonResponse
    {
        $this->assertCustomCertificateAvailable();
        if (!\in_array($field, self::CERTIFICATE_MEDIA_FIELDS, true)) {
            throw new BadRequestHttpException('Invalid certificate image field.');
        }
        [$course, $session] = $this->manager->resolveContext();
        $this->manager->assertCanEdit($course);
        $courseId = (int) $course->getId();
        $sessionId = (int) ($session?->getId() ?? 0);
        $accessUrlId = (int) ($this->accessUrlHelper->getCurrent()?->getId() ?? 0);
        $row = $this->connection->fetchAssociative(
            'SELECT id, '.$field.' AS media_path FROM plugin_customcertificate WHERE c_id = :courseId AND session_id = :sessionId AND access_url_id = :accessUrlId LIMIT 1',
            ['courseId' => $courseId, 'sessionId' => $sessionId, 'accessUrlId' => $accessUrlId],
            ['courseId' => Types::INTEGER, 'sessionId' => Types::INTEGER, 'accessUrlId' => Types::INTEGER],
        );
        if (false !== $row) {
            $relativePath = (string) ($row['media_path'] ?? '');
            $this->connection->update(
                'plugin_customcertificate',
                [$field => ''],
                ['id' => (int) $row['id']],
                [$field => Types::STRING],
                ['id' => Types::INTEGER],
            );
            $this->deleteCertificateFileIfUnused($field, $relativePath, (int) $row['id']);
        }

        return $this->json(['success' => true]);
    }

    private function getImageExtension(UploadedFile $file): string
    {
        return match ((string) $file->getMimeType()) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => throw new BadRequestHttpException('Unsupported image format.'),
        };
    }

    private function deleteCertificateFileIfUnused(string $field, string $relativePath, int $currentId): void
    {
        $relativePath = ltrim($relativePath, '/');
        if ('' === $relativePath) {
            return;
        }

        $references = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM plugin_customcertificate WHERE '.$field.' = :path AND id != :id',
            ['path' => $relativePath, 'id' => $currentId],
            ['path' => Types::STRING, 'id' => Types::INTEGER],
        );
        if ($references > 0) {
            return;
        }

        $storagePath = 'customcertificate/certificates/'.$relativePath;
        if ($this->pluginsFilesystem->fileExists($storagePath)) {
            $this->pluginsFilesystem->delete($storagePath);
        }
    }

    private function assertWatermarkAvailable(): void
    {
        if (!$this->manager->isWatermarkEnabled()) {
            throw $this->createNotFoundException('Course watermark configuration is not enabled.');
        }
    }

    private function assertCourseLegalAvailable(): void
    {
        if (!$this->pluginHelper->isPluginEnabled('CourseLegal')
            || !$this->connection->createSchemaManager()->tablesExist(['session_rel_course_legal'])) {
            throw $this->createNotFoundException('Course legal agreement integration is not available.');
        }
    }

    private function assertCustomCertificateAvailable(): void
    {
        if (!$this->pluginHelper->isPluginEnabled('CustomCertificate')
            || !$this->connection->createSchemaManager()->tablesExist(['plugin_customcertificate'])) {
            throw $this->createNotFoundException('Custom certificate integration is not available.');
        }
    }

    private function assertImage(UploadedFile $file): void
    {
        if ($file->getSize() > 10 * 1024 * 1024) {
            throw new BadRequestHttpException('The image is too large.');
        }
        $mime = (string) $file->getMimeType();
        if (!\in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)
            || false === @getimagesize($file->getPathname())) {
            throw new BadRequestHttpException('Only valid JPG, PNG, GIF or WebP images are allowed.');
        }
    }

    private function getWatermarkPath(Course $course): string
    {
        $accessUrlId = (int) ($this->accessUrlHelper->getCurrent()?->getId() ?? 0);
        $directory = trim((string) $course->getDirectory(), '/');

        return $this->parameterBag->get('kernel.project_dir').'/public/courses/'.$directory.'/'.$accessUrlId.'_pdf_watermark.png';
    }

    private function sanitizeFilename(string $filename): string
    {
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '_', basename($filename));

        return '' === trim((string) $filename, '._-') ? 'agreement' : (string) $filename;
    }

    private function ensureCertificateRow(Course $course, int $sessionId): int
    {
        $courseId = (int) $course->getId();
        $accessUrlId = (int) ($this->accessUrlHelper->getCurrent()?->getId() ?? 0);
        $id = $this->connection->fetchOne(
            'SELECT id FROM plugin_customcertificate WHERE c_id = :courseId AND session_id = :sessionId AND access_url_id = :accessUrlId LIMIT 1',
            ['courseId' => $courseId, 'sessionId' => $sessionId, 'accessUrlId' => $accessUrlId],
            ['courseId' => Types::INTEGER, 'sessionId' => Types::INTEGER, 'accessUrlId' => Types::INTEGER],
        );
        if (false !== $id) {
            return (int) $id;
        }
        $this->connection->insert(
            'plugin_customcertificate',
            [
                'access_url_id' => $accessUrlId,
                'c_id' => $courseId,
                'session_id' => $sessionId,
                'content_course' => '',
                'contents_type' => 0,
                'contents' => '',
                'date_change' => 0,
                'date_start' => '1970-01-01 00:00:00',
                'date_end' => '1970-01-01 00:00:00',
                'type_date_expediction' => 0,
                'place' => '',
                'day' => '',
                'month' => '',
                'year' => '',
                'logo_left' => '',
                'logo_center' => '',
                'logo_right' => '',
                'seal' => '',
                'signature1' => '',
                'signature2' => '',
                'signature3' => '',
                'signature4' => '',
                'signature_text1' => '',
                'signature_text2' => '',
                'signature_text3' => '',
                'signature_text4' => '',
                'background' => '',
                'margin_left' => 0,
                'margin_right' => 0,
                'certificate_default' => 0,
            ],
            [
                'access_url_id' => Types::INTEGER,
                'c_id' => Types::INTEGER,
                'session_id' => Types::INTEGER,
                'contents_type' => Types::INTEGER,
                'date_change' => Types::INTEGER,
                'type_date_expediction' => Types::INTEGER,
                'margin_left' => Types::INTEGER,
                'margin_right' => Types::INTEGER,
                'certificate_default' => Types::INTEGER,
            ],
        );

        return (int) $this->connection->lastInsertId();
    }
}
