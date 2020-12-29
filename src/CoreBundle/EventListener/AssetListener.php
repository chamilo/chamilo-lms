<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Repository\AssetRepository;
use Symfony\Component\HttpFoundation\Request;
use Vich\UploaderBundle\Event\Event;

class AssetListener
{
    protected $assetRepository;

    /**
     * @var Request
     */
    protected $request;

    public function __construct(AssetRepository $assetRepository)
    {
        $this->assetRepository = $assetRepository;
    }

    public function onVichUploaderPostRemove(Event $event)
    {
        /** @var Asset $asset */
        $asset = $event->getObject();
        if ($asset instanceof Asset) {
            $mapping = $event->getMapping();
            $folder = $mapping->getFile($asset)->getFilename();

            // Deletes scorm folder: example: assets/scorm/myABC .
            if (Asset::SCORM === $asset->getCategory() && !empty($folder)) {
                $folder = Asset::SCORM.'/'.$folder;
                $this->assetRepository->getFileSystem()->deleteDir($folder);
            }
        }
    }
}
