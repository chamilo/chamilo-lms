<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Language;
use Doctrine\ORM\Exception\NotSupported;

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
        $table = Database::get_main_table(TABLE_MAIN_LANGUAGE);
        $sql = 'SELECT * FROM '.$table;
        if ($onlyActive) {
            $sql .= ' WHERE available = 1';
        }
        $sql .= ' ORDER BY english_name ASC';

        $rs = Database::query($sql);
        $all_languages = [];
        while ($row = Database::fetch_assoc($rs)) {
            $all_languages[$row['english_name']] = $row;
        }

        return $all_languages;
    }

    /**
     * Get all files of lang folder (forum.inc.php,gradebook.inc.php,notebook.inc.php).
     *
     * @param string $path           The lang path folder  (/var/www/my_lms/main/lang/spanish)
     * @param bool   $only_main_name true if we only want the "subname" trad4all instead of trad4all.inc.php
     *
     * @return array All file of lang folder
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
        $table = Database::get_main_table(TABLE_MAIN_LANGUAGE);
        $sql = 'SELECT * FROM '.$table.' WHERE id = "'.intval($parent_id).'"';
        $rs = Database::query($sql);
        $all_information = [];
        while ($row = Database::fetch_assoc($rs)) {
            $all_information = $row;
        }

        return $all_information;
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
        $table = Database::get_main_table(TABLE_MAIN_LANGUAGE);
        $language_id = intval($language_id);
        $sql = "SELECT original_name
                FROM $table
                WHERE id = $language_id";
        $rs = Database::query($sql);
        if (Database::num_rows($rs) > 0) {
            return Database::result($rs, 0, 'original_name');
        } else {
            return '';
        }
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
        $table = Database::get_main_table(TABLE_MAIN_LANGUAGE);
        $sql = 'SELECT count(*) AS count FROM '.$table.'
                WHERE id = '.intval($language_id).' AND NOT ISNULL(parent_id)';
        $rs = Database::query($sql);

        if (Database::num_rows($rs) > 0 && 1 == Database::result($rs, '0', 'count')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param int $language_id
     *
     * @return bool
     */
    public static function check_if_language_is_used(int $language_id): bool
    {
        $language_info = self::get_all_information_of_language($language_id);
        $table = Database::get_main_table(TABLE_MAIN_USER);
        $sql = 'SELECT count(*) AS count FROM '.$table.'
                WHERE locale ="'.Database::escape_string($language_info['english_name']).'" AND active <> '.USER_SOFT_DELETED;
        $rs = Database::query($sql);
        if (Database::num_rows($rs) > 0 && Database::result($rs, '0', 'count') >= 1) {
            return true;
        } else {
            return false;
        }
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
        $table = Database::get_main_table(TABLE_MAIN_LANGUAGE);
        $sql = 'SELECT count(*) AS count FROM '.$table.'
                WHERE parent_id= '.intval($language_id).' AND NOT ISNULL(parent_id);';
        $rs = Database::query($sql);

        if (Database::num_rows($rs) > 0 && 1 == Database::result($rs, '0', 'count')) {
            return true;
        } else {
            return false;
        }
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
        $tbl_admin_languages = Database::get_main_table(TABLE_MAIN_LANGUAGE);
        $sql = "UPDATE $tbl_admin_languages SET available='0'
                WHERE id = ".intval($language_id)."";
        $result = Database::query($sql);

        return false !== $result; //only return false on sql error
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
        $tbl_admin_languages = Database::get_main_table(TABLE_MAIN_LANGUAGE);
        $sql = "UPDATE $tbl_admin_languages SET available='1'
                WHERE id = ".intval($language_id)."";
        $result = Database::query($sql);

        return false !== $result; //only return false on sql error
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
        $tbl_admin_languages = Database::get_main_table(TABLE_MAIN_LANGUAGE);
        $tbl_settings_current = Database::get_main_table(TABLE_MAIN_SETTINGS);
        $sql = "SELECT * FROM $tbl_admin_languages
                WHERE id = $language_id";
        $result = Database::query($sql);
        $lang = Database::fetch_array($result);
        $sql_update_2 = "UPDATE $tbl_settings_current SET selected_value = '".$lang['isocode']."'
                         WHERE variable = 'platform_language'";
        $result_2 = Database::query($sql_update_2);
        Event::addEvent(
            LOG_PLATFORM_LANGUAGE_CHANGE,
            LOG_PLATFORM_LANGUAGE,
            $lang['english_name']
        );

        return false !== $result_2;
    }

    /**
     * Get platform language ID.
     *
     * @return int The platform language ID
     */
    public static function get_platform_language_id()
    {
        $name = api_get_setting('platformLanguage');
        $tbl_admin_languages = Database::get_main_table(TABLE_MAIN_LANGUAGE);
        $sql = "SELECT id FROM $tbl_admin_languages WHERE english_name ='$name'";
        $res = Database::query($sql);
        if (Database::num_rows($res) < 1) {
            return false;
        }
        $row = Database::fetch_array($res);

        return (int) $row['id'];
    }

    /**
     * Get parent language path (or null if no parent).
     *
     * @deprecated
     *
     * @param string $language_path Children language path
     *
     * @return string Parent language path or null
     */
    public static function get_parent_language_path($language_path)
    {
        $tbl_admin_languages = Database::get_main_table(TABLE_MAIN_LANGUAGE);
        $sql = "SELECT dokeos_folder
                FROM ".$tbl_admin_languages."
                WHERE id = (
                    SELECT parent_id FROM ".$tbl_admin_languages."
                    WHERE dokeos_folder = '".Database::escape_string($language_path)."'
                )
                ";
        $result = Database::query($sql);
        if (0 == Database::num_rows($result)) {
            return null;
        }
        $row = Database::fetch_array($result);

        return $row['dokeos_folder'];
    }

    /**
     * Get language matching isocode.
     *
     * @param string $isocode The language isocode (en, es, fr, zh-TW, etc)
     *
     * @return mixed English name of the matching language, or false if no active language could be found
     */
    public static function getLanguageFromIsocode($isocode)
    {
        $isocode = Database::escape_string($isocode);
        $adminLanguagesTable = Database::get_main_table(TABLE_MAIN_LANGUAGE);
        // select language - if case several languages match, get the last (more recent) one
        $sql = "SELECT english_name
                FROM ".$adminLanguagesTable."
                WHERE
                    isocode ='$isocode' AND
                    available = 1
                ORDER BY id
                DESC LIMIT 1";
        $res = Database::query($sql);
        if (Database::num_rows($res) < 1) {
            return false;
        }
        $row = Database::fetch_assoc($res);

        return $row['english_name'];
    }

    /**
     * Get best language in browser preferences.
     *
     * @param string $preferences The browser-configured language preferences (e.g. "en,es;q=0.7;en-us;q=0.3", etc)
     *
     * @return mixed English name of the matching language, or false if no active language could be found
     */
    public static function getLanguageFromBrowserPreference($preferences)
    {
        if (empty($preferences)) {
            return false;
        }

        $preferencesArray = explode(',', $preferences);

        if (count($preferencesArray) > 0) {
            foreach ($preferencesArray as $pref) {
                $s = strpos($pref, ';');
                if ($s >= 2) {
                    $code = substr($pref, 0, $s);
                } else {
                    $code = $pref;
                }
                $name = self::getLanguageFromIsocode($code);

                if (false !== $name) {
                    return $name;
                }
            }
        }

        return false;
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
