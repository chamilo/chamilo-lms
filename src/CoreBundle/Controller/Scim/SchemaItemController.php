<?php

/* For licensing terms,
see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Scim;

use Chamilo\CoreBundle\Exception\ScimException;
use Chamilo\CoreBundle\Traits\Scim\UserSchemaControllerTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/scim/v2/Schemas/{schemaId}', name: 'scim_schema', methods: ['GET'])]
class SchemaItemController extends AbstractScimController
{
    use UserSchemaControllerTrait;

    public function __invoke(string $schemaId): JsonResponse
    {
        if ('urn:ietf:params:scim:schemas:core:2.0:User' !== $schemaId) {
            throw new ScimException($this->translator->trans('Schema not supported.'));
        }

        $userSchema = $this->getUserCoreSchema();

        return new JsonResponse(
            $userSchema,
            Response::HTTP_OK,
            ['Content-Type' => self::SCIM_CONTENT_TYPE]
        );
    }
}
