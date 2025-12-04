<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Settings\SettingsManager;

class ResourceFileHelper
{
    private bool $accessUrlSpecificFiles;
    private ?AccessUrl $currentAccessUrl;

    public function __construct(
        SettingsManager $settingsManager,
        AccessUrlHelper $accessUrlHelper,
    ) {
        $this->accessUrlSpecificFiles = $accessUrlHelper->isMultiple()
            && 'true' === $settingsManager->getSetting('document.access_url_specific_files');

        $this->currentAccessUrl = $accessUrlHelper->getCurrent();
    }

    public function resolveResourceFileByAccessUrl(ResourceNode $resourceNode): ?ResourceFile
    {
        if (!$resourceNode->hasResourceFile()) {
            return null;
        }

        $resourceFile = null;
        $resourceFiles = $resourceNode->getResourceFiles();

        if ($this->accessUrlSpecificFiles) {
            $currentUrl = $this->currentAccessUrl?->getUrl();

            foreach ($resourceFiles as $file) {
                if ($file->getAccessUrl() && $file->getAccessUrl()->getUrl() === $currentUrl) {
                    $resourceFile = $file;

                    break;
                }
            }
        }

        $resourceFile ??= $resourceFiles->filter(fn ($file) => null === $file->getAccessUrl())->first();

        return $resourceFile;
    }
}
