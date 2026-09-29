<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Repository\OAuthAccessTokenRepository;
use Chamilo\CoreBundle\Repository\OAuthAuthorizationCodeRepository;
use Chamilo\CoreBundle\Repository\OAuthClientRepository;
use Chamilo\CoreBundle\Repository\OAuthRefreshTokenRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Prunes expired OAuth authorization codes, expired/long-revoked tokens, and
 * DCR-registered clients that were never used. Without this, /oauth/register
 * is an unbounded row-growth vector even with rate limiting.
 */
#[AsCommand(
    name: 'chamilo:oauth:gc',
    description: 'Delete expired OAuth authorization codes/tokens and unused registered clients.',
)]
class OAuthGarbageCollectorCommand extends Command
{
    private const int REVOKED_TOKEN_RETENTION_DAYS = 7;
    private const int EXPIRED_REFRESH_RETENTION_DAYS = 30;
    private const int UNUSED_CLIENT_RETENTION_DAYS = 30;

    public function __construct(
        private readonly OAuthAuthorizationCodeRepository $codeRepository,
        private readonly OAuthAccessTokenRepository $accessTokenRepository,
        private readonly OAuthRefreshTokenRepository $refreshTokenRepository,
        private readonly OAuthClientRepository $clientRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = new DateTime();

        $deletedCodes = $this->codeRepository->deleteExpired($now);
        $io->writeln(\sprintf('Deleted %d expired authorization codes.', $deletedCodes));

        $accessCutoff = (clone $now)->modify('-'.self::REVOKED_TOKEN_RETENTION_DAYS.' days');
        $deletedAccessTokens = $this->accessTokenRepository->deleteExpired($accessCutoff);
        $io->writeln(\sprintf('Deleted %d expired access tokens.', $deletedAccessTokens));

        $refreshCutoff = (clone $now)->modify('-'.self::EXPIRED_REFRESH_RETENTION_DAYS.' days');
        $deletedRefreshTokens = $this->refreshTokenRepository->deleteExpired($refreshCutoff);
        $io->writeln(\sprintf('Deleted %d expired refresh tokens.', $deletedRefreshTokens));

        $clientCutoff = (clone $now)->modify('-'.self::UNUSED_CLIENT_RETENTION_DAYS.' days');
        $staleClients = $this->clientRepository->findStaleUnusedClients($clientCutoff);
        foreach ($staleClients as $client) {
            $client->setRevokedAt($now);
        }
        $this->entityManager->flush();
        $io->writeln(\sprintf('Revoked %d unused registered clients.', \count($staleClients)));

        $io->success('OAuth garbage collection complete.');

        return Command::SUCCESS;
    }
}
