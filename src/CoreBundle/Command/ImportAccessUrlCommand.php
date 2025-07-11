<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-access-url',
    description: 'Import AccessUrl entities from an XLSX file.',
)]
class ImportAccessUrlCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('xlsx-file', InputArgument::REQUIRED, 'Path to the XLSX file')
            ->addArgument('base-url', InputArgument::REQUIRED, 'Base URL for subdomains (e.g., https://somedomain.com/)')
            ->setHelp('This command imports AccessUrl entities from an XLSX file. The file must have a title row with "subdomain" and "description" columns. Subdomains are lowercased. The ResourceNode parent is set to AccessUrl ID = 1. If needed, removing the created URLs manually will require cleaning the access_url_rel_user and resource_node tables.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Importing AccessUrl Entities');

        $xlsxFile = $input->getArgument('xlsx-file');
        $baseUrl = $input->getArgument('base-url');

        // Validate base URL
        $parsedUrl = parse_url($baseUrl);
        if (!isset($parsedUrl['host']) || !isset($parsedUrl['scheme'])) {
            $io->error("Invalid base URL: {$baseUrl}. Must include scheme and host (e.g., https://somedomain.com/).");

            return Command::FAILURE;
        }
        $mainDomain = $parsedUrl['host']; // e.g., 'somedomain.com'
        $scheme = $parsedUrl['scheme']; // e.g., 'https'

        // Validate XLSX file
        if (!file_exists($xlsxFile) || !is_readable($xlsxFile)) {
            $io->error("XLSX file not found or not readable: {$xlsxFile}");

            return Command::FAILURE;
        }

        // Get default user (ID 1)
        $defaultUserId = 1;
        $user = $this->entityManager->getRepository(User::class)->find($defaultUserId);
        if (!$user) {
            $io->error("User with ID {$defaultUserId} not found. Cannot assign to URLs.");

            return Command::FAILURE;
        }

        // Get main AccessUrl (resource_node.parent = null) and its ResourceNode
        $mainAccessUrl = $this->entityManager->getRepository(AccessUrl::class)->findOneBy(['parent' => null]);
        if (!$mainAccessUrl) {
            $io->error('Main AccessUrl not found.');

            return Command::FAILURE;
        }

        // Get ResourceType for AccessUrl
        $resourceTypeRepo = $this->entityManager->getRepository(ResourceType::class);
        $resourceType = $resourceTypeRepo->findOneBy(['title' => 'urls']);
        if (!$resourceType) {
            $io->error("ResourceType 'urls' not found.");

            return Command::FAILURE;
        }

        try {
            // Load the Excel file
            $spreadsheet = IOFactory::load($xlsxFile);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Find column indices for 'subdomain' and 'description'
            $headerRow = array_shift($rows); // Assume first row is header
            $subdomainIndex = array_search('subdomain', $headerRow);
            $descriptionIndex = array_search('description', $headerRow);

            if (false === $subdomainIndex || false === $descriptionIndex) {
                $io->error("Columns 'subdomain' or 'description' not found in the Excel file.");

                return Command::FAILURE;
            }

            $createdCount = 0;
            $skippedCount = 0;

            foreach ($rows as $rowNumber => $row) {
                $subdomain = trim($row[$subdomainIndex] ?? ''); // 'subdomain' column
                $description = trim($row[$descriptionIndex] ?? ''); // 'description' column

                // Skip empty subdomain
                if (empty($subdomain)) {
                    $io->warning('Row '.($rowNumber + 2).': Skipping due to empty subdomain.');
                    $skippedCount++;

                    continue;
                }

                // Lowercase and validate subdomain
                $subdomain = strtolower($subdomain);
                if (!preg_match('/^[a-z0-9][a-z0-9\-]*[a-z0-9]$/', $subdomain)) {
                    $io->warning('Row '.($rowNumber + 2).": Invalid subdomain '$subdomain'. Skipping.");
                    $skippedCount++;

                    continue;
                }

                // Construct the subdomain URL
                $subdomainUrl = "{$scheme}://{$subdomain}.{$mainDomain}/";

                // Check if the URL already exists
                $existingUrl = $this->entityManager->getRepository(AccessUrl::class)->findOneBy(['url' => $subdomainUrl]);
                if ($existingUrl) {
                    $io->warning('Row '.($rowNumber + 2).": URL {$subdomainUrl} already exists. Skipping.");
                    $skippedCount++;

                    continue;
                }

                // Create new AccessUrl entity
                $accessUrl = new AccessUrl();
                $accessUrl->setUrl($subdomainUrl);
                $accessUrl->setDescription($description);
                $accessUrl->setActive(1); // Set as active
                $accessUrl->setCreatedBy($defaultUserId); // Set created_by
                $accessUrl->setTms(new DateTime()); // Set timestamp
                // $accessUrl->setResourceNode($resourceNode); // Assign ResourceNode
                $accessUrl->addUser($user); // Associate user
                $accessUrl->setCreator($user); // Temporary hack as AccessUrl should be able to set this automatically
                $accessUrl->setParent($mainAccessUrl); // Set this URL as a child of the admin URL

                // Persist entity
                $this->entityManager->persist($accessUrl);

                $io->success('Row '.($rowNumber + 2).": Created URL: {$subdomainUrl} with description: {$description}, assigned to user ID: {$defaultUserId}, parent AccessUrl ID: 1");
                $createdCount++;
            }

            // Save changes
            $this->entityManager->flush();
            $io->success("Import completed: {$createdCount} URLs created, {$skippedCount} rows skipped.");

            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('Error: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
