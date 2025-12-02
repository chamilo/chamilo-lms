<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Settings\SettingsManager;

class ResourceFileHelper
{
    public function __construct(
        private readonly SettingsManager $settingsManager,
        private readonly AccessUrlHelper $accessUrlHelper,
    ) {}

    public function resolveResourceFileByAccessUrl(ResourceNode $resourceNode): ?ResourceFile
    {
        $accessUrlSpecificFiles = 'true' === $this->settingsManager->getSetting('document.access_url_specific_files')
            && $this->accessUrlHelper->isMultiple();

        $resourceFile = null;
        $resourceFiles = $resourceNode->getResourceFiles();

        if ($accessUrlSpecificFiles) {
            $currentUrl = $this->accessUrlHelper->getCurrent()?->getUrl();

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
