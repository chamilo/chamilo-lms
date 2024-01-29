<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\PersonalFile;
use Chamilo\CoreBundle\Entity\User;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class GradebookCertificateRepository  extends ServiceEntityRepository
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
            ->setMaxResults(1);

        if ($catId === 0) {
            $catId = null;
        }

        if (null === $catId) {
            $qb->andWhere('gc.category IS NULL');
        } else {
            $qb->andWhere('gc.category = :catId')
                ->setParameter('catId', $catId);
        }

        $qb->orderBy('gc.id', 'ASC');

        $query = $qb->getQuery();

        if ($asArray) {
            try {
                return $query->getOneOrNullResult(AbstractQuery::HYDRATE_ARRAY);
            } catch (\Doctrine\ORM\NonUniqueResultException $e) {
                return null;
            }
        } else {
            try {
                return $query->getOneOrNullResult();
            } catch (\Doctrine\ORM\NonUniqueResultException $e) {
                return null;
            }
        }
    }

    public function registerUserInfoAboutCertificate(int $catId, int $userId, float $scoreCertificate, string $fileName = ''): void
    {
        $existingCertificate = $this->getCertificateByUserId($catId === 0 ? null : $catId, $userId);

        if (!$existingCertificate) {
            $certificate = new GradebookCertificate();

            $category = $catId === 0 ? null : $this->_em->getRepository(GradebookCategory::class)->find($catId);
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
}
