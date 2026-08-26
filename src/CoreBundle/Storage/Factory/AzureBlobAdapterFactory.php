<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Storage\Factory;

use AzureOss\Storage\Blob\BlobServiceClient;
use AzureOss\Storage\BlobFlysystem\AzureBlobStorageAdapter;

class AzureBlobAdapterFactory
{
    /**
     * Creates a Flysystem adapter bound to a single Azure Blob Storage container.
     *
     * oneup/flysystem-bundle only ships an "azureblob" adapter for the abandoned
     * league/flysystem-azure-blob-storage, so the adapters are built here and wired
     * through the bundle's "custom" adapter key instead.
     */
    public static function create(
        BlobServiceClient $client,
        string $container,
        ?string $prefix = null,
    ): AzureBlobStorageAdapter {
        return new AzureBlobStorageAdapter($client->getContainerClient($container), $prefix ?? '');
    }
}
