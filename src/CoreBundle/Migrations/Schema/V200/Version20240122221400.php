<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;
use SubLanguageManager;
use Symfony\Component\Process\Process;

use const FILE_APPEND;

final class Version20240122221400 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migration of sublanguages and Vue translation updates.';
    }

    public function up(Schema $schema): void
    {
        // Default sublanguages to be excluded from the update.
        $defaultSubLanguages = ['ast', 'ast_ES', 'ca', 'ca_ES', 'eo', 'gl', 'qu', 'quz_PE', 'qu_PE', 'zh-TW', 'zh_TW', 'pt-BR', 'pt_PT', 'fur', 'fur_IT', 'oc', 'oc_FR'];

        // Fetching sublanguages from the database.
        $sql = "SELECT * FROM language WHERE parent_id IS NOT NULL AND isocode NOT IN('".implode("', '", $defaultSubLanguages)."')";
        $sublanguages = $this->connection->executeQuery($sql)->fetchAllAssociative();

        foreach ($sublanguages as $sublanguage) {
            $newIsoCode = $this->updateAndGenerateSubLanguage($sublanguage);
            $this->generatePoFileFromTrad4All($sublanguage['english_name'], $newIsoCode);
        }

        // Update Vue translations after processing all sublanguages.
        $this->executeVueTranslationsUpdate();

        // Delete the 'import' folder at the end of the process.
        // $this->deleteImportFolder();
    }

    private function updateAndGenerateSubLanguage(array $sublanguage): string
    {
        // Get the parent language ID
        $parentId = $sublanguage['parent_id'];

        // Query to obtain the isocode of the parent language
        $parentIsoQuery = 'SELECT isocode FROM language WHERE id = ?';
        $parentIsoCode = $this->connection->executeQuery($parentIsoQuery, [$parentId])->fetchOne();

        // Get the prefix of the parent language's isocode
        $firstIso = substr($parentIsoCode, 0, 2);
        $newIsoCode = $this->generateSublanguageCode($firstIso, $sublanguage['english_name']);

        // Update the isocode in the language table
        $updateLanguageQuery = 'UPDATE language SET isocode = ? WHERE id = ?';
        $this->connection->executeStatement($updateLanguageQuery, [$newIsoCode, $sublanguage['id']]);
        error_log('Updated language table for id '.$sublanguage['id']);

        // Check and update in settings
        $updateSettingsQuery = "UPDATE settings SET selected_value = ? WHERE variable = 'platform_language' AND selected_value = ?";
        $this->connection->executeStatement($updateSettingsQuery, [$newIsoCode, $sublanguage['english_name']]);
        error_log('Updated settings for language '.$sublanguage['english_name']);

        // Check and update in user table
        $updateUserQuery = 'UPDATE user SET locale = ? WHERE locale = ?';
        $this->connection->executeStatement($updateUserQuery, [$newIsoCode, $sublanguage['english_name']]);
        error_log('Updated user table for language '.$sublanguage['english_name']);

        // Check and update in course table
        $updateCourseQuery = 'UPDATE course SET course_language = ? WHERE course_language = ?';
        $this->connection->executeStatement($updateCourseQuery, [$newIsoCode, $sublanguage['english_name']]);
        error_log('Updated course table for language '.$sublanguage['english_name']);

        // Return the new ISO code.
        return $newIsoCode;
    }

    private function generatePoFileFromTrad4All(string $englishName, string $isocode): void
    {
        $kernel = $this->container->get('kernel');
        $updateRootPath = $this->getUpdateRootPath();

        $langPath = $updateRootPath.'/main/lang/'.$englishName.'/trad4all.inc.php';
        $destinationFilePath = $kernel->getProjectDir().'/var/translations/messages.'.$isocode.'.po';
        $originalFile = $updateRootPath.'/main/lang/english/trad4all.inc.php';

        if (!file_exists($langPath)) {
            error_log("Original file not found: $langPath");

            return;
        }

        $terms = SubLanguageManager::get_all_language_variable_in_file(
            $originalFile,
            true
        );

        foreach ($terms as $index => $translation) {
            $terms[$index] = trim(rtrim($translation, ';'), '"');
        }

        $header = 'msgid ""'."\n".'msgstr ""'."\n".
            '"Project-Id-Version: chamilo\n"'."\n".
            '"Language: '.$isocode.'\n"'."\n".
            '"Content-Type: text/plain; charset=UTF-8\n"'."\n".
            '"Content-Transfer-Encoding: 8bit\n"'."\n\n";
        file_put_contents($destinationFilePath, $header);

        $originalTermsInLanguage = SubLanguageManager::get_all_language_variable_in_file(
            $langPath,
            true
        );

        $termsInLanguage = [];
        foreach ($originalTermsInLanguage as $id => $content) {
            if (!isset($termsInLanguage[$id])) {
                $termsInLanguage[$id] = trim(rtrim($content, ';'), '"');
            }
        }

        $bigString = '';
        $doneTranslations = [];
        foreach ($terms as $term => $englishTranslation) {
            if (isset($doneTranslations[$englishTranslation])) {
                continue;
            }
            $doneTranslations[$englishTranslation] = true;
            $translatedTerm = $termsInLanguage[$term] ?? '';

            // Here we apply a little correction to avoid unterminated strings
            // when a string ends with a \"
            if (preg_match('/\\\$/', $englishTranslation)) {
                $englishTranslation .= '"';
            }

            $search = ['\{', '\}', '\(', '\)', '\;'];
            $replace = ['\\\{', '\\\}', '\\\(', '\\\)', '\\\;'];
            $englishTranslation = str_replace($search, $replace, $englishTranslation);
            if (preg_match('/\\\$/', $translatedTerm)) {
                $translatedTerm .= '"';
            }
            $translatedTerm = str_replace($search, $replace, $translatedTerm);
            if (empty($translatedTerm)) {
                continue;
            }
            // Now build the line
            $bigString .= 'msgid "'.$englishTranslation.'"'."\n".'msgstr "'.$translatedTerm.'"'."\n\n";
        }
        file_put_contents($destinationFilePath, $bigString, FILE_APPEND);

        error_log("Done generating gettext file in $destinationFilePath !\n");
    }

    private function executeVueTranslationsUpdate(): void
    {
        $process = new Process(['php', 'bin/console', 'chamilo:update_vue_translations']);
        $process->run();

        if (!$process->isSuccessful()) {
            // throw new RuntimeException($process->getErrorOutput());
        }

        error_log($process->getOutput());
    }

    private function generateSublanguageCode(string $parentCode, string $variant, int $maxLength = 10): string
    {
        $parentCode = strtolower(trim($parentCode));
        $variant = strtolower(trim($variant));

        // Generate a variant code by truncating the variant name
        $variantCode = substr($variant, 0, $maxLength - \strlen($parentCode) - 1);

        // Build the complete code
        return substr($parentCode.'_'.$variantCode, 0, $maxLength);
    }

    private function deleteImportFolder(): void
    {
        $kernel = $this->container->get('kernel');
        $rootPath = $kernel->getProjectDir();
        $importPath = $rootPath.'/var/translations/import/';

        $this->recursiveRemoveDirectory($importPath);
    }

    private function recursiveRemoveDirectory($directory): void
    {
        foreach (glob("{$directory}/*") as $file) {
            if (is_dir($file)) {
                $this->recursiveRemoveDirectory($file);
            } else {
                unlink($file);
            }
        }
        rmdir($directory);
    }

    public function down(Schema $schema): void {}
}
