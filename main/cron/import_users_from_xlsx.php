<?php

/* For licensing terms, see /license.txt */

/**
 * This script imports users from an XLSX file, compares them to existing users in the Chamilo database,
 * updates or inserts users based on specific rules, and generates XLSX files for accounts with missing
 * email, lastname, or username, and exports accounts with duplicate Mail or Nom Prénom fields.
 * Uses 'Nom Prénom' column for name duplicates and includes 'Actif' and 'Nom Prénom' in exports.
 * - Skips import of users with empty 'Actif' unless they exist in DB (then updates, including inactive status)
 * - Updates existing users by username (always generated via generateProposedLogin) instead of importing as new
 * - Stores 'Matricule' as extra field 'external_user_id' without trimming leading zeros
 * - Logs decisions (skipped, updated, inserted) with details in simulation or proceed mode
 * - Stops processing on two consecutive empty rows in XLSX
 * - Allows custom output directory for XLSX files via command line
 * - Always generates username using generateProposedLogin()
 * - Username format: lastname + first letter of each firstname word; for active duplicates, append next letter from last firstname part
 * - For 3+ occurrences of lastname + firstname, append increasing letters from last firstname part (e.g., jpii, jpiii)
 */

// Ensure the script is run from the command line
if (php_sapi_name() !== 'cli') {
    die('This script must be run from the command line.');
}

// Configuration
$domain = 'example.com'; // Manually configured domain for generated emails

// Include Chamilo bootstrap and necessary classes
require_once __DIR__ . '/../../main/inc/global.inc.php';
// Include PHPExcel classes (assuming installed via Composer)
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../vendor/phpoffice/phpexcel/Classes/PHPExcel.php';
require_once __DIR__ . '/../../vendor/phpoffice/phpexcel/Classes/PHPExcel/IOFactory.php';

// Command-line arguments parsing (without getopt)
$proceed = false;
$outputDir = '/tmp'; // Default output directory
$xlsxFile = $argv[1] ?? ''; // Expect XLSX file path as first argument

// Parse arguments manually
for ($i = 2; $i < count($argv); $i++) {
    $arg = $argv[$i];
    if ($arg === '--proceed' || $arg === '-p') {
        $proceed = true;
    } elseif (preg_match('/^--output-dir=(.+)$/', $arg, $matches)) {
        $outputDir = $matches[1];
    } elseif ($arg === '-o' && $i + 1 < count($argv)) {
        $outputDir = $argv[++$i];
    }
}

// Debug: Log parsed arguments
echo "Parsed arguments:\n";
echo "  XLSX file: $xlsxFile\n";
echo "  Proceed: " . ($proceed ? 'true' : 'false') . "\n";
echo "  Output directory: $outputDir\n";

if (empty($xlsxFile) || !file_exists($xlsxFile)) {
    die("Usage: php import_users_from_xlsx.php <path_to_xlsx_file> [-p|--proceed] [-o <directory>|--output-dir=<directory>]\n");
}

// Validate and prepare output directory
if (!is_dir($outputDir)) {
    if (!mkdir($outputDir, 0755, true)) {
        die("Error: Could not create output directory '$outputDir'\n");
    }
}
if (!is_writable($outputDir)) {
    die("Error: Output directory '$outputDir' is not writable\n");
}
// Ensure trailing slash for consistency
$outputDir = rtrim($outputDir, '/') . '/';

// Initialize database connection
global $database;

// Load XLSX file
try {
    $inputFileType = PHPExcel_IOFactory::identify($xlsxFile);
    $reader = PHPExcel_IOFactory::createReader($inputFileType);
    $phpExcel = $reader->load($xlsxFile);
    $worksheet = $phpExcel->getActiveSheet();
    $xlsxRows = $worksheet->toArray();
} catch (Exception $e) {
    die("Error loading XLSX file: {$e->getMessage()}\n");
}

// Map XLSX columns to Chamilo database user table fields
$xlsxColumnMap = [
    'Nom' => 'lastname',
    'Prénom' => 'firstname',
    'Nom Prénom' => 'fullname',
    'Mail' => 'email',
    'Matricule' => 'official_code',
    'N° de badge' => 'password',
    'Tel mobile' => 'phone',
    'Actif' => 'active',
];

// Extract headers and validate
$xlsxHeaders = array_shift($xlsxRows);
$xlsxColumnIndices = [];
foreach ($xlsxColumnMap as $xlsxHeader => $dbField) {
    $index = array_search($xlsxHeader, $xlsxHeaders);
    if ($index === false) {
        die("Missing required column: {$xlsxHeader}\n");
    }
    $xlsxColumnIndices[$dbField] = $index;
}

// Initialize arrays to store rows with missing fields and duplicates
$emailMissing = [];
$lastnameMissing = [];
$usernameMissing = [];
$xlsxEmailCounts = [];
$xlsxNameCounts = [];
$duplicateEmails = [];
$duplicateNames = [];

// Output columns for missing field and duplicate files
$outputColumns = ['Matricule', 'Nom', 'Prénom', 'Nom Prénom', 'Mail', 'N° de badge', 'Actif', 'Proposed login'];

// Normalize string for duplicate detection
function normalizeName($name)
{
    $name = strtolower(trim($name));
    $name = preg_replace('/[\s-]+/', ' ', $name);
    return $name;
}

// Remove accents from strings
function removeAccents($str) {
    $str = str_replace(
        ['à','á','â','ã','ä','ç','è','é','ê','ë','ì','í','î','ï','ñ','ò','ó','ô','õ','ö','ù','ú','û','ü','ý','ÿ','À','Á','Â','Ã','Ä','Å','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ñ','Ò','Ó','Ô','Õ','Ö','Ù','Ú','Û','Ü','Ý'],
        ['a','a','a','a','a','c','e','e','e','e','i','i','i','i','n','o','o','o','o','o','u','u','u','u','y','y','A','A','A','A','A','A','C','E','E','E','E','I','I','I','I','N','O','O','O','O','O','U','U','U','U','Y'],
        $str
    );
    return $str;
}

// Generate proposed login based on lastname and firstname
function generateProposedLogin($xlsxLastname, $xlsxFirstname, $isActive, &$usedLogins) {
    $lastname = strtolower(trim(removeAccents($xlsxLastname)));
    $lastname = preg_replace('/[\s-]+/', '', $lastname);

    $firstname = trim(removeAccents($xlsxFirstname));
    $firstnameParts = preg_split('/[\s-]+/', $firstname, -1, PREG_SPLIT_NO_EMPTY);
    $firstLetters = '';
    foreach ($firstnameParts as $part) {
        if (!empty($part)) {
            $firstLetters .= strtolower(substr($part, 0, 1));
        }
    }

    // Base username: lastname + first letter of each firstname word
    $baseLogin = $lastname . $firstLetters;
    $login = $baseLogin;

    // Get last part of firstname for duplicate resolution
    $lastFirstnamePart = end($firstnameParts);
    $lastPartLetters = strtolower(preg_replace('/[\s-]+/', '', $lastFirstnamePart));

    // Create a unique key for lastname + firstname combination
    $nameKey = normalizeName($lastname . ' ' . $firstname);

    // Increment occurrence count for this name combination
    $usedLogins['counts'][$nameKey] = isset($usedLogins['counts'][$nameKey]) ? $usedLogins['counts'][$nameKey] + 1 : 1;
    $occurrence = $usedLogins['counts'][$nameKey];

    // Handle duplicates
    if (isset($usedLogins['logins'][$login])) {
        // Only modify if both current and previous users are active
        if ($isActive && $usedLogins['logins'][$login]['active']) {
            if ($occurrence == 2) {
                // Second occurrence: append next letter from last firstname part
                if (strlen($lastPartLetters) > 1) {
                    $login = $baseLogin . substr($lastPartLetters, 1, 1); // e.g., 'i' from 'Pierre'
                } else {
                    $login = $baseLogin . '1'; // Fallback if no more letters
                }
            } elseif ($occurrence >= 3) {
                // Third+ occurrence: append increasing letters from last firstname part
                $extraLetters = min($occurrence - 1, strlen($lastPartLetters) - 1); // e.g., 2 letters for 3rd, 3 for 4th
                if ($extraLetters > 0) {
                    $login = $baseLogin . substr($lastPartLetters, 1, $extraLetters); // e.g., 'ii', 'iii'
                } else {
                    $login = $baseLogin . ($occurrence - 1); // Fallback to number
                }
            }
        }
    }

    // Ensure uniqueness by appending a number if still conflicting
    $suffix = 1;
    $originalLogin = $login;
    while (isset($usedLogins['logins'][$login])) {
        $login = $originalLogin . $suffix;
        $suffix++;
    }

    // Store login with active status
    $usedLogins['logins'][$login] = ['active' => $isActive];
    return $login;
}

// Generate XLSX files for missing fields and duplicates
function createMissingFieldFile($filename, $rows, $columns) {
    if (empty($rows)) {
        echo "No rows to write for $filename\n";
        return;
    }

    $phpExcel = new PHPExcel();
    $worksheet = $phpExcel->getActiveSheet();

    foreach ($columns as $colIndex => $column) {
        $worksheet->setCellValueByColumnAndRow($colIndex, 1, $column);
    }

    foreach ($rows as $rowIndex => $rowData) {
        foreach ($columns as $colIndex => $column) {
            $worksheet->setCellValueByColumnAndRow($colIndex, $rowIndex + 2, $rowData[$column]);
        }
    }

    try {
        $writer = PHPExcel_IOFactory::createWriter($phpExcel, 'Excel2007');
        $writer->save($filename);
        echo "Generated $filename with " . count($rows) . " rows\n";
    } catch (Exception $e) {
        echo "Error saving $filename: {$e->getMessage()}\n";
    }
}

// Detect potential issues in XLSX file
$usedLogins = ['logins' => [], 'counts' => []];
$generatedEmailCounts = [];
$emptyRowCount = 0;
foreach ($xlsxRows as $rowIndex => $xlsxRow) {
    // Check for empty row
    $isEmpty = true;
    foreach ($xlsxRow as $cell) {
        if (!empty(trim($cell))) {
            $isEmpty = false;
            break;
        }
    }
    if ($isEmpty) {
        $emptyRowCount++;
        if ($emptyRowCount >= 2) {
            echo "Stopping processing: Found two consecutive empty rows at row " . ($rowIndex + 2) . "\n";
            break;
        }
        continue;
    } else {
        $emptyRowCount = 0; // Reset counter if row is not empty
    }

    $xlsxUserData = [];
    foreach ($xlsxColumnMap as $dbField) {
        $xlsxUserData[$dbField] = $xlsxRow[$xlsxColumnIndices[$dbField]] ?? '';
    }

    // Generate username
    $isActive = !empty($xlsxUserData['active']);
    $xlsxUserData['username'] = generateProposedLogin($xlsxUserData['lastname'], $xlsxUserData['firstname'], $isActive, $usedLogins);

    $rowData = [
        'Matricule' => $xlsxUserData['official_code'],
        'Nom' => $xlsxUserData['lastname'],
        'Prénom' => $xlsxUserData['firstname'],
        'Nom Prénom' => $xlsxUserData['fullname'],
        'Mail' => $xlsxUserData['email'],
        'N° de badge' => $xlsxUserData['password'],
        'Actif' => $xlsxUserData['active'],
        'Proposed login' => $xlsxUserData['username'],
    ];

    if ($isActive) {
        if (empty($xlsxUserData['email']) && strpos($xlsxUserData['official_code'], '0009') !== false) {
            $emailLastnameParts = preg_split('/[\s-]+/', trim(removeAccents($xlsxUserData['lastname'])), -1, PREG_SPLIT_NO_EMPTY);
            $emailLastname = !empty($emailLastnameParts[0]) ? strtolower($emailLastnameParts[0]) : '';
            $emailFirstnameParts = preg_split('/[\s-]+/', trim(removeAccents($xlsxUserData['firstname'])), -1, PREG_SPLIT_NO_EMPTY);
            $emailFirstname = !empty($emailFirstnameParts[0]) ? strtolower($emailFirstnameParts[0]) : '';

            $baseEmail = "{$emailLastname}.{$emailFirstname}@{$domain}";
            $generatedEmail = $baseEmail;
            $suffix = isset($generatedEmailCounts[$baseEmail]) ? count($generatedEmailCounts[$baseEmail]) + 1 : 1;
            if ($suffix > 1) {
                $generatedEmail = "{$emailLastname}.{$emailFirstname}{$suffix}@{$domain}";
            }
            $generatedEmail = strtoupper($generatedEmail);
            $generatedEmailCounts[$baseEmail][] = $rowData;

            $rowData['Mail'] = $generatedEmail;
            $xlsxUserData['email'] = $generatedEmail;
            $emailMissing[] = $rowData;
            $xlsxEmailCounts[$generatedEmail][] = $rowData;
        } elseif (empty($xlsxUserData['email'])) {
            $emailMissing[] = $rowData;
        }

        if (empty($xlsxUserData['lastname'])) {
            $lastnameMissing[] = $rowData;
        }
        // All usernames are generated
        $usernameMissing[] = $rowData;

        $email = strtolower(trim($xlsxUserData['email']));
        $name = normalizeName($xlsxUserData['fullname']);
        if (!empty($email)) {
            $xlsxEmailCounts[$email][] = $rowData;
        }
        if (!empty($xlsxUserData['fullname'])) {
            $xlsxNameCounts[$name][] = $rowData;
        }
    }
}

foreach ($xlsxEmailCounts as $email => $rows) {
    if (count($rows) > 1) {
        $duplicateEmails = array_merge($duplicateEmails, $rows);
    }
}
foreach ($xlsxNameCounts as $name => $rowData) {
    if (count($rowData) > 1) {
        $duplicateNames = array_merge($duplicateNames, $rowData);
    }
}

usort($duplicateEmails, function ($a, $b) {
    return strcmp(strtolower($a['Mail'] ?? ''), strtolower($b['Mail'] ?? ''));
});

usort($duplicateNames, function ($a, $b) {
    return strcmp(normalizeName($a['Nom Prénom'] ?? ''), normalizeName($b['Nom Prénom'] ?? ''));
});

createMissingFieldFile($outputDir . 'email_missing.xlsx', $emailMissing, $outputColumns);
createMissingFieldFile($outputDir . 'lastname_missing.xlsx', $lastnameMissing, $outputColumns);
createMissingFieldFile($outputDir . 'username_missing.xlsx', $usernameMissing, $outputColumns);
createMissingFieldFile($outputDir . 'duplicate_email.xlsx', $duplicateEmails, $outputColumns);
createMissingFieldFile($outputDir . 'duplicate_name.xlsx', $duplicateNames, $outputColumns);

// Process users: compare with database, log decisions, and update/insert if --proceed
echo "\n=== Processing Users ===\n";
$userManager = new UserManager();
$usedLogins = ['logins' => [], 'counts' => []]; // Reset usedLogins to avoid false duplicates
$emptyRowCount = 0;
foreach ($xlsxRows as $rowIndex => $rowData) {
    // Check for empty row
    $isEmpty = true;
    foreach ($rowData as $cell) {
        if (!empty(trim($cell))) {
            $isEmpty = false;
            break;
        }
    }
    if ($isEmpty) {
        $emptyRowCount++;
        if ($emptyRowCount >= 2) {
            echo "Stopping processing: Found two consecutive empty rows at row " . ($rowIndex + 2) . "\n";
            break;
        }
        continue;
    } else {
        $emptyRowCount = 0; // Reset counter if row is not empty
    }

    $xlsxUserData = [];
    foreach ($xlsxColumnMap as $dbField) {
        $xlsxUserData[$dbField] = $rowData[$xlsxColumnIndices[$dbField]] ?? '';
    }

    // Generate username
    $isActive = !empty($xlsxUserData['active']);
    $xlsxUserData['username'] = generateProposedLogin($xlsxUserData['lastname'], $xlsxUserData['firstname'], $isActive, $usedLogins);
    $dbUsername = Database::escape_string($xlsxUserData['username']);

    // Check for existing user by username
    $sql = "SELECT id, firstname, lastname, email, official_code, phone, active
            FROM user
            WHERE username = '$dbUsername'";
    $stmt = $database->query($sql);
    $dbUser = $stmt->fetch();

    // Prepare data for logging and potential import/update
    $xlsxMatricule = $xlsxUserData['official_code'] ?? ''; // Keep leading zeros
    $xlsxActive = !empty($xlsxUserData['active']) ? 1 : 0;

    // Decision logic
    if (empty($dbUser) && empty($xlsxUserData['active'])) {
        echo "Row " . ($rowIndex + 2) . ": Skipped - 'Actif' is empty and no matching user in database (username: $dbUsername)\n";
        continue;
    }

    // Validate required fields
    $requiredFields = ['lastname', 'firstname', 'email'];
    $missingFields = [];
    foreach ($requiredFields as $field) {
        if (empty($xlsxUserData[$field])) {
            $missingFields[] .= $field;
        }
    }

    if (!empty($missingFields)) {
        echo "Row " . ($rowIndex + 2) . ": Skipped - missing fields: " . implode(", ", $missingFields) . " (username: $dbUsername)\n";
        continue;
    }

    if ($dbUser) {
        // Check for updates
        $updates = [];
        if ($dbUser['firstname'] !== $xlsxUserData['firstname']) {
            $updates[] .= "firstname: '" . $dbUser['firstname'] . "' -> '" . $xlsxUserData['firstname'] . "' ";
        }
        if ($dbUser['lastname'] !== $xlsxUserData['lastname']) {
            $updates[] .= "lastname: '" . $dbUser['lastname'] . "' -> '" . $xlsxUserData['lastname'] . "' ";
        }
        if ($dbUser['email'] !== $xlsxUserData['email']) {
            $updates[] .= "email: '" . $dbUser['email'] . "' -> '" . $xlsxUserData['email'] . "' ";
        }
        if ($dbUser['official_code'] !== $xlsxUserData['official_code']) {
            $updates[] .= "official_code: '" . $dbUser['official_code'] . "' -> '" . $xlsxUserData['official_code'] . "' ";
        }
        if ($dbUser['phone'] !== ($xlsxUserData['phone'] ?? '')) {
            $updates[] .= "phone: '" . $dbUser['phone'] . "' -> '" . ($xlsxUserData['phone'] ?? '') . "' ";
        }
        if ($dbUser['active'] != $xlsxActive) {
            $updates[] .= "active: " . $dbUser['active'] . " -> '" . $xlsxActive . "' ";
        }

        if (!empty($updates)) {
            echo "Row " . ($rowIndex + 2) . ": Update - Existing user found, updates needed (username: $dbUsername)\n";
            echo "  Updates: " . implode(', ', $updates) . "\n";
            if ($proceed) {
                try {
                    $user = UserManager::update_user(
                        $dbUser['id'],
                        $xlsxUserData['firstname'],
                        $xlsxUserData['lastname'],
                        $xlsxUserData['username'], // username generated from lastname + firstname's first letter (although it should not change, it is required by the update_user method)
                        null, // password not updated
                        null, // auth_source
                        $xlsxUserData['email'],
                        null, // status
                        $xlsxUserData['official_code'],
                        $xlsxUserData['phone'],
                        null, // picture_uri
                        null, // expiration_date
                        $xlsxActive
                    );
                    if ($user) {
                        // Update extra field 'external_user_id'
                        UserManager::update_extra_field_value($dbUser['id'], 'external_user_id', $xlsxMatricule);
                        echo "  Success: Updated user and external_user_id (username: $dbUsername)\n";
                    } else {
                        echo "  Error: Could not update user (username: $dbUsername)\n";
                    }
                } catch (Exception $e) {
                    echo "  Error: Failed to update user (username: $dbUsername): {$e->getMessage()}\n";
                }
            } else {
                echo "   Sim mode: Updated user and external_user_id (username: $dbUsername)\n";
            }
        } else {
            echo "Row " . ($rowIndex + 2) . ": No action - no changes needed (username: $dbUsername)\n";
        }
    } else {
        // New user, only insert if 'Actif' is true
        echo "Row " . ($rowIndex + 2) . ": Insert new user - No existing user found (username: $dbUsername)\n";
        if ($proceed) {
            try {
                $password = !empty($xlsxUserData['password']) ? $xlsxUserData['password'] : 'temporary_password';
                $userId = $userManager->create_user(
                    $xlsxUserData['firstname'],
                    $xlsxUserData['lastname'],
                    5, // status (5 = student, adjust as needed)
                    $xlsxUserData['email'],
                    $dbUsername,
                    $password,
                    $xlsxUserData['official_code'],
                    '', // language
                    $xlsxUserData['phone'],
                    '',
                    null,
                    null,
                    $xlsxActive,
                    null,
                    null  // creator_id
                );
                if ($userId) {
                    // Add extra field 'external_user_id'
                    $userManager->update_extra_field_value($userId, 'external_user_id', $xlsxMatricule);
                    echo "  Success: Created user and set external_user_id (username: $dbUsername)\n";
                } else {
                    echo "  Error: Could not create user (username: $dbUsername)\n";
                }
            } catch (Exception $e) {
                echo "  Error: Failed to insert user (username: $dbUsername): {$e->getMessage()}\n";
            }
        } else {
            echo "   Sim mode: Inserted user and external_user_id (username: $dbUsername)\n";
        }
    }
}

if (!$proceed) {
    echo "\nUse --proceed to apply changes to the database.\n";
}
else {
    echo "\nImport completed successfully.\n";
}
