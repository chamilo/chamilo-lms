<?php
/* For licensing terms, see /license.txt */
/**
 * This script adds URL to the access_url table from an XLSX file with a column
 * for the subdomain and a column for the description of that URL.
 * Make sure to configure the file name, the main URL and the columns names
 * below before you run it.
 */

exit;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;

// Configuration
$excelFilePath = '/tmp/urls.xlsx'; // An xlsx file where we find a column with a subdomain name and a column with a description
$subdomainField = 'col2';
$descriptionField = 'col3';
$mainUrl = 'https://somedomain.com/'; // Main URL for constructing subdomains

// Bootstrap Chamilo
require_once __DIR__ . '/../../vendor/autoload.php'; // Adjust path to autoload.php
require_once __DIR__ . '/../../public/main/inc/global.inc.php'; // Chamilo global configuration

// Initialize Doctrine EntityManager
try {
    /** @var EntityManagerInterface $entityManager */
    $entityManager = Database::getManager(); // Chamilo's Database class provides EntityManager
} catch (Exception $e) {
    echo "Error initializing Doctrine: " . $e->getMessage() . "\n";
    exit(1);
}

try {
    // Parse the main URL to extract the domain
    $parsedUrl = parse_url($mainUrl);
    if (!isset($parsedUrl['host'])) {
        throw new Exception("Invalid main URL: {$mainUrl}. Could not extract host.");
    }
    $mainDomain = $parsedUrl['host']; // e.g., 'mypost.com'
    $scheme = $parsedUrl['scheme'] ?? 'https'; // Default to https if not specified

    // Get the current user
    $currentUserId = api_get_user_id() ?: 1; // Default to user ID 1 if not logged in
    $user = $entityManager->getRepository(User::class)->find($currentUserId);
    if (!$user) {
        throw new Exception("User with ID {$currentUserId} not found. Cannot assign to URLs.");
    }

    // Load the Excel file
    $spreadsheet = IOFactory::load($excelFilePath);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();

    // Find column indices for 'ref_tempo' and 'List-item'
    $headerRow = array_shift($rows); // Assume first row is header
    $subdomainIndex = array_search($subdomainField, $headerRow);
    $descriptionIndex = array_search($descriptionField, $headerRow);

    if ($subdomainIndex === false || $descriptionIndex === false) {
        throw new Exception("Columns 'ref_tempo' or 'List-item' not found in the Excel file.");
    }

    // Process each row
    foreach ($rows as $rowNumber => $row) {
        $refTempo = strtolower(trim($row[$subdomainIndex] ?? ''));
        $listItem = trim($row[$descriptionIndex] ?? '');

        // Skip empty ref_tempo values
        if (empty($refTempo)) {
            echo "Row " . ($rowNumber + 2) . ": Skipping due to empty ref_tempo.\n";
            continue;
        }

        // Validate ref_tempo for subdomain compatibility (basic check)
        if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9]$/', $refTempo)) {
            echo "Row " . ($rowNumber + 2) . ": Invalid ref_tempo '$refTempo' for subdomain. Skipping.\n";
            continue;
        }

        // Construct the subdomain URL based on mainUrl
        $subdomainUrl = "{$scheme}://{$refTempo}.{$mainDomain}/";

        // Check if the URL already exists
        $existingUrl = $entityManager->getRepository(AccessUrl::class)->findOneBy(['url' => $subdomainUrl]);
        if ($existingUrl) {
            echo "Row " . ($rowNumber + 2) . ": URL {$subdomainUrl} already exists. Skipping.\n";
            continue;
        }

        // Create new AccessUrl entity
        $accessUrl = new AccessUrl();
        $accessUrl->setUrl($subdomainUrl);
        $accessUrl->setDescription($listItem);
        $accessUrl->setActive(1); // Set as active
        $accessUrl->setCreatedBy($currentUserId); // Set creator ID
        $accessUrl->setTms(new \DateTime()); // Set timestamp

        // Add the current user to the AccessUrl
        $accessUrl->addUser($user);

        // Persist the entity
        //$entityManager->persist($accessUrl);
        echo "Row " . ($rowNumber + 2) . ": Created URL: {$subdomainUrl} with description: {$listItem}, assigned to user ID: {$currentUserId}\n";
    }

    // Save all changes to the database
    $entityManager->flush();
    echo "All URLs have been saved to the database.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

$entityManager->close();
