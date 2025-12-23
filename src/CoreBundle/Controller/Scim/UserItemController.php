<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Scim;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Exception\ScimException;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Serializer\Denormalizer\Scim\UserDenormalizer;
use Chamilo\CoreBundle\Serializer\Normalizer\Scim\UserNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use UserManager;

#[Route(
    '/scim/v2/Users/{uuid}',
    name: 'scim_user',
    methods: ['GET', 'PUT', 'PATCH', 'DELETE']
)]
class UserItemController extends AbstractScimController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepo,
        private readonly ResourceNodeRepository $resourceNodeRepo,
        private readonly SerializerInterface $serializer,
    ) {}

    public function __invoke(string $uuid, Request $request): JsonResponse
    {
        $resourdeNode = $this->resourceNodeRepo->findOneBy(['uuid' => $uuid]);

        /** @var User|null $user */
        $user = $this->userRepo->findOneBy(['resourceNode' => $resourdeNode]);

        return match ($request->getMethod()) {
            'GET' => $this->findUser($user),
            'PUT' => $this->replaceUser($request, $user),
            'PATCH' => $this->patchUser($request, $user),
            'DELETE' => $this->deleteUser($user),
            default => throw new MethodNotAllowedHttpException(['GET', 'PUT', 'PATCH', 'DELETE']),
        };
    }

    private function findUser(User $user): JsonResponse
    {
        $normalized = $this->serializer->normalize($user, UserNormalizer::FORMAT);

        return new JsonResponse(
            $normalized,
            Response::HTTP_OK,
            ['Content-Type' => parent::SCIM_CONTENT_TYPE]
        );
    }

    private function replaceUser(Request $request, User $user): JsonResponse
    {
        $data = $this->getAndValidateJson($request);

        $this->serializer->denormalize($data, User::class, UserDenormalizer::FORMAT, ['object_to_populate' => $user]);

        $this->entityManager->flush();

        return $this->findUser($user);
    }

    private function patchUser(Request $request, User $user): JsonResponse
    {
        $data = $this->getAndValidateJson($request);

        if (!isset($data['schemas']) || !\in_array('urn:ietf:params:scim:api:messages:2.0:PatchOp', $data['schemas'])) {
            throw new ScimException('Schema PatchOp is required');
        }

        if (!isset($data['Operations']) || !is_array($data['Operations'])) {
            throw new ScimException('"Operations" field is required');
        }

        foreach ($data['Operations'] as $operation) {
            $op = strtolower($operation['op'] ?? '');
            $path = $operation['path'] ?? null;
            $value = $operation['value'] ?? null;

            if (!in_array($op, ['add', 'replace'])) {
                throw new ScimException("The operation '{$op}' is not supported.");
            }

            if ($path) {
                $this->applyPatchWithPath($user, $path, $value);
            } else {
                if (!is_array($value)) {
                    throw new ScimException('Required value for operation without path');
                }

                $this->applyBulkReplace($user, $value);
            }
        }
        $this->entityManager->flush();

        return $this->findUser($user);
    }

    private function applyPatchWithPath(User $user, string $path, $value): void
    {
        $lowerPath = strtolower($path);

        if ('active' === $lowerPath) {
            $user->setActive((int) $value);

            return;
        }

        if ('locale' === $lowerPath) {
            $user->setLocale($value);

            return;
        }

        if ('timezone' === $lowerPath) {
            $user->setTimezone($value);

            return;
        }

        /*if ($lowerPath === 'externalid') {
            $user->setExternalId($value);

            return;
        }*/

        // emails[type eq "work"].value
        if (preg_match('/^emails\[type eq "([^"]+)"]\.value$/i', $path, $matches)) {
            $type = $matches[1];

            if (strtolower($type) === 'work') {
                $user->setEmail($value);
            }

            return;
        }

        // phoneNumbers[type eq "home"].value
        if (preg_match('/^phoneNumbers\[type eq "([^"]+)"]\.value$/i', $path, $matches)) {
            if ($phone = $matches[1]) {
                $user->setPhone($phone);
            }

            return;
        }

        // addresses[type eq "home"].formatted
        if (preg_match('/^addresses\[type eq "([^"]+)"]\.formatted$/i', $path, $matches)) {
            if ($address = $matches[1]) {
                $user->setAddress($address);
            }

            return;
        }

        if (str_starts_with($lowerPath, 'name.')) {
            $subPath = substr($path, 5); // exclude "name."

            switch (strtolower($subPath)) {
                case 'givenname':
                    $user->setFirstname($value);
                    break;
                case 'familyname':
                    $user->setLastname($value);
                    break;
            }

            return;
        }
    }

    private function applyBulkReplace(User $user, array $value): void
    {
        /*if (isset($value['externalId'])) {
            $user->setExternalId($value['externalId']);
        }*/

        // name.givenName, name.familyName
        if (isset($value['name.givenName'])) {
            $user->setFirstname($value['name.givenName']);
        }
        
        if (isset($value['name.familyName'])) {
            $user->setLastname($value['name.familyName']);
        }

        if ($email = UserDenormalizer::getPrimaryValue($value, 'emails')) {
            $user->setEmail($email);
        }

        if ($phone = UserDenormalizer::getPrimaryValue($value, 'phoneNumbers')) {
            $user->setPhone($phone);
        }

        if ($address = UserDenormalizer::getPrimaryValue($value, 'addresses', 'formatted')) {
            $user->setAddress($address);
        }

        if (isset($value['active'])) {
            $user->setActive((int) $value['active']);
        }

        if (isset($value['locale'])) {
            $user->setLocale($value['locale']);
        }

        if (isset($value['timezone'])) {
            $user->setTimezone($value['timezone']);
        }
    }

    private function deleteUser(User $user): JsonResponse
    {
        UserManager::delete_user($user->getId());

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
