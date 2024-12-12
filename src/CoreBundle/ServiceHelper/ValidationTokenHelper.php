<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\ServiceHelper;

use Chamilo\CoreBundle\Entity\ValidationToken;
use Chamilo\CoreBundle\Repository\ValidationTokenRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ValidationTokenHelper
{
    public function __construct(
        private readonly ValidationTokenRepository $tokenRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    public function generateLink(int $type, int $resourceId): string
    {
        $token = new ValidationToken($type, $resourceId);
        $this->tokenRepository->save($token, true);

        return $this->urlGenerator->generate('validate_token', [
            'type' => $this->getTypeString($type),
            'hash' => $token->getHash(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public function getTypeId(string $type): int
    {
        return match ($type) {
            'ticket' => 1,
            'user' => 2,
            default => throw new \InvalidArgumentException('Unrecognized validation type'),
        };
    }

    private function getTypeString(int $type): string
    {
        return match ($type) {
            1 => 'ticket',
            2 => 'user',
            default => throw new \InvalidArgumentException('Unrecognized validation type'),
        };
    }
}
