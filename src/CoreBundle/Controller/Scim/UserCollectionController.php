<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Scim;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Serializer\Normalizer\Scim\UserNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/scim/v2/Users', methods: ['GET', 'POST'])]
class UserCollectionController extends AbstractScimController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly SerializerInterface $serializer,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        return $request->isMethod('POST')
            ? $this->createUser($request)
            : $this->listUsers($request);
    }

    private function listUsers(Request $request): JsonResponse
    {
        $startIndex = max(1, $request->query->getInt('startIndex', 1));
        $count = min(100, $request->query->getInt('count', 30));
        $filter = $request->query->get('filter');

        $qb = $this->userRepository->createQueryBuilder('u');

        if ($filter && preg_match('/userName\s+eq\s+"([^"]+)"/i', $filter, $matches)) {
            $qb
                ->andWhere('u.username = :username')
                ->setParameter('username', $matches[1])
            ;
        }

        try {
            $total = (clone $qb)->select('COUNT(u.id)')->getQuery()->getSingleScalarResult();
        } catch (NoResultException|NonUniqueResultException) {
            $total = 0;
        }

        $users = $qb
            ->setFirstResult($startIndex - 1)
            ->setMaxResults($count)
            ->getQuery()
            ->getResult();

        $resources = [];

        foreach ($users as $user) {
            $resources[] = $this->serializer->normalize($user, UserNormalizer::FORMAT);
        }

        $response = [
            'schemas' => ['urn:ietf:params:scim:api:messages:2.0:ListResponse'],
            'totalResults' => (int) $total,
            'itemsPerPage' => count($resources),
            'startIndex' => $startIndex,
            'Resources' => $resources,
        ];

        return new JsonResponse($response, Response::HTTP_OK, ['Content-Type' => parent::SCIM_CONTENT_TYPE]);
    }

    private function createUser(Request $request): JsonResponse
    {
        $data = $this->getAndValidateJson($request);

        $user = new User();

        $this->serializer->denormalize($data, User::class, 'json', ['object_to_populate' => $user]);

        $this->userRepository->updateUser($user);

        $this->accessUrlHelper->getCurrent()?->addUser($user);

        $this->entityManager->flush();

        $normalized = $this->serializer->normalize($user, 'json');

        $headers = [];
        $headers['Content-Type'] = parent::SCIM_CONTENT_TYPE;
        $headers['Location'] = $this->generateUrl(
            'scim_user_item',
            ['id' => $user->getResourceNode()->getUuid()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return new JsonResponse($normalized, Response::HTTP_CREATED, $headers);
    }
}
