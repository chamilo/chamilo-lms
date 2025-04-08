<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

use const FILE_IGNORE_NEW_LINES;
use const PHP_EOL;

final class Version20240806120000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate settings from configuration.php to .env and settings_overrides.yaml files';
    }

    public function up(Schema $schema): void
    {
        global $_configuration;

        $rootPath = $this->getRootPath();
        $updateRootPath = $this->getUpdateRootPath();
        $oldConfigPath = $updateRootPath.'/app/config/configuration.php';
        if (!\in_array($oldConfigPath, get_included_files(), true)) {
            include_once $oldConfigPath;
        }

        // Update .env and .env.local files
        $this->updateEnvFiles($rootPath, [
            'DB_MANAGER_ENABLED' => !empty($_configuration['db_manager_enabled']) ? '1' : '0',
            'SOFTWARE_NAME' => $_configuration['software_name'] ?? '',
            'SOFTWARE_URL' => $_configuration['software_url'] ?? '',
            'DENY_DELETE_USERS' => !empty($_configuration['deny_delete_users']) ? '1' : '0',
            'HOSTING_TOTAL_SIZE_LIMIT' => $_configuration['hosting_total_size_limit'] ?? 0,
            'THEME_FALLBACK' => $_configuration['theme_fallback'] ?? 'chamilo',
            'PACKAGER' => $_configuration['packager'] ?? 'chamilo',
            'DEFAULT_TEMPLATE' => $_configuration['default_template'] ?? 'default',
            'ADMIN_CHAMILO_ANNOUNCEMENTS_DISABLE' => !empty($_configuration['admin_chamilo_announcements_disable']) ? '1' : '0',
            'WEBSERVICE_ENABLE' => !empty($_configuration['webservice_enable']) ? '1' : '0',
        ]);

        // Ensure the settings_overrides.yaml file exists
        $hostingLimitsFile = $rootPath.'/config/settings_overrides.yaml';
        $hostingLimits = ['settings_overrides' => []];

        // Prepare hosting limits
        if (\is_array($_configuration)) {
            foreach ($_configuration as $key => $config) {
                if (is_numeric($key) && \is_array($config)) {
                    // Handle configurations specific to URL IDs
                    $hostingLimits['settings_overrides'][$key]['hosting_limit'] = [
                        ['users' => $config['hosting_limit_users'] ?? 0],
                        ['teachers' => $config['hosting_limit_teachers'] ?? 0],
                        ['courses' => $config['hosting_limit_courses'] ?? 0],
                        ['sessions' => $config['hosting_limit_sessions'] ?? 0],
                        ['disk_space' => $config['hosting_limit_disk_space'] ?? 0],
                        ['active_courses' => $config['hosting_limit_active_courses'] ?? 0],
                        ['total_size' => $_configuration['hosting_total_size_limit'] ?? 0],
                    ];
                }
            }
        }

        // Format hosting limits as YAML
        $yamlContent = "parameters:\n    settings_overrides:\n";
        foreach ($hostingLimits['settings_overrides'] as $urlId => $limits) {
            $yamlContent .= "        {$urlId}:\n            hosting_limit:\n";
            foreach ($limits as $limit) {
                foreach ($limit as $key => $value) {
                    $yamlContent .= "                $key: $value\n";
                }
            }
        }

        // Write hosting limits to settings_overrides.yaml
        file_put_contents($hostingLimitsFile, $yamlContent);
    }

    public function down(Schema $schema): void {}

    private function getRootPath(): string
    {
        return $this->container->getParameter('kernel.project_dir');
    }

    private function updateEnvFiles(string $rootPath, array $envSettings): void
    {
        $envFiles = [$rootPath.'/.env'];

        foreach ($envFiles as $envFile) {
            if (file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES);
                $updatedLines = [];
                $existingKeys = [];

                foreach ($lines as $line) {
                    if (str_contains($line, '=')) {
                        [$key, $value] = explode('=', $line, 2);
                        $key = trim($key);
                        if (\array_key_exists($key, $envSettings)) {
                            $value = $envSettings[$key];
                            unset($envSettings[$key]);
                        }
                        $updatedLines[] = "{$key}={$value}";
                        $existingKeys[] = $key;
                    } else {
                        $updatedLines[] = $line;
                    }
                }

                // Add remaining new settings
                foreach ($envSettings as $key => $value) {
                    if (!\in_array($key, $existingKeys)) {
                        $updatedLines[] = "{$key}={$value}";
                    }
                }

                file_put_contents($envFile, implode(PHP_EOL, $updatedLines).PHP_EOL);
            } else {
                // If the file does not exist, create it with the settings
                $newContent = [];
                foreach ($envSettings as $key => $value) {
                    $newContent[] = "{$key}={$value}";
                }
                file_put_contents($envFile, implode(PHP_EOL, $newContent).PHP_EOL);
            }
        }
    }
}
