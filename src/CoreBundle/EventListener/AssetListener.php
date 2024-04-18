<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Repository\AssetRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Vich\UploaderBundle\Event\Event;

class AssetListener implements EventSubscriberInterface
{
    protected AssetRepository $assetRepository;

    public function __construct(AssetRepository $assetRepository)
    {
        $this->assetRepository = $assetRepository;
    }

    public function onVichUploaderPostRemove(Event $event): void
    {
        /** @var Asset $asset */
        $asset = $event->getObject();
        if ($asset instanceof Asset) {
            $mapping = $event->getMapping();
            $filePath = $asset->getCategory().'/'.$asset->getFile()->getFilename();
            $this->assetRepository->getFileSystem()->deleteDirectory($filePath);

            // Deletes scorm folder: example: assets/scorm/myABC .
            /*if (!empty($folder) && Asset::SCORM === $asset->getCategory()) {
                $folder = Asset::SCORM.'/'.$folder;
                $this->assetRepository->getFileSystem()->deleteDirectory($folder);
            }*/
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return ['vich_uploader.post_remove' => 'onVichUploaderPostRemove'];
    }
}
