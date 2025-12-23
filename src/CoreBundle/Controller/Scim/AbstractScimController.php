<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Scim;

use Chamilo\CoreBundle\Exception\ScimException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractScimController extends AbstractController
{
    public const SCIM_CONTENT_TYPE = 'application/scim+json';

    protected function getAndValidateJson(Request $request): array
    {
        $content = $request->getContent();

        if (empty($content)) {
            throw new ScimException('No content');
        }

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ScimException('Invalid JSON: '.json_last_error_msg());
        }

        return $data;
    }
}