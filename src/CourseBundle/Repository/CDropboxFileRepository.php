<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CDropboxFile;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;

final class CDropboxFileRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CDropboxFile::class);
    }

    /**
     * Files I sent (my own uploads), filtered by category:
     * - categoryId = 0  → only root
     * - categoryId > 0  → only that folder
     */
    public function findSentByContextAndCategory(int $cid, ?int $sid, int $uid, int $categoryId): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sid = (int) ($sid ?? 0);

        $sql = <<<'SQL'
        SELECT
            f.iid               AS id,
            f.title             AS title,
            f.description       AS description,
            f.filesize          AS filesize,
            f.last_upload_date  AS lastUploadDate,
            f.cat_id            AS catId,
            COALESCE(
                GROUP_CONCAT(DISTINCT TRIM(CONCAT(u.firstname, ' ', u.lastname)) SEPARATOR ', '),
                ''
            )                   AS recipients
        FROM c_dropbox_file f
        LEFT JOIN c_dropbox_person p
               ON p.c_id = f.c_id
              AND p.file_id = f.iid
              AND p.user_id <> f.uploader_id
        LEFT JOIN `user` u
               ON u.id = p.user_id
        WHERE f.c_id = :cid
          AND f.session_id = :sid
          AND f.uploader_id = :uid
          AND f.cat_id = :categoryId
        GROUP BY f.iid
        ORDER BY f.last_upload_date DESC, f.iid DESC
    SQL;

        return $conn->fetchAllAssociative($sql, [
            'cid' => $cid,
            'sid' => $sid,
            'uid' => $uid,
            'categoryId' => $categoryId,
        ]);
    }

    /**
     * Files I received (visibility rows), filtered by my category in c_dropbox_person:
     * - categoryId = 0  → only root
     * - categoryId > 0  → only that folder
     */
    public function findReceivedByContextAndCategory(int $cid, ?int $sid, int $uid, int $categoryId): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sid = (int) ($sid ?? 0);

        $sql = <<<'SQL'
        SELECT
            f.iid               AS id,
            f.title             AS title,
            f.description       AS description,
            f.filesize          AS filesize,
            f.last_upload_date  AS lastUploadDate,
            f.cat_id            AS catId,
            TRIM(CONCAT(u.firstname, ' ', u.lastname)) AS uploader
        FROM c_dropbox_person p
        INNER JOIN c_dropbox_file f
                ON f.iid = p.file_id
               AND f.c_id = p.c_id
        LEFT JOIN `user` u
               ON u.id = f.uploader_id
        WHERE p.c_id = :cid
          AND p.user_id = :uid
          AND f.session_id = :sid
          AND f.cat_id = :categoryId
        ORDER BY f.last_upload_date DESC, f.iid DESC
    SQL;

        return $conn->fetchAllAssociative($sql, [
            'cid' => $cid,
            'uid' => $uid,
            'sid' => $sid,
            'categoryId' => $categoryId,
        ]);
    }

    /**
     * Move a file to a target category for the given area and user, without creating duplicates.
     *
     * - area "sent": updates c_dropbox_file.cat_id (uploader's own organization).
     * - area "received": updates c_dropbox_person.cat_id (receiver's own organization).
     *
     * @return int number of affected rows
     */
    public function moveFileForArea(
        int $fileId,
        int $cid,
        ?int $sid,
        int $uid,
        int $targetCatId,
        string $area
    ): int {
        $conn = $this->getEntityManager()->getConnection();
        $targetCatId = (int) $targetCatId;

        if ('sent' === $area) {
            // Move inside sender's space: update file's category if current user is the uploader.
            $sql = <<<'SQL'
        UPDATE c_dropbox_file
           SET cat_id = :targetCatId
         WHERE iid = :fileId
           AND c_id = :cid
           AND uploader_id = :uid
        SQL;

            return $conn->executeStatement($sql, [
                'targetCatId' => $targetCatId,
                'fileId' => $fileId,
                'cid' => $cid,
                'uid' => $uid,
            ]);
        }

        // Move inside receiver's space: update the receiver's own category in c_dropbox_person.
        $sql = <<<'SQL'
        UPDATE c_dropbox_person
           SET cat_id = :targetCatId
         WHERE c_id = :cid
           AND file_id = :fileId
           AND user_id = :uid
    SQL;

        return $conn->executeStatement($sql, [
            'targetCatId' => $targetCatId,
            'cid' => $cid,
            'fileId' => $fileId,
            'uid' => $uid,
        ]);
    }

    public function deleteVisibility(array $fileIds, int $cid, ?int $sid, int $uid, string $area): int
    {
        if (!$fileIds) {
            return 0;
        }

        $conn = $this->getEntityManager()->getConnection();
        $placeholders = implode(',', array_fill(0, \count($fileIds), '?'));

        if ('sent' === $area) {
            $sql = "DELETE FROM c_dropbox_file
                    WHERE iid IN ($placeholders) AND c_id = ? AND session_id = ? AND uploader_id = ?";
            $params = array_merge($fileIds, [$cid, $sid ?? 0, $uid]);

            return $conn->executeStatement($sql, $params);
        }

        $sql = "DELETE FROM c_dropbox_person
                WHERE file_id IN ($placeholders) AND c_id = ? AND user_id = ?";
        $params = array_merge($fileIds, [$cid, $uid]);

        return $conn->executeStatement($sql, $params);
    }

    public function createUploadedFile(
        int $cid,
        ?int $sid,
        int $uploaderId,
        string $storedFilename,
        int $filesize,
        string $originalTitle,
        ?string $description = null
    ): CDropboxFile {
        $now = new DateTime();

        $f = new CDropboxFile();
        $f->setCId($cid);
        $f->setSessionId($sid ?? 0);
        $f->setUploaderId($uploaderId);
        $f->setFilename($storedFilename);
        $f->setFilesize($filesize);
        $f->setTitle($originalTitle);
        $f->setDescription($description ?? '');
        $f->setAuthor(null);
        $f->setUploadDate($now);
        $f->setLastUploadDate($now);
        $f->setCatId(0);

        $em = $this->getEntityManager();
        $em->persist($f);
        $em->flush();

        return $f;
    }
}
