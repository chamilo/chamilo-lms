<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Scim;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Exception\ScimException;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
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
    public function __invoke(
        string $uuid,
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepo,
        SerializerInterface $serializer,
    ): JsonResponse {
        $this->authenticateRequest($request);

        $user = $userRepo->findOneBy(['uuid' => $uuid]);

        if (!$user || $user->isSoftDeleted()) {
            throw $this->createNotFoundException($this->translator->trans('User not found.'));
        }

        return match ($request->getMethod()) {
            'GET' => $this->findUser(
                $user,
                $serializer,
            ),
            'PUT' => $this->replaceUser(
                $user,
                $request,
                $serializer,
                $entityManager,
            ),
            'PATCH' => $this->patchUser(
                $user,
                $request,
                $entityManager,
                $serializer,
            ),
            'DELETE' => $this->deleteUser($user),
            default => throw new MethodNotAllowedHttpException(['GET', 'PUT', 'PATCH', 'DELETE']),
        };
    }

    private function findUser(
        User $user,
        SerializerInterface $serializer,
    ): JsonResponse {
        $normalized = $serializer->normalize($user, UserNormalizer::FORMAT);

        return new JsonResponse(
            $normalized,
            Response::HTTP_OK,
            ['Content-Type' => self::SCIM_CONTENT_TYPE]
        );
    }

    private function replaceUser(
        User $user,
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $data = $this->getAndValidateJson($request);

        $serializer->denormalize($data, User::class, UserDenormalizer::FORMAT, ['object_to_populate' => $user]);

        $entityManager->flush();

        return $this->findUser($user, $serializer);
    }

    private function patchUser(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
    ): JsonResponse {
        $data = $this->getAndValidateJson($request);

        if (!isset($data['schemas']) || !\in_array('urn:ietf:params:scim:api:messages:2.0:PatchOp', $data['schemas'])) {
            throw new ScimException($this->translator->trans('Invalid schemas for PATCH operation.'));
        }

        if (!isset($data['Operations']) || !\is_array($data['Operations'])) {
            throw new ScimException($this->translator->trans('Missing required "Operations" array.'));
        }

        foreach ($data['Operations'] as $operation) {
            $op = strtolower($operation['op'] ?? '');
            $path = $operation['path'] ?? null;
            $value = $operation['value'] ?? null;

            if (!\in_array($op, ['add', 'replace', 'remove'])) {
                throw new ScimException(\sprintf($this->translator->trans("The operation '%s' is not supported."), $op));
            }

            if ($path) {
                if ('remove' === $op) {
                    $value = '';
                }

                $this->applyPatchWithPath($user, $path, (string) $value);

                if ('externalId' === $path) {
                    $this->scimHelper->saveExternalId($value, $user);
                }
            } else {
                if (!\is_array($value)) {
                    throw new ScimException($this->translator->trans('Required value for operation without path'));
                }

                $this->applyBulkReplace($user, $value);

                if (isset($value['externalId'])) {
                    $this->scimHelper->saveExternalId($value['externalId'], $user);
                }
            }
        }

        $entityManager->flush();

        return $this->findUser($user, $serializer);
    }

    private function applyPatchWithPath(User $user, string $path, string $value): void
    {
        $lowerPath = strtolower($path);

        if ('userName' === $lowerPath) {
            $user->setUsername($value);

            return;
        }

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

        // emails[type eq "work"].value
        if (preg_match('/^emails\[type eq "([^"]+)"]\.value$/i', $path, $matches)) {
            $type = $matches[1];

            if ('work' === strtolower($type)) {
                $user->setEmail($value);
            }

            return;
        }

        // phoneNumbers[type eq "work"].value
        if (preg_match('/^phoneNumbers\[type eq "([^"]+)"]\.value$/i', $path, $matches)) {
            $type = $matches[1];

            if ('work' === strtolower($type)) {
                $user->setPhone($value);
            }

            return;
        }

        // addresses[type eq "work"].formatted
        if (preg_match('/^addresses\[type eq "([^"]+)"]\.formatted$/i', $path, $matches)) {
            $type = $matches[1];

            if ('work' === strtolower($type)) {
                $user->setAddress($value);
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
        }
    }

    private function applyBulkReplace(User $user, array $value): void
    {
        /*if (isset($value['externalId'])) {
            $user->setExternalId($value['externalId']);
        }*/

        if (isset($value['userName'])) {
            $user->setUsername($value['userName']);
        }

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
