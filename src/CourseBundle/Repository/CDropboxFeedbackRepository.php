<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CDropboxFeedback;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;

/** Custom queries for Dropbox feedback */
final class CDropboxFeedbackRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CDropboxFeedback::class);
    }

    public function listByFile(int $cid, int $fileId): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.cId = :cid')->setParameter('cid', $cid)
            ->andWhere('f.fileId = :fid')->setParameter('fid', $fileId)
            ->orderBy('f.feedbackDate', 'ASC')
            ->getQuery()->getResult()
        ;
    }

    /**
     * Create a feedback row for a given file.
     */
    public function createForFile(int $cid, int $fileId, int $authorUserId, string $text): CDropboxFeedback
    {
        $em = $this->getEntityManager();
        $conn = $em->getConnection();

        $nextId = (int) $conn->fetchOne(
            'SELECT COALESCE(MAX(feedback_id), 0) + 1 FROM c_dropbox_feedback WHERE c_id = :cid AND file_id = :fid',
            ['cid' => $cid, 'fid' => $fileId]
        );

        $f = (new CDropboxFeedback())
            ->setCId($cid)
            ->setFileId($fileId)
            ->setAuthorUserId($authorUserId)
            ->setFeedback($text)
            ->setFeedbackDate(new DateTime())
            ->setFeedbackId($nextId)
        ;

        $em->persist($f);
        $em->flush();

        return $f;
    }

    /**
     * Convenience creator to avoid colliding with ResourceRepository::create(AbstractResource).
     */
    public function createFeedback(int $cid, int $fileId, int $authorUserId, string $text): CDropboxFeedback
    {
        $em = $this->getEntityManager();

        $fb = new CDropboxFeedback();
        $fb->setCId($cid);
        $fb->setFileId($fileId);
        $fb->setAuthorUserId($authorUserId);
        $fb->setFeedback($text);
        $fb->setFeedbackDate(new DateTime());
        $fb->setFeedbackId(0); // Will be aligned to iid after first flush

        // 1st flush: get autoincrement iid
        $em->persist($fb);
        $em->flush();

        // Align legacy feedback_id = iid without raw SQL or touching protected props
        $fb->setFeedbackId((int) $fb->getIid());

        // 2nd flush: persist alignment
        $em->flush();

        return $fb;
    }
}
