<?php

/* For licensing terms, see /license.txt */

/**
 * Dictionary plugin.
 */
class DictionaryPlugin extends Plugin
{
    public const TABLE_DICTIONARY = 'plugin_dictionary';
    public const PLUGIN_NAME = 'Dictionary';
    public const DEFAULT_REGION_LIMIT = 5;

    protected function __construct()
    {
        parent::__construct('1.1', 'Julio Montoya');

        $this->isAdminPlugin = true;
    }

    public static function create(): self
    {
        static $instance = null;

        return $instance ??= new self();
    }

    public function get_info(): array
    {
        $info = parent::get_info();
        $info['supports_regions'] = false;

        return $info;
    }

    public function getDictionaryPageUrl(array $params = []): string
    {
        $url = api_get_path(WEB_PATH).'plugin/'.self::PLUGIN_NAME.'/index.php';

        if ([] !== $params) {
            $url .= '?'.http_build_query($params);
        }

        return $url;
    }

    public function getAdminUrl()
    {
        return api_get_path(WEB_PLUGIN_PATH).self::PLUGIN_NAME.'/admin.php';
    }

    public function install(): void
    {
        $this->ensureTableExists();
    }

    public function uninstall(): void
    {
        Database::query('DROP TABLE IF EXISTS '.self::TABLE_DICTIONARY);
    }

    public function renderRegion($region): string
    {
        return '';
    }

    public function renderBlock(): string
    {
        $terms = $this->getTerms('', self::DEFAULT_REGION_LIMIT);

        $html = '<section class="rounded-2xl border border-gray-20 bg-white p-4 shadow-sm" data-dictionary-region-block="1">';
        $html .= '<div class="mb-4">';
        $html .= '<p class="text-xs font-semibold uppercase tracking-wide text-blue-700">'.$this->escape($this->get_lang('Dictionary')).'</p>';
        $html .= '<h2 class="mt-1 text-xl font-bold text-gray-90">'.$this->escape($this->get_title()).'</h2>';
        $html .= '</div>';

        $html .= $this->renderSearchForm('region');

        if ([] === $terms) {
            $html .= '<div class="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800">';
            $html .= $this->escape($this->get_lang('NoTermsAvailable'));
            $html .= '</div>';
            $html .= '</section>';

            return $html;
        }

        $html .= '<div class="mt-4 space-y-3">';

        foreach ($terms as $term) {
            $html .= $this->renderTermPreview($term);
        }

        $html .= '</div>';
        $html .= '<div class="mt-4">';
        $html .= '<a class="inline-flex items-center gap-2 text-sm font-semibold text-blue-700 hover:underline" href="'
            .$this->escape($this->getDictionaryPageUrl()).'">';
        $html .= '<span class="mdi mdi-book-open-page-variant ch-tool-icon" aria-hidden="true"></span>';
        $html .= $this->escape($this->get_lang('ViewFullDictionary'));
        $html .= '</a>';
        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    public function renderFullPage(string $keyword = ''): string
    {
        $this->ensureTableExists();

        $terms = $this->getTerms($keyword);

        $html = '
            <style>
                body.dictionary-full-page [data-dictionary-region-block="1"] {
                    display: none !important;
                }
            </style>
            <script>
                document.body.classList.add("dictionary-full-page");
            </script>';

        $html .= '<div class="space-y-6">';
        $html .= '<section class="rounded-2xl border border-gray-20 bg-white p-6 shadow-sm">';
        $html .= '<div class="mb-6">';
        $html .= '<p class="text-xs font-semibold uppercase tracking-wide text-blue-700">'.$this->escape($this->get_lang('Dictionary')).'</p>';
        $html .= '<h1 class="mt-1 text-2xl font-bold text-gray-90">'.$this->escape($this->get_title()).'</h1>';
        $html .= '<p class="mt-2 text-sm text-gray-50">'.$this->escape($this->get_comment()).'</p>';
        $html .= '</div>';
        $html .= $this->renderSearchForm('full', $keyword);
        $html .= '</section>';

        $html .= '<section class="rounded-2xl border border-gray-20 bg-white p-6 shadow-sm">';
        $html .= '<div class="mb-4 flex items-center justify-between gap-4">';
        $html .= '<h2 class="text-xl font-semibold text-gray-90">'.$this->escape($this->get_lang('Terms')).'</h2>';
        $html .= '<span class="rounded-full bg-blue-100 px-3 py-1 text-sm font-semibold text-blue-700">'.count($terms).'</span>';
        $html .= '</div>';

        if ([] === $terms) {
            $html .= '<div class="rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800">';
            $html .= $this->escape($this->get_lang('NoTermsFound'));
            $html .= '</div>';
            $html .= '</section></div>';

            return $html;
        }

        $html .= '<div class="overflow-x-auto">';
        $html .= '<table class="min-w-full divide-y divide-gray-20 text-sm">';
        $html .= '<thead class="bg-gray-10">';
        $html .= '<tr>';
        $html .= '<th class="px-4 py-3 text-left font-semibold text-gray-70">'.$this->escape($this->get_lang('Term')).'</th>';
        $html .= '<th class="px-4 py-3 text-left font-semibold text-gray-70">'.$this->escape($this->get_lang('Definition')).'</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody class="divide-y divide-gray-20 bg-white">';

        foreach ($terms as $term) {
            $html .= '<tr class="hover:bg-gray-10">';
            $html .= '<td class="px-4 py-3 align-top font-semibold text-gray-90">'.$this->escape((string) $term['term']).'</td>';
            $html .= '<td class="px-4 py-3 align-top text-gray-70">'.nl2br($this->escape((string) $term['definition'])).'</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';
        $html .= '</section>';
        $html .= '</div>';

        return $html;
    }

    public function renderSearchForm(string $context, string $keyword = ''): string
    {
        $inputId = 'dictionary-search-'.$context;

        $html = '<form class="flex flex-col gap-3 sm:flex-row sm:items-end" method="get" action="'
            .$this->escape($this->getDictionaryPageUrl()).'">';
        $html .= '<div class="flex-1">';
        $html .= '<label class="mb-1 block text-sm font-semibold text-gray-70" for="'.$this->escape($inputId).'">'
            .$this->escape($this->get_lang('SearchTerm')).'</label>';
        $html .= '<input id="'.$this->escape($inputId).'" name="q" value="'.$this->escape($keyword).'"';
        $html .= ' class="w-full rounded-lg border border-gray-30 px-3 py-2 text-sm text-gray-90"';
        $html .= ' placeholder="'.$this->escape($this->get_lang('SearchPlaceholder')).'">';
        $html .= '</div>';
        $html .= '<button type="submit" class="btn btn--primary inline-flex items-center gap-2">';
        $html .= '<span class="mdi mdi-magnify ch-tool-icon" aria-hidden="true"></span>';
        $html .= $this->escape($this->get_lang('Search'));
        $html .= '</button>';

        if ('' !== $keyword) {
            $html .= '<a class="btn btn--plain inline-flex items-center gap-2" href="'.$this->escape($this->getDictionaryPageUrl()).'">';
            $html .= '<span class="mdi mdi-close ch-tool-icon" aria-hidden="true"></span>';
            $html .= $this->escape($this->get_lang('ClearSearch'));
            $html .= '</a>';
        }

        $html .= '</form>';

        return $html;
    }

    public function getTerms(string $keyword = '', int $limit = 0): array
    {
        $this->ensureTableExists();

        $conditions = [];

        if ('' !== trim($keyword)) {
            $keyword = Database::escape_string(trim($keyword));
            $conditions[] = "(term LIKE '%$keyword%' OR definition LIKE '%$keyword%')";
        }

        $where = [] === $conditions ? '' : ' WHERE '.implode(' AND ', $conditions);
        $sql = 'SELECT id, term, definition FROM '.self::TABLE_DICTIONARY.$where.' ORDER BY term';

        if ($limit > 0) {
            $sql .= ' LIMIT '.(int) $limit;
        }

        $result = Database::query($sql);

        if (!$result) {
            return [];
        }

        return Database::store_result($result, 'ASSOC');
    }

    public function getTermById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $this->ensureTableExists();

        $sql = 'SELECT id, term, definition FROM '.self::TABLE_DICTIONARY.' WHERE id = '.(int) $id.' LIMIT 1';
        $result = Database::query($sql);
        $term = Database::fetch_assoc($result);

        return empty($term) ? null : $term;
    }

    public function addTerm(string $term, string $definition): bool
    {
        $this->ensureTableExists();

        $term = trim(Security::remove_XSS($term));
        $definition = trim(Security::remove_XSS($definition));

        if ('' === $term || '' === $definition) {
            return false;
        }

        return (bool) Database::insert(
            self::TABLE_DICTIONARY,
            [
                'term' => $term,
                'definition' => $definition,
            ]
        );
    }

    public function updateTerm(int $id, string $term, string $definition): bool
    {
        $this->ensureTableExists();

        if ($id <= 0) {
            return false;
        }

        $term = trim(Security::remove_XSS($term));
        $definition = trim(Security::remove_XSS($definition));

        if ('' === $term || '' === $definition) {
            return false;
        }

        return (bool) Database::update(
            self::TABLE_DICTIONARY,
            [
                'term' => $term,
                'definition' => $definition,
            ],
            ['id = ?' => $id]
        );
    }

    public function deleteTerm(int $id): bool
    {
        $this->ensureTableExists();

        if ($id <= 0) {
            return false;
        }

        return (bool) Database::delete(self::TABLE_DICTIONARY, ['id = ?' => $id]);
    }

    public function ensureTableExists(): void
    {
        $sql = 'CREATE TABLE IF NOT EXISTS '.self::TABLE_DICTIONARY.' (
            id INT NOT NULL AUTO_INCREMENT,
            term VARCHAR(255) NOT NULL,
            definition LONGTEXT NOT NULL,
            PRIMARY KEY (id),
            INDEX idx_plugin_dictionary_term (term)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';

        Database::query($sql);
    }

    public function isCurrentDictionaryPage(): bool
    {
        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        $phpSelf = (string) ($_SERVER['PHP_SELF'] ?? '');
        $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '');

        $pluginPath = '/plugin/'.self::PLUGIN_NAME.'/';

        return str_contains($scriptName, $pluginPath)
            || str_contains($phpSelf, $pluginPath)
            || str_contains($requestUri, $pluginPath);
    }

    private function renderTermPreview(array $term): string
    {
        $definition = (string) ($term['definition'] ?? '');
        $definition = trim(strip_tags($definition));

        if (api_strlen($definition) > 160) {
            $definition = api_substr($definition, 0, 157).'...';
        }

        return '
            <article class="rounded-xl border border-gray-20 bg-gray-10 p-4">
                <h3 class="text-base font-semibold text-gray-90">'.$this->escape((string) ($term['term'] ?? '')).'</h3>
                <p class="mt-2 text-sm text-gray-70">'.$this->escape($definition).'</p>
            </article>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
