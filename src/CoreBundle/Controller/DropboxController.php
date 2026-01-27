<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Helpers\ResourceFileHelper;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CourseBundle\Entity\CDropboxCategory;
use Chamilo\CourseBundle\Entity\CDropboxFeedback;
use Chamilo\CourseBundle\Repository\CDropboxCategoryRepository;
use Chamilo\CourseBundle\Repository\CDropboxFeedbackRepository;
use Chamilo\CourseBundle\Repository\CDropboxFileRepository;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{BinaryFileResponse,
    File\UploadedFile,
    JsonResponse,
    Request,
    Response,
    ResponseHeaderBag,
    StreamedResponse};
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Throwable;
use ZipArchive;

use const DATE_ATOM;
use const PATHINFO_EXTENSION;
use const PATHINFO_FILENAME;

#[IsGranted('ROLE_USER')]
#[Route('/dropbox')]
class DropboxController extends AbstractController
{
    private array $userNameCache = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CDropboxCategoryRepository $categoryRepo,
        private readonly CDropboxFileRepository $fileRepo,
        private readonly CDropboxFeedbackRepository $feedbackRepo,
        private readonly SluggerInterface $slugger,
        private readonly ResourceNodeRepository $resourceNodeRepository
    ) {}

    private function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;

        return \sprintf('%.1f %s', $bytes / (1024 ** $i), $units[$i]);
    }

    private function ago(DateTimeImmutable $dt): string
    {
        $diff = (new DateTimeImmutable())->getTimestamp() - $dt->getTimestamp();
        if ($diff < 60) {
            return 'just now';
        }
        if ($diff < 3600) {
            return floor($diff / 60).' min ago';
        }
        if ($diff < 86400) {
            return floor($diff / 3600).' h ago';
        }

        return floor($diff / 86400).' d ago';
    }

    /**
     * Pull Chamilo context (cid/sid/gid) from query string.
     */
    private function context(Request $r): array
    {
        $cid = (int) $r->query->get('cid', 0);
        $sid = $r->query->get('sid') ? (int) $r->query->get('sid') : null;
        $gid = $r->query->get('gid') ? (int) $r->query->get('gid') : null;

        return [$cid, $sid, $gid];
    }

    #[Route('/recipients', name: 'dropbox_recipients', methods: ['GET'])]
    public function recipients(Request $r): JsonResponse
    {
        [$cid, $sid, $gid] = $this->context($r);
        $me = (int) $this->getUser()?->getId();

        if ($cid <= 0) {
            $ref = (string) $r->headers->get('referer', '');
            if ($ref && preg_match('#/resources/dropbox/(\d+)/#', $ref, $m)) {
                $cid = (int) $m[1];
            }
        }
        if ($cid <= 0) {
            return $this->json(['message' => 'Missing course id (cid)'], 400);
        }

        $conn = $this->em->getConnection();
        $userRows = [];

        $sqlCourse = <<<'SQL'
          SELECT DISTINCT u.id, u.firstname, u.lastname
          FROM course_rel_user cru
          INNER JOIN user u ON u.id = cru.user_id
          WHERE cru.c_id = :cid
        SQL;
        $userRows = $conn->fetchAllAssociative($sqlCourse, ['cid' => $cid]);

        if (!empty($sid)) {
            $sqlSess = <<<'SQL'
              SELECT DISTINCT u.id, u.firstname, u.lastname
              FROM session_rel_course_rel_user scru
              INNER JOIN user u ON u.id = scru.user_id
              WHERE scru.c_id = :cid AND scru.session_id = :sid
            SQL;
            $more = $conn->fetchAllAssociative($sqlSess, ['cid' => $cid, 'sid' => (int) $sid]);

            $seen = [];
            foreach ($userRows as $row) {
                $seen[(int) $row['id']] = true;
            }
            foreach ($more as $row) {
                $uid = (int) $row['id'];
                if (!isset($seen[$uid])) {
                    $userRows[] = $row;
                    $seen[$uid] = true;
                }
            }
        }

        $options = [];
        foreach ($userRows as $u) {
            $uid = (int) $u['id'];
            if ($uid === $me) {
                continue;
            }
            $label = trim(($u['firstname'] ?? '').' '.($u['lastname'] ?? '')) ?: ('User #'.$uid);
            $options[] = ['value' => 'user_'.$uid, 'label' => $label];
        }

        array_unshift($options, ['value' => 'self', 'label' => '— Just upload —']);

        return $this->json($options);
    }

    #[Route('/categories', name: 'dropbox_categories_list', methods: ['GET'])]
    public function listCategories(Request $r): JsonResponse
    {
        [$cid, $sid] = $this->context($r);
        $uid = (int) $this->getUser()?->getId();
        $area = (string) $r->query->get('area', 'sent');

        $cats = $this->categoryRepo->findByContextAndArea($cid, $sid, $uid, $area);

        $rows = array_map(fn (CDropboxCategory $c) => [
            'id' => $c->getCatId(),
            'title' => $c->getTitle(),
        ], $cats);

        array_unshift($rows, ['id' => 0, 'title' => 'Root']);

        return $this->json($rows);
    }

    #[Route('/categories', name: 'dropbox_categories_create', methods: ['POST'])]
    public function createCategory(Request $r): JsonResponse
    {
        [$cid, $sid] = $this->context($r);
        $uid = (int) $this->getUser()?->getId();
        $payload = json_decode($r->getContent(), true) ?: [];
        $title = trim((string) ($payload['title'] ?? ''));
        $area = (string) ($payload['area'] ?? 'sent');

        if ('' === $title || !\in_array($area, ['sent', 'received'], true)) {
            return $this->json(['message' => 'Invalid payload'], 400);
        }

        $cat = $this->categoryRepo->createForUser($cid, $sid, $uid, $title, $area);

        return $this->json(['id' => (int) $cat->getCatId(), 'title' => $cat->getTitle()], 201);
    }

    #[Route('/files', name: 'dropbox_files_list', methods: ['GET'])]
    public function listFiles(Request $r): JsonResponse
    {
        [$cid, $sid] = $this->context($r);
        $uid = (int) $this->getUser()?->getId();
        $area = (string) $r->query->get('area', 'sent');
        $categoryId = (int) $r->query->get('categoryId', 0);

        if ('sent' === $area) {
            $files = $this->fileRepo->findSentByContextAndCategory($cid, $sid, $uid, $categoryId);

            $out = array_map(function (array $row) {
                $dt = new DateTimeImmutable($row['lastUploadDate']);

                return [
                    'id' => (int) $row['id'],
                    'title' => $row['title'],
                    'description' => $row['description'],
                    'size' => (int) $row['filesize'],
                    'sizeHuman' => $this->humanSize((int) $row['filesize']),
                    'lastUploadDate' => $dt->format(DATE_ATOM),
                    'lastUploadAgo' => $this->ago($dt),
                    'recipients' => $row['recipients'],
                    'categoryId' => (int) $row['catId'],
                ];
            }, $files);

            return $this->json($out);
        }

        $files = $this->fileRepo->findReceivedByContextAndCategory($cid, $sid, $uid, $categoryId);

        $out = array_map(function (array $row) {
            $dt = new DateTimeImmutable($row['lastUploadDate']);

            return [
                'id' => (int) $row['id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'size' => (int) $row['filesize'],
                'sizeHuman' => $this->humanSize((int) $row['filesize']),
                'lastUploadDate' => $dt->format(DATE_ATOM),
                'lastUploadAgo' => $this->ago($dt),
                'uploader' => $row['uploader'],
                'categoryId' => (int) $row['catId'],
            ];
        }, $files);

        return $this->json($out);
    }

    #[Route('/files/{id<\d+>}/move', name: 'dropbox_file_move', methods: ['PATCH'])]
    public function moveFile(int $id, Request $r): JsonResponse
    {
        [$cid, $sid] = $this->context($r);
        $uid = (int) $this->getUser()?->getId();
        $payload = json_decode($r->getContent(), true) ?: [];
        $targetCatId = (int) ($payload['targetCatId'] ?? 0);
        $area = (string) ($payload['area'] ?? 'sent');

        if (!\in_array($area, ['sent', 'received'], true)) {
            return $this->json(['message' => 'Invalid "area"'], 400);
        }

        $affected = $this->fileRepo->moveFileForArea($id, $cid, $sid, $uid, $targetCatId, $area);

        return $this->json(['moved' => $affected > 0]);
    }

    #[Route('/files', name: 'dropbox_files_delete', methods: ['DELETE'])]
    public function deleteFiles(Request $r): JsonResponse
    {
        [$cid, $sid] = $this->context($r);
        $uid = (int) $this->getUser()?->getId();
        $payload = json_decode($r->getContent(), true) ?: [];
        $ids = array_map('intval', $payload['ids'] ?? []);
        $area = (string) ($payload['area'] ?? 'sent');

        if (!$ids) {
            return $this->json(['deleted' => 0]);
        }

        $deleted = $this->fileRepo->deleteVisibility($ids, $cid, $sid, $uid, $area);

        return $this->json(['deleted' => $deleted]);
    }

    #[Route('/files/{id<\d+>}/feedback', name: 'dropbox_feedback_list', methods: ['GET'])]
    public function listFeedback(int $id, Request $r): JsonResponse
    {
        [$cid] = $this->context($r);
        $rows = $this->feedbackRepo->listByFile($cid, $id);

        return $this->json(array_map(function (CDropboxFeedback $f) {
            return [
                'id' => $f->getFeedbackId(),
                'authorId' => $f->getAuthorUserId(),
                'authorName' => $this->userFullName($f->getAuthorUserId()),
                'text' => $f->getFeedback(),
                'date' => $f->getFeedbackDate()->format(DATE_ATOM),
            ];
        }, $rows));
    }

    #[Route('/files/{id<\d+>}/feedback', name: 'dropbox_feedback_create', methods: ['POST'])]
    public function createFeedback(int $id, Request $r): JsonResponse
    {
        [$cid] = $this->context($r);
        $uid = (int) $this->getUser()?->getId();
        $payload = json_decode($r->getContent(), true) ?: [];
        $text = trim((string) ($payload['text'] ?? ''));

        if ('' === $text) {
            return $this->json(['message' => 'Empty feedback'], 400);
        }

        $this->feedbackRepo->createForFile($cid, $id, $uid, $text);

        return $this->json(['ok' => true], 201);
    }

    #[Route('/categories/{id<\d+>}', name: 'dropbox_categories_rename', methods: ['PATCH'])]
    public function renameCategory(int $id, Request $r): JsonResponse
    {
        [$cid, $sid] = $this->context($r);
        $uid = (int) $this->getUser()?->getId();
        $payload = json_decode($r->getContent(), true) ?: [];
        $title = trim((string) ($payload['title'] ?? ''));
        $area = (string) ($payload['area'] ?? 'sent');

        if ('' === $title || !\in_array($area, ['sent', 'received'], true)) {
            return $this->json(['message' => 'Invalid payload'], 400);
        }

        $cat = $this->categoryRepo->findOneBy([
            'cId' => $cid,
            'sessionId' => (int) ($sid ?? 0),
            'userId' => $uid,
            'catId' => $id,
            'sent' => 'sent' === $area,
            'received' => 'received' === $area,
        ]);

        if (!$cat) {
            return $this->json(['message' => 'Category not found'], 404);
        }

        $cat->setTitle($title);
        $this->em->persist($cat);
        $this->em->flush();

        return $this->json(['ok' => true]);
    }

    #[Route('/categories/{id<\d+>}', name: 'dropbox_categories_delete', methods: ['DELETE'])]
    public function deleteCategory(int $id, Request $r): JsonResponse
    {
        [$cid, $sid] = $this->context($r);
        $uid = (int) $this->getUser()?->getId();
        $area = (string) $r->query->get('area', 'sent');

        if (!\in_array($area, ['sent', 'received'], true)) {
            return $this->json(['message' => 'Invalid area'], 400);
        }
        if (0 === $id) {
            return $this->json(['message' => 'Cannot delete root category'], 400);
        }

        $conn = $this->em->getConnection();
        $sid = (int) ($sid ?? 0);

        if ('sent' === $area) {
            $ids = $conn->fetchFirstColumn(
                <<<'SQL'
            SELECT f.iid
            FROM c_dropbox_file f
            WHERE f.c_id = :cid
              AND f.session_id = :sid
              AND f.uploader_id = :uid
              AND f.cat_id = :cat
            SQL,
                ['cid' => $cid, 'sid' => $sid, 'uid' => $uid, 'cat' => $id]
            );

            $deletedFiles = 0;
            if ($ids) {
                $deletedFiles = $this->fileRepo->deleteVisibility(array_map('intval', $ids), $cid, $sid, $uid, 'sent');
            }

            $cat = $this->categoryRepo->findOneBy([
                'cId' => $cid,
                'sessionId' => $sid,
                'userId' => $uid,
                'catId' => $id,
                'sent' => true,
                'received' => false,
            ]);
            if ($cat) {
                $this->em->remove($cat);
                $this->em->flush();
            }

            return $this->json(['ok' => true, 'deletedFiles' => (int) $deletedFiles]);
        }

        $ids = $conn->fetchFirstColumn(
            <<<'SQL'
        SELECT p.file_id
        FROM c_dropbox_person p
        WHERE p.c_id = :cid
          AND p.user_id = :uid
          AND p.cat_id = :cat
        SQL,
            ['cid' => $cid, 'uid' => $uid, 'cat' => $id]
        );

        $removedVisibilities = 0;
        if ($ids) {
            $removedVisibilities = $this->fileRepo->deleteVisibility(array_map('intval', $ids), $cid, $sid, $uid, 'received');
        }

        $cat = $this->categoryRepo->findOneBy([
            'cId' => $cid,
            'sessionId' => $sid,
            'userId' => $uid,
            'catId' => $id,
            'sent' => false,
            'received' => true,
        ]);
        if ($cat) {
            $this->em->remove($cat);
            $this->em->flush();
        }

        return $this->json(['ok' => true, 'removedVisibilities' => (int) $removedVisibilities]);
    }

    #[Route('/files/{id<\d+>}/download', name: 'dropbox_file_download', methods: ['GET'])]
    public function download(int $id, Request $r, ResourceFileHelper $resourceFileHelper): Response
    {
        [$cid] = $this->context($r);

        $file = $this->fileRepo->find($id);
        if (!$file || (int) $file->getCId() !== $cid) {
            throw $this->createNotFoundException('File not found');
        }

        // Resolve the resource file attached to this dropbox entry
        $resourceNode = $file->getResourceNode();
        $resourceFile = $resourceFileHelper->resolveResourceFileByAccessUrl($resourceNode);

        if (!$resourceFile) {
            throw $this->createNotFoundException('Resource file not found');
        }

        // Display name: prefer the dropbox visible title; fallback to original name
        $downloadName = trim($file->getTitle() ?: $resourceFile->getOriginalName() ?: 'file.bin');

        // Guess mime
        $mime = $resourceFile->getMimeType() ?: 'application/octet-stream';
        if ('application/octet-stream' === $mime && class_exists(MimeTypes::class)) {
            $types = new MimeTypes();
            $guess = $types->guessMimeType($downloadName);
            if ($guess) {
                $mime = $guess;
            }
        }

        // Stream from ResourceNode FS (no tmp-path fallback)
        $stream = $this->resourceNodeRepository->getResourceNodeFileStream($resourceNode, $resourceFile);
        if (!\is_resource($stream)) {
            throw $this->createNotFoundException('Resource stream not available');
        }

        $size = (int) $resourceFile->getSize();

        $response = new StreamedResponse(function () use ($stream): void {
            // Stream file in chunks
            while (!feof($stream)) {
                $buffer = fread($stream, 8192);
                if (false === $buffer) {
                    break;
                }
                echo $buffer;
                @ob_flush();
                flush();
            }
            fclose($stream);
        });

        $downloadName = str_replace(["\r", "\n", "\0"], '', $downloadName);
        $downloadName = trim($downloadName);

        $fallbackName = $this->buildAsciiFilenameFallback($downloadName);

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $downloadName,
            $fallbackName
        );

        $response->headers->set('Content-Type', $mime);
        if ($size > 0) {
            $response->headers->set('Content-Length', (string) $size);
            $response->headers->set('Accept-Ranges', 'none'); // simple download (no Range)
        }
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    #[Route('/files/{id<\d+>}', name: 'dropbox_file_get', methods: ['GET'])]
    public function getFile(int $id, Request $r): JsonResponse
    {
        [$cid] = $this->context($r);
        $row = $this->fileRepo->find($id);
        if (!$row || (int) $row->getCId() !== $cid) {
            return $this->json(['message' => 'File not found'], 404);
        }

        return $this->json([
            'id' => $row->getIid(),
            'title' => $row->getTitle(),
            'description' => $row->getDescription(),
            'categoryId' => $row->getCatId(),
        ]);
    }

    #[Route('/files/{id<\d+>}/update', name: 'dropbox_file_update', methods: ['POST'])]
    public function updateFile(int $id, Request $r): JsonResponse
    {
        [$cid, $sid] = $this->context($r);
        $uid = (int) $this->getUser()?->getId();

        $fileRow = $this->fileRepo->find($id);
        if (!$fileRow || (int) $fileRow->getCId() !== $cid || (int) $fileRow->getUploaderId() !== $uid) {
            return $this->json(['message' => 'File not found or not allowed'], 404);
        }

        /** @var UploadedFile|null $new */
        $new = $r->files->get('newFile');
        $newCat = $r->request->get('categoryId');
        $newCatId = (null !== $newCat) ? (int) $newCat : $fileRow->getCatId();

        $shouldRename = (bool) $r->request->get('renameTitle');
        $explicitNewTitle = trim((string) $r->request->get('newTitle', ''));

        if ($new instanceof UploadedFile) {
            $origClientName = $new->getClientOriginalName() ?: 'upload.bin';
            $origBase = pathinfo($origClientName, PATHINFO_FILENAME);
            $origExt = pathinfo($origClientName, PATHINFO_EXTENSION) ?: 'bin';

            // Safe physical name
            $safeBase = $this->slugger->slug($origBase)->lower();
            $safeName = \sprintf('%s-%s.%s', $safeBase, bin2hex(random_bytes(4)), $origExt);

            $coursePath = \sprintf('%s/course_%d/dropbox', sys_get_temp_dir(), $cid);
            @mkdir($coursePath, 0775, true);
            $new->move($coursePath, $safeName);

            $fileRow->setFilename($safeName);
            $fileRow->setFilesize((int) filesize($coursePath.'/'.$safeName));
            $fileRow->setLastUploadDate(new DateTime());

            // Rename title WITH extension when requested
            if ($shouldRename) {
                $finalTitle = '' !== $explicitNewTitle ? $explicitNewTitle : $origClientName;
                $finalTitle = rtrim($finalTitle);
                $finalTitle = rtrim($finalTitle, '. ');
                if ('' === $finalTitle) {
                    $finalTitle = $origClientName;
                }
                // Max 255 chars
                if (mb_strlen($finalTitle) > 255) {
                    $finalTitle = mb_substr($finalTitle, 0, 255);
                }
                $fileRow->setTitle($finalTitle);
            }
        }

        if ($newCatId !== $fileRow->getCatId()) {
            $fileRow->setCatId($newCatId);
        }

        $this->em->persist($fileRow);
        $this->em->flush();

        return $this->json([
            'ok' => true,
            'id' => (int) $fileRow->getIid(),
            'categoryId' => (int) $fileRow->getCatId(),
            'title' => (string) $fileRow->getTitle(),
        ]);
    }

    #[Route('/categories/{id<\d+>}/zip', name: 'dropbox_category_zip', methods: ['GET'])]
    public function downloadCategoryZip(int $id, Request $r): Response
    {
        [$cid, $sid] = $this->context($r);
        $uid = (int) $this->getUser()?->getId();
        $area = (string) $r->query->get('area', 'sent');
        $catId = (int) $id;

        if (!\in_array($area, ['sent', 'received'], true)) {
            return $this->json(['message' => 'Invalid area'], 400);
        }

        // Fetch candidate rows
        $conn = $this->em->getConnection();
        $sid = (int) ($sid ?? 0);

        if ('sent' === $area) {
            $sql = <<<'SQL'
            SELECT f.iid, f.title, f.filename, f.filesize
            FROM c_dropbox_file f
            WHERE f.c_id = :cid
              AND f.session_id = :sid
              AND f.uploader_id = :uid
              AND f.cat_id = :cat
            ORDER BY f.last_upload_date DESC, f.iid DESC
        SQL;
            $rows = $conn->fetchAllAssociative($sql, [
                'cid' => $cid, 'sid' => $sid, 'uid' => $uid, 'cat' => $catId,
            ]);
            $zipLabel = 'sent';
        } else {
            $sql = <<<'SQL'
            SELECT f.iid, f.title, f.filename, f.filesize
            FROM c_dropbox_person p
            INNER JOIN c_dropbox_file f
              ON f.iid = p.file_id
             AND f.c_id = p.c_id
            WHERE p.c_id = :cid
              AND p.user_id = :uid
              AND f.session_id = :sid
              AND f.cat_id = :cat
            ORDER BY f.last_upload_date DESC, f.iid DESC
        SQL;
            $rows = $conn->fetchAllAssociative($sql, [
                'cid' => $cid, 'uid' => $uid, 'sid' => $sid, 'cat' => $catId,
            ]);
            $zipLabel = 'received';
        }

        if (!$rows) {
            return $this->json(['message' => 'No files in this category'], 404);
        }

        // Prepare ZIP
        $coursePath = \sprintf('%s/course_%d/dropbox', sys_get_temp_dir(), $cid);
        $tmpZipPath = tempnam(sys_get_temp_dir(), 'dbxzip_');
        if (false === $tmpZipPath) {
            return $this->json(['message' => 'Unable to create temp file'], 500);
        }
        $finalZipPath = $tmpZipPath.'.zip';
        @rename($tmpZipPath, $finalZipPath);

        $zip = new ZipArchive();
        if (true !== $zip->open($finalZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            @unlink($finalZipPath);

            return $this->json(['message' => 'Unable to open zip archive'], 500);
        }

        $added = 0;

        // Add files – try physical temp path, then ResourceNode FS
        foreach ($rows as $row) {
            $safePhysical = (string) ($row['filename'] ?? '');
            $downloadName = trim((string) ($row['title'] ?? '')) ?: ($safePhysical ?: 'file.bin');
            $downloadName = str_replace(['\\', '/', "\0"], '_', $downloadName);

            // Ensure unique entry name
            $entryName = $downloadName;
            $i = 1;
            while (false !== $zip->locateName($entryName, ZipArchive::FL_NOCASE | ZipArchive::FL_NODIR)) {
                $pi = pathinfo($downloadName);
                $base = $pi['filename'] ?? $downloadName;
                $ext = isset($pi['extension']) && '' !== $pi['extension'] ? ('.'.$pi['extension']) : '';
                $entryName = $base.' ('.(++$i).')'.$ext;
            }

            $addedThis = false;

            // (a) Try physical temp path
            if ('' !== $safePhysical) {
                $fullPath = $coursePath.'/'.$safePhysical;
                if (is_file($fullPath)) {
                    $zip->addFile($fullPath, $entryName);
                    $added++;
                    $addedThis = true;
                }
            }

            // (b) Fallback: ResourceNode filesystem
            if (!$addedThis) {
                // Load entity to reach ResourceNode
                $fileEntity = $this->fileRepo->find((int) $row['iid']);
                $resourceNode = $fileEntity?->getResourceNode();
                $resourceFile = $resourceNode?->getFirstResourceFile();
                if ($resourceFile) {
                    try {
                        $path = $this->resourceNodeRepository->getFilename($resourceFile);
                        $content = $this->resourceNodeRepository->getFileSystem()->read($path);
                        if (false !== $content && null !== $content) {
                            $zip->addFromString($entryName, $content);
                            $added++;
                            $addedThis = true;
                        }
                    } catch (Throwable $e) {
                        // ignore and continue
                    }
                }
            }
        }

        $zip->close();

        if (0 === $added) {
            @unlink($finalZipPath);

            return $this->json(['message' => 'No files found to include'], 404);
        }

        // Build download name
        $catTitle = 'Root';
        if (0 !== $catId) {
            $cat = $this->categoryRepo->findOneBy([
                'cId' => $cid,
                'sessionId' => $sid,
                'userId' => $uid,
                'catId' => $catId,
                'sent' => 'sent' === $area,
                'received' => 'received' === $area,
            ]);
            if ($cat) {
                $catTitle = $cat->getTitle();
            }
        }
        $slug = $this->slugger->slug($catTitle ?: 'category')->lower();
        $downloadZipName = \sprintf('dropbox-%s-%s-%s.zip', $zipLabel, $slug, date('Ymd_His'));

        $resp = new BinaryFileResponse($finalZipPath);
        $resp->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $downloadZipName
        );
        $resp->deleteFileAfterSend(true);

        return $resp;
    }

    private function userFullName(int $userId): string
    {
        if ($userId <= 0) {
            return 'Unknown user';
        }
        if (isset($this->userNameCache[$userId])) {
            return $this->userNameCache[$userId];
        }
        $conn = $this->em->getConnection();
        $row = $conn->fetchAssociative('SELECT firstname, lastname FROM user WHERE id = :id', ['id' => $userId]);
        $name = trim(($row['firstname'] ?? '').' '.($row['lastname'] ?? '')) ?: ('User #'.$userId);

        return $this->userNameCache[$userId] = $name;
    }

    private function buildAsciiFilenameFallback(string $filename): string
    {
        // Prevent header injection / invalid control chars
        $filename = str_replace(["\r", "\n", "\0"], '', $filename);
        $filename = trim($filename);

        $ext = (string) pathinfo($filename, PATHINFO_EXTENSION);
        $base = (string) pathinfo($filename, PATHINFO_FILENAME);

        // Slugify base name (should become ASCII with the default Symfony slugger)
        $baseAscii = (string) $this->slugger->slug($base, '_');
        $baseAscii = strtolower($baseAscii);

        // Force strict ASCII-only fallback
        $baseAscii = preg_replace('/[^A-Za-z0-9._-]+/', '_', $baseAscii) ?? '';
        $baseAscii = preg_replace('/_+/', '_', $baseAscii) ?? '';
        $baseAscii = trim($baseAscii, '._-');

        if ('' === $baseAscii) {
            $baseAscii = 'download';
        }

        if ('' !== $ext) {
            $ext = strtolower($ext);
            $ext = preg_replace('/[^A-Za-z0-9]+/', '', $ext) ?? '';
            if ('' !== $ext) {
                return $baseAscii.'.'.$ext;
            }
        }

        return $baseAscii;
    }
}
