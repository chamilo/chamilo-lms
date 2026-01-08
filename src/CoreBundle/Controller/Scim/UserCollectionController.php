<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Scim;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Serializer\Denormalizer\Scim\UserDenormalizer;
use Chamilo\CoreBundle\Serializer\Normalizer\Scim\UserNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/scim/v2/Users', name: 'scim_users', methods: ['GET', 'POST'])]
class UserCollectionController extends AbstractScimController
{
    public function __invoke(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        SerializerInterface $serializer,
    ): JsonResponse {
        $this->authenticateRequest($request);

        if ($request->isMethod('POST')) {
            return $this->createUser(
                $request,
                $entityManager,
                $userRepository,
                $serializer,
            );
        }

        return $this->listUsers(
            $request,
            $userRepository,
            $serializer,
        );
    }

    public function listUsers(
        Request $request,
        UserRepository $userRepository,
        SerializerInterface $serializer,
    ): JsonResponse {
        $startIndex = max(1, $request->query->getInt('startIndex', 1));
        $count = min(100, $request->query->getInt('count', 30));
        $filter = $request->query->get('filter');

        $qb = $userRepository->createQueryBuilder('u');

        if ($filter && preg_match('/userName\s+eq\s+"([^"]+)"/i', $filter, $matches)) {
            $qb
                ->andWhere('u.username = :username')
                ->setParameter('username', $matches[1])
            ;
        }

        try {
            $total = (clone $qb)->select('COUNT(u.id)')->getQuery()->getSingleScalarResult();
        } catch (NonUniqueResultException|NoResultException) {
            $total = 0;
        }

        $users = $qb
            ->setFirstResult($startIndex - 1)
            ->setMaxResults($count)
            ->getQuery()
            ->getResult()
        ;

        $resources = [];

        foreach ($users as $user) {
            $resources[] = $serializer->normalize($user, UserNormalizer::FORMAT);
        }

        $response = [
            'schemas' => ['urn:ietf:params:scim:api:messages:2.0:ListResponse'],
            'totalResults' => (int) $total,
            'itemsPerPage' => \count($resources),
            'startIndex' => $startIndex,
            'Resources' => $resources,
        ];

        return new JsonResponse($response, Response::HTTP_OK, ['Content-Type' => self::SCIM_CONTENT_TYPE]);
    }

    public function createUser(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        SerializerInterface $serializer,
    ): JsonResponse {
        $data = $this->getAndValidateJson($request);

        /** @var User $user */
        $user = $serializer->denormalize($data, User::class, UserDenormalizer::FORMAT);

        if ($userRepository->findOneBy(['username' => $user->getUsername()])) {
            throw new ConflictHttpException($this->translator->trans('This login is already in use'));
        }

        $currentAccessUrl = $this->accessUrlHelper->getCurrent();

        $user
            ->setCreator($userRepository->findOnePlatformAdmin())
            ->addAuthSourceByAuthentication(
                $this->scimConfig['auth_source'],
                $currentAccessUrl
            )
            ->setPlainPassword('scim')
            ->setStatus(STUDENT)
            ->setRoleFromStatus(STUDENT)
        ;

        $userRepository->updateUser($user);

        $currentAccessUrl->addUser($user);

        $entityManager->flush();

        if (isset($data['externalId'])) {
            $this->scimHelper->saveExternalId($data['externalId'], $user);
        }

        $normalized = $serializer->normalize($user, UserNormalizer::FORMAT);

        return new JsonResponse(
            $normalized,
            Response::HTTP_CREATED,
            [
                'Content-Type' => self::SCIM_CONTENT_TYPE,
                'Location' => $normalized['meta']['location'],
            ]
        );
    }
}
