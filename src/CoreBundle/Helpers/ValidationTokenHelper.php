<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\ValidationToken;
use Chamilo\CoreBundle\Repository\ValidationTokenRepository;
use InvalidArgumentException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ValidationTokenHelper
{
    // Define constants for the types
    public const TYPE_TICKET = 1;
    public const TYPE_USER = 2;
    public const TYPE_REMEMBER_ME = 3;

    public function __construct(
        private readonly ValidationTokenRepository $tokenRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    public function generateLink(int $type, int $resourceId): string
    {
        $token = new ValidationToken($type, $resourceId);
        $this->tokenRepository->save($token, true);

        // Generate a validation link with the token's hash
        return $this->urlGenerator->generate('validate_token', [
            'type' => $this->getTypeString($type),
            'hash' => $token->getHash(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public function getTypeId(string $type): int
    {
        return match ($type) {
            'ticket' => self::TYPE_TICKET,
            'user' => self::TYPE_USER,
            'remember_me' => self::TYPE_REMEMBER_ME,
            default => throw new InvalidArgumentException('Unrecognized validation type'),
        };
    }

    public function getTypeString(int $type): string
    {
        return match ($type) {
            self::TYPE_TICKET => 'ticket',
            self::TYPE_USER => 'user',
            self::TYPE_REMEMBER_ME => 'remember_me',
            default => throw new InvalidArgumentException('Unrecognized validation type'),
        };
    }
}
