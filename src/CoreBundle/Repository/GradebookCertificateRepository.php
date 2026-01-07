<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\PersonalFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Chamilo\CoreBundle\Entity\User;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class GradebookCertificateRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GradebookCertificate::class);
    }

    /**
     * Fetch a certificate by (user, category). If $catId is 0 or null, searches the "general" certificate.
     */
    public function getCertificateByUserId(?int $catId, int $userId, bool $asArray = false)
    {
        $qb = $this->createQueryBuilder('gc')
            ->where('gc.user = :userId')
            ->setParameter('userId', $userId)
            ->setMaxResults(1)
        ;

        if (0 === $catId) {
            $catId = null;
        }

        if (null === $catId) {
            $qb->andWhere('gc.category IS NULL');
        } else {
            $qb->andWhere('gc.category = :catId')
                ->setParameter('catId', $catId)
            ;
        }

        $qb->orderBy('gc.id', 'ASC');
        $query = $qb->getQuery();

        try {
            return $asArray
                ? $query->getOneOrNullResult(AbstractQuery::HYDRATE_ARRAY)
                : $query->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    /**
     * Backward-compatible metadata update.
     * If you adopt the Resource flow, you may pass an empty $fileName; it will still update score/timestamps.
     */
    public function registerUserInfoAboutCertificate(int $catId, int $userId, float $scoreCertificate, string $fileName = ''): void
    {
        $fileName = ltrim($fileName, '/');
        $existingCertificate = $this->getCertificateByUserId(0 === $catId ? null : $catId, $userId);

        if (!$existingCertificate) {
            $certificate = new GradebookCertificate();

            $category = 0 === $catId ? null : $this->_em->getRepository(GradebookCategory::class)->find($catId);
            $user = $this->_em->getRepository(User::class)->find($userId);

            if ($category) {
                $certificate->setCategory($category);
            }
            $certificate->setUser($user);
            $certificate->setPathCertificate($fileName ?: null);
            $certificate->setScoreCertificate($scoreCertificate);
            $certificate->setCreatedAt(new DateTime());

            $this->_em->persist($certificate);
            $this->_em->flush();

            return;
        }

        if ($fileName) {
            $existingCertificate->setPathCertificate($fileName);
        }
        $existingCertificate->setScoreCertificate($scoreCertificate);
        $this->_em->flush();
    }

    /**
     * Helper: resolve the ResourceType 'files' from the Tool 'files' (personal space).
     *
     * @throws RuntimeException if not found
     */
    private function getPersonalFilesResourceType(): ResourceType
    {
        $em = $this->getEntityManager();

        $qb = $em->createQueryBuilder()
            ->select('rt')
            ->from(ResourceType::class, 'rt')
            ->join('rt.tool', 't')
            ->where('rt.title = :rtTitle')
            ->andWhere('t.title = :toolTitle')
            ->setParameter('rtTitle', 'files')
            ->setParameter('toolTitle', 'files')
            ->setMaxResults(1)
        ;

        $rt = $qb->getQuery()->getOneOrNullResult();
        if ($rt instanceof ResourceType) {
            return $rt;
        }

        $rt = $em->getRepository(ResourceType::class)->findOneBy(['title' => 'files']);
        if (!$rt) {
            throw new RuntimeException("ResourceType 'files' not found.");
        }

        return $rt;
    }

    /**
     * Create or update a certificate as a Resource using ResourceType 'files' (personal files tool).
     * We avoid calling parent::createNodeForResource() to not depend on $this->slugify.
     */
    public function upsertCertificateResource(
        int $catId,
        int $userId,
        float $scoreCertificate,
        string $htmlContent,
        ?string $pdfBinary = null
    ): GradebookCertificate {
        $em = $this->getEntityManager();

        /** @var GradebookCertificate|null $cert */
        $cert = $this->getCertificateByUserId(0 === $catId ? null : $catId, $userId);

        /** @var User|null $user */
        $user = $em->getRepository(User::class)->find($userId);
        if (!$user) {
            throw new InvalidArgumentException("User {$userId} not found.");
        }

        /** @var GradebookCategory|null $category */
        $category = 0 === $catId ? null : $em->getRepository(GradebookCategory::class)->find($catId);

        if (!$cert) {
            $cert = new GradebookCertificate();
            if ($category) {
                $cert->setCategory($category);
            }
            $cert->setUser($user);
            $cert->setScoreCertificate($scoreCertificate);
            $cert->setCreatedAt(new DateTime());
            $em->persist($cert);
            $em->flush();
        } else {
            $cert->setScoreCertificate($scoreCertificate);
        }

        // Deterministic filename for legacy parity (used historically to build URLs)
        $logicalFileName = ltrim(hash('sha256', $userId.($catId ?: 0)).'.html', '/');

        // Ensure resource node exists with ResourceType 'files', under the user's node
        if (!$cert->hasResourceNode()) {
            $filesRt = $this->getPersonalFilesResourceType();
            $parentNode = $user->getResourceNode();
            $this->createNodeForCertificateWithoutSlugify($cert, $user, $parentNode, $filesRt, $logicalFileName);

            // Link to the user (so it appears in their resources)
            $cert->addUserLink($user);
        } else {
            // Keep the resource title coherent (not mandatory but nice to have)
            $node = $cert->getResourceNode();
            $node->setTitle($logicalFileName);
            $em->persist($node);
        }

        // Update or create the HTML ResourceFile
        $updated = $this->updateResourceFileContent($cert, $htmlContent);
        if (!$updated) {
            $this->addFileFromString($cert, $logicalFileName, 'text/html', $htmlContent, true);
        }

        // Optional PDF attachment
        if (null !== $pdfBinary) {
            $pdfName = preg_replace('/\.html$/i', '.pdf', $logicalFileName) ?: ($logicalFileName.'.pdf');
            $this->addFileFromString($cert, $pdfName, 'application/pdf', $pdfBinary, true);
        }

        // Legacy pointer (not strictly required for the Resource flow)
        $cert->setPathCertificate($logicalFileName);

        $em->flush();

        return $cert;
    }

    /**
     * Create a ResourceNode avoiding the slugify service.
     * Generates a basic slug locally and wires parent/creator/type relationships.
     */
    private function createNodeForCertificateWithoutSlugify(
        GradebookCertificate $resource,
        User $creator,
        ResourceNode $parentNode,
        ResourceType $resourceType,
        string $titleForNode
    ): void {
        $em = $this->getEntityManager();

        $slug = $this->basicSlug($titleForNode);

        $node = new ResourceNode();
        $node
            ->setTitle($titleForNode)
            ->setSlug($slug)
            ->setResourceType($resourceType)
            ->setCreator($creator)
        ;

        // Parent-child relationship
        $parentNode?->addChild($node);

        // Wire resource <-> node
        $resource->setResourceNode($node);

        // Persist
        $em->persist($node);
        $em->persist($resource);
    }

    /**
     * Very small local slugger (ASCII-only fallback).
     */
    private function basicSlug(string $text): string
    {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $text = preg_replace('~[^\pL\d]+~u', '-', $text ?? '');
        $text = trim((string) $text, '-');
        $text = preg_replace('~[^-a-z0-9]+~i', '', $text);
        $text = strtolower($text);

        return $text ?: 'resource';
    }

    /**
     * LEGACY (kept for backward-compatibility):
     * Creates a PersonalFile under the user's personal files (old behavior).
     *
     * @deprecated prefer upsertCertificateResource()
     */
    public function generateCertificatePersonalFile(int $userId, string $fileName, string $certificateContent): ?PersonalFile
    {
        $em = $this->getEntityManager();
        $userEntity = $em->getRepository(User::class)->find($userId);

        $existingFile = $em->getRepository(PersonalFile::class)->findOneBy(['title' => $fileName]);

        if (!$existingFile) {
            $tempFilePath = tempnam(sys_get_temp_dir(), 'cert');
            file_put_contents($tempFilePath, $certificateContent);

            $mimeType = mime_content_type($tempFilePath);
            $uploadedFile = new UploadedFile($tempFilePath, $fileName, $mimeType, null, true);

            $personalFile = new PersonalFile();
            $personalFile->setTitle($fileName);
            $personalFile->setCreator($userEntity);
            $personalFile->setParentResourceNode($userEntity->getResourceNode()->getId());
            $personalFile->setResourceName($fileName);
            $personalFile->setUploadFile($uploadedFile);
            $personalFile->addUserLink($userEntity);

            $em->persist($personalFile);
            $em->flush();

            @unlink($tempFilePath);

            return $personalFile;
        }

        return $existingFile;
    }

    /**
     * Backward-compatible alias.
     * Some legacy code calls deleteCertificateResource(userId, catId).
     */
    public function deleteCertificateResource(int $userId, int $catId): bool
    {
        return $this->deleteCertificateAndRelatedFiles($userId, $catId);
    }

    /**
     * Delete the certificate and its associated files.
     * - New mode (resource): hardDelete() removes node + files + links.
     * - Legacy mode (personal file): remove PersonalFile and then the row.
     */
    public function deleteCertificateAndRelatedFiles(int $userId, int $catId): bool
    {
        $em = $this->getEntityManager();

        /** @var GradebookCertificate|null $certificate */
        $certificate = $this->getCertificateByUserId($catId, $userId);

        if (!$certificate) {
            return false;
        }

        // attached ResourceNode -> hard delete (cascades)
        if ($certificate->hasResourceNode()) {
            $this->hardDelete($certificate);

            return true;
        }

        // delete PersonalFile created under user's personal files
        $title = basename(ltrim((string) $certificate->getPathCertificate(), '/'));
        $personalFile = $em->getRepository(PersonalFile::class)->findOneBy(['title' => $title]);

        if ($personalFile) {
            $em->remove($personalFile);
            $em->flush();
        }

        $em->remove($certificate);
        $em->flush();

        return true;
    }

    /**
     * List certificates with course/session/url context.
     */
    public function findCertificatesWithContext(
        int $urlId,
        int $offset = 0,
        int $limit = 50
    ): array {
        return $this->createQueryBuilder('gc')
            ->join('gc.category', 'cat')
            ->join('cat.course', 'course')
            ->join('course.urls', 'curl')
            ->join('curl.url', 'url')
            ->leftJoin('course.sessions', 'src')
            ->leftJoin('src.session', 'session')
            ->where('url.id = :urlId')
            ->setParameter('urlId', $urlId)
            ->orderBy('gc.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findIncompleteCertificates(int $urlId): array
    {
        $today = new DateTimeImmutable();
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('sru', 'u', 's', 'src', 'c')
            ->from(SessionRelUser::class, 'sru')
            ->join('sru.user', 'u')
            ->join('sru.session', 's')
            ->join('s.courses', 'src')
            ->join('src.course', 'c')
            ->join('c.urls', 'curl')
            ->join('curl.url', 'url')
            ->where('url.id = :urlId')
            ->andWhere('s.accessStartDate <= :today')
            ->andWhere('s.accessEndDate   IS NULL OR s.accessEndDate > :today')
            ->andWhere(
                $qb->expr()->not(
                    $qb->expr()->exists(
                        $em->createQueryBuilder()
                            ->select('gc2.id')
                            ->from(GradebookCertificate::class, 'gc2')
                            ->join('gc2.category', 'cat2')
                            ->join('cat2.course', 'cc')
                            ->where('gc2.user = u')
                            ->andWhere('cc = c')
                            ->getDQL()
                    )
                )
            )
            ->setParameter('urlId', $urlId)
            ->setParameter('today', $today)
            ->orderBy('s.accessStartDate', 'DESC')
        ;

        return $qb->getQuery()->getResult();
    }

    public function findRestartableSessions(
        int $urlId,
        int $offset = 0,
        int $limit = 10
    ): array {
        $today = new DateTimeImmutable();

        $qb = $this->_em->createQueryBuilder();

        $qb->select('srcu')
            ->from(SessionRelCourseRelUser::class, 'srcu')
            ->join('srcu.session', 's')
            ->join('srcu.course', 'c')
            ->join('c.urls', 'curl')
            ->join('curl.url', 'url')
            ->where('url.id = :urlId')
            ->andWhere('s.accessEndDate IS NOT NULL')
            ->andWhere('s.accessEndDate < :today')
            ->andWhere(
                $qb->expr()->not(
                    $qb->expr()->exists(
                        $this->_em->createQueryBuilder()
                            ->select('gc2.id')
                            ->from(GradebookCertificate::class, 'gc2')
                            ->join('gc2.category', 'cat2')
                            ->join('cat2.course', 'cc')
                            ->where('gc2.user = srcu.user')
                            ->andWhere('cc = c')
                            ->getDQL()
                    )
                )
            )
            ->orderBy('s.accessEndDate', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->setParameter('urlId', $urlId)
            ->setParameter('today', $today)
        ;

        return $qb->getQuery()->getResult();
    }
}
