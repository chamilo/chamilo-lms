<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Storage\Factory;

use Aws\S3\S3Client;

class AwsS3ClientFactory
{
    /**
     * Creates an S3Client instance, optionally with a custom endpoint for S3-compatible services
     * such as DigitalOcean Spaces, MinIO, Wasabi, etc.
     *
     * Set AWS_S3_STORAGE_ENDPOINT to your provider's endpoint URL (e.g. https://nyc3.digitaloceanspaces.com).
     * Set AWS_S3_USE_PATH_STYLE=true if your provider requires path-style URLs instead of virtual-hosted-style.
     */
    public static function create(
        string $version,
        string $region,
        string $key,
        string $secret,
        string $endpoint = '',
        string $usePathStyle = '',
    ): S3Client {
        $config = [
            'version' => $version,
            'region' => $region,
            'credentials' => [
                'key' => $key,
                'secret' => $secret,
            ],
        ];

        if ('' !== $endpoint) {
            $config['endpoint'] = $endpoint;
            $config['use_path_style_endpoint'] = filter_var($usePathStyle, FILTER_VALIDATE_BOOLEAN);
        }

        return new S3Client($config);
    }
}
