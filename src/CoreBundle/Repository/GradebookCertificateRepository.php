<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\PersonalFile;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Chamilo\CoreBundle\Entity\User;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class GradebookCertificateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GradebookCertificate::class);
    }

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

        if ($asArray) {
            try {
                return $query->getOneOrNullResult(AbstractQuery::HYDRATE_ARRAY);
            } catch (NonUniqueResultException $e) {
                return null;
            }
        } else {
            try {
                return $query->getOneOrNullResult();
            } catch (NonUniqueResultException $e) {
                return null;
            }
        }
    }

    public function registerUserInfoAboutCertificate(int $catId, int $userId, float $scoreCertificate, string $fileName = ''): void
    {
        $existingCertificate = $this->getCertificateByUserId(0 === $catId ? null : $catId, $userId);

        if (!$existingCertificate) {
            $certificate = new GradebookCertificate();

            $category = 0 === $catId ? null : $this->_em->getRepository(GradebookCategory::class)->find($catId);
            $user = $this->_em->getRepository(User::class)->find($userId);

            if (!empty($fileName)) {
                $fileName = '/'.$fileName;
            }

            if ($category) {
                $certificate->setCategory($category);
            }
            $certificate->setUser($user);
            $certificate->setPathCertificate($fileName);
            $certificate->setScoreCertificate($scoreCertificate);
            $certificate->setCreatedAt(new DateTime());

            $this->_em->persist($certificate);
            $this->_em->flush();
        }
    }

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

            unlink($tempFilePath);

            return $personalFile;
        }

        return $existingFile;
    }

    public function deleteCertificateAndRelatedFiles(int $userId, int $catId): bool
    {
        $em = $this->getEntityManager();
        $certificate = $this->getCertificateByUserId($catId, $userId);

        if (!$certificate) {
            return false;
        }

        $title = basename(ltrim($certificate->getPathCertificate(), '/'));
        $personalFile = $em->getRepository(PersonalFile::class)->findOneBy(['title' => $title]);

        if (!$personalFile) {
            return false;
        }

        $em->remove($personalFile);
        $em->flush();

        $em->remove($certificate);
        $em->flush();

        return true;
    }

    public function findCertificatesWithContext(
        int $urlId,
        int $offset = 0,
        int $limit  = 50
    ): array {
        return $this->createQueryBuilder('gc')
            ->join('gc.category',   'cat')
            ->join('cat.course',    'course')
            ->join('course.urls',   'curl')
            ->join('curl.url',      'url')
            ->leftJoin('course.sessions', 'src')
            ->leftJoin('src.session',     'session')
            ->where('url.id = :urlId')
            ->setParameter('urlId', $urlId)
            ->orderBy('gc.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findIncompleteCertificates(int $urlId): array
    {
        $today = new \DateTimeImmutable();
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('sru', 'u', 's', 'src', 'c')
        ->from(SessionRelUser::class, 'sru')
            ->join('sru.user',    'u')
            ->join('sru.session', 's')
            ->join('s.courses',   'src')
            ->join('src.course',  'c')
            ->join('c.urls',      'curl')
            ->join('curl.url',    'url')
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
                            ->join('cat2.course',  'cc')
                            ->where('gc2.user = u')
                            ->andWhere('cc = c')
                            ->getDQL()
                    )
                )
            )
            ->setParameter('urlId', $urlId)
            ->setParameter('today', $today)
            ->orderBy('s.accessStartDate', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function findRestartableSessions(
        int $urlId,
        int $offset = 0,
        int $limit  = 10
    ): array {
        $today = new \DateTimeImmutable();

        $qb = $this->_em->createQueryBuilder();

        $qb->select('srcu')
            ->from(SessionRelCourseRelUser::class, 'srcu')
            ->join('srcu.session', 's')
            ->join('srcu.course',  'c')
            ->join('c.urls',       'curl')
            ->join('curl.url',     'url')
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
                            ->join('cat2.course',  'cc')
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
            ->setParameter('today', $today);

        return $qb->getQuery()->getResult();
    }
}
