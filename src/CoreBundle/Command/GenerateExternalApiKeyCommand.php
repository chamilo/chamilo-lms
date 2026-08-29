<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Service\ExternalApi\ExternalApiKeyManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Generates or revokes a user's "external" API key — the credential a trusted
 * server-to-server integration (e.g. the WordPress storefront plugin's
 * service account) authenticates with against the general /api firewall.
 * There is no self-service UI for this yet (unlike the personal MCP API key
 * screen); an admin/developer with console access runs this once per portal.
 */
#[AsCommand(
    name: 'chamilo:security:generate-external-api-key',
    description: "Generate (or revoke) a user's external API key for server-to-server integrations.",
)]
class GenerateExternalApiKeyCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly AccessUrlRepository $accessUrlRepository,
        private readonly ExternalApiKeyManager $apiKeyManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'Username of the service account (e.g. wp-shop-sync)')
            ->addOption('access-url-id', null, InputOption::VALUE_REQUIRED, 'Access URL (portal) id to scope the key to', '1')
            ->addOption('revoke', null, InputOption::VALUE_NONE, 'Revoke the existing key instead of generating a new one')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $username = (string) $input->getArgument('username');
        $user = $this->userRepository->findOneBy(['username' => $username]);
        if (!$user instanceof User) {
            $io->error(\sprintf('No user found with username "%s".', $username));

            return Command::FAILURE;
        }

        $accessUrlId = (int) $input->getOption('access-url-id');
        $accessUrl = $this->accessUrlRepository->find($accessUrlId);
        if (!$accessUrl instanceof AccessUrl) {
            $io->error(\sprintf('No access URL found with id %d.', $accessUrlId));

            return Command::FAILURE;
        }

        if ($input->getOption('revoke')) {
            $revoked = $this->apiKeyManager->revokeForUser($user, $accessUrl);
            $io->success($revoked ? 'External API key revoked.' : 'No active external API key to revoke.');

            return Command::SUCCESS;
        }

        $result = $this->apiKeyManager->generateForUser($user, $accessUrl);

        $io->success('External API key generated. Copy it now — it cannot be retrieved again:');
        $io->writeln($result['plainKey']);
        $io->note(\sprintf('Key prefix (for identification in the UI/logs): %s', $result['keyPrefix']));

        return Command::SUCCESS;
    }
}
