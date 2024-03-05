<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\TrackEDownloads;
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
     * Save record of a resource being downloaded in track_e_downloads
     * @param int    $userId
     * @param int    $resourceLinkId
     * @param string $documentUrl
     * @return int
     */
    public function saveDownload(int $userId, int $resourceLinkId, string $documentUrl): int
    {
        $download = new TrackEDownloads();
        $download->setDownDocPath($documentUrl);
        $download->setDownUserId($userId);
        $download->setDownDate(new DateTime());

        $resourceLink = $this->_em->getRepository(ResourceLink::class)->find($resourceLinkId);
        if ($resourceLink) {
            $download->setResourceLink($resourceLink);
        }

        $this->_em->persist($download);
        $this->_em->flush();

        return $download->getDownId();
    }
}
