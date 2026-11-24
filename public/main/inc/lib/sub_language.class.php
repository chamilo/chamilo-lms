<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\SettingsCurrent;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\QueryBuilder;

/**
 * This is used in some scripts inside tests.
 */
class SubLanguageManager
{

    const SUBLANGUAGE_TRANS_PATH = '../var/translations/';
    const LANGUAGE_TRANS_PATH = '../translations/';

    public function __construct()
    {
    }

    /**
     * Get all the languages.
     *
     * @param bool $onlyActive Whether to return only active languages (default false)
     *
     * @return array All information about sub-language
     */
    public static function getAllLanguages($onlyActive = false)
    {
        $qb = self::languageRowsQueryBuilder()->orderBy('l.englishName', 'ASC');
        if ($onlyActive) {
            $qb->where('l.available = true');
        }

        $all_languages = [];
        foreach ($qb->getQuery()->getArrayResult() as $row) {
            $row = self::toLegacyLanguageRow($row);
            $all_languages[$row['english_name']] = $row;
        }

        return $all_languages;
    }

    /**
     * Every column of the language table, aliased to the snake_case keys the legacy callers read.
     */
    private static function languageRowsQueryBuilder(): QueryBuilder
    {
        return Container::getEntityManager()->createQueryBuilder()
            ->select(
                'l.id AS id',
                'l.originalName AS original_name',
                'l.englishName AS english_name',
                'l.isocode AS isocode',
                'l.available AS available',
                'IDENTITY(l.parent) AS parent_id'
            )
            ->from(Language::class, 'l')
        ;
    }

    /**
     * The raw table row used to come back as strings with a NULL parent: keep that shape.
     */
    private static function toLegacyLanguageRow(array $row): array
    {
        $row['available'] = (string) (int) $row['available'];

        return $row;
    }

    /**
     * Get all files of lang folder (forum.inc.php,gradebook.inc.php,notebook.inc.php).
     *
     * @param string $path           The lang path folder  (/var/www/my_lms/main/lang/spanish)
     * @param bool   $only_main_name true if we only want the "subname" trad4all instead of trad4all.inc.php
     *
     * @return array All file of lang folder
     *
     * Used by the gettext migration scripts under tests/scripts.
     */
    public static function get_lang_folder_files_list($path, $only_main_name = false)
    {
        $content_dir = [];
        if (is_dir($path)) {
            if ($dh = opendir($path)) {
                while (false !== ($file = readdir($dh))) {
                    if ('.' != $file[0] && '.php' == substr($file, -4, strlen($file))) {
                        if ($only_main_name) {
                            if ('' != $file && strpos($file, '.inc.php')) {
                                $content_dir[] = substr($file, 0, strpos($file, '.inc.php'));
                            }
                        } else {
                            $content_dir[] = $file;
                        }
                    }
                }
            }
            closedir($dh);
        }

        return $content_dir;
    }

    /**
     * Get all information of language.
     *
     * @param int $parent_id The parent id(Language father id)
     *
     * @return array All information about language
     */
    public static function get_all_information_of_language($parent_id)
    {
        $row = self::languageRowsQueryBuilder()
            ->where('l.id = :id')
            ->setParameter('id', (int) $parent_id)
            ->getQuery()
            ->getArrayResult()[0] ?? null;

        return null === $row ? [] : self::toLegacyLanguageRow($row);
    }

    /**
     * Get all information of chamilo file.
     *
     * @param string $system_path_file    The chamilo path file (/var/www/chamilo/main/lang/spanish/gradebook.inc.php)
     * @param bool   $get_as_string_index Whether we want to remove the '$' prefix in the results or not
     *
     * @return array Contains all information of chamilo file
     */
    public static function get_all_language_variable_in_file($system_path_file, $get_as_string_index = false)
    {
        $res_list = [];
        if (!is_readable($system_path_file)) {
            return $res_list;
        }
        $info_file = file($system_path_file);
        foreach ($info_file as $line) {
            if ('$' != substr($line, 0, 1)) {
                continue;
            }
            list($var, $val) = explode('=', $line, 2);
            $var = trim($var);
            $val = trim($val);
            if ($get_as_string_index) { //remove the prefix $
                $var = substr($var, 1);
            }
            $res_list[$var] = $val;
        }

        return $res_list;
    }

    /**
     * Add file in sub-language directory and add header(tag php).
     *
     * @param string $system_path_file The chamilo path file (/var/www/chamilo/main/lang/spanish/gradebook.inc.php)
     *
     * @return bool
     */
    public static function add_file_in_language_directory($system_path_file)
    {
        $return_value = @file_put_contents($system_path_file, '<?php'.PHP_EOL);

        return $return_value;
    }

    /**
     * Write in file of sub-language.
     *
     * @param string $path_file    The path file (/var/www/chamilo/main/lang/spanish/gradebook.inc.php)
     * @param string $new_term     The new sub-language
     * @param string $new_variable The language variable
     *
     * @return bool True on success, False on error
     */
    public static function write_data_in_file($path_file, $new_term, $new_variable)
    {
        $return_value = false;
        $new_data = $new_variable.'='.$new_term;
        $resource = @fopen($path_file, "a");
        if (file_exists($path_file) && $resource) {
            if (false === fwrite($resource, $new_data.PHP_EOL)) {
                //not allow to write
                $return_value = false;
            } else {
                $return_value = true;
            }
            fclose($resource);
        }

        return $return_value;
    }

    /**
     * Add a .po file for a sub-language using its ISO code.
     *
     * @param string $subLanguageIsoCode The ISO code of the sub-language (e.g., 'es_CO')
     *
     * @return bool True on success, false on failure
     */
    public static function addPoFileForSubLanguage($subLanguageIsoCode)
    {
        if (empty($subLanguageIsoCode)) {
            return false;
        }

        // Path for the .po file you want to create
        $poFilePath = api_get_path(SYS_PATH) . self::SUBLANGUAGE_TRANS_PATH . 'messages.' . $subLanguageIsoCode . '.po';
        $translationsDir = dirname($poFilePath);

        // Check if the translations directory is writable
        if (!is_writable($translationsDir)) {
            // Attempt to set writable permissions
            if (!@chmod($translationsDir, 0775)) {
                error_log("Failed to set writable permissions for $translationsDir");
                return false;
            }
        }

        // If the .po file doesn't exist, create it
        if (!file_exists($poFilePath)) {
            $initialContent = "# Translation file for $subLanguageIsoCode\nmsgid \"\"\nmsgstr \"\"\n";
            if (false === file_put_contents($poFilePath, $initialContent)) {
                error_log("Failed to write the initial content to $poFilePath");
                return false;
            }
        }

        return true;
    }

    /**
     * Get name of language by id.
     *
     * @param int $language_id The language id
     *
     * @return string The original name of language
     */
    public static function get_name_of_language_by_id($language_id)
    {
        $rows = Container::getEntityManager()->createQueryBuilder()
            ->select('l.originalName AS original_name')
            ->from(Language::class, 'l')
            ->where('l.id = :id')
            ->setParameter('id', (int) $language_id)
            ->getQuery()
            ->getArrayResult()
        ;

        return [] === $rows ? '' : $rows[0]['original_name'];
    }

    /**
     * Verified if language is sub-language.
     *
     * @param int $language_id
     *
     * @return bool
     */
    public static function check_if_language_is_sub_language($language_id)
    {
        $count = Container::getEntityManager()->createQueryBuilder()
            ->select('COUNT(l.id)')
            ->from(Language::class, 'l')
            ->where('l.id = :id')
            ->andWhere('l.parent IS NOT NULL')
            ->setParameter('id', (int) $language_id)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return 1 === (int) $count;
    }

    /**
     * @param int $language_id
     *
     * @return bool
     */
    public static function check_if_language_is_used(int $language_id): bool
    {
        $language_info = self::get_all_information_of_language($language_id);
        $count = Container::getEntityManager()->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(User::class, 'u')
            ->where('u.locale = :locale')
            ->andWhere('u.active <> :softDeleted')
            ->setParameter('locale', $language_info['isocode'] ?? '')
            ->setParameter('softDeleted', USER_SOFT_DELETED)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $count >= 1;
    }

    /**
     * Verified if language is father of an sub-language.
     *
     * @param int $language_id The language id
     *
     * @return bool
     */
    public static function check_if_language_is_father($language_id)
    {
        $count = Container::getEntityManager()->createQueryBuilder()
            ->select('COUNT(l.id)')
            ->from(Language::class, 'l')
            ->where('IDENTITY(l.parent) = :id')
            ->setParameter('id', (int) $language_id)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $count >= 1;
    }

    /**
     * Make unavailable the language.
     *
     * @param int $language_id The language id
     *
     * @return bool
     */
    public static function make_unavailable_language($language_id)
    {
        Container::getEntityManager()->createQueryBuilder()
            ->update(Language::class, 'l')
            ->set('l.available', ':available')
            ->where('l.id = :id')
            ->setParameter('available', false)
            ->setParameter('id', (int) $language_id)
            ->getQuery()
            ->execute()
        ;

        return true;
    }

    /**
     * Make available the language.
     *
     * @param int $language_id language id
     *
     * @return bool
     */
    public static function make_available_language($language_id)
    {
        Container::getEntityManager()->createQueryBuilder()
            ->update(Language::class, 'l')
            ->set('l.available', ':available')
            ->where('l.id = :id')
            ->setParameter('available', true)
            ->setParameter('id', (int) $language_id)
            ->getQuery()
            ->execute()
        ;

        return true;
    }

    /**
     * Set platform language.
     *
     * @param int $language_id The language id
     *
     * @return bool
     */
    public static function set_platform_language($language_id)
    {
        if (empty($language_id) || (intval($language_id) != $language_id)) {
            return false;
        }
        $language_id = intval($language_id);
        $lang = self::get_all_information_of_language($language_id);
        Container::getEntityManager()->createQueryBuilder()
            ->update(SettingsCurrent::class, 's')
            ->set('s.selectedValue', ':isocode')
            ->where('s.variable = :variable')
            ->setParameter('isocode', $lang['isocode'])
            ->setParameter('variable', 'platform_language')
            ->getQuery()
            ->execute()
        ;
        Event::addEvent(
            LOG_PLATFORM_LANGUAGE_CHANGE,
            LOG_PLATFORM_LANGUAGE,
            $lang['english_name']
        );

        return true;
    }

    /**
     * Get platform language ID.
     *
     * @return int The platform language ID
     */
    public static function get_platform_language_id()
    {
        $rows = Container::getEntityManager()->createQueryBuilder()
            ->select('l.id')
            ->from(Language::class, 'l')
            ->where('l.isocode = :isocode')
            ->setParameter('isocode', (string) api_get_setting('language.platform_language'))
            ->getQuery()
            ->getArrayResult()
        ;

        return [] === $rows ? false : (int) $rows[0]['id'];
    }

    /**
     * Convert a string to a valid PHP camelCase variable name.
     *
     * @param string $string
     * @return string
     */
    public static function stringToCamelCaseVariableName($string)
    {
        $varName = preg_replace('/[^a-z0-9_]/i', '_', $string);  // Replace invalid characters with '_'
        $varName = trim($varName, '_');  // Trim any '_' from the beginning and end
        $varName = ucwords(str_replace('_', ' ', $varName));  // Convert to camel case
        $varName = lcfirst(str_replace(' ', '', $varName));  // Remove spaces and convert the first character to lowercase
        return substr($varName, 0, 25);  // Limit to 15 characters
    }

    /**
     * Retrieve the iso_code for a given language ID and its parent.
     *
     * @param int $languageId
     * @return array [childIsoCode, parentIsoCode]
     *
     * Called by searchTranslations(), which public/main/admin/sub_language.php uses.
     */
    public static function getIsoCodes($languageId)
    {
        $em = Database::getManager();
        $language = $em->getRepository('Chamilo\CoreBundle\Entity\Language')->find($languageId);

        if (!$language) {
            return [null, null];
        }

        $childIsoCode = $language->getIsoCode();
        $parentIsoCode = null;

        if ($language->getParent()) {
            $parentLanguage = $em->getRepository('Chamilo\CoreBundle\Entity\Language')->find($language->getParent());
            if ($parentLanguage) {
                $parentIsoCode = $parentLanguage->getIsoCode();
            }
        }

        return [$childIsoCode, $parentIsoCode];
    }

    /**
     * Search for translations based on a term and language ID.
     *
     * @param string $term        The term to search for.
     * @param int    $languageId  The ID of the language to search in.
     *
     * @return array An array of matched translations.
     *
     * Called by public/main/admin/sub_language.php (search_language_term()).
     */
    public static function searchTranslations($term, $languageId): array
    {
        // Retrieve the ISO codes for the provided language ID.
        list($childIsoCode, $parentIsoCode) = self::getIsoCodes($languageId);

        // Define the files to search in based on the ISO codes.
        $files = ['en' => 'messages.en.po', $parentIsoCode => "messages.$parentIsoCode.po", $childIsoCode => "messages.$childIsoCode.po"];

        $results = [];

        // Step 1: Search for all matches in messages.en.po.
        $matchedMsgids = self::searchMsgidInFile($term, $files['en']);

        // Step 2: For each matched msgid, search for its translation in the other files.
        foreach ($matchedMsgids as $msgid) {
            $entry = [
                'file' => $files['en'],
                'variable' => $msgid,
                'phpVarName' => self::stringToCamelCaseVariableName($msgid),
                'en' => self::getTranslationForVariable($msgid, $files['en'])
            ];
            $entry[$parentIsoCode] = self::getTranslationForVariable($msgid, $files[$parentIsoCode]);
            $entry[$childIsoCode] = self::getTranslationForVariable($msgid, $files[$childIsoCode], true);

            $results[] = $entry;
        }

        return $results;
    }

    /**
     * Search for a specific term inside a given .po file and return the msgids that match.
     *
     * @param string $term      The term to search for.
     * @param string $filename  The name of the .po file to search in.
     *
     * @return array An array of msgids that match the given term.
     */
    private static function searchMsgidInFile($term, $filename)
    {
        $poFilePath = api_get_path(SYS_PATH) . self::LANGUAGE_TRANS_PATH . $filename;
        $matchedMsgids = [];

        if (file_exists($poFilePath)) {
            $lines = file($poFilePath, FILE_IGNORE_NEW_LINES);
            $currentVariable = null;

            foreach ($lines as $line) {
                if (strpos($line, 'msgid "') === 0) {
                    $currentVariable = str_replace('msgid "', '', $line);
                    $currentVariable = rtrim($currentVariable, '"');

                    if (stripos($currentVariable, $term) !== false) {
                        $matchedMsgids[] = $currentVariable;
                    }
                }
            }
        }

        return $matchedMsgids;
    }

    /**
     * Retrieve the translation (msgstr) for a given variable (msgid) from a specified .po file.
     *
     * @param string $variable  The variable (msgid) to search for.
     * @param string $filename  The name of the .po file to retrieve the translation from.
     *
     * @return string The translation (msgstr) for the provided variable, or an empty string if not found.
     */
    private static function getTranslationForVariable(string $variable, string $filename, $checkSubLanguagePath = false): string
    {
        $poFilePath = self::getPoFilePath($filename, $checkSubLanguagePath);
        if (!file_exists($poFilePath)) {
            $shortLanguageCode = self::getShortLanguageCode($filename);
            $poFilePath = self::getPoFilePath($shortLanguageCode . '.po', $checkSubLanguagePath);

            if (!file_exists($poFilePath)) {
                return '';
            }
        }

        $content = file_get_contents($poFilePath);
        $pattern = '/msgid "' . preg_quote($variable, '/') . '"\nmsgstr "(.*?)"/';
        if (preg_match($pattern, $content, $match)) {
            return $match[1];
        }

        return '';
    }

    private static function getPoFilePath(string $filename, bool $checkSubLanguagePath): string
    {
        $path = $checkSubLanguagePath ? self::SUBLANGUAGE_TRANS_PATH : self::LANGUAGE_TRANS_PATH;
        return api_get_path(SYS_PATH) . $path . $filename;
    }

    private static function getShortLanguageCode(string $filename): string
    {
        $parts = explode('.', $filename);
        $languageCodeParts = explode('_', $parts[1]);
        return $parts[0].'.'.$languageCodeParts[0];
    }

    /**
     * Updates or adds a msgid in the specified .po file.
     *
     * @param string $filename  Name of the .po file
     * @param string $msgid     Message identifier to search or add
     * @param string $content   Associated message content
     *
     * @return array Returns true if the operation was successful, otherwise returns false
     */
    public static function updateOrAddMsgid($filename, $msgid, $content): array
    {
        // Strip any directory component and enforce the expected .po naming so a
        // crafted filename (e.g. "../../public/shell.php") cannot escape the
        // translations directory and overwrite an arbitrary file.
        $filename = basename((string) $filename);
        if (1 !== preg_match('/^messages\.[A-Za-z0-9_]+\.po$/', $filename)) {
            return ['success' => false, 'error' => 'Invalid filename'];
        }

        $filePath = api_get_path(SYS_PATH) . self::SUBLANGUAGE_TRANS_PATH .  $filename;

        if (!file_exists($filePath)) {
            return ['success' => false, 'error' => 'File does not exist'];
        }

        if (!is_writable($filePath)) {
            try {
                if (!chmod($filePath, 0664)) {
                    return ['success' => false, 'error' => 'Unable to set the file to writable'];
                }
            } catch (Exception $e) {

                return ['success' => false, 'error' => 'Failed to change file permissions: ' . $e->getMessage()];
            }
        }

        $fileContents = file_get_contents($filePath);
        if ($fileContents === false) {
            return ['success' => false, 'error' => 'Failed to read file contents'];
        }

        $pattern = '/msgid "' . preg_quote($msgid, '/') . '"' . PHP_EOL . 'msgstr "(.*?)"/';
        if (preg_match($pattern, $fileContents)) {
            $replacement = 'msgid "' . $msgid . '"' . PHP_EOL . 'msgstr "' . $content . '"';
            $fileContents = preg_replace($pattern, $replacement, $fileContents);
        } else {
            $appendString = PHP_EOL . PHP_EOL . 'msgid "' . $msgid . '"' . PHP_EOL . 'msgstr "' . $content . '"';
            $fileContents .= $appendString;
        }

        if (file_put_contents($filePath, $fileContents) === false) {
            return ['success' => false, 'error' => 'Failed to write to file'];
        }

        return ['success' => true];
    }

    /**
     * Delete sub-language.
     * In order to avoid deletion of main languages, we check the existence of a parent.
     */
    public static function removeSubLanguage(int $parentId, int $subLanguageId): bool
    {
        $entityManager = Database::getManager();
        $subLanguage = $entityManager->getRepository(Language::class)->find($subLanguageId);
        $parentLanguage = $subLanguage ? $subLanguage->getParent() : null;

        if (!$subLanguage || !$parentLanguage || $parentLanguage->getId() != $parentId) {
            return false;
        }

        // Locate and delete the .po file of the sub-language
        $subLanguageIsoCode = $subLanguage->getIsocode();
        $poFilePath = api_get_path(SYS_PATH) . self::SUBLANGUAGE_TRANS_PATH .  "messages.$subLanguageIsoCode.po";
        if (file_exists($poFilePath)) {
             unlink($poFilePath);
        }

        $entityManager->remove($subLanguage);
        $entityManager->flush();

        return true;
    }

    /**
     * Add a sub-language.
     *
     * @throws Exception
     */
    public static function addSubLanguage(string $originalName, string $englishName, bool $isAvailable, int $parentId, string $isoCode): Language
    {
        $entityManager = Database::getManager();
        $parentLanguage = $entityManager->getRepository(Language::class)->find($parentId);
        if (!$parentLanguage) {
            throw new Exception();
        }

        $subLanguage = new Language();
        $subLanguage->setOriginalName($originalName)
            ->setEnglishName($englishName)
            ->setIsocode($isoCode)
            ->setAvailable($isAvailable)
            ->setParent($parentLanguage);

        $entityManager->persist($subLanguage);
        $entityManager->flush();

        return $subLanguage;
    }

    /**
     * Remove a .po file for a sub-language.
     *
     * @param string $isoCode The ISO code of the sub-language (e.g., 'es_CO')
     *
     * @return bool True on success, false on failure
     */
    public static function removePoFileForSubLanguage(string $isoCode): bool
    {
        if (empty($isoCode)) {
            return false;
        }

        // Path for the .po file you want to remove
        $poFilePath = api_get_path(SYS_PATH) . self::SUBLANGUAGE_TRANS_PATH . "messages.$isoCode.po";

        if (file_exists($poFilePath)) {
            return unlink($poFilePath);
        }

        // File does not exist, consider it a successful removal
        return true;
    }

    /**
     * Check if a language exists by its ID.
     */
    public static function languageExistsById(int $languageId): bool
    {
        $entityManager = Database::getManager();
        try {
            $language = $entityManager->getRepository(Language::class)->find($languageId);

            return $language !== null;
        } catch (NotSupported) {
            return false;
        }
    }

    /**
     * Check if the given language is a parent of any sub-language.
     */
    public static function isParentOfSubLanguage(int $parentId): bool
    {
        $entityManager = Database::getManager();
        $languageRepository = $entityManager->getRepository(Language::class);

        $childrenCount = $languageRepository->count(['parent' => $parentId]);

        return $childrenCount > 0;
    }

    /**
     * Get all information of a sub-language.
     */
    public static function getAllInformationOfSubLanguage(int $parentId, int $subLanguageId): ?Language
    {
        $entityManager = Database::getManager();
        try {
            $languageRepository = $entityManager->getRepository(Language::class);

            return $languageRepository->findOneBy([
                'parent' => $parentId,
                'id' => $subLanguageId
            ]);
        } catch (NotSupported) {
            return null;
        }
    }

    /**
     * Convert a Language entity to an array.
     */
    private static function convertLanguageToArray(Language $language): array
    {
        return [
            'id' => $language->getId(),
            'original_name' => $language->getOriginalName(),
            'english_name' => $language->getEnglishName(),
            'isocode' => $language->getIsocode(),
            'available' => $language->getAvailable(),
            // Add other fields as needed
        ];
    }

    /**
     * Check if a language exists.
     */
    public static function checkIfLanguageExists(string $originalName, string $englishName, string $isoCode): array
    {
        $entityManager = Database::getManager();
        $languageRepository = $entityManager->getRepository(Language::class);

        $messageInformation = [
            'original_name' => false,
            'english_name' => false,
            'isocode' => false,
            'execute_add' => true
        ];

        if ($languageRepository->count(['originalName' => $originalName]) > 0) {
            $messageInformation['original_name'] = true;
            $messageInformation['execute_add'] = false;
        }

        if ($languageRepository->count(['englishName' => $englishName]) > 0) {
            $messageInformation['english_name'] = true;
            $messageInformation['execute_add'] = false;
        }

        $isoList = api_get_platform_isocodes(); // Assuming this is an existing function
        if (!in_array($isoCode, array_values($isoList))) {
            $messageInformation['isocode'] = true;
            $messageInformation['execute_add'] = false;
        }

        return $messageInformation;
    }

    /**
     * Gets the ISO code of the parent language for a given language.
     */
    public static function getParentLocale(string $childIsoCode): ?string
    {
        // Installation-safe: database manager may not be initialized yet.
        if (!class_exists('Database') || !Database::hasManager()) {
            return null;
        }

        try {
            $em = Database::getManager();
            $languageRepository = $em->getRepository(Language::class);

            $language = $languageRepository->findOneBy(['isocode' => $childIsoCode]);
            if (null === $language) {
                return null;
            }

            $parent = $language->getParent();
            if (null === $parent) {
                return null;
            }

            return $parent->getIsocode();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function generateSublanguageCode(string $parentCode, string $variant, int $maxLength = Language::ISO_MAX_LENGTH): string
    {
        $parentCode = strtolower(trim($parentCode));
        $variant = strtolower(trim($variant));

        // Generate a variant code by truncating the variant name
        $variantCode = substr($variant, 0, $maxLength - strlen($parentCode) - 1);

        // Build the complete code
        $fullCode = substr($parentCode . '_' . $variantCode, 0, $maxLength);

        return $fullCode;
    }
}
