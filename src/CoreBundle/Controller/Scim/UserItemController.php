<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Scim;

use Chamilo\CoreBundle\Controller\Scim\AbstractScimController;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Exception\ScimException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[Route(
    '/scim/v2/Users/{id}',
    name: 'scim_user_item',
    methods: ['GET', 'PUT', 'PATCH', 'DELETE']
)]
class UserItemController extends AbstractScimController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SerializerInterface $serializer,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = null;

        return match ($request->getMethod()) {
            'GET'   => $this->findUser($user),
            'PUT'   => $this->replaceUser($request, $user),
            'PATCH' => $this->patchUser($request, $user),
            'DELETE'=> $this->deleteUser($user),
            default => throw new ScimException(
                Response::$statusTexts[Response::HTTP_METHOD_NOT_ALLOWED],
                Response::HTTP_METHOD_NOT_ALLOWED
            ),
        };
    }

    private function findUser(User $user): JsonResponse
    {
        $data = $this->serializer->normalize($user, 'json');

        return new JsonResponse($data, Response::HTTP_OK, ['Content-Type' => parent::SCIM_CONTENT_TYPE]);
    }

    private function replaceUser(Request $request, User $user): JsonResponse
    {
        $data = $this->getAndValidateJson($request);

        $this->serializer->denormalize(
            $data,
            User::class,
            'json',
            ['object_to_populate' => $user]
        );

        $this->entityManager->flush();

        return $this->findUser($user);
    }

    private function patchUser(Request $request, User $user): JsonResponse
    {
        // Para PATCH completo, puedes crear un Denormalizer personalizado o manejar Operations manualmente
        // Por ahora: usamos denormalización parcial (Symfony lo soporta con object_to_populate)
        $data = $this->getAndValidateJson($request);

        if (!isset($data['schemas']) || !in_array('urn:ietf:params:scim:api:messages:2.0:PatchOp', $data['schemas'])) {
            throw new ScimException('Schema PatchOp is required', Response::HTTP_BAD_REQUEST);
        }

        // Aquí iría lógica para Operations (replace, add, remove) → puedes extenderlo después
        // Por simplicidad, usamos denormalización sobre los campos directos
        $this->serializer->denormalize(
            $data,
            User::class,
            'json',
            ['object_to_populate' => $user]
        );

        $this->entityManager->flush();

        return $this->findUser($user);
    }

    private function deleteUser(User $user): JsonResponse
    {
        \UserManager::delete_user($user->getId());

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}