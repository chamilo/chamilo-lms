<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class UserAccessUrlsController extends AbstractController
{
    public function __construct(
        private readonly AccessUrlRepository $accessUrlRepo
    ) {}

    public function __invoke(User $user): array
    {
        return $this->accessUrlRepo->getUserActivePortals($user)->getQuery()->getResult();
    }
}
