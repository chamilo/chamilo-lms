<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\TrackEDownloads;
use Chamilo\CoreBundle\Entity\User;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TrackEDownloadsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrackEDownloads::class);
    }

    /**
     * Save record of a resource being downloaded in track_e_downloads.
     */
    public function saveDownload(User $user, ?ResourceLink $resourceLink, string $documentUrl): int
    {
        $download = (new TrackEDownloads())
            ->setDownDocPath($documentUrl)
            ->setDownUserId($user->getId())
            ->setDownDate(new DateTime())
            ->setResourceLink($resourceLink)
        ;

        $this->_em->persist($download);
        $this->_em->flush();

        return $download->getDownId();
    }
}
